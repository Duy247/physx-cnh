<?php
declare(strict_types=1);

final class App
{
    public function __construct(
        private readonly CatalogSnapshot $catalog,
        private readonly View $view,
    ) {
    }

    public function run(): never
    {
        $path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $path = '/' . trim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $legacyPages = [
            '/welcome' => '/',
            '/roadmap' => '/guides/roadmap',
            '/article' => '/guides/research',
        ];
        if (isset($legacyPages[$path])) {
            header('Location: ' . $legacyPages[$path], true, 301);
            exit;
        }

        if ($path === '/') {
            $this->page('hub', 'CNH Study Hub — Thư viện học liệu mở', 'hub', true, [
                'count' => (int) $this->catalog->all()['counts']['published'],
            ]);
        }
        if ($path === '/physics') {
            $supportedKinds = ['book', 'paper', 'material', 'magazine'];
            $documents = array_values(array_filter(
                $this->catalog->documents(),
                static fn (array $document): bool => in_array($document['kind'] ?? '', $supportedKinds, true),
            ));
            $this->page('physics', 'Vật lý — Thư viện chuyên', 'physics', true, [
                'inbound' => array_reverse(array_slice($documents, -4)),
            ]);
        }
        if ($path === '/about') {
            $this->page('about', 'Về Duy và PhysX-CNH', 'hub', false, [
                'count' => (int) $this->catalog->all()['counts']['published'],
            ]);
        }
        $fieldSpaces = [
            '/math' => ['Toán học', 'MATHEMATICS / 02'],
            '/it' => ['Tin học', 'COMPUTING / 03'],
            '/chemistry' => ['Hóa học', 'CHEMISTRY / 04'],
        ];
        if (isset($fieldSpaces[$path])) {
            [$field, $code] = $fieldSpaces[$path];
            $this->page('field', $field . ' — CNH Study Hub', 'hub', false, ['field' => $field, 'code' => $code]);
        }
        if ($path === '/library') {
            $this->library();
        }
        if ($path === '/nav/physics' || $path === '/nav/physics_mobile') {
            $legacyKinds = ['book' => 'book', 'paper-sol' => 'paper', 'material' => 'material', 'magazines' => 'magazine'];
            $kind = $legacyKinds[(string) ($_GET['type'] ?? '')] ?? '';
            $query = $kind === '' ? '' : '?kind=' . rawurlencode($kind);
            header('Location: /library' . $query, true, 301);
            exit;
        }
        if (preg_match('#^/library/([a-z0-9-]+)$#', $path, $match)) {
            $this->library($match[1]);
        }
        if (preg_match('#^/document/([a-z0-9-]+)$#', $path, $match)) {
            $this->document($match[1]);
        }
        if ($path === '/guides/roadmap') {
            $this->page('graph', 'Lộ trình ôn tập Vật lý', 'physics', false, ['graph' => 'roadmap']);
        }
        if ($path === '/guides/research') {
            $this->page('graph', 'Cách tìm một bài báo khoa học', 'physics', false, ['graph' => 'research']);
        }
        if ($path === '/donate') {
            $this->page('donate', 'Ủng hộ PhysX-CNH', 'physics');
        }
        if ($path === '/donators') {
            $this->page('donators', 'Người đóng góp', 'physics');
        }
        if ($path === '/legal') {
            $this->page('legal', 'Pháp lý và bản quyền', 'physics');
        }
        if ($path === '/sitemap.xml') {
            $this->sitemap();
        }
        if ($path === '/robots.txt') {
            header('Content-Type: text/plain; charset=utf-8');
            echo "User-agent: *\nAllow: /\nSitemap: https://physx-cnh.com/sitemap.xml\n";
            exit;
        }
        $this->page('not-found', 'Không tìm thấy trang', 'physics', false, [], 404);
    }

    /** @param array<string, mixed> $data */
    private function page(string $template, string $title, string $site, bool $immersive = false, array $data = [], int $status = 200): never
    {
        $this->view->render($template, array_merge($data, [
            'title' => $title,
            'description' => 'Kho học liệu mở dành cho học sinh chuyên Vật lý.',
            'site' => $site,
            'immersive' => $immersive,
            'pageCss' => in_array($template, ['donate', 'donators', 'legal'], true) ? 'article' : ($template === 'not-found' ? '' : $template),
            'catalogService' => $this->catalog,
        ]), $status);
    }

    private function library(?string $collectionId = null): never
    {
        $all = $this->catalog->all();
        $documents = $this->catalog->documents();
        $collection = null;
        if ($collectionId !== null) {
            $collection = $this->catalog->collection($collectionId);
            if ($collection === null) {
                $this->page('not-found', 'Không tìm thấy bộ sưu tập', 'physics', false, [], 404);
            }
            $documents = array_values(array_filter($documents, static fn (array $document): bool => ($document['collectionId'] ?? '') === $collectionId));
        }

        $kind = isset($_GET['kind']) && in_array($_GET['kind'], ['book', 'paper', 'material', 'magazine'], true) ? (string) $_GET['kind'] : 'all';
        $orbit = ($_GET['orbit'] ?? '') === '1' && $kind !== 'all';
        $titles = ['book' => 'Sách', 'paper' => 'Đề thi & đáp án', 'material' => 'Tài liệu và handout', 'magazine' => 'Tạp chí Vật lý'];
        $scoped = $kind === 'all' ? $documents : array_values(array_filter($documents, static fn (array $document): bool => ($document['kind'] ?? '') === $kind));
        $heading = $collection['title'] ?? ($titles[$kind] ?? 'Thư viện Vật lý');

        $this->view->render('library', [
            'title' => $heading,
            'description' => 'Tìm kiếm sách, đề thi, handout và tạp chí Vật lý.',
            'site' => 'physics',
            'immersive' => false,
            'pageCss' => 'library',
            'documents' => $documents,
            'initialDocuments' => $scoped,
            'heading' => $heading,
            'kind' => $kind,
            'orbit' => $orbit,
            'catalogService' => $this->catalog,
        ]);
    }

    private function document(string $slug): never
    {
        $document = $this->catalog->document($slug);
        if ($document === null) {
            $this->page('not-found', 'Không tìm thấy tài liệu', 'physics', false, [], 404);
        }
        $url = $this->catalog->resourceUrl($document);
        if (($document['format'] ?? '') === 'pdf') {
            header('Cache-Control: no-store');
            header('Location: /assets/v2/pdfjs/web/viewer.html?file=' . rawurlencode($url), true, 302);
            exit;
        }
        header('Location: ' . $url, true, 302);
        exit;
    }

    private function sitemap(): never
    {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = ['/', '/about', '/physics', '/math', '/it', '/chemistry', '/library', '/guides/roadmap', '/guides/research', '/donate', '/donators', '/legal'];
        foreach ($this->catalog->documents() as $document) {
            $urls[] = '/document/' . rawurlencode((string) $document['slug']);
        }
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            echo '<url><loc>https://physx-cnh.com' . e($url) . '</loc></url>';
        }
        echo '</urlset>';
        exit;
    }
}
