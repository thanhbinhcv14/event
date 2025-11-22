<?php
// Controller Thanh toán - Chỉ hỗ trợ SePay
// Đã loại bỏ hoàn toàn tích hợp MoMo

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tắt display_errors để tránh output HTML trước JSON (nhưng vẫn log errors)
ini_set('display_errors', 0);
// Chỉ báo cáo errors nghiêm trọng, không báo warnings/deprecated
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);

require_once __DIR__ . '/../../config/database.php';

// Kiểm tra file sepay.php có tồn tại không trước khi require
$sepayConfigPath = __DIR__ . '/../../config/sepay.php';
if (file_exists($sepayConfigPath)) {
    require_once $sepayConfigPath;
} else {
    // Định nghĩa các constants mặc định nếu file không tồn tại
    if (!defined('SEPAY_PARTNER_CODE')) define('SEPAY_PARTNER_CODE', '');
    if (!defined('SEPAY_SECRET_KEY')) define('SEPAY_SECRET_KEY', '');
    if (!defined('SEPAY_API_TOKEN')) define('SEPAY_API_TOKEN', '');
    if (!defined('SEPAY_ENVIRONMENT')) define('SEPAY_ENVIRONMENT', 'production');
    if (!defined('SEPAY_CALLBACK_URL')) define('SEPAY_CALLBACK_URL', '');
}

require_once __DIR__ . '/../../vendor/autoload.php'; // Composer autoload (bao gồm SePay SDK) - đã có comment tiếng Việt
require_once __DIR__ . '/../auth/csrf.php';

// ✅ Sử dụng SePay PHP SDK chính thức từ sepay/sepay-pg
use SePay\SePayClient;
use SePay\Builders\CheckoutBuilder;
use SePay\Exceptions\SePayException;

header('Content-Type: application/json');

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Các action không yêu cầu CSRF (chỉ đọc)
$readOnlyActions = ['get_payment_history', 'check_payment_status', 'get_payment_config', 'get_payment_status', 'get_payment_list', 'get_payment_stats', 'get_sepay_form', 'verify_payment', 'get_sepay_order_detail'];

// Các action yêu cầu bảo vệ CSRF (thay đổi dữ liệu)
$modifyActions = ['create_payment', 'update_payment_status', 'generate_qr', 'create_sepay_payment', 'confirm_cash_payment', 'confirm_banking_payment', 'cancel_payment', 'delete_and_recreate_payment'];

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
            
        case 'get_sepay_order_detail':
            getSePayOrderDetail();
            break;
            
        case 'delete_and_recreate_payment':
            deleteAndRecreatePayment();
            break;
            
        case 'auto_cancel_expired_payments':
            autoCancelExpiredPendingPayments();
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
 * ✅ Tạo SePay Checkout URL sử dụng SePay PHP SDK chính thức
 * Tài liệu: https://developer.sepay.vn/vi/cong-thanh-toan/sdk/php
 * SDK: sepay/sepay-pg
 */
