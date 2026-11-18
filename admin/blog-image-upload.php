<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['error' => 'طريقة الطلب غير مسموحة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!verify_csrf()) {
    http_response_code(403);
    echo json_encode(['error' => 'انتهت صلاحية الجلسة. حدّث الصفحة ثم أعد المحاولة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['image'] ?? null;
if (!is_array($file)) {
    http_response_code(400);
    echo json_encode(['error' => 'لم يتم استلام صورة صالحة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$error = '';
$imageUrl = upload_image($file, $error);
if ($imageUrl === null) {
    http_response_code(422);
    echo json_encode(['error' => $error ?: 'تعذر رفع الصورة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['url' => $imageUrl], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
