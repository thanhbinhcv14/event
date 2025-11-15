<?php
/**
 * Chat Controller - Hỗ trợ trực tuyến
 * Tích hợp với hệ thống để hỗ trợ chat thông minh với database
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi tải file: ' . $e->getMessage()]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Debug: Log request info
error_log('Gemini AI API called - Action: ' . $action);
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
error_log('POST data: ' . print_r($_POST, true));
error_log('GET data: ' . print_r($_GET, true));

try {
    $pdo = getDBConnection();
    error_log('Database connection successful');
    
    switch ($action) {
        case 'chat':
            handleChat($pdo);
            break;
            
        case 'get_system_info':
            getSystemInfo($pdo);
            break;
            
        default:
            error_log('Invalid action: ' . $action);
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    error_log('Gemini AI Controller error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}

/**
 * Xử lý chat với Gemini AI
 */
function handleChat($pdo) {
    // Log để debug
    error_log('Chat request received');
    error_log('POST data: ' . print_r($_POST, true));
    
    $message = $_POST['message'] ?? '';
    $conversationHistory = json_decode($_POST['history'] ?? '[]', true);
    
    if (empty($message)) {
        error_log('Error: Empty message');
        echo json_encode(['success' => false, 'error' => 'Thiếu tin nhắn']);
        return;
    }
    
    error_log('Processing message: ' . $message);
    error_log('History count: ' . count($conversationHistory));
    
    try {
        // Lấy thông tin hệ thống từ database
        error_log('Getting system info...');
        $systemInfo = getSystemInfoForAI($pdo);
        error_log('System info retrieved: ' . count($systemInfo['event_types']) . ' event types, ' . count($systemInfo['locations']) . ' locations');
        
        // Tạo prompt với context từ database
        error_log('Building prompt...');
        $prompt = buildPrompt($message, $conversationHistory, $systemInfo);
        error_log('Prompt length: ' . strlen($prompt) . ' characters');
        
        // Gọi Gemini AI API
        error_log('Calling Gemini API...');
        $response = callGeminiAPI($prompt);
        
        if ($response['success']) {
            error_log('Gemini API success');
            echo json_encode([
                'success' => true,
                'message' => $response['message'],
                'suggestions' => $response['suggestions'] ?? []
            ]);
        } else {
            error_log('Gemini API error: ' . ($response['error'] ?? 'Unknown error'));
            echo json_encode([
                'success' => false,
                'error' => $response['error'] ?? 'Lỗi khi gọi Gemini AI'
            ]);
        }
    } catch (Exception $e) {
        error_log('Exception in handleChat: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi xử lý: ' . $e->getMessage()
        ]);
    }
}

/**
 * Lấy thông tin hệ thống từ database
 */
