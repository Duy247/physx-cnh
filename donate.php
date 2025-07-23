<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ủng hộ</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="../image/favicon.ico">
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
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(to bottom, #dce0e8 50%, #1d1d52 50%);
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
        #content {
            transition: filter 0.3s ease; /* Add a smooth transition for opacity */
        }

        #menu:hover ~ #content {
            filter: brightness(0.2); 
        }
        .iframe-container {
        flex: 1;
        padding: 20px;
        transition: filter 0.3s ease;
        }
        iframe {
        width: 100%;
        height: 100%;
        border: none;
        }
        #top-part {
            height: 60%;
            padding-top:1rem;
            box-sizing: border-box;
            font-size: 1.0rem;     
            text-align: justify;
            background-image: url('/image/library.jpg');
            background-size:cover;
                    -webkit-animation: slidein 100s;
                    animation: slidein 100s;

                    -webkit-animation-fill-mode: forwards;
                    animation-fill-mode: forwards;

                    -webkit-animation-iteration-count: infinite;
                    animation-iteration-count: infinite;

                    -webkit-animation-direction: alternate;
                    animation-direction: alternate;
        }

        @-webkit-keyframes slidein {
        from {background-position: top; background-size:2000px; }
        to {background-position: -100px 0px;background-size:2250px;}
        }

        @keyframes slidein {
        from {background-position: top;background-size:2000px; }
        to {background-position: -100px 0px;background-size:2250px;}

        }
        .text-container {
            display: flex;
            justify-content: space-between;
        }
        .text-box {
            flex: 1;
            margin: 0 10px;
            padding: 0.5em 1.0em 0.5em 1em;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            color: black;
            font-size: 1.8vh;
        }
        #introduce {
            flex: 3;     
        }
        #whats-new {
            flex: 2;
        }
        .text-box a{
            text-decoration: none;
            color:#1d1d52;
        }
        #top-part h1 {
            text-align: center;
            color: hsl(0, 0%, 15%,0.7);
            padding: 0;
            font-size: 5vh;
            margin-bottom:0.5rem;
        }

        #top-part h2 {
            text-align: center;
        }
        #bottom-part {
            display: flex;
            clear: both;
            height: 40%;
        }

        #l-bottom-part {
            flex: 0 0 60%;           
            box-sizing: border-box;
        }     

        #r-bottom-part {
            flex: 0 0 40%;
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
            font-size: 1.2vw;
            font-weight: bold;
        }

        .news-content p {
            margin: 10px 0 0;
            font-size: 0.8vw;
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
                background: linear-gradient(to bottom, #dce0e8 50%, #1d1d52 50%);
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
                width: 95%;
                padding: 0;
                margin: 0;
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
                <li><a href="./donate">Ủng hộ duy trì trang web</a></li>
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
        <iframe id="content-iframe" src="./physics/donate.html" frameborder="0"></iframe>
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

