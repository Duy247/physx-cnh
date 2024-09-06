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
  <link rel="stylesheet" href="style.css"> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link rel="shortcut icon" type="image/x-icon" href="../image/favicon.ico">
  <meta charset="UTF-8">
  
</head>
<body>
  <div class="menu">  
    <ul>
    <a href="/welcome"><img src="/image/logo.png" alt="logo" style="width:100%;"></a>
	  
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