<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$post = !empty($_GET['id']) ? db_fetch('SELECT * FROM blog_posts WHERE id=?', [(int)$_GET['id']]) : null;
if (!empty($_GET['id']) && !$post) { flash('error','المقال غير موجود.'); redirect('blog.php'); }
$restoredBlogForm = false;
$clientTimezoneValue = (string)(app_config('app.timezone') ?: 'Africa/Cairo');
$tagRows = $post ? db_fetch_all('SELECT t.name_ar,t.name_en FROM blog_tags t JOIN blog_post_tags pt ON pt.tag_id=t.id WHERE pt.post_id=? ORDER BY t.id',[(int)$post['id']]) : [];

if (is_post()) {
    require_csrf();
    $id = (int)input('id');
    $existing = $id > 0 ? db_fetch('SELECT * FROM blog_posts WHERE id=?',[$id]) : null;
    $slug = strtolower(input('slug'));
    if ($slug === '') $slug = make_slug(input('title_en'));
    $slug = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]/','-',$slug) ?? ''), '-');
    $titleAr = input('title_ar');
    $titleEn = input('title_en');
    $excerptAr = input('excerpt_ar');
    $excerptEn = input('excerpt_en');
    $contentArInput = $_POST['content_ar'] ?? '';
    $contentEnInput = $_POST['content_en'] ?? '';
    $contentAr = sanitize_rich_html(is_string($contentArInput) ? $contentArInput : '');
    $contentEn = sanitize_rich_html(is_string($contentEnInput) ? $contentEnInput : '');
    $categoryId = max(0,(int)input('category_id'));
    $image = input('cover_image');
    $newUpload = null;
    $status = input('status') === 'published' ? 'published' : 'draft';
    $isNews = !empty($_POST['is_news']) ? 1 : 0;
    $publishedInput = input('published_at');
    $clientTimezone = input('client_timezone', (string)(app_config('app.timezone') ?: 'Africa/Cairo'));
    $publishedAt = null;
    $dateError = false;
    try {
        $publishedInputTimezone = new DateTimeZone($clientTimezone);
    } catch (Throwable $ex) {
        $publishedInputTimezone = new DateTimeZone((string)(app_config('app.timezone') ?: 'Africa/Cairo'));
        $dateError = $publishedInput !== '';
    }
    if ($publishedInput !== '') {
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $publishedInput, $publishedInputTimezone);
        $dateErrors = DateTime::getLastErrors();
        if (!$date || (is_array($dateErrors) && ($dateErrors['warning_count'] || $dateErrors['error_count']))) $dateError = true;
        else $publishedAt = $date->setTimezone(new DateTimeZone('Africa/Cairo'))->format('Y-m-d H:i:s');
    }
    if ($status === 'published' && $publishedAt === null && !$dateError) $publishedAt = date('Y-m-d H:i:s');
    $canonical = input('canonical');
    $canonicalOk = $canonical === '' || safe_http_url($canonical) !== '';
    $author = mb_substr(input('author_name', setting('site_name','BlueDesk')),0,120);
    if ($author === '') $author = setting('site_name','BlueDesk');
    $altAr = mb_substr(input('image_alt_ar'),0,255);
    $altEn = mb_substr(input('image_alt_en'),0,255);
    $metaTitleAr = input('meta_title_ar');
    $metaTitleEn = input('meta_title_en');
    $metaDescAr = input('meta_desc_ar');
    $metaDescEn = input('meta_desc_en');
    $tagsAr = array_values(array_unique(array_filter(array_map('trim', explode(',', mb_substr(input('tags_ar'),0,2000))))));
    $tagsEn = array_values(array_unique(array_filter(array_map('trim', explode(',', mb_substr(input('tags_en'),0,2000))))));
    $tagPairs = [];
    for ($i=0; $i<min(15,max(count($tagsAr),count($tagsEn))); $i++) {
        $ar = mb_substr($tagsAr[$i] ?? ($tagsEn[$i] ?? ''),0,120);
        $en = mb_substr($tagsEn[$i] ?? ($tagsAr[$i] ?? ''),0,120);
        if ($ar !== '' || $en !== '') $tagPairs[] = [$ar,$en];
    }

    $error = '';
    if ($titleAr === '' || $titleEn === '' || trim(strip_tags($contentAr)) === '' || trim(strip_tags($contentEn)) === '') $error = 'العنوان والمحتوى مطلوبان باللغتين العربية والإنجليزية.';
    elseif ($slug === '' || strlen($slug) > 180) $error = 'الرابط المختصر غير صالح؛ استخدم حروفًا إنجليزية وأرقامًا وشرطات.';
    elseif ($dateError) $error = 'تاريخ النشر غير صالح.';
    elseif (!$canonicalOk) $error = 'الرابط الأساسي يجب أن يكون URL يبدأ بـ https:// أو http://.';
    elseif ($categoryId > 0 && !db_fetch('SELECT id FROM blog_categories WHERE id=?',[$categoryId])) $error = 'التصنيف المحدد غير موجود.';
    elseif (db_fetch('SELECT id FROM blog_posts WHERE slug=? AND id<>?',[$slug,$id])) $error = 'الرابط المختصر مستخدم في مقال آخر.';

    $coverFile = $_FILES['cover_file'] ?? null;
    if ($error === '' && $coverFile !== null && !is_array($coverFile)) $error = 'بيانات الصورة المرفوعة غير صالحة.';
    if ($error === '' && is_array($coverFile) && !empty($coverFile['name'])) {
        $newUpload = upload_image($coverFile,$uploadError);
        if ($newUpload === null) $error = $uploadError ?: 'تعذر رفع صورة المقال.';
        else $image = $newUpload;
    }
    $uploadBase = rtrim(upload_url(),'/') . '/';
    $isLocalUpload = $image !== '' && preg_match('#^' . preg_quote($uploadBase, '#') . '[a-z0-9][a-z0-9._-]{0,250}\.(jpg|jpeg|png|webp|gif)$#i', $image);
    if ($error === '' && $image !== '' && safe_http_url($image) === '' && !$isLocalUpload) $error = 'صورة المقال يجب أن تكون URL آمنًا أو ملفًا مرفوعًا داخل مكتبة الوسائط.';
    if ($error === '' && strlen($image) > 500) $error = 'رابط الصورة أطول من الحد المسموح.';
    if ($error === '' && $image !== '' && ($altAr === '' || $altEn === '')) $error = 'أضف وصفًا بديلًا للصورة بالعربية والإنجليزية لتحسين الوصول وSEO.';

    if ($error !== '') {
        if ($newUpload) delete_upload($newUpload);
        $_SESSION['blog_form'] = $_POST;
        flash('error',$error);
        redirect('blog-edit.php'.($id?'?id='.$id:''));
    }

    try {
        db()->beginTransaction();
        $uid = (int)($_SESSION['uid'] ?? 0);
        $values = [$categoryId ?: null,$slug,$titleAr,$titleEn,$excerptAr,$excerptEn,$contentAr,$contentEn,$image ?: null,$altAr,$altEn,$author,$status,$isNews,$publishedAt,$metaTitleAr,$metaTitleEn,$metaDescAr,$metaDescEn,$canonical ?: null,$uid];
        if ($existing) {
            db_query('UPDATE blog_posts SET category_id=?,slug=?,title_ar=?,title_en=?,excerpt_ar=?,excerpt_en=?,content_ar=?,content_en=?,cover_image=?,image_alt_ar=?,image_alt_en=?,author_name=?,status=?,is_news=?,published_at=?,meta_title_ar=?,meta_title_en=?,meta_desc_ar=?,meta_desc_en=?,canonical=?,updated_by=?,updated_at=NOW() WHERE id=?',array_merge($values,[$id]));
            $postId = $id;
        } else {
            db_query('INSERT INTO blog_posts (category_id,slug,title_ar,title_en,excerpt_ar,excerpt_en,content_ar,content_en,cover_image,image_alt_ar,image_alt_en,author_name,status,is_news,published_at,meta_title_ar,meta_title_en,meta_desc_ar,meta_desc_en,canonical,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',array_merge($values,[$uid]));
            $postId = (int)db()->lastInsertId();
        }
        db_query('DELETE FROM blog_post_tags WHERE post_id=?',[$postId]);
        foreach ($tagPairs as [$tagAr,$tagEn]) {
            $tagSlug = make_slug($tagEn);
            if (preg_match('/^page-[0-9]+$/',$tagSlug)) $tagSlug = 'tag-' . substr(hash('sha256',mb_strtolower($tagAr)),0,24);
            $tagSlug = substr($tagSlug,0,120);
            db_query('INSERT INTO blog_tags (slug,name_ar,name_en) VALUES (?,?,?) ON DUPLICATE KEY UPDATE name_ar=VALUES(name_ar),name_en=VALUES(name_en)',[$tagSlug,$tagAr,$tagEn]);
            $tag = db_fetch('SELECT id FROM blog_tags WHERE slug=?',[$tagSlug]);
            if ($tag) db_query('INSERT IGNORE INTO blog_post_tags (post_id,tag_id) VALUES (?,?)',[$postId,(int)$tag['id']]);
        }
        db()->commit();
        if ($existing && $existing['cover_image'] !== $image) delete_upload($existing['cover_image']);
        unset($_SESSION['blog_form']);
        log_activity($existing?'تعديل مقال':'إضافة مقال',$titleAr);
        flash('success',$status==='published'?'تم حفظ المقال ونشره أو جدولته.':'تم حفظ المقال كمسودة.');
        redirect('blog.php');
    } catch (Throwable $ex) {
        if (db()->inTransaction()) db()->rollBack();
        if ($newUpload) delete_upload($newUpload);
        $_SESSION['blog_form'] = $_POST;
        error_log('Blog post save failed: '.$ex->getMessage());
        flash('error','تعذر حفظ المقال. راجع الرابط وتأكد من عدم تكراره ثم حاول مرة أخرى.');
        redirect('blog-edit.php'.($id?'?id='.$id:''));
    }
}

