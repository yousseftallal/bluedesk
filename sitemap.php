<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
$now = app_now();

$coreFiles = ['index.php' => 1.0, 'about.php' => 0.8, 'services.php' => 0.9, 'portfolio.php' => 0.8, 'contact.php' => 0.7, 'blog' => 0.8];
$pages = db_fetch_all("SELECT slug, content_ar, content_en, updated_at, created_at FROM pages WHERE status = 'published' AND is_core = 0 ORDER BY sort_order, id");
$projects = db_fetch_all('SELECT id, image FROM projects WHERE is_active = 1 ORDER BY sort_order, id');
$projectGallery = db_fetch_all('SELECT pi.project_id, pi.image_path FROM project_images pi INNER JOIN projects p ON p.id = pi.project_id WHERE p.is_active = 1 ORDER BY pi.project_id, pi.sort_order, pi.id');
$services = db_fetch_all('SELECT image, icon_image FROM services WHERE is_active = 1 ORDER BY sort_order, id');
$team = db_fetch_all('SELECT photo FROM team WHERE is_active = 1 ORDER BY sort_order, id');
$testimonials = db_fetch_all('SELECT avatar FROM testimonials WHERE is_active = 1 ORDER BY sort_order, id');
$categories = db_fetch_all("SELECT c.id, c.slug, c.updated_at FROM blog_categories c WHERE c.is_active=1 AND EXISTS (SELECT 1 FROM blog_posts p WHERE p.category_id=c.id AND p.status='published' AND p.published_at<=?) ORDER BY c.sort_order,c.id", [$now]);
$posts = db_fetch_all("SELECT id, category_id, slug, canonical, cover_image, content_ar, content_en, COALESCE(updated_at,published_at) AS last_modified FROM blog_posts WHERE status='published' AND published_at<=? ORDER BY published_at DESC", [$now]);

$siteParts = parse_url(url());
$siteOrigin = is_array($siteParts) && !empty($siteParts['host'])
    ? strtolower((string) ($siteParts['scheme'] ?? 'https')) . '://' . $siteParts['host'] . (isset($siteParts['port']) ? ':' . (int) $siteParts['port'] : '')
    : '';
