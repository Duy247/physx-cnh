<?php
declare(strict_types=1);

$url = isset($_GET['url']) ? trim((string) $_GET['url']) : '';
$parts = $url !== '' ? parse_url($url) : false;

if ($parts === false || !isset($parts['path'])) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid PDF URL.';
    exit;
}

if (isset($parts['host'])) {
    $host = strtolower($parts['host']);
    if ($host !== 'physx-cnh.com' && $host !== 'www.physx-cnh.com') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'External PDF URLs are not allowed.';
        exit;
    }
}

$decodedPath = rawurldecode($parts['path']);
if (
    strpos($decodedPath, '/physics/') !== 0 ||
    strpos($decodedPath, '..') !== false ||
    strtolower(pathinfo($decodedPath, PATHINFO_EXTENSION)) !== 'pdf'
) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid PDF path.';
    exit;
}

$encodedPath = implode('/', array_map('rawurlencode', explode('/', $decodedPath)));
header('Cache-Control: no-store');
header('Location: ' . $encodedPath, true, 302);
exit;
