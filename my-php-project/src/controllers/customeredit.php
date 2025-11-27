<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and has appropriate role (Chỉ Admin - role 1 và Quản lý sự kiện - role 3)
$userRole = isset($_SESSION['user']['ID_Role']) ? intval($_SESSION['user']['ID_Role']) : 0;
if (!isset($_SESSION['user']) || !in_array($userRole, [1, 3])) {
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

/**
 * Lấy danh sách khách hàng
 */
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
                kh.NgayCapNhat,
                u.Email,
                u.TrangThai,
                u.NgayTao as NgayDangKy,
                COUNT(dl.ID_DatLich) as event_count,
                MAX(dl.NgayTao) as last_event_date
            FROM khachhanginfo kh
            LEFT JOIN users u ON kh.ID_User = u.ID_User
            LEFT JOIN datlichsukien dl ON kh.ID_KhachHang = dl.ID_KhachHang
            GROUP BY kh.ID_KhachHang, kh.ID_User, kh.HoTen, kh.SoDienThoai, kh.DiaChi, 
                     kh.NgaySinh, kh.NgayTao, kh.NgayCapNhat, u.Email, u.TrangThai, u.NgayTao
            ORDER BY kh.NgayTao DESC
        ");
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'customers' => $customers]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách khách hàng: ' . $e->getMessage()]);
    }
}

/**
 * Lấy chi tiết khách hàng
 */
