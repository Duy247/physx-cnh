<?php
declare(strict_types=1);

const MAX_NEW_PDF_BYTES = 99614720; // 95 MiB, below GitHub's 100 MB hard limit.

$root = dirname(__DIR__);
require_once $root . '/src/CatalogRepository.php';

/** @var array<string, array<string, mixed>> $catalogs */
$catalogs = require $root . '/config/catalogs.php';
$repository = new CatalogRepository($root . '/physics');
$command = $argv[1] ?? 'help';
$options = parseOptions(array_slice($argv, 2));

try {
    if ($command === 'validate') {
        $gitTree = optionString($options, 'git-tree');
        $changedFrom = optionString($options, 'changed-from');
        $result = validateCatalogs($repository, $catalogs, $root, $gitTree, $changedFrom);
        foreach ($result['messages'] as $message) {
            fwrite(STDOUT, $message . PHP_EOL);
        }
        if ($result['errors'] !== []) {
            foreach ($result['errors'] as $error) {
                fwrite(STDERR, 'ERROR: ' . $error . PHP_EOL);
            }
            exit(1);
        }
        fwrite(STDOUT, 'Catalog validation passed: ' . $result['catalogs'] . ' catalogs, ' . $result['items'] . ' resources.' . PHP_EOL);
        exit(0);
    }

    if ($command === 'add') {
        addDocument($repository, $catalogs, $root, $options);
        exit(0);
    }

    if ($command === 'catalogs') {
        foreach ($catalogs as $catalog) {
            fwrite(STDOUT, $catalog['id'] . "\t" . $catalog['title'] . PHP_EOL);
        }
        exit(0);
    }

    printUsage();
    exit($command === 'help' ? 0 : 1);
} catch (Throwable $error) {
    fwrite(STDERR, 'ERROR: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

/**
 * @param array<int, string> $arguments
 * @return array<string, string|bool>
 */
function parseOptions(array $arguments): array
{
    $options = [];
    for ($index = 0; $index < count($arguments); $index++) {
        $argument = $arguments[$index];
        if (!str_starts_with($argument, '--')) {
            throw new InvalidArgumentException('Unexpected argument: ' . $argument);
        }

        $option = substr($argument, 2);
        if (str_contains($option, '=')) {
            [$name, $value] = explode('=', $option, 2);
            $options[$name] = $value;
            continue;
        }

        $next = $arguments[$index + 1] ?? null;
        if (is_string($next) && !str_starts_with($next, '--')) {
            $options[$option] = $next;
            $index++;
        } else {
            $options[$option] = true;
        }
    }
    return $options;
}

/** @param array<string, string|bool> $options */
function optionString(array $options, string $name): ?string
{
    $value = $options[$name] ?? null;
    return is_string($value) && trim($value) !== '' ? trim($value) : null;
}

/**
 * @param array<string, array<string, mixed>> $catalogs
 * @return array{catalogs: int, items: int, errors: array<int, string>, messages: array<int, string>}
 */
function validateCatalogs(
    CatalogRepository $repository,
    array $catalogs,
    string $root,
    ?string $gitTree,
    ?string $changedFrom
): array {
    $errors = [];
    $messages = [];
    $allFiles = [];
    $fileCatalogs = [];
    $totalItems = 0;
    $treePaths = null;

    if ($gitTree !== null) {
        assertSafeGitRef($gitTree);
        $treePaths = array_fill_keys(gitPathList($root, ['ls-tree', '-r', '--name-only', '-z', $gitTree, '--', 'physics']), true);
    }

    $seenCatalogIds = [];
    foreach ($catalogs as $route => $catalog) {
        $catalogId = (string) ($catalog['id'] ?? '');
        $manifestPath = (string) ($catalog['manifest'] ?? '');
        if ($catalogId === '' || isset($seenCatalogIds[$catalogId])) {
            $errors[] = 'Catalog configuration contains a missing or duplicate id at ' . $route . '.';
            continue;
        }
        $seenCatalogIds[$catalogId] = true;
        if (($catalog['type'] ?? null) . ':' . ($catalog['level'] ?? null) !== $route) {
            $errors[] = 'Catalog route does not match its type and level: ' . $route . '.';
        }

        try {
            $items = $repository->load($manifestPath, $gitTree === null);
        } catch (CatalogException $error) {
            $errors[] = $error->getMessage();
            continue;
        }

        $totalItems += count($items);
        foreach ($items as $item) {
            $repoPath = 'physics/' . $item['file'];
            if ($treePaths !== null && !isset($treePaths[$repoPath])) {
                $workingTreePath = $root . '/' . str_replace('/', DIRECTORY_SEPARATOR, $repoPath);
                if (!is_file($workingTreePath)) {
                    $errors[] = basename($manifestPath) . ' references a path absent from ' . $gitTree . ': ' . $repoPath;
                }
            }

            $key = strtolower($item['file']);
            if (isset($allFiles[$key])) {
                $errors[] = 'Duplicate resource path in ' . $catalogId . ' and ' . $allFiles[$key] . ': ' . $item['file'];
            } else {
                $allFiles[$key] = $catalogId;
                $fileCatalogs[$repoPath] = $catalogId;
            }

            $absolutePath = $root . '/physics/' . str_replace('/', DIRECTORY_SEPARATOR, $item['file']);
            if (
                str_starts_with($item['file'], 'library/')
                && strtolower(pathinfo($item['file'], PATHINFO_EXTENSION)) === 'pdf'
                && is_file($absolutePath)
            ) {
                validatePdfFile($absolutePath, $item['file'], $errors);
            }
        }
    }

    if ($changedFrom !== null) {
        assertSafeGitRef($changedFrom);
        $allChangedPaths = gitPathList(
            $root,
            ['diff', '--name-only', '--diff-filter=ACDMRT', '-z', $changedFrom . '...HEAD', '--']
        );
        $addedPdfs = array_values(array_filter(
            gitPathList($root, ['diff', '--name-only', '--diff-filter=A', '-z', $changedFrom . '...HEAD', '--', 'physics']),
            static fn (string $path): bool => strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf'
        ));
        $changedPdfs = array_values(array_filter(
            gitPathList($root, ['diff', '--name-only', '--diff-filter=MD', '-z', $changedFrom . '...HEAD', '--', 'physics']),
            static fn (string $path): bool => strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf'
        ));
        $addedPdfSet = array_fill_keys($addedPdfs, true);

        foreach ($allChangedPaths as $path) {
            $allowed = preg_match('#^physics/catalog/[a-z0-9-]+\.json$#', $path) === 1
                || preg_match('#^physics/library/[a-z0-9-]+/[a-z0-9][a-z0-9-]*\.pdf$#', $path) === 1;
            if (!$allowed) {
                $errors[] = 'Contributor PR contains an out-of-scope change: ' . $path;
            }
        }

        foreach ($changedPdfs as $path) {
            $errors[] = 'Contributor PRs must not modify or delete an existing PDF: ' . $path;
        }

        foreach ($addedPdfs as $path) {
            $catalogId = $fileCatalogs[$path] ?? null;
            if ($catalogId === null) {
                $errors[] = 'New PDF is not registered in a catalog: ' . $path;
                continue;
            }
            $expectedPrefix = 'physics/library/' . $catalogId . '/';
            if (!str_starts_with($path, $expectedPrefix)) {
                $errors[] = 'New PDF must be stored under ' . $expectedPrefix . ': ' . $path;
            }
        }

        foreach ($catalogs as $catalog) {
            $manifestPath = (string) $catalog['manifest'];
            $manifestRepoPath = relativeToRoot($manifestPath, $root);
            if (!in_array($manifestRepoPath, $allChangedPaths, true)) {
                continue;
            }

            try {
                $baseManifest = decodeManifest(gitFileAtRef($root, $changedFrom, $manifestRepoPath), $manifestRepoPath . ' at ' . $changedFrom);
                $headManifestJson = file_get_contents($manifestPath);
                if ($headManifestJson === false) {
                    throw new RuntimeException('Cannot read ' . $manifestRepoPath);
                }
                $headManifest = decodeManifest($headManifestJson, $manifestRepoPath);
                assertOnlyCatalogAdditions($baseManifest, $headManifest, $addedPdfSet, $manifestRepoPath, $errors);
            } catch (Throwable $error) {
                $errors[] = $error->getMessage();
            }
        }
        $messages[] = 'Changed-file policy checked against ' . $changedFrom . ': ' . count($addedPdfs) . ' new PDF(s).';
    }

    return [
        'catalogs' => count($seenCatalogIds),
        'items' => $totalItems,
        'errors' => $errors,
        'messages' => $messages,
    ];
}

/** @param array<int, string> $errors */
function validatePdfFile(string $absolutePath, string $displayPath, array &$errors): void
{
    $size = @filesize($absolutePath);
    if ($size === false) {
        $errors[] = 'Cannot determine PDF size: ' . $displayPath;
        return;
    }
    if ($size > MAX_NEW_PDF_BYTES) {
        $errors[] = 'PDF exceeds the 95 MiB repository limit: ' . $displayPath;
    }

    $handle = @fopen($absolutePath, 'rb');
    $signature = is_resource($handle) ? fread($handle, 5) : false;
    if (is_resource($handle)) {
        fclose($handle);
    }
    if ($signature !== '%PDF-') {
        $errors[] = 'File does not have a PDF signature: ' . $displayPath;
    }
}

/**
 * @param array<string, array<string, mixed>> $catalogs
 * @param array<string, string|bool> $options
 */
function addDocument(CatalogRepository $repository, array $catalogs, string $root, array $options): void
{
    $catalogId = requiredOption($options, 'catalog');
    $sourcePdf = requiredOption($options, 'pdf');
    $title = requiredOption($options, 'title');
    $author = optionString($options, 'author') ?? '';
    $description = optionString($options, 'description') ?? '';
    $source = optionString($options, 'source') ?? '';
    $delivery = optionString($options, 'delivery') ?? 'hostinger';
    if (!in_array($delivery, ['hostinger', 'vercel-blob'], true)) {
        throw new RuntimeException('--delivery must be hostinger or vercel-blob.');
    }

    $catalog = null;
    foreach ($catalogs as $candidate) {
        if (($candidate['id'] ?? null) === $catalogId) {
            $catalog = $candidate;
            break;
        }
    }
    if (!is_array($catalog)) {
        throw new InvalidArgumentException('Unknown catalog "' . $catalogId . '". Run "php tools/catalog.php catalogs".');
    }

    $sourcePath = realpath($sourcePdf);
    if ($sourcePath === false || !is_file($sourcePath) || strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) !== 'pdf') {
        throw new InvalidArgumentException('The --pdf value must point to an existing PDF file.');
    }
    $pdfErrors = [];
    validatePdfFile($sourcePath, $sourcePath, $pdfErrors);
    if ($pdfErrors !== []) {
        throw new InvalidArgumentException(implode(' ', $pdfErrors));
    }

    $filename = safePdfFilename(basename($sourcePath));
    $relativePath = 'library/' . $catalogId . '/' . $filename;
    $destinationDirectory = $root . '/physics/library/' . $catalogId;
    $destinationPath = $destinationDirectory . '/' . $filename;
    if (file_exists($destinationPath)) {
        throw new RuntimeException('Destination already exists: physics/' . $relativePath);
    }

    $manifestPath = (string) $catalog['manifest'];
    $originalJson = file_get_contents($manifestPath);
    if ($originalJson === false) {
        throw new RuntimeException('Cannot read ' . $manifestPath);
    }
    $manifest = json_decode($originalJson, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($manifest) || !isset($manifest['items']) || !is_array($manifest['items'])) {
        throw new RuntimeException('Manifest has an invalid structure: ' . $manifestPath);
    }
    foreach ($manifest['items'] as $item) {
        if (is_array($item) && strtolower((string) ($item['file'] ?? '')) === strtolower($relativePath)) {
            throw new RuntimeException('The PDF is already registered: ' . $relativePath);
        }
    }

    $manifest['items'][] = [
        'title' => $title,
        'author' => $author,
        'file' => $relativePath,
        'description' => $description,
        'source' => $source,
        'delivery' => $delivery,
    ];
    $updatedJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;

    if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true) && !is_dir($destinationDirectory)) {
        throw new RuntimeException('Cannot create ' . $destinationDirectory);
    }
    if (!copy($sourcePath, $destinationPath)) {
        throw new RuntimeException('Cannot copy the PDF into the repository.');
    }

    try {
        if (file_put_contents($manifestPath, $updatedJson, LOCK_EX) === false) {
            throw new RuntimeException('Cannot update the catalog manifest.');
        }
        $repository->load($manifestPath, true);
    } catch (Throwable $error) {
        file_put_contents($manifestPath, $originalJson, LOCK_EX);
        @unlink($destinationPath);
        throw $error;
    }

    fwrite(STDOUT, 'Added: physics/' . $relativePath . PHP_EOL);
    fwrite(STDOUT, 'Registered in: ' . relativeToRoot($manifestPath, $root) . PHP_EOL);
    fwrite(STDOUT, 'Next: php tools/catalog.php validate' . PHP_EOL);
}

