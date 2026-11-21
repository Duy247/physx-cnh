# Glasses update relay

The endpoint is `https://physx-cnh.com/api/glasses/v1/updates.php` after deployment
and configuration. It accepts HTTPS posts and supports short, cursor-based long
polling. Hostinger Web and Cloud hosting accepts outbound WebSocket connections
only, so publishing and receiving both use HTTPS on this origin.

## Host setup

Enable the PHP PDO SQLite extension on the host. Create a writable private
directory on the same host as PHP. Keep it outside the site document root.

Create `glasses-relay.php` one directory above the site root, typically next
to `public_html`. Give the site PHP account read access and keep it private.
The file returns:

```php
<?php
return [
    'storage_dir' => '/absolute/path/to/private/glasses-relay',
    'read_token_sha256' => 'READER_SHA256',
    'publish_token_sha256' => [
        'PUBLISHER_SHA256' => 'codex',
        'ANOTHER_PUBLISHER_SHA256' => 'ci',
    ],
];
```

Generate a different token for the companion and each publisher. Share the
token with its client and put only its SHA-256 hash in the config.

```sh
php -r '$token = bin2hex(random_bytes(32)); echo $token, PHP_EOL, hash("sha256", $token), PHP_EOL;'
```

Each publish token is bound to a source name by the config. Remove its entry to
revoke the token. Keep the config and storage permissions limited to the PHP
account. The relay returns 503 until configured. Test that the production host
forwards the `Authorization` header to PHP before distributing client tokens.

## Publish

Use an `Authorization: Bearer <publish-token>` header. A sender can publish a
single status or up to 24 updates in one atomic batch.

```sh
curl --fail-with-body "$RELAY_URL" \
  -H "Authorization: Bearer $GLASSES_PUBLISH_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"job":"build-72","event_id":"build-72-tests","state":"running","text":"Running tests","progress":65}'
```

The server assigns sequential IDs and returns `{"ids":[123]}`. A status has
`job`, `text`, `state` (`running`, `done`, `failed`, or `needs_input`),
and optional integer `progress` (0–100). For idempotent retries, provide a stable
`event_id` per event, unique across jobs for that publisher. Deduplication
applies while the event is in the replay window.

For animation, publish ordered text frames in a batch so the receiver can play
them locally without making an HTTP request per frame. `hold_ms` specifies how
long the client should hold a frame and ranges from 50 to 10,000 ms.

```json
{
  "updates": [
    {"job":"build-72","kind":"frame","text":"TESTING\n[##------]","hold_ms":100},
    {"job":"build-72","kind":"frame","text":"TESTING\n[###-----]","hold_ms":100}
  ]
}
```

## Receive

Use `Authorization: Bearer <read-token>`. Start with a snapshot, then pass
the returned cursor in each subsequent GET after processing its events.

```sh
curl --fail-with-body "$RELAY_URL" -H "Authorization: Bearer $GLASSES_READ_TOKEN"
curl --fail-with-body "$RELAY_URL?after=123&wait_ms=1500" \
  -H "Authorization: Bearer $GLASSES_READ_TOKEN"
```

Responses contain `cursor`, `reset`, `more`, and `updates`. An update includes
`id`, `source`, `job`, `kind`, `created_ms`, and the original status or frame
fields. A snapshot includes the latest entries by source, job, and kind. On
`reset: true`, replace the local state with the returned snapshot. If
`more: true`, fetch the next batch immediately. For animation, enqueue frames
in ID order, and discard stale frames on a reset rather than animating a
snapshot.

The relay retains 512 recent events and 128 current entries. Up to 96 events
can be read per response, and publishing is limited to 120 new events per
second across senders. A long poll waits at most two seconds and one receiver
can wait at a time. Follow `Retry-After` on 429 and 503 responses.

The phone still needs to connect to the glasses over Bluetooth. Actual
animation speed depends on the host, network, and Bluetooth display update
rate. Start with a buffered 4 frames per second on the glasses.
