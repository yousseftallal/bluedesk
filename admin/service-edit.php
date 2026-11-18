<?php
/**
 * Service create/edit.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$service = !empty($_GET['id']) ? db_fetch('SELECT * FROM services WHERE id = ?', [(int) $_GET['id']]) : null;
if (!empty($_GET['id']) && !$service) {
    flash('error', 'الخدمة غير موجودة.');
    redirect('services.php');
}

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    $existing = $id > 0 ? db_fetch('SELECT * FROM services WHERE id = ?', [$id]) : null;

    $slug = strtolower(input('slug')) ?: make_slug(input('title_en'));
    $slug = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]/', '-', $slug)), '-');
    $image = input('image');
    $iconImage = $existing['icon_image'] ?? '';
    $newUploads = [];
    $iconClass = trim(input('icon', 'fa-code'));
    if (!preg_match('/^fa-[a-z0-9-]{1,55}$/', $iconClass)) {
        $iconClass = 'fa-code';
    }
    if (!empty($_FILES['image_file']['name'])) {
        $uploaded = upload_image($_FILES['image_file'], $err);
        if ($uploaded !== null) {
            $image = $uploaded;
            $newUploads[] = $uploaded;
        } else {
            flash('error', $err);
            redirect('service-edit.php' . ($id ? '?id=' . $id : ''));
        }
    }
    if (isset($_POST['remove_icon_image'])) {
        $iconImage = '';
    }
    if (!empty($_FILES['icon_image_file']['name'])) {
        $uploadedIcon = upload_image($_FILES['icon_image_file'], $err);
        if ($uploadedIcon !== null) {
            $iconImage = $uploadedIcon;
            $newUploads[] = $uploadedIcon;
        } else {
            foreach ($newUploads as $newUpload) {
                delete_upload($newUpload);
            }
            flash('error', $err);
            redirect('service-edit.php' . ($id ? '?id=' . $id : ''));
        }
    }

    $data = [
        $slug,
        $iconClass,
        $iconImage,
        in_array(input('color'), ['blue', 'green', 'yellow', 'red'], true) ? input('color') : 'blue',
        input('title_ar'),
        input('title_en'),
        input('desc_ar'),
        input('desc_en'),
        input('long_desc_ar'),
        input('long_desc_en'),
        $image,
        input('features_ar'),
        input('features_en'),
        (int) input('sort_order', '0'),
        isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data[4] === '' || $data[5] === '' || $data[6] === '' || $data[7] === '') {
        flash('error', 'العنوان والوصف مطلوبان باللغتين.');
    } elseif ($data[0] === '') {
        flash('error', 'رابط الخدمة (Slug) غير صالح.');
    } else {
        $dupe = db_fetch('SELECT id FROM services WHERE slug = ? AND id != ?', [$data[0], $id]);
        if ($dupe) {
            flash('error', 'هذا الرابط مستخدم في خدمة أخرى.');
        } else {
            if ($existing) {
                db_query(
                    'UPDATE services SET slug=?, icon=?, icon_image=?, color=?, title_ar=?, title_en=?, desc_ar=?, desc_en=?, long_desc_ar=?, long_desc_en=?, image=?, features_ar=?, features_en=?, sort_order=?, is_active=? WHERE id=?',
                    array_merge($data, [$id])
                );
                if ($image !== $existing['image']) {
                    delete_upload($existing['image']);
                }
                if ($iconImage !== ($existing['icon_image'] ?? '')) {
                    delete_upload($existing['icon_image'] ?? null);
                }
                log_activity('تعديل خدمة', $data[4]);
                flash('success', 'تم تحديث الخدمة.');
            } else {
                db_query(
                    'INSERT INTO services (slug, icon, icon_image, color, title_ar, title_en, desc_ar, desc_en, long_desc_ar, long_desc_en, image, features_ar, features_en, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    $data
                );
                log_activity('إضافة خدمة', $data[4]);
                flash('success', 'تمت إضافة الخدمة.');
            }
            redirect('services.php');
        }
    }
    foreach ($newUploads as $newUpload) {
        delete_upload($newUpload);
    }
    redirect('service-edit.php' . ($id ? '?id=' . $id : ''));
}

$colors = ['blue' => 'أزرق', 'green' => 'أخضر', 'yellow' => 'أصفر', 'red' => 'أحمر'];
$title = $service['title_ar'] ?? 'خدمة جديدة';

$adminTitle = $service ? 'تعديل خدمة' : 'خدمة جديدة';
$adminSubtitle = $title;
$adminActive = 'services';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1><?php echo e($title); ?></h1><p class="sub">تظهر الخدمة في قسم الخدمات بالصفحة الرئيسية وصفحة الخدمات.</p></div>
  <div class="actions"><a class="btn btn-outline" href="services.php"><i class="fa-solid fa-arrow-right"></i> رجوع للخدمات</a></div>
</div>

<form method="post" action="service-edit.php" enctype="multipart/form-data" data-loading>
  <?php echo csrf_field(); ?>
  <input type="hidden" name="id" value="<?php echo (int) ($service['id'] ?? 0); ?>">

  <div class="panel">
    <div class="panel-head"><h2>البيانات الأساسية</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="title_ar">اسم الخدمة (عربي) <span class="req">*</span></label><input class="form-control" id="title_ar" name="title_ar" value="<?php echo e($service['title_ar'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="title_en">اسم الخدمة (إنجليزي) <span class="req">*</span></label><input class="form-control ltr" id="title_en" name="title_en" value="<?php echo e($service['title_en'] ?? ''); ?>" required></div>
        <div class="form-group">
          <label for="slug">الرابط (Slug)</label>
          <input class="form-control ltr" id="slug" name="slug" value="<?php echo e($service['slug'] ?? ''); ?>" pattern="[a-z0-9-]+">
          <span class="hint">اتركه فارغًا للتوليد من الاسم الإنجليزي.</span>
        </div>
        <div class="form-group">
          <label for="color">لون البطاقة</label>
          <select class="form-control" id="color" name="color">
            <?php foreach ($colors as $key => $label): ?>
              <option value="<?php echo e($key); ?>" <?php echo ($service['color'] ?? 'blue') === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="icon">أيقونة Font Awesome</label>
          <div class="copy-field">
            <input class="form-control ltr" id="icon" name="icon" value="<?php echo e($service['icon'] ?? 'fa-code'); ?>" placeholder="fa-code">
            <span class="btn btn-outline btn-icon" aria-hidden="true"><i class="fa-solid <?php echo e($service['icon'] ?? 'fa-code'); ?>"></i></span>
          </div>
          <span class="hint">مثال: <code>fa-code</code> · <code>fa-mobile-screen-button</code> · <code>fa-cloud</code> · <code>fa-brain</code>. يمكنك استخدام هذه الأيقونة أو رفع صورة لها.</span>
          <label for="icon_image_file" style="margin-top:.8rem">رفع صورة للأيقونة (اختياري)</label>
          <input class="form-control" type="file" id="icon_image_file" name="icon_image_file" accept="image/png,image/jpeg,image/webp,image/gif">
          <span class="hint">الصيغ المسموحة: PNG أو JPG أو WEBP أو GIF، حتى 4 ميجابايت.</span>
          <?php if (!empty($service['icon_image'])): ?>
            <div style="display:flex;align-items:center;gap:.7rem;margin-top:.65rem">
              <img src="<?php echo e(asset($service['icon_image'])); ?>" alt="الأيقونة الحالية" width="72" height="72" style="object-fit:contain;border:0;border-radius:0;padding:0">
              <label class="switch"><input type="checkbox" name="remove_icon_image"> إزالة الصورة والعودة لأيقونة Font Awesome</label>
            </div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label for="sort_order">الترتيب</label>
          <input class="form-control ltr" type="number" id="sort_order" name="sort_order" value="<?php echo (int) ($service['sort_order'] ?? 0); ?>">
        </div>
        <div class="form-group full"><label for="desc_ar">الوصف المختصر (عربي) <span class="req">*</span></label><textarea class="form-control" id="desc_ar" name="desc_ar" rows="2" required><?php echo e($service['desc_ar'] ?? ''); ?></textarea></div>
        <div class="form-group full"><label for="desc_en">الوصف المختصر (إنجليزي) <span class="req">*</span></label><textarea class="form-control ltr" id="desc_en" name="desc_en" rows="2" required><?php echo e($service['desc_en'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="long_desc_ar">الوصف التفصيلي (عربي)</label><textarea class="form-control" id="long_desc_ar" name="long_desc_ar" rows="4"><?php echo e($service['long_desc_ar'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="long_desc_en">الوصف التفصيلي (إنجليزي)</label><textarea class="form-control ltr" id="long_desc_en" name="long_desc_en" rows="4"><?php echo e($service['long_desc_en'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="features_ar">المميزات (عربي) — سطر لكل ميزة</label><textarea class="form-control" id="features_ar" name="features_ar" rows="5"><?php echo e($service['features_ar'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="features_en">المميزات (إنجليزي) — سطر لكل ميزة</label><textarea class="form-control ltr" id="features_en" name="features_en" rows="5"><?php echo e($service['features_en'] ?? ''); ?></textarea></div>
        <div class="form-group">
          <label for="image">صورة الخدمة</label>
          <input class="form-control ltr" id="image" name="image" value="<?php echo e($service['image'] ?? ''); ?>" placeholder="https://...">
          <input class="form-control" type="file" name="image_file" style="margin-top:.45rem" accept="image/png,image/jpeg,image/webp,image/gif">
          <span class="hint">رابط صورة خارجي أو ارفع صورة جديدة.</span>
          <?php if (!empty($service['image'])): ?><img class="preview-img" src="<?php echo e(asset($service['image'])); ?>" alt="معاينة"><?php endif; ?>
        </div>
        <div class="form-group">
          <label>الحالة</label>
          <label class="switch"><input type="checkbox" name="is_active" <?php echo (!$service || (int) $service['is_active']) ? 'checked' : ''; ?>> نشطة وتظهر بالموقع</label>
        </div>
      </div>
    </div>
    <div class="form-actions">
      <a class="btn btn-outline" href="services.php">إلغاء</a>
      <div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ الخدمة</button></div>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
