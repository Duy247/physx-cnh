<?php
declare(strict_types=1);

$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$candidate = realpath(__DIR__ . '/..' . $path);
$root = realpath(__DIR__ . '/..');
if ($candidate !== false && $root !== false && str_starts_with($candidate, $root . DIRECTORY_SEPARATOR) && is_file($candidate)) {
    return false;
}
require $root . '/index.php';
