<?php
declare(strict_types=1);

const MAX_DOWNLOAD_BYTES = 536870912; // 512 MiB
const MAX_REDIRECTS = 5;

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

if (!isset($_SESSION['blackmagic_csrf']) || !is_string($_SESSION['blackmagic_csrf'])) {
    $_SESSION['blackmagic_csrf'] = bin2hex(random_bytes(24));
}

$downloadDir = __DIR__ . DIRECTORY_SEPARATOR . 'downloads';
$message = null;
$messageType = 'success';

if (!is_dir($downloadDir) && !mkdir($downloadDir, 0755, true) && !is_dir($downloadDir)) {
    http_response_code(500);
    $message = 'The downloads folder could not be created.';
    $messageType = 'error';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message === null) {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['blackmagic_csrf'], $submittedToken)) {
        http_response_code(400);
        $message = 'The form expired. Refresh the page and try again.';
        $messageType = 'error';
    } else {
        $url = trim((string) ($_POST['file_url'] ?? ''));
        $result = downloadRemoteFile($url, $downloadDir);
        $message = $result['success']
            ? $result['message'] . ' ' . $result['file'] . ' (' . formatBytes($result['bytes']) . ')'
            : $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
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
    <title>Blackmagic URL Downloader</title>
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
    <p class="eyebrow">Blackmagic Utility</p>
    <h1>Download a remote file by URL.</h1>
    <p class="intro">The server streams the source into the local downloads folder. Existing files remain available below for direct, resumable download.</p>

    <section class="panel" aria-labelledby="download-title">
        <form method="post">
            <label id="download-title" for="file_url">Remote file URL</label>
            <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['blackmagic_csrf']) ?>">
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
                        <a class="file-link" href="/blackmagic/download.php?file=<?= rawurlencode($file['name']) ?>">Download</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
