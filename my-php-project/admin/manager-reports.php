<?php
require_once 'includes/admin-header.php';

// Kiểm tra người dùng có role 2 (Quản lý tổ chức)
if ($user['ID_Role'] != 2) {
    header('Location: index.php');
    exit;
}

// Lấy thông tin quản lý
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
    $managerInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$managerInfo) {
        header('Location: ../login.php');
        exit;
    }

    // Lấy báo cáo tiến độ - cho phép tất cả managers (role 2) xem tất cả báo cáo
    // Sử dụng subquery để chỉ lấy bản ghi mới nhất cho mỗi task (ID_NhanVien + ID_Task + LoaiTask)
    $stmt = $pdo->prepare("
        SELECT 
            bct.ID_BaoCao,
            bct.TienDo,
            bct.GhiChu,
            bct.TrangThai,
            bct.NgayBaoCao,
            nv.HoTen as TenNhanVien,
            nv.ChucVu as ChucVuNhanVien,
            CASE 
                WHEN bct.LoaiTask = 'lichlamviec' THEN llv.NhiemVu
                WHEN bct.LoaiTask = 'chitietkehoach' THEN ctk.TenBuoc
            END as TenCongViec,
            COALESCE(dl1.TenSuKien, dl2.TenSuKien) as TenSuKien,
            COALESCE(dl1.NgayBatDau, dl2.NgayBatDau) as NgayBatDau,
            COALESCE(dl1.NgayKetThuc, dl2.NgayKetThuc) as NgayKetThuc
        FROM baocaotiendo bct
        INNER JOIN (
            SELECT ID_NhanVien, ID_Task, LoaiTask, MAX(ID_BaoCao) as MaxID_BaoCao
            FROM baocaotiendo
            GROUP BY ID_NhanVien, ID_Task, LoaiTask
        ) latest ON bct.ID_NhanVien = latest.ID_NhanVien
            AND bct.ID_Task = latest.ID_Task 
            AND bct.LoaiTask = latest.LoaiTask 
            AND bct.ID_BaoCao = latest.MaxID_BaoCao
        LEFT JOIN nhanvieninfo nv ON bct.ID_NhanVien = nv.ID_NhanVien
        LEFT JOIN lichlamviec llv ON bct.ID_Task = llv.ID_LLV AND bct.LoaiTask = 'lichlamviec'
        LEFT JOIN datlichsukien dl1 ON llv.ID_DatLich = dl1.ID_DatLich
        LEFT JOIN chitietkehoach ctk ON bct.ID_Task = ctk.ID_ChiTiet AND bct.LoaiTask = 'chitietkehoach'
        LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
        LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
        LEFT JOIN datlichsukien dl2 ON s.ID_DatLich = dl2.ID_DatLich
        ORDER BY bct.NgayBaoCao DESC
        LIMIT 50
    ");
    $stmt->execute();
    $progressReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy báo cáo sự cố đã nhận
    // Debug: Log manager ID
    error_log("DEBUG: manager-reports.php - Manager ID: " . $managerInfo['ID_NhanVien']);
    
    // Create table if not exists (match database structure)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS baocaosuco (
                ID_BaoCao INT AUTO_INCREMENT PRIMARY KEY,
                ID_NhanVien INT NOT NULL,
                ID_QuanLy INT NOT NULL,
                ID_Task INT NOT NULL,
                LoaiTask ENUM('lichlamviec', 'chitietkehoach') NOT NULL,
                TieuDe VARCHAR(255) NOT NULL,
                MoTa TEXT DEFAULT NULL,
                MucDo ENUM('Thấp', 'Trung bình', 'Cao', 'Khẩn cấp') DEFAULT 'Trung bình',
                TrangThai ENUM('Mới', 'Đang xử lý', 'Đã xử lý', 'Đã đóng') DEFAULT 'Mới',
                NgayBaoCao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    } catch (Exception $e) {
        error_log("DEBUG: manager-reports.php - Table creation: " . $e->getMessage());
    }
    
    // Debug: Check if there are any issue reports in database
    $debugStmt = $pdo->prepare("SELECT COUNT(*) as total FROM baocaosuco");
    $debugStmt->execute();
    $totalReports = $debugStmt->fetch(PDO::FETCH_ASSOC);
    error_log("DEBUG: manager-reports.php - Total issue reports in database: " . $totalReports['total']);
    
    // Debug: Check reports for this manager
    $debugStmt2 = $pdo->prepare("SELECT COUNT(*) as total FROM baocaosuco WHERE ID_QuanLy = ?");
    $debugStmt2->execute([$managerInfo['ID_NhanVien']]);
    $managerReports = $debugStmt2->fetch(PDO::FETCH_ASSOC);
    error_log("DEBUG: manager-reports.php - Reports for manager " . $managerInfo['ID_NhanVien'] . ": " . $managerReports['total']);
    
    // Debug: List all manager IDs in baocaosuco
    $debugStmt3 = $pdo->prepare("SELECT DISTINCT ID_QuanLy FROM baocaosuco");
    $debugStmt3->execute();
    $allManagerIds = $debugStmt3->fetchAll(PDO::FETCH_COLUMN);
    error_log("DEBUG: manager-reports.php - All manager IDs in baocaosuco: " . json_encode($allManagerIds));
    
    // First, try a simple query to check if there are any reports for this manager
    $testStmt = $pdo->prepare("SELECT COUNT(*) as count FROM baocaosuco WHERE ID_QuanLy = ?");
    $testStmt->execute([$managerInfo['ID_NhanVien']]);
    $testResult = $testStmt->fetch(PDO::FETCH_ASSOC);
    error_log("DEBUG: manager-reports.php - Simple count query result: " . $testResult['count']);
    
    // Also check all reports without manager filter
    $allStmt = $pdo->prepare("SELECT COUNT(*) as count FROM baocaosuco");
    $allStmt->execute();
    $allResult = $allStmt->fetch(PDO::FETCH_ASSOC);
    error_log("DEBUG: manager-reports.php - Total reports in database: " . $allResult['count']);
    
    $stmt = $pdo->prepare("
        SELECT 
            bs.ID_BaoCao,
            bs.TieuDe,
            bs.MoTa,
            bs.MucDo,
            bs.TrangThai,
            bs.NgayBaoCao,
            bs.NgayBaoCao as NgayCapNhat,
            COALESCE(nv.HoTen, 'Không xác định') as TenNhanVien,
            COALESCE(nv.ChucVu, '') as ChucVuNhanVien,
            CASE 
                WHEN bs.LoaiTask = 'lichlamviec' THEN COALESCE(llv.NhiemVu, 'Công việc không xác định')
                WHEN bs.LoaiTask = 'chitietkehoach' THEN COALESCE(ctk.TenBuoc, 'Công việc không xác định')
                ELSE 'Công việc không xác định'
            END as TenCongViec,
            COALESCE(dl1.TenSuKien, dl2.TenSuKien, 'N/A') as TenSuKien,
            COALESCE(dl1.NgayBatDau, dl2.NgayBatDau, NULL) as NgayBatDau,
            COALESCE(dl1.NgayKetThuc, dl2.NgayKetThuc, NULL) as NgayKetThuc
        FROM baocaosuco bs
        LEFT JOIN nhanvieninfo nv ON bs.ID_NhanVien = nv.ID_NhanVien
        LEFT JOIN lichlamviec llv ON bs.ID_Task = llv.ID_LLV AND bs.LoaiTask = 'lichlamviec'
        LEFT JOIN datlichsukien dl1 ON llv.ID_DatLich = dl1.ID_DatLich
        LEFT JOIN chitietkehoach ctk ON bs.ID_Task = ctk.ID_ChiTiet AND bs.LoaiTask = 'chitietkehoach'
        LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
        LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
        LEFT JOIN datlichsukien dl2 ON s.ID_DatLich = dl2.ID_DatLich
        ORDER BY bs.NgayBaoCao DESC
        LIMIT 50
    ");
    
    try {
        $stmt->execute();
        $issueReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("ERROR: manager-reports.php - Query failed: " . $e->getMessage());
        error_log("ERROR: manager-reports.php - Manager ID: " . $managerInfo['ID_NhanVien']);
        $issueReports = [];
    }
    
    // Debug: Log results
    error_log("DEBUG: manager-reports.php - Found " . count($issueReports) . " issue reports");
    if (!empty($issueReports)) {
        error_log("DEBUG: manager-reports.php - First report: " . json_encode($issueReports[0]));
    } else {
        // If no reports found, try a simpler query to see all reports
        $debugStmt = $pdo->prepare("SELECT ID_BaoCao, ID_NhanVien, ID_QuanLy, TieuDe, TrangThai FROM baocaosuco ORDER BY NgayBaoCao DESC LIMIT 5");
        $debugStmt->execute();
        $allReports = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("DEBUG: manager-reports.php - All reports in database: " . json_encode($allReports));
        error_log("DEBUG: manager-reports.php - Current manager ID: " . $managerInfo['ID_NhanVien']);
        
        // TEMPORARY: If no reports found for this manager, show all reports for debugging
        // Remove this after fixing the issue
        if (!empty($allReports) && $testResult['count'] == 0) {
            error_log("WARNING: manager-reports.php - No reports found for manager " . $managerInfo['ID_NhanVien'] . ", but there are reports in database");
            error_log("WARNING: manager-reports.php - Showing all reports for debugging");
            
            // Try to get all reports with basic info
            $fallbackStmt = $pdo->prepare("
                SELECT 
                    bs.ID_BaoCao,
                    bs.TieuDe,
                    bs.MoTa,
                    bs.MucDo,
                    bs.TrangThai,
                    bs.NgayBaoCao,
                    bs.NgayBaoCao as NgayCapNhat,
                    COALESCE(nv.HoTen, 'Không xác định') as TenNhanVien,
                    COALESCE(nv.ChucVu, '') as ChucVuNhanVien,
                    CASE 
                        WHEN bs.LoaiTask = 'lichlamviec' THEN COALESCE(llv.NhiemVu, 'Công việc không xác định')
                        WHEN bs.LoaiTask = 'chitietkehoach' THEN COALESCE(ctk.TenBuoc, 'Công việc không xác định')
                        ELSE 'Công việc không xác định'
                    END as TenCongViec,
                    COALESCE(dl1.TenSuKien, dl2.TenSuKien, 'N/A') as TenSuKien,
                    COALESCE(dl1.NgayBatDau, dl2.NgayBatDau, NULL) as NgayBatDau,
                    COALESCE(dl1.NgayKetThuc, dl2.NgayKetThuc, NULL) as NgayKetThuc
                FROM baocaosuco bs
                LEFT JOIN nhanvieninfo nv ON bs.ID_NhanVien = nv.ID_NhanVien
                LEFT JOIN lichlamviec llv ON bs.ID_Task = llv.ID_LLV AND bs.LoaiTask = 'lichlamviec'
                LEFT JOIN datlichsukien dl1 ON llv.ID_DatLich = dl1.ID_DatLich
                LEFT JOIN chitietkehoach ctk ON bs.ID_Task = ctk.ID_ChiTiet AND bs.LoaiTask = 'chitietkehoach'
                LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
                LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
                LEFT JOIN datlichsukien dl2 ON s.ID_DatLich = dl2.ID_DatLich
                ORDER BY bs.NgayBaoCao DESC
                LIMIT 50
            ");
            $fallbackStmt->execute();
            $issueReports = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("DEBUG: manager-reports.php - Fallback query returned " . count($issueReports) . " reports");
        }
    }
    
    // Lấy thống kê
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_progress_reports,
            COUNT(DISTINCT bct.ID_NhanVien) as total_staff_progress,
            COALESCE(AVG(bct.TienDo), 0) as avg_progress,
            SUM(CASE WHEN bct.TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_tasks
        FROM baocaotiendo bct
        WHERE bct.NgayBaoCao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $progressStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Set default values if no data
    if (!$progressStats) {
        $progressStats = [
            'total_progress_reports' => 0,
            'total_staff_progress' => 0,
            'avg_progress' => 0,
            'completed_tasks' => 0
        ];
    }
    
    // Lấy thống kê báo cáo sự cố
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_issue_reports,
            COUNT(DISTINCT bs.ID_NhanVien) as total_staff_issues,
            SUM(CASE WHEN bs.TrangThai = 'Mới' THEN 1 ELSE 0 END) as new_issues,
            SUM(CASE WHEN bs.TrangThai = 'Đang xử lý' THEN 1 ELSE 0 END) as in_progress_issues,
            SUM(CASE WHEN bs.TrangThai = 'Đã xử lý' THEN 1 ELSE 0 END) as resolved_issues,
            SUM(CASE WHEN bs.MucDo = 'Khẩn cấp' THEN 1 ELSE 0 END) as urgent_issues
        FROM baocaosuco bs
        WHERE bs.NgayBaoCao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $issueStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Set default values if no data
    if (!$issueStats) {
        $issueStats = [
            'total_issue_reports' => 0,
            'total_staff_issues' => 0,
            'new_issues' => 0,
            'in_progress_issues' => 0,
            'resolved_issues' => 0,
            'urgent_issues' => 0
        ];
    }
    
    $stats = array_merge($progressStats, $issueStats);
    
} catch (Exception $e) {
    $progressReports = [];
    $issueReports = [];
    $stats = [
        'total_progress_reports' => 0, 
        'total_staff_progress' => 0, 
        'avg_progress' => 0, 
        'completed_tasks' => 0,
        'total_issue_reports' => 0,
        'total_staff_issues' => 0,
        'new_issues' => 0,
        'in_progress_issues' => 0,
        'resolved_issues' => 0,
        'urgent_issues' => 0
    ];
    $managerInfo = ['HoTen' => 'Quản lý', 'ChucVu' => 'Manager'];
    error_log("Error fetching manager reports: " . $e->getMessage());
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
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-number"><?= $stats['total_progress_reports'] ?></div>
            <div class="stats-label">Báo cáo tiến độ</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-number text-danger"><?= $stats['total_issue_reports'] ?></div>
            <div class="stats-label">Báo cáo sự cố</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-number text-success"><?= $stats['total_staff_progress'] ?></div>
            <div class="stats-label">NV báo cáo tiến độ</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-number text-warning"><?= round($stats['avg_progress'], 1) ?>%</div>
            <div class="stats-label">Tiến độ trung bình</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-number text-info"><?= $stats['completed_tasks'] ?></div>
            <div class="stats-label">Công việc hoàn thành</div>
        </div>
    </div>
</div>

<!-- Progress Reports -->
<div class="row">
    <div class="col-12">
        <div class="report-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line text-primary"></i>
                    Báo cáo tiến độ từ nhân viên
                </h5>
            </div>
            
            <!-- Filter Section -->
            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Tìm kiếm</label>
                            <input type="text" id="progressSearchInput" class="form-control form-control-sm" placeholder="Nhân viên, công việc, sự kiện...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Trạng thái</label>
                            <select id="progressStatusFilter" class="form-select form-select-sm">
                                <option value="">Tất cả</option>
                                <option value="Hoàn thành">Hoàn thành</option>
                                <option value="Đang xử lý">Đang xử lý</option>
                                <option value="Chưa bắt đầu">Chưa bắt đầu</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Từ ngày</label>
                            <input type="date" id="progressDateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Đến ngày</label>
                            <input type="date" id="progressDateTo" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Tiến độ</label>
                            <select id="progressRangeFilter" class="form-select form-select-sm">
                                <option value="">Tất cả</option>
                                <option value="0">0%</option>
                                <option value="1-50">1-50%</option>
                                <option value="51-99">51-99%</option>
                                <option value="100">100%</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetProgressFilters()" title="Xóa bộ lọc">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ngày báo cáo</th>
                            <th>Nhân viên</th>
                            <th>Công việc</th>
                            <th>Sự kiện</th>
                            <th>Tiến độ</th>
                            <th>Trạng thái</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody id="progressReportsTableBody">
                        <?php if (empty($progressReports)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Chưa có báo cáo nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($progressReports as $report): ?>
                                <tr class="progress-report-row" 
                                    data-staff="<?= htmlspecialchars(strtolower($report['TenNhanVien'] ?? '')) ?>"
                                    data-task="<?= htmlspecialchars(strtolower($report['TenCongViec'] ?? '')) ?>"
                                    data-event="<?= htmlspecialchars(strtolower($report['TenSuKien'] ?? '')) ?>"
                                    data-status="<?= htmlspecialchars($report['TrangThai'] ?? '') ?>"
                                    data-progress="<?= $report['TienDo'] ?? 0 ?>"
                                    data-date="<?= date('Y-m-d', strtotime($report['NgayBaoCao'])) ?>">
                                    <td><?= date('d/m/Y H:i', strtotime($report['NgayBaoCao'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($report['TenNhanVien']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($report['ChucVuNhanVien']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($report['TenCongViec']) ?></td>
                                    <td><?= htmlspecialchars($report['TenSuKien'] ?? 'N/A') ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?= $report['TienDo'] ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $report['TienDo'] ?>%</small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $report['TrangThai'])) ?>">
                                            <?= htmlspecialchars($report['TrangThai']) ?>
                                        </span>
                                    </td>
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

<!-- Issue Reports -->
<div class="row mt-4">
    <div class="col-12">
        <div class="report-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                    Báo cáo sự cố từ nhân viên
                </h5>
            </div>
            
            <!-- Filter Section -->
            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Tìm kiếm</label>
                            <input type="text" id="issueSearchInput" class="form-control form-control-sm" placeholder="Nhân viên, công việc, sự kiện...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Trạng thái</label>
                            <select id="issueStatusFilter" class="form-select form-select-sm">
                                <option value="">Tất cả</option>
                                <option value="Mới">Mới</option>
                                <option value="Đang xử lý">Đang xử lý</option>
                                <option value="Đã xử lý">Đã xử lý</option>
                                <option value="Đã đóng">Đã đóng</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Mức độ</label>
                            <select id="issueSeverityFilter" class="form-select form-select-sm">
                                <option value="">Tất cả</option>
                                <option value="Thấp">Thấp</option>
                                <option value="Trung bình">Trung bình</option>
                                <option value="Cao">Cao</option>
                                <option value="Khẩn cấp">Khẩn cấp</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Từ ngày</label>
                            <input type="date" id="issueDateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Đến ngày</label>
                            <input type="date" id="issueDateTo" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetIssueFilters()" title="Xóa bộ lọc">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ngày báo cáo</th>
                            <th>Nhân viên</th>
                            <th>Công việc</th>
                            <th>Sự kiện</th>
                            <th>Tiêu đề</th>
                            <th>Mức độ</th>
                            <th>Trạng thái</th>
                            <th>Mô tả</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="issueReportsTableBody">
                        <?php if (empty($issueReports)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">Chưa có báo cáo sự cố nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($issueReports as $report): ?>
                                <tr class="issue-report-row"
                                    data-staff="<?= htmlspecialchars(strtolower($report['TenNhanVien'] ?? '')) ?>"
                                    data-task="<?= htmlspecialchars(strtolower($report['TenCongViec'] ?? '')) ?>"
                                    data-event="<?= htmlspecialchars(strtolower($report['TenSuKien'] ?? '')) ?>"
                                    data-status="<?= htmlspecialchars($report['TrangThai'] ?? '') ?>"
                                    data-severity="<?= htmlspecialchars($report['MucDo'] ?? '') ?>"
                                    data-date="<?= date('Y-m-d', strtotime($report['NgayBaoCao'])) ?>">
                                    <td><?= date('d/m/Y H:i', strtotime($report['NgayBaoCao'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($report['TenNhanVien']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($report['ChucVuNhanVien']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($report['TenCongViec']) ?></td>
                                    <td><?= htmlspecialchars($report['TenSuKien'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($report['TieuDe']) ?></td>
                                    <td>
                                        <?php
                                        $severityClass = '';
                                        switch($report['MucDo']) {
                                            case 'Khẩn cấp': $severityClass = 'bg-danger'; break;
                                            case 'Cao': $severityClass = 'bg-warning'; break;
                                            case 'Trung bình': $severityClass = 'bg-info'; break;
                                            case 'Thấp': $severityClass = 'bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="badge <?= $severityClass ?>"><?= htmlspecialchars($report['MucDo']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch($report['TrangThai']) {
                                            case 'Mới': $statusClass = 'bg-primary'; break;
                                            case 'Đang xử lý': $statusClass = 'bg-warning'; break;
                                            case 'Đã xử lý': $statusClass = 'bg-success'; break;
                                            case 'Đã đóng': $statusClass = 'bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($report['TrangThai']) ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($report['MoTa'])): ?>
                                            <button class="btn btn-sm btn-outline-info" onclick="showNote('<?= htmlspecialchars($report['MoTa']) ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($report['TrangThai'] == 'Mới'): ?>
                                            <button class="btn btn-sm btn-warning" onclick="updateIssueStatus(<?= $report['ID_BaoCao'] ?>, 'Đang xử lý')">
                                                <i class="fas fa-play"></i> Xử lý
                                            </button>
                                        <?php elseif ($report['TrangThai'] == 'Đang xử lý'): ?>
                                            <button class="btn btn-sm btn-success" onclick="updateIssueStatus(<?= $report['ID_BaoCao'] ?>, 'Đã xử lý')">
                                                <i class="fas fa-check"></i> Hoàn thành
                                            </button>
                                        <?php elseif ($report['TrangThai'] == 'Đã xử lý'): ?>
                                            <button class="btn btn-sm btn-secondary" onclick="updateIssueStatus(<?= $report['ID_BaoCao'] ?>, 'Đã đóng')">
                                                <i class="fas fa-times"></i> Đóng
                                            </button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        /* Remove modal backdrop completely */
        .modal-backdrop {
            display: none !important;
        }
        
        /* Ensure body doesn't get locked when modal is open */
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }
        
        /* Optional: Add a subtle overlay effect if you want some visual indication */
        .modal.show {
            background-color: rgba(0, 0, 0, 0.1);
        }
    </style>

    <script>
    // Ẩn overlay loading
    window.addEventListener('load', function() {
        const loadingOverlay = document.getElementById('pageLoading');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    });

    // Hàm hiển thị ghi chú
    function showNote(note) {
        document.getElementById('noteContent').textContent = note;
        new bootstrap.Modal(document.getElementById('noteModal')).show();
    }
    
    // Filter Progress Reports
    function filterProgressReports() {
        const searchTerm = document.getElementById('progressSearchInput').value.toLowerCase();
        const statusFilter = document.getElementById('progressStatusFilter').value;
        const dateFrom = document.getElementById('progressDateFrom').value;
        const dateTo = document.getElementById('progressDateTo').value;
        const progressRange = document.getElementById('progressRangeFilter').value;
        
        const rows = document.querySelectorAll('.progress-report-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const staff = row.getAttribute('data-staff') || '';
            const task = row.getAttribute('data-task') || '';
            const event = row.getAttribute('data-event') || '';
            const status = row.getAttribute('data-status') || '';
            const progress = parseInt(row.getAttribute('data-progress') || 0);
            const date = row.getAttribute('data-date') || '';
            
            // Search filter
            const matchesSearch = !searchTerm || 
                staff.includes(searchTerm) || 
                task.includes(searchTerm) || 
                event.includes(searchTerm);
            
            // Status filter
            const matchesStatus = !statusFilter || status === statusFilter;
            
            // Date filter
            const matchesDateFrom = !dateFrom || date >= dateFrom;
            const matchesDateTo = !dateTo || date <= dateTo;
            
            // Progress range filter
            let matchesProgress = true;
            if (progressRange) {
                if (progressRange === '0') {
                    matchesProgress = progress === 0;
                } else if (progressRange === '1-50') {
                    matchesProgress = progress >= 1 && progress <= 50;
                } else if (progressRange === '51-99') {
                    matchesProgress = progress >= 51 && progress <= 99;
                } else if (progressRange === '100') {
                    matchesProgress = progress === 100;
                }
            }
            
            if (matchesSearch && matchesStatus && matchesDateFrom && matchesDateTo && matchesProgress) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show message if no results
        const tbody = document.getElementById('progressReportsTableBody');
        let noResultsRow = tbody.querySelector('.no-results-message');
        if (visibleCount === 0 && rows.length > 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results-message';
                noResultsRow.innerHTML = '<td colspan="7" class="text-center text-muted">Không tìm thấy báo cáo nào phù hợp với bộ lọc</td>';
                tbody.appendChild(noResultsRow);
            }
            noResultsRow.style.display = '';
        } else if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    }
    
    // Filter Issue Reports
    function filterIssueReports() {
        const searchTerm = document.getElementById('issueSearchInput').value.toLowerCase();
        const statusFilter = document.getElementById('issueStatusFilter').value;
        const severityFilter = document.getElementById('issueSeverityFilter').value;
        const dateFrom = document.getElementById('issueDateFrom').value;
        const dateTo = document.getElementById('issueDateTo').value;
        
        const rows = document.querySelectorAll('.issue-report-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const staff = row.getAttribute('data-staff') || '';
            const task = row.getAttribute('data-task') || '';
            const event = row.getAttribute('data-event') || '';
            const status = row.getAttribute('data-status') || '';
            const severity = row.getAttribute('data-severity') || '';
            const date = row.getAttribute('data-date') || '';
            
            // Search filter
            const matchesSearch = !searchTerm || 
                staff.includes(searchTerm) || 
                task.includes(searchTerm) || 
                event.includes(searchTerm);
            
            // Status filter
            const matchesStatus = !statusFilter || status === statusFilter;
            
            // Severity filter
            const matchesSeverity = !severityFilter || severity === severityFilter;
            
            // Date filter
            const matchesDateFrom = !dateFrom || date >= dateFrom;
            const matchesDateTo = !dateTo || date <= dateTo;
            
            if (matchesSearch && matchesStatus && matchesSeverity && matchesDateFrom && matchesDateTo) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show message if no results
        const tbody = document.getElementById('issueReportsTableBody');
        let noResultsRow = tbody.querySelector('.no-results-message');
        if (visibleCount === 0 && rows.length > 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results-message';
                noResultsRow.innerHTML = '<td colspan="9" class="text-center text-muted">Không tìm thấy báo cáo nào phù hợp với bộ lọc</td>';
                tbody.appendChild(noResultsRow);
            }
            noResultsRow.style.display = '';
        } else if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    }
    
    // Reset Progress Filters
    function resetProgressFilters() {
        document.getElementById('progressSearchInput').value = '';
        document.getElementById('progressStatusFilter').value = '';
        document.getElementById('progressDateFrom').value = '';
        document.getElementById('progressDateTo').value = '';
        document.getElementById('progressRangeFilter').value = '';
        filterProgressReports();
    }
    
    // Reset Issue Filters
    function resetIssueFilters() {
        document.getElementById('issueSearchInput').value = '';
        document.getElementById('issueStatusFilter').value = '';
        document.getElementById('issueSeverityFilter').value = '';
        document.getElementById('issueDateFrom').value = '';
        document.getElementById('issueDateTo').value = '';
        filterIssueReports();
    }
    
    // Add event listeners when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Progress reports filters
        const progressSearch = document.getElementById('progressSearchInput');
        const progressStatus = document.getElementById('progressStatusFilter');
        const progressDateFrom = document.getElementById('progressDateFrom');
        const progressDateTo = document.getElementById('progressDateTo');
        const progressRange = document.getElementById('progressRangeFilter');
        
        if (progressSearch) progressSearch.addEventListener('input', filterProgressReports);
        if (progressStatus) progressStatus.addEventListener('change', filterProgressReports);
        if (progressDateFrom) progressDateFrom.addEventListener('change', filterProgressReports);
        if (progressDateTo) progressDateTo.addEventListener('change', filterProgressReports);
        if (progressRange) progressRange.addEventListener('change', filterProgressReports);
        
        // Issue reports filters
        const issueSearch = document.getElementById('issueSearchInput');
        const issueStatus = document.getElementById('issueStatusFilter');
        const issueSeverity = document.getElementById('issueSeverityFilter');
        const issueDateFrom = document.getElementById('issueDateFrom');
        const issueDateTo = document.getElementById('issueDateTo');
        
        if (issueSearch) issueSearch.addEventListener('input', filterIssueReports);
        if (issueStatus) issueStatus.addEventListener('change', filterIssueReports);
        if (issueSeverity) issueSeverity.addEventListener('change', filterIssueReports);
        if (issueDateFrom) issueDateFrom.addEventListener('change', filterIssueReports);
        if (issueDateTo) issueDateTo.addEventListener('change', filterIssueReports);
    });
    
    // Hàm cập nhật trạng thái sự cố
    function updateIssueStatus(reportId, newStatus) {
        if (!confirm(`Bạn có chắc muốn cập nhật trạng thái thành "${newStatus}"?`)) {
            return;
        }
        
        console.log('DEBUG: updateIssueStatus - reportId:', reportId, 'newStatus:', newStatus);
        
        const formData = new FormData();
        formData.append('action', 'update_report_status');
        formData.append('reportId', reportId);
        formData.append('status', newStatus);
        formData.append('reportType', 'issue');
        
        // Show loading
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        button.disabled = true;
        
        fetch('../src/controllers/manager-reports.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('DEBUG: updateIssueStatus - Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(text => {
            console.log('DEBUG: updateIssueStatus - Raw response:', text);
            try {
                const data = JSON.parse(text);
                console.log('DEBUG: updateIssueStatus - Parsed data:', data);
                
                if (data.success) {
                    alert('Cập nhật trạng thái thành công!');
                    location.reload(); // Reload page to show updated status
                } else {
                    alert('Lỗi: ' + (data.message || 'Unknown error'));
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            } catch (e) {
                console.error('DEBUG: updateIssueStatus - JSON parse error:', e);
                console.error('DEBUG: updateIssueStatus - Response text:', text);
                alert('Lỗi: Không thể phân tích phản hồi từ server. Response: ' + text.substring(0, 200));
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('DEBUG: updateIssueStatus - Error:', error);
            alert('Có lỗi xảy ra khi cập nhật trạng thái: ' + error.message);
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
</script>
</body>
</html>
