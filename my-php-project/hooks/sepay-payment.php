<?php
/**
 * SePay Webhook Handler
 * Handles webhook notifications from SePay when money is received
 * Based on SePay Laravel Package documentation: https://github.com/sepayvn/laravel-sepay
 */

// Debug logging - Log raw JSON input at the very beginning
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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sepay.php';

// Set content type to JSON
header('Content-Type: application/json');

// Log incoming webhook
error_log("SePay Webhook received at: " . date('Y-m-d H:i:s'));

// Verify webhook authentication token
function verifyWebhookToken() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    // Check for Bearer token
    if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
    } else {
        // Fallback: check for custom header
        $token = $_SERVER['HTTP_X_SEPAY_TOKEN'] ?? '';
    }
    
    $expectedToken = defined('SEPAY_WEBHOOK_TOKEN') ? SEPAY_WEBHOOK_TOKEN : '';
    
    if (empty($expectedToken)) {
        error_log("SePay Webhook Warning: SEPAY_WEBHOOK_TOKEN not configured");
        // In production, should reject if token is not set
        // return false;
    }
    
    if (!empty($expectedToken) && $token !== $expectedToken) {
        error_log("SePay Webhook: Invalid token. Expected: " . substr($expectedToken, 0, 10) . "... Got: " . substr($token, 0, 10) . "...");
        return false;
    }
    
    return true;
}

