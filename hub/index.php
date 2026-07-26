<?php
declare(strict_types=1);

const MAX_DOWNLOAD_BYTES = 536870912; // 512 MiB
const MAX_REDIRECTS = 5;

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

if (!isset($_SESSION['hub_csrf']) || !is_string($_SESSION['hub_csrf'])) {
    $_SESSION['hub_csrf'] = bin2hex(random_bytes(24));
}

$downloadDir = __DIR__ . DIRECTORY_SEPARATOR . 'downloads';
$message = null;
$messageType = 'success';

if (!is_dir($downloadDir) && !mkdir($downloadDir, 0755, true) && !is_dir($downloadDir)) {
    http_response_code(500);
    $message = 'The downloads folder could not be created.';
    $messageType = 'error';
}

$legacyDownloadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'blackmagic' . DIRECTORY_SEPARATOR . 'downloads';
if ($message === null && is_dir($legacyDownloadDir) && realpath($legacyDownloadDir) !== realpath($downloadDir)) {
    $legacyFiles = new DirectoryIterator($legacyDownloadDir);
    foreach ($legacyFiles as $legacyFile) {
        if (
            !$legacyFile->isFile()
            || $legacyFile->isLink()
            || $legacyFile->isDot()
            || str_starts_with($legacyFile->getFilename(), '.')
        ) {
            continue;
        }

        $source = $legacyFile->getPathname();
        $fileName = $legacyFile->getFilename();
        $destination = $downloadDir . DIRECTORY_SEPARATOR . $fileName;
        $suffix = 1;
        $alreadyMigrated = false;
        while (file_exists($destination)) {
            $sourceHash = @hash_file('sha256', $source);
            $destinationHash = @hash_file('sha256', $destination);
            if (
                is_file($destination)
                && filesize($source) === filesize($destination)
                && is_string($sourceHash)
                && is_string($destinationHash)
                && hash_equals($sourceHash, $destinationHash)
            ) {
                $alreadyMigrated = true;
                break;
            }
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $stem = pathinfo($fileName, PATHINFO_FILENAME);
            $candidate = $stem . '-legacy-' . $suffix . ($extension === '' ? '' : '.' . $extension);
            $destination = $downloadDir . DIRECTORY_SEPARATOR . $candidate;
            $suffix++;
        }

        if ($alreadyMigrated) {
            continue;
        }

        if (!@rename($source, $destination) && @copy($source, $destination)) {
            @unlink($source);
        }
    }
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $value = (float) $bytes;
    $unit = 0;

    while ($value >= 1024 && $unit < count($units) - 1) {
        $value /= 1024;
        $unit++;
    }

    return ($unit === 0 ? (string) $bytes : number_format($value, 1)) . ' ' . $units[$unit];
}

/**
 * @return array{valid: bool, owner: string, repository: string, message: string}
 */
function parseGitHubRepositoryUrl(string $url): array
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['valid' => false, 'owner' => '', 'repository' => '', 'message' => 'Enter a valid GitHub repository URL.'];
    }

    $parts = parse_url($url);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));
    $segments = array_values(array_filter(explode('/', trim((string) ($parts['path'] ?? ''), '/')), 'strlen'));

    if (!in_array($scheme, ['http', 'https'], true) || !in_array($host, ['github.com', 'www.github.com'], true)) {
        return ['valid' => false, 'owner' => '', 'repository' => '', 'message' => 'Only github.com repository links are supported.'];
    }

    if (count($segments) < 2) {
        return ['valid' => false, 'owner' => '', 'repository' => '', 'message' => 'The link must include an owner and repository.'];
    }

    $owner = $segments[0];
    $repository = preg_replace('/\.git$/i', '', $segments[1]) ?? '';
    if (
        $owner === ''
        || $repository === ''
        || !preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})$/', $owner)
        || !preg_match('/^[A-Za-z0-9_.-]+$/', $repository)
    ) {
        return ['valid' => false, 'owner' => '', 'repository' => '', 'message' => 'The GitHub owner or repository name is invalid.'];
    }

    return ['valid' => true, 'owner' => $owner, 'repository' => $repository, 'message' => ''];
}

