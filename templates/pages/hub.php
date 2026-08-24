<main id="main-content" class="main hub-main">
  <div class="world"><div class="stage hub" data-planetary="hub"><div class="aura"></div><div class="vignette"></div>
    <nav class="planetLabelLayer" aria-label="Các không gian học tập">
      <?php foreach ([['Toán học','/math',false],['Vật lý','/physics',true],['Tin học','/it',false],['Hóa học','/chemistry',false]] as $index => [$field,$href,$active]): ?>
        <a class="planetLabel<?= $active ? ' isActive' : ' isStone' ?>" href="<?= $href ?>" data-hub-planet="<?= $index ?>"><i></i><span><?= e($field) ?></span></a>
      <?php endforeach; ?>
    </nav>
  </div></div>
  <div class="noise" aria-hidden="true"></div>
  <section class="hero" aria-label="CNH Study Hub"></section>
  <section class="fields" id="fields" aria-labelledby="fields-title">
    <h1 class="sectionTitle" id="fields-title">Lĩnh vực</h1>
    <div class="fieldGrid">
      <a href="/physics" aria-label="Mở không gian Vật lý" class="fieldCard physicsCard">
        <div class="cardGlow" aria-hidden="true"></div>
        <span class="cardSymbol"><?= icon('atom', 260) ?></span>
        <div class="cardBody"><h3>Vật lý</h3></div>
        <div class="cardFoot"><span><strong><?= e($count) ?></strong> tài liệu</span><span>Đi vào <?= icon('arrow', 18) ?></span></div>
      </a>
      <?php foreach ([['Toán học', '∑'], ['Tin học', '01'], ['Hóa học', '⚗']] as [$field, $symbol]): ?>
        <article class="fieldCard futureCard" aria-disabled="true">
          <span class="futureSymbol" aria-hidden="true"><?= e($symbol) ?></span>
          <div class="cardBody"><h3><?= e($field) ?></h3></div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>