function createSePayCheckoutURL($amount, $orderDescription, $orderInvoice, $customerName = null, $customerEmail = null, $customerPhone = null) {
    try {
        $partnerCode = defined('SEPAY_PARTNER_CODE') ? SEPAY_PARTNER_CODE : '';
        $secretKey = defined('SEPAY_SECRET_KEY') ? SEPAY_SECRET_KEY : '';
        $environment = defined('SEPAY_ENVIRONMENT') && SEPAY_ENVIRONMENT === 'sandbox' 
            ? SePayClient::ENVIRONMENT_SANDBOX 
            : SePayClient::ENVIRONMENT_PRODUCTION;
        $callbackUrl = defined('SEPAY_CALLBACK_URL') ? SEPAY_CALLBACK_URL : '';
        
        // ✅ Khởi tạo SePay Client với SDK chính thức
        $sepay = new SePayClient(
            $partnerCode, // SP-LIVE-BT953B7A
            $secretKey,   // spsk_live_...
            $environment, // ENVIRONMENT_PRODUCTION hoặc ENVIRONMENT_SANDBOX
            [
                'timeout' => 60,
                'retry_attempts' => 3,
                'retry_delay' => 2000,
                'debug' => false
            ]
        );
        
        // ✅ Sử dụng CheckoutBuilder để tạo checkout data
        $checkoutBuilder = CheckoutBuilder::make()
            ->currency('VND')
            ->orderAmount(intval($amount)) // SDK yêu cầu int, không phải float
            ->operation('PURCHASE')
            ->orderDescription($orderDescription)
            ->orderInvoiceNumber($orderInvoice); // Định dạng: INV-{timestamp}-{paymentId}
        
        // Thêm thông tin khách hàng nếu có (SDK không có customerName/Email/Phone, chỉ có customerId)
        // Có thể thêm vào order_description hoặc dùng customerId
        if ($customerName) {
            // SDK không có customerName, có thể thêm vào description hoặc dùng customerId
            // $checkoutBuilder->customerId($customerName); // Nếu muốn dùng customerId
        }
        
        // Thêm callback URLs
        if ($callbackUrl) {
            $baseUrl = str_replace('/hooks/sepay-payment.php', '', $callbackUrl);
            $checkoutBuilder->successUrl($baseUrl . '/payment/success.php');
            $checkoutBuilder->errorUrl($baseUrl . '/payment/error.php');
            $checkoutBuilder->cancelUrl($baseUrl . '/payment/failure.php');
        }
        
        // Xây dựng checkout data
        $checkoutData = $checkoutBuilder->build();
        
        // ✅ Generate form fields với signature (SDK tự động tạo signature)
        $formFields = $sepay->checkout()->generateFormFields($checkoutData);
        
        // ✅ Lấy checkout URL từ SDK (POST endpoint)
        $checkoutUrl = $sepay->checkout()->getCheckoutUrl($environment);
        
        // ✅ Tạo HTML form để submit (POST method, không phải GET redirect)
        // SDK yêu cầu POST form, không phải GET query string
        $formHtml = $sepay->checkout()->generateFormHtml(
            $checkoutData,
            $environment,
            [
                'id' => 'sepay-checkout-form',
                'style' => 'display: none;' // Ẩn form, sẽ auto-submit bằng JS
            ]
        );
        
        // Log chi tiết để debug
        error_log("SePay Checkout URL created using official SDK for merchant: {$partnerCode}");
        error_log("SePay Checkout URL (POST): " . $checkoutUrl);
        error_log("SePay Form Fields: " . json_encode($formFields, JSON_UNESCAPED_UNICODE));
        
        return [
            'checkout_url' => $checkoutUrl, // URL để POST form
            'checkout_data' => $checkoutData,
            'form_fields' => $formFields,
            'form_html' => $formHtml // HTML form để render và auto-submit
        ];
    } catch (SePayException $e) {
        error_log("SePay Checkout URL SePayException: " . $e->getMessage());
        error_log("SePay Checkout URL Exception Code: " . $e->getCode());
        return null;
    } catch (Exception $e) {
        error_log("SePay Checkout URL Exception: " . $e->getMessage());
        error_log("SePay Checkout URL Exception Trace: " . $e->getTraceAsString());
        return null;
    }
}

/**
 * Tạo hàm gọi SePay API để tạo QR code
 */