/**
 * @return array{success: bool, status: int, data: array<string, mixed>, message: string}
 */
function callGitHubApi(string $path): array
{
    if (!function_exists('curl_init')) {
        return ['success' => false, 'status' => 500, 'data' => [], 'message' => 'GitHub lookup is unavailable because cURL is not installed on this server.'];
    }

    $handle = curl_init('https://api.github.com' . $path);
    if ($handle === false) {
        return ['success' => false, 'status' => 500, 'data' => [], 'message' => 'GitHub lookup could not be started.'];
    }

    $headers = [
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: PhysX-CNH-Utility-Hub/1.0',
    ];
    $token = trim((string) getenv('GITHUB_TOKEN'));
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($handle);
    curl_close($handle);

    if (!is_string($body)) {
        return ['success' => false, 'status' => $status, 'data' => [], 'message' => 'GitHub could not be reached. ' . $curlError];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['success' => false, 'status' => $status, 'data' => [], 'message' => 'GitHub returned an unreadable response.'];
    }

    if ($status < 200 || $status >= 300) {
        $apiMessage = trim((string) ($data['message'] ?? 'GitHub rejected the request.'));
        return ['success' => false, 'status' => $status, 'data' => $data, 'message' => $apiMessage];
    }

    return ['success' => true, 'status' => $status, 'data' => $data, 'message' => ''];
}

function encodeGitHubPath(string $value): string
{
    return implode('/', array_map('rawurlencode', explode('/', $value)));
}

/**
 * @return array{success: bool, message: string, repository?: array<string, mixed>, release?: array<string, mixed>|null}
 */
function analyzeGitHubRepository(string $url): array
{
    $parsed = parseGitHubRepositoryUrl($url);
    if (!$parsed['valid']) {
        return ['success' => false, 'message' => $parsed['message']];
    }

    $apiSlug = '/' . rawurlencode($parsed['owner']) . '/' . rawurlencode($parsed['repository']);
    $repositoryResponse = callGitHubApi('/repos' . $apiSlug);
    if (!$repositoryResponse['success']) {
        $message = $repositoryResponse['status'] === 404
            ? 'The repository was not found or is not publicly accessible.'
            : 'Repository lookup failed: ' . $repositoryResponse['message'];
        return ['success' => false, 'message' => $message];
    }

    $repositoryData = $repositoryResponse['data'];
    $defaultBranch = trim((string) ($repositoryData['default_branch'] ?? ''));
    if ($defaultBranch === '') {
        return ['success' => false, 'message' => 'GitHub did not report a default branch for this repository.'];
    }

    $fullName = (string) ($repositoryData['full_name'] ?? ($parsed['owner'] . '/' . $parsed['repository']));
    $repository = [
        'full_name' => $fullName,
        'description' => trim((string) ($repositoryData['description'] ?? '')),
        'html_url' => (string) ($repositoryData['html_url'] ?? ('https://github.com/' . $fullName)),
        'default_branch' => $defaultBranch,
        'archive_url' => 'https://github.com/' . rawurlencode($parsed['owner']) . '/' . rawurlencode($parsed['repository'])
            . '/archive/refs/heads/' . encodeGitHubPath($defaultBranch) . '.zip',
    ];

    $releaseResponse = callGitHubApi('/repos' . $apiSlug . '/releases/latest');
    if (!$releaseResponse['success']) {
        if ($releaseResponse['status'] === 404) {
            return ['success' => true, 'message' => 'No published release was found.', 'repository' => $repository, 'release' => null];
        }
        return [
            'success' => true,
            'message' => 'The repository was found, but its latest release could not be loaded: ' . $releaseResponse['message'],
            'repository' => $repository,
            'release' => null,
        ];
    }

    $releaseData = $releaseResponse['data'];
    $assets = [];
    foreach (($releaseData['assets'] ?? []) as $asset) {
        if (!is_array($asset)) {
            continue;
        }
        $assetUrl = (string) ($asset['browser_download_url'] ?? '');
        if (!str_starts_with($assetUrl, 'https://github.com/')) {
            continue;
        }
        $assets[] = [
            'name' => (string) ($asset['name'] ?? 'Release asset'),
            'url' => $assetUrl,
            'size' => (int) ($asset['size'] ?? 0),
            'content_type' => (string) ($asset['content_type'] ?? ''),
            'download_count' => (int) ($asset['download_count'] ?? 0),
        ];
    }

    return [
        'success' => true,
        'message' => '',
        'repository' => $repository,
        'release' => [
            'name' => trim((string) ($releaseData['name'] ?? '')),
            'tag_name' => (string) ($releaseData['tag_name'] ?? ''),
            'html_url' => (string) ($releaseData['html_url'] ?? ''),
            'published_at' => (string) ($releaseData['published_at'] ?? ''),
            'assets' => $assets,
        ],
    ];
}

