<?php

declare(strict_types=1);

const MAX_BODY = 65536;
const MAX_BATCH = 24;
const MAX_REPLAY = 512;
const MAX_CURRENT = 128;
const MAX_EVENTS_PER_SECOND = 120;
const STATES = ['running', 'done', 'failed', 'needs_input'];

function jsonResponse(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function reject(int $status, string $error): never
{
    jsonResponse($status, ['error' => $error]);
}

function queryInteger(string $key, int $default, int $max): int
{
    $value = $_GET[$key] ?? (string) $default;
    if (!is_string($value) || !preg_match('/\A(?:0|[1-9][0-9]{0,18})\z/D', $value)) {
        reject(400, 'invalid_' . $key);
    }
    $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => $max]]);
    if ($number === false) {
        reject(400, 'invalid_' . $key);
    }
    return $number;
}

function identity(mixed $value): bool
{
    return is_string($value) && preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,63}\z/D', $value) === 1;
}

function loadUpdates(): array
{
    $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '', 2)[0]));
    if ($type !== 'application/json') {
        reject(415, 'expected_json');
    }
    $body = file_get_contents('php://input', false, null, 0, MAX_BODY + 1);
    if ($body === false || strlen($body) > MAX_BODY) {
        reject(413, 'payload_too_large');
    }
    try {
        $payload = json_decode($body, false, 12, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        reject(400, 'invalid_json');
    }
    $updates = $payload instanceof stdClass && property_exists($payload, 'updates') ? $payload->updates : [$payload];
    if (!is_array($updates) || count($updates) < 1 || count($updates) > MAX_BATCH) {
        reject(400, 'invalid_batch');
    }

    $result = [];
    foreach ($updates as $update) {
        if (!$update instanceof stdClass || !identity($update->job ?? null)) {
            reject(400, 'invalid_job');
        }
        $kind = $update->kind ?? 'status';
        $text = $update->text ?? null;
        if (!is_string($text) || trim($text) === '' || strlen($text) > 800
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $text)) {
            reject(400, 'invalid_text');
        }
        if (isset($update->event_id) && !identity($update->event_id)) {
            reject(400, 'invalid_event_id');
        }
        $data = ['text' => trim($text)];
        if ($kind === 'status') {
            if (!in_array($update->state ?? null, STATES, true)) {
                reject(400, 'invalid_state');
            }
            $data['state'] = $update->state;
            if (isset($update->progress)) {
                if (!is_int($update->progress) || $update->progress < 0 || $update->progress > 100) {
                    reject(400, 'invalid_progress');
                }
                $data['progress'] = $update->progress;
            }
        } elseif ($kind === 'frame') {
            $hold = $update->hold_ms ?? 100;
            if (!is_int($hold) || $hold < 50 || $hold > 10000) {
                reject(400, 'invalid_hold_ms');
            }
            $data['hold_ms'] = $hold;
        } else {
            reject(400, 'invalid_kind');
        }
        $result[] = [$update->job, $kind, $update->event_id ?? null, $data];
    }
    return $result;
}

