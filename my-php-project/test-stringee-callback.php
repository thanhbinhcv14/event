<?php
/**
 * Test Stringee Callback Response Format
 * File này để test xem callback response có đúng format SCCO không
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/stringee.php';
require_once __DIR__ . '/config/database.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Stringee Callback Response</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #007bff; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        .url-box { background: #e9ecef; padding: 10px; border-radius: 4px; margin: 10px 0; font-family: monospace; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test Stringee Callback Response Format</h1>
        
        <?php
        echo '<div class="section">';
        echo '<h2>1. Kiểm tra Answer URL Configuration</h2>';
        
        $answerUrl = STRINGEE_ANSWER_URL;
        echo '<p><strong>Answer URL hiện tại:</strong></p>';
        echo '<div class="url-box">' . htmlspecialchars($answerUrl) . '</div>';
        
        // Kiểm tra URL có phải là helper URL của Stringee không
        if (strpos($answerUrl, 'developer.stringee.com') !== false) {
            echo '<p class="error">❌ <strong>VẤN ĐỀ:</strong> Answer URL đang dùng URL helper của Stringee!</p>';
            echo '<p>URL helper này không hoạt động với production. Cần cập nhật trong Stringee Dashboard.</p>';
            echo '<p><strong>URL đúng cần dùng:</strong></p>';
            echo '<div class="url-box">' . htmlspecialchars(BASE_URL . '/src/controllers/stringee-callback.php?type=answer') . '</div>';
        } else {
            echo '<p class="success">✅ Answer URL không phải là helper URL của Stringee</p>';
        }
        
        // Kiểm tra URL có accessible không
        echo '<p><strong>Kiểm tra URL có accessible:</strong></p>';
        $ch = curl_init($answerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'action' => 'connect',
            'from' => ['type' => 'internal', 'number' => 'test1', 'alias' => 'Test User 1'],
            'to' => ['type' => 'internal', 'number' => 'test2', 'alias' => 'Test User 2'],
            'customData' => 'test=1'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo '<p class="error">❌ Lỗi khi test URL: ' . htmlspecialchars($curlError) . '</p>';
        } else {
            echo '<p class="info">📡 HTTP Status Code: ' . $httpCode . '</p>';
            if ($httpCode === 200) {
                echo '<p class="success">✅ URL accessible và trả về 200 OK</p>';
            } else {
                echo '<p class="warning">⚠️ URL trả về status code: ' . $httpCode . ' (nên là 200)</p>';
            }
        }
        
        echo '</div>';
        
        // Test SCCO Response Format
        echo '<div class="section">';
        echo '<h2>2. Test SCCO Response Format</h2>';
        
        // Simulate callback data
        $testData = [
            'action' => 'connect',
            'from' => [
                'type' => 'internal',
                'number' => 'user123',
                'alias' => 'Test User'
            ],
            'to' => [
                'type' => 'internal',
                'number' => 'user456',
                'alias' => 'Test Receiver'
            ],
            'customData' => 'conversation_id=1&call_id=2&call_type=voice'
        ];
        
        echo '<p><strong>Test với data:</strong></p>';
        echo '<pre>' . json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        
        // Simulate response generation (copy logic from stringee-callback.php)
        $fromNumber = $testData['from']['number'] ?? 'unknown';
        $fromAlias = $testData['from']['alias'] ?? $fromNumber;
        $toNumber = $testData['to']['number'] ?? 'unknown';
        $toAlias = $testData['to']['alias'] ?? $toNumber;
        $customData = $testData['customData'] ?? '';
        
        $sccoResponse = [
            'action' => 'connect',
            'from' => [
                'type' => 'internal',
                'number' => (string)$fromNumber,
                'alias' => (string)$fromAlias
            ],
            'to' => [
                'type' => 'internal',
                'number' => (string)$toNumber,
                'alias' => (string)$toAlias
            ],
            'customData' => (string)$customData,
            'timeout' => (int)STRINGEE_CALL_TIMEOUT,
            'maxConnectTime' => (int)STRINGEE_MAX_CONNECT_TIME,
            'peerToPeerCall' => true
        ];
        
        echo '<p><strong>SCCO Response được tạo:</strong></p>';
        echo '<pre>' . json_encode($sccoResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
        
        // Validate SCCO format
        echo '<p><strong>Validation SCCO Format:</strong></p>';
        $errors = [];
        
        // Check required fields
        $requiredFields = ['action', 'from', 'to', 'customData', 'timeout', 'maxConnectTime', 'peerToPeerCall'];
        foreach ($requiredFields as $field) {
            if (!isset($sccoResponse[$field])) {
                $errors[] = "Thiếu field: $field";
            }
        }
        
        // Check action
        if (isset($sccoResponse['action']) && $sccoResponse['action'] !== 'connect') {
            $errors[] = "action phải là 'connect', nhưng là: " . $sccoResponse['action'];
        }
        
        // Check from/to structure
        if (isset($sccoResponse['from'])) {
            $fromRequired = ['type', 'number', 'alias'];
            foreach ($fromRequired as $field) {
                if (!isset($sccoResponse['from'][$field])) {
                    $errors[] = "from thiếu field: $field";
                }
            }
            if (isset($sccoResponse['from']['number']) && empty($sccoResponse['from']['number'])) {
                $errors[] = "from.number không được rỗng";
            }
        }
        
        if (isset($sccoResponse['to'])) {
            $toRequired = ['type', 'number', 'alias'];
            foreach ($toRequired as $field) {
                if (!isset($sccoResponse['to'][$field])) {
                    $errors[] = "to thiếu field: $field";
                }
            }
            if (isset($sccoResponse['to']['number']) && empty($sccoResponse['to']['number'])) {
                $errors[] = "to.number không được rỗng";
            }
        }
        
        // Check types
        if (isset($sccoResponse['timeout']) && !is_int($sccoResponse['timeout'])) {
            $errors[] = "timeout phải là integer, nhưng là: " . gettype($sccoResponse['timeout']);
        }
        if (isset($sccoResponse['maxConnectTime']) && !is_int($sccoResponse['maxConnectTime'])) {
            $errors[] = "maxConnectTime phải là integer, nhưng là: " . gettype($sccoResponse['maxConnectTime']);
        }
        if (isset($sccoResponse['peerToPeerCall']) && !is_bool($sccoResponse['peerToPeerCall'])) {
            $errors[] = "peerToPeerCall phải là boolean, nhưng là: " . gettype($sccoResponse['peerToPeerCall']);
        }
        
        if (empty($errors)) {
            echo '<p class="success">✅ SCCO Response format đúng!</p>';
        } else {
            echo '<p class="error">❌ SCCO Response có lỗi:</p>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li class="error">' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
        }
        
        // Test JSON encoding
        $jsonResponse = json_encode($sccoResponse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $jsonError = json_last_error();
        if ($jsonError === JSON_ERROR_NONE) {
            echo '<p class="success">✅ JSON encoding thành công</p>';
            echo '<p><strong>JSON Response (để gửi cho Stringee):</strong></p>';
            echo '<pre>' . htmlspecialchars($jsonResponse) . '</pre>';
        } else {
            echo '<p class="error">❌ JSON encoding lỗi: ' . json_last_error_msg() . '</p>';
        }
        
        echo '</div>';
        
        // Giải thích lỗi
        echo '<div class="section">';
        echo '<h2>3. Giải thích lỗi ANSWER_URL_SCCO_INCORRECT_FORMAT</h2>';
        echo '<p><strong>Lỗi này xảy ra khi:</strong></p>';
        echo '<ol>';
        echo '<li><strong>Answer URL trong Stringee Dashboard chưa được cập nhật đúng</strong><br>';
        echo '   → Vẫn đang dùng URL helper: <code>https://developer.stringee.com/scco_helper/...</code><br>';
        echo '   → Cần cập nhật thành URL của server bạn (xem phần 1 ở trên)</li>';
        echo '<li><strong>Response từ callback không đúng format SCCO</strong><br>';
        echo '   → Thiếu các field bắt buộc (action, from, to, timeout, maxConnectTime, peerToPeerCall)<br>';
        echo '   → Field có type sai (timeout phải là int, peerToPeerCall phải là boolean)<br>';
        echo '   → from.number hoặc to.number bị rỗng</li>';
        echo '<li><strong>Có output trước JSON response</strong><br>';
        echo '   → Có whitespace, BOM, hoặc error messages trước JSON<br>';
        echo '   → PHP warnings/notices được output</li>';
        echo '<li><strong>Response không phải là valid JSON</strong><br>';
        echo '   → JSON bị lỗi syntax<br>';
        echo '   → Có ký tự đặc biệt không được escape</li>';
        echo '</ol>';
        
        echo '<p><strong>Cách fix:</strong></p>';
        echo '<ol>';
        echo '<li><strong>Cập nhật Answer URL trong Stringee Dashboard:</strong><br>';
        echo '   a. Đăng nhập vào <a href="https://console.stringee.com/" target="_blank">Stringee Console</a><br>';
        echo '   b. Vào project của bạn<br>';
        echo '   c. Click "Detail" (biểu tượng wrench)<br>';
        echo '   d. Chọn tab "Config URL"<br>';
        echo '   e. Cập nhật Answer URL thành:<br>';
        echo '   <div class="url-box">' . htmlspecialchars(BASE_URL . '/src/controllers/stringee-callback.php?type=answer') . '</div>';
        echo '   f. Click "Save"</li>';
        echo '<li><strong>Kiểm tra response format:</strong><br>';
        echo '   → Chạy test này để xem response có đúng format không<br>';
        echo '   → Kiểm tra logs trong <code>stringee-callback.php</code></li>';
        echo '<li><strong>Kiểm tra output buffering:</strong><br>';
        echo '   → Đảm bảo không có output trước JSON<br>';
        echo '   → File <code>stringee-callback.php</code> đã có output buffering</li>';
        echo '</ol>';
        
        echo '</div>';
        
        // Test thực tế
        echo '<div class="section">';
        echo '<h2>4. Test Callback Thực Tế</h2>';
        echo '<p>Bạn có thể test callback bằng cách:</p>';
        echo '<ol>';
        echo '<li><strong>Dùng curl:</strong><br>';
        echo '<pre>curl -X POST ' . htmlspecialchars($answerUrl) . ' \\
  -H "Content-Type: application/json" \\
  -d \'{
    "action": "connect",
    "from": {"type": "internal", "number": "user1", "alias": "User 1"},
    "to": {"type": "internal", "number": "user2", "alias": "User 2"},
    "customData": "test=1"
  }\'</pre></li>';
        echo '<li><strong>Hoặc mở URL trực tiếp trong browser:</strong><br>';
        echo '   <a href="' . htmlspecialchars($answerUrl) . '" target="_blank">' . htmlspecialchars($answerUrl) . '</a><br>';
        echo '   (Sẽ trả về JSON, có thể là error vì thiếu data)</li>';
        echo '</ol>';
        echo '</div>';
        
        // Checklist
        echo '<div class="section">';
        echo '<h2>5. Checklist</h2>';
        echo '<ul>';
        echo '<li>[ ] Answer URL trong Stringee Dashboard đã được cập nhật đúng</li>';
        echo '<li>[ ] Answer URL accessible từ internet (test bằng curl hoặc browser)</li>';
        echo '<li>[ ] SCCO Response format đúng (xem phần 2)</li>';
        echo '<li>[ ] Không có output trước JSON response</li>';
        echo '<li>[ ] Test thực tế một cuộc gọi</li>';
        echo '</ul>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
