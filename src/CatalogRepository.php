<?php
declare(strict_types=1);

final class CatalogException extends RuntimeException
{
}

final class CatalogRepository
{
    private const MAX_TITLE_LENGTH = 300;
    private const MAX_AUTHOR_LENGTH = 500;
    private const MAX_DESCRIPTION_LENGTH = 2000;
    private const MAX_SOURCE_LENGTH = 1000;
    /** @var array<string, array<int, string>> */
    private array $directoryEntries = [];

    public function __construct(private readonly string $physicsRoot)
    {
    }

    /**
     * @return array<int, array{title: string, author: string, file: string, description: string, source: string, legacy: bool, delivery: string}>
     */
    public function load(string $manifestPath, bool $requireFiles = true): array
    {
        $json = @file_get_contents($manifestPath);
        if ($json === false) {
            throw new CatalogException('Cannot read manifest: ' . $manifestPath);
        }

        try {
            $manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new CatalogException('Invalid JSON in ' . $manifestPath . ': ' . $error->getMessage(), 0, $error);
        }

        if (
            !is_array($manifest)
            || array_diff(array_keys($manifest), ['version', 'items']) !== []
            || ($manifest['version'] ?? null) !== 1
            || !isset($manifest['items'])
            || !is_array($manifest['items'])
        ) {
            throw new CatalogException('Manifest must contain version 1 and an items array: ' . $manifestPath);
        }

        $items = [];
        $seenFiles = [];
        foreach ($manifest['items'] as $index => $rawItem) {
            if (!is_array($rawItem)) {
                throw new CatalogException($this->itemError($manifestPath, $index, 'must be an object'));
            }
            $unknownFields = array_diff(array_keys($rawItem), ['title', 'author', 'file', 'description', 'source', 'legacy', 'delivery']);
            if ($unknownFields !== []) {
                throw new CatalogException($this->itemError($manifestPath, $index, 'contains unknown field(s): ' . implode(', ', $unknownFields)));
            }
            if (isset($rawItem['legacy']) && !is_bool($rawItem['legacy'])) {
                throw new CatalogException($this->itemError($manifestPath, $index, 'legacy must be a boolean'));
            }
            if (isset($rawItem['delivery']) && !in_array($rawItem['delivery'], ['hostinger', 'vercel-blob'], true)) {
                throw new CatalogException($this->itemError($manifestPath, $index, 'delivery must be hostinger or vercel-blob'));
            }

            $item = [
                'title' => $this->textField($rawItem, 'title', true, self::MAX_TITLE_LENGTH, $manifestPath, $index),
                'author' => $this->textField($rawItem, 'author', false, self::MAX_AUTHOR_LENGTH, $manifestPath, $index),
                'file' => $this->fileField($rawItem, $manifestPath, $index),
                'description' => $this->textField($rawItem, 'description', false, self::MAX_DESCRIPTION_LENGTH, $manifestPath, $index),
                'source' => $this->textField($rawItem, 'source', false, self::MAX_SOURCE_LENGTH, $manifestPath, $index),
                'legacy' => ($rawItem['legacy'] ?? false) === true,
                'delivery' => (string) ($rawItem['delivery'] ?? 'hostinger'),
            ];

            $extension = strtolower(pathinfo($item['file'], PATHINFO_EXTENSION));
            if ($extension !== 'pdf' && !($item['legacy'] && in_array($extension, ['html', 'htm'], true))) {
                throw new CatalogException($this->itemError($manifestPath, $index, 'must reference a PDF (legacy HTML requires legacy: true)'));
            }

            $fileKey = strtolower($item['file']);
            if (isset($seenFiles[$fileKey])) {
                throw new CatalogException($this->itemError($manifestPath, $index, 'duplicates file ' . $item['file']));
            }
            $seenFiles[$fileKey] = true;

            if ($requireFiles) {
                $this->assertFileExists($item['file'], $manifestPath, $index);
            }

            $items[] = $item;
        }

        return $items;
    }

    public static function resourceUrl(string $file): string
    {
        return '/physics/' . implode('/', array_map('rawurlencode', explode('/', $file)));
    }

    private function textField(
        array $item,
        string $field,
        bool $required,
        int $maxLength,
        string $manifestPath,
        int $index
    ): string {
        $value = $item[$field] ?? '';
        if (!is_string($value)) {
            throw new CatalogException($this->itemError($manifestPath, $index, $field . ' must be a string'));
        }

        $value = str_replace(["\r\n", "\r"], "\n", trim($value));
        if ($required && $value === '') {
            throw new CatalogException($this->itemError($manifestPath, $index, $field . ' is required'));
        }
        if (preg_match('/[<>]/u', $value) === 1) {
            throw new CatalogException($this->itemError($manifestPath, $index, $field . ' must be plain text, not HTML'));
        }
        if ($this->stringLength($value) > $maxLength) {
            throw new CatalogException($this->itemError($manifestPath, $index, $field . ' is too long'));
        }

        return $value;
    }

    private function fileField(array $item, string $manifestPath, int $index): string
    {
        $file = $item['file'] ?? null;
        if (!is_string($file)) {
            throw new CatalogException($this->itemError($manifestPath, $index, 'file is required'));
        }

        $file = trim($file);
        if (
            $file === ''
            || str_starts_with($file, '/')
            || str_contains($file, '\\')
            || str_contains($file, "\0")
            || preg_match('#(^|/)\.\.?(/|$)#', $file) === 1
            || preg_match('/[\x00-\x1F\x7F]/', $file) === 1
        ) {
            throw new CatalogException($this->itemError($manifestPath, $index, 'file must be a safe path relative to physics/'));
        }

        return $file;
    }

    private function assertFileExists(string $file, string $manifestPath, int $index): void
    {
        $root = realpath($this->physicsRoot);
        $candidate = $root === false ? false : $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
        $resolved = $candidate === false ? false : realpath($candidate);
        if (
            $root === false
            || $candidate === false
            || $resolved === false
            || !str_starts_with($resolved, $root . DIRECTORY_SEPARATOR)
            || !is_file($resolved)
            || !$this->hasExactCase($root, $file)
        ) {
            throw new CatalogException($this->itemError($manifestPath, $index, 'file does not exist with exact casing: ' . $file));
        }
    }

    private function hasExactCase(string $root, string $relativePath): bool
    {
        $current = $root;
        foreach (explode('/', $relativePath) as $segment) {
            $entries = $this->directoryEntries[$current] ??= (@scandir($current) ?: []);
            if (!is_array($entries) || !in_array($segment, $entries, true)) {
                return false;
            }
            $current .= DIRECTORY_SEPARATOR . $segment;
        }
        return true;
    }

    private function itemError(string $manifestPath, int $index, string $message): string
    {
        return basename($manifestPath) . ' item ' . ($index + 1) . ' ' . $message;
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
