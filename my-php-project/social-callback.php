<?php
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/hybridauth.php';

use Hybridauth\Hybridauth;

session_start();

try {
    $hybridauth = new Hybridauth($config);
    
    // Lấy provider từ URL parameter (Hybridauth v3 sử dụng 'hauth.done' hoặc 'provider')
    $provider = $_GET['hauth.done'] ?? $_GET['hauth_done'] ?? $_GET['provider'] ?? null;
    
    // Nếu không có trong GET, thử lấy từ connected adapters
    if (!$provider) {
        $connectedAdapters = $hybridauth->getConnectedAdapters();
        if (!empty($connectedAdapters)) {
            // PHP 7.3+ có array_key_first, nếu không dùng reset và key
            $provider = function_exists('array_key_first') 
                ? array_key_first($connectedAdapters) 
                : key($connectedAdapters);
        }
    }
    
    if (!$provider) {
        throw new Exception('No provider specified in callback');
    }
    
    // Lấy adapter cho provider
    $adapter = $hybridauth->getAdapter($provider);
    
    // Kiểm tra xem adapter đã được authenticate chưa
    if (!$adapter->isConnected()) {
        throw new Exception('Provider not connected. Please try logging in again.');
    }
    
    $userProfile = $adapter->getUserProfile();

    // Lấy thông tin từ provider với kiểm tra null
    $email = $userProfile->email ?? null;
    $name = $userProfile->displayName ?? $userProfile->firstName ?? 'User';
    $providerId = $userProfile->identifier ?? null; // Facebook ID hoặc Google ID
    
    // Kiểm tra dữ liệu bắt buộc
    if (empty($providerId)) {
        throw new Exception('Provider ID is required');
    }
    
    if (empty($email)) {
        throw new Exception('Email is required for registration');
    }
    
    // Xác định loại provider
    $isFacebook = ($provider === 'Facebook');
    $isGoogle = ($provider === 'Google');

    // Kết nối DB
    require_once __DIR__ . '/config/database.php';
    $pdo = getDBConnection();

    // Kiểm tra tài khoản đã tồn tại chưa (theo email hoặc provider ID)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ? OR FacebookID = ? OR GoogleID = ?");
    $stmt->execute([$email, $providerId, $providerId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Đăng ký mới
        $facebookId = $isFacebook ? $providerId : null;
        $googleId = $isGoogle ? $providerId : null;
        
        $stmt = $pdo->prepare("INSERT INTO users (Email, Password, ID_Role, FacebookID, GoogleID) VALUES (?, '', 5, ?, ?)");
        $stmt->execute([$email, $facebookId, $googleId]);
        $userId = $pdo->lastInsertId();
        
        // Thêm thông tin khách hàng
        $stmt2 = $pdo->prepare("INSERT INTO khachhanginfo (ID_User, HoTen) VALUES (?, ?)");
        $stmt2->execute([$userId, $name]);
        
        // Lấy lại thông tin user vừa tạo
        $stmt = $pdo->prepare("SELECT * FROM users WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Cập nhật provider ID nếu chưa có
        $userId = $user['ID_User'];
        if ($isFacebook && empty($user['FacebookID'])) {
            $stmt = $pdo->prepare("UPDATE users SET FacebookID = ? WHERE ID_User = ?");
            $stmt->execute([$providerId, $userId]);
        } elseif ($isGoogle && empty($user['GoogleID'])) {
            $stmt = $pdo->prepare("UPDATE users SET GoogleID = ? WHERE ID_User = ?");
            $stmt->execute([$providerId, $userId]);
        }
        
        // Lấy lại thông tin user đã cập nhật
        $stmt = $pdo->prepare("SELECT * FROM users WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Kiểm tra user đã được tạo/lấy thành công
    if (!$user || empty($user['ID_User'])) {
        throw new Exception('Failed to retrieve user information');
    }

    // Cập nhật trạng thái online
    try {
        $stmt = $pdo->prepare("UPDATE users SET OnlineStatus = 'Online', LastActivity = NOW() WHERE ID_User = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Error setting user online: " . $e->getMessage());
    }

    // Đăng nhập (tạo session) - format giống login.php
    $_SESSION['user'] = [
        'ID_User' => $user['ID_User'],
        'id' => $user['ID_User'],
        'Email' => $user['Email'],
        'email' => $user['Email'],
        'ID_Role' => $user['ID_Role'],
        'role' => $user['ID_Role']
    ];

    // Chuyển hướng theo role (giống login.php)
    if (in_array($user['ID_Role'], [1, 2, 3, 4])) {
        $redirect = 'admin/index.php';
    } else {
        $redirect = 'index.php';
    }
    
    header('Location: ' . $redirect);
    exit;
} catch (Exception $e) {
    // Log lỗi để debug
    error_log('Social login error: ' . $e->getMessage());
    
    // Hiển thị thông báo lỗi và chuyển hướng về trang đăng nhập
    $_SESSION['login_error'] = 'Lỗi đăng nhập mạng xã hội: ' . $e->getMessage();
    header('Location: login.php');
    exit;
}