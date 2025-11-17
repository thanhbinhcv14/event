<?php
require_once 'includes/admin-header.php';

// Check if user has role 4 (Nhân viên)
if ($user['ID_Role'] != 4) {
    header('Location: index.php');
    exit;
}

// Get staff info
try {
    $pdo = getDBConnection();
    $userId = $_SESSION['user']['ID_User'];
    
    $stmt = $pdo->prepare("
        SELECT 
            nv.ID_NhanVien,
            nv.HoTen,
            nv.ChucVu,
            nv.SoDienThoai,
            u.Email
        FROM nhanvieninfo nv
        JOIN users u ON nv.ID_User = u.ID_User
        WHERE nv.ID_User = ?
    ");
    $stmt->execute([$userId]);
    $staffInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staffInfo) {
        header('Location: login.php');
        exit;
    }
    
    // Get statistics from both sources
    $stats = ['total_assignments' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0, 'issues' => 0];
    
    // From lichlamviec
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_assignments,
            SUM(CASE WHEN TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN TrangThai = 'Đang làm' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN TrangThai = 'Chưa làm' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN TrangThai = 'Báo sự cố' THEN 1 ELSE 0 END) as issues
        FROM lichlamviec 
        WHERE ID_NhanVien = ?
    ");
    $stmt->execute([$staffInfo['ID_NhanVien']]);
    $lichlamviecStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // From chitietkehoach
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_assignments,
            SUM(CASE WHEN TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN TrangThai = 'Đang thực hiện' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN TrangThai = 'Chưa bắt đầu' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN TrangThai = 'Báo sự cố' THEN 1 ELSE 0 END) as issues
        FROM chitietkehoach 
        WHERE ID_NhanVien = ?
    ");
    $stmt->execute([$staffInfo['ID_NhanVien']]);
    $chitietkehoachStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Combine statistics
    $stats['total_assignments'] = $lichlamviecStats['total_assignments'] + $chitietkehoachStats['total_assignments'];
    $stats['completed'] = $lichlamviecStats['completed'] + $chitietkehoachStats['completed'];
    $stats['in_progress'] = $lichlamviecStats['in_progress'] + $chitietkehoachStats['in_progress'];
    $stats['pending'] = $lichlamviecStats['pending'] + $chitietkehoachStats['pending'];
    $stats['issues'] = $lichlamviecStats['issues'] + $chitietkehoachStats['issues'];
    
    // Get monthly performance
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(NgayTao, '%Y-%m') as month,
            COUNT(*) as assignments,
            SUM(CASE WHEN TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed
        FROM lichlamviec 
        WHERE ID_NhanVien = ? 
        AND NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute([$staffInfo['ID_NhanVien']]);
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent reports
    $stmt = $pdo->prepare("
        SELECT 
            llv.ID_LLV,
            llv.NhiemVu,
            llv.TrangThai,
            llv.Tiendo,
            llv.GhiChu,
            llv.NgayTao,
            dl.TenSuKien
        FROM lichlamviec llv
        LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
        WHERE llv.ID_NhanVien = ?
        ORDER BY llv.NgayTao DESC
        LIMIT 10
    ");
    $stmt->execute([$staffInfo['ID_NhanVien']]);
    $recentReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // QUAN TRỌNG: Lấy dữ liệu thanh toán từ các sự kiện mà nhân viên tham gia
    // Lấy từ cả lichlamviec và chitietkehoach
    $paymentStats = [
        'total_payments' => 0,
        'total_amount' => 0,
        'by_status' => ['Đang xử lý' => 0, 'Thành công' => 0, 'Thất bại' => 0, 'Đã hủy' => 0],
        'by_method' => ['Chuyển khoản' => 0, 'Momo' => 0, 'ZaloPay' => 0, 'Visa/MasterCard' => 0, 'Tiền mặt' => 0],
        'by_type' => ['Đặt cọc' => 0, 'Thanh toán đủ' => 0, 'Hoàn tiền' => 0],
        'monthly_payments' => []
    ];
    
    // Lấy danh sách ID_DatLich từ các công việc của nhân viên
    $stmt = $pdo->prepare("
        SELECT DISTINCT ID_DatLich 
        FROM lichlamviec 
        WHERE ID_NhanVien = ? AND ID_DatLich IS NOT NULL
        UNION
        SELECT DISTINCT kht.ID_DatLich
        FROM chitietkehoach ctk
        LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
        WHERE ctk.ID_NhanVien = ? AND kht.ID_DatLich IS NOT NULL
    ");
    $stmt->execute([$staffInfo['ID_NhanVien'], $staffInfo['ID_NhanVien']]);
    $eventIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($eventIds)) {
        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        
        // Thống kê thanh toán theo trạng thái
        $stmt = $pdo->prepare("
            SELECT 
                TrangThai,
                COUNT(*) as count,
                SUM(SoTien) as total_amount
            FROM thanhtoan
            WHERE ID_DatLich IN ($placeholders)
            GROUP BY TrangThai
        ");
        $stmt->execute($eventIds);
        $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($statusStats as $stat) {
            $paymentStats['by_status'][$stat['TrangThai']] = (int)$stat['count'];
            $paymentStats['total_payments'] += (int)$stat['count'];
            $paymentStats['total_amount'] += (float)$stat['total_amount'];
        }
        
        // Thống kê thanh toán theo phương thức
        $stmt = $pdo->prepare("
            SELECT 
                PhuongThuc,
                COUNT(*) as count,
                SUM(SoTien) as total_amount
            FROM thanhtoan
            WHERE ID_DatLich IN ($placeholders)
            GROUP BY PhuongThuc
        ");
        $stmt->execute($eventIds);
        $methodStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($methodStats as $stat) {
            $paymentStats['by_method'][$stat['PhuongThuc']] = (int)$stat['count'];
        }
        
        // Thống kê thanh toán theo loại
        $stmt = $pdo->prepare("
            SELECT 
                LoaiThanhToan,
                COUNT(*) as count,
                SUM(SoTien) as total_amount
            FROM thanhtoan
            WHERE ID_DatLich IN ($placeholders) AND LoaiThanhToan != ''
            GROUP BY LoaiThanhToan
        ");
        $stmt->execute($eventIds);
        $typeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($typeStats as $stat) {
            $paymentStats['by_type'][$stat['LoaiThanhToan']] = (int)$stat['count'];
        }
        
        // Thống kê thanh toán theo tháng (12 tháng gần nhất)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(NgayThanhToan, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(SoTien) as total_amount
            FROM thanhtoan
            WHERE ID_DatLich IN ($placeholders)
            AND NgayThanhToan >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(NgayThanhToan, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute($eventIds);
        $paymentStats['monthly_payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $stats = ['total_assignments' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0, 'issues' => 0];
    $monthlyStats = [];
    $recentReports = [];
    $paymentStats = [
        'total_payments' => 0,
        'total_amount' => 0,
        'by_status' => ['Đang xử lý' => 0, 'Thành công' => 0, 'Thất bại' => 0, 'Đã hủy' => 0],
        'by_method' => ['Chuyển khoản' => 0, 'Momo' => 0, 'ZaloPay' => 0, 'Visa/MasterCard' => 0, 'Tiền mặt' => 0],
        'by_type' => ['Đặt cọc' => 0, 'Thanh toán đủ' => 0, 'Hoàn tiền' => 0],
        'monthly_payments' => []
    ];
    $staffInfo = ['HoTen' => 'Nhân viên', 'ChucVu' => 'Staff'];
    error_log("Error fetching staff reports: " . $e->getMessage());
    echo "<!-- Error: " . $e->getMessage() . " -->";
}
?>

<style>
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }
        
        .chart-card canvas {
            max-height: 400px !important;
            height: 400px !important;
        }
        
        
        .report-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            margin-bottom: 15px;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #e9ecef;
            color: #495057;
        }
        
        .status-in-progress {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-issue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
    </style>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?= $stats['total_assignments'] ?></div>
                <div class="stats-label">Tổng công việc</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number text-success"><?= $stats['completed'] ?></div>
                <div class="stats-label">Đã hoàn thành</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number text-warning"><?= $stats['in_progress'] ?></div>
                <div class="stats-label">Đang thực hiện</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number text-danger"><?= $stats['issues'] ?></div>
                <div class="stats-label">Báo sự cố</div>
            </div>
        </div>
    </div>
    
    <!-- Debug Info -->
    <?php if (isset($staffInfo['ID_NhanVien'])): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info">
                <small>Debug: Staff ID: <?= $staffInfo['ID_NhanVien'] ?> | Monthly Stats: <?= count($monthlyStats) ?> records</small>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card">
                <h5 class="mb-3">
                    <i class="fas fa-cogs text-warning"></i>
                    Chức năng báo cáo
                </h5>
                <div class="row">
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="loadDetailedStats()">
                            <i class="fas fa-chart-line"></i> Thống kê chi tiết
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100" onclick="showPerformanceReport()">
                            <i class="fas fa-file-alt"></i> Báo cáo hiệu suất
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info w-100" onclick="showWorkSummary()">
                            <i class="fas fa-calendar-week"></i> Tóm tắt công việc
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100" onclick="exportReport()">
                            <i class="fas fa-download"></i> Xuất báo cáo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Report Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card">
                <h5 class="mb-3">
                    <i class="fas fa-paper-plane text-primary"></i>
                    Gửi báo cáo tiến độ cho quản lý
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <button class="btn btn-primary w-100 mb-3" onclick="showProgressReportModal()">
                            <i class="fas fa-plus"></i> Gửi báo cáo tiến độ
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-info w-100 mb-3" onclick="showProgressReports()">
                            <i class="fas fa-history"></i> Lịch sử báo cáo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Stats Modal -->
    <div class="modal fade" id="detailedStatsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thống kê chi tiết</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailedStatsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Report Modal -->
    <div class="modal fade" id="performanceReportModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Báo cáo hiệu suất</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Từ ngày:</label>
                            <input type="date" class="form-control" id="startDate" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Đến ngày:</label>
                            <input type="date" class="form-control" id="endDate" value="<?= date('Y-m-t') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-primary d-block w-100" onclick="generatePerformanceReport()">
                                <i class="fas fa-search"></i> Tạo báo cáo
                            </button>
                        </div>
                    </div>
                    <div id="performanceReportContent">
                        <div class="text-center text-muted">
                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                            <p>Chọn khoảng thời gian và nhấn "Tạo báo cáo" để xem báo cáo hiệu suất</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Work Summary Modal -->
    <div class="modal fade" id="workSummaryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tóm tắt công việc 30 ngày gần đây</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="workSummaryContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Charts Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="chart-card">
                <h5 class="mb-3">
                    <i class="fas fa-chart-pie text-success"></i>
                    Thống kê thanh toán theo trạng thái
                </h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="paymentStatusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card">
                <h5 class="mb-3">
                    <i class="fas fa-chart-bar text-primary"></i>
                    Thống kê thanh toán theo phương thức
                </h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="chart-card">
                <h5 class="mb-3">
                    <i class="fas fa-chart-pie text-warning"></i>
                    Thống kê thanh toán theo loại
                </h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="paymentTypeChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card">
                <h5 class="mb-3">
                    <i class="fas fa-chart-line text-info"></i>
                    Thanh toán theo tháng (12 tháng gần nhất)
                </h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="paymentMonthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-number text-primary"><?= $paymentStats['total_payments'] ?></div>
                <div class="stats-label">Tổng giao dịch</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-number text-success"><?= number_format($paymentStats['total_amount'], 0, ',', '.') ?> VNĐ</div>
                <div class="stats-label">Tổng số tiền</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-number text-info"><?= $paymentStats['by_status']['Thành công'] ?></div>
                <div class="stats-label">Giao dịch thành công</div>
            </div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="row">
        <div class="col-12">
            <div class="chart-card">
                <h5 class="mb-3">
                    <i class="fas fa-list text-info"></i>
                    Báo cáo gần đây
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nhiệm vụ</th>
                                <th>Sự kiện</th>
                                <th>Trạng thái</th>
                                <th>Tiến độ</th>
                                <th>Ngày tạo</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentReports)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Chưa có báo cáo nào</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentReports as $report): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($report['NhiemVu']) ?></td>
                                        <td><?= htmlspecialchars($report['TenSuKien'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $report['TrangThai'])) ?>">
                                                <?= htmlspecialchars($report['TrangThai']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?= $report['Tiendo'] ?>"></div>
                                            </div>
                                            <small class="text-muted"><?= $report['Tiendo'] ?></small>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($report['NgayTao'])) ?></td>
                                        <td>
                                            <?php if (!empty($report['GhiChu'])): ?>
                                                <button class="btn btn-sm btn-outline-info" onclick="showNote('<?= htmlspecialchars($report['GhiChu']) ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Note Modal -->
    <div class="modal fade" id="noteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ghi chú</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="noteContent"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Report Modal -->
    <div class="modal fade" id="progressReportModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-paper-plane"></i>
                        Gửi báo cáo tiến độ cho quản lý
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Hướng dẫn:</strong> Chọn quản lý và công việc để báo cáo tiến độ. Báo cáo sẽ được gửi đến quản lý để theo dõi.
                    </div>
                    
                    <form id="progressReportForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-user-tie text-primary"></i>
                                        Chọn quản lý: <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="managerSelect" required>
                                        <option value="">-- Chọn quản lý --</option>
                                    </select>
                                    <div class="form-text">Chỉ hiển thị các quản lý (Role 2)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-tasks text-success"></i>
                                        Chọn công việc: <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="taskSelect" required>
                                        <option value="">-- Chọn công việc --</option>
                                    </select>
                                    <div class="form-text">Công việc được giao cho bạn</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-percentage text-warning"></i>
                                        Tiến độ (%): <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="progressInput" min="0" max="100" required>
                                    <div class="form-text">Nhập phần trăm hoàn thành (0-100%)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-flag text-info"></i>
                                        Trạng thái:
                                    </label>
                                    <select class="form-select" id="statusSelect">
                                        <option value="">-- Chọn trạng thái --</option>
                                        <option value="Chưa bắt đầu">Chưa bắt đầu</option>
                                        <option value="Đang thực hiện">Đang thực hiện</option>
                                        <option value="Hoàn thành">Hoàn thành</option>
                                        <option value="Báo sự cố">Báo sự cố</option>
                                    </select>
                                    <div class="form-text">Cập nhật trạng thái công việc</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-comment-alt text-secondary"></i>
                                Ghi chú chi tiết:
                            </label>
                            <textarea class="form-control" id="notesInput" rows="4" placeholder="Mô tả chi tiết về tiến độ công việc, những gì đã hoàn thành, khó khăn gặp phải, kế hoạch tiếp theo..."></textarea>
                            <div class="form-text">Mô tả chi tiết để quản lý hiểu rõ tình hình công việc</div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lưu ý:</strong> Báo cáo sẽ được lưu vào hệ thống và quản lý có thể xem lại lịch sử báo cáo của bạn.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitProgressReport()">
                        <i class="fas fa-paper-plane"></i> Gửi báo cáo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Reports History Modal -->
    <div class="modal fade" id="progressReportsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Lịch sử báo cáo tiến độ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="progressReportsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js - Phải load trước khi khởi tạo biểu đồ -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Hide loading overlay và khởi tạo biểu đồ
        document.addEventListener('DOMContentLoaded', function() {
            const loadingOverlay = document.getElementById('pageLoading');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            
            // Đợi một chút để đảm bảo Chart.js đã load
            setTimeout(function() {
                if (typeof Chart !== 'undefined') {
                    console.log('Chart.js loaded, initializing payment charts...');
                    initializePaymentCharts();
                } else {
                    console.error('Chart.js is not available');
                    // Thử load lại
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
                    script.onload = function() {
                        console.log('Chart.js loaded dynamically');
                        initializePaymentCharts();
                    };
                    script.onerror = function() {
                        console.error('Failed to load Chart.js');
                    };
                    document.head.appendChild(script);
                }
            }, 100);
        });
        
        // Initialize payment charts
        function initializePaymentCharts() {
            try {
                const paymentStats = <?= json_encode($paymentStats) ?>;
                console.log('Payment Stats:', paymentStats);
                console.log('Chart.js available:', typeof Chart !== 'undefined');
                
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js is not loaded');
                    alert('Chart.js chưa được tải. Vui lòng tải lại trang.');
                    return;
                }
                
                // Kiểm tra canvas elements
                const statusCtx = document.getElementById('paymentStatusChart');
                const methodCtx = document.getElementById('paymentMethodChart');
                const typeCtx = document.getElementById('paymentTypeChart');
                const monthlyCtx = document.getElementById('paymentMonthlyChart');
                
                console.log('Canvas elements found:', {
                    statusCtx: !!statusCtx,
                    methodCtx: !!methodCtx,
                    typeCtx: !!typeCtx,
                    monthlyCtx: !!monthlyCtx
                });
                
                // Payment Status Chart (Doughnut)
                if (statusCtx) {
                    console.log('Creating payment status chart...');
                    const statusData = [
                        paymentStats.by_status && paymentStats.by_status['Đang xử lý'] ? paymentStats.by_status['Đang xử lý'] : 0,
                        paymentStats.by_status && paymentStats.by_status['Thành công'] ? paymentStats.by_status['Thành công'] : 0,
                        paymentStats.by_status && paymentStats.by_status['Thất bại'] ? paymentStats.by_status['Thất bại'] : 0,
                        paymentStats.by_status && paymentStats.by_status['Đã hủy'] ? paymentStats.by_status['Đã hủy'] : 0
                    ];
                    console.log('Status data:', statusData);
                    
                    new Chart(statusCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Đang xử lý', 'Thành công', 'Thất bại', 'Đã hủy'],
                        datasets: [{
                            data: statusData,
                            backgroundColor: [
                                '#ffc107',
                                '#28a745',
                                '#dc3545',
                                '#6c757d'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
                    console.log('Payment status chart created');
                } else {
                    console.warn('paymentStatusChart canvas not found');
                }
            
            // Payment Method Chart (Bar)
            if (methodCtx) {
                console.log('Creating payment method chart...');
                const methodData = [
                    paymentStats.by_method && paymentStats.by_method['Chuyển khoản'] ? paymentStats.by_method['Chuyển khoản'] : 0,
                    paymentStats.by_method && paymentStats.by_method['Momo'] ? paymentStats.by_method['Momo'] : 0,
                    paymentStats.by_method && paymentStats.by_method['ZaloPay'] ? paymentStats.by_method['ZaloPay'] : 0,
                    paymentStats.by_method && paymentStats.by_method['Visa/MasterCard'] ? paymentStats.by_method['Visa/MasterCard'] : 0,
                    paymentStats.by_method && paymentStats.by_method['Tiền mặt'] ? paymentStats.by_method['Tiền mặt'] : 0
                ];
                console.log('Method data:', methodData);
                
                new Chart(methodCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Chuyển khoản', 'Momo', 'ZaloPay', 'Visa/MasterCard', 'Tiền mặt'],
                        datasets: [{
                            label: 'Số lượng giao dịch',
                            data: methodData,
                            backgroundColor: [
                                '#007bff',
                                '#e83e8c',
                                '#20c997',
                                '#fd7e14',
                                '#6f42c1'
                            ],
                            borderRadius: 8,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
                console.log('Payment method chart created');
            } else {
                console.warn('paymentMethodChart canvas not found');
            }
            
            // Payment Type Chart (Doughnut)
            if (typeCtx) {
                console.log('Creating payment type chart...');
                const typeData = [
                    paymentStats.by_type && paymentStats.by_type['Đặt cọc'] ? paymentStats.by_type['Đặt cọc'] : 0,
                    paymentStats.by_type && paymentStats.by_type['Thanh toán đủ'] ? paymentStats.by_type['Thanh toán đủ'] : 0,
                    paymentStats.by_type && paymentStats.by_type['Hoàn tiền'] ? paymentStats.by_type['Hoàn tiền'] : 0
                ];
                console.log('Type data:', typeData);
                
                new Chart(typeCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Đặt cọc', 'Thanh toán đủ', 'Hoàn tiền'],
                        datasets: [{
                            data: typeData,
                            backgroundColor: [
                                '#17a2b8',
                                '#28a745',
                                '#ffc107'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
                console.log('Payment type chart created');
            } else {
                console.warn('paymentTypeChart canvas not found');
            }
            
            // Monthly Payment Chart (Line)
            if (monthlyCtx) {
                if (paymentStats.monthly_payments && paymentStats.monthly_payments.length > 0) {
                    console.log('Creating monthly payment chart...');
                    new Chart(monthlyCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: paymentStats.monthly_payments.map(item => item.month),
                        datasets: [{
                            label: 'Số lượng giao dịch',
                            data: paymentStats.monthly_payments.map(item => item.count),
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }, {
                            label: 'Tổng số tiền (triệu VNĐ)',
                            data: paymentStats.monthly_payments.map(item => (item.total_amount / 1000000).toFixed(2)),
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Số lượng giao dịch'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Tổng tiền (triệu VNĐ)'
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                });
                    console.log('Monthly payment chart created');
                } else {
                    console.log('No monthly payment data, showing empty message');
                    monthlyCtx.parentElement.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-chart-line fa-2x mb-2"></i><p>Chưa có dữ liệu thanh toán</p></div>';
                }
            } else {
                console.warn('paymentMonthlyChart canvas not found');
            }
            
            console.log('Payment charts initialized successfully');
        } catch (error) {
            console.error('Error initializing payment charts:', error);
            // Show error message
            const chartContainers = document.querySelectorAll('#paymentStatusChart, #paymentMethodChart, #paymentTypeChart, #paymentMonthlyChart');
            chartContainers.forEach(container => {
                if (container && container.parentElement) {
                    container.parentElement.innerHTML = '<div class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>Lỗi khi tải biểu đồ</p></div>';
                }
            });
        }
        }


        // Show note function
        function showNote(note) {
            document.getElementById('noteContent').textContent = note;
            new bootstrap.Modal(document.getElementById('noteModal')).show();
        }

        // Load detailed stats
        function loadDetailedStats() {
            const modal = new bootstrap.Modal(document.getElementById('detailedStatsModal'));
            modal.show();
            
            fetch('../src/controllers/staff-reports.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_detailed_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayDetailedStats(data);
                } else {
                    document.getElementById('detailedStatsContent').innerHTML = 
                        '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('detailedStatsContent').innerHTML = 
                    '<div class="alert alert-danger">Lỗi khi tải dữ liệu</div>';
            });
        }

        // Display detailed stats
        function displayDetailedStats(data) {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Thống kê tổng quan</h6>
                        <table class="table table-sm">
                            <tr><td>Tổng công việc:</td><td><strong>${data.stats.total_assignments}</strong></td></tr>
                            <tr><td>Đã hoàn thành:</td><td><strong class="text-success">${data.stats.completed}</strong></td></tr>
                            <tr><td>Đang thực hiện:</td><td><strong class="text-warning">${data.stats.in_progress}</strong></td></tr>
                            <tr><td>Chưa làm:</td><td><strong class="text-secondary">${data.stats.pending}</strong></td></tr>
                            <tr><td>Báo sự cố:</td><td><strong class="text-danger">${data.stats.issues}</strong></td></tr>
                            <tr><td>Tỷ lệ hoàn thành:</td><td><strong class="text-primary">${parseFloat(data.stats.completion_rate).toFixed(1)}%</strong></td></tr>
                            <tr><td>Công việc hiện tại:</td><td><strong>${data.stats.current_tasks}</strong></td></tr>
                            <tr><td>Công việc quá hạn:</td><td><strong class="text-danger">${data.stats.overdue_tasks}</strong></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Hiệu suất theo loại sự kiện</h6>
                        <table class="table table-sm">
                            ${data.eventTypeStats.map(item => `
                                <tr>
                                    <td>${item.event_type}</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: ${item.completion_rate}%">
                                                ${parseFloat(item.completion_rate).toFixed(1)}%
                                            </div>
                                        </div>
                                        <small class="text-muted">${item.completed}/${item.assignments} hoàn thành</small>
                                    </td>
                                </tr>
                            `).join('')}
                        </table>
                    </div>
                </div>
            `;
            document.getElementById('detailedStatsContent').innerHTML = content;
        }

        // Show performance report modal
        function showPerformanceReport() {
            new bootstrap.Modal(document.getElementById('performanceReportModal')).show();
        }

        // Generate performance report
        function generatePerformanceReport() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (!startDate || !endDate) {
                alert('Vui lòng chọn khoảng thời gian');
                return;
            }
            
            document.getElementById('performanceReportContent').innerHTML = 
                '<div class="text-center"><div class="spinner-border"></div><p>Đang tạo báo cáo...</p></div>';
            
            fetch('../src/controllers/staff-reports.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_performance_report&start_date=${startDate}&end_date=${endDate}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPerformanceReport(data);
                } else {
                    document.getElementById('performanceReportContent').innerHTML = 
                        '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('performanceReportContent').innerHTML = 
                    '<div class="alert alert-danger">Lỗi khi tạo báo cáo</div>';
            });
        }

        // Display performance report
        function displayPerformanceReport(data) {
            const content = `
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-primary">${data.summary.total}</h5>
                                <p class="card-text">Tổng công việc</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-success">${data.summary.completed}</h5>
                                <p class="card-text">Hoàn thành</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-warning">${data.summary.in_progress}</h5>
                                <p class="card-text">Đang thực hiện</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-danger">${data.summary.issues}</h5>
                                <p class="card-text">Báo sự cố</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nhiệm vụ</th>
                                <th>Sự kiện</th>
                                <th>Địa điểm</th>
                                <th>Trạng thái</th>
                                <th>Tiến độ</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.reports.map(report => `
                                <tr>
                                    <td>${report.NhiemVu}</td>
                                    <td>${report.TenSuKien}</td>
                                    <td>${report.TenDiaDiem}</td>
                                    <td>
                                        <span class="badge bg-${getStatusColor(report.TrangThai)}">
                                            ${report.TrangThai}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: ${report.Tiendo}">
                                                ${report.Tiendo}
                                            </div>
                                        </div>
                                    </td>
                                    <td>${formatDate(report.NgayBatDau)}</td>
                                    <td>${formatDate(report.NgayKetThuc)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            document.getElementById('performanceReportContent').innerHTML = content;
        }

        // Show work summary
        function showWorkSummary() {
            const modal = new bootstrap.Modal(document.getElementById('workSummaryModal'));
            modal.show();
            
            fetch('../src/controllers/staff-reports.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_work_summary'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayWorkSummary(data.workSummary);
                } else {
                    document.getElementById('workSummaryContent').innerHTML = 
                        '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('workSummaryContent').innerHTML = 
                    '<div class="alert alert-danger">Lỗi khi tải dữ liệu</div>';
            });
        }

        // Display work summary
        function displayWorkSummary(workSummary) {
            if (workSummary.length === 0) {
                document.getElementById('workSummaryContent').innerHTML = 
                    '<div class="text-center text-muted"><p>Không có dữ liệu công việc trong 30 ngày gần đây</p></div>';
                return;
            }
            
            const content = `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Tổng công việc</th>
                                <th>Hoàn thành</th>
                                <th>Tỷ lệ hoàn thành</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${workSummary.map(day => `
                                <tr>
                                    <td>${formatDate(day.work_date)}</td>
                                    <td><strong>${day.total_tasks}</strong></td>
                                    <td><strong class="text-success">${day.completed_tasks}</strong></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: ${day.completion_rate}%">
                                                ${parseFloat(day.completion_rate).toFixed(1)}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            document.getElementById('workSummaryContent').innerHTML = content;
        }

        // Export report
        function exportReport() {
            const startDate = prompt('Từ ngày (YYYY-MM-DD):', '<?= date('Y-m-01') ?>');
            if (!startDate) return;
            
            const endDate = prompt('Đến ngày (YYYY-MM-DD):', '<?= date('Y-m-t') ?>');
            if (!endDate) return;
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../src/controllers/staff-reports.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'export_report';
            form.appendChild(actionInput);
            
            const startInput = document.createElement('input');
            startInput.type = 'hidden';
            startInput.name = 'start_date';
            startInput.value = startDate;
            form.appendChild(startInput);
            
            const endInput = document.createElement('input');
            endInput.type = 'hidden';
            endInput.name = 'end_date';
            endInput.value = endDate;
            form.appendChild(endInput);
            
            const formatInput = document.createElement('input');
            formatInput.type = 'hidden';
            formatInput.name = 'format';
            formatInput.value = 'excel';
            form.appendChild(formatInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        // Helper functions
        function getStatusColor(status) {
            switch(status) {
                case 'Hoàn thành': return 'success';
                case 'Đang làm': return 'warning';
                case 'Chưa làm': return 'secondary';
                case 'Báo sự cố': return 'danger';
                default: return 'secondary';
            }
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
        }

        // Progress Report Functions
        function showProgressReportModal() {
            const modal = new bootstrap.Modal(document.getElementById('progressReportModal'));
            modal.show();
            
            // Load managers and tasks
            loadManagers();
            loadTasks();
        }

        function loadManagers() {
            fetch('../src/controllers/staff-reports.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_managers'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('managerSelect');
                    select.innerHTML = '<option value="">-- Chọn quản lý --</option>';
                    data.managers.forEach(manager => {
                        const option = document.createElement('option');
                        option.value = manager.ID_NhanVien;
                        option.textContent = `${manager.HoTen} (${manager.ChucVu})`;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading managers:', error);
            });
        }

        function loadTasks() {
            // Load tasks from both lichlamviec and chitietkehoach
            Promise.all([
                // Load from lichlamviec
                fetch('../src/controllers/staff-schedule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_assignments'
                }).then(response => response.json()),
                
                // Load from chitietkehoach (via event-planning.php)
                fetch('../src/controllers/event-planning.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_staff_tasks'
                }).then(response => response.json()).catch(() => ({ success: true, tasks: [] }))
            ])
            .then(([scheduleData, planningData]) => {
                const select = document.getElementById('taskSelect');
                select.innerHTML = '<option value="">-- Chọn công việc --</option>';
                
                let taskCount = 0;
                
                // Add tasks from lichlamviec
                if (scheduleData.success && scheduleData.assignments) {
                    scheduleData.assignments.forEach(task => {
                        if (task.source_table === 'lichlamviec') {
                            const option = document.createElement('option');
                            option.value = `${task.ID_LLV}_lichlamviec`;
                            option.textContent = `${task.NhiemVu} (${task.TenSuKien || 'N/A'})`;
                            select.appendChild(option);
                            taskCount++;
                        }
                    });
                }
                
                // Add tasks from chitietkehoach
                if (planningData.success && planningData.tasks) {
                    planningData.tasks.forEach(task => {
                        const option = document.createElement('option');
                        option.value = `${task.ID_ChiTiet}_chitietkehoach`;
                        option.textContent = `${task.TenBuoc} (${task.TenSuKien || 'N/A'})`;
                        select.appendChild(option);
                        taskCount++;
                    });
                }
                
                if (taskCount === 0) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Không có công việc nào';
                    option.disabled = true;
                    select.appendChild(option);
                }
            })
            .catch(error => {
                console.error('Error loading tasks:', error);
                const select = document.getElementById('taskSelect');
                select.innerHTML = '<option value="">Lỗi khi tải danh sách công việc</option>';
            });
        }

        function submitProgressReport() {
            const managerId = document.getElementById('managerSelect').value;
            const taskValue = document.getElementById('taskSelect').value;
            const progress = document.getElementById('progressInput').value;
            const status = document.getElementById('statusSelect').value;
            const notes = document.getElementById('notesInput').value;

            if (!managerId || !taskValue || !progress) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                return;
            }

            const [taskId, taskType] = taskValue.split('_');

            const formData = new FormData();
            formData.append('action', 'submit_progress_report');
            formData.append('manager_id', managerId);
            formData.append('task_id', taskId);
            formData.append('task_type', taskType);
            formData.append('progress', progress);
            formData.append('status', status);
            formData.append('notes', notes);

            fetch('../src/controllers/staff-reports.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Gửi báo cáo tiến độ thành công!');
                    bootstrap.Modal.getInstance(document.getElementById('progressReportModal')).hide();
                    // Reset form
                    document.getElementById('progressReportForm').reset();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi gửi báo cáo');
            });
        }

        function showProgressReports() {
            const modal = new bootstrap.Modal(document.getElementById('progressReportsModal'));
            modal.show();
            
            fetch('../src/controllers/staff-reports.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_progress_reports'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayProgressReports(data.reports);
                } else {
                    document.getElementById('progressReportsContent').innerHTML = 
                        '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                document.getElementById('progressReportsContent').innerHTML = 
                    '<div class="alert alert-danger">Lỗi khi tải lịch sử báo cáo</div>';
            });
        }

        function displayProgressReports(reports) {
            if (reports.length === 0) {
                document.getElementById('progressReportsContent').innerHTML = 
                    '<div class="text-center text-muted"><p>Chưa có báo cáo nào</p></div>';
                return;
            }

            const content = `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Ngày báo cáo</th>
                                <th>Công việc</th>
                                <th>Quản lý</th>
                                <th>Tiến độ</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${reports.map(report => `
                                <tr>
                                    <td>${formatDate(report.NgayBaoCao)}</td>
                                    <td>${report.TenCongViec || 'N/A'}</td>
                                    <td>${report.TenQuanLy || 'N/A'}</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: ${report.TienDo}%">
                                                ${report.TienDo}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-${getStatusColor(report.TrangThai)}">
                                            ${report.TrangThai}
                                        </span>
                                    </td>
                                    <td>
                                        ${report.GhiChu ? 
                                            `<button class="btn btn-sm btn-outline-info" onclick="showNote('${report.GhiChu}')">
                                                <i class="fas fa-eye"></i>
                                            </button>` : 
                                            '<span class="text-muted">-</span>'
                                        }
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            document.getElementById('progressReportsContent').innerHTML = content;
        }
    </script>
</body>
</html>
