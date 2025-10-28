<?php
require_once __DIR__ . '/../../config/database.php';
require_once '../../src/auth/auth.php';

// Start session
session_start();

// Check if user is logged in and has role 2
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$user = getCurrentUser();
if ($user['ID_Quyen'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'get_assignments':
            getAssignments($pdo);
            break;
            
        case 'get_events':
            getEvents($pdo);
            break;
            
        case 'get_staff':
            getStaff($pdo);
            break;
            
        case 'create_assignment':
            createAssignment($pdo);
            break;
            
        case 'get_assignment':
            getAssignment($pdo);
            break;
            
        case 'update_assignment':
            updateAssignment($pdo);
            break;
            
        case 'delete_assignment':
            deleteAssignment($pdo);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            break;
    }
} catch (Exception $e) {
    error_log("Staff Assignment Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

function getAssignments($pdo) {
    try {
        // Get all staff assignments with event and staff information
        $sql = "
            SELECT 
                llv.ID_LLV,
                llv.NgayBatDau,
                llv.NgayKetThuc,
                llv.NhiemVu,
                llv.GhiChu,
                llv.TrangThai,
                llv.NgayTao,
                dl.ID_DatLich,
                dl.TenSuKien,
                dl.NgayBatDau as EventStartDate,
                dl.NgayKetThuc as EventEndDate,
                nv.ID_NhanVien,
                nv.HoTen,
                nv.ChucVu,
                nv.Email,
                nv.SoDienThoai,
                dd.TenDiaDiem,
                dd.DiaChi
            FROM lichlamviec llv
            JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
            JOIN nhanvieninfo nv ON llv.ID_NhanVien = nv.ID_NhanVien
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE dl.TrangThaiDuyet = 'Đã duyệt'
            ORDER BY llv.NgayTao DESC
        ";
        
        error_log("SQL Query: " . $sql);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Assignments found: " . count($assignments));
        
        echo json_encode([
            'success' => true,
            'assignments' => $assignments
        ]);
        
    } catch (Exception $e) {
        error_log("Get Assignments Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi tải danh sách phân công: ' . $e->getMessage()
        ]);
    }
}

function getEvents($pdo) {
    try {
        // Get all approved events that can be assigned
        $sql = "
            SELECT 
                dl.ID_DatLich,
                dl.TenSuKien,
                dl.MoTa,
                dl.NgayBatDau,
                dl.NgayKetThuc,
                dl.SoNguoiDuKien,
                dl.NganSach,
                dd.TenDiaDiem,
                dd.DiaChi,
                ls.TenLoai as TenLoaiSK
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            WHERE dl.TrangThaiDuyet = 'Đã duyệt'
            ORDER BY dl.NgayBatDau ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'events' => $events
        ]);
        
    } catch (Exception $e) {
        error_log("Get Events Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi tải danh sách sự kiện: ' . $e->getMessage()
        ]);
    }
}

function getStaff($pdo) {
    try {
        // Get all active staff members
        $sql = "
            SELECT 
                nv.ID_NhanVien,
                nv.HoTen,
                nv.ChucVu,
                nv.Email,
                nv.SoDienThoai,
                nv.TrangThai,
                u.TenDangNhap
            FROM nhanvieninfo nv
            JOIN users u ON nv.ID_User = u.ID_User
            WHERE nv.TrangThai = 'Hoạt động'
            ORDER BY nv.HoTen ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'staff' => $staff
        ]);
        
    } catch (Exception $e) {
        error_log("Get Staff Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi tải danh sách nhân viên: ' . $e->getMessage()
        ]);
    }
}

