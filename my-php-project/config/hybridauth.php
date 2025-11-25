<?php
// Cấu hình Hybridauth cho Social Login (Facebook, Google)
// File này được sử dụng bởi social-login.php và social-callback.php

// Tải config chính để lấy BASE_URL
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/config.php';
}

// Xác định callback URL
// Hybridauth sẽ tự động redirect về file này sau khi xác thực
$callbackUrl = BASE_URL . '/social-callback.php';

// Lấy App ID và Secret từ environment variables hoặc .env file
// Nếu không có trong .env, sử dụng giá trị mặc định
$facebookAppId = $_ENV['FACEBOOK_APP_ID'] ?? '877436944712009';
$facebookAppSecret = $_ENV['FACEBOOK_APP_SECRET'] ?? '6fa5e91f4728125a3ae618dfc86b725a';
$googleClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$googleClientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';

// Cấu hình Hybridauth
return [
    'callback' => $callbackUrl,
    'providers' => [
        // Facebook dùng JavaScript SDK, tắt Hybridauth cho Facebook
        'Facebook' => [
            'enabled' => false, // Dùng Facebook JavaScript SDK thay thế
            'keys' => [
                'id' => $facebookAppId,
                'secret' => $facebookAppSecret
            ],
            'scope' => 'public_profile,email',
            'redirect_uri' => $callbackUrl
        ],
        // Google vẫn dùng Hybridauth
        'Google' => [
            'enabled' => !empty($googleClientId) && !empty($googleClientSecret),
            'keys' => [
                'id' => $googleClientId,
                'secret' => $googleClientSecret
            ],
            'scope' => 'email profile',
            'redirect_uri' => $callbackUrl
        ]
    ],
    // Debug mode (tắt trong production)
    'debug_mode' => false,
    'debug_file' => __DIR__ . '/../logs/hybridauth.log'
];

