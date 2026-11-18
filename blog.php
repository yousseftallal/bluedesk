<?php
require_once __DIR__ . '/includes/functions.php';

$perPage = 9;
$activeLang = ($_GET['lang'] ?? '') === 'en' ? 'en' : 'ar';
$pageParam = $_GET['page'] ?? 1;
$currentPage = max(1,is_scalar($pageParam)?(int)$pageParam:1);
$categoryParam = $_GET['category'] ?? '';
$searchParam = $_GET['q'] ?? '';
$categorySlug = is_string($categoryParam) ? mb_substr(trim($categoryParam),0,120) : '';
$search = is_string($searchParam) ? mb_substr(trim($searchParam),0,100) : '';
$now = app_now();
$category = null;
if ($categorySlug !== '') {
    $category = db_fetch('SELECT * FROM blog_categories WHERE slug=? AND is_active=1',[$categorySlug]);
    if (!$category) {
        http_response_code(404);
        $pageSlug='home';
        $metaOverride=['title_ar'=>'التصنيف غير موجود | BlueDesk','title_en'=>'Category Not Found | BlueDesk','desc_ar'=>'التصنيف المطلوب غير موجود.','desc_en'=>'The requested category was not found.','robots'=>'noindex, follow'];
        require __DIR__ . '/includes/site-header.php';
        ?><main id="main"><section class="page-header"><div class="container"><h1>التصنيف غير موجود</h1><p>التصنيف المطلوب غير موجود أو لم يعد متاحًا.</p><a class="btn btn-primary" href="<?= e(url('blog')) ?>">العودة للمدونة</a></div></section></main><?php
        require __DIR__ . '/includes/site-footer.php'; exit;
    }
}

