<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài Tập Hàng Ngày - xPhO</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="/image/favicon.ico">
    <link rel="stylesheet" href="/css/common.css?v=20260726-1">
    <link rel="stylesheet" href="/css/daily.css">
</head>
<body>
    <header class="mobile-header">
        <a href="/welcome" aria-label="PhysX-CNH home"><img src="/image/logo.png" alt="PhysX-CNH"></a>
        <button id="menu-toggle" type="button" aria-controls="menu" aria-expanded="false" aria-label="Mở menu">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
    </header>
    <div id="menu">
        <div id="m-top-part">
            <img src="/image/logo.png" class="logo" alt="PhysX-CNH">
        </div>
        <div id="m-bottom-part">
            <ul class="menu-list">
                <li><a href="/welcome">Trang chủ</a></li>
                <li><a href="/nav/physics?type=magazines&amp;level=all">Tạp Chí</a></li>
                <li><a href="/nav/physics?type=material&amp;level=pho">Tài Liệu</a></li>
                <li class="dropdown">
                    <div class="dropdown-toggle"><a href="#" aria-haspopup="true">Sách</a></div>
                    <div class="dropdown-content">
                        <a href="/nav/physics?type=book&amp;level=pre-vpho">Sách Bồi Dưỡng<br>HSG Cấp Thành Phố</a>
                        <a href="/nav/physics?type=book&amp;level=vpho-vn">Sách Tiếng Việt<br>Vòng 2 Thành Phố / HSGQG</a>
                        <a href="/nav/physics?type=book&amp;level=vpho-en">Sách Tiếng Anh<br>Vòng 2 Thành Phố / HSGQG</a>
                    </div>
                </li>
                <li><a href="/nav/physics?type=paper-sol&amp;level=pho">Đề Thi &amp; Đáp Án</a></li>
                <li><a href="/nav/physics?type=lessons&amp;level=all">Nội Dung Ngày Học</a></li>
                <li><a href="/daily/daily">Bài Tập Hàng Ngày - xPhO</a></li>
                <li><a href="/roadmap">Lộ trình ôn tập</a></li>
                <li><a href="/article">Cách tìm bài báo khoa học</a></li>
                <li><a href="/donate">Ủng hộ duy trì trang web</a></li>
            </ul>
            <div class="bottom-links">
                <ul class="menu-list-bottom">
                    <li><a href="/disclaimer">Miễn Trừ Trách Nhiệm Pháp Lý</a></li>
                    <li><a href="/welcome">Lượt Truy Cập: <span id="hitCount">0</span></a></li>
                </ul>
            </div>
        </div>
    </div>
    <div id="menu-backdrop" hidden></div>
    <main id="content">
        <iframe id="content-iframe" src="/daily/exercise.php" title="Bài tập hàng ngày"></iframe>
    </main>
    <script src="/js/site-shell.js?v=20260726-1"></script>
</body>
</html>
