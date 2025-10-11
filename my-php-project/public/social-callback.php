<?php
require_once __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/hybridauth.php';

use Hybridauth\Hybridauth;

session_start();

try {
    $hybridauth = new Hybridauth($config);
    $provider = $_GET['hauth_done'] ?? null;
    if (!$provider) {
        throw new Exception('No provider returned');
    }
    $adapter = $hybridauth->getAdapter($provider);
    $userProfile = $adapter->getUserProfile();

    // Lấy thông tin
    $email = $userProfile->email;
    $name = $userProfile->displayName;

    // Kết nối DB
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();

    // Kiểm tra tài khoản đã tồn tại chưa
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Đăng ký mới
        $stmt = $pdo->prepare("INSERT INTO users (Email, Password, ID_Role) VALUES (?, '', 5)");
        $stmt->execute([$email]);
        $userId = $pdo->lastInsertId();
        $stmt2 = $pdo->prepare("INSERT INTO khachhanginfo (ID_User, HoTen) VALUES (?, ?)");
        $stmt2->execute([$userId, $name]);
    } else {
        $userId = $user['ID_User'];
    }

    // Đăng nhập (tạo session)
    $_SESSION['user'] = [
        'id' => $userId,
        'email' => $email,
        'role' => 5
    ];

    // Chuyển hướng về trang chính
    header('Location: admin/index.php');
    exit;
} catch (Exception $e) {
    echo 'Lỗi đăng nhập mạng xã hội: ' . $e->getMessage();
}