<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sepay.php';
// MoMo has been removed - using SePay only
require_once __DIR__ . '/../../vendor/sepay/autoload.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Vui lòng đăng nhập để thực hiện thanh toán']);
    exit();
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_payment':
            createPayment();
            break;
            
        case 'check_payment_status':
            checkPaymentStatus();
            break;
            
        case 'update_payment_status':
            updatePaymentStatus();
            break;
            
        case 'get_payment_config':
            getPaymentConfig();
            break;
            
        case 'get_qr_code':
            getQRCode();
            break;
            
        case 'generate_qr':
            generateQRCode();
            break;
            
        // MoMo cases removed - using SePay only
            
        case 'create_sepay_payment':
            createSePayPayment();
            break;
            
        case 'sepay_callback':
            processSePayCallback();
            break;
            
        case 'get_sepay_form':
            getSePayForm();
            break;
            
        case 'momo_webhook':
            handleMoMoWebhook();
            break;
            
        case 'confirm_cash_payment':
            confirmCashPayment();
            break;
            
        case 'cancel_payment':
            cancelPayment();
            break;
            
        case 'get_payment_status':
            getPaymentStatus();
            break;
            
        case 'get_payment_list':
            getPaymentList();
            break;
            
        case 'get_payment_stats':
            getPaymentStats();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
} catch (Exception $e) {
    error_log("Payment Controller - System Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

function createPayment() {
    global $pdo;
    
    $eventId = $_POST['event_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $paymentMethod = $_POST['payment_method'] ?? null;
    $paymentType = $_POST['payment_type'] ?? 'deposit';
    
    // Validate input
    if (!$eventId || !$amount || !$paymentMethod) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin thanh toán']);
        return;
    }
    
    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Số tiền không hợp lệ']);
        return;
    }
    
    // Validate payment method
    $validMethods = ['momo', 'banking', 'cash', 'zalo'];
    if (!in_array($paymentMethod, $validMethods)) {
        echo json_encode(['success' => false, 'error' => 'Phương thức thanh toán không hợp lệ']);
        return;
    }
    
    // Validate payment type
    $validTypes = ['deposit', 'full'];
    if (!in_array($paymentType, $validTypes)) {
        echo json_encode(['success' => false, 'error' => 'Loại thanh toán không hợp lệ']);
        return;
    }

    // Enforce business rules:
    // - cash and banking allow both deposit and full payment
    // - momo and zalo must be deposit only
    if (in_array($paymentMethod, ['momo', 'zalo']) && $paymentType !== 'deposit') {
        echo json_encode(['success' => false, 'error' => 'MoMo và ZaloPay chỉ hỗ trợ đặt cọc 30%.']);
        return;
    }
    
    // Check if event exists and belongs to user
    $userId = $_SESSION['user']['ID_User'];
    $stmt = $pdo->prepare("
        SELECT dl.*, kh.HoTen, kh.SoDienThoai 
        FROM datlichsukien dl
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE dl.ID_DatLich = ? AND kh.ID_User = ?
    ");
    $stmt->execute([$eventId, $userId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy sự kiện hoặc bạn không có quyền thanh toán']);
        return;
    }
    
    // Check if event is approved
    if ($event['TrangThaiDuyet'] !== 'Đã duyệt') {
        echo json_encode(['success' => false, 'error' => 'Sự kiện chưa được duyệt, không thể thanh toán']);
        return;
    }
    
    // Check if already paid
    if ($paymentType === 'deposit' && $event['TrangThaiThanhToan'] === 'Đã đặt cọc') {
        echo json_encode(['success' => false, 'error' => 'Đã đặt cọc cho sự kiện này']);
        return;
    }
    
    if ($paymentType === 'full' && $event['TrangThaiThanhToan'] === 'Đã thanh toán đủ') {
        echo json_encode(['success' => false, 'error' => 'Đã thanh toán đủ cho sự kiện này']);
        return;
    }
    
    // Generate transaction code
    $transactionCode = 'TXN' . date('YmdHis') . rand(1000, 9999);
    
    // Map payment method to database values
    $methodMap = [
        'momo' => 'Momo',
        'banking' => 'Chuyển khoản',
        'cash' => 'Tiền mặt',
        'zalo' => 'ZaloPay'
    ];
    
    $paymentMethodDB = $methodMap[$paymentMethod];
    
    // Map payment type to database values
    $typeMap = [
        'deposit' => 'Đặt cọc',
        'full' => 'Thanh toán đủ'
    ];
    
    $paymentTypeDB = $typeMap[$paymentType];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Generate QR code data
        $qrData = generateQRData($paymentMethod, $amount, $eventId, $transactionCode);
        
        // Insert payment record
        $stmt = $pdo->prepare("
            INSERT INTO thanhtoan (ID_DatLich, SoTien, LoaiThanhToan, PhuongThuc, TrangThai, MaGiaoDich, GhiChu) 
            VALUES (?, ?, ?, ?, 'Đang xử lý', ?, ?)
        ");
        
        $note = "Thanh toán {$paymentTypeDB} cho sự kiện: {$event['TenSuKien']}";
        $stmt->execute([
            $eventId,
            $amount,
            $paymentTypeDB,
            $paymentMethodDB,
            $transactionCode,
            $note
        ]);
        
        $paymentId = $pdo->lastInsertId();
        
        // Update event payment status to "Chờ thanh toán" until payment is confirmed
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = 'Chờ thanh toán', 
                TienCoc = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE TienCoc END,
                TienConLai = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE 0 END
            WHERE ID_DatLich = ?
        ");
        
        $remainingAmount = $event['TongTien'] - $amount;
        $stmt->execute([
            $paymentTypeDB,
            $amount,
            $paymentTypeDB,
            $remainingAmount,
            $eventId
        ]);
        
        // Insert payment history
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
            VALUES (?, 'created', 'Chưa thanh toán', 'Đang xử lý', ?)
        ");
        $stmt->execute([
            $paymentId,
            "Tạo thanh toán {$paymentTypeDB} - {$paymentMethodDB} - {$transactionCode}"
        ]);
        
        $pdo->commit();
        
        // Prepare response data
        $responseData = [
            'success' => true,
            'message' => 'Tạo thanh toán thành công',
            'payment_id' => $paymentId,
            'transaction_code' => $transactionCode,
            'amount' => $amount,
            'payment_method' => $paymentMethodDB,
            'payment_type' => $paymentTypeDB
        ];
        
        // Add specific instructions based on payment method
        switch ($paymentMethod) {
            case 'momo':
                $responseData['instructions'] = [
                    'type' => 'momo',
                    'phone' => '0123456789',
                    'content' => "THANH TOAN SU KIEN {$eventId}",
                    'amount' => $amount
                ];
                break;
                
            case 'banking':
                $responseData['instructions'] = [
                    'type' => 'banking',
                    'bank' => 'Vietcombank',
                    'account' => '1234567890',
                    'name' => 'CÔNG TY TNHH EVENT MANAGEMENT',
                    'content' => "THANH TOAN SU KIEN {$eventId}",
                    'amount' => $amount
                ];
                break;
                
            case 'cash':
                $responseData['instructions'] = [
                    'type' => 'cash',
                    'address' => '123 Đường Nguyễn Huệ, Quận 1, TP.HCM',
                    'phone' => '(028) 1234-5678',
                    'hours' => '8:00 - 17:00 (Thứ 2 - Thứ 6)',
                    'amount' => $amount
                ];
                break;
                
            case 'zalo':
                $responseData['instructions'] = [
                    'type' => 'zalo',
                    'phone' => '0123456789',
                    'content' => "THANH TOAN SU KIEN {$eventId}",
                    'amount' => $amount
                ];
                break;
        }
        
        echo json_encode($responseData);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getPaymentHistory() {
    global $pdo;
    
    $userId = $_SESSION['user']['ID_User'];
    
    $stmt = $pdo->prepare("
        SELECT t.*, dl.TenSuKien, dl.NgayBatDau
        FROM thanhtoan t
        JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE kh.ID_User = ?
        ORDER BY t.NgayThanhToan DESC
    ");
    $stmt->execute([$userId]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'payments' => $payments]);
}

// Check specific payment status by transaction code
function checkPaymentStatus() {
    global $pdo;
    
    $transactionCode = $_POST['transaction_code'] ?? '';
    
    if (!$transactionCode) {
        echo json_encode(['success' => false, 'error' => 'Thiếu mã giao dịch']);
        return;
    }
    
    // Kiểm tra session
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['ID_User'])) {
        echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
        return;
    }
    
    $userId = $_SESSION['user']['ID_User'];
    
    try {
        // Tìm thanh toán theo mã giao dịch
        $stmt = $pdo->prepare("
            SELECT t.*, dl.TenSuKien, dl.NgayBatDau, dl.TrangThaiThanhToan
            FROM thanhtoan t
            JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            WHERE kh.ID_User = ? AND (
                t.MaGiaoDich = ? OR 
                t.MaGiaoDich LIKE ?
            )
            ORDER BY t.ID_ThanhToan DESC
            LIMIT 1
        ");
        $stmt->execute([
            $userId, 
            $transactionCode, 
            '%' . $transactionCode . '%'
        ]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            echo json_encode([
                'success' => true, 
                'payment' => $payment,
                'found' => true
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'payment' => null,
                'found' => false
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
    }
}

function updatePaymentStatus() {
    global $pdo;
    
    $paymentId = $_POST['payment_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$paymentId || !$status) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin cập nhật']);
        return;
    }
    
    $validStatuses = ['Đang xử lý', 'Thành công', 'Thất bại', 'Đã hủy'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Trạng thái không hợp lệ']);
        return;
    }
    
    // Get current status
    $stmt = $pdo->prepare("SELECT TrangThai FROM thanhtoan WHERE ID_ThanhToan = ?");
    $stmt->execute([$paymentId]);
    $currentStatus = $stmt->fetchColumn();
    
    if (!$currentStatus) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy thanh toán']);
        return;
    }
    
    // Update status
    $stmt = $pdo->prepare("UPDATE thanhtoan SET TrangThai = ? WHERE ID_ThanhToan = ?");
    $stmt->execute([$status, $paymentId]);
    
    // Insert history
    $stmt = $pdo->prepare("
        INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
        VALUES (?, 'status_update', ?, ?, ?)
    ");
    $stmt->execute([
        $paymentId,
        $currentStatus,
        $status,
        "Cập nhật trạng thái từ {$currentStatus} thành {$status}"
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
}

function getPaymentConfig() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT payment_method, config_key, config_value 
        FROM payment_config 
        WHERE is_active = 1
        ORDER BY payment_method, config_key
    ");
    $stmt->execute();
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by payment method
    $result = [];
    foreach ($configs as $config) {
        $method = $config['payment_method'];
        if (!isset($result[$method])) {
            $result[$method] = [];
        }
        $result[$method][$config['config_key']] = $config['config_value'];
    }
    
    echo json_encode(['success' => true, 'config' => $result]);
}

function generateQRData($paymentMethod, $amount, $eventId, $transactionCode) {
    global $pdo;
    
    // Get payment config
        $stmt = $pdo->prepare("
            SELECT config_key, config_value 
            FROM payment_config 
            WHERE payment_method = ? AND is_active = 1
        ");
        $stmt->execute([ucfirst($paymentMethod)]);
        
        // If not found, try with exact case
        if (empty($stmt->fetchAll())) {
            $stmt = $pdo->prepare("
                SELECT config_key, config_value 
                FROM payment_config 
                WHERE payment_method = ? AND is_active = 1
            ");
            $stmt->execute([$paymentMethod]);
        }
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $config = [];
    foreach ($configs as $c) {
        $config[$c['config_key']] = $c['config_value'];
    }
    
    $qrString = '';
    $qrData = [];
    
    switch ($paymentMethod) {
        case 'momo':
            // MoMo has been removed - redirect to SePay
            $qrString = '';
            $qrData = [
                'type' => 'sepay_redirect',
                'message' => 'MoMo đã được thay thế bằng SePay Banking',
                'redirect_to' => 'sepay'
            ];
            break;
            
        case 'banking':
            // Convert bank code to BIN code for VietQR
            $bankCodeMap = [
                'VCB' => '970436', // Vietcombank
                'ICB' => '970415', // VietinBank
                'VPB' => '970432', // VPBank
                'HDB' => '970437', // HDBank
                'TPB' => '970423', // TPBank
            ];
            $bankCodeRaw = $config['bank_code'] ?? 'VCB';
            $bankCode = $bankCodeMap[$bankCodeRaw] ?? '970436';
            $accountNumber = $config['account_number'] ?? '1234567890';
            $accountName = $config['account_name'] ?? 'EVENT MANAGEMENT';
            $bankName = $config['bank_name'] ?? 'Vietcombank';
            
            // Generate VietQR URL with transaction code
            $transferContent = "SK{$eventId}_{$transactionCode}";
            $qrString = "https://img.vietqr.io/image/{$bankCode}-{$accountNumber}-compact2.png?amount={$amount}&addInfo=" . urlencode($transferContent);
            $qrData = [
                'type' => 'banking',
                'bank_code' => $bankCode,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'amount' => $amount,
                'note' => $transferContent,
                'transaction_code' => $transactionCode,
                'qr_url' => $qrString
            ];
            break;
            
        default:
            $qrString = '';
            $qrData = [];
            break;
    }
    
    return [
        'qr_string' => $qrString,
        'qr_data' => json_encode($qrData)
    ];
}

function getQRCode() {
    global $pdo;
    
    $paymentId = $_GET['payment_id'] ?? null;
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID thanh toán']);
        return;
    }
    
    // Check if user has permission to view this payment
    $userId = $_SESSION['user']['ID_User'];
    $stmt = $pdo->prepare("
        SELECT t.*, dl.TenSuKien
        FROM thanhtoan t
        JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE t.ID_ThanhToan = ? AND kh.ID_User = ?
    ");
    $stmt->execute([$paymentId, $userId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy thanh toán hoặc không có quyền xem']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'payment' => [
            'id' => $payment['ID_ThanhToan'],
            'amount' => $payment['SoTien'],
            'method' => $payment['PhuongThuc'],
            'status' => $payment['TrangThai'],
            'transaction_code' => $payment['MaGiaoDich'],
            'event_name' => $payment['TenSuKien']
        ]
    ]);
}

function generateQRCode() {
    global $pdo;
    
    $paymentId = $_POST['payment_id'] ?? null;
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID thanh toán']);
        return;
    }
    
    // Get payment details
    $stmt = $pdo->prepare("SELECT * FROM thanhtoan WHERE ID_ThanhToan = ?");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy thanh toán']);
        return;
    }
    
    // Determine payment method from database value
    $paymentMethod = '';
    switch ($payment['PhuongThuc']) {
        case 'Momo':
            $paymentMethod = 'momo';
            break;
        case 'Chuyển khoản':
            $paymentMethod = 'banking';
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Phương thức thanh toán không hỗ trợ QR']);
            return;
    }
    
    // Generate new QR data
    $qrData = generateQRData($paymentMethod, $payment['SoTien'], $payment['ID_DatLich'], $payment['MaGiaoDich']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Tạo mã QR thành công',
        'qr_code' => $qrData['qr_string'],
        'qr_data' => json_decode($qrData['qr_data'], true)
    ]);
}

function createMoMoPayment() {
    global $pdo;
    
    $eventId = $_POST['event_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $paymentType = $_POST['payment_type'] ?? 'deposit';
    
    // Validate input
    if (!$eventId || !$amount) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin thanh toán']);
        return;
    }
    
    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Số tiền không hợp lệ']);
        return;
    }
    
    // Check if event exists and belongs to user
    $userId = $_SESSION['user']['ID_User'];
    $stmt = $pdo->prepare("
        SELECT dl.*, kh.HoTen, kh.SoDienThoai 
        FROM datlichsukien dl
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE dl.ID_DatLich = ? AND kh.ID_User = ?
    ");
    $stmt->execute([$eventId, $userId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy sự kiện hoặc bạn không có quyền thanh toán']);
        return;
    }
    
    // Check if event is approved
    if ($event['TrangThaiDuyet'] !== 'Đã duyệt') {
        echo json_encode(['success' => false, 'error' => 'Sự kiện chưa được duyệt, không thể thanh toán']);
        return;
    }
    
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
    
    // Generate transaction code
    $transactionCode = 'TXN' . date('YmdHis') . rand(1000, 9999);
    $orderId = "EVENT_{$eventId}_{$transactionCode}";
    $orderInfo = "Thanh toán {$paymentType} cho sự kiện: {$event['TenSuKien']}";
    $extraData = json_encode([
        'event_id' => $eventId,
        'payment_type' => $paymentType,
        'user_id' => $userId
    ]);
    
    // Create MoMo payment
    $result = $momo->createPayment($orderId, $amount, $orderInfo, $extraData);
    
    if ($result && isset($result['payUrl'])) {
        // Save payment record
        $stmt = $pdo->prepare("
            INSERT INTO thanhtoan (ID_DatLich, SoTien, LoaiThanhToan, PhuongThuc, TrangThai, MaGiaoDich, GhiChu) 
            VALUES (?, ?, ?, 'Momo', 'Đang xử lý', ?, ?)
        ");
        
        $paymentTypeDB = $paymentType === 'deposit' ? 'Đặt cọc' : 'Thanh toán đủ';
        $note = "Thanh toán {$paymentTypeDB} qua MoMo cho sự kiện: {$event['TenSuKien']}";
        
        $stmt->execute([
            $eventId,
            $amount,
            $paymentTypeDB,
            $transactionCode,
            $note
        ]);
        
        $paymentId = $pdo->lastInsertId();
        
        // Update event payment status to "Chờ thanh toán" until payment is confirmed
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = 'Chờ thanh toán', 
                TienCoc = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE TienCoc END,
                TienConLai = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE 0 END
            WHERE ID_DatLich = ?
        ");
        
        $remainingAmount = $event['TongTien'] - $amount;
        $stmt->execute([
            $paymentTypeDB,
            $amount,
            $paymentTypeDB,
            $remainingAmount,
            $eventId
        ]);
        
        // Insert payment history
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
            VALUES (?, 'created', 'Chưa thanh toán', 'Đang xử lý', ?)
        ");
        $stmt->execute([
            $paymentId,
            "Tạo thanh toán MoMo {$paymentTypeDB} - {$transactionCode}"
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Tạo thanh toán MoMo thành công',
            'payment_id' => $paymentId,
            'transaction_code' => $transactionCode,
            'pay_url' => $result['payUrl'],
            'order_id' => $orderId,
            'amount' => $amount,
            'payment_method' => 'Momo',
            'payment_type' => $paymentTypeDB
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Không thể tạo thanh toán MoMo: ' . ($result['message'] ?? 'Lỗi không xác định')
        ]);
    }
}

function verifyMoMoPayment() {
    global $pdo;
    
    $orderId = $_POST['order_id'] ?? null;
    
    if (!$orderId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu order ID']);
        return;
    }
    
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
    
    // Get payment status from MoMo
    $result = $momo->getPaymentStatus($orderId);
    
    if ($result && isset($result['resultCode'])) {
        echo json_encode([
            'success' => true,
            'status' => $result['resultCode'],
            'message' => $result['message'] ?? '',
            'data' => $result
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Không thể kiểm tra trạng thái thanh toán'
        ]);
    }
}

function handleMoMoWebhook() {
    global $pdo;
    
    // Get webhook data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid webhook data']);
        return;
    }
    
    // Log webhook data
    error_log("MoMo Webhook received: " . $input);
    
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
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid signature']);
        return;
    }
    
    // Process payment result
    $orderId = $data['orderId'] ?? '';
    $resultCode = $data['resultCode'] ?? '';
    $amount = $data['amount'] ?? 0;
    $transId = $data['transId'] ?? '';
    
    // Find payment record
    $stmt = $pdo->prepare("SELECT * FROM thanhtoan WHERE MaGiaoDich LIKE ?");
    $stmt->execute(["%{$orderId}%"]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Payment not found']);
        return;
    }
    
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
        
        // Calculate remaining amount correctly
        $stmt = $pdo->prepare("SELECT TongTien FROM datlichsukien WHERE ID_DatLich = ?");
        $stmt->execute([$payment['ID_DatLich']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $remainingAmount = $paymentType === 'Đặt cọc' ? 
            ($event['TongTien'] - $amount) : 0;
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
        "MoMo webhook: {$resultCode} - " . ($data['message'] ?? '')
    ]);
    
    // Log webhook processing
    error_log("MoMo Webhook processed: Order {$orderId}, Status {$newStatus}");
    
    echo json_encode(['success' => true, 'message' => 'Webhook processed successfully']);
}

/**
 * Xác nhận thanh toán tiền mặt
 */
function confirmCashPayment() {
    global $pdo;
    
    $paymentId = $_POST['payment_id'] ?? null;
    $confirmNote = $_POST['confirm_note'] ?? '';
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID thanh toán']);
        return;
    }
    
    // Get payment details
    $stmt = $pdo->prepare("SELECT * FROM thanhtoan WHERE ID_ThanhToan = ?");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy thanh toán']);
        return;
    }
    
    // Check if payment is cash payment
    if ($payment['PhuongThuc'] !== 'Tiền mặt') {
        echo json_encode(['success' => false, 'error' => 'Chỉ có thể xác nhận thanh toán tiền mặt']);
        return;
    }
    
    // Check if payment is pending
    if ($payment['TrangThai'] !== 'Đang xử lý' && $payment['TrangThai'] !== 'Chờ thanh toán') {
        echo json_encode(['success' => false, 'error' => 'Thanh toán đã được xử lý']);
        return;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update payment status
        $stmt = $pdo->prepare("UPDATE thanhtoan SET TrangThai = 'Thành công' WHERE ID_ThanhToan = ?");
        $stmt->execute([$paymentId]);
        
        // Update event payment status based on payment type
        $paymentType = $payment['LoaiThanhToan'];
        $newEventStatus = $paymentType === 'Đặt cọc' ? 'Đã đặt cọc' : 'Đã thanh toán đủ';
        
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = ?, 
                TienCoc = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE TienCoc END,
                TienConLai = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE 0 END
            WHERE ID_DatLich = ?
        ");
        
        // For cash payments, calculate remaining amount correctly
        // Get total event amount to calculate remaining
        $stmt = $pdo->prepare("SELECT TongTien FROM datlichsukien WHERE ID_DatLich = ?");
        $stmt->execute([$payment['ID_DatLich']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $remainingAmount = $paymentType === 'Đặt cọc' ? 
            ($event['TongTien'] - $payment['SoTien']) : 0;
        
        $stmt->execute([
            $newEventStatus,
            $paymentType,
            $payment['SoTien'],
            $paymentType,
            $remainingAmount,
            $payment['ID_DatLich']
        ]);
        
        // Insert payment history
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
            VALUES (?, 'cash_confirmed', 'Đang xử lý', 'Thành công', ?)
        ");
        $description = "Xác nhận thanh toán tiền mặt" . ($confirmNote ? " - Ghi chú: {$confirmNote}" : "");
        $stmt->execute([$paymentId, $description]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Xác nhận thanh toán tiền mặt thành công',
            'payment_id' => $paymentId,
            'new_status' => 'Thành công'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Lấy trạng thái thanh toán
 */
function getPaymentStatus() {
    global $pdo;
    
    $paymentId = $_GET['payment_id'] ?? null;
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID thanh toán']);
        return;
    }
    
    // Get payment details with event info
    $stmt = $pdo->prepare("
        SELECT t.*, dl.TenSuKien, dl.NgayBatDau, dl.NgayKetThuc,
               kh.HoTen as KhachHangTen, kh.SoDienThoai
        FROM thanhtoan t
        JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE t.ID_ThanhToan = ?
    ");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy thanh toán']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'payment' => $payment
    ]);
}

/**
 * Lấy danh sách thanh toán cho admin
 */
function getPaymentList() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT t.*, dl.TenSuKien, dl.NgayBatDau, dl.NgayKetThuc,
               kh.HoTen as KhachHangTen, kh.SoDienThoai
        FROM thanhtoan t
        JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        ORDER BY t.NgayThanhToan DESC
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'payments' => $payments
    ]);
}

/**
 * Lấy thống kê thanh toán
 */
function getPaymentStats() {
    global $pdo;
    
    // Total payments
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM thanhtoan");
    $stmt->execute();
    $total = $stmt->fetchColumn();
    
    // Successful payments
    $stmt = $pdo->prepare("SELECT COUNT(*) as successful FROM thanhtoan WHERE TrangThai = 'Thành công'");
    $stmt->execute();
    $successful = $stmt->fetchColumn();
    
    // Total amount
    $stmt = $pdo->prepare("SELECT SUM(SoTien) as total_amount FROM thanhtoan WHERE TrangThai = 'Thành công'");
    $stmt->execute();
    $totalAmount = $stmt->fetchColumn() ?: 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total' => $total,
            'successful' => $successful,
            'total_amount' => $totalAmount
        ]
    ]);
}

