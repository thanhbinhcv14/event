<?php
/**
 * SePay Webhook Handler
 * Handles webhook notifications from SePay when money is received
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sepay.php';

// Set content type to JSON
header('Content-Type: application/json');

// Log incoming webhook
error_log("SePay Webhook received at: " . date('Y-m-d H:i:s'));

// Verify webhook signature (if SePay provides one)
function verifySePaySignature($data, $signature) {
    // TODO: Implement signature verification when SePay provides the method
    // For now, we'll trust the webhook but log for security audit
    return true;
}

try {
    // Get webhook data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid webhook data');
    }
    
    // Log webhook data (sanitized for security)
    $sanitizedData = $data;
    if (isset($sanitizedData['signature'])) {
        $sanitizedData['signature'] = '***HIDDEN***';
    }
    error_log("SePay Webhook data: " . json_encode($sanitizedData));
    
    // Verify webhook signature if provided
    $signature = $data['signature'] ?? '';
    if ($signature && !verifySePaySignature($data, $signature)) {
        throw new Exception('Invalid webhook signature');
    }
    
    $pdo = getDBConnection();
    
    // Extract webhook data with validation
    $accountNumber = $data['account_number'] ?? '';
    $amount = floatval($data['amount'] ?? 0);
    $content = $data['content'] ?? '';
    $transactionId = $data['transaction_id'] ?? '';
    $bankCode = $data['bank_code'] ?? '';
    
    // Validate required fields
    if (empty($accountNumber) || $amount <= 0 || empty($content)) {
        throw new Exception('Missing required webhook data: account_number, amount, or content');
    }
    
    // Parse transaction content to get payment ID
    // Format: SK{eventId}_{paymentId}
    if (preg_match('/SK(\d+)_(.+)/', $content, $matches)) {
        $eventId = intval($matches[1]);
        $paymentId = $matches[2];
    } else {
        throw new Exception('Invalid transaction content format. Expected: SK{eventId}_{paymentId}, got: ' . $content);
    }
    
    // Find payment record
    $stmt = $pdo->prepare("
        SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
        FROM thanhtoan t
        INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
        INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE t.MaGiaoDich = ?
    ");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment not found for ID: ' . $paymentId);
    }
    
    // Verify amount matches (with tolerance for floating point)
    $expectedAmount = floatval($payment['SoTien']);
    $receivedAmount = floatval($amount);
    if (abs($expectedAmount - $receivedAmount) > 0.01) {
        throw new Exception('Amount mismatch: expected ' . $expectedAmount . ', received ' . $receivedAmount);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update payment status to success
        $stmt = $pdo->prepare("
            UPDATE thanhtoan 
            SET TrangThai = 'Thành công', NgayThanhToan = NOW()
            WHERE ID_ThanhToan = ?
        ");
        $stmt->execute([$payment['ID_ThanhToan']]);
        
        // Update event payment status
        $eventStatus = $payment['LoaiThanhToan'] === 'Đặt cọc' ? 'Đã đặt cọc' : 'Đã thanh toán đủ';
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = ?,
                TienCoc = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE TienCoc END,
                TienConLai = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE 0 END
            WHERE ID_DatLich = ?
        ");
        
        $remainingAmount = $payment['LoaiThanhToan'] === 'Đặt cọc' ? 
            ($payment['TongTien'] - $amount) : 0;
        
        $stmt->execute([
            $eventStatus,
            $payment['LoaiThanhToan'],
            $amount,
            $payment['LoaiThanhToan'],
            $remainingAmount,
            $payment['ID_DatLich']
        ]);
        
        // Insert payment history
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (
                payment_id, action, old_status, new_status, description
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $payment['ID_ThanhToan'], 
            'sepay_webhook',
            $payment['TrangThai'], 
            'Thành công', 
            'SePay webhook - Chuyển khoản thành công: ' . $accountNumber . ' - ' . $amount . ' VNĐ - Bank: ' . $bankCode
        ]);
        
        $pdo->commit();
        
        // Log successful processing
        error_log("SePay Webhook processed successfully: Payment {$paymentId}, Amount {$amount}, Event {$eventStatus}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Webhook processed successfully',
            'payment_id' => $paymentId,
            'amount' => $amount,
            'event_status' => $eventStatus,
            'customer' => $payment['HoTen'],
            'event_name' => $payment['TenSuKien'],
            'transaction_id' => $transactionId,
            'bank_code' => $bankCode,
            'processed_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("SePay Webhook error: " . $e->getMessage());
    error_log("SePay Webhook stack trace: " . $e->getTraceAsString());
    
    // Set appropriate HTTP status code
    $httpCode = 400;
    if (strpos($e->getMessage(), 'not found') !== false) {
        $httpCode = 404;
    } elseif (strpos($e->getMessage(), 'signature') !== false) {
        $httpCode = 401;
    }
    
    http_response_code($httpCode);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => $httpCode,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
