<?php
/**
 * MoMo Payment Webhook Handler
 * Handles Instant Payment Notification (IPN) from MoMo
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/momo/MoMoPayment.php';

// Set content type to JSON
header('Content-Type: application/json');

// Log incoming webhook
error_log("MoMo Webhook received at: " . date('Y-m-d H:i:s'));

try {
    // Get webhook data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid webhook data');
    }
    
    // Log webhook data
    error_log("MoMo Webhook data: " . $input);
    
    $pdo = getDBConnection();
    
    // Get MoMo config
    $stmt = $pdo->prepare("
        SELECT config_key, config_value 
        FROM payment_config 
        WHERE payment_method = 'Momo' AND is_active = 1
    ");
    $stmt->execute();
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $config = [];
    foreach ($configs as $c) {
        $config[$c['config_key']] = $c['config_value'];
    }
    
    $momoConfig = [
        'partner_code' => $config['partner_code'] ?? 'MOMO_PARTNER_CODE',
        'access_key' => $config['access_key'] ?? 'MOMO_ACCESS_KEY',
        'secret_key' => $config['secret_key'] ?? 'MOMO_SECRET_KEY',
        'endpoint' => $config['endpoint'] ?? 'https://test-payment.momo.vn/v2/gateway/api/create',
        'return_url' => $config['return_url'] ?? 'http://localhost/event/my-php-project/payment/callback.php',
        'notify_url' => $config['notify_url'] ?? 'http://localhost/event/my-php-project/payment/webhook.php'
    ];
    
    $momo = new MoMoPayment($momoConfig);
    
    // Verify webhook signature
    if (!$momo->verifyPayment($data)) {
        throw new Exception('Invalid signature');
    }
    
    // Process payment result
    $orderId = $data['orderId'] ?? '';
    $resultCode = $data['resultCode'] ?? '';
    $amount = $data['amount'] ?? 0;
    $transId = $data['transId'] ?? '';
    $message = $data['message'] ?? '';
    
    // Find payment record
    $stmt = $pdo->prepare("SELECT * FROM thanhtoan WHERE MaGiaoDich LIKE ?");
    $stmt->execute(["%{$orderId}%"]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment not found for order: ' . $orderId);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update payment status
        $newStatus = 'Thất bại';
        if ($resultCode == 0) {
            $newStatus = 'Thành công';
            
            // Update event payment status
            $paymentType = $payment['LoaiThanhToan'];
            $newEventStatus = $paymentType === 'Đặt cọc' ? 'Đã đặt cọc' : 'Đã thanh toán đủ';
            
            $stmt = $pdo->prepare("
                UPDATE datlichsukien 
                SET TrangThaiThanhToan = ?, 
                    TienCoc = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE TienCoc END,
                    TienConLai = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE 0 END
                WHERE ID_DatLich = ?
            ");
            
            $remainingAmount = $payment['SoTien'] - $amount;
            $stmt->execute([
                $newEventStatus,
                $paymentType,
                $amount,
                $paymentType,
                $remainingAmount,
                $payment['ID_DatLich']
            ]);
        }
        
        // Update payment record
        $stmt = $pdo->prepare("
            UPDATE thanhtoan 
            SET TrangThai = ?, MaGiaoDich = CONCAT(MaGiaoDich, '|', ?)
            WHERE ID_ThanhToan = ?
        ");
        $stmt->execute([$newStatus, $transId, $payment['ID_ThanhToan']]);
        
        // Insert payment history
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
            VALUES (?, 'webhook_update', ?, ?, ?)
        ");
        $stmt->execute([
            $payment['ID_ThanhToan'],
            $payment['TrangThai'],
            $newStatus,
            "MoMo webhook: {$resultCode} - {$message}"
        ]);
        
        $pdo->commit();
        
        // Log successful processing
        error_log("MoMo Webhook processed successfully: Order {$orderId}, Status {$newStatus}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Webhook processed successfully',
            'order_id' => $orderId,
            'status' => $newStatus
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("MoMo Webhook error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>