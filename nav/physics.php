<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/CatalogRepository.php';

/** @var array<string, array<string, mixed>> $catalogs */
$catalogs = require __DIR__ . '/../config/catalogs.php';

$type = isset($_GET['type']) ? (string) $_GET['type'] : 'book';
$level = isset($_GET['level']) ? (string) $_GET['level'] : 'pre-vpho';
$catalog = $catalogs[$type . ':' . $level] ?? $catalogs['book:pre-vpho'];
$catalogItems = [];
$catalogLoadFailed = false;

try {
    $repository = new CatalogRepository(__DIR__ . '/../physics');
    $catalogItems = $repository->load((string) $catalog['manifest']);
} catch (CatalogException $error) {
    $catalogLoadFailed = true;
    error_log('PhysX-CNH catalog error: ' . $error->getMessage());
}

$catalogText = static fn (string $value): string => nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), false);
$catalogSearchText = static fn (string $value): string => htmlspecialchars(
    preg_replace('/\s+/u', ' ', $value) ?? $value,
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($catalog['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="/image/favicon.ico">
    <link rel="stylesheet" href="/css/common.css?v=20260726-1">
    <link rel="stylesheet" href="/nav/style.css?v=20260726-4">
</head>
<body>
    <header class="mobile-header">
        <a href="/welcome" aria-label="PhysX-CNH home"><img src="/image/logo.png" alt="PhysX-CNH"></a>
        <button id="menu-toggle" type="button" aria-controls="menu" aria-expanded="false" aria-label="Mở menu">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
    </header>
    <nav id="menu" aria-label="Điều hướng chính">
        <div id="m-top-part">
            <img src="/image/logo.png" class="logo" alt="PhysX-CNH">
        </div>
        <div id="m-bottom-part">
            <ul class="menu-list">
                <li><a href="/welcome">Trang chủ</a></li>
                <li><a href="/nav/physics?type=magazines&amp;level=all">Tạp Chí</a></li>
                <li><a href="/nav/physics?type=material&amp;level=pho">Tài Liệu</a></li>
                <li class="dropdown">
                    <div class="dropdown-toggle">
                        <a href="#" aria-label="Mở danh mục sách">Sách</a>
                    </div>
                    <div class="dropdown-content">
                        <a href="/nav/physics?type=book&amp;level=pre-vpho">Sách Bồi Dưỡng<br>HSG Cấp Thành Phố</a>
                        <a href="/nav/physics?type=book&amp;level=vpho-vn">Sách Tiếng Việt<br>Vòng 2 Thành Phố / HSGQG</a>
                        <a href="/nav/physics?type=book&amp;level=vpho-en">Sách Tiếng Anh<br>Vòng 2 Thành Phố / HSGQG</a>
                    </div>
                </li>
                <li><a href="/nav/physics?type=paper-sol&amp;level=pho">Đề Thi &amp; Đáp Án</a></li>
                <li><a href="/nav/physics?type=lessons&amp;level=all">Nội Dung Ngày Học</a></li>
                <li><a href="/roadmap">Lộ trình ôn tập</a></li>
                <li><a href="/article">Cách tìm bài báo khoa học</a></li>
                <li><a href="/donate">Ủng hộ duy trì trang web</a></li>
                <li><a href="https://astronomy.physx-cnh.com"><b>AstroGallery</b></a></li>
            </ul>
            <div class="bottom-links">
                <ul class="menu-list-bottom">
                    <li><a href="/disclaimer">Miễn Trừ Trách Nhiệm Pháp Lý</a></li>
                    <li><a href="/welcome">Lượt Truy Cập: <span id="hitCount">0</span></a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div id="menu-backdrop" hidden></div>

    <aside class="menu" id="catalog-menu">
        <div class="fixed-header">
            <a href="/welcome" aria-label="PhysX-CNH home">
                <div id="logo-container"><img src="/image/logo.png" alt="PhysX-CNH" class="logo"></div>
            </a>
            <h2><?php echo htmlspecialchars($catalog['heading'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <h3><?php echo htmlspecialchars($catalog['subheading'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <div class="search-container">
                <label class="visually-hidden" for="search-input">Tìm tài liệu</label>
                <input type="search" id="search-input" placeholder="Tìm theo tên hoặc tác giả...">
            </div>
            <div class="sort-options">
                <label for="sort-select">Sắp theo:</label>
                <select id="sort-select">
                    <option value="title">Tên tài liệu</option>
                    <option value="author">Tác giả</option>
                </select>
            </div>
        </div>

        <div class="book-container" role="list">
            <?php if ($catalogLoadFailed): ?>
                <p class="catalog-error">Không thể tải danh mục tài liệu.</p>
            <?php else: ?>
                <?php foreach ($catalogItems as $item): ?>
                    <?php
                    $resourceUrl = CatalogRepository::resourceUrl($item['file']);
                    $isPdf = strtolower(pathinfo($item['file'], PATHINFO_EXTENSION)) === 'pdf';
                    ?>
                    <article
                        class="book-item"
                        role="listitem"
                        data-title="<?php echo $catalogSearchText($item['title']); ?>"
                        data-author="<?php echo $catalogSearchText($item['author']); ?>"
                    >
                        <div class="book-details">
                            <strong><?php echo $catalogText($item['title']); ?></strong>
                            <?php if ($item['author'] !== ''): ?>
                                <br><?php echo $catalogText($item['author']); ?>
                            <?php endif; ?>
                            <?php if ($item['description'] !== ''): ?>
                                <br><span class="book-description"><?php echo $catalogText($item['description']); ?></span>
                            <?php endif; ?>
                            <?php if ($item['source'] !== ''): ?>
                                <br><span class="book-source">Nguồn: <?php echo $catalogText($item['source']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="book-actions">
                            <a class="open-resource" href="<?php echo htmlspecialchars($resourceUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo $isPdf ? 'Mở PDF' : 'Mở tài liệu'; ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button class="menu-toggle" type="button" aria-controls="catalog-menu" aria-expanded="true" aria-label="Thu gọn danh mục">&lt;</button>
    </aside>

    <main class="iframe-container">
        <iframe id="content-iframe" src="<?php echo htmlspecialchars($catalog['welcome'], ENT_QUOTES, 'UTF-8'); ?>" title="Trình xem tài liệu"></iframe>
    </main>
    <script src="/js/site-shell.js?v=20260726-1"></script>
    <script src="/nav/script.js?v=20260726-1"></script>
</body>
</html>
