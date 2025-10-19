<?php
/**
 * Dynamic Pricing System
 * Tính toán giá biến động theo thời gian
 */

require_once __DIR__ . '/../../config/database.php';

class DynamicPricing {
    
    // Hệ số tăng giá theo thời gian trong ngày
    const TIME_MULTIPLIERS = [
        'morning' => 1.0,      // 6:00 - 12:00: Giá gốc
        'afternoon' => 1.1,    // 12:00 - 18:00: +10%
        'evening' => 1.25,     // 18:00 - 22:00: +25%
        'night' => 1.3         // 22:00 - 6:00: +30%
    ];
    
    // Hệ số tăng giá theo ngày trong tuần
    const DAY_MULTIPLIERS = [
        'weekday' => 1.0,      // Thứ 2-6: Giá gốc
        'weekend' => 1.2,      // Thứ 7, CN: +20%
        'holiday' => 1.4       // Ngày lễ: +40%
    ];
    
    // Danh sách ngày lễ Việt Nam (năm 2025)
    const HOLIDAYS_2025 = [
        '2025-01-01', // Tết Dương lịch
        '2025-01-28', // Tết Nguyên đán (bắt đầu)
        '2025-01-29', // Tết Nguyên đán
        '2025-01-30', // Tết Nguyên đán
        '2025-01-31', // Tết Nguyên đán
        '2025-02-01', // Tết Nguyên đán
        '2025-02-02', // Tết Nguyên đán
        '2025-02-03', // Tết Nguyên đán (kết thúc)
        '2025-04-18', // Giỗ Tổ Hùng Vương
        '2025-04-30', // Ngày Giải phóng miền Nam
        '2025-05-01', // Ngày Quốc tế Lao động
        '2025-09-02', // Quốc khánh
        '2025-12-25'  // Giáng sinh
    ];
    
    /**
     * Tính giá động cho địa điểm
     */
    public static function calculateLocationPrice($basePrice, $startDateTime, $endDateTime = null) {
        $startTime = new DateTime($startDateTime);
        $endTime = $endDateTime ? new DateTime($endDateTime) : clone $startTime;
        $endTime->add(new DateInterval('PT4H')); // Mặc định 4 giờ nếu không có end time
        
        $totalPrice = 0;
        $currentTime = clone $startTime;
        
        // Tính giá theo từng giờ
        while ($currentTime < $endTime) {
            $hourPrice = self::calculateHourlyPrice($basePrice, $currentTime);
            $totalPrice += $hourPrice;
            $currentTime->add(new DateInterval('PT1H'));
        }
        
        return round($totalPrice);
    }
    
    /**
     * Tính giá cho 1 giờ cụ thể
     */
    private static function calculateHourlyPrice($basePrice, $datetime) {
        $timeMultiplier = self::getTimeMultiplier($datetime);
        $dayMultiplier = self::getDayMultiplier($datetime);
        
        return $basePrice * $timeMultiplier * $dayMultiplier;
    }
    
    /**
     * Lấy hệ số theo thời gian trong ngày
     */
    private static function getTimeMultiplier($datetime) {
        $hour = (int)$datetime->format('H');
        
        if ($hour >= 6 && $hour < 12) {
            return self::TIME_MULTIPLIERS['morning'];
        } elseif ($hour >= 12 && $hour < 18) {
            return self::TIME_MULTIPLIERS['afternoon'];
        } elseif ($hour >= 18 && $hour < 22) {
            return self::TIME_MULTIPLIERS['evening'];
        } else {
            return self::TIME_MULTIPLIERS['night'];
        }
    }
    
    /**
     * Lấy hệ số theo ngày trong tuần
     */
    private static function getDayMultiplier($datetime) {
        $dateString = $datetime->format('Y-m-d');
        
        // Kiểm tra ngày lễ
        if (in_array($dateString, self::HOLIDAYS_2025)) {
            return self::DAY_MULTIPLIERS['holiday'];
        }
        
        // Kiểm tra cuối tuần
        $dayOfWeek = (int)$datetime->format('w'); // 0 = CN, 6 = T7
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            return self::DAY_MULTIPLIERS['weekend'];
        }
        
