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
ini_set('log_errors', 1);
error_reporting(E_ALL);

// QUAN TRỌNG: Start output buffering để capture mọi output
// Đảm bảo không có output nào trước JSON response
if (ob_get_level() == 0) {
    ob_start();
}

// Clear output buffer ngay từ đầu để đảm bảo không có whitespace
while (ob_get_level() > 0) {
    ob_end_clean();
}

// KHÔNG set header ở đây - sẽ set sau khi xử lý xong

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/stringee.php';
    $pdo = getDBConnection();
} catch (Exception $e) {
    error_log('Stringee callback config error: ' . $e->getMessage());
    // Clear output trước khi gửi error
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8', true);
    echo json_encode(['error' => 'Configuration error']);
    exit;
}

// Xác định callback type
$callbackType = $_GET['type'] ?? 'answer';

// Lấy data từ request
$input = file_get_contents('php://input');
$jsonData = json_decode($input, true);

// Stringee có thể gửi data theo nhiều cách:
// 1. GET parameters (query string)
// 2. POST parameters (form data)
// 3. JSON body
// 4. Raw JSON trong php://input

// Merge tất cả các nguồn (ưu tiên: JSON body > POST > GET)
$data = [];
if ($jsonData && is_array($jsonData)) {
    $data = array_merge($data, $jsonData);
}
$data = array_merge($data, $_POST);
$data = array_merge($data, $_GET);

