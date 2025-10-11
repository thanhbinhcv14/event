<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

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

$pdo = getDBConnection();
$role = 5; // Mặc định khách hàng

try {
    // 1. Thêm user vào bảng users
    $stmt = $pdo->prepare("INSERT INTO users (Email, Password, ID_Role) VALUES (?, ?, ?)");
    $stmt->execute([
        $data['email'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        $role
    ]);
    $userId = $pdo->lastInsertId();

    // 2. Thêm thông tin khách hàng vào bảng khachhanginfo (có địa chỉ và ngày sinh)
    $stmt2 = $pdo->prepare("INSERT INTO khachhanginfo (ID_User, HoTen, SoDienThoai, DiaChi, NgaySinh) VALUES (?, ?, ?, ?, ?)");
    $stmt2->execute([
        $userId,
        $data['fullname'],
        $data['phone'],
        $data['address'],
        $data['birthday']
    ]);

    echo json_encode(['message' => 'Đăng ký thành công']);
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Email đã tồn tại hoặc lỗi dữ liệu']);
}
?>