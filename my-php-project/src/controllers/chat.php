<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../auth/auth.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi tải file: ' . $e->getMessage()]);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

// Get current user ID
$userId = getCurrentUserId();
if (!$userId || $userId == 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Không thể lấy thông tin người dùng']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    $userId = getCurrentUserId();
    
    // Debug logging
    error_log("Chat controller - Action: $action, UserId: $userId");
    
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
            
        case 'get_available_managers':
            getAvailableManagers($pdo);
            break;
            
        case 'search_conversations':
            searchConversations($pdo, $userId);
            break;
            
        case 'transfer_chat':
            transferChat($pdo, $userId);
            break;
            
        case 'mark_as_read':
            markAsRead($pdo, $userId);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    error_log('Chat controller error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}

// Get conversations for current user
function getConversations($pdo, $userId) {
    // Debug logging
    error_log("getConversations called with userId: " . $userId);
    
    try {
    
    if (!$userId || $userId == 0) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        return;
    }
    
    // Get current user role
    $stmt = $pdo->prepare("SELECT ID_Role FROM users WHERE ID_User = ?");
    $stmt->execute([$userId]);
    $currentUserRole = $stmt->fetchColumn();
    
    // For role 5 (customers), only show conversations with role 3 (event managers)
    if ($currentUserRole == 5) {
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
                    WHEN c.user1_id = ? THEN (u2.TrangThai = 'Hoạt động')
                    ELSE (u1.TrangThai = 'Hoạt động')
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
            WHERE (c.user1_id = ? OR c.user2_id = ?)
            AND (
                (c.user1_id = ? AND u2.ID_Role = 3) OR 
                (c.user2_id = ? AND u1.ID_Role = 3)
            )
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId]);
    } else {
        // For other roles, show all conversations
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
                    WHEN c.user1_id = ? THEN (u2.TrangThai = 'Hoạt động')
                    ELSE (u1.TrangThai = 'Hoạt động')
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
            WHERE (c.user1_id = ? OR c.user2_id = ?)
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
    }
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($conversations) . " conversations for user " . $userId);
    
    echo json_encode(['success' => true, 'conversations' => $conversations]);
    
    } catch (Exception $e) {
        error_log("Error in getConversations: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi tải cuộc trò chuyện: ' . $e->getMessage()]);
    }
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
    
    // Format messages for frontend
    $formattedMessages = [];
    foreach ($messages as $message) {
        $formattedMessages[] = [
            'id' => $message['id'],
            'conversation_id' => $message['conversation_id'],
            'sender_id' => $message['sender_id'],
            'message' => $message['message'],
            'created_at' => $message['created_at'],
            'IsRead' => $message['IsRead'],
            'sender_name' => $message['sender_name']
        ];
    }
    
    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET IsRead = 1 
        WHERE conversation_id = ? AND sender_id != ?
    ");
    $stmt->execute([$conversationId, $userId]);
    
    echo json_encode(['success' => true, 'messages' => $formattedMessages]);
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
        SELECT id, user1_id, user2_id FROM conversations 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        error_log("User $userId does not have access to conversation $conversationId");
        echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập cuộc trò chuyện này']);
        return;
    }
    
    // Log conversation details for debugging
    error_log("Conversation details: " . print_r($conversation, true));
    
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
        
        // Format message for frontend
        $formattedMessage = [
            'id' => $messageData['id'],
            'conversation_id' => $messageData['conversation_id'],
            'sender_id' => $messageData['sender_id'],
            'message' => $messageData['message'],
            'created_at' => $messageData['created_at'],
            'IsRead' => $messageData['IsRead'],
            'sender_name' => $messageData['sender_name']
        ];
        
        echo json_encode(['success' => true, 'message' => $formattedMessage]);
        
    } catch (Exception $e) {
        error_log("Error in sendMessage: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi gửi tin nhắn: ' . $e->getMessage()]);
    }
}

