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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.15/index.global.min.css">
    <style>
        #calendar {
            width: 70%;
            max-height: 80%;
            margin: auto;
        }
        #choose-date-btn {
            display: none;
            margin-bottom: 20px;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            background-color: #1d1d52;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        #choose-date-btn:hover {
            background-color: #4e54c8;
        }
        .slideshow-container {
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        .slides {
            display: flex;
            transition: transform 0.5s ease-in-out;
        }
        .slide {
            min-width: 100%;
            box-sizing: border-box;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            max-height: 80%;
            overflow-y: auto;
            position: relative;
        }
        .close-modal-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="context">
        <div class="content-wrap">        
            <div class="content">
                <h2>xPhO Physics Club</h2>
                <div class="note">
                    Đây là các bài tập được đăng trên nhóm xPhO Physics Club. Mỗi ngày sẽ có một bộ bốn bài Cơ - Nhiệt - Điện - Quang được đăng trên nhóm. Mình sẽ đăng tải lại ở đây kết hợp cùng các comment và gợi ý giải bài tập. Chúc các bạn học tốt! Tham gia nhóm Discord của xPhO tại <a href="https://discord.gg/ZUVKMZM58s" target="_blank">đây</a>.
                </div>
                <button id="choose-date-btn" onclick="window.location.href='./current.php'">Quay lại</button>
                <div id="calendar"></div>
                <div id="exercise-container" style="display: none;">
                    <?php
                    if (isset($_GET['date'])) {
                        $json = file_get_contents('./exercises/' . $_GET['date']);
                        $data = json_decode($json, true);
                        echo '<h2>' . $data['datetext'] . '</h2>';
                    ?>
                    <form method="GET" action="">
                        <input type="hidden" name="date" value="<?php echo $_GET['date']; ?>">
                        <label for="exercise-select">Chọn bài tập:</label>
                        <select id="exercise-select" name="exercise" onchange="this.form.submit()">
                            <option value="" disabled selected>Chọn bài tập</option>
                            <?php
                            foreach ($data['exercise'] as $index => $exercise) {
                                $selected = (isset($_GET['exercise']) && $_GET['exercise'] == $index) ? 'selected' : '';
                                echo '<option value="' . $index . '" ' . $selected . '>' . $exercise['title'] . '</option>';
                            }
                            ?>
                        </select>
                    </form>
                    <div class="slideshow-container">
                        <div class="slides">
                            <?php
                            if (isset($_GET['exercise'])) {
                                $slide = $data['exercise'][$_GET['exercise']];
                                echo '<div class="slide">';
                                echo '<h3>' . $slide['title'] . '</h3>';
                                echo '<div class="task">' . $slide['task'] . '</div>';
                                echo '<h3 class="hint-toggle" onclick="toggleHint(event)">Gợi ý <i class="fas fa-caret-down"></i></h3>';
                                echo '<div class="hint-content"><p>' . $slide['hint'] . '</p></div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                    }
                    ?>
                </div>
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
    <div id="exercise-modal" class="modal">
        <div class="modal-content">        
            <button class="close-modal-btn" onclick="toggleModal(false)">×</button>
            <h2 id="modal-date-title"></h2>
            <h3>Bao gồm các bài tập</h3>
            <div id="modal-exercise-titles"></div>
            <button id="view-exercises-btn" onclick="proceedToExercises()">Xem</button>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.15/index.global.min.js"></script>
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

        function showCalendar() {
            document.getElementById('calendar').style.display = 'block';
            document.getElementById('exercise-container').style.display = 'none';
            document.getElementById('choose-date-btn').style.display = 'none';
        }

        function toggleModal(show, dateTitle='', titles='', url='') {
            const modal = document.getElementById('exercise-modal');
            document.getElementById('view-exercises-btn').dataset.url = url;
            document.getElementById('modal-date-title').innerText = dateTitle;
            document.getElementById('modal-exercise-titles').innerHTML = titles;
            modal.style.display = show ? 'flex' : 'none';
        }

        function proceedToExercises() {
            const url = document.getElementById('view-exercises-btn').dataset.url;
            if (url) window.location.href = url;
        }

        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: [
                    <?php
                    $files = glob('./exercises/*.json');
                    foreach ($files as $file) {
                        $json = file_get_contents($file);
                        $data = json_decode($json, true);
                        $titles = '';
                        foreach ($data['exercise'] as $exercise) {
                            $titles .= $exercise['quicktitle'] . '<br><div style="text-align: center;"><img src="' . $exercise['quickimg'] . '" style="max-width: 30%; height: auto;"></div><br>';
                        }
                        echo "{ title: '" . $data['datetext'] . "', start: '" . date('Y-m-d', strtotime($data['date'])) . "', url: '?date=" . basename($file) . "', extendedProps: { titles: `" . $titles . "` } },";
                    }
                    ?>
                ],
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        const dateTitle = info.event.title;
                        const titles = info.event.extendedProps.titles.replace(/<br>/g,'<br/>');
                        toggleModal(true, dateTitle, titles, info.event.url);
                    }
                }
            });
            calendar.render();
            document.getElementById('exercise-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    toggleModal(false);
                }
            });
        });

        if (window.location.search.includes('date')) {
            document.getElementById('calendar').style.display = 'none';
            document.getElementById('exercise-container').style.display = 'block';
            document.getElementById('choose-date-btn').style.display = 'block';
        }

    </script>
</body>
</html>