try {
    // Verify webhook authentication
    if (!verifyWebhookToken()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized: Invalid webhook token',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Get webhook data (already decoded at the top for logging)
    $input = $rawInput; // Use already read input
    // $data already decoded above for logging
    
    if (!$data) {
        // Log error to file
        $errorLog = "\n" . str_repeat("-", 80) . "\n";
        $errorLog .= "ERROR: Invalid webhook data - JSON decode failed\n";
        $errorLog .= "Raw input: " . $rawInput . "\n";
        $errorLog .= "JSON Error: " . json_last_error_msg() . "\n";
        $errorLog .= str_repeat("-", 80) . "\n";
        file_put_contents(__DIR__ . '/hook_log.txt', $errorLog, FILE_APPEND);
        
        throw new Exception('Invalid webhook data: JSON decode failed - ' . json_last_error_msg());
    }
    
    // Log webhook to database
    $pdo = getDBConnection();
    
    // Save webhook log
    $stmt = $pdo->prepare("
        INSERT INTO webhook_logs (webhook_source, raw_data, processed, created_at) 
        VALUES ('sepay', ?, 0, NOW())
    ");
    $stmt->execute([$input]);
    $webhookLogId = $pdo->lastInsertId();
    
    // Log webhook data (sanitized for security)
    $sanitizedData = $data;
    if (isset($sanitizedData['signature'])) {
        $sanitizedData['signature'] = '***HIDDEN***';
    }
    error_log("SePay Webhook data: " . json_encode($sanitizedData));
    
    // Extract webhook data according to SePay format
    // Format from SePay Laravel Package documentation:
    // gateway, transactionDate, accountNumber, content, transferType, transferAmount, referenceCode, id
    $gateway = $data['gateway'] ?? '';
    $transactionDate = $data['transactionDate'] ?? '';
    $accountNumber = $data['accountNumber'] ?? '';
    $subAccount = $data['subAccount'] ?? null;
    $code = $data['code'] ?? null;
    $content = $data['content'] ?? '';
    $transferType = $data['transferType'] ?? ''; // 'in' or 'out'
    $description = $data['description'] ?? '';
    $transferAmount = floatval($data['transferAmount'] ?? 0);
    $referenceCode = $data['referenceCode'] ?? '';
    $accumulated = floatval($data['accumulated'] ?? 0);
    $sepayTransactionId = $data['id'] ?? '';
    
    // Only process incoming transfers
    if ($transferType !== 'in') {
        // Update webhook log
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
    
    // Validate required fields
    if (empty($accountNumber) || $transferAmount <= 0 || empty($content)) {
        throw new Exception('Missing required webhook data: accountNumber, transferAmount, or content');
    }
    
    // Get match pattern from config (default: SK)
    $matchPattern = defined('SEPAY_MATCH_PATTERN') ? SEPAY_MATCH_PATTERN : 'SK';
    
    // Parse transaction content to get payment ID
    // Format can be: SK{eventId}_{paymentId} or custom pattern like SE{paymentId}
    // Content may contain text before pattern like "Thanh toan QR SK20_SEPAY_..."
    $paymentId = null;
    $eventId = null;
    
    // Try pattern: SK{eventId}_{paymentId} (may have text before)
    // Matches: "Thanh toan QR SK20_SEPAY_1762094590_1284" or "SK20_SEPAY_1762094590_1284"
    if (preg_match('/' . preg_quote($matchPattern, '/') . '(\d+)_(.+?)(?:\s|$)/', $content, $matches)) {
        $eventId = intval($matches[1]);
        $paymentId = trim($matches[2]); // Remove trailing spaces
    } 
    // Try pattern: SK{eventId}_{paymentId} at end of string
    elseif (preg_match('/' . preg_quote($matchPattern, '/') . '(\d+)_(.+)$/', $content, $matches)) {
        $eventId = intval($matches[1]);
        $paymentId = trim($matches[2]);
    }
    // Try pattern: SK{paymentId} (without eventId, may have text before)
    elseif (preg_match('/' . preg_quote($matchPattern, '/') . '([A-Z0-9_]+?)(?:\s|$)/', $content, $matches)) {
        $paymentId = trim($matches[1]);
    }
    // Try to find payment ID directly from content (SEPAY_ format)
    elseif (preg_match('/SEPAY_(\d+_\d+)/', $content, $matches)) {
        $paymentId = 'SEPAY_' . $matches[1];
    }
    // Try to find any alphanumeric code that might be payment ID
    else {
        // Last resort: try to extract any code from content
        $parts = preg_split('/[\s_\-]+/', $content);
        foreach ($parts as $part) {
            if (preg_match('/SEPAY_/', $part) || (strlen($part) > 10 && preg_match('/^[A-Z0-9_]+$/', $part))) {
                $paymentId = $part;
                break;
            }
        }
    }
    
    if (empty($paymentId)) {
        // Log parsing failure for debugging
        $parseErrorLog = "\n" . str_repeat("-", 80) . "\n";
        $parseErrorLog .= "ERROR: Could not extract payment ID from content\n";
        $parseErrorLog .= "Content: " . $content . "\n";
        $parseErrorLog .= "Match Pattern: " . $matchPattern . "\n";
        $parseErrorLog .= "All parts: " . print_r(preg_split('/[\s_\-]+/', $content), true) . "\n";
        $parseErrorLog .= str_repeat("-", 80) . "\n";
        file_put_contents(__DIR__ . '/hook_log.txt', $parseErrorLog, FILE_APPEND);
        
        throw new Exception('Could not extract payment ID from content. Content: ' . $content);
    }
    
    // Log successful parsing
    $parseLog = "\n" . str_repeat("-", 40) . "\n";
    $parseLog .= "Payment ID Parsed Successfully\n";
    $parseLog .= "Content: " . $content . "\n";
    $parseLog .= "Event ID: " . ($eventId ?? 'N/A') . "\n";
    $parseLog .= "Payment ID: " . $paymentId . "\n";
    $parseLog .= str_repeat("-", 40) . "\n";
    file_put_contents(__DIR__ . '/hook_log.txt', $parseLog, FILE_APPEND);
    
    // Find payment record - try exact match first, then partial match
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
    $stmt->execute([$paymentId, "%{$paymentId}%", $paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        // Update webhook log with error
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, response = ? WHERE id = ?");
        $stmt->execute([json_encode(['error' => 'Payment not found', 'payment_id' => $paymentId]), $webhookLogId]);
        
        throw new Exception('Payment not found for ID: ' . $paymentId . ' (content: ' . $content . ')');
    }
    
    // Check if payment already processed
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
    
    // Verify amount matches (with tolerance for floating point)
    $expectedAmount = floatval($payment['SoTien']);
    $receivedAmount = floatval($transferAmount);
    if (abs($expectedAmount - $receivedAmount) > 0.01) {
        throw new Exception('Amount mismatch: expected ' . $expectedAmount . ', received ' . $receivedAmount);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update payment status to success and store SePay transaction ID
        $stmt = $pdo->prepare("
            UPDATE thanhtoan 
            SET TrangThai = 'Thành công', 
                NgayThanhToan = ?,
                SePayTransactionId = ?
            WHERE ID_ThanhToan = ?
        ");
        $paymentDate = !empty($transactionDate) ? $transactionDate : date('Y-m-d H:i:s');
        $stmt->execute([$paymentDate, $sepayTransactionId, $payment['ID_ThanhToan']]);
        
        // Update event payment status
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
        
        // Insert payment history
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
        
        // Update webhook log with success
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
        
        // Log successful processing
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
        
        // Update webhook log with error
        $stmt = $pdo->prepare("UPDATE webhook_logs SET processed = 1, response = ? WHERE id = ?");
        $stmt->execute([json_encode(['error' => $e->getMessage()]), $webhookLogId]);
        
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("SePay Webhook error: " . $e->getMessage());
    error_log("SePay Webhook stack trace: " . $e->getTraceAsString());
    
    // Set appropriate HTTP status code
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
