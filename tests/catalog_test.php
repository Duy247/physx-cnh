<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/src/CatalogRepository.php';

/** @var array<string, array<string, mixed>> $catalogs */
$catalogs = require $root . '/config/catalogs.php';
$repository = new CatalogRepository($root . '/physics');
$requireFiles = getenv('CATALOG_SKIP_FILES') !== '1';
$expectedCounts = [
    'books-pre-vpho' => 25,
    'books-vpho-vn' => 28,
    'books-vpho-en' => 36,
    'materials-pho' => 59,
    'paper-sol-pho' => 24,
    'olympiads' => 2738,
    'magazines' => 135,
    'lessons' => 13,
];

$total = 0;
$seenFiles = [];
$irodovFound = false;
foreach ($catalogs as $route => $catalog) {
    $id = (string) $catalog['id'];
    check(isset($expectedCounts[$id]), 'Unexpected catalog id: ' . $id);
    check($route === $catalog['type'] . ':' . $catalog['level'], 'Route mismatch: ' . $route);
    $items = $repository->load((string) $catalog['manifest'], $requireFiles);
    check(count($items) === $expectedCounts[$id], $id . ' count changed unexpectedly.');
    $total += count($items);

    foreach ($items as $item) {
        $key = strtolower($item['file']);
        check(!isset($seenFiles[$key]), 'Duplicate file across catalogs: ' . $item['file']);
        $seenFiles[$key] = true;
        if ($item['file'] === 'books/irodov-vi.pdf') {
            $irodovFound = $item['author'] === 'Dịch: Lương Duyên Bình, Nguyễn Quang Hậu';
        }
    }
}

check($total === 3058, 'Expected 3058 catalog resources.');
check($irodovFound, 'The repaired Irodov record is missing.');
check(
    CatalogRepository::resourceUrl('books/Tài liệu 1.pdf') === '/physics/books/T%C3%A0i%20li%E1%BB%87u%201.pdf',
    'Resource URL encoding failed.'
);

$temporaryDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'physx-catalog-test-' . bin2hex(random_bytes(6));
if (!mkdir($temporaryDirectory, 0700, true) && !is_dir($temporaryDirectory)) {
    throw new RuntimeException('Cannot create test directory.');
}

try {
    $invalidManifest = $temporaryDirectory . DIRECTORY_SEPARATOR . 'invalid.json';
    file_put_contents($invalidManifest, json_encode([
        'version' => 1,
        'items' => [[
            'title' => '<script>alert(1)</script>',
            'file' => '../outside.pdf',
        ]],
    ], JSON_THROW_ON_ERROR));

    $failedSafely = false;
    try {
        $repository->load($invalidManifest, false);
    } catch (CatalogException) {
        $failedSafely = true;
    }
    check($failedSafely, 'Unsafe metadata was not rejected.');
} finally {
    @unlink($temporaryDirectory . DIRECTORY_SEPARATOR . 'invalid.json');
    @rmdir($temporaryDirectory);
}

fwrite(STDOUT, 'Catalog tests passed: 8 catalogs, 3058 resources.' . PHP_EOL);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, 'TEST FAILURE: ' . $message . PHP_EOL);
        exit(1);
    }
}
