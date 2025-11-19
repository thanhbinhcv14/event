<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'];
$pdo = getDBConnection();

// Get payment history for current user
$userId = $user['ID_User'];

// Kiểm tra xem bảng hoadon có tồn tại không
$tableExists = false;
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'hoadon'");
    $tableExists = $checkTable->rowCount() > 0;
} catch (Exception $e) {
    $tableExists = false;
}

// Query khác nhau tùy vào việc bảng hoadon có tồn tại hay không
if ($tableExists) {
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            dl.TenSuKien,
            dl.NgayBatDau,
            dl.NgayKetThuc,
            dl.ID_DatLich,
            d.TenDiaDiem,
            kh.HoTen,
            kh.SoDienThoai,
            kh.DiaChi,
            u.Email as UserEmail,
            -- Lấy thông tin hóa đơn nếu có
            h.HoTen as InvoiceHoTen,
            h.SoDienThoai as InvoiceSoDienThoai,
            h.Email as InvoiceEmail,
            h.DiaChi as InvoiceDiaChi
        FROM thanhtoan t
        INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
        INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        LEFT JOIN users u ON kh.ID_User = u.ID_User
        LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
        LEFT JOIN hoadon h ON t.ID_ThanhToan = h.ID_ThanhToan
        WHERE kh.ID_User = ?
        ORDER BY t.NgayThanhToan DESC
    ");
} else {
    // Nếu không có bảng hoadon, chỉ lấy thông tin từ khachhanginfo
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            dl.TenSuKien,
            dl.NgayBatDau,
            dl.NgayKetThuc,
            dl.ID_DatLich,
            d.TenDiaDiem,
            kh.HoTen,
            kh.SoDienThoai,
            kh.DiaChi,
            u.Email as UserEmail,
            -- Không có bảng hoadon, dùng thông tin từ khachhanginfo
            kh.HoTen as InvoiceHoTen,
            kh.SoDienThoai as InvoiceSoDienThoai,
            u.Email as InvoiceEmail,
            kh.DiaChi as InvoiceDiaChi
        FROM thanhtoan t
        INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
        INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        LEFT JOIN users u ON kh.ID_User = u.ID_User
        LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
        WHERE kh.ID_User = ?
        ORDER BY t.NgayThanhToan DESC
    ");
}

