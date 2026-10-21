# Shared public note

## Files and deployment

- `index.php`: responsive public textarea using plain JavaScript and existing site colors/CSS; 1.5-second autosave debounce, Save button, five-second polling, status messages, and conflict reload/manual merge controls. Note content is assigned only to textarea `.value`. The input handler only sets flags/status and resets a timer; UTF-8 encoding and JSON serialization run at save time, not on every keystroke. Spellcheck is disabled to reduce work on large notes.
- `api.php`: JSON-only GET/PUT/POST API, API-level compare-and-swap, file-backed rate limiting, atomic backing-store updates and interrupted-write recovery.
- `data/.htaccess`: `Require all denied` protects all backing-store files.
- `data/.gitignore`: ignores runtime contents while keeping directory configuration.
- `.user.ini`: `post_max_size=32M`, `memory_limit=256M` for PHP CGI/FPM/LSAPI configurations that load per-directory INI files.
- `note/.htaccess`: equivalent PHP limits for LiteSpeed/mod_php and denial of direct `.user.ini` access.
- Root `.htaccess`: routes `/note` and `/note/` to `note/index.php`.
- `tests/note_api_smoke.py` (repository root's tests directory): reproducible isolated PHP API tests, defaulting to a local multi-worker `php -S` server and curl; optional `--cli` transport adapter when sockets are unavailable. Temporary storage is deleted after the run, leaving the site's shared note untouched.

Requires PHP 8.1+ and PHP write permission on `note/data/`; no Node runtime. First use creates `note.txt`, `meta.json`, and a stable lock. Runtime contents also include per-IP rate windows and a temporary recovery journal. Inactive rate files are removed during saves. No physics files or PDFs were changed. Nothing was committed or pushed.

## API and conflict semantics

All communication is JSON API calls; there are no file upload/download endpoints or transfer protocol. Storage is only a backing store behind the API.

`GET /note/api.php` returns `{text, version, updated_at}`, initially `{"text":"","version":0,"updated_at":null}`. Subsequent timestamps are UTC ISO 8601.

`PUT` or `POST /note/api.php` requires `Content-Type: application/json` (optional UTF-8 charset) and `{"text":"Hello","base_version":0}`. The API performs compare-and-swap: it saves only when `base_version == current version`, increments the integer version once, and returns the saved snapshot. A mismatch returns 409 with the latest text/version/updated_at and does not alter the note. This version comparison is the conflict mechanism. `flock` only makes the backing-store read/compare/write transaction atomic across concurrent requests; it does not resolve edit conflicts. Every storage reader/writer takes the stable lock. Writes use temporary-file renames and a pending transaction journal, replayed before the next read if interrupted.

The text limit is **5 MiB = 5,242,880 UTF-8 bytes** (the brief's 5 MB), with 413 above it. JSON escaping can expand each byte to six bytes, so the API accepts at most 31,461,376 request bytes (six times the text limit plus 4096 bytes of envelope). PHP's configured 32 MiB body limit permits that JSON envelope, including POST. No line-count limit is imposed.

Other responses: 400 malformed JSON/invalid fields; 405 unsupported method; 415 incorrect content type; 429 rate limit with Retry-After; 500 unavailable storage. Responses are JSON with `Cache-Control: no-store`. Valid save attempts, including conflicts, are limited to 30 per rolling minute per connection IP (`REMOTE_ADDR`, never forwarded headers). Users behind a shared proxy can share the quota.

Manual merge: local edits remain in the canvas on conflict; the latest saved text appears read-only below. Edit the canvas and explicitly choose “Save merged canvas” to submit against that latest version, or “Load latest” to discard local edits. Another intervening save produces another 409. Unsaved edits remain only while the page is open, with a navigation warning.

## Actual verification results — 2026-10-21

Runtime: `/usr/bin/php`, PHP 8.4.26 CLI. These results supersede the earlier missing-PHP report.

- PASS: `php -l note/api.php` and `php -l note/index.php` (all new PHP files).
- PASS: shipped INI settings loaded with `php -c note/.user.ini` report `post_max_size=32M` and `memory_limit=256M`.
- PASS: `python3 tests/note_api_smoke.py --cli`, executing the actual, unchanged PHP API using a test-only `php://input` adapter and disposable storage. This adapter supplies method, content type and connection address, and captures PHP's response status; it does not test HTTP transport or headers.

| CLI API-code check | Observed result |
| --- | --- |
| Initial GET | Empty text, version 0, null timestamp |
| PUT then GET | Literal script-like text, Unicode and CRLF preserved; version 1 |
| Stale POST | 409 with latest snapshot; stored note unchanged |
| Oversized PUT | 5,242,881 bytes returned 413; stored note unchanged |
| Single PUT of 60,000 lines, then GET | 3,000,000 bytes, byte-identical in both save response and GET; version 2 |
| Exact 5 MiB POST then GET | Accepted, byte-identical; version 3 |
| Fully escaped 5 MiB POST then GET | Approximately 30 MiB JSON body accepted and preserved; version 4 |
| Concurrent requests with the same base version | Exactly one 200 and one 409; both report the winner's version 5 snapshot |
| Bad content type / malformed JSON / invalid version / unsupported method | 415 / 400 / 400 / 405 |
| Repeated save attempts | 429; GET remains available and note remains unchanged |
| PHP diagnostics | No warnings or fatal errors |

60,000-line timing from the final CLI run: PUT **0.059347 s**, GET **0.026692 s**, combined round trip including JSON encoding/decoding, process startup and assertions **0.096437 s**. These are local CLI adapter timings, **not HTTP/curl or hosting timings**. Each line is 50 bytes including its newline. SHA-256 of original and retrieved UTF-8 bytes:

```text
5c10628e161c25fc9911608840e50eedc57398918577a3aff87ec4ba3c84bf4a
```

- PASS: extracted client JavaScript passed `node --check`; mocked DOM/fetch checks passed initial load, save, literal text assignment, conflict preservation, explicit merge save, idle polling and reload. These are functional checks, not a browser responsiveness benchmark.
- PASS: `git diff --check`.
- **BLOCKED: full local HTTP/curl tests.** `python3 tests/note_api_smoke.py` failed at socket creation with `PermissionError: [Errno 1] Operation not permitted`. A direct `/usr/bin/php -S 127.0.0.1:8765 -t .` also failed to listen. This execution environment prohibits sockets, so PHP availability alone does not enable the requested HTTP run. No HTTP status/header, curl timing, or HTTP large-body result is claimed.

## Run the remaining HTTP checks

On a machine permitting localhost sockets, from the repository root:

```sh
python3 tests/note_api_smoke.py
```

This lints all note PHP files, starts an isolated four-worker PHP server with the shipped INI limits, uses curl for GET/save/stale-409/oversize-413 and all checks above, verifies the 60,000-line round trip byte-for-byte, prints PUT/GET/combined timings and SHA-256, and stops the server. The suite requires PHP, curl, Python 3 and Unix process groups. JSON request bytes go through curl stdin; no upload/download endpoint is used.

On LiteSpeed/Apache hosting, verify `/note` and `/note/` display the editor, `.user.ini` and all `/note/data/` files return 403, and PHP applies the per-directory limits. The local PHP development server does not interpret `.htaccess`; loading `.user.ini` explicitly via `-c` verifies the settings themselves, not their hosting discovery. Any upstream hosting request-body cap must also accommodate the allowed JSON body. A target-host check and browser performance check with a large pasted note remain outstanding.
