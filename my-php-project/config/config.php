<?php
// Tải file .env
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Đặt giá trị mặc định nếu file .env không tồn tại
if (!file_exists(__DIR__ . '/../.env')) {
    $_ENV['DB_HOST'] = 'localhost';
    $_ENV['DB_NAME'] = 'event';
    $_ENV['DB_USER'] = 'root';
    $_ENV['DB_PASS'] = '';
    // Tạo JWT secret ngẫu nhiên an toàn nếu chưa tồn tại
    if (!isset($_ENV['JWT_SECRET'])) {
        $_ENV['JWT_SECRET'] = bin2hex(random_bytes(32));
    }
} else {
    loadEnv(__DIR__ . '/../.env');
}

// Định nghĩa các hằng số
define('JWT_SECRET', $_ENV['JWT_SECRET']);

// Gemini AI API Key
// Lấy từ environment variable hoặc dùng giá trị mặc định
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? 'AIzaSyAT4nAOSfEwiO-8DozSXwncJ1rIj-nmpVk');

// Email SMTP Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? 'thanhbinh14062003@gmail.com');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? 'fqawwkwjysmslukr'); // App Password từ Gmail (đã loại bỏ khoảng trắng)
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'thanhbinh14062003@gmail.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'Hệ thống sự kiện');
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls'); // tls hoặc ssl

// Cấu hình Base URL - Tự động phát hiện từ server
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);

// Xóa tên file nếu có
$basePath = str_replace(basename($scriptName), '', $basePath);
$basePath = rtrim($basePath, '/');

// Nếu basePath rỗng hoặc chỉ là '/', sử dụng root
if (empty($basePath) || $basePath === '/') {
    $basePath = '';
} else {
    $basePath = $basePath;
}

// Base URL (cho các lời gọi API)
define('BASE_URL', $protocol . '://' . $host . $basePath);

// Base Path (cho assets, đường dẫn tương đối)
define('BASE_PATH', $basePath);

// Session Security Configuration
// CHỈ start session nếu chưa được start (tránh conflict với các file khác)
if (session_status() === PHP_SESSION_NONE) {
    // Cấu hình session an toàn
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_lifetime', 0); // Until browser close
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    
    // Chỉ đặt cờ secure nếu đang dùng HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    // Thuộc tính SameSite cho cookie (PHP 7.3+)
    if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
        ini_set('session.cookie_samesite', 'Strict');
    }
    
    // Bắt đầu session với xử lý lỗi
    try {
        session_start();
    } catch (Exception $e) {
        error_log('Session start error in config.php: ' . $e->getMessage());
        // Không throw exception để tránh break các file khác
    }
}