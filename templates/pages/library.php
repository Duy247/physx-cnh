<?php
$kindLabels = ['book' => 'Sách', 'material' => 'Chuyên đề', 'paper' => 'Đề thi', 'magazine' => 'Tạp chí'];
$payload = [];
foreach ($documents as $document) {
    $payload[] = [
        'slug' => $document['slug'], 'title' => $document['title'], 'authors' => $document['authors'],
        'description' => $document['description'], 'kind' => $document['kind'], 'language' => $document['language'],
        'pages' => $document['pages'], 'addedAt' => $document['addedAt'], 'cover' => $catalogService->coverUrl($document),
        'competition' => $document['competition'] ?? null, 'competitionLabel' => $document['competitionLabel'] ?? null,
        'year' => $document['year'] ?? null, 'role' => $document['role'] ?? null, 'problemNumber' => $document['problemNumber'] ?? null,
    ];
}
$initialCount = count($initialDocuments);
?>
<main id="main-content" class="page-shell">
  <p class="page-kicker"><span data-library-total><?= $initialCount ?></span> tài liệu</p>
  <h1 class="page-title" data-library-title><?= e($heading) ?></h1>
  <section class="explorer" data-library data-initial-kind="<?= e($kind) ?>" data-orbit="<?= $orbit ? '1' : '0' ?>">
    <div class="controls">
      <label class="search"><?= icon('search',22) ?><input data-search type="search" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Tìm tên sách, tác giả, kỳ thi…" aria-label="Tìm tài liệu"><kbd>⌘ K</kbd></label>
      <div class="filters">
        <?php if (!$orbit): ?><label>Loại<select data-kind><option value="all">Tất cả</option><?php foreach ($kindLabels as $value => $label): ?><option value="<?= $value ?>" <?= $kind === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><?php endif; ?>
        <span data-paper-filters hidden><label>Kỳ thi<select data-competition><option value="all">Tất cả</option></select></label><label>Năm<select data-year><option value="all">Tất cả</option></select></label></span>
        <label>Ngôn ngữ<select data-language><option value="all">Tất cả</option><option value="vi">Tiếng Việt</option><option value="en">English</option></select></label>
        <label>Sắp xếp<select data-sort><option value="title">Tên tài liệu</option><option value="author">Tác giả</option></select></label>
      </div>
    </div>
    <div class="resultHeading"><p><strong data-result-count><?= $initialCount ?></strong> tài liệu phù hợp</p><button type="button" data-clear hidden>Xóa bộ lọc</button></div>
    <div class="grid" data-results></div>
    <button class="button more" type="button" data-more hidden>Xem thêm</button>
    <div class="empty" data-empty hidden><p>Không tìm thấy tài liệu phù hợp.</p><button class="button" type="button" data-reset>Đặt lại tìm kiếm</button></div>
  </section>
  <script type="application/json" id="library-data"><?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</main>
