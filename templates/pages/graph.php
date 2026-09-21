<?php $isRoadmap = $graph === 'roadmap'; ?>
<main id="main-content" class="page graph-page" data-graph-page="<?= e($graph) ?>">
  <header><h1><?= $isRoadmap ? 'Lộ trình ôn tập Vật lý chuyên' : 'Cách tìm một bài báo khoa học' ?></h1></header>
  <section class="graphShell" data-graph-shell aria-label="Sơ đồ tương tác">
    <div class="graphBar"><span>◎ Chạm một nút để xem chi tiết · kéo để di chuyển · cuộn hoặc chụm để thu phóng</span><button type="button" data-fullscreen aria-label="Mở sơ đồ toàn màn hình"><?= icon('expand',19) ?></button></div>
    <div class="diagram" data-diagram></div>
    <aside class="detail" data-detail hidden><button type="button" data-detail-close aria-label="Đóng nội dung chi tiết"><?= icon('close',18) ?></button><h2 data-detail-title></h2><div data-detail-content></div></aside>
  </section>
</main>
