<?php
declare(strict_types=1);

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit('Method not allowed.');
}

$requestedName = (string) ($_GET['file'] ?? '');
if ($requestedName === '' || basename($requestedName) !== $requestedName || str_contains($requestedName, "\0")) {
    http_response_code(400);
    exit('Invalid filename.');
}

$downloadDirectory = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'downloads');
$filePath = $downloadDirectory === false
    ? false
    : realpath($downloadDirectory . DIRECTORY_SEPARATOR . $requestedName);

if (
    $downloadDirectory === false
    || $filePath === false
    || !str_starts_with($filePath, $downloadDirectory . DIRECTORY_SEPARATOR)
    || !is_file($filePath)
    || !is_readable($filePath)
) {
    http_response_code(404);
    exit('File not found.');
}

$fileSize = filesize($filePath);
if ($fileSize === false) {
    http_response_code(500);
    exit('File size could not be determined.');
}

$start = 0;
$end = max(0, $fileSize - 1);
$rangeHeader = trim((string) ($_SERVER['HTTP_RANGE'] ?? ''));

if ($rangeHeader !== '') {
    if (!preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $matches)) {
        header('Content-Range: bytes */' . $fileSize);
        http_response_code(416);
        exit;
    }

    $rangeStart = $matches[1];
    $rangeEnd = $matches[2];

    if ($rangeStart === '' && $rangeEnd === '') {
        header('Content-Range: bytes */' . $fileSize);
        http_response_code(416);
        exit;
    }

    if ($rangeStart === '') {
        $suffixLength = (int) $rangeEnd;
        if ($suffixLength < 1) {
            header('Content-Range: bytes */' . $fileSize);
            http_response_code(416);
            exit;
        }
        $start = max(0, $fileSize - $suffixLength);
    } else {
        $start = (int) $rangeStart;
        if ($rangeEnd !== '') {
            $end = (int) $rangeEnd;
        }
    }

    if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
        header('Content-Range: bytes */' . $fileSize);
        http_response_code(416);
        exit;
    }

    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
}

$length = $end - $start + 1;
$fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', $requestedName) ?: 'download.bin';

header('Content-Type: application/octet-stream');
header(
    'Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' .
    rawurlencode($requestedName)
);
header('Accept-Ranges: bytes');
header('Content-Length: ' . $length);
header('Cache-Control: private, no-transform, max-age=0');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    exit;
}

set_time_limit(0);
$stream = fopen($filePath, 'rb');
if ($stream === false) {
    http_response_code(500);
    exit('File could not be opened.');
}

fseek($stream, $start);
$remaining = $length;

while ($remaining > 0 && !feof($stream)) {
    $chunk = fread($stream, min(65536, $remaining));
    if ($chunk === false || $chunk === '') {
        break;
    }

    echo $chunk;
    $remaining -= strlen($chunk);

    if (connection_aborted()) {
        break;
    }
    flush();
}

fclose($stream);
exit;
