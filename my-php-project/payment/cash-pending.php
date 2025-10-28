<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

$pdo = getDBConnection();
$paymentId = $_GET['payment_id'] ?? null;

if (!$paymentId) {
    header('Location: ../events/my-events.php');
    exit();
}

// Get payment details
$stmt = $pdo->prepare("
    SELECT t.*, dl.TenSuKien, dl.NgayBatDau, dl.NgayKetThuc,
           kh.HoTen as KhachHangTen, kh.SoDienThoai
    FROM thanhtoan t
    JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
    JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
    WHERE t.ID_ThanhToan = ? AND kh.ID_User = ?
");
$stmt->execute([$paymentId, $_SESSION['user']['ID_User']]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    header('Location: ../events/my-events.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chờ xác nhận thanh toán tiền mặt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .pending-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        .pending-icon {
            font-size: 80px;
            color: #ffc107;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .company-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .steps {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .step-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .step-number {
            background: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            color: white;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            transition: transform 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            color: white;
        }
        .status-badge {
            font-size: 1.2rem;
            padding: 10px 20px;
            border-radius: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="pending-card">
            <div class="text-center">
                <div class="pending-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h2 class="mb-3">Chờ xác nhận thanh toán tiền mặt</h2>
                <p class="text-muted mb-4">Thanh toán của bạn đang chờ quản lý xác nhận</p>
                
                <div class="status-badge bg-warning text-dark mb-4">
                    <i class="fas fa-hourglass-half"></i> Đang chờ xác nhận
                </div>
            </div>

            <!-- Payment Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5><i class="fas fa-receipt"></i> Thông tin thanh toán</h5>
                    <table class="table table-sm">
                        <tr><td><strong>Mã giao dịch:</strong></td><td><?= htmlspecialchars($payment['MaGiaoDich']) ?></td></tr>
                        <tr><td><strong>Số tiền:</strong></td><td><?= number_format($payment['SoTien']) ?> VNĐ</td></tr>
                        <tr><td><strong>Loại:</strong></td><td><?= htmlspecialchars($payment['LoaiThanhToan']) ?></td></tr>
                        <tr><td><strong>Phương thức:</strong></td><td><?= htmlspecialchars($payment['PhuongThuc']) ?></td></tr>
                        <tr><td><strong>Trạng thái:</strong></td><td><span class="badge bg-warning"><?= htmlspecialchars($payment['TrangThai']) ?></span></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-calendar"></i> Thông tin sự kiện</h5>
                    <table class="table table-sm">
                        <tr><td><strong>Tên sự kiện:</strong></td><td><?= htmlspecialchars($payment['TenSuKien']) ?></td></tr>
                        <tr><td><strong>Ngày bắt đầu:</strong></td><td><?= date('d/m/Y H:i', strtotime($payment['NgayBatDau'])) ?></td></tr>
                        <tr><td><strong>Ngày kết thúc:</strong></td><td><?= date('d/m/Y H:i', strtotime($payment['NgayKetThuc'])) ?></td></tr>
                        <tr><td><strong>Ngày tạo:</strong></td><td><?= date('d/m/Y H:i', strtotime($payment['NgayThanhToan'])) ?></td></tr>
                    </table>
                </div>
            </div>

            <!-- Company Information -->
            <div class="company-info">
                <h5><i class="fas fa-building"></i> Thông tin công ty</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Tên công ty:</strong> CÔNG TY TNHH QUẢN LÝ SỰ KIỆN ABC</p>
                        <p><strong>Địa chỉ:</strong> 123 Đường Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh</p>
                        <p><strong>Số điện thoại:</strong> (028) 1234-5678</p>
                        <p><strong>Email:</strong> info@eventabc.com</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Giờ làm việc:</strong></p>
                        <p>Thứ 2 - Thứ 6: 8:00 - 17:00</p>
                        <p>Thứ 7: 8:00 - 12:00</p>
                        <p>Chủ nhật: Nghỉ</p>
                    </div>
                </div>
            </div>

            <!-- Steps -->
            <div class="steps">
                <h5><i class="fas fa-list-ol"></i> Các bước tiếp theo</h5>
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div>
                        <strong>Đến văn phòng công ty</strong><br>
                        <small class="text-muted">Mang theo CMND/CCCD và số tiền đúng theo hóa đơn</small>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div>
                        <strong>Thanh toán trực tiếp</strong><br>
                        <small class="text-muted">Nhân viên sẽ thu tiền và cấp biên lai thanh toán</small>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div>
                        <strong>Chờ xác nhận</strong><br>
                        <small class="text-muted">Quản lý sẽ xác nhận thanh toán trong vòng 24 giờ</small>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">4</div>
                    <div>
                        <strong>Nhận thông báo</strong><br>
                        <small class="text-muted">Bạn sẽ nhận được email/SMS xác nhận khi hoàn tất</small>
                    </div>
                </div>
            </div>

            <!-- Important Notes -->
            <div class="alert alert-warning">
                <h6><i class="fas fa-exclamation-triangle"></i> Lưu ý quan trọng</h6>
                <ul class="mb-0">
                    <li>Vui lòng đến văn phòng trong giờ làm việc</li>
                    <li>Mang theo CMND/CCCD để xác minh danh tính</li>
                    <li>Chuẩn bị đúng số tiền theo hóa đơn</li>
                    <li>Nhận biên lai thanh toán sau khi hoàn tất</li>
                    <li>Thanh toán sẽ được xác nhận trong vòng 24 giờ</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-4">
                <a href="../events/my-events.php" class="btn-custom">
                    <i class="fas fa-calendar-alt me-2"></i>Xem sự kiện của tôi
                </a>
                <a href="../" class="btn-custom">
                    <i class="fas fa-home me-2"></i>Về trang chủ
                </a>
                <button class="btn-custom" onclick="checkPaymentStatus()">
                    <i class="fas fa-sync-alt me-2"></i>Kiểm tra trạng thái
                </button>
            </div>

            <!-- Auto refresh info -->
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Trang này sẽ tự động cập nhật khi có thay đổi trạng thái
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkPaymentStatus() {
            // Show loading
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang kiểm tra...';
            button.disabled = true;

            // Check payment status
            fetch('../src/controllers/payment.php?action=get_payment_status&payment_id=<?= $paymentId ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.payment) {
                        const status = data.payment.TrangThai;
                        
                        if (status === 'Thành công') {
                            alert('Thanh toán đã được xác nhận thành công!');
                            window.location.href = 'success.php?payment_id=<?= $paymentId ?>';
                        } else if (status === 'Thất bại') {
                            alert('Thanh toán đã bị từ chối. Vui lòng liên hệ với chúng tôi.');
                            window.location.href = 'failure.php?payment_id=<?= $paymentId ?>';
                        } else {
                            alert('Thanh toán vẫn đang chờ xác nhận. Vui lòng kiểm tra lại sau.');
                        }
                    } else {
                        alert('Không thể kiểm tra trạng thái thanh toán. Vui lòng thử lại sau.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi kiểm tra trạng thái. Vui lòng thử lại sau.');
                })
                .finally(() => {
                    // Restore button
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }

        // Auto refresh every 30 seconds
        setInterval(() => {
            fetch('../src/controllers/payment.php?action=get_payment_status&payment_id=<?= $paymentId ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.payment) {
                        const status = data.payment.TrangThai;
                        
                        if (status === 'Thành công') {
                            window.location.href = 'success.php?payment_id=<?= $paymentId ?>';
                        } else if (status === 'Thất bại') {
                            window.location.href = 'failure.php?payment_id=<?= $paymentId ?>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Auto refresh error:', error);
                });
        }, 30000); // 30 seconds
    </script>
</body>
</html>
