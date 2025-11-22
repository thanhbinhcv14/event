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
if (!$data || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu thông tin']);
    exit;
}

// Verify CSRF token (except for get_csrf_token action)
if (!verifyCSRFJson()) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token không hợp lệ. Vui lòng tải lại trang.']);
    exit;
}

// Input validation
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu thông tin']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email không hợp lệ']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Check if user exists and password is correct
    // Không phân biệt giữa "tài khoản không tồn tại" và "sai mật khẩu" để bảo mật
    if (!$user || !password_verify($password, $user['Password'] ?? '')) {
        http_response_code(401);
        echo json_encode(['error' => 'Email hoặc mật khẩu không đúng']);
        exit;
    }

    // User exists and password is correct
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session directly without JWT
    $_SESSION['user'] = [
        'ID_User' => $user['ID_User'],
        'id' => $user['ID_User'],
        'Email' => $user['Email'],
        'email' => $user['Email'],
        'ID_Role' => $user['ID_Role'],
        'role' => $user['ID_Role']
    ];
    
    // Regenerate CSRF token after successful login
    regenerateCSRFToken();

    // Set user online status
    try {
        $stmt = $pdo->prepare("UPDATE users SET OnlineStatus = 'Online', LastActivity = NOW() WHERE ID_User = ?");
        $stmt->execute([$user['ID_User']]);
        error_log("User " . $user['ID_User'] . " set online on login");
    } catch (Exception $e) {
        error_log("Error setting user online: " . $e->getMessage());
    }

    // Chỉ role 1,2,3,4 mới vào admin
    if (in_array($user['ID_Role'], [1, 2, 3, 4])) {
        $redirect = 'admin/index.php';
    } else {
        $redirect = 'index.php';
    }

    echo json_encode([
        'success' => true,
        'role' => $user['ID_Role'],
        'redirect' => $redirect
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Có lỗi xảy ra, vui lòng thử lại sau']);
}
?>