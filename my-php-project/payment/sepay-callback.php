<?php
// SePay Callback Handler
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/sepay/autoload.php';

// Log callback for debugging
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log the callback data
error_log('SePay Callback: ' . $input);

// Process the callback
try {
    $pdo = getDBConnection();
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid callback data']);
        exit;
    }
    
    // Verify signature (implement your verification logic)
    $signature = $data['signature'] ?? '';
    $orderId = $data['orderInvoiceNumber'] ?? '';
    $status = $data['status'] ?? '';
    $amount = $data['amount'] ?? 0;
    
    // Find payment by order ID
    $stmt = $pdo->prepare("
        SELECT t.*, dl.TongTien 
        FROM thanhtoan t
        INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
        WHERE t.MaGiaoDich = ?
    ");
    $stmt->execute([$orderId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Payment not found']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    if ($status === 'SUCCESS') {
        // Update payment status
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
            SET TrangThaiThanhToan = ?
            WHERE ID_DatLich = ?
        ");
        $stmt->execute([$eventStatus, $payment['ID_DatLich']]);
        
        // Insert payment history
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (
                payment_id, action, old_status, new_status, description
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $payment['ID_ThanhToan'], 
            'sepay_callback',
            $payment['TrangThai'], 
            'Thành công', 
            'SePay callback - ' . $status
        ]);
        
    } else {
        // Payment failed
        $stmt = $pdo->prepare("
            UPDATE thanhtoan 
            SET TrangThai = 'Thất bại'
            WHERE ID_ThanhToan = ?
        ");
        $stmt->execute([$payment['ID_ThanhToan']]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Callback processed']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('SePay Callback Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Callback error: ' . $e->getMessage()]);
}
?>
