<?php
require_once __DIR__ . '/functions.php';

$pageSlug   = $pageSlug ?? 'home';
$meta       = array_merge(page_meta($pageSlug), is_array($metaOverride ?? null) ? $metaOverride : []);
$requestedLanguage = strtolower(trim((string) ($_GET['lang'] ?? '')));
$hasExplicitLanguage = in_array($requestedLanguage, ['ar', 'en'], true);
$documentLang = $hasExplicitLanguage
    ? $requestedLanguage
    : (($meta['language'] ?? 'ar') === 'en' ? 'en' : 'ar');
$criticalImage = '';
$criticalImageSizes = '';
if ($pageSlug === 'home') {
    $criticalImage = setting('hero_image');
    $criticalImageSizes = '(max-width: 1024px) calc(100vw - 32px), 540px';
} elseif ($pageSlug === 'about') {
    $criticalImage = setting('about_image');
    $criticalImageSizes = '(max-width: 1024px) calc(100vw - 32px), 560px';
} elseif ($pageSlug === 'services' && !empty($services[0]['image'])) {
    $criticalImage = (string) $services[0]['image'];
    $criticalImageSizes = '(max-width: 720px) calc(100vw - 32px), 560px';
} elseif ($pageSlug === 'portfolio' && !empty($project['image'])) {
    $criticalImage = (string) $project['image'];
    $criticalImageSizes = '(max-width: 900px) calc(100vw - 32px), 900px';
} elseif ($pageSlug === 'portfolio' && !empty($projects[0]['image'])) {
    $criticalImage = (string) $projects[0]['image'];
    $criticalImageSizes = '(max-width: 720px) calc(100vw - 32px), (max-width: 1100px) 50vw, 360px';
} elseif ($pageSlug === 'blog' && !empty($post['cover_image'])) {
    $criticalImage = (string) $post['cover_image'];
    $criticalImageSizes = '(max-width: 900px) calc(100vw - 32px), 900px';
} elseif ($pageSlug === 'blog' && !empty($posts[0]['cover_image'])) {
    $criticalImage = (string) $posts[0]['cover_image'];
    $criticalImageSizes = '(max-width: 720px) 100vw, 360px';
}
$criticalImageSrcset = $criticalImage !== '' ? asset_srcset($criticalImage) : '';
$criticalImage = $criticalImage !== '' ? asset($criticalImage) : '';
$headTitle = $meta[$documentLang === 'en' ? 'title_en' : 'title_ar'];
$headDesc = $meta[$documentLang === 'en' ? 'desc_en' : 'desc_ar'];
$siteName   = setting('site_name', 'BlueDesk');
$headerNav  = db_fetch_all("SELECT * FROM nav_items WHERE location = 'header' AND is_active = 1 ORDER BY sort_order, id");
$branches   = db_fetch_all("SELECT * FROM branches WHERE is_active = 1 ORDER BY is_main DESC, sort_order, id");
$currentFile = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$adsenseClientId = trim(setting('adsense_publisher_id', 'ca-pub-8622942134176055'));
$adsenseSurfaceEnabled = false;
if ($currentFile === 'index.php') {
    $adsenseSurfaceEnabled = setting('adsense_home_enabled', '1') === '1';
} elseif ($currentFile === 'blog.php') {
    $adsenseSurfaceEnabled = setting('adsense_blog_enabled', '1') === '1'
        && empty($search)
        && !empty($posts);
} elseif ($currentFile === 'blog-post.php') {
    $adsenseSurfaceEnabled = setting('adsense_article_enabled', '1') === '1'
        && !empty($post['id']);
}
$adsenseEnabled = setting('adsense_enabled', '1') === '1'
    && $adsenseSurfaceEnabled
    && preg_match('/^ca-pub-[0-9]{16,20}$/D', $adsenseClientId) === 1;
