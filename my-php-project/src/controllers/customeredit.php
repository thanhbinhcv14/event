<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit();
}

$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (!in_array($userRole, [1, 2, 3])) {
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Chỉ lấy khách hàng (ID_Role = 5)
    $CUSTOMER_ROLE = 5;

    // Lấy danh sách khách hàng
    if (isset($_GET['action']) && $_GET['action'] === 'list') {
        // Kiểm tra xem có bảng khachhanginfo không
        $checkTable = $pdo->query("SHOW TABLES LIKE 'khachhanginfo'");
        if ($checkTable->rowCount() == 0) {
            echo json_encode(['error' => 'Bảng khachhanginfo không tồn tại']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT u.ID_User, u.Email, u.NgayTao, u.NgayCapNhat, u.TrangThai, k.HoTen, k.SoDienThoai, k.DiaChi, k.NgaySinh
            FROM users u
            LEFT JOIN khachhanginfo k ON u.ID_User = k.ID_User
            WHERE u.ID_Role = ?
            ORDER BY u.ID_User DESC");
        $stmt->execute([$CUSTOMER_ROLE]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log số lượng khách hàng
        error_log("Found " . count($customers) . " customers");
        
        // Đảm bảo mỗi khách hàng có đủ thông tin
        foreach ($customers as &$customer) {
            $customer['TrangThai'] = $customer['TrangThai'] ?: 'Hoạt động';
            $customer['HoTen'] = $customer['HoTen'] ?: 'Chưa cập nhật';
            $customer['SoDienThoai'] = $customer['SoDienThoai'] ?: '';
            $customer['DiaChi'] = $customer['DiaChi'] ?: '';
        }
        
        // Nếu không có khách hàng, tạo dữ liệu mẫu
        if (count($customers) == 0) {
            echo json_encode([
                [
                    'ID_User' => 1, 
                    'Email' => 'customer@example.com', 
                    'HoTen' => 'Khách hàng mẫu', 
                    'SoDienThoai' => '0123456789', 
                    'DiaChi' => 'Hà Nội', 
                    'NgaySinh' => '1990-01-01',
                    'TrangThai' => 'Hoạt động',
                    'NgayTao' => date('Y-m-d H:i:s'),
                    'NgayCapNhat' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo json_encode($customers);
        }
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in customeredit.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Lấy thông tin 1 khách hàng
if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT u.ID_User, u.Email, k.HoTen, k.SoDienThoai, k.DiaChi, k.NgaySinh
        FROM users u
        LEFT JOIN khachhanginfo k ON u.ID_User = k.ID_User
        WHERE u.ID_User = ? AND u.ID_Role = ?");
    $stmt->execute([$_GET['id'], $CUSTOMER_ROLE]);
    echo json_encode($stmt->fetch());
    exit;
}

// Lấy thông tin chi tiết khách hàng cho modal
if (isset($_GET['action']) && $_GET['action'] === 'get_customer' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT u.ID_User, u.Email, u.NgayTao, u.NgayCapNhat, u.TrangThai, k.HoTen, k.SoDienThoai, k.DiaChi, k.NgaySinh
            FROM users u
            LEFT JOIN khachhanginfo k ON u.ID_User = k.ID_User
            WHERE u.ID_User = ? AND u.ID_Role = ?");
        $stmt->execute([$_GET['id'], $CUSTOMER_ROLE]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            // Đảm bảo có đủ thông tin cần thiết
            $customer['TrangThai'] = $customer['TrangThai'] ?: 'Hoạt động';
            $customer['HoTen'] = $customer['HoTen'] ?: 'Chưa cập nhật';
            $customer['SoDienThoai'] = $customer['SoDienThoai'] ?: '';
            $customer['DiaChi'] = $customer['DiaChi'] ?: '';
            echo json_encode(['success' => true, 'customer' => $customer]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy khách hàng']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit;
}

// Lấy thông tin cá nhân khách hàng từ bảng khachhanginfo
if (isset($_GET['action']) && $_GET['action'] === 'info' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM khachhanginfo WHERE ID_User = ?");
    $stmt->execute([$_GET['id']]);
    echo json_encode($stmt->fetch());
    exit;
}

// Thêm khách hàng
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $birthday = $_POST['birthday'] ?? '';

    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    // $action không được định nghĩa ở phạm vi này, kiểm tra trực tiếp độ dài mật khẩu
    if (strlen($password) < 6) $errors[] = 'Mật khẩu tối thiểu 6 ký tự';
    if (strlen($fullname) < 2) $errors[] = 'Họ tên quá ngắn';
    if (!preg_match('/^0\d{9,10}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ';
    if (empty($address)) $errors[] = 'Địa chỉ không được để trống';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) $errors[] = 'Ngày sinh không hợp lệ';

    if ($errors) {
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (Email, Password, ID_Role) VALUES (?, ?, ?)");
    $stmt->execute([
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $CUSTOMER_ROLE
    ]);
    $userId = $pdo->lastInsertId();
    $stmt2 = $pdo->prepare("INSERT INTO khachhanginfo (ID_User, HoTen, SoDienThoai, DiaChi, NgaySinh) VALUES (?, ?, ?, ?, ?)");
    $stmt2->execute([
        $userId,
        $fullname,
        $phone,
        $address,
        $birthday
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// Sửa khách hàng
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (!empty($_POST['password'])) {
        $stmt = $pdo->prepare("UPDATE users SET Email=?, Password=? WHERE ID_User=? AND ID_Role=?");
        $stmt->execute([
            $_POST['email'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            $_POST['id'],
            $CUSTOMER_ROLE
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET Email=? WHERE ID_User=? AND ID_Role=?");
        $stmt->execute([
            $_POST['email'],
            $_POST['id'],
            $CUSTOMER_ROLE
        ]);
    }
    $stmt2 = $pdo->prepare("UPDATE khachhanginfo SET HoTen=?, SoDienThoai=?, DiaChi=?, NgaySinh=? WHERE ID_User=?");
    $stmt2->execute([
        $_POST['fullname'],
        $_POST['phone'],
        $_POST['address'],
        $_POST['birthday'],
        $_POST['id']
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// Xóa khách hàng
// Lấy thống kê khách hàng
if (isset($_GET['action']) && $_GET['action'] === 'get_customer_stats') {
    try {
        // Tổng khách hàng
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE ID_Role = ?");
        $stmt->execute([$CUSTOMER_ROLE]);
        $total = $stmt->fetchColumn();
        
        // Khách hàng hoạt động
        $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM users WHERE ID_Role = ? AND (TrangThai = 'Hoạt động' OR TrangThai IS NULL OR TrangThai = '')");
        $stmt->execute([$CUSTOMER_ROLE]);
        $active = $stmt->fetchColumn();
        
        // Khách hàng chờ xác thực
        $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM users WHERE ID_Role = ? AND TrangThai = 'Chưa xác minh'");
        $stmt->execute([$CUSTOMER_ROLE]);
        $pending = $stmt->fetchColumn();
        
        // Khách hàng bị khóa
        $stmt = $pdo->prepare("SELECT COUNT(*) as blocked FROM users WHERE ID_Role = ? AND TrangThai = 'Bị khóa'");
        $stmt->execute([$CUSTOMER_ROLE]);
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
        echo json_encode(['success' => false, 'error' => 'Lỗi thống kê: ' . $e->getMessage()]);
    }
    exit;
}

// Thêm khách hàng mới
if (isset($_POST['action']) && $_POST['action'] === 'add_customer') {
    try {
        $input = $_POST;
        
        // Validate required fields
        $requiredFields = ['HoTen', 'Email', 'SoDienThoai', 'TrangThai'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                exit();
            }
        }
        
        // Validate email format
        if (!filter_var($input['Email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Email không hợp lệ']);
            exit();
        }
        
        // Validate phone format (Vietnamese phone number)
        if (!preg_match('/^[0-9]{10,11}$/', $input['SoDienThoai'])) {
            echo json_encode(['success' => false, 'error' => 'Số điện thoại không hợp lệ (10-11 chữ số)']);
            exit();
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT ID_User FROM users WHERE Email = ?");
        $stmt->execute([$input['Email']]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Email đã tồn tại']);
            exit();
        }
        
        // Insert into users table
        $stmt = $pdo->prepare("INSERT INTO users (Email, Password, ID_Role, TrangThai) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $input['Email'],
            password_hash('123456', PASSWORD_DEFAULT), // Default password
            $CUSTOMER_ROLE,
            $input['TrangThai']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Insert into khachhanginfo table
        $stmt = $pdo->prepare("INSERT INTO khachhanginfo (ID_User, HoTen, SoDienThoai, DiaChi, NgaySinh) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $input['HoTen'],
            $input['SoDienThoai'],
            $input['DiaChi'] ?? '',
            $input['NgaySinh'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Thêm khách hàng thành công']);
    } catch (Exception $e) {
        error_log("Error adding customer: " . $e->getMessage());
        error_log("Input data: " . print_r($input, true));
        echo json_encode(['success' => false, 'error' => 'Lỗi thêm khách hàng: ' . $e->getMessage()]);
    }
    exit;
}

// Cập nhật khách hàng
if (isset($_POST['action']) && $_POST['action'] === 'update_customer') {
    try {
        $input = $_POST;
        $userId = $input['id'] ?? null;
        
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Thiếu ID khách hàng']);
            exit();
        }
        
        // Validate required fields
        $requiredFields = ['HoTen', 'Email', 'SoDienThoai', 'TrangThai'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                exit();
            }
        }
        
        // Validate email format
        if (!filter_var($input['Email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Email không hợp lệ']);
            exit();
        }
        
        // Validate phone format (Vietnamese phone number)
        if (!preg_match('/^[0-9]{10,11}$/', $input['SoDienThoai'])) {
            echo json_encode(['success' => false, 'error' => 'Số điện thoại không hợp lệ (10-11 chữ số)']);
            exit();
        }
        
        // Check if email already exists for other users
        $stmt = $pdo->prepare("SELECT ID_User FROM users WHERE Email = ? AND ID_User != ?");
        $stmt->execute([$input['Email'], $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Email đã tồn tại']);
            exit();
        }
        
        // Update users table
        $stmt = $pdo->prepare("UPDATE users SET Email = ?, TrangThai = ? WHERE ID_User = ? AND ID_Role = ?");
        $stmt->execute([$input['Email'], $input['TrangThai'], $userId, $CUSTOMER_ROLE]);
        
        // Update khachhanginfo table
        $stmt = $pdo->prepare("UPDATE khachhanginfo SET HoTen = ?, SoDienThoai = ?, DiaChi = ?, NgaySinh = ? WHERE ID_User = ?");
        $stmt->execute([
            $input['HoTen'],
            $input['SoDienThoai'],
            $input['DiaChi'] ?? '',
            $input['NgaySinh'] ?? null,
            $userId
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật khách hàng thành công']);
    } catch (Exception $e) {
        error_log("Error updating customer: " . $e->getMessage());
        error_log("Input data: " . print_r($input, true));
        echo json_encode(['success' => false, 'error' => 'Lỗi cập nhật khách hàng: ' . $e->getMessage()]);
    }
    exit;
}

// Xóa khách hàng
if (isset($_POST['action']) && $_POST['action'] === 'delete_customer') {
    try {
        $userId = $_POST['id'] ?? null;
        
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Thiếu ID khách hàng']);
            exit();
        }
        
        // Delete from khachhanginfo first
        $stmt = $pdo->prepare("DELETE FROM khachhanginfo WHERE ID_User = ?");
        $stmt->execute([$userId]);
        
        // Delete from users
        $stmt = $pdo->prepare("DELETE FROM users WHERE ID_User = ? AND ID_Role = ?");
        $stmt->execute([$userId, $CUSTOMER_ROLE]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Xóa khách hàng thành công']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy khách hàng']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi xóa khách hàng: ' . $e->getMessage()]);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt2 = $pdo->prepare("DELETE FROM khachhanginfo WHERE ID_User=?");
    $stmt2->execute([$_POST['id']]);
    $stmt = $pdo->prepare("DELETE FROM users WHERE ID_User=? AND ID_Role=?");
    $stmt->execute([$_POST['id'], $CUSTOMER_ROLE]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);