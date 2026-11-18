<?php
/**
 * Testimonials list.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    if (input('action') === 'toggle') {
        db_query('UPDATE testimonials SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'تم تغيير حالة الرأي.');
    } elseif (input('action') === 'delete') {
        $row = db_fetch('SELECT name_ar, avatar, video_path FROM testimonials WHERE id = ?', [$id]);
        db_query('DELETE FROM testimonials WHERE id = ?', [$id]);
        if ($row) {
            delete_upload($row['avatar']);
            delete_upload($row['video_path'] ?? null);
            log_activity('حذف رأي عميل', $row['name_ar']);
        }
        flash('success', 'تم حذف الرأي.');
    }
    redirect('testimonials.php');
}

$items = db_fetch_all('SELECT * FROM testimonials ORDER BY sort_order, id');

$adminTitle = 'آراء العملاء';
$adminSubtitle = 'الشهادات المعروضة بالموقع';
$adminActive = 'testimonials';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>آراء العملاء</h1><p class="sub">أضف شهادات العملاء التي تظهر في الصفحة الرئيسية.</p></div>
  <div class="actions"><a class="btn btn-primary" href="testimonial-edit.php"><i class="fa-solid fa-plus"></i> رأي جديد</a></div>
</div>

<div class="panel">
  <div class="panel-head"><h2>كل الآراء (<?php echo count($items); ?>)</h2></div>
  <div class="panel-body p0">
    <?php if (!$items): ?>
      <div class="empty"><i class="fa-solid fa-quote-right"></i><p>لا توجد آراء بعد.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>العميل</th><th>الرأي</th><th>الفيديو</th><th>التقييم</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($items as $row): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:.6rem">
                    <?php if ($row['avatar']): ?><img class="thumb" src="<?php echo e(asset($row['avatar'])); ?>" alt=""><?php endif; ?>
                    <div>
                      <div class="cell-title"><?php echo e($row['name_ar']); ?></div>
                      <div class="cell-sub"><?php echo e($row['role_ar']); ?></div>
                    </div>
                  </div>
                </td>
                <td class="muted" style="max-width:320px"><?php echo e(mb_strimwidth($row['quote_ar'], 0, 85, '…')); ?></td>
                <td><?php echo testimonial_video_asset($row['video_path'] ?? null) !== '' ? '<span class="badge active">فيديو مرفق</span>' : '<span class="muted">نص فقط</span>'; ?></td>
                <td style="color:var(--yellow-dark);white-space:nowrap"><?php echo str_repeat('<i class="fa-solid fa-star"></i>', (int) $row['rating']); ?></td>
                <td><?php echo (int) $row['sort_order']; ?></td>
                <td><span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $row['is_active'] ? 'نشط' : 'موقوف'; ?></span></td>
                <td class="actions-cell">
                  <form method="post" action="testimonials.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-outline btn-sm btn-icon" type="submit" title="<?php echo $row['is_active'] ? 'إيقاف' : 'تفعيل'; ?>"><i class="fa-solid <?php echo $row['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i></button>
                  </form>
                  <a class="btn btn-outline btn-sm" href="testimonial-edit.php?id=<?php echo (int) $row['id']; ?>"><i class="fa-solid fa-pen"></i> تعديل</a>
                  <form method="post" action="testimonials.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف رأي «<?php echo e($row['name_ar']); ?>»؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
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
