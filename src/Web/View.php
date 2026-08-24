<?php
declare(strict_types=1);

final class View
{
    public function __construct(private readonly string $templateRoot)
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], int $status = 200): never
    {
        http_response_code($status);
        extract($data, EXTR_SKIP);
        ob_start();
        require $this->templateRoot . '/pages/' . $template . '.php';
        $content = (string) ob_get_clean();
        require $this->templateRoot . '/layout.php';
        exit;
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function icon(string $name, int $size = 20): string
{
    $paths = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'grid' => '<rect width="7" height="7" x="3" y="3"/><rect width="7" height="7" x="14" y="3"/><rect width="7" height="7" x="14" y="14"/><rect width="7" height="7" x="3" y="14"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'atom' => '<circle cx="12" cy="12" r="1"/><path d="M20.2 20.2c2.04-2.03-.79-8.15-6.3-13.66S2.27-1.8.24.24s.79 8.15 6.3 13.66 11.63 8.34 13.66 6.3Z" transform="translate(1.78 1.78) scale(.85)"/><path d="M3.8 20.2c-2.04-2.03.79-8.15 6.3-13.66S21.73-1.8 23.76.24s-.79 8.15-6.3 13.66S5.83 22.24 3.8 20.2Z" transform="translate(-1.78 1.78) scale(.85)"/>',
        'orbit' => '<circle cx="12" cy="12" r="2.6"/><circle cx="19" cy="5" r="1.8"/><circle cx="5" cy="19" r="1.8"/><path d="M10.2 2.25a10 10 0 0 1 11.55 11.55M13.8 21.75A10 10 0 0 1 2.25 10.2"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
        'satellite' => '<path d="m13 7 4 4M14 4l6 6M5 19l4-4M3 21l6-6M9 11l4 4M7 13l4 4"/><path d="M15 3 3 15l6 6L21 9z"/>',
        'expand' => '<path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21.3l7.8-7.8 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/>',
    ];
    $body = $paths[$name] ?? $paths['file'];
    return '<svg aria-hidden="true" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}
