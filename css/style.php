<?php
declare(strict_types=1);

$cssFile = __DIR__ . '/style.css';
if (!is_file($cssFile) || !is_readable($cssFile)) {
    http_response_code(404);
    exit;
}

$modified = filemtime($cssFile) ?: time();
$etag = 'W/"css-' . $modified . '-' . filesize($cssFile) . '"';
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=31536000, immutable');
header('ETag: ' . $etag);
header('Vary: Accept-Encoding');
if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
    http_response_code(304);
    exit;
}
$content = file_get_contents($cssFile);
if ($content === false) {
    http_response_code(500);
    exit;
}
$acceptsGzip = preg_match('/(?:^|,)\s*gzip\s*(?:;\s*q=([0-9.]+))?/i', (string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''), $gzipMatch) === 1
    && (!isset($gzipMatch[1]) || (float) $gzipMatch[1] > 0);
if ($acceptsGzip && function_exists('gzencode') && !ini_get('zlib.output_compression')) {
    $compressed = gzencode($content, 6);
    if ($compressed !== false) {
        header('Content-Encoding: gzip');
        echo $compressed;
        exit;
    }
}
echo $content;
