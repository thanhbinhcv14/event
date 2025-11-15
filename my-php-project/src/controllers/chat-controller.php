<?php
/**
 * Chat Controller - Chuyên xử lý các chức năng chat
 * Bản FIX: Khách hàng (Role 5) chỉ được chat với Quản trị viên (1) và Quản lý sự kiện (3)
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
    $userId = getCurrentUserId();
    error_log("Chat Controller - Action: $action, UserId: $userId");

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
        case 'get_admin_user':
            getAdminUser($pdo);
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
        case 'get_chat_stats':
            getChatStats($pdo, $userId);
            break;
        case 'get_online_users':
            getOnlineUsers($pdo);
            break;
        case 'get_online_count':
            getOnlineCount($pdo);
            break;
        case 'update_user_status':
            updateUserStatus($pdo, $userId);
            break;
        case 'set_user_online':
            setUserOnline($pdo, $userId);
            break;
        case 'set_user_offline':
            setUserOffline($pdo, $userId);
            break;
        case 'update_activity':
            updateUserActivity($pdo, $userId);
            break;
        case 'get_media_messages':
            getMediaMessages($pdo, $userId);
            break;
        case 'delete_message':
            deleteMessage($pdo, $userId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ: ' . $action]);
            break;
    }

} catch (Exception $e) {
    error_log('Chat controller error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}

/**
 * 🧾 Lấy danh sách cuộc trò chuyện
 */