if (!is_post() && isset($_SESSION['blog_form']) && is_array($_SESSION['blog_form'])) {
    $restored = $_SESSION['blog_form'];
    unset($_SESSION['blog_form']);
    if ((int)($restored['id'] ?? 0) === (int)($post['id'] ?? 0)) {
        $restoredBlogForm = true;
        $clientTimezoneValue = is_string($restored['client_timezone'] ?? null) ? $restored['client_timezone'] : (string)(app_config('app.timezone') ?: 'Africa/Cairo');
        foreach (['category_id','slug','title_ar','title_en','excerpt_ar','excerpt_en','content_ar','content_en','cover_image','image_alt_ar','image_alt_en','author_name','status','is_news','meta_title_ar','meta_title_en','meta_desc_ar','meta_desc_en','canonical'] as $field) {
            if (isset($restored[$field]) && is_string($restored[$field])) $post[$field] = $restored[$field];
        }
        $post['published_at'] = is_string($restored['published_at'] ?? null) ? $restored['published_at'] : ($post['published_at'] ?? null);
        $post['id'] = (int)($restored['id'] ?? 0);
        $tagTextAr = is_string($restored['tags_ar'] ?? null) ? $restored['tags_ar'] : '';
        $tagTextEn = is_string($restored['tags_en'] ?? null) ? $restored['tags_en'] : '';
        $publishedLocal = is_string($restored['published_at'] ?? null) ? $restored['published_at'] : '';
    }
}

