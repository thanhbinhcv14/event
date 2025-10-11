<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    $userId = getCurrentUserId();
    
    switch ($action) {
        case 'get_or_create_support_conversation':
            getOrCreateSupportConversation($pdo, $userId);
            break;
            
        case 'get_messages':
            getMessages($pdo, $userId);
            break;
            
        case 'send_message':
            sendMessage($pdo, $userId);
            break;
            
        case 'get_available_staff':
            getAvailableStaff($pdo);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}

// Get or create support conversation with admin/event manager
function getOrCreateSupportConversation($pdo, $userId) {
    // Debug logging
    error_log("getOrCreateSupportConversation called with userId: " . $userId);
    
    // Find available staff (admin or event manager)
    $stmt = $pdo->prepare("
        SELECT u.ID_User, 
               COALESCE(nv.HoTen, u.Email) as HoTen,
               u.Email,
               p.RoleName
        FROM users u
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
        WHERE u.ID_Role IN (1, 3) AND u.TrangThai = 'Hoạt động'
        ORDER BY u.ID_Role ASC, u.ID_User ASC
        LIMIT 1
    ");
    $stmt->execute();
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Found staff: " . print_r($staff, true));
    
    if (!$staff) {
        echo json_encode(['success' => false, 'error' => 'Không có nhân viên hỗ trợ trực tuyến']);
        return;
    }
    
    $staffId = $staff['ID_User'];
    
    // Check if conversation already exists
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
    ");
    $stmt->execute([$userId, $staffId, $staffId, $userId]);
    $existingConv = $stmt->fetch();
    
    if ($existingConv) {
        error_log("Existing conversation found: " . $existingConv['id']);
        echo json_encode([
            'success' => true, 
            'conversation_id' => $existingConv['id'],
            'staff_info' => $staff
        ]);
        return;
    }
    
    // Create new conversation
    error_log("Creating new conversation between user $userId and staff $staffId");
    $stmt = $pdo->prepare("
        INSERT INTO conversations (user1_id, user2_id, created_at, updated_at) 
        VALUES (?, ?, NOW(), NOW())
    ");
    $stmt->execute([$userId, $staffId]);
    
    $conversationId = $pdo->lastInsertId();
    error_log("New conversation created with ID: " . $conversationId);
    
    echo json_encode([
        'success' => true, 
        'conversation_id' => $conversationId,
        'staff_info' => $staff
    ]);
}

// Get messages for support conversation
function getMessages($pdo, $userId) {
    $conversationId = $_GET['conversation_id'] ?? '';
    
    if (empty($conversationId)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID cuộc trò chuyện']);
        return;
    }
    
    // Check if user has access to this conversation
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập cuộc trò chuyện này']);
        return;
    }
    
    // Get messages with proper field mapping
    $stmt = $pdo->prepare("
        SELECT m.id,
               m.conversation_id,
               m.sender_id,
               m.MessageText as message,
               m.SentAt as created_at,
               m.IsRead,
               COALESCE(nv.HoTen, kh.HoTen, u.Email) as sender_name
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.ID_User
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
        WHERE m.conversation_id = ?
        ORDER BY m.SentAt ASC
    ");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET IsRead = 1 
        WHERE conversation_id = ? AND sender_id != ?
    ");
    $stmt->execute([$conversationId, $userId]);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

// Send a message
function sendMessage($pdo, $userId) {
    $conversationId = $_POST['conversation_id'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($conversationId) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
        return;
    }
    
    if (!$userId || $userId == 0) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        return;
    }
    
    // Check if user has access to this conversation
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập cuộc trò chuyện này']);
        return;
    }
    
    try {
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO messages (conversation_id, sender_id, MessageText, IsRead, SentAt) 
            VALUES (?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$conversationId, $userId, $message]);
        
        $messageId = $pdo->lastInsertId();
        
        // Update conversation timestamp
        $stmt = $pdo->prepare("
            UPDATE conversations 
            SET updated_at = NOW(), LastMessage_ID = ?
            WHERE id = ?
        ");
        $stmt->execute([$messageId, $conversationId]);
        
        // Get the inserted message with proper field mapping
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   COALESCE(nv.HoTen, kh.HoTen, u.Email) as sender_name,
                   m.SentAt as created_at,
                   m.MessageText as message,
                   m.sender_id
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.ID_User
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            WHERE m.id = ?
        ");
        $stmt->execute([$messageId]);
        $messageData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'message' => $messageData]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi khi gửi tin nhắn: ' . $e->getMessage()]);
    }
}

// Get available staff
function getAvailableStaff($pdo) {
    $stmt = $pdo->prepare("
        SELECT u.ID_User, 
               COALESCE(nv.HoTen, u.Email) as HoTen, 
               u.Email, 
               u.TrangThai,
               p.RoleName
        FROM users u
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
        WHERE u.ID_Role IN (1, 3) AND u.TrangThai = 'Hoạt động'
        ORDER BY u.ID_Role ASC, COALESCE(nv.HoTen, u.Email) ASC
    ");
    $stmt->execute();
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'staff' => $staff]);
}
?>
