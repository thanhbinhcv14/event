<?php
/**
 * SePay Webhook Handler
 * Xử lý thông báo webhook từ SePay khi nhận được tiền
 * Dựa trên tài liệu SePay Laravel Package: https://github.com/sepayvn/laravel-sepay
 */

// ✅ Chỉ log POST requests (webhook thật từ SePay), bỏ qua GET requests (từ browser)
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$isTestMode = isset($_GET['test']) && $_GET['test'] === '1';

// Chỉ log POST requests hoặc GET với ?test=1
if ($requestMethod === 'POST' || ($requestMethod === 'GET' && $isTestMode)) {
// Ghi log debug - Ghi log JSON input ngay từ đầu
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$logEntry = "\n" . str_repeat("=", 80) . "\n";
$logEntry .= "SePay Webhook Received: " . date('Y-m-d H:i:s') . "\n";
    $logEntry .= "Request Method: " . $requestMethod . "\n";
$logEntry .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'UNKNOWN') . "\n";
$logEntry .= "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'UNKNOWN') . "\n";
$logEntry .= "Authorization Header: " . (isset($_SERVER['HTTP_AUTHORIZATION']) ? substr($_SERVER['HTTP_AUTHORIZATION'], 0, 30) . '...' : 'NOT SET') . "\n";
$logEntry .= "Raw Input Length: " . strlen($rawInput) . " bytes\n";
$logEntry .= "Raw Input (first 500 chars): " . substr($rawInput, 0, 500) . "\n";
$logEntry .= "Decoded JSON: " . print_r($data, true) . "\n";
$logEntry .= str_repeat("=", 80) . "\n";
file_put_contents(__DIR__ . '/hook_log.txt', $logEntry, FILE_APPEND);
error_log("SePay Webhook - Raw input logged to hook_log.txt");
} else {
    // GET request không phải test mode - không log để tránh spam
    $rawInput = '';
    $data = null;
}

// Đặt content type là JSON trước (trước khi có bất kỳ output nào)
header('Content-Type: application/json');

// Cho phép CORS cho SePay webhook (nếu cần)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Xử lý request OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sepay.php';

// Ghi log webhook đến
error_log("SePay Webhook received at: " . date('Y-m-d H:i:s'));

