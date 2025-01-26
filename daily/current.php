<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.5">
    <title>PhysX-CNH</title>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="./exercise.css">
</head>
<body>
    <div class="context">
        <div class="content-wrap">        
            <div class="content">
                <h2>xPhO Physics Club</h2>
                <div class="note">
                    Đây là các bài tập được đăng trên nhóm xPhO Physics Club. Mỗi ngày sẽ có một bộ bốn bài Cơ - Nhiệt - Điện - Quang được đăng trên nhóm. Mình sẽ đăng tải lại ở đây kết hợp cùng các comment và gợi ý giải bài tập. Chúc các bạn học tốt! Tham gia nhóm Discord của xPhO tại <a href="https://discord.gg/ZUVKMZM58s" target="_blank">đây</a>.
                </div>
                <form method="GET" action="">
                    <label for="date-select">Chọn ngày:</label>
                    <select id="date-select" name="date">
                        <?php
                        $files = glob('./exercises/*.json');
                        foreach ($files as $file) {
                            $json = file_get_contents($file);
                            $data = json_decode($json, true);
                            $selected = (isset($_GET['date']) && $_GET['date'] == basename($file)) ? 'selected' : '';
                            echo '<option value="' . basename($file) . '" ' . $selected . '>' . $data['date'] . '</option>';
                        }
                        ?>
                    </select>
                    <button type="submit">Xem bài tập</button>
                </form>
                <?php
                if (isset($_GET['date'])) {
                    $json = file_get_contents('./exercises/' . $_GET['date']);
                    $data = json_decode($json, true);
                    echo '<h2>' . $data['date'] . '</h2>';
                ?>
                <div class="slideshow-container">
                    <div class="slides">
                        <?php
                        foreach ($data['exercise'] as $slide) {
                            echo '<div class="slide">';
                            echo '<h3>' . $slide['title'] . '</h3>';
                            echo '<div class="task">' . $slide['task'] . '</div>';
                            echo '<h3 class="hint-toggle" onclick="toggleHint(event)">Gợi ý <i class="fas fa-caret-down"></i></h3>';
                            echo '<div class="hint-content"><p>' . $slide['hint'] . '</p></div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                    <a class="next" onclick="plusSlides(1)">&#10095;</a>
                </div>
                <?php
                }
                ?>
            </div>
        </div>
            <div class="footers">
            </div>
    </div>
    
    <div class="area" >
        <ul class="circles">
                <li>Hello</li>
                <li></li>
                <li><img src="https://media.tenor.com/EZm07tiVCgEAAAAi/gstarludi-peepo.gif"></li>
                <li></li>
                <li><img src="https://media.tenor.com/olauWWzjGykAAAAi/pepe-noodles.gif"></li>
                <li>You<br>Might Be<br>Tired</li>
                <li></li>
                <li></li>
                <li><img src="https://media.tenor.com/kgW8YAeZ9kcAAAAi/pepe.gif"></li>
                <li></li>
                <li></li>
                <li><img src="https://media.tenor.com/bdela5G4BHkAAAAi/peepo-meme.gif"></li>
        </ul>
    </div > 
    <script>
        function toggleHint(event) {
            event.target.classList.toggle('active');
            var hintContent = event.target.nextElementSibling;
            hintContent.style.display = hintContent.style.display === 'block' ? 'none' : 'block';
        }
        const MathJaxScript = () => {
        const script = document.createElement('script');
        script.type = "text/javascript";
        script.src = 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js';
        script.async = true;
        document.head.appendChild(script);
        };

        window.MathJax = {
        tex: {
            inlineMath: [["$", "$"], ["\\(", "\\)"]],
            packages: ["base", "newcommand", "configMacros"]
        },
        svg: {
            fontCache: "global"
        }
        };

        MathJaxScript();

        let slideIndex = 0;
        showSlides(slideIndex);

        function plusSlides(n) {
            showSlides(slideIndex += n);
        }

        function showSlides(n) {
            let slides = document.getElementsByClassName("slide");
            if (n >= slides.length) { slideIndex = 0 }
            if (n < 0) { slideIndex = slides.length - 1 }
            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            slides[slideIndex].style.display = "block";
        }
    </script>
</body>
</html>