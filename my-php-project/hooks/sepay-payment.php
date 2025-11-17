<?php
/**
 * SePay Webhook Handler
 * Xử lý thông báo webhook từ SePay khi nhận được tiền
 * Dựa trên tài liệu SePay Laravel Package: https://github.com/sepayvn/laravel-sepay
 */

// Ghi log debug - Ghi log JSON input ngay từ đầu
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$logEntry = "\n" . str_repeat("=", 80) . "\n";
$logEntry .= "SePay Webhook Received: " . date('Y-m-d H:i:s') . "\n";
$logEntry .= "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . "\n";
$logEntry .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'UNKNOWN') . "\n";
$logEntry .= "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'UNKNOWN') . "\n";
$logEntry .= "Authorization Header: " . (isset($_SERVER['HTTP_AUTHORIZATION']) ? substr($_SERVER['HTTP_AUTHORIZATION'], 0, 30) . '...' : 'NOT SET') . "\n";
$logEntry .= "Raw Input Length: " . strlen($rawInput) . " bytes\n";
$logEntry .= "Raw Input (first 500 chars): " . substr($rawInput, 0, 500) . "\n";
$logEntry .= "Decoded JSON: " . print_r($data, true) . "\n";
$logEntry .= str_repeat("=", 80) . "\n";
file_put_contents(__DIR__ . '/hook_log.txt', $logEntry, FILE_APPEND);
error_log("SePay Webhook - Raw input logged to hook_log.txt");

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
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    $token = '';
    
    // SePay gửi với format: Authorization: Apikey {API_KEY}
    if (preg_match('/Apikey\s+(.+)/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
    } 
    // Dự phòng: Kiểm tra Bearer token
    elseif (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
    } 
    // Dự phòng: Kiểm tra custom header
    else {
        $token = $_SERVER['HTTP_X_SEPAY_TOKEN'] ?? '';
    }
    
    $expectedToken = defined('SEPAY_WEBHOOK_TOKEN') ? SEPAY_WEBHOOK_TOKEN : '';
    
    if (empty($expectedToken)) {
        error_log("SePay Webhook Warning: SEPAY_WEBHOOK_TOKEN not configured");
        // Trong production, nên reject nếu token không được cấu hình
        return false;
    }
    
    if (empty($token)) {
        error_log("SePay Webhook: No authentication token provided");
        return false;
    }
    
    if ($token !== $expectedToken) {
        error_log("SePay Webhook: Invalid token. Expected: " . substr($expectedToken, 0, 10) . "... Got: " . substr($token, 0, 10) . "...");
        return false;
    }
    
    return true;
}