function getCustomerDetails() {
    try {
        $pdo = getDBConnection();
        
        $customerId = $_GET['id'] ?? $_GET['customer_id'] ?? $_POST['id'] ?? $_POST['customer_id'] ?? '';
        
        if (empty($customerId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin khách hàng']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                kh.ID_KhachHang,
                kh.ID_User,
                kh.HoTen,
                kh.SoDienThoai,
                kh.DiaChi,
                kh.NgaySinh,
                kh.NgayTao,
                kh.NgayCapNhat,
                u.Email,
                u.TrangThai,
                u.NgayTao as NgayDangKy,
                u.NgayCapNhat as NgayCapNhatUser
            FROM khachhanginfo kh
            LEFT JOIN users u ON kh.ID_User = u.ID_User
            WHERE kh.ID_User = ? OR kh.ID_KhachHang = ?
        ");
        $stmt->execute([$customerId, $customerId]);
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

/**
 * Thêm khách hàng mới
 */
function addCustomer() {
    try {
        $pdo = getDBConnection();
        
        // Lấy dữ liệu từ POST
        $email = trim($_POST['Email'] ?? '');
        $fullName = trim($_POST['HoTen'] ?? '');
        $phone = trim($_POST['SoDienThoai'] ?? '');
        $password = $_POST['MatKhau'] ?? '';
        $address = trim($_POST['DiaChi'] ?? '');
        $birthday = $_POST['NgaySinh'] ?? '';
        $status = $_POST['TrangThai'] ?? 'Hoạt động';
        
        // Validation: Kiểm tra các trường bắt buộc
        if (empty($email) || empty($fullName) || empty($phone) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
            return;
        }
        
        // Validation: Họ tên
        if (strlen($fullName) < 2 || strlen($fullName) > 100) {
            echo json_encode(['success' => false, 'message' => 'Họ tên phải có từ 2 đến 100 ký tự']);
            return;
        }
        if (!preg_match('/^[A-Za-zÀ-ỹ\s]+$/', $fullName)) {
            echo json_encode(['success' => false, 'message' => 'Họ tên chỉ được chứa chữ cái và khoảng trắng']);
            return;
        }
        
        // Validation: Email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
            return;
        }
        if (strlen($email) > 100) {
            echo json_encode(['success' => false, 'message' => 'Email không được vượt quá 100 ký tự']);
            return;
        }
        
        // Validation: Số điện thoại (Vietnamese format)
        if (!preg_match('/^(0|\+84)[35789][0-9]{8}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ. Phải bắt đầu bằng 0 hoặc +84, tiếp theo là 3/5/7/8/9 và 8 chữ số']);
            return;
        }
        if (strlen($phone) > 10) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại không được vượt quá 10 ký tự']);
            return;
        }
        
        // Validation: Mật khẩu
        if (strlen($password) < 6 || strlen($password) > 50) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có từ 6 đến 50 ký tự']);
            return;
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/', $password)) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự, bao gồm chữ hoa, chữ thường và số']);
            return;
        }
        
        // Validation: Trạng thái
        $validStatuses = ['Hoạt động', 'Chưa xác minh', 'Bị khóa'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
            return;
        }
        
        // Validation: Địa chỉ (nếu có)
        if (!empty($address) && strlen($address) > 255) {
            echo json_encode(['success' => false, 'message' => 'Địa chỉ không được vượt quá 255 ký tự']);
            return;
        }
        
        // Validation: Ngày sinh (nếu có)
        if (!empty($birthday)) {
            $birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
            if (!$birthdayDate || $birthdayDate->format('Y-m-d') !== $birthday) {
                echo json_encode(['success' => false, 'message' => 'Ngày sinh không hợp lệ']);
                return;
            }
            // Kiểm tra ngày sinh không được trong tương lai
            if ($birthdayDate > new DateTime()) {
                echo json_encode(['success' => false, 'message' => 'Ngày sinh không được trong tương lai']);
                return;
            }
        }
        
        // Kiểm tra email đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT ID_User FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email đã tồn tại trong hệ thống']);
            return;
        }
        
        // Kiểm tra số điện thoại đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE SoDienThoai = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại đã tồn tại trong hệ thống']);
            return;
        }
        
        // Bắt đầu transaction
        $pdo->beginTransaction();
        
        try {
            // Tạo tài khoản user (ID_Role = 5 là khách hàng)
            $stmt = $pdo->prepare("
                INSERT INTO users (Email, Password, ID_Role, TrangThai, NgayTao) 
                VALUES (?, ?, 5, ?, NOW())
            ");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$email, $hashedPassword, $status]);
            $userId = $pdo->lastInsertId();
            
            // Tạo thông tin khách hàng
            $stmt = $pdo->prepare("
                INSERT INTO khachhanginfo (ID_User, HoTen, SoDienThoai, DiaChi, NgaySinh, NgayTao) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $birthdayValue = !empty($birthday) ? $birthday : null;
            $addressValue = !empty($address) ? $address : null;
            $stmt->execute([$userId, $fullName, $phone, $addressValue, $birthdayValue]);
            
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

/**
 * Cập nhật thông tin khách hàng
 */
function updateCustomer() {
    try {
        $pdo = getDBConnection();
        
        // Lấy dữ liệu từ POST
        $userId = $_POST['id'] ?? '';
        $fullName = trim($_POST['HoTen'] ?? '');
        $phone = trim($_POST['SoDienThoai'] ?? '');
        $password = $_POST['MatKhau'] ?? '';
        $address = trim($_POST['DiaChi'] ?? '');
        $birthday = $_POST['NgaySinh'] ?? '';
        $status = $_POST['TrangThai'] ?? 'Hoạt động';
        
        // Validation: Kiểm tra các trường bắt buộc
        if (empty($userId) || empty($fullName) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
            return;
        }
        
        // Validation: Họ tên
        if (strlen($fullName) < 2 || strlen($fullName) > 100) {
            echo json_encode(['success' => false, 'message' => 'Họ tên phải có từ 2 đến 100 ký tự']);
            return;
        }
        if (!preg_match('/^[A-Za-zÀ-ỹ\s]+$/', $fullName)) {
            echo json_encode(['success' => false, 'message' => 'Họ tên chỉ được chứa chữ cái và khoảng trắng']);
            return;
        }
        
        // Validation: Số điện thoại
        if (!preg_match('/^(0|\+84)[35789][0-9]{8}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại không hợp lệ. Phải bắt đầu bằng 0 hoặc +84, tiếp theo là 3/5/7/8/9 và 8 chữ số']);
            return;
        }
        if (strlen($phone) > 10) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại không được vượt quá 10 ký tự']);
            return;
        }
        
        // Validation: Mật khẩu (nếu có)
        if (!empty($password)) {
            if (strlen($password) < 6 || strlen($password) > 50) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có từ 6 đến 50 ký tự']);
                return;
            }
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/', $password)) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự, bao gồm chữ hoa, chữ thường và số']);
                return;
            }
        }
        
        // Validation: Trạng thái
        $validStatuses = ['Hoạt động', 'Chưa xác minh', 'Bị khóa'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
            return;
        }
        
        // Validation: Địa chỉ (nếu có)
        if (!empty($address) && strlen($address) > 255) {
            echo json_encode(['success' => false, 'message' => 'Địa chỉ không được vượt quá 255 ký tự']);
            return;
        }
        
        // Validation: Ngày sinh (nếu có)
        if (!empty($birthday)) {
            $birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
            if (!$birthdayDate || $birthdayDate->format('Y-m-d') !== $birthday) {
                echo json_encode(['success' => false, 'message' => 'Ngày sinh không hợp lệ']);
                return;
            }
            if ($birthdayDate > new DateTime()) {
                echo json_encode(['success' => false, 'message' => 'Ngày sinh không được trong tương lai']);
                return;
            }
        }
        
        // Kiểm tra khách hàng có tồn tại không
        $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng']);
            return;
        }
        
        $khachHangId = $customer['ID_KhachHang'];
        
        // Kiểm tra số điện thoại đã tồn tại ở khách hàng khác chưa
        $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE SoDienThoai = ? AND ID_KhachHang != ?");
        $stmt->execute([$phone, $khachHangId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại đã được sử dụng bởi khách hàng khác']);
            return;
        }
        
        // Kiểm tra nếu đang cố khóa tài khoản và khách hàng có sự kiện
        if ($status === 'Bị khóa') {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM datlichsukien WHERE ID_KhachHang = ?");
            $stmt->execute([$khachHangId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Không thể khóa tài khoản khách hàng đã có sự kiện. Vui lòng hoàn tất hoặc hủy các sự kiện liên quan trước']);
                return;
            }
        }
        
        // Bắt đầu transaction
        $pdo->beginTransaction();
        
        try {
            // Cập nhật thông tin khách hàng
            $stmt = $pdo->prepare("
                UPDATE khachhanginfo 
                SET HoTen = ?, SoDienThoai = ?, DiaChi = ?, NgaySinh = ?, NgayCapNhat = NOW()
                WHERE ID_KhachHang = ?
            ");
            $birthdayValue = !empty($birthday) ? $birthday : null;
            $addressValue = !empty($address) ? $address : null;
            $stmt->execute([$fullName, $phone, $addressValue, $birthdayValue, $khachHangId]);
            
            // Cập nhật trạng thái và mật khẩu (nếu có) trong bảng users
            $updateFields = [];
            $updateValues = [];
            
            // Luôn cập nhật trạng thái
            $updateFields[] = "TrangThai = ?";
            $updateValues[] = $status;
            
            // Cập nhật mật khẩu nếu có
            if (!empty($password)) {
                $updateFields[] = "Password = ?";
                $updateValues[] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $updateValues[] = $userId;
            $stmt = $pdo->prepare("
                UPDATE users 
                SET " . implode(', ', $updateFields) . ", NgayCapNhat = NOW()
                WHERE ID_User = ?
            ");
            $stmt->execute($updateValues);
            
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

/**
 * Xóa khách hàng
 */
function deleteCustomer() {
    try {
        $pdo = getDBConnection();
        
        $customerId = $_POST['id'] ?? $_POST['customer_id'] ?? '';
        
        if (empty($customerId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin khách hàng']);
            return;
        }
        
        // Lấy thông tin khách hàng
        $stmt = $pdo->prepare("SELECT ID_KhachHang, ID_User FROM khachhanginfo WHERE ID_User = ? OR ID_KhachHang = ?");
        $stmt->execute([$customerId, $customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng']);
            return;
        }
        
        $khachHangId = $customer['ID_KhachHang'];
        $userId = $customer['ID_User'];
        
        // Kiểm tra khách hàng có sự kiện không
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM datlichsukien WHERE ID_KhachHang = ?");
        $stmt->execute([$khachHangId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa khách hàng đã có sự kiện. Vui lòng xóa các sự kiện liên quan trước']);
            return;
        }
        
        // Bắt đầu transaction
        $pdo->beginTransaction();
        
        try {
            // Xóa thông tin khách hàng
            $stmt = $pdo->prepare("DELETE FROM khachhanginfo WHERE ID_KhachHang = ?");
            $stmt->execute([$khachHangId]);
            
            // Xóa tài khoản user
            $stmt = $pdo->prepare("DELETE FROM users WHERE ID_User = ?");
            $stmt->execute([$userId]);
            
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

/**
 * Lấy danh sách sự kiện của khách hàng
 */
function getCustomerEvents() {
    try {
        $pdo = getDBConnection();
        
        $customerId = $_GET['customer_id'] ?? $_POST['customer_id'] ?? '';
        
        if (empty($customerId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin khách hàng']);
            return;
        }
        
        // Lấy ID_KhachHang từ ID_User nếu cần
        $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE ID_User = ? OR ID_KhachHang = ?");
        $stmt->execute([$customerId, $customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng']);
            return;
        }
        
        $khachHangId = $customer['ID_KhachHang'];
        
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
                dl.TongTien,
                dl.NgayTao,
                dd.TenDiaDiem,
                ls.TenLoai as TenLoaiSK
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            WHERE dl.ID_KhachHang = ?
            ORDER BY dl.NgayTao DESC
        ");
        $stmt->execute([$khachHangId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'events' => $events]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy sự kiện của khách hàng: ' . $e->getMessage()]);
    }
}

/**
 * Lấy thống kê khách hàng
 */
function getCustomerStats() {
    try {
        $pdo = getDBConnection();
        
        // Tổng số khách hàng
        $stmt = $pdo->query("SELECT COUNT(*) FROM khachhanginfo");
        $total = $stmt->fetchColumn();
        
        // Khách hàng hoạt động
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM khachhanginfo kh 
            JOIN users u ON kh.ID_User = u.ID_User 
            WHERE u.TrangThai = 'Hoạt động'
        ");
        $active = $stmt->fetchColumn();
        
        // Khách hàng chưa xác minh
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM khachhanginfo kh 
            JOIN users u ON kh.ID_User = u.ID_User 
            WHERE u.TrangThai = 'Chưa xác minh'
        ");
        $pending = $stmt->fetchColumn();
        
        // Khách hàng bị khóa
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM khachhanginfo kh 
            JOIN users u ON kh.ID_User = u.ID_User 
            WHERE u.TrangThai = 'Bị khóa'
        ");
        $blocked = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total' => (int)$total,
                'active' => (int)$active,
                'pending' => (int)$pending,
                'blocked' => (int)$blocked
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê khách hàng: ' . $e->getMessage()]);
    }
}
?>