function getConversations($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT ID_Role FROM users WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $currentUserRole = $stmt->fetchColumn();

        if ($currentUserRole == 5) {
            // Chỉ hiện admin (1) và event manager (3)
            $stmt = $pdo->prepare("
                SELECT 
                    c.id, c.user1_id, c.user2_id, c.updated_at,
                    CASE WHEN c.user1_id = ? THEN COALESCE(nv2.HoTen, kh2.HoTen, u2.Email)
                         ELSE COALESCE(nv1.HoTen, kh1.HoTen, u1.Email) END as other_user_name,
                    CASE WHEN c.user1_id = ? THEN u2.ID_User ELSE u1.ID_User END as other_user_id,
                    m.MessageText as last_message, m.SentAt as last_message_time,
                    CASE WHEN c.user1_id = ? THEN (u2.TrangThai = 'Hoạt động' AND u2.OnlineStatus = 'Online') ELSE (u1.TrangThai = 'Hoạt động' AND u1.OnlineStatus = 'Online') END as is_online,
                    (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != ? AND IsRead = 0) as unread_count
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
                    (c.user1_id = ? AND u2.ID_Role IN (1,3)) OR 
                    (c.user2_id = ? AND u1.ID_Role IN (1,3))
                )
                ORDER BY c.updated_at DESC
            ");
            $stmt->execute([$userId,$userId,$userId,$userId,$userId,$userId,$userId,$userId]);
        } else {
            // Các role khác xem được tất cả
            $stmt = $pdo->prepare("
                SELECT 
                    c.id, c.user1_id, c.user2_id, c.updated_at,
                    CASE WHEN c.user1_id = ? THEN COALESCE(nv2.HoTen, kh2.HoTen, u2.Email)
                         ELSE COALESCE(nv1.HoTen, kh1.HoTen, u1.Email) END as other_user_name,
                    CASE WHEN c.user1_id = ? THEN u2.ID_User ELSE u1.ID_User END as other_user_id,
                    m.MessageText as last_message, m.SentAt as last_message_time,
                    CASE WHEN c.user1_id = ? THEN (u2.TrangThai = 'Hoạt động' AND u2.OnlineStatus = 'Online') ELSE (u1.TrangThai = 'Hoạt động' AND u1.OnlineStatus = 'Online') END as is_online,
                    (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != ? AND IsRead = 0) as unread_count
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
            $stmt->execute([$userId,$userId,$userId,$userId,$userId,$userId]);
        }

        echo json_encode(['success' => true, 'conversations' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi khi tải cuộc trò chuyện: ' . $e->getMessage()]);
    }
}

/**
 * 💬 Tạo cuộc trò chuyện mới
 */
function createConversation($pdo, $userId) {
    $otherUserId = $_POST['other_user_id'] ?? '';

    $stmt = $pdo->prepare("SELECT ID_Role FROM users WHERE ID_User = ?");
    $stmt->execute([$userId]);
    $currentUserRole = $stmt->fetchColumn();

    if ($otherUserId === 'auto' || $otherUserId === 'auto_online') {
        // Tìm nhân viên online trước, nếu không có thì tìm admin
        $stmt = $pdo->prepare("
            SELECT u.ID_User, COALESCE(nv.HoTen, u.Email) as HoTen, u.Email, p.RoleName, u.TrangThai,
                   CASE WHEN u.TrangThai = 'Hoạt động' AND u.OnlineStatus = 'Online' THEN 1 ELSE 0 END as is_online
            FROM users u
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
            LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
            WHERE u.ID_Role IN (1,3) AND u.TrangThai = 'Hoạt động'
            ORDER BY 
                CASE WHEN u.TrangThai = 'Hoạt động' THEN 0 ELSE 1 END,
                u.ID_Role ASC, 
                u.ID_User ASC
            LIMIT 1
        ");
        $stmt->execute();
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff) {
            echo json_encode(['success' => false, 'error' => 'Không có nhân viên hỗ trợ trực tuyến']);
            return;
        }
        $otherUserId = $staff['ID_User'];
    } elseif ($otherUserId === 'admin') {
        // Tìm admin (role 1) trực tiếp
        $stmt = $pdo->prepare("
            SELECT u.ID_User, COALESCE(nv.HoTen, u.Email) as HoTen, u.Email, p.RoleName, u.TrangThai
            FROM users u
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
            LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
            WHERE u.ID_Role = 1 AND u.TrangThai = 'Hoạt động'
            ORDER BY u.ID_User ASC
            LIMIT 1
        ");
        $stmt->execute();
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff) {
            echo json_encode(['success' => false, 'error' => 'Không có quản trị viên trực tuyến']);
            return;
        }
        $otherUserId = $staff['ID_User'];
    }

    // Kiểm tra trùng hội thoại
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
    ");
    $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
    $existing = $stmt->fetch();
    if ($existing) {
        echo json_encode(['success' => true, 'conversation_id' => $existing['id']]);
        return;
    }

    // Tạo hội thoại mới
    $stmt = $pdo->prepare("
        INSERT INTO conversations (user1_id, user2_id, created_at, updated_at)
        VALUES (?, ?, NOW(), NOW())
    ");
    $stmt->execute([$userId, $otherUserId]);
    $conversationId = $pdo->lastInsertId();

    // Lời chào tự động
    $stmt2 = $pdo->prepare("SELECT ID_Role FROM users WHERE ID_User = ?");
    $stmt2->execute([$otherUserId]);
    $staffRole = $stmt2->fetchColumn();

    if ($staffRole == 1) {
        $welcomeMessage = "Xin chào! Tôi là Quản trị viên. Tôi có thể hỗ trợ bạn về tài khoản hoặc các vấn đề hệ thống.";
    } elseif ($staffRole == 3) {
        $welcomeMessage = "Xin chào! Tôi là Quản lý sự kiện. Tôi sẽ giúp bạn với các vấn đề liên quan đến sự kiện.";
    } else {
        $welcomeMessage = "Xin chào! Tôi là nhân viên hỗ trợ.";
    }

    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, MessageText, IsRead, SentAt)
        VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->execute([$conversationId, $otherUserId, $welcomeMessage]);

    // Trả về thông tin nhân viên được chọn nếu có
    $response = ['success' => true, 'conversation_id' => $conversationId];
    
    if (isset($staff) && $staff) {
        $response['assigned_staff'] = [
            'id' => $staff['ID_User'],
            'name' => $staff['HoTen'],
            'email' => $staff['Email'],
            'role' => $staff['RoleName'],
            'is_online' => isset($staff['is_online']) ? $staff['is_online'] : 1
        ];
    }
    
    echo json_encode($response);
}

/**
 * 📋 Lấy danh sách nhân viên hỗ trợ
 */
function getAvailableStaff($pdo) {
    $stmt = $pdo->prepare("
        SELECT u.ID_User, COALESCE(nv.HoTen, u.Email) as HoTen, u.Email, u.TrangThai, p.RoleName
        FROM users u
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
        WHERE u.ID_Role IN (1,3) AND u.TrangThai = 'Hoạt động'
        ORDER BY u.ID_Role ASC, COALESCE(nv.HoTen, u.Email) ASC
    ");
    $stmt->execute();
    echo json_encode(['success' => true, 'staff' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

/**
 * Lấy tin nhắn của một cuộc trò chuyện
 */
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
    
    // Get messages with proper field mapping (including file/media fields)
    $stmt = $pdo->prepare("
        SELECT m.id,
               m.conversation_id,
               m.sender_id,
               m.MessageText as message,
               m.message_type,
               m.file_path,
               m.file_name,
               m.file_size,
               m.mime_type,
               m.SentAt as created_at,
               m.IsRead,
               COALESCE(nv.HoTen, kh.HoTen, u.Email) as sender_name,
               cm.thumbnail_path
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.ID_User
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
        LEFT JOIN chat_media cm ON m.id = cm.message_id
        WHERE m.conversation_id = ?
        ORDER BY m.SentAt ASC
    ");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug logging
    error_log("Retrieved " . count($messages) . " messages for conversation $conversationId");
    
    // Format messages for frontend (including file/media fields)
    $formattedMessages = [];
    foreach ($messages as $message) {
        $formattedMessage = [
            'id' => $message['id'],
            'conversation_id' => $message['conversation_id'],
            'sender_id' => $message['sender_id'],
            'message' => $message['message'],
            'created_at' => $message['created_at'],
            'IsRead' => $message['IsRead'],
            'sender_name' => $message['sender_name']
        ];
        
        // Add file/media fields if they exist
        if (!empty($message['message_type'])) {
            $formattedMessage['message_type'] = $message['message_type'];
        }
        if (!empty($message['file_path'])) {
            $formattedMessage['file_path'] = $message['file_path'];
        }
        if (!empty($message['file_name'])) {
            $formattedMessage['file_name'] = $message['file_name'];
        }
        if (!empty($message['file_size'])) {
            $formattedMessage['file_size'] = $message['file_size'];
        }
        if (!empty($message['mime_type'])) {
            $formattedMessage['mime_type'] = $message['mime_type'];
        }
        if (!empty($message['thumbnail_path'])) {
            $formattedMessage['thumbnail_path'] = $message['thumbnail_path'];
        }
        
        $formattedMessages[] = $formattedMessage;
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

/**
 * Gửi tin nhắn
 */
function sendMessage($pdo, $userId) {
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
    
    try {
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO messages (conversation_id, sender_id, MessageText, IsRead, SentAt) 
            VALUES (?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$conversationId, $userId, $message]);
        
        $messageId = $pdo->lastInsertId();
        error_log("Message inserted with ID: " . $messageId);
        
        // Update conversation timestamp
        $stmt = $pdo->prepare("
            UPDATE conversations 
            SET updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$conversationId]);
        
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
        
        // Debug logging for message alignment
        error_log("Message sent by user $userId: " . json_encode($formattedMessage));
        
        echo json_encode(['success' => true, 'message' => $formattedMessage]);
        
    } catch (Exception $e) {
        error_log("Error in sendMessage: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi gửi tin nhắn: ' . $e->getMessage()]);
    }
}

/**
 * Lấy danh sách quản lý sự kiện đang online (chỉ role 1 và 3)
 */
function getAvailableManagers($pdo) {
    // Thời gian threshold để tính online (5 phút)
    $onlineThreshold = 300;
    
    // Chỉ lấy users role 1 và 3 đang online
    // Kiểm tra: TrangThai = 'Hoạt động', OnlineStatus = 'Online', và LastActivity trong 5 phút
    $stmt = $pdo->prepare("
        SELECT u.ID_User as id,
               COALESCE(nv.HoTen, u.Email) as name,
               u.Email as email,
               u.TrangThai,
               u.OnlineStatus,
               u.LastActivity,
               CASE 
                   WHEN u.TrangThai = 'Hoạt động' 
                        AND u.OnlineStatus = 'Online' 
                        AND (u.LastActivity IS NULL 
                             OR u.LastActivity = '' 
                             OR TIMESTAMPDIFF(SECOND, u.LastActivity, NOW()) <= ?) 
                   THEN 1 
                   ELSE 0 
               END as is_online,
               nv.ChucVu as chuc_vu,
               p.RoleName,
               u.ID_Role
        FROM users u
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
        WHERE u.ID_Role IN (1, 3)
        AND u.TrangThai = 'Hoạt động' 
        AND u.OnlineStatus = 'Online'
        AND (u.LastActivity IS NULL 
             OR u.LastActivity = '' 
             OR TIMESTAMPDIFF(SECOND, u.LastActivity, NOW()) <= ?)
        ORDER BY 
            u.ID_Role ASC,
            COALESCE(nv.HoTen, u.Email) ASC
    ");
    $stmt->execute([$onlineThreshold, $onlineThreshold]);
    $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'managers' => $managers]);
}

/**
 * Lấy ID của quản trị viên (role 1)
 */
function getAdminUser($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT ID_User 
            FROM users 
            WHERE ID_Role = 1 
            AND TrangThai = 'Hoạt động'
            ORDER BY ID_User ASC
            LIMIT 1
        ");
        $stmt->execute();
        $adminId = $stmt->fetchColumn();
        
        if ($adminId) {
            echo json_encode(['success' => true, 'admin_id' => $adminId]);
        } else {
            // Dự phòng: lấy user đầu tiên có role 1
            $stmt = $pdo->prepare("
                SELECT ID_User 
                FROM users 
                WHERE ID_Role = 1
                ORDER BY ID_User ASC
                LIMIT 1
            ");
            $stmt->execute();
            $adminId = $stmt->fetchColumn();
            
            if ($adminId) {
                echo json_encode(['success' => true, 'admin_id' => $adminId]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy quản trị viên']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi: ' . $e->getMessage()]);
    }
}

/**
 * Tìm kiếm cuộc trò chuyện
 */
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

/**
 * Chuyển cuộc trò chuyện
 */
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

/**
 * Đánh dấu tin nhắn đã đọc
 */
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

/**
 * Lấy thống kê chat
 */
function getChatStats($pdo, $userId) {
    try {
        // Get total conversations
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_conversations
            FROM conversations 
            WHERE user1_id = ? OR user2_id = ?
        ");
        $stmt->execute([$userId, $userId]);
        $totalConversations = $stmt->fetchColumn();
        
        // Get unread messages
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_messages
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE (c.user1_id = ? OR c.user2_id = ?) 
            AND m.sender_id != ? AND m.IsRead = 0
        ");
        $stmt->execute([$userId, $userId, $userId]);
        $unreadMessages = $stmt->fetchColumn();
        
        // Get online users
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as online_users
            FROM users 
            WHERE TrangThai = 'Hoạt động' AND ID_Role IN (1, 2, 3, 4)
        ");
        $stmt->execute();
        $onlineUsers = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_conversations' => $totalConversations,
                'unread_messages' => $unreadMessages,
                'online_users' => $onlineUsers
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy thống kê: ' . $e->getMessage()]);
    }
}

/**
 * Lấy danh sách user online
 */
function getOnlineUsers($pdo) {
    // Lấy tất cả users với thông tin chi tiết sử dụng OnlineStatus và LastActivity
    $stmt = $pdo->prepare("
        SELECT u.ID_User,
               COALESCE(nv.HoTen, kh.HoTen, u.Email) as name,
               u.Email,
               u.TrangThai,
               u.OnlineStatus,
               u.LastActivity,
               p.RoleName
        FROM users u
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
        LEFT JOIN phanquyen p ON u.ID_Role = p.ID_Role
        WHERE u.ID_Role IN (1, 2, 3, 4, 5)
        ORDER BY u.OnlineStatus DESC, u.LastActivity DESC, COALESCE(nv.HoTen, kh.HoTen, u.Email) ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tính toán trạng thái online thực tế dựa trên OnlineStatus và LastActivity
    $currentTime = time();
    $onlineThreshold = 300; // 5 phút
    
    foreach ($users as &$user) {
        $isOnline = false;
        
        // Kiểm tra nếu user có TrangThai = 'Hoạt động' và OnlineStatus = 'Online'
        if ($user['TrangThai'] === 'Hoạt động' && $user['OnlineStatus'] === 'Online') {
            // Kiểm tra LastActivity nếu có
            if ($user['LastActivity']) {
                $lastActivity = strtotime($user['LastActivity']);
                $isOnline = ($currentTime - $lastActivity) <= $onlineThreshold;
            } else {
                // Nếu không có LastActivity, coi như online nếu OnlineStatus = 'Online'
                $isOnline = true;
            }
        }
        
        $user['is_online'] = $isOnline ? 1 : 0;
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
}

/**
 * Lấy số lượng user online (đơn giản)
 */
function getOnlineCount($pdo) {
    try {
        // Debug: Log the query
        error_log("getOnlineCount - Executing query");
        
        // Đếm users có OnlineStatus = 'Online' và LastActivity trong 5 phút gần đây
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as online_count
            FROM users 
            WHERE TrangThai = 'Hoạt động' 
            AND OnlineStatus = 'Online'
            AND (LastActivity IS NULL OR LastActivity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE))
            AND ID_Role IN (1, 2, 3, 4, 5)
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Log the result
        error_log("getOnlineCount - Result: " . print_r($result, true));
        
        // Also get detailed info for debugging
        $stmt2 = $pdo->prepare("
            SELECT ID_User, Email, TrangThai, OnlineStatus, LastActivity, ID_Role
            FROM users 
            WHERE TrangThai = 'Hoạt động' 
            AND OnlineStatus = 'Online'
            AND (LastActivity IS NULL OR LastActivity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE))
            AND ID_Role IN (1, 2, 3, 4, 5)
        ");
        $stmt2->execute();
        $onlineUsers = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("getOnlineCount - Online users: " . print_r($onlineUsers, true));
        
        echo json_encode([
            'success' => true, 
            'count' => (int)$result['online_count'],
            'debug' => [
                'online_users' => $onlineUsers,
                'query_time' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("getOnlineCount - Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi đếm online users: ' . $e->getMessage()]);
    }
}

/**
 * Cập nhật trạng thái user
 */
function updateUserStatus($pdo, $userId) {
    $status = $_POST['status'] ?? 'Hoạt động';
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET TrangThai = ? 
            WHERE ID_User = ?
        ");
        $stmt->execute([$status, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi cập nhật trạng thái: ' . $e->getMessage()]);
    }
}

/**
 * Đặt user online
 */
function setUserOnline($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET TrangThai = 'Hoạt động',
                OnlineStatus = 'Online',
                LastActivity = NOW()
            WHERE ID_User = ?
        ");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User đã online']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi đặt user online: ' . $e->getMessage()]);
    }
}

/**
 * Đặt user offline
 */
function setUserOffline($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET OnlineStatus = 'Offline'
            WHERE ID_User = ?
        ");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User đã offline']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi đặt user offline: ' . $e->getMessage()]);
    }
}

/**
 * Cập nhật hoạt động của user
 */
function updateUserActivity($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET LastActivity = NOW(),
                OnlineStatus = 'Online'
            WHERE ID_User = ? AND TrangThai = 'Hoạt động'
        ");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật hoạt động thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi cập nhật hoạt động: ' . $e->getMessage()]);
    }
}

/**
 * Lấy tin nhắn media
 */
function getMediaMessages($pdo, $userId) {
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
    
    // Get media messages
    $stmt = $pdo->prepare("
        SELECT m.id,
               m.conversation_id,
               m.sender_id,
               m.MessageText as message,
               m.message_type,
               m.file_path,
               m.file_name,
               m.file_size,
               m.mime_type,
               m.SentAt as created_at,
               m.IsRead,
               COALESCE(nv.HoTen, kh.HoTen, u.Email) as sender_name,
               cm.thumbnail_path
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.ID_User
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
        LEFT JOIN chat_media cm ON m.id = cm.message_id
        WHERE m.conversation_id = ? 
        AND m.message_type IN ('image', 'file', 'voice_call', 'video_call')
        ORDER BY m.SentAt DESC
        LIMIT 50
    ");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

/**
 * Xóa tin nhắn
 */
function deleteMessage($pdo, $userId) {
    $messageId = $_POST['message_id'] ?? '';
    
    if (empty($messageId)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID tin nhắn']);
        return;
    }
    
    // Check if user has access to this message
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.message_type, m.file_path, cm.file_path as media_path
        FROM messages m
        LEFT JOIN chat_media cm ON m.id = cm.message_id
        WHERE m.id = ? AND m.sender_id = ?
    ");
    $stmt->execute([$messageId, $userId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        echo json_encode(['success' => false, 'error' => 'Không có quyền xóa tin nhắn này']);
        return;
    }
    
    try {
        // Delete file if exists
        if ($message['file_path'] && file_exists($message['file_path'])) {
            unlink($message['file_path']);
        }
        if ($message['media_path'] && file_exists($message['media_path'])) {
            unlink($message['media_path']);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$messageId]);
        
        echo json_encode(['success' => true, 'message' => 'Tin nhắn đã được xóa']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi xóa tin nhắn: ' . $e->getMessage()]);
    }
}

?>