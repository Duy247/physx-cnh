<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài liệu chuyên lý Nguyễn Huệ</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="./image/favicon.ico">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
        
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f1f1f1;
            height: 100vh;
            overflow: hidden;
        }

        #menu {
            width: 20%;
            height: 100%; /* 100% of body height */
            background-color: #dce0e8;
            float: left; /* Position the menu to the left */
            box-sizing: border-box;
            
        }
        #m-top-part {
            height: 20%;
            padding: 1rem;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(to bottom, #dddddd 85%, #919191 85%);
        }

        #m-bottom-part {
            height: 80%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            
        }

        .menu-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .menu-list li {
            width: 100%;
        }

        .menu-list li a {
            display: block;
            padding: 10px 15px 10px 10px;
            background-color: #dce0e8;
            color: black;
            text-decoration: none;
            text-align: right;
            transition: background-color 0.3s ease, transform 0.3s ease;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
        }

        .menu-list li a::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background-color: #656973;
            transition: left 0.2s ease;
            z-index: -1;
        }

        .menu-list li a:hover {
            transform: scale(1.05);
            font-weight: bold;
            color: white;
        }

        .menu-list li a:hover::before {
            left: 0;
        }

        .dropdown-content {
            display: none;
            padding: 5px 15px 10px 5px;
            background-color: #dce0e8;
            color: black;
            text-decoration: none;
            text-align: right;
            transition: background-color 0.3s ease, transform 0.3s ease;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
        }
        .dropdown > a::after {
            content: "";
            margin-left: 5px;
        }

        .dropdown:hover > a::after {
            content: " (Click để mở rộng)";
        }
        .dropdown.active > a::after {
            content: "";
        }
        .dropdown.active .dropdown-content {
            display: block;
        }

        .menu-list-bottom {
            list-style-type: none;
            padding: 0;
        }

        .menu-list-bottom li a {
            display: block;
            padding: 0px 15px 5px 0px;
            background-color: #dce0e8;
            color: black;
            text-decoration: none;
            text-align: right;
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }

        .bottom-links {
            margin-top: auto;
        }

        .bottom-links .menu-list li {
            margin-bottom: 5px;
        }

        #content {
            height: 100%; /* 100% of body height */
            width: 80%;
            float: left; /* Position the content next to the menu */
        }

        #left-section {
            height: 100%; /* 100% of content height */
            width: 60%;
            float: left;
            box-sizing: border-box;       
        }
        #l-top-part {
            height: 50%;
            padding:1rem 5rem 1rem 5rem;
            box-sizing: border-box;
            font-size: 1.0rem;
            text-align: justify;
            background: linear-gradient(to bottom, #ffffff 98%, #919191 98%);
        }

        #l-top-part h1 {
            text-align: center;
            
        }

        #l-bottom-part {
            height: 50%;
            padding: 5px;
            box-sizing: border-box;
        }

        #right-section {
            height: 100%; /* 100% of content height */
            width: 40%;
            float: left;
            background-color: #dce0e8;
            padding: 5px;
            box-sizing: border-box;   
        }

        #r-top-part {
            height: 30%;
            padding: 0rem 1rem 1rem 1rem;
            box-sizing: border-box;
            font-size:1.1rem;
        }
        #r-top-part h2 {
            text-align: center;
        }

        #r-bottom-part {
            height: 70%;
            padding: 5px;
            box-sizing: border-box;
        }
        .rainbow-text {
            animation: rainbow 7.5s linear infinite;
        }
        @keyframes rainbow {
        0% { color: red; }
        14% { color: orange; }
        28% { color: yellow; }
        42% { color: green; }
        57% { color: blue; }
        71% { color: indigo; }
        85% { color: violet; }
        100% { color: red; }
        }
    </style>
        
