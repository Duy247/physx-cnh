<?php
declare(strict_types=1);

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function reply(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

// Every reader and writer uses this stable lock, including journal recovery.
function atomicFile(string $path, string $contents): void
{
    $temp = tempnam(dirname($path), '.write-');
    if ($temp === false) {
        throw new RuntimeException('Cannot create temporary file');
    }
    try {
        if (file_put_contents($temp, $contents) !== strlen($contents) || !rename($temp, $path)) {
            throw new RuntimeException('Cannot write storage');
        }
    } finally {
        if (is_file($temp)) {
            unlink($temp);
        }
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'PUT', 'POST'], true)) {
    header('Allow: GET, PUT, POST');
    reply(405, ['error' => 'Method not allowed']);
}
const MAX_TEXT_BYTES = 5 * 1024 * 1024;
// Accommodate fully escaped JSON, plus a small envelope.
const MAX_BODY_BYTES = MAX_TEXT_BYTES * 6 + 4096;

$input = null;
if ($method !== 'GET') {
    if (!preg_match('/^application\/json(?:\s*;\s*charset\s*=\s*"?utf-8"?)?\s*$/i', $_SERVER['CONTENT_TYPE'] ?? '')) {
        reply(415, ['error' => 'Use application/json with UTF-8']);
    }
    // JSON escaping can expand each text byte to six bytes.
    $raw = file_get_contents('php://input', false, null, 0, MAX_BODY_BYTES + 1);
    if ($raw === false || strlen($raw) > MAX_BODY_BYTES) {
        reply(413, ['error' => 'Request too large']);
    }
    try {
        $input = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        reply(400, ['error' => 'Invalid JSON']);
    }
    if (!is_array($input) || !isset($input['text'], $input['base_version']) || !is_string($input['text']) || !is_int($input['base_version']) || $input['base_version'] < 0) {
        reply(400, ['error' => 'Expected text string and nonnegative integer base_version']);
    }
    if (strlen($input['text']) > MAX_TEXT_BYTES) {
        reply(413, ['error' => 'Text exceeds 5 MB (5242880 UTF-8 bytes)']);
    }
}

try {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create storage');
    }
    $lock = fopen($dir . '/note.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Cannot lock storage');
    }
    $journal = $dir . '/pending.json';
    if (is_file($journal)) {
        $pending = json_decode(file_get_contents($journal), true, 8, JSON_THROW_ON_ERROR);
        atomicFile($dir . '/note.txt', $pending['text']);
        atomicFile($dir . '/meta.json', json_encode(['version' => $pending['version'], 'updated_at' => $pending['updated_at']], JSON_THROW_ON_ERROR));
        if (!unlink($journal)) {
            throw new RuntimeException('Cannot finish recovery');
        }
    }
    if (!is_file($dir . '/meta.json') && !is_file($dir . '/note.txt')) {
        atomicFile($journal, json_encode(['text' => '', 'version' => 0, 'updated_at' => null], JSON_THROW_ON_ERROR));
        atomicFile($dir . '/note.txt', '');
        atomicFile($dir . '/meta.json', '{"version":0,"updated_at":null}');
        unlink($journal);
    }
    $meta = json_decode(file_get_contents($dir . '/meta.json'), true, 8, JSON_THROW_ON_ERROR);
    $text = file_get_contents($dir . '/note.txt');
    if ($text === false || !is_int($meta['version'] ?? null) || !array_key_exists('updated_at', $meta)) {
        throw new RuntimeException('Invalid storage');
    }
    $current = ['text' => $text, 'version' => $meta['version'], 'updated_at' => $meta['updated_at']];
    if ($method === 'GET') {
        reply(200, $current);
    }
    // Use the connection address; untrusted forwarded headers cannot bypass this limit.
    $ratePath = $dir . '/rate-' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown') . '.json';
    $now = time();
    $rate = is_file($ratePath) ? json_decode(file_get_contents($ratePath), true, 8, JSON_THROW_ON_ERROR) : [];
    $rate = array_values(array_filter($rate, static function ($time) use ($now) { return $time > $now - 60; }));
    if (count($rate) >= 30) {
        header('Retry-After: ' . max(1, 60 - ($now - $rate[0])));
        reply(429, ['error' => 'Too many saves; wait a minute']);
    }
    $rate[] = $now;
    atomicFile($ratePath, json_encode($rate, JSON_THROW_ON_ERROR));
    // Bound storage growth from old IP addresses without deleting live windows.
    foreach (glob($dir . '/rate-*.json') ?: [] as $path) {
        if (filemtime($path) < $now - 120) {
            unlink($path);
        }
    }
    // API compare-and-swap resolves conflicts; flock only makes this
    // read/compare/write transaction atomic across concurrent requests.
    if ($input['base_version'] !== $current['version']) {
        reply(409, array_merge(['error' => 'The note changed; reload or merge'], $current));
    }
    if ($current['version'] === PHP_INT_MAX) {
        throw new RuntimeException('Version limit reached');
    }
    $current = ['text' => $input['text'], 'version' => $current['version'] + 1, 'updated_at' => gmdate('c')];
    atomicFile($journal, json_encode($current, JSON_THROW_ON_ERROR));
    atomicFile($dir . '/note.txt', $current['text']);
    atomicFile($dir . '/meta.json', json_encode(['version' => $current['version'], 'updated_at' => $current['updated_at']], JSON_THROW_ON_ERROR));
    if (!unlink($journal)) {
        throw new RuntimeException('Cannot finish save');
    }
    reply(200, $current);
} catch (Throwable $e) {
    error_log('Note storage: ' . $e->getMessage());
    reply(500, ['error' => 'Note storage is unavailable; please try again']);
}
