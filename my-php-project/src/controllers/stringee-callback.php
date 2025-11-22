<?php
/**
 * Stringee Callback Handler
 * Xử lý Answer URL và Event URL từ Stringee
 * 
 * SCCO Response Format:
 * {
 *   "action": "connect",
 *   "from": {"type": "internal", "number": "user_id", "alias": "User Name"},
 *   "to": {"type": "internal", "number": "user_id", "alias": "User Name"},
 *   "customData": "",
 *   "timeout": 60,
 *   "maxConnectTime": 0,
 *   "peerToPeerCall": true
 * }
 */

// Tắt output để tránh output trước JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Clear output buffer
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Set JSON header
header('Content-Type: application/json', true);

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/stringee.php';
    $pdo = getDBConnection();
} catch (Exception $e) {
    error_log('Stringee callback config error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error']);
    exit;
}

// Xác định callback type
$callbackType = $_GET['type'] ?? 'answer';

// Lấy data từ request
$input = file_get_contents('php://input');
$jsonData = json_decode($input, true);

// Stringee gửi GET parameters, merge với JSON body nếu có
$data = array_merge($_POST, $_GET);
if ($jsonData && is_array($jsonData)) {
    $data = array_merge($data, $jsonData);
}

// Log để debug
error_log('Stringee Callback [' . $callbackType . ']: ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

// Xử lý callback
if ($callbackType === 'answer') {
    handleAnswerCallback($pdo, $data);
} else {
    handleEventCallback($pdo, $data);
}

/**
 * Handle Answer Callback
 * Trả về SCCO response cho Stringee
 */
function handleAnswerCallback($pdo, $data) {
    // Stringee gửi: from="user_id", to="user_id" trong GET parameters
    $fromUserId = isset($data['from']) ? trim((string)$data['from']) : '';
    $toUserId = isset($data['to']) ? trim((string)$data['to']) : '';
    $callId = isset($data['callId']) ? trim((string)$data['callId']) : '';
    
    // Nếu không có từ request, thử lấy từ database
    if (empty($fromUserId) || empty($toUserId)) {
        if (!empty($callId)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT caller_id, receiver_id,
                           COALESCE(nv1.HoTen, kh1.HoTen, u1.Email) as caller_name,
                           COALESCE(nv2.HoTen, kh2.HoTen, u2.Email) as receiver_name
                    FROM call_sessions cs
                    LEFT JOIN users u1 ON cs.caller_id = u1.ID_User
                    LEFT JOIN nhanvieninfo nv1 ON u1.ID_User = nv1.ID_User
                    LEFT JOIN khachhanginfo kh1 ON u1.ID_User = kh1.ID_User
                    LEFT JOIN users u2 ON cs.receiver_id = u2.ID_User
                    LEFT JOIN nhanvieninfo nv2 ON u2.ID_User = nv2.ID_User
                    LEFT JOIN khachhanginfo kh2 ON u2.ID_User = kh2.ID_User
                    WHERE cs.id = ?
                ");
                $stmt->execute([$callId]);
                $callInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($callInfo) {
                    if (empty($fromUserId)) {
                        $fromUserId = (string)$callInfo['caller_id'];
                        $fromAlias = $callInfo['caller_name'] ?? $fromUserId;
                    }
                    if (empty($toUserId)) {
                        $toUserId = (string)$callInfo['receiver_id'];
                        $toAlias = $callInfo['receiver_name'] ?? $toUserId;
                    }
                }
            } catch (Exception $e) {
                error_log('Error getting call info: ' . $e->getMessage());
            }
        }
    }
    
    // Đảm bảo có giá trị (không được rỗng)
    if (empty($fromUserId)) {
        $fromUserId = isset($data['userId']) ? (string)$data['userId'] : 'unknown';
    }
    if (empty($toUserId)) {
        $toUserId = isset($data['userId']) ? (string)$data['userId'] : 'unknown';
    }
    
    // Lấy alias nếu chưa có
    if (!isset($fromAlias)) {
        $fromAlias = $fromUserId;
    }
    if (!isset($toAlias)) {
        $toAlias = $toUserId;
    }
    
    // Tạo SCCO response
    $response = [
        'action' => 'connect',
        'from' => [
            'type' => 'internal',
            'number' => (string)$fromUserId,
            'alias' => (string)$fromAlias
        ],
        'to' => [
            'type' => 'internal',
            'number' => (string)$toUserId,
            'alias' => (string)$toAlias
        ],
        'customData' => isset($data['custom']) ? (string)$data['custom'] : '',
        'timeout' => (int)STRINGEE_CALL_TIMEOUT,
        'maxConnectTime' => (int)STRINGEE_MAX_CONNECT_TIME,
        'peerToPeerCall' => true
    ];
    
    // Log response
    error_log('Stringee SCCO Response: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    
    // Output JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json', true);
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Handle Event Callback
 * Xử lý các events từ Stringee
 */
function handleEventCallback($pdo, $data) {
    $eventType = $data['type'] ?? $data['event'] ?? '';
    $callId = $data['callId'] ?? $data['call_id'] ?? '';
    
    error_log('Stringee Event: ' . $eventType . ' - Call ID: ' . $callId);
    
    // Update call session nếu có
    if (!empty($callId)) {
        try {
            $status = 'ended';
            if ($eventType === 'call-started' || $eventType === 'callStarted') {
                $status = 'accepted';
            } elseif ($eventType === 'call-rejected' || $eventType === 'callRejected') {
                $status = 'rejected';
            }
            
            $stmt = $pdo->prepare("UPDATE call_sessions SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $callId]);
        } catch (Exception $e) {
            error_log('Error updating call session: ' . $e->getMessage());
        }
    }
    
    // Output JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json', true);
    echo json_encode(['success' => true, 'event' => $eventType]);
    exit;
}