function createSePayQRCode($amount, $content, $accountNumber = null) {
    try {
        $apiToken = SEPAY_API_TOKEN;
        
        // ✅ QUAN TRỌNG: SePay API endpoint để tạo QR code
        // URL: https://my.sepay.vn/createqr
        $url = 'https://my.sepay.vn/createqr';
        
        // ✅ Định dạng request theo SePay API
        // SePay có thể yêu cầu các thông tin:
        // - amount: Số tiền
        // - content/description: Nội dung chuyển khoản (phải khớp với pattern đã cấu hình)
        // - accountNumber: Số tài khoản (có thể không cần nếu đã cấu hình trong SePay)
        // - callbackUrl: URL webhook (có thể không cần nếu đã cấu hình trong Dashboard)
        
        // ✅ Thử nhiều định dạng request khác nhau vì SePay API có thể yêu cầu định dạng khác
        // Định dạng 1: Với partner_code và secret_key (nếu cần)
        $partnerCode = defined('SEPAY_PARTNER_CODE') ? SEPAY_PARTNER_CODE : '';
        $secretKey = defined('SEPAY_SECRET_KEY') ? SEPAY_SECRET_KEY : '';
        
        // Thử các định dạng request khác nhau
        $requestFormats = [
            // Định dạng 1: Đơn giản với amount và content
            [
                'amount' => floatval($amount),
            'content' => $content,
                'description' => $content,
            ],
            // Định dạng 2: Với accountNumber
            [
                'amount' => floatval($amount),
                'content' => $content,
                'description' => $content,
                'accountNumber' => $accountNumber,
            ],
            // Định dạng 3: Với partner_code và secret_key
            [
                'partner_code' => $partnerCode,
                'secret_key' => $secretKey,
                'amount' => floatval($amount),
                'content' => $content,
                'description' => $content,
            ],
            // Định dạng 4: Với tất cả thông tin
            [
                'partner_code' => $partnerCode,
                'amount' => floatval($amount),
                'content' => $content,
                'description' => $content,
                'accountNumber' => $accountNumber,
                'callbackUrl' => defined('SEPAY_CALLBACK_URL') ? SEPAY_CALLBACK_URL : '',
            ],
        ];
        
        // Thử từng định dạng cho đến khi thành công
        foreach ($requestFormats as $index => $data) {
            // Bỏ qua định dạng không có accountNumber nếu accountNumber là bắt buộc
            if ($index > 0 && !$accountNumber) {
                continue;
            }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // ✅ Theo dõi redirects
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Tối đa 5 redirects
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Apikey ' . $apiToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        // curl_close() is deprecated in PHP 8.0+, cURL handle auto-closes when variable is destroyed
        unset($ch);
            
            // Ghi log chi tiết để debug
            error_log("SePay QR API Request Format " . ($index + 1) . ": " . json_encode($data));
            error_log("SePay QR API Response Code: " . $httpCode);
            if ($finalUrl !== $url) {
                error_log("SePay QR API: Redirected from {$url} to {$finalUrl}");
            }
            if ($redirectUrl) {
                error_log("SePay QR API: Redirect URL: {$redirectUrl}");
            }
            error_log("SePay QR API Response: " . substr($response, 0, 500));
        
        if ($curlError) {
                error_log("SePay QR API cURL Error (Format " . ($index + 1) . "): " . $curlError);
                continue; // Thử định dạng tiếp theo
            }
            
            // ✅ Xử lý redirect (302/301) - SePay redirect đến trang login
            if ($httpCode === 302 || $httpCode === 301) {
                error_log("SePay QR API: Redirect (HTTP {$httpCode})");
                if ($finalUrl && (strpos($finalUrl, '/login') !== false || strpos($finalUrl, 'login') !== false)) {
                    // ✅ SePay redirect đến trang login = API không công khai, cần đăng nhập
                    error_log("SePay QR API: Redirected to login page - API endpoint is not public. SePay may not have public QR creation API.");
                    // Dừng ngay, không thử định dạng tiếp theo
                    break;
                }
                if ($redirectUrl) {
                    // Nếu redirect đến URL có chứa QR code, có thể là thành công
                    if (strpos($redirectUrl, 'qr') !== false || strpos($redirectUrl, 'qr.sepay.vn') !== false) {
                        error_log("SePay QR API: Redirect URL may contain QR code: {$redirectUrl}");
                        return [
                            'qr_code' => $redirectUrl,
                            'id' => null,
                            'raw_response' => ['redirect_url' => $redirectUrl, 'http_code' => $httpCode]
                        ];
                    }
                }
                // Tiếp tục thử định dạng tiếp theo
                continue;
            }
            
            // ✅ Kiểm tra nếu response là HTML (trang login)
            if ($httpCode === 200 && (strpos($response, '<html') !== false || strpos($response, 'login') !== false || strpos($response, 'Đăng nhập') !== false)) {
                error_log("SePay QR API: Response is HTML login page - API endpoint is not public.");
                // Dừng ngay, không thử định dạng tiếp theo
                break;
            }
            
            if ($httpCode === 200 || $httpCode === 201) {
                $result = json_decode($response, true);
                
                if ($result) {
                    // SePay có thể trả về định dạng khác nhau, kiểm tra các trường có thể có
                    $qrCodeUrl = $result['qr_code'] ?? $result['qrCode'] ?? $result['qr_url'] ?? 
                                (isset($result['data']) ? ($result['data']['qr_code'] ?? $result['data']['qrCode'] ?? null) : null) ??
                                (isset($result['qr']) ? $result['qr'] : null);
                    $qrId = $result['id'] ?? $result['qr_id'] ?? 
                           (isset($result['data']) ? ($result['data']['id'] ?? $result['data']['qr_id'] ?? null) : null) ??
                           (isset($result['qrId']) ? $result['qrId'] : null);
                    
                    // ✅ Kiểm tra nhiều trường có thể có trong response
                    // SePay có thể trả về QR code URL hoặc QR ID
                    if ($qrCodeUrl || $qrId) {
                        error_log("SePay QR API Success (Format " . ($index + 1) . "): QR created - ID: " . ($qrId ?? 'N/A') . ", URL: " . ($qrCodeUrl ?? 'N/A'));
                        
                        // Nếu có QR ID nhưng chưa có URL, có thể cần tạo URL từ ID
                        if ($qrId && !$qrCodeUrl) {
                            // Thử tạo URL từ QR ID (nếu SePay có pattern URL)
                            // Ví dụ: https://qr.sepay.vn/{qrId} hoặc https://my.sepay.vn/qr/{qrId}
                            $possibleUrls = [
                                "https://qr.sepay.vn/{$qrId}",
                                "https://my.sepay.vn/qr/{$qrId}",
                                "https://qr.sepay.vn/img?id={$qrId}",
                            ];
                            // Không tự tạo URL, để SePay trả về
                            error_log("SePay QR API: Has QR ID but no URL. QR ID: {$qrId}");
                        }
                        
                        return [
                            'qr_code' => $qrCodeUrl,
                            'id' => $qrId,
                            'raw_response' => $result
                        ];
                    } else {
                        // Nếu response không có qr_code nhưng có data, có thể là định dạng khác
                        error_log("SePay QR API: Response received but no qr_code field (Format " . ($index + 1) . "). Full response: " . json_encode($result));
                        // Tiếp tục thử định dạng tiếp theo
                    }
                } else {
                    error_log("SePay QR API: Invalid JSON response (Format " . ($index + 1) . "): " . $response);
                }
            } else {
                error_log("SePay QR API HTTP Error (Format " . ($index + 1) . "): " . $httpCode . " - " . substr($response, 0, 500));
            }
        }
        
        // ✅ Nếu tất cả định dạng đều thất bại hoặc redirect đến login
        // SePay không có API tạo QR code công khai
        error_log("SePay QR API: All request formats failed or redirected to login. SePay may not have public QR creation API.");
        error_log("SePay QR API: Will use VietQR fallback. Webhook will still work based on transfer content.");
        
        // ✅ Không thử endpoint khác vì SePay không có API công khai
        // Trả về null để sử dụng VietQR fallback
        return null;
    } catch (Exception $e) {
        error_log("SePay QR API Exception: " . $e->getMessage());
        error_log("SePay QR API Exception Trace: " . $e->getTraceAsString());
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
        // Định dạng: SEPAY + eventId + ID_ThanhToan (chỉ số, tổng 3-10 ký tự sau "SEPAY")
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
        
        // Ghi log để debug
        error_log("SePay Transfer Content: {$transferContent} (eventId: {$eventId}, ID_ThanhToan: {$insertedId}, suffix length: " . strlen($suffix) . ")");
        
        // ✅ TẠO SEPAY CHECKOUT URL (Chuyển hướng đến trang thanh toán SePay)
        $orderDescription = "Thanh toán {$paymentTypeDB} - " . $event['TenSuKien'];
        $orderInvoice = 'INV-' . time() . '-' . $insertedId;
        
        // Lưu order_invoice vào database để webhook có thể khớp
        try {
            $stmt = $pdo->prepare("UPDATE thanhtoan SET GhiChu = CONCAT(IFNULL(GhiChu, ''), ' | OrderInvoice: ', ?) WHERE ID_ThanhToan = ?");
            $stmt->execute([$orderInvoice, $insertedId]);
        } catch (Exception $e) {
            error_log("Warning: Could not update GhiChu with orderInvoice: " . $e->getMessage());
        }
        
        $checkoutResult = createSePayCheckoutURL(
            $amount,
            $orderDescription,
            $orderInvoice,
            $event['HoTen'],
            $event['UserEmail'] ?? null,
            $event['SoDienThoai'] ?? null
        );
        
        // ✅ QUAN TRỌNG: Gọi SePay API để tạo QR code từ https://my.sepay.vn/createqr (fallback)
        // SePay sẽ tạo QR code và theo dõi giao dịch, đảm bảo webhook được gửi đúng
        error_log("SePay: Creating QR code with content: {$transferContent}, amount: {$amount}");
        $sepayQRResult = createSePayQRCode($amount, $transferContent, $accountNumber);
        
        // Nếu API SePay thành công, sử dụng QR từ SePay
        if ($sepayQRResult) {
            // Kiểm tra các định dạng response có thể có
            $qrCodeUrl = $sepayQRResult['qr_code'] ?? $sepayQRResult['qrCode'] ?? $sepayQRResult['qr_url'] ?? 
                        (isset($sepayQRResult['data']) ? ($sepayQRResult['data']['qr_code'] ?? $sepayQRResult['data']['qrCode'] ?? null) : null);
            $sepayQRId = $sepayQRResult['id'] ?? $sepayQRResult['qr_id'] ?? 
                        (isset($sepayQRResult['data']) ? ($sepayQRResult['data']['id'] ?? $sepayQRResult['data']['qr_id'] ?? null) : null);
            
            if ($qrCodeUrl) {
                // Lưu ID QR SePay vào database nếu có
            if ($sepayQRId) {
                try {
                    $stmt = $pdo->prepare("UPDATE thanhtoan SET SePayQRId = ? WHERE ID_ThanhToan = ?");
                    $stmt->execute([$sepayQRId, $insertedId]);
                        error_log("SePay QR ID saved: {$sepayQRId} for payment ID: {$insertedId}");
                } catch (Exception $e) {
                    // Nếu cột SePayQRId không tồn tại, lưu vào GhiChu
                    error_log("SePayQRId column not found, saving to GhiChu: " . $e->getMessage());
                    $stmt = $pdo->prepare("UPDATE thanhtoan SET GhiChu = CONCAT(GhiChu, ' | SePayQRId: ', ?) WHERE ID_ThanhToan = ?");
                    $stmt->execute([$sepayQRId, $insertedId]);
                }
            }
                
                // Lưu transferContent vào GhiChu để webhook có thể tìm thấy
                try {
                    $stmt = $pdo->prepare("UPDATE thanhtoan SET GhiChu = CONCAT(IFNULL(GhiChu, ''), ' | TransferContent: ', ?) WHERE ID_ThanhToan = ?");
                    $stmt->execute([$transferContent, $insertedId]);
                } catch (Exception $e) {
                    error_log("Warning: Could not update GhiChu with transferContent: " . $e->getMessage());
                }
                
                error_log("SePay QR Code created successfully: {$qrCodeUrl}");
        } else {
                // Nếu response có nhưng không có qr_code, ghi log để debug
                error_log("SePay QR API: Response received but no qr_code URL. Response: " . json_encode($sepayQRResult));
            }
        }
        
        // ✅ Dự phòng: Tạo mã QR cục bộ bằng VietQR nếu SePay API thất bại
        // Lưu ý: Nếu dùng VietQR, SePay vẫn có thể gửi webhook dựa trên amount và thời gian
        if (empty($qrCodeUrl)) {
            $qrCodeUrl = 'https://img.vietqr.io/image/' . $bankCode . '-' . $accountNumber . '-compact2.png?amount=' . $amount . '&addInfo=' . urlencode($transferContent);
            error_log("SePay QR API failed, using VietQR fallback. Content: {$transferContent}");
            error_log("Note: SePay webhook may still work based on amount and time matching");
        }
        
        // Tạo chuỗi QR dự phòng với thông tin ngân hàng
        $fallbackQrData = "Bank: {$bankName}\nAccount: {$accountNumber}\nAmount: {$amount}\nContent: {$transferContent}";
        $fallbackQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($fallbackQrData);
        
        // ✅ Trả về cả checkout URL và QR code (người dùng có thể chọn)
        $response = [
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
        ];
        
        // ✅ Thêm SePay Checkout URL nếu có (Ưu tiên chuyển hướng đến SePay Checkout Gateway)
        if ($checkoutResult && isset($checkoutResult['checkout_url'])) {
            $response['sepay_checkout_url'] = $checkoutResult['checkout_url'];
            $response['checkout_invoice'] = $orderInvoice;
            $response['checkout_merchant'] = SEPAY_PARTNER_CODE; // SP-LIVE-BT953B7A
            
            // ✅ Thêm form HTML và form fields để submit POST form
            if (isset($checkoutResult['form_html'])) {
                $response['form_html'] = $checkoutResult['form_html'];
            }
            if (isset($checkoutResult['form_fields'])) {
                $response['form_fields'] = $checkoutResult['form_fields'];
            }
            
            $response['message'] = 'Đang chuyển hướng đến trang thanh toán SePay...';
            error_log("SePay Checkout URL ready for POST form submission: " . substr($checkoutResult['checkout_url'], 0, 150) . "...");
        } else {
            error_log("SePay Checkout URL creation failed, using QR code fallback");
        }
        
        echo json_encode($response);
        
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
    
    try {
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
    } catch (Exception $e) {
        error_log("getPaymentList Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi lấy danh sách thanh toán',
            'payments' => []
        ]);
    }
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
    
    try {
        // Tổng số thanh toán
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM thanhtoan");
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Số thanh toán thành công
        $stmt = $pdo->prepare("SELECT COUNT(*) as success FROM thanhtoan WHERE TrangThai = 'Thành công'");
        $stmt->execute();
        $success = $stmt->fetch(PDO::FETCH_ASSOC)['success'] ?? 0;
        
        // Tổng số tiền
        $stmt = $pdo->prepare("SELECT SUM(SoTien) as total_amount FROM thanhtoan WHERE TrangThai = 'Thành công'");
        $stmt->execute();
        $totalAmount = $stmt->fetch(PDO::FETCH_ASSOC)['total_amount'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total' => (int)$total,
                'successful' => (int)$success,
                'total_amount' => (float)$totalAmount
            ]
        ]);
    } catch (Exception $e) {
        error_log("getPaymentStats Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi lấy thống kê thanh toán',
            'stats' => [
                'total' => 0,
                'successful' => 0,
                'total_amount' => 0
            ]
        ]);
    }
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

