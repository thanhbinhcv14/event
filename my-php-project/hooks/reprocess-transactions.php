<?php
/**
 * Reprocess Unprocessed Transactions
 * Xử lý lại các giao dịch chưa được xử lý trong tb_transactions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sepay.php';

header('Content-Type: application/json');

$pdo = getDBConnection();

try {
    // Lấy các giao dịch chưa xử lý
    $stmt = $pdo->query("
        SELECT * FROM tb_transactions
        WHERE processed = 0 
        AND transfer_type = 'in'
        ORDER BY created_at ASC
        LIMIT 10
    ");
    $unprocessed = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($unprocessed)) {
        echo json_encode([
            'success' => true,
            'message' => 'Không có giao dịch nào cần xử lý',
            'count' => 0
        ]);
        exit;
    }
    
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($unprocessed as $tx) {
        $result = [
            'transaction_id' => $tx['id'],
            'content' => $tx['transaction_content'],
            'amount' => $tx['transfer_amount'],
            'status' => 'processing'
        ];
        
        try {
            // Parse payment ID từ content
            $content = $tx['transaction_content'];
            $paymentId = null;
            
            // Thử nhiều pattern
            if (preg_match('/SEPAY_(\d+_\d+)/', $content, $matches)) {
                $paymentId = 'SEPAY_' . $matches[1];
            } elseif (preg_match('/SK(\d+)_(.+?)(?:\s|$)/', $content, $matches)) {
                $paymentId = trim($matches[2]);
                // Nếu chứa SEPAY_, extract phần đó
                if (preg_match('/SEPAY_(\d+_\d+)/', $paymentId, $sepayMatches)) {
                    $paymentId = 'SEPAY_' . $sepayMatches[1];
                }
            }
            
            if (empty($paymentId)) {
                throw new Exception('Không thể trích xuất payment ID từ content: ' . $content);
            }
            
            // Tìm payment
            $payment = null;
            
            // Thử 1: Khớp chính xác
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
            
            // Thử 2: Khớp với content
            if (!$payment) {
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
            
            // Thử 3: Trích xuất SEPAY_xxx và khớp
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
            
            if (!$payment) {
                throw new Exception('Không tìm thấy payment cho ID: ' . $paymentId);
            }
            
            // Kiểm tra payment đã được xử lý chưa
            if ($payment['TrangThai'] === 'Thành công' && !empty($payment['SePayTransactionId'])) {
                // Đã xử lý rồi, chỉ cập nhật transaction
                $stmt = $pdo->prepare("UPDATE tb_transactions SET processed = 1, payment_id = ? WHERE id = ?");
                $stmt->execute([$payment['ID_ThanhToan'], $tx['id']]);
                
                $result['status'] = 'already_processed';
                $result['payment_id'] = $payment['ID_ThanhToan'];
                $result['message'] = 'Payment đã được xử lý';
            } else {
                // Xử lý payment
                $pdo->beginTransaction();
                
                $receivedAmount = floatval($tx['transfer_amount']);
                $expectedAmount = floatval($payment['SoTien']);
                
                // Kiểm tra số tiền (cho phép sai lệch nhỏ)
                $amountDiff = abs($receivedAmount - $expectedAmount);
                $amountTolerance = 1000; // Cho phép sai lệch 1000 VNĐ
                
                if ($amountDiff > $amountTolerance) {
                    throw new Exception("Số tiền không khớp: Mong đợi {$expectedAmount}, Nhận được {$receivedAmount}");
                }
                
                // Cập nhật payment
                $stmt = $pdo->prepare("
                    UPDATE thanhtoan 
                    SET TrangThai = 'Thành công',
                        SePayTransactionId = ?,
                        NgayThanhToan = NOW()
                    WHERE ID_ThanhToan = ?
                ");
                $stmt->execute([$tx['sepay_transaction_id'], $payment['ID_ThanhToan']]);
                
                // Cập nhật event status
                $eventStatus = 'Đã thanh toán đủ';
                if ($payment['LoaiThanhToan'] === 'Đặt cọc') {
                    $eventStatus = 'Đã đặt cọc';
                }
                
                $stmt = $pdo->prepare("
                    UPDATE datlichsukien 
                    SET TrangThaiThanhToan = ?
                    WHERE ID_DatLich = ?
                ");
                $stmt->execute([$eventStatus, $payment['ID_DatLich']]);
                
                // Cập nhật transaction
                $stmt = $pdo->prepare("UPDATE tb_transactions SET processed = 1, payment_id = ? WHERE id = ?");
                $stmt->execute([$payment['ID_ThanhToan'], $tx['id']]);
                
                $pdo->commit();
                
                $result['status'] = 'success';
                $result['payment_id'] = $payment['ID_ThanhToan'];
                $result['message'] = 'Xử lý payment thành công';
                $successCount++;
            }
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['error'] = $e->getMessage();
            $errorCount++;
        }
        
        $results[] = $result;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Đã xử lý {$successCount} giao dịch thành công, {$errorCount} lỗi",
        'total' => count($unprocessed),
        'success_count' => $successCount,
        'error_count' => $errorCount,
        'results' => $results
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