$stmt->execute([$userId]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử thanh toán - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header-section h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .content-section {
            padding: 2rem;
        }
        
        .payment-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .payment-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .payment-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-cancelled {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .payment-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .invoice-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .btn-view-details {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-view-details:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container">
        <div class="main-container">
            <div class="header-section">
                <h1><i class="fas fa-history"></i> Lịch sử thanh toán</h1>
                <p>Xem lại tất cả các giao dịch thanh toán của bạn</p>
            </div>
            
            <div class="content-section">
                <?php if (empty($payments)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h3 class="text-muted">Chưa có thanh toán nào</h3>
                        <p class="text-muted">Bạn chưa thực hiện thanh toán nào cho sự kiện.</p>
                        <a href="my-events.php" class="btn btn-primary">
                            <i class="fas fa-calendar"></i> Xem sự kiện của tôi
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($payments as $payment): ?>
                            <div class="col-md-12">
                                <div class="payment-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-2">
                                                <i class="fas fa-calendar-check text-primary"></i>
                                                <?= htmlspecialchars($payment['TenSuKien']) ?>
                                            </h5>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($payment['TenDiaDiem'] ?? 'Chưa xác định') ?>
                                            </p>
                                        </div>
                                        <div>
                                            <?php
                                            $statusClass = '';
                                            switch($payment['TrangThai']) {
                                                case 'Thành công':
                                                    $statusClass = 'status-success';
                                                    break;
                                                case 'Đang xử lý':
                                                    $statusClass = 'status-pending';
                                                    break;
                                                case 'Thất bại':
                                                    $statusClass = 'status-failed';
                                                    break;
                                                case 'Đã hủy':
                                                    $statusClass = 'status-cancelled';
                                                    break;
                                            }
                                            ?>
                                            <span class="payment-status <?= $statusClass ?>">
                                                <?= htmlspecialchars($payment['TrangThai']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="mb-2">
                                                <strong><i class="fas fa-money-bill-wave text-success"></i> Số tiền:</strong>
                                                <span class="payment-amount">
                                                    <?= number_format($payment['SoTien'], 0, ',', '.') ?> VNĐ
                                                </span>
                                            </p>
                                            <p class="mb-2">
                                                <strong><i class="fas fa-tag"></i> Loại thanh toán:</strong>
                                                <?= htmlspecialchars($payment['LoaiThanhToan']) ?>
                                            </p>
                                            <p class="mb-2">
                                                <strong><i class="fas fa-credit-card"></i> Phương thức:</strong>
                                                <?= htmlspecialchars($payment['PhuongThuc']) ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-2">
                                                <strong><i class="fas fa-calendar"></i> Ngày thanh toán:</strong>
                                                <?= date('d/m/Y H:i', strtotime($payment['NgayThanhToan'])) ?>
                                            </p>
                                            <p class="mb-2">
                                                <strong><i class="fas fa-calendar-alt"></i> Ngày sự kiện:</strong>
                                                <?= date('d/m/Y H:i', strtotime($payment['NgayBatDau'])) ?>
                                            </p>
                                            <?php if ($payment['MaGiaoDich']): ?>
                                                <p class="mb-2">
                                                    <strong><i class="fas fa-barcode"></i> Mã giao dịch:</strong>
                                                    <code><?= htmlspecialchars($payment['MaGiaoDich']) ?></code>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Thông tin hóa đơn -->
                                    <div class="invoice-info">
                                        <h6 class="mb-3">
                                            <i class="fas fa-file-invoice text-primary"></i> Thông tin hóa đơn
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2">
                                                    <strong>Họ tên:</strong>
                                                    <?= htmlspecialchars($payment['InvoiceHoTen'] ?? $payment['HoTen']) ?>
                                                </p>
                                                <p class="mb-2">
                                                    <strong>Số điện thoại:</strong>
                                                    <?= htmlspecialchars($payment['InvoiceSoDienThoai'] ?? $payment['SoDienThoai']) ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <?php if ($payment['InvoiceEmail'] ?? $payment['UserEmail']): ?>
                                                    <p class="mb-2">
                                                        <strong>Email:</strong>
                                                        <?= htmlspecialchars($payment['InvoiceEmail'] ?? $payment['UserEmail']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($payment['InvoiceDiaChi'] ?? $payment['DiaChi']): ?>
                                                    <p class="mb-2">
                                                        <strong>Địa chỉ:</strong>
                                                        <?= htmlspecialchars($payment['InvoiceDiaChi'] ?? $payment['DiaChi']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($payment['GhiChu']): ?>
                                        <div class="mt-3">
                                            <strong><i class="fas fa-sticky-note"></i> Ghi chú:</strong>
                                            <p class="text-muted mb-0"><?= htmlspecialchars($payment['GhiChu']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mt-3 text-end">
                                        <button class="btn btn-outline-primary btn-sm me-2" onclick="viewInvoice(<?= $payment['ID_ThanhToan'] ?>)">
                                            <i class="fas fa-file-invoice"></i> Xem hóa đơn
                                        </button>
                                        <a href="my-events.php?event_id=<?= $payment['ID_DatLich'] ?>" class="btn btn-view-details">
                                            <i class="fas fa-eye"></i> Xem chi tiết sự kiện
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal xem hóa đơn -->
    <div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoiceModalLabel">
                        <i class="fas fa-file-invoice text-primary"></i> Hóa đơn thanh toán
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="invoiceModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="printInvoice()">
                        <i class="fas fa-print"></i> In hóa đơn
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewInvoice(paymentId) {
            const modal = new bootstrap.Modal(document.getElementById('invoiceModal'));
            const modalBody = document.getElementById('invoiceModalBody');
            
            // Hiển thị loading
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải thông tin hóa đơn...</p>
                </div>
            `;
            
            modal.show();
            
            // Lấy thông tin hóa đơn
            fetch(`../src/controllers/payment.php?action=get_invoice&payment_id=${paymentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const invoice = data.invoice;
                        const payment = data.payment;
                        
                        modalBody.innerHTML = `
                            <div class="invoice-details">
                                <div class="text-center mb-4">
                                    <h4 class="text-primary">HÓA ĐƠN THANH TOÁN</h4>
                                    <p class="text-muted mb-0">Mã hóa đơn: #${invoice.ID_HoaDon || payment.ID_ThanhToan}</p>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Thông tin khách hàng</h6>
                                        <table class="table table-sm table-borderless">
                                            <tr><td class="text-muted" style="width: 40%;">Họ tên:</td><td><strong>${invoice.HoTen || payment.HoTen}</strong></td></tr>
                                            <tr><td class="text-muted">Số điện thoại:</td><td>${invoice.SoDienThoai || payment.SoDienThoai}</td></tr>
                                            <tr><td class="text-muted">Email:</td><td>${invoice.Email || payment.UserEmail || 'N/A'}</td></tr>
                                            <tr><td class="text-muted">Địa chỉ:</td><td>${invoice.DiaChi || payment.DiaChi || 'N/A'}</td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3">Thông tin thanh toán</h6>
                                        <table class="table table-sm table-borderless">
                                            <tr><td class="text-muted" style="width: 40%;">Sự kiện:</td><td><strong>${payment.TenSuKien}</strong></td></tr>
                                            <tr><td class="text-muted">Loại thanh toán:</td><td><span class="badge bg-${payment.LoaiThanhToan === 'Đặt cọc' ? 'warning' : 'success'}">${payment.LoaiThanhToan}</span></td></tr>
                                            <tr><td class="text-muted">Số tiền:</td><td><strong class="text-primary">${new Intl.NumberFormat('vi-VN').format(payment.SoTien)} VNĐ</strong></td></tr>
                                            <tr><td class="text-muted">Phương thức:</td><td>${payment.PhuongThuc}</td></tr>
                                            <tr><td class="text-muted">Ngày thanh toán:</td><td>${new Date(payment.NgayThanhToan).toLocaleString('vi-VN')}</td></tr>
                                            ${payment.MaGiaoDich ? `<tr><td class="text-muted">Mã giao dịch:</td><td><code>${payment.MaGiaoDich}</code></td></tr>` : ''}
                                            <tr><td class="text-muted">Trạng thái:</td><td><span class="badge bg-${payment.TrangThai === 'Thành công' ? 'success' : payment.TrangThai === 'Đang xử lý' ? 'warning' : 'danger'}">${payment.TrangThai}</span></td></tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Lưu ý:</strong> Đây là hóa đơn ${payment.LoaiThanhToan === 'Đặt cọc' ? 'đặt cọc' : 'thanh toán đủ'} cho sự kiện "${payment.TenSuKien}".
                                    ${payment.LoaiThanhToan === 'Đặt cọc' ? 'Bạn cần thanh toán đủ số tiền còn lại trước khi sự kiện diễn ra.' : ''}
                                </div>
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.error || 'Không thể tải thông tin hóa đơn'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Lỗi khi tải thông tin hóa đơn
                        </div>
                    `;
                });
        }
        
        function printInvoice() {
            const invoiceContent = document.getElementById('invoiceModalBody').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>In hóa đơn</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            @media print {
                                body { margin: 0; padding: 20px; }
                                .btn { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        ${invoiceContent}
                        <script>
                            window.onload = function() {
                                window.print();
                            }
                        <\/script>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>

