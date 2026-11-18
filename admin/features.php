<?php
/**
 * Features & values list.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    if (input('action') === 'toggle') {
        db_query('UPDATE features SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'تم تغيير حالة العنصر.');
    } elseif (input('action') === 'delete') {
        $row = db_fetch('SELECT title_ar FROM features WHERE id = ?', [$id]);
        db_query('DELETE FROM features WHERE id = ?', [$id]);
        if ($row) {
            log_activity('حذف ميزة', $row['title_ar']);
        }
        flash('success', 'تم حذف العنصر.');
    }
    redirect('features.php' . (!empty($_POST['section']) ? '?section=' . urlencode(input('section')) : ''));
}

$section = ($_GET['section'] ?? 'why') === 'value' ? 'value' : 'why';
$items = db_fetch_all('SELECT * FROM features WHERE section = ? ORDER BY sort_order, id', [$section]);
$colors = ['blue' => 'أزرق', 'green' => 'أخضر', 'yellow' => 'أصفر', 'red' => 'أحمر'];

$adminTitle = 'المزايا والقيم';
$adminSubtitle = 'بطاقات "لماذا بلوديسك" وقيم الشركة';
$adminActive = 'features';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>المزايا والقيم</h1><p class="sub">تظهر مزايا "لماذا بلوديسك" في الرئيسية، والقيم في صفحة من نحن.</p></div>
  <div class="actions"><a class="btn btn-primary" href="feature-edit.php?section=<?php echo e($section); ?>"><i class="fa-solid fa-plus"></i> عنصر جديد</a></div>
</div>

<div class="panel">
  <div class="section-tabs">
    <a href="features.php?section=why" class="<?php echo $section === 'why' ? 'active' : ''; ?>">لماذا بلوديسك (Why)</a>
    <a href="features.php?section=value" class="<?php echo $section === 'value' ? 'active' : ''; ?>">قيم الشركة (Values)</a>
  </div>
  <div class="panel-body p0">
    <?php if (!$items): ?>
      <div class="empty"><i class="fa-solid fa-star"></i><p>لا توجد عناصر في هذا القسم.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>العنصر</th><th>الوصف</th><th>اللون</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($items as $row): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:.6rem">
                    <span class="stat-icon" style="width:38px;height:38px;font-size:.9rem;background:linear-gradient(135deg,#669df6,var(--blue-dark))"><i class="fa-solid <?php echo e($row['icon']); ?>"></i></span>
                    <div>
                      <div class="cell-title"><?php echo e($row['title_ar']); ?></div>
                      <div class="cell-sub"><?php echo e($row['title_en']); ?></div>
                    </div>
                  </div>
                </td>
                <td class="muted" style="max-width:320px"><?php echo e(mb_strimwidth($row['desc_ar'], 0, 80, '…')); ?></td>
                <td><?php echo e($colors[$row['color']] ?? $row['color']); ?></td>
                <td><?php echo (int) $row['sort_order']; ?></td>
                <td><span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $row['is_active'] ? 'نشط' : 'موقوف'; ?></span></td>
                <td class="actions-cell">
                  <form method="post" action="features.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="section" value="<?php echo e($section); ?>"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-outline btn-sm btn-icon" type="submit" title="<?php echo $row['is_active'] ? 'إيقاف' : 'تفعيل'; ?>"><i class="fa-solid <?php echo $row['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i></button>
                  </form>
                  <a class="btn btn-outline btn-sm" href="feature-edit.php?id=<?php echo (int) $row['id']; ?>"><i class="fa-solid fa-pen"></i> تعديل</a>
                  <form method="post" action="features.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="section" value="<?php echo e($section); ?>"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف العنصر «<?php echo e($row['title_ar']); ?>»؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
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
