<?php
/**
 * LiveKit Controller - Tạo access token và quản lý rooms cho voice/video call
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/livekit.php';
    require_once __DIR__ . '/../auth/auth.php';
    require_once __DIR__ . '/../../vendor/autoload.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi tải file: ' . $e->getMessage()]);
    exit;
}

// Import LiveKit classes (phải đặt sau require autoload.php và trước khi sử dụng)
use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use Agence104\LiveKit\RoomServiceClient;
use Agence104\LiveKit\RoomCreateOptions;

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
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'get_token':
            getLiveKitToken($pdo, $userId);
            break;
        case 'create_room':
            createLiveKitRoom($pdo, $userId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ: ' . $action]);
            break;
    }

} catch (Exception $e) {
    error_log('LiveKit controller error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}

/**
 * Tạo LiveKit Access Token cho người dùng join room
 */
function getLiveKitToken($pdo, $userId) {
    $roomName = $_POST['room_name'] ?? $_GET['room_name'] ?? '';
    $callId = $_POST['call_id'] ?? $_GET['call_id'] ?? '';
    $conversationId = $_POST['conversation_id'] ?? $_GET['conversation_id'] ?? '';
    
    // Nếu có call_id, lấy room_name từ call_sessions
    if (!empty($callId) && empty($roomName)) {
        $stmt = $pdo->prepare("
            SELECT conversation_id, call_type 
            FROM call_sessions 
            WHERE id = ? AND (caller_id = ? OR receiver_id = ?)
        ");
        $stmt->execute([$callId, $userId, $userId]);
        $call = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($call) {
            $conversationId = $call['conversation_id'];
            // Tạo room name từ conversation_id và call_id
            $roomName = 'call_' . $callId . '_conv_' . $conversationId;
        }
    }
    
    // Nếu có conversation_id nhưng chưa có room_name, tạo room name từ conversation_id
    if (!empty($conversationId) && empty($roomName)) {
        $roomName = 'conv_' . $conversationId;
    }
    
    if (empty($roomName)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu tên room']);
        return;
    }
    
    // Kiểm tra quyền truy cập conversation nếu có
    if (!empty($conversationId)) {
        $stmt = $pdo->prepare("
            SELECT id, user1_id, user2_id FROM conversations 
            WHERE id = ? AND (user1_id = ? OR user2_id = ?)
        ");
        $stmt->execute([$conversationId, $userId, $userId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập cuộc trò chuyện này']);
            return;
        }
    }
    
    try {
        // Lấy thông tin người dùng
        $stmt = $pdo->prepare("
            SELECT u.ID_User, u.Email,
                   COALESCE(nv.HoTen, kh.HoTen, u.Email) as display_name
            FROM users u
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            WHERE u.ID_User = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy thông tin người dùng']);
            return;
        }
        
        // Kiểm tra LiveKit config
        if (empty(LIVEKIT_API_KEY) || empty(LIVEKIT_API_SECRET)) {
            echo json_encode(['success' => false, 'error' => 'LiveKit chưa được cấu hình. Vui lòng cấu hình LIVEKIT_API_KEY và LIVEKIT_API_SECRET']);
            return;
        }
        
        // Tạo identity từ user ID
        $identity = 'user_' . $userId;
        
        // Tạo token options
        $tokenOptions = (new AccessTokenOptions())
            ->setIdentity($identity);
        
        // Tạo video grants với quyền join room, publish và subscribe
        $videoGrant = (new VideoGrant())
            ->setRoomJoin()
            ->setRoomName($roomName)
            ->setCanPublish(true)
            ->setCanSubscribe(true);
        
        // Tạo access token
        $token = (new AccessToken(LIVEKIT_API_KEY, LIVEKIT_API_SECRET))
            ->init($tokenOptions)
            ->setGrant($videoGrant)
            ->toJwt();
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'room_name' => $roomName,
            'ws_url' => LIVEKIT_WS_URL,
            'identity' => $identity,
            'display_name' => $user['display_name']
        ]);
        
    } catch (Exception $e) {
        error_log('LiveKit token creation error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi tạo token: ' . $e->getMessage()]);
    }
}

/**
 * Tạo LiveKit Room (nếu cần)
 */
function createLiveKitRoom($pdo, $userId) {
    $roomName = $_POST['room_name'] ?? '';
    $conversationId = $_POST['conversation_id'] ?? '';
    
    if (empty($roomName)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu tên room']);
        return;
    }
    
    // Kiểm tra quyền truy cập conversation nếu có
    if (!empty($conversationId)) {
        $stmt = $pdo->prepare("
            SELECT id, user1_id, user2_id FROM conversations 
            WHERE id = ? AND (user1_id = ? OR user2_id = ?)
        ");
        $stmt->execute([$conversationId, $userId, $userId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập cuộc trò chuyện này']);
            return;
        }
    }
    
    try {
        // Kiểm tra LiveKit config
        if (empty(LIVEKIT_API_KEY) || empty(LIVEKIT_API_SECRET)) {
            echo json_encode(['success' => false, 'error' => 'LiveKit chưa được cấu hình']);
            return;
        }
        
        // Sử dụng LiveKit Server SDK để tạo room
        $roomService = new RoomServiceClient(LIVEKIT_URL, LIVEKIT_API_KEY, LIVEKIT_API_SECRET);
        
        // Tạo room options
        $roomOptions = (new RoomCreateOptions())
            ->setName($roomName)
            ->setEmptyTimeout(LIVEKIT_ROOM_EMPTY_TIMEOUT)
            ->setMaxParticipants(LIVEKIT_ROOM_MAX_PARTICIPANTS);
        
        // Tạo room
        $room = $roomService->createRoom($roomOptions);
        
        echo json_encode([
            'success' => true,
            'room' => [
                'name' => $room->getName(),
                'num_participants' => $room->getNumParticipants(),
                'creation_time' => $room->getCreationTime()
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('LiveKit room creation error: ' . $e->getMessage());
        // Nếu room đã tồn tại, vẫn trả về success
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo json_encode([
                'success' => true,
                'room' => [
                    'name' => $roomName,
                    'message' => 'Room đã tồn tại'
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Lỗi tạo room: ' . $e->getMessage()]);
        }
    }
}
?>

