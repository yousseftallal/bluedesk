<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=300');

$publishedAfter = date('Y-m-d H:i:s', time() - 2 * 24 * 60 * 60);
$publishedBefore = date('Y-m-d H:i:s');
$newsPosts = db_fetch_all(
    "SELECT slug, title_ar, title_en, published_at
     FROM blog_posts
     WHERE status = 'published'
       AND is_news = 1
       AND (canonical IS NULL OR canonical = '')
       AND published_at >= ?
       AND published_at <= ?
     ORDER BY published_at DESC, id DESC
     LIMIT 500",
    [$publishedAfter, $publishedBefore]
);
$publicationName = setting('site_name', 'BlueDesk');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
<?php foreach ($newsPosts as $post):
    $postPath = 'blog/' . rawurlencode((string) $post['slug']);
    $publicationDate = date(DATE_W3C, strtotime((string) $post['published_at']));
    foreach (['ar', 'en'] as $language):
        $title = trim((string) ($language === 'en' ? $post['title_en'] : $post['title_ar']));
        if ($title === '') continue;
        $articleUrl = blog_url($postPath, $language);
?>
  <url>
    <loc><?= e($articleUrl) ?></loc>
    <news:news>
      <news:publication>
        <news:name><?= e($publicationName) ?></news:name>
        <news:language><?= e($language) ?></news:language>
      </news:publication>
      <news:publication_date><?= e($publicationDate) ?></news:publication_date>
      <news:title><?= e($title) ?></news:title>
    </news:news>
  </url>
<?php endforeach; endforeach; ?>
</urlset>
