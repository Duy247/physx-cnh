<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && $method !== 'POST') {
    header('Allow: GET, POST');
    respond(405, ['error' => 'Method not allowed']);
}

$countFile = __DIR__ . DIRECTORY_SEPARATOR . 'visit.txt';
$handle = @fopen($countFile, 'c+');
if ($handle === false) {
    respond(500, ['error' => 'Counter storage is unavailable']);
}

$lockType = $method === 'POST' ? LOCK_EX : LOCK_SH;
if (!flock($handle, $lockType)) {
    fclose($handle);
    respond(500, ['error' => 'Unable to lock counter storage']);
}

rewind($handle);
$rawCount = stream_get_contents($handle);
$rawCount = $rawCount === false ? '' : trim($rawCount);

if (!preg_match('/^\d+$/', $rawCount)) {
    flock($handle, LOCK_UN);
    fclose($handle);
    respond(500, ['error' => 'Counter storage contains invalid data']);
}

$count = (int) $rawCount;

if ($method === 'POST') {
    if ($count === PHP_INT_MAX) {
        flock($handle, LOCK_UN);
        fclose($handle);
        respond(500, ['error' => 'Counter has reached its maximum value']);
    }

    $count++;
    rewind($handle);

    if (!ftruncate($handle, 0) || fwrite($handle, (string) $count) === false || !fflush($handle)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        respond(500, ['error' => 'Unable to update counter storage']);
    }
}

flock($handle, LOCK_UN);
fclose($handle);

respond(200, ['count' => $count]);
