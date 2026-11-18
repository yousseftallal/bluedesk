<?php
/**
 * Admin logout (POST + CSRF only).
 */
require_once __DIR__ . '/../includes/functions.php';

if (!is_post() || !verify_csrf()) {
    redirect('index.php');
}

$user = current_user();
if ($user) {
    log_activity('تسجيل خروج', 'تم تسجيل الخروج بواسطة ' . $user['username']);
}
logout_user();
redirect('login.php?loggedout=1');
