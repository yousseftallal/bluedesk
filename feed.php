<?php
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/rss+xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');
$siteName=setting('site_name','BlueDesk');
$now=app_now();
$items=db_fetch_all("SELECT p.slug,p.title_ar,p.title_en,p.excerpt_ar,p.excerpt_en,p.content_ar,p.content_en,p.cover_image,p.author_name,p.published_at,p.updated_at,c.title_ar AS category_ar FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id WHERE p.status='published' AND p.published_at<=? ORDER BY p.published_at DESC LIMIT 30",[$now]);
$xml=static fn($value): string => htmlspecialchars((string)$value,ENT_XML1|ENT_QUOTES,'UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
  <title><?= $xml($siteName.' Blog') ?></title>
  <link><?= $xml(url('blog')) ?></link>
  <description><?= $xml(setting('seo_desc_ar','مقالات ونصائح تقنية من BlueDesk.')) ?></description>
  <language>ar</language>
  <lastBuildDate><?= $items ? date(DATE_RSS,strtotime($items[0]['updated_at'] ?: $items[0]['published_at'])) : date(DATE_RSS) ?></lastBuildDate>
  <atom:link href="<?= $xml(url('feed.php')) ?>" rel="self" type="application/rss+xml" />
  <?php foreach($items as $item): $link=url('blog/'.rawurlencode($item['slug'])); $description=$item['excerpt_ar'] ?: mb_substr(trim(preg_replace('/\s+/u',' ',strip_tags($item['content_ar']))),0,300); ?>
  <item>
    <title><?= $xml($item['title_ar']) ?></title>
    <link><?= $xml($link) ?></link>
    <guid isPermaLink="true"><?= $xml($link) ?></guid>
    <pubDate><?= date(DATE_RSS,strtotime($item['published_at'])) ?></pubDate>
    <author><?= $xml($item['author_name']) ?></author>
    <?php if($item['category_ar']): ?><category><?= $xml($item['category_ar']) ?></category><?php endif; ?>
    <description><?= $xml($description) ?></description>
    <content:encoded><?= $xml($item['content_ar']) ?></content:encoded>
  </item>
  <?php endforeach; ?>
</channel>
</rss>
