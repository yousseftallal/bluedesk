<?php
/**
 * My profile.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$me = current_user();

if (is_post()) {
    require_csrf();
    $action = input('action');

    if ($action === 'profile') {
        $name = input('name');
        $email = strtolower(input('email'));
        $username = strtolower(input('username'));
        $errors = [];
        if ($name === '') $errors[] = 'الاسم مطلوب.';
        if (!valid_email($email)) $errors[] = 'البريد الإلكتروني غير صالح.';
        if (!preg_match('/^[a-z0-9_.-]{3,60}$/', $username)) $errors[] = 'اسم المستخدم غير صالح.';
        if (!$errors) {
            $dupe = db_fetch('SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?', [$email, $username, $me['id']]);
            if ($dupe) {
                $errors[] = 'البريد أو اسم المستخدم مستخدم بحساب آخر.';
            }
        }
        if ($errors) {
            foreach ($errors as $err) flash('error', $err);
        } else {
            db_query('UPDATE users SET name=?, email=?, username=? WHERE id=?', [$name, $email, $username, $me['id']]);
            log_activity('تحديث الملف الشخصي', $username);
            flash('success', 'تم تحديث بياناتك الشخصية.');
        }
        redirect('profile.php');
    }

    if ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');
        $row = db_fetch('SELECT password_hash FROM users WHERE id = ?', [$me['id']]);

        if (!$row || !password_verify($current, $row['password_hash'])) {
            flash('error', 'كلمة المرور الحالية غير صحيحة.');
        } elseif (mb_strlen($new) < 12) {
            flash('error', 'كلمة المرور الجديدة يجب أن تكون 12 حرفًا على الأقل.');
        } elseif ($new !== $confirm) {
            flash('error', 'تأكيد كلمة المرور غير مطابق.');
        } elseif ($new === $current) {
            flash('error', 'اختر كلمة مرور مختلفة عن الحالية.');
        } else {
            db_query('UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            $_SESSION['auth_version'] = (int) ($_SESSION['auth_version'] ?? 0) + 1;
            log_activity('تغيير كلمة المرور', 'تم تغيير كلمة مرور الحساب');
            flash('success', 'تم تغيير كلمة المرور بنجاح.');
        }
        redirect('profile.php');
    }
}

$me = current_user();

$adminTitle = 'ملفي الشخصي';
$adminSubtitle = 'بيانات الحساب وكلمة المرور';
$adminActive = 'profile';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div><h1>ملفي الشخصي</h1><p class="sub">آخر دخول: <?php echo localized_datetime_html($me['last_login'] ? (string)$me['last_login'] : null); ?></p></div>
</div>

<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h2>البيانات الشخصية</h2></div>
    <form method="post" action="profile.php" data-loading>
      <?php echo csrf_field(); ?><input type="hidden" name="action" value="profile">
      <div class="panel-body">
        <div class="form-group"><label for="name">الاسم</label><input class="form-control" id="name" name="name" value="<?php echo e($me['name']); ?>" required></div>
        <div class="form-group"><label for="email">البريد الإلكتروني</label><input class="form-control ltr" type="email" id="email" name="email" value="<?php echo e($me['email']); ?>" required></div>
        <div class="form-group"><label for="username">اسم المستخدم</label><input class="form-control ltr" id="username" name="username" value="<?php echo e($me['username']); ?>" pattern="[a-zA-Z0-9_.-]{3,60}" required></div>
        <div class="form-group"><label>الصلاحية</label><input class="form-control" value="<?php echo $me['role'] === 'admin' ? 'مدير نظام' : 'محرر محتوى'; ?>" disabled></div>
      </div>
      <div class="form-actions"><div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> حفظ البيانات</button></div></div>
    </form>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>تغيير كلمة المرور</h2></div>
    <form method="post" action="profile.php" data-loading>
      <?php echo csrf_field(); ?><input type="hidden" name="action" value="password">
      <div class="panel-body">
        <div class="form-group"><label for="current_password">كلمة المرور الحالية</label><input class="form-control ltr" type="password" id="current_password" name="current_password" autocomplete="current-password" required></div>
        <div class="form-group"><label for="new_password">كلمة المرور الجديدة</label><input class="form-control ltr" type="password" id="new_password" name="new_password" autocomplete="new-password" minlength="12" required></div>
        <div class="form-group"><label for="new_password_confirm">تأكيد كلمة المرور الجديدة</label><input class="form-control ltr" type="password" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password" minlength="12" required></div>
        <p class="help-box" style="margin:0"><b>نصيحة أمان:</b> استخدم كلمة مرور فريدة لا تقل عن 12 حرفًا مع أرقام ورموز.</p>
      </div>
      <div class="form-actions"><div class="right"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-key"></i> تحديث كلمة المرور</button></div></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
