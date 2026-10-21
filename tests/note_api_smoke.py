#!/usr/bin/env python3
"""Exercise the real PHP note API using curl and disposable storage."""
import argparse
import concurrent.futures
import hashlib
import json
import os
from pathlib import Path
import shutil
import signal
import socket
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[1]
LIMIT = 5 * 1024 * 1024
CLI_ADAPTER = r'''<?php
// Test-only transport adapter: feed request bytes to php://input under CLI.
$body = stream_get_contents(STDIN);
class InputAdapter {
    public $context;
    private $offset = 0;
    public function stream_open($path, $mode, $options, &$opened_path) { return $path === 'php://input'; }
    public function stream_read($count) { global $body; $value = substr($body, $this->offset, $count); $this->offset += strlen($value); return $value; }
    public function stream_eof() { global $body; return $this->offset >= strlen($body); }
    public function stream_stat() { return []; }
}
stream_wrapper_unregister('php');
stream_wrapper_register('php', InputAdapter::class);
$_SERVER['REQUEST_METHOD'] = $argv[2];
$_SERVER['CONTENT_TYPE'] = $argv[3];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
register_shutdown_function(function () { echo "\n", (http_response_code() ?: 200); });
require $argv[1];
'''



def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--cli', action='store_true', help='Use a CLI input adapter when sockets are blocked; does not test HTTP/curl')
    cli = parser.parse_args().cli
    if cli:
        print('CLI transport adapter only: no HTTP server or curl; timings include PHP process startup')
    for path in sorted((ROOT / 'note').rglob('*.php')):
        subprocess.run(['php', '-l', str(path)], check=True)
    with tempfile.TemporaryDirectory(prefix='physx-note-smoke-') as tmp:
        root = Path(tmp)
        note = root / 'note'
        note.mkdir()
        for name in ['api.php', 'index.php', '.user.ini']:
            shutil.copy2(ROOT / 'note' / name, note / name)
        server = None
        if cli:
            adapter = root / 'cli-adapter.php'
            adapter.write_text(CLI_ADAPTER)
        else:
            with socket.socket() as sock:
                sock.bind(('127.0.0.1', 0))
                port = sock.getsockname()[1]
            url = f'http://127.0.0.1:{port}/note/api.php'
            log = (root / 'php.log').open('w+')
            env = dict(os.environ, PHP_CLI_SERVER_WORKERS='4')
            # CLI server does not load .user.ini automatically; use the shipped settings.
            server = subprocess.Popen(
                ['php', '-c', str(note / '.user.ini'), '-S', f'127.0.0.1:{port}', '-t', str(root)],
                stdout=log, stderr=log, env=env, start_new_session=True,
            )

        def request(method='GET', body=None, content_type='application/json'):
            if cli:
                start = time.perf_counter()
                result = subprocess.run(['php', '-c', str(note / '.user.ini'), str(adapter),
                    str(note / 'api.php'), method, content_type], input=body or b'',
                    capture_output=True, check=True)
                assert not result.stderr, result.stderr.decode()
                payload, status = result.stdout.rsplit(b'\n', 1)
                return int(status), json.loads(payload), time.perf_counter() - start
            # Input and output stay on stdin/stdout: these are JSON API calls,
            # never file upload/download endpoints.
            args = ['curl', '--silent', '--show-error', '--max-time', '30',
                    '--write-out', '\n%{http_code} %{time_total}', '-X', method, url]
            if body is not None:
                args += ['-H', f'Content-Type: {content_type}', '--data-binary', '@-']
            result = subprocess.run(args, input=body, capture_output=True, check=True)
            payload, metrics = result.stdout.rsplit(b'\n', 1)
            status, duration = metrics.decode().split()
            return int(status), json.loads(payload), float(duration)

        def encoded(text, version):
            return json.dumps({'text': text, 'base_version': version}).encode()

        try:
            if not cli:
                for _ in range(100):
                    try:
                        with socket.create_connection(('127.0.0.1', port), timeout=.1):
                            break
                    except OSError:
                        if server.poll() is not None:
                            raise RuntimeError('PHP server exited before startup')
                        time.sleep(.05)
                else:
                    raise RuntimeError('PHP server did not start')

            status, initial, _ = request()
            assert status == 200 and initial == {'text': '', 'version': 0, 'updated_at': None}
            print('PASS GET: empty note, version 0')
            text = 'Hello <script>alert(1)</script> — tiếng Việt\r\n'
            status, saved, _ = request('PUT', encoded(text, 0))
            assert status == 200 and saved['text'] == text and saved['version'] == 1 and saved['updated_at']
            assert request()[1] == saved
            print('PASS PUT save and GET: literal text, Unicode, CRLF, version 1')
            status, stale, _ = request('POST', encoded('stale', 0))
            assert status == 409 and all(stale[key] == saved[key] for key in saved)
            assert request()[1] == saved
            print('PASS stale POST: 409 with latest snapshot; note unchanged')
            status, _, _ = request('PUT', encoded('x' * (LIMIT + 1), 1))
            assert status == 413 and request()[1] == saved
            print('PASS oversize: 5 MiB + 1 byte returns 413; note unchanged')

            # Exactly 50 bytes per line, including newline: 3,000,000 bytes.
            large = ''.join(f'{number:06d} ' + 'shared canvas line '.ljust(42, '.') + '\n'
                            for number in range(60000))
            original = large.encode('utf-8')
            assert len(original) == 3000000 and large.count('\n') == 60000
            start = time.perf_counter()
            status, large_saved, put_time = request('PUT', encoded(large, 1))
            assert status == 200 and large_saved['text'].encode() == original and large_saved['version'] == 2
            status, returned, get_time = request()
            assert status == 200 and returned['text'].encode() == original and returned == large_saved
            elapsed = time.perf_counter() - start
            digest = hashlib.sha256(original).hexdigest()
            print(f'PASS 60,000-line single PUT/GET: {len(original):,} bytes, byte-identical; '
                  f'PUT {put_time:.6f}s, GET {get_time:.6f}s, '
                  f'round trip incl. JSON/{"CLI" if cli else "curl"}/assertions {elapsed:.6f}s; SHA-256 {digest}')

            status, boundary, _ = request('POST', encoded('b' * LIMIT, 2))
            assert status == 200 and boundary['version'] == 3 and len(boundary['text']) == LIMIT
            assert request()[1] == boundary
            print('PASS exact 5 MiB boundary: POST and GET')
            # Worst-case JSON expansion (six bytes per decoded byte).
            status, escaped, escaped_time = request('POST', encoded('\x00' * LIMIT, 3))
            assert status == 200 and escaped['version'] == 4 and escaped['text'] == '\x00' * LIMIT
            assert request()[1] == escaped
            print(f'PASS escaped 5 MiB POST: ~30 MiB JSON body accepted ({escaped_time:.6f}s)')

            assert request('PUT', b'{}', 'text/plain')[0] == 415
            assert request('PUT', b'{')[0] == 400
            assert request('PUT', b'{"text":"x","base_version":"4"}')[0] == 400
            assert request('DELETE')[0] == 405
            print('PASS content type 415, malformed JSON/invalid version 400, method 405')

            # Two callers submit the same base version concurrently. Only one wins.
            with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
                futures = [pool.submit(request, 'PUT', encoded(value, 4)) for value in ['writer A', 'writer B']]
                results = [future.result() for future in futures]
            assert sorted(result[0] for result in results) == [200, 409]
            winner = next(result[1] for result in results if result[0] == 200)
            loser = next(result[1] for result in results if result[0] == 409)
            assert winner['version'] == 5 and all(loser[key] == winner[key] for key in winner)
            assert request()[1] == winner
            print('PASS concurrent API compare-and-swap: exactly one 200 and one 409, version 5')

            for _ in range(31):
                status, _, _ = request('PUT', encoded('rate test', 0))
                if status == 429:
                    break
                assert status == 409
            else:
                raise AssertionError('Rate limit never triggered')
            assert request()[1] == winner
            print('PASS per-IP save rate limit: 429; GET remains available and unchanged')
            if not cli:
                log.flush()
                log.seek(0)
                server_log = log.read()
                assert 'PHP Warning' not in server_log and 'PHP Fatal' not in server_log, server_log
            print('PASS PHP diagnostics: no warnings or fatal errors')
        finally:
            if server is not None:
                os.killpg(server.pid, signal.SIGTERM)
                server.wait(timeout=5)
                log.close()



if __name__ == '__main__':
    main()