$configuredCanonical = safe_http_url((string) $meta['canonical']);
$canonical  = $configuredCanonical ?: url($currentFile);
if ($hasExplicitLanguage && $canonical !== '') {
    $canonical = url_with_language($canonical, $documentLang) ?: $canonical;
}
$alternateAr = safe_http_url((string) ($meta['alternate_ar'] ?? ''));
$alternateEn = safe_http_url((string) ($meta['alternate_en'] ?? ''));
if ($alternateAr === '' && $alternateEn === '' && $pageSlug !== 'blog' && $canonical !== '') {
    $alternateAr = url_with_language($canonical, 'ar');
    $alternateEn = url_with_language($canonical, 'en');
}
$styleVersion = (string) (filemtime(APP_ROOT . '/css/style.css') ?: 1);
$logo       = setting('logo', 'assets/logo.svg');
$favicon    = setting('favicon', 'assets/favicon.svg');
$colorRed   = safe_css_color(setting('color_red', '#e53935'), '#e53935');
$colorBlue  = safe_css_color(setting('color_blue', '#4285f4'), '#4285f4');
$colorYellow= safe_css_color(setting('color_yellow', '#fbbc04'), '#fbbc04');
$colorGreen = safe_css_color(setting('color_green', '#34a853'), '#34a853');
$phone      = setting('phone', '01505768013');
$phoneIntl  = safe_tel(setting('phone_intl', '+201505768013')) ?: safe_tel(setting('phone', '01505768013'));
$quoteMessage = $documentLang === 'en'
    ? 'Hello, I would like to request a quote for my project.'
    : 'مرحبًا، أريد الحصول على عرض سعر لمشروعي.';
$quoteWhatsapp = whatsapp_href($quoteMessage);