/** @param array<string, string|bool> $options */
function requiredOption(array $options, string $name): string
{
    $value = optionString($options, $name);
    if ($value === null) {
        throw new InvalidArgumentException('Missing required option --' . $name . '.');
    }
    return $value;
}

function safePdfFilename(string $filename): string
{
    $stem = pathinfo($filename, PATHINFO_FILENAME);
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $stem);
    $ascii = is_string($ascii) ? $ascii : $stem;
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $ascii) ?? '', '-'));
    if ($slug === '') {
        $slug = 'document-' . gmdate('Ymd-His');
    }
    return substr($slug, 0, 120) . '.pdf';
}

function assertSafeGitRef(string $ref): void
{
    if (preg_match('#^[A-Za-z0-9._/-]+$#', $ref) !== 1 || str_starts_with($ref, '-')) {
        throw new InvalidArgumentException('Unsafe Git reference: ' . $ref);
    }
}

function gitFileAtRef(string $root, string $ref, string $path): string
{
    $result = runProcess(['git', '-C', $root, 'show', $ref . ':' . $path]);
    if ($result['code'] !== 0) {
        throw new RuntimeException('Cannot read ' . $path . ' from ' . $ref . ': ' . trim($result['stderr']));
    }
    return $result['stdout'];
}

/** @return array{version: int, items: array<int, array<string, mixed>>} */
function decodeManifest(string $json, string $label): array
{
    $manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($manifest) || ($manifest['version'] ?? null) !== 1 || !isset($manifest['items']) || !is_array($manifest['items'])) {
        throw new RuntimeException('Invalid catalog manifest: ' . $label);
    }
    return $manifest;
}

