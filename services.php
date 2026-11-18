<?php
require_once __DIR__ . '/includes/functions.php';

$pageSlug = 'services';
$services = db_fetch_all('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id');
$whyFeatures = db_fetch_all("SELECT * FROM features WHERE section = 'why' AND is_active = 1 ORDER BY sort_order, id");
require __DIR__ . '/includes/site-header.php';
?>
  <main id="main">
    <section class="page-header">
      <div class="container">
        <h1 data-i18n="services.page.title">خدماتنا</h1>
        <p data-i18n="services.page.desc">نقدم مجموعة شاملة من الخدمات البرمجية التي تغطي كل احتياجات شركتك الرقمية.</p>
        <nav class="breadcrumb" aria-label="مسار التنقل">
          <a href="index.php" data-i18n="nav.home">الرئيسية</a>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          <span aria-current="page" data-i18n="services.page.title">خدماتنا</span>
        </nav>
      </div>
    </section>

    <section class="section" aria-label="تفاصيل الخدمات">
      <div class="container">
        <?php $rowIndex = 0; foreach ($services as $service): $isFirstService = $rowIndex === 0; $reverse = $rowIndex % 2 === 1; $rowIndex++; ?>
        <article id="<?= e($service['slug']) ?>" class="service-row<?= $reverse ? ' reverse' : '' ?> reveal">
          <div class="sr-media">
            <img src="<?= e(asset((string) $service['image'])) ?>"<?= asset_srcset_attributes((string) $service['image'], '(max-width: 720px) calc(100vw - 32px), 560px') ?> alt="<?= e($service['title_ar']) ?>" data-alt-ar="<?= e($service['title_ar']) ?>" data-alt-en="<?= e($service['title_en']) ?>" width="560" height="385" loading="<?= $isFirstService ? 'eager' : 'lazy' ?>" fetchpriority="<?= $isFirstService ? 'high' : 'auto' ?>" decoding="async">
          </div>
          <div>
            <span class="sr-tag grad-<?= e($service['color']) ?>">
              <?php if (!empty($service['icon_image'])): ?>
                <img class="service-tag-icon" src="<?= e(asset($service['icon_image'])) ?>"<?= asset_srcset_attributes((string) $service['icon_image'], '18px') ?> alt="" width="18" height="18" decoding="async">
              <?php else: ?>
                <i class="fa-solid <?= e($service['icon']) ?>" aria-hidden="true"></i>
              <?php endif; ?>
              <?= e($service['title_en']) ?>
            </span>
            <h2 data-ar="<?= e($service['title_ar']) ?>" data-en="<?= e($service['title_en']) ?>"><?= e($service['title_ar']) ?></h2>
            <p data-ar="<?= e($service['long_desc_ar'] ?: $service['desc_ar']) ?>" data-en="<?= e($service['long_desc_en'] ?: $service['desc_en']) ?>"><?= e($service['long_desc_ar'] ?: $service['desc_ar']) ?></p>
            <ul class="sr-features">
              <?php
                $featuresAr = split_lines($service['features_ar']);
                $featuresEn = split_lines($service['features_en']);
                $count = max(count($featuresAr), count($featuresEn));
                for ($i = 0; $i < $count; $i++):
                  $fAr = $featuresAr[$i] ?? ($featuresEn[$i] ?? '');
                  $fEn = $featuresEn[$i] ?? ($featuresAr[$i] ?? '');
              ?>
              <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <span data-ar="<?= e($fAr) ?>" data-en="<?= e($fEn) ?>"><?= e($fAr) ?></span></li>
              <?php endfor; ?>
            </ul>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="section section-alt" aria-labelledby="svc-why-title">
      <div class="container">
        <div class="section-head reveal">
          <p class="section-tag"><i class="fa-solid fa-star" aria-hidden="true"></i> <span data-i18n="why.tag">لماذا بلوديسك</span></p>
          <h2 id="svc-why-title" data-ar="<?= e(setting('why_title_ar')) ?>" data-en="<?= e(setting('why_title_en')) ?>"><?= e(setting('why_title_ar')) ?></h2>
        </div>
        <div class="cards">
          <?php foreach ($whyFeatures as $feature): ?>
          <article class="card c-<?= e($feature['color']) ?> reveal">
            <div class="card-icon"><i class="fa-solid <?= e($feature['icon']) ?>" aria-hidden="true"></i></div>
            <h3 data-ar="<?= e($feature['title_ar']) ?>" data-en="<?= e($feature['title_en']) ?>"><?= e($feature['title_ar']) ?></h3>
            <p data-ar="<?= e($feature['desc_ar']) ?>" data-en="<?= e($feature['desc_en']) ?>"><?= e($feature['desc_ar']) ?></p>
          </article>
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
