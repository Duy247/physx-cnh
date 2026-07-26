<?php
declare(strict_types=1);

function callPostmanApi(string $url, string $apiKey): array
{
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'cURL is not available on this server.', 'http_code' => 0, 'data' => null];
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-Api-Key: ' . $apiKey,
            'Accept: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) {
        return ['success' => false, 'message' => 'Connection error: ' . $curlError, 'http_code' => $httpCode, 'data' => null];
    }

    $decoded = json_decode($response, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        $apiMessage = is_array($decoded) ? (string) ($decoded['error']['message'] ?? '') : '';
        return [
            'success' => false,
            'message' => $apiMessage !== '' ? $apiMessage : 'Postman returned HTTP ' . $httpCode . '.',
            'http_code' => $httpCode,
            'data' => $decoded,
        ];
    }

    return ['success' => true, 'message' => 'OK', 'http_code' => $httpCode, 'data' => $decoded];
}

function safeFileName(string $name): string
{
    $name = preg_replace('/[\\\\\\/:"*?<>|]+/', '_', $name) ?? '';
    $name = preg_replace('/\s+/', '_', $name) ?? '';
    $name = trim($name, '._');
    return $name !== '' ? $name : 'Unnamed_Collection';
}

function removeTemporaryDirectory(string $directory): void
{
    foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    @rmdir($directory);
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiKey = trim((string) ($_POST['api_key'] ?? ''));

    if ($apiKey === '') {
        $result = ['success' => false, 'message' => 'A Postman API key is required.'];
    } elseif (!class_exists('ZipArchive')) {
        $result = ['success' => false, 'message' => 'ZIP support is not available on this server.'];
    } else {
        $listResponse = callPostmanApi('https://api.getpostman.com/collections', $apiKey);

        if (!$listResponse['success']) {
            $result = ['success' => false, 'message' => 'Collections could not be loaded. ' . $listResponse['message']];
        } else {
            $collections = $listResponse['data']['collections'] ?? [];
            $temporaryDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'postman_collections_' . bin2hex(random_bytes(6));

            if (!mkdir($temporaryDirectory, 0700, true) && !is_dir($temporaryDirectory)) {
                $result = ['success' => false, 'message' => 'A temporary export folder could not be created.'];
            } else {
                $zipPath = $temporaryDirectory . DIRECTORY_SEPARATOR . 'postman_collections.zip';
                $zip = new ZipArchive();
                $zipOpened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true;
                $exported = 0;

                if (!$zipOpened) {
                    $result = ['success' => false, 'message' => 'The collection ZIP could not be created.'];
                } else {
                    foreach ($collections as $collection) {
                        $uid = (string) ($collection['uid'] ?? '');
                        $name = (string) ($collection['name'] ?? 'Unnamed Collection');
                        if ($uid === '') {
                            continue;
                        }

                        $detail = callPostmanApi('https://api.getpostman.com/collections/' . rawurlencode($uid), $apiKey);
                        if (!$detail['success'] || !isset($detail['data']['collection'])) {
                            continue;
                        }

                        $json = json_encode(
                            $detail['data']['collection'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        );
                        if ($json === false) {
                            continue;
                        }

                        $zip->addFromString(safeFileName($name) . '.json', $json);
                        $exported++;
                    }
                    $zip->close();

                    if ($exported < 1 || !is_file($zipPath)) {
                        $result = ['success' => false, 'message' => 'No collections could be exported with this API key.'];
                    } else {
                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename="postman_collections.zip"');
                        header('Content-Length: ' . filesize($zipPath));
                        header('Cache-Control: no-store');
                        readfile($zipPath);
                        removeTemporaryDirectory($temporaryDirectory);
                        exit;
                    }
                }

                removeTemporaryDirectory($temporaryDirectory);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Postman Collection Exporter</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #0b0d12;
            --surface: #151922;
            --surface-2: #1b202b;
            --border: #2b3240;
            --text: #f7f8fa;
            --muted: #a7afbe;
            --accent: #ff6c37;
            --accent-hover: #ff8358;
            --error: #ff8a9a;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at 80% -10%, rgba(255, 108, 55, 0.2), transparent 34rem),
                var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .shell {
            width: min(1040px, calc(100% - 32px));
            margin: 0 auto;
            padding: 28px 0 72px;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--text);
            font-weight: 850;
            text-decoration: none;
        }
        .brand-mark {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border-radius: 10px;
            background: var(--accent);
            color: #1c0a03;
        }
        nav {
            display: flex;
            gap: 6px;
            padding: 4px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: rgba(21, 25, 34, 0.82);
        }
        nav a {
            padding: 8px 12px;
            border-radius: 8px;
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
        }
        nav a[aria-current="page"] {
            background: var(--surface-2);
            color: var(--text);
        }
        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(260px, 0.8fr);
            gap: 42px;
            align-items: end;
            padding: 84px 0 34px;
        }
        .eyebrow {
            margin: 0 0 14px;
            color: var(--accent);
            font-size: 0.76rem;
            font-weight: 850;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }
        h1 {
            margin: 0;
            font-size: clamp(2.5rem, 7vw, 5.25rem);
            line-height: 0.95;
            letter-spacing: -0.065em;
        }
        .hero p:last-child {
            margin: 0;
            color: var(--muted);
            font-size: 1.04rem;
            line-height: 1.65;
        }
        .card {
            padding: clamp(22px, 4vw, 34px);
            border: 1px solid var(--border);
            border-radius: 20px;
            background: rgba(21, 25, 34, 0.94);
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.28);
        }
        .card-head {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }
        .card h2 { margin: 0 0 7px; font-size: 1.35rem; }
        .card-copy { margin: 0; color: var(--muted); line-height: 1.55; }
        .secure {
            flex: none;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(78, 217, 158, 0.1);
            color: #78e6b7;
            font-size: 0.76rem;
            font-weight: 800;
        }
        label {
            display: block;
            margin-bottom: 9px;
            font-size: 0.9rem;
            font-weight: 750;
        }
        .key-field { position: relative; }
        input {
            width: 100%;
            min-height: 54px;
            padding: 0 92px 0 15px;
            border: 1px solid var(--border);
            border-radius: 12px;
            outline: none;
            background: #0d1118;
            color: var(--text);
            font: inherit;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 108, 55, 0.15);
        }
        .reveal {
            position: absolute;
            top: 7px;
            right: 7px;
            min-height: 40px;
            padding: 0 12px;
            border: 0;
            border-radius: 8px;
            background: var(--surface-2);
            color: var(--muted);
            font-weight: 750;
            cursor: pointer;
        }
        .submit {
            width: 100%;
            min-height: 54px;
            margin-top: 14px;
            border: 0;
            border-radius: 12px;
            background: var(--accent);
            color: #210b03;
            font: inherit;
            font-weight: 850;
            cursor: pointer;
        }
        .submit:hover { background: var(--accent-hover); }
        .submit:disabled { cursor: wait; opacity: 0.72; }
        .privacy {
            margin: 13px 0 0;
            color: var(--muted);
            font-size: 0.8rem;
            text-align: center;
        }
        .alert {
            margin-top: 16px;
            padding: 13px 15px;
            border: 1px solid var(--error);
            border-radius: 12px;
            background: rgba(255, 138, 154, 0.08);
            color: var(--error);
            line-height: 1.45;
        }
        @media (max-width: 720px) {
            .topbar { align-items: flex-start; flex-direction: column; }
            .hero { grid-template-columns: 1fr; gap: 18px; padding-top: 56px; }
            .card-head { flex-direction: column; }
        }
    </style>
</head>
<body>
<main class="shell">
    <header class="topbar">
        <a class="brand" href="/postman">
            <span class="brand-mark" aria-hidden="true">P</span>
            Postman Exporter
        </a>
        <nav aria-label="Export type">
            <a href="/postman" aria-current="page">Collections</a>
            <a href="/postman/env">Environments</a>
        </nav>
    </header>

    <section class="hero">
        <div>
            <p class="eyebrow">Workspace backup utility</p>
            <h1>Take your collections with you.</h1>
        </div>
        <p>Export every collection available to your Postman API key as import-ready JSON files inside one ZIP archive.</p>
    </section>

    <section class="card" aria-labelledby="export-title">
        <div class="card-head">
            <div>
                <h2 id="export-title">Export collections</h2>
                <p class="card-copy">Your download starts automatically after the export is assembled.</p>
            </div>
            <span class="secure">Key not stored</span>
        </div>

        <form method="post" id="export-form" autocomplete="off">
            <label for="api_key">Postman API key</label>
            <div class="key-field">
                <input type="password" id="api_key" name="api_key" placeholder="PMAK-••••••••••••••••" required>
                <button class="reveal" type="button" id="reveal-key" aria-controls="api_key" aria-pressed="false">Show</button>
            </div>
            <button class="submit" type="submit" id="submit-button">Build &amp; download ZIP</button>
            <p class="privacy">The key is sent only to the official Postman API for this request.</p>
        </form>

        <?php if (is_array($result) && !$result['success']): ?>
            <div class="alert" role="alert"><?= htmlspecialchars((string) $result['message'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </section>
</main>
<script>
    (function () {
        const keyInput = document.getElementById('api_key');
        const revealButton = document.getElementById('reveal-key');
        const form = document.getElementById('export-form');
        const submitButton = document.getElementById('submit-button');

        revealButton.addEventListener('click', function () {
            const revealing = keyInput.type === 'password';
            keyInput.type = revealing ? 'text' : 'password';
            revealButton.textContent = revealing ? 'Hide' : 'Show';
            revealButton.setAttribute('aria-pressed', String(revealing));
        });

        form.addEventListener('submit', function () {
            submitButton.disabled = true;
            submitButton.textContent = 'Building export…';
        });
    })();
</script>
</body>
</html>
