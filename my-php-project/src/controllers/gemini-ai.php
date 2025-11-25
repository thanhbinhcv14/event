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

try {
    $pdo = getDBConnection();
    
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
    $message = $_POST['message'] ?? '';
    $conversationHistory = json_decode($_POST['history'] ?? '[]', true);
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu tin nhắn']);
        return;
    }
    
    try {
        // Lấy thông tin hệ thống từ database
        $systemInfo = getSystemInfoForAI($pdo);
        
        // Tạo prompt với context từ database
        $prompt = buildPrompt($message, $conversationHistory, $systemInfo);
        
        // Gọi Gemini AI API
        $response = callGeminiAPI($prompt);
        
        if ($response['success']) {
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
        
        // Lấy địa điểm với các trường địa chỉ mới
        $stmt = $pdo->query("
            SELECT ID_DD, TenDiaDiem, LoaiDiaDiem, DiaChi, SoNha, DuongPho, 
                   PhuongXa, QuanHuyen, TinhThanh, SucChua, 
                   GiaThueGio, GiaThueNgay, LoaiThue, MoTa
            FROM diadiem 
            WHERE TrangThaiHoatDong = 'Hoạt động'
            ORDER BY TenDiaDiem ASC
        ");
        $info['locations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy thông tin phòng cho các địa điểm trong nhà
        $stmt = $pdo->query("
            SELECT ID_Phong, ID_DD, TenPhong, SucChua, GiaThueGio, GiaThueNgay, 
                   LoaiThue, MoTa, TrangThai
            FROM phong 
            WHERE TrangThai = 'Sẵn sàng'
            ORDER BY ID_DD ASC, TenPhong ASC
        ");
        $allRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nhóm phòng theo ID_DD
        $info['rooms'] = [];
        foreach ($allRooms as $room) {
            $locationId = $room['ID_DD'];
            if (!isset($info['rooms'][$locationId])) {
                $info['rooms'][$locationId] = [];
            }
            $info['rooms'][$locationId][] = $room;
        }
        
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
    
    // Địa điểm - Hiển thị nhiều hơn để AI có đủ thông tin tư vấn
    $locationsToShow = $systemInfo['locations'] ?? [];
    $systemContext .= "CÁC ĐỊA ĐIỂM CÓ SẴN (Tổng: " . count($locationsToShow) . " địa điểm):\n";
    // Hiển thị tối đa 30 địa điểm để đủ thông tin tư vấn
    if (count($locationsToShow) > 30) {
        $systemContext .= "(Hiển thị 30 địa điểm đầu tiên, còn " . (count($locationsToShow) - 30) . " địa điểm khác)\n";
        $locationsToShow = array_slice($locationsToShow, 0, 30);
    }
    foreach ($locationsToShow as $location) {
        $systemContext .= "- {$location['TenDiaDiem']} ({$location['LoaiDiaDiem']})\n";
        
        // Hiển thị địa chỉ đầy đủ (từ DiaChi hoặc tự tạo từ các thành phần)
        $address = $location['DiaChi'] ?? '';
        if (empty($address)) {
            // Tự tạo địa chỉ từ các thành phần nếu DiaChi trống
            $addressParts = [];
            if (!empty($location['SoNha'])) $addressParts[] = $location['SoNha'];
            if (!empty($location['DuongPho'])) $addressParts[] = $location['DuongPho'];
            if (!empty($location['PhuongXa'])) $addressParts[] = $location['PhuongXa'];
            if (!empty($location['QuanHuyen'])) $addressParts[] = $location['QuanHuyen'];
            if (!empty($location['TinhThanh'])) $addressParts[] = $location['TinhThanh'];
            $address = implode(', ', $addressParts);
        }
        
        $systemContext .= "  Địa chỉ: {$address}\n";
        
        // Hiển thị thông tin chi tiết địa chỉ nếu có
        if (!empty($location['QuanHuyen']) || !empty($location['TinhThanh'])) {
            $detailParts = [];
            if (!empty($location['QuanHuyen'])) $detailParts[] = $location['QuanHuyen'];
            if (!empty($location['TinhThanh'])) $detailParts[] = $location['TinhThanh'];
            if (!empty($detailParts)) {
                $systemContext .= "  Khu vực: " . implode(', ', $detailParts) . "\n";
            }
        }
        
        $systemContext .= "  Sức chứa: {$location['SucChua']} người\n";
        
        // Chỉ hiển thị giá thuê cho địa điểm ngoài trời (địa điểm trong nhà có giá thuê = null)
        if ($location['LoaiDiaDiem'] === 'Ngoài trời') {
            if (!empty($location['GiaThueGio'])) {
                $systemContext .= "  Giá thuê theo giờ: " . number_format($location['GiaThueGio'], 0, ',', '.') . " VNĐ/giờ\n";
            }
            if (!empty($location['GiaThueNgay'])) {
                $systemContext .= "  Giá thuê theo ngày: " . number_format($location['GiaThueNgay'], 0, ',', '.') . " VNĐ/ngày\n";
            }
            if (!empty($location['LoaiThue'])) {
                $systemContext .= "  Loại thuê: {$location['LoaiThue']}\n";
            }
        } else {
            // Địa điểm trong nhà - hiển thị danh sách phòng
            $locationId = $location['ID_DD'];
            $rooms = $systemInfo['rooms'][$locationId] ?? [];
            
            if (!empty($rooms)) {
                $systemContext .= "  Các phòng có sẵn:\n";
                foreach ($rooms as $room) {
                    $systemContext .= "    • {$room['TenPhong']}\n";
                    $systemContext .= "      - Sức chứa: {$room['SucChua']} người\n";
                    
                    // Hiển thị giá thuê theo loại thuê
                    if ($room['LoaiThue'] === 'Theo giờ' || $room['LoaiThue'] === 'Cả hai') {
                        if (!empty($room['GiaThueGio'])) {
                            $systemContext .= "      - Giá thuê theo giờ: " . number_format($room['GiaThueGio'], 0, ',', '.') . " VNĐ/giờ\n";
                        }
                    }
                    if ($room['LoaiThue'] === 'Theo ngày' || $room['LoaiThue'] === 'Cả hai') {
                        if (!empty($room['GiaThueNgay'])) {
                            $systemContext .= "      - Giá thuê theo ngày: " . number_format($room['GiaThueNgay'], 0, ',', '.') . " VNĐ/ngày\n";
                        }
                    }
                    
                    if (!empty($room['MoTa'])) {
                        $systemContext .= "      - Mô tả: {$room['MoTa']}\n";
                    }
                    $systemContext .= "\n";
                }
            } else {
                $systemContext .= "  Lưu ý: Địa điểm trong nhà có các phòng riêng, giá thuê được tính theo từng phòng (chưa có thông tin phòng chi tiết)\n";
            }
        }
        
        if (!empty($location['MoTa'])) {
            $systemContext .= "  Mô tả: {$location['MoTa']}\n";
        }
        
        $systemContext .= "\n";
    }
    
    // Thiết bị - Hiển thị đầy đủ để AI có thể tư vấn
    $systemContext .= "THIẾT BỊ CÓ SẴN (Tổng: " . count($systemInfo['equipment'] ?? []) . " thiết bị):\n";
    $equipmentByType = [];
    foreach ($systemInfo['equipment'] ?? [] as $equip) {
        $type = $equip['LoaiThietBi'] ?? 'Khác';
        if (!isset($equipmentByType[$type])) {
            $equipmentByType[$type] = [];
        }
        $equipmentByType[$type][] = $equip;
    }
    foreach ($equipmentByType as $type => $items) {
        $systemContext .= "- {$type} (" . count($items) . " thiết bị):\n";
        // Hiển thị tối đa 10 thiết bị mỗi loại
        foreach (array_slice($items, 0, 10) as $item) {
            $systemContext .= "  + {$item['TenThietBi']} - " . number_format($item['GiaThue'] ?? 0, 0, ',', '.') . " VNĐ";
            if (!empty($item['MoTa'])) {
                $systemContext .= " ({$item['MoTa']})";
            }
            $systemContext .= "\n";
        }
        if (count($items) > 10) {
            $systemContext .= "  ... và " . (count($items) - 10) . " thiết bị khác\n";
        }
    }
    $systemContext .= "\n";
    
    // Combo
    if (!empty($systemInfo['combos'])) {
        $systemContext .= "COMBO DỊCH VỤ (Tiết kiệm hơn khi thuê riêng lẻ):\n";
        foreach (array_slice($systemInfo['combos'] ?? [], 0, 10) as $combo) {
            $systemContext .= "- {$combo['TenCombo']} (" . number_format($combo['GiaCombo'], 0, ',', '.') . " VNĐ)\n";
            if (!empty($combo['MoTa'])) {
                $systemContext .= "  Mô tả: {$combo['MoTa']}\n";
            }
            // Hiển thị thiết bị trong combo nếu có
            if (!empty($combo['equipment'])) {
                $systemContext .= "  Bao gồm: ";
                $equipList = [];
                foreach ($combo['equipment'] as $eq) {
                    $equipList[] = $eq['TenThietBi'] . ($eq['SoLuong'] > 1 ? " x{$eq['SoLuong']}" : "");
                }
                $systemContext .= implode(', ', $equipList) . "\n";
            }
        }
        $systemContext .= "\n";
    }
    
    // Mã giảm giá
    if (!empty($systemInfo['discount_codes'])) {
        $systemContext .= "MÃ GIẢM GIÁ ĐANG ÁP DỤNG:\n";
        foreach ($systemInfo['discount_codes'] as $code) {
            $discountText = '';
            if ($code['LoaiGiamGia'] === 'Phần trăm') {
                $discountText = "Giảm {$code['GiaTriGiamGia']}%";
            } else {
                $discountText = "Giảm " . number_format($code['GiaTriGiamGia'], 0, ',', '.') . " VNĐ";
            }
            
            $systemContext .= "- Mã: {$code['MaCode']} - {$code['TenMa']}\n";
            $systemContext .= "  {$discountText}";
            
            if ($code['SoTienToiThieu'] > 0) {
                $systemContext .= " (Áp dụng cho đơn hàng từ " . number_format($code['SoTienToiThieu'], 0, ',', '.') . " VNĐ)";
            }
            
            if (!empty($code['MoTa'])) {
                $systemContext .= "\n  Mô tả: {$code['MoTa']}";
            }
            
            $systemContext .= "\n";
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
    
    $fullPrompt .= "HƯỚNG DẪN TƯ VẤN VÀ ĐỀ XUẤT (QUAN TRỌNG - PHẢI TUÂN THEO):\n";
    $fullPrompt .= "1. Khi khách hàng hỏi về đăng ký sự kiện hoặc tư vấn, hãy:\n";
    $fullPrompt .= "   - Thu thập thông tin: loại sự kiện, số người, ngày giờ, địa điểm mong muốn, ngân sách\n";
    $fullPrompt .= "   - Đưa ra gợi ý CỤ THỂ dựa trên thông tin hệ thống (PHẢI dùng dữ liệu thực tế từ database):\n";
    $fullPrompt .= "     + Địa điểm: Chọn địa điểm có sức chứa phù hợp với số người (ưu tiên địa điểm có sức chứa >= số người)\n";
    $fullPrompt .= "       * Nêu TÊN ĐỊA ĐIỂM cụ thể, địa chỉ, sức chứa, giá thuê (theo giờ/ngày)\n";
    $fullPrompt .= "       * Nếu địa điểm trong nhà, đề xuất PHÒNG CỤ THỂ với tên phòng, sức chứa, giá thuê\n";
    $fullPrompt .= "     + Thiết bị: Đề xuất thiết bị CỤ THỂ dựa trên loại sự kiện:\n";
    $fullPrompt .= "       * Hội thảo: micro, loa, máy chiếu, bàn ghế, màn chiếu\n";
    $fullPrompt .= "       * Tiệc sinh nhật: âm thanh, ánh sáng, bàn ghế, trang trí\n";
    $fullPrompt .= "       * Đám cưới: âm thanh, ánh sáng, trang trí, bàn tiệc\n";
    $fullPrompt .= "       * Nêu TÊN THIẾT BỊ cụ thể và GIÁ THUÊ từ database\n";
    $fullPrompt .= "     + Combo: Nếu có combo phù hợp, đề xuất để tiết kiệm chi phí (nêu tên combo và giá)\n";
    $fullPrompt .= "   - Tính toán chi phí ước tính CHI TIẾT:\n";
    $fullPrompt .= "     + Giá cơ bản loại sự kiện (từ database)\n";
    $fullPrompt .= "     + Giá thuê địa điểm/phòng (theo giờ hoặc ngày tùy loại, từ database)\n";
    $fullPrompt .= "     + Giá thuê thiết bị (từng thiết bị hoặc giá combo nếu đề xuất combo, từ database)\n";
    $fullPrompt .= "     + Tổng chi phí = Giá cơ bản + Giá địa điểm + Giá thiết bị\n";
    $fullPrompt .= "     + Đề xuất mã giảm giá nếu có và phù hợp (kiểm tra số tiền tối thiểu)\n";
    $fullPrompt .= "     + Tính tổng chi phí sau giảm giá (nếu có mã giảm giá)\n";
    $fullPrompt .= "     + Hiển thị kết quả theo định dạng: \"Tổng chi phí ước tính: X.XXX.XXX VNĐ\"\n";
    $fullPrompt .= "   - Đề xuất đặt sự kiện ngay khi đã có đủ thông tin\n\n";
    
    $fullPrompt .= "2. Khi khách hàng cung cấp thông tin sự kiện (ví dụ: 'Tôi có 10tr cần đặt phòng hội thảo' hoặc 'Tôi muốn tổ chức tiệc sinh nhật cho 50 người'), hãy:\n";
    $fullPrompt .= "   - Xác nhận lại thông tin đầy đủ: loại sự kiện, số người, ngày giờ, địa điểm mong muốn (nếu có), ngân sách (nếu có)\n";
    $fullPrompt .= "   - Đề xuất địa điểm/phòng phù hợp CỤ THỂ:\n";
    $fullPrompt .= "     + Tìm địa điểm có sức chứa >= số người (ưu tiên địa điểm có sức chứa gần với số người nhất)\n";
    $fullPrompt .= "     + Nếu địa điểm trong nhà, đề xuất PHÒNG CỤ THỂ có sức chứa phù hợp (nêu tên phòng, sức chứa, giá)\n";
    $fullPrompt .= "     + Nêu rõ: Tên địa điểm, địa chỉ, sức chứa, giá thuê (theo giờ/ngày)\n";
    $fullPrompt .= "     + Nếu khách hàng có ngân sách, chỉ đề xuất địa điểm/phòng phù hợp với ngân sách\n";
    $fullPrompt .= "   - Đề xuất thiết bị cần thiết CỤ THỂ:\n";
    $fullPrompt .= "     + Dựa trên loại sự kiện: hội thảo cần micro, loa, máy chiếu, bàn ghế\n";
    $fullPrompt .= "     + Đề xuất combo nếu có combo phù hợp (tiết kiệm hơn thuê riêng lẻ)\n";
    $fullPrompt .= "     + Nêu rõ: Tên thiết bị, giá thuê (hoặc tên combo, giá combo)\n";
    $fullPrompt .= "   - Tính toán chi phí ước tính CHI TIẾT:\n";
    $fullPrompt .= "     + Liệt kê từng khoản:\n";
    $fullPrompt .= "       • Giá cơ bản loại sự kiện: X.XXX.XXX VNĐ\n";
    $fullPrompt .= "       • Giá thuê địa điểm/phòng: X.XXX.XXX VNĐ\n";
    $fullPrompt .= "       • Giá thuê thiết bị/combo: X.XXX.XXX VNĐ\n";
    $fullPrompt .= "       • Tổng chi phí: X.XXX.XXX VNĐ\n";
    $fullPrompt .= "     + Kiểm tra mã giảm giá có thể áp dụng (nếu tổng >= số tiền tối thiểu)\n";
    $fullPrompt .= "     + Nếu có mã giảm giá: Giảm X% hoặc X.XXX.XXX VNĐ\n";
    $fullPrompt .= "     + Tổng sau giảm giá: X.XXX.XXX VNĐ\n";
    $fullPrompt .= "   - Mạnh dạn đề xuất: 'Bạn có muốn tôi giúp bạn đăng ký sự kiện này ngay không? Tôi có thể hướng dẫn bạn từng bước.'\n\n";
    
    $fullPrompt .= "3. Khi khách hàng hỏi về giá cả hoặc dịch vụ, hãy:\n";
    $fullPrompt .= "   - Cung cấp thông tin giá CỤ THỂ từ database:\n";
    $fullPrompt .= "     + Giá cơ bản các loại sự kiện\n";
    $fullPrompt .= "     + Giá thuê địa điểm/phòng (theo giờ hoặc ngày)\n";
    $fullPrompt .= "     + Giá thuê thiết bị (theo loại)\n";
    $fullPrompt .= "     + Giá combo (nếu có, thường rẻ hơn thuê riêng lẻ)\n";
    $fullPrompt .= "   - Giới thiệu mã giảm giá đang có (nếu có)\n";
    $fullPrompt .= "   - So sánh các gói dịch vụ nếu có\n";
    $fullPrompt .= "   - Đề xuất: 'Bạn có muốn tôi giúp bạn tính toán chi phí cho sự kiện không? Chỉ cần cho tôi biết loại sự kiện và số người tham dự, tôi sẽ tính toán chi tiết cho bạn.'\n\n";
    
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
    // Lấy API key từ config (bảo mật hơn)
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : 'AIzaSyAT4nAOSfEwiO-8DozSXwncJ1rIj-nmpVk';
    
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
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        try {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
        } finally {
            if (is_resource($ch)) { 
                @curl_close($ch); 
            }
            unset($ch);
        }
        
        if ($error) {
            error_log("Gemini API CURL Error (model {$model}): " . $error);
            $lastError = 'Lỗi kết nối API: ' . $error;
            continue; // Thử model tiếp theo
        }
        
        if ($httpCode === 200) {
            // Thành công!
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