function createAssignment($pdo) {
    try {
        $eventId = $_POST['event_id'] ?? '';
        $staffId = $_POST['staff_id'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $taskDescription = $_POST['task_description'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($eventId) || empty($staffId) || empty($startDate) || empty($endDate) || empty($taskDescription)) {
            echo json_encode([
                'success' => false,
                'error' => 'Vui lòng điền đầy đủ thông tin bắt buộc'
            ]);
            return;
        }
        
        // Check if event exists and is approved
        $checkEventSql = "SELECT ID_DatLich FROM datlichsukien WHERE ID_DatLich = ? AND TrangThaiDuyet = 'Đã duyệt'";
        $checkEventStmt = $pdo->prepare($checkEventSql);
        $checkEventStmt->execute([$eventId]);
        
        if (!$checkEventStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'error' => 'Sự kiện không tồn tại hoặc chưa được duyệt'
            ]);
            return;
        }
        
        // Check if staff exists and is active
        $checkStaffSql = "SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_NhanVien = ? AND TrangThai = 'Hoạt động'";
        $checkStaffStmt = $pdo->prepare($checkStaffSql);
        $checkStaffStmt->execute([$staffId]);
        
        if (!$checkStaffStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'error' => 'Nhân viên không tồn tại hoặc không hoạt động'
            ]);
            return;
        }
        
        // Check if staff is already assigned to this event
        $existingSql = "SELECT ID_LLV FROM lichlamviec WHERE ID_DatLich = ? AND ID_NhanVien = ?";
        $existingStmt = $pdo->prepare($existingSql);
        $existingStmt->execute([$eventId, $staffId]);
        
        if ($existingStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'error' => 'Nhân viên này đã được phân công cho sự kiện này'
            ]);
            return;
        }
        
        // Create new assignment
        $insertSql = "
            INSERT INTO lichlamviec (ID_DatLich, ID_NhanVien, NgayBatDau, NgayKetThuc, NhiemVu, GhiChu, TrangThai, NgayTao)
            VALUES (?, ?, ?, ?, ?, ?, 'Đã phân công', NOW())
        ";
        
        $insertStmt = $pdo->prepare($insertSql);
        $result = $insertStmt->execute([$eventId, $staffId, $startDate, $endDate, $taskDescription, $notes]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Tạo phân công thành công'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi tạo phân công'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Create Assignment Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi tạo phân công: ' . $e->getMessage()
        ]);
    }
}

function getAssignment($pdo) {
    try {
        $assignmentId = $_GET['assignment_id'] ?? '';
        
        if (empty($assignmentId)) {
            echo json_encode([
                'success' => false,
                'error' => 'ID phân công không hợp lệ'
            ]);
            return;
        }
        
        $sql = "
            SELECT 
                llv.*,
                dl.TenSuKien,
                dl.MoTa,
                dl.NgayBatDau as EventStartDate,
                dl.NgayKetThuc as EventEndDate,
                nv.HoTen,
                nv.ChucVu,
                nv.Email,
                nv.SoDienThoai,
                dd.TenDiaDiem,
                dd.DiaChi
            FROM lichlamviec llv
            JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
            JOIN nhanvieninfo nv ON llv.ID_NhanVien = nv.ID_NhanVien
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE llv.ID_LLV = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$assignmentId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($assignment) {
            echo json_encode([
                'success' => true,
                'assignment' => $assignment
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Không tìm thấy phân công'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Get Assignment Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi tải phân công: ' . $e->getMessage()
        ]);
    }
}

function updateAssignment($pdo) {
    try {
        $assignmentId = $_POST['assignment_id'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $taskDescription = $_POST['task_description'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($assignmentId)) {
            echo json_encode([
                'success' => false,
                'error' => 'ID phân công không hợp lệ'
            ]);
            return;
        }
        
        $updateSql = "
            UPDATE lichlamviec 
            SET NgayBatDau = ?, NgayKetThuc = ?, NhiemVu = ?, GhiChu = ?, TrangThai = ?
            WHERE ID_LLV = ?
        ";
        
        $stmt = $pdo->prepare($updateSql);
        $result = $stmt->execute([$startDate, $endDate, $taskDescription, $notes, $status, $assignmentId]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật phân công thành công'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi cập nhật phân công'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Update Assignment Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi cập nhật phân công: ' . $e->getMessage()
        ]);
    }
}

function deleteAssignment($pdo) {
    try {
        $assignmentId = $_POST['assignment_id'] ?? '';
        
        if (empty($assignmentId)) {
            echo json_encode([
                'success' => false,
                'error' => 'ID phân công không hợp lệ'
            ]);
            return;
        }
        
        $deleteSql = "DELETE FROM lichlamviec WHERE ID_LLV = ?";
        $stmt = $pdo->prepare($deleteSql);
        $result = $stmt->execute([$assignmentId]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Xóa phân công thành công'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi xóa phân công'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Delete Assignment Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi xóa phân công: ' . $e->getMessage()
        ]);
    }
}
?>