$org = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type'       => 'Organization',
            'name'        => $siteName,
            'alternateName'=> 'بلوديسك',
            'url'         => url(),
            'logo'        => asset($logo),
            'email'       => setting('email'),
            'telephone'   => $phoneIntl,
        ],
    ],
];
foreach ($branches as $b) {
    $org['@graph'][] = [
        '@type'     => 'LocalBusiness',
        'name'      => $siteName . ' - ' . $b['name_ar'],
        'image'     => asset($logo),
        'telephone' => $phoneIntl,
        'email'     => $b['email'] ?: setting('email'),
        'address'   => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $b['address_en'],
            'addressCountry'  => 'EG',
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="<?= e($documentLang) ?>" dir="<?= $documentLang === 'en' ? 'ltr' : 'rtl' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($headTitle) ?></title>
  <meta name="description" content="<?= e($headDesc) ?>">
  <meta name="author" content="<?= e($siteName) ?>">
  <meta name="robots" content="<?= e($meta['robots'] ?? 'index, follow') ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <?php if ($alternateAr !== ''): ?><link rel="alternate" hreflang="ar" href="<?= e($alternateAr) ?>"><?php endif; ?>
  <?php if ($alternateEn !== ''): ?><link rel="alternate" hreflang="en" href="<?= e($alternateEn) ?>"><?php endif; ?>
  <?php if ($alternateAr !== '' && $alternateEn !== ''): ?><link rel="alternate" hreflang="x-default" href="<?= e($alternateAr) ?>"><?php endif; ?>
  <link rel="alternate" type="application/rss+xml" title="<?= e($siteName) ?> Blog" href="<?= e(url('feed.php')) ?>">

  <meta property="og:type" content="<?= e($meta['og_type'] ?? 'website') ?>">
  <meta property="og:locale" content="<?= $documentLang === 'en' ? 'en_US' : 'ar_AR' ?>">
  <meta property="og:locale:alternate" content="<?= $documentLang === 'en' ? 'ar_AR' : 'en_US' ?>">
  <meta property="og:site_name" content="<?= e($siteName) ?>">
  <meta property="og:title" content="<?= e($headTitle) ?>">
  <meta property="og:description" content="<?= e($headDesc) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e(asset($meta['image'] ?? setting('hero_image', $logo))) ?>">
  <?php if (!empty($meta['image_alt'])): ?><meta property="og:image:alt" content="<?= e($meta['image_alt']) ?>"><meta name="twitter:image:alt" content="<?= e($meta['image_alt']) ?>"><?php endif; ?>
  <?php if (!empty($meta['published_at'])): ?><meta property="article:published_time" content="<?= e(utc_datetime_iso((string)$meta['published_at'])) ?>"><?php endif; ?>
  <?php if (!empty($meta['modified_at'])): ?><meta property="article:modified_time" content="<?= e(utc_datetime_iso((string)$meta['modified_at'])) ?>"><?php endif; ?>
  <?php if (!empty($meta['author'])): ?><meta property="article:author" content="<?= e($meta['author']) ?>"><?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($headTitle) ?>">
  <meta name="twitter:description" content="<?= e($headDesc) ?>">
  <meta name="twitter:image" content="<?= e(asset($meta['image'] ?? setting('hero_image', $logo))) ?>">
  <meta name="theme-color" content="<?= e($colorBlue) ?>">

  <link rel="icon" href="<?= e(asset($favicon)) ?>">
  <link rel="apple-touch-icon" href="<?= e(asset($favicon)) ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <?php if ($criticalImage !== ''): ?><link rel="preload" href="<?= e($criticalImage) ?>" as="image" fetchpriority="high"<?= $criticalImageSrcset !== '' ? ' imagesrcset="' . e($criticalImageSrcset) . '" imagesizes="' . e($criticalImageSizes) . '"' : '' ?>><?php endif; ?>
  <?php if ($adsenseEnabled): ?>
  <link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= e(rawurlencode($adsenseClientId)) ?>" crossorigin="anonymous"></script>
  <?php endif; ?>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=optional" rel="stylesheet" media="print" data-async-stylesheet>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" media="print" data-async-stylesheet>
  <noscript>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
  </noscript>
  <link rel="stylesheet" href="<?= e(asset('css/style.php') . '?v=' . rawurlencode($styleVersion)) ?>">
  <?php if ($pageSlug === 'contact'): ?>
  <link rel="stylesheet" href="<?= e(asset('assets/vendor/intl-tel-input/css/intlTelInput.min.css') . '?v=29.5.8') ?>">
  <?php endif; ?>
  <style>:root{--red:<?= e($colorRed) ?>;--blue:<?= e($colorBlue) ?>;--yellow:<?= e($colorYellow) ?>;--green:<?= e($colorGreen) ?>;}</style>

  <script type="application/ld+json"><?= json_encode($org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
  <?php foreach (($structuredData ?? []) as $jsonLd): if (is_array($jsonLd)): ?><script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script><?php endif; endforeach; ?>
</head>
<body data-page="<?= e($pageSlug) ?>" data-default-lang="<?= e($documentLang) ?>"<?= !empty($meta['blog_language_route']) ? ' data-blog-language-route="1"' : '' ?>
      data-title-ar="<?= e($meta['title_ar']) ?>" data-title-en="<?= e($meta['title_en']) ?>"
      data-desc-ar="<?= e($meta['desc_ar']) ?>" data-desc-en="<?= e($meta['desc_en']) ?>">
  <a href="#main" class="skip-link" data-i18n="skip.link">تخطي إلى المحتوى الرئيسي</a>

  <header class="navbar" role="banner">
    <div class="container nav-inner">
      <a href="<?= e(url('index.php')) ?>" class="brand" aria-label="<?= e($siteName) ?>">
        <img src="<?= e(asset($logo)) ?>"<?= asset_srcset_attributes($logo, '160px') ?> alt="<?= e($siteName) ?>" width="160" height="44" fetchpriority="low" decoding="async">
      </a>
      <nav class="nav-links" id="mainNav" role="navigation" aria-label="القائمة الرئيسية">
        <?php foreach ($headerNav as $item): ?>
        <a href="<?= e(site_href($item['url']) ?: '#') ?>"<?= nav_active($item['url']) ? ' class="active"' : '' ?><?= (int) $item['open_new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : '' ?>
           data-ar="<?= e($item['label_ar']) ?>" data-en="<?= e($item['label_en']) ?>"><?= e($item['label_ar']) ?></a>
        <?php endforeach; ?>
      </nav>
      <div class="nav-actions">
        <div class="lang-switch" role="group" aria-label="Language / اللغة">
          <button type="button" data-lang="ar" class="active" aria-pressed="true">ع</button>
          <button type="button" data-lang="en" aria-pressed="false">EN</button>
        </div>
        <a href="<?= e($quoteWhatsapp ?: url('contact.php')) ?>" class="btn btn-primary" data-i18n="nav.cta">احصل على عرض سعر</a>
        <button class="hamburger" aria-expanded="false" aria-controls="mainNav" aria-label="فتح القائمة">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>