function isPublicIp(string $ip): bool
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

/**
 * @return array{valid: bool, message: string, host: string, port: int, addresses: array<int, string>}
 */
function validateRemoteUrl(string $url): array
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['valid' => false, 'message' => 'Enter a valid URL.', 'host' => '', 'port' => 0, 'addresses' => []];
    }

    $parts = parse_url($url);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));

    if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
        return ['valid' => false, 'message' => 'Only HTTP and HTTPS URLs are supported.', 'host' => '', 'port' => 0, 'addresses' => []];
    }

    if (isset($parts['user']) || isset($parts['pass'])) {
        return ['valid' => false, 'message' => 'URLs containing credentials are not allowed.', 'host' => '', 'port' => 0, 'addresses' => []];
    }

    $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
    if (!in_array($port, [80, 443], true)) {
        return ['valid' => false, 'message' => 'Only standard HTTP and HTTPS ports are allowed.', 'host' => '', 'port' => 0, 'addresses' => []];
    }

    $addresses = [];
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $addresses[] = $host;
    } else {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                $address = (string) ($record['ip'] ?? $record['ipv6'] ?? '');
                if ($address !== '') {
                    $addresses[] = $address;
                }
            }
        }
    }

    $addresses = array_values(array_unique($addresses));
    if ($addresses === []) {
        return ['valid' => false, 'message' => 'The URL host could not be resolved.', 'host' => '', 'port' => 0, 'addresses' => []];
    }

    foreach ($addresses as $address) {
        if (!isPublicIp($address)) {
            return ['valid' => false, 'message' => 'Private, local, and reserved network addresses are blocked.', 'host' => '', 'port' => 0, 'addresses' => []];
        }
    }

    return [
        'valid' => true,
        'message' => '',
        'host' => $host,
        'port' => $port,
        'addresses' => $addresses,
    ];
}

function resolveRedirectUrl(string $baseUrl, string $location): ?string
{
    $location = trim($location);
    if ($location === '') {
        return null;
    }

    if (filter_var($location, FILTER_VALIDATE_URL)) {
        return $location;
    }

    $base = parse_url($baseUrl);
    if (!is_array($base) || empty($base['scheme']) || empty($base['host'])) {
        return null;
    }

    if (str_starts_with($location, '//')) {
        return $base['scheme'] . ':' . $location;
    }

    $authority = $base['scheme'] . '://' . $base['host'];
    if (isset($base['port'])) {
        $authority .= ':' . $base['port'];
    }

    if (str_starts_with($location, '/')) {
        return $authority . $location;
    }

    $basePath = (string) ($base['path'] ?? '/');
    $directory = rtrim(str_replace('\\', '/', dirname($basePath)), '/');
    $combined = ($directory === '' ? '' : $directory) . '/' . $location;
    $segments = [];

    foreach (explode('/', $combined) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            array_pop($segments);
            continue;
        }
        $segments[] = $segment;
    }

    return $authority . '/' . implode('/', $segments);
}

