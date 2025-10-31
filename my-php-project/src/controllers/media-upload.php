<?php
/**
 * Media Upload Handler for Chat
 * Xử lý upload hình ảnh và file cho chat
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../src/auth/auth.php';
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

// Tạo thư mục uploads nếu chưa có
$uploadDir = __DIR__ . '/../uploads/chat/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Tạo thư mục con theo năm/tháng
$yearMonth = date('Y/m');
$fullUploadDir = $uploadDir . $yearMonth . '/';
if (!file_exists($fullUploadDir)) {
    mkdir($fullUploadDir, 0755, true);
}

// Cấu hình upload
$allowedTypes = [
    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain', 'application/zip', 'application/x-rar-compressed'
];

$maxFileSize = 10 * 1024 * 1024; // 10MB
$maxImageSize = 5 * 1024 * 1024; // 5MB for images

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Chỉ hỗ trợ POST request']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Không có file được upload hoặc có lỗi']);
    exit;
}

$file = $_FILES['file'];
$conversationId = $_POST['conversation_id'] ?? '';

if (empty($conversationId)) {
    echo json_encode(['success' => false, 'error' => 'Thiếu ID cuộc trò chuyện']);
    exit;
}

// Kiểm tra quyền truy cập conversation
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập cuộc trò chuyện này']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi kiểm tra quyền: ' . $e->getMessage()]);
    exit;
}

// Validate file
$fileInfo = pathinfo($file['name']);
$extension = strtolower($fileInfo['extension']);
$mimeType = mime_content_type($file['tmp_name']);

// Kiểm tra loại file
if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Loại file không được hỗ trợ']);
    exit;
}

// Kiểm tra kích thước file
if ($file['size'] > $maxFileSize) {
    echo json_encode(['success' => false, 'error' => 'File quá lớn. Tối đa 10MB']);
    exit;
}

// Kiểm tra kích thước hình ảnh
if (strpos($mimeType, 'image/') === 0 && $file['size'] > $maxImageSize) {
    echo json_encode(['success' => false, 'error' => 'Hình ảnh quá lớn. Tối đa 5MB']);
    exit;
}

// Tạo tên file unique
$fileName = uniqid() . '_' . time() . '.' . $extension;
$filePath = $fullUploadDir . $fileName;

// Upload file
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(['success' => false, 'error' => 'Không thể upload file']);
    exit;
}

// Tạo thumbnail cho hình ảnh
$thumbnailPath = null;
if (strpos($mimeType, 'image/') === 0) {
    $thumbnailPath = createThumbnail($filePath, $fullUploadDir, $fileName);
}

// Lưu vào database
try {
    $pdo = getDBConnection();
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, MessageText, message_type, file_path, file_name, file_size, mime_type, IsRead, SentAt) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    
    $messageText = '';
    if (strpos($mimeType, 'image/') === 0) {
        $messageText = '[Hình ảnh]';
        $messageType = 'image';
    } else {
        $messageText = '[File: ' . $file['name'] . ']';
        $messageType = 'file';
    }
    
    $stmt->execute([
        $conversationId, 
        $userId, 
        $messageText, 
        $messageType,
        $filePath,
        $file['name'],
        $file['size'],
        $mimeType
    ]);
    
    $messageId = $pdo->lastInsertId();
    
    // Insert media record
    $stmt = $pdo->prepare("
        INSERT INTO chat_media (message_id, file_path, file_name, file_size, mime_type, thumbnail_path) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $messageId,
        $filePath,
        $file['name'],
        $file['size'],
        $mimeType,
        $thumbnailPath
    ]);
    
    // Update conversation timestamp
    $stmt = $pdo->prepare("
        UPDATE conversations 
        SET updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$conversationId]);
    
    // Get sender name
    $stmt = $pdo->prepare("
        SELECT COALESCE(nv.HoTen, kh.HoTen, u.Email) as sender_name
        FROM users u
        LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
        LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
        WHERE u.ID_User = ?
    ");
    $stmt->execute([$userId]);
    $senderName = $stmt->fetchColumn();
    
    // Return response
    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'sender_id' => $userId,
            'message' => $messageText,
            'message_type' => $messageType,
            'file_path' => $filePath,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'mime_type' => $mimeType,
            'thumbnail_path' => $thumbnailPath,
            'created_at' => date('Y-m-d H:i:s'),
            'IsRead' => 0,
            'sender_name' => $senderName
        ]
    ]);
    
} catch (Exception $e) {
    // Xóa file nếu có lỗi database
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    if ($thumbnailPath && file_exists($thumbnailPath)) {
        unlink($thumbnailPath);
    }
    
    echo json_encode(['success' => false, 'error' => 'Lỗi lưu database: ' . $e->getMessage()]);
}

/**
 * Tạo thumbnail cho hình ảnh
 */
function createThumbnail($originalPath, $uploadDir, $fileName) {
    try {
        $imageInfo = getimagesize($originalPath);
        if (!$imageInfo) return null;
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Tạo thumbnail chỉ khi hình ảnh lớn hơn 300x300
        if ($width <= 300 && $height <= 300) {
            return null;
        }
        
        // Tính toán kích thước thumbnail
        $thumbSize = 300;
        $ratio = min($thumbSize / $width, $thumbSize / $height);
        $thumbWidth = intval($width * $ratio);
        $thumbHeight = intval($height * $ratio);
        
        // Tạo thumbnail
        $thumbFileName = 'thumb_' . $fileName;
        $thumbPath = $uploadDir . $thumbFileName;
        
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($originalPath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($originalPath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($originalPath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($originalPath);
                break;
            default:
                return null;
        }
        
        if (!$source) return null;
        
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
        }
        
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
        
        // Save thumbnail
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($thumbnail, $thumbPath, 85);
                break;
            case 'image/png':
                imagepng($thumbnail, $thumbPath, 8);
                break;
            case 'image/gif':
                imagegif($thumbnail, $thumbPath);
                break;
            case 'image/webp':
                imagewebp($thumbnail, $thumbPath, 85);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return $thumbPath;
        
    } catch (Exception $e) {
        error_log('Thumbnail creation error: ' . $e->getMessage());
        return null;
    }
}
?>
