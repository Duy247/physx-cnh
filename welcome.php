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
        .logo{
            width: 100%;
            height: 100%;
            object-fit: contain;
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
            background: linear-gradient(to bottom, #dddddd 78%, #1d1d52 78%);
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
        .menu-list li.daily a {
            font-weight: bold;
            color: white;
            background: linear-gradient(90deg, #ff0000, #ff7f00, #ffff00, #00ff00, #0000ff, #4b0082, #9400d3);
            background-size: 500% 500%;
            animation: dailyGradient 5s ease infinite;
        }
        @keyframes dailyGradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .menu-list li a::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background-color: #26245f;
            transition: left 0.2s ease;
            z-index: -1;
        }

        .menu-list li a:hover {
            transform: scale(1.00);
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

        .dropdown-toggle:hover > a::after {
            content: " (Click để mở rộng)";
        }
        .dropdown-toggle.active > a::after {
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
        #top-part {
            height: 50%;
            padding: 1rem 5rem 1rem 5rem;
            box-sizing: border-box;
            font-size: 1.0rem;     
            text-align: justify;
            background-color: hsla(200, 40%, 30%, .6);
            background-image:
                url('https://78.media.tumblr.com/cae86e76225a25b17332dfc9cf8b1121/tumblr_p7n8kqHMuD1uy4lhuo1_540.png'),
                url('https://78.media.tumblr.com/66445d34fe560351d474af69ef3f2fb0/tumblr_p7n908E1Jb1uy4lhuo1_1280.png'),
                url('https://78.media.tumblr.com/8cd0a12b7d9d5ba2c7d26f42c25de99f/tumblr_p7n8kqHMuD1uy4lhuo2_1280.png'),
                url('https://78.media.tumblr.com/5ecb41b654f4e8878f59445b948ede50/tumblr_p7n8on19cV1uy4lhuo1_1280.png'),
                url('https://78.media.tumblr.com/28bd9a2522fbf8981d680317ccbf4282/tumblr_p7n8kqHMuD1uy4lhuo3_1280.png');
            background-repeat: repeat-x;
            background-position:
                0 20%,
                0 100%,
                0 50%,
                0 100%,
                0 0;
            background-size:
                2500px,
                800px,
                500px 200px,
                1000px,
                400px 260px;
            animation: 500s para infinite linear;
        }

        @keyframes para {
            100% {
                background-position: 
                    -5000px 20%,
                    -800px 95%,
                    500px 50%,
                    1000px 100%,
                    400px 0;
                }
        }
        .text-container {
            display: flex;
            justify-content: space-between;
        }
        .text-box {
            flex: 1;
            margin: 0 10px;
            padding: 5px 15px 5px 10px;
            background: rgba(29, 29, 82, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            color: white;
        }
        #introduce {
            flex: 2;     
        }
        #whats-new {
            flex: 1;
        }
        .text-box a{
            text-decoration: none;
            color:#29ff00;
        }
        #top-part h1 {
            text-align: center;
            color:#38389e;
        }

        #top-part h2 {
            text-align: center;
        }
        #bottom-part {
            display: flex;
            clear: both;
            height: 50%;
        }

        #l-bottom-part {
            flex: 0 0 60%;           
            box-sizing: border-box;
        }     

        #r-bottom-part {
            flex: 0 0 40%;
            padding: 5px;
            box-sizing: border-box;
            background-color: black;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .slideshow-container {
            max-width: 1000px;
            position: relative;
            margin: auto;
        }

        .mySlides {
            display: none;
        }

        .mySlides img {
            width: 100%;
            height: auto;
        }

        .fade {
            -webkit-animation-name: fade;
            -webkit-animation-duration: 2.5s;
            animation-name: fade;
            animation-duration: 2.5s;
        }

        @-webkit-keyframes fade {
            from {opacity: .2}
            to {opacity: 1}
        }

        @keyframes fade {
            from {opacity: .2}
            to {opacity: 1}
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
        .news-slideshow-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;            
        }

        .news-slide {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;          
        }

        .news-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .news-content {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 20px 50px 20px 20px;
            background-color: rgba(0, 0, 0, 0.7);
            color: #fff;
            text-align: justify;
        }

        .news-content h3 {
            padding-left:10px;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .news-content p {
            margin: 10px 0 0;
            font-size: 16px;
            opacity: 1;
            transition: opacity 0.3s ease-in-out;
            padding: 0px 40px 0px 10px;
        }

        .news-dots {
            position: relative;
            bottom: 20px;
            left: 50%;
            transform: translateX(0%);
            opacity: 0.3;
            align-self: auto;
        }
        .news-dots:hover {
            opacity: 1;
        }
        .news-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #bbb;
            margin: 0 5px;
            cursor: pointer;
        }

        .news-dot.active {
            background-color: #fff;
        }
        @media screen and (max-width: 600px) {
            body {
                overflow: auto;
            }
            #menu {
                width: 100%;
                height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            #m-top-part {
                width: 100%;
                height: 20%;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                background: linear-gradient(to bottom, #dce0e8 78%, #1d1d52 78%);
            }
            #m-bottom-part {
                width: 100%;
                height: 80%;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            #content {
                display: none;
            }
            .menu-list {
                width: 100%;
                padding: 0;
                margin: 0;
            }
            .menu-list li {
                width: 100%;
            }
            .menu-list li a {
                display: block;
                width: 100%;
                padding: 10px;
                background-color: #dce0e8;
                color: black;
                text-decoration: none;
                text-align: center;
                transition: background-color 0.3s ease, transform 0.3s ease;
                font-size: 1.1rem;
                position: relative;
                overflow: hidden;
            }
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
                <li><a href="./welcome">Trang chủ</a></li>
                <li><a href="./nav/physics?type=magazines&level=all" id="materialsVltt">Tạp Chí</a></li>
                <li><a href="./nav/physics?type=material&level=pho" id="materialsPreVpho">Tài Liệu</a></li>
                <li class="dropdown">
                    <div class="dropdown-toggle">
                        <a>Sách</a>
                    </div>
                    <div class="dropdown-content">
                        <a href="./nav/physics?type=book&level=pre-vpho" id="booksPreVpho">Sách Bồi Dưỡng<br>HSG Cấp Thành Phố</a>
                        <a href="./nav/physics?type=book&level=vpho-vn" id="booksVn">Sách Tiếng Việt<br>Vòng 2 Thành Phố / HSGQG</a>
                        <a href="./nav/physics?type=book&level=vpho-en" id="booksEn">Sách Tiếng Anh<br>Vòng 2 Thành Phố / HSGQG</a>
                    </div>
                </li>
                <li><a href="./nav/physics?type=paper-sol&level=pho" id="materialsOlympiad">Đề Thi & Đáp Án</a></li>
                <li><a href="./nav/physics?type=lessons&level=all" id="lessons">Nội Dung Ngày Học</a></li>
                <li class="daily"><a href="/daily/current" id="dailyCurrent">Bài Tập Hàng Ngày</a></li>
            </ul>
            <div class="bottom-links">
                <ul class="menu-list-bottom">
                    <li><a href="./disclaimer">Miễn Trừ Trách Nhiệm Pháp Lý</a></li>
                    <li><a href="welcome_new.php">Lượt Truy Cập: <span id="hitCount"> 0 </span></a></li>
                </ul>
            </div>
        </div>
    </div>

    <div id="content">
        <div id="top-part">
            <h1>Tổng hợp tài liệu chuyên lý Nguyễn Huệ</h1>
            <div class="text-container">
                <div class="text-box" id="introduce">
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
                <div class="text-box" id="whats-new">
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
            </div>
        </div> 
        <div id="bottom-part">
            <div id="l-bottom-part">
                <div class="news-slideshow-container">
                    <?php
                    $jsonFile = './whats-new/news.json';
                    $jsonData = file_get_contents($jsonFile);
                    $newsItems = json_decode($jsonData, true);

                    foreach ($newsItems as $index => $newsItem) {
                        $backgroundImage = $newsItem['background_image'];
                        $headline = $newsItem['headline'];
                        $detail = $newsItem['detail'];

                        echo '<div class="news-slide">';
                        echo '<img src="' . $backgroundImage . '" alt="News Image">';
                        echo '<div class="news-content">';
                        echo '<h3>' . $headline . '</h3>';
                        echo '<p>' . $detail . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <div class="news-dots">
                    <?php
                    $totalSlides = count($newsItems);
                    for ($i = 0; $i < $totalSlides; $i++) {
                        echo '<span class="news-dot" onclick="currentNewsSlide(' . ($i + 1) . ')"></span>';
                    }
                    ?>
                </div>
            </div>
            <div id="r-bottom-part">
                <div class="slideshow-container">
                    <?php
                    $imageDirectory = './physics/img/';
                    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                    $images = glob($imageDirectory . '*.*');
                    foreach ($images as $image) {
                        $extension = pathinfo($image, PATHINFO_EXTENSION);
                        if (in_array(strtolower($extension), $imageExtensions)) {
                            echo '<div class="mySlides fade">';
                            echo '<img src="' . $image . '" style="width:100%">';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function isMobileDevice() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }
        var booksPreVpho = document.getElementById('booksPreVpho');
        var booksVn = document.getElementById('booksVn');
        var booksEn = document.getElementById('booksEn');
        var materialsPreVpho = document.getElementById('materialsPreVpho');
        var materialsOlympiad = document.getElementById('materialsOlympiad');
        var materialsVltt = document.getElementById('materialsVltt');
        var lessons = document.getElementById('lessons');
        
        if (isMobileDevice()) {         
            booksPreVpho.href = "./nav/physics_mobile?type=book&level=pre-vpho";
            booksVn.href = "./nav/physics_mobile?type=book&level=vpho-vn";
            booksEn.href = "./nav/physics_mobile?type=book&level=vpho-en";
            materialsPreVpho.href = "./nav/physics_mobile?type=material&level=pho";
            materialsOlympiad.href = "./nav/physics_mobile?type=paper-sol&level=pho";
            materialsVltt.href = "./nav/physics_mobile?type=magazines&level=all";
            lessons.href = "./nav/physics_mobile?type=lessons&level=all";
        }
        function updateHitCount() {
            fetch('./visit_count/hit_counter.php') 
                .then(response => response.json())
                .then(data => {
                    const hitCount = data.count;
                    document.getElementById('hitCount').textContent = hitCount;
                })
                .catch(error => {
                    console.error('Error fetching hit count:', error);
                });
        }

        window.addEventListener('load', updateHitCount);
        document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function(event) {
                event.preventDefault();
                var dropdown = toggle.parentElement;
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
        var slideIndex = 0;
        showSlides();

        function showSlides() {
            var i;
            var slides = document.getElementsByClassName("mySlides");
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            slideIndex++;
            if (slideIndex > slides.length) {
                slideIndex = 1;
            }
            if (slides[slideIndex - 1]) {
                slides[slideIndex - 1].style.display = "block";
            }
            setTimeout(showSlides, 10000); // Change image every 3 seconds
        }
        var newsSlideIndex = 1;
        showNewsSlides(newsSlideIndex);

        function plusNewsSlides(n) {
            showNewsSlides(newsSlideIndex += n);
        }

        function currentNewsSlide(n) {
            showNewsSlides(newsSlideIndex = n);
        }

        function showNewsSlides(n) {
            var i;
            var newsSlides = document.getElementsByClassName("news-slide");
            var newsDots = document.getElementsByClassName("news-dot");
            if (n > newsSlides.length) {
                newsSlideIndex = 1;
            }
            if (n < 1) {
                newsSlideIndex = newsSlides.length;
            }
            for (i = 0; i < newsSlides.length; i++) {
                newsSlides[i].style.display = "none";
            }
            for (i = 0; i < newsDots.length; i++) {
                newsDots[i].className = newsDots[i].className.replace(" active", "");
            }
            newsSlides[newsSlideIndex - 1].style.display = "block";
            newsDots[newsSlideIndex - 1].className += " active";
        }

        var newsSlideInterval = setInterval(function() {
            plusNewsSlides(1);
        }, 30000);

        var newsSlideshow = document.querySelector(".news-slideshow-container");
        newsSlideshow.addEventListener("mouseover", function() {
            clearInterval(newsSlideInterval);
        });
        newsSlideshow.addEventListener("mouseout", function() {
            newsSlideInterval = setInterval(function() {
                plusNewsSlides(1);
            }, 5000);
        });
    </script> 
</body>
</html>

