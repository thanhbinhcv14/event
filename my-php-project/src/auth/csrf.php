<?php
/**
 * CSRF Protection Helper
 * Bảo vệ khỏi Cross-Site Request Forgery attacks
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate CSRF token và lưu vào session
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token hiện tại (không tạo mới nếu chưa có)
 * @return string|null CSRF token hoặc null
 */
function getCSRFToken() {
    return $_SESSION['csrf_token'] ?? null;
}

/**
 * Verify CSRF token
 * @param string $token Token cần verify
 * @param int $maxAge Thời gian token hợp lệ (giây), mặc định 3600 (1 giờ)
 * @return bool True nếu token hợp lệ
 */
function verifyCSRFToken($token, $maxAge = 3600) {
    if (empty($token)) {
        return false;
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Kiểm tra token có khớp không
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    // Kiểm tra token có hết hạn không
    if (isset($_SESSION['csrf_token_time'])) {
        $age = time() - $_SESSION['csrf_token_time'];
        if ($age > $maxAge) {
            // Token hết hạn, xóa và tạo mới
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }
    }
    
    return true;
}

/**
 * Verify CSRF token từ POST request
 * @param int $maxAge Thời gian token hợp lệ (giây)
 * @return bool True nếu token hợp lệ
 */
function verifyCSRFPost($maxAge = 3600) {
    $token = $_POST['csrf_token'] ?? $_POST['_token'] ?? '';
    return verifyCSRFToken($token, $maxAge);
}

// Cache để lưu input stream (php://input chỉ đọc được một lần)
$GLOBALS['_csrf_cached_input'] = null;

/**
 * Get và cache input stream
 */
function getCachedInput() {
    if ($GLOBALS['_csrf_cached_input'] === null) {
        $GLOBALS['_csrf_cached_input'] = file_get_contents('php://input');
    }
    return $GLOBALS['_csrf_cached_input'];
}

/**
 * Verify CSRF token từ JSON request
 * @param int $maxAge Thời gian token hợp lệ (giây)
 * @return bool True nếu token hợp lệ
 */
function verifyCSRFJson($maxAge = 3600) {
    $jsonInput = getCachedInput();
    $data = json_decode($jsonInput, true);
    
    if (!$data) {
        return false;
    }
    
    $token = $data['csrf_token'] ?? $data['_token'] ?? '';
    return verifyCSRFToken($token, $maxAge);
}

/**
 * Verify CSRF token từ header
 * @param int $maxAge Thời gian token hợp lệ (giây)
 * @return bool True nếu token hợp lệ
 */
function verifyCSRFHeader($maxAge = 3600) {
    $headers = getallheaders();
    $token = $headers['X-CSRF-Token'] ?? $headers['X-CSRF-TOKEN'] ?? '';
    return verifyCSRFToken($token, $maxAge);
}

/**
 * Verify CSRF token tự động (từ POST, JSON hoặc Header)
 * @param int $maxAge Thời gian token hợp lệ (giây)
 * @return bool True nếu token hợp lệ
 */
function verifyCSRF($maxAge = 3600) {
    // Ưu tiên: POST > JSON > Header
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST)) {
            return verifyCSRFPost($maxAge);
        } else {
            // POST với JSON body
            return verifyCSRFJson($maxAge);
        }
    }
    
    // Kiểm tra header
    return verifyCSRFHeader($maxAge);
}

/**
 * Require CSRF token - Nếu không hợp lệ sẽ trả về error
 * @param int $maxAge Thời gian token hợp lệ (giây)
 * @return void Exit nếu token không hợp lệ
 */
function requireCSRF($maxAge = 3600) {
    if (!verifyCSRF($maxAge)) {
        // Debug logging
        error_log("CSRF verification failed. Session token: " . (isset($_SESSION['csrf_token']) ? 'exists' : 'missing'));
        error_log("Request method: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
        error_log("POST data: " . print_r($_POST, true));
        
        // Log JSON input if available
        $jsonInput = getCachedInput();
        if ($jsonInput) {
            $jsonData = json_decode($jsonInput, true);
            error_log("JSON input: " . print_r($jsonData, true));
            error_log("CSRF token in JSON: " . ($jsonData['csrf_token'] ?? 'NOT FOUND'));
        }
        
        // Log headers
        $headers = getallheaders();
        error_log("X-CSRF-Token header: " . ($headers['X-CSRF-Token'] ?? $headers['X-CSRF-TOKEN'] ?? 'NOT FOUND'));
        
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'CSRF token không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang.',
            'code' => 'CSRF_TOKEN_INVALID',
            'debug' => [
                'has_session_token' => isset($_SESSION['csrf_token']),
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'has_post_data' => !empty($_POST),
                'has_json_data' => !empty($jsonInput)
            ]
        ]);
        exit;
    }
}

/**
 * Regenerate CSRF token (sau khi verify thành công cho các action quan trọng)
 */
function regenerateCSRFToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