$imageUrl = static function (string $path) use ($siteOrigin): string {
    $resolved = asset(trim($path));
    if ($resolved === '') {
        return '';
    }
    $resolvedPath = parse_url($resolved, PHP_URL_PATH) ?: '';
    if (!preg_match('/\.(?:avif|gif|jpe?g|png|svg|webp)$/i', $resolvedPath)) {
        return '';
    }
    if (str_starts_with($resolved, '/')) {
        return $siteOrigin !== '' ? $siteOrigin . $resolved : '';
    }
    return safe_http_url($resolved);
};
$collectImages = static function (array $paths = [], array $htmlBodies = []) use ($imageUrl): array {
    $images = [];
    foreach ($paths as $path) {
        if (is_string($path) && $path !== '') {
            $loc = $imageUrl($path);
            if ($loc !== '') $images[] = $loc;
        }
    }
    foreach ($htmlBodies as $html) {
        if (!is_string($html) || $html === '') continue;
        if (preg_match_all('/<img\b[^>]*\ssrc\s*=\s*(["\'])(.*?)\1/is', $html, $matches)) {
            foreach ($matches[2] as $src) {
                $loc = $imageUrl(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($loc !== '') $images[] = $loc;
            }
        }
    }
    return array_slice(array_values(array_unique($images)), 0, 1000);
};

$galleryByProject = [];
foreach ($projectGallery as $image) {
    $galleryByProject[(int) $image['project_id']][] = $image['image_path'];
}
$postImages = [];
$postImagesByCategory = [];
foreach ($posts as $post) {
    $images = $collectImages([$post['cover_image']], [$post['content_ar'], $post['content_en']]);
    $postImages[(int) $post['id']] = $images;
    if ($post['category_id'] !== null) {
        $postImagesByCategory[(int) $post['category_id']] = array_merge($postImagesByCategory[(int) $post['category_id']] ?? [], $images);
    }
}

$homeImagePaths = [setting('hero_image'), setting('why_image'), setting('logo'), setting('logo_white'), setting('favicon')];
$serviceImagePaths = [];
foreach ($services as $service) {
    $homeImagePaths[] = $service['icon_image'];
    $serviceImagePaths[] = $service['image'];
    $serviceImagePaths[] = $service['icon_image'];
}
foreach ($projects as $project) {
    $homeImagePaths[] = $project['image'];
}
foreach (array_slice($posts, 0, 12) as $post) {
    $homeImagePaths[] = $post['cover_image'];
}
foreach ($testimonials as $testimonial) {
    $homeImagePaths[] = $testimonial['avatar'];
}
$pageImages = [
    'index.php' => $collectImages($homeImagePaths),
    'about.php' => $collectImages(array_merge([setting('about_image')], array_column($team, 'photo'))),
    'services.php' => $collectImages($serviceImagePaths),
    'portfolio.php' => $collectImages(array_column($projects, 'image')),
    'contact.php' => [],
    'blog' => $collectImages(array_column(array_slice($posts, 0, 12), 'cover_image')),
];

$writeUrl = static function (string $loc, array $alternates, ?string $lastModified, string $changefreq, string $priority, array $images = []): void {
    echo "  <url>\n    <loc>" . e($loc) . "</loc>\n";
    foreach ($alternates as $language => $href) {
        echo '    <xhtml:link rel="alternate" hreflang="' . e($language) . '" href="' . e($href) . '" />' . "\n";
    }
    foreach (array_slice(array_values(array_unique($images)), 0, 1000) as $image) {
        echo '    <image:image><image:loc>' . e($image) . "</image:loc></image:image>\n";
    }
    if ($lastModified !== null && $lastModified !== '') {
        echo '    <lastmod>' . e($lastModified) . "</lastmod>\n";
    }
    echo '    <changefreq>' . e($changefreq) . "</changefreq>\n";
    echo '    <priority>' . e($priority) . "</priority>\n  </url>\n";
};

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($coreFiles as $file => $priority):
    $arUrl = $file === 'blog' ? blog_url('blog', 'ar') : url_with_language(url($file), 'ar');
    $enUrl = $file === 'blog' ? blog_url('blog', 'en') : url_with_language(url($file), 'en');
    $alternates = ['ar' => $arUrl, 'en' => $enUrl];
    foreach ($alternates as $localizedUrl) {
        // These core pages do not expose a trustworthy content-modified timestamp.
        $writeUrl($localizedUrl, $alternates, null, 'weekly', (string) $priority, $pageImages[$file] ?? []);
    }
endforeach; ?>
<?php foreach ($pages as $page):
    $pageUrl = url('page.php?slug=' . urlencode($page['slug']));
    $alternates = ['ar' => url_with_language($pageUrl, 'ar'), 'en' => url_with_language($pageUrl, 'en')];
    $images = $collectImages([], [$page['content_ar'], $page['content_en']]);
    $lastModified = date('Y-m-d', strtotime($page['updated_at'] ?: $page['created_at']));
    foreach ($alternates as $localizedUrl) {
        $writeUrl($localizedUrl, $alternates, $lastModified, 'monthly', '0.6', $images);
    }
endforeach; ?>
<?php foreach ($projects as $project):
    $projectId = (int) $project['id'];
    $projectUrl = url('project.php?id=' . $projectId);
    $alternates = ['ar' => url_with_language($projectUrl, 'ar'), 'en' => url_with_language($projectUrl, 'en')];
    $images = $collectImages(array_merge([(string) $project['image']], $galleryByProject[$projectId] ?? []));
    foreach ($alternates as $localizedUrl) {
        // The current project schema has no updated_at field; omit an inaccurate lastmod.
        $writeUrl($localizedUrl, $alternates, null, 'monthly', '0.6', $images);
    }
endforeach; ?>
<?php foreach ($categories as $category):
    $categoryPath = 'blog/category/' . rawurlencode($category['slug']);
    $alternates = ['ar' => blog_url($categoryPath, 'ar'), 'en' => blog_url($categoryPath, 'en')];
    $images = $collectImages($postImagesByCategory[(int) $category['id']] ?? []);
    $lastModified = $category['updated_at'] ? date('Y-m-d', strtotime($category['updated_at'])) : null;
    foreach ($alternates as $localizedUrl) {
        $writeUrl($localizedUrl, $alternates, $lastModified, 'weekly', '0.5', $images);
    }
endforeach; ?>
<?php foreach ($posts as $post):
    $canonical = safe_http_url((string) $post['canonical']);
    $images = $postImages[(int) $post['id']] ?? [];
    if ($canonical !== '') {
        $writeUrl($canonical, [], date('Y-m-d', strtotime($post['last_modified'])), 'monthly', '0.7', $images);
        continue;
    }
    $postPath = 'blog/' . rawurlencode($post['slug']);
    $alternates = ['ar' => blog_url($postPath, 'ar'), 'en' => blog_url($postPath, 'en')];
    $lastModified = date('Y-m-d', strtotime($post['last_modified']));
    foreach ($alternates as $localizedUrl) {
        $writeUrl($localizedUrl, $alternates, $lastModified, 'monthly', '0.7', $images);
    }
endforeach; ?>
</urlset>