function sanitizeFileName(string $name): string
{
    $name = rawurldecode(basename(str_replace('\\', '/', $name)));
    $name = preg_replace('/[^\pL\pN._ -]+/u', '_', $name) ?? '';
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '', " .\t\n\r\0\x0B");

    if ($name === '') {
        return 'download-' . gmdate('Ymd-His') . '.bin';
    }

    if (strlen($name) > 180) {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $stem = pathinfo($name, PATHINFO_FILENAME);
        $suffix = $extension === '' ? '' : '.' . $extension;
        $name = substr($stem, 0, max(1, 180 - strlen($suffix))) . $suffix;
    }

    return $name;
}

function fileNameFromResponse(string $url, string $contentDisposition): string
{
    if (preg_match("/filename\\*=UTF-8''([^;]+)/i", $contentDisposition, $matches)) {
        return sanitizeFileName($matches[1]);
    }

    if (preg_match('/filename="?([^";]+)"?/i', $contentDisposition, $matches)) {
        return sanitizeFileName($matches[1]);
    }

    $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
    return sanitizeFileName(basename($path));
}

function uniqueDestination(string $directory, string $fileName): string
{
    $candidate = $directory . DIRECTORY_SEPARATOR . $fileName;
    if (!file_exists($candidate)) {
        return $candidate;
    }

    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $stem = pathinfo($fileName, PATHINFO_FILENAME);

    for ($index = 2; $index < 10000; $index++) {
        $suffix = '-' . $index . ($extension === '' ? '' : '.' . $extension);
        $candidate = $directory . DIRECTORY_SEPARATOR . $stem . $suffix;
        if (!file_exists($candidate)) {
            return $candidate;
        }
    }

    throw new RuntimeException('A unique destination filename could not be generated.');
}

/**
 * @return array{success: bool, message: string, file: string, bytes: int}
 */
