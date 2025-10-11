<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and has admin privileges
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (!isset($_SESSION['user']) || !in_array($userRole, [1, 2])) {
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit();
}

$pdo = getDBConnection();
$STAFF_ROLES = [2, 3, 4];

// Lấy danh sách nhân viên
if ((isset($_GET['action']) && $_GET['action'] === 'list') || (isset($_POST['action']) && $_POST['action'] === 'list')) {
    $stmt = $pdo->prepare("SELECT u.ID_User, u.Email, u.ID_Role, u.TrangThai, u.NgayTao, u.NgayCapNhat, p.RoleName, s.HoTen, s.SoDienThoai, s.DiaChi, s.NgaySinh, s.ChucVu, s.Luong, s.NgayVaoLam
        FROM users u
        LEFT JOIN nhanvieninfo s ON u.ID_User = s.ID_User
        LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
        WHERE u.ID_Role IN (2,3,4)");
    $stmt->execute();
    echo json_encode($stmt->fetchAll());
    exit;
}

// Lấy thông tin 1 nhân viên
if ((isset($_GET['action']) && $_GET['action'] === 'get') || (isset($_POST['action']) && $_POST['action'] === 'get')) {
    $userId = $_POST['id'] ?? $_GET['id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID nhân viên']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT u.ID_User, u.Email, u.ID_Role, u.TrangThai, u.NgayTao, u.NgayCapNhat, s.HoTen, s.SoDienThoai, s.DiaChi, s.NgaySinh, s.ChucVu, s.Luong, s.NgayVaoLam
        FROM users u
        LEFT JOIN nhanvieninfo s ON u.ID_User = s.ID_User
        WHERE u.ID_User = ? AND u.ID_Role IN (2,3,4)");
    $stmt->execute([$userId]);
    $staff = $stmt->fetch();
    
    if ($staff) {
        echo json_encode($staff);
    } else {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy nhân viên']);
    }
    exit;
}

// Thêm nhân viên
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $birthday = $_POST['birthday'] ?? '';
    $chucVu = trim($_POST['chucvu'] ?? '');
    $luong = $_POST['luong'] ?? null;
    $ngayVaoLam = $_POST['ngayvaolam'] ?? null;

    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    if (strlen($password) < 6) $errors[] = 'Mật khẩu tối thiểu 6 ký tự';
    if (strlen($fullname) < 2) $errors[] = 'Họ tên quá ngắn';
    if (!preg_match('/^0\d{9,10}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ';
    if (empty($address)) $errors[] = 'Địa chỉ không được để trống';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) $errors[] = 'Ngày sinh không hợp lệ';
    if ($luong !== null && $luong !== '' && !is_numeric($luong)) $errors[] = 'Lương phải là số';
    if ($ngayVaoLam && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngayVaoLam)) $errors[] = 'Ngày vào làm không hợp lệ';

    if ($errors) {
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (Email, Password, ID_Role) VALUES (?, ?, ?)");
    $stmt->execute([
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $_POST['role']
    ]);
    $userId = $pdo->lastInsertId();
    $stmt2 = $pdo->prepare("INSERT INTO nhanvieninfo (ID_User, HoTen, SoDienThoai, DiaChi, NgaySinh, ChucVu, Luong, NgayVaoLam) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt2->execute([
        $userId,
        $fullname,
        $phone,
        $address,
        $birthday,
        $chucVu !== '' ? $chucVu : null,
        $luong !== '' ? $luong : null,
        $ngayVaoLam ?: null
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// Sửa nhân viên
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (!empty($_POST['password'])) {
        $stmt = $pdo->prepare("UPDATE users SET Email=?, Password=?, ID_Role=? WHERE ID_User=? AND ID_Role IN (2,3,4)");
        $stmt->execute([
            $_POST['email'],
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            $_POST['role'],
            $_POST['id']
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET Email=?, ID_Role=? WHERE ID_User=? AND ID_Role IN (2,3,4)");
        $stmt->execute([
            $_POST['email'],
            $_POST['role'],
            $_POST['id']
        ]);
    }
    $stmt2 = $pdo->prepare("UPDATE nhanvieninfo SET HoTen=?, SoDienThoai=?, DiaChi=?, NgaySinh=?, ChucVu=?, Luong=?, NgayVaoLam=? WHERE ID_User=?");
    $stmt2->execute([
        $_POST['fullname'],
        $_POST['phone'],
        $_POST['address'],
        $_POST['birthday'],
        $_POST['chucvu'] ?? null,
        $_POST['luong'] !== '' ? $_POST['luong'] : null,
        $_POST['ngayvaolam'] ?? null,
        $_POST['id']
    ]);
    echo json_encode(['success' => true]);
    exit;
}

// Xóa nhân viên
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt2 = $pdo->prepare("DELETE FROM nhanvieninfo WHERE ID_User=?");
    $stmt2->execute([$_POST['id']]);
    $stmt = $pdo->prepare("DELETE FROM users WHERE ID_User=? AND ID_Role IN (2,3,4)");
    $stmt->execute([$_POST['id']]);
    echo json_encode(['success' => true]);
    exit;
}

// Lấy danh sách role nhân viên
if ((isset($_GET['action']) && $_GET['action'] === 'roles') || (isset($_POST['action']) && $_POST['action'] === 'roles')) {
    $roles = $pdo->query("SELECT ID_Role, RoleName FROM phanquyen WHERE ID_Role IN (2,3,4)")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($roles);
    exit;
}

// Lấy thống kê nhân viên
if ((isset($_GET['action']) && $_GET['action'] === 'get_staff_stats') || (isset($_POST['action']) && $_POST['action'] === 'get_staff_stats')) {
    try {
        // Tổng nhân viên
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE ID_Role IN (2,3,4)");
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        
        // Nhân viên hoạt động
        $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM users WHERE ID_Role IN (2,3,4) AND TrangThai = 'Hoạt động'");
        $stmt->execute();
        $active = $stmt->fetch()['active'];
        
        // Nhân viên chưa xác minh
        $stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM users WHERE ID_Role IN (2,3,4) AND TrangThai = 'Chưa xác minh'");
        $stmt->execute();
        $pending = $stmt->fetch()['pending'];
        
        // Nhân viên bị khóa
        $stmt = $pdo->prepare("SELECT COUNT(*) as blocked FROM users WHERE ID_Role IN (2,3,4) AND TrangThai = 'Bị khóa'");
        $stmt->execute();
        $blocked = $stmt->fetch()['blocked'];
        
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
        echo json_encode(['success' => false, 'error' => 'Lỗi thống kê: ' . $e->getMessage()]);
    }
    exit;
}

// Thêm nhân viên mới
if (isset($_POST['action']) && $_POST['action'] === 'add_staff') {
    try {
        $input = $_POST;
        
        // Validate required fields
        $requiredFields = ['HoTen', 'Email', 'MatKhau', 'SoDienThoai', 'ID_Role', 'TrangThai'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                exit();
            }
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
            password_hash($input['MatKhau'], PASSWORD_DEFAULT),
            $input['ID_Role'],
            $input['TrangThai']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Insert into nhanvieninfo table
        $stmt = $pdo->prepare("INSERT INTO nhanvieninfo (ID_User, HoTen, SoDienThoai, DiaChi, NgaySinh, ChucVu, Luong, NgayVaoLam) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $input['HoTen'],
            $input['SoDienThoai'],
            $input['DiaChi'] ?? '',
            $input['NgaySinh'] ?? null,
            $input['ChucVu'] ?? '',
            $input['Luong'] ?? 0,
            $input['NgayVaoLam'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Thêm nhân viên thành công']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi thêm nhân viên: ' . $e->getMessage()]);
    }
    exit;
}

// Cập nhật nhân viên
if (isset($_POST['action']) && $_POST['action'] === 'update_staff') {
    try {
        $input = $_POST;
        $userId = $_POST['id'] ?? $_GET['id'] ?? null;
        
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Thiếu ID nhân viên']);
            exit();
        }
        
        // Validate required fields
        $requiredFields = ['HoTen', 'Email', 'SoDienThoai', 'ID_Role', 'TrangThai'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                exit();
            }
        }
        
        // Check if email already exists for other users
        $stmt = $pdo->prepare("SELECT ID_User FROM users WHERE Email = ? AND ID_User != ?");
        $stmt->execute([$input['Email'], $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Email đã tồn tại']);
            exit();
        }
        
        // Update users table
        $updateFields = ['Email', 'ID_Role', 'TrangThai'];
        $updateValues = [];
        $updateSql = [];
        
        foreach ($updateFields as $field) {
            $updateSql[] = "{$field} = ?";
            $updateValues[] = $input[$field];
        }
        
        // Add password if provided
        if (!empty($input['MatKhau'])) {
            $updateSql[] = "Password = ?";
            $updateValues[] = password_hash($input['MatKhau'], PASSWORD_DEFAULT);
        }
        
        $updateValues[] = $userId;
        
        $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updateSql) . " WHERE ID_User = ?");
        $stmt->execute($updateValues);
        
        // Update nhanvieninfo table
        $stmt = $pdo->prepare("UPDATE nhanvieninfo SET HoTen = ?, SoDienThoai = ?, DiaChi = ?, NgaySinh = ?, ChucVu = ?, Luong = ?, NgayVaoLam = ? WHERE ID_User = ?");
        $stmt->execute([
            $input['HoTen'],
            $input['SoDienThoai'],
            $input['DiaChi'] ?? '',
            $input['NgaySinh'] ?? null,
            $input['ChucVu'] ?? '',
            $input['Luong'] ?? 0,
            $input['NgayVaoLam'] ?? null,
            $userId
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật nhân viên thành công']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi cập nhật nhân viên: ' . $e->getMessage()]);
    }
    exit;
}

// Xóa nhân viên
if (isset($_POST['action']) && $_POST['action'] === 'delete_staff') {
    try {
        $userId = $_POST['id'] ?? $_GET['id'] ?? null;
        
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Thiếu ID nhân viên']);
            exit();
        }
        
        // Delete from nhanvieninfo first
        $stmt = $pdo->prepare("DELETE FROM nhanvieninfo WHERE ID_User = ?");
        $stmt->execute([$userId]);
        
        // Delete from users
        $stmt = $pdo->prepare("DELETE FROM users WHERE ID_User = ? AND ID_Role IN (2,3,4)");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Xóa nhân viên thành công']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy nhân viên']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi xóa nhân viên: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action']);
exit();