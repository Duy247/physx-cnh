<main id="main-content" class="main hub-main">
  <div class="world"><div class="stage hub" data-planetary="hub"><div class="aura"></div><div class="vignette"></div>
    <nav class="planetLabelLayer" aria-label="Các không gian học tập">
      <?php foreach ([['Toán học','/math',false,4],['Vật lý','/physics',true,2],['Tin học','/it',false,6],['Hóa học','/chemistry',false,1]] as [$field,$href,$active,$planetIndex]): ?>
        <a class="planetLabel<?= $active ? ' isActive' : ' isStone' ?>" href="<?= $href ?>" data-hub-planet="<?= $planetIndex ?>"><i></i><span><?= e($field) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <a class="deepSpaceGateway" href="https://astronomy.physx-cnh.com" aria-label="Đi xa hơn đến không gian Thiên văn học">
      <span>Đi xa hơn</span><span class="deepSpaceArrow" aria-hidden="true">↗</span>
    </a>
  </div></div>
  <div class="noise" aria-hidden="true"></div>
  <section class="hero" aria-label="CNH Study Hub"></section>
  <?php $showcaseImages = [
      '/physics/img/begiang_2019.jpg', '/physics/img/image2.jpg', '/physics/img/image3.jpg',
      '/physics/img/image4.jpg', '/physics/img/image5.jpg', '/physics/img/image8.jpg',
      '/physics/img/image9.jpg', '/physics/img/khaigiang_2016.jpg',
      '/physics/img/random_moment_1.jpg', '/physics/img/thapbut1.jpg',
  ]; ?>
  <section class="aboutSection" aria-labelledby="about-title">
    <div class="legacyShowcase" data-showcase>
      <a class="aboutLink" href="/about" aria-labelledby="about-title about-prompt"><h2 id="about-title">Về chúng tôi</h2><span id="about-prompt">Gặp người đứng sau PhysX-CNH <?= icon('arrow', 20) ?></span></a>
      <div class="showcaseViewport">
        <div class="showcaseTrack">
          <?php for ($copy = 0; $copy < 2; $copy++): ?>
            <div class="showcaseSet"<?= $copy ? ' aria-hidden="true"' : '' ?>>
              <?php foreach ($showcaseImages as $index => $source): ?>
                <figure class="showcaseFrame"><img src="<?= e($source) ?>" alt="<?= $copy ? '' : 'Hoạt động PhysX-CNH – ảnh ' . ($index + 1) ?>" loading="<?= $index < 3 && !$copy ? 'eager' : 'lazy' ?>" decoding="async"></figure>
              <?php endforeach; ?>
            </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </section>
  <section class="fields" id="fields" aria-labelledby="fields-title" data-field-orbit>
    <div class="fieldStage">
      <h1 class="sectionTitle" id="fields-title">Lĩnh vực</h1>
      <div class="fieldGrid">
        <a href="/physics" aria-label="Mở không gian Vật lý" class="fieldCard physicsCard" data-field-card>
          <div class="cardGlow" aria-hidden="true"></div>
          <span class="cardSymbol"><?= icon('atom', 260) ?></span>
          <div class="cardBody"><h3>Vật lý</h3></div>
          <div class="cardFoot"><span><strong><?= e($count) ?></strong> tài liệu</span><span>Đi vào <?= icon('arrow', 18) ?></span></div>
        </a>
        <?php foreach ([['Toán học', '∑'], ['Tin học', '01'], ['Hóa học', '⚗']] as [$field, $symbol]): ?>
          <article class="fieldCard futureCard" aria-disabled="true" data-field-card>
            <span class="futureSymbol" aria-hidden="true"><?= e($symbol) ?></span>
            <div class="cardBody"><h3><?= e($field) ?></h3></div>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="fieldSteps" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    </div>
  </section>
</main>
