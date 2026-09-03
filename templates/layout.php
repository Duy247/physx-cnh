<?php
/** @var string $content */
$isHub = ($site ?? 'physics') === 'hub';
$bodyClasses = [$isHub ? 'hub-site' : 'physics-site'];
if ($immersive ?? false) {
    $bodyClasses[] = 'immersive-landing';
}
$navigation = $isHub
    ? [['/physics', 'Vật lý']]
    : [['/physics', 'Tổng quan'], ['/library', 'Thư viện'], ['/guides/roadmap', 'Lộ trình'], ['/guides/research', 'Nghiên cứu']];
$cssFiles = ['globals'];
if (!empty($pageCss)) {
    $cssFiles[] = (string) $pageCss;
}
if (in_array($pageCss ?? '', ['hub', 'physics'], true)) {
    $cssFiles[] = 'cinematic';
}
if (($pageCss ?? '') === 'physics') {
    $cssFiles[] = 'relay';
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title><?= e($title ?? 'PhysX-CNH') ?> · CNH Study Hub</title>
  <meta name="description" content="<?= e($description ?? '') ?>">
  <meta name="theme-color" content="#f7f3e8">
  <meta property="og:locale" content="vi_VN">
  <meta property="og:site_name" content="CNH Study Hub">
  <meta property="og:title" content="<?= e($title ?? 'PhysX-CNH') ?>">
  <meta property="og:description" content="<?= e($description ?? '') ?>">
  <meta property="og:image" content="https://physx-cnh.com/assets/v2/images/physx-cnh-og.png">
  <link rel="icon" href="/image/favicon.ico">
  <link rel="preload" href="/assets/v2/fonts/be-vietnam-pro-300-latin.woff2" as="font" type="font/woff2" crossorigin>
<?php foreach (array_unique($cssFiles) as $css): ?>
  <link rel="stylesheet" href="/assets/v2/css/<?= e($css) ?>.css?v=32">
<?php endforeach; ?>
</head>
<body class="<?= e(implode(' ', $bodyClasses)) ?>">
<a class="skip-link" href="#main-content">Bỏ qua điều hướng</a>
<div class="site-frame">
  <header class="site-header">
    <a class="site-brand" href="<?= $isHub ? '/' : '/physics' ?>" aria-label="<?= $isHub ? 'CNH Study Hub — Trang chủ' : 'PhysX-CNH — Trang Vật lý' ?>">
      <img src="/image/logo.png" width="124" height="72" alt="PhysX-CNH">
      <span class="site-brand-context"><?= $isHub ? 'STUDY HUB' : 'VẬT LÝ' ?></span>
    </a>
    <nav class="desktop-nav" aria-label="Điều hướng chính">
      <?php foreach ($navigation as [$href, $label]): ?><a href="<?= $href ?>"><?= e($label) ?></a><?php endforeach; ?>
    </nav>
    <div class="header-actions">
      <a class="icon-link" href="<?= $isHub ? '/physics' : '/library' ?>" aria-label="<?= $isHub ? 'Mở không gian Vật lý' : 'Tìm tài liệu' ?>"><?= icon($isHub ? 'orbit' : 'search', $isHub ? 21 : 19) ?></a>
      <?php if (!$isHub): ?><a class="icon-link hub-link" href="/" aria-label="Tất cả lĩnh vực"><?= icon('grid', 18) ?></a><?php endif; ?>
      <details class="mobile-menu">
        <summary aria-label="Mở menu"><?= icon('menu', 20) ?></summary>
        <nav aria-label="Điều hướng di động">
          <?php foreach ($navigation as [$href, $label]): ?><a href="<?= $href ?>"><?= e($label) ?></a><?php endforeach; ?>
        </nav>
      </details>
    </div>
  </header>
  <?= $content ?>
  <footer class="site-footer">
    <div><p class="site-footer__brand">PHYSX-CNH</p><p>Kho học liệu mở cho cộng đồng học sinh chuyên.</p></div>
    <nav aria-label="Liên kết cuối trang"><a href="/about">Về chúng tôi</a><a href="/donate">Ủng hộ</a><a href="/donators">Người đóng góp</a><a href="/legal">Pháp lý</a><a href="https://astronomy.physx-cnh.com">AstroGallery ↗</a></nav>
    <p class="site-footer__end">PHYSX-CNH · <?= date('Y') ?></p>
  </footer>
</div>
<script src="/assets/v2/js/app.js?v=7" defer></script>
<?php if (in_array($pageCss ?? '', ['hub', 'physics'], true)): ?><script type="module" src="/assets/v2/js/planetary.js?v=23"></script><?php endif; ?>
<?php if (($pageCss ?? '') === 'library'): ?><script src="/assets/v2/js/library.js?v=7" defer></script><?php endif; ?>
<?php if (($pageCss ?? '') === 'graph'): ?><script src="/assets/v2/vendor/go.js?v=4.0.3" defer></script><script src="/assets/v2/js/graphs.js?v=4" defer></script><?php endif; ?>
</body>
</html>
