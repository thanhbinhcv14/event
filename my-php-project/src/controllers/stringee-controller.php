<?php
/**
 * Stringee Controller - Xử lý token generation và call management cho Stringee
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/stringee.php';
    require_once __DIR__ . '/../auth/auth.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi tải file: ' . $e->getMessage()]);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$userId = getCurrentUserId();
if (!$userId || $userId == 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Không thể lấy thông tin người dùng']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_token':
            getStringeeToken($userId);
            break;
        case 'make_call':
            makeStringeeCall($userId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log('Stringee controller error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}

/**
 * Generate Stringee Access Token
 * Tạo token cho Stringee SDK
 */
function getStringeeToken($userId) {
    $conversationId = $_POST['conversation_id'] ?? '';
    $callId = $_POST['call_id'] ?? '';
    $callType = $_POST['call_type'] ?? 'voice'; // voice hoặc video
    
    try {
        $pdo = getDBConnection();
        
        // Lấy thông tin conversation nếu có call_id
        if (!empty($callId)) {
            $stmt = $pdo->prepare("
                SELECT conversation_id, caller_id, receiver_id, call_type 
                FROM call_sessions 
                WHERE id = ?
            ");
            $stmt->execute([$callId]);
            $callSession = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$callSession) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy cuộc gọi']);
                return;
            }
            
            $conversationId = $callSession['conversation_id'];
            $callType = $callSession['call_type'];
        }
        
        // Tạo user ID cho Stringee (dạng string)
        $stringeeUserId = (string)$userId;
        
        // Generate token
        $token = generateStringeeToken($stringeeUserId);
        
        // Lấy thông tin user (tên từ nhanvieninfo hoặc khachhanginfo)
        $stmt = $pdo->prepare("
            SELECT COALESCE(nv.HoTen, kh.HoTen, u.Email) as name
            FROM users u
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            WHERE u.ID_User = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userName = $user ? $user['name'] : 'User ' . $userId;
        
        // Đảm bảo API SID được trim và validate
        $apiSid = trim(STRINGEE_API_SID);
        if (empty($apiSid)) {
            throw new Exception('API SID không được cấu hình. Vui lòng kiểm tra config/stringee.php');
        }
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'api_sid' => $apiSid,
            'server_addrs' => json_decode(STRINGEE_SERVER_ADDRS, true),
            'user_id' => $stringeeUserId,
            'user_name' => $userName,
            'call_type' => $callType,
            'conversation_id' => $conversationId
        ]);
        
    } catch (Exception $e) {
        error_log('Stringee token generation error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi tạo token: ' . $e->getMessage()]);
    }
}

/**
 * Generate Stringee Access Token
 * Sử dụng REST API của Stringee để tạo token
 */
function generateStringeeToken($userId) {
    $apiSid = trim(STRINGEE_API_SID);
    $apiSecret = trim(STRINGEE_API_SECRET);
    
    // Validate API SID và Secret
    if (empty($apiSid)) {
        throw new Exception('API SID không được cấu hình hoặc rỗng');
    }
    if (empty($apiSecret)) {
        throw new Exception('API Secret không được cấu hình hoặc rỗng');
    }
    
    // Tạo JWT token cho Stringee
    // Stringee sử dụng JWT với các claims:
    // - jti: unique token ID
    // - iss: API SID
    // - exp: expiration time
    // - userId: user ID
    
    $expireTime = time() + STRINGEE_TOKEN_TTL;
    $jti = bin2hex(random_bytes(16)); // Unique token ID
    
    // Build JWT payload
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];
    
    $payload = [
        'jti' => $jti,
        'iss' => $apiSid,
        'exp' => $expireTime,
        'userId' => $userId
    ];
    
    // Encode header và payload
    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));
    
    // Create signature
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $apiSecret, true);
    $signatureEncoded = base64UrlEncode($signature);
    
    // Build final token
    $token = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    
    return $token;
}

/**
 * Base64 URL encode (Stringee format)
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Make Stringee Call
 * Tạo cuộc gọi qua Stringee API
 */
function makeStringeeCall($userId) {
    $conversationId = $_POST['conversation_id'] ?? '';
    $receiverId = $_POST['receiver_id'] ?? '';
    $callType = $_POST['call_type'] ?? 'voice';
    
    if (empty($conversationId) || empty($receiverId)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin cuộc gọi']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        
        // Lấy thông tin receiver
        $stmt = $pdo->prepare("
            SELECT u.ID_User, u.Email, COALESCE(nv.HoTen, kh.HoTen, u.Email) as name
            FROM users u
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            WHERE u.ID_User = ?
        ");
        $stmt->execute([$receiverId]);
        $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$receiver) {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy người nhận']);
            return;
        }
        
        // Tạo call session trong database
        $stmt = $pdo->prepare("
            INSERT INTO call_sessions (conversation_id, caller_id, receiver_id, call_type, status, started_at) 
            VALUES (?, ?, ?, ?, 'initiated', NOW())
        ");
        $stmt->execute([$conversationId, $userId, $receiverId, $callType]);
        $callId = $pdo->lastInsertId();
        
        // Generate token cho caller
        $callerToken = generateStringeeToken((string)$userId);
        $receiverToken = generateStringeeToken((string)$receiverId);
        
        echo json_encode([
            'success' => true,
            'call_id' => $callId,
            'caller_token' => $callerToken,
            'receiver_token' => $receiverToken,
            'api_sid' => trim(STRINGEE_API_SID),
            'server_addrs' => json_decode(STRINGEE_SERVER_ADDRS, true),
            'call_type' => $callType,
            'receiver_id' => $receiverId,
            'receiver_name' => $receiver['name']
        ]);
        
    } catch (Exception $e) {
        error_log('Stringee call error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi tạo cuộc gọi: ' . $e->getMessage()]);
    }
}

?>

