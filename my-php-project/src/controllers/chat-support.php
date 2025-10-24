<?php
/**
 * Chat Support Controller - Xử lý chat hỗ trợ cho admin/staff
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit();
}

$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (!in_array($userRole, [1, 2, 3, 4])) { // Admin, Quản lý tổ chức, Quản lý sự kiện, Nhân viên
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'get_customers':
            getCustomers($pdo);
            break;
            
        case 'get_chat_history':
            getChatHistory($pdo);
            break;
            
        case 'send_message':
            sendMessage($pdo);
            break;
            
        case 'get_online_customers':
            getOnlineCustomers($pdo);
            break;
            
        case 'transfer_chat':
            transferChat($pdo);
            break;
            
        case 'end_chat':
            endChat($pdo);
            break;
            
        case 'get_quick_replies':
            getQuickReplies($pdo);
            break;
            
        case 'search_customers':
            searchCustomers($pdo);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Chat support error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

/**
 * Lấy danh sách khách hàng có cuộc trò chuyện
 */
function getCustomers($pdo) {
    try {
        // Get customers who have initiated chat
        $stmt = $pdo->query("
            SELECT DISTINCT 
                u.ID_User,
                u.Email,
                COALESCE(kh.HoTen, u.Email) as HoTen,
                u.TrangThai,
                u.NgayTao,
                u.NgayCapNhat,
                COUNT(DISTINCT c.id) as conversation_count,
                MAX(m.SentAt) as last_message_time,
                (SELECT MessageText FROM messages m2 
                 WHERE m2.conversation_id = c.id 
                 ORDER BY m2.SentAt DESC LIMIT 1) as last_message,
                (SELECT COUNT(*) FROM messages m3 
                 WHERE m3.conversation_id = c.id 
                 AND m3.sender_id != u.ID_User 
                 AND m3.IsRead = 0) as unread_count
            FROM users u
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            LEFT JOIN conversations c ON (u.ID_User = c.user1_id OR u.ID_User = c.user2_id)
            LEFT JOIN messages m ON c.id = m.conversation_id
            WHERE u.ID_Role = 5
            GROUP BY u.ID_User, u.Email, kh.HoTen, u.TrangThai, u.NgayTao, u.NgayCapNhat
            ORDER BY last_message_time DESC, u.NgayTao DESC
        ");
        
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format customer data
        $formattedCustomers = [];
        foreach ($customers as $customer) {
            $formattedCustomers[] = [
                'id' => $customer['ID_User'],
                'name' => $customer['HoTen'] ?: 'Khách hàng',
                'email' => $customer['Email'],
                'status' => getCustomerStatus($customer['TrangThai']),
                'lastMessage' => $customer['last_message'] ?: 'Chưa có tin nhắn',
                'lastMessageTime' => $customer['last_message_time'] ? 
                    date('H:i', strtotime($customer['last_message_time'])) : 
                    date('H:i', strtotime($customer['NgayTao'])),
                'unreadCount' => (int)$customer['unread_count'],
                'conversationCount' => (int)$customer['conversation_count']
            ];
        }
        
        echo json_encode(['success' => true, 'customers' => $formattedCustomers]);
        
    } catch (Exception $e) {
        error_log("Get customers error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Không thể tải danh sách khách hàng']);
    }

}

/**
 * Lấy lịch sử chat với khách hàng
 */
function getChatHistory($pdo) {
    $customerId = $_GET['customer_id'] ?? $_POST['customer_id'] ?? null;
    
    if (!$customerId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID khách hàng']);
        return;
    }
    
    try {
        // Get conversation between support and customer
        $stmt = $pdo->prepare("
            SELECT 
                m.id,
                m.MessageText,
                m.SentAt,
                m.sender_id,
                m.IsRead,
                u.Email as sender_email,
                COALESCE(nv.HoTen, kh.HoTen, u.Email) as sender_name
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            JOIN users u ON m.sender_id = u.ID_User
            LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            WHERE (c.user1_id = ? OR c.user2_id = ?)
            ORDER BY m.SentAt ASC
        ");
        
        $stmt->execute([$customerId, $customerId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format messages
        $formattedMessages = [];
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'id' => $message['id'],
                'text' => $message['MessageText'],
                'time' => date('H:i', strtotime($message['SentAt'])),
                'sender' => $message['sender_id'] == $customerId ? 'customer' : 'support',
                'senderName' => $message['sender_name'] ?: $message['sender_email'],
                'isRead' => (bool)$message['IsRead']
            ];
        }
        
        echo json_encode(['success' => true, 'messages' => $formattedMessages]);
        
    } catch (Exception $e) {
        error_log("Get chat history error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Không thể tải lịch sử chat']);
    }
}

/**
 * Gửi tin nhắn cho khách hàng
 */
function sendMessage($pdo) {
    $customerId = $_POST['customer_id'] ?? null;
    $messageText = $_POST['message'] ?? null;
    $supportId = $_SESSION['user']['ID_User'];
    
    if (!$customerId || !$messageText) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin tin nhắn']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get or create conversation
        $conversationId = getOrCreateConversation($pdo, $customerId, $supportId);
        
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO messages (conversation_id, sender_id, MessageText, IsRead, SentAt)
            VALUES (?, ?, ?, 0, NOW())
        ");
        
        $stmt->execute([$conversationId, $supportId, $messageText]);
        $messageId = $pdo->lastInsertId();
        
        // Update conversation last message
        $stmt = $pdo->prepare("
            UPDATE conversations 
            SET LastMessage_ID = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$messageId, $conversationId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => [
                'id' => $messageId,
                'text' => $messageText,
                'time' => date('H:i'),
                'sender' => 'support'
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Send message error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Không thể gửi tin nhắn']);
    }
}

/**
 * Lấy hoặc tạo cuộc trò chuyện
 */
function getOrCreateConversation($pdo, $customerId, $supportId) {
    // Check if conversation exists
    $stmt = $pdo->prepare("
        SELECT id 
        FROM conversations 
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
    ");
    $stmt->execute([$customerId, $supportId, $supportId, $customerId]);
    $conversation = $stmt->fetch();
    
    if ($conversation) {
        return $conversation['id'];
    }
    
    // Create new conversation
    $stmt = $pdo->prepare("
        INSERT INTO conversations (user1_id, user2_id, created_at, updated_at)
        VALUES (?, ?, NOW(), NOW())
    ");
    $stmt->execute([$customerId, $supportId]);
    
    return $pdo->lastInsertId();
}

/**
 * Lấy số khách hàng trực tuyến
 */
function getOnlineCustomers($pdo) {
    try {
        // Get customers who are online (recently active)
        $stmt = $pdo->query("
            SELECT 
                u.ID_User,
                u.Email,
                kh.HoTen,
                u.TrangThai,
                MAX(m.SentAt) as last_activity
            FROM users u
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            LEFT JOIN conversations c ON (u.ID_User = c.user1_id OR u.ID_User = c.user2_id)
            LEFT JOIN messages m ON c.id = m.conversation_id
            WHERE u.ID_Role = 5 
            AND u.TrangThai = 'Hoạt động'
            AND (m.SentAt > DATE_SUB(NOW(), INTERVAL 30 MINUTE) OR m.SentAt IS NULL)
            GROUP BY u.ID_User, u.Email, kh.HoTen, u.TrangThai
            ORDER BY last_activity DESC
        ");
        
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'count' => count($customers)]);
        
    } catch (Exception $e) {
        error_log("Get online customers error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Không thể tải số khách hàng trực tuyến']);
    }
}

/**
 * Chuyển cuộc trò chuyện cho nhân viên khác
 */
function transferChat($pdo) {
    $customerId = $_POST['customer_id'] ?? null;
    $transferTo = $_POST['transfer_to'] ?? null;
    $note = $_POST['note'] ?? '';
    $currentSupportId = $_SESSION['user']['ID_User'];
    
    if (!$customerId || !$transferTo) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin chuyển cuộc trò chuyện']);
        return;
    }
    
    try {
        // Update conversation to new support person
        $stmt = $pdo->prepare("
            UPDATE conversations 
            SET user2_id = ?, updated_at = NOW()
            WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
        ");
        $stmt->execute([$transferTo, $customerId, $currentSupportId, $currentSupportId, $customerId]);
        
        // Add transfer note as message
        $conversationId = getOrCreateConversation($pdo, $customerId, $transferTo);
        $stmt = $pdo->prepare("
            INSERT INTO messages (conversation_id, sender_id, MessageText, IsRead, SentAt)
            VALUES (?, ?, ?, 0, NOW())
        ");
        $transferMessage = "Cuộc trò chuyện đã được chuyển" . ($note ? ": " . $note : "");
        $stmt->execute([$conversationId, $currentSupportId, $transferMessage]);
        
        echo json_encode(['success' => true, 'message' => 'Đã chuyển cuộc trò chuyện thành công']);
        
    } catch (Exception $e) {
        error_log("Transfer chat error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Không thể chuyển cuộc trò chuyện']);
    }
}

/**
 * Kết thúc cuộc trò chuyện
 */
function endChat($pdo) {
    $customerId = $_POST['customer_id'] ?? null;
    $supportId = $_SESSION['user']['ID_User'];
    
    if (!$customerId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID khách hàng']);
        return;
    }
    
    try {
        // Add end chat message
        $conversationId = getOrCreateConversation($pdo, $customerId, $supportId);
        $stmt = $pdo->prepare("
            INSERT INTO messages (conversation_id, sender_id, MessageText, IsRead, SentAt)
            VALUES (?, ?, ?, 0, NOW())
        ");
        $endMessage = "Cuộc trò chuyện đã kết thúc. Cảm ơn bạn đã liên hệ!";
        $stmt->execute([$conversationId, $supportId, $endMessage]);
        
        echo json_encode(['success' => true, 'message' => 'Đã kết thúc cuộc trò chuyện']);
        
    } catch (Exception $e) {
        error_log("End chat error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Không thể kết thúc cuộc trò chuyện']);
    }
}

/**
 * Lấy danh sách tin nhắn nhanh
 */
function getQuickReplies($pdo) {
    $quickReplies = [
        [
            'id' => 'greeting',
            'title' => 'Chào hỏi',
            'text' => 'Xin chào! Tôi có thể giúp gì cho bạn?'
        ],
        [
            'id' => 'thanks',
            'title' => 'Cảm ơn',
            'text' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ hỗ trợ bạn ngay.'
        ],
        [
            'id' => 'wait',
            'title' => 'Chờ đợi',
            'text' => 'Vui lòng chờ một chút, tôi đang kiểm tra thông tin cho bạn.'
        ],
        [
            'id' => 'end',
            'title' => 'Kết thúc',
            'text' => 'Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi. Chúc bạn một ngày tốt lành!'
        ],
        [
            'id' => 'event_info',
            'title' => 'Thông tin sự kiện',
            'text' => 'Bạn có thể tìm hiểu thêm về các dịch vụ sự kiện của chúng tôi tại trang chủ.'
        ]
    ];
    
    echo json_encode(['success' => true, 'quickReplies' => $quickReplies]);
}

/**
 * Tìm kiếm khách hàng
 */
function searchCustomers($pdo) {
    $query = $_GET['query'] ?? $_POST['query'] ?? '';
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu từ khóa tìm kiếm']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.ID_User,
                u.Email,
                kh.HoTen,
                u.TrangThai,
                u.NgayTao
            FROM users u
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            WHERE u.ID_Role = 5 
            AND (u.Email LIKE ? OR kh.HoTen LIKE ?)
            ORDER BY u.NgayTao DESC
            LIMIT 20
        ");
        
        $searchTerm = "%{$query}%";
        $stmt->execute([$searchTerm, $searchTerm]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format customer data
        $formattedCustomers = [];
        foreach ($customers as $customer) {
            $formattedCustomers[] = [
                'id' => $customer['ID_User'],
                'name' => $customer['HoTen'] ?: 'Khách hàng',
                'email' => $customer['Email'],
                'status' => getCustomerStatus($customer['TrangThai'])
            ];
        }
        
        echo json_encode(['success' => true, 'customers' => $formattedCustomers]);
        
    } catch (Exception $e) {
        error_log("Search customers error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Không thể tìm kiếm khách hàng']);
    }
}

/**
 * Lấy trạng thái khách hàng
 */
function getCustomerStatus($status) {
    switch ($status) {
        case 'Hoạt động':
            return 'online';
        case 'Chưa xác minh':
            return 'away';
        case 'Bị khóa':
            return 'offline';
        default:
            return 'offline';
    }
}
?>