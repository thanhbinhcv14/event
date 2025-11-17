<?php
/**
 * Call Controller - Xử lý voice call và video call
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../../config/database.php';
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
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'initiate_call':
            initiateCall($pdo, $userId);
            break;
        case 'accept_call':
            acceptCall($pdo, $userId);
            break;
        case 'reject_call':
            rejectCall($pdo, $userId);
            break;
        case 'end_call':
            endCall($pdo, $userId);
            break;
        case 'get_call_status':
            getCallStatus($pdo, $userId);
            break;
        case 'get_active_calls':
            getActiveCalls($pdo, $userId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ: ' . $action]);
            break;
    }

} catch (Exception $e) {
    error_log('Call controller error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}

/**
 * Khởi tạo cuộc gọi
 */
function initiateCall($pdo, $userId) {
    $conversationId = $_POST['conversation_id'] ?? '';
    $callType = $_POST['call_type'] ?? 'voice'; // voice hoặc video
    
    if (empty($conversationId)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID cuộc trò chuyện']);
        return;
    }
    
    if (!in_array($callType, ['voice', 'video'])) {
        echo json_encode(['success' => false, 'error' => 'Loại cuộc gọi không hợp lệ']);
        return;
    }
    
    // Kiểm tra quyền truy cập conversation
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
    
    // Xác định người nhận
    $receiverId = ($conversation['user1_id'] == $userId) ? $conversation['user2_id'] : $conversation['user1_id'];
    
    // QUAN TRỌNG: Cleanup tất cả call sessions cũ trước khi kiểm tra busy
    try {
        // 1. Cleanup call sessions cũ hơn 10 phút (bất kỳ status nào)
        $stmt = $pdo->prepare("
            UPDATE call_sessions 
            SET status = 'ended', ended_at = NOW()
            WHERE status IN ('initiated', 'ringing', 'accepted') 
            AND started_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ");
        $stmt->execute();
        
        // 2. Cleanup TẤT CẢ call sessions cũ của conversation này (không chỉ cũ hơn 1 phút)
        // Điều này đảm bảo không có call cũ nào của conversation này còn sót lại
        $stmt = $pdo->prepare("
            UPDATE call_sessions 
            SET status = 'ended', ended_at = NOW()
            WHERE conversation_id = ?
            AND status IN ('initiated', 'ringing')
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM call_sessions 
                    WHERE conversation_id = ? 
                    AND status IN ('initiated', 'ringing', 'accepted')
                    ORDER BY started_at DESC 
                    LIMIT 1
                ) AS latest
            )
        ");
        $stmt->execute([$conversationId, $conversationId]);
        
        // 3. Cleanup call sessions của receiver với status 'initiated' hoặc 'ringing' cũ hơn 5 giây
        // Chỉ giữ lại call đang ring (trong 5 giây gần nhất) hoặc đang accepted
        $stmt = $pdo->prepare("
            UPDATE call_sessions 
            SET status = 'missed', ended_at = NOW()
            WHERE receiver_id = ?
            AND status IN ('initiated', 'ringing')
            AND started_at < DATE_SUB(NOW(), INTERVAL 5 SECOND)
        ");
        $stmt->execute([$receiverId]);
        
        // 4. Cleanup call sessions của caller với status 'initiated' hoặc 'ringing' cũ hơn 5 giây
        $stmt = $pdo->prepare("
            UPDATE call_sessions 
            SET status = 'missed', ended_at = NOW()
            WHERE caller_id = ?
            AND status IN ('initiated', 'ringing')
            AND started_at < DATE_SUB(NOW(), INTERVAL 5 SECOND)
        ");
        $stmt->execute([$userId]);
        
    } catch (Exception $e) {
        // Log error but continue
        error_log('Error cleaning up old call sessions: ' . $e->getMessage());
    }
    
    // QUAN TRỌNG: Chỉ coi là "busy" nếu:
    // 1. Có call với status 'accepted' (đang thực sự gọi) - bất kỳ thời gian nào
    // 2. Có call với status 'initiated' hoặc 'ringing' trong 5 giây gần nhất (đang ring)
    // 3. Loại trừ conversation hiện tại (cho phép gọi lại cùng conversation)
    $stmt = $pdo->prepare("
        SELECT id, status, started_at FROM call_sessions 
        WHERE receiver_id = ? 
        AND (
            (status = 'accepted') OR 
            (status IN ('initiated', 'ringing') AND started_at > DATE_SUB(NOW(), INTERVAL 5 SECOND))
        )
        AND conversation_id != ?
    ");
    $stmt->execute([$receiverId, $conversationId]);
    $activeCall = $stmt->fetch();
    if ($activeCall) {
        // Log để debug
        error_log("Call blocked: Receiver {$receiverId} has active call session ID: {$activeCall['id']}, status: {$activeCall['status']}, started_at: {$activeCall['started_at']}");
        echo json_encode(['success' => false, 'error' => 'Người nhận đang bận']);
        return;
    }
    
    // Kiểm tra caller có đang trong cuộc gọi khác không (tương tự logic trên)
    $stmt = $pdo->prepare("
        SELECT id, status, started_at FROM call_sessions 
        WHERE caller_id = ? 
        AND (
            (status = 'accepted') OR 
            (status IN ('initiated', 'ringing') AND started_at > DATE_SUB(NOW(), INTERVAL 5 SECOND))
        )
        AND conversation_id != ?
    ");
    $stmt->execute([$userId, $conversationId]);
    $activeCall = $stmt->fetch();
    if ($activeCall) {
        // Log để debug
        error_log("Call blocked: Caller {$userId} has active call session ID: {$activeCall['id']}, status: {$activeCall['status']}, started_at: {$activeCall['started_at']}");
        echo json_encode(['success' => false, 'error' => 'Bạn đang trong cuộc gọi khác']);
        return;
    }
    
    try {
        // Tạo call session
        $stmt = $pdo->prepare("
            INSERT INTO call_sessions (conversation_id, caller_id, receiver_id, call_type, status, started_at) 
            VALUES (?, ?, ?, ?, 'initiated', NOW())
        ");
        $stmt->execute([$conversationId, $userId, $receiverId, $callType]);
        $callId = $pdo->lastInsertId();
        
        // Tạo message thông báo cuộc gọi
        $messageText = $callType === 'video' ? '[Cuộc gọi video]' : '[Cuộc gọi thoại]';
        $stmt = $pdo->prepare("
            INSERT INTO messages (conversation_id, sender_id, MessageText, message_type, IsRead, SentAt) 
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$conversationId, $userId, $messageText, $callType . '_call']);
        
        // Update conversation timestamp
        $stmt = $pdo->prepare("
            UPDATE conversations 
            SET updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$conversationId]);
        
        // Lấy thông tin người nhận
        $stmt = $pdo->prepare("
            SELECT COALESCE(nv.HoTen, kh.HoTen, u.Email) as receiver_name, u.Email
            FROM users u
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            WHERE u.ID_User = ?
        ");
        $stmt->execute([$receiverId]);
        $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'call_id' => $callId,
            'call_type' => $callType,
            'receiver_id' => $receiverId,
            'receiver_name' => $receiver['receiver_name'],
            'status' => 'initiated'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi tạo cuộc gọi: ' . $e->getMessage()]);
    }
}

/**
 * Chấp nhận cuộc gọi
 */
function acceptCall($pdo, $userId) {
    $callId = $_POST['call_id'] ?? '';
    
    if (empty($callId)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID cuộc gọi']);
        return;
    }
    
    // Kiểm tra quyền và trạng thái cuộc gọi
    $stmt = $pdo->prepare("
        SELECT id, caller_id, receiver_id, call_type, status 
        FROM call_sessions 
        WHERE id = ? AND receiver_id = ? AND status IN ('initiated', 'ringing')
    ");
    $stmt->execute([$callId, $userId]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$call) {
        echo json_encode(['success' => false, 'error' => 'Cuộc gọi không tồn tại hoặc không thể chấp nhận']);
        return;
    }
    
    try {
        // Cập nhật trạng thái cuộc gọi
        $stmt = $pdo->prepare("
            UPDATE call_sessions 
            SET status = 'accepted', started_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$callId]);
        
        // Lấy thông tin caller
        $stmt = $pdo->prepare("
            SELECT COALESCE(nv.HoTen, kh.HoTen, u.Email) as caller_name
            FROM users u
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            WHERE u.ID_User = ?
        ");
        $stmt->execute([$call['caller_id']]);
        $caller = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'call_id' => $callId,
            'call_type' => $call['call_type'],
            'caller_id' => $call['caller_id'],
            'caller_name' => $caller['caller_name'],
            'status' => 'accepted'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi chấp nhận cuộc gọi: ' . $e->getMessage()]);
    }
}

/**
 * Từ chối cuộc gọi
 */
function rejectCall($pdo, $userId) {
    $callId = $_POST['call_id'] ?? '';
    
    if (empty($callId)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID cuộc gọi']);
        return;
    }
    
    // Kiểm tra quyền và trạng thái cuộc gọi
    $stmt = $pdo->prepare("
        SELECT id, caller_id, receiver_id, call_type, status 
        FROM call_sessions 
        WHERE id = ? AND receiver_id = ? AND status IN ('initiated', 'ringing')
    ");
    $stmt->execute([$callId, $userId]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$call) {
        echo json_encode(['success' => false, 'error' => 'Cuộc gọi không tồn tại hoặc không thể từ chối']);
        return;
    }
    
    try {
        // Cập nhật trạng thái cuộc gọi
        $stmt = $pdo->prepare("
            UPDATE call_sessions 
            SET status = 'rejected', ended_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$callId]);
        
        echo json_encode([
            'success' => true,
            'call_id' => $callId,
            'status' => 'rejected'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi từ chối cuộc gọi: ' . $e->getMessage()]);
    }
}

/**
 * Kết thúc cuộc gọi
 * QUAN TRỌNG: Cho phép end call ở mọi trạng thái (initiated, ringing, accepted)
 */
function endCall($pdo, $userId) {
    $callId = $_POST['call_id'] ?? '';
    
    if (empty($callId)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID cuộc gọi']);
        return;
    }
    
    // Kiểm tra quyền và trạng thái cuộc gọi - Cho phép end ở mọi trạng thái
    $stmt = $pdo->prepare("
        SELECT id, caller_id, receiver_id, call_type, status, started_at 
        FROM call_sessions 
        WHERE id = ? AND (caller_id = ? OR receiver_id = ?) 
        AND status IN ('initiated', 'ringing', 'accepted')
    ");
    $stmt->execute([$callId, $userId, $userId]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$call) {
        // Nếu không tìm thấy call, vẫn trả về success để frontend có thể cleanup
        echo json_encode([
            'success' => true,
            'call_id' => $callId,
            'status' => 'ended',
            'message' => 'Cuộc gọi đã được kết thúc'
        ]);
        return;
    }
    
    try {
        // Tính toán thời lượng cuộc gọi (nếu có started_at)
        $duration = 0;
        if ($call['started_at']) {
            try {
                $startedAt = new DateTime($call['started_at']);
                $endedAt = new DateTime();
                $duration = $endedAt->getTimestamp() - $startedAt->getTimestamp();
                // Đảm bảo duration không âm
                if ($duration < 0) {
                    $duration = 0;
                }
            } catch (Exception $e) {
                error_log('Error calculating call duration: ' . $e->getMessage());
                $duration = 0;
            }
        }
        
        // Cập nhật trạng thái cuộc gọi - Cho phép end ở mọi trạng thái
        $stmt = $pdo->prepare("
            UPDATE call_sessions 
            SET status = 'ended', ended_at = NOW(), duration = ?
            WHERE id = ?
        ");
        $stmt->execute([$duration, $callId]);
        
        echo json_encode([
            'success' => true,
            'call_id' => $callId,
            'duration' => $duration,
            'status' => 'ended',
            'message' => 'Cuộc gọi đã được kết thúc'
        ]);
        
    } catch (Exception $e) {
        error_log('Error ending call: ' . $e->getMessage());
        // Vẫn trả về success để frontend có thể cleanup
        echo json_encode([
            'success' => true,
            'call_id' => $callId,
            'status' => 'ended',
            'message' => 'Cuộc gọi đã được kết thúc (có lỗi nhỏ khi lưu)'
        ]);
    }
}

/**
 * Lấy trạng thái cuộc gọi
 */
function getCallStatus($pdo, $userId) {
    $callId = $_GET['call_id'] ?? '';
    
    if (empty($callId)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID cuộc gọi']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT cs.*, 
               COALESCE(nv1.HoTen, kh1.HoTen, u1.Email) as caller_name,
               COALESCE(nv2.HoTen, kh2.HoTen, u2.Email) as receiver_name
        FROM call_sessions cs
        LEFT JOIN users u1 ON cs.caller_id = u1.ID_User
        LEFT JOIN users u2 ON cs.receiver_id = u2.ID_User
        LEFT JOIN nhanvieninfo nv1 ON u1.ID_User = nv1.ID_User
        LEFT JOIN nhanvieninfo nv2 ON u2.ID_User = nv2.ID_User
        LEFT JOIN khachhanginfo kh1 ON u1.ID_User = kh1.ID_User
        LEFT JOIN khachhanginfo kh2 ON u2.ID_User = kh2.ID_User
        WHERE cs.id = ? AND (cs.caller_id = ? OR cs.receiver_id = ?)
    ");
    $stmt->execute([$callId, $userId, $userId]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$call) {
        echo json_encode(['success' => false, 'error' => 'Cuộc gọi không tồn tại']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'call' => $call
    ]);
}

/**
 * Lấy danh sách cuộc gọi đang hoạt động
 */
function getActiveCalls($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT cs.*, 
               COALESCE(nv1.HoTen, kh1.HoTen, u1.Email) as caller_name,
               COALESCE(nv2.HoTen, kh2.HoTen, u2.Email) as receiver_name
        FROM call_sessions cs
        LEFT JOIN users u1 ON cs.caller_id = u1.ID_User
        LEFT JOIN users u2 ON cs.receiver_id = u2.ID_User
        LEFT JOIN nhanvieninfo nv1 ON u1.ID_User = nv1.ID_User
        LEFT JOIN nhanvieninfo nv2 ON u2.ID_User = nv2.ID_User
        LEFT JOIN khachhanginfo kh1 ON u1.ID_User = kh1.ID_User
        LEFT JOIN khachhanginfo kh2 ON u2.ID_User = kh2.ID_User
        WHERE (cs.caller_id = ? OR cs.receiver_id = ?) 
        AND cs.status IN ('initiated', 'ringing', 'accepted')
        ORDER BY cs.started_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    $calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'calls' => $calls
    ]);
}
?>
