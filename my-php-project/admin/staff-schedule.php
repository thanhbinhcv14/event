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
        
        // Check if this staff has any assignments
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM chitietkehoach WHERE ID_NhanVien = ?");
        $checkStmt->execute([$staffInfo['ID_NhanVien']]);
        $assignmentCount = $checkStmt->fetch(PDO::FETCH_ASSOC);
        error_log("DEBUG: Direct check - chitietkehoach assignments for staff " . $staffInfo['ID_NhanVien'] . ": " . $assignmentCount['count']);
    }
    
    // Get assignments from both lichlamviec and chitietkehoach
    $assignments = [];
    
    if ($staffInfo && $staffInfo['ID_NhanVien']) {
    // First, try to get from lichlamviec
    $stmt = $pdo->prepare("
        SELECT 
            llv.ID_LLV,
            llv.NhiemVu,
            llv.NgayBatDau,
            llv.NgayKetThuc,
            llv.TrangThai,
            llv.GhiChu,
            llv.CongViec,
                llv.NgayKetThuc as HanHoanThanh,
            llv.Tiendo,
                NULL as ThoiGianBatDauThucTe,
                NULL as ThoiGianKetThucThucTe,
                NULL as TienDoPhanTram,
                NULL as ThoiGianLamViec,
                NULL as ChamTienDo,
                NULL as GhiChuTienDo,
            COALESCE(dl.TenSuKien, 'Không xác định') as TenSuKien,
            COALESCE(dl.NgayBatDau, llv.NgayBatDau) as EventStartDate,
            COALESCE(dl.NgayKetThuc, llv.NgayKetThuc) as EventEndDate,
            COALESCE(dd.TenDiaDiem, 'Không xác định') as TenDiaDiem,
            COALESCE(dd.DiaChi, 'Không xác định') as DiaChi,
                COALESCE(kht.TenKeHoach, llv.NhiemVu) as ten_kehoach,
                COALESCE(kht.NoiDung, llv.GhiChu) as kehoach_noidung,
                COALESCE(kht.TrangThai, llv.TrangThai) as kehoach_trangthai,
            'lichlamviec' as source_table
        FROM lichlamviec llv
        LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN kehoachthuchien kht ON llv.ID_KeHoach = kht.ID_KeHoach
        WHERE llv.ID_NhanVien = ?
        ORDER BY llv.NgayBatDau ASC
    ");
    $stmt->execute([$staffInfo['ID_NhanVien']]);
    $lichlamviec_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Then, try to get from chitietkehoach
    $stmt = $pdo->prepare("
        SELECT 
            ck.ID_ChiTiet as ID_LLV,
            ck.TenBuoc as NhiemVu,
            ck.NgayBatDau,
            ck.NgayKetThuc,
            ck.TrangThai,
                ck.MoTa as GhiChu,
            ck.TenBuoc as CongViec,
            ck.NgayKetThuc as HanHoanThanh,
            '0' as Tiendo,
                NULL as ThoiGianBatDauThucTe,
                NULL as ThoiGianKetThucThucTe,
                NULL as TienDoPhanTram,
                NULL as ThoiGianLamViec,
                NULL as ChamTienDo,
                NULL as GhiChuTienDo,
            COALESCE(dl.TenSuKien, 'Không xác định') as TenSuKien,
            COALESCE(dl.NgayBatDau, ck.NgayBatDau) as EventStartDate,
            COALESCE(dl.NgayKetThuc, ck.NgayKetThuc) as EventEndDate,
            COALESCE(dd.TenDiaDiem, 'Không xác định') as TenDiaDiem,
            COALESCE(dd.DiaChi, 'Không xác định') as DiaChi,
                COALESCE(kht.TenKeHoach, ck.TenBuoc) as ten_kehoach,
                COALESCE(kht.NoiDung, ck.MoTa) as kehoach_noidung,
                COALESCE(kht.TrangThai, ck.TrangThai) as kehoach_trangthai,
            'chitietkehoach' as source_table
        FROM chitietkehoach ck
            LEFT JOIN kehoachthuchien kht ON ck.ID_KeHoach = kht.ID_KeHoach
            LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
        LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        WHERE ck.ID_NhanVien = ?
        ORDER BY ck.NgayBatDau ASC
    ");
    $stmt->execute([$staffInfo['ID_NhanVien']]);
    $chitietkehoach_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine both results - but prioritize lichlamviec to avoid duplicates
    $assignments = $lichlamviec_assignments;
    
    // Only add chitietkehoach assignments that don't have corresponding lichlamviec entries
    foreach ($chitietkehoach_assignments as $chitiet) {
        $isDuplicate = false;
        foreach ($lichlamviec_assignments as $lich) {
            // Check if this chitietkehoach entry already exists in lichlamviec
            // by comparing task name, dates, and staff ID
            if ($chitiet['NhiemVu'] == $lich['NhiemVu'] && 
                $chitiet['NgayBatDau'] == $lich['NgayBatDau'] && 
                $chitiet['NgayKetThuc'] == $lich['NgayKetThuc']) {
                $isDuplicate = true;
                break;
            }
        }
        if (!$isDuplicate) {
            $assignments[] = $chitiet;
        }
    }
        
        error_log("DEBUG: Found " . count($lichlamviec_assignments) . " lichlamviec assignments");
        error_log("DEBUG: Found " . count($chitietkehoach_assignments) . " chitietkehoach assignments");
        error_log("DEBUG: Total assignments: " . count($assignments));
        
        // Debug: Log first assignment if exists
        if (!empty($chitietkehoach_assignments)) {
            error_log("DEBUG: First chitietkehoach assignment: " . json_encode($chitietkehoach_assignments[0]));
        }
    } else {
        error_log("WARNING: No staff ID found, using fallback by user/email to fetch assignments");
        // Fallback: fetch by users link (ID_User/Email) via subqueries/joins
        // lichlamviec via subselect of staff IDs mapped to this user
        $stmt = $pdo->prepare("
            SELECT 
                llv.ID_LLV,
                llv.NhiemVu,
                llv.NgayBatDau,
                llv.NgayKetThuc,
                llv.TrangThai,
                llv.GhiChu,
                llv.CongViec,
                llv.NgayKetThuc as HanHoanThanh,
                llv.Tiendo,
                NULL as ThoiGianBatDauThucTe,
                NULL as ThoiGianKetThucThucTe,
                NULL as TienDoPhanTram,
                NULL as ThoiGianLamViec,
                NULL as ChamTienDo,
                NULL as GhiChuTienDo,
                COALESCE(dl.TenSuKien, 'Không xác định') as TenSuKien,
                COALESCE(dl.NgayBatDau, llv.NgayBatDau) as EventStartDate,
                COALESCE(dl.NgayKetThuc, llv.NgayKetThuc) as EventEndDate,
                COALESCE(dd.TenDiaDiem, 'Không xác định') as TenDiaDiem,
                COALESCE(dd.DiaChi, 'Không xác định') as DiaChi,
                COALESCE(kht.TenKeHoach, llv.NhiemVu) as ten_kehoach,
                COALESCE(kht.NoiDung, llv.GhiChu) as kehoach_noidung,
                COALESCE(kht.TrangThai, llv.TrangThai) as kehoach_trangthai,
                'lichlamviec' as source_table
            FROM lichlamviec llv
            LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN kehoachthuchien kht ON llv.ID_KeHoach = kht.ID_KeHoach
            WHERE llv.ID_NhanVien IN (
                SELECT nv.ID_NhanVien FROM nhanvieninfo nv JOIN users u ON nv.ID_User = u.ID_User
                WHERE u.ID_User = ? OR u.Email = ?
            )
            ORDER BY llv.NgayBatDau ASC
        ");
        $stmt->execute([$userId, $userEmail]);
        $lichlamviec_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // chitietkehoach via join to nv->users by user
        $stmt = $pdo->prepare("
            SELECT 
                ck.ID_ChiTiet as ID_LLV,
                ck.TenBuoc as NhiemVu,
                ck.NgayBatDau,
                ck.NgayKetThuc,
                ck.TrangThai,
                ck.MoTa as GhiChu,
                ck.TenBuoc as CongViec,
                ck.NgayKetThuc as HanHoanThanh,
                '0' as Tiendo,
                NULL as ThoiGianBatDauThucTe,
                NULL as ThoiGianKetThucThucTe,
                NULL as TienDoPhanTram,
                NULL as ThoiGianLamViec,
                NULL as ChamTienDo,
                NULL as GhiChuTienDo,
                COALESCE(dl.TenSuKien, 'Không xác định') as TenSuKien,
                COALESCE(dl.NgayBatDau, ck.NgayBatDau) as EventStartDate,
                COALESCE(dl.NgayKetThuc, ck.NgayKetThuc) as EventEndDate,
                COALESCE(dd.TenDiaDiem, 'Không xác định') as TenDiaDiem,
                COALESCE(dd.DiaChi, 'Không xác định') as DiaChi,
                COALESCE(kht.TenKeHoach, ck.TenBuoc) as ten_kehoach,
                COALESCE(kht.NoiDung, ck.MoTa) as kehoach_noidung,
                COALESCE(kht.TrangThai, ck.TrangThai) as kehoach_trangthai,
                'chitietkehoach' as source_table
            FROM chitietkehoach ck
            JOIN nhanvieninfo nv ON ck.ID_NhanVien = nv.ID_NhanVien
            JOIN users u ON nv.ID_User = u.ID_User
            LEFT JOIN kehoachthuchien kht ON ck.ID_KeHoach = kht.ID_KeHoach
            LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
            LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE u.ID_User = ? OR u.Email = ?
            ORDER BY ck.NgayBatDau ASC
        ");
        $stmt->execute([$userId, $userEmail]);
        $chitietkehoach_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $assignments = array_merge($lichlamviec_assignments, $chitietkehoach_assignments);
    }
    
    // Debug: Log assignments query
    error_log("DEBUG: Staff Schedule - Staff ID: " . ($staffInfo['ID_NhanVien'] ?? 'N/A'));
    error_log("DEBUG: Staff Schedule - lichlamviec assignments: " . count($lichlamviec_assignments));
    error_log("DEBUG: Staff Schedule - chitietkehoach assignments: " . count($chitietkehoach_assignments));
    error_log("DEBUG: Staff Schedule - Total assignments: " . count($assignments));
    
    // Debug: Log first assignment data
    if (!empty($assignments)) {
        error_log("DEBUG: First assignment data: " . json_encode($assignments[0]));
    }
    
    if (!empty($lichlamviec_assignments)) {
        error_log("DEBUG: Staff Schedule - First lichlamviec assignment: " . json_encode($lichlamviec_assignments[0]));
    }
    if (!empty($chitietkehoach_assignments)) {
        error_log("DEBUG: Staff Schedule - First chitietkehoach assignment: " . json_encode($chitietkehoach_assignments[0]));
    }
    if (!empty($assignments)) {
        error_log("DEBUG: Staff Schedule - First combined assignment: " . json_encode($assignments[0]));
    }
    
} catch (Exception $e) {
    $assignments = [];
    $lichlamviec_assignments = [];
    $chitietkehoach_assignments = [];
    $staffInfo = ['ID_NhanVien' => null, 'HoTen' => 'Nhân viên', 'ChucVu' => 'Staff', 'Email' => ''];
    error_log("Error fetching staff assignments: " . $e->getMessage());
    echo "<!-- Error: " . $e->getMessage() . " -->";
}
?>

<style>
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.95rem;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .stats-card .fas {
            font-size: 2.2rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }
        
        .assignment-card {
            border: 1px solid #e0e0e0;
            border-radius: 15px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .assignment-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .assignment-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            position: relative;
        }
        
        .assignment-header::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .assignment-header h5 {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 12px;
        }
        
        .assignment-header h5 i {
            color: #667eea;
            margin-right: 8px;
        }
        
        .assignment-header p {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .assignment-header p i {
            color: #667eea;
            margin-right: 6px;
            width: 16px;
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
        
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -32px;
            top: 8px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #667eea;
            border: 4px solid white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            z-index: 2;
        }
        
        .timeline-item.completed::before {
            background: #28a745;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        .timeline-item.in-progress::before {
            background: #ffc107;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }
        
        .timeline-item.issue::before {
            background: #dc3545;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
        
        .event-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #2196f3;
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.1);
        }
        
        .event-info h6 {
            color: #1976d2;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        
        .event-info h6 i {
            color: #2196f3;
            margin-right: 8px;
        }
        
        .event-info p {
            color: #424242;
            margin-bottom: 8px;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .event-info p strong {
            color: #1976d2;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .action-buttons .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            min-width: 120px;
            text-align: center;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .action-buttons .btn i {
            margin-right: 5px;
        }
        
        .card-body {
            padding: 25px;
            background: #fafbfc;
        }
        
        .card-body h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        
        .card-body h6 i {
            color: #6c757d;
            margin-right: 6px;
        }
        
        .card-body p {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom: none;
            padding: 20px 25px;
        }
        
        .modal-header .modal-title {
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .modal-header .modal-title i {
            margin-right: 10px;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.8;
        }
        
        .modal-header .btn-close:hover {
            opacity: 1;
        }
        
        .modal-body {
            padding: 25px;
            background: #fafbfc;
        }
        
        .modal-footer {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 15px 25px;
        }
        
        .modal-footer .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 8px 20px;
        }
        
        /* Modal content styling */
        .modal-body .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .modal-body .card-header {
            border-radius: 12px 12px 0 0;
            border-bottom: none;
            padding: 15px 20px;
        }
        
        .modal-body .card-body {
            padding: 20px;
        }
        
        .modal-body .card-header h6 {
            margin: 0;
            font-weight: 600;
        }
        
        .modal-body .card-header i {
            margin-right: 8px;
        }
        
        .modal-body .alert {
            border-radius: 8px;
            border: none;
        }
        
        .modal-body .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .modal-body .badge {
            font-size: 0.8rem;
            padding: 6px 10px;
        }
        
        /* Status indicators */
        .status-indicator {
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .alert.border-warning {
            border-left: 4px solid #ffc107 !important;
        }
        
        .alert.border-success {
            border-left: 4px solid #198754 !important;
        }
        
        .alert.border-danger {
            border-left: 4px solid #dc3545 !important;
        }
        
        .alert.border-secondary {
            border-left: 4px solid #6c757d !important;
        }
        
        /* Timer animation for "Đang làm việc" */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .status-working .badge {
            animation: pulse 2s infinite;
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
                            <div class="stats-number"><?= count($assignments) ?></div>
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
                            <div class="stats-number">
                                <?= count(array_filter($assignments, function($a) { return empty($a['TrangThai']) || $a['TrangThai'] == 'Chưa làm' || $a['TrangThai'] == 'Chưa bắt đầu'; })) ?>
                            </div>
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
                            <div class="stats-number">
                                <?= count(array_filter($assignments, function($a) { return $a['TrangThai'] == 'Đang làm' || $a['TrangThai'] == 'Đang thực hiện'; })) ?>
                            </div>
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
                            <div class="stats-number">
                                <?= count(array_filter($assignments, function($a) { return $a['TrangThai'] == 'Hoàn thành'; })) ?>
                            </div>
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
                            <div class="stats-number">
                                <?= count(array_filter($assignments, function($a) { return $a['TrangThai'] == 'Báo sự cố'; })) ?>
                            </div>
                            <div class="stats-label">Báo sự cố</div>
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

                <?php if (empty($assignments)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h3>Chưa có công việc nào được phân công</h3>
                    <p class="text-muted">Các công việc được phân công sẽ hiển thị ở đây.</p>
                    <?php if (isset($staffInfo['ID_NhanVien'])): ?>
                    <p class="text-muted">Staff ID: <?= $staffInfo['ID_NhanVien'] ?></p>
                    <?php endif; ?>
                    <div class="alert alert-info mt-3">
                        <small>Debug: Tìm kiếm công việc cho nhân viên ID: <?= $staffInfo['ID_NhanVien'] ?? 'N/A' ?></small>
                        <br><small>Lịch làm việc: <?= count($lichlamviec_assignments) ?> công việc</small>
                        <br><small>Chi tiết kế hoạch: <?= count($chitietkehoach_assignments) ?> công việc</small>
                        <br><small>Tổng cộng: <?= count($assignments) ?> công việc</small>
                        <br><small>User ID: <?= $userId ?? 'N/A' ?></small>
                        <br><small>User Email: <?= $userEmail ?? 'N/A' ?></small>
                        <?php if (!empty($chitietkehoach_assignments)): ?>
                        <br><small>First chitietkehoach: <?= htmlspecialchars(json_encode($chitietkehoach_assignments[0])) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($assignments as $assignment): ?>
                    <div class="timeline-item <?= strtolower(str_replace(' ', '-', $assignment['TrangThai'])) ?>">
                        <div class="assignment-card">
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
                                <?php if ($assignment['Tiendo']): ?>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">Tiến độ</small>
                                        <small class="text-muted"><?= $assignment['Tiendo'] ?></small>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" style="width: <?= $assignment['Tiendo'] ?>"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body">
                                <!-- Event Information -->
                                <div class="event-info">
                                    <h6 class="mb-2">
                                        <i class="fas fa-calendar-check"></i>
                                        Thông tin sự kiện
                                    </h6>
                                    <p class="mb-1">
                                        <strong>Sự kiện:</strong> <?= htmlspecialchars($assignment['TenSuKien']) ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Địa điểm:</strong> <?= htmlspecialchars($assignment['TenDiaDiem']) ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Địa chỉ:</strong> <?= htmlspecialchars($assignment['DiaChi']) ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Thời gian sự kiện:</strong> 
                                        <?= date('d/m/Y H:i', strtotime($assignment['EventStartDate'])) ?> - 
                                        <?= date('d/m/Y H:i', strtotime($assignment['EventEndDate'])) ?>
                                    </p>
                                </div>
                                
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
                                    <button class="btn btn-primary btn-sm" onclick="startWork(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>')">
                                        <i class="fas fa-play"></i>
                                        Bắt đầu làm việc
                                    </button>
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
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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

    <!-- Update Progress Modal -->
    <div class="modal fade" id="updateProgressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật tiến độ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProgressForm">
                        <input type="hidden" id="progressAssignmentId" name="assignmentId">
                        
                        <div class="mb-3">
                            <label for="progressValue" class="form-label">Tiến độ (%)</label>
                            <input type="number" class="form-control" id="progressValue" name="progress" min="0" max="100" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="progressNote" class="form-label">Ghi chú tiến độ</label>
                            <textarea class="form-control" id="progressNote" name="note" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveProgressUpdate()">Cập nhật</button>
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

    <!-- Update Progress Modal -->
    <div class="modal fade" id="updateProgressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-percentage"></i>
                        Cập nhật tiến độ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProgressForm">
                        <input type="hidden" id="progressAssignmentId" name="assignmentId">
                        <input type="hidden" id="progressTable" name="sourceTable">
                        
                        <div class="mb-3">
                            <label for="progressValue" class="form-label">Tiến độ hoàn thành (%)</label>
                            <input type="number" class="form-control" id="progressValue" name="progress" min="0" max="100" required>
                            <div class="form-text">Nhập phần trăm hoàn thành từ 0 đến 100</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="progressNote" class="form-label">Ghi chú tiến độ</label>
                            <textarea class="form-control" id="progressNote" name="note" rows="3" placeholder="Mô tả tiến độ hiện tại..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveProgressUpdate()">
                        <i class="fas fa-save"></i>
                        Cập nhật tiến độ
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

        function startWork(assignmentId, sourceTable) {
            try {
                document.getElementById('startWorkId').value = assignmentId;
                document.getElementById('startWorkTable').value = sourceTable;
                
                const modal = new bootstrap.Modal(document.getElementById('startWorkModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening start work modal:', error);
                alert('Có lỗi xảy ra khi mở modal bắt đầu làm việc');
            }
        }

        function updateProgress(assignmentId, sourceTable) {
            try {
                document.getElementById('progressAssignmentId').value = assignmentId;
                document.getElementById('progressTable').value = sourceTable;
                
                const modal = new bootstrap.Modal(document.getElementById('updateProgressModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening update progress modal:', error);
                alert('Có lỗi xảy ra khi mở modal cập nhật tiến độ');
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
                        // Reload page to show updated status
                        location.reload();
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
                            
                            // Update UI dynamically without reload
                            console.log('Calling updateTaskStatusAfterStart with:', assignmentId, sourceTable);
                            updateTaskStatusAfterStart(assignmentId, sourceTable);
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

        function saveProgressUpdate() {
            const form = document.getElementById('updateProgressForm');
            const formData = new FormData(form);
            formData.append('action', 'update_progress');
            
            fetch('../src/controllers/staff-schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cập nhật tiến độ thành công');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật tiến độ');
            });
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
                        location.reload();
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
                    location.reload();
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
                    
                    // Get event info
                    const eventInfo = card.querySelector('.event-info');
                    const eventName = eventInfo ? eventInfo.querySelector('p:nth-child(2)')?.textContent.replace('Sự kiện: ', '') : '';
                    const location = eventInfo ? eventInfo.querySelector('p:nth-child(3)')?.textContent.replace('Địa điểm: ', '') : '';
                    const address = eventInfo ? eventInfo.querySelector('p:nth-child(4)')?.textContent.replace('Địa chỉ: ', '') : '';
                    const eventTime = eventInfo ? eventInfo.querySelector('p:nth-child(5)')?.textContent.replace('Thời gian sự kiện: ', '') : '';
                    
                    // Get plan content
                    const planContent = card.querySelector('.card-body p')?.textContent || '';
                    
                    return {
                        id: assignmentId,
                        title: title,
                        startTime: startTime,
                        endTime: endTime,
                        deadline: deadline,
                        eventName: eventName,
                        location: location,
                        address: address,
                        eventTime: eventTime,
                        planContent: planContent,
                        sourceTable: sourceTable
                    };
                }
            }
            return null;
        }
        
        function displayTaskDetails(assignment) {
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
                                <p><strong>Hạn hoàn thành:</strong> ${assignment.deadline}</p>
                                <p><strong>Nguồn dữ liệu:</strong> 
                                    <span class="badge bg-${assignment.sourceTable === 'chitietkehoach' ? 'info' : 'secondary'}">
                                        ${assignment.sourceTable === 'chitietkehoach' ? 'Chi tiết kế hoạch' : 'Lịch làm việc'}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                ${assignment.planContent ? `
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

        // Hide loading overlay when page is loaded
        document.addEventListener('DOMContentLoaded', function() {
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
    </script>
</body>
</html>
