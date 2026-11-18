<?php
/**
 * One-time CLI helper: build WebP and responsive variants for existing uploads.
 * Usage: php scripts/generate-upload-image-variants.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/includes/functions.php';

$uploadDir = (string) app_config('app.upload_dir');
$files = array_merge(
    glob($uploadDir . '/*.jpg') ?: [],
    glob($uploadDir . '/*.jpeg') ?: [],
    glob($uploadDir . '/*.png') ?: [],
    glob($uploadDir . '/*.gif') ?: [],
    glob($uploadDir . '/*.webp') ?: []
);
$optimized = 0;
$skipped = 0;

foreach ($files as $file) {
    $filename = basename($file);
    if (preg_match('/-\d+w\.webp$/i', $filename)) {
        continue;
    }
    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'webp') {
        $stem = pathinfo($filename, PATHINFO_FILENAME);
        if (is_file($uploadDir . '/' . $stem . '.jpg') || is_file($uploadDir . '/' . $stem . '.jpeg') || is_file($uploadDir . '/' . $stem . '.png') || is_file($uploadDir . '/' . $stem . '.gif')) {
            continue; // The source file is processed once; do not re-encode its WebP copy.
        }
    }
    $info = @getimagesize($file);
    $mime = $info['mime'] ?? '';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
        $skipped++;
        continue;
    }

    $converted = generate_upload_webp_variants(basename($file), $file, $mime);
    $webp = preg_replace('/\.(?:jpe?g|png|gif)$/i', '.webp', $file);
    if ($converted && $webp && is_file($webp) && (getimagesize($webp)['mime'] ?? '') === 'image/webp') {
        $optimized++;
    } else {
        $skipped++;
    }
}

echo 'WebP copies generated: ' . $optimized . '; unchanged or unsupported: ' . $skipped . PHP_EOL;
