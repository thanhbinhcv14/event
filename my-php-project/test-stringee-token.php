<?php
/**
 * Test Stringee Token Generation
 * File này để test xem token có được tạo đúng không
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/stringee.php';

// ✅ Copy các hàm cần thiết từ stringee-controller.php để tránh lỗi action
/**
 * Generate Stringee Access Token
 * Sử dụng REST API của Stringee để tạo token
 */
function generateStringeeToken($userId) {
    $apiSid = trim(STRINGEE_API_SID);
    $apiSecret = trim(STRINGEE_API_SECRET);
    
    // Validate API SID và Secret
    if (empty($apiSid)) {
        throw new Exception('API SID không được cấu hình hoặc rỗng');
    }
    if (empty($apiSecret)) {
        throw new Exception('API Secret không được cấu hình hoặc rỗng');
    }
    
    // Tạo JWT token cho Stringee
    // Stringee sử dụng JWT với các claims:
    // - jti: unique token ID
    // - iss: API SID
    // - exp: expiration time
    // - userId: user ID
    
    $expireTime = time() + STRINGEE_TOKEN_TTL;
    $jti = bin2hex(random_bytes(16)); // Unique token ID
    
    // Build JWT payload
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];
    
    $payload = [
        'jti' => $jti,
        'iss' => $apiSid,
        'exp' => $expireTime,
        'userId' => $userId
    ];
    
    // Encode header và payload
    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));
    
    // Create signature
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $apiSecret, true);
    $signatureEncoded = base64UrlEncode($signature);
    
    // Build final token
    $token = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    
    return $token;
}

