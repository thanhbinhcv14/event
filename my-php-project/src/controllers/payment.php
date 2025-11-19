<?php
// Controller Thanh toán - Chỉ hỗ trợ SePay
// Đã loại bỏ hoàn toàn tích hợp MoMo

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sepay.php';
require_once __DIR__ . '/../../vendor/sepay/autoload.php';
require_once __DIR__ . '/../auth/csrf.php';

header('Content-Type: application/json');

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Các action không yêu cầu CSRF (chỉ đọc)
$readOnlyActions = ['get_payment_history', 'check_payment_status', 'get_payment_config', 'get_payment_status', 'get_payment_list', 'get_payment_stats', 'get_sepay_form', 'verify_payment'];

// Các action yêu cầu bảo vệ CSRF (thay đổi dữ liệu)
$modifyActions = ['create_payment', 'update_payment_status', 'generate_qr', 'create_sepay_payment', 'confirm_cash_payment', 'confirm_banking_payment', 'cancel_payment'];

// Xác minh CSRF cho các action thay đổi dữ liệu
if (in_array($action, $modifyActions)) {
    requireCSRF();
}

// sepay_callback được xử lý bởi webhook, bỏ qua CSRF (xác thực webhook được xử lý riêng)
if ($action === 'sepay_callback') {
    // Xác thực webhook được xử lý riêng
}

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
            
        case 'verify_payment':
            verifyPayment();
            break;
            
        case 'cancel_payment':
            cancelPayment();
            break;
            
        case 'get_invoice':
            getInvoice();
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
    
    // Kiểm tra dữ liệu đầu vào
    if (!$eventId || !$amount || !$paymentMethod) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin thanh toán']);
        return;
    }
    
    // Kiểm tra số tiền
    if (!is_numeric($amount) || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Số tiền không hợp lệ']);
        return;
    }
    
    // Kiểm tra sự kiện có tồn tại và thuộc về người dùng không
    $userId = $_SESSION['user']['ID_User'];
    $stmt = $pdo->prepare("
        SELECT dl.*, kh.HoTen, kh.SoDienThoai, kh.DiaChi, kh.ID_KhachHang, u.Email as UserEmail
        FROM datlichsukien dl
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        LEFT JOIN users u ON kh.ID_User = u.ID_User
        WHERE dl.ID_DatLich = ? AND kh.ID_User = ?
    ");
    $stmt->execute([$eventId, $userId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy sự kiện hoặc bạn không có quyền thanh toán']);
        return;
    }
    
    // Kiểm tra sự kiện đã được duyệt chưa
    if ($event['TrangThaiDuyet'] !== 'Đã duyệt') {
        echo json_encode(['success' => false, 'error' => 'Sự kiện chưa được duyệt, không thể thanh toán']);
        return;
    }
    
    // Chỉ hỗ trợ thanh toán SePay và Tiền mặt
    if (!in_array($paymentMethod, ['sepay', 'cash'])) {
        echo json_encode(['success' => false, 'error' => 'Chỉ hỗ trợ thanh toán SePay Banking và tiền mặt']);
        return;
    }
    
    // Kiểm tra khoảng cách từ ngày đăng ký đến ngày tổ chức
    $daysFromRegistrationToEvent = 0;
    if (!empty($event['NgayTao']) && !empty($event['NgayBatDau'])) {
        $registrationDate = new DateTime($event['NgayTao']);
        $eventStartDate = new DateTime($event['NgayBatDau']);
        $daysFromRegistrationToEvent = $registrationDate->diff($eventStartDate)->days;
    }
    
    $paymentTypeDB = $paymentType === 'deposit' ? 'Đặt cọc' : 'Thanh toán đủ';
    
    // Nếu từ lúc đặt đến lúc diễn ra < 7 ngày (hoặc = 0): Bắt buộc thanh toán đủ, không cho đặt cọc
    if ($daysFromRegistrationToEvent < 7) {
        if ($paymentType === 'deposit') {
            echo json_encode([
                'success' => false, 
                'error' => 'Sự kiện này diễn ra trong vòng 7 ngày, bạn phải thanh toán đủ ngay. Không thể đặt cọc.'
            ]);
            return;
        }
        // Nếu là thanh toán đủ và < 7 ngày, cho phép tiếp tục (không cần kiểm tra đặt cọc)
        // Bỏ qua tất cả validation về deposit ở dưới
    } else if ($paymentTypeDB === 'Thanh toán đủ' && $daysFromRegistrationToEvent >= 7) {
        // Nếu từ lúc đặt đến lúc diễn ra ≥ 7 ngày: Bắt buộc đặt cọc trước khi thanh toán đủ
        // TRỪ KHI phương thức thanh toán là tiền mặt (cash) - cho phép thanh toán đủ trực tiếp
        if ($paymentMethod !== 'cash') {
            // Kiểm tra xem có thanh toán đặt cọc thành công không
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as deposit_count 
                FROM thanhtoan 
                WHERE ID_DatLich = ? 
                AND LoaiThanhToan = 'Đặt cọc' 
                AND TrangThai = 'Thành công'
            ");
            $stmt->execute([$eventId]);
            $depositCount = $stmt->fetch(PDO::FETCH_ASSOC)['deposit_count'];
            
            if ($depositCount == 0) {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Bạn cần đặt cọc trước khi thanh toán đủ. Vui lòng thực hiện thanh toán đặt cọc trước.'
                ]);
                return;
            }
            
            // Kiểm tra trạng thái thanh toán hiện tại
            if ($event['TrangThaiThanhToan'] !== 'Đã đặt cọc') {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Trạng thái thanh toán không hợp lệ. Vui lòng đặt cọc trước khi thanh toán đủ.'
                ]);
                return;
            }
        } else {
            // Tiền mặt: Cho phép thanh toán đủ trực tiếp, không cần đặt cọc trước
            // Bỏ qua validation về deposit và deadline
        }
        
        // Kiểm tra hạn thanh toán: Deadline = Ngày đặt cọc + 7 ngày (chỉ áp dụng nếu đã có đặt cọc)
        // Nếu là tiền mặt thanh toán đủ trực tiếp, bỏ qua kiểm tra deadline
        if ($paymentMethod !== 'cash' && !empty($event['NgayBatDau'])) {
            // Lấy ngày đặt cọc thành công đầu tiên
            $stmt = $pdo->prepare("
                SELECT NgayThanhToan 
                FROM thanhtoan 
                WHERE ID_DatLich = ? 
                AND LoaiThanhToan = 'Đặt cọc' 
                AND TrangThai = 'Thành công'
                ORDER BY NgayThanhToan ASC
                LIMIT 1
            ");
            $stmt->execute([$eventId]);
            $depositPayment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($depositPayment && !empty($depositPayment['NgayThanhToan'])) {
                $depositDate = new DateTime($depositPayment['NgayThanhToan']);
                $now = new DateTime();
                
                // Deadline luôn = đặt cọc + 7 ngày
                $deadlineDate = clone $depositDate;
                $deadlineDate->modify('+7 days');
                
                // Kiểm tra xem đã quá hạn chưa
                if ($now > $deadlineDate) {
                    $daysPastDeadline = $now->diff($deadlineDate)->days;
                    echo json_encode([
                        'success' => false, 
                        'error' => "Đã quá hạn thanh toán đủ (hạn: " . $deadlineDate->format('d/m/Y') . "). Vui lòng đến công ty đóng tiền mặt trước khi sự kiện diễn ra, nếu không sự kiện sẽ bị hủy và không hoàn lại cọc."
                    ]);
                    return;
                }
                
                // Kiểm tra xem có đang gần deadline không (trong vòng 3 ngày)
                $daysUntilDeadline = $now->diff($deadlineDate)->days;
                if ($daysUntilDeadline <= 3 && $daysUntilDeadline > 0) {
                    // Cho phép thanh toán nhưng cảnh báo người dùng
                    // Cảnh báo này sẽ được hiển thị trong response
                }
            }
        }
    }
    
    try {
    // Tạo mã giao dịch
    $transactionCode = 'TXN' . date('YmdHis') . rand(1000, 9999);
    
        // Lưu bản ghi thanh toán
        $stmt = $pdo->prepare("
            INSERT INTO thanhtoan (ID_DatLich, SoTien, LoaiThanhToan, PhuongThuc, TrangThai, MaGiaoDich, GhiChu) 
            VALUES (?, ?, ?, ?, 'Đang xử lý', ?, ?)
        ");
        
        // Lưu SePay dưới dạng 'Chuyển khoản' để khớp với ENUM hiện có
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
        
        // Lưu thông tin hóa đơn (nếu bảng tồn tại)
        try {
            // Kiểm tra xem bảng hoadon có tồn tại không
            $tableExists = false;
            try {
                $checkTable = $pdo->query("SHOW TABLES LIKE 'hoadon'");
                $tableExists = $checkTable->rowCount() > 0;
            } catch (Exception $e) {
                $tableExists = false;
            }
            
            if ($tableExists) {
                $invoiceData = $_POST['invoice_data'] ?? null;
                if ($invoiceData) {
                    // Nếu invoice_data là JSON string, decode nó
                    if (is_string($invoiceData)) {
                        $invoiceData = json_decode($invoiceData, true);
                    }
                    
                    if (is_array($invoiceData)) {
                        $invoiceName = $invoiceData['name'] ?? $event['HoTen'];
                        $invoicePhone = $invoiceData['phone'] ?? $event['SoDienThoai'];
                        // Email luôn lấy từ users (email đăng ký), không cho phép thay đổi từ form
                        $invoiceEmail = $event['UserEmail'] ?? $event['Email'] ?? null;
                        $invoiceAddress = $invoiceData['address'] ?? null;
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO hoadon (
                                ID_ThanhToan, ID_DatLich, ID_KhachHang, 
                                HoTen, SoDienThoai, Email, DiaChi
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $paymentId,
                            $eventId,
                            $event['ID_KhachHang'],
                            $invoiceName,
                            $invoicePhone,
                            $invoiceEmail,
                            $invoiceAddress
                        ]);
                    }
                } else {
                    // Nếu không có thông tin hóa đơn, lưu thông tin mặc định từ khách hàng
                    $stmt = $pdo->prepare("
                        INSERT INTO hoadon (
                            ID_ThanhToan, ID_DatLich, ID_KhachHang, 
                            HoTen, SoDienThoai, Email, DiaChi
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $paymentId,
                        $eventId,
                        $event['ID_KhachHang'],
                        $event['HoTen'],
                        $event['SoDienThoai'],
                        $event['UserEmail'] ?? $event['Email'] ?? null,
                        $event['DiaChi'] ?? null
                    ]);
                }
            }
        } catch (PDOException $e) {
            // Bảng hoadon có thể không tồn tại, bỏ qua lỗi này và tiếp tục
            error_log("Warning: Could not insert invoice data: " . $e->getMessage());
        }
        
        // Cập nhật trạng thái thanh toán ngay lập tức trên booking để ẩn nút thanh toán trong UI
        if ($paymentMethodDB === 'Tiền mặt') {
            // Nếu là thanh toán tiền mặt, cập nhật trạng thái thành "Thanh toán bằng tiền mặt"
            $stmt = $pdo->prepare("UPDATE datlichsukien SET TrangThaiThanhToan = 'Thanh toán bằng tiền mặt' WHERE ID_DatLich = ?");
            $stmt->execute([$eventId]);
        } elseif ($paymentTypeDB === 'Đặt cọc' && $event['TrangThaiThanhToan'] !== 'Đã đặt cọc') {
            // Nếu là thanh toán đặt cọc (chuyển khoản), cập nhật trạng thái thành "Đã đặt cọc"
            $stmt = $pdo->prepare("UPDATE datlichsukien SET TrangThaiThanhToan = 'Đã đặt cọc' WHERE ID_DatLich = ?");
            $stmt->execute([$eventId]);
        }
        
        // Tạo dữ liệu QR
        $qrData = generateQRData($paymentMethod, $amount, $eventId, $transactionCode);
        
        // Tính toán hạn thanh toán đủ (chỉ tính nếu đặt cọc và từ đăng ký đến tổ chức ≥ 7 ngày)
        $deadlineInfo = null;
        if ($paymentTypeDB === 'Đặt cọc' && $daysFromRegistrationToEvent >= 7 && !empty($event['NgayBatDau'])) {
            $depositDate = new DateTime(); // Ngày hiện tại (khi đặt cọc)
            $now = new DateTime();
            
            // Deadline luôn = đặt cọc + 7 ngày
            $deadlineDate = clone $depositDate;
            $deadlineDate->modify('+7 days');
            
            $daysUntilDeadline = $now->diff($deadlineDate)->days;
            
            $deadlineInfo = [
                'deadline_date' => $deadlineDate->format('Y-m-d H:i:s'),
                'deadline_formatted' => $deadlineDate->format('d/m/Y'),
                'days_until_deadline' => $daysUntilDeadline,
                'is_past_deadline' => $now > $deadlineDate,
                'is_approaching' => $daysUntilDeadline <= 3 && $daysUntilDeadline > 0,
                'days_from_registration_to_event' => $daysFromRegistrationToEvent
            ];
        }
        
        echo json_encode([
            'success' => true,
            'payment_id' => $paymentId,
            'transaction_code' => $transactionCode,
            'amount' => $amount,
            'payment_method' => $paymentMethodDB,
            'qr_code' => $qrData['qr_string'],
            'qr_data' => $qrData['qr_data'],
            'message' => 'Tạo thanh toán thành công',
            'deadline_info' => $deadlineInfo
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}

function generateQRData($paymentMethod, $amount, $eventId, $transactionCode) {
    global $pdo;
    
    // Lấy cấu hình thanh toán
    $stmt = $pdo->prepare("
        SELECT config_key, config_value 
        FROM payment_config 
        WHERE payment_method = ? AND is_active = 1
    ");
    $stmt->execute([ucfirst($paymentMethod)]);
    
    // Nếu không tìm thấy, thử với chữ hoa/thường chính xác
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
            // Chuyển đổi mã ngân hàng sang mã BIN cho VietQR
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
            
            // Tạo URL VietQR với mã giao dịch
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

/**
 * Tạo hàm gọi SePay API để tạo QR code
 */
function createSePayQRCode($amount, $content, $accountNumber = null) {
    try {
        $baseUrl = SEPAY_BASE_URL;
        $apiToken = SEPAY_API_TOKEN;
        
        // Gọi SePay API để tạo QR code
        // API endpoint: /v1/qr/create hoặc /createqr
        $url = $baseUrl . '/createqr';
        
        $data = [
            'amount' => $amount,
            'content' => $content,
            'accountNumber' => $accountNumber
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Apikey ' . $apiToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("SePay QR API Error: " . $curlError);
            return null;
        }
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result;
        } else {
            error_log("SePay QR API HTTP Error: " . $httpCode . " - " . $response);
            return null;
        }
    } catch (Exception $e) {
        error_log("SePay QR API Exception: " . $e->getMessage());
        return null;
    }
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
        // Lấy thông tin chi tiết sự kiện
    $stmt = $pdo->prepare("
            SELECT dl.*, k.HoTen, k.SoDienThoai, k.DiaChi, k.ID_KhachHang, u.Email as UserEmail
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
    
        // Tạo bản ghi thanh toán
        $paymentId = 'SEPAY_' . time() . '_' . rand(1000, 9999);
        
        // Tạo transferContent trước để lưu vào GhiChu (sẽ được tạo lại sau với insertedId)
        // Tạm thời dùng paymentId để tạo content
        $tempContent = 'SEPAY' . $eventId . substr($paymentId, -8); // Tạm thời
        
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
            "Tạo thanh toán SePay {$paymentTypeDB} - {$paymentId} - Content: {$tempContent}"
        ]);
        
        $insertedId = $pdo->lastInsertId();
        
        // Lưu thông tin hóa đơn (nếu bảng tồn tại)
        try {
            // Kiểm tra xem bảng hoadon có tồn tại không
            $tableExists = false;
            try {
                $checkTable = $pdo->query("SHOW TABLES LIKE 'hoadon'");
                $tableExists = $checkTable->rowCount() > 0;
            } catch (Exception $e) {
                $tableExists = false;
            }
            
            if ($tableExists) {
                $invoiceData = $_POST['invoice_data'] ?? null;
                if ($invoiceData) {
                    // Nếu invoice_data là JSON string, decode nó
                    if (is_string($invoiceData)) {
                        $invoiceData = json_decode($invoiceData, true);
                    }
                    
                    if (is_array($invoiceData)) {
                        $invoiceName = $invoiceData['name'] ?? $event['HoTen'];
                        $invoicePhone = $invoiceData['phone'] ?? $event['SoDienThoai'];
                        // Email luôn lấy từ users (email đăng ký), không cho phép thay đổi từ form
                        $invoiceEmail = $event['UserEmail'] ?? $event['Email'] ?? null;
                        $invoiceAddress = $invoiceData['address'] ?? null;
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO hoadon (
                                ID_ThanhToan, ID_DatLich, ID_KhachHang, 
                                HoTen, SoDienThoai, Email, DiaChi
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $insertedId,
                            $eventId,
                            $event['ID_KhachHang'],
                            $invoiceName,
                            $invoicePhone,
                            $invoiceEmail,
                            $invoiceAddress
                        ]);
                    }
                } else {
                    // Nếu không có thông tin hóa đơn, lưu thông tin mặc định từ khách hàng
                    $stmt = $pdo->prepare("
                        INSERT INTO hoadon (
                            ID_ThanhToan, ID_DatLich, ID_KhachHang, 
                            HoTen, SoDienThoai, Email, DiaChi
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $insertedId,
                        $eventId,
                        $event['ID_KhachHang'],
                        $event['HoTen'],
                        $event['SoDienThoai'],
                        $event['UserEmail'] ?? $event['Email'] ?? null,
                        $event['DiaChi'] ?? null
                    ]);
                }
            }
        } catch (PDOException $e) {
            // Bảng hoadon có thể không tồn tại, bỏ qua lỗi này và tiếp tục
            error_log("Warning: Could not insert invoice data: " . $e->getMessage());
        }
        
        // Cập nhật trạng thái đặt cọc ngay lập tức trên booking để ẩn nút thanh toán trong UI
        if ($paymentTypeDB === 'Đặt cọc') {
            $stmt = $pdo->prepare("UPDATE datlichsukien SET TrangThaiThanhToan = 'Đã đặt cọc' WHERE ID_DatLich = ?");
            $stmt->execute([$eventId]);
        }
        
        // Thêm lịch sử thanh toán
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
        
        // Lấy thông tin tài khoản ngân hàng từ database
        $stmt = $pdo->prepare("
            SELECT config_key, config_value 
            FROM payment_config 
            WHERE payment_method = 'Banking' AND is_active = 1
        ");
        $stmt->execute();
        $bankConfig = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nếu không tìm thấy Banking, thử 'banking'
        if (empty($bankConfig)) {
    $stmt = $pdo->prepare("
        SELECT config_key, config_value 
        FROM payment_config 
                WHERE payment_method = 'banking' AND is_active = 1
    ");
    $stmt->execute();
            $bankConfig = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Chuyển đổi sang mảng kết hợp
        $bankConfigArray = [];
        foreach ($bankConfig as $row) {
            $bankConfigArray[$row['config_key']] = $row['config_value'];
        }
        
        // Sử dụng thông tin tài khoản ngân hàng thực tế hoặc dùng giá trị mặc định
        $bankCodeMap = [
            'VCB' => '970436', // Vietcombank
            'ICB' => '970415', // VietinBank
            'VPB' => '970432', // VPBank
            'HDB' => '970437', // HDBank
            'TPB' => '970423', // TPBank
        ];
        $bankCode = $bankCodeMap[$bankConfigArray['bank_code'] ?? 'VCB'] ?? '970436';
        $accountNumber = $bankConfigArray['account_number'] ?? '100872918542';
        $accountName = $bankConfigArray['account_name'] ?? 'BUI THANH BINH';
        $bankName = $bankConfigArray['bank_name'] ?? 'VietinBank';
        
        // Tạo nội dung chuyển khoản đúng với pattern SePay: SEPAY + số (3-10 ký tự)
        // Format: SEPAY + eventId + ID_ThanhToan (chỉ số, tổng 3-10 ký tự sau "SEPAY")
        // Ví dụ: SEPAY20123 (eventId=20, ID_ThanhToan=123) → SEPAY + 20123 (5 số)
        // Cấu hình SePay: Prefix "SEPAY" + Suffix số (3-10 ký tự)
        
        $eventIdStr = (string)$eventId;
        $insertedIdStr = (string)$insertedId;
        
        // Tạo suffix: eventId + ID_ThanhToan
        $suffix = $eventIdStr . $insertedIdStr;
        
        // Giới hạn tổng độ dài trong khoảng 3-10 ký tự (theo cấu hình SePay)
        if (strlen($suffix) > 10) {
            // Nếu quá dài, chỉ lấy 10 ký tự cuối (ưu tiên ID_ThanhToan)
            $suffix = substr($insertedIdStr, -10);
            if (strlen($suffix) < 3) {
                $suffix = str_pad($suffix, 3, '0', STR_PAD_LEFT);
            }
        } elseif (strlen($suffix) < 3) {
            // Đảm bảo tối thiểu 3 ký tự
            $suffix = str_pad($suffix, 3, '0', STR_PAD_LEFT);
        }
        
        $transferContent = 'SEPAY' . $suffix;
        
        // Cập nhật GhiChu với transferContent chính xác
        try {
            $stmt = $pdo->prepare("
                UPDATE thanhtoan 
                SET GhiChu = CONCAT(GhiChu, ' | TransferContent: ', ?) 
                WHERE ID_ThanhToan = ?
            ");
            $stmt->execute([$transferContent, $insertedId]);
        } catch (Exception $e) {
            error_log("Warning: Could not update GhiChu with transferContent: " . $e->getMessage());
        }
        
        // Log để debug
        error_log("SePay Transfer Content: {$transferContent} (eventId: {$eventId}, ID_ThanhToan: {$insertedId}, suffix length: " . strlen($suffix) . ")");
        
        // Gọi SePay API để tạo QR code
        $sepayQRResult = createSePayQRCode($amount, $transferContent, $accountNumber);
        
        // Nếu API SePay thành công, sử dụng QR từ SePay
        if ($sepayQRResult && isset($sepayQRResult['qr_code'])) {
            $qrCodeUrl = $sepayQRResult['qr_code'];
            $sepayQRId = $sepayQRResult['id'] ?? null;
            
            // Lưu ID QR SePay vào database nếu có (lưu vào GhiChu nếu cột SePayQRId không tồn tại)
            if ($sepayQRId) {
                try {
                    $stmt = $pdo->prepare("UPDATE thanhtoan SET SePayQRId = ? WHERE ID_ThanhToan = ?");
                    $stmt->execute([$sepayQRId, $insertedId]);
                } catch (Exception $e) {
                    // Nếu cột SePayQRId không tồn tại, lưu vào GhiChu
                    error_log("SePayQRId column not found, saving to GhiChu: " . $e->getMessage());
                    $stmt = $pdo->prepare("UPDATE thanhtoan SET GhiChu = CONCAT(GhiChu, ' | SePayQRId: ', ?) WHERE ID_ThanhToan = ?");
                    $stmt->execute([$sepayQRId, $insertedId]);
                }
            }
        } else {
            // Dự phòng: Tạo mã QR cục bộ bằng VietQR
            $qrCodeUrl = 'https://img.vietqr.io/image/' . $bankCode . '-' . $accountNumber . '-compact2.png?amount=' . $amount . '&addInfo=' . urlencode($transferContent);
        }
        
        // Tạo chuỗi QR dự phòng với thông tin ngân hàng
        $fallbackQrData = "Bank: {$bankName}\nAccount: {$accountNumber}\nAmount: {$amount}\nContent: {$transferContent}";
        $fallbackQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($fallbackQrData);
        
        echo json_encode([
            'success' => true,
            'payment_id' => $insertedId,
            'payment_code' => $paymentId,
            'transaction_id' => $paymentId,
            'transaction_code' => $paymentId,
            'amount' => $amount,
            'event_name' => $event['TenSuKien'],
            'customer_name' => $event['HoTen'],
            'payment_method' => 'bank_transfer',
            'message' => 'QR Code đã được tạo. Vui lòng quét mã để thanh toán.',
            'qr_code' => $qrCodeUrl,
            'qr_string' => $qrCodeUrl,
            'fallback_qr' => $fallbackQrUrl,
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
            'sepay_qr_id' => $sepayQRResult['id'] ?? null,
            'auto_updated' => false,
            'waiting_payment' => true
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}

/**
 * Kiểm tra trạng thái thanh toán khi người dùng nhấn "Xác nhận thanh toán"
 */
function verifyPayment() {
    global $pdo;
    
    $paymentId = $_POST['payment_id'] ?? null;
    $transactionCode = $_POST['transaction_code'] ?? null;
    
    if (!$paymentId && !$transactionCode) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin thanh toán']);
        return;
    }
    
    try {
        // Tìm bản ghi thanh toán
        if ($paymentId) {
            $stmt = $pdo->prepare("
                SELECT t.*, dl.TenSuKien, dl.ID_DatLich, kh.HoTen
                FROM thanhtoan t
                INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                WHERE t.ID_ThanhToan = ?
            ");
            $stmt->execute([$paymentId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT t.*, dl.TenSuKien, dl.ID_DatLich, kh.HoTen
                FROM thanhtoan t
                INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                WHERE t.MaGiaoDich = ? OR t.MaGiaoDich LIKE ?
            ");
            $stmt->execute([$transactionCode, "%{$transactionCode}%"]);
        }
        
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy thanh toán']);
            return;
        }
        
        // Kiểm tra trạng thái thanh toán
        $status = $payment['TrangThai'];
        $isSuccess = ($status === 'Thành công');
        $isPending = ($status === 'Đang xử lý');
        
        // Kiểm tra xem có SePayTransactionId không (đã nhận webhook)
        $hasWebhook = !empty($payment['SePayTransactionId']);
        
        echo json_encode([
            'success' => true,
            'payment_id' => $payment['ID_ThanhToan'],
            'transaction_code' => $payment['MaGiaoDich'],
            'status' => $status,
            'is_success' => $isSuccess,
            'is_pending' => $isPending,
            'has_webhook' => $hasWebhook,
            'amount' => $payment['SoTien'],
            'event_name' => $payment['TenSuKien'],
            'customer_name' => $payment['HoTen'],
            'message' => $isSuccess 
                ? 'Thanh toán đã được xác nhận thành công!' 
                : ($isPending 
                    ? 'Thanh toán đang được xử lý. Vui lòng đợi trong giây lát...' 
                    : 'Thanh toán chưa được xác nhận.')
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}

// Các hàm khác giữ nguyên...
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
    $note = $_POST['note'] ?? '';
    
    if (!$paymentId || !$status) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
    
        // Lấy thông tin thanh toán hiện tại
        $stmt = $pdo->prepare("
            SELECT t.*, dl.TrangThaiThanhToan as EventPaymentStatus
            FROM thanhtoan t
            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            WHERE t.ID_ThanhToan = ?
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception('Không tìm thấy thanh toán');
        }
        
        $oldStatus = $payment['TrangThai'];
        $eventId = $payment['ID_DatLich'];
        $paymentType = $payment['LoaiThanhToan'];
        
        // Cập nhật trạng thái thanh toán và ghi chú
        $updateNote = !empty($note) ? ", GhiChu = CONCAT(IFNULL(GhiChu, ''), IF(CHAR_LENGTH(IFNULL(GhiChu, '')) > 0, ' | ', ''), ?)" : '';
        $stmt = $pdo->prepare("UPDATE thanhtoan SET TrangThai = ?" . $updateNote . " WHERE ID_ThanhToan = ?");
        if (!empty($note)) {
            $stmt->execute([$status, $note, $paymentId]);
        } else {
        $stmt->execute([$status, $paymentId]);
        }
        
        // Cập nhật trạng thái thanh toán sự kiện dựa trên trạng thái và loại thanh toán
        if ($status === 'Thành công') {
            // Thanh toán thành công - cập nhật trạng thái sự kiện dựa trên loại thanh toán
            if ($paymentType === 'Đặt cọc') {
                $newEventStatus = 'Đã đặt cọc';
            } elseif ($paymentType === 'Thanh toán đủ') {
                // Khi thanh toán đủ được xác nhận, luôn đặt thành "Đã thanh toán đủ"
                // Điều này sẽ ghi đè trạng thái "Đã đặt cọc"
                $newEventStatus = 'Đã thanh toán đủ';
            } else {
                $newEventStatus = $payment['EventPaymentStatus']; // Giữ nguyên trạng thái hiện tại
            }
            
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = ? 
                WHERE ID_DatLich = ?
            ");
            $stmt->execute([$newEventStatus, $eventId]);
            
            // Ghi log tiến trình thanh toán cho thanh toán đủ
            if ($paymentType === 'Thanh toán đủ' && $payment['EventPaymentStatus'] === 'Đã đặt cọc') {
                error_log("Payment progression: Event #{$eventId} moved from 'Đã đặt cọc' to 'Đã thanh toán đủ' via payment status update #{$paymentId}");
            }
        } elseif ($status === 'Thất bại' || $status === 'Đã hủy') {
            // Thanh toán thất bại hoặc đã hủy - kiểm tra xem có thanh toán thành công khác không
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM thanhtoan 
                WHERE ID_DatLich = ? AND TrangThai = 'Thành công' AND ID_ThanhToan != ?
            ");
            $stmt->execute([$eventId, $paymentId]);
            $otherSuccessful = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Chỉ đặt lại trạng thái sự kiện nếu không có thanh toán thành công khác
            if ($otherSuccessful == 0) {
                // Kiểm tra xem có thanh toán thành công nào đang chờ xử lý không
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM thanhtoan 
                    WHERE ID_DatLich = ? AND TrangThai = 'Thành công'
                ");
                $stmt->execute([$eventId]);
                $anySuccessful = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($anySuccessful == 0) {
                    $stmt = $pdo->prepare("
                        UPDATE datlichsukien 
                        SET TrangThaiThanhToan = 'Chưa thanh toán' 
                        WHERE ID_DatLich = ?
                    ");
                    $stmt->execute([$eventId]);
                }
            }
        }
        // Đối với các trạng thái khác (Đang xử lý, Chờ thanh toán), không thay đổi trạng thái sự kiện
        
        // Thêm lịch sử thanh toán (nếu bảng tồn tại)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
                VALUES (?, 'update_status', ?, ?, ?)
            ");
            $description = !empty($note) ? "Cập nhật trạng thái: {$note}" : "Cập nhật trạng thái từ {$oldStatus} sang {$status}";
            $stmt->execute([$paymentId, $oldStatus, $status, $description]);
        } catch (PDOException $e) {
            // Bảng payment_history có thể không tồn tại, bỏ qua lỗi này
            error_log("Warning: Could not insert payment history: " . $e->getMessage());
        }
        
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
    
        // Lấy thông tin thanh toán để kiểm tra loại thanh toán
        $stmt = $pdo->prepare("
            SELECT t.*, dl.TrangThaiThanhToan as EventPaymentStatus
            FROM thanhtoan t
            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            WHERE t.ID_ThanhToan = ?
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception('Không tìm thấy thanh toán');
        }
        
        $oldStatus = $payment['TrangThai'];
        $eventId = $payment['ID_DatLich'];
        $paymentType = $payment['LoaiThanhToan'];
        
        // Cập nhật trạng thái thanh toán
        $updateNote = !empty($confirmNote) ? ", GhiChu = CONCAT(IFNULL(GhiChu, ''), ' | Xác nhận tiền mặt: ', ?)" : '';
        $stmt = $pdo->prepare("UPDATE thanhtoan SET TrangThai = 'Thành công'" . $updateNote . " WHERE ID_ThanhToan = ?");
        if (!empty($confirmNote)) {
            $stmt->execute([$confirmNote, $paymentId]);
        } else {
            $stmt->execute([$paymentId]);
        }
        
        // Cập nhật trạng thái thanh toán sự kiện dựa trên loại thanh toán
        if ($paymentType === 'Đặt cọc') {
            $newEventStatus = 'Đã đặt cọc';
        } elseif ($paymentType === 'Thanh toán đủ') {
            // Khi thanh toán đủ được xác nhận, luôn đặt thành "Đã thanh toán đủ"
            // Điều này sẽ ghi đè trạng thái "Đã đặt cọc"
            $newEventStatus = 'Đã thanh toán đủ';
        } else {
            $newEventStatus = 'Đã thanh toán đủ'; // Default fallback
        }
        
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = ? 
            WHERE ID_DatLich = ?
        ");
        $stmt->execute([$newEventStatus, $eventId]);
        
        // Ghi log tiến trình thanh toán cho thanh toán đủ
        if ($paymentType === 'Thanh toán đủ' && $payment['EventPaymentStatus'] === 'Đã đặt cọc') {
            error_log("Payment progression: Event #{$eventId} moved from 'Đã đặt cọc' to 'Đã thanh toán đủ' via cash payment #{$paymentId}");
        }
        
        // Thêm lịch sử thanh toán
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
            VALUES (?, 'confirm_cash', ?, 'Thành công', ?)
        ");
        $description = 'Xác nhận thanh toán tiền mặt' . (!empty($confirmNote) ? ': ' . $confirmNote : '');
        $stmt->execute([$paymentId, $oldStatus, $description]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Xác nhận thanh toán tiền mặt thành công']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Lỗi xác nhận: ' . $e->getMessage()]);
    }
}

function getPaymentStatus() {
    global $pdo;
    
    $paymentId = $_GET['payment_id'] ?? $_POST['payment_id'] ?? null;
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID thanh toán']);
        return;
    }
    
    try {
        // Kiểm tra xem bảng hoadon có tồn tại không
        $tableExists = false;
        try {
            $checkTable = $pdo->query("SHOW TABLES LIKE 'hoadon'");
            $tableExists = $checkTable->rowCount() > 0;
        } catch (Exception $e) {
            $tableExists = false;
        }
        
        // Nếu có bảng hoadon, ưu tiên lấy thông tin từ hóa đơn (thông tin đã thay đổi khi thanh toán)
        if ($tableExists) {
            $stmt = $pdo->prepare("
                SELECT t.*, 
                       dl.TenSuKien, 
                       dl.NgayBatDau, 
                       dl.NgayKetThuc,
                       -- Thông tin từ hóa đơn (ưu tiên - thông tin đã thay đổi khi thanh toán)
                       COALESCE(h.HoTen, kh.HoTen) as KhachHangTen,
                       COALESCE(h.SoDienThoai, kh.SoDienThoai) as SoDienThoai,
                       COALESCE(h.DiaChi, kh.DiaChi) as KhachHangDiaChi,
                       COALESCE(h.Email, u.Email) as KhachHangEmail,
                       -- Thông tin gốc từ khachhanginfo
                       kh.HoTen as KhachHangTenGoc,
                       kh.SoDienThoai as SoDienThoaiGoc,
                       kh.DiaChi as KhachHangDiaChiGoc,
                       kh.NgaySinh,
                       u.Email as KhachHangEmailGoc,
                       u.ID_User as KhachHangID_User,
                       -- Thông tin hóa đơn
                       h.ID_HoaDon,
                       h.HoTen as InvoiceHoTen,
                       h.SoDienThoai as InvoiceSoDienThoai,
                       h.Email as InvoiceEmail,
                       h.DiaChi as InvoiceDiaChi
                FROM thanhtoan t
                LEFT JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                LEFT JOIN users u ON kh.ID_User = u.ID_User
                LEFT JOIN hoadon h ON t.ID_ThanhToan = h.ID_ThanhToan
                WHERE t.ID_ThanhToan = ?
            ");
        } else {
            // Nếu không có bảng hoadon, lấy từ khachhanginfo và users
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   dl.TenSuKien, 
                   dl.NgayBatDau, 
                   dl.NgayKetThuc,
                   kh.HoTen as KhachHangTen, 
                       kh.SoDienThoai,
                       kh.DiaChi as KhachHangDiaChi,
                       kh.NgaySinh,
                       u.Email as KhachHangEmail,
                       u.ID_User as KhachHangID_User,
                       NULL as ID_HoaDon
            FROM thanhtoan t
            LEFT JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                LEFT JOIN users u ON kh.ID_User = u.ID_User
            WHERE t.ID_ThanhToan = ?
        ");
        }
        
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy thanh toán']);
            return;
        }
        
        echo json_encode(['success' => true, 'payment' => $payment]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
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

function getInvoice() {
    global $pdo;
    
    $paymentId = $_GET['payment_id'] ?? null;
    
    if (!$paymentId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID thanh toán']);
        return;
    }
    
    try {
        $userId = $_SESSION['user']['ID_User'];
        
        // Kiểm tra xem bảng hoadon có tồn tại không
        $tableExists = false;
        try {
            $checkTable = $pdo->query("SHOW TABLES LIKE 'hoadon'");
            $tableExists = $checkTable->rowCount() > 0;
        } catch (Exception $e) {
            $tableExists = false;
        }
        
        // Lấy thông tin thanh toán và hóa đơn
        if ($tableExists) {
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    dl.TenSuKien,
                    dl.NgayBatDau,
                    dl.NgayKetThuc,
                    kh.HoTen,
                    kh.SoDienThoai,
                    kh.DiaChi,
                    u.Email as UserEmail,
                    h.ID_HoaDon,
                    h.HoTen as InvoiceHoTen,
                    h.SoDienThoai as InvoiceSoDienThoai,
                    h.Email as InvoiceEmail,
                    h.DiaChi as InvoiceDiaChi
                FROM thanhtoan t
                INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                LEFT JOIN users u ON kh.ID_User = u.ID_User
                LEFT JOIN hoadon h ON t.ID_ThanhToan = h.ID_ThanhToan
                WHERE t.ID_ThanhToan = ? AND kh.ID_User = ?
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    dl.TenSuKien,
                    dl.NgayBatDau,
                    dl.NgayKetThuc,
                    kh.HoTen,
                    kh.SoDienThoai,
                    kh.DiaChi,
                    u.Email as UserEmail,
                    NULL as ID_HoaDon,
                    kh.HoTen as InvoiceHoTen,
                    kh.SoDienThoai as InvoiceSoDienThoai,
                    u.Email as InvoiceEmail,
                    kh.DiaChi as InvoiceDiaChi
                FROM thanhtoan t
                INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                LEFT JOIN users u ON kh.ID_User = u.ID_User
                WHERE t.ID_ThanhToan = ? AND kh.ID_User = ?
            ");
        }
        
        $stmt->execute([$paymentId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy hóa đơn hoặc bạn không có quyền xem']);
            return;
        }
        
        // Tách thông tin hóa đơn và thanh toán
        $invoice = [
            'ID_HoaDon' => $result['ID_HoaDon'] ?? null,
            'HoTen' => $result['InvoiceHoTen'] ?? $result['HoTen'],
            'SoDienThoai' => $result['InvoiceSoDienThoai'] ?? $result['SoDienThoai'],
            'Email' => $result['InvoiceEmail'] ?? $result['UserEmail'] ?? null,
            'DiaChi' => $result['InvoiceDiaChi'] ?? $result['DiaChi'] ?? null
        ];
        
        $payment = [
            'ID_ThanhToan' => $result['ID_ThanhToan'],
            'TenSuKien' => $result['TenSuKien'],
            'SoTien' => $result['SoTien'],
            'LoaiThanhToan' => $result['LoaiThanhToan'],
            'PhuongThuc' => $result['PhuongThuc'],
            'TrangThai' => $result['TrangThai'],
            'NgayThanhToan' => $result['NgayThanhToan'],
            'MaGiaoDich' => $result['MaGiaoDich'] ?? null,
            'HoTen' => $result['HoTen'],
            'SoDienThoai' => $result['SoDienThoai'],
            'DiaChi' => $result['DiaChi'] ?? null,
            'UserEmail' => $result['UserEmail'] ?? null
        ];
        
        echo json_encode([
            'success' => true,
            'invoice' => $invoice,
            'payment' => $payment
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}

function getPaymentStats() {
    global $pdo;
    
    // Tổng số thanh toán
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM thanhtoan");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Số thanh toán thành công
    $stmt = $pdo->prepare("SELECT COUNT(*) as success FROM thanhtoan WHERE TrangThai = 'Thành công'");
    $stmt->execute();
    $success = $stmt->fetch(PDO::FETCH_ASSOC)['success'];
    
    // Tổng số tiền
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
    
    // Lấy dữ liệu callback
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid callback data']);
        return;
    }
    
    // Xử lý callback SePay
    // Sẽ được triển khai dựa trên định dạng callback của SePay
    
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
    
    // Trả về HTML form SePay
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
        
        // Lấy thông tin thanh toán để kiểm tra loại thanh toán
        $stmt = $pdo->prepare("
            SELECT t.*, dl.TrangThaiThanhToan as EventPaymentStatus
            FROM thanhtoan t
            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            WHERE t.ID_ThanhToan = ?
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            throw new Exception('Không tìm thấy thanh toán');
        }
        
        $oldStatus = $payment['TrangThai'];
        $eventId = $payment['ID_DatLich'];
        $paymentType = $payment['LoaiThanhToan'];
        
        // Cập nhật trạng thái thanh toán
        $updateNote = !empty($confirmNote) ? ", GhiChu = CONCAT(IFNULL(GhiChu, ''), ' | Xác nhận chuyển khoản: ', ?)" : '';
        $stmt = $pdo->prepare("UPDATE thanhtoan SET TrangThai = 'Thành công'" . $updateNote . " WHERE ID_ThanhToan = ?");
        if (!empty($confirmNote)) {
            $stmt->execute([$confirmNote, $paymentId]);
        } else {
            $stmt->execute([$paymentId]);
        }
        
        // Cập nhật trạng thái thanh toán sự kiện dựa trên loại thanh toán
        if ($paymentType === 'Đặt cọc') {
            $newEventStatus = 'Đã đặt cọc';
        } elseif ($paymentType === 'Thanh toán đủ') {
            // Khi thanh toán đủ được xác nhận, luôn đặt thành "Đã thanh toán đủ"
            // Điều này sẽ ghi đè trạng thái "Đã đặt cọc"
            $newEventStatus = 'Đã thanh toán đủ';
        } else {
            $newEventStatus = 'Đã thanh toán đủ'; // Default fallback
        }
        
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiThanhToan = ? 
            WHERE ID_DatLich = ?
        ");
        $stmt->execute([$newEventStatus, $eventId]);
        
        // Ghi log tiến trình thanh toán cho thanh toán đủ
        if ($paymentType === 'Thanh toán đủ' && $payment['EventPaymentStatus'] === 'Đã đặt cọc') {
            error_log("Payment progression: Event #{$eventId} moved from 'Đã đặt cọc' to 'Đã thanh toán đủ' via banking payment #{$paymentId}");
        }
        
        // Thêm lịch sử thanh toán
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
            VALUES (?, 'confirm_banking', ?, 'Thành công', ?)
        ");
        $description = 'Xác nhận thanh toán chuyển khoản' . (!empty($confirmNote) ? ': ' . $confirmNote : '');
        $stmt->execute([$paymentId, $oldStatus, $description]);
        
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
        
        // Nếu có transaction_id, tìm thanh toán trước
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
            
            // Chỉ hủy nếu thanh toán đang chờ xử lý
            if ($payment['TrangThai'] === 'Đang xử lý') {
                // Hủy thanh toán cụ thể
                $stmt = $pdo->prepare("
                    UPDATE thanhtoan 
                    SET TrangThai = 'Hủy', GhiChu = CONCAT(IFNULL(GhiChu, ''), ' - Đã hủy bởi người dùng')
                    WHERE ID_ThanhToan = ?
                ");
                $stmt->execute([$payment['ID_ThanhToan']]);
                
                // Thêm lịch sử thanh toán
                $stmt = $pdo->prepare("
                    INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
                    VALUES (?, 'cancel_payment', 'Đang xử lý', 'Hủy', 'Người dùng hủy thanh toán khi đóng modal')
                ");
                $stmt->execute([$payment['ID_ThanhToan']]);
                
                // Kiểm tra xem có thanh toán đang chờ xử lý khác cho sự kiện này không
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM thanhtoan 
                    WHERE ID_DatLich = ? AND TrangThai = 'Đang xử lý'
                ");
                $stmt->execute([$payment['ID_DatLich']]);
                $remainingPending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // Chỉ đặt lại trạng thái thanh toán sự kiện nếu không có thanh toán đang chờ xử lý khác
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
        // Dự phòng: sử dụng event_id nếu được cung cấp (tương thích ngược)
        else if ($eventId) {
            // Hủy tất cả thanh toán đang chờ xử lý cho sự kiện này
            $stmt = $pdo->prepare("
                UPDATE thanhtoan 
                SET TrangThai = 'Hủy', GhiChu = CONCAT(IFNULL(GhiChu, ''), ' - Đã hủy bởi người dùng')
                WHERE ID_DatLich = ? AND TrangThai = 'Đang xử lý'
            ");
            $stmt->execute([$eventId]);
            
            // Đặt lại trạng thái thanh toán sự kiện
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
