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

    // Lấy báo cáo tiến độ đã nhận
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
            dl.TenSuKien
        FROM baocaotiendo bct
        LEFT JOIN nhanvieninfo nv ON bct.ID_NhanVien = nv.ID_NhanVien
        LEFT JOIN lichlamviec llv ON bct.ID_Task = llv.ID_LLV AND bct.LoaiTask = 'lichlamviec'
        LEFT JOIN chitietkehoach ctk ON bct.ID_Task = ctk.ID_ChiTiet AND bct.LoaiTask = 'chitietkehoach'
        LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
        LEFT JOIN datlichsukien dl ON COALESCE(llv.ID_DatLich, kht.ID_DatLich) = dl.ID_DatLich
        WHERE bct.ID_QuanLy = ?
        ORDER BY bct.NgayBaoCao DESC
        LIMIT 50
    ");
    $stmt->execute([$managerInfo['ID_NhanVien']]);
    $progressReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy báo cáo sự cố đã nhận
    $stmt = $pdo->prepare("
        SELECT 
            bs.ID_BaoCao,
            bs.TieuDe,
            bs.MoTa,
            bs.MucDo,
            bs.TrangThai,
            bs.NgayBaoCao,
            bs.NgayCapNhat,
            nv.HoTen as TenNhanVien,
            nv.ChucVu as ChucVuNhanVien,
            CASE 
                WHEN bs.LoaiTask = 'lichlamviec' THEN llv.NhiemVu
                WHEN bs.LoaiTask = 'chitietkehoach' THEN ctk.TenBuoc
            END as TenCongViec,
            dl.TenSuKien
        FROM baocaosuco bs
        LEFT JOIN nhanvieninfo nv ON bs.ID_NhanVien = nv.ID_NhanVien
        LEFT JOIN lichlamviec llv ON bs.ID_Task = llv.ID_LLV AND bs.LoaiTask = 'lichlamviec'
        LEFT JOIN chitietkehoach ctk ON bs.ID_Task = ctk.ID_ChiTiet AND bs.LoaiTask = 'chitietkehoach'
        LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
        LEFT JOIN datlichsukien dl ON COALESCE(llv.ID_DatLich, kht.ID_DatLich) = dl.ID_DatLich
        WHERE bs.ID_QuanLy = ?
        ORDER BY bs.NgayBaoCao DESC
        LIMIT 50
    ");
    $stmt->execute([$managerInfo['ID_NhanVien']]);
    $issueReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy thống kê
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_progress_reports,
            COUNT(DISTINCT bct.ID_NhanVien) as total_staff_progress,
            AVG(bct.TienDo) as avg_progress,
            SUM(CASE WHEN bct.TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_tasks
        FROM baocaotiendo bct
        WHERE bct.ID_QuanLy = ?
        AND bct.NgayBaoCao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$managerInfo['ID_NhanVien']]);
    $progressStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
        WHERE bs.ID_QuanLy = ?
        AND bs.NgayBaoCao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$managerInfo['ID_NhanVien']]);
    $issueStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-number text-danger"><?= $stats['urgent_issues'] ?></div>
            <div class="stats-label">Sự cố khẩn cấp</div>
        </div>
    </div>
</div>

<!-- Progress Reports -->
<div class="row">
    <div class="col-12">
        <div class="report-card">
            <h5 class="mb-3">
                <i class="fas fa-chart-line text-primary"></i>
                Báo cáo tiến độ từ nhân viên
            </h5>
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
                    <tbody>
                        <?php if (empty($progressReports)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Chưa có báo cáo nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($progressReports as $report): ?>
                                <tr>
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
            <h5 class="mb-3">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                Báo cáo sự cố từ nhân viên
            </h5>
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
                    <tbody>
                        <?php if (empty($issueReports)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">Chưa có báo cáo sự cố nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($issueReports as $report): ?>
                                <tr>
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
    
    // Hàm cập nhật trạng thái sự cố
    function updateIssueStatus(reportId, newStatus) {
        if (!confirm(`Bạn có chắc muốn cập nhật trạng thái thành "${newStatus}"?`)) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'update_report_status');
        formData.append('reportId', reportId);
        formData.append('status', newStatus);
        formData.append('reportType', 'issue');
        
        fetch('../src/controllers/manager-reports.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cập nhật trạng thái thành công!');
                location.reload(); // Reload page to show updated status
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi cập nhật trạng thái');
        });
    }
</script>
</body>
</html>
