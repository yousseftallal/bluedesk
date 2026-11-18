<?php
require_once __DIR__ . '/includes/functions.php';

$projectId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$project = $projectId > 0
    ? db_fetch('SELECT * FROM projects WHERE id = ? AND is_active = 1', [$projectId])
    : null;
$projects = $project ? [$project] : [];
$projectGallery = $project
    ? db_fetch_all('SELECT image_path, sort_order FROM project_images WHERE project_id = ? ORDER BY sort_order, id', [(int) $project['id']])
    : [];
$projectImageUrls = [];
if ($project && !empty($project['image'])) {
    $projectImageUrls[] = asset($project['image']);
}
foreach ($projectGallery as $galleryImage) {
    $projectImageUrls[] = asset($galleryImage['image_path']);
}
$galleryIndexOffset = $project && !empty($project['image']) ? 1 : 0;
$pageSlug = 'portfolio';
$projectDemoUrl = $project && !empty($project['show_demo'])
    ? safe_http_url((string) $project['link'])
    : '';
$siteName = setting('site_name', 'BlueDesk');

if ($project) {
    $canonical = url('project.php?id=' . (int) $project['id']);
    $metaOverride = [
        'title_ar' => $project['title_ar'] . ' | ' . $siteName,
        'title_en' => $project['title_en'] . ' | ' . $siteName,
        'desc_ar' => mb_substr(trim(strip_tags($project['desc_ar'])), 0, 300),
        'desc_en' => mb_substr(trim(strip_tags($project['desc_en'])), 0, 300),
        'canonical' => $canonical,
        'image' => $project['image'] ?: setting('hero_image'),
        'image_alt' => $project['title_ar'],
        'og_type' => 'article',
    ];
    $structuredData = [[
        '@context' => 'https://schema.org',
        '@type' => 'CreativeWork',
        'name' => $project['title_ar'],
        'alternateName' => $project['title_en'],
        'description' => trim(strip_tags($project['desc_ar'])),
        'image' => $projectImageUrls ?: [asset(setting('hero_image'))],
        'url' => $canonical,
        'creator' => [
            '@type' => 'Organization',
            'name' => $siteName,
        ],
    ]];
} else {
    http_response_code(404);
    $metaOverride = [
        'title_ar' => 'المشروع غير موجود | ' . $siteName,
        'title_en' => 'Project not found | ' . $siteName,
        'desc_ar' => 'المشروع غير موجود أو لم يعد منشورًا.',
        'desc_en' => 'This project does not exist or is no longer published.',
        'canonical' => url('project.php'),
        'robots' => 'noindex, nofollow',
    ];
    $structuredData = [];
}

