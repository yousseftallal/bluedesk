<?php
/**
 * Services list.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    if (input('action') === 'toggle') {
        db_query('UPDATE services SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'تم تغيير حالة الخدمة.');
    } elseif (input('action') === 'delete') {
        $row = db_fetch('SELECT title_ar, image, icon_image FROM services WHERE id = ?', [$id]);
        db_query('DELETE FROM services WHERE id = ?', [$id]);
        if ($row) {
            delete_upload($row['image']);
            delete_upload($row['icon_image'] ?? null);
            log_activity('حذف خدمة', $row['title_ar']);
        }
        flash('success', 'تم حذف الخدمة.');
    }
    redirect('services.php');
}

$services = db_fetch_all('SELECT * FROM services ORDER BY sort_order, id');
$colors = ['blue' => 'أزرق', 'green' => 'أخضر', 'yellow' => 'أصفر', 'red' => 'أحمر'];

$adminTitle = 'الخدمات';
$adminSubtitle = 'الخدمات المعروضة في الموقع';
$adminActive = 'services';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>إدارة الخدمات</h1><p class="sub">أضف وعدّل الخدمات التي تظهر في الصفحة الرئيسية وصفحة الخدمات.</p></div>
  <div class="actions"><a class="btn btn-primary" href="service-edit.php"><i class="fa-solid fa-plus"></i> خدمة جديدة</a></div>
</div>

<div class="panel">
  <div class="panel-head"><h2>كل الخدمات (<?php echo count($services); ?>)</h2></div>
  <div class="panel-body p0">
    <?php if (!$services): ?>
      <div class="empty"><i class="fa-solid fa-layer-group"></i><p>لا توجد خدمات. أضف أول خدمة.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>الخدمة</th><th>الرابط</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($services as $row): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:.7rem">
                    <span class="stat-icon" style="width:<?php echo !empty($row['icon_image']) ? '52px' : '40px'; ?>;height:<?php echo !empty($row['icon_image']) ? '52px' : '40px'; ?>;font-size:.95rem;background:<?php echo !empty($row['icon_image']) ? 'transparent' : 'linear-gradient(135deg,#669df6,var(--blue-dark))'; ?>;box-shadow:<?php echo !empty($row['icon_image']) ? 'none' : 'var(--shadow-sm)'; ?>;overflow:visible">
                      <?php if (!empty($row['icon_image'])): ?>
                        <img src="<?php echo e(asset($row['icon_image'])); ?>" alt="" width="52" height="52" style="object-fit:contain;padding:0">
                      <?php else: ?>
                        <i class="fa-solid <?php echo e($row['icon']); ?>"></i>
                      <?php endif; ?>
                    </span>
                    <div>
                      <div class="cell-title"><?php echo e($row['title_ar']); ?></div>
                      <div class="cell-sub"><?php echo e($row['title_en']); ?> · <?php echo e($colors[$row['color']] ?? $row['color']); ?></div>
                    </div>
                  </div>
                </td>
                <td><span class="muted" dir="ltr">services.php#<?php echo e($row['slug']); ?></span></td>
                <td><?php echo (int) $row['sort_order']; ?></td>
                <td><span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $row['is_active'] ? 'نشطة' : 'موقوفة'; ?></span></td>
                <td class="actions-cell">
                  <form method="post" action="services.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-outline btn-sm btn-icon" type="submit" title="<?php echo $row['is_active'] ? 'إيقاف' : 'تفعيل'; ?>"><i class="fa-solid <?php echo $row['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i></button>
                  </form>
                  <a class="btn btn-outline btn-sm" href="service-edit.php?id=<?php echo (int) $row['id']; ?>"><i class="fa-solid fa-pen"></i> تعديل</a>
                  <form method="post" action="services.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف خدمة «<?php echo e($row['title_ar']); ?>»؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