/**
 * @param array{version: int, items: array<int, array<string, mixed>>} $baseManifest
 * @param array{version: int, items: array<int, array<string, mixed>>} $headManifest
 * @param array<string, bool> $addedPdfSet
 * @param array<int, string> $errors
 */
function assertOnlyCatalogAdditions(
    array $baseManifest,
    array $headManifest,
    array $addedPdfSet,
    string $manifestPath,
    array &$errors
): void {
    $headByFile = [];
    foreach ($headManifest['items'] as $item) {
        if (is_array($item) && is_string($item['file'] ?? null)) {
            $headByFile[(string) $item['file']] = $item;
        }
    }

    $baseFiles = [];
    foreach ($baseManifest['items'] as $item) {
        if (!is_array($item) || !is_string($item['file'] ?? null)) {
            continue;
        }
        $file = (string) $item['file'];
        $baseFiles[$file] = true;
        if (!isset($headByFile[$file])) {
            $errors[] = 'Contributor PR removed a catalog entry from ' . $manifestPath . ': ' . $file;
        } elseif ($headByFile[$file] !== $item) {
            $errors[] = 'Contributor PR modified an existing catalog entry in ' . $manifestPath . ': ' . $file;
        }
    }

    foreach ($headByFile as $file => $item) {
        if (isset($baseFiles[$file])) {
            continue;
        }
        $repoPath = 'physics/' . $file;
        if (!isset($addedPdfSet[$repoPath])) {
            $errors[] = 'New catalog entry does not correspond to a PDF added by this PR: ' . $repoPath;
        }
    }
}