try {
    // Xác thực webhook
    // Cho phép chế độ test qua GET parameter (chỉ để debug - xóa trong production)
    $isTestMode = isset($_GET['test']) && $_GET['test'] === '1';
    
    if (!$isTestMode && !verifyWebhookToken()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: Invalid webhook token',
            'message' => 'This endpoint requires Authorization header with API key. SePay will send POST requests with proper authentication.',
            'hint' => 'For testing, add ?test=1 to URL (development only)',
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
    
    // Lưu log webhook
    $stmt = $pdo->prepare("
        INSERT INTO webhook_logs (webhook_source, raw_data, processed, created_at) 
        VALUES ('sepay', ?, 0, NOW())
    ");
    $stmt->execute([$input]);
    $webhookLogId = $pdo->lastInsertId();
    
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
        // Cập nhật log webhook
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, response = ? WHERE id = ?");
        $stmt->execute([json_encode(['message' => 'Skipped: Not an incoming transfer']), $webhookLogId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Webhook received but skipped (not incoming transfer)',
            'transfer_type' => $transferType,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Kiểm tra các trường bắt buộc
    if (empty($accountNumber) || $transferAmount <= 0 || empty($content)) {
        throw new Exception('Missing required webhook data: accountNumber, transferAmount, or content');
    }
    
    // Lấy pattern match từ config (mặc định: SK)
    $matchPattern = defined('SEPAY_MATCH_PATTERN') ? SEPAY_MATCH_PATTERN : 'SK';
    
    // Parse nội dung giao dịch để lấy payment ID
    // Format có thể là: SK{eventId}_{paymentId} hoặc pattern tùy chỉnh như SE{paymentId}
    // Content có thể chứa text trước pattern như "Thanh toan QR SK20_SEPAY_..."
    $paymentId = null;
    $eventId = null;
    
    // Thử pattern: SK{eventId}_{paymentId} (có thể có text trước)
    // Khớp: "Thanh toan QR SK20_SEPAY_1762094590_1284" hoặc "SK20_SEPAY_1762094590_1284"
    if (preg_match('/' . preg_quote($matchPattern, '/') . '(\d+)_(.+?)(?:\s|$)/', $content, $matches)) {
        $eventId = intval($matches[1]);
        $paymentId = trim($matches[2]); // Loại bỏ khoảng trắng thừa
    } 
    // Thử pattern: SK{eventId}_{paymentId} ở cuối chuỗi
    elseif (preg_match('/' . preg_quote($matchPattern, '/') . '(\d+)_(.+)$/', $content, $matches)) {
        $eventId = intval($matches[1]);
        $paymentId = trim($matches[2]);
    }
    // Thử tìm payment ID trực tiếp từ content (format SEPAY_) - ưu tiên cao
    // Khớp: "SK20_SEPAY_1762094590_1284" -> "SEPAY_1762094590_1284"
    elseif (preg_match('/SEPAY_(\d+_\d+)/', $content, $matches)) {
        $paymentId = 'SEPAY_' . $matches[1];
    }
    // Thử pattern: SK{paymentId} (không có eventId, có thể có text trước)
    elseif (preg_match('/' . preg_quote($matchPattern, '/') . '([A-Z0-9_]+?)(?:\s|$)/', $content, $matches)) {
        $paymentId = trim($matches[1]);
    }
    // Thử tìm bất kỳ mã alphanumeric nào có thể là payment ID
    else {
        // Cuối cùng: thử trích xuất bất kỳ mã nào từ content
        $parts = preg_split('/[\s_\-]+/', $content);
        foreach ($parts as $part) {
            if (preg_match('/SEPAY_/', $part) || (strlen($part) > 10 && preg_match('/^[A-Z0-9_]+$/', $part))) {
                $paymentId = $part;
                break;
            }
        }
    }
    
    // Nếu paymentId có dạng "SEPAY_xxx" từ pattern SK{eventId}_SEPAY_xxx, giữ nguyên
    // Nếu paymentId là toàn bộ chuỗi sau SK{eventId}_, cần kiểm tra xem có chứa SEPAY_ không
    if ($paymentId && preg_match('/^SEPAY_/', $paymentId)) {
        // Đã đúng format, giữ nguyên
    } elseif ($paymentId && strpos($paymentId, 'SEPAY_') !== false) {
        // Nếu paymentId chứa SEPAY_ nhưng không bắt đầu bằng SEPAY_, extract phần SEPAY_
        if (preg_match('/SEPAY_(\d+_\d+)/', $paymentId, $matches)) {
            $paymentId = 'SEPAY_' . $matches[1];
        }
    }
    
    if (empty($paymentId)) {
        // Ghi log lỗi parse để debug
        $parseErrorLog = "\n" . str_repeat("-", 80) . "\n";
        $parseErrorLog .= "ERROR: Không thể trích xuất payment ID từ content\n";
        $parseErrorLog .= "Content: " . $content . "\n";
        $parseErrorLog .= "Match Pattern: " . $matchPattern . "\n";
        $parseErrorLog .= "All parts: " . print_r(preg_split('/[\s_\-]+/', $content), true) . "\n";
        $parseErrorLog .= str_repeat("-", 80) . "\n";
        file_put_contents(__DIR__ . '/hook_log.txt', $parseErrorLog, FILE_APPEND);
        
        throw new Exception('Could not extract payment ID from content. Content: ' . $content);
    }
    
    // Ghi log parse thành công
    $parseLog = "\n" . str_repeat("-", 40) . "\n";
    $parseLog .= "Payment ID Parsed Successfully\n";
    $parseLog .= "Content: " . $content . "\n";
    $parseLog .= "Event ID: " . ($eventId ?? 'N/A') . "\n";
    $parseLog .= "Payment ID: " . $paymentId . "\n";
    $parseLog .= str_repeat("-", 40) . "\n";
    file_put_contents(__DIR__ . '/hook_log.txt', $parseLog, FILE_APPEND);
    
    // Tìm payment record - thử nhiều pattern
    // Pattern 1: Khớp chính xác với paymentId đã parse
    // Pattern 2: Khớp với toàn bộ content (nếu content chứa MaGiaoDich)
    // Pattern 3: Khớp với phần sau SK{eventId}_ (SEPAY_xxx)
    // Pattern 4: Khớp một phần
    
    $payment = null;
    
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
        // Cập nhật log webhook với lỗi
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, response = ? WHERE id = ?");
        $stmt->execute([json_encode(['error' => 'Payment not found', 'payment_id' => $paymentId]), $webhookLogId]);
        
        throw new Exception('Payment not found for ID: ' . $paymentId . ' (content: ' . $content . ')');
    }
    
    // Kiểm tra payment đã được xử lý chưa
    if ($payment['TrangThai'] === 'Thành công' && !empty($payment['SePayTransactionId'])) {
        // Payment already processed, update webhook log
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, response = ? WHERE id = ?");
        $stmt->execute([json_encode(['message' => 'Payment already processed', 'payment_id' => $payment['ID_ThanhToan']]), $webhookLogId]);
        
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
        
        // Cập nhật log webhook với kết quả thành công
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
        
        // Cập nhật log webhook với lỗi
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, response = ? WHERE id = ?");
        $stmt->execute([json_encode(['error' => $e->getMessage()]), $webhookLogId]);
        
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
