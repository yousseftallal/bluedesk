<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$editing = !empty($_GET['id']) ? db_fetch('SELECT * FROM blog_categories WHERE id = ?', [(int) $_GET['id']]) : null;
if (!empty($_GET['id']) && !$editing) {
    flash('error', 'التصنيف غير موجود.');
    redirect('blog-categories.php');
}

if (is_post()) {
    require_csrf();
    $action = input('action');
    $id = (int) input('id');
    if ($action === 'delete') {
        $category = db_fetch('SELECT id, title_ar FROM blog_categories WHERE id = ?', [$id]);
        if ($category) {
            db_query('DELETE FROM blog_categories WHERE id = ?', [$id]);
            log_activity('حذف تصنيف مدونة', $category['title_ar']);
            flash('success', 'تم حذف التصنيف. المقالات المرتبطة به ستبقى بدون تصنيف.');
        }
        redirect('blog-categories.php');
    }

    $titleAr = input('title_ar');
    $titleEn = input('title_en');
    $slug = strtolower(input('slug'));
    if ($slug === '') $slug = make_slug($titleEn);
    $slug = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]/', '-', $slug) ?? ''), '-');
    $descriptionAr = input('description_ar');
    $descriptionEn = input('description_en');
    $metaTitleAr = input('meta_title_ar');
    $metaTitleEn = input('meta_title_en');
    $metaDescAr = input('meta_desc_ar');
    $metaDescEn = input('meta_desc_en');
    $sortOrder = max(-10000, min(10000, (int) input('sort_order', '0')));
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($titleAr === '' || $titleEn === '' || $slug === '') {
        flash('error', 'اسم التصنيف بالعربي والإنجليزي والرابط مطلوبة.');
    } elseif (db_fetch('SELECT id FROM blog_categories WHERE slug = ? AND id <> ?', [$slug, $id])) {
        flash('error', 'رابط التصنيف مستخدم بالفعل.');
    } else {
        if ($id > 0) {
            db_query('UPDATE blog_categories SET slug=?, title_ar=?, title_en=?, description_ar=?, description_en=?, meta_title_ar=?, meta_title_en=?, meta_desc_ar=?, meta_desc_en=?, sort_order=?, is_active=?, updated_at=NOW() WHERE id=?', [$slug,$titleAr,$titleEn,$descriptionAr,$descriptionEn,$metaTitleAr,$metaTitleEn,$metaDescAr,$metaDescEn,$sortOrder,$active,$id]);
            log_activity('تعديل تصنيف مدونة', $titleAr);
        } else {
            db_query('INSERT INTO blog_categories (slug,title_ar,title_en,description_ar,description_en,meta_title_ar,meta_title_en,meta_desc_ar,meta_desc_en,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [$slug,$titleAr,$titleEn,$descriptionAr,$descriptionEn,$metaTitleAr,$metaTitleEn,$metaDescAr,$metaDescEn,$sortOrder,$active]);
            log_activity('إضافة تصنيف مدونة', $titleAr);
        }
        flash('success', 'تم حفظ التصنيف.');
        redirect('blog-categories.php');
    }
    redirect('blog-categories.php' . ($id ? '?id=' . $id : ''));
}