function downloadRemoteFile(string $initialUrl, string $directory): array
{
    $initialValidation = validateRemoteUrl($initialUrl);
    if (!$initialValidation['valid']) {
        return ['success' => false, 'message' => $initialValidation['message'], 'file' => '', 'bytes' => 0];
    }

    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'cURL is not available on this server.', 'file' => '', 'bytes' => 0];
    }

    $temporaryPath = $directory . DIRECTORY_SEPARATOR . '.download-' . bin2hex(random_bytes(8)) . '.part';
    $currentUrl = $initialUrl;

    for ($redirect = 0; $redirect <= MAX_REDIRECTS; $redirect++) {
        $validation = validateRemoteUrl($currentUrl);
        if (!$validation['valid']) {
            @unlink($temporaryPath);
            return ['success' => false, 'message' => $validation['message'], 'file' => '', 'bytes' => 0];
        }

        $stream = @fopen($temporaryPath, 'wb');
        if ($stream === false) {
            return ['success' => false, 'message' => 'The temporary download file could not be opened.', 'file' => '', 'bytes' => 0];
        }

        $location = '';
        $contentDisposition = '';
        $tooLarge = false;
        $progressTooLarge = false;
        $curl = curl_init($currentUrl);
        $resolveEntries = [];

        foreach ($validation['addresses'] as $address) {
            $formattedAddress = str_contains($address, ':') ? '[' . $address . ']' : $address;
            $resolveEntries[] = $validation['host'] . ':' . $validation['port'] . ':' . $formattedAddress;
        }

        curl_setopt_array($curl, [
            CURLOPT_FILE => $stream,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_USERAGENT => 'PhysX-CNH Blackmagic Downloader/2.0',
            CURLOPT_HTTPHEADER => ['Accept: */*'],
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_RESOLVE => $resolveEntries,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_NOPROGRESS => false,
            CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$location, &$contentDisposition, &$tooLarge): int {
                $length = strlen($header);
                $separator = strpos($header, ':');
                if ($separator === false) {
                    return $length;
                }

                $name = strtolower(trim(substr($header, 0, $separator)));
                $value = trim(substr($header, $separator + 1));

                if ($name === 'location') {
                    $location = $value;
                } elseif ($name === 'content-disposition') {
                    $contentDisposition = $value;
                } elseif ($name === 'content-length' && ctype_digit($value) && (int) $value > MAX_DOWNLOAD_BYTES) {
                    $tooLarge = true;
                    return 0;
                }

                return $length;
            },
            CURLOPT_XFERINFOFUNCTION => static function ($handle, float $total, float $downloaded) use (&$progressTooLarge): int {
                if ($total > MAX_DOWNLOAD_BYTES || $downloaded > MAX_DOWNLOAD_BYTES) {
                    $progressTooLarge = true;
                    return 1;
                }
                return 0;
            },
        ]);

        $completed = curl_exec($curl);
        $curlError = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        fclose($stream);

        if ($tooLarge || $progressTooLarge) {
            @unlink($temporaryPath);
            return ['success' => false, 'message' => 'The remote file exceeds the 512 MB limit.', 'file' => '', 'bytes' => 0];
        }

        if ($status >= 300 && $status < 400 && $location !== '') {
            @unlink($temporaryPath);
            $nextUrl = resolveRedirectUrl($currentUrl, $location);
            if ($nextUrl === null) {
                return ['success' => false, 'message' => 'The remote server returned an invalid redirect.', 'file' => '', 'bytes' => 0];
            }
            $currentUrl = $nextUrl;
            continue;
        }

        if ($completed === false) {
            @unlink($temporaryPath);
            $message = str_contains(strtolower($curlError), 'maximum file size')
                ? 'The remote file exceeds the 512 MB limit.'
                : 'The remote download failed: ' . ($curlError !== '' ? $curlError : 'unknown cURL error');
            return ['success' => false, 'message' => $message, 'file' => '', 'bytes' => 0];
        }

        if ($status < 200 || $status >= 300) {
            @unlink($temporaryPath);
            return ['success' => false, 'message' => 'The remote server returned HTTP ' . $status . '.', 'file' => '', 'bytes' => 0];
        }

        $bytes = filesize($temporaryPath);
        if ($bytes === false || $bytes < 1) {
            @unlink($temporaryPath);
            return ['success' => false, 'message' => 'The remote server returned an empty file.', 'file' => '', 'bytes' => 0];
        }

        try {
            $fileName = fileNameFromResponse($currentUrl, $contentDisposition);
            $destination = uniqueDestination($directory, $fileName);
        } catch (Throwable $error) {
            @unlink($temporaryPath);
            return ['success' => false, 'message' => $error->getMessage(), 'file' => '', 'bytes' => 0];
        }

        if (!rename($temporaryPath, $destination)) {
            @unlink($temporaryPath);
            return ['success' => false, 'message' => 'The completed file could not be moved into the downloads folder.', 'file' => '', 'bytes' => 0];
        }

        return [
            'success' => true,
            'message' => 'Download completed.',
            'file' => basename($destination),
            'bytes' => (int) $bytes,
        ];
    }

    @unlink($temporaryPath);
    return ['success' => false, 'message' => 'The remote URL exceeded the redirect limit.', 'file' => '', 'bytes' => 0];
}

$githubResult = null;
$githubUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message === null) {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['hub_csrf'], $submittedToken)) {
        http_response_code(400);
        $message = 'The form expired. Refresh the page and try again.';
        $messageType = 'error';
    } else {
        $action = (string) ($_POST['action'] ?? 'download_url');
        if ($action === 'analyze_github') {
            $githubUrl = trim((string) ($_POST['github_url'] ?? ''));
            $githubResult = analyzeGitHubRepository($githubUrl);
        } elseif ($action === 'download_url') {
            $url = trim((string) ($_POST['file_url'] ?? ''));
            $result = downloadRemoteFile($url, $downloadDir);
            $message = $result['success']
                ? $result['message'] . ' ' . $result['file'] . ' (' . formatBytes($result['bytes']) . ')'
                : $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        } else {
            http_response_code(400);
            $message = 'Unknown action.';
            $messageType = 'error';
        }
    }
}

