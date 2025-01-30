<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lộ trình ôn tập</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="./image/favicon.ico">
    <script src="https://unpkg.com/gojs@3.0.11/release/go.js"></script>
    <link rel="stylesheet" href="/css/common_reverse.css">
    <link rel="stylesheet" href="/css/roadmap.css">      
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
                <li><a href="./article">Cách tìm bài báo khoa học</a></li>
                <li><a href="./donate" id="donate">Ủng hộ duy trì trang web</a></li>
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
        <div id="sample">
            <div id="myDiagramDiv" style="width: 100%; height: 70vh; background-color: #bababa"></div>  
                <textarea id="mySavedModel" style="width: 100%; display:none">
                <?php
                        $Model = './roadmap/article.txt'; // Path to your text file
                        echo "". file_get_contents($Model) . "";
                ?> 
                </textarea> 
                <div id="nodeInfo">Click vào từng hạng mục để xem chi tiết</div>
            </div>
        </div>
    </div>

    <script>
        <?php
            $nodeDataList = './roadmap/article_des.txt'; // Path to your text file
            echo "const nodeDataList = " . file_get_contents($nodeDataList) . ";";
        ?>
        
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
        var donate = document.getElementById('donate');
        
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
        
    </script> 
    <script id="code">
  function init() {
    myDiagram = new go.Diagram('myDiagramDiv', {
      'animationManager.initialAnimationStyle': go.AnimationStyle.None,
      InitialAnimationStarting: (e) => {
        var animation = e.subject.defaultAnimation;
        animation.easing = go.Animation.EaseOutExpo;
        animation.duration = 800;
        animation.add(e.diagram, 'scale', 0.3, 1);
        animation.add(e.diagram, 'opacity', 0, 1);
      },

      // have mouse wheel events zoom in and out instead of scroll up and down
      'toolManager.mouseWheelBehavior': go.WheelMode.Zoom,
      // support double-click in background creating a new node
      //'clickCreatingTool.archetypeNodeData': { text: 'new node' },
      // enable undo & redo
      'undoManager.isEnabled': false
    });

    myDiagram.addDiagramListener("ObjectSingleClicked", function(e) {
        if (e.subject && e.subject.part && !(e.subject.part instanceof go.Link)) {
            var nodeData = e.subject.part.data;
            var additionalContent = nodeDataList[nodeData.text]?.content || ""; 
            document.getElementById("nodeInfo").innerHTML = "Nội dung: " + nodeData.text + additionalContent;
        }
    });


    const colors = {
      pink: '#facbcb',
      blue: '#b7d8f7',
      green: '#b9e1c8',
      yellow: '#faeb98',
      background: 'white'
    };
    const colorsDark = {
      green: '#3fab76',
      yellow: '#f4d90a',
      blue: '#0091ff',
      pink: '#e65257',
      background: '#929292'
    };
    myDiagram.div.style.backgroundColor = colors.background;

    myDiagram.nodeTemplate = new go.Node('Auto', {
      isShadowed: true,
      shadowBlur: 0,
      shadowOffset: new go.Point(5, 5),
      shadowColor: 'black'
    })
      .bindTwoWay('location', 'loc', go.Point.parse, go.Point.stringify)
      .add(
        new go.Shape('RoundedRectangle', {
          strokeWidth: 1.5,
          fill: colors.blue,
          portId: '',
          fromLinkable: true, fromLinkableSelfNode: false, fromLinkableDuplicates: true,
          toLinkable: true, toLinkableSelfNode: false, toLinkableDuplicates: true,
          cursor: 'pointer'
        })
          .bind('fill', 'type', (type) => {
            if (type === 'Start') return colors.green;
            if (type === 'End') return colors.pink;
            return colors.blue;
          })
          .bind('figure', 'type', (type) => {
            if (type === 'Start' || type === 'End') return 'Circle';
            return 'RoundedRectangle';
          }),
        new go.TextBlock({
          font: 'bold small-caps 11pt helvetica, bold arial, sans-serif',
          shadowVisible: false,
          margin: 8,
          font: 'bold 14px sans-serif',
          stroke: '#333'
        }).bind('text')
      );

   

    // clicking the button inserts a new node to the right of the selected node,
    // and adds a link to that new node
    

    // replace the default Link template in the linkTemplateMap
    myDiagram.linkTemplate = new go.Link({
      // shadow options are for the label, not the link itself
      isShadowed: true,
      shadowBlur: 0,
      shadowColor: 'black',
      shadowOffset: new go.Point(2.5, 2.5),

      curve: go.Curve.Bezier,
      curviness: 40,
      adjusting: go.LinkAdjusting.Stretch,
      reshapable: true,
      relinkableFrom: true,
      relinkableTo: true,
      fromShortLength: 8,
      toShortLength: 10
    })
      .bindTwoWay('points')
      .bind('curviness')
      .add(
        // Main shape geometry
        new go.Shape({ strokeWidth: 2, shadowVisible: false, stroke: 'black' })
          .bind('strokeDashArray', 'progress', (progress) => (progress ? [] : [5, 6]))
          .bind('opacity', 'progress', (progress) => (progress ? 1 : 0.5)),
        // Arrowheads
        new go.Shape({ fromArrow: 'circle', strokeWidth: 1.5, fill: 'white' }).bind('opacity', 'progress', (progress) => (progress ? 1 : 0.5)),
        new go.Shape({ toArrow: 'standard', stroke: null, scale: 1.5, fill: 'black' }).bind('opacity', 'progress', (progress) => (progress ? 1 : 0.5)),
        // The link label
        new go.Panel('Auto')
          .add(
            new go.Shape('RoundedRectangle', {
              shadowVisible: true,
              fill: colors.yellow,
              strokeWidth: 0.5
            }),
            new go.TextBlock({
              font: '9pt helvetica, arial, sans-serif',
              margin: 1,
              editable: true, // enable in-place editing
              text: 'Action' // default text
            }).bind('text')
            // editing the text automatically updates the model data
          )
      );

    // read in the JSON data from the "mySavedModel" element
    load();
  }

  function load() {
    myDiagram.model = go.Model.fromJson(document.getElementById('mySavedModel').value);
  }

  window.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>

