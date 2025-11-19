<?php
/**
 * Debug Webhook - Kiểm tra webhook có được gửi từ SePay không
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/sepay.php';

header('Content-Type: text/html; charset=utf-8');

// Đọc log file
$logFile = __DIR__ . '/hook_log.txt';
$logs = '';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    // Chỉ lấy 10000 ký tự cuối
    if (strlen($logs) > 10000) {
        $logs = '...' . substr($logs, -10000);
    }
}

// Đếm số POST requests
$postCount = substr_count($logs, 'Request Method: POST');
$getCount = substr_count($logs, 'Request Method: GET');

// Đếm số webhook thành công
$successCount = substr_count($logs, 'Token verified successfully');
$errorCount = substr_count($logs, 'No authentication token provided');

// Lấy payments đang chờ
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT t.*, dl.TenSuKien, dl.NgayBatDau,
               kh.HoTen as KhachHangTen
        FROM thanhtoan t
        LEFT JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
        LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE t.PhuongThuc = 'Chuyển khoản'
        AND t.TrangThai IN ('Đang xử lý', 'Chờ xác nhận')
        ORDER BY t.ID_ThanhToan DESC
        LIMIT 10
    ");
    $stmt->execute();
    $pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pendingPayments = [];
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Webhook SePay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
        }
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
        }
        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .stat-success { background: #28a745; color: white; }
        .stat-warning { background: #ffc107; color: black; }
        .stat-danger { background: #dc3545; color: white; }
        .stat-info { background: #17a2b8; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mb-4">
            <i class="fas fa-bug"></i> Debug Webhook SePay
        </h1>
        
        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card stat-info">
                    <h3><?php echo $postCount; ?></h3>
                    <p>POST Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-warning">
                    <h3><?php echo $getCount; ?></h3>
                    <p>GET Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-success">
                    <h3><?php echo $successCount; ?></h3>
                    <p>Token Verified</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-danger">
                    <h3><?php echo $errorCount; ?></h3>
                    <p>No Token</p>
                </div>
            </div>
        </div>
        
        <!-- Cấu hình Webhook -->
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-cog"></i> Cấu hình Webhook</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th>Webhook URL:</th>
                        <td><code><?php echo SEPAY_CALLBACK_URL; ?></code></td>
                    </tr>
                    <tr>
                        <th>API Key:</th>
                        <td><code><?php echo substr(SEPAY_WEBHOOK_TOKEN, 0, 20); ?>...</code></td>
                    </tr>
                    <tr>
                        <th>Match Pattern:</th>
                        <td><code><?php echo SEPAY_MATCH_PATTERN; ?></code></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Payments đang chờ -->
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-clock"></i> Payments đang chờ webhook
                    <span class="badge bg-dark"><?php echo count($pendingPayments); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($pendingPayments)): ?>
                    <p class="text-muted">Không có payment nào đang chờ.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Sự kiện</th>
                                    <th>Khách hàng</th>
                                    <th>Số tiền</th>
                                    <th>Loại</th>
                                    <th>Ngày tạo</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingPayments as $p): ?>
                                    <tr>
                                        <td><?php echo $p['ID_ThanhToan']; ?></td>
                                        <td><?php echo htmlspecialchars($p['TenSuKien'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($p['KhachHangTen'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format($p['SoTien']); ?> ₫</td>
                                        <td><?php echo htmlspecialchars($p['LoaiThanhToan']); ?></td>
                                        <td><?php echo $p['NgayTao'] ?? 'N/A'; ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(substr($p['GhiChu'] ?? '', 0, 100)); ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Hướng dẫn cấu hình SePay -->
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-check-circle"></i> Cấu hình SePay - Đã hoàn tất
                </h5>
            </div>
            <div class="card-body">
                <h6>1. ✅ Cấu hình "Cấu trúc mã thanh toán" trong SePay:</h6>
                <div class="alert alert-success">
                    <strong>Đã cấu hình:</strong>
                    <ul class="mb-0">
                        <li><strong>Prefix:</strong> <code>SEPAY</code> ✅</li>
                        <li><strong>Suffix:</strong> Số nguyên, từ 3 đến 10 ký tự ✅</li>
                        <li><strong>Ví dụ:</strong> <code>SEPAY11111111</code> hoặc <code>SEPAY20123</code> ✅</li>
                        <li><strong>Trạng thái:</strong> Đang hoạt động ✅</li>
                    </ul>
                </div>
                
                <h6>2. ✅ Cấu hình Webhook:</h6>
                <div class="alert alert-success">
                    <ul class="mb-0">
                        <li><strong>URL:</strong> <code><?php echo SEPAY_CALLBACK_URL; ?></code> ✅</li>
                        <li><strong>Authentication:</strong> API Key ✅</li>
                        <li><strong>API Key:</strong> <code><?php echo substr(SEPAY_WEBHOOK_TOKEN, 0, 20); ?>...</code> ✅</li>
                        <li><strong>Event:</strong> "Có tiền vào" ✅</li>
                        <li><strong>Bank Account:</strong> VietinBank - 100872918542 ✅</li>
                        <li><strong>Status:</strong> Kích hoạt ✅</li>
                    </ul>
                </div>
                
                <h6>3. ⚠️ Lưu ý quan trọng:</h6>
                <div class="alert alert-info">
                    <ul class="mb-0">
                        <li><strong>Webhook chỉ chấp nhận POST requests:</strong> GET requests sẽ bị từ chối (đây là hành vi đúng)</li>
                        <li><strong>SePay sẽ gửi webhook tự động:</strong> Khi có giao dịch chuyển khoản với content khớp pattern <code>SEPAY{suffix}</code></li>
                        <li><strong>Content format:</strong> Khi tạo payment, hệ thống sẽ tạo content dạng <code>SEPAY{eventId}{paymentId}</code> (ví dụ: <code>SEPAY2224</code>)</li>
                        <li><strong>Webhook sẽ tự động:</strong> Tìm payment bằng content, sau đó cập nhật trạng thái thành "Thành công"</li>
                    </ul>
                </div>
                
                <h6>4. 🧪 Cách test webhook:</h6>
                <div class="alert alert-warning">
                    <ul class="mb-0">
                        <li><strong>Test GET endpoint:</strong> <a href="sepay-payment.php?test=1" target="_blank">sepay-payment.php?test=1</a></li>
                        <li><strong>Test thật:</strong> Tạo payment mới và chuyển khoản với content <code>SEPAY{suffix}</code></li>
                        <li><strong>Kiểm tra logs:</strong> Xem phần "Webhook Logs" bên dưới để xem có webhook nào được gửi không</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Webhook Logs -->
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt"></i> Webhook Logs
                    <?php if (file_exists($logFile)): ?>
                        <span class="badge bg-light text-dark">
                            <?php echo number_format(filesize($logFile)); ?> bytes
                        </span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p class="text-muted">Chưa có log webhook.</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Chưa có webhook nào được gửi từ SePay.</strong>
                        <ul class="mb-0 mt-2">
                            <li>Webhook sẽ được gửi tự động khi có giao dịch chuyển khoản với content khớp pattern <code>SEPAY{suffix}</code></li>
                            <li>Đảm bảo bạn đã tạo payment và chuyển khoản với content đúng format</li>
                            <li>Kiểm tra trong SePay Dashboard xem có giao dịch nào đã được gửi webhook không</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="code-block"><?php echo htmlspecialchars($logs); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card">
            <div class="card-body">
                <h5>Thao tác</h5>
                <div class="btn-group" role="group">
                    <a href="?refresh=1" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Làm mới
                    </a>
                    <a href="sepay-payment.php?test=1" target="_blank" class="btn btn-success">
                        <i class="fas fa-vial"></i> Test GET Endpoint
                    </a>
                    <a href="../events/my-events.php" class="btn btn-info">
                        <i class="fas fa-calendar"></i> Xem Sự kiện
                    </a>
                    <a href="../admin/payment-management.php" class="btn btn-warning">
                        <i class="fas fa-credit-card"></i> Quản lý Thanh toán
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh mỗi 30 giây
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>

