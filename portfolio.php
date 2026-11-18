<?php
require_once __DIR__ . '/includes/functions.php';

$pageSlug  = 'portfolio';
$projects  = db_fetch_all('SELECT * FROM projects WHERE is_active = 1 ORDER BY sort_order, id');
$process   = db_fetch_all('SELECT * FROM process_steps WHERE is_active = 1 ORDER BY sort_order, id');
require __DIR__ . '/includes/site-header.php';
?>
  <main id="main">
    <section class="page-header">
      <div class="container">
        <h1 data-i18n="portfolio.title">أعمالنا</h1>
        <p data-i18n="portfolio.desc">استعرض أحدث مشاريعنا ونماذج من الأعمال التي أنجزناها لعملائنا.</p>
        <nav class="breadcrumb" aria-label="مسار التنقل">
          <a href="index.php" data-i18n="nav.home">الرئيسية</a>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          <span aria-current="page" data-i18n="portfolio.title">أعمالنا</span>
        </nav>
      </div>
    </section>

    <section class="section" aria-label="قائمة المشاريع">
      <div class="container">
        <div class="portfolio-grid">
          <?php $projectIndex = 0; foreach ($projects as $project): $isFirstProject = $projectIndex++ === 0; $demoUrl = !empty($project['show_demo']) ? safe_http_url((string) $project['link']) : ''; ?>
          <article class="portfolio-card reveal">
            <div class="pc-img">
              <a href="project.php?id=<?= (int) $project['id'] ?>" aria-label="<?= e($project['title_ar']) ?>"><img src="<?= e(asset((string) $project['image'])) ?>"<?= asset_srcset_attributes((string) $project['image'], '(max-width: 720px) calc(100vw - 32px), (max-width: 1100px) 50vw, 360px') ?> alt="<?= e($project['title_ar']) ?>" data-alt-ar="<?= e($project['title_ar']) ?>" data-alt-en="<?= e($project['title_en']) ?>" width="360" height="225" loading="<?= $isFirstProject ? 'eager' : 'lazy' ?>" fetchpriority="<?= $isFirstProject ? 'high' : 'auto' ?>" decoding="async"></a>
              <span class="pc-tag" data-ar="<?= e($project['tag_ar']) ?>" data-en="<?= e($project['tag_en']) ?>"><?= e($project['tag_ar']) ?></span>
            </div>
            <div class="pc-body">
              <h2><a class="project-title-link" href="project.php?id=<?= (int) $project['id'] ?>" data-ar="<?= e($project['title_ar']) ?>" data-en="<?= e($project['title_en']) ?>"><?= e($project['title_ar']) ?></a></h2>
              <p class="project-excerpt" data-ar="<?= e($project['desc_ar']) ?>" data-en="<?= e($project['desc_en']) ?>"><?= e($project['desc_ar']) ?></p>
              <div class="pc-stack"><?php foreach (array_map('trim', explode(',', (string) $project['stack'])) as $tech): if ($tech === '') continue; ?><span><?= e($tech) ?></span><?php endforeach; ?></div>
              <div class="pc-actions">
                <?php if ($demoUrl !== ''): ?><a class="btn btn-primary project-demo-link" href="<?= e($demoUrl) ?>" target="_blank" rel="noopener noreferrer"><span data-i18n="work.demo">تجربة المشروع</span> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a><?php endif; ?>
                <a class="btn btn-outline project-details-link" href="project.php?id=<?= (int) $project['id'] ?>"><span data-i18n="work.details">عرض التفاصيل</span> <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section section-alt" aria-labelledby="process-title">
      <div class="container">
        <div class="section-head reveal">
          <p class="section-tag"><i class="fa-solid fa-list-check" aria-hidden="true"></i> <span data-i18n="process.tag">منهجيتنا</span></p>
          <h2 id="process-title" data-i18n="process.title">كيف نعمل معك خطوة بخطوة</h2>
          <p data-i18n="process.desc">عملية واضحة ومنظمة تضمن تحقيق أفضل النتائج لمشروعك.</p>
        </div>
        <div class="process">
          <?php foreach ($process as $step): ?>
          <div class="process-step reveal">
            <h3 data-ar="<?= e($step['title_ar']) ?>" data-en="<?= e($step['title_en']) ?>"><?= e($step['title_ar']) ?></h3>
            <p data-ar="<?= e($step['desc_ar']) ?>" data-en="<?= e($step['desc_en']) ?>"><?= e($step['desc_ar']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section" aria-labelledby="cta-title">
      <div class="container">
        <div class="cta-band reveal">
          <h2 id="cta-title" data-ar="<?= e(setting('cta_title_ar')) ?>" data-en="<?= e(setting('cta_title_en')) ?>"><?= e(setting('cta_title_ar')) ?></h2>
          <p data-ar="<?= e(setting('cta_desc_ar')) ?>" data-en="<?= e(setting('cta_desc_en')) ?>"><?= e(setting('cta_desc_ar')) ?></p>
          <a href="contact.php" class="btn btn-white btn-lg" data-i18n="cta.btn">تواصل معنا الآن</a>
        </div>
      </div>
    </section>
  </main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
