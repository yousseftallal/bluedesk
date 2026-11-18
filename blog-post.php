<?php
require_once __DIR__ . '/includes/functions.php';
$activeLang=($_GET['lang'] ?? '')==='en'?'en':'ar';
$now=app_now();
$slugParam=$_GET['slug'] ?? '';
$slug=is_string($slugParam)?trim($slugParam):'';
$post=$slug!==''?db_fetch("SELECT p.*,c.slug AS category_slug,c.title_ar AS category_ar,c.title_en AS category_en FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id AND c.is_active=1 WHERE p.slug=? AND p.status='published' AND p.published_at<=?",[$slug,$now]):null;
if (!$post) {
    http_response_code(404);
    $pageSlug='blog';
    $metaOverride=['title_ar'=>'المقال غير موجود | BlueDesk','title_en'=>'Article Not Found | BlueDesk','desc_ar'=>'المقال المطلوب غير موجود أو لم يعد متاحًا.','desc_en'=>'The requested article was not found or is no longer available.','canonical'=>url('blog'),'robots'=>'noindex, follow'];
    require __DIR__ . '/includes/site-header.php';
    ?><main id="main"><section class="page-header"><div class="container"><h1>المقال غير موجود</h1><p>قد يكون المقال حُذف أو لم يعد متاحًا.</p><a class="btn btn-primary" href="<?= e(url('blog')) ?>">العودة إلى المدونة</a></div></section></main><?php
    require __DIR__ . '/includes/site-footer.php'; exit;
}

