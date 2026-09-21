<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Web/CatalogSnapshot.php';
require_once __DIR__ . '/src/Web/View.php';
require_once __DIR__ . '/src/Web/App.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$catalog = new CatalogSnapshot(__DIR__ . '/physics/catalog/public-snapshot.json', __DIR__);
$view = new View(__DIR__ . '/templates');

try {
    (new App($catalog, $view))->run();
} catch (Throwable $error) {
    error_log($error->__toString());
    http_response_code(500);
    echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Lỗi máy chủ</title><body><h1>Không thể mở trang</h1><p>Vui lòng thử lại sau.</p></body></html>';
}
