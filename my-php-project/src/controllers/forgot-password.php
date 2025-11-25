<?php
/**
 * Forgot Password Controller
 * Xử lý quên mật khẩu - tạo mật khẩu mới và gửi email
 */

// Đảm bảo output buffer được clear
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Require database và autoload
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Import PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Lấy dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

// Validate input
if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Vui lòng nhập email']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email không hợp lệ']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Kiểm tra email có tồn tại trong hệ thống không
    $stmt = $pdo->prepare("SELECT ID_User, Email FROM users WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Tài khoản chưa có trong hệ thống']);
        exit;
    }
    
    // Tạo mật khẩu mới ngẫu nhiên (8-12 ký tự, bao gồm chữ hoa, chữ thường, số)
    function generateRandomPassword($length = 10) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $all = $uppercase . $lowercase . $numbers;
        
        // Đảm bảo có ít nhất 1 chữ hoa, 1 chữ thường, 1 số
        $password = $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        
        // Thêm các ký tự ngẫu nhiên còn lại
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $all[rand(0, strlen($all) - 1)];
        }
        
        // Xáo trộn các ký tự
        return str_shuffle($password);
    }
    
    $newPassword = generateRandomPassword(10);
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Cập nhật mật khẩu mới vào database
    $stmt = $pdo->prepare("UPDATE users SET Password = ? WHERE Email = ?");
    $stmt->execute([$hashedPassword, $email]);
    
    // Gửi email chứa mật khẩu mới
    $to = $email;
    $subject = 'Mật khẩu mới - Hệ thống sự kiện';
    $message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .password-box { background: #fff; border: 2px solid #667eea; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center; }
            .password { font-size: 24px; font-weight: bold; color: #667eea; letter-spacing: 2px; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🔐 Mật khẩu mới</h2>
            </div>
            <div class='content'>
                <p>Xin chào,</p>
                <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản của mình.</p>
                
                <div class='password-box'>
                    <p style='margin: 0; color: #666;'>Mật khẩu mới của bạn:</p>
                    <div class='password'>{$newPassword}</div>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Lưu ý:</strong> Vui lòng đăng nhập và đổi mật khẩu này ngay sau khi nhận được email để đảm bảo an toàn tài khoản.
                </div>
                
                <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này hoặc liên hệ với chúng tôi.</p>
                
                <p>Trân trọng,<br><strong>Hệ thống sự kiện</strong></p>
            </div>
            <div class='footer'>
                <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                <p>&copy; " . date('Y') . " Hệ thống sự kiện. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Gửi email bằng PHPMailer với SMTP Gmail
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Tắt debug output để không làm hỏng JSON response
        // Debug info sẽ được log qua exception handling
        $mail->SMTPDebug = 0; // 0 = off (tắt để không output ra stdout)
        
        // Nếu cần debug, uncomment dòng sau và xem error_log
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = function($str, $level) {
        //     error_log("PHPMailer Debug (Level $level): " . trim($str));
        // };
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to); // Email người nhận
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message); // Plain text version
        
        // Gửi email
        $mail->send();
        
        // Đảm bảo không có output nào trước JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => 'Mật khẩu mới đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.'
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // Log lỗi chi tiết
        $errorInfo = isset($mail) ? $mail->ErrorInfo : 'PHPMailer not initialized';
        $exceptionMsg = $e->getMessage();
        
        error_log("PHPMailer Error: " . $errorInfo);
        error_log("Exception: " . $exceptionMsg);
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Trả về lỗi chi tiết hơn trong development (localhost)
        $isDevelopment = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);
        
        // Đảm bảo không có output nào trước JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        
        $errorMessage = $isDevelopment 
            ? 'Không thể gửi email: ' . htmlspecialchars($errorInfo, ENT_QUOTES, 'UTF-8') . ' | ' . htmlspecialchars($exceptionMsg, ENT_QUOTES, 'UTF-8')
            : 'Không thể gửi email. Vui lòng thử lại sau hoặc liên hệ hỗ trợ.';
        
        echo json_encode([
            'success' => false,
            'error' => $errorMessage,
            'debug' => $isDevelopment ? [
                'error_info' => $errorInfo,
                'exception' => $exceptionMsg
            ] : null
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Không tiết lộ thông tin chi tiết cho user
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra, vui lòng thử lại sau'
    ]);
}

