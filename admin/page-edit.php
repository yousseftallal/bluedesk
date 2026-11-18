<?php
/**
 * Page create/edit.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$page = null;
if (!empty($_GET['id'])) {
    $page = db_fetch('SELECT * FROM pages WHERE id = ?', [(int) $_GET['id']]);
} elseif (!empty($_GET['slug'])) {
    $page = db_fetch('SELECT * FROM pages WHERE slug = ?', [(string) $_GET['slug']]);
}
if (!empty($_GET['id']) && !$page) {
    flash('error', 'الصفحة غير موجودة.');
    redirect('pages.php');
}

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    $existing = $id > 0 ? db_fetch('SELECT * FROM pages WHERE id = ?', [$id]) : null;

    $slug = strtolower(input('slug'));
    if ($slug === '') {
        $slug = make_slug(input('title_en') ?: input('title_ar'));
    }
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = trim(preg_replace('/-+/', '-', $slug), '-');
    if ($existing && (int) $existing['is_core']) {
        $slug = $existing['slug'];
    }

    $data = [
        $slug,
        input('title_ar'),
        input('title_en'),
        (string) ($_POST['content_ar'] ?? ''),
        (string) ($_POST['content_en'] ?? ''),
        input('meta_title_ar'),
        input('meta_title_en'),
        input('meta_desc_ar'),
        input('meta_desc_en'),
        input('canonical'),
        input('status') === 'draft' ? 'draft' : 'published',
        (int) input('sort_order', '0'),
    ];

    if ($data[1] === '' || $data[2] === '') {
        flash('error', 'عنوان الصفحة مطلوب باللغتين.');
    } elseif ($slug === '') {
        flash('error', 'رابط الصفحة غير صالح.');
    } else {
        $dupe = db_fetch('SELECT id FROM pages WHERE slug = ? AND id != ?', [$slug, $id]);
        if ($dupe) {
            flash('error', 'هذا الرابط مستخدم بالفعل في صفحة أخرى.');
        } else {
            if ($existing) {
                db_query(
                    'UPDATE pages SET slug=?, title_ar=?, title_en=?, content_ar=?, content_en=?, meta_title_ar=?, meta_title_en=?, meta_desc_ar=?, meta_desc_en=?, canonical=?, status=?, sort_order=?, updated_at=NOW() WHERE id=?',
                    array_merge($data, [$id])
                );
                log_activity('تعديل صفحة', $data[1]);
                flash('success', 'تم تحديث الصفحة.');
                redirect('pages.php');
            } else {
                db_query(
                    'INSERT INTO pages (slug, title_ar, title_en, content_ar, content_en, meta_title_ar, meta_title_en, meta_desc_ar, meta_desc_en, canonical, status, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    $data
                );
                log_activity('إنشاء صفحة', $data[1]);
                flash('success', 'تم إنشاء الصفحة.');
                redirect('pages.php');
            }
        }
    }
    redirect('page-edit.php' . ($id ? '?id=' . $id : ''));
}

$isCore = $page && (int) $page['is_core'];
$title = $page['title_ar'] ?? 'صفحة جديدة';

$adminTitle = $page ? 'تعديل صفحة' : 'صفحة جديدة';
$adminSubtitle = $title;
$adminActive = 'pages';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1><?php echo e($title); ?></h1><p class="sub"><?php echo $page ? 'عدّل بيانات الصفحة ومحتواها' : 'أنشئ صفحة محتوى جديدة على الموقع'; ?></p></div>
  <div class="actions"><a class="btn btn-outline" href="pages.php"><i class="fa-solid fa-arrow-right"></i> رجوع للصفحات</a></div>
</div>

<form method="post" action="page-edit.php" data-loading>
  <?php echo csrf_field(); ?>
  <input type="hidden" name="id" value="<?php echo (int) ($page['id'] ?? 0); ?>">

  <div class="panel">
    <div class="panel-head"><h2>بيانات الصفحة</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group">
          <label for="title_ar">العنوان (عربي) <span class="req">*</span></label>
          <input class="form-control" id="title_ar" name="title_ar" value="<?php echo e($page['title_ar'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
          <label for="title_en">العنوان (إنجليزي) <span class="req">*</span></label>
          <input class="form-control ltr" id="title_en" name="title_en" value="<?php echo e($page['title_en'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
          <label for="slug">الرابط (Slug)</label>
          <input class="form-control ltr" id="slug" name="slug" value="<?php echo e($page['slug'] ?? ''); ?>" pattern="[a-z0-9-]+" <?php echo $isCore ? 'readonly' : ''; ?>>
          <span class="hint"><?php echo $isCore ? 'لا يمكن تغيير رابط الصفحات الأساسية.' : 'أحرف إنجليزية وأرقام وشرطات فقط. اتركه فارغًا للتوليد التلقائي.'; ?></span>
        </div>
        <div class="form-group">
          <label for="status">الحالة</label>
          <select class="form-control" id="status" name="status">
            <option value="published" <?php echo ($page['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>منشورة</option>
            <option value="draft" <?php echo ($page['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>مسودة</option>
          </select>
        </div>
        <div class="form-group">
          <label for="sort_order">الترتيب</label>
          <input class="form-control ltr" type="number" id="sort_order" name="sort_order" value="<?php echo (int) ($page['sort_order'] ?? 0); ?>">
        </div>
        <div class="form-group">
          <label for="canonical">Canonical URL (اختياري)</label>
          <input class="form-control ltr" id="canonical" name="canonical" value="<?php echo e($page['canonical'] ?? ''); ?>" placeholder="https://www.bluedeskgroup.net/...">
        </div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>المحتوى</h2><span class="muted">يدعم HTML</span></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group">
          <label for="content_ar">المحتوى (عربي)</label>
          <textarea class="form-control" id="content_ar" name="content_ar" rows="14" style="font-family:Consolas,monospace;font-size:.82rem"><?php echo e($page['content_ar'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
          <label for="content_en">المحتوى (إنجليزي)</label>
          <textarea class="form-control ltr" id="content_en" name="content_en" rows="14" style="font-family:Consolas,monospace;font-size:.82rem"><?php echo e($page['content_en'] ?? ''); ?></textarea>
        </div>
      </div>
      <p class="help-box" style="margin-top:.4rem"><b>مثال:</b> <code>&lt;h2&gt;عنوان&lt;/h2&gt;&lt;p&gt;فقرة&lt;/p&gt;</code> — الصفحات الأساسية (الرئيسية، من نحن...) لها تصميم مخصص؛ المحتوى هنا يظهر في الصفحات المخصصة فقط.</p>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>تحسين محركات البحث (SEO)</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="meta_title_ar">عنوان SEO (عربي)</label><input class="form-control" id="meta_title_ar" name="meta_title_ar" value="<?php echo e($page['meta_title_ar'] ?? ''); ?>" maxlength="255"></div>
        <div class="form-group"><label for="meta_title_en">عنوان SEO (إنجليزي)</label><input class="form-control ltr" id="meta_title_en" name="meta_title_en" value="<?php echo e($page['meta_title_en'] ?? ''); ?>" maxlength="255"></div>
        <div class="form-group"><label for="meta_desc_ar">وصف SEO (عربي)</label><textarea class="form-control" id="meta_desc_ar" name="meta_desc_ar" rows="3" maxlength="320"><?php echo e($page['meta_desc_ar'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="meta_desc_en">وصف SEO (إنجليزي)</label><textarea class="form-control ltr" id="meta_desc_en" name="meta_desc_en" rows="3" maxlength="320"><?php echo e($page['meta_desc_en'] ?? ''); ?></textarea></div>
      </div>
    </div>
    <div class="form-actions">
      <a class="btn btn-outline" href="pages.php">إلغاء</a>
      <div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ الصفحة</button></div>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