// Log chi tiết để debug
error_log('=== Stringee Callback [' . $callbackType . '] ===');
error_log('GET params: ' . json_encode($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
error_log('POST params: ' . json_encode($_POST, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
error_log('JSON body: ' . $input);
error_log('Merged data: ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
error_log('==========================================');

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
    // Stringee có thể gửi thông tin theo nhiều cách:
    // 1. GET parameters: from, to, callId
    // 2. JSON body: from, to, callId
    // 3. POST parameters: from, to, callId
    
    $fromUserId = '';
    $toUserId = '';
    $callId = '';
    
    // Lấy từ nhiều nguồn khác nhau - Stringee có thể gửi với nhiều tên field khác nhau
    // QUAN TRỌNG: Stringee có thể gửi from/to là object {number: "17", alias: "", type: "internal"}
    // hoặc là string "17"
    $fromUserId = '';
    if (isset($data['from'])) {
        if (is_array($data['from']) && isset($data['from']['number'])) {
            // from là object: {number: "17", alias: "", type: "internal"}
            $fromUserId = trim((string)$data['from']['number']);
        } elseif (is_string($data['from']) && !empty($data['from'])) {
            // from là string: "17"
            $fromUserId = trim((string)$data['from']);
        }
    }
    
    // Fallback: thử các field name khác
    if (empty($fromUserId)) {
        if (isset($data['fromNumber']) && !empty($data['fromNumber'])) {
            $fromUserId = trim((string)$data['fromNumber']);
        } elseif (isset($data['fromUserId']) && !empty($data['fromUserId'])) {
            $fromUserId = trim((string)$data['fromUserId']);
        } elseif (isset($data['from_id']) && !empty($data['from_id'])) {
            $fromUserId = trim((string)$data['from_id']);
        } elseif (isset($data['request_from_user_id']) && !empty($data['request_from_user_id'])) {
            $fromUserId = trim((string)$data['request_from_user_id']);
        }
    }
    
    // Tương tự cho to
    $toUserId = '';
    if (isset($data['to'])) {
        if (is_array($data['to']) && isset($data['to']['number'])) {
            // to là object: {number: "3", alias: "", type: "internal"}
            $toUserId = trim((string)$data['to']['number']);
        } elseif (is_string($data['to']) && !empty($data['to'])) {
            // to là string: "3"
            $toUserId = trim((string)$data['to']);
        }
    }
    
    // Fallback: thử các field name khác
    if (empty($toUserId)) {
        if (isset($data['toNumber']) && !empty($data['toNumber'])) {
            $toUserId = trim((string)$data['toNumber']);
        } elseif (isset($data['toUserId']) && !empty($data['toUserId'])) {
            $toUserId = trim((string)$data['toUserId']);
        } elseif (isset($data['to_id']) && !empty($data['to_id'])) {
            $toUserId = trim((string)$data['to_id']);
        }
    }
    
    // Ưu tiên: callId > call_id > callIdNumber
    $callId = '';
    if (isset($data['callId']) && !empty($data['callId'])) {
        $callId = trim((string)$data['callId']);
    } elseif (isset($data['call_id']) && !empty($data['call_id'])) {
        $callId = trim((string)$data['call_id']);
    } elseif (isset($data['callIdNumber']) && !empty($data['callIdNumber'])) {
        $callId = trim((string)$data['callIdNumber']);
    }
    
    // QUAN TRỌNG: Lấy callId từ customData nếu có (Stringee gửi trong custom field)
    if (empty($callId) && isset($data['custom'])) {
        $customData = $data['custom'];
        if (is_string($customData)) {
            $customJson = json_decode($customData, true);
            if ($customJson && isset($customJson['callId'])) {
                $callId = trim((string)$customJson['callId']);
            }
        } elseif (is_array($customData) && isset($customData['callId'])) {
            $callId = trim((string)$customData['callId']);
        }
    }
    
    // QUAN TRỌNG: Stringee callId (ví dụ: call-vn-1-393NUNB451-1763592399089) KHÔNG phải database ID
    // Phải query bằng stringee_call_id hoặc from/to
    $callInfo = null;
    
    // Ưu tiên 1: Query bằng stringee_call_id nếu có (chính xác nhất)
        if (!empty($callId)) {
            try {
                $stmt = $pdo->prepare("
                SELECT id, caller_id, receiver_id,
                           COALESCE(nv1.HoTen, kh1.HoTen, u1.Email) as caller_name,
                           COALESCE(nv2.HoTen, kh2.HoTen, u2.Email) as receiver_name
                    FROM call_sessions cs
                    LEFT JOIN users u1 ON cs.caller_id = u1.ID_User
                    LEFT JOIN nhanvieninfo nv1 ON u1.ID_User = nv1.ID_User
                    LEFT JOIN khachhanginfo kh1 ON u1.ID_User = kh1.ID_User
                    LEFT JOIN users u2 ON cs.receiver_id = u2.ID_User
                    LEFT JOIN nhanvieninfo nv2 ON u2.ID_User = nv2.ID_User
                    LEFT JOIN khachhanginfo kh2 ON u2.ID_User = kh2.ID_User
                WHERE cs.stringee_call_id = ?
                ORDER BY cs.started_at DESC
                LIMIT 1
                ");
                $stmt->execute([$callId]);
                $callInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($callInfo) {
                error_log('Found call session by stringee_call_id: ' . $callId);
            }
        } catch (Exception $e) {
            error_log('Error getting call info by stringee_call_id: ' . $e->getMessage());
        }
    }
    
    // Nếu không tìm thấy bằng callId và có from/to, query bằng from/to
    if (!$callInfo && !empty($fromUserId) && !empty($toUserId)) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, caller_id, receiver_id,
                       COALESCE(nv1.HoTen, kh1.HoTen, u1.Email) as caller_name,
                       COALESCE(nv2.HoTen, kh2.HoTen, u2.Email) as receiver_name
                FROM call_sessions cs
                LEFT JOIN users u1 ON cs.caller_id = u1.ID_User
                LEFT JOIN nhanvieninfo nv1 ON u1.ID_User = nv1.ID_User
                LEFT JOIN khachhanginfo kh1 ON u1.ID_User = kh1.ID_User
                LEFT JOIN users u2 ON cs.receiver_id = u2.ID_User
                LEFT JOIN nhanvieninfo nv2 ON u2.ID_User = nv2.ID_User
                LEFT JOIN khachhanginfo kh2 ON u2.ID_User = kh2.ID_User
                WHERE (
                    (cs.caller_id = ? AND cs.receiver_id = ?) OR
                    (cs.caller_id = ? AND cs.receiver_id = ?)
                )
                AND cs.status IN ('initiated', 'ringing', 'accepted')
                ORDER BY cs.started_at DESC
                LIMIT 1
            ");
            $stmt->execute([$fromUserId, $toUserId, $toUserId, $fromUserId]);
            $callInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Cập nhật callId nếu tìm thấy
            if ($callInfo && empty($callId)) {
                $callId = (string)$callInfo['id'];
            }
        } catch (Exception $e) {
            error_log('Error getting call info by from/to: ' . $e->getMessage());
        }
    }
    
    // FALLBACK: Nếu vẫn không tìm thấy, query call session mới nhất (trong 5 phút gần đây)
    // CHỈ fallback nếu request có ít nhất 1 trong: from, to, callId (có thể thiếu nhưng không phải hoàn toàn trống)
    // Nếu request hoàn toàn trống (chỉ có type=answer), đây là callback rác → reject ngay
    $hasAnyCallData = !empty($callId) || !empty($fromUserId) || !empty($toUserId);
    
    if (!$callInfo && $hasAnyCallData) {
        // Có ít nhất 1 thông tin → cố gắng tìm call session
        try {
            error_log('Warning: No call info found by callId or from/to. Trying to get latest active call session...');
            $stmt = $pdo->prepare("
                SELECT id, caller_id, receiver_id,
                       COALESCE(nv1.HoTen, kh1.HoTen, u1.Email) as caller_name,
                       COALESCE(nv2.HoTen, kh2.HoTen, u2.Email) as receiver_name
                FROM call_sessions cs
                LEFT JOIN users u1 ON cs.caller_id = u1.ID_User
                LEFT JOIN nhanvieninfo nv1 ON u1.ID_User = nv1.ID_User
                LEFT JOIN khachhanginfo kh1 ON u1.ID_User = kh1.ID_User
                LEFT JOIN users u2 ON cs.receiver_id = u2.ID_User
                LEFT JOIN nhanvieninfo nv2 ON u2.ID_User = nv2.ID_User
                LEFT JOIN khachhanginfo kh2 ON u2.ID_User = kh2.ID_User
                WHERE cs.status IN ('initiated', 'ringing', 'accepted')
                AND cs.started_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY cs.started_at DESC
                LIMIT 1
            ");
            $stmt->execute();
            $callInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($callInfo) {
                error_log('Found latest call session: ID=' . $callInfo['id'] . ', caller=' . $callInfo['caller_id'] . ', receiver=' . $callInfo['receiver_id']);
                if (empty($callId)) {
                    $callId = (string)$callInfo['id'];
                }
            } else {
                error_log('No active call session found in last 5 minutes');
            }
        } catch (Exception $e) {
            error_log('Error getting latest call session: ' . $e->getMessage());
        }
    } elseif (!$callInfo && !$hasAnyCallData) {
        // Request hoàn toàn trống → callback rác → reject ngay
        error_log('ERROR: Callback rác - không có from/to/callId trong request. Rejecting...');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8', true);
        header('Cache-Control: no-cache, no-store, must-revalidate', true);
        
        $rejectResponse = json_encode([
            'action' => 'reject'
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        header('Content-Length: ' . strlen($rejectResponse), true);
        echo $rejectResponse;
        exit;
    }
    
    // Nếu tìm thấy callInfo từ database, ưu tiên dùng (chính xác hơn)
    if ($callInfo) {
        $fromUserId = (string)$callInfo['caller_id'];
        $toUserId = (string)$callInfo['receiver_id'];
        $fromAlias = $callInfo['caller_name'] ?? 'User ' . $fromUserId;
        $toAlias = $callInfo['receiver_name'] ?? 'User ' . $toUserId;
    } else {
        // Nếu không tìm thấy trong database, sử dụng from/to từ request và query alias
        if (!empty($fromUserId)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT COALESCE(nv.HoTen, kh.HoTen, u.Email) as name
                    FROM users u
                    LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
                    LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
                    WHERE u.ID_User = ?
                ");
                $stmt->execute([$fromUserId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $fromAlias = $user ? $user['name'] : 'User ' . $fromUserId;
            } catch (Exception $e) {
                $fromAlias = 'User ' . $fromUserId;
            }
        } else {
            $fromAlias = 'Unknown User';
        }
        
        if (!empty($toUserId)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT COALESCE(nv.HoTen, kh.HoTen, u.Email) as name
                    FROM users u
                    LEFT JOIN nhanvieninfo nv ON u.ID_User = nv.ID_User
                    LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
                    WHERE u.ID_User = ?
                ");
                $stmt->execute([$toUserId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $toAlias = $user ? $user['name'] : 'User ' . $toUserId;
            } catch (Exception $e) {
                $toAlias = 'User ' . $toUserId;
            }
        } else {
            $toAlias = 'Unknown User';
        }
    }
    
    // Đảm bảo có giá trị (không được rỗng) - QUAN TRỌNG cho SCCO format
    if (empty($fromUserId) || empty($toUserId)) {
        // Log chi tiết để debug
        error_log('WARNING: Missing fromUserId or toUserId in Stringee callback.');
        error_log('Request data: ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        error_log('GET params: ' . json_encode($_GET, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        error_log('POST params: ' . json_encode($_POST, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        error_log('JSON body: ' . file_get_contents('php://input'));
        error_log('CallId found: ' . ($callId ?: 'empty'));
        error_log('CallInfo found: ' . ($callInfo ? 'yes' : 'no'));
        
        // QUAN TRỌNG: Khi thiếu dữ liệu, trả về action "reject" hoặc "continue"
        // để Stringee không báo lỗi SCCO format
        // Đây là callback rác hoặc callback lần 2 không có context
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8', true);
        header('Cache-Control: no-cache, no-store, must-revalidate', true);
        
        // Trả về action "reject" để từ chối cuộc gọi không hợp lệ
        // Hoặc "continue" để bỏ qua callback này
        $rejectResponse = json_encode([
            'action' => 'reject'
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        header('Content-Length: ' . strlen($rejectResponse), true);
        echo $rejectResponse;
        exit;
    }
    
    // Đảm bảo alias có giá trị
    if (!isset($fromAlias) || empty($fromAlias)) {
        $fromAlias = 'User ' . $fromUserId;
    }
    if (!isset($toAlias) || empty($toAlias)) {
        $toAlias = 'User ' . $toUserId;
    }
    
    // Tạo SCCO response - QUAN TRỌNG: Format phải chính xác cho call nội bộ
    // Call nội bộ KHÔNG cần số điện thoại thật, chỉ cần user_id (string)
    $response = [
        'action' => 'connect',
        'from' => [
            'type' => 'internal',  // QUAN TRỌNG: internal cho call nội bộ
            'number' => (string)$fromUserId,  // user_id dạng string
            'alias' => (string)$fromAlias
        ],
        'to' => [
            'type' => 'internal',  // QUAN TRỌNG: internal cho call nội bộ
            'number' => (string)$toUserId,  // user_id dạng string
            'alias' => (string)$toAlias
        ],
        'timeout' => (int)STRINGEE_CALL_TIMEOUT,  // QUAN TRỌNG: phải là integer, không phải string
        'maxConnectTime' => (int)STRINGEE_MAX_CONNECT_TIME,  // QUAN TRỌNG: phải là integer
        'peerToPeerCall' => true  // QUAN TRỌNG: phải là boolean true, không phải string "true"
    ];
    
    // Chỉ thêm customData nếu có giá trị (Stringee có thể không cần field này)
    if (isset($data['custom']) && !empty($data['custom'])) {
        $response['customData'] = (string)$data['custom'];
    }
    
    // Validate response trước khi gửi
    if (empty($response['from']['number']) || empty($response['to']['number'])) {
        error_log('ERROR: Invalid SCCO response - empty from/to number');
        http_response_code(500);
        echo json_encode(['error' => 'Invalid call configuration']);
        exit;
    }
    
    // Log response
    error_log('Stringee SCCO Response: ' . json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    
    // QUAN TRỌNG: Clear tất cả output trước khi gửi JSON
    // Đảm bảo không có output nào trước JSON (kể cả whitespace, BOM, etc.)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Set HTTP status code 200 và headers TRƯỚC KHI output
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8', true);
    header('Cache-Control: no-cache, no-store, must-revalidate', true);
    header('Pragma: no-cache', true);
    header('Expires: 0', true);
    
    // Validate JSON encoding
    $jsonOutput = json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($jsonOutput === false) {
        error_log('ERROR: Failed to encode SCCO response to JSON: ' . json_last_error_msg());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate response']);
        exit;
    }
    
    // Set Content-Length header để đảm bảo Stringee nhận đúng response
    header('Content-Length: ' . strlen($jsonOutput), true);
    
    // Output JSON - đảm bảo không có whitespace trước/sau
    // Không có newline, space, hoặc bất kỳ character nào trước/sau JSON
    echo $jsonOutput;
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
    // QUAN TRỌNG: Stringee callId (ví dụ: call-vn-1-393NUNB451-1763592398520) KHÔNG phải database ID
    // Phải query bằng stringee_call_id hoặc from/to
    if (!empty($callId) || !empty($data['from']) || !empty($data['to'])) {
        try {
            // Xác định status từ event type
            $status = 'ended';
            if ($eventType === 'call-started' || $eventType === 'callStarted' || $eventType === 'stringee_call' && isset($data['call_status']) && $data['call_status'] === 'created') {
                $status = 'accepted';
            } elseif ($eventType === 'call-rejected' || $eventType === 'callRejected') {
                $status = 'rejected';
            } elseif (isset($data['call_status'])) {
                // Map Stringee call_status sang database status
                $stringeeStatus = $data['call_status'];
                if ($stringeeStatus === 'created' || $stringeeStatus === 'answered') {
                    $status = 'accepted';
                } elseif ($stringeeStatus === 'ended') {
                    $status = 'ended';
                } elseif ($stringeeStatus === 'rejected') {
                    $status = 'rejected';
                } elseif ($stringeeStatus === 'missed') {
                    $status = 'missed';
                }
            }
            
            // Lấy from/to từ request
            $fromUserId = '';
            if (isset($data['from'])) {
                if (is_array($data['from']) && isset($data['from']['number'])) {
                    $fromUserId = trim((string)$data['from']['number']);
                } elseif (is_string($data['from']) && !empty($data['from'])) {
                    $fromUserId = trim((string)$data['from']);
                }
            }
            if (empty($fromUserId) && isset($data['fromNumber'])) {
                $fromUserId = trim((string)$data['fromNumber']);
            }
            if (empty($fromUserId) && isset($data['request_from_user_id'])) {
                $fromUserId = trim((string)$data['request_from_user_id']);
            }
            
            $toUserId = '';
            if (isset($data['to'])) {
                if (is_array($data['to']) && isset($data['to']['number'])) {
                    $toUserId = trim((string)$data['to']['number']);
                } elseif (is_string($data['to']) && !empty($data['to'])) {
                    $toUserId = trim((string)$data['to']);
                }
            }
            if (empty($toUserId) && isset($data['toNumber'])) {
                $toUserId = trim((string)$data['toNumber']);
            }
            
            // QUAN TRỌNG: Tính toán ended_at và duration nếu status = 'ended'
            $updateFields = ['status = ?'];
            $updateParams = [$status];
            
            if ($status === 'ended') {
                $updateFields[] = 'ended_at = NOW()';
                $updateFields[] = 'duration = TIMESTAMPDIFF(SECOND, started_at, NOW())';
            }
            
            // QUAN TRỌNG: Cập nhật stringee_call_id nếu có và chưa được set
            if (!empty($callId)) {
                $updateFields[] = 'stringee_call_id = COALESCE(stringee_call_id, ?)';
                $updateParams[] = $callId;
            }
            
            // Ưu tiên 1: Query bằng stringee_call_id nếu có (chính xác nhất)
            $updated = false;
            if (!empty($callId)) {
                // Thêm callId vào cuối updateParams để match với WHERE clause
                $updateParamsWithCallId = array_merge($updateParams, [$callId]);
                $stmt = $pdo->prepare("
                    UPDATE call_sessions 
                    SET " . implode(', ', $updateFields) . "
                    WHERE stringee_call_id = ?
                    LIMIT 1
                ");
                $stmt->execute($updateParamsWithCallId);
                if ($stmt->rowCount() > 0) {
                    $updated = true;
                    error_log('Updated call session by stringee_call_id: ' . $callId . ' -> status: ' . $status);
                }
            }
            
            // Ưu tiên 2: Query bằng from/to nếu chưa update và có from/to
            if (!$updated && !empty($fromUserId) && !empty($toUserId)) {
                $stmt = $pdo->prepare("
                    UPDATE call_sessions 
                    SET " . implode(', ', $updateFields) . "
                    WHERE (
                        (caller_id = ? AND receiver_id = ?) OR
                        (caller_id = ? AND receiver_id = ?)
                    )
                    AND status IN ('initiated', 'ringing', 'accepted')
                    ORDER BY started_at DESC
                    LIMIT 1
                ");
                $updateParamsForFromTo = array_merge($updateParams, [$fromUserId, $toUserId, $toUserId, $fromUserId]);
                $stmt->execute($updateParamsForFromTo);
                if ($stmt->rowCount() > 0) {
                    $updated = true;
                    error_log('Updated call session by from/to: ' . $fromUserId . ' -> ' . $toUserId . ' -> status: ' . $status);
                }
            }
            
            // Ưu tiên 3: Update call session mới nhất nếu vẫn chưa update
            if (!$updated) {
                $stmt = $pdo->prepare("
                    UPDATE call_sessions 
                    SET " . implode(', ', $updateFields) . "
                    WHERE status IN ('initiated', 'ringing', 'accepted')
                    AND started_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                    ORDER BY started_at DESC
                    LIMIT 1
                ");
                $stmt->execute($updateParams);
                if ($stmt->rowCount() > 0) {
                    $updated = true;
                    error_log('Updated latest call session -> status: ' . $status);
                }
            }
            
            if (!$updated) {
                error_log('Warning: No call session found to update. CallId: ' . ($callId ?: 'empty') . ', From: ' . ($fromUserId ?: 'empty') . ', To: ' . ($toUserId ?: 'empty'));
            }
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
