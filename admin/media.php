<?php
/**
 * Media library.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

if (is_post()) {
    require_csrf();
    if (input('action') === 'upload') {
        if (!empty($_FILES['file']['name'])) {
            $url = upload_image($_FILES['file'], $err);
            if ($url !== null) {
                log_activity('رفع صورة', basename($url));
                flash('success', 'تم رفع الصورة بنجاح.');
            } else {
                flash('error', $err ?: 'تعذر رفع الصورة.');
            }
        } else {
            flash('error', 'اختر صورة للرفع.');
        }
    } elseif (input('action') === 'delete') {
        $id = (int) input('id');
        $row = db_fetch('SELECT * FROM media WHERE id = ?', [$id]);
        if ($row) {
            delete_upload(upload_url($row['filename']));
            db_query('DELETE FROM media WHERE id = ?', [$id]);
            log_activity('حذف وسيط', $row['original_name']);
            flash('success', 'تم حذف الصورة.');
        }
    }
    redirect('media.php' . (!empty($_POST['p']) ? '?p=' . (int) $_POST['p'] : ''));
}

$total = (int) (db_fetch('SELECT COUNT(*) AS c FROM media')['c'] ?? 0);
$pager = paginate($total, 24);
$media = db_fetch_all('SELECT * FROM media ORDER BY id DESC LIMIT ' . $pager['per'] . ' OFFSET ' . $pager['offset']);

$adminTitle = 'مكتبة الوسائط';
$adminSubtitle = 'كل الصور المرفوعة على الموقع';
$adminActive = 'media';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>مكتبة الوسائط</h1><p class="sub"><?php echo $total; ?> صورة — الصيغ المسموحة: JPG, PNG, WEBP, GIF (حتى 4MB). الصور الثابتة تتحول إلى WebP تلقائيًا مع حفظ الأصل؛ ملفات GIF المتحركة تحتفظ بحركتها.</p></div>
</div>

<div class="panel">
  <div class="panel-head"><h2>رفع صورة جديدة</h2></div>
  <form method="post" action="media.php" enctype="multipart/form-data" data-loading>
    <?php echo csrf_field(); ?><input type="hidden" name="action" value="upload">
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group">
          <label for="file">اختر الصورة</label>
          <input class="form-control" type="file" id="file" name="file" accept="image/png,image/jpeg,image/webp,image/gif" required>
          <span class="hint">سيتم توليد اسم فريد للصورة تلقائيًا.</span>
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end">
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-cloud-arrow-up"></i> رفع الصورة</button>
        </div>
      </div>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>الصور (<?php echo $total; ?>)</h2></div>
  <div class="panel-body">
    <?php if (!$media): ?>
      <div class="empty"><i class="fa-solid fa-images"></i><p>لا توجد صور بعد. ارفع أول صورة بالأعلى.</p></div>
    <?php else: ?>
      <div class="media-grid">
        <?php foreach ($media as $row): ?>
          <?php $src = asset(upload_url($row['filename'])); ?>
          <div class="media-item">
            <a href="<?php echo e($src); ?>" target="_blank" rel="noopener">
              <img src="<?php echo e($src); ?>" alt="<?php echo e($row['original_name']); ?>" loading="lazy">
            </a>
            <div class="mi-body">
              <div class="mi-name" title="<?php echo e($row['original_name']); ?>"><?php echo e($row['original_name']); ?></div>
              <div class="mi-meta"><?php echo e(number_format($row['size'] / 1024, 0)); ?> KB · <?php echo localized_datetime_html((string)$row['created_at'], 'date'); ?></div>
              <div class="mi-actions">
                <button class="btn btn-outline btn-sm btn-icon" type="button" data-copy="<?php echo e($src); ?>" title="نسخ الرابط"><i class="fa-solid fa-link"></i></button>
                <form method="post" action="media.php" class="inline-form">
                  <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>"><input type="hidden" name="p" value="<?php echo (int) $pager['page']; ?>">
                  <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف الصورة نهائيًا؟ قد تكون مستخدمة في الموقع." title="حذف"><i class="fa-solid fa-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php echo pagination_links($pager); ?>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
