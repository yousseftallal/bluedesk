<?php
/**
 * Admin login.
 */
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$username = '';

if (is_post()) {
    if (!verify_csrf()) {
        $error = 'انتهت صلاحية الجلسة. من فضلك أعد تحميل الصفحة وحاول مرة أخرى.';
    } else {
        $username = input('username');
        $password = (string) ($_POST['password'] ?? '');
        $ip = client_ip();

        if (login_blocked($ip)) {
            $error = 'محاولات كثيرة فاشلة. يرجى المحاولة بعد 15 دقيقة.';
        } elseif ($username === '' || $password === '') {
            $error = 'من فضلك أدخل اسم المستخدم وكلمة المرور.';
        } else {
            usleep(400000);
            $user = db_fetch('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1', [$username, $username]);
            if ($user && password_verify($password, $user['password_hash'])) {
                if (!(int) $user['is_active']) {
                    $error = 'هذا الحساب موقوف. تواصل مع مدير النظام.';
                    record_login_attempt($ip, $username, false);
                } else {
                    record_login_attempt($ip, $username, true);
                    login_user($user);
                    log_activity('تسجيل دخول', 'تم تسجيل الدخول من ' . $ip);
                    flash('success', 'أهلًا بك ' . $user['name'] . '! تم تسجيل الدخول بنجاح.');
                    redirect('index.php');
                }
            } else {
                record_login_attempt($ip, $username, false);
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>تسجيل الدخول — <?php echo e(setting('site_name', 'BlueDesk')); ?> Admin</title>
<link rel="icon" href="<?php echo e(asset(setting('favicon', 'assets/favicon.svg'))); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <img src="<?php echo e(asset(setting('logo', 'assets/logo.svg'))); ?>" alt="<?php echo e(setting('site_name', 'BlueDesk')); ?>">
    </div>
    <h1>لوحة تحكم بلوديسك</h1>
    <p class="sub">سجّل الدخول لإدارة محتوى الموقع</p>

    <?php if ($error !== ''): ?>
      <div class="login-error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" data-loading>
      <?php echo csrf_field(); ?>
      <div class="form-group">
        <label for="username">اسم المستخدم أو البريد الإلكتروني</label>
        <input class="form-control" type="text" id="username" name="username" value="<?php echo e($username); ?>" autocomplete="username" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">كلمة المرور</label>
        <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary btn-block" type="submit">
        <i class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول
      </button>
    </form>

    <p class="login-foot">
      <i class="fa-solid fa-shield-halved"></i>
      محمي بتقنيات التشفير — <a href="<?php echo e(url('index.php')); ?>" style="color:var(--blue-dark);font-weight:700">العودة للموقع</a>
    </p>
  </div>
</div>
<script src="assets/admin.js"></script>
</body>
</html>
