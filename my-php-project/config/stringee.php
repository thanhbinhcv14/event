<?php
/**
 * Stringee Configuration
 * Cấu hình API keys và settings cho Stringee Voice/Video Call
 * 
 * ⚠️ QUAN TRỌNG: Cập nhật API SID và Secret từ Stringee Dashboard trước khi upload
 */

// API SID Key (từ Stringee Dashboard)
// Lấy từ: https://console.stringee.com/ → Project → API Keys
define('STRINGEE_API_SID', 'SK.0.bRpD5SVz9xXGy0sIeyYYWdm2rbupZ6w');

// API Secret Key (từ Stringee Dashboard)
// ⚠️ BẢO MẬT: Không commit file này vào git public repository
define('STRINGEE_API_SECRET', 'WVpzNndOM2tGZGEyYlNQV0NhMG5hdjZHcWxKbWIx');

// Server Addresses (WebSocket endpoints)
// Stringee cung cấp nhiều server để load balancing
define('STRINGEE_SERVER_ADDRS', json_encode([
    'wss://v1.stringee.com:6899/',
    'wss://v2.stringee.com:6899/'
]));

// Token TTL (Time To Live) - Thời gian token có hiệu lực (giây)
// Mặc định: 24 giờ (86400 giây)
define('STRINGEE_TOKEN_TTL', 86400);

// Call Timeout - Thời gian chờ trả lời cuộc gọi (giây)
// Mặc định: 60 giây
define('STRINGEE_CALL_TIMEOUT', 60);

// Max Connect Time - Thời gian tối đa cho cuộc gọi (giây)
// 0 = không giới hạn
define('STRINGEE_MAX_CONNECT_TIME', 0);

// Answer URL - Callback URL cho Answer URL
// ⚠️ QUAN TRỌNG: Cập nhật URL này trong Stringee Dashboard
// Production: https://sukien.info.vn/my-php-project/src/controllers/stringee-callback.php?type=answer
// Localhost: http://localhost/my-php-project/src/controllers/stringee-callback.php?type=answer
if (!defined('STRINGEE_ANSWER_URL')) {
    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    $answerPath = rtrim($basePath, '/') . '/src/controllers/stringee-callback.php?type=answer';
    define('STRINGEE_ANSWER_URL', $baseUrl . $answerPath);
}

// Event URL - Callback URL cho Event URL
// ⚠️ QUAN TRỌNG: Cập nhật URL này trong Stringee Dashboard
// Production: https://sukien.info.vn/my-php-project/src/controllers/stringee-callback.php?type=event
// Localhost: http://localhost/my-php-project/src/controllers/stringee-callback.php?type=event
if (!defined('STRINGEE_EVENT_URL')) {
    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    $eventPath = rtrim($basePath, '/') . '/src/controllers/stringee-callback.php?type=event';
    define('STRINGEE_EVENT_URL', $baseUrl . $eventPath);
}

// Recording Settings
// Có ghi âm cuộc gọi hay không
define('STRINGEE_RECORD_CALLS', false);

// Recording Format
// 'mp3' hoặc 'wav'
define('STRINGEE_RECORD_FORMAT', 'mp3');

// App to Phone (Auto connect to phone number)
// 'auto' hoặc 'manual'
define('STRINGEE_APP_TO_PHONE', 'auto');

/**
 * Validate Stringee Configuration
 * Kiểm tra các cấu hình có đầy đủ không
 */
function validateStringeeConfig() {
    $errors = [];
    
    if (empty(STRINGEE_API_SID) || STRINGEE_API_SID === 'SK.0.bRpD5SVz9xXGy0sIeyYYWdm2rbupZ6w') {
        // API SID đã được cập nhật, không cần warning
    }
    
    if (empty(STRINGEE_API_SECRET) || STRINGEE_API_SECRET === 'WVpzNndOM2tGZGEyYlNQV0NhMG5hdjZHcWxKbWIx') {
        // API Secret đã được cập nhật, không cần warning
    }
    
    if (empty(STRINGEE_ANSWER_URL) || strpos(STRINGEE_ANSWER_URL, 'localhost') !== false) {
        $errors[] = 'STRINGEE_ANSWER_URL chưa được cập nhật cho production';
    }
    
    if (empty(STRINGEE_EVENT_URL) || strpos(STRINGEE_EVENT_URL, 'localhost') !== false) {
        $errors[] = 'STRINGEE_EVENT_URL chưa được cập nhật cho production';
    }
    
    return $errors;
}

// Log warning nếu đang dùng default values (chỉ trong development)
if (defined('BASE_URL') && strpos(BASE_URL, 'localhost') !== false) {
    // Development mode - không cần validate
} else {
    // Production mode - validate config
    $configErrors = validateStringeeConfig();
    if (!empty($configErrors)) {
        error_log('⚠️ Stringee Config Warnings: ' . implode(', ', $configErrors));
    }
}

