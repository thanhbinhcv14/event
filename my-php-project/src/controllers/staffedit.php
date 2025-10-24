<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_staff':
        getStaff();
        break;
    case 'get_staff_details':
        getStaffDetails();
        break;
    case 'add_staff':
        addStaff();
        break;
    case 'update_staff':
        updateStaff();
        break;
    case 'delete_staff':
        deleteStaff();
        break;
    case 'get_staff_assignments':
        getStaffAssignments();
        break;
    case 'get_staff_stats':
        getStaffStats();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

function getStaff() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                nv.ID_NhanVien,
                nv.ID_User,
                nv.HoTen,
                nv.ChucVu,
                nv.SoDienThoai,
                nv.DiaChi,
                nv.NgaySinh,
                nv.Luong,
                nv.NgayVaoLam,
                nv.NgayTao,
                u.Email,
                u.ID_Role,
                u.TrangThai,
                COUNT(llv.ID_LLV) as SoCongViec
            FROM nhanvieninfo nv
            LEFT JOIN users u ON nv.ID_User = u.ID_User
            LEFT JOIN lichlamviec llv ON nv.ID_NhanVien = llv.ID_NhanVien
            GROUP BY nv.ID_NhanVien
            ORDER BY nv.NgayTao DESC
        ");
        $stmt->execute();
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'staff' => $staff]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage()]);
    }
}

