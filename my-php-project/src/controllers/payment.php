<?php
// Payment Controller - SePay Only
// Removed MoMo integration completely

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sepay.php';
require_once __DIR__ . '/../../vendor/sepay/autoload.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'create_payment':
            createPayment();
            break;
            
        case 'get_payment_history':
            getPaymentHistory();
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
            
        case 'generate_qr':
            generateQRCode();
            break;
            
        case 'create_sepay_payment':
            createSePayPayment();
            break;
            
        case 'sepay_callback':
            processSePayCallback();
            break;
            
        case 'get_sepay_form':
            getSePayForm();
            break;
            
        case 'confirm_cash_payment':
            confirmCashPayment();
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
            
        case 'confirm_banking_payment':
            confirmBankingPayment();
            break;
            
        case 'cancel_payment':
            cancelPayment();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
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
    
    // Only support SePay and Cash payments
    if (!in_array($paymentMethod, ['sepay', 'cash'])) {
        echo json_encode(['success' => false, 'error' => 'Chỉ hỗ trợ thanh toán SePay Banking và tiền mặt']);
        return;
    }
    
    try {
    // Generate transaction code
    $transactionCode = 'TXN' . date('YmdHis') . rand(1000, 9999);
    
        // Save payment record
        $stmt = $pdo->prepare("
            INSERT INTO thanhtoan (ID_DatLich, SoTien, LoaiThanhToan, PhuongThuc, TrangThai, MaGiaoDich, GhiChu) 
            VALUES (?, ?, ?, ?, 'Đang xử lý', ?, ?)
        ");
        
        $paymentTypeDB = $paymentType === 'deposit' ? 'Đặt cọc' : 'Thanh toán đủ';
        // Store SePay as 'Chuyển khoản' to match existing ENUM
        $paymentMethodDB = $paymentMethod === 'sepay' ? 'Chuyển khoản' : 'Tiền mặt';
        $note = "Thanh toán {$paymentTypeDB} qua {$paymentMethodDB} cho sự kiện: {$event['TenSuKien']}";
        
        $stmt->execute([
            $eventId,
            $amount,
            $paymentTypeDB,
            $paymentMethodDB,
            $transactionCode,
            $note
        ]);
        
        $paymentId = $pdo->lastInsertId();
        
        // Reflect deposit status immediately on booking to hide payment button in UI
        if ($paymentTypeDB === 'Đặt cọc') {
            $stmt = $pdo->prepare("UPDATE datlichsukien SET TrangThaiThanhToan = 'Đã đặt cọc' WHERE ID_DatLich = ?");
            $stmt->execute([$eventId]);
        }
        
        // Generate QR data
        $qrData = generateQRData($paymentMethod, $amount, $eventId, $transactionCode);
        
        echo json_encode([
            'success' => true,
            'payment_id' => $paymentId,
            'transaction_code' => $transactionCode,
            'amount' => $amount,
            'payment_method' => $paymentMethodDB,
            'qr_code' => $qrData['qr_string'],
            'qr_data' => $qrData['qr_data'],
            'message' => 'Tạo thanh toán thành công'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
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
        case 'sepay':
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
            
        case 'cash':
            $qrString = "CASH_PAYMENT_{$eventId}_{$transactionCode}";
            $qrData = [
                'type' => 'cash',
                'amount' => $amount,
                'transaction_code' => $transactionCode,
                'instructions' => 'Liên hệ trực tiếp để thanh toán tiền mặt'
            ];
            break;
            
        default:
            $qrString = '';
            $qrData = [];
            break;
    }
    
    return [
        'qr_string' => $qrString,
        'qr_data' => $qrData
    ];
}

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
    
        // Create payment record
        $paymentId = 'SEPAY_' . time() . '_' . rand(1000, 9999);
        $stmt = $pdo->prepare("
            INSERT INTO thanhtoan (ID_DatLich, SoTien, LoaiThanhToan, PhuongThuc, TrangThai, MaGiaoDich, GhiChu) 
            VALUES (?, ?, ?, 'Chuyển khoản', 'Đang xử lý', ?, ?)
        ");
        
        $paymentTypeDB = $paymentType === 'deposit' ? 'Đặt cọc' : 'Thanh toán đủ';
        $stmt->execute([
            $eventId,
            $amount,
            $paymentTypeDB,
            $paymentId,
            "Tạo thanh toán SePay {$paymentTypeDB} - {$paymentId}"
        ]);
        
        $insertedId = $pdo->lastInsertId();
        
        // Reflect deposit status immediately on booking to hide payment button in UI
        if ($paymentTypeDB === 'Đặt cọc') {
            $stmt = $pdo->prepare("UPDATE datlichsukien SET TrangThaiThanhToan = 'Đã đặt cọc' WHERE ID_DatLich = ?");
            $stmt->execute([$eventId]);
        }
        
        // Add payment history
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $insertedId,
            'create_payment',
            'Chưa thanh toán',
            'Đang xử lý',
            'Tạo thanh toán SePay - ' . $paymentType
        ]);
        
        // Get bank account info from database
        $stmt = $pdo->prepare("
            SELECT config_key, config_value 
            FROM payment_config 
            WHERE payment_method = 'Banking' AND is_active = 1
        ");
        $stmt->execute();
        $bankConfig = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If Banking not found, try 'banking'
        if (empty($bankConfig)) {
    $stmt = $pdo->prepare("
        SELECT config_key, config_value 
        FROM payment_config 
                WHERE payment_method = 'banking' AND is_active = 1
    ");
    $stmt->execute();
            $bankConfig = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Convert to associative array
        $bankConfigArray = [];
        foreach ($bankConfig as $row) {
            $bankConfigArray[$row['config_key']] = $row['config_value'];
        }
        
        // Use actual bank account info or fallback to default
        $bankCodeMap = [
            'VCB' => '970436', // Vietcombank
            'ICB' => '970415', // VietinBank
            'VPB' => '970432', // VPBank
            'HDB' => '970437', // HDBank
            'TPB' => '970423', // TPBank
        ];
        $bankCode = $bankCodeMap[$bankConfigArray['bank_code'] ?? 'VCB'] ?? '970436';
        $accountNumber = $bankConfigArray['account_number'] ?? '1234567890';
        $accountName = $bankConfigArray['account_name'] ?? 'CONG TY TNHH DEMO';
        $bankName = $bankConfigArray['bank_name'] ?? 'Vietcombank';
        
        // Generate proper transfer content with transaction ID
        $transferContent = 'SK' . $eventId . '_' . $paymentId;
        $qrCodeUrl = 'https://img.vietqr.io/image/' . $bankCode . '-' . $accountNumber . '-compact2.png?amount=' . $amount . '&addInfo=' . urlencode($transferContent);
        
        // Create fallback QR string with bank info
        $fallbackQrData = "Bank: {$bankName}\nAccount: {$accountNumber}\nAmount: {$amount}\nContent: {$transferContent}";
        $fallbackQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($fallbackQrData);
        
        // Also create a simple QR string for fallback
        $qrString = $qrCodeUrl;
        
        echo json_encode([
            'success' => true,
            'payment_id' => $insertedId,
            'payment_code' => $paymentId,
            'transaction_id' => $paymentId,
            'transaction_code' => $paymentId, // Add this for consistency
            'amount' => $amount,
            'event_name' => $event['TenSuKien'],
            'customer_name' => $event['HoTen'],
            'payment_method' => 'bank_transfer',
            'message' => 'QR Code đã được tạo. Vui lòng quét mã để thanh toán.',
            'qr_code' => $qrCodeUrl,
            'qr_string' => $qrString, // Add fallback QR string
            'fallback_qr' => $fallbackQrUrl, // Alternative QR service
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
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}

// Other functions remain the same...
function getPaymentHistory() {
    global $pdo;
    
    $userId = $_SESSION['user']['ID_User'];
    
    $stmt = $pdo->prepare("
        SELECT t.*, dl.TenSuKien, dl.NgayBatDau, dl.NgayKetThuc
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

function checkPaymentStatus() {
    global $pdo;
    
    $transactionCode = $_POST['transaction_code'] ?? '';
    
    if (!$transactionCode) {
        echo json_encode(['success' => false, 'error' => 'Thiếu mã giao dịch']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM thanhtoan 
        WHERE MaGiaoDich = ? OR MaGiaoDich LIKE ?
    ");
    $stmt->execute([$transactionCode, "%{$transactionCode}%"]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy giao dịch']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'payment' => $payment,
        'status' => $payment['TrangThai']
    ]);
}

function updatePaymentStatus() {
    global $pdo;
    
    $paymentId = $_POST['payment_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$paymentId || !$status) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
    
    // Update payment status
        $stmt = $pdo->prepare("UPDATE thanhtoan SET TrangThai = ? WHERE ID_ThanhToan = ?");
        $stmt->execute([$status, $paymentId]);
        
        // Update event payment status
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = ? 
            WHERE ID_DatLich = (SELECT ID_DatLich FROM thanhtoan WHERE ID_ThanhToan = ?)
        ");
        $stmt->execute([$status, $paymentId]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Lỗi cập nhật: ' . $e->getMessage()]);
    }
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
    
    echo json_encode(['success' => true, 'configs' => $configs]);
}

function generateQRCode() {
    global $pdo;
    
    $paymentId = $_POST['payment_id'] ?? null;
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID thanh toán']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM thanhtoan WHERE ID_ThanhToan = ?");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy thanh toán']);
        return;
    }
    
    $qrData = generateQRData($payment['PhuongThuc'], $payment['SoTien'], $payment['ID_DatLich'], $payment['MaGiaoDich']);
    
    echo json_encode([
        'success' => true,
        'qr_code' => $qrData['qr_string'],
        'qr_data' => $qrData['qr_data']
    ]);
}

function confirmCashPayment() {
    global $pdo;
    
    $paymentId = $_POST['payment_id'] ?? null;
    $confirmNote = $_POST['confirm_note'] ?? '';
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID thanh toán']);
        return;
    }
    
    try {
    $pdo->beginTransaction();
    
        // Update payment status
        $stmt = $pdo->prepare("UPDATE thanhtoan SET TrangThai = 'Thành công' WHERE ID_ThanhToan = ?");
        $stmt->execute([$paymentId]);
        
        // Update event payment status
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = 'Đã thanh toán đủ' 
            WHERE ID_DatLich = (SELECT ID_DatLich FROM thanhtoan WHERE ID_ThanhToan = ?)
        ");
        $stmt->execute([$paymentId]);
        
        // Add payment history
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
            VALUES (?, 'confirm_cash', 'Đang xử lý', 'Thành công', ?)
        ");
        $stmt->execute([$paymentId, 'Xác nhận thanh toán tiền mặt: ' . $confirmNote]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Xác nhận thanh toán tiền mặt thành công']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Lỗi xác nhận: ' . $e->getMessage()]);
    }
}

function getPaymentStatus() {
    global $pdo;
    
    $paymentId = $_GET['payment_id'] ?? null;
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID thanh toán']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM thanhtoan WHERE ID_ThanhToan = ?");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy thanh toán']);
        return;
    }
    
    echo json_encode(['success' => true, 'payment' => $payment]);
}

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
    
    echo json_encode(['success' => true, 'payments' => $payments]);
}

