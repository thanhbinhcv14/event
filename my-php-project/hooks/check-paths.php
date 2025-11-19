<?php
/**
 * Kiểm tra đường dẫn và cấu hình cho hosting sukien.info.vn
 * File này giúp xác minh tất cả đường dẫn đều đúng
 */

echo "<h1>Kiểm tra Cấu trúc và Đường dẫn</h1>";
echo "<pre>";

// 1. Kiểm tra đường dẫn hiện tại
echo "=== 1. Đường dẫn hiện tại ===\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "__FILE__: " . __FILE__ . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "\n";

// 2. Kiểm tra các file config
echo "=== 2. Kiểm tra file config ===\n";
$configFiles = [
    'database.php' => __DIR__ . '/../config/database.php',
    'sepay.php' => __DIR__ . '/../config/sepay.php',
    'config.php' => __DIR__ . '/../config/config.php',
];

foreach ($configFiles as $name => $path) {
    if (file_exists($path)) {
        echo "✓ {$name}: Tồn tại tại {$path}\n";
    } else {
        echo "✗ {$name}: KHÔNG TỒN TẠI tại {$path}\n";
    }
}
echo "\n";

// 3. Kiểm tra thư mục log
echo "=== 3. Kiểm tra thư mục log ===\n";
$logDir = __DIR__;
$logFile = $logDir . '/hook_log.txt';
echo "Thư mục log: {$logDir}\n";
echo "File log: {$logFile}\n";
if (is_writable($logDir)) {
    echo "✓ Thư mục log: Có thể ghi\n";
} else {
    echo "✗ Thư mục log: KHÔNG thể ghi (quyền: " . substr(sprintf('%o', fileperms($logDir)), -4) . ")\n";
}
echo "\n";

// 4. Kiểm tra database connection
echo "=== 4. Kiểm tra kết nối database ===\n";
try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();
    echo "✓ Kết nối database: THÀNH CÔNG\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM thanhtoan");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Số lượng payment trong DB: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "✗ Kết nối database: THẤT BẠI - " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Kiểm tra SePay config
echo "=== 5. Kiểm tra SePay config ===\n";
try {
    require_once __DIR__ . '/../config/sepay.php';
    echo "✓ SEPAY_CALLBACK_URL: " . (defined('SEPAY_CALLBACK_URL') ? SEPAY_CALLBACK_URL : 'N/A') . "\n";
    echo "✓ SEPAY_MATCH_PATTERN: " . (defined('SEPAY_MATCH_PATTERN') ? SEPAY_MATCH_PATTERN : 'N/A') . "\n";
    echo "✓ SEPAY_WEBHOOK_TOKEN: " . (defined('SEPAY_WEBHOOK_TOKEN') ? substr(SEPAY_WEBHOOK_TOKEN, 0, 20) . '...' : 'N/A') . "\n";
} catch (Exception $e) {
    echo "✗ Load SePay config: THẤT BẠI - " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Kiểm tra webhook URL
echo "=== 6. Kiểm tra Webhook URL ===\n";
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'sukien.info.vn';
$webhookUrl = $protocol . '://' . $host . '/hooks/sepay-payment.php';
echo "Webhook URL hiện tại: {$webhookUrl}\n";
echo "Webhook URL mong đợi: https://sukien.info.vn/hooks/sepay-payment.php\n";
if ($webhookUrl === 'https://sukien.info.vn/hooks/sepay-payment.php') {
    echo "✓ Webhook URL: ĐÚNG\n";
} else {
    echo "⚠ Webhook URL: KHÁC (có thể do đang test local)\n";
}
echo "\n";

// 7. Kiểm tra .htaccess
echo "=== 7. Kiểm tra .htaccess ===\n";
$htaccessFile = __DIR__ . '/.htaccess';
if (file_exists($htaccessFile)) {
    echo "✓ .htaccess trong hooks: Tồn tại\n";
    $htaccessContent = file_get_contents($htaccessFile);
    if (strpos($htaccessContent, 'Authorization') !== false) {
        echo "✓ .htaccess: Có cấu hình Authorization header\n";
    } else {
        echo "⚠ .htaccess: KHÔNG có cấu hình Authorization header\n";
    }
} else {
    echo "✗ .htaccess trong hooks: KHÔNG tồn tại\n";
}
echo "\n";

// 8. Kiểm tra quyền truy cập webhook
echo "=== 8. Kiểm tra quyền truy cập ===\n";
$webhookFile = __DIR__ . '/sepay-payment.php';
if (file_exists($webhookFile)) {
    echo "✓ sepay-payment.php: Tồn tại\n";
    if (is_readable($webhookFile)) {
        echo "✓ sepay-payment.php: Có thể đọc\n";
    } else {
        echo "✗ sepay-payment.php: KHÔNG thể đọc\n";
    }
} else {
    echo "✗ sepay-payment.php: KHÔNG tồn tại\n";
}
echo "\n";

// 9. Kiểm tra cấu trúc thư mục
echo "=== 9. Cấu trúc thư mục ===\n";
$baseDir = dirname(__DIR__);
$dirs = ['config', 'database', 'hooks', 'payment', 'src'];
foreach ($dirs as $dir) {
    $path = $baseDir . '/' . $dir;
    if (is_dir($path)) {
        echo "✓ {$dir}/: Tồn tại\n";
    } else {
        echo "✗ {$dir}/: KHÔNG tồn tại\n";
    }
}
echo "\n";

echo "</pre>";
echo "<p><a href='sepay-payment.php?test=1'>Test Webhook Endpoint</a></p>";
?>

