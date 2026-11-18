<?php
/**
 * Team member create/edit.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$member = !empty($_GET['id']) ? db_fetch('SELECT * FROM team WHERE id = ?', [(int) $_GET['id']]) : null;
if (!empty($_GET['id']) && !$member) {
    flash('error', 'العضو غير موجود.');
    redirect('team.php');
}

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    $existing = $id > 0 ? db_fetch('SELECT * FROM team WHERE id = ?', [$id]) : null;

    $photo = input('photo');
    if (!empty($_FILES['photo_file']['name'])) {
        $uploaded = upload_image($_FILES['photo_file'], $err);
        if ($uploaded !== null) {
            $photo = $uploaded;
        } else {
            flash('error', $err);
            redirect('team-edit.php' . ($id ? '?id=' . $id : ''));
        }
    }

    $data = [input('name_ar'), input('name_en'), input('role_ar'), input('role_en'), $photo, (int) input('sort_order', '0'), isset($_POST['is_active']) ? 1 : 0];

    if ($data[0] === '' || $data[1] === '') {
        flash('error', 'اسم العضو مطلوب باللغتين.');
    } else {
        if ($existing) {
            db_query('UPDATE team SET name_ar=?, name_en=?, role_ar=?, role_en=?, photo=?, sort_order=?, is_active=? WHERE id=?', array_merge($data, [$id]));
            if ($photo !== $existing['photo']) {
                delete_upload($existing['photo']);
            }
            log_activity('تعديل عضو فريق', $data[0]);
            flash('success', 'تم تحديث بيانات العضو.');
        } else {
            db_query('INSERT INTO team (name_ar, name_en, role_ar, role_en, photo, sort_order, is_active) VALUES (?,?,?,?,?,?,?)', $data);
            log_activity('إضافة عضو فريق', $data[0]);
            flash('success', 'تمت إضافة العضو.');
        }
        redirect('team.php');
    }
    redirect('team-edit.php' . ($id ? '?id=' . $id : ''));
}

$title = $member['name_ar'] ?? 'عضو جديد';
$adminTitle = $member ? 'تعديل عضو' : 'عضو جديد';
$adminSubtitle = $title;
$adminActive = 'team';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1><?php echo e($title); ?></h1><p class="sub">بيانات عضو فريق العمل كما تظهر بالموقع.</p></div>
  <div class="actions"><a class="btn btn-outline" href="team.php"><i class="fa-solid fa-arrow-right"></i> رجوع للفريق</a></div>
</div>

<form method="post" action="team-edit.php" enctype="multipart/form-data" data-loading>
  <?php echo csrf_field(); ?>
  <input type="hidden" name="id" value="<?php echo (int) ($member['id'] ?? 0); ?>">
  <div class="panel">
    <div class="panel-head"><h2>بيانات العضو</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="name_ar">الاسم (عربي) <span class="req">*</span></label><input class="form-control" id="name_ar" name="name_ar" value="<?php echo e($member['name_ar'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="name_en">الاسم (إنجليزي) <span class="req">*</span></label><input class="form-control ltr" id="name_en" name="name_en" value="<?php echo e($member['name_en'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="role_ar">المسمى الوظيفي (عربي)</label><input class="form-control" id="role_ar" name="role_ar" value="<?php echo e($member['role_ar'] ?? ''); ?>"></div>
        <div class="form-group"><label for="role_en">المسمى الوظيفي (إنجليزي)</label><input class="form-control ltr" id="role_en" name="role_en" value="<?php echo e($member['role_en'] ?? ''); ?>"></div>
        <div class="form-group"><label for="sort_order">الترتيب</label><input class="form-control ltr" type="number" id="sort_order" name="sort_order" value="<?php echo (int) ($member['sort_order'] ?? 0); ?>"></div>
        <div class="form-group"><label>الحالة</label><label class="switch"><input type="checkbox" name="is_active" <?php echo (!$member || (int) $member['is_active']) ? 'checked' : ''; ?>> نشط بالموقع</label></div>
        <div class="form-group">
          <label for="photo">الصورة الشخصية</label>
          <input class="form-control ltr" id="photo" name="photo" value="<?php echo e($member['photo'] ?? ''); ?>" placeholder="https://...">
          <input class="form-control" type="file" name="photo_file" style="margin-top:.45rem" accept="image/png,image/jpeg,image/webp,image/gif">
          <span class="hint">صورة مربعة أو رأسية بجودة جيدة.</span>
          <?php if (!empty($member['photo'])): ?><img class="preview-img" src="<?php echo e(asset($member['photo'])); ?>" alt="معاينة" style="max-width:110px"><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="form-actions">
      <a class="btn btn-outline" href="team.php">إلغاء</a>
      <div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ العضو</button></div>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
