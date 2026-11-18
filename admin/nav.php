<?php
/**
 * Navigation menus manager (header / footer).
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (is_post()) {
    require_csrf();
    $action = input('action');

    if ($action === 'save') {
        $id = (int) input('id');
        $data = [
            in_array(input('location'), ['header', 'footer_quick', 'footer_services'], true) ? input('location') : 'header',
            input('label_ar'), input('label_en'), input('url'),
            (int) input('sort_order', '0'),
            isset($_POST['is_active']) ? 1 : 0,
            isset($_POST['open_new_tab']) ? 1 : 0,
        ];
        if ($data[1] === '' || $data[2] === '' || $data[3] === '') {
            flash('error', 'العنوان والرابط مطلوبان باللغتين.');
        } elseif (safe_href($data[3]) === '') {
            flash('error', 'الرابط غير صالح أو يستخدم بروتوكولًا غير مسموح.');
        } elseif ($id > 0) {
            db_query('UPDATE nav_items SET location=?, label_ar=?, label_en=?, url=?, sort_order=?, is_active=?, open_new_tab=? WHERE id=?', array_merge($data, [$id]));
            log_activity('تعديل عنصر قائمة', $data[1]);
            flash('success', 'تم تحديث عنصر القائمة.');
        } else {
            db_query('INSERT INTO nav_items (location, label_ar, label_en, url, sort_order, is_active, open_new_tab) VALUES (?,?,?,?,?,?,?)', $data);
            log_activity('إضافة عنصر قائمة', $data[1]);
            flash('success', 'تمت إضافة عنصر القائمة.');
        }
        redirect('nav.php?loc=' . $data[0]);
    }

    if ($action === 'delete') {
        $id = (int) input('id');
        $row = db_fetch('SELECT label_ar FROM nav_items WHERE id = ?', [$id]);
        db_query('DELETE FROM nav_items WHERE id = ?', [$id]);
        log_activity('حذف عنصر قائمة', $row['label_ar'] ?? ('#' . $id));
        flash('success', 'تم حذف العنصر.');
        redirect('nav.php' . (!empty($_POST['location']) ? '?loc=' . urlencode(input('location')) : ''));
    }

    if ($action === 'toggle') {
        $id = (int) input('id');
        db_query('UPDATE nav_items SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'تم تغيير حالة العنصر.');
        redirect('nav.php' . (!empty($_POST['location']) ? '?loc=' . urlencode(input('location')) : ''));
    }
}

$loc = in_array($_GET['loc'] ?? 'header', ['header', 'footer_quick', 'footer_services'], true) ? ($_GET['loc'] ?? 'header') : 'header';
$labels = ['header' => 'القائمة الرئيسية (Header)', 'footer_quick' => 'روابط سريعة (Footer)', 'footer_services' => 'خدمات (Footer)'];
$items = db_fetch_all('SELECT * FROM nav_items WHERE location = ? ORDER BY sort_order, id', [$loc]);
$edit = !empty($_GET['edit']) ? db_fetch('SELECT * FROM nav_items WHERE id = ?', [(int) $_GET['edit']]) : null;

$adminTitle = 'التنقل والقوائم';
$adminSubtitle = 'روابط الهيدر والفوتر';
$adminActive = 'nav';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>إدارة القوائم</h1><p class="sub">روابط القائمة الرئيسية وروابط الفوتر باللغتين.</p></div>
</div>

<div class="panel">
  <div class="section-tabs">
    <?php foreach ($labels as $key => $label): ?>
      <a href="nav.php?loc=<?php echo e($key); ?>" class="<?php echo $loc === $key ? 'active' : ''; ?>"><?php echo e($label); ?></a>
    <?php endforeach; ?>
  </div>

  <form method="post" action="nav.php" data-loading>
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?php echo (int) ($edit['id'] ?? 0); ?>">
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="label_ar">العنوان (عربي) <span class="req">*</span></label><input class="form-control" id="label_ar" name="label_ar" value="<?php echo e($edit['label_ar'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="label_en">العنوان (إنجليزي) <span class="req">*</span></label><input class="form-control ltr" id="label_en" name="label_en" value="<?php echo e($edit['label_en'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="url">الرابط <span class="req">*</span></label><input class="form-control ltr" id="url" name="url" value="<?php echo e($edit['url'] ?? ''); ?>" placeholder="index.php أو https://..." required></div>
        <div class="form-group"><label for="location">القائمة</label>
          <select class="form-control" id="location" name="location">
            <?php foreach ($labels as $key => $label): ?>
              <option value="<?php echo e($key); ?>" <?php echo ($edit['location'] ?? $loc) === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label for="sort_order">الترتيب</label><input class="form-control ltr" type="number" id="sort_order" name="sort_order" value="<?php echo (int) ($edit['sort_order'] ?? (count($items) + 1)); ?>"></div>
        <div class="form-group">
          <label>خيارات</label>
          <label class="switch" style="margin-inline-end:1rem"><input type="checkbox" name="is_active" <?php echo (!$edit || (int) $edit['is_active']) ? 'checked' : ''; ?>> نشط</label>
          <label class="switch"><input type="checkbox" name="open_new_tab" <?php echo !empty($edit['open_new_tab']) ? 'checked' : ''; ?>> فتح في تاب جديد</label>
        </div>
      </div>
    </div>
    <div class="form-actions">
      <?php if ($edit): ?><a class="btn btn-outline" href="nav.php?loc=<?php echo e($loc); ?>">إلغاء التعديل</a><?php endif; ?>
      <div class="right"><button class="btn btn-primary" type="submit"><?php echo $edit ? 'تحديث العنصر' : 'إضافة عنصر'; ?></button></div>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2><?php echo e($labels[$loc]); ?> (<?php echo count($items); ?>)</h2></div>
  <div class="panel-body p0">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>العنوان</th><th>الرابط</th><th>الترتيب</th><th>الحالة</th><th>تاب جديد</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($items as $row): ?>
            <tr>
              <td class="cell-title"><?php echo e($row['label_ar']); ?> <span class="cell-sub">— <?php echo e($row['label_en']); ?></span></td>
              <td><span class="muted" dir="ltr"><?php echo e($row['url']); ?></span></td>
              <td><?php echo (int) $row['sort_order']; ?></td>
              <td><span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $row['is_active'] ? 'نشط' : 'موقوف'; ?></span></td>
              <td><?php echo $row['open_new_tab'] ? '<i class="fa-solid fa-check" style="color:var(--green)"></i>' : '—'; ?></td>
              <td class="actions-cell">
                <form method="post" action="nav.php" class="inline-form">
                  <?php echo csrf_field(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="location" value="<?php echo e($loc); ?>"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                  <button class="btn btn-outline btn-sm btn-icon" type="submit" title="<?php echo $row['is_active'] ? 'إيقاف' : 'تفعيل'; ?>"><i class="fa-solid <?php echo $row['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i></button>
                </form>
                <a class="btn btn-outline btn-sm btn-icon" href="nav.php?loc=<?php echo e($loc); ?>&edit=<?php echo (int) $row['id']; ?>" title="تعديل"><i class="fa-solid fa-pen"></i></a>
                <form method="post" action="nav.php" class="inline-form">
                  <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="location" value="<?php echo e($loc); ?>"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                  <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف العنصر «<?php echo e($row['label_ar']); ?>»؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
