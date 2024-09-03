<!DOCTYPE html>
<html>
<head>
  <title>Sách HSG TP</title>
  <link rel="stylesheet" href="style.css"> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link rel="shortcut icon" type="image/x-icon" href="./image/favicon.ico">
  <meta charset="UTF-8">
  
</head>
<body>
  <div class="menu">  
    <ul>
    <a href="/welcome"><img src="/image/logo.png" alt="logo" style="width:100%;"></a>
	  <h2>SÁCH IN / ẤN BẢN</h2>
    <h3>Trước vòng chọn VPhO</h3>
    <div class="search-container">
      <input type="text" id="search-input" placeholder="Tìm sách theo tên...">
    </div>

    <div class="sort-options">
      <label for="sort-select">Sắp theo:</label>
      <select id="sort-select">
        <option value="title">Tên sách</option>
        <option value="author">Tác giả</option>
      </select>
    </div>
    <div class="book-container">
    <?php          
      $lines = file('../physics/books-pre-vpho.txt');           
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
    <iframe id="content-iframe" src="./welcome/welcome_physics.html" frameborder="0"></iframe>
  </div>
  <script src="./script.js"></script> 
  <script>
    
  </script>
</body>
</html>