<!DOCTYPE html>
<html>
<head>
    <title>PDF Viewer</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css"> 
    <script src="./pdf.min.js"></script>
    <style>
        /* Add your custom styles here */
    </style>
</head>
<body>
    <div id="notification">Đang tải ...</div>
    <div class="navigation">
        <button id="prevPage">&lt;</button>
        <input type="number" id="pageInput" min="1">
        <button id="jumpPage">Nhảy</button>
        <button id="nextPage">&gt;</button>
        
        <br><span id="pageInfo"></span>
    </div>
    <div id="pdf-container">
        <canvas id="pdf-canvas"></canvas>
    </div>
    <button class="download-button"><a href="" id="downloadButton" download>Tải xuống</a></button>
    
    <a href="/welcome"><img src="/image/logo.png" alt="logo" style="width: 40%; display: block; margin: 0 auto;"></a>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var url = <?php echo json_encode($_GET['url'] ?? ''); ?>;

            var pdfjsLib = window['pdfjs-dist/build/pdf'];
            pdfjsLib.GlobalWorkerOptions.workerSrc = './pdf.worker.min.js';
            var pdfDoc = null;
            var currentPage = 1;
            var totalPages = 0;
            var scale = 1.5;

            function renderPage(num) {
                pdfDoc.getPage(num).then(function(page) {
                    var viewport = page.getViewport({scale: scale});
                    var canvas = document.getElementById('pdf-canvas');
                    var ctx = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    var renderContext = {
                        canvasContext: ctx,
                        viewport: viewport
                    };
                    page.render(renderContext);
                });
            }

            function showPage(pageNum) {
                if (pageNum < 1 || pageNum > totalPages) {
                    return;
                }
                currentPage = pageNum;
                renderPage(pageNum);
                updatePageInfo();
            }

            function updatePageInfo() {
                document.getElementById('pageInfo').textContent = 'Page ' + currentPage + ' of ' + totalPages;
                document.getElementById('pageInput').value = currentPage;
            }

            pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
                pdfDoc = pdfDoc_;
                totalPages = pdfDoc.numPages;

                showPage(currentPage);

                document.getElementById('prevPage').addEventListener('click', function() {
                    if (currentPage > 1) {
                        showPage(currentPage - 1);
                    }
                });

                document.getElementById('nextPage').addEventListener('click', function() {
                    if (currentPage < totalPages) {
                        showPage(currentPage + 1);
                    }
                });

                document.getElementById('jumpPage').addEventListener('click', function() {
                    var pageNum = parseInt(document.getElementById('pageInput').value);
                    if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= totalPages) {
                        showPage(pageNum);
                    }
                });

                updatePageInfo();

                var notification = document.getElementById('notification');
                notification.parentNode.removeChild(notification);

                var downloadButton = document.getElementById('downloadButton');
                downloadButton.href = url;
            });
        });
    </script>
</body>
</html>