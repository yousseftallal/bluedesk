<?php
/**
 * Session + authentication helpers.
 */
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$isAdminRequest = preg_match('#(?:^|/)admin(?:/|$)#i', $requestPath) === 1;
if ($isAdminRequest && !headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
}

function request_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || ((bool) app_config('app.trust_proxy_headers') && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
}

if ((bool) app_config('app.force_https') && !request_is_https()) {
    $base = parse_url((string) app_config('app.base_url'));
    if (is_array($base) && strtolower($base['scheme'] ?? '') === 'https' && !empty($base['host'])) {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        if ($requestUri === '' || $requestUri[0] !== '/' || str_starts_with($requestUri, '//') || preg_match('/[\r\n]/', $requestUri)) {
            $requestUri = '/';
        }
        header('Location: https://' . $base['host'] . $requestUri, true, 302);
        exit;
    }
    http_response_code(503);
    exit('HTTPS must be configured before serving this site.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = request_is_https();

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_name((string) (app_config('app.session_name') ?: 'bluedesk_sess'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if ($isAdminRequest && !headers_sent()) {
    header('Cache-Control: no-store, private', true);
}

// Expire authenticated sessions after 30 minutes without an HTTP request.
if (!empty($_SESSION['uid'])) {
    $now = time();
    $lastActivity = (int) ($_SESSION['last_activity'] ?? $now);
    if ($now - $lastActivity > 1800) {
        logout_user();
    } else {
        $_SESSION['last_activity'] = $now;
    }
}

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $uid = (int) ($_SESSION['uid'] ?? 0);
    if ($uid <= 0) {
        return $user = null;
    }
    try {
        $row = db_fetch('SELECT id, name, email, username, role, is_active, last_login, session_version FROM users WHERE id = ?', [$uid]);
    } catch (Throwable $ex) {
        $row = null;
    }
    if (!$row || !(int) $row['is_active']) {
        return $user = null;
    }
    if (!isset($_SESSION['auth_version']) || (int) $_SESSION['auth_version'] !== (int) $row['session_version']) {
        logout_user();
        return $user = null;
    }
    return $user = $row;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        $next = urlencode($_SERVER['REQUEST_URI'] ?? '/admin/index.php');
        header('Location: login.php?next=' . $next);
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('غير مصرح لك بالوصول إلى هذه الصفحة.');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
    $_SESSION['auth_version'] = (int) ($user['session_version'] ?? 0);
    $_SESSION['last_activity'] = time();
    unset($_SESSION['csrf_token']);
    csrf_token();
    try {
        db_query('UPDATE users SET last_login = NOW() WHERE id = ?', [$user['id']]);
    } catch (Throwable $ex) {
        error_log('last_login update failed: ' . $ex->getMessage());
    }
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/** Login rate limiting: max 5 failed attempts per IP in 15 minutes. */
function login_blocked(string $ip): bool
{
    $row = db_fetch(
        'SELECT COUNT(*) AS c FROM login_attempts
         WHERE ip = ? AND success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)',
        [$ip]
    );
    return ((int) ($row['c'] ?? 0)) >= 5;
}

function record_login_attempt(string $ip, string $username, bool $success): void
{
    try {
        db_query(
            'INSERT INTO login_attempts (ip, username, success) VALUES (?, ?, ?)',
            [$ip, mb_substr($username, 0, 120), $success ? 1 : 0]
        );
    } catch (Throwable $ex) {
        error_log('login_attempts insert failed: ' . $ex->getMessage());
    }
}
