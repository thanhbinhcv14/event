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
    
} catch (Exception $e) {
    $stats = ['total_assignments' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0, 'issues' => 0];
    $monthlyStats = [];
    $recentReports = [];
    $staffInfo = ['HoTen' => 'Nhân viên', 'ChucVu' => 'Staff'];
    error_log("Error fetching staff reports: " . $e->getMessage());
    echo "<!-- Error: " . $e->getMessage() . " -->";
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        #monthlyChart {
            max-height: 400px !important;
            height: 400px !important;
        }
        
        #statusChart {
            max-height: 300px !important;
            height: 300px !important;
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

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="chart-card">
                <h5 class="mb-3">
                    <i class="fas fa-chart-bar text-primary"></i>
                    Hiệu suất theo tháng
                </h5>
                <div style="height: 400px; position: relative;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-card">
                <h5 class="mb-3">
                    <i class="fas fa-pie-chart text-success"></i>
                    Phân bố trạng thái
                </h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gửi báo cáo tiến độ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="progressReportForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Chọn quản lý:</label>
                                    <select class="form-select" id="managerSelect" required>
                                        <option value="">-- Chọn quản lý --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Chọn công việc:</label>
                                    <select class="form-select" id="taskSelect" required>
                                        <option value="">-- Chọn công việc --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tiến độ (%):</label>
                                    <input type="number" class="form-control" id="progressInput" min="0" max="100" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái:</label>
                                    <select class="form-select" id="statusSelect">
                                        <option value="">-- Chọn trạng thái --</option>
                                        <option value="Đang thực hiện">Đang thực hiện</option>
                                        <option value="Hoàn thành">Hoàn thành</option>
                                        <option value="Báo sự cố">Báo sự cố</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú:</label>
                            <textarea class="form-control" id="notesInput" rows="3" placeholder="Mô tả chi tiết về tiến độ công việc..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="submitProgressReport()">Gửi báo cáo</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Hide loading overlay
        window.addEventListener('load', function() {
            const loadingOverlay = document.getElementById('pageLoading');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        });

        // Wait for DOM to be ready before initializing charts
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });

        // Initialize charts function
        function initializeCharts() {
            try {
                // Monthly Performance Chart
                const monthlyCtx = document.getElementById('monthlyChart');
                if (monthlyCtx) {
                    const monthlyData = <?= json_encode($monthlyStats) ?>;
                    
                    // Handle empty data
                    if (!monthlyData || monthlyData.length === 0) {
                        monthlyCtx.parentElement.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-chart-line fa-2x mb-2"></i><p>Chưa có dữ liệu thống kê</p></div>';
                        return;
                    }
                    
                    new Chart(monthlyCtx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: monthlyData.map(item => item.month),
                            datasets: [{
                                label: 'Tổng công việc',
                                data: monthlyData.map(item => item.assignments),
                                borderColor: '#667eea',
                                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                tension: 0.4
                            }, {
                                label: 'Hoàn thành',
                                data: monthlyData.map(item => item.completed),
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                // Status Distribution Chart
                const statusCtx = document.getElementById('statusChart');
                if (statusCtx) {
                    const statsData = [
                        <?= $stats['completed'] ?>,
                        <?= $stats['in_progress'] ?>,
                        <?= $stats['pending'] ?>,
                        <?= $stats['issues'] ?>
                    ];
                    
                    // Check if all values are zero
                    if (statsData.every(val => val === 0)) {
                        statusCtx.parentElement.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-pie-chart fa-2x mb-2"></i><p>Chưa có dữ liệu phân bố</p></div>';
                        return;
                    }
                    
                    new Chart(statusCtx.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Hoàn thành', 'Đang thực hiện', 'Chưa làm', 'Báo sự cố'],
                            datasets: [{
                                data: statsData,
                                backgroundColor: [
                                    '#28a745',
                                    '#ffc107',
                                    '#6c757d',
                                    '#dc3545'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error initializing charts:', error);
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
            // Load tasks from staff-schedule.php
            fetch('../src/controllers/staff-schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_assignments'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('taskSelect');
                    select.innerHTML = '<option value="">-- Chọn công việc --</option>';
                    data.assignments.forEach(task => {
                        const option = document.createElement('option');
                        option.value = `${task.ID_LLV}_${task.source_table}`;
                        option.textContent = `${task.NhiemVu} (${task.TenSuKien})`;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading tasks:', error);
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
