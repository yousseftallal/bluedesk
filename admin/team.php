<?php
/**
 * Team list.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    if (input('action') === 'toggle') {
        db_query('UPDATE team SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('success', 'تم تغيير حالة العضو.');
    } elseif (input('action') === 'delete') {
        $row = db_fetch('SELECT name_ar, photo FROM team WHERE id = ?', [$id]);
        db_query('DELETE FROM team WHERE id = ?', [$id]);
        if ($row) {
            delete_upload($row['photo']);
            log_activity('حذف عضو فريق', $row['name_ar']);
        }
        flash('success', 'تم حذف عضو الفريق.');
    }
    redirect('team.php');
}

$members = db_fetch_all('SELECT * FROM team ORDER BY sort_order, id');

$adminTitle = 'الفريق';
$adminSubtitle = 'أعضاء فريق العمل';
$adminActive = 'team';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>فريق العمل</h1><p class="sub">الأعضاء الظاهرون في قسم الفريق بالصفحة الرئيسية.</p></div>
  <div class="actions"><a class="btn btn-primary" href="team-edit.php"><i class="fa-solid fa-plus"></i> عضو جديد</a></div>
</div>

<div class="panel">
  <div class="panel-head"><h2>كل الأعضاء (<?php echo count($members); ?>)</h2></div>
  <div class="panel-body p0">
    <?php if (!$members): ?>
      <div class="empty"><i class="fa-solid fa-user-group"></i><p>لا يوجد أعضاء بعد.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>العضو</th><th>المسمى الوظيفي</th><th>الترتيب</th><th>الحالة</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($members as $row): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:.6rem">
                    <?php if ($row['photo']): ?><img class="thumb" style="border-radius:50%" src="<?php echo e(asset($row['photo'])); ?>" alt=""><?php endif; ?>
                    <div>
                      <div class="cell-title"><?php echo e($row['name_ar']); ?></div>
                      <div class="cell-sub"><?php echo e($row['name_en']); ?></div>
                    </div>
                  </div>
                </td>
                <td><?php echo e($row['role_ar']); ?> <span class="cell-sub">— <?php echo e($row['role_en']); ?></span></td>
                <td><?php echo (int) $row['sort_order']; ?></td>
                <td><span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $row['is_active'] ? 'نشط' : 'موقوف'; ?></span></td>
                <td class="actions-cell">
                  <form method="post" action="team.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-outline btn-sm btn-icon" type="submit" title="<?php echo $row['is_active'] ? 'إيقاف' : 'تفعيل'; ?>"><i class="fa-solid <?php echo $row['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i></button>
                  </form>
                  <a class="btn btn-outline btn-sm" href="team-edit.php?id=<?php echo (int) $row['id']; ?>"><i class="fa-solid fa-pen"></i> تعديل</a>
                  <form method="post" action="team.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف العضو «<?php echo e($row['name_ar']); ?>»؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
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