// Xác thực token webhook
function verifyWebhookToken() {
    // ✅ Lấy Authorization header từ nhiều nguồn (tùy thuộc vào server config)
    $authHeader = '';
    $allHeaders = [];
    
    // ✅ Ưu tiên 1: getallheaders() - hoạt động tốt nhất trên hầu hết server
    if (function_exists('getallheaders')) {
        $allHeaders = getallheaders();
        if (isset($allHeaders['Authorization'])) {
            $authHeader = $allHeaders['Authorization'];
        } elseif (isset($allHeaders['authorization'])) {
            $authHeader = $allHeaders['authorization'];
        }
    }
    
    // ✅ Ưu tiên 2: apache_request_headers() - cho Apache
    if (empty($authHeader) && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }
    }
    
    // ✅ Ưu tiên 3: $_SERVER['HTTP_AUTHORIZATION'] - standard PHP
    if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    
    // ✅ Ưu tiên 4: $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] - Apache với mod_rewrite
    if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    
    // ✅ Ưu tiên 5: Kiểm tra tất cả $_SERVER keys có chứa AUTHORIZATION
    if (empty($authHeader)) {
        foreach ($_SERVER as $key => $value) {
            if (stripos($key, 'AUTHORIZATION') !== false && !empty($value)) {
                $authHeader = $value;
                break;
            }
        }
    }
    
    // Log chi tiết để debug
    $debugLog = "\n" . str_repeat("=", 80) . "\n";
    $debugLog .= "SePay Webhook Auth Debug: " . date('Y-m-d H:i:s') . "\n";
    $debugLog .= "HTTP_AUTHORIZATION: " . ($_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT SET') . "\n";
    $debugLog .= "REDIRECT_HTTP_AUTHORIZATION: " . ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'NOT SET') . "\n";
    $debugLog .= "All SERVER keys with 'AUTH': " . implode(', ', array_filter(array_keys($_SERVER), function($k) { return stripos($k, 'AUTH') !== false; })) . "\n";
    $debugLog .= "getallheaders() available: " . (function_exists('getallheaders') ? 'YES' : 'NO') . "\n";
    $debugLog .= "apache_request_headers() available: " . (function_exists('apache_request_headers') ? 'YES' : 'NO') . "\n";
    if (function_exists('getallheaders')) {
        $debugLog .= "getallheaders() result: " . print_r(getallheaders(), true) . "\n";
    }
    $debugLog .= "Auth header extracted: " . ($authHeader ? substr($authHeader, 0, 100) : 'EMPTY') . "\n";
    $debugLog .= str_repeat("=", 80) . "\n";
    file_put_contents(__DIR__ . '/hook_log.txt', $debugLog, FILE_APPEND);
    error_log("SePay Webhook Auth Debug logged to hook_log.txt");
    
    $token = '';
    
    // ✅ SePay gửi với format: Authorization: Apikey {API_KEY}
    // Pattern: "Apikey" (case-insensitive) + whitespace + token
    if (!empty($authHeader)) {
        // Thử pattern chính xác: Apikey {token}
        if (preg_match('/Apikey\s+(.+)$/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
            error_log("SePay Webhook: Token extracted from 'Apikey' format (length: " . strlen($token) . ")");
    } 
    // Dự phòng: Kiểm tra Bearer token
        elseif (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
            error_log("SePay Webhook: Token extracted from 'Bearer' format (length: " . strlen($token) . ")");
        }
        // Dự phòng: Nếu không có prefix, có thể token là toàn bộ header
        elseif (preg_match('/^[A-Z0-9]{20,}$/i', trim($authHeader))) {
            $token = trim($authHeader);
            error_log("SePay Webhook: Token extracted as raw header (length: " . strlen($token) . ")");
        }
    }
    
    // ✅ Dự phòng: Kiểm tra custom header X-SePay-Token
    if (empty($token)) {
        if (isset($_SERVER['HTTP_X_SEPAY_TOKEN'])) {
            $token = $_SERVER['HTTP_X_SEPAY_TOKEN'];
            error_log("SePay Webhook: Token extracted from X-SePay-Token header");
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['X-SePay-Token'])) {
                $token = $headers['X-SePay-Token'];
                error_log("SePay Webhook: Token extracted from X-SePay-Token in getallheaders()");
            } elseif (isset($headers['x-sepay-token'])) {
                $token = $headers['x-sepay-token'];
                error_log("SePay Webhook: Token extracted from x-sepay-token in getallheaders()");
            }
        }
    }
    
    // ✅ Hỗ trợ cả API Token và Secret Key từ IPN config
    $expectedApiToken = defined('SEPAY_WEBHOOK_TOKEN') ? SEPAY_WEBHOOK_TOKEN : '';
    $expectedSecretKey = defined('SEPAY_IPN_SECRET_KEY') ? SEPAY_IPN_SECRET_KEY : '';
    
    if (empty($expectedApiToken) && empty($expectedSecretKey)) {
        error_log("SePay Webhook Warning: Neither SEPAY_WEBHOOK_TOKEN nor SEPAY_IPN_SECRET_KEY configured");
        // Trong production, nên reject nếu token không được cấu hình
        return false;
    }
    
    if (empty($token)) {
        error_log("SePay Webhook: No authentication token provided");
        error_log("SePay Webhook: Auth header was: " . ($authHeader ? substr($authHeader, 0, 100) : 'EMPTY'));
        return false;
    }
    
    // ✅ So sánh token với cả API Token và Secret Key
    $tokenMatched = false;
    
    // Thử với API Token trước (ưu tiên)
    if (!empty($expectedApiToken) && $token === $expectedApiToken) {
        $tokenMatched = true;
        error_log("SePay Webhook: Token matched with API Token");
    }
    // Thử với Secret Key (nếu có)
    elseif (!empty($expectedSecretKey) && $token === $expectedSecretKey) {
        $tokenMatched = true;
        error_log("SePay Webhook: Token matched with Secret Key from IPN config");
    }
    
    if (!$tokenMatched) {
        error_log("SePay Webhook: Invalid token.");
        if (!empty($expectedApiToken)) {
            error_log("SePay Webhook: Expected API Token (first 20 chars): " . substr($expectedApiToken, 0, 20));
        }
        if (!empty($expectedSecretKey)) {
            error_log("SePay Webhook: Expected Secret Key (first 20 chars): " . substr($expectedSecretKey, 0, 20));
        }
        error_log("SePay Webhook: Got (first 20 chars): " . substr($token, 0, 20));
        error_log("SePay Webhook: Got length: " . strlen($token));
        return false;
    }
    
    error_log("SePay Webhook: Token verified successfully");
    return true;
}

