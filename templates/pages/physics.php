<?php $kindLabels = ['book' => 'Sách', 'material' => 'Chuyên đề', 'paper' => 'Đề thi', 'magazine' => 'Tạp chí']; ?>
<main id="main-content" class="main physics-main">
  <div class="world">
    <div class="stage physics" data-planetary="physics">
      <div class="aura"></div><div class="vignette"></div>
      <nav class="satelliteLayer" aria-label="Các kho tài liệu Vật lý">
        <?php foreach ([['VINASAT-1','Sách chuyên Vật lý','book'],['VINASAT-2','Đề thi & đáp án','paper'],['VNREDSat-1','Tài liệu chuyên đề','material'],['PicoDragon','Tạp chí Vật lý','magazine']] as $index => [$spacecraft,$label,$kind]): ?>
          <a class="satelliteLabel" href="/library?kind=<?= $kind ?>&amp;orbit=1" data-satellite="<?= $index ?>" data-spacecraft="<?= e($spacecraft) ?>"><i></i><span><b><?= e($spacecraft) ?></b><small><?= e($label) ?></small></span></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </div>
  <div class="spectralWash" aria-hidden="true"></div>
  <section class="hero" aria-label="PhysX-CNH">
    <details class="relay" data-relay>
      <summary class="trigger"><span class="beacon"><?= icon('satellite',18) ?><i></i></span><span class="triggerCopy"><strong>Houston, tài liệu tới</strong><small>Đã nhận <?= str_pad((string) count($inbound), 2, '0', STR_PAD_LEFT) ?> tín hiệu</small></span><span class="chevron">⌄</span></summary>
      <div class="transmission"><header><span><?= icon('satellite',14) ?> Tiếp sóng / <b data-relay-source>K2-18 b</b></span><b>Đang nhận</b></header><div class="message"><strong>Tài liệu mới vào quỹ đạo</strong></div><ol>
        <?php foreach ($inbound as $index => $document): ?><li><a href="/document/<?= e($document['slug']) ?>"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><span class="documentCopy"><strong><?= e($document['title']) ?></strong><small><?= e($kindLabels[$document['kind']] ?? 'Tài liệu') ?></small></span><?= icon('arrow',15) ?></a></li><?php endforeach; ?>
      </ol></div>
    </details>
    <div class="heroActions"><a href="/library">Thư viện <?= icon('arrow',18) ?></a><a href="/guides/roadmap">Lộ trình</a></div>
  </section>
  <section class="observatory">
    <section class="searchPanel" aria-label="Tìm tài liệu"><form class="searchBox" action="/library"><?= icon('search',22) ?><input name="q" type="search" placeholder="Tìm tài liệu…" aria-label="Tìm tài liệu"><button type="submit" aria-label="Tìm tài liệu"><?= icon('arrow',21) ?></button></form></section>
  </section>
</main>
