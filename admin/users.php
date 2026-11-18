<?php
/**
 * Users list (admin only).
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$me = current_user();

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    $target = db_fetch('SELECT * FROM users WHERE id = ?', [$id]);

    if (!$target) {
        flash('error', 'المستخدم غير موجود.');
    } elseif ($id === (int) $me['id']) {
        flash('error', 'لا يمكنك حذف أو إيقاف حسابك الحالي.');
    } elseif (input('action') === 'delete') {
        if ($target['role'] === 'admin') {
            $admins = (int) (db_fetch("SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND is_active = 1")['c'] ?? 0);
            if ($admins <= 1) {
                flash('error', 'لا يمكن حذف آخر مدير نشط في النظام.');
                redirect('users.php');
            }
        }
        db_query('DELETE FROM users WHERE id = ?', [$id]);
        log_activity('حذف مستخدم', $target['username']);
        flash('success', 'تم حذف المستخدم.');
    } elseif (input('action') === 'toggle') {
        if ($target['role'] === 'admin' && (int) $target['is_active'] === 1) {
            $admins = (int) (db_fetch("SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND is_active = 1")['c'] ?? 0);
            if ($admins <= 1) {
                flash('error', 'لا يمكن إيقاف آخر مدير نشط في النظام.');
                redirect('users.php');
            }
        }
        db_query('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$id]);
        log_activity('تغيير حالة مستخدم', $target['username']);
        flash('success', 'تم تغيير حالة المستخدم.');
    }
    redirect('users.php');
}

$users = db_fetch_all('SELECT * FROM users ORDER BY id');

$adminTitle = 'المستخدمون';
$adminSubtitle = 'حسابات لوحة التحكم والصلاحيات';
$adminActive = 'users';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>المستخدمون والصلاحيات</h1><p class="sub">إدارة من يمكنه الدخول إلى لوحة التحكم.</p></div>
  <div class="actions"><a class="btn btn-primary" href="user-edit.php"><i class="fa-solid fa-user-plus"></i> مستخدم جديد</a></div>
</div>

<div class="panel">
  <div class="panel-head"><h2>الحسابات (<?php echo count($users); ?>)</h2></div>
  <div class="panel-body p0">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>المستخدم</th><th>البريد</th><th>الصلاحية</th><th>آخر دخول</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($users as $row): ?>
            <tr>
              <td>
                <div class="cell-title"><?php echo e($row['name']); ?> <?php if ((int) $row['id'] === (int) $me['id']): ?><span class="badge main">أنت</span><?php endif; ?></div>
                <div class="cell-sub" dir="ltr" style="text-align:start">@<?php echo e($row['username']); ?></div>
              </td>
              <td dir="ltr" style="text-align:start"><?php echo e($row['email']); ?></td>
              <td><span class="badge role-<?php echo e($row['role']); ?>"><?php echo $row['role'] === 'admin' ? 'مدير نظام' : 'محرر محتوى'; ?></span></td>
              <td class="muted"><?php echo $row['last_login'] ? localized_datetime_html((string)$row['last_login']) : 'لم يسجل بعد'; ?></td>
              <td><span class="badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $row['is_active'] ? 'نشط' : 'موقوف'; ?></span></td>
              <td class="actions-cell">
                <?php if ((int) $row['id'] !== (int) $me['id']): ?>
                  <form method="post" action="users.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-outline btn-sm btn-icon" type="submit" title="<?php echo $row['is_active'] ? 'إيقاف' : 'تفعيل'; ?>"><i class="fa-solid <?php echo $row['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i></button>
                  </form>
                <?php endif; ?>
                <a class="btn btn-outline btn-sm" href="user-edit.php?id=<?php echo (int) $row['id']; ?>"><i class="fa-solid fa-pen"></i> تعديل</a>
                <?php if ((int) $row['id'] !== (int) $me['id']): ?>
                  <form method="post" action="users.php" class="inline-form">
                    <?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                    <button class="btn btn-danger btn-sm btn-icon" type="submit" data-confirm="حذف المستخدم «<?php echo e($row['username']); ?>» نهائيًا؟" title="حذف"><i class="fa-solid fa-trash"></i></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
