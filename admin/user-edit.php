<?php
/**
 * User create/edit (admin only).
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$me = current_user();
$user = !empty($_GET['id']) ? db_fetch('SELECT * FROM users WHERE id = ?', [(int) $_GET['id']]) : null;
if (!empty($_GET['id']) && !$user) {
    flash('error', 'المستخدم غير موجود.');
    redirect('users.php');
}
$isSelf = $user && (int) $user['id'] === (int) $me['id'];

if (is_post()) {
    require_csrf();
    $id = (int) input('id');
    $existing = $id > 0 ? db_fetch('SELECT * FROM users WHERE id = ?', [$id]) : null;
    $editingSelf = $existing && (int) $existing['id'] === (int) $me['id'];

    $name = input('name');
    $email = strtolower(input('email'));
    $username = strtolower(input('username'));
    $role = input('role') === 'admin' ? 'admin' : 'editor';
    $active = isset($_POST['is_active']) ? 1 : 0;
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    if ($editingSelf) {
        $role = 'admin';
        $active = 1;
    }

    $errors = [];
    if ($name === '') $errors[] = 'الاسم مطلوب.';
    if (!valid_email($email)) $errors[] = 'البريد الإلكتروني غير صالح.';
    if (!preg_match('/^[a-z0-9_.-]{3,60}$/', $username)) $errors[] = 'اسم المستخدم يجب أن يكون 3-60 حرفًا إنجليزيًا/أرقامًا.';
    if ($existing) {
        if ($password !== '') {
            if (mb_strlen($password) < 12) $errors[] = 'كلمة المرور يجب أن تكون 12 حرفًا على الأقل.';
            if ($password !== $confirm) $errors[] = 'تأكيد كلمة المرور غير مطابق.';
        }
    } else {
        if (mb_strlen($password) < 12) $errors[] = 'كلمة المرور يجب أن تكون 12 حرفًا على الأقل.';
        if ($password !== $confirm) $errors[] = 'تأكيد كلمة المرور غير مطابق.';
    }

    if (!$errors) {
        $dupe = db_fetch('SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?', [$email, $username, $id]);
        if ($dupe) {
            $errors[] = 'البريد الإلكتروني أو اسم المستخدم مستخدم بالفعل.';
        }
    }

    if (!$errors && $existing && $existing['role'] === 'admin' && ($role !== 'admin' || !$active)) {
        $admins = (int) (db_fetch("SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND is_active = 1 AND id != ?", [$id])['c'] ?? 0);
        if ($admins < 1) {
            $errors[] = 'لا يمكن إزالة صلاحية أو إيقاف آخر مدير نشط.';
        }
    }

    if (!$errors) {
        if ($existing) {
            db_query('UPDATE users SET name=?, email=?, username=?, role=?, is_active=? WHERE id=?', [$name, $email, $username, $role, $active, $id]);
            if ($password !== '') {
                db_query('UPDATE users SET password_hash=?, session_version = session_version + 1 WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), $id]);
                if ($editingSelf) {
                    $_SESSION['auth_version'] = (int) ($_SESSION['auth_version'] ?? 0) + 1;
                }
            }
            log_activity('تعديل مستخدم', $username);
            flash('success', 'تم تحديث بيانات المستخدم.');
        } else {
            db_query('INSERT INTO users (name, email, username, password_hash, role, is_active) VALUES (?,?,?,?,?,?)', [$name, $email, $username, password_hash($password, PASSWORD_DEFAULT), $role, $active]);
            log_activity('إضافة مستخدم', $username);
            flash('success', 'تمت إضافة المستخدم.');
        }
        redirect('users.php');
    }

    foreach ($errors as $err) {
        flash('error', $err);
    }
    redirect('user-edit.php' . ($id ? '?id=' . $id : ''));
}

$title = $user['name'] ?? 'مستخدم جديد';
$adminTitle = $user ? 'تعديل مستخدم' : 'مستخدم جديد';
$adminSubtitle = $title;
$adminActive = 'users';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1><?php echo e($title); ?></h1><p class="sub"><?php echo $isSelf ? 'أنت تعدل حسابك الحالي — لا يمكن تغيير صلاحيتك أو إيقافك.' : 'بيانات الدخول والصلاحيات.'; ?></p></div>
  <div class="actions"><a class="btn btn-outline" href="users.php"><i class="fa-solid fa-arrow-right"></i> رجوع للمستخدمين</a></div>
</div>

<form method="post" action="user-edit.php" data-loading>
  <?php echo csrf_field(); ?>
  <input type="hidden" name="id" value="<?php echo (int) ($user['id'] ?? 0); ?>">
  <div class="panel">
    <div class="panel-head"><h2>بيانات المستخدم</h2></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="name">الاسم <span class="req">*</span></label><input class="form-control" id="name" name="name" value="<?php echo e($user['name'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="email">البريد الإلكتروني <span class="req">*</span></label><input class="form-control ltr" type="email" id="email" name="email" value="<?php echo e($user['email'] ?? ''); ?>" required></div>
        <div class="form-group"><label for="username">اسم المستخدم <span class="req">*</span></label><input class="form-control ltr" id="username" name="username" value="<?php echo e($user['username'] ?? ''); ?>" pattern="[a-zA-Z0-9_.-]{3,60}" required></div>
        <div class="form-group">
          <label for="role">الصلاحية</label>
          <select class="form-control" id="role" name="role" <?php echo $isSelf ? 'disabled' : ''; ?>>
            <option value="editor" <?php echo ($user['role'] ?? 'editor') === 'editor' ? 'selected' : ''; ?>>محرر محتوى</option>
            <option value="admin" <?php echo ($user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>مدير نظام</option>
          </select>
          <?php if ($isSelf): ?><input type="hidden" name="role" value="admin"><span class="hint">لا يمكن تغيير صلاحية حسابك الحالي.</span><?php endif; ?>
        </div>
        <div class="form-group">
          <label>الحالة</label>
          <label class="switch"><input type="checkbox" name="is_active" <?php echo (!$user || (int) $user['is_active']) ? 'checked' : ''; ?> <?php echo $isSelf ? 'disabled' : ''; ?>> نشط</label>
          <?php if ($isSelf): ?><input type="hidden" name="is_active" value="1"><?php endif; ?>
        </div>
        <div class="form-group full">
          <p class="help-box" style="margin:0"><b>الصلاحيات:</b> مدير النظام يصل لكل شيء (بما فيها الإعدادات والمستخدمون). محرر المحتوى يدير المحتوى والرسائل والوسائط فقط.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>كلمة المرور</h2><span class="muted"><?php echo $user ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية' : 'مطلوبة للحساب الجديد'; ?></span></div>
    <div class="panel-body">
      <div class="form-grid">
        <div class="form-group"><label for="password">كلمة المرور <?php echo $user ? '' : '<span class="req">*</span>'; ?></label><input class="form-control ltr" type="password" id="password" name="password" autocomplete="new-password" minlength="12" <?php echo $user ? '' : 'required'; ?>></div>
        <div class="form-group"><label for="password_confirm">تأكيد كلمة المرور</label><input class="form-control ltr" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="12"></div>
      </div>
          <p class="hint">12 حرفًا على الأقل. استخدم كلمة مرور فريدة يصعب تخمينها.</p>
    </div>
    <div class="form-actions">
      <a class="btn btn-outline" href="users.php">إلغاء</a>
      <div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ المستخدم</button></div>
    </div>
  </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
