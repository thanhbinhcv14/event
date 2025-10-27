<?php
/**
 * MoMo Payment Callback Handler
 * Handles return from MoMo payment gateway
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/momo/MoMoPayment.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log callback
error_log("MoMo Callback received at: " . date('Y-m-d H:i:s'));

try {
    // Get callback data
    $orderId = $_GET['orderId'] ?? '';
    $resultCode = $_GET['resultCode'] ?? '';
    $amount = $_GET['amount'] ?? 0;
    $transId = $_GET['transId'] ?? '';
    $message = $_GET['message'] ?? '';
    
    if (!$orderId) {
        throw new Exception('Missing order ID');
    }
    
    // Log callback data
    error_log("MoMo Callback data: " . json_encode($_GET));

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
    
    // Verify callback signature
    if (!$momo->verifyPayment($_GET)) {
        throw new Exception('Invalid signature');
    }
    
    // Find payment record
    $stmt = $pdo->prepare("SELECT * FROM thanhtoan WHERE MaGiaoDich LIKE ?");
    $stmt->execute(["%{$orderId}%"]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
        throw new Exception('Payment not found for order: ' . $orderId);
    }
    
    // Get event details
    $stmt = $pdo->prepare("
        SELECT dl.*, kh.HoTen, kh.SoDienThoai 
        FROM datlichsukien dl
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE dl.ID_DatLich = ?
    ");
    $stmt->execute([$payment['ID_DatLich']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Determine payment status
    $paymentStatus = 'Thất bại';
    $eventStatus = $event['TrangThaiThanhToan'];
    $success = false;
    
    if ($resultCode == 0) {
        $paymentStatus = 'Thành công';
        $success = true;
        
        // Update event payment status if not already updated by webhook
        if ($event['TrangThaiThanhToan'] === 'Chưa thanh toán') {
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
            
            $eventStatus = $newEventStatus;
        }
    }
    
    // Update payment record
    $stmt = $pdo->prepare("
        UPDATE thanhtoan 
        SET TrangThai = ?, MaGiaoDich = CONCAT(MaGiaoDich, '|', ?)
        WHERE ID_ThanhToan = ?
    ");
    $stmt->execute([$paymentStatus, $transId, $payment['ID_ThanhToan']]);
    
    // Insert payment history
    $stmt = $pdo->prepare("
        INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
        VALUES (?, 'callback_update', ?, ?, ?)
    ");
    $stmt->execute([
        $payment['ID_ThanhToan'],
        $payment['TrangThai'], 
        $paymentStatus,
        "MoMo callback: {$resultCode} - {$message}"
    ]);
    
    // Log successful processing
    error_log("MoMo Callback processed successfully: Order {$orderId}, Status {$paymentStatus}");
    
    // Redirect to success/failure page
    if ($success) {
        $redirectUrl = "/event/my-php-project/payment/success.php?order_id={$orderId}&amount={$amount}";
    } else {
        $redirectUrl = "/event/my-php-project/payment/failure.php?order_id={$orderId}&message=" . urlencode($message);
    }
    
    header("Location: {$redirectUrl}");
    exit();
    
} catch (Exception $e) {
    error_log("MoMo Callback error: " . $e->getMessage());
    
    // Redirect to error page
    header("Location: /event/my-php-project/payment/error.php?message=" . urlencode($e->getMessage()));
    exit();
}
?>