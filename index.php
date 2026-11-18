<?php
require_once __DIR__ . '/includes/functions.php';

$pageSlug    = 'home';
$now         = app_now();
$services    = db_fetch_all('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, id');
$stats       = db_fetch_all('SELECT * FROM stats WHERE is_active = 1 ORDER BY sort_order, id');
$whyFeatures = db_fetch_all("SELECT * FROM features WHERE section = 'why' AND is_active = 1 ORDER BY sort_order, id");
$projects    = db_fetch_all('SELECT * FROM projects WHERE is_active = 1 ORDER BY id DESC LIMIT 6');
$latestPosts = db_fetch_all("SELECT p.*, c.slug AS category_slug, c.title_ar AS category_ar, c.title_en AS category_en FROM blog_posts p LEFT JOIN blog_categories c ON c.id = p.category_id WHERE p.status = 'published' AND p.published_at <= ? ORDER BY p.published_at DESC, p.id DESC LIMIT 3", [$now]);
$process     = db_fetch_all('SELECT * FROM process_steps WHERE is_active = 1 ORDER BY sort_order, id');
$testimonials= db_fetch_all('SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id');
$projectCardExcerpt = static function ($value): string {
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '');
    if (mb_strlen($text, 'UTF-8') <= 220) {
        return $text;
    }
    $excerpt = mb_substr($text, 0, 220, 'UTF-8');
    $lastSpace = mb_strrpos($excerpt, ' ', 0, 'UTF-8');
    if ($lastSpace !== false && $lastSpace > 175) {
        $excerpt = mb_substr($excerpt, 0, $lastSpace, 'UTF-8');
    }
    return rtrim($excerpt) . '…';
};
require __DIR__ . '/includes/site-header.php';
?>
  <main id="main">
    <!-- HERO -->
    <section class="hero" aria-labelledby="hero-title">
      <div class="container hero-grid">
        <div>
          <p class="hero-badge"><span class="dot" aria-hidden="true"></span> <span data-ar="<?= e(setting('hero_badge_ar')) ?>" data-en="<?= e(setting('hero_badge_en')) ?>"><?= e(setting('hero_badge_ar')) ?></span></p>
          <h1 id="hero-title">
            <span data-ar="<?= e(setting('hero_title1_ar')) ?>" data-en="<?= e(setting('hero_title1_en')) ?>"><?= e(setting('hero_title1_ar')) ?></span>
            <span class="c-blue" data-ar="<?= e(setting('hero_title2_ar')) ?>" data-en="<?= e(setting('hero_title2_en')) ?>"><?= e(setting('hero_title2_ar')) ?></span>
            <span class="c-red" data-ar="<?= e(setting('hero_title3_ar')) ?>" data-en="<?= e(setting('hero_title3_en')) ?>"><?= e(setting('hero_title3_ar')) ?></span>
            <span class="c-green" data-ar="<?= e(setting('hero_title4_ar')) ?>" data-en="<?= e(setting('hero_title4_en')) ?>"><?= e(setting('hero_title4_ar')) ?></span>
          </h1>
          <p class="lead" data-ar="<?= e(setting('hero_lead_ar')) ?>" data-en="<?= e(setting('hero_lead_en')) ?>"><?= e(setting('hero_lead_ar')) ?></p>
          <div class="hero-cta">
            <a href="<?= e($quoteWhatsapp ?: url('contact.php')) ?>" class="btn btn-primary btn-lg" data-ar="<?= e(setting('hero_cta1_ar')) ?>" data-en="<?= e(setting('hero_cta1_en')) ?>"><?= e(setting('hero_cta1_ar')) ?></a>
            <a href="portfolio.php" class="btn btn-outline btn-lg" data-ar="<?= e(setting('hero_cta2_ar')) ?>" data-en="<?= e(setting('hero_cta2_en')) ?>"><?= e(setting('hero_cta2_ar')) ?></a>
          </div>
          <div class="hero-stats" role="list">
            <?php foreach ($stats as $stat): ?>
            <div class="stat" role="listitem">
              <b data-count="<?= (int) $stat['value'] ?>" data-suffix="<?= e($stat['suffix']) ?>">0</b>
              <span data-ar="<?= e($stat['label_ar']) ?>" data-en="<?= e($stat['label_en']) ?>"><?= e($stat['label_ar']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="hero-visual">
          <img class="shot" src="<?= e(asset(setting('hero_image'))) ?>"<?= asset_srcset_attributes(setting('hero_image'), '(max-width: 1024px) calc(100vw - 32px), 540px') ?> alt="<?= e(setting('site_name')) ?>" width="540" height="418" fetchpriority="high" decoding="async">
          <div class="hero-float f1"><i class="fa-solid fa-code" aria-hidden="true"></i> <span data-i18n="hero.f1">تطوير الويب</span></div>
          <div class="hero-float f2"><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i> <span data-i18n="hero.f2">تطبيقات الجوال</span></div>
          <div class="hero-float f3"><i class="fa-solid fa-cloud" aria-hidden="true"></i> <span data-i18n="hero.f3">حلول سحابية</span></div>
        </div>
      </div>
    </section>

    <!-- PORTFOLIO -->
    <section class="section" aria-labelledby="work-title">
      <div class="container">
        <div class="section-head reveal">
          <p class="section-tag"><i class="fa-solid fa-briefcase" aria-hidden="true"></i> <span data-i18n="work.tag">أعمالنا</span></p>
          <h2 id="work-title" data-i18n="work.title">مشاريع نفخر بإنجازها</h2>
          <p data-i18n="work.desc">نماذج من المشاريع التي أنجزناها لعملائنا في مختلف القطاعات.</p>
        </div>
        <div class="portfolio-grid">
          <?php foreach ($projects as $project): $demoUrl = !empty($project['show_demo']) ? safe_http_url((string) $project['link']) : ''; $projectExcerptAr = $projectCardExcerpt($project['desc_ar']); $projectExcerptEn = $projectCardExcerpt($project['desc_en']); ?>
          <article class="portfolio-card reveal">
            <div class="pc-img">
              <a href="project.php?id=<?= (int) $project['id'] ?>" aria-label="<?= e($project['title_ar']) ?>"><img src="<?= e(asset((string) $project['image'])) ?>"<?= asset_srcset_attributes((string) $project['image'], '(max-width: 720px) calc(100vw - 32px), (max-width: 1100px) 50vw, 360px') ?> alt="<?= e($project['title_ar']) ?>" data-alt-ar="<?= e($project['title_ar']) ?>" data-alt-en="<?= e($project['title_en']) ?>" width="360" height="225" loading="lazy" decoding="async"></a>
              <span class="pc-tag" data-ar="<?= e($project['tag_ar']) ?>" data-en="<?= e($project['tag_en']) ?>"><?= e($project['tag_ar']) ?></span>
            </div>
            <div class="pc-body">
              <h3><a class="project-title-link" href="project.php?id=<?= (int) $project['id'] ?>" data-ar="<?= e($project['title_ar']) ?>" data-en="<?= e($project['title_en']) ?>"><?= e($project['title_ar']) ?></a></h3>
              <p class="project-excerpt" data-ar="<?= e($projectExcerptAr) ?>" data-en="<?= e($projectExcerptEn) ?>"><?= e($projectExcerptAr) ?></p>
              <div class="pc-stack"><?php foreach (array_map('trim', explode(',', (string) $project['stack'])) as $tech): if ($tech === '') continue; ?><span><?= e($tech) ?></span><?php endforeach; ?></div>
              <div class="pc-actions">
                <?php if ($demoUrl !== ''): ?><a class="btn btn-primary project-demo-link" href="<?= e($demoUrl) ?>" target="_blank" rel="noopener noreferrer"><span data-i18n="work.demo">تجربة المشروع</span> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a><?php endif; ?>
                <a class="btn btn-outline project-details-link" href="project.php?id=<?= (int) $project['id'] ?>"><span data-i18n="work.details">عرض التفاصيل</span> <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:2.6rem" class="reveal">
          <a href="portfolio.php" class="btn btn-outline btn-lg" data-i18n="work.all">عرض كل الأعمال</a>
        </div>
      </div>
    </section>

    <!-- SERVICES -->
    <section class="section" aria-labelledby="services-title">
      <div class="container">
        <div class="section-head reveal">
          <p class="section-tag"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> <span data-i18n="services.tag">خدماتنا</span></p>
          <h2 id="services-title" data-i18n="services.title">حلول برمجية متكاملة لكل احتياج</h2>
          <p data-i18n="services.desc">من الفكرة إلى الإطلاق، نقدم خدمات برمجية شاملة تغطي كل مراحل التحول الرقمي لشركتك.</p>
        </div>
        <div class="cards service-cards-grid">
          <?php foreach ($services as $service): ?>
          <article class="card c-<?= e($service['color']) ?> service-card reveal">
            <div class="card-icon<?= !empty($service['icon_image']) ? ' has-icon-image' : '' ?>" aria-hidden="true">
              <?php if (!empty($service['icon_image'])): ?>
                <img src="<?= e(asset($service['icon_image'])) ?>"<?= asset_srcset_attributes((string) $service['icon_image'], '78px') ?> alt="" width="58" height="58" loading="lazy" decoding="async">
              <?php else: ?>
                <i class="fa-solid <?= e($service['icon']) ?>"></i>
              <?php endif; ?>
            </div>
            <h3 data-ar="<?= e($service['title_ar']) ?>" data-en="<?= e($service['title_en']) ?>"><?= e($service['title_ar']) ?></h3>
            <p id="service-description-<?= (int) $service['id'] ?>" class="service-description" data-ar="<?= e($service['desc_ar']) ?>" data-en="<?= e($service['desc_en']) ?>"><?= e($service['desc_ar']) ?></p>
            <a class="card-link" href="services.php#<?= e(rawurlencode($service['slug'])) ?>">
              <span data-i18n="svc.more">التفاصيل</span> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- WHY US -->
    <section class="section section-alt" aria-labelledby="why-title">
      <div class="container features">
        <div class="reveal">
          <p class="section-tag"><i class="fa-solid fa-star" aria-hidden="true"></i> <span data-i18n="why.tag">لماذا بلوديسك</span></p>
          <h2 id="why-title" style="font-size:clamp(1.7rem,3vw,2.3rem);font-weight:800;line-height:1.35;margin:.9rem 0" data-ar="<?= e(setting('why_title_ar')) ?>" data-en="<?= e(setting('why_title_en')) ?>"><?= e(setting('why_title_ar')) ?></h2>
          <p style="color:var(--ink-soft);line-height:1.85;margin-bottom:2rem" data-ar="<?= e(setting('why_desc_ar')) ?>" data-en="<?= e(setting('why_desc_en')) ?>"><?= e(setting('why_desc_ar')) ?></p>
          <div class="feature-list">
            <?php foreach ($whyFeatures as $feature): ?>
            <div class="feature-item">
              <div class="fi-icon grad-<?= e($feature['color']) ?>"><i class="fa-solid <?= e($feature['icon']) ?>" aria-hidden="true"></i></div>
              <div>
                <h3 data-ar="<?= e($feature['title_ar']) ?>" data-en="<?= e($feature['title_en']) ?>"><?= e($feature['title_ar']) ?></h3>
                <p data-ar="<?= e($feature['desc_ar']) ?>" data-en="<?= e($feature['desc_en']) ?>"><?= e($feature['desc_ar']) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="features-img reveal">
          <img src="<?= e(asset(setting('why_image'))) ?>"<?= asset_srcset_attributes(setting('why_image'), '(max-width: 1024px) calc(100vw - 32px), 560px') ?> alt="<?= e(setting('site_name')) ?>" width="560" height="476" loading="lazy" decoding="async">
          <div class="features-badge">
            <span class="fb-num"><?= e(setting('why_badge_num', '98%')) ?></span>
            <span class="fb-txt" data-ar="<?= e(setting('why_badge_label_ar')) ?>" data-en="<?= e(setting('why_badge_label_en')) ?>"><?= e(setting('why_badge_label_ar')) ?></span>
          </div>
        </div>
      </div>
    </section>

    <!-- LATEST BLOG POSTS -->
    <?php if ($latestPosts): ?>
    <section class="section section-alt" aria-labelledby="home-blog-title">
      <div class="container">
        <div class="section-head reveal">
          <p class="section-tag"><i class="fa-solid fa-pen-nib" aria-hidden="true"></i> <span data-i18n="blog.title">المدونة</span></p>
          <h2 id="home-blog-title" data-ar="أحدث المقالات" data-en="Latest articles">أحدث المقالات</h2>
          <p data-ar="أفكار ونصائح عملية تساعدك على تطوير أعمالك ومنتجاتك الرقمية." data-en="Practical ideas and insights to help grow your business and digital products.">أفكار ونصائح عملية تساعدك على تطوير أعمالك ومنتجاتك الرقمية.</p>
        </div>
        <div class="blog-grid">
          <?php foreach ($latestPosts as $item):
            $excerptAr = $item['excerpt_ar'] ?: mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($item['content_ar']))), 0, 180);
            $excerptEn = $item['excerpt_en'] ?: mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($item['content_en']))), 0, 180);
            $postUrl = blog_url('blog/' . rawurlencode($item['slug']));
          ?>
          <article class="blog-card reveal">
            <a class="blog-card-image" href="<?= e($postUrl) ?>" aria-label="<?= e($item['title_ar']) ?>">
              <?php if ($item['cover_image']): ?>
              <img src="<?= e(asset($item['cover_image'])) ?>"<?= asset_srcset_attributes((string) $item['cover_image'], '(max-width: 720px) calc(100vw - 32px), 360px') ?><?= asset_dimensions_attributes((string) $item['cover_image']) ?> alt="<?= e($item['image_alt_ar'] ?: $item['title_ar']) ?>" loading="lazy" decoding="async">
              <?php else: ?>
              <span class="blog-image-placeholder"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></span>
              <?php endif; ?>
            </a>
            <div class="blog-card-body">
              <?php if ($item['category_ar']): ?><a class="blog-category" href="<?= e(blog_url('blog/category/' . rawurlencode($item['category_slug']))) ?>" data-ar="<?= e($item['category_ar']) ?>" data-en="<?= e($item['category_en']) ?>"><?= e($item['category_ar']) ?></a><?php endif; ?>
              <div class="blog-card-meta"><?= localized_datetime_html((string)$item['published_at'],'date') ?><span><?= e($item['author_name']) ?></span></div>
              <h3><a href="<?= e($postUrl) ?>" data-ar="<?= e($item['title_ar']) ?>" data-en="<?= e($item['title_en']) ?>"><?= e($item['title_ar']) ?></a></h3>
              <p data-ar="<?= e($excerptAr) ?>" data-en="<?= e($excerptEn) ?>"><?= e($excerptAr) ?></p>
              <a class="blog-read-more" href="<?= e($postUrl) ?>"><span data-i18n="blog.read">اقرأ المقال</span> <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:2.6rem" class="reveal">
          <a href="<?= e(blog_url('blog')) ?>" class="btn btn-outline btn-lg" data-i18n="blog.all">كل المقالات</a>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- PROCESS -->
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

    <!-- TESTIMONIALS -->
    <section class="section" aria-labelledby="test-title">
      <div class="container">
        <div class="section-head reveal">
          <p class="section-tag"><i class="fa-solid fa-comments" aria-hidden="true"></i> <span data-i18n="test.tag">آراء عملائنا</span></p>
          <h2 id="test-title" data-i18n="test.title">ماذا يقول عملاؤنا عنا</h2>
          <p data-i18n="test.desc">قصص نجاح حقيقية من عملاء وثقوا بنا وحققوا نتائج استثنائية.</p>
        </div>
        <div class="testimonials">
          <?php foreach ($testimonials as $t): ?>
          <?php $testimonialVideo = testimonial_video_asset($t['video_path'] ?? null); ?>
          <article class="testimonial reveal<?= $testimonialVideo !== '' ? ' has-video' : '' ?>">
            <?php if ($testimonialVideo !== ''): ?>
            <div class="testimonial-video">
              <div class="testimonial-video-label"><i class="fa-solid fa-circle-play" aria-hidden="true"></i> <span data-ar="شاهد رأي العميل بالفيديو" data-en="Watch the client video review">شاهد رأي العميل بالفيديو</span></div>
              <video controls playsinline preload="none" aria-label="<?= e($t['name_ar']) ?> — <?= e($t['name_en']) ?>">
                <source src="<?= e($testimonialVideo) ?>" type="<?= str_ends_with((string) $t['video_path'], '.webm') ? 'video/webm' : 'video/mp4' ?>">
                <span data-ar="متصفحك لا يدعم تشغيل الفيديو." data-en="Your browser does not support video playback.">متصفحك لا يدعم تشغيل الفيديو.</span>
              </video>
            </div>
            <?php endif; ?>
            <div class="stars" role="img" aria-label="<?= (int) $t['rating'] ?> / 5">
              <?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-solid fa-star" aria-hidden="true"></i><?php endfor; ?>
            </div>
            <blockquote data-ar="<?= e($t['quote_ar']) ?>" data-en="<?= e($t['quote_en']) ?>"><?= e($t['quote_ar']) ?></blockquote>
            <div class="t-person">
              <img src="<?= e(asset((string) $t['avatar'])) ?>" alt="<?= e($t['name_ar']) ?>" width="48" height="48" loading="lazy">
              <div>
                <b data-ar="<?= e($t['name_ar']) ?>" data-en="<?= e($t['name_en']) ?>"><?= e($t['name_ar']) ?></b>
                <span data-ar="<?= e($t['role_ar']) ?>" data-en="<?= e($t['role_en']) ?>"><?= e($t['role_ar']) ?></span>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- CTA -->
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