/**
 * @param array<int, string> $arguments
 * @return array<int, string>
 */
function gitPathList(string $root, array $arguments): array
{
    $result = runProcess(array_merge(['git', '-C', $root], $arguments));
    if ($result['code'] !== 0) {
        throw new RuntimeException('Git command failed: ' . trim($result['stderr']));
    }
    return array_values(array_filter(explode("\0", $result['stdout']), static fn (string $path): bool => $path !== ''));
}

/**
 * @param array<int, string> $command
 * @return array{code: int, stdout: string, stderr: string}
 */
function runProcess(array $command): array
{
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start process: ' . implode(' ', $command));
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    return ['code' => $code, 'stdout' => (string) $stdout, 'stderr' => (string) $stderr];
}

function relativeToRoot(string $path, string $root): string
{
    $prefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
    return str_replace($prefix, '', str_replace('\\', '/', $path));
}

function printUsage(): void
{
    fwrite(STDOUT, <<<TEXT
PhysX-CNH catalog maintenance

Commands:
  php tools/catalog.php catalogs
  php tools/catalog.php validate [--git-tree HEAD] [--changed-from origin/master]
  php tools/catalog.php add --catalog ID --pdf FILE --title TITLE [--author NAME] [--description TEXT] [--source TEXT] [--delivery hostinger|vercel-blob]

TEXT);
}
