<?php
/**
 * Permanent redirects for legacy .html URLs → PHP pages.
 */
$map = [
    'index'     => 'index.php',
    'about'     => 'about.php',
    'services'  => 'services.php',
    'portfolio' => 'portfolio.php',
    'contact'   => 'contact.php',
];
$key = isset($_GET['p']) ? (string) $_GET['p'] : '';
if (isset($map[$key])) {
    header('Location: ' . $map[$key], true, 301);
    exit;
}
http_response_code(404);
exit('الصفحة غير موجودة.');