$files = [];
if (is_dir($downloadDir)) {
    $iterator = new DirectoryIterator($downloadDir);
    foreach ($iterator as $item) {
        if (!$item->isFile() || $item->isDot() || str_starts_with($item->getFilename(), '.')) {
            continue;
        }
        $files[] = [
            'name' => $item->getFilename(),
            'size' => $item->getSize(),
            'modified' => $item->getMTime(),
        ];
    }
}

usort($files, static fn (array $left, array $right): int => $right['modified'] <=> $left['modified']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Utility Hub · PhysX-CNH</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #070b17;
            --surface: #10182a;
            --surface-soft: #151f35;
            --border: #273552;
            --text: #eef4ff;
            --muted: #91a0ba;
            --accent: #67e8f9;
            --accent-strong: #22d3ee;
            --success: #34d399;
            --error: #fb7185;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at 15% 0%, rgba(34, 211, 238, 0.14), transparent 28rem),
                var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .shell {
            width: min(1080px, calc(100% - 32px));
            margin: 0 auto;
            padding: 56px 0 72px;
        }
        .eyebrow {
            margin: 0 0 10px;
            color: var(--accent);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }
        h1 {
            max-width: 720px;
            margin: 0;
            font-size: clamp(2.15rem, 6vw, 4.5rem);
            line-height: 0.98;
            letter-spacing: -0.055em;
        }
        .intro {
            max-width: 650px;
            margin: 20px 0 0;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.65;
        }
        .panel {
            margin-top: 34px;
            padding: clamp(20px, 4vw, 32px);
            border: 1px solid var(--border);
            border-radius: 20px;
            background: rgba(16, 24, 42, 0.92);
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
        }
        .panel h2 {
            margin: 0 0 8px;
            font-size: clamp(1.35rem, 4vw, 2rem);
        }
        .panel-description {
            margin: 0 0 20px;
            color: var(--muted);
            line-height: 1.55;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 750;
        }
        .download-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
        }
        input[type="url"] {
            min-width: 0;
            min-height: 52px;
            padding: 0 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            outline: none;
            background: #09101f;
            color: var(--text);
            font: inherit;
        }
        input[type="url"]:focus {
            border-color: var(--accent-strong);
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.14);
        }
        button,
        .file-link {
            min-height: 52px;
            border: 0;
            border-radius: 12px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
        }
        button {
            padding: 0 22px;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #04202a;
        }
        button:hover { filter: brightness(1.08); }
        .limit {
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 0.84rem;
        }
        .notice {
            margin-top: 18px;
            padding: 13px 15px;
            border: 1px solid currentColor;
            border-radius: 12px;
            font-weight: 650;
        }
        .notice.success { color: var(--success); background: rgba(52, 211, 153, 0.08); }
        .notice.error { color: var(--error); background: rgba(251, 113, 133, 0.08); }
        .inventory-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }
        .inventory-heading h2 {
            margin: 0;
            font-size: clamp(1.35rem, 4vw, 2rem);
        }
        .count {
            color: var(--muted);
            white-space: nowrap;
        }
        .file-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .file-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 18px;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--surface-soft);
        }
        .file-name {
            overflow-wrap: anywhere;
            font-weight: 750;
        }
        .file-meta {
            color: var(--muted);
            font-size: 0.84rem;
            white-space: nowrap;
        }
        .file-link {
            display: inline-flex;
            align-items: center;
            min-height: 40px;
            padding: 0 14px;
            background: #21304b;
            color: var(--text);
            text-decoration: none;
        }
        .file-link:hover { background: #2c4165; }
        .result-card {
            margin-top: 22px;
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #09101f;
        }
        .result-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 16px;
        }
        .result-head h3 {
            margin: 0;
            font-size: 1.25rem;
        }
        .result-head a,
        .release-title a {
            color: var(--accent);
        }
        .description {
            margin: 8px 0 0;
            color: var(--muted);
            line-height: 1.5;
        }
        .branch {
            flex: 0 0 auto;
            padding: 5px 9px;
            border: 1px solid var(--border);
            border-radius: 999px;
            color: var(--accent);
            font-size: 0.78rem;
            font-weight: 750;
        }
        .direct-list {
            display: grid;
            gap: 10px;
            margin: 18px 0 0;
            padding: 0;
            list-style: none;
        }
        .direct-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface-soft);
        }
        .direct-details { min-width: 0; }
        .direct-name {
            display: block;
            overflow-wrap: anywhere;
            font-weight: 750;
        }
        .direct-meta {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 0.8rem;
            overflow-wrap: anywhere;
        }
        .action-link,
        .copy-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 800;
            text-decoration: none;
        }
        .action-link {
            background: var(--accent-strong);
            color: #04202a;
        }
        .copy-button {
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
        }
        .release-title {
            margin: 24px 0 0;
            font-size: 1.05rem;
        }
        .inline-notice {
            margin: 18px 0 0;
            color: var(--muted);
        }
        .empty {
            margin: 0;
            padding: 28px;
            border: 1px dashed var(--border);
            border-radius: 14px;
            color: var(--muted);
            text-align: center;
        }
        @media (max-width: 680px) {
            .shell { padding-top: 34px; }
            .download-form { grid-template-columns: 1fr; }
            button { width: 100%; }
            .result-head { display: block; }
            .branch {
                display: inline-block;
                margin-top: 12px;
            }
            .direct-row {
                grid-template-columns: 1fr 1fr;
            }
            .direct-details { grid-column: 1 / -1; }
            .action-link,
            .copy-button { width: 100%; }
            .file-row {
                grid-template-columns: 1fr auto;
                gap: 8px 12px;
            }
            .file-meta { white-space: normal; }
            .file-link {
                grid-column: 2;
                grid-row: 1 / span 2;
            }
        }
    </style>
