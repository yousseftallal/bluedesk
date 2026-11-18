<?php
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
$page = $slug !== '' ? db_fetch("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$slug]) : null;

if (!$page) {
    http_response_code(404);
    $pageSlug = 'home';
    require __DIR__ . '/includes/site-header.php';
    ?>
    <main id="main">
      <section class="page-header">
        <div class="container">
          <h1>404</h1>
          <p>الصفحة المطلوبة غير موجودة أو تم حذفها. / The requested page was not found.</p>
          <p style="margin-top:1.4rem"><a class="btn btn-primary" href="index.php">العودة للرئيسية</a></p>
        </div>
      </section>
    </main>
    <?php
    require __DIR__ . '/includes/site-footer.php';
    exit;
}

$pageSlug = $page['slug'];
require __DIR__ . '/includes/site-header.php';
?>
  <main id="main">
    <section class="page-header">
      <div class="container">
        <h1 data-ar="<?= e($page['title_ar']) ?>" data-en="<?= e($page['title_en']) ?>"><?= e($page['title_ar']) ?></h1>
        <nav class="breadcrumb" aria-label="مسار التنقل">
          <a href="index.php" data-i18n="nav.home">الرئيسية</a>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          <span aria-current="page" data-ar="<?= e($page['title_ar']) ?>" data-en="<?= e($page['title_en']) ?>"><?= e($page['title_ar']) ?></span>
        </nav>
      </div>
    </section>
    <section class="section">
      <div class="container page-content">
        <?php $contentAr = sanitize_rich_html((string) $page['content_ar']); $contentEn = sanitize_rich_html((string) $page['content_en']); ?>
        <div data-ar-html="<?= e($contentAr) ?>" data-en-html="<?= e($contentEn) ?>">
          <?= $contentAr ?>
        </div>
      </div>
    </section>
  </main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