/**
 * ✅ Truy vấn chi tiết đơn hàng từ SePay API sử dụng SDK
 * API: $sepay->orders()->retrieve('ORDER_INVOICE_NUMBER')
 * Tài liệu: https://developer.sepay.vn/vi/cong-thanh-toan/sdk/php
 */
function getSePayOrderDetail() {
    $orderId = $_POST['order_id'] ?? $_GET['order_id'] ?? '';
    
    if (!$orderId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu order_id']);
        return;
    }
    
    try {
        $partnerCode = defined('SEPAY_PARTNER_CODE') ? SEPAY_PARTNER_CODE : '';
        $secretKey = defined('SEPAY_SECRET_KEY') ? SEPAY_SECRET_KEY : '';
        $environment = defined('SEPAY_ENVIRONMENT') && SEPAY_ENVIRONMENT === 'sandbox' 
            ? SePayClient::ENVIRONMENT_SANDBOX 
            : SePayClient::ENVIRONMENT_PRODUCTION;
        
        // ✅ Khởi tạo SePay Client với SDK
        $sepay = new SePayClient(
            $partnerCode,
            $secretKey,
            $environment
        );
        
        // ✅ Sử dụng SDK để truy vấn đơn hàng
        $order = $sepay->orders()->retrieve($orderId);
        
        echo json_encode([
            'success' => true,
            'data' => $order,
            'order_status' => $order['order_status'] ?? null,
            'order_amount' => $order['order_amount'] ?? null,
            'transactions' => $order['transactions'] ?? []
        ]);
        
    } catch (\SePay\Exceptions\NotFoundException $e) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy đơn hàng: ' . $e->getMessage()]);
    } catch (\SePay\Exceptions\AuthenticationException $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi xác thực: ' . $e->getMessage()]);
    } catch (\SePay\Exceptions\ValidationException $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi validation: ' . $e->getMessage()]);
    } catch (SePayException $e) {
        echo json_encode(['success' => false, 'error' => 'SePay Exception: ' . $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
    }
}

/**
 * ✅ Xóa thanh toán cũ và tạo lại thanh toán mới
 */
function deleteAndRecreatePayment() {
    global $pdo;
    
    $paymentId = $_POST['payment_id'] ?? null;
    $eventId = $_POST['event_id'] ?? null;
    
    if (!$paymentId && !$eventId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu payment_id hoặc event_id']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Tìm thanh toán cũ
        if ($paymentId) {
            $stmt = $pdo->prepare("
                SELECT t.*, dl.TenSuKien, k.HoTen, k.SoDienThoai, k.DiaChi, k.ID_KhachHang, u.Email as UserEmail
                FROM thanhtoan t
                INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                INNER JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
                LEFT JOIN users u ON k.ID_User = u.ID_User
                WHERE t.ID_ThanhToan = ?
            ");
            $stmt->execute([$paymentId]);
            $oldPayment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$oldPayment) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy thanh toán']);
                return;
            }
            
            $eventId = $oldPayment['ID_DatLich'];
        } else {
            // Lấy thông tin sự kiện
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
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy sự kiện']);
                return;
            }
            
            // Tìm thanh toán đang chờ xử lý
            $stmt = $pdo->prepare("
                SELECT * FROM thanhtoan 
                WHERE ID_DatLich = ? AND TrangThai = 'Đang xử lý'
                ORDER BY ID_ThanhToan DESC
                LIMIT 1
            ");
            $stmt->execute([$eventId]);
            $oldPayment = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Chỉ xóa nếu thanh toán đang chờ xử lý hoặc đã hủy
        if ($oldPayment && in_array($oldPayment['TrangThai'], ['Đang xử lý', 'Hủy'])) {
            // Xóa thanh toán cũ
            $stmt = $pdo->prepare("DELETE FROM thanhtoan WHERE ID_ThanhToan = ?");
            $stmt->execute([$oldPayment['ID_ThanhToan']]);
            
            // Xóa lịch sử thanh toán
            try {
                $stmt = $pdo->prepare("DELETE FROM payment_history WHERE payment_id = ?");
                $stmt->execute([$oldPayment['ID_ThanhToan']]);
            } catch (Exception $e) {
                error_log("Warning: Could not delete payment_history: " . $e->getMessage());
            }
            
            // Xóa hóa đơn nếu có
            try {
                $stmt = $pdo->prepare("DELETE FROM hoadon WHERE ID_ThanhToan = ?");
                $stmt->execute([$oldPayment['ID_ThanhToan']]);
            } catch (Exception $e) {
                error_log("Warning: Could not delete hoadon: " . $e->getMessage());
            }
            
            error_log("Deleted old payment ID: {$oldPayment['ID_ThanhToan']}");
        }
        
        // Lấy thông tin sự kiện để tạo lại
        if (!isset($event)) {
            $stmt = $pdo->prepare("
                SELECT dl.*, k.HoTen, k.SoDienThoai, k.DiaChi, k.ID_KhachHang, u.Email as UserEmail
                FROM datlichsukien dl
                INNER JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
                LEFT JOIN users u ON k.ID_User = u.ID_User
                WHERE dl.ID_DatLich = ?
            ");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Tính toán lại số tiền
        $amount = $oldPayment ? $oldPayment['SoTien'] : ($_POST['amount'] ?? null);
        $paymentType = $oldPayment ? $oldPayment['LoaiThanhToan'] : ($_POST['payment_type'] ?? 'deposit');
        
        if (!$amount) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'Thiếu số tiền']);
            return;
        }
        
        // Tạo lại thanh toán mới (gọi lại createSePayPayment logic)
        // Lưu các biến POST cần thiết
        $_POST['event_id'] = $eventId;
        $_POST['amount'] = $amount;
        $_POST['payment_type'] = $paymentType === 'Đặt cọc' ? 'deposit' : 'full';
        $_POST['invoice_data'] = json_encode([
            'name' => $event['HoTen'],
            'phone' => $event['SoDienThoai'],
            'address' => $event['DiaChi']
        ]);
        
        $pdo->commit();
        
        // Gọi lại createSePayPayment
        createSePayPayment();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Lỗi xóa và tạo lại thanh toán: ' . $e->getMessage()]);
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

/**
 * ✅ Tự động hủy các thanh toán "Đang xử lý" đã quá thời gian (mặc định: 15 phút)
 * Nếu người dùng tạo thanh toán nhưng không chuyển khoản và thoát trang,
 * thanh toán sẽ tự động bị hủy sau một khoảng thời gian và sự kiện quay lại trạng thái "Chưa thanh toán"
 * 
 * @param int $expirationMinutes Thời gian hết hạn tính bằng phút (mặc định: 15 phút)
 * @return array Kết quả xử lý
 */
function autoCancelExpiredPendingPayments($expirationMinutes = 15) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Tính thời gian hết hạn (mặc định: 15 phút trước)
        $expirationTime = date('Y-m-d H:i:s', strtotime("-{$expirationMinutes} minutes"));
        
        // Tìm các thanh toán "Đang xử lý" đã tạo hơn X phút trước
        $stmt = $pdo->prepare("
            SELECT t.ID_ThanhToan, t.ID_DatLich, t.LoaiThanhToan, t.NgayTao, dl.TrangThaiThanhToan
            FROM thanhtoan t
            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
            WHERE t.TrangThai = 'Đang xử lý'
            AND t.NgayTao < ?
            AND t.PhuongThuc = 'Chuyển khoản' -- Chỉ hủy thanh toán chuyển khoản, không hủy tiền mặt
        ");
        $stmt->execute([$expirationTime]);
        $expiredPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cancelledCount = 0;
        $eventsToReset = [];
        
        foreach ($expiredPayments as $payment) {
            // Hủy thanh toán
            $stmt = $pdo->prepare("
                UPDATE thanhtoan 
                SET TrangThai = 'Hủy', 
                    GhiChu = CONCAT(IFNULL(GhiChu, ''), ' - Tự động hủy: Quá thời gian chờ thanh toán (', NOW(), ')')
                WHERE ID_ThanhToan = ?
            ");
            $stmt->execute([$payment['ID_ThanhToan']]);
            
            // Thêm lịch sử thanh toán
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO payment_history (payment_id, action, old_status, new_status, description) 
                    VALUES (?, 'auto_cancel_expired', 'Đang xử lý', 'Hủy', ?)
                ");
                $description = "Tự động hủy: Quá thời gian chờ thanh toán ({$expirationMinutes} phút)";
                $stmt->execute([$payment['ID_ThanhToan'], $description]);
            } catch (PDOException $e) {
                // Bảng payment_history có thể không tồn tại, bỏ qua
                error_log("Warning: Could not insert payment history: " . $e->getMessage());
            }
            
            $cancelledCount++;
            
            // Lưu danh sách sự kiện cần đặt lại trạng thái
            if (!in_array($payment['ID_DatLich'], $eventsToReset)) {
                $eventsToReset[] = $payment['ID_DatLich'];
            }
        }
        
        // Đặt lại trạng thái sự kiện về "Chưa thanh toán" nếu không còn thanh toán "Đang xử lý" nào khác
        foreach ($eventsToReset as $eventId) {
            // Kiểm tra xem còn thanh toán "Đang xử lý" nào khác không
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM thanhtoan 
                WHERE ID_DatLich = ? AND TrangThai = 'Đang xử lý'
            ");
            $stmt->execute([$eventId]);
            $remainingPending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Nếu không còn thanh toán "Đang xử lý" nào và sự kiện đang ở trạng thái "Đã đặt cọc" (do thanh toán đặt cọc)
            // thì chỉ đặt lại nếu không có thanh toán thành công nào
            if ($remainingPending == 0) {
                // Kiểm tra xem có thanh toán thành công nào không
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM thanhtoan 
                    WHERE ID_DatLich = ? AND TrangThai = 'Thành công'
                ");
                $stmt->execute([$eventId]);
                $successfulCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // Chỉ đặt lại trạng thái nếu không có thanh toán thành công nào
                if ($successfulCount == 0) {
                    $stmt = $pdo->prepare("
                        UPDATE datlichsukien 
                        SET TrangThaiThanhToan = 'Chưa thanh toán' 
                        WHERE ID_DatLich = ?
                    ");
                    $stmt->execute([$eventId]);
                }
            }
        }
        
        $pdo->commit();
        
        error_log("Auto-cancelled {$cancelledCount} expired pending payments (older than {$expirationMinutes} minutes)");
        
        return [
            'success' => true,
            'cancelled_count' => $cancelledCount,
            'events_reset' => count($eventsToReset)
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error auto-cancelling expired pending payments: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
