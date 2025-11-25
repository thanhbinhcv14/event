<?php
/**
 * Facebook Login Controller
 * Xử lý đăng nhập bằng Facebook JavaScript SDK
 */

// Đảm bảo output buffer được clear trước khi gửi JSON
if (ob_get_length()) {
    ob_clean();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header JSON ngay từ đầu
header('Content-Type: application/json; charset=utf-8');

// Require database
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Lấy dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$accessToken = $input['access_token'] ?? '';
$userID = $input['user_id'] ?? '';

if (empty($accessToken) || empty($userID)) {
    echo json_encode(['success' => false, 'message' => 'Missing access token or user ID']);
    exit;
}

try {
    // Lấy App ID và Secret từ config hoặc environment variables
    $appId = $_ENV['FACEBOOK_APP_ID'] ?? '877436944712009';
    $appSecret = $_ENV['FACEBOOK_APP_SECRET'] ?? '6fa5e91f4728125a3ae618dfc86b725a';
    
    // Log để debug (chỉ log trong production)
    if (defined('BASE_URL') && strpos(BASE_URL, 'localhost') === false) {
        error_log("Facebook Login - App ID: {$appId}, Domain: " . ($_SERVER['HTTP_HOST'] ?? 'unknown'));
    }
    
    /**
     * Helper function để gọi Facebook Graph API bằng cURL
     */
    function callFacebookAPI($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FacebookLogin/1.0)',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        if ($response === false || $curlErrno !== 0) {
            throw new Exception("Facebook API request failed: " . ($curlError ?: 'Unknown cURL error'));
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Facebook API returned HTTP {$httpCode}");
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse Facebook API response: " . json_last_error_msg());
        }
        
        if (isset($data['error'])) {
            $errorMsg = $data['error']['message'] ?? 'Unknown Facebook API error';
            $errorCode = $data['error']['code'] ?? 'UNKNOWN';
            throw new Exception("Facebook API error: {$errorMsg} (Code: {$errorCode})");
        }
        
        return $data;
    }
    
    // Verify access token
    $appAccessToken = "{$appId}|{$appSecret}";
    $verifyUrl = "https://graph.facebook.com/v18.0/debug_token?input_token={$accessToken}&access_token={$appAccessToken}";
    try {
        $verifyData = callFacebookAPI($verifyUrl);
        if (!isset($verifyData['data']['is_valid']) || !$verifyData['data']['is_valid']) {
            $errorMsg = 'Invalid access token';
            if (isset($verifyData['data']['error'])) {
                $errorMsg .= ': ' . $verifyData['data']['error']['message'];
            }
            throw new Exception($errorMsg);
        }
    } catch (Exception $e) {
        error_log("Facebook Token Verification Error: " . $e->getMessage());
        throw new Exception('Failed to verify access token: ' . $e->getMessage());
    }
    
    // Lấy thông tin user từ Facebook Graph API
    $graphUrl = "https://graph.facebook.com/v18.0/me?fields=id,name,email,picture&access_token={$accessToken}";
    try {
        $userData = callFacebookAPI($graphUrl);
    } catch (Exception $e) {
        error_log("Facebook Graph API Error: " . $e->getMessage());
        throw new Exception('Failed to get user data from Facebook: ' . $e->getMessage());
    }
    
    // Lấy thông tin từ Facebook
    $facebookId = $userData['id'] ?? null;
    $email = $userData['email'] ?? null;
    $name = $userData['name'] ?? 'User';
    $picture = $userData['picture']['data']['url'] ?? null;
    
    // Kiểm tra dữ liệu bắt buộc
    if (empty($facebookId)) {
        throw new Exception('Facebook ID is required');
    }
    
    if (empty($email)) {
        throw new Exception('Email is required for registration');
    }
    
    // Kết nối DB
    $pdo = getDBConnection();
    
    // Kiểm tra tài khoản đã tồn tại chưa (theo email hoặc Facebook ID)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ? OR FacebookID = ?");
    $stmt->execute([$email, $facebookId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Đăng ký mới
        $stmt = $pdo->prepare("INSERT INTO users (Email, Password, ID_Role, FacebookID, GoogleID) VALUES (?, '', 5, ?, NULL)");
        $stmt->execute([$email, $facebookId]);
        $userId = $pdo->lastInsertId();
        
        // Thêm thông tin khách hàng
        $stmt2 = $pdo->prepare("INSERT INTO khachhanginfo (ID_User, HoTen) VALUES (?, ?)");
        $stmt2->execute([$userId, $name]);
        
        // Lấy lại thông tin user vừa tạo
        $stmt = $pdo->prepare("SELECT * FROM users WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Cập nhật Facebook ID nếu chưa có
        $userId = $user['ID_User'];
        if (empty($user['FacebookID'])) {
            $stmt = $pdo->prepare("UPDATE users SET FacebookID = ? WHERE ID_User = ?");
            $stmt->execute([$facebookId, $userId]);
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
    
    // Xác định redirect URL theo role
    if (in_array($user['ID_Role'], [1, 2, 3, 4])) {
        $redirect = 'admin/index.php';
    } else {
        $redirect = 'index.php';
    }
    
    // Trả về success response
    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công',
        'redirect' => $redirect,
        'user' => [
            'id' => $user['ID_User'],
            'email' => $user['Email'],
            'role' => $user['ID_Role']
        ]
    ]);
    
} catch (Exception $e) {
    // Log lỗi chi tiết để debug
    $errorDetails = [
        'message' => $e->getMessage(),
        'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    error_log('Facebook login error: ' . json_encode($errorDetails));
    
    // Trả về error response với thông tin chi tiết hơn
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi đăng nhập Facebook: ' . $e->getMessage(),
        'error_code' => 'FACEBOOK_LOGIN_ERROR',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown'
    ]);
}

