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
        case 'get_conversations':
            getConversations($pdo, $userId);
            break;
            
        case 'get_messages':
            getMessages($pdo, $userId);
            break;
            
        case 'send_message':
            sendMessage($pdo, $userId);
            break;
            
        case 'create_conversation':
            createConversation($pdo, $userId);
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

// Get conversations for current user
function getConversations($pdo, $userId) {
    // Debug logging
    error_log("getConversations called with userId: " . $userId);
    
    if (!$userId || $userId == 0) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.user1_id,
            c.user2_id,
            c.updated_at,
            CASE 
                WHEN c.user1_id = ? THEN COALESCE(nv2.HoTen, kh2.HoTen, u2.Email)
                ELSE COALESCE(nv1.HoTen, kh1.HoTen, u1.Email)
            END as other_user_name,
            CASE 
                WHEN c.user1_id = ? THEN u2.ID_User
                ELSE u1.ID_User
            END as other_user_id,
            m.MessageText as last_message,
            m.SentAt as last_message_time,
            CASE 
                WHEN c.user1_id = ? THEN u2.TrangThai = 'Hoạt động'
                ELSE u1.TrangThai = 'Hoạt động'
            END as is_online
        FROM conversations c
        LEFT JOIN users u1 ON c.user1_id = u1.ID_User
        LEFT JOIN users u2 ON c.user2_id = u2.ID_User
        LEFT JOIN nhanvieninfo nv1 ON u1.ID_User = nv1.ID_User
        LEFT JOIN nhanvieninfo nv2 ON u2.ID_User = nv2.ID_User
        LEFT JOIN khachhanginfo kh1 ON u1.ID_User = kh1.ID_User
        LEFT JOIN khachhanginfo kh2 ON u2.ID_User = kh2.ID_User
        LEFT JOIN (
            SELECT conversation_id, MessageText, SentAt,
                   ROW_NUMBER() OVER (PARTITION BY conversation_id ORDER BY SentAt DESC) as rn
            FROM messages
        ) m ON c.id = m.conversation_id AND m.rn = 1
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($conversations) . " conversations for user " . $userId);
    
    echo json_encode(['success' => true, 'conversations' => $conversations]);
}

// Get messages for a conversation
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
    
    // Debug logging
    error_log("Retrieved " . count($messages) . " messages for conversation $conversationId");
    if (!empty($messages)) {
        error_log("First message: " . print_r($messages[0], true));
    }
    
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
    // Debug logging
    error_log("sendMessage called with userId: " . $userId);
    error_log("POST data: " . print_r($_POST, true));
    
    $conversationId = $_POST['conversation_id'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($conversationId) || empty($message)) {
        error_log("Missing conversation_id or message");
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
        return;
    }
    
    if (!$userId || $userId == 0) {
        error_log("Invalid user ID: " . $userId);
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
        error_log("User $userId does not have access to conversation $conversationId");
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
        error_log("Message inserted with ID: " . $messageId);
        
        // Update conversation timestamp and last message
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
        
        error_log("Message data: " . print_r($messageData, true));
        echo json_encode(['success' => true, 'message' => $messageData]);
        
    } catch (Exception $e) {
        error_log("Error in sendMessage: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi gửi tin nhắn: ' . $e->getMessage()]);
    }
}

// Create new conversation
function createConversation($pdo, $userId) {
    $otherUserId = $_POST['other_user_id'] ?? '';
    
    if (empty($otherUserId)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID người dùng']);
        return;
    }
    
    // Check if conversation already exists
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
    ");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
    $existingConv = $stmt->fetch();
    
    if ($existingConv) {
        echo json_encode(['success' => true, 'conversation_id' => $existingConv['id']]);
        return;
    }
    
    // Create new conversation
    $stmt = $pdo->prepare("
        INSERT INTO conversations (user1_id, user2_id, created_at, updated_at) 
        VALUES (?, ?, NOW(), NOW())
    ");
    $stmt->execute([$userId, $otherUserId]);
    
    $conversationId = $pdo->lastInsertId();
    
    echo json_encode(['success' => true, 'conversation_id' => $conversationId]);
}

// Get available staff for chat
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
        WHERE u.ID_Role IN (1, 2, 3, 4) AND u.TrangThai = 'Hoạt động'
        ORDER BY u.TrangThai DESC, COALESCE(nv.HoTen, u.Email) ASC
    ");
    $stmt->execute();
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'staff' => $staff]);
}

// getCurrentUserId() is already defined in src/auth/auth.php
?>