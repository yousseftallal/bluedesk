<?php
/**
 * Create the first administrator or rotate an existing administrator password.
 * This utility is intentionally limited to command-line use.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';

function ask(string $prompt): string
{
    fwrite(STDOUT, $prompt);
    return trim((string) fgets(STDIN));
}

function temporary_password(): string
{
    return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
}

$admins = db_fetch_all("SELECT id, name, email, username FROM users WHERE role = 'admin' ORDER BY id");
$password = temporary_password();

if ($admins) {
    fwrite(STDOUT, "مديرون موجودون:\n");
    foreach ($admins as $admin) {
        fwrite(STDOUT, ' - ' . $admin['username'] . ' (' . $admin['email'] . ")\n");
    }
    $username = ask('اكتب اسم مستخدم المدير الذي تريد إعادة تعيين كلمة مروره (Enter للإلغاء): ');
    if ($username === '') {
        fwrite(STDOUT, "تم الإلغاء.\n");
        exit(0);
    }
    $admin = db_fetch("SELECT id FROM users WHERE username = ? AND role = 'admin'", [$username]);
    if (!$admin) {
        fwrite(STDERR, "لم يتم العثور على مدير بهذا الاسم.\n");
        exit(1);
    }
    db_query('UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
    db_query(
        'INSERT INTO activity_log (user_id, action, details, ip) VALUES (?, ?, ?, ?)',
        [$admin['id'], 'إعادة تعيين كلمة مرور', 'تمت إعادة كلمة المرور من أداة سطر الأوامر.', '127.0.0.1']
    );
    fwrite(STDOUT, "تم تغيير كلمة المرور. اسم المستخدم: {$username}\n");
} else {
    fwrite(STDOUT, "إنشاء حساب المدير الأول.\n");
    $name = ask('الاسم: ');
    $email = strtolower(ask('البريد الإلكتروني: '));
    $username = strtolower(ask('اسم المستخدم (3-60 حرفًا): '));

    if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || mb_strlen($email) > 190 || !preg_match('/^[a-z0-9_.-]{3,60}$/', $username)) {
        fwrite(STDERR, "البيانات غير صالحة؛ لم يتم إنشاء الحساب.\n");
        exit(1);
    }
    if (db_fetch('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1', [$email, $username])) {
        fwrite(STDERR, "البريد الإلكتروني أو اسم المستخدم مستخدم بالفعل.\n");
        exit(1);
    }

    db_query(
        'INSERT INTO users (name, email, username, password_hash, role, is_active) VALUES (?, ?, ?, ?, \'admin\', 1)',
        [$name, $email, $username, password_hash($password, PASSWORD_DEFAULT)]
    );
    $admin = db_fetch("SELECT id FROM users WHERE username = ? AND role = 'admin'", [$username]);
    if ($admin) {
        db_query(
            'INSERT INTO activity_log (user_id, action, details, ip) VALUES (?, ?, ?, ?)',
            [$admin['id'], 'إنشاء مدير', 'تم إنشاء حساب المدير من أداة سطر الأوامر.', '127.0.0.1']
        );
    }
    fwrite(STDOUT, "تم إنشاء حساب المدير. اسم المستخدم: {$username}\n");
}

fwrite(STDOUT, "كلمة المرور المؤقتة (تظهر مرة واحدة): {$password}\n");
fwrite(STDOUT, "احفظها ثم غيّرها فور تسجيل الدخول.\n");