// Create new conversation
function createConversation($pdo, $userId) {
    $otherUserId = $_POST['other_user_id'] ?? '';
    
    // Auto assignment for support
    if ($otherUserId === 'auto') {
        // Get current user role
        $stmt = $pdo->prepare("SELECT ID_Role FROM users WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $currentUserRole = $stmt->fetchColumn();
        
        // For customers (role 5), assign to event managers (role 3)
        if ($currentUserRole == 5) {
            $stmt = $pdo->prepare("
                SELECT u.ID_User, 
                       COALESCE(nv.HoTen, u.Email) as HoTen,
                       u.Email,
                       p.RoleName
                FROM users u
                LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
                LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
                WHERE u.ID_Role = 3 AND u.TrangThai = 'Hoạt động'
                ORDER BY u.ID_User ASC
                LIMIT 1
            ");
            $stmt->execute();
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$staff) {
                // Fallback to any available staff
                $stmt = $pdo->prepare("
                    SELECT u.ID_User, 
                           COALESCE(nv.HoTen, u.Email) as HoTen,
                           u.Email,
                           p.RoleName
                    FROM users u
                    LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
                    LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
                    WHERE u.ID_Role IN (1, 2, 3, 4) AND u.TrangThai = 'Hoạt động'
                    ORDER BY u.ID_Role ASC, u.ID_User ASC
                    LIMIT 1
                ");
                $stmt->execute();
                $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Log staff assignment for debugging
            error_log("Staff assigned to customer: " . print_r($staff, true));
        } else {
            // For other roles, find any available staff
            $stmt = $pdo->prepare("
                SELECT u.ID_User, 
                       COALESCE(nv.HoTen, u.Email) as HoTen,
                       u.Email,
                       p.RoleName
                FROM users u
                LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
                LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
                WHERE u.ID_Role IN (1, 2, 3, 4) AND u.TrangThai = 'Hoạt động'
                ORDER BY u.ID_Role ASC, u.ID_User ASC
                LIMIT 1
            ");
            $stmt->execute();
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if (!$staff) {
            echo json_encode(['success' => false, 'error' => 'Không có nhân viên hỗ trợ trực tuyến']);
            return;
        }
        
        $otherUserId = $staff['ID_User'];
    }
    
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
    
    // Add welcome message
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, MessageText, IsRead, SentAt) 
        VALUES (?, ?, ?, 0, NOW())
    ");
    
    // Get staff role for appropriate welcome message
    $stmt2 = $pdo->prepare("SELECT ID_Role FROM users WHERE ID_User = ?");
    $stmt2->execute([$otherUserId]);
    $staffRole = $stmt2->fetchColumn();
    
    if ($staffRole == 3) {
        $welcomeMessage = "Xin chào! Tôi là quản lý sự kiện. Tôi sẽ hỗ trợ bạn về các vấn đề liên quan đến sự kiện. Bạn có thể gửi tin nhắn bất cứ lúc nào, tôi sẽ trả lời khi online.";
    } else {
        $welcomeMessage = "Xin chào! Tôi là nhân viên hỗ trợ. Tôi có thể giúp gì cho bạn?";
    }
    
    $stmt->execute([$conversationId, $otherUserId, $welcomeMessage]);
    
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

// Mark messages as read
function markAsRead($pdo, $userId) {
    $conversationId = $_POST['conversation_id'] ?? '';
    
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
    
    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET IsRead = 1 
        WHERE conversation_id = ? AND sender_id != ? AND IsRead = 0
    ");
    $stmt->execute([$conversationId, $userId]);
    
    echo json_encode(['success' => true]);
}

// Get available managers for chat
function getAvailableManagers($pdo) {
    $stmt = $pdo->prepare("
        SELECT u.ID_User as id,
               COALESCE(nv.HoTen, u.Email) as name,
               u.Email as email,
               u.TrangThai,
               CASE WHEN u.TrangThai = 'Hoạt động' THEN 1 ELSE 0 END as is_online,
               nv.ChuyenMon as specialization
        FROM users u
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        WHERE u.ID_Role = 3 AND u.TrangThai = 'Hoạt động'
        ORDER BY u.TrangThai DESC, COALESCE(nv.HoTen, u.Email) ASC
    ");
    $stmt->execute();
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'managers' => $managers]);
}

// Search conversations
function searchConversations($pdo, $userId) {
    $query = $_GET['query'] ?? '';
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu từ khóa tìm kiếm']);
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
                WHEN c.user1_id = ? THEN (u2.TrangThai = 'Hoạt động')
                ELSE (u1.TrangThai = 'Hoạt động')
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
        WHERE (c.user1_id = ? OR c.user2_id = ?)
        AND (
            CASE 
                WHEN c.user1_id = ? THEN COALESCE(nv2.HoTen, kh2.HoTen, u2.Email)
                ELSE COALESCE(nv1.HoTen, kh1.HoTen, u1.Email)
            END LIKE ? OR
            m.MessageText LIKE ?
        )
        ORDER BY c.updated_at DESC
    ");
    $searchTerm = "%$query%";
    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $searchTerm, $searchTerm]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'conversations' => $conversations]);
}

// Transfer chat to another staff member
function transferChat($pdo, $userId) {
    $conversationId = $_POST['conversation_id'] ?? '';
    $transferTo = $_POST['transfer_to'] ?? '';
    $note = $_POST['note'] ?? '';
    
    if (empty($conversationId) || empty($transferTo)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin chuyển cuộc trò chuyện']);
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
    
    // Update conversation with new staff member
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET user2_id = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$transferTo, $conversationId]);
    
    // Add transfer notification message
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, MessageText, IsRead, SentAt) 
        VALUES (?, ?, ?, 0, NOW())
    ");
    $transferMessage = "Cuộc trò chuyện đã được chuyển cho nhân viên khác. " . ($note ? "Ghi chú: $note" : "");
    $stmt->execute([$conversationId, $userId, $transferMessage]);
    
    echo json_encode(['success' => true, 'message' => 'Đã chuyển cuộc trò chuyện thành công']);
}

// getCurrentUserId() is already defined in src/auth/auth.php
?>