$categories = db_fetch_all('SELECT id,title_ar,title_en FROM blog_categories ORDER BY sort_order,id');
$tagTextAr = $tagTextAr ?? implode(', ',array_column($tagRows,'name_ar'));
$tagTextEn = $tagTextEn ?? implode(', ',array_column($tagRows,'name_en'));
$publishedLocal = $publishedLocal ?? '';
$publishedUtcValue = !$restoredBlogForm && !empty($post['published_at']) ? utc_datetime_iso((string)$post['published_at'], 'Africa/Cairo') : '';
$blogEditorTools = [
    ['command' => 'undo', 'icon' => 'fa-rotate-left', 'ar' => 'تراجع', 'en' => 'Undo'],
    ['command' => 'redo', 'icon' => 'fa-rotate-right', 'ar' => 'إعادة', 'en' => 'Redo'],
    ['command' => 'bold', 'icon' => 'fa-bold', 'ar' => 'عريض', 'en' => 'Bold'],
    ['command' => 'italic', 'icon' => 'fa-italic', 'ar' => 'مائل', 'en' => 'Italic'],
    ['command' => 'underline', 'icon' => 'fa-underline', 'ar' => 'تحته خط', 'en' => 'Underline'],
    ['command' => 'strikeThrough', 'icon' => 'fa-strikethrough', 'ar' => 'يتوسطه خط', 'en' => 'Strike through'],
    ['command' => 'insertUnorderedList', 'icon' => 'fa-list-ul', 'ar' => 'قائمة نقطية', 'en' => 'Bulleted list'],
    ['command' => 'insertOrderedList', 'icon' => 'fa-list-ol', 'ar' => 'قائمة مرقمة', 'en' => 'Numbered list'],
    ['command' => 'justifyRight', 'icon' => 'fa-align-right', 'ar' => 'محاذاة لليمين', 'en' => 'Align right'],
    ['command' => 'justifyCenter', 'icon' => 'fa-align-center', 'ar' => 'توسيط', 'en' => 'Center'],
    ['command' => 'justifyLeft', 'icon' => 'fa-align-left', 'ar' => 'محاذاة لليسار', 'en' => 'Align left'],
    ['command' => 'indent', 'icon' => 'fa-indent', 'ar' => 'زيادة المسافة البادئة', 'en' => 'Indent'],
    ['command' => 'outdent', 'icon' => 'fa-outdent', 'ar' => 'تقليل المسافة البادئة', 'en' => 'Outdent'],
    ['command' => 'createLink', 'icon' => 'fa-link', 'ar' => 'إضافة رابط', 'en' => 'Insert link'],
    ['command' => 'unlink', 'icon' => 'fa-link-slash', 'ar' => 'إزالة الرابط', 'en' => 'Remove link'],
    ['command' => 'insertHorizontalRule', 'icon' => 'fa-minus', 'ar' => 'فاصل', 'en' => 'Divider'],
    ['command' => 'removeFormat', 'icon' => 'fa-eraser', 'ar' => 'مسح التنسيق', 'en' => 'Clear formatting'],
];
$adminTitle = !empty($post['id']) ? 'تعديل مقال' : 'مقال جديد';
$adminSubtitle = $post['title_ar'] ?? 'إنشاء محتوى للمدونة';
$adminActive = 'blog';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1><?= e($adminTitle) ?></h1><p class="sub">اكتب المقال بالعربية والإنجليزية، ثم راجع الرابط والصورة وبيانات الظهور في نتائج البحث.</p></div><div class="actions"><a class="btn btn-outline" href="blog.php"><i class="fa-solid fa-arrow-right"></i> رجوع للمقالات</a></div></div>
<form method="post" action="blog-edit.php" enctype="multipart/form-data" data-loading>
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($post['id'] ?? 0) ?>"><input type="hidden" name="client_timezone" value="<?= e($clientTimezoneValue) ?>">
  <div class="panel"><div class="panel-head"><h2>معلومات المقال</h2></div><div class="panel-body"><div class="form-grid">
    <div class="form-group"><label for="title_ar">العنوان بالعربية *</label><input class="form-control" id="title_ar" name="title_ar" required maxlength="255" value="<?= e($post['title_ar'] ?? '') ?>"></div>
    <div class="form-group"><label for="title_en">Title in English *</label><input class="form-control ltr" id="title_en" name="title_en" required maxlength="255" value="<?= e($post['title_en'] ?? '') ?>"></div>
    <div class="form-group"><label for="slug">الرابط المختصر *</label><input class="form-control ltr" id="slug" name="slug" maxlength="180" value="<?= e($post['slug'] ?? '') ?>" placeholder="how-to-choose-software"><span class="hint">ثابت وسهل القراءة؛ يُنشأ تلقائيًا من العنوان الإنجليزي عند تركه فارغًا.</span></div>
    <div class="form-group"><label for="category_id">التصنيف</label><select class="form-control" id="category_id" name="category_id"><option value="0">بدون تصنيف</option><?php foreach($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (int)($post['category_id'] ?? 0)===(int)$cat['id']?'selected':'' ?>><?= e($cat['title_ar']) ?> · <?= e($cat['title_en']) ?></option><?php endforeach; ?></select><?php if(!$categories): ?><span class="hint"><a href="blog-categories.php">أنشئ تصنيفًا أولًا</a></span><?php endif; ?></div>
    <div class="form-group"><label for="excerpt_ar">مقدمة قصيرة بالعربية</label><textarea class="form-control" id="excerpt_ar" name="excerpt_ar" rows="3" maxlength="1000"><?= e($post['excerpt_ar'] ?? '') ?></textarea></div>
    <div class="form-group"><label for="excerpt_en">Short excerpt in English</label><textarea class="form-control ltr" id="excerpt_en" name="excerpt_en" rows="3" maxlength="1000"><?= e($post['excerpt_en'] ?? '') ?></textarea></div>
    <div class="form-group full"><div class="form-grid">
    <?php foreach ([['id'=>'content_ar','label'=>'المحتوى بالعربية *','dir'=>'rtl','lang'=>'ar','placeholder'=>'اكتب أو الصق محتوى المقال هنا...'],['id'=>'content_en','label'=>'Article content in English *','dir'=>'ltr','lang'=>'en','placeholder'=>'Write or paste the article here...']] as $editorConfig): ?>
    <div class="form-group blog-editor-field">
      <label id="<?= e($editorConfig['id']) ?>_label" for="<?= e($editorConfig['id']) ?>"><?= e($editorConfig['label']) ?></label>
      <div class="rich-editor" data-rich-editor data-source-id="<?= e($editorConfig['id']) ?>" data-upload-url="<?= e(url('admin/blog-image-upload.php')) ?>" data-csrf="<?= e(csrf_token()) ?>">
        <div class="rich-editor-toolbar" role="toolbar" aria-label="<?= $editorConfig['lang']==='ar'?'أدوات تنسيق المقال':'Article formatting tools' ?>">
          <div class="rich-editor-group">
            <?php foreach ($blogEditorTools as $tool): ?><button type="button" class="rich-editor-button" data-editor-command="<?= e($tool['command']) ?>" title="<?= e($tool[$editorConfig['lang']]) ?>" aria-label="<?= e($tool[$editorConfig['lang']]) ?>"><i class="fa-solid <?= e($tool['icon']) ?>" aria-hidden="true"></i></button><?php endforeach; ?>
            <span class="rich-editor-separator" aria-hidden="true"></span>
            <label class="rich-editor-select-label"><span class="sr-only"><?= $editorConfig['lang']==='ar'?'نمط الفقرة':'Paragraph style' ?></span><select class="rich-editor-select" data-editor-block aria-label="<?= $editorConfig['lang']==='ar'?'نمط الفقرة':'Paragraph style' ?>"><option value="p"><?= $editorConfig['lang']==='ar'?'فقرة':'Paragraph' ?></option><option value="h2"><?= $editorConfig['lang']==='ar'?'عنوان رئيسي':'Heading 2' ?></option><option value="h3"><?= $editorConfig['lang']==='ar'?'عنوان فرعي':'Heading 3' ?></option><option value="h4"><?= $editorConfig['lang']==='ar'?'عنوان صغير':'Heading 4' ?></option><option value="blockquote"><?= $editorConfig['lang']==='ar'?'اقتباس':'Quote' ?></option><option value="pre"><?= $editorConfig['lang']==='ar'?'كتلة كود':'Code block' ?></option></select></label>
            <label class="rich-editor-select-label"><span class="sr-only"><?= $editorConfig['lang']==='ar'?'نوع الخط':'Font family' ?></span><select class="rich-editor-select rich-editor-font" data-editor-font aria-label="<?= $editorConfig['lang']==='ar'?'نوع الخط':'Font family' ?>"><option value="Arial">Arial</option><option value="Cairo">Cairo</option><option value="Georgia">Georgia</option><option value="Tahoma">Tahoma</option><option value="Times New Roman">Times New Roman</option><option value="Verdana">Verdana</option></select></label>
            <label class="rich-editor-select-label"><span class="sr-only"><?= $editorConfig['lang']==='ar'?'حجم الخط':'Font size' ?></span><select class="rich-editor-select rich-editor-size" data-editor-size aria-label="<?= $editorConfig['lang']==='ar'?'حجم الخط':'Font size' ?>"><option value="3"><?= $editorConfig['lang']==='ar'?'عادي':'Normal' ?></option><option value="2"><?= $editorConfig['lang']==='ar'?'صغير':'Small' ?></option><option value="4"><?= $editorConfig['lang']==='ar'?'متوسط':'Medium' ?></option><option value="5"><?= $editorConfig['lang']==='ar'?'كبير':'Large' ?></option><option value="6"><?= $editorConfig['lang']==='ar'?'كبير جدًا':'Extra large' ?></option></select></label>
            <label class="rich-editor-color" title="<?= $editorConfig['lang']==='ar'?'لون النص':'Text color' ?>"><i class="fa-solid fa-font" aria-hidden="true"></i><input type="color" value="#202124" data-editor-color aria-label="<?= $editorConfig['lang']==='ar'?'لون النص':'Text color' ?>"></label>
            <label class="rich-editor-color rich-editor-highlight" title="<?= $editorConfig['lang']==='ar'?'تمييز النص':'Highlight text' ?>"><i class="fa-solid fa-highlighter" aria-hidden="true"></i><input type="color" value="#fff2a8" data-editor-highlight aria-label="<?= $editorConfig['lang']==='ar'?'تمييز النص':'Highlight text' ?>"></label>
            <span class="rich-editor-separator" aria-hidden="true"></span>
            <button type="button" class="rich-editor-button" data-editor-image-url title="<?= $editorConfig['lang']==='ar'?'إضافة صورة من رابط':'Insert image from URL' ?>" aria-label="<?= $editorConfig['lang']==='ar'?'إضافة صورة من رابط':'Insert image from URL' ?>"><i class="fa-regular fa-image" aria-hidden="true"></i></button>
            <button type="button" class="rich-editor-button" data-editor-upload-image title="<?= $editorConfig['lang']==='ar'?'رفع صورة من جهازك':'Upload an image' ?>" aria-label="<?= $editorConfig['lang']==='ar'?'رفع صورة من جهازك':'Upload an image' ?>"><i class="fa-solid fa-upload" aria-hidden="true"></i></button>
            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-editor-file hidden>
            <button type="button" class="rich-editor-button" data-editor-table title="<?= $editorConfig['lang']==='ar'?'إدراج جدول':'Insert table' ?>" aria-label="<?= $editorConfig['lang']==='ar'?'إدراج جدول':'Insert table' ?>"><i class="fa-solid fa-table" aria-hidden="true"></i></button>
          </div>
        </div>
        <div class="rich-editor-surface" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="<?= e($editorConfig['id']) ?>_label" aria-required="true" dir="<?= e($editorConfig['dir']) ?>" lang="<?= e($editorConfig['lang']) ?>" spellcheck="true" data-editor-surface data-placeholder="<?= e($editorConfig['placeholder']) ?>" hidden></div>
        <div class="rich-editor-status"><span class="rich-editor-message" role="status" aria-live="polite" data-editor-message><?= $editorConfig['lang']==='ar'?'الصق النص المنسق مباشرةً؛ يتم الاحتفاظ بالعناوين والقوائم والجداول والتنسيق الآمن.':'Paste formatted text directly; headings, lists, tables and safe styling are preserved.' ?></span><span class="rich-editor-count" data-editor-count>0</span></div>
      </div>
      <textarea class="form-control blog-editor-source<?= $editorConfig['dir']==='ltr'?' ltr':'' ?>" id="<?= e($editorConfig['id']) ?>" name="<?= e($editorConfig['id']) ?>" rows="18" required data-editor-source placeholder="<?= e($editorConfig['placeholder']) ?>"><?= e($post[$editorConfig['id']] ?? '') ?></textarea>
    </div>
    <?php endforeach; ?>
    </div><span class="hint">تقدر تنسخ من Word أو Google Docs أو صفحة ويب وتلصق هنا. التنسيق الشائع والصور والجداول سيظهروا في المقال بعد الحفظ، مع إزالة الأكواد غير الآمنة.</span></div>
    <div class="form-group"><label for="tags_ar">وسوم عربية (افصل بينها بفاصلة)</label><input class="form-control" id="tags_ar" name="tags_ar" value="<?= e($tagTextAr) ?>" maxlength="2000"></div>
    <div class="form-group"><label for="tags_en">English tags (comma separated)</label><input class="form-control ltr" id="tags_en" name="tags_en" value="<?= e($tagTextEn) ?>" maxlength="2000"></div>
    <div class="form-group"><label for="author_name">اسم الكاتب</label><input class="form-control" id="author_name" name="author_name" value="<?= e($post['author_name'] ?? setting('site_name','BlueDesk')) ?>" maxlength="120"></div>
    <div class="form-group"><label for="status">الحالة</label><select class="form-control" id="status" name="status"><option value="draft" <?= ($post['status'] ?? 'draft')==='draft'?'selected':'' ?>>مسودة</option><option value="published" <?= ($post['status'] ?? '')==='published'?'selected':'' ?>>منشور / مجدول</option></select></div>
    <div class="form-group"><label for="published_at">موعد النشر حسب توقيت جهازك</label><input class="form-control ltr" type="datetime-local" id="published_at" name="published_at" value="<?= e($publishedLocal) ?>" data-local-datetime-input data-utc-value="<?= e($publishedUtcValue) ?>"><span class="hint">الموعد يُحفظ كوقت عالمي ثم يظهر لكل زائر حسب منطقته الزمنية.</span></div>
    <div class="form-group full"><label>الظهور في أخبار Google</label><label class="switch"><input type="checkbox" name="is_news" value="1" <?= !empty($post['is_news']) ? 'checked' : '' ?>> هذا خبر أصلي وحديث</label><span class="hint">اختره للأخبار الفعلية مثل إطلاق منتج أو إعلان شركة. الخريطة تشمل الخبر المنشور خلال أول 48 ساعة فقط؛ لا ترسل رابطها إلى Search Console قبل وجود خبر حديث منشور.</span></div>
  </div></div></div>
  <div class="panel"><div class="panel-head"><h2>الصورة البارزة</h2></div><div class="panel-body"><div class="form-grid">
    <div class="form-group"><label for="cover_image">رابط الصورة (اختياري)</label><input class="form-control ltr" id="cover_image" name="cover_image" value="<?= e($post['cover_image'] ?? '') ?>" placeholder="https://example.com/image.webp"></div>
    <div class="form-group"><label for="cover_file">أو ارفع صورة</label><input class="form-control" type="file" id="cover_file" name="cover_file" accept="image/jpeg,image/png,image/webp,image/gif"><span class="hint">JPG أو PNG أو WebP أو GIF، بحد الحجم المحدد في إعدادات الموقع.</span></div>
    <div class="form-group"><label for="image_alt_ar">النص البديل للصورة بالعربية</label><input class="form-control" id="image_alt_ar" name="image_alt_ar" value="<?= e($post['image_alt_ar'] ?? '') ?>" maxlength="255"></div>
    <div class="form-group"><label for="image_alt_en">Image alt text in English</label><input class="form-control ltr" id="image_alt_en" name="image_alt_en" value="<?= e($post['image_alt_en'] ?? '') ?>" maxlength="255"></div>
    <?php if(!empty($post['cover_image'])): ?><div class="form-group full"><img class="preview-img" src="<?= e(asset($post['cover_image'])) ?>" alt="معاينة الصورة الحالية"></div><?php endif; ?>
  </div></div></div>
  <div class="panel"><div class="panel-head"><h2>تحسين محركات البحث والمشاركة</h2></div><div class="panel-body"><div class="form-grid">
    <div class="form-group"><label for="meta_title_ar">عنوان SEO عربي</label><input class="form-control" id="meta_title_ar" name="meta_title_ar" maxlength="255" value="<?= e($post['meta_title_ar'] ?? '') ?>"><span class="hint">اتركه فارغًا لاستخدام عنوان المقال.</span></div>
    <div class="form-group"><label for="meta_title_en">SEO title English</label><input class="form-control ltr" id="meta_title_en" name="meta_title_en" maxlength="255" value="<?= e($post['meta_title_en'] ?? '') ?>"></div>
    <div class="form-group"><label for="meta_desc_ar">وصف نتائج البحث عربي</label><textarea class="form-control" id="meta_desc_ar" name="meta_desc_ar" maxlength="320" rows="3"><?= e($post['meta_desc_ar'] ?? '') ?></textarea><span class="hint">اكتب وصفًا واضحًا ومميزًا للمقال.</span></div>
    <div class="form-group"><label for="meta_desc_en">Search description English</label><textarea class="form-control ltr" id="meta_desc_en" name="meta_desc_en" maxlength="320" rows="3"><?= e($post['meta_desc_en'] ?? '') ?></textarea></div>
    <div class="form-group full"><label for="canonical">الرابط الأساسي Canonical (اختياري)</label><input class="form-control ltr" id="canonical" name="canonical" maxlength="500" value="<?= e($post['canonical'] ?? '') ?>" placeholder="https://example.com/blog/article-slug"><span class="hint">استخدمه فقط عند وجود نسخة أصلية للمقال في عنوان آخر.</span></div>
  </div></div><div class="form-actions"><a class="btn btn-outline" href="blog.php">إلغاء</a><div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ المقال</button></div></div></div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
