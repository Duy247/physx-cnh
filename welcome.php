<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài liệu chuyên lí Nguyễn Huệ</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="./image/favicon.ico">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');

        body {
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
            color: white;
            overflow: hidden;
            background-color: rgb(45, 43, 43);
        }
        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 0;
            opacity: 0.5;
        }

        .background img {
            width: calc(100vw / 3);
            height: calc(100vh / 3);
            object-fit: cover;
            transition: transform 1s ease-in-out, opacity 1s ease-in-out;
            opacity: 0.8;
            position: relative;
        }

        .background img.swap {
            transform: scale(1.1);
            opacity: 1;
            z-index: 1;
        }

        .container {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 85vh;
            transition: opacity 0.2s ease-in-out;
        }

        .container.fade-out {
            opacity: 0;
        }

        h1 {
            font-size: 3.5rem;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .text-box {
            background-color: rgba(0, 0, 0, 0.7);
            padding-left: 1rem;
            padding-right:0.5rem;
            max-width: 600px;
            border: 1px solid white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
            text-align: justify;
            margin-bottom:1rem;
        }

        p {
            font-size: 1rem;
            line-height: 1.6;
        }

        .link-boxes {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            margin-top: 0rem;
            width: 50%;
        }
        .link-boxes-vertical {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            margin-top: 0rem;
        }
        
        .link-boxes-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 0rem;           
            align-items: flex-end;
            z-index:9999;
        }
        footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            
        }

        .link-box {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 1rem;
            margin: 0 0.5rem;
            border: 1px solid rgb(188, 241, 161);
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(23, 249, 15, 0.4);
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease-in-out;
            width: 250px;
            height: 30%;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: inherit; 
            text-decoration: inherit;       
        }


        .link-box-footer{
            background-color: rgba(0, 0, 0, 0.7);
            padding: 1rem;
            margin: 0 0.5rem;
            border: 1px solid #777777;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(117, 117, 117, 0.4);
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease-in-out;
            width: 250px;
            height: 30%;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: inherit; 
            text-decoration: inherit;
        }

        .link-box:hover {
            transform: scale(1.1);
            border: 1px solid rgb(245, 160, 75);
            box-shadow: 0 0 10px rgba(249, 132, 15, 0.4);
        }

        .link-box a {
            font-size: 1rem;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .content-wrapper {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            width: 50%;
        }
        .whats-new {
            background-color: rgba(0, 0, 0, 0.7);
            padding-left: 1rem;
            padding-right: 0.5rem;
            max-width: 400px;
            border: 1px solid rgb(222, 98, 31);
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(254, 7, 7, 0.9);
            text-align: left;
            margin-bottom:1rem;
            margin-left:0.75rem;
            color:#eaa612;
        }

        .whats-new h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .whats-new ul {
            padding-left: 0.5rem;
            text-align:justify;
        }

        .whats-new li {
            font-size: 1rem;
            line-height: 1.6;
        }
        .whats-new a{
            color: #1dea12;
        }
        .rainbow-text {
            animation: rainbow 1.5s linear infinite;
        }
        .welcome-message {
            text-align: center;
            padding: 20px;
            z-index: 1;
        }

        .welcome-message h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .welcome-message p {
            font-size: 18px;
        }
        .loader {
            display: inline-grid;
            width: 80px;
            aspect-ratio: 1;
        }
        .loader:before,
        .loader:after {
            content:"";
            grid-area: 1/1;
            border-radius: 50%;
            animation: l3-0 2s alternate infinite ease-in-out;
        }
        .loader:before {
            margin: 25%;
            background: repeating-conic-gradient(#C02942 0 60deg,#0B486B 0 120deg);
            translate: 0 50%;
            rotate: -150deg;
        }
        .loader:after {
            padding: 10%;
            margin: -10%;
            background: repeating-conic-gradient(#0B486B 0 30deg,#C02942 0 60deg);
            mask:linear-gradient(#0000 50%,#000 0) content-box exclude,linear-gradient(#0000 50%,#000 0);
            rotate: -75deg;
            animation-name: l3-1;
        }
        @keyframes l3-0 {to{rotate: 150deg}}
        @keyframes l3-1 {to{rotate:  75deg}}

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
        .logo{
            opacity:0.9;
        }
        /* Phone layout */

        @media screen and (max-width: 600px) {
        body{
            overflow:auto;
        }
        h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        }

        .container {
        height: auto;
        padding: 2rem 0;
        }

        .text-box {
        max-width: 90%;
        align-self: center;
        }

        .link-boxes, .link-boxes-vertical, .content-wrapper {
        flex-direction: column;
        width: 90%;
        align-self: center;
        }

        .link-box, .link-box-footer {
        width: 90%;
        height: auto;
        margin: 0.5rem 0;
        }

        .whats-new {
        max-width: 100%;
        margin-left: 0;
        margin-top: 1rem;
        }
        footer {
            position: relative;
            bottom: 0;
            left: 0;
            right: 0;
            align-items: center;
        }
        .link-boxes-footer {
            display: flex;
            flex-direction: column;
            margin-top: 0rem;  
            margin-left:0;         
            align-items: flex-end;
            z-index:9999;
            padding:0;
        }
        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: auto;
            height: auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 0;
            opacity: 0.5;
        }
        .logo{
            align-self: center;
            order: -1;
        }
        }
    </style>
        
</head>
<body>
    <div id="loading-screen">
        <div class="welcome-message">
            <h1>Trang web tài liệu chuyên lí PhysX-CNH</h1>
            <p>Đang tải trang chủ...</p>
            <div class="loader"></div> 
        </div>
        <div class="flying-equations-container"></div>
    </div>
    <div id="main-content" style="display: none;">
        <div class="background">
            <img src="physics/img/image1.png" alt="Background Image 1">
            <img src="physics/img/image2.jpg" alt="Background Image 2">
            <img src="physics/img/image3.jpg" alt="Background Image 3">
            <img src="physics/img/image4.jpg" alt="Background Image 4">
            <img src="physics/img/image5.jpg" alt="Background Image 5">
            <img src="physics/img/image6.jpg" alt="Background Image 6">
            <img src="physics/img/image7.jpg" alt="Background Image 7">
            <img src="physics/img/image8.jpg" alt="Background Image 8">
            <img src="physics/img/image9.jpg" alt="Background Image 9">
        </div>
        <div class="container">
            <h1>Tổng hợp tài liệu chuyên lí Nguyễn Huệ</h1>
            <div class="content-wrapper">
                <div class="text-box">
                    <p>Đây là trang web tổng hợp tài liệu hỗ trợ học tập cho các học sinh thuộc đội tuyển vật lí. Web được vận hành và duy trì trên nền tảng Github bởi Văn Thành Duy, chuyên lí Nguyễn Huệ K69.</p>
                    <p>Trang web vận hành tốt nhất trên trình duyệt máy tính (PC / Chromebook / Samsung Dex). Để truy cập vào các tài liệu, vui lòng chọn mục từ menu bên trái. </p>
                    <p>Để yêu cầu tìm và bổ sung tài liệu, liên hệ <i><b>duy5a247@gmail.com</b></i></p>
                    <p>Nếu quá trình load và tải tài liệu bị chậm, hãy sử dụng DNS Cloudfare 1.1.1.1</p>
                    <p>Cảm ơn tới các cá nhân đã đóng góp tài liệu</p>
                    <ul>
                        <li>Văn Thành Duy Lí 1 K69 CNH</li>
                        <li>Phạm Quang Chính Lí 2 K75 CNH</li>
                        
                    </ul>
                </div>
                <div class="whats-new">
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
            
            <div class="link-boxes" id="books">
                <a href="./nav/physics.php?type=book&level=pre-vpho" class="link-box" id="booksPreVpho">
                    Sách HSG cấp thành phố
                </a>  
                <a href="./nav/physics.php?type=book&level=vpho-vn" class="link-box" id="booksVn">
                    Sách tiếng việt vòng 2 thành phố / HSGQG
                </a>
                <a href="./nav/physics.php?type=book&level=vpho-en" class="link-box" id="booksEn">
                    Sách tiếng anh vòng 2 thành phố / HSGQG
                </a>         
            </div>
            <div class="link-boxes" id="materials">
                <a href="./nav/physics.php?type=material&level=pho" class="link-box" id="materialsPreVpho">
                    Tài liệu & Handouts
                </a>
                <a href="./nav/physics.php?type=paper-sol&level=pho" class="link-box" id="materialsOlympiad">
                    Đề & Đáp án Olympics khu vực và quốc tế
                </a>
                <a href="./nav/physics.php?type=magazines&level=all" class="link-box" id="materialsVltt">
                    Tạp chí
                </a>
            </div>
            <div class="link-boxes" id="lessons-all">
                <a href="./nav/physics.php?type=lessons&level=all" class="link-box" id="lessons">
                    Nội dung các ngày học
                </a>
                <a href="/daily/current" class="link-box" id="dailyCurrent">
                    <span class="rainbow-text">Bài tập hàng ngày</span>
                </a>
                <a href="/blog" class="link-box" id="blog">
                    Blog cá nhân
                </a>
            </div>   
        </div>
        <footer>
            <div class="link-boxes-footer" id="fotter">
                <div class="link-boxes-vertical" id="disclaimer">
                    <a href="/disclaimer" class="link-box-footer">
                        Miễn trừ trách nhiệm pháp lý
                    </a>
                </div>
                <img src="/image/logo.png" class="logo">
                <div class="link-boxes-vertical" id="count">
                    <a href="#" class="link-box-footer"> 
                        Lượt truy cập : <span id="hitCount"> 0 </span>
                    </a>
                </div>
            </div>
        </footer>
    </div>

    
    <script src="js/script_welcome.js"></script> 
</body>
</html>