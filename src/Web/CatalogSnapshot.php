<?php
declare(strict_types=1);

final class CatalogSnapshot
{
    /** @var array<string, mixed>|null */
    private static ?array $memory = null;
    private static int $memoryMtime = 0;

    public function __construct(
        private readonly string $snapshotPath,
        private readonly string $projectRoot,
    ) {
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $mtime = (int) (@filemtime($this->snapshotPath) ?: 0);
        if ($mtime === 0) {
            throw new RuntimeException('Catalog snapshot is unavailable.');
        }

        if (self::$memory !== null && self::$memoryMtime === $mtime) {
            return self::$memory;
        }

        $cacheKey = 'physx_catalog_' . sha1($this->snapshotPath . ':' . $mtime);
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success && is_array($cached)) {
                self::$memory = $cached;
                self::$memoryMtime = $mtime;
                return $cached;
            }
        }

        $json = file_get_contents($this->snapshotPath);
        if ($json === false) {
            throw new RuntimeException('Catalog snapshot cannot be read.');
        }
        $catalog = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($catalog) || !isset($catalog['documents'], $catalog['collections'], $catalog['counts'])) {
            throw new RuntimeException('Catalog snapshot has an invalid shape.');
        }

        self::$memory = $catalog;
        self::$memoryMtime = $mtime;
        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $catalog, 3600);
        }
        return $catalog;
    }

    /** @return list<array<string, mixed>> */
    public function documents(): array
    {
        return array_values($this->all()['documents']);
    }

    /** @return array<string, mixed>|null */
    public function document(string $slug): ?array
    {
        foreach ($this->documents() as $document) {
            if (($document['slug'] ?? '') === $slug) {
                return $document;
            }
        }
        return null;
    }

    /** @return array<string, mixed>|null */
    public function collection(string $id): ?array
    {
        foreach ($this->all()['collections'] as $collection) {
            if (($collection['id'] ?? '') === $id) {
                return $collection;
            }
        }
        return null;
    }

    /** @param array<string, mixed> $document */
    public function resourceUrl(array $document): string
    {
        $path = (string) ($document['file']['path'] ?? '');
        $normalized = str_replace('\\', '/', $path);
        if ($normalized === '' || str_contains($normalized, "\0") || str_starts_with($normalized, '/') || preg_match('#(?:^|/)\.\.(?:/|$)#', $normalized)) {
            throw new RuntimeException('Unsafe catalog resource path.');
        }
        $segments = array_map('rawurlencode', explode('/', $normalized));
        return '/physics/' . implode('/', $segments);
    }

    /** @param array<string, mixed> $document */
    public function coverUrl(array $document): ?string
    {
        if (($document['format'] ?? '') !== 'pdf') {
            return null;
        }
        $slug = (string) ($document['slug'] ?? '');
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return null;
        }
        $relative = '/assets/v2/covers/' . $slug . '.webp';
        return is_file($this->projectRoot . $relative) ? $relative : null;
    }
}
