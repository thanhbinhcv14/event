<?php
// Set error reporting to catch all errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

session_start();

// Try to include database config
try {
    $dbPath = __DIR__ . '/../../config/database.php';
    if (!file_exists($dbPath)) {
        throw new Exception('File database.php không tồn tại tại: ' . $dbPath);
    }
    require_once $dbPath;
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi cấu hình database: ' . $e->getMessage()]);
    exit;
}

// Check if user is logged in and has role 4 (Staff)
if (!isset($_SESSION['user']) || $_SESSION['user']['ID_Role'] != 4) {
    // Clear any output buffer
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_assignments':
        getAssignments();
        break;
    case 'update_assignment_status':
        updateAssignmentStatus();
        break;
    case 'update_progress':
        updateProgress();
        break;
    case 'start_work':
        startWork();
        break;
    case 'complete_work':
        completeWork();
        break;
    case 'report_issue':
        reportIssue();
        break;
    case 'get_event_details':
        getEventDetails();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

function getAssignments() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        $userId = $_SESSION['user']['ID_User'];
        
        // Get staff info
        $stmt = $pdo->prepare("
            SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?
        ");
        $stmt->execute([$userId]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            error_log("ERROR: Staff not found for user ID: " . $userId);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin nhân viên']);
            return;
        }
        
        error_log("DEBUG: Staff found - ID: " . $staff['ID_NhanVien']);
        
        // Get assignments from both lichlamviec and chitietkehoach
        $assignments = [];
        
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
                       llv.ThoiGianBatDauThucTe,
                       llv.ThoiGianKetThucThucTe,
                       llv.TienDoPhanTram,
                       llv.ThoiGianLamViec,
                       llv.ChamTienDo,
                       llv.GhiChuTienDo,
                       COALESCE(dl.TenSuKien, 'Không xác định') as TenSuKien,
                       COALESCE(dl.NgayBatDau, llv.NgayBatDau) as EventStartDate,
                       COALESCE(dl.NgayKetThuc, llv.NgayKetThuc) as EventEndDate,
                       COALESCE(dd.TenDiaDiem, 'Không xác định') as TenDiaDiem,
                       COALESCE(dd.DiaChi, 'Không xác định') as DiaChi,
                       COALESCE(kh.HoTen, 'Không xác định') as TenKhachHang,
                       COALESCE(kh.SoDienThoai, 'Không xác định') as SoDienThoai,
                       'lichlamviec' as source_table
                   FROM lichlamviec llv
                   LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
                   LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
                   LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                   WHERE llv.ID_NhanVien = ?
                   ORDER BY llv.NgayBatDau ASC
               ");
        $stmt->execute([$staff['ID_NhanVien']]);
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
                       COALESCE(kh.HoTen, 'Không xác định') as TenKhachHang,
                       COALESCE(kh.SoDienThoai, 'Không xác định') as SoDienThoai,
                       'chitietkehoach' as source_table
                   FROM chitietkehoach ck
                   LEFT JOIN kehoachthuchien kht ON ck.ID_KeHoach = kht.ID_KeHoach
                   LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
                   LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
                   LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
                   LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                   WHERE ck.ID_NhanVien = ?
                   ORDER BY ck.NgayBatDau ASC
               ");
        $stmt->execute([$staff['ID_NhanVien']]);
        $chitietkehoach_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine both results
        $assignments = array_merge($lichlamviec_assignments, $chitietkehoach_assignments);
        
        // Debug logs
        error_log("DEBUG: Staff Schedule Controller - Staff ID: " . $staff['ID_NhanVien']);
        error_log("DEBUG: Staff Schedule Controller - lichlamviec assignments: " . count($lichlamviec_assignments));
        error_log("DEBUG: Staff Schedule Controller - chitietkehoach assignments: " . count($chitietkehoach_assignments));
        error_log("DEBUG: Staff Schedule Controller - Total assignments: " . count($assignments));
        
        echo json_encode(['success' => true, 'assignments' => $assignments]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy lịch làm việc: ' . $e->getMessage()]);
    }
}

function updateAssignmentStatus() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? '';
        $newStatus = $_POST['newStatus'] ?? '';
        $progress = $_POST['progress'] ?? '';
        $note = $_POST['note'] ?? '';
        $sourceTable = $_POST['sourceTable'] ?? 'lichlamviec';
        
        if (empty($assignmentId) || empty($newStatus)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Validate status values
        $validStatuses = ['Chưa làm', 'Đang làm', 'Hoàn thành', 'Báo sự cố'];
        if (!in_array($newStatus, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
            return;
        }
        
        if ($sourceTable === 'chitietkehoach') {
            // Update chitietkehoach table
            $stmt = $pdo->prepare("
                UPDATE chitietkehoach 
                SET TrangThai = ?
                WHERE ID_ChiTiet = ?
            ");
            $result = $stmt->execute([$newStatus, $assignmentId]);
            
            // Sync status with lichlamviec if exists
            if ($result) {
                try {
                    $scheduleSql = "UPDATE lichlamviec SET TrangThai = ? WHERE ID_ChiTiet = ?";
                    $scheduleStmt = $pdo->prepare($scheduleSql);
                    $scheduleStmt->execute([$newStatus, $assignmentId]);
                    
                    // Update ThoiGianHoanThanh based on status
                    if ($newStatus === 'Đang làm') {
                        $timeSql = "UPDATE lichlamviec SET ThoiGianHoanThanh = NULL WHERE ID_ChiTiet = ?";
                        $timeStmt = $pdo->prepare($timeSql);
                        $timeStmt->execute([$assignmentId]);
                    } elseif ($newStatus === 'Hoàn thành') {
                        $timeSql = "UPDATE lichlamviec SET ThoiGianHoanThanh = NOW() WHERE ID_ChiTiet = ?";
                        $timeStmt = $pdo->prepare($timeSql);
                        $timeStmt->execute([$assignmentId]);
                    }
                } catch (Exception $e) {
                    error_log("ERROR: updateAssignmentStatus - Failed to sync lichlamviec: " . $e->getMessage());
                }
            }
        } else {
            // Update lichlamviec table
            $stmt = $pdo->prepare("
                UPDATE lichlamviec 
                SET TrangThai = ?, Tiendo = ?, GhiChu = ?, NgayCapNhat = NOW()
                WHERE ID_LLV = ?
            ");
            $result = $stmt->execute([$newStatus, $progress, $note, $assignmentId]);
            
            // Update ThoiGianHoanThanh based on status
            if ($result) {
                if ($newStatus === 'Đang làm') {
                    $timeSql = "UPDATE lichlamviec SET ThoiGianHoanThanh = NULL WHERE ID_LLV = ?";
                    $timeStmt = $pdo->prepare($timeSql);
                    $timeStmt->execute([$assignmentId]);
                } elseif ($newStatus === 'Hoàn thành') {
                    $timeSql = "UPDATE lichlamviec SET ThoiGianHoanThanh = NOW() WHERE ID_LLV = ?";
                    $timeStmt = $pdo->prepare($timeSql);
                    $timeStmt->execute([$assignmentId]);
                }
                
                // Sync status with chitietkehoach if exists
                try {
                    $stmt = $pdo->prepare("SELECT ID_ChiTiet FROM lichlamviec WHERE ID_LLV = ?");
                    $stmt->execute([$assignmentId]);
                    $chitietId = $stmt->fetchColumn();
                    
                    if ($chitietId) {
                        $chitietSql = "UPDATE chitietkehoach SET TrangThai = ? WHERE ID_ChiTiet = ?";
                        $chitietStmt = $pdo->prepare($chitietSql);
                        $chitietStmt->execute([$newStatus, $chitietId]);
                    }
                } catch (Exception $e) {
                    error_log("ERROR: updateAssignmentStatus - Failed to sync chitietkehoach: " . $e->getMessage());
                }
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
        
    } catch (Exception $e) {
        error_log("ERROR: updateAssignmentStatus - " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage()]);
    }
}

function updateProgress() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? '';
        $sourceTable = $_POST['sourceTable'] ?? 'lichlamviec';
        $progress = $_POST['progress'] ?? '';
        $note = $_POST['note'] ?? '';
        
        if (empty($assignmentId) || empty($progress)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Validate progress value (0-100)
        $progressInt = intval($progress);
        if ($progressInt < 0 || $progressInt > 100) {
            echo json_encode(['success' => false, 'message' => 'Tiến độ phải từ 0 đến 100']);
            return;
        }
        
        if ($sourceTable === 'chitietkehoach') {
            // chitietkehoach only has basic columns, so we update TrangThai based on progress
            // If progress > 0, set status to 'Đang làm'
            $newStatus = ($progressInt > 0 && $progressInt < 100) ? 'Đang làm' : 
                        ($progressInt >= 100 ? 'Hoàn thành' : 'Chưa làm');
            
            $sql = "
                UPDATE chitietkehoach 
                SET TrangThai = ?
                WHERE ID_ChiTiet = ?
            ";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$newStatus, $assignmentId]);
            
            // Sync status with lichlamviec if exists
            if ($result) {
                try {
                    $scheduleSql = "UPDATE lichlamviec SET TrangThai = ? WHERE ID_ChiTiet = ?";
                    $scheduleStmt = $pdo->prepare($scheduleSql);
                    $scheduleStmt->execute([$newStatus, $assignmentId]);
                    
                    // Also update progress in lichlamviec if exists
                    $progressSql = "UPDATE lichlamviec SET TienDo = ?, GhiChu = ?, NgayCapNhat = NOW() WHERE ID_ChiTiet = ?";
                    $progressStmt = $pdo->prepare($progressSql);
                    $progressStmt->execute([$progress . '%', $note, $assignmentId]);
                } catch (Exception $e) {
                    error_log("ERROR: updateProgress - Failed to sync lichlamviec: " . $e->getMessage());
                }
            }
        } else {
            // lichlamviec has columns: TienDo (varchar), GhiChu (text), NgayCapNhat (auto-update)
            // Update progress and status based on progress value
            $newStatus = ($progressInt > 0 && $progressInt < 100) ? 'Đang làm' : 
                        ($progressInt >= 100 ? 'Hoàn thành' : 'Chưa làm');
            
            $sql = "
                UPDATE lichlamviec 
                SET TienDo = ?, 
                    GhiChu = ?, 
                    TrangThai = ?,
                    NgayCapNhat = NOW()
                WHERE ID_LLV = ?
            ";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$progress . '%', $note, $newStatus, $assignmentId]);
            
            // Update ThoiGianHoanThanh based on status
            if ($result) {
                if ($newStatus === 'Đang làm') {
                    $timeSql = "UPDATE lichlamviec SET ThoiGianHoanThanh = NULL WHERE ID_LLV = ?";
                    $timeStmt = $pdo->prepare($timeSql);
                    $timeStmt->execute([$assignmentId]);
                } elseif ($newStatus === 'Hoàn thành') {
                    $timeSql = "UPDATE lichlamviec SET ThoiGianHoanThanh = NOW() WHERE ID_LLV = ?";
                    $timeStmt = $pdo->prepare($timeSql);
                    $timeStmt->execute([$assignmentId]);
                }
                
                // Sync status with chitietkehoach if exists
                try {
                    $stmt = $pdo->prepare("SELECT ID_ChiTiet FROM lichlamviec WHERE ID_LLV = ?");
                    $stmt->execute([$assignmentId]);
                    $chitietId = $stmt->fetchColumn();
                    
                    if ($chitietId) {
                        $chitietSql = "UPDATE chitietkehoach SET TrangThai = ? WHERE ID_ChiTiet = ?";
                        $chitietStmt = $pdo->prepare($chitietSql);
                        $chitietStmt->execute([$newStatus, $chitietId]);
                    }
                } catch (Exception $e) {
                    error_log("ERROR: updateProgress - Failed to sync chitietkehoach: " . $e->getMessage());
                }
            }
        }
        
        // Auto-update plan status based on step statuses
        if ($result) {
            try {
                updatePlanStatusFromSteps($pdo, $assignmentId, $sourceTable);
            } catch (Exception $e) {
                error_log("ERROR: updateProgress - Failed to update plan status: " . $e->getMessage());
            }
        }
        
        // Send progress report to manager (Role 2) whenever progress is updated
        if ($result) {
            try {
                // Get staff ID
                $userId = $_SESSION['user']['ID_User'];
                $stmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?");
                $stmt->execute([$userId]);
                $staffId = $stmt->fetchColumn();
                
                if ($staffId) {
                    // Get manager ID (Role 2) - get the first available manager
                    $stmt = $pdo->prepare("
                        SELECT nv.ID_NhanVien 
                        FROM nhanvieninfo nv 
                        JOIN users u ON nv.ID_User = u.ID_User 
                        WHERE u.ID_Role = 2 
                        LIMIT 1
                    ");
                    $stmt->execute();
                    $managerId = $stmt->fetchColumn();
                    
                    if ($managerId) {
                        // Create baocaotiendo table if not exists (match database structure)
                        try {
                            $pdo->exec("
                                CREATE TABLE IF NOT EXISTS baocaotiendo (
                                    ID_BaoCao INT AUTO_INCREMENT PRIMARY KEY,
                                    ID_NhanVien INT NOT NULL,
                                    ID_QuanLy INT NOT NULL,
                                    ID_Task INT NOT NULL,
                                    LoaiTask ENUM('lichlamviec', 'chitietkehoach') NOT NULL,
                                    TienDo INT DEFAULT 0,
                                    GhiChu TEXT DEFAULT NULL,
                                    TrangThai VARCHAR(50) DEFAULT NULL,
                                    NgayBaoCao DATETIME DEFAULT CURRENT_TIMESTAMP
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                            ");
                        } catch (Exception $e) {
                            // Table might already exist, continue
                            error_log("DEBUG: updateProgress - Table creation: " . $e->getMessage());
                        }
                        
                        // Prepare progress report note
                        $reportNote = $note ? $note : "Cập nhật tiến độ: {$progressInt}%";
                        if ($progressInt >= 100) {
                            $reportNote .= " - Công việc đã hoàn thành";
                        } elseif ($progressInt == 0) {
                            $reportNote .= " - Công việc chưa bắt đầu";
                        }
                        
                        // Set report status based on progress
                        if ($progressInt >= 100) {
                            $reportStatus = 'Hoàn thành';
                        } elseif ($progressInt > 0) {
                            $reportStatus = 'Đang xử lý';
                        } else {
                            $reportStatus = 'Chưa bắt đầu';
                        }
                        
                        // Insert or update progress report
                        // Check if report already exists for this task
                        $checkStmt = $pdo->prepare("
                            SELECT ID_BaoCao FROM baocaotiendo 
                            WHERE ID_NhanVien = ? AND ID_Task = ? AND LoaiTask = ?
                            ORDER BY NgayBaoCao DESC LIMIT 1
                        ");
                        $checkStmt->execute([$staffId, $assignmentId, $sourceTable]);
                        $existingReport = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existingReport) {
                            // Update existing report
                            $updateStmt = $pdo->prepare("
                                UPDATE baocaotiendo 
                                SET TienDo = ?, 
                                    GhiChu = ?, 
                                    TrangThai = ?,
                                    NgayBaoCao = NOW()
                                WHERE ID_BaoCao = ?
                            ");
                            $updateStmt->execute([
                                $progressInt, 
                                $reportNote, 
                                $reportStatus,
                                $existingReport['ID_BaoCao']
                            ]);
                            error_log("DEBUG: updateProgress - Updated existing progress report ID: " . $existingReport['ID_BaoCao']);
                        } else {
                            // Insert new report
                            $insertStmt = $pdo->prepare("
                                INSERT INTO baocaotiendo (ID_NhanVien, ID_QuanLy, ID_Task, LoaiTask, TienDo, GhiChu, TrangThai, NgayBaoCao)
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                            ");
                            $insertStmt->execute([
                                $staffId, 
                                $managerId, 
                                $assignmentId, 
                                $sourceTable, 
                                $progressInt, 
                                $reportNote, 
                                $reportStatus
                            ]);
                            error_log("DEBUG: updateProgress - Created new progress report ID: " . $pdo->lastInsertId());
                        }
                        
                        error_log("DEBUG: updateProgress - Progress report sent to manager ID: " . $managerId . " (Staff ID: " . $staffId . ", Task ID: " . $assignmentId . ", Progress: " . $progressInt . "%)");
                    } else {
                        error_log("WARNING: updateProgress - No manager (Role 2) found to send progress report");
                    }
                }
            } catch (Exception $e) {
                error_log("ERROR: updateProgress - Failed to send progress report: " . $e->getMessage());
                error_log("ERROR: updateProgress - Stack trace: " . $e->getTraceAsString());
                // Don't fail the whole operation if report sending fails
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Cập nhật tiến độ thành công' . ($progressInt >= 100 ? ' và đã gửi báo cáo cho quản lý' : ''),
            'progress' => $progressInt,
            'status' => $newStatus ?? ''
        ]);
        
    } catch (Exception $e) {
        error_log("ERROR: updateProgress - " . $e->getMessage());
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật tiến độ: ' . $e->getMessage()]);
    }
}

function reportIssue() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? '';
        $sourceTable = $_POST['sourceTable'] ?? 'lichlamviec';
        $note = $_POST['note'] ?? '';
        
        // Debug logs
        error_log("DEBUG: reportIssue - assignmentId: " . $assignmentId);
        error_log("DEBUG: reportIssue - sourceTable: " . $sourceTable);
        error_log("DEBUG: reportIssue - note: " . $note);
        
        if (empty($assignmentId) || empty($note)) {
            error_log("ERROR: reportIssue - Missing required fields");
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Get staff info
        $userId = $_SESSION['user']['ID_User'];
        $stmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $staffId = $stmt->fetchColumn();
        
        if (!$staffId) {
            throw new Exception("Staff not found");
        }
        
        // Get manager ID (Role 2) for this staff
        $stmt = $pdo->prepare("
            SELECT nv.ID_NhanVien 
            FROM nhanvieninfo nv 
            JOIN users u ON nv.ID_User = u.ID_User 
            WHERE u.ID_Role = 2 
            LIMIT 1
        ");
        $stmt->execute();
        $managerId = $stmt->fetchColumn();
        
        if (!$managerId) {
            throw new Exception("Manager not found");
        }
        
        // Create baocaosuco table if not exists (match database structure)
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
            // Table might already exist, continue
            error_log("DEBUG: reportIssue - Table creation: " . $e->getMessage());
        }
        
        // Get task title
        $taskTitle = '';
        if ($sourceTable === 'chitietkehoach') {
            $stmt = $pdo->prepare("SELECT TenBuoc FROM chitietkehoach WHERE ID_ChiTiet = ?");
            $stmt->execute([$assignmentId]);
            $taskTitle = $stmt->fetchColumn() ?: 'Công việc không xác định';
        } else {
            $stmt = $pdo->prepare("SELECT NhiemVu FROM lichlamviec WHERE ID_LLV = ?");
            $stmt->execute([$assignmentId]);
            $taskTitle = $stmt->fetchColumn() ?: 'Công việc không xác định';
        }
        
        // Insert issue report
        $stmt = $pdo->prepare("
            INSERT INTO baocaosuco (ID_NhanVien, ID_QuanLy, ID_Task, LoaiTask, TieuDe, MoTa, MucDo, TrangThai, NgayBaoCao)
            VALUES (?, ?, ?, ?, ?, ?, 'Trung bình', 'Mới', NOW())
        ");
        $result = $stmt->execute([
            $staffId,
            $managerId,
            $assignmentId,
            $sourceTable,
            "Báo sự cố: " . $taskTitle,
            $note
        ]);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log("ERROR: reportIssue - SQL Error: " . json_encode($errorInfo));
            throw new Exception("Không thể lưu báo cáo sự cố: " . ($errorInfo[2] ?? 'Unknown error'));
        }
        
        $reportId = $pdo->lastInsertId();
        error_log("DEBUG: reportIssue - Issue report created with ID: " . $reportId);
        error_log("DEBUG: reportIssue - Staff ID: " . $staffId . ", Manager ID: " . $managerId);
        
        // Update task status
        if ($sourceTable === 'chitietkehoach') {
            $sql = "UPDATE chitietkehoach SET TrangThai = 'Báo sự cố' WHERE ID_ChiTiet = ?";
            $params = [$assignmentId];
        } else {
            $sql = "UPDATE lichlamviec SET TrangThai = 'Báo sự cố', GhiChu = ? WHERE ID_LLV = ?";
            $params = [$note, $assignmentId];
        }
        
        $stmt = $pdo->prepare($sql);
        $updateResult = $stmt->execute($params);
        
        error_log("DEBUG: reportIssue - Issue report saved successfully");
        error_log("DEBUG: reportIssue - Task status updated: " . ($updateResult ? 'true' : 'false'));
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Báo sự cố thành công và đã gửi đến quản lý']);
        
    } catch (Exception $e) {
        error_log("ERROR: reportIssue - Exception: " . $e->getMessage());
        error_log("ERROR: reportIssue - Stack trace: " . $e->getTraceAsString());
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi báo sự cố: ' . $e->getMessage()]);
    }
}

function startWork() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? '';
        $sourceTable = $_POST['sourceTable'] ?? 'lichlamviec';
        $note = $_POST['note'] ?? '';
        
        // Debug logs
        error_log("DEBUG: startWork - assignmentId: " . $assignmentId);
        error_log("DEBUG: startWork - sourceTable: " . $sourceTable);
        error_log("DEBUG: startWork - note: " . $note);
        
        if (empty($assignmentId)) {
            error_log("ERROR: startWork - Missing assignmentId");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin công việc']);
            return;
        }
        
        $currentTime = date('Y-m-d H:i:s');
        error_log("DEBUG: startWork - currentTime: " . $currentTime);
        
        // Try to update with basic fields first
        if ($sourceTable === 'chitietkehoach') {
            $sql = "
                UPDATE chitietkehoach 
                SET TrangThai = 'Đang làm'
                WHERE ID_ChiTiet = ?
            ";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$assignmentId]);
        } else {
            $sql = "
                UPDATE lichlamviec 
                SET TrangThai = 'Đang làm'
                WHERE ID_LLV = ?
            ";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$assignmentId]);
            
            // If updating lichlamviec, also update corresponding chitietkehoach
            if ($result) {
                try {
                    // Get the corresponding chitietkehoach ID
                    $stmt = $pdo->prepare("SELECT ID_ChiTiet FROM lichlamviec WHERE ID_LLV = ?");
                    $stmt->execute([$assignmentId]);
                    $chitietId = $stmt->fetchColumn();
                    
                    if ($chitietId) {
                        // Update chitietkehoach status
                        $stmt = $pdo->prepare("UPDATE chitietkehoach SET TrangThai = 'Đang làm' WHERE ID_ChiTiet = ?");
                        $stmt->execute([$chitietId]);
                        error_log("DEBUG: startWork - Updated chitietkehoach ID: " . $chitietId);
                    }
                } catch (Exception $e) {
                    error_log("ERROR: startWork - Failed to update chitietkehoach: " . $e->getMessage());
                }
            }
        }
        
        error_log("DEBUG: startWork - SQL: " . $sql);
        error_log("DEBUG: startWork - Params: " . json_encode([$assignmentId]));
        error_log("DEBUG: startWork - Update result: " . ($result ? 'true' : 'false'));
        error_log("DEBUG: startWork - Rows affected: " . $stmt->rowCount());
        
        // Additional debug: Check current status before update
        if ($sourceTable === 'chitietkehoach') {
            $checkStmt = $pdo->prepare("SELECT TrangThai FROM chitietkehoach WHERE ID_ChiTiet = ?");
            $checkStmt->execute([$assignmentId]);
            $currentStatus = $checkStmt->fetchColumn();
            error_log("DEBUG: startWork - Current chitietkehoach status: " . $currentStatus);
        } else {
            $checkStmt = $pdo->prepare("SELECT TrangThai FROM lichlamviec WHERE ID_LLV = ?");
            $checkStmt->execute([$assignmentId]);
            $currentStatus = $checkStmt->fetchColumn();
            error_log("DEBUG: startWork - Current lichlamviec status: " . $currentStatus);
        }
        
        if (!$result) {
            error_log("ERROR: startWork - Failed to execute update");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái công việc']);
            return;
        }
        
        // Success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Bắt đầu làm việc thành công']);
        
    } catch (Exception $e) {
        error_log("ERROR: startWork - Exception: " . $e->getMessage());
        error_log("ERROR: startWork - Stack trace: " . $e->getTraceAsString());
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi bắt đầu làm việc: ' . $e->getMessage()]);
    }
}

function completeWork() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? '';
        $sourceTable = $_POST['sourceTable'] ?? 'lichlamviec';
        $progress = $_POST['progress'] ?? 100;
        $note = $_POST['note'] ?? '';
        
        // Debug logs
        error_log("DEBUG: completeWork - assignmentId: " . $assignmentId);
        error_log("DEBUG: completeWork - sourceTable: " . $sourceTable);
        error_log("DEBUG: completeWork - progress: " . $progress);
        error_log("DEBUG: completeWork - note: " . $note);
        
        if (empty($assignmentId)) {
            error_log("ERROR: completeWork - Missing assignmentId");
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin công việc']);
            return;
        }
        
        $currentTime = date('Y-m-d H:i:s');
        error_log("DEBUG: completeWork - currentTime: " . $currentTime);
        
        // Get deadline to check if late
        if ($sourceTable === 'chitietkehoach') {
            $stmt = $pdo->prepare("SELECT NgayKetThuc FROM chitietkehoach WHERE ID_ChiTiet = ?");
            $stmt->execute([$assignmentId]);
            $deadline = $stmt->fetchColumn();
            error_log("DEBUG: completeWork - chitietkehoach deadline: " . $deadline);
        } else {
            $stmt = $pdo->prepare("SELECT NgayKetThuc FROM lichlamviec WHERE ID_LLV = ?");
            $stmt->execute([$assignmentId]);
            $deadline = $stmt->fetchColumn();
            error_log("DEBUG: completeWork - lichlamviec deadline: " . $deadline);
        }
        
        $isLate = $currentTime > $deadline;
        error_log("DEBUG: completeWork - isLate: " . ($isLate ? 'true' : 'false'));
        
        if ($sourceTable === 'chitietkehoach') {
            // chitietkehoach only has basic columns: ID_ChiTiet, ID_KeHoach, TenBuoc, MoTa, ID_NhanVien, NgayBatDau, NgayKetThuc, TrangThai
            $sql = "
                UPDATE chitietkehoach 
                SET TrangThai = 'Hoàn thành'
                WHERE ID_ChiTiet = ?
            ";
            $params = [$assignmentId];
            error_log("DEBUG: completeWork - chitietkehoach SQL: " . $sql);
            error_log("DEBUG: completeWork - chitietkehoach Params: " . json_encode($params));
        } else {
            // lichlamviec has columns: ThoiGianHoanThanh (datetime), TienDo (varchar), GhiChu (text)
            $sql = "
                UPDATE lichlamviec 
                SET TrangThai = 'Hoàn thành', 
                    ThoiGianHoanThanh = ?, 
                    TienDo = ?
                WHERE ID_LLV = ?
            ";
            $params = [$currentTime, $progress . '%', $assignmentId];
            error_log("DEBUG: completeWork - lichlamviec SQL: " . $sql);
            error_log("DEBUG: completeWork - lichlamviec Params: " . json_encode($params));
        }
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        error_log("DEBUG: completeWork - Update result: " . ($result ? 'true' : 'false'));
        error_log("DEBUG: completeWork - Rows affected: " . $stmt->rowCount());
        
        if (!$result) {
            error_log("ERROR: completeWork - Failed to execute update");
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái công việc']);
            return;
        }
        
        // If updating lichlamviec, also update corresponding chitietkehoach
        if ($sourceTable === 'lichlamviec') {
            try {
                // Get the corresponding chitietkehoach ID
                $stmt = $pdo->prepare("SELECT ID_ChiTiet FROM lichlamviec WHERE ID_LLV = ?");
                $stmt->execute([$assignmentId]);
                $chitietId = $stmt->fetchColumn();
                
                if ($chitietId) {
                    // Update chitietkehoach status
                    $stmt = $pdo->prepare("UPDATE chitietkehoach SET TrangThai = 'Hoàn thành' WHERE ID_ChiTiet = ?");
                    $stmt->execute([$chitietId]);
                    error_log("DEBUG: completeWork - Updated chitietkehoach ID: " . $chitietId);
                }
            } catch (Exception $e) {
                error_log("ERROR: completeWork - Failed to update chitietkehoach: " . $e->getMessage());
            }
        }
        
        // Auto-update plan status based on step statuses
        try {
            updatePlanStatusFromSteps($pdo, $assignmentId, $sourceTable);
        } catch (Exception $e) {
            error_log("ERROR: completeWork - Failed to update plan status: " . $e->getMessage());
        }
        
        // Send progress report to manager
        try {
            // Get staff ID
            $userId = $_SESSION['user']['ID_User'];
            $stmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?");
            $stmt->execute([$userId]);
            $staffId = $stmt->fetchColumn();
            
            if ($staffId) {
                // Get manager ID (Role 2) - get the first available manager
                $stmt = $pdo->prepare("
                    SELECT nv.ID_NhanVien 
                    FROM nhanvieninfo nv 
                    JOIN users u ON nv.ID_User = u.ID_User 
                    WHERE u.ID_Role = 2 
                    LIMIT 1
                ");
                $stmt->execute();
                $managerId = $stmt->fetchColumn();
                
                if ($managerId) {
                    // Create baocaotiendo table if not exists (match database structure)
                    try {
                    $pdo->exec("
                        CREATE TABLE IF NOT EXISTS baocaotiendo (
                            ID_BaoCao INT AUTO_INCREMENT PRIMARY KEY,
                            ID_NhanVien INT NOT NULL,
                            ID_QuanLy INT NOT NULL,
                            ID_Task INT NOT NULL,
                            LoaiTask ENUM('lichlamviec', 'chitietkehoach') NOT NULL,
                                TienDo INT DEFAULT 0,
                                GhiChu TEXT DEFAULT NULL,
                                TrangThai VARCHAR(50) DEFAULT NULL,
                                NgayBaoCao DATETIME DEFAULT CURRENT_TIMESTAMP
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                        ");
                    } catch (Exception $e) {
                        // Table might already exist, continue
                        error_log("DEBUG: completeWork - Table creation: " . $e->getMessage());
                    }
                    
                    // Prepare report note
                    $reportNote = $note ? $note : "Hoàn thành công việc - Tiến độ: 100%";
                    
                    // Check if report already exists for this task
                    $checkStmt = $pdo->prepare("
                        SELECT ID_BaoCao FROM baocaotiendo 
                        WHERE ID_NhanVien = ? AND ID_Task = ? AND LoaiTask = ?
                        ORDER BY NgayBaoCao DESC LIMIT 1
                    ");
                    $checkStmt->execute([$staffId, $assignmentId, $sourceTable]);
                    $existingReport = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingReport) {
                        // Update existing report
                        $updateStmt = $pdo->prepare("
                            UPDATE baocaotiendo 
                            SET TienDo = ?, 
                                GhiChu = ?, 
                                TrangThai = 'Hoàn thành',
                                NgayBaoCao = NOW()
                            WHERE ID_BaoCao = ?
                        ");
                        $updateStmt->execute([
                            100, 
                            $reportNote, 
                            $existingReport['ID_BaoCao']
                        ]);
                        error_log("DEBUG: completeWork - Updated existing progress report ID: " . $existingReport['ID_BaoCao']);
                    } else {
                        // Insert new report
                        $insertStmt = $pdo->prepare("
                            INSERT INTO baocaotiendo (ID_NhanVien, ID_QuanLy, ID_Task, LoaiTask, TienDo, GhiChu, TrangThai, NgayBaoCao)
                            VALUES (?, ?, ?, ?, ?, ?, 'Hoàn thành', NOW())
                        ");
                        $insertStmt->execute([
                            $staffId, 
                            $managerId, 
                            $assignmentId, 
                            $sourceTable, 
                            100, 
                            $reportNote
                        ]);
                        error_log("DEBUG: completeWork - Created new progress report ID: " . $pdo->lastInsertId());
                    }
                    
                    error_log("DEBUG: completeWork - Progress report sent to manager ID: " . $managerId);
                } else {
                    error_log("WARNING: completeWork - No manager (Role 2) found to send progress report");
                }
            }
        } catch (Exception $e) {
            error_log("ERROR: completeWork - Failed to send progress report: " . $e->getMessage());
            error_log("ERROR: completeWork - Stack trace: " . $e->getTraceAsString());
            // Don't fail the whole operation if report sending fails
            // But log the error for debugging
        }
        
        $message = 'Hoàn thành công việc thành công';
        if ($isLate) {
            $message .= ' (Chậm tiến độ)';
        }
        
        error_log("DEBUG: completeWork - Success: " . $message);
        
        // Check and update event status if all steps are completed
        try {
        checkAndUpdateEventStatus($pdo, $assignmentId, $sourceTable);
        } catch (Exception $e) {
            error_log("ERROR: completeWork - Failed to check event status: " . $e->getMessage());
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $message, 'isLate' => $isLate]);
        
    } catch (Exception $e) {
        error_log("ERROR: completeWork - Exception: " . $e->getMessage());
        error_log("ERROR: completeWork - Stack trace: " . $e->getTraceAsString());
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi hoàn thành công việc: ' . $e->getMessage()]);
    }
}

function getEventDetails() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? $_GET['assignmentId'] ?? '';
        $eventId = $_GET['event_id'] ?? '';
        
        error_log("DEBUG: getEventDetails - assignmentId: " . $assignmentId);
        error_log("DEBUG: getEventDetails - eventId: " . $eventId);
        
        // If assignmentId is provided, get eventId from assignment
        if (!empty($assignmentId)) {
            // Try to get from chitietkehoach first
            $stmt = $pdo->prepare("
                SELECT dl.ID_DatLich 
                FROM chitietkehoach ck
                LEFT JOIN kehoachthuchien kht ON ck.ID_KeHoach = kht.ID_KeHoach
                LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
                LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
                WHERE ck.ID_ChiTiet = ?
            ");
            $stmt->execute([$assignmentId]);
            $eventId = $stmt->fetchColumn();
            
            if (!$eventId) {
                // Try to get from lichlamviec
                $stmt = $pdo->prepare("
                    SELECT ID_DatLich 
                    FROM lichlamviec 
                    WHERE ID_LLV = ?
                ");
                $stmt->execute([$assignmentId]);
                $eventId = $stmt->fetchColumn();
            }
        }
        
        if (empty($eventId)) {
            error_log("ERROR: getEventDetails - No eventId found");
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện']);
            return;
        }
        
        error_log("DEBUG: getEventDetails - Final eventId: " . $eventId);
        
        // Get event details
        $stmt = $pdo->prepare("
            SELECT 
                dl.*,
                dd.TenDiaDiem,
                dd.DiaChi,
                dd.MoTa as DiaDiemMoTa,
                dd.SucChua,
                dd.GiaThue,
                ls.TenLoai as TenLoaiSK,
                ls.MoTa as LoaiSKMoTa,
                kh.HoTen as TenKhachHang,
                kh.SoDienThoai,
                kh.DiaChi as KhachHangDiaChi,
                s.ID_SuKien
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
            WHERE dl.ID_DatLich = ?
        ");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            error_log("ERROR: getEventDetails - Event not found for ID: " . $eventId);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện']);
            return;
        }
        
        error_log("DEBUG: getEventDetails - Event found: " . json_encode($event));
        
        // Get registered equipment (check if ID_SuKien exists)
        $equipment = [];
        if (!empty($event['ID_SuKien'])) {
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        st.ID_SuKien_ThietBi,
                        t.TenThietBi,
                        t.MoTa,
                        t.GiaThue,
                        t.TrangThai as ThietBiTrangThai,
                        st.SoLuong,
                        st.GhiChu
                    FROM sukien_thietbi st
                    LEFT JOIN thietbi t ON st.ID_ThietBi = t.ID_ThietBi
                    WHERE st.ID_SuKien = ?
                ");
                $stmt->execute([$event['ID_SuKien']]);
                $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("DEBUG: Found " . count($equipment) . " equipment items");
            } catch (Exception $e) {
                error_log("DEBUG: Error getting equipment: " . $e->getMessage());
                $equipment = [];
            }
        } else {
            error_log("DEBUG: No ID_SuKien found for event");
        }
        
        // Get combo equipment (check if tables exist first)
        $combos = [];
        $comboEquipment = [];
        
        try {
            // Check if sukien_combo table exists and ID_SuKien is available
            if (!empty($event['ID_SuKien'])) {
                $stmt = $pdo->query("SHOW TABLES LIKE 'sukien_combo'");
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("
                        SELECT 
                            sc.ID_SuKien,
                            c.ID_Combo,
                            c.TenCombo,
                            c.MoTa as ComboMoTa,
                            c.GiaCombo,
                            sc.SoLuong,
                            sc.GhiChu
                        FROM sukien_combo sc
                        LEFT JOIN combo c ON sc.ID_Combo = c.ID_Combo
                        WHERE sc.ID_SuKien = ?
                    ");
                    $stmt->execute([$event['ID_SuKien']]);
                    $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("DEBUG: Found " . count($combos) . " combos");
                    
                    // Get combo equipment details
                    foreach ($combos as $combo) {
                        try {
                            $stmt = $pdo->prepare("
                                SELECT 
                                    ct.ID_Combo,
                                    t.TenThietBi,
                                    t.MoTa,
                                    t.GiaThue,
                                    t.TrangThai,
                                    ct.SoLuong
                                FROM combo_thietbi ct
                                LEFT JOIN thietbi t ON ct.ID_TB = t.ID_TB
                                WHERE ct.ID_Combo = ?
                            ");
                            $stmt->execute([$combo['ID_Combo']]);
                            $comboEquipment[$combo['ID_Combo']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            error_log("DEBUG: Found " . count($comboEquipment[$combo['ID_Combo']]) . " equipment items for combo " . $combo['ID_Combo']);
                        } catch (Exception $e) {
                            error_log("DEBUG: Error getting combo equipment for combo " . $combo['ID_Combo'] . ": " . $e->getMessage());
                            $comboEquipment[$combo['ID_Combo']] = [];
                        }
                    }
                } else {
                    error_log("DEBUG: sukien_combo table does not exist");
                }
            } else {
                error_log("DEBUG: No ID_SuKien for combo queries");
            }
        } catch (Exception $e) {
            error_log("DEBUG: Error getting combo data: " . $e->getMessage());
        }
        
        // Get registration details
        $registration = null;
        if (!empty($event['ID_DatLich'])) {
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        dl.NgayTao as NgayDangKy,
                        dl.TrangThaiDuyet,
                        dl.NgayCapNhat as NgayDuyet,
                        dl.GhiChu as DangKyGhiChu,
                        dl.GhiChu as LyDoTuChoi,
                        nv.HoTen as NguoiDuyet
                    FROM datlichsukien dl
                    LEFT JOIN nhanvieninfo nv ON dl.ID_NhanVienDuyet = nv.ID_NhanVien
                    WHERE dl.ID_DatLich = ?
                ");
                $stmt->execute([$event['ID_DatLich']]);
                $registration = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("DEBUG: Found registration details");
            } catch (Exception $e) {
                error_log("DEBUG: Error getting registration details: " . $e->getMessage());
            }
        }
        
        $event['equipment'] = $equipment;
        $event['combos'] = $combos;
        $event['comboEquipment'] = $comboEquipment;
        $event['registration'] = $registration;
        
        error_log("DEBUG: getEventDetails - Equipment count: " . count($equipment));
        error_log("DEBUG: getEventDetails - Combos count: " . count($combos));
        error_log("DEBUG: getEventDetails - Registration: " . ($registration ? 'Found' : 'Not found'));
        
        // Ensure we always return JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'event' => $event]);
        
    } catch (Exception $e) {
        error_log("ERROR: getEventDetails - Exception: " . $e->getMessage());
        error_log("ERROR: getEventDetails - Stack trace: " . $e->getTraceAsString());
        
        // Clear any output buffer and ensure we return JSON
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy chi tiết sự kiện: ' . $e->getMessage()]);
    }
}

/**
 * Check and update event status if all steps are completed
 */
function checkAndUpdateEventStatus($pdo, $assignmentId, $sourceTable) {
    try {
        error_log("DEBUG: checkAndUpdateEventStatus - assignmentId: " . $assignmentId . ", sourceTable: " . $sourceTable);
        
        // Get event ID from the completed step
        $eventId = null;
        
        if ($sourceTable === 'chitietkehoach') {
            // Get event ID from chitietkehoach -> kehoachthuchien -> sukien
            $stmt = $pdo->prepare("
                SELECT s.ID_SuKien 
                FROM chitietkehoach ctk
                JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
                JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
                WHERE ctk.ID_ChiTiet = ?
            ");
            $stmt->execute([$assignmentId]);
            $eventId = $stmt->fetchColumn();
        } else if ($sourceTable === 'lichlamviec') {
            // Get event ID from lichlamviec -> datlichsukien
            $stmt = $pdo->prepare("
                SELECT ID_DatLich 
                FROM lichlamviec 
                WHERE ID_LLV = ?
            ");
            $stmt->execute([$assignmentId]);
            $datLichId = $stmt->fetchColumn();
            
            if ($datLichId) {
                // Get event ID from datlichsukien -> sukien
                $stmt = $pdo->prepare("
                    SELECT s.ID_SuKien 
                    FROM datlichsukien dl
                    JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
                    WHERE dl.ID_DatLich = ?
                ");
                $stmt->execute([$datLichId]);
                $eventId = $stmt->fetchColumn();
            }
        }
        
        if (!$eventId) {
            error_log("DEBUG: checkAndUpdateEventStatus - No event ID found");
            return;
        }
        
        error_log("DEBUG: checkAndUpdateEventStatus - Event ID: " . $eventId);
        
        // Check if all steps for this event are completed
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_steps,
                SUM(CASE WHEN TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_steps
            FROM (
                SELECT TrangThai FROM chitietkehoach ctk
                JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
                WHERE kht.ID_SuKien = ?
                
                UNION ALL
                
                SELECT TrangThai FROM lichlamviec llv
                JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
                JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
                WHERE s.ID_SuKien = ?
            ) as all_steps
        ");
        $stmt->execute([$eventId, $eventId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalSteps = $result['total_steps'];
        $completedSteps = $result['completed_steps'];
        
        error_log("DEBUG: checkAndUpdateEventStatus - Total steps: " . $totalSteps . ", Completed: " . $completedSteps);
        
        // If all steps are completed, update event status
        if ($totalSteps > 0 && $totalSteps == $completedSteps) {
            error_log("DEBUG: checkAndUpdateEventStatus - All steps completed, updating event status");
            
            // Update event status to completed
            $stmt = $pdo->prepare("UPDATE sukien SET TrangThaiThucTe = 'Hoàn thành' WHERE ID_SuKien = ?");
            $stmt->execute([$eventId]);
            
            error_log("DEBUG: checkAndUpdateEventStatus - Event status updated to 'Hoàn thành'");
        } else {
            error_log("DEBUG: checkAndUpdateEventStatus - Not all steps completed yet");
        }
        
    } catch (Exception $e) {
        error_log("ERROR: checkAndUpdateEventStatus - Exception: " . $e->getMessage());
    }
}

/**
 * Auto-update plan status (kehoachthuchien) based on step statuses (chitietkehoach)
 */
function updatePlanStatusFromSteps($pdo, $assignmentId, $sourceTable) {
    try {
        // Get plan ID from assignment
        $planId = null;
        
        if ($sourceTable === 'chitietkehoach') {
            // Get plan ID directly from chitietkehoach
            $stmt = $pdo->prepare("SELECT ID_KeHoach FROM chitietkehoach WHERE ID_ChiTiet = ?");
            $stmt->execute([$assignmentId]);
            $planId = $stmt->fetchColumn();
        } else if ($sourceTable === 'lichlamviec') {
            // Get plan ID from lichlamviec -> chitietkehoach -> kehoachthuchien
            $stmt = $pdo->prepare("
                SELECT kht.ID_KeHoach 
                FROM lichlamviec llv
                JOIN chitietkehoach ctk ON llv.ID_ChiTiet = ctk.ID_ChiTiet
                JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
                WHERE llv.ID_LLV = ?
            ");
            $stmt->execute([$assignmentId]);
            $planId = $stmt->fetchColumn();
        }
        
        if (!$planId) {
            error_log("DEBUG: updatePlanStatusFromSteps - No plan ID found for assignment: " . $assignmentId);
            return;
        }
        
        error_log("DEBUG: updatePlanStatusFromSteps - Plan ID: " . $planId);
        
        // Get all steps for this plan with their actual status from lichlamviec
        // A step is considered "Hoàn thành" only if ALL assigned staff have completed it
        // A step is considered "Đang làm" if at least one staff is working on it
        $stmt = $pdo->prepare("
            SELECT 
                ck.ID_ChiTiet,
                ck.TrangThai as chitiet_status,
                COUNT(llv.ID_LLV) as total_assignments,
                SUM(CASE WHEN llv.TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_assignments,
                SUM(CASE WHEN llv.TrangThai = 'Đang làm' THEN 1 ELSE 0 END) as inprogress_assignments,
                SUM(CASE WHEN llv.TrangThai = 'Chưa làm' THEN 1 ELSE 0 END) as notstarted_assignments
            FROM chitietkehoach ck
            LEFT JOIN lichlamviec llv ON ck.ID_ChiTiet = llv.ID_ChiTiet
            WHERE ck.ID_KeHoach = ?
            GROUP BY ck.ID_ChiTiet, ck.TrangThai
        ");
        $stmt->execute([$planId]);
        $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($steps)) {
            error_log("DEBUG: updatePlanStatusFromSteps - No steps found for plan: " . $planId);
            return;
        }
        
        $totalSteps = count($steps);
        $completedSteps = 0;
        $inProgressSteps = 0;
        
        foreach ($steps as $step) {
            $totalAssignments = (int)$step['total_assignments'];
            $completedAssignments = (int)$step['completed_assignments'];
            $inProgressAssignments = (int)$step['inprogress_assignments'];
            
            // If step has assignments in lichlamviec, use those to determine status
            if ($totalAssignments > 0) {
                // Step is completed only if ALL assignments are completed
                if ($completedAssignments === $totalAssignments && $totalAssignments > 0) {
                    $completedSteps++;
                } 
                // Step is in progress if at least one assignment is in progress or completed (but not all)
                else if ($inProgressAssignments > 0 || $completedAssignments > 0) {
                    $inProgressSteps++;
                }
            } else {
                // No assignments in lichlamviec, use chitietkehoach status
                if ($step['chitiet_status'] === 'Hoàn thành') {
                    $completedSteps++;
                } else if ($step['chitiet_status'] === 'Đang làm') {
                    $inProgressSteps++;
                }
            }
        }
        
        error_log("DEBUG: updatePlanStatusFromSteps - Total: $totalSteps, Completed: $completedSteps, In Progress: $inProgressSteps");
        
        // Determine new plan status
        $newPlanStatus = null;
        if ($completedSteps === $totalSteps && $totalSteps > 0) {
            // All steps completed
            $newPlanStatus = 'Hoàn thành';
        } else if ($inProgressSteps > 0 || $completedSteps > 0) {
            // At least one step is in progress or completed
            $newPlanStatus = 'Đang thực hiện';
        } else {
            // All steps are "Chưa làm"
            $newPlanStatus = 'Chưa bắt đầu';
        }
        
        // Update plan status
        if ($newPlanStatus) {
            $updateStmt = $pdo->prepare("
                UPDATE kehoachthuchien 
                SET TrangThai = ? 
                WHERE ID_KeHoach = ?
            ");
            $updateResult = $updateStmt->execute([$newPlanStatus, $planId]);
            
            if ($updateResult) {
                error_log("DEBUG: updatePlanStatusFromSteps - Plan status updated to: " . $newPlanStatus);
            } else {
                error_log("ERROR: updatePlanStatusFromSteps - Failed to update plan status");
            }
        }
    } catch (Exception $e) {
        error_log("ERROR: updatePlanStatusFromSteps - " . $e->getMessage());
    }
}
?>
