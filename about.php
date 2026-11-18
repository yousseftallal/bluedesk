<?php
require_once __DIR__ . '/includes/functions.php';

$pageSlug = 'about';
$values   = db_fetch_all("SELECT * FROM features WHERE section = 'value' AND is_active = 1 ORDER BY sort_order, id");
$team     = db_fetch_all('SELECT * FROM team WHERE is_active = 1 ORDER BY sort_order, id');
require __DIR__ . '/includes/site-header.php';
?>
  <main id="main">
    <section class="page-header">
      <div class="container">
        <h1 data-i18n="about.title">من نحن</h1>
        <p data-i18n="about.desc">تعرّف على شركة بلوديسك وقصة نجاحنا وقيمنا التي نعمل بها كل يوم.</p>
        <nav class="breadcrumb" aria-label="مسار التنقل">
          <a href="index.php" data-i18n="nav.home">الرئيسية</a>
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          <span aria-current="page" data-i18n="about.title">من نحن</span>
        </nav>
      </div>
    </section>

    <section class="section" aria-labelledby="about-h2">
      <div class="container about-grid">
        <div class="reveal">
          <img src="<?= e(asset(setting('about_image'))) ?>"<?= asset_srcset_attributes(setting('about_image'), '(max-width: 1024px) calc(100vw - 32px), 560px') ?> alt="<?= e(setting('site_name')) ?>" width="560" height="420" loading="eager" fetchpriority="high" decoding="async">
        </div>
        <div class="reveal">
          <p class="section-tag"><i class="fa-solid fa-building" aria-hidden="true"></i> <?= e(setting('site_name')) ?></p>
          <h2 id="about-h2" data-ar="<?= e(setting('about_h2_ar')) ?>" data-en="<?= e(setting('about_h2_en')) ?>"><?= e(setting('about_h2_ar')) ?></h2>
          <p data-ar="<?= e(setting('about_p1_ar')) ?>" data-en="<?= e(setting('about_p1_en')) ?>"><?= e(setting('about_p1_ar')) ?></p>
          <p data-ar="<?= e(setting('about_p2_ar')) ?>" data-en="<?= e(setting('about_p2_en')) ?>"><?= e(setting('about_p2_ar')) ?></p>
          <ul class="check-list">
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <span data-ar="<?= e(setting('about_ck' . $i . '_ar')) ?>" data-en="<?= e(setting('about_ck' . $i . '_en')) ?>"><?= e(setting('about_ck' . $i . '_ar')) ?></span></li>
            <?php endfor; ?>
          </ul>
        </div>
      </div>
    </section>

    <section class="section section-alt" aria-labelledby="values-title">
      <div class="container">
        <div class="section-head reveal">
          <p class="section-tag"><i class="fa-solid fa-gem" aria-hidden="true"></i> <span data-i18n="values.tag">قيمنا</span></p>
          <h2 id="values-title" data-ar="<?= e(setting('values_title_ar')) ?>" data-en="<?= e(setting('values_title_en')) ?>"><?= e(setting('values_title_ar')) ?></h2>
        </div>
        <div class="values-grid">
          <?php foreach ($values as $value): ?>
          <div class="value-card reveal">
            <div class="vi grad-<?= e($value['color']) ?>"><i class="fa-solid <?= e($value['icon']) ?>" aria-hidden="true"></i></div>
            <h3 data-ar="<?= e($value['title_ar']) ?>" data-en="<?= e($value['title_en']) ?>"><?= e($value['title_ar']) ?></h3>
            <p data-ar="<?= e($value['desc_ar']) ?>" data-en="<?= e($value['desc_en']) ?>"><?= e($value['desc_ar']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section" aria-labelledby="team-title">
      <div class="container">
        <div class="section-head reveal">
          <p class="section-tag"><i class="fa-solid fa-users" aria-hidden="true"></i> <span data-i18n="team.title">فريق العمل</span></p>
          <h2 id="team-title" data-ar="<?= e(setting('team_title_ar')) ?>" data-en="<?= e(setting('team_title_en')) ?>"><?= e(setting('team_title_ar')) ?></h2>
        </div>
        <div class="team-grid">
          <?php foreach ($team as $member): ?>
          <article class="team-card reveal">
            <img src="<?= e(asset((string) $member['photo'])) ?>"<?= asset_srcset_attributes((string) $member['photo'], '260px') ?> alt="<?= e($member['name_ar']) ?>" width="260" height="260" loading="lazy" decoding="async">
            <div class="tc-body">
              <h3 data-ar="<?= e($member['name_ar']) ?>" data-en="<?= e($member['name_en']) ?>"><?= e($member['name_ar']) ?></h3>
              <span data-ar="<?= e($member['role_ar']) ?>" data-en="<?= e($member['role_en']) ?>"><?= e($member['role_ar']) ?></span>
            </div>
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
