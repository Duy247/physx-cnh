<!DOCTYPE html>
<html>
<head>
  <?php
    $type = isset($_GET['type']) ? $_GET['type'] : 'book';
    $level = isset($_GET['level']) ? $_GET['level'] : 'pre-vpho';
    
    $title = 'Tài liệu HSG Vật lí';
    
    if ($type === 'book' && $level === 'pre-vpho') {
      $title = 'Sách trước Vòng chọn VPhO';
    } elseif ($type === 'book' && $level === 'vpho-vn') {
      $title = 'Sách VPhO và Vòng chọn (VN)';
    } elseif ($type === 'book' && $level === 'vpho-en') {
      $title = 'Sách VPhO và Vòng chọn (EN)';
    } elseif ($type ==='material' && $level === 'pho') {
      $title = 'Tài liệu và handouts';
    } elseif ($type ==='paper-sol' && $level === 'pho') {
      $title = 'Đề thi & Đáp án';
    } elseif ($type ==='magazines' && $level === 'all') {
      $title = 'Tạp chí';
    } elseif ($type ==='lessons' && $level === 'all') {
      $title = 'Nội dung ngày học';
    }  else {
      $title = 'Tài liệu HSG Vật lí khác';
    }
  ?>
  
  <title><?php echo $title; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link rel="shortcut icon" type="image/x-icon" href="../image/favicon.ico">
  <meta charset="UTF-8">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');
    body {
      display: flex;
      max-height: 100vh;
      margin: 0;
      font-family: 'Montserrat', sans-serif;
      background-color: #dce0e8;
    }
    .menu {
      width: 20%;
      background-color: #dce0e8;
      padding: 10px;
      height: 98vh;
      text-align: center;
      transition: width 0.3s ease;
      position: relative;
    }
    .menu.retracted {
      width: 20px;
    }
    .menu a {
      text-decoration: none;
    }
    .menu h2,h3 {
      color: black;
      margin-bottom: 0px;
      margin-top: 0px;
      white-space: nowrap;
    }
	  .menu h3 {
      margin-bottom: 10px;
      color:black;
      font-size:14px;
    }
    .menu ul {
      list-style-type: none;
      padding: 0;
      margin: 0;
      height: 95vh;
      overflow-y: auto;
      transition: opacity 0.3s ease;
    }
    .menu.retracted ul {
      opacity: 0;
      pointer-events: none;
    }
    .menu li {
      width:95%;
      margin-bottom: 10px;
      background-color: #dce0e8;
      border-radius: 4px;
      color: black;
      font-size: 14px;
      font-weight: bold;
      padding-top:5px;
      padding-bottom:5px;
    }
    .menu li::before {
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
    .menu li:hover {
      transform: scale(1.00);
      font-weight: bold;
      color: white;
    }
    .menu li:hover::before {
      left: 0;
    }
    .iframe-container {
      flex: 1;
      padding: 20px;
    }
    iframe {
      width: 100%;
      height: 100%;
      border: none;
    }
    .menu-toggle {
      position: absolute;
      top: 50%;
      right: -10px;
      transform: translateY(-50%);
      width: 20px;
      height: 40%;
      background-color: #262626;
      border: none;
      border-top-right-radius: 10px;
      border-bottom-right-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #b95edd;
      font-size: 20px;
      transition: background-color 0.3s ease;
    }
	.menu-toggle:hover {
	  background-color: #73c977; /* Subtle background change on hover */
    font-size: 30px; 
	}
  .search-container {
    margin-bottom: 10px;
  }

  .search-container input[type="text"] {
    width: 80%;
    padding: 5px;
    font-size: 14px;
    border: 1px solid #b95edd;
    border-radius: 4px;
  }

  .sort-options {
    margin-bottom: 10px;
  }

  .sort-options label {
    color: black;
    font-size: 14px;
    margin-right: 5px;
  }

  .sort-options select {
    padding: 5px;
    font-size: 14px;
    border: 1px solid #b95edd;
    border-radius: 4px;
    background-color: #dce0e8;
    color: black;
  }
    /* Custom scroll bar styles */
    ::-webkit-scrollbar {
      width: 0px;
    }
    ::-webkit-scrollbar-track {
      background-color: #262626;
    }
    ::-webkit-scrollbar-thumb {
      background-color: #73c977;
      border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background-color: #555;
    }
    ::-webkit-scrollbar-button {
      display: none;
    }
    @media (max-width: 600px) {
      body {
        flex-direction: column;
      }
      .menu {
        width: 100%;
        height: auto;
        max-height: 200px;
      }
      .menu ul {
        max-height: none;
      }
      .iframe-container {
        height: calc(100vh - 260px);
      }
    }

  </style>
</head>
<body>
  <div class="menu">  
    <ul>
    <a href="/welcome"><img src="/image/logo.png" alt="logo" style="width:100%; background: linear-gradient(to bottom, #dce0e8 50%, #1d1d52 50%);"></a>
	  
    <?php
    $type = isset($_GET['type']) ? $_GET['type'] : 'none';
    $level = isset($_GET['level']) ? $_GET['level'] : 'none';
    
    if ($type ==='book' && $level === 'pre-vpho') {
      echo '<h2>SÁCH IN / ẤN BẢN</h2>';
      echo '<h3>Trước vòng chọn VPhO</h3>';
      $filePath = '../physics/books-pre-vpho.txt';
    } elseif ($type ==='book' && $level === 'vpho-vn') {
        echo '<h2>SÁCH IN / ẤN BẢN</h2>';
        echo '<h3>VPhO và vòng chọn</h3>';
        $filePath = '../physics/books-vpho-vn.txt';
    } elseif ($type ==='book' && $level === 'vpho-en') {
        echo '<h2>SÁCH IN / ẤN BẢN<br>Tiếng Anh</h2>';
        echo '<h3>VPhO và vòng chọn</h3>';
        $filePath = '../physics/books-vpho-en.txt';
    } elseif ($type ==='material' && $level === 'pho') {
        echo '<h2>TÀI LIỆU / HANDOUTS</h2>';
        echo '<h3>VPhO trở lên</h3>';
        $filePath = '../physics/materials-pho.txt';
    } elseif ($type ==='paper-sol' && $level === 'pho') {
      echo '<h2>ĐỀ THI & ĐÁP ÁN</h2>';
      echo '<h3>PhO cấp khu vực đến quốc tế</h3>';
      $filePath = '../physics/paper-sol-pho.txt';
    } elseif ($type ==='magazines' && $level === 'all') {
      echo '<h2>TẠP CHÍ</h2>';
      echo '<h3>PhO cấp khu vực đến quốc tế</h3>';
      $filePath = '../physics/magazines.txt';
    } elseif ($type ==='lessons' && $level === 'all') {
      echo '<h2>NỘI DUNG NGÀY HỌC</h2>';
      echo '<h3>Đội tuyển vật lí CNH</h3>';
      $filePath = '../physics/lessons.txt';
    } else {
        echo '<h3>Khác</h3>';
        $filePath = '../physics/books-other.txt';
    }
    ?>
    <div class="search-container">
      <input type="text" id="search-input" placeholder="Tìm sách theo tên...">
    </div>

    <div class="sort-options">
      <label for="sort-select">Sắp theo:</label>
      <select id="sort-select">
        <option value="title">Tên tài liệu</option>
        <option value="author">Tác giả</option>
      </select>
    </div>
    <div class="book-container">
    <?php          
      $lines = file($filePath);           
      foreach ($lines as $line) {                
          $bookData = explode('|', $line);               
          if (count($bookData) == 4) {
              $title = trim($bookData[0]);
              $author = trim($bookData[1]);
              $file = trim($bookData[2]);
              $description = trim($bookData[3]);
              echo '<a class="book-item" data-file="' . $file . '" data-title="' . htmlspecialchars($title) . '" data-author="' . htmlspecialchars($author) . '"><li>' . $title . '<br>' . $author . '<br>' . $description . '</li></a>';
          }
      }
    ?>
    </div>
    
    </ul>
    <button class="menu-toggle" aria-label="Toggle Menu">&lt;</button>
  </div>
  <div class="iframe-container">
    <iframe id="content-iframe" src="" frameborder="0"></iframe>
  </div>
  <script src="./script.js"></script> 
  <script>
    (function() {
    var iframe = document.getElementById('content-iframe');
    var type = '<?php echo $type; ?>';
    var level = '<?php echo $level; ?>';

    if (type === 'book' && level === 'pre-vpho') {
      iframe.src = './welcome/books-pre-vpho.html';
    } else if (type === 'book' && level === 'vpho-vn') {
      iframe.src = './welcome/books-vpho-vn.html';
    } else if (type === 'book' && level === 'vpho-en') {
      iframe.src = './welcome/books-vpho-en.html';
    } else if (type === 'material' && level === 'pho') {
      iframe.src = './welcome/materials-pho.html';
    } else if (type === 'paper-sol' && level === 'pho') {
      iframe.src = './welcome/paper-sol-pho.html';
    } else if (type === 'magazines' && level === 'all') {
      iframe.src = './welcome/magazines.html';
    } else if (type === 'lessons' && level === 'all') {
      iframe.src = './welcome/lessons.html';
    } else {
      iframe.src = './welcome/books-other.html';
    }
  })();
  </script>
</body>
</html>