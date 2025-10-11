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

try {
    $pdo = getDBConnection();
    $userId = getCurrentUserId();
    
    // Tìm staff có sẵn (role 1, 2, 3, 4)
    $stmt = $pdo->prepare("
        SELECT u.ID_User, 
               COALESCE(nv.HoTen, u.Email) as HoTen
        FROM users u
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        WHERE u.ID_Role IN (1, 2, 3, 4) 
        AND u.TrangThai = 'Hoạt động'
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt->execute();
    $staff = $stmt->fetch();
    
    if (!$staff) {
        echo json_encode(['success' => false, 'error' => 'Không có nhân viên hỗ trợ trực tuyến']);
        exit;
    }
    
    // Kiểm tra xem đã có cuộc trò chuyện chưa
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
    ");
    $stmt->execute([$userId, $staff['ID_User'], $staff['ID_User'], $userId]);
    $existingConv = $stmt->fetch();
    
    if ($existingConv) {
        echo json_encode(['success' => true, 'conversation_id' => $existingConv['id']]);
        exit;
    }
    
    // Tạo cuộc trò chuyện mới
    $stmt = $pdo->prepare("
        INSERT INTO conversations (user1_id, user2_id, created_at, updated_at) 
        VALUES (?, ?, NOW(), NOW())
    ");
    $stmt->execute([$userId, $staff['ID_User']]);
    
    $conversationId = $pdo->lastInsertId();
    
    // Thêm tin nhắn chào mừng
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, MessageText, IsRead, SentAt) 
        VALUES (?, ?, ?, 0, NOW())
    ");
    $welcomeMessage = "Xin chào! Tôi là " . $staff['HoTen'] . ". Tôi có thể giúp gì cho bạn?";
    $stmt->execute([$conversationId, $staff['ID_User'], $welcomeMessage]);
    
    echo json_encode([
        'success' => true, 
        'conversation_id' => $conversationId,
        'staff_name' => $staff['HoTen']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}
?>