try {
    // Xác thực webhook
    // Cho phép chế độ test qua GET parameter (chỉ để debug - xóa trong production)
    $isTestMode = isset($_GET['test']) && $_GET['test'] === '1';
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
    
    // ✅ Nếu là GET request (từ browser), chỉ cho phép test mode
    if ($requestMethod === 'GET' && !$isTestMode) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: GET requests not allowed',
            'message' => 'This endpoint only accepts POST requests from SePay with Authorization header.',
            'hint' => 'For testing, add ?test=1 to URL (development only)',
            'request_method' => $requestMethod,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ✅ Xác thực token cho POST requests (từ SePay)
    if (!$isTestMode && !verifyWebhookToken()) {
        // Log chi tiết để debug
        $debugInfo = [
            'request_method' => $requestMethod,
            'has_authorization' => isset($_SERVER['HTTP_AUTHORIZATION']),
            'authorization_preview' => isset($_SERVER['HTTP_AUTHORIZATION']) ? substr($_SERVER['HTTP_AUTHORIZATION'], 0, 30) . '...' : 'NOT SET',
            'all_headers' => function_exists('getallheaders') ? getallheaders() : 'getallheaders() not available',
            'expected_token_length' => defined('SEPAY_WEBHOOK_TOKEN') ? strlen(SEPAY_WEBHOOK_TOKEN) : 0,
            'expected_token_preview' => defined('SEPAY_WEBHOOK_TOKEN') ? substr(SEPAY_WEBHOOK_TOKEN, 0, 20) . '...' : 'NOT CONFIGURED'
        ];
        
        error_log("SePay Webhook Auth Failed - Debug Info: " . json_encode($debugInfo, JSON_PRETTY_PRINT));
        
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: Invalid webhook token',
            'message' => 'This endpoint requires Authorization header with API key. SePay will send POST requests with proper authentication.',
            'hint' => 'For testing, add ?test=1 to URL (development only)',
            'debug' => $debugInfo,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Chế độ test - trả về thông tin mà không xử lý
    if ($isTestMode) {
        echo json_encode([
            'success' => true,
            'message' => 'Webhook endpoint is accessible (TEST MODE)',
            'warning' => 'This is test mode. Real webhooks from SePay will be POST requests with Authorization header.',
            'config' => [
                'webhook_token_configured' => defined('SEPAY_WEBHOOK_TOKEN') && !empty(SEPAY_WEBHOOK_TOKEN),
                'webhook_token_length' => defined('SEPAY_WEBHOOK_TOKEN') ? strlen(SEPAY_WEBHOOK_TOKEN) : 0,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'has_authorization' => isset($_SERVER['HTTP_AUTHORIZATION']),
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Lấy dữ liệu webhook (đã decode ở trên để log)
    $input = $rawInput; // Sử dụng input đã đọc
    // $data đã được decode ở trên để log
    
    if (!$data) {
        // Ghi log lỗi vào file
        $errorLog = "\n" . str_repeat("-", 80) . "\n";
        $errorLog .= "ERROR: Invalid webhook data - JSON decode failed\n";
        $errorLog .= "Raw input: " . $rawInput . "\n";
        $errorLog .= "JSON Error: " . json_last_error_msg() . "\n";
        $errorLog .= str_repeat("-", 80) . "\n";
        file_put_contents(__DIR__ . '/hook_log.txt', $errorLog, FILE_APPEND);
        
        throw new Exception('Invalid webhook data: JSON decode failed - ' . json_last_error_msg());
    }
    
    // Ghi log webhook vào database
    $pdo = getDBConnection();
    
    // Lưu log webhook (nếu bảng tồn tại)
    $webhookLogId = null;
    try {
    $stmt = $pdo->prepare("
        INSERT INTO webhook_logs (webhook_source, raw_data, processed, created_at) 
        VALUES ('sepay', ?, 0, NOW())
    ");
    $stmt->execute([$input]);
    $webhookLogId = $pdo->lastInsertId();
    } catch (Exception $e) {
        // Bảng webhook_logs có thể không tồn tại, chỉ log warning
        error_log("Warning: Could not save to webhook_logs: " . $e->getMessage());
    }
    
    // Ghi log dữ liệu webhook (đã làm sạch để bảo mật)
    $sanitizedData = $data;
    if (isset($sanitizedData['signature'])) {
        $sanitizedData['signature'] = '***HIDDEN***';
    }
    error_log("SePay Webhook data: " . json_encode($sanitizedData));
    
    // Trích xuất dữ liệu webhook theo format SePay
    // Format từ tài liệu SePay Laravel Package:
    // gateway, transactionDate, accountNumber, content, transferType, transferAmount, referenceCode, id
    $gateway = $data['gateway'] ?? '';
    $transactionDate = $data['transactionDate'] ?? '';
    $accountNumber = $data['accountNumber'] ?? '';
    $subAccount = $data['subAccount'] ?? null;
    $code = $data['code'] ?? null;
    $content = $data['content'] ?? '';
    $transferType = $data['transferType'] ?? ''; // 'in' (tiền vào) hoặc 'out' (tiền ra)
    $description = $data['description'] ?? '';
    $transferAmount = floatval($data['transferAmount'] ?? 0);
    $referenceCode = $data['referenceCode'] ?? '';
    $accumulated = floatval($data['accumulated'] ?? 0);
    $sepayTransactionId = $data['id'] ?? '';
    
    // Tính toán amount_in và amount_out
    $amount_in = 0;
    $amount_out = 0;
    if ($transferType == "in") {
        $amount_in = $transferAmount;
    } else if ($transferType == "out") {
        $amount_out = $transferAmount;
    }
    
    // Lưu tất cả giao dịch vào bảng tb_transactions (theo mẫu từ tài liệu SePay)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tb_transactions (
                gateway, transaction_date, account_number, sub_account,
                amount_in, amount_out, accumulated, code, transaction_content,
                reference_number, body, transfer_type, transfer_amount, sepay_transaction_id,
                processed, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([
            $gateway,
            $transactionDate ?: date('Y-m-d H:i:s'),
            $accountNumber,
            $subAccount,
            $amount_in,
            $amount_out,
            $accumulated,
            $code,
            $content,
            $referenceCode,
            $description,
            $transferType,
            $transferAmount,
            $sepayTransactionId
        ]);
        $transactionId = $pdo->lastInsertId();
        error_log("SePay transaction saved to tb_transactions with ID: " . $transactionId);
    } catch (Exception $e) {
        // Nếu bảng tb_transactions chưa tồn tại, chỉ log lỗi nhưng không dừng xử lý
        error_log("Warning: Could not save to tb_transactions: " . $e->getMessage());
    }
    
    // Chỉ xử lý giao dịch tiền vào
    if ($transferType !== 'in') {
        // Cập nhật log webhook (nếu có)
        if ($webhookLogId) {
            try {
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, response = ? WHERE id = ?");
        $stmt->execute([json_encode(['message' => 'Skipped: Not an incoming transfer']), $webhookLogId]);
            } catch (Exception $e) {
                error_log("Warning: Could not update webhook_logs: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Webhook received but skipped (not incoming transfer)',
            'transfer_type' => $transferType,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Kiểm tra các trường bắt buộc
    // Lưu ý: content có thể rỗng nếu SePay không nhận diện được code thanh toán
    // Trong trường hợp này, sẽ tìm payment bằng amount và thời gian
    if (empty($accountNumber) || $transferAmount <= 0) {
        throw new Exception('Missing required webhook data: accountNumber or transferAmount');
    }
    
    // Log cảnh báo nếu content rỗng
    if (empty($content)) {
        error_log("SePay Webhook Warning: Content is empty. This may happen if SePay cannot recognize payment code. Will try to find payment by amount and time.");
    }
    
    // Lấy pattern match từ config (mặc định: SEPAY)
    $matchPattern = defined('SEPAY_MATCH_PATTERN') ? SEPAY_MATCH_PATTERN : 'SEPAY';
    
    // Parse nội dung giao dịch để lấy payment ID
    // Format thực tế từ SePay có thể là:
    // 1. SEPAY + số (3-10 ký tự) - Ví dụ: SEPAY20123 (eventId=20, ID_ThanhToan=123)
    // 2. SEVQR hoặc SEVOR (format mặc định của SePay)
    // 3. CT DEN:... ZP... ... SEVQR (format đầy đủ từ SePay)
    // 4. ZP{number} (mã giao dịch từ SePay)
    // 5. SK{eventId}_{paymentId} (format cũ)
    // 6. SEPAY_{timestamp}_{random} (format cũ)
    
    $paymentId = null;
    $eventId = null;
    
    // Log content để debug
    error_log("SePay Webhook: Parsing content: " . $content);
    error_log("SePay Webhook: Amount: " . $transferAmount);
    error_log("SePay Webhook: Transaction Date: " . $transactionDate);
    
    // ✅ Pattern 1: SEPAY + số (3-10 ký tự) - Format: SEPAY{suffix}
    // Ví dụ: SEPAY20123, SEPAY123456
    // Tìm payment record dựa trên content (lưu trong GhiChu hoặc tìm theo pattern)
    if (preg_match('/SEPAY(\d{3,10})(?:\s|$|_|\.)/', $content, $matches)) {
        $suffix = $matches[1];
        $searchContent = 'SEPAY' . $suffix;
        
        // Tìm payment record dựa trên content trong GhiChu hoặc MaGiaoDich
        // Hoặc tìm trong bảng thanhtoan với điều kiện content khớp
        $stmt = $pdo->prepare("
            SELECT ID_ThanhToan, ID_DatLich, MaGiaoDich, GhiChu
            FROM thanhtoan 
            WHERE GhiChu LIKE ? OR MaGiaoDich LIKE ?
            ORDER BY ID_ThanhToan DESC 
            LIMIT 10
        ");
        $stmt->execute(['%' . $searchContent . '%', '%' . $searchContent . '%']);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tìm payment record gần nhất có content khớp
        foreach ($payments as $payment) {
            // Kiểm tra xem content có trong GhiChu không
            if (strpos($payment['GhiChu'], $searchContent) !== false || 
                strpos($payment['MaGiaoDich'], $searchContent) !== false) {
                $paymentId = $payment['MaGiaoDich'];
                $eventId = $payment['ID_DatLich'];
                break;
            }
        }
        
        // Nếu không tìm thấy, thử parse suffix để tìm eventId và ID_ThanhToan
        if (empty($paymentId) && strlen($suffix) >= 3) {
            error_log("SePay Webhook: Trying to parse suffix: " . $suffix);
            // Thử parse: eventId có thể là 1-4 chữ số, phần còn lại là ID_ThanhToan
            // Ví dụ: "2221" → eventId=22, ID_ThanhToan=21
            // Hoặc: "20123" → eventId=20, ID_ThanhToan=123
            $maxEventIdLen = min(4, strlen($suffix) - 1); // Ít nhất 1 chữ số cho ID_ThanhToan
            for ($eventIdLen = 1; $eventIdLen <= $maxEventIdLen; $eventIdLen++) {
                $tryEventId = intval(substr($suffix, 0, $eventIdLen));
                $tryInsertedId = intval(substr($suffix, $eventIdLen));
                
                error_log("SePay Webhook: Trying parse - eventIdLen: {$eventIdLen}, tryEventId: {$tryEventId}, tryInsertedId: {$tryInsertedId}");
                
                if ($tryEventId > 0 && $tryInsertedId > 0) {
                    // Tìm payment trực tiếp bằng ID_DatLich và ID_ThanhToan
                    $stmt = $pdo->prepare("
                        SELECT ID_ThanhToan, ID_DatLich, MaGiaoDich, GhiChu, SoTien, TrangThai
                        FROM thanhtoan 
                        WHERE ID_DatLich = ? AND ID_ThanhToan = ? 
                        ORDER BY ID_ThanhToan DESC 
                        LIMIT 1
                    ");
                    $stmt->execute([$tryEventId, $tryInsertedId]);
                    $foundPayment = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($foundPayment) {
                        $paymentId = $foundPayment['MaGiaoDich'];
                        $eventId = $tryEventId;
                        error_log("SePay Webhook: Found payment by parsing suffix - eventId: {$tryEventId}, ID_ThanhToan: {$tryInsertedId}, paymentId: {$paymentId}");
                        // Lưu payment để sử dụng sau này (tránh phải query lại)
                        // Cần lấy đầy đủ thông tin payment với JOIN
                        $stmt = $pdo->prepare("
                            SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
                            FROM thanhtoan t
                            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                            INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                            WHERE t.ID_ThanhToan = ? AND t.ID_DatLich = ?
                            LIMIT 1
                        ");
                        $stmt->execute([$tryInsertedId, $tryEventId]);
                        $fullPayment = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($fullPayment) {
                            $payment = $fullPayment;
                        } else {
                            $payment = $foundPayment;
                        }
                        break;
                    }
                }
            }
        }
    }
    // ✅ Pattern cũ: SEPAY_{timestamp}_{random} - Backward compatible
    elseif (preg_match('/SEPAY_(\d+)_(\d+)/', $content, $matches)) {
        $paymentId = 'SEPAY_' . $matches[1] . '_' . $matches[2];
    }
    // ✅ Pattern cũ: SK{eventId}_{paymentId} - Backward compatible
    elseif (preg_match('/SK(\d+)_(.+?)(?:\s|$)/', $content, $matches)) {
        $eventId = intval($matches[1]);
        $paymentId = trim($matches[2]);
    }
    // ✅ Pattern: SEPAY + số bất kỳ (fallback)
    elseif (preg_match('/SEPAY(\d{3,10})(?:\s|$|_)/', $content, $matches)) {
        // Tìm payment record dựa trên content
        $searchPattern = 'SEPAY' . $matches[1];
        $stmt = $pdo->prepare("
            SELECT MaGiaoDich, ID_DatLich 
            FROM thanhtoan 
            WHERE MaGiaoDich LIKE ? OR GhiChu LIKE ?
            ORDER BY ID_ThanhToan DESC 
            LIMIT 1
        ");
        $stmt->execute(['%' . $searchPattern . '%', '%' . $searchPattern . '%']);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($payment) {
            $paymentId = $payment['MaGiaoDich'];
            $eventId = $payment['ID_DatLich'];
        }
    }
    
    // ✅ Khởi tạo biến payment (có thể đã được set từ parse suffix)
    if (!isset($payment)) {
        $payment = null;
    }
    
    // ✅ Nếu không tìm thấy payment ID từ content, thử tìm bằng cách khác
    if (empty($paymentId) && empty($payment)) {
        error_log("SePay Webhook: Could not extract payment ID from content. Trying alternative methods...");
        error_log("Content: " . $content);
        error_log("Amount: " . $transferAmount);
        error_log("Reference Code: " . $referenceCode);
        error_log("Transaction Date: " . $transactionDate);
        
        // ✅ Phương pháp 1: Tìm payment record dựa trên amount và thời gian gần đây (trong 48h)
        // SePay có thể không gửi đúng content, nhưng amount và thời gian sẽ khớp
        // Mở rộng thời gian tìm kiếm từ 24h lên 48h để đảm bảo không bỏ sót
        $stmt = $pdo->prepare("
            SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
            FROM thanhtoan t
            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            WHERE ABS(t.SoTien - ?) <= 0.01
            AND t.PhuongThuc = 'Chuyển khoản'
            AND t.TrangThai IN ('Đang xử lý', 'Chờ xác nhận')
            AND t.NgayTao >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
            ORDER BY t.ID_ThanhToan DESC
            LIMIT 10
        ");
        $stmt->execute([$transferAmount]);
        $candidatePayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("SePay Webhook: Found " . count($candidatePayments) . " candidate payments by amount: " . $transferAmount);
        
        if (count($candidatePayments) === 1) {
            // Chỉ có 1 payment khớp → dùng payment này
            $payment = $candidatePayments[0];
            $paymentId = $payment['MaGiaoDich'];
            $eventId = $payment['ID_DatLich'];
            error_log("SePay Webhook: Found payment by amount match: " . $paymentId);
        } elseif (count($candidatePayments) > 1) {
            // Có nhiều payment khớp → chọn payment gần nhất chưa được xác nhận
            foreach ($candidatePayments as $candidate) {
                // Kiểm tra SePayTransactionId (có thể không có cột này)
                $hasSePayTransactionId = false;
                try {
                    $hasSePayTransactionId = isset($candidate['SePayTransactionId']) && !empty($candidate['SePayTransactionId']);
                } catch (Exception $e) {
                    // Cột không tồn tại, bỏ qua
                }
                
                if ($candidate['TrangThai'] === 'Đang xử lý' && !$hasSePayTransactionId) {
                    $payment = $candidate;
                    $paymentId = $payment['MaGiaoDich'];
                    $eventId = $payment['ID_DatLich'];
                    error_log("SePay Webhook: Found payment by amount match (multiple, selected unprocessed): " . $paymentId);
                break;
                }
            }
            
            // Nếu vẫn chưa chọn được, chọn payment đầu tiên
            if (empty($paymentId) && !empty($candidatePayments[0])) {
                $payment = $candidatePayments[0];
                $paymentId = $payment['MaGiaoDich'];
                $eventId = $payment['ID_DatLich'];
                error_log("SePay Webhook: Found payment by amount match (multiple, selected first): " . $paymentId);
            }
        }
        
        // ✅ Phương pháp 2: Tìm payment record dựa trên referenceCode (nếu có)
        if (empty($paymentId) && !empty($referenceCode)) {
            $stmt = $pdo->prepare("
                SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
                FROM thanhtoan t
                INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                WHERE t.GhiChu LIKE ? OR t.MaGiaoDich LIKE ?
                ORDER BY t.ID_ThanhToan DESC
                LIMIT 1
            ");
            $stmt->execute(['%' . $referenceCode . '%', '%' . $referenceCode . '%']);
            $foundPayment = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($foundPayment) {
                $payment = $foundPayment;
                $paymentId = $payment['MaGiaoDich'];
                $eventId = $payment['ID_DatLich'];
                error_log("SePay Webhook: Found payment by referenceCode: " . $paymentId);
            }
        }
        
        // ✅ Phương pháp 3: Parse content để tìm mã ZP (ZP253230014170 có thể là mã)
        // Format: CT DEN:... ZP{number} ... SEVQR
        if (empty($paymentId) && preg_match('/ZP(\d+)/', $content, $matches)) {
            $code = 'ZP' . $matches[1];
            error_log("SePay Webhook: Found ZP code in content: " . $code);
            $stmt = $pdo->prepare("
                SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
                FROM thanhtoan t
                INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                WHERE t.GhiChu LIKE ? OR t.MaGiaoDich LIKE ?
                ORDER BY t.ID_ThanhToan DESC
                LIMIT 1
            ");
            $stmt->execute(['%' . $code . '%', '%' . $code . '%']);
            $foundPayment = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($foundPayment) {
                $payment = $foundPayment;
                $paymentId = $payment['MaGiaoDich'];
                $eventId = $payment['ID_DatLich'];
                error_log("SePay Webhook: Found payment by ZP code: " . $paymentId);
            }
        }
        
        // ✅ Phương pháp 4: Nếu content chỉ là SEVQR hoặc SEVOR (format mặc định SePay)
        // Thì chỉ dựa vào amount và thời gian để tìm payment
        // (Đã được xử lý ở Phương pháp 1 - tìm bằng amount)
        if (empty($paymentId) && (strpos($content, 'SEVQR') !== false || strpos($content, 'SEVOR') !== false)) {
            error_log("SePay Webhook: Content contains SEVQR/SEVOR, using amount-based matching (already attempted)");
        }
        
        // ✅ Phương pháp 5: Parse số từ content (có thể là ID giao dịch)
        // Format: CT DEN:{number} hoặc các số trong content
        if (empty($paymentId) && preg_match('/CT\s+DEN:(\d+)/', $content, $matches)) {
            $denCode = $matches[1];
            error_log("SePay Webhook: Found CT DEN code: " . $denCode);
            // Thử tìm trong GhiChu hoặc MaGiaoDich
            $stmt = $pdo->prepare("
                SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
                FROM thanhtoan t
                INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                WHERE t.GhiChu LIKE ? OR t.MaGiaoDich LIKE ?
                ORDER BY t.ID_ThanhToan DESC
                LIMIT 1
            ");
            $stmt->execute(['%' . $denCode . '%', '%' . $denCode . '%']);
            $foundPayment = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($foundPayment) {
                $payment = $foundPayment;
                $paymentId = $payment['MaGiaoDich'];
                $eventId = $payment['ID_DatLich'];
                error_log("SePay Webhook: Found payment by CT DEN code: " . $paymentId);
            }
        }
        
        // Nếu vẫn không tìm thấy, ghi log lỗi
    if (empty($paymentId)) {
        $parseErrorLog = "\n" . str_repeat("-", 80) . "\n";
            $parseErrorLog .= "ERROR: Không thể tìm payment record\n";
        $parseErrorLog .= "Content: " . $content . "\n";
            $parseErrorLog .= "Amount: " . $transferAmount . "\n";
            $parseErrorLog .= "Reference Code: " . $referenceCode . "\n";
            $parseErrorLog .= "Account Number: " . $accountNumber . "\n";
            $parseErrorLog .= "Transaction Date: " . $transactionDate . "\n";
        $parseErrorLog .= "Match Pattern: " . $matchPattern . "\n";
            $parseErrorLog .= "Candidates found: " . count($candidatePayments) . "\n";
        $parseErrorLog .= str_repeat("-", 80) . "\n";
        file_put_contents(__DIR__ . '/hook_log.txt', $parseErrorLog, FILE_APPEND);
        
            throw new Exception('Payment not found for amount: ' . $transferAmount . ' (content: ' . $content . ')');
        }
    }
    
    // Ghi log parse thành công
    $parseLog = "\n" . str_repeat("-", 40) . "\n";
    $parseLog .= "Payment ID Parsed Successfully\n";
    $parseLog .= "Content: " . $content . "\n";
    $parseLog .= "Event ID: " . ($eventId ?? 'N/A') . "\n";
    $parseLog .= "Payment ID: " . $paymentId . "\n";
    $parseLog .= str_repeat("-", 40) . "\n";
    file_put_contents(__DIR__ . '/hook_log.txt', $parseLog, FILE_APPEND);
    
    // ✅ Nếu đã tìm thấy payment từ phương pháp thay thế, bỏ qua các bước tìm kiếm tiếp theo
    // Nếu chưa có, tìm payment record - thử nhiều pattern
    if (empty($payment) && !empty($paymentId)) {
    // Thử 1: Khớp chính xác với paymentId
    $stmt = $pdo->prepare("
        SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
        FROM thanhtoan t
        INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
        INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE t.MaGiaoDich = ?
        LIMIT 1
    ");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($payment) {
            error_log("SePay Webhook: Found payment by paymentId: " . $paymentId);
        }
    }
    
    // Thử 2: Khớp với toàn bộ content (nếu chứa MaGiaoDich)
    if (!$payment && !empty($content)) {
        $stmt = $pdo->prepare("
            SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
            FROM thanhtoan t
            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            WHERE t.MaGiaoDich = ? OR ? LIKE CONCAT('%', t.MaGiaoDich, '%')
            ORDER BY 
                CASE WHEN t.MaGiaoDich = ? THEN 1 ELSE 2 END,
                t.ID_ThanhToan DESC
            LIMIT 1
        ");
        $stmt->execute([$content, $content, $content]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Thử 3: Trích xuất SEPAY_xxx từ content và khớp
    if (!$payment && preg_match('/SEPAY_(\d+_\d+)/', $content, $sepayMatches)) {
        $sepayId = 'SEPAY_' . $sepayMatches[1];
        $stmt = $pdo->prepare("
            SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
            FROM thanhtoan t
            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            WHERE t.MaGiaoDich = ? OR t.MaGiaoDich LIKE ?
            ORDER BY 
                CASE WHEN t.MaGiaoDich = ? THEN 1 ELSE 2 END,
                t.ID_ThanhToan DESC
            LIMIT 1
        ");
        $stmt->execute([$sepayId, "%{$sepayId}%", $sepayId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Thử 4: Khớp một phần với paymentId
    if (!$payment) {
        $stmt = $pdo->prepare("
            SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
            FROM thanhtoan t
            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            WHERE t.MaGiaoDich LIKE ?
            ORDER BY t.ID_ThanhToan DESC
            LIMIT 1
        ");
        $stmt->execute(["%{$paymentId}%"]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Cập nhật payment_id vào bảng tb_transactions nếu tìm thấy payment
    if ($payment && isset($transactionId)) {
        try {
            $stmt = $pdo->prepare("UPDATE tb_transactions SET payment_id = ? WHERE id = ?");
            $stmt->execute([$payment['ID_ThanhToan'], $transactionId]);
        } catch (Exception $e) {
            error_log("Warning: Could not update payment_id in tb_transactions: " . $e->getMessage());
        }
    }
    
    if (!$payment) {
        // Cập nhật log webhook với lỗi (nếu có)
        if ($webhookLogId) {
            try {
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, response = ? WHERE id = ?");
        $stmt->execute([json_encode(['error' => 'Payment not found', 'payment_id' => $paymentId]), $webhookLogId]);
            } catch (Exception $e) {
                error_log("Warning: Could not update webhook_logs: " . $e->getMessage());
            }
        }
        
        throw new Exception('Payment not found for ID: ' . $paymentId . ' (content: ' . $content . ')');
    }
    
    // Kiểm tra payment đã được xử lý chưa
    if ($payment['TrangThai'] === 'Thành công' && !empty($payment['SePayTransactionId'])) {
        // Payment already processed, update webhook log (nếu có)
        if ($webhookLogId) {
            try {
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, response = ? WHERE id = ?");
        $stmt->execute([json_encode(['message' => 'Payment already processed', 'payment_id' => $payment['ID_ThanhToan']]), $webhookLogId]);
            } catch (Exception $e) {
                error_log("Warning: Could not update webhook_logs: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment already processed',
            'payment_id' => $paymentId,
            'payment_status' => $payment['TrangThai'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Xác minh số tiền khớp (cho phép sai lệch nhỏ do floating point)
    $expectedAmount = floatval($payment['SoTien']);
    $receivedAmount = floatval($transferAmount);
    if (abs($expectedAmount - $receivedAmount) > 0.01) {
        throw new Exception('Amount mismatch: expected ' . $expectedAmount . ', received ' . $receivedAmount);
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // Cập nhật trạng thái payment thành công và lưu SePay transaction ID
        $stmt = $pdo->prepare("
            UPDATE thanhtoan 
            SET TrangThai = 'Thành công', 
                NgayThanhToan = ?,
                SePayTransactionId = ?
            WHERE ID_ThanhToan = ?
        ");
        $paymentDate = !empty($transactionDate) ? $transactionDate : date('Y-m-d H:i:s');
        $stmt->execute([$paymentDate, $sepayTransactionId, $payment['ID_ThanhToan']]);
        
        // Cập nhật trạng thái thanh toán của sự kiện
        $eventStatus = $payment['LoaiThanhToan'] === 'Đặt cọc' ? 'Đã đặt cọc' : 'Đã thanh toán đủ';
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = ?,
                TienCoc = CASE WHEN ? = 'Đặt cọc' THEN COALESCE(TienCoc, 0) + ? ELSE TienCoc END,
                TienConLai = CASE WHEN ? = 'Đặt cọc' THEN GREATEST(0, TongTien - COALESCE(TienCoc, 0) - ?) ELSE 0 END
            WHERE ID_DatLich = ?
        ");
        
        $stmt->execute([
            $eventStatus,
            $payment['LoaiThanhToan'],
            $receivedAmount,
            $payment['LoaiThanhToan'],
            $receivedAmount,
            $payment['ID_DatLich']
        ]);
        
        // Thêm lịch sử thanh toán
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (
                payment_id, action, old_status, new_status, description
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $description = sprintf(
            'SePay webhook - Chuyển khoản thành công: %s - %s VNĐ - Gateway: %s - Transaction: %s - Content: %s',
            $accountNumber,
            number_format($receivedAmount, 0, ',', '.'),
            $gateway,
            $sepayTransactionId,
            $content
        );
        $stmt->execute([
            $payment['ID_ThanhToan'], 
            'sepay_webhook',
            $payment['TrangThai'], 
            'Thành công', 
            $description
        ]);
        
        // Cập nhật processed = 1 trong tb_transactions
        if (isset($transactionId)) {
            try {
                $stmt = $pdo->prepare("UPDATE tb_transactions SET processed = 1, payment_id = ? WHERE id = ?");
                $stmt->execute([$payment['ID_ThanhToan'], $transactionId]);
            } catch (Exception $e) {
                error_log("Warning: Could not update processed status in tb_transactions: " . $e->getMessage());
            }
        }
        
        // Cập nhật log webhook với kết quả thành công (nếu có)
        if ($webhookLogId) {
            try {
        $responseData = [
            'success' => true,
            'message' => 'Webhook processed successfully',
            'payment_id' => $paymentId,
            'payment_db_id' => $payment['ID_ThanhToan'],
            'amount' => $receivedAmount,
            'event_status' => $eventStatus,
            'processed_at' => date('Y-m-d H:i:s')
        ];
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, payment_id = ?, response = ? WHERE id = ?");
        $stmt->execute([$payment['ID_ThanhToan'], json_encode($responseData), $webhookLogId]);
            } catch (Exception $e) {
                error_log("Warning: Could not update webhook_logs: " . $e->getMessage());
            }
        }
        
        $pdo->commit();
        
        // Ghi log xử lý thành công
        error_log("SePay Webhook processed successfully: Payment {$paymentId} (DB ID: {$payment['ID_ThanhToan']}), Amount {$receivedAmount}, Event {$eventStatus}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Webhook processed successfully',
            'payment_id' => $paymentId,
            'payment_db_id' => $payment['ID_ThanhToan'],
            'amount' => $receivedAmount,
            'event_status' => $eventStatus,
            'customer' => $payment['HoTen'],
            'event_name' => $payment['TenSuKien'],
            'transaction_id' => $sepayTransactionId,
            'reference_code' => $referenceCode,
            'gateway' => $gateway,
            'account_number' => $accountNumber,
            'transaction_date' => $transactionDate,
            'processed_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Cập nhật log webhook với lỗi (nếu có)
        if ($webhookLogId) {
            try {
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, response = ? WHERE id = ?");
        $stmt->execute([json_encode(['error' => $e->getMessage()]), $webhookLogId]);
            } catch (Exception $logError) {
                error_log("Warning: Could not update webhook_logs: " . $logError->getMessage());
            }
        }
        
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("SePay Webhook error: " . $e->getMessage());
    error_log("SePay Webhook stack trace: " . $e->getTraceAsString());
    
    // Đặt HTTP status code phù hợp
    $httpCode = 400;
    if (strpos($e->getMessage(), 'not found') !== false) {
        $httpCode = 404;
    } elseif (strpos($e->getMessage(), 'token') !== false || strpos($e->getMessage(), 'Unauthorized') !== false) {
        $httpCode = 401;
    }
    
    http_response_code($httpCode);
    
    $errorResponse = [
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => $httpCode,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($errorResponse);
}
?>
