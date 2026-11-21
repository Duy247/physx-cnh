<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');

function finish(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['result' => $message]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_SERVER['HTTPS'])
    || $_SERVER['HTTPS'] === 'off' || time() > 1795292790) {
    finish(404, 'unavailable');
}
$token = $_SERVER['HTTP_X_RELAY_SETUP'] ?? '';
if (!preg_match('/\A[a-f0-9]{64}\z/D', $token) || !hash_equals('595bac3b50ed5cf8721efa1283147827f397373ebb08bfef7f95f477295e829b', hash('sha256', $token))) {
    finish(404, 'unavailable');
}
$root = dirname(__DIR__, 3);
$parent = dirname($root);
$config = $parent . '/glasses-relay.php';
$storage = $parent . '/glasses-relay-private';
umask(0077);
if (is_file($config)) finish(409, 'already_configured');
if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) finish(503, 'pdo_sqlite_required');
if (!is_dir($storage) && !@mkdir($storage, 0700)) finish(503, 'private_directory_creation_failed');
if (realpath($storage) !== $storage || !is_writable($storage)) finish(503, 'private_storage_unavailable');
$content = "<?php\nreturn " . var_export([
    'storage_dir' => $storage,
    'read_token_sha256' => '0ad6d11e6db19d4ec5662635b5a40cdb62024cf3628a54910f87123cafdaf064',
    'publish_token_sha256' => ['f02cb311f670c7ff1a3f57cdcfc698794dfea538b24e0d15198444b5b565193a' => 'workspace'],
], true) . ";\n";
$temp = @tempnam($parent, 'relay-config-');
if ($temp === false || dirname($temp) !== $parent) finish(503, 'config_directory_unavailable');
try {
    if (file_put_contents($temp, $content) !== strlen($content) || !chmod($temp, 0600)) {
        finish(503, 'config_write_failed');
    }
    if (!@link($temp, $config)) {
        finish(is_file($config) ? 409 : 503, is_file($config) ? 'already_configured' : 'config_publish_failed');
    }
} finally {
    @unlink($temp);
}
finish(201, 'configured');
