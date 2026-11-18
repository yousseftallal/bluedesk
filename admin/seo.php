<?php
/**
 * SEO settings (admin only).
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (is_post()) {
    require_csrf();
    foreach (['seo_title_ar', 'seo_title_en', 'seo_desc_ar', 'seo_desc_en'] as $key) {
        save_setting($key, input($key));
    }
    log_activity('تحديث SEO', 'تم تحديث بيانات SEO الافتراضية');
    flash('success', 'تم حفظ إعدادات SEO العامة.');
    redirect('seo.php');
}

$pages = db_fetch_all('SELECT * FROM pages ORDER BY is_core DESC, sort_order, id');

$adminTitle = 'تحسين محركات البحث';
$adminSubtitle = 'SEO العام وبيانات كل صفحة';
$adminActive = 'seo';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>تحسين محركات البحث (SEO)</h1><p class="sub">العناوين والأوصاف الافتراضية، مع إمكانية ضبط كل صفحة على حدة.</p></div>
  <div class="actions"><a class="btn btn-outline" href="<?php echo e(url('sitemap.php')); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-sitemap"></i> عرض Sitemap</a><a class="btn btn-outline" href="<?php echo e(url('feed.php')); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-rss"></i> RSS المدونة</a><a class="btn btn-primary" href="blog.php"><i class="fa-solid fa-newspaper"></i> إدارة SEO المقالات</a></div>
</div>

<form method="post" action="seo.php" data-loading>
  <?php echo csrf_field(); ?>
  <div class="panel">
    <div class="panel-head"><h2>البيانات الافتراضية للموقع</h2></div>
    <div class="panel-body">
      <p class="help-box"><b>متى تُستخدم؟</b> عندما لا تحتوي الصفحة على عنوان/وصف SEO مخصص، يتم استخدام هذه القيم.</p>
      <div class="form-grid">
        <div class="form-group"><label for="seo_title_ar">عنوان الموقع (عربي)</label><input class="form-control" id="seo_title_ar" name="seo_title_ar" value="<?php echo e(setting('seo_title_ar')); ?>" maxlength="255"></div>
        <div class="form-group"><label for="seo_title_en">عنوان الموقع (إنجليزي)</label><input class="form-control ltr" id="seo_title_en" name="seo_title_en" value="<?php echo e(setting('seo_title_en')); ?>" maxlength="255"></div>
        <div class="form-group"><label for="seo_desc_ar">وصف الموقع (عربي)</label><textarea class="form-control" id="seo_desc_ar" name="seo_desc_ar" rows="3" maxlength="320"><?php echo e(setting('seo_desc_ar')); ?></textarea></div>
        <div class="form-group"><label for="seo_desc_en">وصف الموقع (إنجليزي)</label><textarea class="form-control ltr" id="seo_desc_en" name="seo_desc_en" rows="3" maxlength="320"><?php echo e(setting('seo_desc_en')); ?></textarea></div>
      </div>
    </div>
    <div class="form-actions"><div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ إعدادات SEO</button></div></div>
  </div>
</form>

<div class="panel">
  <div class="panel-head"><h2>بيانات SEO لكل صفحة</h2></div>
  <div class="panel-body p0">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>الصفحة</th><th>عنوان SEO</th><th>وصف SEO</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($pages as $row): ?>
            <tr>
              <td>
                <div class="cell-title"><?php echo e($row['title_ar']); ?></div>
                <div class="cell-sub" dir="ltr" style="text-align:start"><?php echo e($row['slug']); ?></div>
              </td>
              <td class="muted" style="max-width:300px"><?php echo e($row['meta_title_ar'] ?: '— (يستخدم الافتراضي)'); ?></td>
              <td class="muted" style="max-width:300px"><?php echo e($row['meta_desc_ar'] ? mb_strimwidth($row['meta_desc_ar'], 0, 80, '…') : '— (يستخدم الافتراضي)'); ?></td>
              <td><a class="btn btn-outline btn-sm" href="page-edit.php?id=<?php echo (int) $row['id']; ?>"><i class="fa-solid fa-pen"></i> تعديل SEO</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h2>أدوات إضافية</h2></div>
  <div class="panel-body">
    <div class="form-grid">
      <div class="form-group">
        <label>خريطة الموقع (Sitemap)</label>
        <div class="copy-field">
          <input class="form-control" value="<?php echo e(url('sitemap.php')); ?>" readonly>
          <button class="btn btn-outline btn-icon" type="button" data-copy="<?php echo e(url('sitemap.php')); ?>"><i class="fa-solid fa-copy"></i></button>
        </div>
        <span class="hint">أضف هذا الرابط في Google Search Console.</span>
      </div>
      <div class="form-group">
        <label>ملف robots.txt</label>
        <div class="copy-field">
          <input class="form-control" value="<?php echo e(url('robots.txt')); ?>" readonly>
          <button class="btn btn-outline btn-icon" type="button" data-copy="<?php echo e(url('robots.txt')); ?>"><i class="fa-solid fa-copy"></i></button>
        </div>
        <span class="hint">يجب أن يشير إلى Sitemap الصحيح.</span>
      </div>
      <div class="form-group">
        <label>خلاصة المدونة RSS</label>
        <div class="copy-field">
          <input class="form-control" value="<?php echo e(url('feed.php')); ?>" readonly>
          <button class="btn btn-outline btn-icon" type="button" data-copy="<?php echo e(url('feed.php')); ?>"><i class="fa-solid fa-copy"></i></button>
        </div>
        <span class="hint">تُحدّث تلقائيًا بآخر 30 مقالًا منشورًا.</span>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
