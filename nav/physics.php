<?php
declare(strict_types=1);

$catalogs = [
    'book:pre-vpho' => [
        'title' => 'Sách trước Vòng chọn VPhO',
        'heading' => 'SÁCH IN / ẤN BẢN',
        'subheading' => 'Trước vòng chọn VPhO',
        'file' => __DIR__ . '/../physics/books-pre-vpho.txt',
        'welcome' => '/nav/welcome/books-pre-vpho.html',
    ],
    'book:vpho-vn' => [
        'title' => 'Sách VPhO và Vòng chọn (VN)',
        'heading' => 'SÁCH IN / ẤN BẢN',
        'subheading' => 'VPhO và vòng chọn',
        'file' => __DIR__ . '/../physics/books-vpho-vn.txt',
        'welcome' => '/nav/welcome/books-vpho-vn.html',
    ],
    'book:vpho-en' => [
        'title' => 'Sách VPhO và Vòng chọn (EN)',
        'heading' => 'SÁCH IN / ẤN BẢN — TIẾNG ANH',
        'subheading' => 'VPhO và vòng chọn',
        'file' => __DIR__ . '/../physics/books-vpho-en.txt',
        'welcome' => '/nav/welcome/books-vpho-en.html',
    ],
    'material:pho' => [
        'title' => 'Tài liệu và handouts',
        'heading' => 'TÀI LIỆU / HANDOUTS',
        'subheading' => 'VPhO trở lên',
        'file' => __DIR__ . '/../physics/materials-pho.txt',
        'welcome' => '/nav/welcome/materials-pho.html',
    ],
    'paper-sol:pho' => [
        'title' => 'Đề thi & Đáp án',
        'heading' => 'ĐỀ THI & ĐÁP ÁN',
        'subheading' => 'PhO cấp khu vực đến quốc tế',
        'file' => __DIR__ . '/../physics/paper-sol-pho.txt',
        'welcome' => '/nav/welcome/paper-sol-pho.html',
    ],
    'magazines:all' => [
        'title' => 'Tạp chí',
        'heading' => 'TẠP CHÍ',
        'subheading' => 'PhO cấp khu vực đến quốc tế',
        'file' => __DIR__ . '/../physics/magazines.txt',
        'welcome' => '/nav/welcome/magazines.html',
    ],
    'lessons:all' => [
        'title' => 'Nội dung ngày học',
        'heading' => 'NỘI DUNG NGÀY HỌC',
        'subheading' => 'Đội tuyển vật lí CNH',
        'file' => __DIR__ . '/../physics/lessons.txt',
        'welcome' => '/nav/welcome/lessons.html',
    ],
];

$type = isset($_GET['type']) ? (string) $_GET['type'] : 'book';
$level = isset($_GET['level']) ? (string) $_GET['level'] : 'pre-vpho';
$catalog = $catalogs[$type . ':' . $level] ?? $catalogs['book:pre-vpho'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($catalog['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="/image/favicon.ico">
    <link rel="stylesheet" href="/nav/style.css?v=20260726-1">
</head>
<body>
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
            <?php
            $lines = @file($catalog['file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                echo '<p class="catalog-error">Không thể tải danh mục tài liệu.</p>';
            } else {
                foreach ($lines as $line) {
                    $bookData = explode('|', $line);
                    if (count($bookData) !== 4) {
                        continue;
                    }

                    [$title, $author, $file, $description] = array_map('trim', $bookData);
                    if ($file === '' || $file[0] !== '/' || strpos($file, '..') !== false) {
                        continue;
                    }

                    $resourceUrl = '/physics' . $file;
                    $isPdf = strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf';
                    $searchTitle = htmlspecialchars(strip_tags($title), ENT_QUOTES, 'UTF-8');
                    $searchAuthor = htmlspecialchars(strip_tags($author), ENT_QUOTES, 'UTF-8');
                    $safeUrl = htmlspecialchars($resourceUrl, ENT_QUOTES, 'UTF-8');

                    echo '<article class="book-item" role="listitem" data-title="' . $searchTitle . '" data-author="' . $searchAuthor . '">';
                    echo '<div class="book-details"><strong>' . $title . '</strong><br>' . $author;
                    if ($description !== '') {
                        echo '<br><span class="book-description">' . $description . '</span>';
                    }
                    echo '</div><div class="book-actions">';
                    echo '<a class="open-resource" href="' . $safeUrl . '">' . ($isPdf ? 'Mở PDF' : 'Mở tài liệu') . '</a>';
                    if ($isPdf) {
                        echo '<a class="download-resource" href="' . $safeUrl . '" download>Tải xuống</a>';
                    }
                    echo '</div></article>';
                }
            }
            ?>
        </div>

        <button class="menu-toggle" type="button" aria-controls="catalog-menu" aria-expanded="true" aria-label="Thu gọn danh mục">&lt;</button>
    </aside>

    <main class="iframe-container">
        <iframe id="content-iframe" src="<?php echo htmlspecialchars($catalog['welcome'], ENT_QUOTES, 'UTF-8'); ?>" title="Trình xem tài liệu"></iframe>
    </main>
    <script src="/nav/script.js?v=20260726-1"></script>
</body>
</html>
