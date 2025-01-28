<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="website" property="og:type">
    <meta content="Tài liệu chuyên lý Nguyễn Huệ" property="og:title">
    <meta name="twitter:title" content="Tài liệu chuyên lý Nguyễn Huệ">
    <meta property="og:description" content="Tổng hợp tài liệu chuyên lý ôn tập thi HSG các cấp và Olympics Vật lý.">
    <meta property="og:site_name" content="PhysX-CNH">
    <meta name="twitter:description" content="Tổng hợp tài liệu chuyên lý ôn tập thi HSG các cấp và Olympics Vật lý.">
    <meta content="https://physx-cnh.com/image/logo_meta.png" property="og:image">
    <meta property="og:image:width" content="1200" /> 
    <meta property="og:image:height" content="630" /> 
    <meta property="og:image:type" content="image/jpeg">
    <meta content="https://physx-cnh.com/image/logo_meta.png" name="twitter:image">
    <title>Tài liệu chuyên lý Nguyễn Huệ</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="./image/favicon.ico">
    <link rel="stylesheet" href="/css/common.css">
    <link rel="stylesheet" href="/css/welcome.css">
        
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
                <li><a href="/daily/daily" id="dailyCurrent">Bài Tập Hàng Ngày - xPhO</a></li>
                <li><a href="./roadmap">Lộ trình ôn tập</a></li>
                <li><a href="./donate" id="donate">Ủng hộ duy trì trang web</a></li>
            </ul>
            <div class="bottom-links">
                <ul class="menu-list-bottom">
                    <li><a href="./disclaimer">Miễn Trừ Trách Nhiệm Pháp Lý</a></li>
                    <li><a href="welcome.php">Lượt Truy Cập: <span id="hitCount"> 0 </span></a></li>
                </ul>
            </div>
        </div>
    </div>

    <div id="content">
        <div id="top-part">
            <h1>Tài liệu chuyên lý Nguyễn Huệ</h1>
            <div class="text-container">
                <div class="text-box" id="introduce">
                    <p>Đây là trang web tổng hợp tài liệu hỗ trợ học tập cho các học sinh thuộc đội tuyển vật lí. <br>Web được vận hành và duy trì trên nền tảng Github bởi Văn Thành Duy, chuyên lí Nguyễn Huệ K69.</p>
                    <p>Trang web vận hành tốt nhất trên trình duyệt máy tính (PC / Chromebook / Samsung Dex). <br>Để truy cập vào các tài liệu, vui lòng chọn mục từ menu bên trái. </p>
                    <p>Để yêu cầu tìm và bổ sung tài liệu, liên hệ <b><a href="mailto:duy5a247@gmail.com">duy5a247@gmail.com</a></b> hoặc tới <a href="https://github.com/Duy247/physx-cnh" style= "color:red;">repository github</a> và đọc thêm hướng dẫn <br>Nếu quá trình load và tải tài liệu bị chậm, hãy sử dụng DNS Cloudfare 1.1.1.1</p>         
                    <p>Cảm ơn tới các cá nhân đã đóng góp tài liệu</p>
                    <ul>
                        <li>Văn Thành Duy Lý 1 K69 CNH | Phạm Quang Chính Lý 2 K75 CNH</li>
                    </ul>
                    <p>Cảm ơn tới các cá nhân đã đóng góp duy trì hosting</p>
                    <ul>
                        <li><a href="./donators" style="color: red;">Danh sách các bạn trẻ đã donate</a></li>
                    </ul>
                </div>
                <div class="text-box" id="whats-new">
                    <h2><span class="rainbow-text">Cập nhật mới</span></h2>
                    <ul>                 
                        <?php
                        $whatsNewFile = './whats-new/whats_new.txt'; 
                        if (file_exists($whatsNewFile)) {
                            $lines = file($whatsNewFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                            foreach ($lines as $line) {
                                if (strpos($line, '* ') === 0) { 
                                    echo '<li>' . substr($line, 2) . '</li>'; 
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

    <div id="newYearModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <img src="/newyear/img.png" alt="New Year Poster" style="width:100%; border-radius: 10px;">
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
        var daily = document.getElementById('dailyCurrent');
        var donate = document.getElementById('donate');
        
        if (isMobileDevice()) {         
            booksPreVpho.href = "./nav/physics_mobile?type=book&level=pre-vpho";
            booksVn.href = "./nav/physics_mobile?type=book&level=vpho-vn";
            booksEn.href = "./nav/physics_mobile?type=book&level=vpho-en";
            materialsPreVpho.href = "./nav/physics_mobile?type=material&level=pho";
            materialsOlympiad.href = "./nav/physics_mobile?type=paper-sol&level=pho";
            materialsVltt.href = "./nav/physics_mobile?type=magazines&level=all";
            lessons.href = "./nav/physics_mobile?type=lessons&level=all";
            daily.href = "./daily/exercise";
            donate.href = "./physics/donate";
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
            }, 10000);
        });

        const headings = document.querySelectorAll('.news-content h3');

        headings.forEach(heading => {
        let timeoutId; 

        heading.addEventListener('mouseenter', () => {
            heading.classList.add('show-paragraph');

            clearTimeout(timeoutId); 
        });

        heading.addEventListener('mouseleave', () => {
            timeoutId = setTimeout(() => {
            heading.classList.remove('show-paragraph');
            }, 15000); 
        });
        });

        function setCookie(name, value, hours) {
            var date = new Date();
            date.setTime(date.getTime() + (hours * 60 * 60 * 1000));
            var expires = "expires=" + date.toUTCString();
            document.cookie = name + "=" + value + ";" + expires + ";path=/";
        }

        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        window.onload = function() {
            var modal = document.getElementById("newYearModal");
            var span = document.getElementsByClassName("close")[0];

            if (!getCookie("seenNewYearPoster")) {
                modal.style.display = "block";
            }

            span.onclick = function() {
                modal.style.display = "none";
                setCookie("seenNewYearPoster", "true", 3);
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                    setCookie("seenNewYearPoster", "true", 3);
                }
            }
        }
    </script> 
</body>
</html>