function getSystemInfoForAI($pdo) {
    $info = [];
    
    try {
        // Lấy loại sự kiện
        $stmt = $pdo->query("
            SELECT ID_LoaiSK, TenLoai, MoTa, GiaCoBan 
            FROM loaisukien 
            ORDER BY TenLoai ASC
        ");
        $info['event_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy địa điểm
        $stmt = $pdo->query("
            SELECT ID_DD, TenDiaDiem, LoaiDiaDiem, DiaChi, SucChua, 
                   GiaThueGio, GiaThueNgay, LoaiThue, MoTa
            FROM diadiem 
            WHERE TrangThaiHoatDong = 'Hoạt động'
            ORDER BY TenDiaDiem ASC
        ");
        $info['locations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy thiết bị
        $stmt = $pdo->query("
            SELECT ID_TB, TenThietBi, LoaiThietBi, MoTa, GiaThue, TrangThai
            FROM thietbi 
            WHERE TrangThai = 'Sẵn sàng'
            ORDER BY TenThietBi ASC
        ");
        $info['equipment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy combo
        $stmt = $pdo->query("
            SELECT ID_Combo, TenCombo, MoTa, GiaCombo, TrangThai
            FROM combo 
            WHERE TrangThai = 'Hoạt động'
            ORDER BY TenCombo ASC
        ");
        $info['combos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy thống kê sự kiện
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_events,
                COUNT(CASE WHEN TrangThaiSuKien = 'Đã duyệt' THEN 1 END) as approved_events,
                COUNT(CASE WHEN TrangThaiSuKien = 'Chờ duyệt' THEN 1 END) as pending_events
            FROM datlichsukien
        ");
        $info['stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting system info: " . $e->getMessage());
    }
    
    return $info;
}

/**
 * Xây dựng prompt cho Gemini AI
 */
function buildPrompt($message, $conversationHistory, $systemInfo) {
    $systemContext = "Bạn là nhân viên tư vấn chuyên nghiệp của hệ thống quản lý sự kiện. Nhiệm vụ của bạn là:\n\n";
    $systemContext .= "1. Hỗ trợ khách hàng đăng ký sự kiện\n";
    $systemContext .= "2. Tư vấn về dịch vụ, địa điểm, thiết bị\n";
    $systemContext .= "3. Tính toán chi phí và đưa ra gợi ý tối ưu\n";
    $systemContext .= "4. Hướng dẫn sử dụng hệ thống\n\n";
    $systemContext .= "QUAN TRỌNG VỀ XƯNG HÔ:\n";
    $systemContext .= "- Bạn phải xưng 'tôi' và gọi khách hàng là 'bạn'\n";
    $systemContext .= "- Sử dụng ngôn ngữ thân thiện, tự nhiên như nhân viên tư vấn thật sự\n";
    $systemContext .= "- Luôn dùng 'bạn' để gọi khách hàng và 'tôi' để tự xưng\n";
    $systemContext .= "- KHÔNG tự nhận mình là AI hay robot. Hãy nói chuyện như một con người đang hỗ trợ khách hàng.\n\n";
    
    // Thông tin hệ thống
    $systemContext .= "THÔNG TIN HỆ THỐNG:\n\n";
    
    // Loại sự kiện
    $systemContext .= "CÁC LOẠI SỰ KIỆN CÓ SẴN:\n";
    foreach ($systemInfo['event_types'] ?? [] as $type) {
        $systemContext .= "- {$type['TenLoai']} (Giá cơ bản: " . number_format($type['GiaCoBan'], 0, ',', '.') . " VNĐ)\n";
        if (!empty($type['MoTa'])) {
            $systemContext .= "  Mô tả: {$type['MoTa']}\n";
        }
    }
    $systemContext .= "\n";
    
    // Địa điểm
    $systemContext .= "CÁC ĐỊA ĐIỂM CÓ SẴN:\n";
    foreach (array_slice($systemInfo['locations'] ?? [], 0, 10) as $location) {
        $systemContext .= "- {$location['TenDiaDiem']} ({$location['LoaiDiaDiem']})\n";
        $systemContext .= "  Địa chỉ: {$location['DiaChi']}\n";
        $systemContext .= "  Sức chứa: {$location['SucChua']} người\n";
        if ($location['GiaThueGio']) {
            $systemContext .= "  Giá thuê theo giờ: " . number_format($location['GiaThueGio'], 0, ',', '.') . " VNĐ/giờ\n";
        }
        if ($location['GiaThueNgay']) {
            $systemContext .= "  Giá thuê theo ngày: " . number_format($location['GiaThueNgay'], 0, ',', '.') . " VNĐ/ngày\n";
        }
        $systemContext .= "\n";
    }
    
    // Thiết bị
    $systemContext .= "THIẾT BỊ CÓ SẴN:\n";
    $equipmentByType = [];
    foreach ($systemInfo['equipment'] ?? [] as $equip) {
        $type = $equip['LoaiThietBi'] ?? 'Khác';
        if (!isset($equipmentByType[$type])) {
            $equipmentByType[$type] = [];
        }
        $equipmentByType[$type][] = $equip;
    }
    foreach ($equipmentByType as $type => $items) {
        $systemContext .= "- {$type}:\n";
        foreach (array_slice($items, 0, 5) as $item) {
            $systemContext .= "  + {$item['TenThietBi']}";
            if ($item['GiaThue']) {
                $systemContext .= " (" . number_format($item['GiaThue'], 0, ',', '.') . " VNĐ)";
            }
            $systemContext .= "\n";
        }
    }
    $systemContext .= "\n";
    
    // Combo
    if (!empty($systemInfo['combos'])) {
        $systemContext .= "COMBO DỊCH VỤ:\n";
        foreach (array_slice($systemInfo['combos'] ?? [], 0, 5) as $combo) {
            $systemContext .= "- {$combo['TenCombo']} (" . number_format($combo['GiaCombo'], 0, ',', '.') . " VNĐ)\n";
        }
        $systemContext .= "\n";
    }
    
    // Lịch sử hội thoại
    $historyText = "";
    if (!empty($conversationHistory)) {
        $historyText = "LỊCH SỬ HỘI THOẠI:\n";
        foreach (array_slice($conversationHistory, -5) as $msg) {
            $role = $msg['role'] ?? 'user';
            $content = $msg['content'] ?? '';
            $historyText .= ($role === 'user' ? 'Khách hàng' : 'AI') . ": {$content}\n";
        }
        $historyText .= "\n";
    }
    
    // Prompt cuối cùng
    $fullPrompt = $systemContext . "\n" . $historyText . "\n";
    $fullPrompt .= "CÂU HỎI CỦA KHÁCH HÀNG: {$message}\n\n";
    $fullPrompt .= "Hãy trả lời như một nhân viên tư vấn thật sự - tự nhiên, thân thiện, chuyên nghiệp và hữu ích. ";
    $fullPrompt .= "Sử dụng ngôn ngữ giao tiếp tự nhiên như đang nói chuyện trực tiếp với khách hàng.\n\n";
    
    $fullPrompt .= "HƯỚNG DẪN ĐỀ XUẤT ĐẶT SỰ KIỆN:\n";
    $fullPrompt .= "1. Khi khách hàng hỏi về đăng ký sự kiện, hãy:\n";
    $fullPrompt .= "   - Thu thập thông tin: loại sự kiện, số người, ngày giờ, địa điểm mong muốn, ngân sách\n";
    $fullPrompt .= "   - Đưa ra gợi ý cụ thể dựa trên thông tin hệ thống (địa điểm phù hợp, thiết bị cần thiết)\n";
    $fullPrompt .= "   - Tính toán chi phí ước tính dựa trên giá từ database\n";
    $fullPrompt .= "   - Đề xuất đặt sự kiện ngay khi đã có đủ thông tin\n\n";
    
    $fullPrompt .= "2. Khi khách hàng cung cấp thông tin sự kiện (ví dụ: 'Tôi muốn tổ chức tiệc sinh nhật cho 50 người vào ngày 15/1'), hãy:\n";
    $fullPrompt .= "   - Xác nhận lại thông tin\n";
    $fullPrompt .= "   - Đề xuất địa điểm phù hợp với số người\n";
    $fullPrompt .= "   - Đề xuất thiết bị cần thiết (âm thanh, ánh sáng, v.v.)\n";
    $fullPrompt .= "   - Tính toán chi phí ước tính\n";
    $fullPrompt .= "   - Mạnh dạn đề xuất: 'Bạn có muốn tôi giúp bạn đăng ký sự kiện này ngay không? Tôi có thể hướng dẫn bạn từng bước.'\n\n";
    
    $fullPrompt .= "3. Khi khách hàng hỏi về giá cả hoặc dịch vụ, hãy:\n";
    $fullPrompt .= "   - Cung cấp thông tin giá cụ thể từ database\n";
    $fullPrompt .= "   - So sánh các gói dịch vụ nếu có\n";
    $fullPrompt .= "   - Đề xuất: 'Bạn có muốn tôi giúp bạn tính toán chi phí cho sự kiện không? Chỉ cần cho tôi biết loại sự kiện và số người tham dự.'\n\n";
    
    $fullPrompt .= "4. Luôn kết thúc bằng cách:\n";
    $fullPrompt .= "   - Hỏi xem khách hàng có cần hỗ trợ gì thêm không\n";
    $fullPrompt .= "   - Đề xuất đặt sự kiện nếu phù hợp: 'Bạn có muốn đăng ký sự kiện ngay bây giờ không? Tôi có thể hướng dẫn bạn.'\n\n";
    
    $fullPrompt .= "KHÔNG sử dụng các từ như 'AI', 'robot', 'trợ lý AI', 'hệ thống AI'. Hãy nói chuyện như một con người bình thường.\n";
    $fullPrompt .= "Hãy chủ động đề xuất đặt sự kiện khi thấy khách hàng có nhu cầu, đừng chỉ trả lời câu hỏi một cách thụ động.";
    
    return $fullPrompt;
}

/**
 * Gọi Gemini AI API
 */
function callGeminiAPI($prompt) {
    $apiKey = 'AIzaSyDtCMBxxPV1ryIWhWR6oRsPhA8Pchi7rZ8';
    
    // Thử các model khác nhau nếu một model không hoạt động
    // Sắp xếp theo thứ tự ưu tiên: nhanh nhất và đã test thành công trước
    // Kết quả test: gemini-2.5-flash-lite (0.78s) nhanh hơn gemini-2.5-flash (1.84s)
    $models = [
        'gemini-2.5-flash-lite', // Model 2.5 nhanh nhất (0.78s) - ƯU TIÊN 1
        'gemini-2.5-flash',      // Model 2.5 nhanh (1.84s) - ƯU TIÊN 2
        'gemini-2.5-pro',        // Model 2.5 mạnh nhất (2025) - ƯU TIÊN 3
        'gemini-1.5-flash',      // Model 1.5 nhanh (fallback)
        'gemini-1.5-pro',        // Model 1.5 mạnh (fallback)
        'gemini-pro',            // Model cũ (fallback cuối cùng)
        'gemini-1.0-pro'         // Model 1.0 (fallback cuối cùng)
    ];
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 2048,
        ]
    ];
    
    $lastError = null;
    
    // Thử từng model cho đến khi thành công
    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        error_log("Trying Gemini API with model: {$model}");
        error_log("URL: {$url}");
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Gemini API CURL Error (model {$model}): " . $error);
            $lastError = 'Lỗi kết nối API: ' . $error;
            continue; // Thử model tiếp theo
        }
        
        error_log("Gemini API Response (model {$model}): HTTP {$httpCode}");
        
        if ($httpCode === 200) {
            // Thành công!
            error_log("Gemini API success with model: {$model}");
            $result = json_decode($response, true);
            
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $message = $result['candidates'][0]['content']['parts'][0]['text'];
                
                // Tạo gợi ý tự động dựa trên phản hồi và lịch sử
                $conversationHistory = json_decode($_POST['history'] ?? '[]', true);
                $suggestions = generateSuggestions($message, $conversationHistory);
                
                return [
                    'success' => true,
                    'message' => $message,
                    'suggestions' => $suggestions
                ];
            }
            
            error_log("Gemini API response structure unexpected: " . substr($response, 0, 500));
            $lastError = 'Không nhận được phản hồi từ AI';
        } else if ($httpCode === 404) {
            // Model không tồn tại, thử model tiếp theo
            error_log("Model {$model} not found (404), trying next model");
            $lastError = "Model {$model} không tồn tại";
            continue;
        } else {
            // Lỗi khác
            error_log("Gemini API HTTP Error (model {$model}): {$httpCode} - " . substr($response, 0, 500));
            $lastError = "Lỗi API: HTTP {$httpCode}";
            
            // Nếu là lỗi 400 (bad request) hoặc 403 (forbidden), không thử model khác
            if ($httpCode === 400 || $httpCode === 403) {
                break;
            }
            continue; // Thử model tiếp theo
        }
    }
    
    // Tất cả các model đều thất bại
    error_log("All Gemini API models failed. Last error: {$lastError}");
    return ['success' => false, 'error' => $lastError ?? 'Không thể kết nối với Gemini API'];
}

/**
 * Tạo gợi ý tự động dựa trên câu trả lời và lịch sử hội thoại
 */
function generateSuggestions($aiResponse, $conversationHistory = []) {
    $suggestions = [];
    $lowerResponse = mb_strtolower($aiResponse, 'UTF-8');
    
    // Phân tích lịch sử hội thoại để xác định nhu cầu
    $hasEventInfo = false;
    $eventKeywords = ['sự kiện', 'tiệc', 'hội nghị', 'đám cưới', 'sinh nhật', 'tổ chức'];
    foreach ($conversationHistory as $msg) {
        if ($msg['role'] === 'user') {
            $userMsg = mb_strtolower($msg['content'], 'UTF-8');
            foreach ($eventKeywords as $keyword) {
                if (strpos($userMsg, $keyword) !== false) {
                    $hasEventInfo = true;
                    break 2;
                }
            }
        }
    }
    
    // Kiểm tra xem AI có đề xuất đặt sự kiện không
    $suggestRegister = false;
    $registerKeywords = ['đăng ký', 'đặt sự kiện', 'tổ chức', 'đăng ký ngay', 'hướng dẫn bạn'];
    foreach ($registerKeywords as $keyword) {
        if (strpos($lowerResponse, $keyword) !== false) {
            $suggestRegister = true;
            break;
        }
    }
    
    // Nếu AI đề xuất đặt sự kiện hoặc khách hàng đã cung cấp thông tin sự kiện
    if ($suggestRegister || $hasEventInfo) {
        $suggestions[] = ['text' => 'Đăng ký sự kiện ngay', 'action' => 'register'];
    }
    
    // Gợi ý dựa trên nội dung phản hồi
    if (strpos($lowerResponse, 'giá') !== false || strpos($lowerResponse, 'chi phí') !== false || strpos($lowerResponse, 'tính toán') !== false) {
        if (!in_array('Đăng ký sự kiện ngay', array_column($suggestions, 'text'))) {
            $suggestions[] = ['text' => 'Tính toán chi phí', 'action' => 'pricing'];
        }
    }
    
    if (strpos($lowerResponse, 'địa điểm') !== false || strpos($lowerResponse, 'location') !== false || strpos($lowerResponse, 'nơi tổ chức') !== false) {
        $suggestions[] = ['text' => 'Xem danh sách địa điểm', 'action' => 'locations'];
    }
    
    if (strpos($lowerResponse, 'thiết bị') !== false || strpos($lowerResponse, 'equipment') !== false || strpos($lowerResponse, 'âm thanh') !== false || strpos($lowerResponse, 'ánh sáng') !== false) {
        $suggestions[] = ['text' => 'Xem thiết bị có sẵn', 'action' => 'equipment'];
    }
    
    // Nếu khách hàng hỏi về loại sự kiện
    if (strpos($lowerResponse, 'loại sự kiện') !== false || strpos($lowerResponse, 'dịch vụ') !== false) {
        $suggestions[] = ['text' => 'Xem tất cả dịch vụ', 'action' => 'pricing'];
    }
    
    // Gợi ý mặc định nếu chưa có gợi ý nào
    if (empty($suggestions)) {
        $suggestions = [
            ['text' => 'Đăng ký sự kiện', 'action' => 'register'],
            ['text' => 'Xem giá dịch vụ', 'action' => 'pricing'],
            ['text' => 'Xem địa điểm', 'action' => 'locations']
        ];
    }
    
    // Giới hạn số lượng gợi ý
    return array_slice($suggestions, 0, 6);
}

/**
 * Lấy thông tin hệ thống (API endpoint)
 */
function getSystemInfo($pdo) {
    $info = getSystemInfoForAI($pdo);
    echo json_encode(['success' => true, 'data' => $info]);
}
?>