</head>
<body>
<main class="shell">
    <p class="eyebrow">PhysX-CNH Utility Hub</p>
    <h1>Fetch files and inspect GitHub releases.</h1>
    <p class="intro">Turn a repository page into direct source and release links, or ask the server to fetch a remote file into the shared downloads folder.</p>

    <section class="panel" aria-labelledby="github-title">
        <h2 id="github-title">GitHub repository</h2>
        <p class="panel-description">Paste a public repository link to find its default-branch source archive and every file attached to the latest published release.</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['hub_csrf']) ?>">
            <input type="hidden" name="action" value="analyze_github">
            <label for="github_url">Repository URL</label>
            <div class="download-form">
                <input type="url" id="github_url" name="github_url" value="<?= escapeHtml($githubUrl) ?>" placeholder="https://github.com/dvx/lofi" required>
                <button type="submit">Find direct links</button>
            </div>
        </form>

        <?php if (is_array($githubResult) && !$githubResult['success']): ?>
            <div class="notice error" role="alert"><?= escapeHtml($githubResult['message']) ?></div>
        <?php elseif (is_array($githubResult) && isset($githubResult['repository'])): ?>
            <?php $repository = $githubResult['repository']; ?>
            <div class="result-card">
                <div class="result-head">
                    <div>
                        <h3><a href="<?= escapeHtml($repository['html_url']) ?>" target="_blank" rel="noopener noreferrer"><?= escapeHtml($repository['full_name']) ?></a></h3>
                        <?php if ($repository['description'] !== ''): ?>
                            <p class="description"><?= escapeHtml($repository['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span class="branch">Default: <?= escapeHtml($repository['default_branch']) ?></span>
                </div>

                <ul class="direct-list">
                    <li class="direct-row">
                        <div class="direct-details">
                            <span class="direct-name">Default branch source (.zip)</span>
                            <span class="direct-meta"><?= escapeHtml($repository['archive_url']) ?></span>
                        </div>
                        <a class="action-link" href="<?= escapeHtml($repository['archive_url']) ?>">Download ZIP</a>
                        <button class="copy-button" type="button" data-copy-url="<?= escapeHtml($repository['archive_url']) ?>">Copy link</button>
                    </li>
                </ul>

                <?php if (is_array($githubResult['release'])): ?>
                    <?php $release = $githubResult['release']; ?>
                    <h4 class="release-title">
                        Latest release: <?= escapeHtml($release['name'] !== '' ? $release['name'] : $release['tag_name']) ?>
                        <?php if ($release['html_url'] !== ''): ?>
                            · <a href="<?= escapeHtml($release['html_url']) ?>" target="_blank" rel="noopener noreferrer">release page</a>
                        <?php endif; ?>
                    </h4>
                    <?php if ($release['assets'] === []): ?>
                        <p class="inline-notice">This release has no uploaded files.</p>
                    <?php else: ?>
                        <ul class="direct-list">
                            <?php foreach ($release['assets'] as $asset): ?>
                                <li class="direct-row">
                                    <div class="direct-details">
                                        <span class="direct-name"><?= escapeHtml($asset['name']) ?></span>
                                        <span class="direct-meta"><?= escapeHtml(formatBytes((int) $asset['size'])) ?> · <?= number_format((int) $asset['download_count']) ?> downloads</span>
                                    </div>
                                    <a class="action-link" href="<?= escapeHtml($asset['url']) ?>">Download</a>
                                    <button class="copy-button" type="button" data-copy-url="<?= escapeHtml($asset['url']) ?>">Copy link</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="inline-notice"><?= escapeHtml($githubResult['message']) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel" aria-labelledby="download-title">
        <h2 id="download-title">URL downloader</h2>
        <p class="panel-description">Fetch a public HTTP or HTTPS URL into this Hub’s downloads folder.</p>
        <form method="post">
            <label for="file_url">Remote file URL</label>
            <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['hub_csrf']) ?>">
            <input type="hidden" name="action" value="download_url">
            <div class="download-form">
                <input type="url" id="file_url" name="file_url" placeholder="https://example.com/archive.zip" required>
                <button type="submit">Fetch file</button>
            </div>
            <p class="limit">HTTP/HTTPS only · Public hosts only · Maximum file size 512 MB</p>
        </form>

        <?php if ($message !== null): ?>
            <div class="notice <?= escapeHtml($messageType) ?>" role="status"><?= escapeHtml($message) ?></div>
        <?php endif; ?>
    </section>

    <section class="panel" aria-labelledby="files-title">
        <div class="inventory-heading">
            <h2 id="files-title">Available downloads</h2>
            <span class="count"><?= count($files) ?> file<?= count($files) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($files === []): ?>
            <p class="empty">No files are available yet.</p>
        <?php else: ?>
            <ul class="file-list">
                <?php foreach ($files as $file): ?>
                    <li class="file-row">
                        <span class="file-name"><?= escapeHtml($file['name']) ?></span>
                        <span class="file-meta">
                            <?= escapeHtml(formatBytes((int) $file['size'])) ?><br>
                            <?= escapeHtml(gmdate('Y-m-d H:i', (int) $file['modified'])) ?> UTC
                        </span>
                        <a class="file-link" href="/hub/download.php?file=<?= rawurlencode($file['name']) ?>">Download</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
<script>
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-copy-url]');
        if (!button) return;

        const originalLabel = button.textContent;
        try {
            await navigator.clipboard.writeText(button.dataset.copyUrl);
            button.textContent = 'Copied';
        } catch {
            button.textContent = 'Copy failed';
        }
        window.setTimeout(() => { button.textContent = originalLabel; }, 1600);
    });
</script>
</body>
</html>
