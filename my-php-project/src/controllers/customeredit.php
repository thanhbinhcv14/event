<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2, 3])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_customers':
        getCustomers();
        break;
    case 'get_customer_details':
        getCustomerDetails();
        break;
    case 'add_customer':
        addCustomer();
        break;
    case 'update_customer':
        updateCustomer();
        break;
    case 'delete_customer':
        deleteCustomer();
        break;
    case 'get_customer_events':
        getCustomerEvents();
        break;
    case 'get_customer_stats':
        getCustomerStats();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

function getCustomers() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                kh.ID_KhachHang,
                kh.ID_User,
                kh.HoTen,
                kh.SoDienThoai,
                kh.DiaChi,
                kh.NgaySinh,
                kh.NgayTao,
                u.Email,
                u.TrangThai,
                COUNT(dl.ID_DatLich) as event_count,
                MAX(dl.NgayTao) as last_event_date
            FROM khachhanginfo kh
            LEFT JOIN users u ON kh.ID_User = u.ID_User
            LEFT JOIN datlichsukien dl ON kh.ID_KhachHang = dl.ID_KhachHang
            GROUP BY kh.ID_KhachHang
            ORDER BY kh.NgayTao DESC
        ");
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'customers' => $customers]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách khách hàng: ' . $e->getMessage()]);
    }
}

function getCustomerDetails() {
    try {
        $pdo = getDBConnection();
        
        $customerId = $_GET['customer_id'] ?? $_GET['id'] ?? $_POST['id'] ?? '';
        
        if (empty($customerId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin khách hàng']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                kh.*,
                u.Email,
                u.TrangThai,
                u.NgayTao as NgayDangKy
            FROM khachhanginfo kh
            LEFT JOIN users u ON kh.ID_User = u.ID_User
            WHERE kh.ID_User = ?
        ");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng']);
            return;
        }
        
        echo json_encode(['success' => true, 'customer' => $customer]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy chi tiết khách hàng: ' . $e->getMessage()]);
    }
}

function addCustomer() {
    try {
        $pdo = getDBConnection();
        
        $email = $_POST['Email'] ?? '';
        $fullName = $_POST['HoTen'] ?? '';
        $phone = $_POST['SoDienThoai'] ?? '';
        $address = $_POST['DiaChi'] ?? '';
        $birthday = $_POST['NgaySinh'] ?? '';
        $status = $_POST['TrangThai'] ?? 'Hoạt động';
        
        if (empty($email) || empty($fullName) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT ID_User FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email đã tồn tại']);
            return;
        }
        
        $pdo->beginTransaction();
        
        try {
            // Create user account
            $stmt = $pdo->prepare("
                INSERT INTO users (Email, Password, ID_Role, TrangThai, NgayTao) 
                VALUES (?, ?, 5, ?, NOW())
            ");
            $defaultPassword = password_hash('123456', PASSWORD_DEFAULT); // Default password
            $stmt->execute([$email, $defaultPassword, $status]);
            $userId = $pdo->lastInsertId();
            
            // Create customer info
            $stmt = $pdo->prepare("
                INSERT INTO khachhanginfo (ID_User, HoTen, SoDienThoai, DiaChi, NgaySinh, NgayTao) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $fullName, $phone, $address, $birthday]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Thêm khách hàng thành công']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm khách hàng: ' . $e->getMessage()]);
    }
}

function updateCustomer() {
    try {
        $pdo = getDBConnection();
        
        $userId = $_POST['id'] ?? '';
        $fullName = $_POST['HoTen'] ?? '';
        $phone = $_POST['SoDienThoai'] ?? '';
        $address = $_POST['DiaChi'] ?? '';
        $birthday = $_POST['NgaySinh'] ?? '';
        $status = $_POST['TrangThai'] ?? '';
        
        if (empty($userId) || empty($fullName) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // First, get the customer info to find ID_KhachHang
        $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng']);
            return;
        }
        
        $khachHangId = $customer['ID_KhachHang'];
        
        $pdo->beginTransaction();
        
        try {
            // Update customer info
            $stmt = $pdo->prepare("
                UPDATE khachhanginfo 
                SET HoTen = ?, SoDienThoai = ?, DiaChi = ?, NgaySinh = ?
                WHERE ID_KhachHang = ?
            ");
            $stmt->execute([$fullName, $phone, $address, $birthday, $khachHangId]);
            
            // Update user status if provided
            if (!empty($status)) {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET TrangThai = ?
                    WHERE ID_User = ?
                ");
                $stmt->execute([$status, $userId]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin khách hàng thành công']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật khách hàng: ' . $e->getMessage()]);
    }
}

function deleteCustomer() {
    try {
        $pdo = getDBConnection();
        
        $customerId = $_POST['customer_id'] ?? $_POST['id'] ?? '';
        
        if (empty($customerId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // First, get the customer info to find ID_KhachHang
        $stmt = $pdo->prepare("SELECT ID_KhachHang, ID_User FROM khachhanginfo WHERE ID_User = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng']);
            return;
        }
        
        $khachHangId = $customer['ID_KhachHang'];
        
        // Check if customer has events
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM datlichsukien WHERE ID_KhachHang = ?");
        $stmt->execute([$khachHangId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa khách hàng đã có sự kiện']);
            return;
        }
        
        $pdo->beginTransaction();
        
        try {
            // Delete customer info
            $stmt = $pdo->prepare("DELETE FROM khachhanginfo WHERE ID_KhachHang = ?");
            $stmt->execute([$khachHangId]);
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE ID_User = ?");
            $stmt->execute([$customerId]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Xóa khách hàng thành công']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa khách hàng: ' . $e->getMessage()]);
    }
}

function getCustomerEvents() {
    try {
        $pdo = getDBConnection();
        
        $customerId = $_GET['customer_id'] ?? '';
        
        if (empty($customerId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin khách hàng']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                dl.ID_DatLich,
                dl.TenSuKien,
                dl.MoTa,
                dl.NgayBatDau,
                dl.NgayKetThuc,
                dl.TrangThaiDuyet,
                dl.TrangThaiThanhToan,
                dl.NganSach,
                dl.NgayTao,
                dd.TenDiaDiem,
                ls.TenLoai as TenLoaiSK
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            WHERE dl.ID_KhachHang = ?
            ORDER BY dl.NgayTao DESC
        ");
        $stmt->execute([$customerId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'events' => $events]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy sự kiện của khách hàng: ' . $e->getMessage()]);
    }
}

function getCustomerStats() {
    try {
        $pdo = getDBConnection();
        
        // Total customers
        $stmt = $pdo->query("SELECT COUNT(*) FROM khachhanginfo");
        $total = $stmt->fetchColumn();
        
        // Active customers
        $stmt = $pdo->query("SELECT COUNT(*) FROM khachhanginfo kh JOIN users u ON kh.ID_User = u.ID_User WHERE u.TrangThai = 'Hoạt động'");
        $active = $stmt->fetchColumn();
        
        // Pending customers
        $stmt = $pdo->query("SELECT COUNT(*) FROM khachhanginfo kh JOIN users u ON kh.ID_User = u.ID_User WHERE u.TrangThai = 'Chưa xác minh'");
        $pending = $stmt->fetchColumn();
        
        // Blocked customers
        $stmt = $pdo->query("SELECT COUNT(*) FROM khachhanginfo kh JOIN users u ON kh.ID_User = u.ID_User WHERE u.TrangThai = 'Bị khóa'");
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
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê khách hàng: ' . $e->getMessage()]);
    }
}
?>