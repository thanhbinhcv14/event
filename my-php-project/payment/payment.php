<?php
session_start();
require_once '../config/database.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

$pdo = getDBConnection();
$eventId = $_GET['event_id'] ?? null;
$paymentTypeParam = $_GET['payment_type'] ?? 'deposit'; // 'deposit', 'full', hoặc 'remaining'

if (!$eventId) {
    header('Location: ../events/my-events.php');
    exit();
}

$userId = $_SESSION['user']['ID_User'];

// Lấy chi tiết sự kiện với tất cả thông tin cần thiết (tương tự get_my_events)
try {
$stmt = $pdo->prepare("
        SELECT dl.*, d.TenDiaDiem, d.DiaChi, d.SucChua, d.GiaThueGio, d.GiaThueNgay, d.LoaiThue, d.LoaiDiaDiem,
               ls.TenLoai, ls.GiaCoBan, k.HoTen, k.SoDienThoai, k.DiaChi as DiaChiKhachHang, k.ID_KhachHang,
               u.Email as UserEmail,
               p.ID_Phong, p.TenPhong as TenPhong, p.GiaThueGio as PhongGiaThueGio, p.GiaThueNgay as PhongGiaThueNgay, p.LoaiThue as PhongLoaiThue,
               COALESCE(equipment_total.TongGiaThietBi, 0) as TongGiaThietBi,
               s.TrangThaiThucTe as TrangThaiSuKien,
               COALESCE(pending_payments.PendingPayments, 0) as PendingPayments
    FROM datlichsukien dl 
        INNER JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
        LEFT JOIN users u ON k.ID_User = u.ID_User
        LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
        LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
        LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
        LEFT JOIN phong p ON dl.ID_Phong = p.ID_Phong
        LEFT JOIN (
            SELECT ID_DatLich, SUM(DonGia * SoLuong) as TongGiaThietBi
            FROM chitietdatsukien
            WHERE ID_TB IS NOT NULL OR ID_Combo IS NOT NULL
            GROUP BY ID_DatLich
        ) equipment_total ON dl.ID_DatLich = equipment_total.ID_DatLich
        LEFT JOIN (
            SELECT ID_DatLich, COUNT(*) as PendingPayments
            FROM thanhtoan
            WHERE TrangThai = 'Đang xử lý'
            GROUP BY ID_DatLich
        ) pending_payments ON pending_payments.ID_DatLich = dl.ID_DatLich
        WHERE dl.ID_DatLich = ? AND k.ID_User = ?
    ");
    $stmt->execute([$eventId, $userId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: ../events/my-events.php');
    exit();
}

    // Tính toán phân tích giá
    $eventTypePrice = floatval($event['GiaCoBan'] ?? 0);
    $equipmentPrice = floatval($event['TongGiaThietBi'] ?? 0);
    
    // Tính giá địa điểm
    $locationPrice = 0;
    if (!empty($event['NgayBatDau']) && !empty($event['NgayKetThuc'])) {
        $startDate = new DateTime($event['NgayBatDau']);
        $endDate = new DateTime($event['NgayKetThuc']);
        $durationHours = ceil(($endDate->getTimestamp() - $startDate->getTimestamp()) / 3600);
        $durationDays = ceil(($endDate->getTimestamp() - $startDate->getTimestamp()) / 86400);
        
        // Kiểm tra địa điểm trong nhà có phòng không
        $isIndoor = ($event['LoaiDiaDiem'] === 'Trong nhà' || $event['LoaiDiaDiem'] === 'Trong nha');
        $hasRoom = !empty($event['TenPhong']) && !empty($event['ID_Phong']);
        
        if ($isIndoor && $hasRoom) {
            // Sử dụng giá phòng
            if ($event['LoaiThueApDung'] === 'Theo giờ' && !empty($event['PhongGiaThueGio'])) {
                $locationPrice = $durationHours * floatval($event['PhongGiaThueGio']);
            } else if ($event['LoaiThueApDung'] === 'Theo ngày' && !empty($event['PhongGiaThueNgay'])) {
                $locationPrice = $durationDays * floatval($event['PhongGiaThueNgay']);
            } else if ($event['PhongLoaiThue'] === 'Theo giờ' && !empty($event['PhongGiaThueGio'])) {
                $locationPrice = $durationHours * floatval($event['PhongGiaThueGio']);
            } else if ($event['PhongLoaiThue'] === 'Theo ngày' && !empty($event['PhongGiaThueNgay'])) {
                $locationPrice = $durationDays * floatval($event['PhongGiaThueNgay']);
            } else if ($event['PhongLoaiThue'] === 'Cả hai') {
                $hourlyPrice = $durationHours * floatval($event['PhongGiaThueGio'] ?? 0);
                $dailyPrice = $durationDays * floatval($event['PhongGiaThueNgay'] ?? 0);
                $locationPrice = ($hourlyPrice > 0 && $dailyPrice > 0) ? min($hourlyPrice, $dailyPrice) : max($hourlyPrice, $dailyPrice);
            }
        } else if ($event['LoaiThueApDung']) {
            // Sử dụng loại thuê đã áp dụng
            if ($event['LoaiThueApDung'] === 'Theo giờ' && !empty($event['GiaThueGio'])) {
                $locationPrice = $durationHours * floatval($event['GiaThueGio']);
            } else if ($event['LoaiThueApDung'] === 'Theo ngày' && !empty($event['GiaThueNgay'])) {
                $locationPrice = $durationDays * floatval($event['GiaThueNgay']);
            }
        } else if ($event['LoaiThue'] === 'Theo giờ' && !empty($event['GiaThueGio'])) {
            $locationPrice = $durationHours * floatval($event['GiaThueGio']);
        } else if ($event['LoaiThue'] === 'Theo ngày' && !empty($event['GiaThueNgay'])) {
            $locationPrice = $durationDays * floatval($event['GiaThueNgay']);
        } else if ($event['LoaiThue'] === 'Cả hai') {
            $hourlyPrice = $durationHours * floatval($event['GiaThueGio'] ?? 0);
            $dailyPrice = $durationDays * floatval($event['GiaThueNgay'] ?? 0);
            $locationPrice = ($hourlyPrice > 0 && $dailyPrice > 0) ? min($hourlyPrice, $dailyPrice) : max($hourlyPrice, $dailyPrice);
        }
    }
    
    // Sử dụng TongTien nếu có, nếu không thì tính toán
    $totalAmount = !empty($event['TongTien']) && $event['TongTien'] > 0 
        ? floatval($event['TongTien']) 
        : ($locationPrice + $eventTypePrice + $equipmentPrice);
    
    // Tính số tiền đặt cọc
    $depositAmount = !empty($event['TienCocYeuCau']) && $event['TienCocYeuCau'] > 0 
        ? floatval($event['TienCocYeuCau']) 
        : round($totalAmount * 0.3);
    
    // Tính số tiền còn lại (nếu đã đặt cọc, tính từ tổng tiền trừ đi số tiền đã đặt cọc)
    $remainingAmount = $totalAmount - $depositAmount;
    
    // Nếu đã đặt cọc, tính lại số tiền còn lại từ tổng tiền trừ đi số tiền đã thanh toán
    if ($event['TrangThaiThanhToan'] === 'Đã đặt cọc') {
        $stmtPaid = $pdo->prepare("
            SELECT SUM(SoTien) as TotalPaid 
            FROM thanhtoan 
            WHERE ID_DatLich = ? 
            AND TrangThai = 'Thành công'
        ");
        $stmtPaid->execute([$eventId]);
        $paidResult = $stmtPaid->fetch(PDO::FETCH_ASSOC);
        $totalPaid = floatval($paidResult['TotalPaid'] ?? 0);
        $remainingAmount = $totalAmount - $totalPaid;
    }
    
    // Tính số ngày từ đăng ký đến sự kiện
    $daysFromRegistrationToEvent = 0;
    if (!empty($event['NgayTao']) && !empty($event['NgayBatDau'])) {
        $registrationDate = new DateTime($event['NgayTao']);
        $eventStartDate = new DateTime($event['NgayBatDau']);
        $daysFromRegistrationToEvent = $registrationDate->diff($eventStartDate)->days;
    }
    $requiresFullPayment = ($daysFromRegistrationToEvent > 0 && $daysFromRegistrationToEvent < 7);
    
    // Lấy hạn thanh toán nếu đã có đặt cọc
    $paymentDeadline = null;
    if ($event['TrangThaiThanhToan'] === 'Đã đặt cọc' && !empty($event['NgayBatDau'])) {
        $stmtDeposit = $pdo->prepare("
            SELECT NgayThanhToan 
            FROM thanhtoan 
            WHERE ID_DatLich = ? 
            AND LoaiThanhToan = 'Đặt cọc' 
            AND TrangThai = 'Thành công'
            ORDER BY NgayThanhToan ASC
            LIMIT 1
        ");
        $stmtDeposit->execute([$eventId]);
        $depositPayment = $stmtDeposit->fetch(PDO::FETCH_ASSOC);
        
        if ($depositPayment && !empty($depositPayment['NgayThanhToan'])) {
            $depositDate = new DateTime($depositPayment['NgayThanhToan']);
            $now = new DateTime();
            $deadlineDate = clone $depositDate;
            $deadlineDate->modify('+7 days');
            
            $daysUntilDeadline = $now->diff($deadlineDate)->days;
            
            $paymentDeadline = [
                'deadline_date' => $deadlineDate->format('Y-m-d H:i:s'),
                'deadline_formatted' => $deadlineDate->format('d/m/Y'),
                'days_until_deadline' => $daysUntilDeadline,
                'is_past_deadline' => $now > $deadlineDate,
                'is_approaching' => $daysUntilDeadline <= 3 && $daysUntilDeadline > 0
            ];
        }
    }
    
} catch (Exception $e) {
    error_log("Error loading event: " . $e->getMessage());
    header('Location: ../events/my-events.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán sự kiện - <?= htmlspecialchars($event['TenSuKien']) ?></title>
    <link rel="icon" href="../img/logo/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .payment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            overflow: hidden;
        }
        
        .payment-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .payment-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .payment-content {
            padding: 2rem;
        }
        
        .info-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: white;
        }
        
        .info-card h5 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .payment-method-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .payment-method-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .payment-method-card.selected {
            border-color: #667eea !important;
            background-color: #f8f9ff !important;
        }
        
        .payment-type-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .payment-type-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .payment-type-card.selected {
            border-color: #667eea !important;
            background-color: #f8f9ff !important;
        }
        
        .summary-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            position: sticky;
            top: 20px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 12px;
        }
        
        /* ✅ SePay QR Code Template Styling */
        .sepay-qr-wrapper {
            background: white;
            border: 3px solid #1e3a8a;
            border-radius: 12px;
            padding: 20px;
            max-width: 400px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .sepay-qr-header {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .sepay-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #1e40af;
            font-weight: 700;
            font-size: 24px;
        }
        
        .sepay-logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3b82f6, #1e40af);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .sepay-qr-code {
            border: 2px solid #1e3a8a;
            border-radius: 8px;
            padding: 15px;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 15px 0;
        }
        
        .sepay-qr-code img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }
        
        .sepay-qr-footer {
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 10px 0;
            border-top: 1px solid #e5e7eb;
            margin-top: 15px;
        }
        
        .sepay-partner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            flex: 1;
        }
        
        .sepay-partner-name {
            font-size: 12px;
            font-weight: 600;
            color: #1e40af;
        }
        
        .sepay-partner-logo {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .napas-logo {
            background: #1e40af;
            color: white;
        }
        
        .vietinbank-logo {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
        }
        
        .vietqr-logo {
            background: #dc2626;
            color: white;
        }
        
        .sepay-divider {
            width: 1px;
            height: 30px;
            background: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-container">
            <!-- Header -->
            <div class="payment-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-credit-card"></i> Thanh toán sự kiện</h1>
                        <p class="mb-0">Xác nhận thông tin và hoàn tất thanh toán</p>
                    </div>
                    <div>
                        <a href="../events/my-events.php" class="btn btn-light">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="payment-content">
        <div class="row">
                    <div class="col-lg-8">
                <!-- Event Information -->
                        <div class="info-card">
                            <h5><i class="fas fa-calendar-alt"></i> Thông tin sự kiện</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Tên sự kiện:</strong> <?= htmlspecialchars($event['TenSuKien']) ?></p>
                                    <p><strong>Loại sự kiện:</strong> <?= htmlspecialchars($event['TenLoai'] ?? 'Chưa xác định') ?></p>
                                    <p><strong>Địa điểm:</strong> <?= htmlspecialchars($event['TenDiaDiem'] ?? 'Chưa xác định') ?></p>
                                    <?php if (!empty($event['TenPhong']) && ($event['LoaiDiaDiem'] === 'Trong nhà' || $event['LoaiDiaDiem'] === 'Trong nha')): ?>
                                    <p><strong>Phòng:</strong> <?= htmlspecialchars($event['TenPhong']) ?></p>
                                    <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Ngày bắt đầu:</strong> <?= date('d/m/Y H:i', strtotime($event['NgayBatDau'])) ?></p>
                                <p><strong>Ngày kết thúc:</strong> <?= date('d/m/Y H:i', strtotime($event['NgayKetThuc'])) ?></p>
                                    <p><strong>Số người:</strong> <?= htmlspecialchars($event['SoNguoiDuKien'] ?? 'Chưa xác định') ?> người</p>
                        </div>
                    </div>
                </div>

                        <!-- Payment Method Selection -->
                        <div class="info-card">
                            <h5><i class="fas fa-credit-card"></i> Chọn phương thức thanh toán</h5>
                        <div class="row">
                            <div class="col-md-6">
                                    <div class="payment-method-card" data-method="sepay" id="sepayCard">
                                        <i class="fas fa-university fa-3x text-primary mb-3"></i>
                                        <h6>SePay Banking</h6>
                                        <small class="text-muted">Thanh toán qua ngân hàng</small>
                                        </div>
                                    </div>
                            <div class="col-md-6">
                                    <div class="payment-method-card" data-method="cash" id="cashCard">
                                        <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                                        <h6>Tiền mặt</h6>
                                        <small class="text-muted">Thanh toán trực tiếp</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Details (hidden by default) -->
                            <div id="paymentDetails" style="display: none;" class="mt-3">
                                <div id="sepayDetails" class="payment-details" style="display: none;">
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        Sau khi nhấn "Xác nhận thanh toán", hệ thống sẽ hiển thị QR và thông tin ngân hàng.
                                        </div>
                                    </div>
                                <div id="cashDetails" class="payment-details" style="display: none;">
                                    <div class="alert alert-success">
                                        <h6><i class="fas fa-money-bill-wave"></i> Thanh toán tiền mặt</h6>
                                        <p>Vui lòng liên hệ trực tiếp với chúng tôi để thanh toán:</p>
                                        <ul>
                                            <li><strong>Địa chỉ:</strong> 123 Đường ABC, Quận 1, TP.HCM</li>
                                            <li><strong>Điện thoại:</strong> 0123456789</li>
                                            <li><strong>Thời gian:</strong> 8:00 - 17:00 (Thứ 2 - Thứ 6)</li>
                                        </ul>
                                </div>
                            </div>
                                </div>
                            </div>

                        <!-- Payment Type Selection -->
                        <div class="info-card" id="paymentTypeSection" style="display: none;">
                            <h5><i class="fas fa-calculator"></i> Chọn loại thanh toán</h5>
                            <div class="row">
                            <div class="col-md-6">
                                    <div class="payment-type-card" data-type="deposit" id="depositCard">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="paymentType" id="depositPayment" value="deposit" 
                                                   <?= ($paymentTypeParam === 'deposit' && !$requiresFullPayment && $event['TrangThaiThanhToan'] !== 'Đã đặt cọc') ? 'checked' : '' ?>
                                                   <?= ($requiresFullPayment || $event['TrangThaiThanhToan'] === 'Đã đặt cọc') ? 'disabled' : '' ?>>
                                            <label class="form-check-label w-100" for="depositPayment">
                                                <strong>Đặt cọc</strong>
                                                <br>
                                                <span class="text-primary fw-bold"><?= number_format($depositAmount, 0, ',', '.') ?> VNĐ</span>
                                                <br>
                                                <small class="text-muted">Thanh toán 30% để giữ chỗ</small>
                                                <?php if ($requiresFullPayment): ?>
                                                <br><small class="text-danger"><i class="fas fa-ban"></i> Sự kiện diễn ra trong vòng 7 ngày, không thể đặt cọc</small>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </div>
                            </div>
                            <div class="col-md-6">
                                    <div class="payment-type-card" data-type="<?= $event['TrangThaiThanhToan'] === 'Đã đặt cọc' ? 'remaining' : 'full' ?>" id="fullCard">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="paymentType" id="fullPayment" value="<?= $event['TrangThaiThanhToan'] === 'Đã đặt cọc' ? 'remaining' : 'full' ?>"
                                                   <?= ($paymentTypeParam === 'full' || $paymentTypeParam === 'remaining' || $requiresFullPayment || $event['TrangThaiThanhToan'] === 'Đã đặt cọc') ? 'checked' : '' ?>
                                                   <?= (!$requiresFullPayment && $event['TrangThaiThanhToan'] !== 'Đã đặt cọc') ? 'disabled' : '' ?>>
                                            <label class="form-check-label w-100" for="fullPayment">
                                                <strong><?= $event['TrangThaiThanhToan'] === 'Đã đặt cọc' ? 'Thanh toán phần còn lại' : 'Thanh toán đủ' ?></strong>
                                                <br>
                                                <span class="text-success fw-bold"><?= number_format($event['TrangThaiThanhToan'] === 'Đã đặt cọc' ? $remainingAmount : $totalAmount, 0, ',', '.') ?> VNĐ</span>
                                                <br>
                                                <small class="text-muted"><?= $event['TrangThaiThanhToan'] === 'Đã đặt cọc' ? 'Thanh toán số tiền còn lại' : 'Thanh toán toàn bộ số tiền' ?></small>
                                                <?php if ($requiresFullPayment): ?>
                                                <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Bắt buộc thanh toán đủ (sự kiện diễn ra trong vòng 7 ngày)</small>
                                                <?php endif; ?>
                                                <?php if ($paymentDeadline): ?>
                                                <br><small class="<?= $paymentDeadline['is_past_deadline'] ? 'text-danger' : ($paymentDeadline['is_approaching'] ? 'text-warning' : 'text-info') ?>">
                                                    <i class="fas fa-clock"></i> Hạn: <?= $paymentDeadline['deadline_formatted'] ?>
                                                    <?= $paymentDeadline['is_past_deadline'] ? '<br><strong>⚠️ Đã quá hạn! Phải đến công ty đóng tiền mặt.</strong>' : '' ?>
                                                </small>
                                                <?php endif; ?>
                                            </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                        <!-- Contact Information -->
                        <div class="info-card">
                            <h5><i class="fas fa-user"></i> Thông tin liên lạc</h5>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle"></i> 
                                <small>Vui lòng kiểm tra và cập nhật thông tin liên lạc để chúng tôi có thể liên hệ với bạn về thanh toán.</small>
                    </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="invoiceName" value="<?= htmlspecialchars($event['HoTen'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="invoicePhone" value="<?= htmlspecialchars($event['SoDienThoai'] ?? '') ?>" required>
                                    <small class="text-muted">Số điện thoại để liên lạc (có thể khác với số đăng ký)</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-muted">(không thể thay đổi)</span></label>
                                    <input type="email" class="form-control" id="invoiceEmail" value="<?= htmlspecialchars($event['UserEmail'] ?? '') ?>" readonly disabled style="background-color: #e9ecef; cursor: not-allowed;">
                                    <small class="text-muted">Email đăng ký tài khoản (không thể thay đổi)</small>
                            </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Địa chỉ</label>
                                    <input type="text" class="form-control" id="invoiceAddress" value="<?= htmlspecialchars($event['DiaChiKhachHang'] ?? '') ?>" placeholder="Nhập địa chỉ liên lạc">
                                    <small class="text-muted">Địa chỉ để liên lạc (có thể khác với địa chỉ đăng ký)</small>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Summary Sidebar -->
                    <div class="col-lg-4">
                        <div class="summary-card">
                            <h5 class="mb-3"><i class="fas fa-receipt"></i> Tóm tắt thanh toán</h5>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Giá loại sự kiện:</span>
                                    <strong><?= number_format($eventTypePrice, 0, ',', '.') ?> VNĐ</strong>
                    </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Giá địa điểm:</span>
                                    <strong><?= number_format($locationPrice, 0, ',', '.') ?> VNĐ</strong>
                        </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Giá thiết bị:</span>
                                    <strong><?= number_format($equipmentPrice, 0, ',', '.') ?> VNĐ</strong>
                            </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><strong>Tổng tiền:</strong></span>
                                    <strong class="text-primary"><?= number_format($totalAmount, 0, ',', '.') ?> VNĐ</strong>
                            </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tiền cọc:</span>
                                    <strong class="text-warning"><?= number_format($depositAmount, 0, ',', '.') ?> VNĐ</strong>
                        </div>
                                <div class="d-flex justify-content-between">
                                    <span>Còn lại:</span>
                                    <strong class="text-info"><?= number_format($remainingAmount, 0, ',', '.') ?> VNĐ</strong>
                </div>
            </div>

                        <!-- Discount Code Input -->
                        <div class="mb-3">
                            <label for="discountCode" class="form-label">
                                <i class="fas fa-ticket-alt text-warning"></i> <strong>Mã giảm giá</strong>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="discountCode" 
                                       placeholder="Nhập mã giảm giá hoặc chọn từ mã đã lưu" maxlength="50">
                                <button class="btn btn-outline-secondary" type="button" id="loadSavedCodeBtn" 
                                        onclick="event.preventDefault(); event.stopPropagation(); loadSavedDiscountCode(); return false;" title="Tải mã đã lưu">
                                    <i class="fas fa-bookmark"></i>
                                </button>
                                <button class="btn btn-outline-primary" type="button" id="applyDiscountBtn" onclick="event.preventDefault(); event.stopPropagation(); applyDiscountCode(); return false;">
                                    <i class="fas fa-check"></i> Áp dụng
                                </button>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-info-circle"></i> Mã giảm giá chỉ áp dụng khi thanh toán đủ 1 lần. 
                                Bạn có thể lưu mã giảm giá trên trang chủ để sử dụng sau.
                            </small>
                            <div id="discountCodeMessage" class="mt-2" style="display: none;"></div>
                        </div>

                        <div class="mb-3">
                                <label class="form-label"><strong>Số tiền thanh toán:</strong></label>
                            <div class="input-group">
                                    <input type="text" class="form-control form-control-lg text-center fw-bold" id="amountDisplay" readonly>
                                <span class="input-group-text">VNĐ</span>
                            </div>
                            <div id="discountInfo" class="mt-2" style="display: none;"></div>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-primary btn-lg" id="proceedPayment" disabled>
                                    <i class="fas fa-credit-card"></i> Xác nhận thanh toán
                            </button>
                        </div>

                            <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> 
                                Thông tin thanh toán được mã hóa và bảo mật
                            </small>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/csrf-helper.js"></script>
    <script>
        const eventId = <?= $eventId ?>;
        const totalAmount = <?= $totalAmount ?>;
        const depositAmount = <?= $depositAmount ?>;
        const remainingAmount = <?= $remainingAmount ?>;
        const requiresFullPayment = <?= $requiresFullPayment ? 'true' : 'false' ?>;
        const hasDeposit = <?= ($event['TrangThaiThanhToan'] === 'Đã đặt cọc') ? 'true' : 'false' ?>;
        const userId = <?= json_encode($_SESSION['user']['ID_User'] ?? null) ?>;
        
        let selectedPaymentType = '<?= $paymentTypeParam ?>';
        let selectedPaymentMethod = null;
        let appliedDiscountCode = null; // Store applied discount code data
        
        // Cập nhật hiển thị số tiền
        function updateAmountDisplay() {
            let baseAmount;
            if (selectedPaymentType === 'deposit') {
                baseAmount = depositAmount;
            } else if (selectedPaymentType === 'remaining' || hasDeposit) {
                baseAmount = remainingAmount;
            } else {
                baseAmount = totalAmount;
            }
            
            // Apply discount if available (chỉ cho SePay, không cho tiền mặt)
            let finalAmount = baseAmount;
            let discountAmount = 0;
            
            // Chỉ áp dụng mã giảm giá khi: SePay + thanh toán đủ 1 lần (không cho đặt cọc và thanh toán phần còn lại)
            if (appliedDiscountCode && appliedDiscountCode.success && 
                selectedPaymentMethod === 'sepay' && 
                selectedPaymentType === 'full') {
                discountAmount = appliedDiscountCode.discount_amount || 0;
                finalAmount = Math.max(0, baseAmount - discountAmount);
            } else {
                // Tiền mặt, đặt cọc hoặc thanh toán phần còn lại không áp dụng mã giảm giá
                if (selectedPaymentMethod === 'cash' || selectedPaymentType === 'deposit' || selectedPaymentType === 'remaining') {
                    appliedDiscountCode = null;
                }
            }
            
            $('#amountDisplay').val(new Intl.NumberFormat('vi-VN').format(finalAmount));
            
            // Update discount info display (chỉ hiển thị khi thanh toán đủ SePay)
            const discountInfoDiv = $('#discountInfo');
            if (discountAmount > 0 && selectedPaymentType === 'full' && selectedPaymentMethod === 'sepay') {
                const discountFormatted = new Intl.NumberFormat('vi-VN').format(discountAmount);
                const baseAmountFormatted = new Intl.NumberFormat('vi-VN').format(baseAmount);
                discountInfoDiv.html(`
                    <div class="alert alert-success mb-0 py-2">
                        <small>
                            <i class="fas fa-ticket-alt"></i> 
                            <strong>Đã giảm:</strong> ${discountFormatted} VNĐ
                            <br>
                            <span class="text-muted">Tổng gốc: ${baseAmountFormatted} VNĐ</span>
                        </small>
                    </div>
                `).show();
            } else {
                discountInfoDiv.hide();
            }
        }
        
        // Cập nhật lựa chọn thẻ loại thanh toán
        function updatePaymentTypeSelection() {
            $('.payment-type-card').removeClass('selected');
            if (selectedPaymentType === 'deposit') {
                $('#depositCard').addClass('selected');
            } else {
                $('#fullCard').addClass('selected');
            }
            // Cập nhật value của radio button nếu là remaining
            if (selectedPaymentType === 'remaining') {
                $('#fullPayment').val('remaining');
            } else if (selectedPaymentType === 'full') {
                $('#fullPayment').val('full');
            }
        }
        
        // Lựa chọn loại thanh toán
        $('input[name="paymentType"]').on('change', function() {
            selectedPaymentType = $(this).val();
            // Cập nhật trạng thái mã giảm giá dựa trên loại thanh toán
            updateDiscountCodeAvailability();
            updateAmountDisplay();
            updatePaymentTypeSelection();
            checkProceedButton();
        });

        // Lựa chọn phương thức thanh toán
        $('.payment-method-card').on('click', function() {
            $('.payment-method-card').removeClass('selected');
            $(this).addClass('selected');
            selectedPaymentMethod = $(this).data('method');
            
            // Hiển thị phần loại thanh toán
            $('#paymentTypeSection').slideDown(300);
            
            // Hiển thị/ẩn chi tiết thanh toán
            $('.payment-details').hide();
            $('#paymentDetails').show();
            if (selectedPaymentMethod === 'sepay') {
                $('#sepayDetails').show();
                // SePay: khôi phục logic loại thanh toán
                if (requiresFullPayment) {
                    // < 7 ngày: bắt buộc thanh toán đủ
                    $('#depositPayment').prop('disabled', true);
                    $('#fullPayment').prop('checked', true).prop('disabled', false);
                    selectedPaymentType = 'full';
                } else if (hasDeposit) {
                    // Đã đặt cọc: chỉ cho phép thanh toán phần còn lại
                    $('#depositPayment').prop('disabled', true);
                    $('#fullPayment').prop('checked', true).prop('disabled', false);
                    // Cập nhật value của fullPayment thành 'remaining'
                    $('#fullPayment').val('remaining');
                    selectedPaymentType = 'remaining';
                } else {
                    // >= 7 ngày và chưa đặt cọc: cho phép cả deposit và full
                    $('#depositPayment').prop('disabled', false);
                    $('#fullPayment').prop('disabled', false);
                    $('#fullPayment').val('full');
                    selectedPaymentType = 'deposit';
                    $('#depositPayment').prop('checked', true);
                }
                updateAmountDisplay();
                updatePaymentTypeSelection();
                // Cập nhật trạng thái mã giảm giá
                updateDiscountCodeAvailability();
            } else if (selectedPaymentMethod === 'cash') {
                $('#cashDetails').show();
                // Tiền mặt phải là thanh toán đủ hoặc phần còn lại
                $('#depositPayment').prop('disabled', true);
                $('#fullPayment').prop('checked', true).prop('disabled', false);
                if (hasDeposit) {
                    $('#fullPayment').val('remaining');
                    selectedPaymentType = 'remaining';
                } else {
                    $('#fullPayment').val('full');
                    selectedPaymentType = 'full';
                }
                // Tiền mặt không cho áp dụng mã giảm giá
                disableDiscountCode();
                updateAmountDisplay();
                updatePaymentTypeSelection();
                // Cập nhật trạng thái mã giảm giá
                updateDiscountCodeAvailability();
            }
            
            checkProceedButton();
        });
        
        // Kiểm tra nút xác nhận có nên được bật không
        function checkProceedButton() {
            const invoiceName = $('#invoiceName').val().trim();
            const invoicePhone = $('#invoicePhone').val().trim();
            
            if (selectedPaymentMethod && selectedPaymentType && invoiceName && invoicePhone) {
                $('#proceedPayment').prop('disabled', false);
            } else {
                $('#proceedPayment').prop('disabled', true);
            }
        }
        
        // Xác thực thông tin liên lạc
        $('#invoiceName, #invoicePhone').on('input', checkProceedButton);
        
        // Khởi tạo
        updateAmountDisplay();
        updatePaymentTypeSelection();
        // Cập nhật trạng thái mã giảm giá ban đầu
        updateDiscountCodeAvailability();

        // Tiến hành thanh toán
        $('#proceedPayment').on('click', function() {
            if (!selectedPaymentMethod) {
                alert('Vui lòng chọn phương thức thanh toán');
                return;
            }

            const invoiceName = $('#invoiceName').val().trim();
            const invoicePhone = $('#invoicePhone').val().trim();
            
            if (!invoiceName || !invoicePhone) {
                alert('Vui lòng điền đầy đủ thông tin liên lạc (Họ tên và Số điện thoại)');
                return;
            }

            let baseAmount;
            if (selectedPaymentType === 'deposit') {
                baseAmount = depositAmount;
            } else if (selectedPaymentType === 'remaining' || hasDeposit) {
                baseAmount = remainingAmount;
            } else {
                baseAmount = totalAmount;
            }
            
            // Apply discount if available (chỉ cho SePay + thanh toán đủ 1 lần, không cho đặt cọc và thanh toán phần còn lại)
            let amount = baseAmount;
            if (appliedDiscountCode && appliedDiscountCode.success && 
                selectedPaymentMethod === 'sepay' && 
                selectedPaymentType === 'full') {
                const discountAmount = appliedDiscountCode.discount_amount || 0;
                amount = Math.max(0, baseAmount - discountAmount);
            }
            
            const invoiceData = {
                name: invoiceName,
                phone: invoicePhone,
                address: $('#invoiceAddress').val().trim() || null
            };
            
            // Prepare discount code data (chỉ gửi khi SePay + thanh toán đủ 1 lần)
            const discountData = (appliedDiscountCode && appliedDiscountCode.success && 
                                  selectedPaymentMethod === 'sepay' && 
                                  selectedPaymentType === 'full') ? {
                discount_code: appliedDiscountCode.code.MaCode,
                discount_amount: appliedDiscountCode.discount_amount || 0,
                discount_id: appliedDiscountCode.code.ID_MaGiamGia
            } : null;
            
            if (confirm(`Xác nhận thanh toán ${new Intl.NumberFormat('vi-VN').format(amount)} VNĐ qua ${selectedPaymentMethod === 'sepay' ? 'SePay Banking' : 'Tiền mặt'}?`)) {
                // Hiển thị loading
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
                
                // Chuẩn bị dữ liệu API
            let apiAction, apiData;
            
            if (selectedPaymentMethod === 'sepay') {
                apiAction = 'create_sepay_payment';
                apiData = {
                    action: apiAction,
                    event_id: eventId,
                    amount: amount,
                    payment_type: selectedPaymentType,
                    invoice_data: invoiceData
                };
            } else {
                apiAction = 'create_payment';
                apiData = {
                    action: apiAction,
                    event_id: eventId,
                    amount: amount,
                    payment_method: 'cash',
                    payment_type: selectedPaymentType,
                    invoice_data: invoiceData
                };
            }
            
            // Add discount code data if available
            if (discountData) {
                apiData.discount_code = discountData.discount_code;
                apiData.discount_amount = discountData.discount_amount;
                apiData.discount_id = discountData.discount_id;
            }
            
            // Đảm bảo CSRF token được thêm vào trước khi gửi request
            const sendPaymentRequest = async () => {
                    try {
                        // Lấy CSRF token
                        const csrfToken = await (window.CSRFHelper ? window.CSRFHelper.getToken() : Promise.resolve(null));
                        
                        // Thêm token vào data nếu chưa có
                        if (csrfToken && !apiData.csrf_token) {
                            apiData.csrf_token = csrfToken;
                        }
                        
                        // Xử lý thanh toán
                        $.ajax({
                            url: '../src/controllers/payment.php',
                method: 'POST',
                            data: apiData,
                            dataType: 'json',
                            beforeSend: function(xhr) {
                                if (csrfToken) {
                                    xhr.setRequestHeader('X-CSRF-Token', csrfToken);
                                }
                            },
                            success: function(response) {
                                console.log('Payment Response:', response);
                                
                                if (response.success) {
                                    if (selectedPaymentMethod === 'cash') {
                        alert('Yêu cầu thanh toán tiền mặt đã được tạo. Trạng thái: Đang xử lý.');
                                        window.location.href = '../events/my-events.php';
                                    } else if (response.sepay_checkout_url || response.form_html) {
                                        // ✅ Ưu tiên: Submit POST form đến SePay Checkout Gateway
                                        // SePay yêu cầu POST form, không phải GET redirect
                                        console.log('Submitting SePay Checkout form');
                                        console.log('Response data:', {
                                            has_form_html: !!response.form_html,
                                            has_form_fields: !!response.form_fields,
                                            checkout_url: response.sepay_checkout_url,
                                            checkout_warning: response.checkout_warning
                                        });
                                        
                                        // ⚠️ Thử submit form, nếu fail thì fallback về QR code
                                        let formSubmitted = false;
                                        
                                        if (response.form_html) {
                                            // Render và tự động submit form
                                            console.log('Using form_html from response');
                                            console.log('Form HTML preview:', response.form_html.substring(0, 500));
                                            
                                            // Xóa form cũ nếu có
                                            $('#sepay-checkout-form').remove();
                                            
                                            // Append form vào body
                                            $('body').append(response.form_html);
                                            
                                            // Kiểm tra form có tồn tại không
                                            const formElement = $('#sepay-checkout-form');
                                            if (formElement.length > 0) {
                                                console.log('Form found, action URL:', formElement.attr('action'));
                                                console.log('Form method:', formElement.attr('method'));
                                                
                                                // Thêm event listener để detect nếu form submit fail
                                                formElement.on('submit', function(e) {
                                                    console.log('Form submit event triggered');
                                                    formSubmitted = true;
                                                });
                                                
                                                // Submit form sau một chút delay để đảm bảo form đã được render
                                                setTimeout(function() {
                                                    try {
                                                        formElement.submit();
                                                        // Nếu sau 3 giây vẫn chưa redirect, có thể form submit fail
                                                        setTimeout(function() {
                                                            if (!formSubmitted && document.visibilityState === 'visible') {
                                                                console.warn('Form submit may have failed, falling back to QR code');
                                                                if (response.bank_info || response.qr_code || response.qr_string) {
                                                                    showSePayQRCode(response);
                                                                }
                                                            }
                                                        }, 3000);
                                                    } catch (error) {
                                                        console.error('Error submitting form:', error);
                                                        // Fallback về QR code
                                                        if (response.bank_info || response.qr_code || response.qr_string) {
                                                            showSePayQRCode(response);
                                                        }
                                                    }
                                                }, 100);
                                            } else {
                                                console.error('Form not found after append!');
                                                // Fallback: tạo form từ form_fields hoặc hiển thị QR
                                                if (response.sepay_checkout_url && response.form_fields) {
                                                    createAndSubmitForm(response.sepay_checkout_url, response.form_fields);
                                                } else if (response.bank_info || response.qr_code || response.qr_string) {
                                                    showSePayQRCode(response);
                                                }
                                            }
                                        } else if (response.sepay_checkout_url && response.form_fields) {
                                            // Tạo form động từ form_fields
                                            console.log('Creating form from form_fields');
                                            createAndSubmitForm(response.sepay_checkout_url, response.form_fields);
                    } else {
                                            // Fallback: redirect hoặc hiển thị QR code
                                            console.warn('Fallback: redirecting to SePay Checkout (may not work):', response.sepay_checkout_url);
                                            if (response.sepay_checkout_url) {
                                                // Thử redirect, nếu fail thì fallback về QR
                                                const redirectTimeout = setTimeout(function() {
                                                    if (document.visibilityState === 'visible') {
                                                        console.warn('Redirect may have failed, falling back to QR code');
                                                        if (response.bank_info || response.qr_code || response.qr_string) {
                                                            showSePayQRCode(response);
                                                        }
                                                    }
                                                }, 3000);
                                                
                                                try {
                                                    window.location.href = response.sepay_checkout_url;
                                                } catch (error) {
                                                    console.error('Error redirecting:', error);
                                                    clearTimeout(redirectTimeout);
                                                    if (response.bank_info || response.qr_code || response.qr_string) {
                                                        showSePayQRCode(response);
                                                    } else {
                                                        alert('Lỗi: Không thể truy cập trang thanh toán. Vui lòng thử lại hoặc sử dụng QR code.');
                                                    }
                                                }
                                            } else {
                                                // Không có URL, hiển thị QR code nếu có
                                                if (response.bank_info || response.qr_code || response.qr_string) {
                                                    showSePayQRCode(response);
                                                } else {
                                                    alert('Lỗi: Không có URL thanh toán. Vui lòng thử lại.');
                                                }
                                            }
                                        }
                                        
                                        // Helper function để tạo và submit form
                                        function createAndSubmitForm(checkoutUrl, formFields) {
                                            console.log('Creating form with URL:', checkoutUrl);
                                            console.log('Form fields:', Object.keys(formFields));
                                            
                                            // Xóa form cũ nếu có
                                            $('#sepay-checkout-form-dynamic').remove();
                                            
                                            // Tạo form động
                                            const form = $('<form>', {
                                                id: 'sepay-checkout-form-dynamic',
                                                method: 'POST',
                                                action: checkoutUrl,
                                                style: 'display: none;'
                                            });
                                            
                                            // Thêm các field
                                            $.each(formFields, function(key, value) {
                                                form.append($('<input>', {
                                                    type: 'hidden',
                                                    name: key,
                                                    value: value
                                                }));
                                            });
                                            
                                            // Append vào body
                                            $('body').append(form);
                                            
                                            // Submit form với error handling
                                            setTimeout(function() {
                                                try {
                                                    form.submit();
                                                    // Nếu sau 3 giây vẫn chưa redirect, có thể form submit fail
                                                    setTimeout(function() {
                                                        if (document.visibilityState === 'visible') {
                                                            console.warn('Form submit may have failed, check if QR code is available');
                                                        }
                                                    }, 3000);
                                                } catch (error) {
                                                    console.error('Error submitting dynamic form:', error);
                                                }
                                            }, 100);
                                        }
                                    } else if (response.bank_info || response.qr_code || response.qr_string) {
                                        // Hiển thị QR code và thông tin ngân hàng ngay trên trang
                                        console.log('Calling showSePayQRCode with:', response);
                                        showSePayQRCode(response);
                                        
                                        // ✅ Tự động bắt đầu polling sau 30 giây (để user có thời gian chuyển khoản)
                                        if (response.payment_id) {
                                            setTimeout(function() {
                                                console.log('Starting auto-polling for payment:', response.payment_id);
                                                startAutoPolling(response.payment_id, response.transaction_code || response.transaction_id);
                                            }, 30000); // Đợi 30 giây trước khi bắt đầu polling
                                        }
                    } else {
                                        console.warn('Response không có bank_info hoặc qr_code:', response);
                                        alert('Thanh toán thành công! Chúng tôi sẽ xác nhận trong thời gian sớm nhất.');
                        window.location.href = '../events/my-events.php';
                    }
                } else {
                                    alert('Lỗi thanh toán: ' + response.error);
                                    $('#proceedPayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Xác nhận thanh toán');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('=== Payment Error Details ===');
                                console.error('Status:', status);
                                console.error('Error:', error);
                                console.error('HTTP Status Code:', xhr.status);
                                console.error('Response Text:', xhr.responseText);
                                
                                // Thử parse JSON response nếu có
                                let errorMessage = 'Lỗi không xác định';
                                let errorDetails = null;
                                
                                try {
                                    if (xhr.responseText) {
                                        const response = JSON.parse(xhr.responseText);
                                        errorMessage = response.error || response.message || 'Lỗi không xác định';
                                        errorDetails = response;
                                        console.error('Parsed Error Response:', response);
                                    }
                                } catch (e) {
                                    console.error('Could not parse response as JSON:', e);
                                    // Nếu không phải JSON, hiển thị raw response
                                    if (xhr.responseText && xhr.responseText.length < 500) {
                                        errorMessage = xhr.responseText;
                                    }
                                }
                                
                                console.error('Error Message:', errorMessage);
                                console.error('=== End Payment Error Details ===');
                                
                                // Hiển thị thông báo lỗi cho người dùng
                                let userMessage = 'Đã xảy ra lỗi khi xử lý thanh toán.';
                                
                                if (xhr.status === 500) {
                                    userMessage = 'Lỗi máy chủ (500). Vui lòng thử lại sau hoặc liên hệ hỗ trợ.';
                                } else if (xhr.status === 404) {
                                    userMessage = 'Không tìm thấy trang thanh toán. Vui lòng thử lại.';
                                } else if (xhr.status === 403) {
                                    userMessage = 'Không có quyền thực hiện thao tác này.';
                                } else if (xhr.status === 400) {
                                    userMessage = errorMessage || 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.';
                                } else if (errorMessage && errorMessage !== 'Lỗi không xác định') {
                                    userMessage = errorMessage;
                                }
                                
                                alert('❌ ' + userMessage);
                                
                                // Nếu lỗi CSRF, thử refresh token và gửi lại
                                if (xhr.status === 403 && errorDetails && errorDetails.code === 'CSRF_TOKEN_INVALID') {
                                    if (window.CSRFHelper) {
                                        window.CSRFHelper.refreshToken().then(() => {
                                            alert('Phiên làm việc đã hết hạn. Vui lòng thử lại.');
                                            location.reload();
                                        });
                                    } else {
                                        alert('Lỗi xác thực. Vui lòng tải lại trang và thử lại.');
                                    }
                                } else {
                                    // Đã hiển thị alert ở trên với userMessage chi tiết
                                    // Không cần alert thêm ở đây
                                }
                                
                                // Re-enable button
                                $('#proceedPayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Xác nhận thanh toán');
                            }
                        });
                    } catch (error) {
                        console.error('Error getting CSRF token:', error);
                        alert('Lỗi xác thực. Vui lòng tải lại trang và thử lại.');
                        $('#proceedPayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Xác nhận thanh toán');
                    }
                };
                
                // Gọi hàm async
                sendPaymentRequest();
            }
        });
        
        // Hiển thị QR code và thông tin ngân hàng SePay
        function showSePayQRCode(paymentData) {
            console.log('showSePayQRCode called with:', paymentData);
            
            try {
                // Tạo HTML để hiển thị QR code và thông tin ngân hàng
                const qrCodeHtml = `
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="info-card text-center">
                            <h5 class="mb-4"><i class="fas fa-qrcode text-primary"></i> Quét mã QR để thanh toán</h5>
                            
                            <div class="mb-4">
                                <!-- ✅ QR Code với design giống SePay template -->
                                <div id="qrcodeContainer" class="sepay-qr-wrapper">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Đang tạo QR code...</span>
                            </div>
                                </div>
                                <p class="text-muted small mt-2" id="qrStatusMessage">
                                    <i class="fas fa-spinner fa-spin"></i> Đang tạo QR code...
                                </p>
                                ${paymentData.sepay_qr_id ? 
                                    '<div class="alert alert-success alert-sm mt-2"><i class="fas fa-check-circle"></i> QR Code từ SePay - Đảm bảo webhook hoạt động</div>' : 
                                    '<div class="alert alert-warning alert-sm mt-2"><i class="fas fa-info-circle"></i> QR Code từ VietQR - SePay webhook vẫn hoạt động dựa trên nội dung chuyển khoản</div>'
                                }
                                        </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6 mb-3">
                                    <div class="info-card">
                                        <h6 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Thông tin thanh toán</h6>
                                        <table class="table table-sm table-borderless text-start">
                                            <tr><td class="text-muted" style="width: 40%;">Sự kiện:</td><td><strong>${paymentData.event_name || 'N/A'}</strong></td></tr>
                                            <tr><td class="text-muted">Số tiền:</td><td><strong class="text-primary">${new Intl.NumberFormat('vi-VN').format(paymentData.amount)} VNĐ</strong></td></tr>
                                            <tr><td class="text-muted">Mã giao dịch:</td><td><code>${paymentData.transaction_code || paymentData.transaction_id || 'N/A'}</code></td></tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="info-card">
                                        <h6 class="text-primary mb-3"><i class="fas fa-university"></i> Thông tin ngân hàng</h6>
                                        <table class="table table-sm table-borderless text-start">
                                            <tr><td class="text-muted" style="width: 40%;">Ngân hàng:</td><td><strong>${paymentData.bank_info?.bank_name || 'N/A'}</strong></td></tr>
                                            <tr><td class="text-muted">Số tài khoản:</td><td><code>${paymentData.bank_info?.account_number || 'N/A'}</code></td></tr>
                                            <tr><td class="text-muted">Chủ tài khoản:</td><td>${paymentData.bank_info?.account_name || 'N/A'}</td></tr>
                                            <tr><td class="text-muted">Nội dung:</td><td><code class="text-break">${paymentData.bank_info?.content || 'N/A'}</code></td></tr>
                                        </table>
                            </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-center gap-2 mt-4">
                                <button class="btn btn-secondary" onclick="window.location.href='../events/my-events.php'">
                                    <i class="fas fa-arrow-left"></i> Quay lại
                                </button>
                                <button class="btn btn-success" onclick="verifyPaymentStatus(${paymentData.payment_id}, '${paymentData.transaction_code || paymentData.transaction_id}')">
                                    <i class="fas fa-check-circle"></i> Xác nhận thanh toán
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
                // Thay thế toàn bộ nội dung trong payment-content
                const $paymentContent = $('.payment-content');
                if ($paymentContent.length === 0) {
                    console.error('Không tìm thấy .payment-content');
                    alert('Lỗi: Không thể hiển thị QR code. Vui lòng thử lại.');
                    return;
                }
                
                $paymentContent.html(qrCodeHtml);
                
                // Tạo QR Code sau khi HTML đã được thêm
                setTimeout(function() {
                    generateQRCodeWithFallback(paymentData);
                }, 100);
            } catch (error) {
                console.error('Lỗi khi hiển thị QR code:', error);
                alert('Lỗi khi hiển thị QR code. Vui lòng thử lại.');
            }
        }
        
        // Tạo QR Code với fallback
        function generateQRCodeWithFallback(paymentData) {
            try {
                // ✅ QUAN TRỌNG: Kiểm tra xem QR code có từ SePay không
                const isSePayQR = paymentData.sepay_qr_id !== null && paymentData.sepay_qr_id !== undefined;
                let qrUrl = paymentData.qr_code || paymentData.qr_string || '';
                const fallbackQr = paymentData.fallback_qr || '';
                
                // ✅ Nếu có SePay QR ID, ưu tiên sử dụng QR từ SePay
                if (isSePayQR && qrUrl) {
                    console.log('Using SePay QR Code (ID: ' + paymentData.sepay_qr_id + ')');
                    // Cập nhật thông báo
                    $('.text-muted.small').html('<i class="fas fa-check-circle text-success"></i> QR Code từ SePay - Đã sẵn sàng để quét');
                } else if (qrUrl && qrUrl.includes('vietqr.io')) {
                    console.log('Using VietQR fallback (SePay API failed)');
                    // Cập nhật thông báo
                    $('.text-muted.small').html('<i class="fas fa-info-circle text-warning"></i> QR Code từ VietQR - SePay webhook vẫn hoạt động dựa trên nội dung chuyển khoản');
                }
                
                if (!qrUrl && paymentData.bank_info) {
                    // Tạo QR từ thông tin ngân hàng
                    const qrData = `Bank: ${paymentData.bank_info.bank_name}\nAccount: ${paymentData.bank_info.account_number}\nAmount: ${paymentData.amount}\nContent: ${paymentData.bank_info.content}`;
                    qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrData)}`;
                }
                
                // Danh sách dịch vụ QR để thử (ưu tiên SePay QR nếu có)
                const qrServices = [];
                
                // ✅ Ưu tiên 1: QR code từ SePay (nếu có)
                if (isSePayQR && qrUrl && !qrUrl.includes('vietqr.io')) {
                    qrServices.push(qrUrl);
                }
                
                // ✅ Ưu tiên 2: VietQR URL (nếu có) với proxy
                if (qrUrl && qrUrl.includes('vietqr.io')) {
                    const withoutProtocol = qrUrl.replace(/^https?:\/\//, '');
                    qrServices.push(`https://images.weserv.nl/?url=${encodeURIComponent(withoutProtocol)}`);
                    qrServices.push(`https://wsrv.nl/?url=${encodeURIComponent(withoutProtocol)}`);
                    qrServices.push(qrUrl); // Thêm URL gốc
                } else if (qrUrl) {
                    // QR URL khác (không phải VietQR)
                    qrServices.push(qrUrl);
                }
                
                // ✅ Ưu tiên 3: QR dự phòng
                if (fallbackQr) {
                    qrServices.push(fallbackQr);
                }
                
                // ✅ Ưu tiên 4: Tạo QR từ thông tin ngân hàng
                if (paymentData.bank_info) {
                    const qrData = `Bank: ${paymentData.bank_info.bank_name}\nAccount: ${paymentData.bank_info.account_number}\nAmount: ${paymentData.amount}\nContent: ${paymentData.bank_info.content}`;
                    qrServices.push(`https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=${encodeURIComponent(qrData)}`);
                    qrServices.push(`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrData)}&format=png`);
                }
                
                // Thử từng dịch vụ
                let currentService = 0;
                const qrContainer = $('#qrcodeContainer');
                
                if (qrContainer.length === 0) {
                    console.error('Không tìm thấy #qrcodeContainer');
                    return;
                }
                
                function tryNextService() {
                    if (currentService >= qrServices.length) {
                        // Tất cả services đều fail, hiển thị thông tin thủ công
                        qrContainer.html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h6>QR Code không khả dụng</h6>
                                <p class="mb-0">Vui lòng chuyển khoản theo thông tin ngân hàng bên dưới</p>
                            </div>
                        `);
                        return;
                    }
                    
                    const img = new Image();
                    img.onload = function() {
                        // ✅ Cập nhật thông báo trạng thái
                        const isSePayQR = paymentData.sepay_qr_id !== null && paymentData.sepay_qr_id !== undefined;
                        const qrSource = isSePayQR ? 'SePay' : (qrServices[currentService].includes('vietqr.io') ? 'VietQR' : 'Fallback');
                        
                        // ✅ Hiển thị QR code với design giống SePay template
                        qrContainer.html(`
                            <div class="sepay-qr-header">
                                <div class="sepay-logo">
                                    <div class="sepay-logo-icon">SP</div>
                                    <span>SePay</span>
                                </div>
                            </div>
                            <div class="sepay-qr-code">
                                <img src="${qrServices[currentService]}" alt="QR Code" style="max-width: 280px; max-height: 280px;">
                            </div>
                            <div class="sepay-qr-footer">
                                <div class="sepay-partner">
                                    <div class="sepay-partner-name">napas</div>
                                    <div class="sepay-partner-name" style="font-size: 14px; font-weight: 700;">247</div>
                                </div>
                                <div class="sepay-divider"></div>
                                <div class="sepay-partner">
                                    <div class="sepay-partner-logo vietinbank-logo">V</div>
                                    <div class="sepay-partner-name">VietinBank</div>
                                </div>
                                <div class="sepay-divider"></div>
                                <div class="sepay-partner">
                                    <div class="sepay-partner-name" style="color: #dc2626; font-weight: 700;">VIETQR</div>
                                    <div style="font-size: 10px; color: #dc2626;">™</div>
                                </div>
                            </div>
                            ${isSePayQR ? '<div class="text-center mt-2"><span class="badge bg-success">QR từ SePay</span></div>' : ''}
                        `);
                        
                        // Cập nhật thông báo
                        $('#qrStatusMessage').html(`
                            <i class="fas fa-check-circle text-success"></i> 
                            QR Code đã sẵn sàng (${qrSource})
                        `);
                    };
                    img.onerror = function() {
                        currentService++;
                        tryNextService();
                    };
                    img.src = qrServices[currentService];
                }
                
                tryNextService();
            } catch (error) {
                console.error('Lỗi khi tạo QR code:', error);
                const qrContainer = $('#qrcodeContainer');
                if (qrContainer.length > 0) {
                    qrContainer.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h6>Lỗi khi tạo QR Code</h6>
                            <p class="mb-0">Vui lòng chuyển khoản theo thông tin ngân hàng bên dưới</p>
                        </div>
                    `);
                }
            }
        }
        
        // Xác minh trạng thái thanh toán - tự động kiểm tra trạng thái khi xác nhận
        function verifyPaymentStatus(paymentId, transactionCode) {
            if (!confirm('Bạn đã hoàn tất chuyển khoản? Hệ thống sẽ kiểm tra và xác nhận thanh toán.')) {
                return;
            }
            
            // Hiển thị loading
            const verifyBtn = event.target;
            const originalText = verifyBtn.innerHTML;
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';
            
            // Gọi API xác minh thanh toán (tự động kiểm tra trạng thái)
            $.ajax({
                url: '../src/controllers/payment.php',
                method: 'POST',
                data: {
                    action: 'verify_payment',
                    payment_id: paymentId,
                    transaction_code: transactionCode
                },
                dataType: 'json',
                success: function(response) {
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = originalText;
                    
                    if (response.success) {
                        if (response.is_success) {
                            // ✅ Thanh toán thành công → Redirect đến success.php
                            const successUrl = `success.php?payment_id=${paymentId}&amount=${response.amount}&order_id=${response.transaction_code}`;
                            window.location.href = successUrl;
                        } else if (response.is_pending) {
                            // ⏳ Đang xử lý → Bắt đầu auto-polling
                            startAutoPolling(paymentId, transactionCode);
                            alert('⏳ ' + response.message + '\n\nHệ thống đang tự động kiểm tra. Vui lòng đợi...');
                        } else {
                            // ❌ Thất bại → Redirect đến failure.php
                            const failureUrl = `failure.php?payment_id=${paymentId}&order_id=${response.transaction_code}&message=${encodeURIComponent(response.message)}`;
                            window.location.href = failureUrl;
                        }
                    } else {
                        alert('❌ Lỗi: ' + (response.error || 'Không thể xác nhận thanh toán'));
                    }
                },
                error: function() {
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = originalText;
                    alert('❌ Lỗi kết nối khi xác nhận thanh toán. Vui lòng thử lại sau.');
                }
            });
        }
        
        // Tự động polling để kiểm tra trạng thái thanh toán sau khi người dùng chuyển khoản
        let pollingInterval = null;
        let pollingAttempts = 0;
        const MAX_POLLING_ATTEMPTS = 60; // Tối đa 60 lần (5 phút nếu mỗi 5 giây)
        
        function startAutoPolling(paymentId, transactionCode) {
            // Dừng polling cũ nếu có
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
            
            pollingAttempts = 0;
            
            // Hiển thị thông báo đang kiểm tra
            const statusDiv = $('.payment-status-message');
            if (statusDiv.length === 0) {
                $('.payment-content').prepend(`
                    <div class="alert alert-info payment-status-message">
                        <i class="fas fa-spinner fa-spin"></i> 
                        Đang tự động kiểm tra trạng thái thanh toán...
                        <div class="mt-2">
                            <small>Hệ thống sẽ tự động cập nhật khi nhận được webhook từ SePay.</small>
                        </div>
                    </div>
                `);
            }
            
            // Bắt đầu polling mỗi 5 giây
            pollingInterval = setInterval(function() {
                pollingAttempts++;
                
                if (pollingAttempts > MAX_POLLING_ATTEMPTS) {
                    // Dừng polling sau 5 phút
                    clearInterval(pollingInterval);
                    $('.payment-status-message').html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i> 
                            Đã kiểm tra trong 5 phút nhưng chưa nhận được xác nhận.
                            <div class="mt-2">
                                <small>Vui lòng nhấn nút "Xác nhận thanh toán" để kiểm tra lại hoặc liên hệ hỗ trợ.</small>
                            </div>
                        </div>
                    `);
                    return;
                }
                
                // Gọi API kiểm tra trạng thái thanh toán
                $.ajax({
                    url: '../src/controllers/payment.php',
                    method: 'POST',
                    data: {
                        action: 'verify_payment',
                        payment_id: paymentId,
                        transaction_code: transactionCode
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (response.is_success) {
                                // ✅ Thanh toán thành công → Dừng polling và redirect
                                clearInterval(pollingInterval);
                                const successUrl = `success.php?payment_id=${paymentId}&amount=${response.amount}&order_id=${response.transaction_code}`;
                                window.location.href = successUrl;
                            } else if (!response.is_pending) {
                                // ❌ Thất bại → Dừng polling và redirect
                                clearInterval(pollingInterval);
                                const failureUrl = `failure.php?payment_id=${paymentId}&order_id=${response.transaction_code}&message=${encodeURIComponent(response.message)}`;
                                window.location.href = failureUrl;
                            }
                            // Nếu vẫn pending, tiếp tục polling
                        }
                    },
                    error: function() {
                        // Lỗi kết nối, tiếp tục thử
                        console.error('Polling error, retrying...');
                    }
                });
            }, 5000); // Mỗi 5 giây
        }
        
        // Dừng polling khi người dùng rời trang
        $(window).on('beforeunload', function() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        });
        
        // Load saved discount code from localStorage
        function loadSavedDiscountCode(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            try {
                const savedCodes = localStorage.getItem('savedDiscountCodes');
                if (savedCodes) {
                    const codes = JSON.parse(savedCodes);
                    if (codes && codes.length > 0) {
                        if (codes.length === 1) {
                            const savedCode = codes[0];
                            $('#discountCode').val(savedCode);
                            
                            const messageDiv = $('#discountCodeMessage');
                            messageDiv.html(`
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i> 
                                    Đã tải mã giảm giá đã lưu: <strong>${savedCode}</strong>. 
                                    Nhấn "Áp dụng" để sử dụng.
                                </div>
                            `).show();
                        } else {
                            showSavedCodesSelection(codes);
                        }
                    } else {
                        const messageDiv = $('#discountCodeMessage');
                        messageDiv.html(`
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Chưa có mã giảm giá nào được lưu. Vui lòng lưu mã trên trang chủ.
                            </div>
                        `).show();
                    }
                } else {
                    const messageDiv = $('#discountCodeMessage');
                    messageDiv.html(`
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Chưa có mã giảm giá nào được lưu. Vui lòng lưu mã trên trang chủ.
                        </div>
                    `).show();
                }
            } catch (e) {
                console.error('Error loading saved discount code:', e);
                const messageDiv = $('#discountCodeMessage');
                messageDiv.html(`
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-times-circle"></i> 
                        Lỗi khi tải mã giảm giá đã lưu.
                    </div>
                `).show();
            }
            
            return false;
        }
        
        // Show saved codes selection (if multiple codes saved)
        function showSavedCodesSelection(codes) {
            const messageDiv = $('#discountCodeMessage');
            let html = '<div class="alert alert-info mb-2"><i class="fas fa-info-circle"></i> Bạn có nhiều mã đã lưu. Chọn mã để sử dụng:</div>';
            html += '<div class="d-flex flex-wrap gap-2 mb-2">';
            codes.forEach(code => {
                const escapedCode = code.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                html += `
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="event.preventDefault(); event.stopPropagation(); $('#discountCode').val('${escapedCode}'); $('#discountCodeMessage').hide(); return false;">
                        ${code}
                    </button>
                `;
            });
            html += '</div>';
            messageDiv.html(html).show();
        }
        
        // Disable discount code section (for cash payment)
        function disableDiscountCode() {
            $('#discountCode').prop('disabled', true);
            $('#loadSavedCodeBtn').prop('disabled', true);
            $('#applyDiscountBtn').prop('disabled', true);
            // Ẩn thông báo, chỉ hiển thị khi người dùng cố gắng áp dụng mã
            $('#discountCodeMessage').hide();
            appliedDiscountCode = null;
        }
        
        // Enable discount code section (chỉ cho thanh toán đủ 1 lần)
        function enableDiscountCode() {
            // Chỉ enable khi thanh toán đủ (full), không cho đặt cọc và thanh toán phần còn lại
            if (selectedPaymentType === 'full') {
                $('#discountCode').prop('disabled', false);
                $('#loadSavedCodeBtn').prop('disabled', false);
                $('#applyDiscountBtn').prop('disabled', false);
                // Ẩn thông báo lỗi nếu có
                if ($('#discountCodeMessage').hasClass('alert-warning') || $('#discountCodeMessage').hasClass('alert-danger')) {
                    $('#discountCodeMessage').hide();
                }
            } else {
                // Đặt cọc hoặc thanh toán phần còn lại không cho phép mã giảm giá
                disableDiscountCodeForNonFull();
            }
        }
        
        // Disable discount code section (for deposit or remaining payment)
        function disableDiscountCodeForNonFull() {
            $('#discountCode').prop('disabled', true);
            $('#loadSavedCodeBtn').prop('disabled', true);
            $('#applyDiscountBtn').prop('disabled', true);
            // Ẩn thông báo, chỉ hiển thị khi người dùng cố gắng áp dụng mã
            $('#discountCodeMessage').hide();
            appliedDiscountCode = null;
        }
        
        // Cập nhật trạng thái mã giảm giá dựa trên phương thức và loại thanh toán
        function updateDiscountCodeAvailability() {
            // Tiền mặt không cho phép mã giảm giá
            if (selectedPaymentMethod === 'cash') {
                disableDiscountCode();
            } 
            // Đặt cọc hoặc thanh toán phần còn lại không cho phép mã giảm giá
            else if (selectedPaymentType === 'deposit' || selectedPaymentType === 'remaining') {
                disableDiscountCodeForNonFull();
            }
            // SePay + thanh toán đủ 1 lần mới cho phép mã giảm giá
            else if (selectedPaymentMethod === 'sepay' && selectedPaymentType === 'full') {
                enableDiscountCode();
            }
        }
        
        // Apply discount code
        function applyDiscountCode(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Không cho phép áp dụng mã giảm giá khi thanh toán tiền mặt
            if (selectedPaymentMethod === 'cash') {
                const messageDiv = $('#discountCodeMessage');
                messageDiv.html('<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> Mã giảm giá không áp dụng cho thanh toán tiền mặt.</div>').show();
                appliedDiscountCode = null;
                updateAmountDisplay();
                return false;
            }
            
            // Không cho phép áp dụng mã giảm giá khi đặt cọc hoặc thanh toán phần còn lại
            if (selectedPaymentType === 'deposit' || selectedPaymentType === 'remaining') {
                const messageDiv = $('#discountCodeMessage');
                let message = '';
                if (selectedPaymentType === 'deposit') {
                    message = '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> Mã giảm giá chỉ áp dụng khi thanh toán đủ 1 lần, không áp dụng cho đặt cọc.</div>';
                } else {
                    message = '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> Mã giảm giá chỉ áp dụng khi thanh toán đủ 1 lần, không áp dụng cho thanh toán phần còn lại.</div>';
                }
                messageDiv.html(message).show();
                appliedDiscountCode = null;
                updateAmountDisplay();
                return false;
            }
            
            // Chỉ cho phép áp dụng mã giảm giá khi thanh toán đủ
            if (selectedPaymentType !== 'full') {
                const messageDiv = $('#discountCodeMessage');
                messageDiv.html('<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> Mã giảm giá chỉ áp dụng khi thanh toán đủ 1 lần.</div>').show();
                appliedDiscountCode = null;
                updateAmountDisplay();
                return false;
            }
            
            const code = $('#discountCode').val().trim();
            const messageDiv = $('#discountCodeMessage');
            
            if (!code) {
                messageDiv.html('<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle"></i> Vui lòng nhập mã giảm giá</div>').show();
                appliedDiscountCode = null;
                updateAmountDisplay();
                return false;
            }
            
            // Get current payment amount (base amount before discount)
            let baseAmount;
            if (selectedPaymentType === 'remaining' || hasDeposit) {
                baseAmount = remainingAmount;
            } else {
                baseAmount = totalAmount;
            }
            
            $('#applyDiscountBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...');
            
            $.ajax({
                url: '../src/controllers/magiamgia-controller.php',
                method: 'GET',
                data: {
                    action: 'validate_code',
                    code: code,
                    user_id: userId,
                    total_amount: baseAmount
                },
                dataType: 'json',
                success: function(response) {
                    $('#applyDiscountBtn').prop('disabled', false).html('<i class="fas fa-check"></i> Áp dụng');
                    
                    if (response.success) {
                        appliedDiscountCode = response;
                        messageDiv.html(`<div class="alert alert-success mb-0"><i class="fas fa-check-circle"></i> ${response.message || 'Mã giảm giá hợp lệ!'}</div>`).show();
                        updateAmountDisplay();
                    } else {
                        appliedDiscountCode = null;
                        messageDiv.html(`<div class="alert alert-danger mb-0"><i class="fas fa-times-circle"></i> ${response.error || 'Mã giảm giá không hợp lệ'}</div>`).show();
                        updateAmountDisplay();
                    }
                },
                error: function() {
                    $('#applyDiscountBtn').prop('disabled', false).html('<i class="fas fa-check"></i> Áp dụng');
                    appliedDiscountCode = null;
                    messageDiv.html('<div class="alert alert-danger mb-0"><i class="fas fa-times-circle"></i> Lỗi kết nối. Vui lòng thử lại.</div>').show();
                    updateAmountDisplay();
                }
            });
            
            return false;
        }
        
        // Allow Enter key to apply discount code
        $('#discountCode').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                applyDiscountCode();
            }
        });
        
        // Load saved discount code on page load
        $(document).ready(function() {
            // Load saved discount code if available
            try {
                const savedCodes = localStorage.getItem('savedDiscountCodes');
                if (savedCodes) {
                    const codes = JSON.parse(savedCodes);
                    if (codes && codes.length === 1) {
                        $('#discountCode').val(codes[0]);
                    }
                }
            } catch (e) {
                console.error('Error loading saved discount code on page load:', e);
            }
        });
    </script>
</body>
</html>
