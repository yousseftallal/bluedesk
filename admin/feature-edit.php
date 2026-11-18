<?php
/**
 * Feature/value create/edit.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$item = !empty($_GET['id']) ? db_fetch('SELECT * FROM features WHERE id = ?', [(int) $_GET['id']]) : null;
if (!empty($_GET['id']) && !$item) {
    flash('error', 'العنصر غير موجود.');
    redirect('features.php');
}

$section = $item['section'] ?? (($_GET['section'] ?? 'why') === 'value' ? 'value' : 'why');

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    $data = [
        input('section') === 'value' ? 'value' : 'why',
        input('icon', 'fa-star'),
        in_array(input('color'), ['blue', 'green', 'yellow', 'red'], true) ? input('color') : 'blue',
        input('title_ar'), input('title_en'),
        input('desc_ar'), input('desc_en'),
        (int) input('sort_order', '0'),
        isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data[3] === '' || $data[4] === '') {
        flash('error', 'العنوان مطلوب باللغتين.');
    } else {
        if ($id > 0) {
            db_query('UPDATE features SET section=?, icon=?, color=?, title_ar=?, title_en=?, desc_ar=?, desc_en=?, sort_order=?, is_active=? WHERE id=?', array_merge($data, [$id]));
            log_activity('تعديل ميزة', $data[3]);
            flash('success', 'تم تحديث العنصر.');
        } else {
            db_query('INSERT INTO features (section, icon, color, title_ar, title_en, desc_ar, desc_en, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?)', $data);
            log_activity('إضافة ميزة', $data[3]);
            flash('success', 'تمت إضافة العنصر.');
        }
        redirect('features.php?section=' . $data[0]);
    }
    redirect('feature-edit.php' . ($id ? '?id=' . $id : '?section=' . $section));
}

$colors = ['blue' => 'أزرق', 'green' => 'أخضر', 'yellow' => 'أصفر', 'red' => 'أحمر'];
$title = $item['title_ar'] ?? ($section === 'value' ? 'قيمة جديدة' : 'ميزة جديدة');
$adminTitle = $item ? 'تعديل عنصر' : 'عنصر جديد';
$adminSubtitle = $title;
$adminActive = 'features';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1><?php echo e($title); ?></h1><p class="sub">بطاقة ضمن قسم <?php echo $section === 'value' ? 'قيم الشركة' : 'لماذا بلوديسك'; ?>.</p></div>
  <div class="actions"><a class="btn btn-outline" href="features.php?section=<?php echo e($section); ?>"><i class="fa-solid fa-arrow-right"></i> رجوع للقائمة</a></div>
</div>

<form method="post" action="feature-edit.php" data-loading>
  <?php echo csrf_field(); ?>
  <input type="hidden" name="id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
  <div class="panel">
    <div class="panel-head"><h2>بيانات العنصر</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group">
          <label for="section">القسم</label>
          <select class="form-control" id="section" name="section">
            <option value="why" <?php echo $section === 'why' ? 'selected' : ''; ?>>لماذا بلوديسك (Why)</option>
            <option value="value" <?php echo $section === 'value' ? 'selected' : ''; ?>>قيم الشركة (Values)</option>
          </select>
        </div>
        <div class="form-group">
          <label for="color">اللون</label>
          <select class="form-control" id="color" name="color">
            <?php foreach ($colors as $key => $label): ?>
              <option value="<?php echo e($key); ?>" <?php echo ($item['color'] ?? 'blue') === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="icon">أيقونة Font Awesome</label>
          <div class="copy-field">
            <input class="form-control ltr" id="icon" name="icon" value="<?php echo e($item['icon'] ?? 'fa-star'); ?>">
            <span class="btn btn-outline btn-icon" aria-hidden="true"><i class="fa-solid <?php echo e($item['icon'] ?? 'fa-star'); ?>"></i></span>
          </div>
        </div>
        <div class="form-group"><label for="sort_order">الترتيب</label><input class="form-control ltr" type="number" id="sort_order" name="sort_order" value="<?php echo (int) ($item['sort_order'] ?? 0); ?>"></div>
        <div class="form-group"><label for="title_ar">العنوان (عربي) <span class="req">*</span></label><input class="form-control" id="title_ar" name="title_ar" value="<?php echo e($item['title_ar'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="title_en">العنوان (إنجليزي) <span class="req">*</span></label><input class="form-control ltr" id="title_en" name="title_en" value="<?php echo e($item['title_en'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="desc_ar">الوصف (عربي)</label><textarea class="form-control" id="desc_ar" name="desc_ar" rows="3"><?php echo e($item['desc_ar'] ?? ''); ?></textarea></div>
        <div class="form-group"><label for="desc_en">الوصف (إنجليزي)</label><textarea class="form-control ltr" id="desc_en" name="desc_en" rows="3"><?php echo e($item['desc_en'] ?? ''); ?></textarea></div>
        <div class="form-group"><label>الحالة</label><label class="switch"><input type="checkbox" name="is_active" <?php echo (!$item || (int) ($item['is_active'] ?? 1)) ? 'checked' : ''; ?>> نشط بالموقع</label></div>
      </div>
    </div>
    <div class="form-actions">
      <a class="btn btn-outline" href="features.php?section=<?php echo e($section); ?>">إلغاء</a>
      <div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ العنصر</button></div>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