function getPaymentStats() {
    global $pdo;
    
    // Total payments
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM thanhtoan");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Successful payments
    $stmt = $pdo->prepare("SELECT COUNT(*) as success FROM thanhtoan WHERE TrangThai = 'Thành công'");
    $stmt->execute();
    $success = $stmt->fetch(PDO::FETCH_ASSOC)['success'];
    
    // Total amount
    $stmt = $pdo->prepare("SELECT SUM(SoTien) as total_amount FROM thanhtoan WHERE TrangThai = 'Thành công'");
    $stmt->execute();
    $totalAmount = $stmt->fetch(PDO::FETCH_ASSOC)['total_amount'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_payments' => $total,
            'successful_payments' => $success,
            'total_amount' => $totalAmount
        ]
    ]);
}

function processSePayCallback() {
    global $pdo;
    
    // Get callback data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid callback data']);
        return;
    }
    
    // Process SePay callback
    // This would be implemented based on SePay's callback format
    
    echo json_encode(['success' => true, 'message' => 'Callback processed']);
}

function getSePayForm() {
    $eventId = $_GET['event_id'] ?? null;
    $amount = $_GET['amount'] ?? null;
    $paymentType = $_GET['payment_type'] ?? 'deposit';
    
    if (!$eventId || !$amount) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
        return;
    }
    
    // Return SePay form HTML
        echo json_encode([
            'success' => true,
        'form_html' => '<div>SePay Form - Event: ' . $eventId . ', Amount: ' . $amount . '</div>'
    ]);
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
        
        // Update payment status
        $stmt = $pdo->prepare("UPDATE thanhtoan SET TrangThai = 'Thành công' WHERE ID_ThanhToan = ?");
        $stmt->execute([$paymentId]);
        
        // Update event payment status
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = 'Đã thanh toán đủ' 
            WHERE ID_DatLich = (SELECT ID_DatLich FROM thanhtoan WHERE ID_ThanhToan = ?)
        ");
        $stmt->execute([$paymentId]);
        
        // Add payment history
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
            VALUES (?, 'confirm_banking', 'Đang xử lý', 'Thành công', ?)
        ");
        $stmt->execute([$paymentId, 'Xác nhận thanh toán chuyển khoản: ' . $confirmNote]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Xác nhận thanh toán chuyển khoản thành công']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Lỗi xác nhận: ' . $e->getMessage()]);
    }
}