function getStaffDetails() {
    try {
        $pdo = getDBConnection();
        
        $staffId = $_GET['staff_id'] ?? $_GET['id'] ?? $_POST['id'] ?? '';
        
        if (empty($staffId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin nhân viên']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                nv.*,
                u.Email,
                u.ID_Role,
                u.TrangThai as UserStatus,
                u.NgayTao as NgayDangKy
            FROM nhanvieninfo nv
            LEFT JOIN users u ON nv.ID_User = u.ID_User
            WHERE nv.ID_User = ?
        ");
        $stmt->execute([$staffId]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên']);
            return;
        }
        
        echo json_encode($staff);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy chi tiết nhân viên: ' . $e->getMessage()]);
    }
}

function addStaff() {
    try {
        $pdo = getDBConnection();
        
        $email = $_POST['Email'] ?? '';
        $password = $_POST['MatKhau'] ?? '';
        $fullName = $_POST['HoTen'] ?? '';
        $phone = $_POST['SoDienThoai'] ?? '';
        $address = $_POST['DiaChi'] ?? '';
        $birthday = $_POST['NgaySinh'] ?? '';
        $position = $_POST['ChucVu'] ?? '';
        $salary = $_POST['Luong'] ?? 0;
        $startDate = $_POST['NgayVaoLam'] ?? '';
        $role = $_POST['ID_Role'] ?? 4; // Default to staff role
        
        if (empty($email) || empty($password) || empty($fullName) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
            return;
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT ID_User FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng']);
            return;
        }
        
        $pdo->beginTransaction();
        
        try {
            // Insert user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (Email, Password, ID_Role, TrangThai, NgayTao)
                VALUES (?, ?, ?, 'Hoạt động', NOW())
            ");
            $stmt->execute([$email, $hashedPassword, $role]);
        $userId = $pdo->lastInsertId();
        
            // Insert staff info
            $stmt = $pdo->prepare("
                INSERT INTO nhanvieninfo (ID_User, HoTen, SoDienThoai, DiaChi, NgaySinh, ChucVu, Luong, NgayVaoLam, TrangThai, NgayTao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Hoạt động', NOW())
            ");
            $stmt->execute([$userId, $fullName, $phone, $address, $birthday, $position, $salary, $startDate]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Thêm nhân viên thành công']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm nhân viên: ' . $e->getMessage()]);
    }
}

function updateStaff() {
    try {
        $pdo = getDBConnection();
        
        $staffId = $_POST['id'] ?? '';
        $fullName = $_POST['HoTen'] ?? '';
        $phone = $_POST['SoDienThoai'] ?? '';
        $address = $_POST['DiaChi'] ?? '';
        $birthday = $_POST['NgaySinh'] ?? '';
        $position = $_POST['ChucVu'] ?? '';
        $salary = $_POST['Luong'] ?? 0;
        $startDate = $_POST['NgayVaoLam'] ?? '';
        $status = $_POST['TrangThai'] ?? '';
        $password = $_POST['MatKhau'] ?? '';
        
        if (empty($staffId) || empty($fullName) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $pdo->beginTransaction();
        
        try {
            // Update staff info
            $stmt = $pdo->prepare("
                UPDATE nhanvieninfo 
                SET HoTen = ?, SoDienThoai = ?, DiaChi = ?, NgaySinh = ?, ChucVu = ?, Luong = ?, NgayVaoLam = ?
                WHERE ID_User = ?
            ");
            $stmt->execute([$fullName, $phone, $address, $birthday, $position, $salary, $startDate, $staffId]);
            
            // Update user status and password if provided
            if (!empty($status) || !empty($password)) {
                $updateFields = [];
                $updateValues = [];
                
                if (!empty($status)) {
                    $updateFields[] = "TrangThai = ?";
                    $updateValues[] = $status;
                }
                
                if (!empty($password)) {
                    $updateFields[] = "MatKhau = ?";
                    $updateValues[] = password_hash($password, PASSWORD_DEFAULT);
                }
                
                $updateValues[] = $staffId;
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET " . implode(', ', $updateFields) . "
                    WHERE ID_User = ?
                ");
                $stmt->execute($updateValues);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin nhân viên thành công']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật nhân viên: ' . $e->getMessage()]);
    }
}

function deleteStaff() {
    try {
        $pdo = getDBConnection();
        
        $staffId = $_POST['id'] ?? '';
        
        if (empty($staffId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Check if staff has assignments
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lichlamviec WHERE ID_NhanVien = ?");
        $stmt->execute([$staffId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa nhân viên đã có công việc được phân công']);
            return;
        }
        
        $pdo->beginTransaction();
        
        try {
            // Delete staff info
            $stmt = $pdo->prepare("DELETE FROM nhanvieninfo WHERE ID_User = ?");
            $stmt->execute([$staffId]);
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE ID_User = ?");
            $stmt->execute([$staffId]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Xóa nhân viên thành công']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa nhân viên: ' . $e->getMessage()]);
    }
}

function getStaffAssignments() {
    try {
        $pdo = getDBConnection();
        
        $staffId = $_GET['staff_id'] ?? '';
        
        if (empty($staffId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin nhân viên']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                llv.ID_LLV,
                llv.CongViec,
                llv.HanHoanThanh,
                llv.Tiendo,
                llv.TrangThai,
                llv.GhiChu,
                llv.NgayTao,
                dl.TenSuKien,
                dl.NgayBatDau,
                dl.NgayKetThuc,
                dd.TenDiaDiem
            FROM lichlamviec llv
            LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE llv.ID_NhanVien = ?
            ORDER BY llv.HanHoanThanh ASC
        ");
        $stmt->execute([$staffId]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'assignments' => $assignments]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy công việc của nhân viên: ' . $e->getMessage()]);
    }
}

function getStaffStats() {
    try {
        $pdo = getDBConnection();
        
        // Total staff
        $stmt = $pdo->query("SELECT COUNT(*) FROM nhanvieninfo");
        $total = $stmt->fetchColumn();
        
        // Active staff
        $stmt = $pdo->query("SELECT COUNT(*) FROM nhanvieninfo nv JOIN users u ON nv.ID_User = u.ID_User WHERE u.TrangThai = 'Hoạt động'");
        $active = $stmt->fetchColumn();
        
        // Pending staff
        $stmt = $pdo->query("SELECT COUNT(*) FROM nhanvieninfo nv JOIN users u ON nv.ID_User = u.ID_User WHERE u.TrangThai = 'Chưa xác minh'");
        $pending = $stmt->fetchColumn();
        
        // Blocked staff
        $stmt = $pdo->query("SELECT COUNT(*) FROM nhanvieninfo nv JOIN users u ON nv.ID_User = u.ID_User WHERE u.TrangThai = 'Bị khóa'");
        $blocked = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total' => $total,
                'active' => $active,
                'pending' => $pending,
                'blocked' => $blocked
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê nhân viên: ' . $e->getMessage()]);
    }
}
?>