/**
 * Base64 URL encode (Stringee format)
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Stringee Token</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #007bff; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Test Stringee Token Generation</h1>
        
        <?php
        echo '<div class="section">';
        echo '<h2>1. Kiểm tra API Credentials</h2>';
        
        $apiSid = trim(STRINGEE_API_SID);
        $apiSecret = trim(STRINGEE_API_SECRET);
        
        // Kiểm tra API SID
        if (empty($apiSid)) {
            echo '<p class="error">❌ STRINGEE_API_SID không được cấu hình hoặc rỗng</p>';
        } else {
            echo '<p class="success">✅ STRINGEE_API_SID: ' . htmlspecialchars(substr($apiSid, 0, 20)) . '... (length: ' . strlen($apiSid) . ')</p>';
            
            // Kiểm tra format
            if (strpos($apiSid, 'SK.') === 0) {
                echo '<p class="success">✅ API SID format đúng (bắt đầu với SK.)</p>';
            } else {
                echo '<p class="warning">⚠️ API SID format có vẻ không đúng (nên bắt đầu với SK.)</p>';
            }
        }
        
        // Kiểm tra API Secret
        if (empty($apiSecret)) {
            echo '<p class="error">❌ STRINGEE_API_SECRET không được cấu hình hoặc rỗng</p>';
        } else {
            echo '<p class="success">✅ STRINGEE_API_SECRET: ******** (length: ' . strlen($apiSecret) . ')</p>';
            
            if (strlen($apiSecret) < 20) {
                echo '<p class="warning">⚠️ API Secret có vẻ quá ngắn (thường > 20 ký tự)</p>';
            }
        }
        
        echo '</div>';
        
        // Test token generation
        echo '<div class="section">';
        echo '<h2>2. Test Token Generation</h2>';
        
        try {
            $testUserId = 'test_user_' . time();
            echo '<p class="info">📝 Test với User ID: ' . htmlspecialchars($testUserId) . '</p>';
            
            $token = generateStringeeToken($testUserId);
            
            if (empty($token)) {
                echo '<p class="error">❌ Token generation trả về rỗng</p>';
            } else {
                echo '<p class="success">✅ Token generated successfully</p>';
                echo '<p>Token length: ' . strlen($token) . ' characters</p>';
                echo '<p>Token preview: ' . htmlspecialchars(substr($token, 0, 50)) . '...</p>';
                
                // Kiểm tra token format (JWT có 3 parts)
                $parts = explode('.', $token);
                if (count($parts) === 3) {
                    echo '<p class="success">✅ Token format đúng (JWT có 3 parts)</p>';
                    
                    // Decode payload
                    try {
                        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                        if ($payload) {
                            echo '<h3>Token Payload:</h3>';
                            echo '<pre>' . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                            
                            // Kiểm tra các fields
                            $requiredFields = ['jti', 'iss', 'exp', 'userId'];
                            $missingFields = [];
                            foreach ($requiredFields as $field) {
                                if (!isset($payload[$field])) {
                                    $missingFields[] = $field;
                                }
                            }
                            
                            if (empty($missingFields)) {
                                echo '<p class="success">✅ Token payload có đầy đủ các fields cần thiết</p>';
                                
                                // Kiểm tra exp
                                $exp = $payload['exp'];
                                $now = time();
                                $expiresIn = $exp - $now;
                                
                                echo '<p>Token expires in: ' . round($expiresIn / 3600, 2) . ' hours</p>';
                                
                                if ($expiresIn > 0) {
                                    echo '<p class="success">✅ Token chưa hết hạn</p>';
                                } else {
                                    echo '<p class="error">❌ Token đã hết hạn</p>';
                                }
                                
                                // Kiểm tra iss (API SID)
                                if ($payload['iss'] === $apiSid) {
                                    echo '<p class="success">✅ Token issuer (iss) khớp với API SID</p>';
                                } else {
                                    echo '<p class="error">❌ Token issuer (iss) KHÔNG khớp với API SID</p>';
                                    echo '<p>Expected: ' . htmlspecialchars($apiSid) . '</p>';
                                    echo '<p>Got: ' . htmlspecialchars($payload['iss']) . '</p>';
                                }
                                
                                // Kiểm tra userId
                                if ($payload['userId'] === $testUserId) {
                                    echo '<p class="success">✅ Token userId khớp</p>';
                                } else {
                                    echo '<p class="warning">⚠️ Token userId không khớp</p>';
                                }
                                
                            } else {
                                echo '<p class="error">❌ Token payload thiếu fields: ' . implode(', ', $missingFields) . '</p>';
                            }
                        } else {
                            echo '<p class="error">❌ Không thể decode token payload</p>';
                        }
                    } catch (Exception $e) {
                        echo '<p class="error">❌ Lỗi decode token: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                } else {
                    echo '<p class="error">❌ Token format SAI (JWT phải có 3 parts, nhưng có ' . count($parts) . ' parts)</p>';
                }
            }
        } catch (Exception $e) {
            echo '<p class="error">❌ Lỗi khi tạo token: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        
        echo '</div>';
        
        // Hướng dẫn
        echo '<div class="section">';
        echo '<h2>3. Hướng dẫn Kiểm tra trong Stringee Dashboard</h2>';
        echo '<ol>';
        echo '<li>Truy cập: <a href="https://console.stringee.com/" target="_blank">https://console.stringee.com/</a></li>';
        echo '<li>Đăng nhập với tài khoản của bạn</li>';
        echo '<li>Click vào project của bạn (hoặc tạo project mới)</li>';
        echo '<li>Click <strong>"Detail"</strong> (biểu tượng wrench) hoặc click vào tên project</li>';
        echo '<li>Chọn tab <strong>"API"</strong> hoặc <strong>"Credentials"</strong></li>';
        echo '<li>Copy <strong>API SID Key</strong> và <strong>API Secret Key</strong></li>';
        echo '<li>So sánh với giá trị trong <code>config/stringee.php</code></li>';
        echo '</ol>';
        echo '<p class="warning">⚠️ Lưu ý: API Secret chỉ hiển thị 1 lần. Nếu mất, phải reset hoặc tạo project mới.</p>';
        echo '</div>';
        
        // Next steps
        echo '<div class="section">';
        echo '<h2>4. Next Steps</h2>';
        echo '<ul>';
        echo '<li>Nếu token generation thành công → Kiểm tra authentication trong browser console</li>';
        echo '<li>Nếu có lỗi → Xem file <code>docs/STRINGEE_AUTHENTICATION_CHECK.md</code> để biết cách sửa</li>';
        echo '<li>Đảm bảo API SID và Secret trong Dashboard khớp với config</li>';
        echo '</ul>';
        echo '</div>';
        ?>
    </div>
</body>
</html>