$postPath='blog/'.rawurlencode($post['slug']);
$postUrl=blog_url($postPath,$activeLang);
$titleAr=$post['meta_title_ar'] ?: $post['title_ar'].' | '.$post['author_name'];
$titleEn=$post['meta_title_en'] ?: $post['title_en'].' | '.$post['author_name'];
$excerptAr=$post['excerpt_ar'] ?: mb_substr(trim(preg_replace('/\s+/u',' ',strip_tags($post['content_ar']))),0,220);
$excerptEn=$post['excerpt_en'] ?: mb_substr(trim(preg_replace('/\s+/u',' ',strip_tags($post['content_en']))),0,220);
$descAr=$post['meta_desc_ar'] ?: $excerptAr;
$descEn=$post['meta_desc_en'] ?: $excerptEn;
$canonical=safe_http_url((string)$post['canonical']) ?: $postUrl;
$alternateAr=blog_url($postPath,'ar');
$alternateEn=blog_url($postPath,'en');
$contentAr=sanitize_rich_html((string)$post['content_ar']);
$contentEn=sanitize_rich_html((string)$post['content_en']);
$cover=$post['cover_image'] ? asset($post['cover_image']) : asset(setting('hero_image','assets/logo.svg'));
$publishedIso=utc_datetime_iso((string)$post['published_at'],'Africa/Cairo');
$modifiedIso=utc_datetime_iso((string)($post['updated_at'] ?: $post['published_at']),'Africa/Cairo');
$wordCount=max(1,count(preg_split('/\s+/u',trim(strip_tags($activeLang==='en'?$contentEn:$contentAr)), -1, PREG_SPLIT_NO_EMPTY) ?: []));
$readingMinutes=max(1,(int)ceil($wordCount/220));
$tags=db_fetch_all('SELECT t.name_ar,t.name_en FROM blog_tags t JOIN blog_post_tags pt ON pt.tag_id=t.id WHERE pt.post_id=? ORDER BY t.id',[(int)$post['id']]);
$related=$post['category_id']?db_fetch_all("SELECT slug,title_ar,title_en,excerpt_ar,excerpt_en,content_ar,content_en,cover_image,image_alt_ar,image_alt_en FROM blog_posts WHERE category_id=? AND id<>? AND status='published' AND published_at<=? ORDER BY published_at DESC LIMIT 3",[(int)$post['category_id'],(int)$post['id'],$now]):[];
$metaOverride=['title_ar'=>$titleAr,'title_en'=>$titleEn,'desc_ar'=>mb_substr($descAr,0,320),'desc_en'=>mb_substr($descEn,0,320),'canonical'=>$canonical,'image'=>$cover,'image_alt'=>$activeLang==='en'?($post['image_alt_en'] ?: $post['title_en']):($post['image_alt_ar'] ?: $post['title_ar']),'og_type'=>'article','published_at'=>$publishedIso,'modified_at'=>$modifiedIso,'author'=>$post['author_name'],'language'=>$activeLang,'blog_language_route'=>true,'alternate_ar'=>$post['canonical']?null:$alternateAr,'alternate_en'=>$post['canonical']?null:$alternateEn];
$pageSlug='blog';
$articleSchema=['@context'=>'https://schema.org','@type'=>!empty($post['is_news'])?'NewsArticle':'BlogPosting','mainEntityOfPage'=>['@type'=>'WebPage','@id'=>$canonical],'headline'=>$activeLang==='en'?$post['title_en']:$post['title_ar'],'description'=>$activeLang==='en'?$descEn:$descAr,'image'=>[$cover],'datePublished'=>$publishedIso,'dateModified'=>$modifiedIso,'wordCount'=>$wordCount,'timeRequired'=>'PT'.$readingMinutes.'M','author'=>['@type'=>'Person','name'=>$post['author_name']],'publisher'=>['@type'=>'Organization','name'=>setting('site_name','BlueDesk'),'logo'=>['@type'=>'ImageObject','url'=>asset(setting('logo','assets/logo.svg'))]],'isPartOf'=>['@type'=>'Blog','name'=>setting('site_name','BlueDesk').' Blog','url'=>blog_url('blog',$activeLang)],'inLanguage'=>$activeLang,'url'=>$canonical];
if ($tags) $articleSchema['keywords']=implode(', ',array_map(static fn(array $tag): string => $activeLang==='en'?$tag['name_en']:$tag['name_ar'],$tags));
if ($post['category_ar']) $articleSchema['articleSection']=$activeLang==='en'?$post['category_en']:$post['category_ar'];
$breadcrumbItems=[['@type'=>'ListItem','position'=>1,'name'=>$activeLang==='en'?'Home':'الرئيسية','item'=>url('index.php')],['@type'=>'ListItem','position'=>2,'name'=>$activeLang==='en'?'Blog':'المدونة','item'=>blog_url('blog',$activeLang)]];
if($post['category_ar'])$breadcrumbItems[]=['@type'=>'ListItem','position'=>3,'name'=>$activeLang==='en'?$post['category_en']:$post['category_ar'],'item'=>blog_url('blog/category/'.rawurlencode($post['category_slug']),$activeLang)];
$breadcrumbItems[]=['@type'=>'ListItem','position'=>count($breadcrumbItems)+1,'name'=>$activeLang==='en'?$post['title_en']:$post['title_ar'],'item'=>$canonical];
$structuredData=[$articleSchema,['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$breadcrumbItems]];
require __DIR__ . '/includes/site-header.php';
?>
<main id="main" class="blog-page blog-article-page">
  <section class="blog-article-hero">
    <div class="container">
      <nav class="breadcrumb" aria-label="<?= $activeLang === 'en' ? 'Breadcrumb' : 'مسار التنقل' ?>">
        <a href="<?= e(url('index.php')) ?>" data-i18n="nav.home"><?= $activeLang === 'en' ? 'Home' : 'الرئيسية' ?></a>
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        <a href="<?= e(blog_url('blog', $activeLang)) ?>" data-i18n="blog.title"><?= $activeLang === 'en' ? 'Blog' : 'المدونة' ?></a>
        <?php if ($post['category_ar']): ?>
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        <a href="<?= e(blog_url('blog/category/' . rawurlencode($post['category_slug']), $activeLang)) ?>" data-ar="<?= e($post['category_ar']) ?>" data-en="<?= e($post['category_en']) ?>"><?= e($activeLang === 'en' ? $post['category_en'] : $post['category_ar']) ?></a>
        <?php endif; ?>
      </nav>

      <header class="article-heading">
        <span class="blog-eyebrow"><i class="fa-solid fa-pen-nib" aria-hidden="true"></i> BlueDesk Insights</span>
        <?php if ($post['category_ar']): ?>
        <a class="blog-category article-category" href="<?= e(blog_url('blog/category/' . rawurlencode($post['category_slug']), $activeLang)) ?>" data-ar="<?= e($post['category_ar']) ?>" data-en="<?= e($post['category_en']) ?>"><?= e($activeLang === 'en' ? $post['category_en'] : $post['category_ar']) ?></a>
        <?php endif; ?>
        <h1 data-ar="<?= e($post['title_ar']) ?>" data-en="<?= e($post['title_en']) ?>"><?= e($activeLang === 'en' ? $post['title_en'] : $post['title_ar']) ?></h1>
        <p class="article-excerpt" data-ar="<?= e($excerptAr) ?>" data-en="<?= e($excerptEn) ?>"><?= e($activeLang === 'en' ? $excerptEn : $excerptAr) ?></p>
        <div class="article-meta">
          <span><i class="fa-regular fa-user" aria-hidden="true"></i> <?= e($post['author_name']) ?></span>
          <i class="fa-regular fa-calendar" aria-hidden="true"></i> <?= localized_datetime_html((string)$post['published_at'],'date','Africa/Cairo') ?>
          <span><i class="fa-regular fa-clock" aria-hidden="true"></i> <span data-ar="<?= $readingMinutes ?> دقائق قراءة" data-en="<?= $readingMinutes ?> min read"><?= $readingMinutes ?> <?= $activeLang === 'en' ? 'min read' : 'دقائق قراءة' ?></span></span>
        </div>
      </header>
    </div>
  </section>

  <section class="section blog-article-section">
    <div class="container blog-article-layout">
      <article class="blog-article">
        <?php if ($post['cover_image']): ?>
        <figure class="article-cover">
          <img src="<?= e($cover) ?>"<?= asset_srcset_attributes((string) $post['cover_image'], '(max-width: 900px) calc(100vw - 32px), 900px') ?><?= asset_dimensions_attributes((string) $post['cover_image']) ?> alt="<?= e($activeLang === 'en' ? ($post['image_alt_en'] ?: $post['title_en']) : ($post['image_alt_ar'] ?: $post['title_ar'])) ?>" loading="eager" fetchpriority="high" decoding="async">
          <figcaption class="sr-only"><?= e($activeLang === 'en' ? ($post['image_alt_en'] ?: $post['title_en']) : ($post['image_alt_ar'] ?: $post['title_ar'])) ?></figcaption>
        </figure>
        <?php endif; ?>

        <div class="article-content page-content" data-ar-html="<?= e($contentAr) ?>" data-en-html="<?= e($contentEn) ?>"><?= $activeLang === 'en' ? $contentEn : $contentAr ?></div>

        <?php if ($tags): ?>
        <div class="article-tags">
          <strong data-i18n="blog.tags"><?= $activeLang === 'en' ? 'Tags:' : 'الوسوم:' ?></strong>
          <?php foreach ($tags as $tag): ?><span data-ar="<?= e($tag['name_ar']) ?>" data-en="<?= e($tag['name_en']) ?>"><?= e($activeLang === 'en' ? $tag['name_en'] : $tag['name_ar']) ?></span><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="article-share">
          <strong data-i18n="blog.share"><?= $activeLang === 'en' ? 'Share this article' : 'شارك المقال' ?></strong>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($canonical) ?>" target="_blank" rel="noopener noreferrer" aria-label="مشاركة على Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
          <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= rawurlencode($canonical) ?>" target="_blank" rel="noopener noreferrer" aria-label="مشاركة على LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
          <a href="https://wa.me/?text=<?= rawurlencode(($activeLang === 'en' ? $post['title_en'] : $post['title_ar']) . ' ' . $canonical) ?>" target="_blank" rel="noopener noreferrer" aria-label="مشاركة على WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a>
        </div>
      </article>

      <aside class="article-aside">
        <div class="article-aside-card">
          <span class="aside-icon"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i></span>
          <span class="blog-eyebrow">BlueDesk Blog</span>
          <h2 data-i18n="blog.trusted"><?= $activeLang === 'en' ? 'Trusted technology insights' : 'محتوى تقني موثوق' ?></h2>
          <p data-i18n="blog.aside"><?= $activeLang === 'en' ? 'Practical ideas to help you grow your business and digital products.' : 'أفكار عملية تساعدك على تطوير أعمالك ومنتجاتك الرقمية.' ?></p>
          <a class="btn btn-primary" href="<?= e(url('contact.php')) ?>" data-i18n="blog.contact"><?= $activeLang === 'en' ? 'Talk to our team' : 'تواصل مع فريقنا' ?></a>
          <a class="aside-blog-link" href="<?= e(blog_url('blog', $activeLang)) ?>"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> <span data-i18n="blog.all"><?= $activeLang === 'en' ? 'All articles' : 'كل المقالات' ?></span></a>
        </div>
      </aside>
    </div>
  </section>

  <?php if ($related): ?>
  <section class="section related-posts">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow" data-i18n="blog.read-also"><?= $activeLang === 'en' ? 'Read also' : 'اقرأ أيضًا' ?></span>
        <h2 data-i18n="blog.related"><?= $activeLang === 'en' ? 'Related articles' : 'مقالات ذات صلة' ?></h2>
      </div>
      <div class="blog-grid">
        <?php foreach ($related as $item):
          $relatedAr = $item['excerpt_ar'] ?: mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($item['content_ar']))), 0, 160);
          $relatedEn = $item['excerpt_en'] ?: mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($item['content_en']))), 0, 160);
          $relatedUrl = blog_url('blog/' . rawurlencode($item['slug']), $activeLang);
        ?>
        <article class="blog-card">
          <a class="blog-card-image" href="<?= e($relatedUrl) ?>">
            <?php if ($item['cover_image']): ?>
            <img src="<?= e(asset($item['cover_image'])) ?>"<?= asset_srcset_attributes((string) $item['cover_image'], '(max-width: 720px) 100vw, 360px') ?><?= asset_dimensions_attributes((string) $item['cover_image']) ?> alt="<?= e($activeLang === 'en' ? ($item['image_alt_en'] ?: $item['title_en']) : ($item['image_alt_ar'] ?: $item['title_ar'])) ?>" loading="lazy" decoding="async">
            <?php else: ?>
            <span class="blog-image-placeholder"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></span>
            <?php endif; ?>
          </a>
          <div class="blog-card-body">
            <h3><a href="<?= e($relatedUrl) ?>" data-ar="<?= e($item['title_ar']) ?>" data-en="<?= e($item['title_en']) ?>"><?= e($activeLang === 'en' ? $item['title_en'] : $item['title_ar']) ?></a></h3>
            <p data-ar="<?= e($relatedAr) ?>" data-en="<?= e($relatedEn) ?>"><?= e($activeLang === 'en' ? $relatedEn : $relatedAr) ?></p>
            <a class="blog-read-more" href="<?= e($relatedUrl) ?>"><span data-i18n="blog.read"><?= $activeLang === 'en' ? 'Read article' : 'اقرأ المقال' ?></span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
</main><?php require __DIR__ . '/includes/site-footer.php'; ?>