function cancelPayment() {
    global $pdo;
    
    $transactionId = $_POST['transaction_id'] ?? '';
    $eventId = $_POST['event_id'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        // If transaction_id is provided, find payment first
        if ($transactionId) {
            $stmt = $pdo->prepare("
                SELECT ID_ThanhToan, ID_DatLich, TrangThai 
                FROM thanhtoan 
                WHERE MaGiaoDich = ? OR MaGiaoDich LIKE ?
            ");
            $stmt->execute([$transactionId, "%{$transactionId}%"]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy giao dịch']);
                return;
            }
            
            // Only cancel if payment is pending
            if ($payment['TrangThai'] === 'Đang xử lý') {
                // Cancel the specific payment
                $stmt = $pdo->prepare("
                    UPDATE thanhtoan 
                    SET TrangThai = 'Hủy', GhiChu = CONCAT(IFNULL(GhiChu, ''), ' - Đã hủy bởi người dùng')
                    WHERE ID_ThanhToan = ?
                ");
                $stmt->execute([$payment['ID_ThanhToan']]);
                
                // Add payment history
                $stmt = $pdo->prepare("
                    INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
                    VALUES (?, 'cancel_payment', 'Đang xử lý', 'Hủy', 'Người dùng hủy thanh toán khi đóng modal')
                ");
                $stmt->execute([$payment['ID_ThanhToan']]);
                
                // Check if there are other pending payments for this event
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM thanhtoan 
                    WHERE ID_DatLich = ? AND TrangThai = 'Đang xử lý'
                ");
                $stmt->execute([$payment['ID_DatLich']]);
                $remainingPending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // Only reset event payment status if no other pending payments
                if ($remainingPending == 0) {
                    $stmt = $pdo->prepare("
                        UPDATE datlichsukien 
                        SET TrangThaiThanhToan = 'Chưa thanh toán' 
                        WHERE ID_DatLich = ?
                    ");
                    $stmt->execute([$payment['ID_DatLich']]);
                }
                
                $pdo->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Hủy thanh toán thành công',
                    'event_id' => $payment['ID_DatLich']
                ]);
            } else {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Không thể hủy thanh toán đã hoàn thành hoặc đã hủy']);
            }
        } 
        // Fallback: use event_id if provided (backward compatibility)
        else if ($eventId) {
            // Cancel all pending payments for this event
            $stmt = $pdo->prepare("
                UPDATE thanhtoan 
                SET TrangThai = 'Hủy', GhiChu = CONCAT(IFNULL(GhiChu, ''), ' - Đã hủy bởi người dùng')
                WHERE ID_DatLich = ? AND TrangThai = 'Đang xử lý'
            ");
            $stmt->execute([$eventId]);
            
            // Reset event payment status
            $stmt = $pdo->prepare("
                UPDATE datlichsukien 
                SET TrangThaiThanhToan = 'Chưa thanh toán' 
                WHERE ID_DatLich = ?
            ");
            $stmt->execute([$eventId]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Hủy thanh toán thành công']);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'Thiếu transaction_id hoặc event_id']);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Lỗi hủy thanh toán: ' . $e->getMessage()]);
    }
}
?>
