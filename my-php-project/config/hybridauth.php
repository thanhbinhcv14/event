<?php
// HybridAuth Configuration
// Đọc từ .env file để bảo mật

// Load .env nếu chưa load
if (!isset($_ENV['GOOGLE_CLIENT_ID'])) {
    require_once __DIR__ . '/config.php';
}

// Auto-detect callback URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($basePath, '/');
$callbackUrl = $_ENV['SOCIAL_CALLBACK_URL'] ?? $protocol . '://' . $host . $basePath . '/social-callback.php';

return [
    'callback' => $callbackUrl,
    'providers' => [
        'Google' => [
            'enabled' => !empty($_ENV['GOOGLE_CLIENT_ID'] ?? ''),
            'keys'    => [
                'id'     => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
                'secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? ''
            ],
            'scope'   => 'email profile'
        ],
        'Facebook' => [
            'enabled' => !empty($_ENV['FACEBOOK_APP_ID'] ?? ''),
            'keys'    => [
                'id'     => $_ENV['FACEBOOK_APP_ID'] ?? '',
                'secret' => $_ENV['FACEBOOK_APP_SECRET'] ?? ''
            ],
            'scope'   => 'email'
        ]
    ]
];