</head>
<body>
    <div id="menu">  
        <div id="m-top-part">
            <img src="/image/logo.png" class="logo">
        </div>
        <div id="m-bottom-part">
            <ul class="menu-list">
                <li><a href="welcome_new.php">Trang chủ</a></li>
                <li><a href="welcome_new.php">Tin Tức</a></li>
                <li><a href="welcome_new.php">Tạp Chí</a></li>
                <li><a href="welcome_new.php">Tài Liệu</a></li>
                <li class="dropdown">
                    <a href="#">Sách</a>
                    <div class="dropdown-content">
                        <a href="welcome_new.php">Sách Bồi Dưỡng<br>HSG Cấp Thành Phố</a>
                        <a href="welcome_new.php">Sách Tiếng Việt<br>Vòng 2 Thành Phố / HSGQG</a>
                        <a href="welcome_new.php">Sách Tiếng Anh<br>Vòng 2 Thành Phố / HSGQG</a>
                    </div>
                </li>
                <li><a href="welcome_new.php">Đề Thi & Đáp Án</a></li>
                <li><a href="welcome_new.php">Nội Dung Ngày Học</a></li>
                <li><a href="welcome_new.php">Bài Tập Hàng Ngày</a></li>
            </ul>
            <div class="bottom-links">
                <ul class="menu-list-bottom">
                    <li><a href="welcome_new.php">Miễn Trừ Trách Nhiệm Pháp Lý</a></li>
                    <li><a href="welcome_new.php">Lượt Truy Cập:</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div id="content">
        <div id="left-section">
            <div id="l-top-part">
                <h1>Tổng hợp tài liệu chuyên lý Nguyễn Huệ</h1>
                <div class="text-box">
                    <p>Đây là trang web tổng hợp tài liệu hỗ trợ học tập cho các học sinh thuộc đội tuyển vật lí. Web được vận hành và duy trì trên nền tảng Github bởi Văn Thành Duy, chuyên lí Nguyễn Huệ K69.</p>
                    <p>Trang web vận hành tốt nhất trên trình duyệt máy tính (PC / Chromebook / Samsung Dex). Để truy cập vào các tài liệu, vui lòng chọn mục từ menu bên trái. </p>
                    <p>Để yêu cầu tìm và bổ sung tài liệu, liên hệ <b><a href="mailto:duy5a247@gmail.com">duy5a247@gmail.com</a></b> hoặc tới <a href="https://github.com/Duy247/physx-cnh">repository github</a> và đọc thêm hướng dẫn</p>
                    <p>Nếu quá trình load và tải tài liệu bị chậm, hãy sử dụng DNS Cloudfare 1.1.1.1</p>
                    <p>Cảm ơn tới các cá nhân đã đóng góp tài liệu</p>
                    <ul>
                        <li>Văn Thành Duy Lý 1 K69 CNH</li>
                        <li>Phạm Quang Chính Lý 2 K75 CNH</li>
                    </ul>
                </div>
            </div>
            <div id="l-bottom-part">
                <h1>Bottom Part</h1>
                <p>This is some temporary content to make the bottom part visible.</p>
            </div>
        </div>

        <div id="right-section">
            <div id="r-top-part">
                <h2><span class="rainbow-text">Cập nhật mới</span></h2>
                <ul>                 
                    <?php
                    $whatsNewFile = './whats-new/whats_new.txt'; // Path to your text file
                    if (file_exists($whatsNewFile)) {
                        $lines = file($whatsNewFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        foreach ($lines as $line) {
                            if (strpos($line, '* ') === 0) { // Check if the line starts with '* '
                                echo '<li>' . substr($line, 2) . '</li>'; // Remove '* ' and add to the list
                            }
                        }
                    } else {
                        echo '<li>No updates yet.</li>';
                    }
                    ?>
                </ul>
            </div>

            <div id="r-bottom-part">
                <h1>Bottom Part</h1>
                <p>This is some temporary content to make the bottom part visible.</p>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.dropdown').forEach(function(dropdown) {
        dropdown.addEventListener('click', function(event) {
            event.preventDefault();
            dropdown.classList.toggle('active');
        });
        });

        document.addEventListener('click', function(event) {
        var dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(function(dropdown) {
            if (!dropdown.contains(event.target)) {
            dropdown.classList.remove('active');
            }
        });
        });
    </script> 
</body>
</html>

