<?php
/**
 * BlueDesk configuration TEMPLATE.
 * Copy this file to config.php on your server and fill in real values.
 * Never commit production credentials.
 */
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

return [
    'app' => [
        'name'        => 'BlueDesk',
        'base_url'    => 'https://www.bluedeskgroup.net',
        'debug'       => false,
        'force_https' => true,
        'timezone'    => 'Africa/Cairo',
        'upload_dir'  => APP_ROOT . '/uploads',
        'upload_url'  => '/uploads',
        'max_upload'  => 4 * 1024 * 1024,
        'max_video_upload' => 35 * 1024 * 1024,
        'session_name'=> 'bluedesk_sess',
        'trust_proxy_headers' => false, // Set true only behind a trusted proxy that overwrites X-Forwarded-Proto.
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => '3306',
        'name'    => 'bluedesk',
        'user'    => 'CHANGE_ME',
        'pass'    => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],
];