$categories = db_fetch_all('SELECT c.*, (SELECT COUNT(*) FROM blog_posts p WHERE p.category_id=c.id) AS post_count FROM blog_categories c ORDER BY c.sort_order, c.id');
$adminTitle = 'تصنيفات المدونة';
$adminSubtitle = 'تصنيفات عربية وإنجليزية مع إعدادات SEO مستقلة';
$adminActive = 'blog_categories';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h1>تصنيفات المدونة</h1><p class="sub">نظّم المقالات واجعل لكل تصنيف صفحة قابلة للفهرسة في محركات البحث.</p></div><div class="actions"><a class="btn btn-outline" href="blog.php"><i class="fa-solid fa-arrow-right"></i> المقالات</a></div></div>
<div class="grid-2" style="align-items:start">
  <div class="panel">
    <div class="panel-head"><h2><?= $editing ? 'تعديل تصنيف' : 'إضافة تصنيف' ?></h2></div>
    <form method="post" action="blog-categories.php<?= $editing ? '?id=' . (int)$editing['id'] : '' ?>">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
      <div class="panel-body"><div class="form-grid">
        <div class="form-group"><label for="title_ar">الاسم بالعربية *</label><input class="form-control" id="title_ar" name="title_ar" value="<?= e($editing['title_ar'] ?? '') ?>" required maxlength="190"></div>
        <div class="form-group"><label for="title_en">Name in English *</label><input class="form-control ltr" id="title_en" name="title_en" value="<?= e($editing['title_en'] ?? '') ?>" required maxlength="190"></div>
        <div class="form-group full"><label for="slug">الرابط المختصر</label><input class="form-control ltr" id="slug" name="slug" value="<?= e($editing['slug'] ?? '') ?>" placeholder="software-development" maxlength="120"><span class="hint">حروف إنجليزية صغيرة وأرقام وشرطات.</span></div>
        <div class="form-group"><label for="description_ar">وصف التصنيف عربي</label><textarea class="form-control" id="description_ar" name="description_ar" rows="3"><?= e($editing['description_ar'] ?? '') ?></textarea></div>
        <div class="form-group"><label for="description_en">Category description</label><textarea class="form-control ltr" id="description_en" name="description_en" rows="3"><?= e($editing['description_en'] ?? '') ?></textarea></div>
        <div class="form-group"><label for="meta_title_ar">عنوان SEO عربي</label><input class="form-control" id="meta_title_ar" name="meta_title_ar" value="<?= e($editing['meta_title_ar'] ?? '') ?>" maxlength="255"></div>
        <div class="form-group"><label for="meta_title_en">SEO title English</label><input class="form-control ltr" id="meta_title_en" name="meta_title_en" value="<?= e($editing['meta_title_en'] ?? '') ?>" maxlength="255"></div>
        <div class="form-group"><label for="meta_desc_ar">وصف SEO عربي</label><textarea class="form-control" id="meta_desc_ar" name="meta_desc_ar" rows="3" maxlength="320"><?= e($editing['meta_desc_ar'] ?? '') ?></textarea></div>
        <div class="form-group"><label for="meta_desc_en">SEO description English</label><textarea class="form-control ltr" id="meta_desc_en" name="meta_desc_en" rows="3" maxlength="320"><?= e($editing['meta_desc_en'] ?? '') ?></textarea></div>
        <div class="form-group"><label for="sort_order">الترتيب</label><input class="form-control ltr" type="number" id="sort_order" name="sort_order" value="<?= (int)($editing['sort_order'] ?? 0) ?>"></div>
        <div class="form-group"><label>الحالة</label><label class="switch"><input type="checkbox" name="is_active" <?= (!$editing || (int)$editing['is_active']) ? 'checked' : '' ?>> ظاهر بالموقع</label></div>
      </div></div>
      <div class="form-actions"><a class="btn btn-outline" href="blog-categories.php">إلغاء</a><div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ التصنيف</button></div></div>
    </form>
  </div>
  <div class="panel"><div class="panel-head"><h2>التصنيفات (<?= count($categories) ?>)</h2></div><div class="panel-body p0">
    <?php if (!$categories): ?><div class="empty"><i class="fa-solid fa-folder-open"></i><p>أضف أول تصنيف للمدونة.</p></div><?php else: ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>التصنيف</th><th>المقالات</th><th>الحالة</th><th></th></tr></thead><tbody>
      <?php foreach ($categories as $row): ?><tr><td><div class="cell-title"><?= e($row['title_ar']) ?></div><div class="cell-sub" dir="ltr"><?= e($row['title_en']) ?> · <?= e($row['slug']) ?></div></td><td><?= (int)$row['post_count'] ?></td><td><span class="badge <?= $row['is_active'] ? 'active' : 'inactive' ?>"><?= $row['is_active'] ? 'نشط' : 'مخفي' ?></span></td><td class="actions-cell"><a class="btn btn-outline btn-sm" href="blog-categories.php?id=<?= (int)$row['id'] ?>"><i class="fa-solid fa-pen"></i> تعديل</a><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف تصنيف «<?= e($row['title_ar']) ?>»؟" title="حذف"><i class="fa-solid fa-trash"></i></button></form></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </div></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
