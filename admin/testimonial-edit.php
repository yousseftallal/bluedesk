<?php
/**
 * Testimonial create/edit.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$item = !empty($_GET['id']) ? db_fetch('SELECT * FROM testimonials WHERE id = ?', [(int) $_GET['id']]) : null;
if (!empty($_GET['id']) && !$item) {
    flash('error', 'الرأي غير موجود.');
    redirect('testimonials.php');
}

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    $existing = $id > 0 ? db_fetch('SELECT * FROM testimonials WHERE id = ?', [$id]) : null;

    $data = [
        input('quote_ar'), input('quote_en'),
        input('name_ar'), input('name_en'),
        input('role_ar'), input('role_en'),
        '',
        max(1, min(5, (int) input('rating', '5'))),
        (int) input('sort_order', '0'),
        isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data[0] === '' || $data[1] === '' || $data[2] === '' || $data[3] === '') {
        flash('error', 'نص الرأي واسم العميل مطلوبان باللغتين.');
    } else {
        $avatar = input('avatar');
        $newAvatar = false;
        if (!empty($_FILES['avatar_file']['name'])) {
            $uploaded = upload_image($_FILES['avatar_file'], $err);
            if ($uploaded !== null) {
                $avatar = $uploaded;
                $newAvatar = true;
            } else {
                flash('error', $err);
                redirect('testimonial-edit.php' . ($id ? '?id=' . $id : ''));
            }
        }

        $videoPath = $existing['video_path'] ?? null;
        if (!empty($_FILES['video_file']['name'])) {
            $uploadedVideo = upload_testimonial_video($_FILES['video_file'], $videoError);
            if ($uploadedVideo !== null) {
                $videoPath = $uploadedVideo;
            } else {
                if ($newAvatar) delete_upload($avatar);
                flash('error', $videoError ?: 'تعذر رفع الفيديو.');
                redirect('testimonial-edit.php' . ($id ? '?id=' . $id : ''));
            }
        } elseif (isset($_POST['remove_video'])) {
            $videoPath = null;
        }
        $data[6] = $avatar;

        if ($existing) {
            db_query(
                'UPDATE testimonials SET quote_ar=?, quote_en=?, name_ar=?, name_en=?, role_ar=?, role_en=?, avatar=?, rating=?, sort_order=?, is_active=?, video_path=? WHERE id=?',
                array_merge($data, [$videoPath, $id])
            );
            if ($avatar !== $existing['avatar']) {
                delete_upload($existing['avatar']);
            }
            if ($videoPath !== ($existing['video_path'] ?? null)) {
                delete_upload($existing['video_path'] ?? null);
            }
            log_activity('تعديل رأي عميل', $data[2]);
            flash('success', 'تم تحديث الرأي.');
        } else {
            db_query(
                'INSERT INTO testimonials (quote_ar, quote_en, name_ar, name_en, role_ar, role_en, avatar, rating, sort_order, is_active, video_path) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                array_merge($data, [$videoPath])
            );
            log_activity('إضافة رأي عميل', $data[2]);
            flash('success', 'تمت إضافة الرأي.');
        }
        redirect('testimonials.php');
    }
    redirect('testimonial-edit.php' . ($id ? '?id=' . $id : ''));
}

$title = $item['name_ar'] ?? 'رأي جديد';
$adminTitle = $item ? 'تعديل رأي' : 'رأي جديد';
$adminSubtitle = $title;
$adminActive = 'testimonials';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1><?php echo e($title); ?></h1><p class="sub">شهادة عميل تظهر في قسم آراء العملاء.</p></div>
  <div class="actions"><a class="btn btn-outline" href="testimonials.php"><i class="fa-solid fa-arrow-right"></i> رجوع للآراء</a></div>
</div>

<form method="post" action="testimonial-edit.php" enctype="multipart/form-data" data-loading>
  <?php echo csrf_field(); ?>
  <input type="hidden" name="id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
  <div class="panel">
    <div class="panel-head"><h2>بيانات الرأي</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group full"><label for="quote_ar">نص الرأي (عربي) <span class="req">*</span></label><textarea class="form-control" id="quote_ar" name="quote_ar" rows="3" required><?php echo e($item['quote_ar'] ?? ''); ?></textarea></div>
        <div class="form-group full"><label for="quote_en">نص الرأي (إنجليزي) <span class="req">*</span></label><textarea class="form-control ltr" id="quote_en" name="quote_en" rows="3" required><?php echo e($item['quote_en'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="name_ar">اسم العميل (عربي) <span class="req">*</span></label><input class="form-control" id="name_ar" name="name_ar" value="<?php echo e($item['name_ar'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="name_en">اسم العميل (إنجليزي) <span class="req">*</span></label><input class="form-control ltr" id="name_en" name="name_en" value="<?php echo e($item['name_en'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="role_ar">المسمى الوظيفي (عربي)</label><input class="form-control" id="role_ar" name="role_ar" value="<?php echo e($item['role_ar'] ?? ''); ?>"></div>
        <div class="form-group"><label for="role_en">المسمى الوظيفي (إنجليزي)</label><input class="form-control ltr" id="role_en" name="role_en" value="<?php echo e($item['role_en'] ?? ''); ?>"></div>
        <div class="form-group">
          <label for="rating">التقييم</label>
          <select class="form-control" id="rating" name="rating">
            <?php for ($r = 5; $r >= 1; $r--): ?>
              <option value="<?php echo $r; ?>" <?php echo (int) ($item['rating'] ?? 5) === $r ? 'selected' : ''; ?>><?php echo str_repeat('★', $r) . str_repeat('☆', 5 - $r) . ' (' . $r . '/5)'; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group"><label for="sort_order">الترتيب</label><input class="form-control ltr" type="number" id="sort_order" name="sort_order" value="<?php echo (int) ($item['sort_order'] ?? 0); ?>"></div>
        <div class="form-group">
          <label for="avatar">صورة العميل</label>
          <input class="form-control ltr" id="avatar" name="avatar" value="<?php echo e($item['avatar'] ?? ''); ?>" placeholder="https://...">
          <input class="form-control" type="file" name="avatar_file" style="margin-top:.45rem" accept="image/png,image/jpeg,image/webp,image/gif">
          <?php if (!empty($item['avatar'])): ?><img class="preview-img" src="<?php echo e(asset($item['avatar'])); ?>" alt="معاينة" style="max-width:90px;border-radius:50%"><?php endif; ?>
        </div>
        <div class="form-group full">
          <label for="video_file">فيديو رأي العميل (اختياري)</label>
          <input class="form-control" id="video_file" name="video_file" type="file" accept="video/mp4,video/webm,.mp4,.webm">
          <span class="hint">الصيغ المدعومة MP4 وWebM، بحد أقصى <?php echo (int) round(testimonial_video_max_size() / 1048576); ?> ميجابايت. سيظهر الفيديو داخل بطاقة الرأي ويمكن تشغيله من الصفحة نفسها.</span>
          <?php $videoPreview = testimonial_video_asset($item['video_path'] ?? null); ?>
          <?php if ($videoPreview !== ''): ?>
            <video class="testimonial-admin-video" controls playsinline preload="metadata" aria-label="معاينة فيديو رأي العميل">
              <source src="<?php echo e($videoPreview); ?>" type="<?php echo str_ends_with((string) $item['video_path'], '.webm') ? 'video/webm' : 'video/mp4'; ?>">
            </video>
            <label class="switch"><input type="checkbox" name="remove_video"> إزالة الفيديو الحالي</label>
          <?php endif; ?>
        </div>
        <div class="form-group"><label>الحالة</label><label class="switch"><input type="checkbox" name="is_active" <?php echo (!$item || (int) ($item['is_active'] ?? 1)) ? 'checked' : ''; ?>> نشط بالموقع</label></div>
      </div>
    </div>
    <div class="form-actions">
      <a class="btn btn-outline" href="testimonials.php">إلغاء</a>
      <div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ الرأي</button></div>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
