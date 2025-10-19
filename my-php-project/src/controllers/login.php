<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu thông tin']);
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($data['password'], $user['Password'])) {
        // Set session directly without JWT
        $_SESSION['user'] = [
            'ID_User' => $user['ID_User'],
            'id' => $user['ID_User'],
            'Email' => $user['Email'],
            'email' => $user['Email'],
            'ID_Role' => $user['ID_Role'],
            'role' => $user['ID_Role']
        ];

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
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Sai tài khoản hoặc mật khẩu']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Có lỗi xảy ra, vui lòng thử lại sau']);
}
?>