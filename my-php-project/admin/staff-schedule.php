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
    $userEmail = $_SESSION['user']['Email'] ?? null;
    
    error_log("DEBUG: User ID from session: " . $userId);
    error_log("DEBUG: User role from session: " . ($_SESSION['user']['ID_Role'] ?? 'N/A'));
    
    // Initialize default values
    $staffInfo = null;
    $assignments = [];
    $lichlamviec_assignments = [];
    $chitietkehoach_assignments = [];
    
    // Primary lookup by users.ID_User linkage
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
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $staffInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback lookup by users.Email if linkage is missing
    if (!$staffInfo && $userEmail) {
        error_log("DEBUG: Trying fallback lookup by email: " . $userEmail);
        $stmt = $pdo->prepare("
            SELECT 
                nv.ID_NhanVien,
                nv.HoTen,
                nv.ChucVu,
                nv.SoDienThoai,
                u.Email
            FROM nhanvieninfo nv
            JOIN users u ON nv.ID_User = u.ID_User
            WHERE u.Email = ?
            LIMIT 1
        ");
        $stmt->execute([$userEmail]);
        $staffInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($staffInfo) {
            error_log("DEBUG: Fallback lookup successful - Staff ID: " . $staffInfo['ID_NhanVien']);
        } else {
            error_log("DEBUG: Fallback lookup failed");
        }
    }
    
    if (!$staffInfo) {
        error_log("ERROR: Staff info not found for user ID: " . $userId);
        $staffInfo = ['ID_NhanVien' => null, 'HoTen' => 'Nhân viên', 'ChucVu' => 'Staff', 'Email' => ''];
    } else {
        error_log("DEBUG: Staff info found - ID: " . $staffInfo['ID_NhanVien'] . ", Name: " . $staffInfo['HoTen']);
    }
    
    // Assignments sẽ được load qua AJAX từ API
    $assignments = [];
    
} catch (Exception $e) {
    $staffInfo = ['ID_NhanVien' => null, 'HoTen' => 'Nhân viên', 'ChucVu' => 'Staff', 'Email' => ''];
    $assignments = [];
    error_log("Error fetching staff info: " . $e->getMessage());
    echo "<!-- Error: " . $e->getMessage() . " -->";
}
?>

    <style>
        /* Statistics Cards - Gọn gàng hơn */
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 18px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e9ecef;
            transition: box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stats-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 4px;
            font-weight: 500;
        }
        
        .stats-card .fas {
            font-size: 1.8rem;
            margin-bottom: 10px;
            opacity: 0.7;
        }
        
        /* Assignment Cards - Tối ưu spacing */
        .assignment-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            margin-bottom: 20px;
            transition: box-shadow 0.2s ease;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        
        .assignment-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .assignment-header {
            background: #f8f9fa;
            padding: 16px 20px;
            border-bottom: 1px solid #e9ecef;
            position: relative;
        }
        
        .assignment-header::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #667eea;
        }
        
        .assignment-header h5 {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .assignment-header h5 i {
            color: #667eea;
            margin-right: 6px;
            font-size: 0.95rem;
        }
        
        .assignment-header p {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 4px;
            line-height: 1.4;
        }
        
        .assignment-header p i {
            color: #667eea;
            margin-right: 5px;
            width: 14px;
            font-size: 0.8rem;
        }
        
        /* Status Badges - Nhỏ gọn hơn */
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
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
        
        /* Timeline - Bỏ vạch, thay bằng ô trắng */
        .timeline {
            position: relative;
            padding-left: 0;
        }
        
        .timeline::before {
            display: none;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            display: none;
        }
        
        /* Thay thế bằng ô trắng nhỏ bên trái card */
        .timeline-item .assignment-card {
            position: relative;
        }
        
        .timeline-item .assignment-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #e9ecef;
            border-radius: 12px 0 0 12px;
        }
        
        .timeline-item.completed .assignment-card::before {
            background: #28a745;
        }
        
        .timeline-item.in-progress .assignment-card::before {
            background: #ffc107;
        }
        
        .timeline-item.issue .assignment-card::before {
            background: #dc3545;
        }
        
        /* Hoặc nếu muốn bỏ hoàn toàn, dùng border-left thay thế */
        .timeline-item .assignment-header::before {
            width: 4px;
            background: #e9ecef;
        }
        
        .timeline-item.completed .assignment-header::before {
            background: #28a745;
        }
        
        .timeline-item.in-progress .assignment-header::before {
            background: #ffc107;
        }
        
        .timeline-item.issue .assignment-header::before {
            background: #dc3545;
        }
        
        /* Card Body - Gọn hơn */
        .card-body {
            padding: 18px 20px;
            background: #ffffff;
        }
        
        .card-body h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .card-body h6 i {
            color: #6c757d;
            margin-right: 5px;
            font-size: 0.85rem;
        }
        
        .card-body p {
            color: #6c757d;
            font-size: 0.85rem;
            line-height: 1.5;
            margin-bottom: 8px;
        }
        
        /* Alerts - Gọn hơn */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 0.85rem;
        }
        
        .alert.border-warning {
            border-left: 3px solid #ffc107 !important;
        }
        
        .alert.border-success {
            border-left: 3px solid #198754 !important;
        }
        
        .alert.border-danger {
            border-left: 3px solid #dc3545 !important;
        }
        
        .alert.border-secondary {
            border-left: 3px solid #6c757d !important;
        }
        
        /* Action Buttons - Gọn gàng */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
        }
        
        .action-buttons .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 6px 14px;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            min-width: auto;
        }
        
        .action-buttons .btn:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        
        .action-buttons .btn i {
            margin-right: 4px;
            font-size: 0.8rem;
        }
        
        /* Progress Bar - Gọn hơn */
        .progress {
            height: 18px;
            border-radius: 10px;
            background: #e9ecef;
        }
        
        .progress-bar {
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 18px;
        }
        
        /* Modal - Tối ưu */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom: none;
            padding: 16px 20px;
        }
        
        .modal-header .modal-title {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .modal-header .modal-title i {
            margin-right: 8px;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.8;
        }
        
        .modal-body {
            padding: 20px;
            background: #ffffff;
        }
        
        .modal-footer {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 12px 20px;
        }
        
        .modal-footer .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 6px 16px;
            font-size: 0.9rem;
        }
        
        /* Modal content styling */
        .modal-body .card {
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-bottom: 16px;
            box-shadow: none;
        }
        
        .modal-body .card-header {
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid #e9ecef;
            padding: 12px 16px;
            background: #f8f9fa;
        }
        
        .modal-body .card-body {
            padding: 16px;
        }
        
        .modal-body .card-header h6 {
            margin: 0;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .modal-body .card-header i {
            margin-right: 6px;
            font-size: 0.85rem;
        }
        
        .modal-body .badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
        
        /* Status indicators */
        .status-indicator {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Timer animation */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .status-working .badge {
            animation: pulse 2s infinite;
        }
        
        /* Filter Section - Gọn hơn */
        .card {
            border-radius: 10px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        
        .card-body {
            padding: 16px;
        }
        
        .form-label {
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 6px;
        }
        
        .form-control, .form-select {
            font-size: 0.9rem;
            padding: 8px 12px;
            border-radius: 6px;
        }
        
        /* Small text improvements */
        small {
            font-size: 0.8rem;
            line-height: 1.4;
        }
        
        /* Badge improvements */
        .badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            font-weight: 500;
        }
        
        /* Remove excessive spacing */
        .mb-4 {
            margin-bottom: 1.25rem !important;
        }
        
        .mb-3 {
            margin-bottom: 0.9rem !important;
        }
        
        .mb-2 {
            margin-bottom: 0.6rem !important;
        }
        
        .mt-3 {
            margin-top: 0.9rem !important;
        }
        
        .mt-2 {
            margin-top: 0.6rem !important;
        }
        
        .mt-1 {
            margin-top: 0.3rem !important;
        }
        
        /* Text improvements */
        h3 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        h5 {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        h6 {
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 16px;
        }
        
        .empty-state h4 {
            color: #6c757d;
            font-size: 1.2rem;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #adb5bd;
            font-size: 0.9rem;
        }
    </style>

    <!-- Statistics -->
    <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-tasks fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stats-number" id="statTotal">0</div>
                            <div class="stats-label">Tổng công việc</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock fa-2x text-secondary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stats-number" id="statNotStarted">0</div>
                            <div class="stats-label">Chưa bắt đầu</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-play fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stats-number" id="statInProgress">0</div>
                            <div class="stats-label">Đang làm việc</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stats-number" id="statCompleted">0</div>
                            <div class="stats-label">Hoàn thành</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stats-number" id="statIssue">0</div>
                            <div class="stats-label">Báo sự cố</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="searchInput" class="form-label small text-muted mb-1">
                                    <i class="fas fa-search"></i> Tìm kiếm
                                </label>
                                <input type="text" 
                                       id="searchInput" 
                                       class="form-control" 
                                       placeholder="Tìm theo tên công việc, sự kiện, địa điểm..."
                                       onkeyup="filterAssignments()">
                            </div>
                            <div class="col-md-3">
                                <label for="statusFilter" class="form-label small text-muted mb-1">
                                    <i class="fas fa-filter"></i> Lọc theo trạng thái
                                </label>
                                <select id="statusFilter" class="form-select" onchange="filterAssignments()">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="Chưa bắt đầu">Chưa bắt đầu</option>
                                    <option value="Đang thực hiện">Đang thực hiện</option>
                                    <option value="Hoàn thành">Hoàn thành</option>
                                    <option value="Báo sự cố">Báo sự cố</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="eventFilter" class="form-label small text-muted mb-1">
                                    <i class="fas fa-calendar"></i> Lọc theo sự kiện
                                </label>
                                <select id="eventFilter" class="form-select" onchange="filterAssignments()">
                                    <option value="">Tất cả sự kiện</option>
                                    <!-- Options sẽ được load từ JavaScript -->
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> Xóa bộ lọc
                                </button>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted" id="filterResultsCount">
                                    Hiển thị <strong>0</strong> công việc
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments List -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">
                        <i class="fas fa-tasks"></i>
                        Danh sách công việc được phân công
                    </h3>
                    <div class="d-flex gap-2">
                        
                        <button class="btn btn-outline-info btn-sm" onclick="viewAllAssignments()">
                            <i class="fas fa-eye"></i>
                            Xem tất cả
                        </button>
                    </div>
                </div>

                <!-- Loading state -->
                <div id="assignmentsLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-3 text-muted">Đang tải danh sách công việc...</p>
                </div>
                
                <!-- Empty state -->
                <div id="assignmentsEmpty" class="text-center py-5" style="display: none;">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h3>Chưa có công việc nào được phân công</h3>
                    <p class="text-muted">Các công việc được phân công sẽ hiển thị ở đây.</p>
                </div>
                
                <!-- Assignments container -->
                <div class="timeline" id="assignmentsContainer" style="display: none;">
                    <!-- Assignments sẽ được render từ JavaScript -->
                    <div class="timeline-item <?= strtolower(str_replace(' ', '-', $assignment['TrangThai'])) ?>">
                        <div class="assignment-card" 
                             data-assignment-id="<?= $assignment['ID_LLV'] ?>" 
                             data-source-table="<?= htmlspecialchars($assignment['source_table'] ?? 'lichlamviec') ?>"
                             data-event-name="<?= htmlspecialchars($assignment['TenSuKien'] ?? '') ?>"
                             data-task-name="<?= htmlspecialchars($assignment['NhiemVu'] ?? '') ?>"
                             data-location-name="<?= htmlspecialchars($assignment['TenDiaDiem'] ?? '') ?>"
                             data-status="<?= htmlspecialchars($assignment['TrangThai'] ?? '') ?>">
                            <div class="assignment-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-2">
                                            <i class="fas fa-tasks"></i>
                                            <?= htmlspecialchars($assignment['NhiemVu']) ?>
                                        </h5>
                                        <p class="mb-1 text-muted">
                                            <i class="fas fa-calendar"></i>
                                            <?= date('d/m/Y H:i', strtotime($assignment['NgayBatDau'])) ?> - 
                                            <?= date('d/m/Y H:i', strtotime($assignment['NgayKetThuc'])) ?>
                                        </p>
                                        <?php if ($assignment['HanHoanThanh']): ?>
                                        <p class="mb-1 text-muted">
                                            <i class="fas fa-clock"></i>
                                            Hạn hoàn thành: <?= date('d/m/Y', strtotime($assignment['HanHoanThanh'])) ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Progress Bar -->
                                <?php 
                                $tiendo = $assignment['Tiendo'] ?? '0';
                                // Remove % if exists and convert to number
                                $tiendoValue = intval(str_replace('%', '', $tiendo));
                                // Always show progress bar if there's a value (including 100%)
                                if ($tiendoValue >= 0): 
                                ?>
                                <div class="mt-3" id="progress-bar-<?= $assignment['ID_LLV'] ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">Tiến độ</small>
                                        <small class="text-muted"><strong><?= $tiendoValue ?>%</strong></small>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?= $tiendoValue >= 100 ? 'bg-success' : 'bg-primary' ?> progress-bar-striped <?= $tiendoValue < 100 ? 'progress-bar-animated' : '' ?>" 
                                             role="progressbar" 
                                             style="width: <?= $tiendoValue ?>%" 
                                             aria-valuenow="<?= $tiendoValue ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $tiendoValue ?>%
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body" 
                                 data-customer-name="<?= htmlspecialchars($assignment['TenKhachHang']) ?>"
                                 data-customer-phone="<?= htmlspecialchars($assignment['SoDienThoai']) ?>"
                                 data-customer-address="<?= htmlspecialchars($assignment['KhachHangDiaChi'] ?? '') ?>"
                                 data-event-name="<?= htmlspecialchars($assignment['TenSuKien']) ?>"
                                 data-event-type="<?= htmlspecialchars($assignment['TenLoaiSK'] ?? '') ?>"
                                 data-event-location="<?= htmlspecialchars($assignment['TenDiaDiem']) ?>"
                                 data-event-address="<?= htmlspecialchars($assignment['DiaChi']) ?>"
                                 data-event-start="<?= date('d/m/Y H:i', strtotime($assignment['EventStartDate'])) ?>"
                                 data-event-end="<?= date('d/m/Y H:i', strtotime($assignment['EventEndDate'])) ?>"
                                 data-event-attendees="<?= $assignment['SoNguoiDuKien'] ?? 0 ?>"
                                 data-event-budget="<?= $assignment['NganSach'] ?? 0 ?>"
                                 data-event-description="<?= htmlspecialchars($assignment['SuKienMoTa'] ?? '') ?>"
                                 data-event-note="<?= htmlspecialchars($assignment['SuKienGhiChu'] ?? '') ?>">
                                <!-- Assignment Details -->
                                <?php if ($assignment['GhiChu']): ?>
                                <div class="mb-3">
                                    <h6><i class="fas fa-sticky-note"></i> Ghi chú</h6>
                                    <p class="text-muted"><?= htmlspecialchars($assignment['GhiChu']) ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($assignment['kehoach_noidung']): ?>
                                <div class="mb-3">
                                    <h6><i class="fas fa-clipboard-list"></i> Nội dung kế hoạch</h6>
                                    <p class="text-muted"><?= htmlspecialchars($assignment['kehoach_noidung']) ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Timer and Progress Section -->
                                <?php if (empty($assignment['TrangThai']) || $assignment['TrangThai'] == 'Chưa bắt đầu' || $assignment['TrangThai'] == 'Chưa làm'): ?>
                                <div class="mb-3">
                                    <div class="alert alert-secondary border-secondary">
                                    <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-clock text-secondary"></i>
                                                <strong class="text-secondary">CHƯA BẮT ĐẦU</strong>
                                                <span class="badge bg-secondary text-white ms-2">
                                                    <i class="fas fa-hourglass-start"></i> 
                                                    Chờ bắt đầu
                                                </span>
                                            </div>
                                            <div>
                                                <span class="badge bg-secondary text-white">
                                                    <i class="fas fa-info-circle"></i> 
                                                    Sử dụng các nút bên dưới để cập nhật trạng thái
                                                </span>
                                            </div>
                                    </div>
                                </div>
                                <?php elseif ($assignment['TrangThai'] == 'Đang thực hiện' || $assignment['TrangThai'] == 'Đang làm'): ?>
                                <div class="mb-3">
                                    <div class="alert alert-warning border-warning status-working">
                                    <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-play-circle text-warning"></i>
                                                <strong class="text-warning">ĐANG LÀM VIỆC</strong>
                                                <span class="badge bg-warning text-dark ms-2">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php 
                                                    if (isset($assignment['ThoiGianBatDauThucTe']) && $assignment['ThoiGianBatDauThucTe']) {
                                                        $startTime = strtotime($assignment['ThoiGianBatDauThucTe']);
                                                        $currentTime = time();
                                                        $elapsedTime = $currentTime - $startTime;
                                                        $hours = floor($elapsedTime / 3600);
                                                        $minutes = floor(($elapsedTime % 3600) / 60);
                                                        echo $hours . 'h ' . $minutes . 'm';
                                                    } else {
                                                        echo 'Đang làm việc';
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Thời gian làm việc thực tế -->
                                    <?php if (isset($assignment['ThoiGianBatDauThucTe']) && $assignment['ThoiGianBatDauThucTe']): ?>
                                    <div class="mt-2">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-play text-success"></i>
                                                    <strong>Bắt đầu:</strong> <?= date('d/m/Y H:i', strtotime($assignment['ThoiGianBatDauThucTe'])) ?>
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock text-info"></i>
                                                    <strong>Đã làm:</strong> 
                                                    <?php
                                                    $startTime = strtotime($assignment['ThoiGianBatDauThucTe']);
                                                    $currentTime = time();
                                                    $workTime = $currentTime - $startTime;
                                                    $hours = floor($workTime / 3600);
                                                    $minutes = floor(($workTime % 3600) / 60);
                                                    echo $hours . 'h ' . $minutes . 'm';
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Tiến độ hiện tại -->
                                    <?php if (isset($assignment['TienDoPhanTram']) && $assignment['TienDoPhanTram'] > 0): ?>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">Tiến độ hiện tại</small>
                                            <small class="text-muted"><?= $assignment['TienDoPhanTram'] ?>%</small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-primary" style="width: <?= $assignment['TienDoPhanTram'] ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Ghi chú tiến độ -->
                                    <?php if (isset($assignment['GhiChuTienDo']) && $assignment['GhiChuTienDo']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-sticky-note"></i>
                                            <strong>Ghi chú:</strong> <?= htmlspecialchars($assignment['GhiChuTienDo']) ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php elseif ($assignment['TrangThai'] == 'Hoàn thành'): ?>
                                <div class="mb-3">
                                    <div class="alert alert-success border-success">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-check-circle text-success"></i>
                                                <strong class="text-success">ĐÃ HOÀN THÀNH</strong>
                                        <?php if (isset($assignment['ChamTienDo']) && $assignment['ChamTienDo']): ?>
                                                <span class="badge bg-warning text-dark ms-2">
                                                    <i class="fas fa-exclamation-triangle"></i> Chậm tiến độ
                                                </span>
                                                <?php else: ?>
                                                <span class="badge bg-success text-white ms-2">
                                                    <i class="fas fa-trophy"></i> Đúng hạn
                                                </span>
                                        <?php endif; ?>
                                            </div>
                                            <div>
                                                <button class="btn btn-outline-info btn-sm" onclick="viewTaskDetails(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>')">
                                                    <i class="fas fa-eye"></i>
                                                    Xem chi tiết
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Thời gian làm việc hoàn thành -->
                                    <?php if (isset($assignment['ThoiGianBatDauThucTe']) && $assignment['ThoiGianBatDauThucTe']): ?>
                                    <div class="mt-2">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-play text-success"></i>
                                                    <strong>Bắt đầu:</strong> <?= date('d/m/Y H:i', strtotime($assignment['ThoiGianBatDauThucTe'])) ?>
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-stop text-danger"></i>
                                                    <strong>Kết thúc:</strong> 
                                                    <?php if (isset($assignment['ThoiGianKetThucThucTe']) && $assignment['ThoiGianKetThucThucTe']): ?>
                                                        <?= date('d/m/Y H:i', strtotime($assignment['ThoiGianKetThucThucTe'])) ?>
                                                    <?php else: ?>
                                                        Chưa có
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Tổng thời gian làm việc -->
                                        <?php if (isset($assignment['ThoiGianKetThucThucTe']) && $assignment['ThoiGianKetThucThucTe']): ?>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <i class="fas fa-clock text-info"></i>
                                                <strong>Tổng thời gian:</strong> 
                                                <?php
                                                $startTime = strtotime($assignment['ThoiGianBatDauThucTe']);
                                                $endTime = strtotime($assignment['ThoiGianKetThucThucTe']);
                                                $totalTime = $endTime - $startTime;
                                                $hours = floor($totalTime / 3600);
                                                $minutes = floor(($totalTime % 3600) / 60);
                                                echo $hours . 'h ' . $minutes . 'm';
                                                ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Tiến độ cuối cùng -->
                                    <?php if (isset($assignment['TienDoPhanTram']) && $assignment['TienDoPhanTram'] > 0): ?>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">Tiến độ cuối cùng</small>
                                            <small class="text-muted"><?= $assignment['TienDoPhanTram'] ?>%</small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: <?= $assignment['TienDoPhanTram'] ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Ghi chú hoàn thành -->
                                    <?php if (isset($assignment['GhiChuTienDo']) && $assignment['GhiChuTienDo']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-sticky-note"></i>
                                            <strong>Ghi chú hoàn thành:</strong> <?= htmlspecialchars($assignment['GhiChuTienDo']) ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php elseif ($assignment['TrangThai'] == 'Báo sự cố'): ?>
                                <div class="mb-3">
                                    <?php 
                                    // Check if issue has been resolved
                                    $issueStatus = $assignment['IssueStatus'] ?? null;
                                    $isResolved = ($issueStatus === 'Đã xử lý' || $issueStatus === 'Đã đóng');
                                    ?>
                                    
                                    <?php if ($isResolved): ?>
                                    <!-- Issue has been resolved -->
                                    <div class="alert alert-success border-success">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-check-circle text-success"></i>
                                                <strong class="text-success">SỰ CỐ ĐÃ ĐƯỢC XỬ LÝ</strong>
                                                <span class="badge bg-success text-white ms-2">
                                                    <i class="fas fa-check"></i> 
                                                    <?= $issueStatus === 'Đã xử lý' ? 'Đã xử lý' : 'Đã đóng' ?>
                                                </span>
                                            </div>
                                            <div>
                                                <button class="btn btn-outline-info btn-sm" onclick="viewTaskDetails(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>')">
                                                    <i class="fas fa-eye"></i>
                                                    Xem chi tiết
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <!-- Issue still pending -->
                                    <div class="alert alert-danger border-danger">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-exclamation-triangle text-danger"></i>
                                                <strong class="text-danger">BÁO SỰ CỐ</strong>
                                                <span class="badge bg-danger text-white ms-2">
                                                    <i class="fas fa-warning"></i> 
                                                    Cần hỗ trợ
                                                </span>
                                            </div>
                                            <div>
                                                <button class="btn btn-outline-warning btn-sm" onclick="viewTaskDetails(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>')">
                                                    <i class="fas fa-eye"></i>
                                                    Xem chi tiết
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Thông tin sự cố -->
                                    <?php if (isset($assignment['GhiChuTienDo']) && $assignment['GhiChuTienDo']): ?>
                                    <div class="mt-2">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>Mô tả sự cố:</strong> <?= htmlspecialchars($assignment['GhiChuTienDo']) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="action-buttons">
                                    <?php if (empty($assignment['TrangThai']) || $assignment['TrangThai'] == 'Chưa bắt đầu' || $assignment['TrangThai'] == 'Chưa làm'): ?>
                                    <!-- Only show "Bắt đầu làm việc" button for empty, "Chưa bắt đầu" or "Chưa làm" status -->
                                    <?php
                                    // Kiểm tra ràng buộc thời gian: chỉ được bắt đầu trước giờ bắt đầu tối đa 5 phút
                                    $canStart = false;
                                    $minutesRemaining = 0;
                                    if (!empty($assignment['NgayBatDau'])) {
                                        $now = new DateTime();
                                        $startTime = new DateTime($assignment['NgayBatDau']);
                                        $earliestStart = clone $startTime;
                                        $earliestStart->modify('-5 minutes');
                                        
                                        $canStart = $now >= $earliestStart;
                                        if (!$canStart) {
                                            $diff = $now->diff($earliestStart);
                                            $minutesRemaining = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
                                        }
                                    }
                                    ?>
                                    <button class="btn <?= $canStart ? 'btn-primary' : 'btn-secondary' ?> btn-sm" 
                                            onclick="startWork(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>')"
                                            <?= !$canStart ? 'disabled' : '' ?>
                                            <?= !$canStart ? 'title="Chỉ được bắt đầu trước 5 phút kể từ giờ bắt đầu"' : '' ?>>
                                        <i class="fas fa-play"></i>
                                        Bắt đầu làm việc
                                    </button>
                                    <?php if (!$canStart): ?>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-info-circle"></i> 
                                        Chỉ được bắt đầu trước 5 phút kể từ giờ bắt đầu
                                    </small>
                                    <?php endif; ?>
                                    <?php elseif ($assignment['TrangThai'] == 'Hoàn thành'): ?>
                                    <!-- Hide all buttons when task is completed -->
                                    <div class="text-center text-muted">
                                        <i class="fas fa-check-circle text-success"></i>
                                        <small>Công việc đã hoàn thành</small>
                                    </div>
                                    <?php else: ?>
                                    <!-- Show buttons for "Đang làm" and "Báo sự cố" status -->
                                    <?php if ($assignment['TrangThai'] == 'Đang thực hiện' || $assignment['TrangThai'] == 'Đang làm'): ?>
                                    <button class="btn btn-info btn-sm" onclick="updateProgress(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>')">
                                        <i class="fas fa-percentage"></i>
                                        Cập nhật tiến độ
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($assignment['TrangThai'] != 'Hoàn thành'): ?>
                                    <button class="btn btn-success btn-sm" onclick="showCompleteWorkModal(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>', '<?= htmlspecialchars($assignment['NhiemVu'] ?? $assignment['TenBuoc'] ?? 'Công việc') ?>')">
                                        <i class="fas fa-check"></i>
                                        Hoàn thành & Báo cáo
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-danger btn-sm" onclick="reportIssue(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>')">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Báo sự cố
                                    </button>
                                    
                                    <button class="btn btn-outline-info btn-sm" onclick="viewTaskDetails(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?>')">
                                        <i class="fas fa-eye"></i>
                                        Chi tiết
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật trạng thái</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm">
                        <input type="hidden" id="assignmentId" name="assignmentId">
                        <input type="hidden" id="newStatus" name="newStatus">
                        <input type="hidden" id="sourceTable" name="sourceTable">
                        
                        <div class="mb-3">
                            <label for="progress" class="form-label">Tiến độ (%)</label>
                            <input type="number" class="form-control" id="progress" name="progress" min="0" max="100" value="0">
                        </div>
                        
                        <div class="mb-3">
                            <label for="note" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveStatusUpdate()">Lưu</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Report Issue Modal -->
    <div class="modal fade" id="reportIssueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Báo sự cố</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reportIssueForm">
                        <input type="hidden" id="issueAssignmentId" name="assignmentId">
                        
                        <div class="mb-3">
                            <label for="issueDescription" class="form-label">Mô tả sự cố *</label>
                            <textarea class="form-control" id="issueDescription" name="note" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" onclick="saveIssueReport()">Báo sự cố</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Start Work Modal -->
    <div class="modal fade" id="startWorkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-play"></i>
                        Bắt đầu làm việc
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="startWorkForm">
                        <input type="hidden" id="startWorkId" name="assignmentId">
                        <input type="hidden" id="startWorkTable" name="sourceTable">
                        
                        <div class="mb-3">
                            <label for="startWorkNote" class="form-label">Ghi chú bắt đầu</label>
                            <textarea class="form-control" id="startWorkNote" name="note" rows="3" placeholder="Mô tả công việc sẽ thực hiện..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Thời gian bắt đầu sẽ được ghi nhận tự động khi bạn nhấn "Bắt đầu".
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success" onclick="saveStartWork()">
                        <i class="fas fa-play"></i>
                        Bắt đầu làm việc
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Complete Work Modal -->
    <div class="modal fade" id="completeWorkModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle"></i>
                        Hoàn thành công việc & Báo cáo tiến độ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Thông báo:</strong> Khi hoàn thành công việc, bạn cần báo cáo tiến độ cho quản lý (Role 2).
                    </div>
                    
                    <form id="completeWorkForm">
                        <input type="hidden" id="completeWorkId" name="assignmentId">
                        <input type="hidden" id="completeWorkTable" name="sourceTable">
                        <input type="hidden" id="completeWorkTaskName" name="taskName">
                        
                        <div class="row">
                            <div class="col-md-6">
                        <div class="mb-3">
                                    <label for="finalProgress" class="form-label">
                                        <i class="fas fa-percentage text-success"></i>
                                        Tiến độ cuối cùng (%): <span class="text-danger">*</span>
                                    </label>
                            <input type="number" class="form-control" id="finalProgress" name="progress" min="0" max="100" value="100" required>
                                    <div class="form-text">Nhập phần trăm hoàn thành (thường là 100%)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="managerSelectComplete" class="form-label">
                                        <i class="fas fa-user-tie text-primary"></i>
                                        Chọn quản lý báo cáo: <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="managerSelectComplete" name="managerId" required>
                                        <option value="">-- Chọn quản lý --</option>
                                    </select>
                                    <div class="form-text">Chọn quản lý để gửi báo cáo tiến độ</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="completeWorkNote" class="form-label">
                                <i class="fas fa-comment-alt text-info"></i>
                                Ghi chú hoàn thành:
                            </label>
                            <textarea class="form-control" id="completeWorkNote" name="note" rows="4" placeholder="Mô tả chi tiết kết quả công việc, những gì đã hoàn thành, khó khăn gặp phải..."></textarea>
                            <div class="form-text">Mô tả chi tiết để quản lý hiểu rõ kết quả công việc</div>
                        </div>
                        
                        <div class="alert alert-warning" id="lateProgressAlert" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Cảnh báo:</strong> Công việc này đã quá hạn! Vui lòng giải thích lý do trong ghi chú.
                        </div>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Xác nhận:</strong> Sau khi hoàn thành, công việc sẽ được đánh dấu là "Hoàn thành" và báo cáo tiến độ sẽ được gửi đến quản lý.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                    <button type="button" class="btn btn-success" onclick="submitCompleteWorkWithReport()">
                        <i class="fas fa-check"></i>
                        Hoàn thành & Báo cáo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Details Modal -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tasks"></i>
                        Chi tiết công việc
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="taskDetailsContent">
                        <!-- Task details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-check"></i>
                        Chi tiết sự kiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="eventDetailsContent">
                        <!-- Event details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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
        function updateStatus(assignmentId, status, sourceTable = 'lichlamviec') {
            try {
                document.getElementById('assignmentId').value = assignmentId;
                document.getElementById('newStatus').value = status;
                document.getElementById('sourceTable').value = sourceTable;
                
                const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening update status modal:', error);
                alert('Có lỗi xảy ra khi mở modal cập nhật trạng thái');
            }
        }

        // Helper function: Kiểm tra xem có thể bắt đầu task hay không (chỉ được bắt đầu trước giờ bắt đầu tối đa 5 phút)
        function canStartTask(startTime) {
            if (!startTime) return false;
            
            const now = new Date();
            const start = new Date(startTime);
            const earliestStart = new Date(start);
            earliestStart.setMinutes(earliestStart.getMinutes() - 5); // Trừ 5 phút
            
            // Chỉ được bắt đầu từ (giờ bắt đầu - 5 phút) trở đi
            // Ví dụ: task bắt đầu lúc 05:00 → chỉ được bắt đầu từ 04:55 trở đi
            return now >= earliestStart;
        }
        
        // Helper function: Tính số phút còn lại trước khi có thể bắt đầu
        function getMinutesUntilCanStart(startTime) {
            if (!startTime) return 0;
            
            const now = new Date();
            const start = new Date(startTime);
            const earliestStart = new Date(start);
            earliestStart.setMinutes(earliestStart.getMinutes() - 5);
            
            if (now >= earliestStart) return 0;
            
            const diff = earliestStart.getTime() - now.getTime();
            const minutes = Math.ceil(diff / (1000 * 60)); // Chuyển từ milliseconds sang phút
            
            // Giới hạn tối đa hiển thị là 1440 phút (24 giờ) để tránh hiển thị số quá lớn
            return minutes > 1440 ? 1440 : minutes;
        }
        
        function startWork(assignmentId, sourceTable) {
            try {
                // Tìm assignment card để lấy thông tin thời gian
                const card = document.querySelector(`.assignment-card[data-assignment-id="${assignmentId}"]`);
                if (!card) {
                    alert('Không tìm thấy thông tin công việc');
                    return;
                }
                
                // Lấy thời gian bắt đầu từ assignment data
                const assignment = allAssignments.find(a => a.ID_LLV == assignmentId && a.source_table === sourceTable);
                if (!assignment || !assignment.NgayBatDau) {
                    alert('Không tìm thấy thông tin thời gian bắt đầu');
                    return;
                }
                
                // Kiểm tra ràng buộc thời gian: chỉ được bắt đầu trước giờ bắt đầu tối đa 5 phút
                if (!canStartTask(assignment.NgayBatDau)) {
                    alert('Chỉ được bắt đầu trước 5 phút kể từ giờ bắt đầu.');
                    return;
                }
                
                document.getElementById('startWorkId').value = assignmentId;
                document.getElementById('startWorkTable').value = sourceTable;
                
                const modal = new bootstrap.Modal(document.getElementById('startWorkModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening start work modal:', error);
                alert('Có lỗi xảy ra khi mở modal bắt đầu làm việc');
            }
        }


        function showCompleteWorkModal(assignmentId, sourceTable, taskName) {
            try {
                console.log('=== SHOW COMPLETE WORK MODAL DEBUG ===');
                console.log('assignmentId:', assignmentId);
                console.log('sourceTable:', sourceTable);
                console.log('taskName:', taskName);
                
                // Set form values
                document.getElementById('completeWorkId').value = assignmentId;
                document.getElementById('completeWorkTable').value = sourceTable;
                document.getElementById('completeWorkTaskName').value = taskName;
                
                // Load managers for progress report
                loadManagersForCompleteWork();
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('completeWorkModal'));
                modal.show();
                
            } catch (error) {
                console.error('Error opening complete work modal:', error);
                alert('Có lỗi xảy ra khi mở modal hoàn thành công việc');
            }
        }
        
        function loadManagersForCompleteWork() {
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
                    const select = document.getElementById('managerSelectComplete');
                    select.innerHTML = '<option value="">-- Chọn quản lý --</option>';
                    data.managers.forEach(manager => {
                        const option = document.createElement('option');
                        option.value = manager.ID_NhanVien;
                        option.textContent = `${manager.HoTen} (${manager.ChucVu})`;
                        select.appendChild(option);
                    });
                } else {
                    console.error('Error loading managers:', data.message);
                    alert('Lỗi khi tải danh sách quản lý: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error loading managers:', error);
                alert('Có lỗi xảy ra khi tải danh sách quản lý');
            });
        }
        
        function submitCompleteWorkWithReport() {
            try {
                const assignmentId = document.getElementById('completeWorkId').value;
                const sourceTable = document.getElementById('completeWorkTable').value;
                const taskName = document.getElementById('completeWorkTaskName').value;
                const progress = document.getElementById('finalProgress').value;
                const managerId = document.getElementById('managerSelectComplete').value;
                const note = document.getElementById('completeWorkNote').value;
                
                if (!assignmentId || !sourceTable || !progress || !managerId) {
                    alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                    return;
                }
                
                // Show loading
                const submitBtn = document.querySelector('#completeWorkModal .btn-success');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                submitBtn.disabled = true;
                
                // First complete the work
                fetch('../src/controllers/staff-schedule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=complete_work&assignmentId=${assignmentId}&sourceTable=${sourceTable}&progress=${progress}&note=${encodeURIComponent(note)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Then submit progress report
                        const formData = new FormData();
                        formData.append('action', 'submit_progress_report');
                        formData.append('manager_id', managerId);
                        formData.append('task_id', assignmentId);
                        formData.append('task_type', sourceTable);
                        formData.append('progress', progress);
                        formData.append('status', 'Hoàn thành');
                        formData.append('notes', note);
                        
                        return fetch('../src/controllers/staff-reports.php', {
                            method: 'POST',
                            body: formData
                        });
                    } else {
                        throw new Error(data.message || 'Lỗi khi hoàn thành công việc');
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Hoàn thành công việc và báo cáo tiến độ thành công!');
                        bootstrap.Modal.getInstance(document.getElementById('completeWorkModal')).hide();
                        // Reload assignments từ API
                        loadAssignments();
                    } else {
                        throw new Error(data.message || 'Lỗi khi gửi báo cáo tiến độ');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra: ' + error.message);
                })
                .finally(() => {
                    // Restore button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
                
            } catch (error) {
                console.error('Error submitting complete work with report:', error);
                alert('Có lỗi xảy ra khi hoàn thành công việc');
            }
        }

        function completeWork(assignmentId, sourceTable) {
            try {
                console.log('=== COMPLETE WORK DEBUG ===');
                console.log('assignmentId:', assignmentId);
                console.log('sourceTable:', sourceTable);
                
                const completeWorkId = document.getElementById('completeWorkId');
                const completeWorkTable = document.getElementById('completeWorkTable');
                
                console.log('completeWorkId element:', completeWorkId);
                console.log('completeWorkTable element:', completeWorkTable);
                
                if (!completeWorkId || !completeWorkTable) {
                    console.error('Modal elements not found!');
                    alert('Lỗi: Không tìm thấy các trường form');
                    return;
                }
                
                completeWorkId.value = assignmentId;
                completeWorkTable.value = sourceTable;
                
                console.log('Set values - completeWorkId.value:', completeWorkId.value);
                console.log('Set values - completeWorkTable.value:', completeWorkTable.value);
                
                // Check if late progress
                checkLateProgress(assignmentId, sourceTable);
                
                const modal = new bootstrap.Modal(document.getElementById('completeWorkModal'));
                modal.show();
                
                console.log('Modal opened successfully');
            } catch (error) {
                console.error('Error opening complete work modal:', error);
                alert('Có lỗi xảy ra khi mở modal hoàn thành công việc: ' + error.message);
            }
        }

        function reportIssue(assignmentId, sourceTable) {
            try {
                document.getElementById('issueAssignmentId').value = assignmentId;
                
                // Store sourceTable for later use
                document.getElementById('issueAssignmentId').setAttribute('data-source-table', sourceTable);
                
                const modal = new bootstrap.Modal(document.getElementById('reportIssueModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening report issue modal:', error);
                alert('Có lỗi xảy ra khi mở modal báo sự cố');
            }
        }

        function saveStatusUpdate() {
            try {
                const form = document.getElementById('updateStatusForm');
                const formData = new FormData(form);
                formData.append('action', 'update_assignment_status');
                
                fetch('../src/controllers/staff-schedule.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Cập nhật trạng thái thành công');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi cập nhật trạng thái: ' + error.message);
                });
            } catch (error) {
                console.error('Error in saveStatusUpdate:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái');
            }
        }

        function saveStartWork() {
            try {
            const form = document.getElementById('startWorkForm');
                if (!form) {
                    alert('Không tìm thấy form bắt đầu làm việc');
                    return;
                }
                
                // Get assignment ID and source table from form
                const assignmentId = document.getElementById('startWorkId').value;
                const sourceTable = document.getElementById('startWorkTable').value;
                
                console.log('=== SAVE START WORK DEBUG ===');
                console.log('assignmentId:', assignmentId);
                console.log('sourceTable:', sourceTable);
                
            const formData = new FormData(form);
            formData.append('action', 'start_work');
            
                // Debug logs
                console.log('Form data:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }
                
                fetch('../src/controllers/staff-schedule.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response URL:', response.url);
                    return response.text(); // Get raw response first
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                        
                if (data.success) {
                    alert('Bắt đầu làm việc thành công');
                            bootstrap.Modal.getInstance(document.getElementById('startWorkModal')).hide();
                            
                            // Reload assignments từ API
                            loadAssignments();
                } else {
                    alert('Lỗi: ' + data.message);
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Raw response was:', text);
                        alert('Lỗi: Không thể phân tích phản hồi từ server');
                }
            })
            .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Có lỗi xảy ra khi bắt đầu làm việc: ' + error.message);
                });
            } catch (error) {
                console.error('Error in saveStartWork:', error);
                alert('Có lỗi xảy ra khi bắt đầu làm việc');
            }
        }

        function updateTaskStatusAfterStart(assignmentId, sourceTable) {
            try {
                console.log('=== UPDATE TASK STATUS AFTER START ===');
                console.log('assignmentId:', assignmentId);
                console.log('sourceTable:', sourceTable);
                
                // Find the task card by assignment ID
                const taskCards = document.querySelectorAll('.timeline-item');
                let targetCard = null;
                
                console.log('Total task cards found:', taskCards.length);
                
                // Method 1: Look for buttons with startWork onclick
                for (let i = 0; i < taskCards.length; i++) {
                    const card = taskCards[i];
                    const buttons = card.querySelectorAll('button[onclick*="startWork"]');
                    console.log(`Card ${i}: Found ${buttons.length} startWork buttons`);
                    
                    for (let j = 0; j < buttons.length; j++) {
                        const button = buttons[j];
                        const onclickAttr = button.getAttribute('onclick');
                        console.log(`Button ${j} onclick:`, onclickAttr);
                        
                        if (onclickAttr && onclickAttr.includes(assignmentId)) {
                            targetCard = card;
                            console.log('Found target card by startWork button!');
                            break;
                        }
                    }
                    if (targetCard) break;
                }
                
                // Method 2: If not found, look for any button with assignmentId
                if (!targetCard) {
                    console.log('Method 1 failed, trying Method 2...');
                    for (let i = 0; i < taskCards.length; i++) {
                        const card = taskCards[i];
                        const buttons = card.querySelectorAll('button[onclick*="' + assignmentId + '"]');
                        console.log(`Card ${i}: Found ${buttons.length} buttons with assignmentId`);
                        
                        if (buttons.length > 0) {
                            targetCard = card;
                            console.log('Found target card by assignmentId!');
                            break;
                        }
                    }
                }
                
                if (!targetCard) {
                    console.error('Could not find task card for assignment ID:', assignmentId);
                    console.log('Available task cards:', Array.from(taskCards).map(card => {
                        const buttons = card.querySelectorAll('button[onclick*="startWork"]');
                        return Array.from(buttons).map(btn => btn.getAttribute('onclick'));
                    }));
                    return;
                }
                
                // Update status badge
                const statusBadge = targetCard.querySelector('.status-badge');
                if (statusBadge) {
                    console.log('Updating status badge from:', statusBadge.textContent, 'to: Đang làm');
                    statusBadge.textContent = 'Đang làm';
                    // Update CSS class to match the new status
                    statusBadge.className = 'status-badge status-in-progress';
                } else {
                    console.error('Could not find status badge');
                }
                
                // Update status alert section
                const statusAlert = targetCard.querySelector('.alert');
                if (statusAlert) {
                    statusAlert.className = 'alert alert-warning border-warning status-working';
                    statusAlert.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-play-circle text-warning"></i>
                                <strong class="text-warning">ĐANG LÀM VIỆC</strong>
                                <span class="badge bg-warning text-dark ms-2">
                                    <i class="fas fa-clock"></i> 
                                    Bắt đầu làm việc
                                </span>
                            </div>
                        </div>
                    `;
                }
                
                // Update action buttons
                const actionButtons = targetCard.querySelector('.action-buttons');
                if (actionButtons) {
                    console.log('Found action buttons container');
                    console.log('Current action buttons HTML:', actionButtons.innerHTML);
                    console.log('Updating action buttons for assignment:', assignmentId, 'sourceTable:', sourceTable);
                    
                    actionButtons.innerHTML = `
                        <button class="btn btn-info btn-sm" onclick="updateProgress(${assignmentId}, '${sourceTable}')">
                            <i class="fas fa-percentage"></i>
                            Cập nhật tiến độ
                        </button>
                        
                        <button class="btn btn-success btn-sm" onclick="showCompleteWorkModal(${assignmentId}, '${sourceTable}', 'Công việc')">
                            <i class="fas fa-check"></i>
                            Hoàn thành & Báo cáo
                        </button>
                        
                        <button class="btn btn-danger btn-sm" onclick="reportIssue(${assignmentId}, '${sourceTable}')">
                            <i class="fas fa-exclamation-triangle"></i>
                            Báo sự cố
                        </button>
                        
                        <button class="btn btn-outline-info btn-sm" onclick="viewTaskDetails(${assignmentId}, '${sourceTable}')">
                            <i class="fas fa-eye"></i>
                            Chi tiết
                        </button>
                    `;
                    
                    // Remove debug alert if exists
                    const debugAlert = actionButtons.querySelector('.alert-info');
                    if (debugAlert) {
                        debugAlert.remove();
                    }
                    
                    console.log('Action buttons updated successfully');
                    console.log('New action buttons HTML:', actionButtons.innerHTML);
                } else {
                    console.error('Could not find action buttons container');
                }
                
                // Update statistics
                updateStatistics();
                
                console.log('Task status updated successfully for assignment:', assignmentId);
                
            } catch (error) {
                console.error('Error updating task status:', error);
                // Fallback to reload if dynamic update fails
                location.reload();
            }
        }
        
        function updateStatistics() {
            try {
                // Count tasks by status
                const taskCards = document.querySelectorAll('.timeline-item');
                let notStarted = 0;
                let inProgress = 0;
                let completed = 0;
                let reported = 0;
                
                taskCards.forEach(card => {
                    const statusBadge = card.querySelector('.status-badge');
                    if (statusBadge) {
                        const status = statusBadge.textContent.trim();
                        if (status === '' || status === 'Chưa bắt đầu' || status === 'Chưa làm') {
                            notStarted++;
                        } else if (status === 'Đang làm' || status === 'Đang thực hiện') {
                            inProgress++;
                        } else if (status === 'Hoàn thành') {
                            completed++;
                        } else if (status === 'Báo sự cố') {
                            reported++;
                        }
                    }
                });
                
                // Update statistics display
                const statsElements = document.querySelectorAll('.stats-number');
                if (statsElements.length >= 4) {
                    statsElements[0].textContent = notStarted;
                    statsElements[1].textContent = inProgress;
                    statsElements[2].textContent = completed;
                    statsElements[3].textContent = reported;
                }
                
            } catch (error) {
                console.error('Error updating statistics:', error);
            }
        }


        function saveCompleteWork() {
            const form = document.getElementById('completeWorkForm');
            const formData = new FormData(form);
            formData.append('action', 'complete_work');
            
            // Debug logs
            console.log('=== SAVE COMPLETE WORK DEBUG ===');
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            fetch('../src/controllers/staff-schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text(); // Get raw response first
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    if (data.success) {
                        alert('Hoàn thành công việc thành công');
                        // Reload assignments từ API
                        loadAssignments();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw response was:', text);
                    alert('Lỗi: Không thể phân tích phản hồi từ server');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Có lỗi xảy ra khi hoàn thành công việc: ' + error.message);
            });
        }

        function checkLateProgress(assignmentId, sourceTable) {
            // This function will check if the work is being completed late
            // For now, we'll show the alert if the current time is past the deadline
            const now = new Date();
            const deadlineElements = document.querySelectorAll(`[data-assignment-id="${assignmentId}"] .deadline`);
            
            if (deadlineElements.length > 0) {
                const deadlineText = deadlineElements[0].textContent;
                const deadlineDate = new Date(deadlineText.split(': ')[1]);
                
                if (now > deadlineDate) {
                    document.getElementById('lateProgressAlert').style.display = 'block';
                }
            }
        }

        function saveIssueReport() {
            try {
            const form = document.getElementById('reportIssueForm');
            const formData = new FormData(form);
            formData.append('action', 'report_issue');
            
                // Get sourceTable from stored attribute
                const assignmentIdElement = document.getElementById('issueAssignmentId');
                const sourceTable = assignmentIdElement.getAttribute('data-source-table') || 'lichlamviec';
                formData.append('sourceTable', sourceTable);
                
                console.log('=== SAVE ISSUE REPORT DEBUG ===');
                console.log('assignmentId:', assignmentIdElement.value);
                console.log('sourceTable:', sourceTable);
                console.log('note:', formData.get('note'));
                
                fetch('../src/controllers/staff-schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Báo sự cố thành công');
                        bootstrap.Modal.getInstance(document.getElementById('reportIssueModal')).hide();
                    // Reload assignments từ API
                    loadAssignments();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                    alert('Có lỗi xảy ra khi báo sự cố: ' + error.message);
                });
            } catch (error) {
                console.error('Error in saveIssueReport:', error);
                alert('Có lỗi xảy ra khi báo sự cố');
            }
        }

        function viewTaskDetails(assignmentId, sourceTable) {
            console.log('=== VIEW TASK DETAILS DEBUG ===');
            console.log('assignmentId:', assignmentId);
            console.log('sourceTable:', sourceTable);
            
            // Get assignment data from current page
            const assignment = getAssignmentData(assignmentId, sourceTable);
            
            if (!assignment) {
                alert('Không tìm thấy thông tin công việc');
                return;
            }
            
            // Show loading in modal
            document.getElementById('taskDetailsContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải chi tiết công việc...</p>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
            modal.show();
            
            // Display task details
            displayTaskDetails(assignment);
        }
        
        function getAssignmentData(assignmentId, sourceTable) {
            // Find assignment data from current page
            const assignmentCards = document.querySelectorAll('.assignment-card');
            for (let card of assignmentCards) {
                const buttons = card.querySelectorAll('button[onclick*="' + assignmentId + '"]');
                if (buttons.length > 0) {
                    // Extract data from the card
                    const title = card.querySelector('h5').textContent.trim();
                    const timeInfo = card.querySelectorAll('p');
                    const startTime = timeInfo[0] ? timeInfo[0].textContent.replace(/.*?(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}).*/, '$1') : '';
                    const endTime = timeInfo[0] ? timeInfo[0].textContent.replace(/.*?(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})$/, '$1') : '';
                    const deadline = timeInfo[1] ? timeInfo[1].textContent.replace(/.*?(\d{2}\/\d{2}\/\d{4})$/, '$1') : '';
                    
                    // Get data from data attributes
                    const cardBody = card.querySelector('.card-body');
                    const customerName = cardBody?.getAttribute('data-customer-name') || '';
                    const customerPhone = cardBody?.getAttribute('data-customer-phone') || '';
                    const customerAddress = cardBody?.getAttribute('data-customer-address') || '';
                    const eventName = cardBody?.getAttribute('data-event-name') || '';
                    const eventType = cardBody?.getAttribute('data-event-type') || '';
                    const eventLocation = cardBody?.getAttribute('data-event-location') || '';
                    const eventAddress = cardBody?.getAttribute('data-event-address') || '';
                    const eventStart = cardBody?.getAttribute('data-event-start') || '';
                    const eventEnd = cardBody?.getAttribute('data-event-end') || '';
                    const eventAttendees = cardBody?.getAttribute('data-event-attendees') || '0';
                    const eventBudget = cardBody?.getAttribute('data-event-budget') || '0';
                    const eventDescription = cardBody?.getAttribute('data-event-description') || '';
                    const eventNote = cardBody?.getAttribute('data-event-note') || '';
                    
                    // Get plan content
                    const planContent = card.querySelector('.card-body p')?.textContent || '';
                    
                    return {
                        id: assignmentId,
                        title: title,
                        startTime: startTime,
                        endTime: endTime,
                        deadline: deadline,
                        customerName: customerName,
                        customerPhone: customerPhone,
                        customerAddress: customerAddress,
                        eventName: eventName,
                        eventType: eventType,
                        eventLocation: eventLocation,
                        eventAddress: eventAddress,
                        eventStart: eventStart,
                        eventEnd: eventEnd,
                        eventAttendees: eventAttendees,
                        eventBudget: eventBudget,
                        eventDescription: eventDescription,
                        eventNote: eventNote,
                        planContent: planContent,
                        sourceTable: sourceTable
                    };
                }
            }
            return null;
        }
        
        function displayTaskDetails(assignment) {
            // Format budget
            const formatBudget = (budget) => {
                if (!budget || budget === '0') return '';
                const num = parseInt(budget);
                return num.toLocaleString('vi-VN') + ' VNĐ';
            };
            
            // Format attendees
            const formatAttendees = (attendees) => {
                if (!attendees || attendees === '0') return '';
                return parseInt(attendees).toLocaleString('vi-VN') + ' người';
            };
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-tasks"></i>
                                    Thông tin công việc
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Tên công việc:</strong> ${assignment.title}</p>
                                <p><strong>Thời gian bắt đầu:</strong> ${assignment.startTime}</p>
                                <p><strong>Thời gian kết thúc:</strong> ${assignment.endTime}</p>
                                <p class="mb-0"><strong>Hạn hoàn thành:</strong> ${assignment.deadline}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-user"></i>
                                    Thông tin khách hàng
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Tên khách hàng:</strong> ${assignment.customerName || 'Không xác định'}</p>
                                <p><strong>Số điện thoại:</strong> ${assignment.customerPhone || 'Không xác định'}</p>
                                ${assignment.customerAddress ? `<p class="mb-0"><strong>Địa chỉ:</strong> ${assignment.customerAddress}</p>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar-check"></i>
                                    Thông tin sự kiện
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Sự kiện:</strong> ${assignment.eventName || 'Không xác định'}</p>
                                ${assignment.eventType && assignment.eventType !== 'Không xác định' ? `
                                    <p><strong>Loại sự kiện:</strong> 
                                        <span class="badge bg-primary">${assignment.eventType}</span>
                                    </p>
                                ` : ''}
                                <p><strong>Địa điểm:</strong> ${assignment.eventLocation || 'Không xác định'}</p>
                                <p><strong>Địa chỉ:</strong> ${assignment.eventAddress || 'Không xác định'}</p>
                                ${assignment.eventStart ? `
                                    <p><strong>Thời gian sự kiện:</strong> ${assignment.eventStart} - ${assignment.eventEnd}</p>
                                ` : ''}
                                ${formatAttendees(assignment.eventAttendees) ? `
                                    <p><strong>Số người dự kiến:</strong> ${formatAttendees(assignment.eventAttendees)}</p>
                                ` : ''}
                                ${formatBudget(assignment.eventBudget) ? `
                                    <p><strong>Ngân sách:</strong> ${formatBudget(assignment.eventBudget)}</p>
                                ` : ''}
                                ${assignment.eventDescription ? `
                                    <p><strong>Mô tả:</strong> ${assignment.eventDescription}</p>
                                ` : ''}
                                ${assignment.eventNote ? `
                                    <p class="mb-0"><strong>Ghi chú:</strong> ${assignment.eventNote}</p>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
                ${assignment.planContent ? `
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-clipboard-list"></i>
                            Nội dung kế hoạch
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">${assignment.planContent}</p>
                    </div>
                </div>
                ` : ''}
                
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle"></i>
                            Hướng dẫn thực hiện
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb"></i> Lưu ý quan trọng:</h6>
                            <ul class="mb-0">
                                <li>Kiểm tra kỹ thời gian và địa điểm trước khi bắt đầu</li>
                                <li>Chuẩn bị đầy đủ thiết bị và dụng cụ cần thiết</li>
                                <li>Liên hệ với khách hàng nếu có thay đổi</li>
                                <li>Cập nhật tiến độ thường xuyên</li>
                                <li>Báo cáo ngay khi có sự cố xảy ra</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('taskDetailsContent').innerHTML = content;
        }

        function viewDetails(assignmentId) {
            console.log('=== VIEW DETAILS DEBUG ===');
            console.log('assignmentId:', assignmentId);
            
            // Show loading in modal
            document.getElementById('eventDetailsContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải chi tiết sự kiện...</p>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
            modal.show();
            
            // Fetch event details
            console.log('Fetching event details for assignmentId:', assignmentId);
            console.log('Fetch URL:', '../src/controllers/staff-schedule.php');
            fetch('../src/controllers/staff-schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_event_details&assignmentId=' + assignmentId
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response URL:', response.url);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                console.log('Response length:', text.length);
                console.log('First 200 chars:', text.substring(0, 200));
                
                // Check if response looks like HTML (error page)
                if (text.includes('<html') || text.includes('<!DOCTYPE') || text.includes('<body') || text.includes('<br>') || text.includes('<b>')) {
                    console.error('Response appears to be HTML, not JSON');
                    console.error('Full HTML response:', text);
                    console.error('This usually means the request went to wrong URL or server error');
                    document.getElementById('eventDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lỗi:</strong> Server trả về trang HTML thay vì dữ liệu JSON. 
                            <br><small>Có thể do:</small>
                            <ul class="small">
                                <li>Đường dẫn controller không đúng</li>
                                <li>Server trả về trang lỗi</li>
                                <li>PHP error hoặc warning</li>
                            </ul>
                            <br><small>Response: ${text.substring(0, 500)}...</small>
                            <br><button class="btn btn-sm btn-outline-danger mt-2" onclick="console.log('Full response:', \`${text}\`)">Xem toàn bộ response</button>
                        </div>
                    `;
                    return;
                }
                
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                if (data.success) {
                    displayEventDetails(data.event);
                } else {
                    document.getElementById('eventDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                                <strong>Lỗi:</strong> ${data.message}
                            </div>
                        `;
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw response was:', text);
                    document.getElementById('eventDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lỗi:</strong> Không thể phân tích phản hồi từ server
                            <br><small>Lỗi JSON: ${e.message}</small>
                            <br><small>Response: ${text.substring(0, 200)}...</small>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                document.getElementById('eventDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Lỗi:</strong> Có lỗi xảy ra khi tải chi tiết sự kiện: ${error.message}
                    </div>
                `;
            });
        }
        
        function displayEventDetails(event) {
            console.log('=== DISPLAY EVENT DETAILS DEBUG ===');
            console.log('Event data:', event);
            console.log('Equipment count:', event.equipment ? event.equipment.length : 0);
            console.log('Combos count:', event.combos ? event.combos.length : 0);
            console.log('Combo equipment:', event.comboEquipment);
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle"></i>
                                    Thông tin sự kiện
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                <p><strong>Tên sự kiện:</strong> ${event.TenSuKien || 'Không xác định'}</p>
                                <p><strong>Loại sự kiện:</strong> ${event.TenLoaiSK || 'Không xác định'}</p>
                                        <p><strong>Ngày bắt đầu:</strong> ${formatDateTime(event.NgayBatDau)}</p>
                                        <p><strong>Ngày kết thúc:</strong> ${formatDateTime(event.NgayKetThuc)}</p>
                                        <p><strong>Thời gian diễn ra:</strong> 
                                            <span class="badge bg-info">
                                                ${calculateEventDuration(event.NgayBatDau, event.NgayKetThuc)}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                <p><strong>Số người dự kiến:</strong> ${event.SoNguoiDuKien || 'Không xác định'}</p>
                                <p><strong>Ngân sách:</strong> ${formatCurrency(event.NganSach)}</p>
                                <p><strong>Trạng thái duyệt:</strong> 
                                    <span class="badge bg-${getApprovalStatusColor(event.TrangThaiDuyet)}">${event.TrangThaiDuyet || 'Không xác định'}</span>
                                </p>
                                        <p><strong>Trạng thái thanh toán:</strong> 
                                            <span class="badge bg-${getPaymentStatusColor(event.TrangThaiThanhToan)}">${event.TrangThaiThanhToan || 'Không xác định'}</span>
                                        </p>
                                    </div>
                                </div>
                                ${event.MoTa ? `
                                <div class="mt-3">
                                    <p><strong>Mô tả sự kiện:</strong></p>
                                    <div class="alert alert-light">
                                        <i class="fas fa-info-circle"></i>
                                        ${event.MoTa}
                                    </div>
                                </div>
                                ` : ''}
                                ${event.GhiChu ? `
                                <div class="mt-2">
                                    <p><strong>Ghi chú:</strong></p>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-sticky-note"></i>
                                        ${event.GhiChu}
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Thông tin địa điểm
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                <p><strong>Tên địa điểm:</strong> ${event.TenDiaDiem || 'Không xác định'}</p>
                                <p><strong>Địa chỉ:</strong> ${event.DiaChi || 'Không xác định'}</p>
                                        ${event.SucChua ? `<p><strong>Sức chứa:</strong> ${event.SucChua} người</p>` : ''}
                                    </div>
                                    <div class="col-md-6">
                                        ${event.GiaThue ? `<p><strong>Giá thuê:</strong> ${formatCurrency(event.GiaThue)}</p>` : ''}
                                        <div class="mt-2">
                                            <a href="https://maps.google.com/?q=${encodeURIComponent(event.DiaChi || '')}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-map-marker-alt"></i> Xem trên bản đồ
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                ${event.DiaDiemMoTa ? `
                                <div class="mt-3">
                                    <p><strong>Mô tả địa điểm:</strong></p>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        ${event.DiaDiemMoTa}
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-user"></i>
                                    Thông tin khách hàng
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                <p><strong>Tên khách hàng:</strong> ${event.TenKhachHang || 'Không xác định'}</p>
                                        <p><strong>Số điện thoại:</strong> 
                                            ${event.SoDienThoai ? `<a href="tel:${event.SoDienThoai}" class="text-decoration-none">${event.SoDienThoai}</a>` : 'Không xác định'}
                                        </p>
                                ${event.KhachHangDiaChi ? `<p><strong>Địa chỉ:</strong> ${event.KhachHangDiaChi}</p>` : ''}
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mt-2">
                                            ${event.SoDienThoai ? `
                                            <a href="tel:${event.SoDienThoai}" class="btn btn-sm btn-outline-success me-2 mb-2">
                                                <i class="fas fa-phone"></i> Gọi điện
                                            </a>
                                            ` : ''}
                                            ${event.SoDienThoai ? `
                                            <a href="https://zalo.me/${event.SoDienThoai}" target="_blank" class="btn btn-sm btn-outline-primary mb-2">
                                                <i class="fab fa-facebook-messenger"></i> Zalo
                                            </a>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-tasks"></i>
                                    Thông tin công việc
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Nhiệm vụ:</strong> ${event.NhiemVu || 'Không xác định'}</p>
                                <p><strong>Trạng thái:</strong> 
                                    <span class="badge bg-${getStatusColor(event.TrangThai)}">${event.TrangThai || 'Không xác định'}</span>
                                </p>
                                <p><strong>Tiến độ:</strong> ${event.Tiendo || '0%'}</p>
                                <p><strong>Hạn hoàn thành:</strong> ${formatDate(event.HanHoanThanh)}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${event.registration ? `
                <div class="card mb-3">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-clipboard-check"></i>
                            Thông tin đăng ký sự kiện
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Ngày đăng ký:</strong> ${formatDateTime(event.registration.NgayDangKy)}</p>
                                <p><strong>Trạng thái duyệt:</strong> 
                                    <span class="badge bg-${getApprovalStatusColor(event.registration.TrangThaiDuyet)}">${event.registration.TrangThaiDuyet || 'Không xác định'}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                ${event.registration.NgayDuyet ? `<p><strong>Ngày duyệt:</strong> ${formatDateTime(event.registration.NgayDuyet)}</p>` : ''}
                                ${event.registration.NguoiDuyet ? `<p><strong>Người duyệt:</strong> ${event.registration.NguoiDuyet}</p>` : ''}
                            </div>
                        </div>
                        ${event.registration.DangKyGhiChu ? `<p><strong>Ghi chú đăng ký:</strong> ${event.registration.DangKyGhiChu}</p>` : ''}
                        ${event.registration.LyDoTuChoi ? `<p><strong>Lý do từ chối:</strong> <span class="text-danger">${event.registration.LyDoTuChoi}</span></p>` : ''}
                    </div>
                </div>
                ` : ''}
                
                ${event.equipment && event.equipment.length > 0 ? `
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-tools"></i>
                            Thiết bị đã đăng ký (${event.equipment.length} thiết bị)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tên thiết bị</th>
                                        <th>Số lượng</th>
                                        <th>Giá thuê</th>
                                        <th>Trạng thái</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${event.equipment.map(item => `
                                        <tr>
                                            <td>
                                                <strong>${item.TenThietBi}</strong>
                                                ${item.MoTa ? `<br><small class="text-muted">${item.MoTa}</small>` : ''}
                                            </td>
                                            <td><span class="badge bg-primary">${item.SoLuong}</span></td>
                                            <td>${formatCurrency(item.GiaThue)}</td>
                                            <td><span class="badge bg-${getEquipmentStatusColor(item.ThietBiTrangThai)}">${item.ThietBiTrangThai || 'Không xác định'}</span></td>
                                            <td>
                                                ${formatDate(item.ThietBiNgayBatDau)} - ${formatDate(item.ThietBiNgayKetThuc)}
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                ` : `
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-tools"></i>
                            Thiết bị đã đăng ký
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted text-center">
                            <i class="fas fa-info-circle"></i>
                            Chưa có thiết bị nào được đăng ký cho sự kiện này
                        </p>
                    </div>
                </div>
                `}
                
                ${(!event.equipment || event.equipment.length === 0) && (!event.combos || event.combos.length === 0) ? `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Thông báo:</strong> Chưa có thiết bị hoặc combo nào được đăng ký cho sự kiện này.
                </div>
                ` : ''}
                
                ${event.GhiChu ? `
                <div class="card mb-3">
                    <div class="card-header bg-light text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-sticky-note"></i>
                            Ghi chú công việc
                        </h6>
                    </div>
                    <div class="card-body">
                        <p>${event.GhiChu}</p>
                    </div>
                </div>
                ` : ''}
                
                ${event.kehoach_noidung ? `
                <div class="card mb-3">
                    <div class="card-header bg-light text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-clipboard-list"></i>
                            Nội dung kế hoạch
                        </h6>
                    </div>
                    <div class="card-body">
                        <p>${event.kehoach_noidung}</p>
                    </div>
                </div>
                ` : ''}
                
                ${event.combos && event.combos.length > 0 ? `
                <div class="card mb-3">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-box"></i>
                            Combo thiết bị đã đăng ký (${event.combos.length} combo)
                        </h6>
                    </div>
                    <div class="card-body">
                        ${event.combos.map(combo => `
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-box"></i>
                                        ${combo.TenCombo || 'Combo không xác định'}
                                        <span class="badge bg-primary ms-2">${combo.SoLuong || '0'} combo</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Mô tả combo:</strong> ${combo.ComboMoTa || 'Không có mô tả'}</p>
                                            <p><strong>Giá combo:</strong> ${formatCurrency(combo.GiaCombo)}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Số lượng:</strong> ${combo.SoLuong || '0'}</p>
                                            <p><strong>Ghi chú:</strong> ${combo.GhiChu || 'Không có ghi chú'}</p>
                                        </div>
                                    </div>
                                    
                                    ${event.comboEquipment && event.comboEquipment[combo.ID_Combo] && event.comboEquipment[combo.ID_Combo].length > 0 ? `
                                    <div class="mt-3">
                                        <h6><i class="fas fa-list"></i> Thiết bị trong combo:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Tên thiết bị</th>
                                                        <th>Mô tả</th>
                                                        <th>Số lượng</th>
                                                        <th>Giá thuê</th>
                                                        <th>Trạng thái</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${event.comboEquipment[combo.ID_Combo].map(item => `
                                                        <tr>
                                                            <td>${item.TenThietBi || 'Không xác định'}</td>
                                                            <td>${item.MoTa || 'Không có mô tả'}</td>
                                                            <td>${item.SoLuong || '0'}</td>
                                                            <td>${formatCurrency(item.GiaThue)}</td>
                                                            <td>
                                                                <span class="badge bg-${getEquipmentStatusColor(item.TrangThai)}">${item.TrangThai || 'Không xác định'}</span>
                                                            </td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
                
                ${(!event.equipment || event.equipment.length === 0) && (!event.combos || event.combos.length === 0) ? `
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle"></i>
                            Thông tin thiết bị
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Thông báo:</strong> Chưa có thiết bị hoặc combo nào được đăng ký cho sự kiện này.
                        </div>
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('eventDetailsContent').innerHTML = content;
        }
        
        function formatDateTime(dateString) {
            if (!dateString) return 'Không xác định';
            const date = new Date(dateString);
            return date.toLocaleString('vi-VN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function formatDate(dateString) {
            if (!dateString) return 'Không xác định';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }
        
        function formatCurrency(amount) {
            if (!amount) return 'Không xác định';
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        }
        
        function getStatusColor(status) {
            switch(status) {
                case 'Chưa làm': return 'secondary';
                case 'Đang làm': return 'warning';
                case 'Hoàn thành': return 'success';
                case 'Báo sự cố': return 'danger';
                default: return 'secondary';
            }
        }
        
        function getApprovalStatusColor(status) {
            switch(status) {
                case 'Đã duyệt': return 'success';
                case 'Chờ duyệt': return 'warning';
                case 'Từ chối': return 'danger';
                default: return 'secondary';
            }
        }
        
        function getPaymentStatusColor(status) {
            switch(status) {
                case 'Đã thanh toán': return 'success';
                case 'Chưa thanh toán': return 'warning';
                case 'Thanh toán một phần': return 'info';
                case 'Quá hạn': return 'danger';
                default: return 'secondary';
            }
        }
        
        function calculateEventDuration(startDate, endDate) {
            if (!startDate || !endDate) return 'Không xác định';
            
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffMs = end - start;
            
            if (diffMs <= 0) return 'Thời gian không hợp lệ';
            
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            
            if (diffHours > 0) {
                return `${diffHours} giờ ${diffMinutes} phút`;
            } else {
                return `${diffMinutes} phút`;
            }
        }
        
        function getEquipmentStatusColor(status) {
            switch(status) {
                case 'Sẵn sàng': return 'success';
                case 'Đang sử dụng': return 'warning';
                case 'Bảo trì': return 'danger';
                case 'Không khả dụng': return 'secondary';
                default: return 'info';
            }
        }

        function viewAllAssignments() {
            // Scroll to assignments list
            document.querySelector('.timeline').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }
        
        // Filter assignments function
        function filterAssignments() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const statusFilter = document.getElementById('statusFilter').value;
            const eventFilter = document.getElementById('eventFilter').value;
            
            // Filter assignments array
            let filtered = allAssignments.filter(assignment => {
                const taskName = (assignment.NhiemVu || '').toLowerCase();
                const eventName = (assignment.TenSuKien || '').toLowerCase();
                const locationName = (assignment.TenDiaDiem || '').toLowerCase();
                const status = (assignment.TrangThai || '').trim();
                
                // Check search term match
                const matchesSearch = !searchTerm || 
                    taskName.includes(searchTerm) ||
                    eventName.includes(searchTerm) ||
                    locationName.includes(searchTerm);
                
                // Check status filter match
                const matchesStatus = !statusFilter || status === statusFilter;
                
                // Check event filter match
                const matchesEvent = !eventFilter || 
                    (assignment.TenSuKien || '') === eventFilter;
                
                return matchesSearch && matchesStatus && matchesEvent;
            });
            
            // Re-render filtered assignments
            renderAssignments(filtered);
            
            // Show message if no results
            const timeline = document.getElementById('assignmentsContainer');
            if (filtered.length === 0 && allAssignments.length > 0) {
                let noResultsMsg = timeline.querySelector('.no-results-message');
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'no-results-message text-center py-5';
                    noResultsMsg.innerHTML = `
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Không tìm thấy công việc nào</h5>
                        <p class="text-muted">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
                        <button class="btn btn-outline-primary btn-sm" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Xóa bộ lọc
                        </button>
                    `;
                    timeline.appendChild(noResultsMsg);
                }
                noResultsMsg.style.display = 'block';
            } else {
                const noResultsMsg = timeline.querySelector('.no-results-message');
                if (noResultsMsg) {
                    noResultsMsg.style.display = 'none';
                }
            }
        }
        
        // Clear all filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('eventFilter').value = '';
            filterAssignments();
        }

        // Global variable để lưu assignments
        let allAssignments = [];
        
        // Load assignments từ API khi trang load
        document.addEventListener('DOMContentLoaded', function() {
            loadAssignments();
            
            setTimeout(function() {
                const loadingOverlay = document.getElementById('pageLoading');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
            }, 1000);
        });

        // Also hide on window load as backup
        window.addEventListener('load', function() {
            setTimeout(function() {
                const loadingOverlay = document.getElementById('pageLoading');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
            }, 500);
        });

        // Force hide loading after 3 seconds
        setTimeout(function() {
            const loadingOverlay = document.getElementById('pageLoading');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        }, 3000);
        
        // Load assignments từ API
        function loadAssignments() {
            const loadingDiv = document.getElementById('assignmentsLoading');
            const emptyDiv = document.getElementById('assignmentsEmpty');
            const containerDiv = document.getElementById('assignmentsContainer');
            
            // Show loading
            if (loadingDiv) loadingDiv.style.display = 'block';
            if (emptyDiv) emptyDiv.style.display = 'none';
            if (containerDiv) containerDiv.style.display = 'none';
            
            console.log('Loading assignments from API...');
            fetch('../src/controllers/staff-schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_assignments'
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get response as text first to check if it's valid JSON
                return response.text().then(text => {
                    console.log('Raw response:', text.substring(0, 200)); // Log first 200 chars
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Response không phải JSON hợp lệ: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                console.log('Parsed data:', data);
                
                if (data.success && data.assignments) {
                    console.log('Assignments loaded:', data.assignments.length);
                    allAssignments = data.assignments;
                    renderAssignments(allAssignments);
                    updateStatistics(allAssignments);
                    updateEventFilter(allAssignments);
                } else {
                    console.error('Error loading assignments:', data.message || 'Unknown error');
                    if (loadingDiv) loadingDiv.style.display = 'none';
                    if (emptyDiv) emptyDiv.style.display = 'block';
                    if (data.message) {
                        console.error('Error message:', data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error loading assignments:', error);
                console.error('Error stack:', error.stack);
                if (loadingDiv) loadingDiv.style.display = 'none';
                if (emptyDiv) emptyDiv.style.display = 'block';
                alert('Có lỗi xảy ra khi tải danh sách công việc: ' + error.message);
            });
        }
        
        // Render assignments vào HTML
        function renderAssignments(assignments) {
            const loadingDiv = document.getElementById('assignmentsLoading');
            const emptyDiv = document.getElementById('assignmentsEmpty');
            const containerDiv = document.getElementById('assignmentsContainer');
            
            if (loadingDiv) loadingDiv.style.display = 'none';
            
            if (!assignments || assignments.length === 0) {
                if (emptyDiv) emptyDiv.style.display = 'block';
                if (containerDiv) containerDiv.style.display = 'none';
                return;
            }
            
            if (emptyDiv) emptyDiv.style.display = 'none';
            if (containerDiv) containerDiv.style.display = 'block';
            
            // Sort assignments: non-completed tasks first, then completed tasks
            const sortedAssignments = [...assignments].sort((a, b) => {
                const aCompleted = (a.TrangThai == 'Hoàn thành') ? 1 : 0;
                const bCompleted = (b.TrangThai == 'Hoàn thành') ? 1 : 0;
                
                if (aCompleted != bCompleted) {
                    return aCompleted - bCompleted;
                }
                
                const aDate = new Date(a.NgayBatDau || '1970-01-01').getTime();
                const bDate = new Date(b.NgayBatDau || '1970-01-01').getTime();
                return aDate - bDate;
            });
            
            let html = '';
            sortedAssignments.forEach(assignment => {
                html += renderAssignmentCard(assignment);
            });
            
            if (containerDiv) {
                containerDiv.innerHTML = html;
            }
            
            // Update filter results count
            updateFilterResultsCount(assignments.length);
        }
        
        // Render một assignment card
        function renderAssignmentCard(assignment) {
            const statusClass = assignment.TrangThai ? assignment.TrangThai.toLowerCase().replace(/\s+/g, '-') : 'pending';
            const tiendo = assignment.Tiendo || '0';
            const tiendoValue = parseInt(tiendo.toString().replace('%', '')) || 0;
            const sourceTable = assignment.source_table || 'lichlamviec';
            
            // Format dates
            const formatDateTime = (dateStr) => {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${day}/${month}/${year} ${hours}:${minutes}`;
            };
            
            const formatDate = (dateStr) => {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}/${month}/${year}`;
            };
            
            // Calculate elapsed time
            const calculateElapsedTime = (startTime) => {
                if (!startTime) return 'Đang làm việc';
                const start = new Date(startTime).getTime();
                const now = Date.now();
                const elapsed = Math.floor((now - start) / 1000);
                const hours = Math.floor(elapsed / 3600);
                const minutes = Math.floor((elapsed % 3600) / 60);
                return `${hours}h ${minutes}m`;
            };
            
            // Calculate total time
            const calculateTotalTime = (startTime, endTime) => {
                if (!startTime || !endTime) return '';
                const start = new Date(startTime).getTime();
                const end = new Date(endTime).getTime();
                const total = Math.floor((end - start) / 1000);
                const hours = Math.floor(total / 3600);
                const minutes = Math.floor((total % 3600) / 60);
                return `${hours}h ${minutes}m`;
            };
            
            // Escape HTML
            const escapeHtml = (text) => {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };
            
            let statusSection = '';
            const status = assignment.TrangThai || 'Chưa làm';
            
            if (status === 'Chưa làm' || status === 'Chưa bắt đầu' || !status) {
                statusSection = `
                    <div class="mb-3">
                        <div class="alert alert-secondary border-secondary">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-clock text-secondary"></i>
                                    <strong class="text-secondary">CHƯA BẮT ĐẦU</strong>
                                    <span class="badge bg-secondary text-white ms-2">
                                        <i class="fas fa-hourglass-start"></i> Chờ bắt đầu
                                    </span>
                                </div>
                                <div>
                                    <span class="badge bg-secondary text-white">
                                        <i class="fas fa-info-circle"></i> Sử dụng các nút bên dưới để cập nhật trạng thái
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (status === 'Đang làm' || status === 'Đang thực hiện') {
                const elapsedTime = calculateElapsedTime(assignment.ThoiGianBatDauThucTe);
                statusSection = `
                    <div class="mb-3">
                        <div class="alert alert-warning border-warning status-working">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-play-circle text-warning"></i>
                                    <strong class="text-warning">ĐANG LÀM VIỆC</strong>
                                    <span class="badge bg-warning text-dark ms-2">
                                        <i class="fas fa-clock"></i> ${elapsedTime}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${assignment.ThoiGianBatDauThucTe ? `
                    <div class="mt-2">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-play text-success"></i>
                                    <strong>Bắt đầu:</strong> ${formatDateTime(assignment.ThoiGianBatDauThucTe)}
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-clock text-info"></i>
                                    <strong>Đã làm:</strong> ${elapsedTime}
                                </small>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                `;
            } else if (status === 'Hoàn thành') {
                statusSection = `
                    <div class="mb-3">
                        <div class="alert alert-success border-success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-check-circle text-success"></i>
                                    <strong class="text-success">ĐÃ HOÀN THÀNH</strong>
                                    ${assignment.ChamTienDo ? `
                                    <span class="badge bg-warning text-dark ms-2">
                                        <i class="fas fa-exclamation-triangle"></i> Chậm tiến độ
                                    </span>
                                    ` : `
                                    <span class="badge bg-success text-white ms-2">
                                        <i class="fas fa-trophy"></i> Đúng hạn
                                    </span>
                                    `}
                                </div>
                                <div>
                                    <button class="btn btn-outline-info btn-sm" onclick="viewTaskDetails(${assignment.ID_LLV}, '${sourceTable}')">
                                        <i class="fas fa-eye"></i> Xem chi tiết
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${assignment.ThoiGianBatDauThucTe ? `
                    <div class="mt-2">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-play text-success"></i>
                                    <strong>Bắt đầu:</strong> ${formatDateTime(assignment.ThoiGianBatDauThucTe)}
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-stop text-danger"></i>
                                    <strong>Kết thúc:</strong> ${assignment.ThoiGianKetThucThucTe ? formatDateTime(assignment.ThoiGianKetThucThucTe) : 'Chưa có'}
                                </small>
                            </div>
                        </div>
                        ${assignment.ThoiGianKetThucThucTe ? `
                        <div class="mt-1">
                            <small class="text-muted">
                                <i class="fas fa-clock text-info"></i>
                                <strong>Tổng thời gian:</strong> ${calculateTotalTime(assignment.ThoiGianBatDauThucTe, assignment.ThoiGianKetThucThucTe)}
                            </small>
                        </div>
                        ` : ''}
                    </div>
                    ` : ''}
                    ${assignment.KPI !== null && assignment.KPI !== undefined ? `
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-chart-line"></i>
                            <strong>KPI:</strong> 
                            <span class="${assignment.KPI >= 0 ? 'text-success' : 'text-danger'}">
                                ${(function() {
                                    const kpiValue = parseFloat(assignment.KPI);
                                    return kpiValue >= 0 ? `+${kpiValue.toFixed(2)}%` : `${kpiValue.toFixed(2)}%`;
                                })()}
                            </span>
                            ${assignment.KPI >= 0 ? '(Nhanh hơn dự kiến)' : '(Chậm hơn dự kiến)'}
                        </small>
                    </div>
                    ` : ''}
                `;
            } else if (status === 'Báo sự cố') {
                const issueStatus = assignment.IssueStatus || null;
                const isResolved = (issueStatus === 'Đã xử lý' || issueStatus === 'Đã đóng');
                statusSection = `
                    <div class="mb-3">
                        ${isResolved ? `
                        <div class="alert alert-success border-success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-check-circle text-success"></i>
                                    <strong class="text-success">SỰ CỐ ĐÃ ĐƯỢC XỬ LÝ</strong>
                                    <span class="badge bg-success text-white ms-2">
                                        <i class="fas fa-check"></i> ${issueStatus === 'Đã xử lý' ? 'Đã xử lý' : 'Đã đóng'}
                                    </span>
                                </div>
                                <div>
                                    <button class="btn btn-outline-info btn-sm" onclick="viewTaskDetails(${assignment.ID_LLV}, '${sourceTable}')">
                                        <i class="fas fa-eye"></i> Xem chi tiết
                                    </button>
                                </div>
                            </div>
                        </div>
                        ` : `
                        <div class="alert alert-danger border-danger">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                    <strong class="text-danger">BÁO SỰ CỐ</strong>
                                    <span class="badge bg-danger text-white ms-2">
                                        <i class="fas fa-warning"></i> Cần hỗ trợ
                                    </span>
                                </div>
                                <div>
                                    <button class="btn btn-outline-warning btn-sm" onclick="viewTaskDetails(${assignment.ID_LLV}, '${sourceTable}')">
                                        <i class="fas fa-eye"></i> Xem chi tiết
                                    </button>
                                </div>
                            </div>
                        </div>
                        `}
                    </div>
                    ${assignment.GhiChuTienDo ? `
                    <div class="mt-2">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            <strong>Mô tả sự cố:</strong> ${escapeHtml(assignment.GhiChuTienDo)}
                        </div>
                    </div>
                    ` : ''}
                `;
            }
            
            // Action buttons
            let actionButtons = '';
            if (status === 'Chưa làm' || status === 'Chưa bắt đầu' || !status) {
                // Kiểm tra xem có thể bắt đầu hay không (trước giờ bắt đầu tối đa 5 phút)
                const canStart = canStartTask(assignment.NgayBatDau);
                const buttonDisabled = !canStart ? 'disabled' : '';
                const buttonClass = !canStart ? 'btn-secondary' : 'btn-primary';
                const tooltipText = !canStart ? 'Chỉ được bắt đầu trước 5 phút kể từ giờ bắt đầu' : '';
                
                actionButtons = `
                    <button class="btn ${buttonClass} btn-sm" 
                            onclick="startWork(${assignment.ID_LLV}, '${sourceTable}')" 
                            ${buttonDisabled}
                            ${tooltipText ? `title="${tooltipText}" data-bs-toggle="tooltip"` : ''}>
                        <i class="fas fa-play"></i> Bắt đầu làm việc
                    </button>
                    ${!canStart ? `
                    <small class="text-muted d-block mt-1">
                        <i class="fas fa-info-circle"></i> 
                        Chỉ được bắt đầu trước 5 phút kể từ giờ bắt đầu
                    </small>
                    ` : ''}
                `;
            } else if (status === 'Hoàn thành') {
                actionButtons = `
                    <div class="text-center text-muted">
                        <i class="fas fa-check-circle text-success"></i>
                        <small>Công việc đã hoàn thành</small>
                    </div>
                `;
            } else {
                actionButtons = `
                    ${status !== 'Hoàn thành' ? `
                    <button class="btn btn-success btn-sm" onclick="showCompleteWorkModal(${assignment.ID_LLV}, '${sourceTable}', '${escapeHtml(assignment.NhiemVu || 'Công việc')}')">
                        <i class="fas fa-check"></i> Hoàn thành & Báo cáo
                    </button>
                    ` : ''}
                    <button class="btn btn-danger btn-sm" onclick="reportIssue(${assignment.ID_LLV}, '${sourceTable}')">
                        <i class="fas fa-exclamation-triangle"></i> Báo sự cố
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="viewTaskDetails(${assignment.ID_LLV}, '${sourceTable}')">
                        <i class="fas fa-eye"></i> Chi tiết
                    </button>
                `;
            }
            
            return `
                <div class="timeline-item ${statusClass}">
                    <div class="assignment-card" 
                         data-assignment-id="${assignment.ID_LLV}" 
                         data-source-table="${escapeHtml(sourceTable)}"
                         data-event-name="${escapeHtml(assignment.TenSuKien || '')}"
                         data-task-name="${escapeHtml(assignment.NhiemVu || '')}"
                         data-location-name="${escapeHtml(assignment.TenDiaDiem || '')}"
                         data-status="${escapeHtml(status)}">
                        <div class="assignment-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5 class="mb-2">
                                        <i class="fas fa-tasks"></i>
                                        ${escapeHtml(assignment.NhiemVu || 'Không có tên')}
                                    </h5>
                                    <p class="mb-1 text-muted">
                                        <i class="fas fa-calendar"></i>
                                        ${formatDateTime(assignment.NgayBatDau)} - ${formatDateTime(assignment.NgayKetThuc)}
                                    </p>
                                    ${assignment.HanHoanThanh ? `
                                    <p class="mb-1 text-muted">
                                        <i class="fas fa-clock"></i>
                                        Hạn hoàn thành: ${formatDate(assignment.HanHoanThanh)}
                                    </p>
                                    ` : ''}
                                </div>
                            </div>
                            
                            ${tiendoValue >= 0 ? `
                            <div class="mt-3" id="progress-bar-${assignment.ID_LLV}">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Tiến độ</small>
                                    <small class="text-muted"><strong>${tiendoValue}%</strong></small>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar ${tiendoValue >= 100 ? 'bg-success' : 'bg-primary'} progress-bar-striped ${tiendoValue < 100 ? 'progress-bar-animated' : ''}" 
                                         role="progressbar" 
                                         style="width: ${tiendoValue}%" 
                                         aria-valuenow="${tiendoValue}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        ${tiendoValue}%
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                        
                        <div class="card-body" 
                             data-customer-name="${escapeHtml(assignment.TenKhachHang || '')}"
                             data-customer-phone="${escapeHtml(assignment.SoDienThoai || '')}"
                             data-customer-address="${escapeHtml(assignment.KhachHangDiaChi || '')}"
                             data-event-name="${escapeHtml(assignment.TenSuKien || '')}"
                             data-event-type="${escapeHtml(assignment.TenLoaiSK || '')}"
                             data-event-location="${escapeHtml(assignment.TenDiaDiem || '')}"
                             data-event-address="${escapeHtml(assignment.DiaChi || '')}"
                             data-event-start="${formatDateTime(assignment.EventStartDate || assignment.NgayBatDau)}"
                             data-event-end="${formatDateTime(assignment.EventEndDate || assignment.NgayKetThuc)}"
                             data-event-attendees="${assignment.SoNguoiDuKien || 0}"
                             data-event-budget="${assignment.NganSach || 0}"
                             data-event-description="${escapeHtml(assignment.SuKienMoTa || '')}"
                             data-event-note="${escapeHtml(assignment.SuKienGhiChu || '')}">
                            ${assignment.GhiChu ? `
                            <div class="mb-3">
                                <h6><i class="fas fa-sticky-note"></i> Ghi chú</h6>
                                <p class="text-muted">${escapeHtml(assignment.GhiChu)}</p>
                            </div>
                            ` : ''}
                            
                            ${assignment.kehoach_noidung ? `
                            <div class="mb-3">
                                <h6><i class="fas fa-clipboard-list"></i> Nội dung kế hoạch</h6>
                                <p class="text-muted">${escapeHtml(assignment.kehoach_noidung)}</p>
                            </div>
                            ` : ''}
                            
                            ${statusSection}
                            
                            <div class="action-buttons">
                                ${actionButtons}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Update statistics
        function updateStatistics(assignments) {
            if (!assignments) assignments = allAssignments;
            
            const total = assignments.length;
            const notStarted = assignments.filter(a => !a.TrangThai || a.TrangThai === 'Chưa làm' || a.TrangThai === 'Chưa bắt đầu').length;
            const inProgress = assignments.filter(a => a.TrangThai === 'Đang làm' || a.TrangThai === 'Đang thực hiện').length;
            const completed = assignments.filter(a => a.TrangThai === 'Hoàn thành').length;
            const issue = assignments.filter(a => a.TrangThai === 'Báo sự cố').length;
            
            const statTotal = document.getElementById('statTotal');
            const statNotStarted = document.getElementById('statNotStarted');
            const statInProgress = document.getElementById('statInProgress');
            const statCompleted = document.getElementById('statCompleted');
            const statIssue = document.getElementById('statIssue');
            
            if (statTotal) statTotal.textContent = total;
            if (statNotStarted) statNotStarted.textContent = notStarted;
            if (statInProgress) statInProgress.textContent = inProgress;
            if (statCompleted) statCompleted.textContent = completed;
            if (statIssue) statIssue.textContent = issue;
        }
        
        // Update event filter dropdown
        function updateEventFilter(assignments) {
            const eventFilter = document.getElementById('eventFilter');
            if (!eventFilter) return;
            
            // Get unique event names
            const uniqueEvents = [];
            assignments.forEach(assignment => {
                const eventName = assignment.TenSuKien || 'Không xác định';
                if (!uniqueEvents.includes(eventName)) {
                    uniqueEvents.push(eventName);
                }
            });
            uniqueEvents.sort();
            
            // Clear existing options except first one
            eventFilter.innerHTML = '<option value="">Tất cả sự kiện</option>';
            
            // Add options
            uniqueEvents.forEach(eventName => {
                const option = document.createElement('option');
                option.value = eventName;
                option.textContent = eventName;
                eventFilter.appendChild(option);
            });
        }
        
        // Update filter results count
        function updateFilterResultsCount(count) {
            const countElement = document.getElementById('filterResultsCount');
            if (countElement) {
                countElement.innerHTML = `Hiển thị <strong>${count}</strong> công việc`;
            }
        }
    </script>
</body>
</html>
