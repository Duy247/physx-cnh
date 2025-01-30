<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bài Tập Hàng Ngày - xPhO</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="../image/favicon.ico">
    <link rel="stylesheet" href="/css/common.css">
    <link rel="stylesheet" href="/css/daily.css">
</head>
<body>
    <div id="menu">  
        <div id="m-top-part">
            <img src="../image/logo.png" class="logo">
        </div>
        <div id="m-bottom-part">
            <ul class="menu-list">
                <li><a href="../welcome">Trang chủ</a></li>
                <li><a href="../nav/physics?type=magazines&level=all" id="materialsVltt">Tạp Chí</a></li>
                <li><a href="../nav/physics?type=material&level=pho" id="materialsPreVpho">Tài Liệu</a></li>
                <li class="dropdown">
                    <div class="dropdown-toggle">
                        <a>Sách</a>
                    </div>
                    <div class="dropdown-content">
                        <a href="../nav/physics?type=book&level=pre-vpho" id="booksPreVpho">Sách Bồi Dưỡng<br>HSG Cấp Thành Phố</a>
                        <a href="../nav/physics?type=book&level=vpho-vn" id="booksVn">Sách Tiếng Việt<br>Vòng 2 Thành Phố / HSGQG</a>
                        <a href="../nav/physics?type=book&level=vpho-en" id="booksEn">Sách Tiếng Anh<br>Vòng 2 Thành Phố / HSGQG</a>
                    </div>
                </li>
                <li><a href="../nav/physics?type=paper-sol&level=pho" id="materialsOlympiad">Đề Thi & Đáp Án</a></li>
                <li><a href="../nav/physics?type=lessons&level=all" id="lessons">Nội Dung Ngày Học</a></li>
                <li><a href="../daily/daily" id="dailyCurrent">Bài Tập Hàng Ngày - xPhO</a></li>
                <li><a href="../roadmap">Lộ trình ôn tập</a></li>
                <li><a href="./article">Cách tìm bài báo khoa học</a></li>
                <li><a href="../donate">Ủng hộ duy trì trang web</a></li>
            </ul>
            <div class="bottom-links">
                <ul class="menu-list-bottom">
                    <li><a href="../disclaimer">Miễn Trừ Trách Nhiệm Pháp Lý</a></li>
                    <li><a href="welcome.php">Lượt Truy Cập: <span id="hitCount"> 0 </span></a></li>
                </ul>
            </div>
        </div>
    </div>

    <div id="content">
        <iframe id="content-iframe" src="./exercise.php" frameborder="0"></iframe>
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
            booksPreVpho.href = "../nav/physics_mobile?type=book&level=pre-vpho";
            booksVn.href = "../nav/physics_mobile?type=book&level=vpho-vn";
            booksEn.href = "../nav/physics_mobile?type=book&level=vpho-en";
            materialsPreVpho.href = "../nav/physics_mobile?type=material&level=pho";
            materialsOlympiad.href = "../nav/physics_mobile?type=paper-sol&level=pho";
            materialsVltt.href = "../nav/physics_mobile?type=magazines&level=all";
            lessons.href = "../nav/physics_mobile?type=lessons&level=all";
        }
        function updateHitCount() {
            fetch('../visit_count/hit_counter.php') 
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

