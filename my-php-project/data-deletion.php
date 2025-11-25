<?php
/**
 * Facebook Data Deletion Callback
 * 
 * Trang này xử lý yêu cầu xóa dữ liệu từ Facebook
 * Facebook sẽ gửi POST request với signed_request để xác thực
 */

require_once __DIR__ . '/config/database.php';

// Hàm xác thực signed_request từ Facebook
function parseSignedRequest($signed_request, $secret) {
    list($encoded_sig, $payload) = explode('.', $signed_request, 2);
    
    // Decode data
    $sig = base64_url_decode($encoded_sig);
    $data = json_decode(base64_url_decode($payload), true);
    
    // Verify signature
    $expected_sig = hash_hmac('sha256', $payload, $secret, true);
    
    if ($sig !== $expected_sig) {
        return null;
    }
    
    return $data;
}

function base64_url_decode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
}

// Xử lý request
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $signed_request = $_POST['signed_request'] ?? '';
    
    if (empty($signed_request)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Missing signed_request parameter'
        ]);
        exit;
    }
    
    // Lấy App Secret từ config
    $app_secret = '6fa5e91f4728125a3ae618dfc86b725a'; // Thay bằng App Secret của bạn
    
    // Parse signed_request
    $data = parseSignedRequest($signed_request, $app_secret);
    
    if (!$data || !isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid signed_request'
        ]);
        exit;
    }
    
    $facebook_user_id = $data['user_id'];
    
    try {
        $pdo = getDBConnection();
        
        // Tìm user trong database theo FacebookID
        $stmt = $pdo->prepare("SELECT ID_User FROM users WHERE FacebookID = ?");
        $stmt->execute([$facebook_user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $userId = $user['ID_User'];
            
            // Bắt đầu transaction
            $pdo->beginTransaction();
            
            try {
                // Xóa dữ liệu từ các bảng liên quan
                // 1. Xóa thông tin khách hàng (nếu có)
                $stmt = $pdo->prepare("DELETE FROM khachhanginfo WHERE ID_User = ?");
                $stmt->execute([$userId]);
                
                // 2. Xóa thông tin nhân viên (nếu có)
                $stmt = $pdo->prepare("DELETE FROM nhanvieninfo WHERE ID_User = ?");
                $stmt->execute([$userId]);
                
                // 3. Xóa FacebookID từ users (giữ lại account nếu user muốn)
                // Hoặc xóa hoàn toàn user nếu chỉ đăng nhập bằng Facebook
                $stmt = $pdo->prepare("SELECT GoogleID, Email, Password FROM users WHERE ID_User = ?");
                $stmt->execute([$userId]);
                $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userInfo) {
                    // Nếu user chỉ có FacebookID và không có password/GoogleID, xóa hoàn toàn
                    if (empty($userInfo['GoogleID']) && empty($userInfo['Password'])) {
                        // Xóa user hoàn toàn
                        $stmt = $pdo->prepare("DELETE FROM users WHERE ID_User = ?");
                        $stmt->execute([$userId]);
                    } else {
                        // Chỉ xóa FacebookID, giữ lại account
                        $stmt = $pdo->prepare("UPDATE users SET FacebookID = NULL WHERE ID_User = ?");
                        $stmt->execute([$userId]);
                    }
                }
                
                // 4. Xóa dữ liệu chat (nếu có)
                $stmt = $pdo->prepare("DELETE FROM messages WHERE UserID = ?");
                $stmt->execute([$userId]);
                
                // 5. Xóa conversations (nếu có)
                $stmt = $pdo->prepare("DELETE FROM conversations WHERE UserID = ?");
                $stmt->execute([$userId]);
                
                $pdo->commit();
                
                // Trả về confirmation code cho Facebook
                $confirmation_code = uniqid('DEL_', true);
                
                echo json_encode([
                    'url' => 'https://sukien.info.vn/data-deletion-status.php?code=' . $confirmation_code,
                    'confirmation_code' => $confirmation_code
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            
        } else {
            // User không tồn tại trong database
            echo json_encode([
                'url' => 'https://sukien.info.vn/data-deletion-status.php',
                'confirmation_code' => null
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Data deletion error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => 'An error occurred while processing your request'
        ]);
    }
    
} else {
    // GET request - Hiển thị trang thông tin
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Xóa dữ liệu người dùng - Event Management System</title>
        <link rel="icon" href="img/logo/logo.jpg">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: #f5f5f5;
                padding: 40px 0;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .container {
                max-width: 800px;
            }
            .card {
                border: none;
                border-radius: 15px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            }
            .card-header {
                background: linear-gradient(45deg, #667eea, #764ba2);
                color: white;
                border-radius: 15px 15px 0 0 !important;
                padding: 20px;
            }
            .card-body {
                padding: 30px;
            }
            .btn-primary {
                background: linear-gradient(45deg, #667eea, #764ba2);
                border: none;
                border-radius: 20px;
                padding: 10px 30px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-trash-alt me-2"></i>Xóa dữ liệu người dùng</h3>
                </div>
                <div class="card-body">
                    <h5>Yêu cầu xóa dữ liệu từ Facebook</h5>
                    <p>
                        Nếu bạn muốn xóa dữ liệu của mình khỏi hệ thống, vui lòng thực hiện yêu cầu xóa dữ liệu 
                        từ trang cài đặt tài khoản Facebook của bạn.
                    </p>
                    <p>
                        <strong>Các dữ liệu sẽ bị xóa:</strong>
                    </p>
                    <ul>
                        <li>Thông tin tài khoản liên kết với Facebook</li>
                        <li>Thông tin cá nhân (nếu chỉ đăng nhập bằng Facebook)</li>
                        <li>Lịch sử chat và tin nhắn</li>
                        <li>Dữ liệu liên quan đến tài khoản Facebook</li>
                    </ul>
                    <p class="text-muted">
                        <small>
                            <strong>Lưu ý:</strong> Nếu tài khoản của bạn cũng được liên kết với email hoặc Google, 
                            tài khoản sẽ được giữ lại nhưng thông tin Facebook sẽ bị xóa.
                        </small>
                    </p>
                    <div class="mt-4">
                        <a href="privacy-policy.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại Chính sách quyền riêng tư
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}
?>