// SePay Payment Functions
function createSePayPayment() {
    global $pdo;
    
    $eventId = $_POST['event_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $paymentType = $_POST['payment_type'] ?? 'deposit';
    
    if (!$eventId || !$amount) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin thanh toán']);
        return;
    }
    
    try {
        // Get event details
        $stmt = $pdo->prepare("
            SELECT dl.*, k.HoTen, k.SoDienThoai, u.Email
            FROM datlichsukien dl
            INNER JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
            LEFT JOIN users u ON k.ID_User = u.ID_User
            WHERE dl.ID_DatLich = ?
        ");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy sự kiện']);
            return;
        }
        
        // Create payment record - Đang xử lý, không tự động cập nhật
        $paymentId = 'SEPAY_' . time() . '_' . rand(1000, 9999);
        $stmt = $pdo->prepare("
            INSERT INTO thanhtoan (
                ID_DatLich, MaGiaoDich, SoTien, PhuongThuc, LoaiThanhToan, 
                TrangThai, NgayThanhToan, GhiChu
            ) VALUES (?, ?, ?, 'Chuyển khoản', ?, 'Đang xử lý', NOW(), ?)
        ");
        $stmt->execute([
            $eventId, $paymentId, $amount, $paymentType, 
            'Thanh toán qua SePay - ' . $event['TenSuKien']
        ]);
        
        $insertedId = $pdo->lastInsertId();
        
        // Update event payment status - Đang xử lý, không tự động cập nhật
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = 'Đang xử lý'
            WHERE ID_DatLich = ?
        ");
        $stmt->execute([$eventId]);
        
        // Thêm lịch sử thanh toán
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (
                payment_id, action, old_status, new_status, description
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $insertedId,
            'create_payment',
            'Chưa thanh toán',
            'Đang xử lý',
            'Tạo thanh toán SePay - ' . $paymentType
        ]);
        
               // Create SePay checkout form
               try {
                   // Debug logging
                   error_log('SePay Payment Debug: ' . json_encode([
                       'event_id' => $eventId,
                       'amount' => $amount,
                       'payment_type' => $paymentType,
                       'event_data' => [
                           'HoTen' => $event['HoTen'] ?? 'NULL',
                           'SoDienThoai' => $event['SoDienThoai'] ?? 'NULL',
                           'Email' => $event['Email'] ?? 'NULL',
                           'TenSuKien' => $event['TenSuKien'] ?? 'NULL'
                       ]
                   ]));
                   
                   // SePay is a bank account management service, not a payment gateway
                   // Get bank account info from database
                   $stmt = $pdo->prepare("
                       SELECT config_key, config_value 
                       FROM payment_config 
                       WHERE payment_method = 'Banking' AND is_active = 1
                   ");
                   $stmt->execute();
                   
                   // If Banking not found, try 'banking'
                   if (empty($stmt->fetchAll())) {
                       $stmt = $pdo->prepare("
                           SELECT config_key, config_value 
                           FROM payment_config 
                           WHERE payment_method = 'banking' AND is_active = 1
                       ");
                       $stmt->execute();
                   }
                   $bankConfig = [];
                   while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                       $bankConfig[$row['config_key']] = $row['config_value'];
                   }
                   
                   // Use actual bank account info or fallback to default
                   // Convert bank code to BIN code for VietQR
                   $bankCodeMap = [
                       'VCB' => '970436', // Vietcombank
                       'ICB' => '970415', // VietinBank
                       'VPB' => '970432', // VPBank
                       'HDB' => '970437', // HDBank
                       'TPB' => '970423', // TPBank
                   ];
                   $bankCode = $bankCodeMap[$bankConfig['bank_code']] ?? '970436'; // Default to Vietcombank
                   $accountNumber = $bankConfig['account_number'] ?? '1234567890';
                   $accountName = $bankConfig['account_name'] ?? 'CONG TY TNHH DEMO';
                   $bankName = $bankConfig['bank_name'] ?? 'Vietcombank';
                   
                   // Generate proper transfer content with transaction ID
                   $transferContent = 'SK' . $eventId . '_' . $paymentId;
                   // Use compact2 format for QR code with amount and transaction info
                   $qrCodeUrl = 'https://img.vietqr.io/image/' . $bankCode . '-' . $accountNumber . '-compact2.png?amount=' . $amount . '&addInfo=' . urlencode($transferContent);
                   
                   echo json_encode([
                       'success' => true,
                       'payment_id' => $insertedId,
                       'payment_code' => $paymentId,
                       'transaction_id' => $paymentId, // Thêm transaction_id để frontend hiển thị
                       'amount' => $amount,
                       'event_name' => $event['TenSuKien'],
                       'customer_name' => $event['HoTen'],
                       'payment_method' => 'bank_transfer',
                       'message' => 'QR Code đã được tạo. Vui lòng quét mã để thanh toán.',
                       'qr_code' => $qrCodeUrl,
                       'bank_info' => [
                           'bank_name' => $bankName,
                           'bank_code' => $bankCode,
                           'account_number' => $accountNumber,
                           'account_name' => $accountName,
                           'amount' => $amount,
                           'content' => $transferContent,
                           'transaction_id' => $paymentId,
                           'event_id' => $eventId
                       ],
                       'auto_updated' => false,
                       'waiting_payment' => true
                   ]);
                   return;
                   
                   $sepay = new \SePay\SePayClient(
                       SEPAY_PARTNER_CODE,
                       SEPAY_SECRET_KEY,
                       SEPAY_ENVIRONMENT,
                       SEPAY_API_TOKEN
                   );
            
            $checkoutData = \SePay\Builders\CheckoutBuilder::make()
                ->paymentMethod('BANK_TRANSFER')
                ->currency('VND')
                ->orderInvoiceNumber('INV-' . $eventId . '-' . time())
                ->orderAmount($amount)
                ->operation('PURCHASE')
                ->orderDescription('Thanh toán sự kiện - ' . ($event['TenSuKien'] ?? 'Sự kiện'))
                ->customerName($event['HoTen'] ?? 'Khách hàng')
                ->customerPhone($event['SoDienThoai'] ?? '')
                ->customerEmail($event['Email'] ?? '')
                ->build();
            
            $formHtml = $sepay->checkout()->generateFormHtml($checkoutData);
            
            echo json_encode([
                'success' => true,
                'payment_id' => $insertedId,
                'payment_code' => $paymentId,
                'amount' => $amount,
                'event_name' => $event['TenSuKien'],
                'customer_name' => $event['HoTen'],
                'form_html' => $formHtml,
                'pay_url' => SEPAY_BASE_URL . '/checkout'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'error' => 'Lỗi tạo form SePay: ' . $e->getMessage()
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi tạo thanh toán: ' . $e->getMessage()]);
    }
}

function getSePayForm() {
    $eventId = $_GET['event_id'] ?? null;
    $amount = $_GET['amount'] ?? null;
    $paymentType = $_GET['payment_type'] ?? 'deposit';
    
    if (!$eventId || !$amount) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
        return;
    }
    
    try {
        // Initialize SePay client
                   $sepay = new \SePay\SePayClient(
                       SEPAY_PARTNER_CODE,
                       SEPAY_SECRET_KEY,
                       SEPAY_ENVIRONMENT,
                       SEPAY_API_TOKEN
                   );
        
        // Create checkout data
        $checkoutData = \SePay\Builders\CheckoutBuilder::make()
            ->paymentMethod('BANK_TRANSFER')
            ->currency('VND')
            ->orderInvoiceNumber('INV-' . $eventId . '-' . time())
            ->orderAmount($amount)
            ->operation('PURCHASE')
            ->orderDescription('Thanh toán sự kiện - ID: ' . $eventId)
            ->build();
        
        // Generate form HTML
        $formHtml = $sepay->checkout()->generateFormHtml($checkoutData);
        
        echo json_encode([
            'success' => true,
            'form_html' => $formHtml,
            'amount' => $amount,
            'payment_type' => $paymentType
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi tạo form SePay: ' . $e->getMessage()]);
    }
}

function processSePayCallback() {
    global $pdo;
    
    // Get callback data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid callback data']);
        return;
    }
    
    try {
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
            return;
        }
        
        $pdo->beginTransaction();
        
        if ($status === 'SUCCESS') {
            // Cập nhật thanh toán thành công
            $stmt = $pdo->prepare("
                UPDATE thanhtoan 
                SET TrangThai = 'Thành công', NgayThanhToan = NOW()
                WHERE ID_ThanhToan = ?
            ");
            $stmt->execute([$payment['ID_ThanhToan']]);
            
            // Cập nhật trạng thái sự kiện
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
            
            // Thêm lịch sử thanh toán
            $stmt = $pdo->prepare("
                INSERT INTO payment_history (
                    payment_id, action, old_status, new_status, description
                ) VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $payment['ID_ThanhToan'], 
                'webhook_success',
                $payment['TrangThai'], 
                'Thành công', 
                'SePay webhook - Chuyển khoản thành công: ' . $status
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
        echo json_encode(['success' => false, 'error' => 'Callback error: ' . $e->getMessage()]);
    }
}

function confirmBankingPayment() {
    global $pdo;
    
    $paymentId = $_POST['payment_id'] ?? '';
    $confirmNote = $_POST['confirm_note'] ?? '';
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID thanh toán']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Lấy thông tin thanh toán
        $stmt = $pdo->prepare("
            SELECT t.*, dl.TongTien, dl.HoTen, dl.TenSuKien
            FROM thanhtoan t
            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            WHERE t.ID_ThanhToan = ?
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception('Không tìm thấy thanh toán');
        }
        
        // Cập nhật thanh toán thành công
        $stmt = $pdo->prepare("
            UPDATE thanhtoan 
            SET TrangThai = 'Thành công', 
                PhuongThuc = 'Chuyển khoản',
                NgayThanhToan = NOW()
            WHERE ID_ThanhToan = ?
        ");
        $stmt->execute([$paymentId]);
        
        // Cập nhật trạng thái sự kiện
        $eventStatus = $payment['LoaiThanhToan'] === 'Đặt cọc' ? 'Đã đặt cọc' : 'Đã thanh toán đủ';
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = ?,
                TienCoc = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE TienCoc END,
                TienConLai = CASE WHEN ? = 'Đặt cọc' THEN ? ELSE 0 END
            WHERE ID_DatLich = ?
        ");
        
        $remainingAmount = $payment['LoaiThanhToan'] === 'Đặt cọc' ? 
            ($payment['TongTien'] - $payment['SoTien']) : 0;
        
        $stmt->execute([
            $eventStatus,
            $payment['LoaiThanhToan'],
            $payment['SoTien'],
            $payment['LoaiThanhToan'],
            $remainingAmount,
            $payment['ID_DatLich']
        ]);
        
        // Thêm lịch sử thanh toán
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (
                payment_id, action, old_status, new_status, description
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $paymentId,
            'manual_confirm',
            $payment['TrangThai'],
            'Thành công',
            'Xác nhận chuyển khoản thủ công' . ($confirmNote ? ' - ' . $confirmNote : '')
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Xác nhận chuyển khoản thành công',
            'payment' => [
                'id' => $payment['ID_ThanhToan'],
                'customer' => $payment['HoTen'],
                'event' => $payment['TenSuKien'],
                'amount' => $payment['SoTien'],
                'type' => $payment['LoaiThanhToan']
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Cancel payment and reset to initial state
function cancelPayment() {
    global $pdo;
    
    $eventId = $_POST['event_id'] ?? '';
    
    if (!$eventId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID sự kiện']);
        return;
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['ID_User'])) {
        echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
        return;
    }
    
    $userId = $_SESSION['user']['ID_User'];
    
    try {
        $pdo->beginTransaction();
        
        // Lấy thông tin thanh toán đang xử lý và kiểm tra ownership
        $stmt = $pdo->prepare("
            SELECT t.*, dl.TongTien, dl.TenSuKien, kh.HoTen
            FROM thanhtoan t
            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            WHERE t.ID_DatLich = ? AND t.TrangThai = 'Đang xử lý' AND kh.ID_User = ?
            ORDER BY t.ID_ThanhToan DESC
            LIMIT 1
        ");
        $stmt->execute([$eventId, $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception('Không tìm thấy thanh toán đang xử lý hoặc bạn không có quyền hủy thanh toán này');
        }
        
        // Cập nhật thanh toán thành "Đã hủy"
        $stmt = $pdo->prepare("
            UPDATE thanhtoan 
            SET TrangThai = 'Đã hủy',
                GhiChu = CONCAT(GhiChu, ' - Hủy bởi khách hàng: ', NOW())
            WHERE ID_ThanhToan = ?
        ");
        $stmt->execute([$payment['ID_ThanhToan']]);
        
        // Reset trạng thái sự kiện về "Chưa thanh toán"
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = 'Chưa thanh toán',
                TienCoc = 0,
                TienConLai = TongTien
            WHERE ID_DatLich = ?
        ");
        $stmt->execute([$eventId]);
        
        // Thêm lịch sử thanh toán
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (
                payment_id, action, old_status, new_status, description
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $payment['ID_ThanhToan'],
            'cancel_payment',
            'Đang xử lý',
            'Đã hủy',
            'Khách hàng hủy thanh toán'
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Đã hủy thanh toán thành công',
            'event' => [
                'id' => $eventId,
                'customer' => $payment['HoTen'],
                'event_name' => $payment['TenSuKien'],
                'amount' => $payment['SoTien']
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Lỗi: ' . $e->getMessage()]);
    }
}
?>