require __DIR__ . '/includes/site-header.php';
?>
<main id="main">
  <?php if (!$project): ?>
    <section class="section">
      <div class="container">
        <div class="project-not-found">
          <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
          <h1 data-i18n="project.notfound">المشروع غير موجود أو لم يعد منشورًا.</h1>
          <a class="btn btn-primary" href="portfolio.php" data-i18n="work.back">العودة إلى الأعمال</a>
        </div>
      </div>
    </section>
  <?php else: ?>
    <section class="page-header project-page-header">
      <div class="container">
        <p class="section-tag"><i class="fa-solid fa-briefcase" aria-hidden="true"></i> <span data-ar="<?= e($project['tag_ar']) ?>" data-en="<?= e($project['tag_en']) ?>"><?= e($project['tag_ar']) ?></span></p>
        <h1 data-ar="<?= e($project['title_ar']) ?>" data-en="<?= e($project['title_en']) ?>"><?= e($project['title_ar']) ?></h1>
        <nav class="breadcrumb" aria-label="مسار التنقل">
          <a href="index.php" data-i18n="nav.home">الرئيسية</a>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          <a href="portfolio.php" data-i18n="work.tag">أعمالنا</a>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          <span aria-current="page" data-ar="<?= e($project['title_ar']) ?>" data-en="<?= e($project['title_en']) ?>"><?= e($project['title_ar']) ?></span>
        </nav>
      </div>
    </section>

    <section class="section project-detail-section">
      <div class="container">
        <article class="project-detail-layout">
          <div class="project-detail-media">
            <?php if (!empty($project['image'])): ?>
              <button class="project-image-open project-main-image-open" type="button" data-project-lightbox-trigger data-image-src="<?= e(asset($project['image'])) ?>" aria-label="<?= e($project['title_ar']) ?>">
                <img src="<?= e(asset($project['image'])) ?>"<?= asset_srcset_attributes((string) $project['image'], '(max-width: 900px) calc(100vw - 32px), 900px') ?> alt="<?= e($project['title_ar']) ?>" data-alt-ar="<?= e($project['title_ar']) ?>" data-alt-en="<?= e($project['title_en']) ?>" width="900" height="560" fetchpriority="high" decoding="async">
                <span><i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i></span>
              </button>
            <?php else: ?>
              <div class="project-detail-placeholder"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div>
            <?php endif; ?>
            <?php if ($projectGallery): ?>
              <section class="project-gallery" aria-labelledby="project-gallery-title">
                <h2 id="project-gallery-title" data-i18n="project.gallery">صور من المشروع</h2>
                <div class="project-gallery-grid">
                  <?php foreach ($projectGallery as $galleryIndex => $galleryImage): ?>
                    <?php $galleryImageUrl = asset($galleryImage['image_path']); $imageNumber = $galleryIndex + $galleryIndexOffset + 1; ?>
                    <button class="project-gallery-item project-image-open" type="button" data-project-lightbox-trigger data-image-src="<?= e($galleryImageUrl) ?>" aria-label="<?= e($project['title_ar']) ?> — <?= $imageNumber ?>">
                      <img src="<?= e($galleryImageUrl) ?>"<?= asset_srcset_attributes((string) $galleryImage['image_path'], '(max-width: 720px) 38vw, 220px') ?><?= asset_dimensions_attributes((string) $galleryImage['image_path']) ?> alt="<?= e($project['title_ar']) ?> — <?= $imageNumber ?>" data-alt-ar="<?= e($project['title_ar']) ?> — <?= $imageNumber ?>" data-alt-en="<?= e($project['title_en']) ?> — <?= $imageNumber ?>" loading="lazy" decoding="async">
                      <span><i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i></span>
                    </button>
                  <?php endforeach; ?>
                </div>
              </section>
            <?php endif; ?>
          </div>
          <div class="project-detail-content">
            <h2 data-i18n="project.overview">عن المشروع</h2>
            <p class="project-full-description" data-ar="<?= e($project['desc_ar']) ?>" data-en="<?= e($project['desc_en']) ?>"><?= e($project['desc_ar']) ?></p>
            <?php $technologies = array_values(array_filter(array_map('trim', explode(',', (string) $project['stack'])))); ?>
            <?php if ($technologies): ?>
              <h3 data-i18n="project.tech">التقنيات المستخدمة</h3>
              <div class="pc-stack project-detail-stack">
                <?php foreach ($technologies as $technology): ?><span><?= e($technology) ?></span><?php endforeach; ?>
              </div>
            <?php endif; ?>
            <div class="project-detail-actions">
              <?php if ($projectDemoUrl !== ''): ?>
                <a class="btn btn-primary" href="<?= e($projectDemoUrl) ?>" target="_blank" rel="noopener noreferrer"><span data-i18n="work.demo">تجربة المشروع</span> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
              <?php endif; ?>
              <a class="btn btn-outline" href="portfolio.php"><span data-i18n="work.back">العودة إلى الأعمال</span> <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
            </div>
          </div>
        </article>
      </div>
    </section>
    <?php if ($projectImageUrls): ?>
      <dialog class="project-lightbox" data-project-lightbox-dialog data-i18n-attr="aria-label:project.image.viewer">
        <div class="project-lightbox-toolbar">
          <span class="project-lightbox-counter" data-project-lightbox-counter aria-live="polite"></span>
          <button class="project-lightbox-close" type="button" data-project-lightbox-close data-i18n-attr="aria-label:project.image.close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
        <div class="project-lightbox-stage">
          <button class="project-lightbox-nav" type="button" data-project-lightbox-prev data-i18n-attr="aria-label:project.image.prev"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
          <img data-project-lightbox-image src="" alt="">
          <button class="project-lightbox-nav" type="button" data-project-lightbox-next data-i18n-attr="aria-label:project.image.next"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        </div>
      </dialog>
    <?php endif; ?>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
