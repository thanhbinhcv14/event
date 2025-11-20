<?php
/**
 * Media Upload Handler for Chat
 * Xử lý upload hình ảnh và file cho chat
 */

// Bật error logging để debug (nhưng không hiển thị cho người dùng)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Thiết lập error handler để bắt tất cả lỗi
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Media upload PHP error [$errno]: $errstr in $errfile on line $errline");
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => "PHP Error: $errstr (Line $errline)"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}, E_ALL);

// Thiết lập exception handler
set_exception_handler(function($exception) {
    error_log("Media upload uncaught exception: " . $exception->getMessage());
    error_log("Stack trace: " . $exception->getTraceAsString());
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Uncaught exception: ' . $exception->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

// Thiết lập JSON header trước
header('Content-Type: application/json; charset=utf-8');

// Bắt đầu output buffering để bắt bất kỳ output không mong muốn nào
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sử dụng cùng pattern đường dẫn như các controller khác
try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../auth/auth.php';
} catch (Exception $e) {
    ob_clean();
    error_log('Media upload require error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi tải file: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    ob_clean();
    error_log('Media upload fatal error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi fatal: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Bọc tất cả trong try-catch để bắt tất cả lỗi
try {
    if (!isLoggedIn()) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userId = getCurrentUserId();
    if (!$userId || $userId == 0) {
        ob_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Không thể lấy thông tin người dùng'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    ob_clean();
    error_log('Media upload auth error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi xác thực: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    ob_clean();
    error_log('Media upload auth fatal error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi fatal xác thực: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tạo thư mục uploads nếu chưa có
// Từ src/controllers/ -> uploads/chat/ (lên 1 cấp đến src/, sau đó lên 1 cấp đến my-php-project/)
$uploadDir = dirname(dirname(__DIR__)) . '/uploads/chat/';
if (!file_exists($uploadDir)) {
    // Tạo cấu trúc thư mục nếu chưa tồn tại
    $parentDir = dirname($uploadDir);
    if (!file_exists($parentDir)) {
        mkdir($parentDir, 0755, true);
    }
    mkdir($uploadDir, 0755, true);
}

// Lấy conversation_id trước khi tạo thư mục
$conversationId = $_POST['conversation_id'] ?? '';

if (empty($conversationId)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Thiếu ID cuộc trò chuyện'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tạo thư mục theo conversation_id và năm/tháng
// Cấu trúc: uploads/chat/{conversation_id}/Y/m/
$conversationDir = $uploadDir . $conversationId . '/';
if (!file_exists($conversationDir)) {
    mkdir($conversationDir, 0755, true);
}

$yearMonth = date('Y/m');
$fullUploadDir = $conversationDir . $yearMonth . '/';
if (!file_exists($fullUploadDir)) {
    mkdir($fullUploadDir, 0755, true);
}

// Cấu hình upload
$allowedTypes = [
    // Hình ảnh
    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
    // Video
    'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm', 'video/x-matroska',
    // Tài liệu
    'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain', 'application/zip', 'application/x-rar-compressed'
];

$maxFileSize = 10 * 1024 * 1024; // 10MB cho tài liệu
$maxImageSize = 10 * 1024 * 1024; // 10MB cho hình ảnh
$maxVideoSize = 50 * 1024 * 1024; // 50MB cho video

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Chỉ hỗ trợ POST request'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    ob_clean();
    $errorMsg = 'Không có file được upload hoặc có lỗi';
    if (isset($_FILES['file']['error'])) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File vượt quá kích thước tối đa của PHP',
            UPLOAD_ERR_FORM_SIZE => 'File vượt quá kích thước tối đa của form',
            UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
            UPLOAD_ERR_NO_FILE => 'Không có file được upload',
            UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file',
            UPLOAD_ERR_EXTENSION => 'Upload bị chặn bởi extension'
        ];
        $errorMsg = $uploadErrors[$_FILES['file']['error']] ?? $errorMsg;
    }
    echo json_encode(['success' => false, 'error' => $errorMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];
// conversationId đã được lấy ở trên

// Kiểm tra quyền truy cập conversation
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    
    if (!$stmt->fetch()) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập cuộc trò chuyện này'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    ob_clean();
    error_log('Conversation access check error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Lỗi kiểm tra quyền: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Xác thực file
$fileInfo = pathinfo($file['name']);
$extension = isset($fileInfo['extension']) ? strtolower($fileInfo['extension']) : '';

// Lấy MIME type an toàn
$mimeType = '';
if (function_exists('mime_content_type')) {
    $mimeType = mime_content_type($file['tmp_name']);
} elseif (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} else {
    // Fallback về loại file đã upload
    $mimeType = $file['type'] ?? '';
}

// Nếu vẫn rỗng, thử xác định từ extension
if (empty($mimeType) && !empty($extension)) {
    $mimeTypes = [
        // Hình ảnh
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        // Video
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska',
        // Tài liệu
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed'
    ];
    $mimeType = $mimeTypes[$extension] ?? '';
}

// Xác thực extension
if (empty($extension)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'File không có phần mở rộng']);
    exit;
}

// Kiểm tra loại file
if (empty($mimeType) || !in_array($mimeType, $allowedTypes)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Loại file không được hỗ trợ. MIME type: ' . $mimeType], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra kích thước file dựa trên loại
$isVideo = strpos($mimeType, 'video/') === 0;
$isImage = strpos($mimeType, 'image/') === 0;

if ($isVideo && $file['size'] > $maxVideoSize) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Video quá lớn. Tối đa 50MB'], JSON_UNESCAPED_UNICODE);
    exit;
} elseif ($isImage && $file['size'] > $maxImageSize) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Hình ảnh quá lớn. Tối đa 10MB'], JSON_UNESCAPED_UNICODE);
    exit;
} elseif (!$isVideo && !$isImage && $file['size'] > $maxFileSize) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'File quá lớn. Tối đa 10MB'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tạo tên file unique
$fileName = uniqid() . '_' . time() . '.' . $extension;
$filePath = $fullUploadDir . $fileName;

// Upload file
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    ob_clean();
    $errorMsg = 'Không thể upload file';
    if (!is_writable(dirname($filePath))) {
        $errorMsg = 'Thư mục upload không có quyền ghi';
    } elseif (!is_dir(dirname($filePath))) {
        $errorMsg = 'Thư mục upload không tồn tại';
    }
    echo json_encode(['success' => false, 'error' => $errorMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tạo đường dẫn tương đối để lưu vào database
// Từ: my-php-project/src/controllers/uploads/chat/{conversation_id}/Y/m/file.jpg
// Thành: uploads/chat/{conversation_id}/Y/m/file.jpg
$relativeFilePath = 'uploads/chat/' . $conversationId . '/' . $yearMonth . '/' . $fileName;

// Tạo thumbnail cho hình ảnh (sử dụng absolute path để tạo thumbnail)
$thumbnailPath = null;
$relativeThumbnailPath = null;
if (strpos($mimeType, 'image/') === 0) {
    try {
        // Kiểm tra GD extension có sẵn không
        if (!function_exists('imagecreatefromjpeg')) {
            error_log('GD extension not available, skipping thumbnail creation');
        } else {
            $thumbnailPath = createThumbnail($filePath, $fullUploadDir, $fileName);
            if ($thumbnailPath) {
                // Tạo relative path cho thumbnail
                $thumbnailFileName = 'thumb_' . $fileName;
                $relativeThumbnailPath = 'uploads/chat/' . $conversationId . '/' . $yearMonth . '/' . $thumbnailFileName;
            }
        }
    } catch (Exception $e) {
        error_log('Thumbnail creation error (non-fatal): ' . $e->getMessage());
        // Tiếp tục không có thumbnail
        $thumbnailPath = null;
        $relativeThumbnailPath = null;
    } catch (Error $e) {
        error_log('Thumbnail creation fatal error (non-fatal): ' . $e->getMessage());
        // Tiếp tục không có thumbnail
        $thumbnailPath = null;
        $relativeThumbnailPath = null;
    }
}

// Lưu vào database
try {
    error_log('Media upload: Starting database operations');
    $pdo = getDBConnection();
    error_log('Media upload: Database connection successful');
    
    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, MessageText, message_type, file_path, file_name, file_size, mime_type, IsRead, SentAt) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    
    $messageText = '';
    if (strpos($mimeType, 'image/') === 0) {
        $messageText = '[Hình ảnh]';
        $messageType = 'image';
    } elseif (strpos($mimeType, 'video/') === 0) {
        $messageText = '[Video]';
        $messageType = 'video';
    } else {
        $messageText = '[File: ' . $file['name'] . ']';
        $messageType = 'file';
    }
    
    $stmt->execute([
        $conversationId, 
        $userId, 
        $messageText, 
        $messageType,
        $relativeFilePath, // Lưu đường dẫn tương đối
        $file['name'],
        $file['size'],
        $mimeType
    ]);
    
    $messageId = $pdo->lastInsertId();
    
    // Insert media record (relativeThumbnailPath đã được tạo ở trên)
    $stmt = $pdo->prepare("
        INSERT INTO chat_media (message_id, file_path, file_name, file_size, mime_type, thumbnail_path) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $messageId,
        $relativeFilePath, // Lưu đường dẫn tương đối
        $file['name'],
        $file['size'],
        $mimeType,
        $relativeThumbnailPath // Lưu đường dẫn tương đối
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
    
    // Clear any output buffer before sending JSON
    ob_clean();
    
    // Return response với đường dẫn tương đối
    $response = [
        'success' => true,
        'message' => [
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'sender_id' => $userId,
            'message' => $messageText,
            'message_type' => $messageType,
            'file_path' => $relativeFilePath, // Trả về đường dẫn tương đối
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'mime_type' => $mimeType,
            'thumbnail_path' => $relativeThumbnailPath, // Trả về đường dẫn tương đối
            'created_at' => date('Y-m-d H:i:s'),
            'IsRead' => 0,
            'sender_name' => $senderName
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
    
} catch (Exception $e) {
    // Clear output buffer
    ob_clean();
    
    // Xóa file nếu có lỗi database
    if (isset($filePath) && file_exists($filePath)) {
        @unlink($filePath);
    }
    if (isset($thumbnailPath) && $thumbnailPath && file_exists($thumbnailPath)) {
        @unlink($thumbnailPath);
    }
    
    // Log error for debugging
    error_log('Media upload error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    error_log('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Lỗi lưu database: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    // Clear output buffer
    ob_clean();
    
    // Xóa file nếu có lỗi
    if (isset($filePath) && file_exists($filePath)) {
        @unlink($filePath);
    }
    if (isset($thumbnailPath) && $thumbnailPath && file_exists($thumbnailPath)) {
        @unlink($thumbnailPath);
    }
    
    // Log fatal error
    error_log('Media upload fatal error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    error_log('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Lỗi fatal: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
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
