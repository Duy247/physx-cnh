<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/src/Web/CatalogSnapshot.php';

$catalog = new CatalogSnapshot($root . '/physics/catalog/public-snapshot.json', $root);
$snapshot = $catalog->all();
check(($snapshot['counts']['published'] ?? 0) === 3045, 'Expected 3045 public records.');
check(($snapshot['counts']['pdf'] ?? 0) === 3045, 'Expected 3045 published PDFs.');
check(!isset($snapshot['counts']['lesson']), 'Lesson count must not be published.');
check(count($catalog->documents()) === 3045, 'Document array count does not match snapshot.');
check(count(array_filter($catalog->documents(), static fn (array $document): bool => ($document['kind'] ?? '') === 'lesson')) === 0, 'Lesson documents must not be public.');
check(count(array_filter($catalog->documents(), static fn (array $document): bool => !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($document['addedAt'] ?? '')))) === 0, 'Every public document needs a prepared addition date.');

$providers = array_unique(array_map(static fn (array $document): string => (string) ($document['delivery']['provider'] ?? ''), $catalog->documents()));
check($providers === ['hostinger'], 'Every public record must be served by Hostinger.');
check($catalog->document('1000-solved-problems-in-modern-physics') !== null, 'Known English book is missing.');
check($catalog->collection('books-vpho-en') !== null, 'Known collection is missing.');
check($catalog->resourceUrl(['file' => ['path' => 'books/Tài liệu 1.pdf']]) === '/physics/books/T%C3%A0i%20li%E1%BB%87u%201.pdf', 'Resource URL encoding failed.');

$unsafeRejected = false;
try {
    $catalog->resourceUrl(['file' => ['path' => '../secret.pdf']]);
} catch (RuntimeException) {
    $unsafeRejected = true;
}
check($unsafeRejected, 'Path traversal was not rejected.');

$pdfs = array_filter($catalog->documents(), static fn (array $document): bool => $document['format'] === 'pdf');
check(count(array_filter($pdfs, static fn (array $document): bool => !is_int($document['pages'] ?? null) || $document['pages'] < 1)) === 0, 'Every published PDF needs a prepared page count.');
$olympiads = array_values(array_filter($catalog->documents(), static fn (array $document): bool => ($document['collectionId'] ?? '') === 'olympiads'));
check(count($olympiads) === 2738, 'Expected 2738 unique Olympiad records.');
check(count(array_filter($olympiads, static fn (array $document): bool => !is_string($document['competition'] ?? null) || ($document['competition'] ?? '') === '' || !is_int($document['year'] ?? null))) === 0, 'Every Olympiad record needs competition and year metadata.');
$missingCovers = array_filter($pdfs, static fn (array $document): bool => $catalog->coverUrl($document) === null);
check(count($missingCovers) === 0, 'Every current published PDF should have a cover.');

fwrite(STDOUT, "Web tests passed: 3045 records, 3045 covers, Hostinger-only delivery.\n");

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, 'TEST FAILURE: ' . $message . PHP_EOL);
        exit(1);
    }
}