function database(string $directory): PDO
{
    $db = new PDO('sqlite:' . $directory . '/updates.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('PRAGMA busy_timeout = 1500');
    if ((int) $db->query('PRAGMA user_version')->fetchColumn() === 0) {
        $db->exec('PRAGMA journal_mode = WAL');
        $db->exec('BEGIN IMMEDIATE');
        $db->exec('CREATE TABLE IF NOT EXISTS updates (
            id INTEGER PRIMARY KEY AUTOINCREMENT, source TEXT NOT NULL, job TEXT NOT NULL,
            kind TEXT NOT NULL, event_id TEXT, created_ms INTEGER NOT NULL, data TEXT NOT NULL,
            UNIQUE(source, event_id)
        )');
        $db->exec('CREATE INDEX IF NOT EXISTS update_time ON updates(created_ms)');
        $db->exec('CREATE TABLE IF NOT EXISTS current_updates (
            source TEXT NOT NULL, job TEXT NOT NULL, kind TEXT NOT NULL,
            id INTEGER NOT NULL UNIQUE, created_ms INTEGER NOT NULL, data TEXT NOT NULL,
            PRIMARY KEY(source, job, kind)
        )');
        $db->exec('PRAGMA user_version = 1');
        $db->exec('COMMIT');
    }
    return $db;
}

function publish(PDO $db, string $source, array $updates): array
{
    $db->exec('BEGIN IMMEDIATE');
    $now = (int) floor(microtime(true) * 1000);
    $recent = $db->prepare('SELECT COUNT(*) FROM updates WHERE created_ms > ?');
    $recent->execute([$now - 1000]);
    $recentCount = (int) $recent->fetchColumn();
    $recent->closeCursor();
    $lookup = $db->prepare('SELECT id FROM updates WHERE source = ? AND event_id = ?');
    $insert = $db->prepare('INSERT INTO updates(source, job, kind, event_id, created_ms, data) VALUES (?, ?, ?, ?, ?, ?)');
    $current = $db->prepare('INSERT INTO current_updates(source, job, kind, id, created_ms, data)
        VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT(source, job, kind) DO UPDATE SET
        id=excluded.id, created_ms=excluded.created_ms, data=excluded.data');
    $ids = [];
    foreach ($updates as [$job, $kind, $eventId, $data]) {
        if ($eventId !== null) {
            $lookup->execute([$source, $eventId]);
            $prior = $lookup->fetchColumn();
            $lookup->closeCursor();
            if ($prior !== false) {
                $ids[] = (int) $prior;
                continue;
            }
        }
        if ($recentCount >= MAX_EVENTS_PER_SECOND) {
            $db->exec('ROLLBACK');
            header('Retry-After: 1');
            reject(429, 'publish_rate_limit');
        }
        $encoded = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $insert->execute([$source, $job, $kind, $eventId, $now, $encoded]);
        $id = (int) $db->lastInsertId();
        $current->execute([$source, $job, $kind, $id, $now, $encoded]);
        $ids[] = $id;
        $recentCount++;
    }
    $db->exec('DELETE FROM updates WHERE id <= (SELECT MAX(id) FROM updates) - ' . MAX_REPLAY);
    $db->exec('DELETE FROM current_updates WHERE id < (
        SELECT id FROM current_updates ORDER BY id DESC LIMIT 1 OFFSET ' . (MAX_CURRENT - 1) . ')');
    $db->exec('COMMIT');
    return ['ids' => $ids];
}

function readWindow(PDO $db, ?int $after, int $limit): array
{
    $db->beginTransaction();
    $range = $db->query('SELECT MIN(id) AS oldest, MAX(id) AS newest FROM updates')->fetch(PDO::FETCH_ASSOC);
    $newest = (int) $range['newest'];
    $reset = $after !== null && ($after > $newest || ($after < (int) $range['oldest'] - 1));
    if ($after === null || $reset) {
        $rows = $db->query('SELECT id, source, job, kind, created_ms, data FROM current_updates ORDER BY id')
            ->fetchAll(PDO::FETCH_ASSOC);
        $cursor = $newest;
    } else {
        $query = $db->prepare('SELECT id, source, job, kind, created_ms, data FROM updates
            WHERE id > :after ORDER BY id LIMIT :limit');
        $query->bindValue(':after', $after, PDO::PARAM_INT);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        $query->closeCursor();
        $cursor = $rows === [] ? $after : (int) $rows[count($rows) - 1]['id'];
    }
    $db->commit();
    foreach ($rows as &$row) {
        $data = json_decode($row['data'], true, 12, JSON_THROW_ON_ERROR);
        unset($row['data']);
        $row += $data;
    }
    unset($row);
    return ['cursor' => $cursor, 'reset' => $reset, 'more' => $cursor < $newest, 'updates' => $rows];
}

function receive(PDO $db, string $directory): array
{
    $after = isset($_GET['after']) ? queryInteger('after', 0, PHP_INT_MAX) : null;
    $limit = max(1, queryInteger('limit', 48, 96));
    $wait = queryInteger('wait_ms', 0, 2000);
    $result = readWindow($db, $after, $limit);
    if ($wait === 0 || $after === null || $result['reset'] || $result['updates'] !== []) {
        return $result;
    }

    $lock = fopen($directory . '/receiver.lock', 'c');
    if ($lock === false) {
        throw new RuntimeException('Unable to open receiver lock');
    }
    try {
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            header('Retry-After: 1');
            reject(429, 'receiver_already_waiting');
        }
        $deadline = hrtime(true) + $wait * 1_000_000;
        do {
            usleep(50000);
            $result = readWindow($db, $after, $limit);
            if ($result['reset'] || $result['updates'] !== []) {
                break;
            }
        } while (hrtime(true) < $deadline && !connection_aborted());
        return $result;
    } finally {
        fclose($lock);
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    reject(405, 'method_not_allowed');
}
if (PHP_SAPI !== 'cli-server' && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    reject(400, 'https_required');
}
$site = dirname(__DIR__, 3);
$configPath = dirname($site) . '/glasses-relay.php';
if (!is_file($configPath)) {
    reject(503, 'relay_not_configured');
}

try {
    $config = require $configPath;
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/\ABearer ([a-f0-9]{64})\z/iD', $auth, $match)) {
        header('WWW-Authenticate: Bearer');
        reject(401, 'invalid_token');
    }
    $hash = hash('sha256', $match[1]);
    $source = null;
    if ($method === 'GET') {
        if (!hash_equals($config['read_token_sha256'], $hash)) {
            reject(403, 'token_cannot_read');
        }
    } else {
        $source = $config['publish_token_sha256'][$hash] ?? null;
        if (!identity($source)) {
            reject(403, 'token_cannot_publish');
        }
    }

    $directory = realpath($config['storage_dir']);
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: $site;
    if ($directory === false || $directory === $documentRoot || str_starts_with($directory, $documentRoot . DIRECTORY_SEPARATOR)
        || $directory === $site || str_starts_with($directory, $site . DIRECTORY_SEPARATOR) || !is_writable($directory)) {
        reject(503, 'relay_storage_unavailable');
    }
    umask(0077);
    if ($method === 'POST') {
        $updates = loadUpdates();
        jsonResponse(200, publish(database($directory), $source, $updates));
    }
    jsonResponse(200, receive(database($directory), $directory));
} catch (Throwable $error) {
    if ($error instanceof PDOException && (int) ($error->errorInfo[1] ?? 0) === 5) {
        header('Retry-After: 1');
        reject(503, 'relay_busy');
    }
    error_log('Glasses relay error: ' . get_class($error));
    reject(503, 'relay_unavailable');
}
