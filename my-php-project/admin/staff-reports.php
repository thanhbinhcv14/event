<?php
require_once 'includes/admin-header.php';

// Kiểm tra người dùng có role 4 (Nhân viên)
if ($user['ID_Role'] != 4) {
    header('Location: index.php');
    exit;
}

// Lấy thông tin nhân viên
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
        header('Location: ../login.php');
        exit;
    }
    
    // Lấy thống kê từ cả hai nguồn
    $stats = ['total_assignments' => 0, 'completed' => 0, 'in_progress' => 0, 'pending' => 0, 'issues' => 0];
    
    // Từ lichlamviec
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
    
    // Từ chitietkehoach
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
    
    // Kết hợp thống kê
    $stats['total_assignments'] = $lichlamviecStats['total_assignments'] + $chitietkehoachStats['total_assignments'];
    $stats['completed'] = $lichlamviecStats['completed'] + $chitietkehoachStats['completed'];
    $stats['in_progress'] = $lichlamviecStats['in_progress'] + $chitietkehoachStats['in_progress'];
    $stats['pending'] = $lichlamviecStats['pending'] + $chitietkehoachStats['pending'];
    $stats['issues'] = $lichlamviecStats['issues'] + $chitietkehoachStats['issues'];
    
    // Lấy hiệu suất theo tháng
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
    
    // Lấy báo cáo gần đây
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
            height: 20px;
            border-radius: 10px;
            background-color: #e9ecef;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .progress-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            transition: width 0.6s ease;
            border-radius: 10px;
            min-width: 2em;
        }
        
        .progress-bar.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        }
        
        .progress-bar.bg-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
        }
        
        .progress-bar.bg-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
        }
        
        .progress-bar.bg-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
        }
        
        .progress-sm {
            height: 16px;
            font-size: 0.7rem;
        }
        
        .progress-md {
            height: 20px;
            font-size: 0.75rem;
        }
        
        .progress-lg {
            height: 24px;
            font-size: 0.8rem;
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
    

    <!-- Action Buttons -->
    <!-- Detailed Stats Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line text-primary"></i>
                        Thống kê chi tiết
                </h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadDetailedStats()">
                        <i class="fas fa-sync-alt"></i> Làm mới
                        </button>
                    </div>
                <div id="detailedStatsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
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
                    Nhiệm vụ gần đây
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
                                            <?php
                                            // Parse progress value
                                            $progressValue = 0;
                                            if (!empty($report['Tiendo'])) {
                                                if (is_numeric($report['Tiendo'])) {
                                                    $progressValue = (int)$report['Tiendo'];
                                                } else {
                                                    preg_match('/(\d+)/', $report['Tiendo'], $matches);
                                                    $progressValue = isset($matches[1]) ? (int)$matches[1] : 0;
                                                }
                                            }
                                            $progressValue = max(0, min(100, $progressValue));
                                            
                                            // Determine progress bar color
                                            $progressClass = 'bg-secondary';
                                            if ($progressValue >= 100) {
                                                $progressClass = 'bg-success';
                                            } elseif ($progressValue > 0) {
                                                $progressClass = 'bg-warning';
                                            }
                                            ?>
                                            <div class="progress">
                                                <div class="progress-bar <?= $progressClass ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $progressValue ?>%" 
                                                     aria-valuenow="<?= $progressValue ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <strong><?= $progressValue ?>%</strong>
                                                </div>
                                            </div>
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

    <!-- Progress Reports History Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-history text-info"></i>
                        Lịch sử báo cáo tiến độ
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadProgressReports()">
                        <i class="fas fa-sync-alt"></i> Làm mới
                    </button>
                </div>
                <div id="progressReportsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Hide loading overlay
        document.addEventListener('DOMContentLoaded', function() {
            const loadingOverlay = document.getElementById('pageLoading');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
            
            // Load detailed stats and progress reports on page load
            loadDetailedStats();
            loadProgressReports();
        });


        // Show note function
        function showNote(note) {
            if (!note) {
                alert('Không có ghi chú');
                return;
            }
            // Unescape and display note (note is already escaped when passed)
            const noteDiv = document.getElementById('noteContent');
            // Decode escaped characters and preserve line breaks
            let decodedNote = note.replace(/\\n/g, '\n').replace(/\\'/g, "'");
            // Escape HTML for display
            noteDiv.textContent = decodedNote;
            // Or use innerHTML with proper escaping
            noteDiv.innerHTML = decodedNote.replace(/\n/g, '<br>').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            new bootstrap.Modal(document.getElementById('noteModal')).show();
        }

        // Load detailed stats
        function loadDetailedStats() {
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
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="fas fa-chart-pie text-primary"></i> Thống kê theo trạng thái</h6>
                        <div style="height: 300px; position: relative;">
                            <canvas id="statusPieChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="fas fa-info-circle text-info"></i> Thống kê tổng quan</h6>
                        <table class="table table-sm">
                            <tr><td>Tổng công việc:</td><td><strong>${data.stats.total_assignments}</strong></td></tr>
                            <tr><td>Đã hoàn thành:</td><td><strong class="text-success">${data.stats.completed}</strong></td></tr>
                            <tr><td>Đang thực hiện:</td><td><strong class="text-warning">${data.stats.in_progress}</strong></td></tr>
                            <tr><td>Chưa làm:</td><td><strong class="text-secondary">${data.stats.pending}</strong></td></tr>
                            <tr><td>Báo sự cố:</td><td><strong class="text-danger">${data.stats.issues}</strong></td></tr>
                            <tr><td>Tỷ lệ hoàn thành:</td><td><strong class="text-primary">${parseFloat(data.stats.completion_rate || 0).toFixed(1)}%</strong></td></tr>
                            <tr><td>Công việc hiện tại:</td><td><strong>${data.stats.current_tasks || 0}</strong></td></tr>
                            <tr><td>Công việc quá hạn:</td><td><strong class="text-danger">${data.stats.overdue_tasks || 0}</strong></td></tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6 class="mb-3"><i class="fas fa-chart-bar text-success"></i> Hiệu suất theo loại sự kiện</h6>
                        ${data.eventTypeStats && data.eventTypeStats.length > 0 ? `
                        <div style="height: 400px; position: relative;">
                            <canvas id="eventTypeBarChart"></canvas>
                        </div>
                        ` : '<p class="text-muted">Chưa có dữ liệu theo loại sự kiện</p>'}
                    </div>
                </div>
            `;
            document.getElementById('detailedStatsContent').innerHTML = content;
            
            // Initialize charts after content is added
            setTimeout(() => {
                initializeDetailedStatsCharts(data);
            }, 100);
        }
        
        // Initialize charts for detailed stats
        function initializeDetailedStatsCharts(data) {
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js is not loaded');
                    return;
                }
                
            // Status Pie Chart
            const statusCtx = document.getElementById('statusPieChart');
                if (statusCtx) {
                    const statusData = [
                    data.stats.completed || 0,
                    data.stats.in_progress || 0,
                    data.stats.pending || 0,
                    data.stats.issues || 0
                ];
                    
                    new Chart(statusCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Đã hoàn thành', 'Đang thực hiện', 'Chưa làm', 'Báo sự cố'],
                        datasets: [{
                            data: statusData,
                            backgroundColor: [
                                '#28a745',
                                '#ffc107',
                                '#6c757d',
                                '#dc3545'
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
                                    usePointStyle: true,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Event Type Bar Chart
            if (data.eventTypeStats && data.eventTypeStats.length > 0) {
                const eventTypeCtx = document.getElementById('eventTypeBarChart');
                if (eventTypeCtx) {
                    const eventTypes = data.eventTypeStats.map(item => escapeHtml(item.event_type || 'N/A'));
                    const completionRates = data.eventTypeStats.map(item => parseFloat(item.completion_rate || 0));
                    const completedCounts = data.eventTypeStats.map(item => item.completed || 0);
                    const totalCounts = data.eventTypeStats.map(item => item.assignments || 0);
                    
                    new Chart(eventTypeCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                            labels: eventTypes,
                        datasets: [{
                                label: 'Tỷ lệ hoàn thành (%)',
                                data: completionRates,
                                backgroundColor: completionRates.map(rate => {
                                    if (rate >= 100) return '#28a745';
                                    if (rate >= 50) return '#ffc107';
                                    if (rate > 0) return '#17a2b8';
                                    return '#6c757d';
                                }),
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
                                },
                                tooltip: {
                                    callbacks: {
                                        afterLabel: function(context) {
                                            const index = context.dataIndex;
                                            return `Hoàn thành: ${completedCounts[index]}/${totalCounts[index]}`;
                                        }
                                    }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        callback: function(value) {
                                            return value + '%';
                                        }
                                    },
                                title: {
                                    display: true,
                                        text: 'Tỷ lệ hoàn thành (%)'
                                    }
                                },
                                x: {
                                    ticks: {
                                        maxRotation: 45,
                                        minRotation: 45
                                }
                            }
                        }
                    }
                });
                }
            }
        }

        // Display performance report (removed - no longer used)
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
                            ${(data.reports || []).map(report => {
                                // Parse progress value
                                let progressValue = 0;
                                if (report.Tiendo !== null && report.Tiendo !== undefined) {
                                    if (typeof report.Tiendo === 'string') {
                                        const match = report.Tiendo.toString().match(/(\d+)/);
                                        progressValue = match ? parseInt(match[1]) : 0;
                                    } else {
                                        progressValue = parseInt(report.Tiendo) || 0;
                                    }
                                }
                                progressValue = Math.max(0, Math.min(100, progressValue));
                                
                                // Determine progress bar color
                                let progressBarClass = 'bg-secondary';
                                if (progressValue >= 100) {
                                    progressBarClass = 'bg-success';
                                } else if (progressValue > 0) {
                                    progressBarClass = 'bg-warning';
                                }
                                
                                return `
                                <tr>
                                    <td>${escapeHtml(report.NhiemVu || 'N/A')}</td>
                                    <td>${escapeHtml(report.TenSuKien || 'N/A')}</td>
                                    <td>${escapeHtml(report.TenDiaDiem || 'N/A')}</td>
                                    <td>
                                        <span class="badge bg-${getStatusColor(report.TrangThai || '')}">
                                            ${escapeHtml(report.TrangThai || 'N/A')}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar ${progressBarClass}" style="width: ${progressValue}%">
                                                ${progressValue}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>${formatDate(report.NgayBatDau)}</td>
                                    <td>${formatDate(report.NgayKetThuc)}</td>
                                </tr>
                            `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            document.getElementById('performanceReportContent').innerHTML = content;
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
            try {
            const date = new Date(dateString);
                if (isNaN(date.getTime())) return 'N/A';
                return date.toLocaleDateString('vi-VN', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                }) + ' ' + date.toLocaleTimeString('vi-VN', {
                    hour: '2-digit', 
                    minute: '2-digit'
                });
            } catch (e) {
                return dateString;
            }
        }

        // Load Progress Reports
        function loadProgressReports() {
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

        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function displayProgressReports(reports) {
            if (reports.length === 0) {
                document.getElementById('progressReportsContent').innerHTML = 
                    '<div class="text-center text-muted py-4"><i class="fas fa-inbox fa-3x mb-3"></i><p>Chưa có báo cáo nào</p></div>';
                return;
            }

            const content = `
                <div class="mb-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Tổng số báo cáo:</strong> ${reports.length} báo cáo
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 150px;">Ngày báo cáo</th>
                                <th>Công việc</th>
                                <th>Sự kiện</th>
                                <th>Địa điểm</th>
                                <th>Quản lý</th>
                                <th style="width: 200px;">Tiến độ</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${reports.map(report => {
                                // Parse progress value (handle both number and string with %)
                                let progressValue = 0;
                                if (report.TienDo !== null && report.TienDo !== undefined) {
                                    if (typeof report.TienDo === 'string') {
                                        const match = report.TienDo.toString().match(/(\d+)/);
                                        progressValue = match ? parseInt(match[1]) : 0;
                                    } else {
                                        progressValue = parseInt(report.TienDo) || 0;
                                    }
                                }
                                
                                // Ensure progress is between 0 and 100
                                progressValue = Math.max(0, Math.min(100, progressValue));
                                
                                // Determine progress bar color
                                let progressBarClass = 'bg-secondary';
                                if (progressValue >= 100) {
                                    progressBarClass = 'bg-success';
                                } else if (progressValue > 0) {
                                    progressBarClass = 'bg-warning';
                                }
                                
                                // Escape HTML for security
                                const tenCongViec = escapeHtml(report.TenCongViec || 'N/A');
                                const tenSuKien = escapeHtml(report.TenSuKien || 'N/A');
                                const tenDiaDiem = escapeHtml(report.TenDiaDiem || 'N/A');
                                const tenQuanLy = escapeHtml(report.TenQuanLy || 'N/A');
                                const chucVuQuanLy = escapeHtml(report.ChucVuQuanLy || '');
                                const trangThai = escapeHtml(report.TrangThai || 'N/A');
                                const ghiChu = report.GhiChu ? escapeHtml(report.GhiChu).replace(/'/g, "\\'").replace(/\n/g, '\\n') : '';
                                
                                return `
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt text-primary"></i>
                                                ${formatDate(report.NgayBaoCao)}
                                            </small>
                                            </div>
                                    </td>
                                    <td>
                                        <strong>${tenCongViec}</strong>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-calendar-check text-info"></i>
                                            ${tenSuKien}
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-map-marker-alt text-danger"></i>
                                            ${tenDiaDiem}
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-user-tie text-info"></i>
                                            <strong>${tenQuanLy}</strong>
                                            ${chucVuQuanLy ? `<br><small class="text-muted">${chucVuQuanLy}</small>` : ''}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar ${progressBarClass} ${progressValue < 100 && progressValue > 0 ? 'progress-bar-striped progress-bar-animated' : ''}" 
                                                 role="progressbar" 
                                                 style="width: ${progressValue}%" 
                                                 aria-valuenow="${progressValue}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <strong>${progressValue}%</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-${getStatusColor(report.TrangThai || '')}">
                                            ${trangThai}
                                        </span>
                                    </td>
                                    <td>
                                        ${report.GhiChu ? 
                                            `<button class="btn btn-sm btn-outline-info" onclick="showNote('${ghiChu}')" title="Xem ghi chú">
                                                <i class="fas fa-eye"></i> Xem
                                            </button>` : 
                                            '<span class="text-muted"><i class="fas fa-minus"></i></span>'
                                        }
                                    </td>
                                </tr>
                            `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            document.getElementById('progressReportsContent').innerHTML = content;
        }
    </script>
</body>
</html>
