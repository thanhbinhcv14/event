<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth/csrf.php';

header('Content-Type: application/json');

// Handle CSRF token request
$action = $_GET['action'] ?? '';
if ($action === 'get_csrf_token') {
    echo json_encode([
        'success' => true,
        'csrf_token' => generateCSRFToken()
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (
    !$data ||
    !isset($data['email']) ||
    !isset($data['password']) ||
    !isset($data['fullname']) ||
    !isset($data['phone']) ||
    !isset($data['address']) ||
    !isset($data['birthday'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu thông tin']);
    exit;
}

// Verify CSRF token
if (!verifyCSRFJson()) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token không hợp lệ. Vui lòng tải lại trang.']);
    exit;
}

// Input validation and sanitization
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$fullname = trim($data['fullname'] ?? '');
$phone = trim($data['phone'] ?? '');
$address = trim($data['address'] ?? '');
$birthday = $data['birthday'] ?? '';

// Validate email format
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email không hợp lệ']);
    exit;
}

// Validate password strength (backend validation)
if (empty($password) || strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Mật khẩu phải có ít nhất 6 ký tự']);
    exit;
}

// Check password strength: at least one uppercase, one lowercase, one number
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Mật khẩu phải bao gồm chữ hoa, chữ thường và số']);
    exit;
}

// Validate required fields
if (empty($fullname) || empty($phone) || empty($address) || empty($birthday)) {
    http_response_code(400);
    echo json_encode(['error' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

$pdo = getDBConnection();
$role = 5; // Mặc định khách hàng

try {
    $pdo = getDBConnection();
    
    // 1. Thêm user vào bảng users (sử dụng sanitized values)
    $stmt = $pdo->prepare("INSERT INTO users (Email, Password, ID_Role) VALUES (?, ?, ?)");
    $stmt->execute([
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $role
    ]);
    $userId = $pdo->lastInsertId();

    // 2. Thêm thông tin khách hàng vào bảng khachhanginfo (sử dụng sanitized values)
    $stmt2 = $pdo->prepare("INSERT INTO khachhanginfo (ID_User, HoTen, SoDienThoai, DiaChi, NgaySinh) VALUES (?, ?, ?, ?, ?)");
    $stmt2->execute([
        $userId,
        htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($address, ENT_QUOTES, 'UTF-8'),
        $birthday
    ]);

    // Regenerate CSRF token after successful registration
    regenerateCSRFToken();
    
    echo json_encode(['message' => 'Đăng ký thành công']);
} catch (PDOException $e) {
    http_response_code(400);
    // Don't expose database errors to users
    if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'UNIQUE') !== false) {
        echo json_encode(['error' => 'Email đã tồn tại']);
    } else {
        error_log("Registration error: " . $e->getMessage());
        echo json_encode(['error' => 'Đăng ký thất bại. Vui lòng thử lại sau.']);
    }
}
?>