$where = ["p.status='published'",'p.published_at <= ?'];
$params=[$now];
if ($category) { $where[]='p.category_id=?'; $params[]=(int)$category['id']; }
if ($search !== '') { $where[]='(p.title_ar LIKE ? OR p.title_en LIKE ? OR p.excerpt_ar LIKE ? OR p.excerpt_en LIKE ?)'; $needle='%'.$search.'%'; array_push($params,$needle,$needle,$needle,$needle); }
$whereSql=implode(' AND ',$where);
$total=(int)(db_fetch('SELECT COUNT(*) AS c FROM blog_posts p WHERE '.$whereSql,$params)['c'] ?? 0);
$pages=max(1,(int)ceil($total/$perPage));
if ($currentPage > $pages) { http_response_code(404); $currentPage=$pages; }
$offset=($currentPage-1)*$perPage;
$posts=db_fetch_all('SELECT p.*,c.slug AS category_slug,c.title_ar AS category_ar,c.title_en AS category_en FROM blog_posts p LEFT JOIN blog_categories c ON c.id=p.category_id WHERE '.$whereSql.' ORDER BY p.published_at DESC,p.id DESC LIMIT '.$perPage.' OFFSET '.$offset,$params);
$categories=db_fetch_all("SELECT c.id,c.slug,c.title_ar,c.title_en,COUNT(p.id) AS post_count FROM blog_categories c LEFT JOIN blog_posts p ON p.category_id=c.id AND p.status='published' AND p.published_at<=? WHERE c.is_active=1 GROUP BY c.id ORDER BY c.sort_order,c.id",[$now]);
$siteName=setting('site_name','BlueDesk');
$blogTitle=$category ? $category['title_ar'] : 'المدونة';
$blogTitleEn=$category ? $category['title_en'] : 'Blog';
$titleAr=$category ? ($category['meta_title_ar'] ?: $blogTitle.' | مدونة '.$siteName) : 'المدونة | مقالات ونصائح تقنية من '.$siteName;
$titleEn=$category ? ($category['meta_title_en'] ?: $blogTitleEn.' | '.$siteName.' Blog') : 'Blog | Technology Insights from '.$siteName;
$descAr=$category ? ($category['meta_desc_ar'] ?: $category['description_ar']) : 'مقالات ونصائح عملية من فريق BlueDesk حول تطوير المواقع والتطبيقات والذكاء الاصطناعي والتحول الرقمي.';
$descEn=$category ? ($category['meta_desc_en'] ?: $category['description_en']) : 'Practical articles from BlueDesk on web and mobile development, AI and digital transformation.';
if ($search !== '') { $titleAr='نتائج البحث في المدونة | '.$siteName; $titleEn='Blog Search Results | '.$siteName; $descAr='نتائج البحث عن '.mb_substr($search,0,60).' في مدونة '.$siteName; $descEn='Search results for '.mb_substr($search,0,60).' on the '.$siteName.' blog.'; }
$canonical=$category ? blog_url('blog/category/'.rawurlencode($category['slug']),$activeLang) : blog_url('blog',$activeLang);
if ($currentPage>1) $canonical .= (str_contains($canonical,'?')?'&':'?').'page='.$currentPage;
$altBase=$category?'blog/category/'.rawurlencode($category['slug']):'blog';
$altAr=blog_url($altBase,'ar');$altEn=blog_url($altBase,'en');
if($currentPage>1){$altAr.='?page='.$currentPage;$altEn.='&page='.$currentPage;}
$metaOverride=['title_ar'=>$titleAr,'title_en'=>$titleEn,'desc_ar'=>mb_substr($descAr ?: 'مقالات ونصائح تقنية من BlueDesk.',0,320),'desc_en'=>mb_substr($descEn ?: 'Technology articles from BlueDesk.',0,320),'canonical'=>$canonical,'robots'=>$search!==''?'noindex, follow':'index, follow','image'=>setting('hero_image','assets/logo.svg'),'language'=>$activeLang,'blog_language_route'=>true,'alternate_ar'=>$search===''?$altAr:null,'alternate_en'=>$search===''?$altEn:null];
$pageSlug='blog';
$archiveBreadcrumbs=[['@type'=>'ListItem','position'=>1,'name'=>$activeLang==='en'?'Home':'الرئيسية','item'=>url('index.php')],['@type'=>'ListItem','position'=>2,'name'=>$activeLang==='en'?'Blog':'المدونة','item'=>blog_url('blog',$activeLang)]];
if($category)$archiveBreadcrumbs[]=['@type'=>'ListItem','position'=>3,'name'=>$activeLang==='en'?$category['title_en']:$category['title_ar'],'item'=>blog_url('blog/category/'.rawurlencode($category['slug']),$activeLang)];
$structuredData=[['@context'=>'https://schema.org','@type'=>'CollectionPage','name'=>$activeLang==='en'?$titleEn:$titleAr,'description'=>$activeLang==='en'?$metaOverride['desc_en']:$metaOverride['desc_ar'],'url'=>$canonical,'inLanguage'=>$activeLang,'isPartOf'=>['@type'=>'Blog','name'=>$siteName.' Blog','url'=>blog_url('blog',$activeLang)]],['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$archiveBreadcrumbs]];
require __DIR__ . '/includes/site-header.php';
?>
<main id="main" class="blog-page">
  <section class="page-header blog-header"><div class="container"><span class="blog-eyebrow"><i class="fa-solid fa-pen-nib"></i> BlueDesk Insights</span><h1 data-ar="<?= e($category ? $category['title_ar'] : 'مدونة BlueDesk') ?>" data-en="<?= e($category ? $category['title_en'] : 'BlueDesk Blog') ?>"><?= e($activeLang==='en' ? ($category ? $category['title_en'] : 'BlueDesk Blog') : ($category ? $category['title_ar'] : 'مدونة BlueDesk')) ?></h1><p data-ar="<?= e($category ? ($category['description_ar'] ?: 'مقالات وأفكار تساعدك على اتخاذ قرارات رقمية أفضل.') : 'مقالات وأفكار تساعدك على اتخاذ قرارات رقمية أفضل.') ?>" data-en="<?= e($category ? ($category['description_en'] ?: 'Articles and ideas for better digital decisions.') : 'Articles and ideas for better digital decisions.') ?>"><?= e($activeLang==='en' ? ($category ? ($category['description_en'] ?: 'Articles and ideas for better digital decisions.') : 'Articles and ideas for better digital decisions.') : ($category ? ($category['description_ar'] ?: 'مقالات وأفكار تساعدك على اتخاذ قرارات رقمية أفضل.') : 'مقالات وأفكار تساعدك على اتخاذ قرارات رقمية أفضل.')) ?></p><nav class="breadcrumb" aria-label="<?= $activeLang==='en'?'Breadcrumb':'مسار التنقل' ?>"><a href="<?= e(url('index.php')) ?>" data-i18n="nav.home"><?= $activeLang==='en'?'Home':'الرئيسية' ?></a><i class="fa-solid fa-chevron-left" aria-hidden="true"></i><span aria-current="page" data-i18n="blog.title"><?= $activeLang==='en'?'Blog':'المدونة' ?></span><?php if($category): ?><i class="fa-solid fa-chevron-left" aria-hidden="true"></i><span><?= e($activeLang==='en'?$category['title_en']:$category['title_ar']) ?></span><?php endif; ?></nav></div></section>
  <section class="section blog-listing"><div class="container">
    <div class="blog-toolbar"><div class="blog-categories"><a href="<?= e(blog_url('blog',$activeLang)) ?>" class="<?= !$category?'active':'' ?>" data-i18n="blog.all"><?= $activeLang==='en'?'All articles':'كل المقالات' ?></a><?php foreach($categories as $cat): ?><a href="<?= e(blog_url('blog/category/'.rawurlencode($cat['slug']),$activeLang)) ?>" class="<?= $category && (int)$category['id']===(int)$cat['id']?'active':'' ?>"><span data-ar="<?= e($cat['title_ar']) ?>" data-en="<?= e($cat['title_en']) ?>"><?= e($activeLang==='en'?$cat['title_en']:$cat['title_ar']) ?></span><small><?= (int)$cat['post_count'] ?></small></a><?php endforeach; ?></div><form class="blog-search" method="get" action="<?= e(url('blog')) ?>"><label class="sr-only" for="blogSearch" data-i18n="blog.search"><?= $activeLang==='en'?'Search articles':'ابحث في المقالات' ?></label><input id="blogSearch" type="search" name="q" value="<?= e($search) ?>" data-ph-ar="ابحث في المقالات" data-ph-en="Search articles" placeholder="<?= $activeLang==='en'?'Search articles':'ابحث في المقالات' ?>"><button type="submit" aria-label="<?= $activeLang==='en'?'Search articles':'بحث' ?>" data-i18n-attr="aria-label:blog.search"><i class="fa-solid fa-magnifying-glass"></i></button><?php if($activeLang==='en'): ?><input type="hidden" name="lang" value="en"><?php endif; ?><?php if($category): ?><input type="hidden" name="category" value="<?= e($category['slug']) ?>"><?php endif; ?></form></div>
    <?php if($search!==''): ?><p class="blog-result-label"><span data-i18n="blog.results-for"><?= $activeLang==='en'?'Search results for:':'نتائج البحث عن:' ?></span> <strong><?= e($search) ?></strong> (<?= $total ?>)</p><?php endif; ?>
    <?php if($posts): ?><div class="blog-grid">
      <?php $postIndex = 0; foreach($posts as $item): $isFirstPost = $postIndex++ === 0; $excerptAr=$item['excerpt_ar'] ?: mb_substr(trim(preg_replace('/\s+/u',' ',strip_tags($item['content_ar']))),0,180); $excerptEn=$item['excerpt_en'] ?: mb_substr(trim(preg_replace('/\s+/u',' ',strip_tags($item['content_en']))),0,180); $postTitle=$activeLang==='en'?$item['title_en']:$item['title_ar']; $postExcerpt=$activeLang==='en'?$excerptEn:$excerptAr; ?>
      <article class="blog-card"><a class="blog-card-image" href="<?= e(blog_url('blog/'.rawurlencode($item['slug']),$activeLang)) ?>" aria-label="<?= e($postTitle) ?>"><?php if($item['cover_image']): ?><img src="<?= e(asset($item['cover_image'])) ?>"<?= asset_srcset_attributes((string) $item['cover_image'], '(max-width: 720px) 100vw, 360px') ?><?= asset_dimensions_attributes((string) $item['cover_image']) ?> alt="<?= e($activeLang==='en'?($item['image_alt_en'] ?: $item['title_en']):($item['image_alt_ar'] ?: $item['title_ar'])) ?>" loading="<?= $isFirstPost ? 'eager' : 'lazy' ?>" fetchpriority="<?= $isFirstPost ? 'high' : 'auto' ?>" decoding="async"><?php else: ?><span class="blog-image-placeholder"><i class="fa-solid fa-newspaper"></i></span><?php endif; ?></a><div class="blog-card-body"><?php if($item['category_ar']): ?><a class="blog-category" href="<?= e(blog_url('blog/category/'.rawurlencode($item['category_slug']),$activeLang)) ?>" data-ar="<?= e($item['category_ar']) ?>" data-en="<?= e($item['category_en']) ?>"><?= e($activeLang==='en'?$item['category_en']:$item['category_ar']) ?></a><?php endif; ?><div class="blog-card-meta"><?= localized_datetime_html((string)$item['published_at'],'date') ?><span><?= e($item['author_name']) ?></span></div><h2><a href="<?= e(blog_url('blog/'.rawurlencode($item['slug']),$activeLang)) ?>" data-ar="<?= e($item['title_ar']) ?>" data-en="<?= e($item['title_en']) ?>"><?= e($postTitle) ?></a></h2><p data-ar="<?= e($excerptAr) ?>" data-en="<?= e($excerptEn) ?>"><?= e($postExcerpt) ?></p><a class="blog-read-more" href="<?= e(blog_url('blog/'.rawurlencode($item['slug']),$activeLang)) ?>"><span data-i18n="blog.read"><?= $activeLang==='en'?'Read article':'اقرأ المقال' ?></span><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a></div></article>
      <?php endforeach; ?>
    </div><?php if($pages>1): ?><nav class="blog-pagination" aria-label="<?= $activeLang==='en'?'Blog pages':'صفحات المدونة' ?>"><?php for($p=1;$p<=$pages;$p++): $extraParams=[];if($search!=='')$extraParams['q']=$search;if($p>1)$extraParams['page']=$p;$path=$category?'blog/category/'.rawurlencode($category['slug']):'blog';$href=blog_url($path,$activeLang);if($extraParams)$href.=(str_contains($href,'?')?'&':'?').http_build_query($extraParams); ?><a class="<?= $p===$currentPage?'active':'' ?>" href="<?= e($href) ?>"<?= $p===$currentPage?' aria-current="page"':'' ?>><?= $p ?></a><?php endfor; ?></nav><?php endif; ?>
    <?php else: ?><div class="blog-empty"><i class="fa-regular fa-folder-open"></i><h2 data-i18n="<?= $search!==''?'blog.no-results':'blog.no-posts' ?>"><?= $activeLang==='en'?($search!==''?'No results found':'No articles published yet'):($search!==''?'لا توجد نتائج':'لا توجد مقالات منشورة بعد') ?></h2><p data-i18n="<?= $search!==''?'blog.try-search':'blog.follow' ?>"><?= $activeLang==='en'?($search!==''?'Try different search terms.':'Check back soon for the latest BlueDesk articles.'):($search!==''?'جرّب كلمات بحث أخرى.':'تابعنا قريبًا للاطلاع على أحدث مقالات BlueDesk.') ?></p></div><?php endif; ?>
  </div></section>
</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