        return self::DAY_MULTIPLIERS['weekday'];
    }
    
    /**
     * Lấy thông tin chi tiết về giá
     */
    public static function getPricingDetails($basePrice, $startDateTime, $endDateTime = null) {
        $startTime = new DateTime($startDateTime);
        $endTime = $endDateTime ? new DateTime($endDateTime) : clone $startTime;
        $endTime->add(new DateInterval('PT4H'));
        
        $details = [
            'base_price' => $basePrice,
            'start_time' => $startTime->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'duration_hours' => $startTime->diff($endTime)->h,
            'time_breakdown' => [],
            'total_price' => 0,
            'savings' => 0
        ];
        
        $currentTime = clone $startTime;
        $totalPrice = 0;
        
        while ($currentTime < $endTime) {
            $timeMultiplier = self::getTimeMultiplier($currentTime);
            $dayMultiplier = self::getDayMultiplier($currentTime);
            $hourPrice = $basePrice * $timeMultiplier * $dayMultiplier;
            
            $details['time_breakdown'][] = [
                'time' => $currentTime->format('H:i'),
                'date' => $currentTime->format('Y-m-d'),
                'day_type' => self::getDayType($currentTime),
                'time_type' => self::getTimeType($currentTime),
                'multiplier' => $timeMultiplier * $dayMultiplier,
                'price' => round($hourPrice)
            ];
            
            $totalPrice += $hourPrice;
            $currentTime->add(new DateInterval('PT1H'));
        }
        
        $details['total_price'] = round($totalPrice);
        $details['savings'] = max(0, $basePrice * $details['duration_hours'] - $totalPrice);
        
        return $details;
    }
    
    /**
     * Lấy loại ngày
     */
    private static function getDayType($datetime) {
        $dateString = $datetime->format('Y-m-d');
        
        if (in_array($dateString, self::HOLIDAYS_2025)) {
            return 'holiday';
        }
        
        $dayOfWeek = (int)$datetime->format('w');
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            return 'weekend';
        }
        
        return 'weekday';
    }
    
    /**
     * Lấy loại thời gian
     */
    private static function getTimeType($datetime) {
        $hour = (int)$datetime->format('H');
        
        if ($hour >= 6 && $hour < 12) return 'morning';
        if ($hour >= 12 && $hour < 18) return 'afternoon';
        if ($hour >= 18 && $hour < 22) return 'evening';
        return 'night';
    }
    
    /**
     * Tính giá combo (giữ nguyên)
     */
    public static function calculateComboPrice($basePrice) {
        return $basePrice; // Combo không thay đổi giá
    }
    
    /**
     * Tính giá thiết bị (giữ nguyên)
     */
    public static function calculateEquipmentPrice($basePrice) {
        return $basePrice; // Thiết bị không thay đổi giá
    }
    
    /**
     * Lấy gợi ý thời gian tiết kiệm
     */
    public static function getSavingsSuggestions($basePrice, $preferredDate) {
        $suggestions = [];
        $date = new DateTime($preferredDate);
        
        // Gợi ý thời gian trong ngày
        $morningPrice = $basePrice * self::TIME_MULTIPLIERS['morning'];
        $afternoonPrice = $basePrice * self::TIME_MULTIPLIERS['afternoon'];
        $eveningPrice = $basePrice * self::TIME_MULTIPLIERS['evening'];
        
        $suggestions[] = [
            'time' => '6:00 - 12:00',
            'type' => 'morning',
            'price' => round($morningPrice),
            'savings' => round($eveningPrice - $morningPrice),
            'description' => 'Buổi sáng - Tiết kiệm nhất'
        ];
        
        $suggestions[] = [
            'time' => '12:00 - 18:00',
            'type' => 'afternoon',
            'price' => round($afternoonPrice),
            'savings' => round($eveningPrice - $afternoonPrice),
            'description' => 'Buổi chiều - Giá hợp lý'
        ];
        
        // Gợi ý ngày trong tuần
        $weekdayMultiplier = self::DAY_MULTIPLIERS['weekday'];
        $weekendMultiplier = self::DAY_MULTIPLIERS['weekend'];
        
        if ($weekendMultiplier > $weekdayMultiplier) {
            $suggestions[] = [
                'time' => 'Thứ 2-6',
                'type' => 'weekday',
                'price' => round($basePrice * $weekdayMultiplier),
                'savings' => round($basePrice * ($weekendMultiplier - $weekdayMultiplier)),
                'description' => 'Ngày thường - Tiết kiệm hơn cuối tuần'
            ];
        }
        
        return $suggestions;
    }
}

// API endpoint để tính giá động
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'calculate_location_price':
                $basePrice = $input['base_price'];
                $startDateTime = $input['start_datetime'];
                $endDateTime = $input['end_datetime'] ?? null;
                
                $result = DynamicPricing::calculateLocationPrice($basePrice, $startDateTime, $endDateTime);
                echo json_encode(['success' => true, 'price' => $result]);
                break;
                
            case 'get_pricing_details':
                $basePrice = $input['base_price'];
                $startDateTime = $input['start_datetime'];
                $endDateTime = $input['end_datetime'] ?? null;
                
                $result = DynamicPricing::getPricingDetails($basePrice, $startDateTime, $endDateTime);
                echo json_encode(['success' => true, 'details' => $result]);
                break;
                
            case 'get_savings_suggestions':
                $basePrice = $input['base_price'];
                $preferredDate = $input['preferred_date'];
                
                $result = DynamicPricing::getSavingsSuggestions($basePrice, $preferredDate);
                echo json_encode(['success' => true, 'suggestions' => $result]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
