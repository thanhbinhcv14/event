<?php
// Set error reporting to prevent HTML errors from being displayed
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Include Socket.IO client
require_once __DIR__ . '/../socket/socket-client.php';

// Include CSRF protection
require_once __DIR__ . '/../auth/csrf.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Debug action
error_log("Debug - Action: " . $action);
error_log("Debug - Session data: " . print_r($_SESSION, true));

// For data loading actions, don't require login
$publicActions = ['get_csrf_token', 'get_event_types', 'get_locations_by_type', 'get_all_locations', 'get_equipment_suggestions', 'get_combo_suggestions', 'get_all_equipment', 'get_all_combos', 'get_event_selected_data', 'get_event_equipment', 'check_equipment_availability', 'check_combo_availability'];

// Xử lý get_csrf_token trước (không cần database)
if ($action === 'get_csrf_token') {
    echo json_encode([
        'success' => true,
        'csrf_token' => generateCSRFToken()
    ]);
    exit();
}

if (!in_array($action, $publicActions)) {
    // Check if user is logged in for other actions
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
        exit();
    }

    $user = $_SESSION['user'];
    $userId = $user['ID_User'] ?? $user['id'] ?? null;

    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'Thông tin người dùng không hợp lệ']);
        exit();
    }
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    // Debug database connection
    if (!$pdo) {
        error_log("Debug - Database connection failed");
        echo json_encode(['success' => false, 'error' => 'Lỗi kết nối database']);
        exit();
    }
    error_log("Debug - Database connection successful");
    
    switch ($action) {
            
        case 'get_event_types':
            // Get event types from database
            try {
                $stmt = $pdo->query("SELECT * FROM loaisukien ORDER BY TenLoai");
                $eventTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'event_types' => $eventTypes]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_locations_by_type':
            // Get locations suitable for specific event type
            $eventType = $_GET['event_type'] ?? '';
            
            if (!$eventType) {
                echo json_encode(['success' => false, 'error' => 'Thiếu thông tin loại sự kiện']);
                exit();
            }
            
            try {
                // Debug: Log the event type
                error_log("Debug - Event type: " . $eventType);
                
                // Get event type ID
                $stmt = $pdo->prepare("SELECT ID_LoaiSK FROM loaisukien WHERE TenLoai = ?");
                $stmt->execute([$eventType]);
                $eventTypeData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("Debug - Event type data: " . print_r($eventTypeData, true));
                
                if (!$eventTypeData) {
                    echo json_encode(['success' => false, 'error' => 'Loại sự kiện không hợp lệ: ' . $eventType]);
                    exit();
                }
                
                // Get locations suitable for this event type from diadiem_loaisk table
                $stmt = $pdo->prepare("
                    SELECT d.* 
                    FROM diadiem d
                    INNER JOIN diadiem_loaisk dl ON d.ID_DD = dl.ID_DD
                    WHERE dl.ID_LoaiSK = ? AND d.TrangThaiHoatDong = 'Hoạt động'
                    ORDER BY d.TenDiaDiem
                ");
                $stmt->execute([$eventTypeData['ID_LoaiSK']]);
                $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("Debug - Found locations: " . count($locations));
                error_log("Debug - Locations: " . print_r($locations, true));
                
                // If no specific locations found, get all active locations
                if (empty($locations)) {
                    error_log("Debug - No specific locations found, getting all active locations");
                    $stmt = $pdo->query("
                        SELECT * FROM diadiem 
                        WHERE TrangThaiHoatDong = 'Hoạt động' 
                        ORDER BY TenDiaDiem
                    ");
                    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("Debug - All active locations: " . count($locations));
                }
                
                echo json_encode(['success' => true, 'locations' => $locations]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_all_locations':
            // Get all active locations
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        ID_DD,
                        TenDiaDiem,
                        DiaChi,
                        SucChua,
                        GiaThueGio,
                        GiaThueNgay,
                        LoaiThue,
                        LoaiDiaDiem,
                        MoTa,
                        HinhAnh
                    FROM diadiem
                    WHERE TrangThaiHoatDong = 'Hoạt động'
                    ORDER BY TenDiaDiem ASC
                ");
                $stmt->execute();
                $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'locations' => $locations]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_all_equipment':
            // Get all available equipment
            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM thietbi 
                    WHERE TrangThai = 'Sẵn sàng' 
                    ORDER BY LoaiThietBi, TenThietBi
                ");
                $stmt->execute();
                $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'equipment' => $equipment]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'check_equipment_availability':
            // Kiểm tra số lượng thiết bị còn lại trong ngày
            $equipmentId = $_GET['equipment_id'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $startTime = $_GET['start_time'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $endTime = $_GET['end_time'] ?? null;
            $eventId = $_GET['event_id'] ?? null; // Để loại trừ sự kiện đang chỉnh sửa
            
            if (!$equipmentId || !$startDate || !$endDate) {
                echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
                break;
            }
            
            try {
                // Lấy tổng số lượng thiết bị
                $stmt = $pdo->prepare("SELECT SoLuong FROM thietbi WHERE ID_TB = ?");
                $stmt->execute([$equipmentId]);
                $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$equipment) {
                    echo json_encode(['success' => false, 'error' => 'Không tìm thấy thiết bị']);
                    break;
                }
                
                $totalQuantity = intval($equipment['SoLuong']);
                
                // Tính toán datetime từ date và time
                $startDateTime = $startDate . ' ' . ($startTime ?? '00:00:00');
                $endDateTime = $endDate . ' ' . ($endTime ?? '23:59:59');
                
                // Lấy số lượng thiết bị đã được đặt trong khoảng thời gian này
                // Loại trừ các sự kiện bị từ chối hoặc đã hoàn thành
                $sql = "
                    SELECT COALESCE(SUM(ct.SoLuong), 0) as booked_quantity
                    FROM chitietdatsukien ct
                    INNER JOIN datlichsukien dl ON ct.ID_DatLich = dl.ID_DatLich
                    WHERE ct.ID_TB = ?
                    AND dl.TrangThaiDuyet != 'Từ chối'
                    AND dl.TrangThaiDuyet != 'Hoàn thành'
                    AND (
                        (dl.NgayBatDau <= ? AND dl.NgayKetThuc >= ?) OR
                        (dl.NgayBatDau <= ? AND dl.NgayKetThuc >= ?) OR
                        (dl.NgayBatDau >= ? AND dl.NgayKetThuc <= ?)
                    )
                ";
                
                $params = [$equipmentId, $startDateTime, $startDateTime, $endDateTime, $endDateTime, $startDateTime, $endDateTime];
                
                // Nếu đang chỉnh sửa sự kiện, loại trừ sự kiện đó
                if ($eventId) {
                    $sql .= " AND dl.ID_DatLich != ?";
                    $params[] = $eventId;
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $bookedQuantity = intval($result['booked_quantity'] ?? 0);
                $availableQuantity = max(0, $totalQuantity - $bookedQuantity);
                
                echo json_encode([
                    'success' => true,
                    'total_quantity' => $totalQuantity,
                    'booked_quantity' => $bookedQuantity,
                    'available_quantity' => $availableQuantity
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'check_combo_availability':
            // Kiểm tra số lượng thiết bị của combo có đủ không trong ngày
            $comboId = $_GET['combo_id'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $startTime = $_GET['start_time'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $endTime = $_GET['end_time'] ?? null;
            $eventId = $_GET['event_id'] ?? null; // Để loại trừ sự kiện đang chỉnh sửa
            
            if (!$comboId || !$startDate || !$endDate) {
                echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
                break;
            }
            
            try {
                // Tính toán datetime từ date và time
                $startDateTime = $startDate . ' ' . ($startTime ?? '00:00:00');
                $endDateTime = $endDate . ' ' . ($endTime ?? '23:59:59');
                
                // Lấy danh sách thiết bị trong combo từ bảng combo_thietbi hoặc combochitiet
                $stmt = $pdo->prepare("
                    SELECT ID_TB, SoLuong 
                    FROM combo_thietbi 
                    WHERE ID_Combo = ?
                    UNION
                    SELECT ID_TB, SoLuong 
                    FROM combochitiet 
                    WHERE ID_Combo = ?
                ");
                $stmt->execute([$comboId, $comboId]);
                $comboEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($comboEquipment)) {
                    echo json_encode(['success' => false, 'error' => 'Combo không có thiết bị']);
                    break;
                }
                
                $equipmentAvailability = [];
                $allAvailable = true;
                
                // Kiểm tra từng thiết bị trong combo
                foreach ($comboEquipment as $item) {
                    $equipmentId = $item['ID_TB'];
                    $requiredQuantity = intval($item['SoLuong']);
                    
                    // Lấy tổng số lượng thiết bị
                    $stmt = $pdo->prepare("SELECT SoLuong FROM thietbi WHERE ID_TB = ?");
                    $stmt->execute([$equipmentId]);
                    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$equipment) {
                        $allAvailable = false;
                        $equipmentAvailability[] = [
                            'equipment_id' => $equipmentId,
                            'required' => $requiredQuantity,
                            'available' => 0,
                            'total' => 0,
                            'booked' => 0,
                            'sufficient' => false
                        ];
                        continue;
                    }
                    
                    $totalQuantity = intval($equipment['SoLuong']);
                    
                    // Lấy số lượng thiết bị đã được đặt trong khoảng thời gian này
                    $sql = "
                        SELECT COALESCE(SUM(ct.SoLuong), 0) as booked_quantity
                        FROM chitietdatsukien ct
                        INNER JOIN datlichsukien dl ON ct.ID_DatLich = dl.ID_DatLich
                        WHERE ct.ID_TB = ?
                        AND dl.TrangThaiDuyet != 'Từ chối'
                        AND dl.TrangThaiDuyet != 'Đã hủy'
                        AND (
                            (dl.NgayBatDau <= ? AND dl.NgayKetThuc >= ?) OR
                            (dl.NgayBatDau <= ? AND dl.NgayKetThuc >= ?) OR
                            (dl.NgayBatDau >= ? AND dl.NgayKetThuc <= ?)
                        )
                    ";
                    
                    $params = [$equipmentId, $startDateTime, $startDateTime, $endDateTime, $endDateTime, $startDateTime, $endDateTime];
                    
                    // Nếu đang chỉnh sửa sự kiện, loại trừ sự kiện đó
                    if ($eventId) {
                        $sql .= " AND dl.ID_DatLich != ?";
                        $params[] = $eventId;
                    }
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $bookedQuantity = intval($result['booked_quantity'] ?? 0);
                    $availableQuantity = max(0, $totalQuantity - $bookedQuantity);
                    $sufficient = $availableQuantity >= $requiredQuantity;
                    
                    if (!$sufficient) {
                        $allAvailable = false;
                    }
                    
                    $equipmentAvailability[] = [
                        'equipment_id' => $equipmentId,
                        'required' => $requiredQuantity,
                        'available' => $availableQuantity,
                        'total' => $totalQuantity,
                        'booked' => $bookedQuantity,
                        'sufficient' => $sufficient
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'available' => $allAvailable,
                    'equipment' => $equipmentAvailability
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_equipment_suggestions':
            // Get equipment suggestions based on event type and location
            $eventType = $_GET['event_type'] ?? '';
            $locationId = $_GET['location_id'] ?? '';
            
            if (!$eventType || !$locationId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu thông tin loại sự kiện hoặc địa điểm']);
                exit();
            }
            
            try {
                // Get event type ID
                $stmt = $pdo->prepare("SELECT ID_LoaiSK FROM loaisukien WHERE TenLoai = ?");
                $stmt->execute([$eventType]);
                $eventTypeData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$eventTypeData) {
                    echo json_encode(['success' => false, 'error' => 'Loại sự kiện không hợp lệ']);
                    exit();
                }
                
                // Get equipment suggestions based on event type from sukien_thietbi table
                // This is a mapping table that suggests equipment for different event types
                $stmt = $pdo->prepare("
                    SELECT DISTINCT t.*
                    FROM thietbi t
                    INNER JOIN sukien_thietbi st ON t.ID_TB = st.ID_TB
                    WHERE st.ID_LoaiSK = ?
                    ORDER BY t.LoaiThietBi, t.TenThietBi
                ");
                $stmt->execute([$eventTypeData['ID_LoaiSK']]);
                $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // If no specific equipment found, get general equipment suggestions
                if (empty($equipment)) {
                    $equipment = getGeneralEquipmentSuggestions($eventType, $pdo);
                }
                
                echo json_encode(['success' => true, 'equipment' => $equipment]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'register':
            // Verify CSRF token
            requireCSRF();
            
            // Register new event using datlichsukien table
            // Sử dụng getCachedInput() để tránh lỗi php://input chỉ đọc được một lần
            $jsonInput = getCachedInput();
            $input = json_decode($jsonInput, true);
            if (!$input) {
                $input = $_POST;
            }
            
            // Debug input
            error_log("Debug - Register input: " . print_r($input, true));
            
            // Get user ID from session
            $userId = $_SESSION['user']['ID_User'] ?? $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy thông tin người dùng']);
                exit();
            }
            
            // Validate required fields
            $requiredFields = ['event_name', 'event_date', 'event_time', 'event_end_date', 'event_end_time', 'location_id'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                    exit();
                }
            }
            
            // Validate dates
            $eventDate = $input['event_date'];
            $eventEndDate = $input['event_end_date'];
            $eventTime = $input['event_time'] ?? '00:00';
            $eventEndTime = $input['event_end_time'] ?? '00:00';
            $today = date('Y-m-d');
            
            if ($eventDate < $today) {
                echo json_encode(['success' => false, 'error' => 'Ngày bắt đầu không được là ngày trong quá khứ']);
                exit();
            }
            
            if ($eventEndDate < $eventDate) {
                echo json_encode(['success' => false, 'error' => 'Ngày kết thúc không được trước ngày bắt đầu']);
                exit();
            }
            
            // Check if event start time is at least 12 hours from now
            $eventStartDateTime = new DateTime($eventDate . ' ' . $eventTime);
            $now = new DateTime();
            $minDateTime = clone $now;
            $minDateTime->modify('+12 hours'); // Add 12 hours to current time
            
            if ($eventStartDateTime < $minDateTime) {
                $hoursDiff = ($eventStartDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
                $hoursLeft = max(0, floor($hoursDiff));
                $minDateTimeStr = $minDateTime->format('d/m/Y H:i');
                echo json_encode([
                    'success' => false, 
                    'error' => "Sự kiện phải được đăng ký trước ít nhất 12 giờ. Thời gian bắt đầu bạn chọn chỉ còn {$hoursLeft} giờ nữa. Vui lòng chọn thời gian sau {$minDateTimeStr}."
                ]);
                exit();
            }
            
            // Validate time if same date
            if ($eventDate === $eventEndDate) {
                if ($eventTime >= $eventEndTime) {
                    echo json_encode(['success' => false, 'error' => 'Giờ kết thúc phải sau giờ bắt đầu khi cùng ngày']);
                    exit();
                }
            }
            
            // Check if event end time is in the past
            $eventEndDateTime = new DateTime($eventEndDate . ' ' . $eventEndTime);
            
            if ($eventEndDateTime < $now) {
                echo json_encode(['success' => false, 'error' => 'Thời gian kết thúc sự kiện đã qua. Bạn không thể đăng ký sự kiện với thời gian trong quá khứ.']);
                exit();
            }
            
            // Debug: Log input data
            error_log("Debug - Input data: " . print_r($input, true));
            error_log("Debug - Total price: " . ($input['total_price'] ?? 'NOT SET'));
            
            // Get customer ID from user session
            $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE ID_User = ?");
            $stmt->execute([$userId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy thông tin khách hàng']);
                exit();
            }
            
            // Get event type ID
            $eventType = $input['event_type'] ?? '';
            $eventTypeId = null;
            if ($eventType) {
                // Check if event_type is already an ID (numeric) or a name
                if (is_numeric($eventType)) {
                    // It's already an ID
                    $eventTypeId = $eventType;
                } else {
                    // It's a name, need to find ID
                    $stmt = $pdo->prepare("SELECT ID_LoaiSK FROM loaisukien WHERE TenLoai = ?");
                    $stmt->execute([$eventType]);
                    $eventTypeData = $stmt->fetch(PDO::FETCH_ASSOC);
                    $eventTypeId = $eventTypeData ? $eventTypeData['ID_LoaiSK'] : null;
                }
            }
            
            // Debug event type
            error_log("Debug - Event type: " . $eventType);
            error_log("Debug - Event type ID: " . $eventTypeId);
            
            // Validate event type ID
            if (!$eventTypeId) {
                echo json_encode(['success' => false, 'error' => 'Loại sự kiện không hợp lệ']);
                exit();
            }
            
            // Prepare event datetime
            $eventDateTime = $input['event_date'] . ' ' . $input['event_time'];
            $endDateTime = $input['event_end_date'] . ' ' . $input['event_end_time'];
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Debug: Log values before insert
                error_log("Debug - About to insert TongTien: " . ($input['total_price'] ?? 0));
                error_log("Debug - Budget: " . ($input['budget'] ?? 'NULL'));
                error_log("Debug - Location rental type: " . ($input['location_rental_type'] ?? 'NOT SET'));
                
                // Convert rental type to database format
                $loaiThueApDung = null;
                if (isset($input['location_rental_type'])) {
                    if ($input['location_rental_type'] === 'hour') {
                        $loaiThueApDung = 'Theo giờ';
                    } elseif ($input['location_rental_type'] === 'day') {
                        $loaiThueApDung = 'Theo ngày';
                    }
                }
                error_log("Debug - Converted LoaiThueApDung: " . ($loaiThueApDung ?? 'NULL'));
                
                // Insert into datlichsukien table
                $roomId = $input['room_id'] ?? null;
                
                $sql = "INSERT INTO datlichsukien (
                    ID_KhachHang, TenSuKien, MoTa, NgayBatDau, NgayKetThuc, 
                    ID_DD, ID_LoaiSK, SoNguoiDuKien, NganSach, TongTien,
                    TrangThaiDuyet, TrangThaiThanhToan, GhiChu, LoaiThueApDung, ID_Phong
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $customer['ID_KhachHang'],
                    $input['event_name'],
                    $input['description'] ?? '',
                    $eventDateTime,
                    $endDateTime,
                    $input['location_id'],
                    $eventTypeId,
                    $input['expected_guests'] ?? null,
                    $input['budget'] ?? null,
                    $input['total_price'] ?? 0,
                    'Chờ duyệt',
                    'Chưa thanh toán',
                    'Đăng ký từ website',
                    $loaiThueApDung,
                    $roomId
                ]);
                
                if (!$result) {
                    throw new Exception('Lỗi khi tạo đơn đặt lịch sự kiện');
                }
                
                $datLichId = $pdo->lastInsertId();
                
                // Nếu có combo được chọn, thêm vào bảng chitietdatsukien
                // Hỗ trợ cả combo_id (single) và combo_ids (array) để tương thích ngược
                $comboIds = [];
                if (!empty($input['combo_ids']) && is_array($input['combo_ids'])) {
                    $comboIds = $input['combo_ids'];
                } elseif (!empty($input['combo_id'])) {
                    // Tương thích ngược với format cũ
                    $comboIds = [$input['combo_id']];
                }
                
                if (!empty($comboIds)) {
                    foreach ($comboIds as $comboId) {
                        // Lấy giá combo
                        $stmt = $pdo->prepare("SELECT GiaCombo, TenCombo FROM combo WHERE ID_Combo = ?");
                        $stmt->execute([$comboId]);
                        $combo = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($combo) {
                            $stmt = $pdo->prepare("
                                INSERT INTO chitietdatsukien (ID_DatLich, ID_Combo, SoLuong, DonGia, GhiChu) 
                                VALUES (?, ?, 1, ?, ?)
                            ");
                            $comboName = $combo['TenCombo'] ?? 'Combo thiết bị';
                            $stmt->execute([$datLichId, $comboId, $combo['GiaCombo'], "Combo: {$comboName}"]);
                        }
                    }
                }
                
                // If individual equipment was selected, add it to chitietdatsukien table
                if (!empty($input['equipment_ids']) && is_array($input['equipment_ids'])) {
                    // Get quantities if provided
                    $equipmentQuantities = [];
                    if (!empty($input['equipment_quantities']) && is_array($input['equipment_quantities'])) {
                        foreach ($input['equipment_quantities'] as $eq) {
                            if (isset($eq['id']) && isset($eq['quantity'])) {
                                $equipmentQuantities[$eq['id']] = intval($eq['quantity']);
                            }
                        }
                    }
                    
                    foreach ($input['equipment_ids'] as $equipmentId) {
                        // Get equipment price
                        $stmt = $pdo->prepare("SELECT GiaThue FROM thietbi WHERE ID_TB = ?");
                        $stmt->execute([$equipmentId]);
                        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Get quantity (default to 1 if not provided)
                        $quantity = isset($equipmentQuantities[$equipmentId]) ? $equipmentQuantities[$equipmentId] : 1;
                        
                        if ($equipment) {
                            $stmt = $pdo->prepare("
                                INSERT INTO chitietdatsukien (ID_DatLich, ID_TB, SoLuong, DonGia, GhiChu) 
                                VALUES (?, ?, ?, ?, 'Thiết bị riêng lẻ')
                            ");
                            $stmt->execute([$datLichId, $equipmentId, $quantity, $equipment['GiaThue']]);
                        }
                    }
                }
                
                $pdo->commit();
                $success = true;
                
                // Send real-time notification to admins
                $userName = $user['Email'] ?? 'User';
                notifyEventRegistration($datLichId, $input['event_name'], $userName, $userId);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Đăng ký sự kiện thành công', 'dat_lich_id' => $datLichId]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Lỗi khi đăng ký sự kiện']);
            }
            break;
            
        case 'get_my_events':
            // Get user's registered events
            try {
                // First, auto-cancel expired events that haven't been fully paid
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("
                        UPDATE datlichsukien 
                        SET TrangThaiDuyet = 'Từ chối',
                            GhiChu = CONCAT(IFNULL(GhiChu, ''), ' - Tự động hủy: Đã qua thời gian tổ chức và chưa thanh toán đủ (', NOW(), ')')
                        WHERE ID_KhachHang IN (
                            SELECT ID_KhachHang FROM khachhanginfo WHERE ID_User = ?
                        )
                        AND NgayKetThuc < NOW()
                        AND TrangThaiThanhToan != 'Đã thanh toán đủ'
                        AND TrangThaiDuyet != 'Từ chối'
                        AND TrangThaiDuyet != 'Hoàn thành'
                    ");
                    $stmt->execute([$userId]);
                    $cancelledCount = $stmt->rowCount();
                    
                    // Also cancel pending payments for expired events
                    if ($cancelledCount > 0) {
                        $stmt = $pdo->prepare("
                            UPDATE thanhtoan t
                            INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                            SET t.TrangThai = 'Hủy',
                                t.GhiChu = CONCAT(IFNULL(t.GhiChu, ''), ' - Tự động hủy: Sự kiện đã qua thời gian tổ chức')
                            WHERE dl.ID_KhachHang IN (
                                SELECT ID_KhachHang FROM khachhanginfo WHERE ID_User = ?
                            )
                            AND dl.NgayKetThuc < NOW()
                            AND dl.TrangThaiThanhToan != 'Đã thanh toán đủ'
                            AND t.TrangThai = 'Đang xử lý'
                        ");
                        $stmt->execute([$userId]);
                    }
                    
                    // Tự động hủy sự kiện quá deadline thanh toán đủ (tính từ ngày đặt cọc)
                    // Lấy các sự kiện đã đặt cọc nhưng chưa thanh toán đủ
                    $stmt = $pdo->prepare("
                        SELECT dl.ID_DatLich, dl.NgayBatDau, dl.TrangThaiDuyet
                        FROM datlichsukien dl
                        INNER JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
                        WHERE k.ID_User = ?
                        AND dl.TrangThaiThanhToan = 'Đã đặt cọc'
                        AND dl.TrangThaiDuyet = 'Đã duyệt'
                    ");
                    $stmt->execute([$userId]);
                    $eventsWithDeposit = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $deadlineCancelledCount = 0;
                    foreach ($eventsWithDeposit as $event) {
                        // Lấy ngày đặt cọc thành công
                        $stmtDeposit = $pdo->prepare("
                            SELECT NgayThanhToan 
                            FROM thanhtoan 
                            WHERE ID_DatLich = ? 
                            AND LoaiThanhToan = 'Đặt cọc' 
                            AND TrangThai = 'Thành công'
                            ORDER BY NgayThanhToan ASC
                            LIMIT 1
                        ");
                        $stmtDeposit->execute([$event['ID_DatLich']]);
                        $depositPayment = $stmtDeposit->fetch(PDO::FETCH_ASSOC);
                        
                        if ($depositPayment && !empty($depositPayment['NgayThanhToan']) && !empty($event['NgayBatDau'])) {
                            $depositDate = new DateTime($depositPayment['NgayThanhToan']);
                            $eventStartDate = new DateTime($event['NgayBatDau']);
                            $now = new DateTime();
                            
                            // Deadline luôn = đặt cọc + 7 ngày
                            $deadlineDate = clone $depositDate;
                            $deadlineDate->modify('+7 days');
                            
                            // Tính số ngày từ đặt cọc đến ngày tổ chức (để hiển thị)
                            $daysFromDepositToEvent = $depositDate->diff($eventStartDate)->days;
                            
                            // Kiểm tra nếu đã quá deadline và chưa thanh toán đủ
                            if ($now > $deadlineDate && $now < $eventStartDate) {
                                // Hủy sự kiện và cập nhật ghi chú
                                $stmtCancel = $pdo->prepare("
                                    UPDATE datlichsukien 
                                    SET TrangThaiDuyet = 'Đã hủy',
                                        GhiChu = CONCAT(IFNULL(GhiChu, ''), ' | Tự động hủy: Quá hạn thanh toán đủ (hạn: ', ?, '). Không hoàn lại cọc.')
                                    WHERE ID_DatLich = ?
                                ");
                                $stmtCancel->execute([$deadlineDate->format('d/m/Y'), $event['ID_DatLich']]);
                                
                                // Hủy các thanh toán đang chờ xử lý
                                $stmtCancelPayments = $pdo->prepare("
                                    UPDATE thanhtoan 
                                    SET TrangThai = 'Hủy',
                                        GhiChu = CONCAT(IFNULL(GhiChu, ''), ' - Tự động hủy: Quá hạn thanh toán đủ. Không hoàn lại cọc.')
                                    WHERE ID_DatLich = ?
                                    AND TrangThai = 'Đang xử lý'
                                ");
                                $stmtCancelPayments->execute([$event['ID_DatLich']]);
                                
                                $deadlineCancelledCount++;
                            }
                        }
                    }
                    
                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log("Error auto-cancelling expired events: " . $e->getMessage());
                }
                
                $stmt = $pdo->prepare("
                    SELECT dl.*, d.TenDiaDiem, d.DiaChi, d.SucChua, d.GiaThueGio, d.GiaThueNgay, d.LoaiThue, d.LoaiDiaDiem,
                           ls.TenLoai, ls.GiaCoBan, k.HoTen, k.SoDienThoai, k.DiaChi as DiaChiKhachHang, k.ID_KhachHang,
                           u.Email as UserEmail,
                           p.ID_Phong, p.TenPhong as TenPhong, p.GiaThueGio as PhongGiaThueGio, p.GiaThueNgay as PhongGiaThueNgay, p.LoaiThue as PhongLoaiThue,
                           COALESCE(equipment_total.TongGiaThietBi, 0) as TongGiaThietBi,
                           s.TrangThaiThucTe as TrangThaiSuKien,
                           COALESCE(pending_payments.PendingPayments, 0) as PendingPayments
                    FROM datlichsukien dl
                    INNER JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
                    LEFT JOIN users u ON k.ID_User = u.ID_User
                    LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
                    LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
                    LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
                    LEFT JOIN phong p ON dl.ID_Phong = p.ID_Phong
                    LEFT JOIN (
                        SELECT ID_DatLich, SUM(DonGia * SoLuong) as TongGiaThietBi
                        FROM chitietdatsukien
                        WHERE ID_TB IS NOT NULL OR ID_Combo IS NOT NULL
                        GROUP BY ID_DatLich
                    ) equipment_total ON dl.ID_DatLich = equipment_total.ID_DatLich
                    LEFT JOIN (
                        SELECT ID_DatLich, COUNT(*) as PendingPayments
                        FROM thanhtoan
                        WHERE TrangThai = 'Đang xử lý'
                        GROUP BY ID_DatLich
                    ) pending_payments ON pending_payments.ID_DatLich = dl.ID_DatLich
                    WHERE k.ID_User = ?
                    ORDER BY dl.NgayTao DESC
                ");
                $stmt->execute([$userId]);
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Tính toán thông tin thanh toán cho mỗi sự kiện
                foreach ($events as &$event) {
                    // Tính khoảng cách từ đăng ký đến tổ chức
                    $daysFromRegistrationToEvent = 0;
                    if (!empty($event['NgayTao']) && !empty($event['NgayBatDau'])) {
                        $registrationDate = new DateTime($event['NgayTao']);
                        $eventStartDate = new DateTime($event['NgayBatDau']);
                        $daysFromRegistrationToEvent = $registrationDate->diff($eventStartDate)->days;
                    }
                    $event['DaysFromRegistrationToEvent'] = $daysFromRegistrationToEvent;
                    $event['RequiresFullPayment'] = ($daysFromRegistrationToEvent > 0 && $daysFromRegistrationToEvent < 7);
                    
                    // Lấy thông tin thanh toán thành công gần nhất (để hiển thị phương thức thanh toán)
                    $stmtPayment = $pdo->prepare("
                        SELECT PhuongThuc, LoaiThanhToan, TrangThai, NgayThanhToan
                        FROM thanhtoan
                        WHERE ID_DatLich = ?
                        AND TrangThai = 'Thành công'
                        ORDER BY NgayThanhToan DESC
                        LIMIT 1
                    ");
                    $stmtPayment->execute([$event['ID_DatLich']]);
                    $paymentInfo = $stmtPayment->fetch(PDO::FETCH_ASSOC);
                    
                    if ($paymentInfo) {
                        // Map giá trị PhuongThuc từ database sang format frontend
                        $phuongThuc = $paymentInfo['PhuongThuc'] ?? null;
                        if ($phuongThuc === 'Tiền mặt') {
                            $event['PaymentMethod'] = 'cash';
                        } elseif ($phuongThuc === 'Chuyển khoản') {
                            $event['PaymentMethod'] = 'sepay';
                        } else {
                            // Các phương thức khác (Momo, ZaloPay, Visa/MasterCard)
                            $event['PaymentMethod'] = strtolower(str_replace(['/', ' '], ['', ''], $phuongThuc));
                        }
                        $event['PaymentType'] = $paymentInfo['LoaiThanhToan'] ?? null;
                    } else {
                        $event['PaymentMethod'] = null;
                        $event['PaymentType'] = null;
                    }
                    
                    // Lấy danh sách thanh toán để hiển thị nút xem hóa đơn
                    // Bao gồm cả thanh toán thành công và thanh toán đang xử lý (nếu là tiền mặt)
                    $stmtPayments = $pdo->prepare("
                        SELECT ID_ThanhToan, LoaiThanhToan, SoTien, PhuongThuc, NgayThanhToan, TrangThai
                        FROM thanhtoan
                        WHERE ID_DatLich = ?
                        AND (TrangThai = 'Thành công' OR (TrangThai = 'Đang xử lý' AND PhuongThuc = 'Tiền mặt'))
                        ORDER BY NgayThanhToan DESC
                    ");
                    $stmtPayments->execute([$event['ID_DatLich']]);
                    $event['SuccessfulPayments'] = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Lấy thanh toán đang xử lý (tiền mặt) để hiển thị thông tin
                    $stmtPendingCash = $pdo->prepare("
                        SELECT ID_ThanhToan, LoaiThanhToan, SoTien, PhuongThuc, NgayThanhToan, TrangThai
                        FROM thanhtoan
                        WHERE ID_DatLich = ?
                        AND TrangThai = 'Đang xử lý'
                        AND PhuongThuc = 'Tiền mặt'
                        ORDER BY NgayThanhToan DESC
                        LIMIT 1
                    ");
                    $stmtPendingCash->execute([$event['ID_DatLich']]);
                    $pendingCashPayment = $stmtPendingCash->fetch(PDO::FETCH_ASSOC);
                    $event['PendingCashPayment'] = $pendingCashPayment ? $pendingCashPayment : null;
                    
                    // Tính toán hạn thanh toán đủ cho sự kiện đã đặt cọc (tính từ ngày đặt cọc)
                    if ($event['TrangThaiThanhToan'] === 'Đã đặt cọc' && !empty($event['NgayBatDau'])) {
                        // Lấy ngày đặt cọc thành công đầu tiên
                        $stmtDeposit = $pdo->prepare("
                            SELECT NgayThanhToan 
                            FROM thanhtoan 
                            WHERE ID_DatLich = ? 
                            AND LoaiThanhToan = 'Đặt cọc' 
                            AND TrangThai = 'Thành công'
                            ORDER BY NgayThanhToan ASC
                            LIMIT 1
                        ");
                        $stmtDeposit->execute([$event['ID_DatLich']]);
                        $depositPayment = $stmtDeposit->fetch(PDO::FETCH_ASSOC);
                        
                        if ($depositPayment && !empty($depositPayment['NgayThanhToan'])) {
                            $depositDate = new DateTime($depositPayment['NgayThanhToan']);
                            $eventStartDate = new DateTime($event['NgayBatDau']);
                            $now = new DateTime();
                            
                            // Deadline luôn = đặt cọc + 7 ngày
                            $deadlineDate = clone $depositDate;
                            $deadlineDate->modify('+7 days');
                            
                            // Tính số ngày từ đặt cọc đến ngày tổ chức (để hiển thị)
                            $daysFromDepositToEvent = $depositDate->diff($eventStartDate)->days;
                            
                            $daysUntilDeadline = $now->diff($deadlineDate)->days;
                            
                            $event['PaymentDeadline'] = [
                                'deadline_date' => $deadlineDate->format('Y-m-d H:i:s'),
                                'deadline_formatted' => $deadlineDate->format('d/m/Y'),
                                'days_until_deadline' => $daysUntilDeadline,
                                'is_past_deadline' => $now > $deadlineDate,
                                'is_approaching' => $daysUntilDeadline <= 3 && $daysUntilDeadline > 0,
                                'days_from_deposit_to_event' => $daysFromDepositToEvent,
                                'deposit_date' => $depositPayment['NgayThanhToan'],
                                'days_from_registration_to_event' => $daysFromRegistrationToEvent
                            ];
                        }
                    }
                }
                unset($event);

                echo json_encode([
                    'success' => true, 
                    'events' => $events, 
                    'cancelled_expired' => $cancelledCount ?? 0,
                    'cancelled_deadline' => $deadlineCancelledCount ?? 0
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_all_combos':
            // Get all available combos
            try {
                $stmt = $pdo->prepare("
                    SELECT c.ID_Combo, c.TenCombo, c.MoTa, c.GiaCombo
                    FROM combo c
                    ORDER BY c.GiaCombo ASC
                ");
                $stmt->execute();
                $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get combo details (equipment in each combo)
                foreach ($combos as &$combo) {
                    $stmt = $pdo->prepare("
                        SELECT t.ID_TB, t.TenThietBi, t.LoaiThietBi, t.HangSX, t.GiaThue, t.DonViTinh, cc.SoLuong
                        FROM combochitiet cc
                        INNER JOIN thietbi t ON cc.ID_TB = t.ID_TB
                        WHERE cc.ID_Combo = ?
                        ORDER BY t.LoaiThietBi, t.TenThietBi
                    ");
                    $stmt->execute([$combo['ID_Combo']]);
                    $combo['equipment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                echo json_encode(['success' => true, 'combos' => $combos]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_combo_suggestions':
            // Get combo suggestions based on event type
            try {
                $eventType = $_GET['event_type'] ?? '';
                $locationId = $_GET['location_id'] ?? '';
                
                if (empty($eventType)) {
                    echo json_encode(['success' => false, 'error' => 'Thiếu loại sự kiện']);
                    break;
                }
                
                // Get event type ID
                $stmt = $pdo->prepare("SELECT ID_LoaiSK FROM loaisukien WHERE TenLoai = ?");
                $stmt->execute([$eventType]);
                $eventTypeData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$eventTypeData) {
                    echo json_encode(['success' => false, 'error' => 'Loại sự kiện không tồn tại']);
                    break;
                }
                
                $eventTypeId = $eventTypeData['ID_LoaiSK'];
                
                // Get combo suggestions for this event type
                $stmt = $pdo->prepare("
                    SELECT c.ID_Combo, c.TenCombo, c.MoTa, c.GiaCombo, 
                           COALESCE(cl.UuTien, 1) as UuTien
                    FROM combo c
                    INNER JOIN combo_loaisk cl ON c.ID_Combo = cl.ID_Combo
                    WHERE cl.ID_LoaiSK = ?
                    ORDER BY COALESCE(cl.UuTien, 1) ASC, c.GiaCombo ASC
                ");
                $stmt->execute([$eventTypeId]);
                $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get combo details (equipment in each combo)
                foreach ($combos as &$combo) {
                    $stmt = $pdo->prepare("
                        SELECT t.ID_TB, t.TenThietBi, t.LoaiThietBi, t.HangSX, t.GiaThue, t.DonViTinh, cc.SoLuong
                        FROM combochitiet cc
                        INNER JOIN thietbi t ON cc.ID_TB = t.ID_TB
                        WHERE cc.ID_Combo = ?
                        ORDER BY t.LoaiThietBi, t.TenThietBi
                    ");
                    $stmt->execute([$combo['ID_Combo']]);
                    $combo['equipment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                echo json_encode(['success' => true, 'combos' => $combos]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_event_for_edit':
            $eventId = $_GET['event_id'] ?? null;
            
            if (!$eventId) {
                echo json_encode(['success' => false, 'message' => 'ID sự kiện không hợp lệ']);
                break;
            }
            
            // Check if user is logged in
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
                break;
            }
            
            $userId = $_SESSION['user']['ID_User'] ?? $_SESSION['user']['id'] ?? null;
            
            // Debug
            error_log("Debug - Event ID: " . $eventId);
            error_log("Debug - User ID: " . $userId);
            error_log("Debug - Session user: " . print_r($_SESSION['user'], true));
            
            try {
                // Get customer ID from user session
                $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE ID_User = ?");
                $stmt->execute([$userId]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("Debug - Customer: " . print_r($customer, true));
                
                if (!$customer) {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng']);
                    break;
                }
                
                $customerId = $customer['ID_KhachHang'];
                
                error_log("Debug - Customer ID: " . $customerId);
                
                // Get event details
                $stmt = $pdo->prepare("
                    SELECT * FROM datlichsukien 
                    WHERE ID_DatLich = ? AND ID_KhachHang = ? AND TrangThaiDuyet = 'Chờ duyệt'
                ");
                $stmt->execute([$eventId, $customerId]);
                $event = $stmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("Debug - Found event: " . print_r($event, true));
                
                // If no event found with status restriction, try without status restriction
                if (!$event) {
                    error_log("Debug - No event found with status restriction, trying without status");
                    $stmt = $pdo->prepare("
                        SELECT * FROM datlichsukien 
                        WHERE ID_DatLich = ? AND ID_KhachHang = ?
                    ");
                    $stmt->execute([$eventId, $customerId]);
                    $event = $stmt->fetch(PDO::FETCH_ASSOC);
                    error_log("Debug - Found event without status: " . print_r($event, true));
                }
                
                if (!$event) {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện hoặc bạn không có quyền chỉnh sửa']);
                    break;
                }
                
                echo json_encode(['success' => true, 'event' => $event]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'update_event':
            // Verify CSRF token
            requireCSRF();
            
            $input = json_decode(getCachedInput(), true);
            
            if (!$input) {
                echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ']);
                break;
            }
            
            // Check if user is logged in
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
                break;
            }
            
            $userId = $_SESSION['user']['ID_User'] ?? $_SESSION['user']['id'] ?? null;
            $editId = $input['edit_id'] ?? null;
            
            if (!$editId) {
                echo json_encode(['success' => false, 'error' => 'ID sự kiện không hợp lệ']);
                break;
            }
            
            try {
                // Get customer ID from user session
                $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE ID_User = ?");
                $stmt->execute([$userId]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$customer) {
                    echo json_encode(['success' => false, 'error' => 'Không tìm thấy thông tin khách hàng']);
                    break;
                }
                
                $customerId = $customer['ID_KhachHang'];
                
                // Check if event belongs to user and is not approved yet
                $stmt = $pdo->prepare("
                    SELECT ID_DatLich FROM datlichsukien 
                    WHERE ID_DatLich = ? AND ID_KhachHang = ? AND TrangThaiDuyet = 'Chờ duyệt'
                ");
                $stmt->execute([$editId, $customerId]);
                $event = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$event) {
                    echo json_encode(['success' => false, 'error' => 'Không tìm thấy sự kiện hoặc bạn không có quyền chỉnh sửa']);
                    break;
                }
                
                // Get event type ID
                $eventType = $input['event_type'] ?? '';
                $eventTypeId = null;
                if ($eventType) {
                    // Check if event_type is already an ID (numeric) or a name
                    if (is_numeric($eventType)) {
                        // It's already an ID
                        $eventTypeId = $eventType;
                    } else {
                        // It's a name, need to find ID
                        $stmt = $pdo->prepare("SELECT ID_LoaiSK FROM loaisukien WHERE TenLoai = ?");
                        $stmt->execute([$eventType]);
                        $eventTypeData = $stmt->fetch(PDO::FETCH_ASSOC);
                        $eventTypeId = $eventTypeData ? $eventTypeData['ID_LoaiSK'] : null;
                    }
                }
                
                // Validate event type ID
                if (!$eventTypeId) {
                    echo json_encode(['success' => false, 'error' => 'Loại sự kiện không hợp lệ']);
                    break;
                }
                
                // Validate dates
                $eventDate = $input['event_date'];
                $eventEndDate = $input['event_end_date'];
                $eventTime = $input['event_time'] ?? '00:00';
                $eventEndTime = $input['event_end_time'] ?? '00:00';
                $today = date('Y-m-d');
                
                if ($eventDate < $today) {
                    echo json_encode(['success' => false, 'error' => 'Ngày bắt đầu không được là ngày trong quá khứ']);
                    break;
                }
                
                if ($eventEndDate < $eventDate) {
                    echo json_encode(['success' => false, 'error' => 'Ngày kết thúc không được trước ngày bắt đầu']);
                    break;
                }
                
                // Check if event start time is at least 12 hours from now
                $eventStartDateTime = new DateTime($eventDate . ' ' . $eventTime);
                $now = new DateTime();
                $minDateTime = clone $now;
                $minDateTime->modify('+12 hours'); // Add 12 hours to current time
                
                if ($eventStartDateTime < $minDateTime) {
                    $hoursDiff = ($eventStartDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
                    $hoursLeft = max(0, floor($hoursDiff));
                    $minDateTimeStr = $minDateTime->format('d/m/Y H:i');
                    echo json_encode([
                        'success' => false, 
                        'error' => "Sự kiện phải được đăng ký trước ít nhất 12 giờ. Thời gian bắt đầu bạn chọn chỉ còn {$hoursLeft} giờ nữa. Vui lòng chọn thời gian sau {$minDateTimeStr}."
                    ]);
                    break;
                }
                
                // Validate time if same date
                if ($eventDate === $eventEndDate) {
                    if ($eventTime >= $eventEndTime) {
                        echo json_encode(['success' => false, 'error' => 'Giờ kết thúc phải sau giờ bắt đầu khi cùng ngày']);
                        break;
                    }
                }
                
                // Check if event end time is in the past
                $eventEndDateTime = new DateTime($eventEndDate . ' ' . $eventEndTime);
                
                if ($eventEndDateTime < $now) {
                    echo json_encode(['success' => false, 'error' => 'Thời gian kết thúc sự kiện đã qua. Bạn không thể cập nhật sự kiện với thời gian trong quá khứ.']);
                    break;
                }
                
                // Update event
                $startDateTime = $input['event_date'] . ' ' . $input['event_time'];
                $endDateTime = $input['event_end_date'] . ' ' . $input['event_end_time'];
                
                // Convert rental type to database format
                $loaiThueApDung = null;
                if (isset($input['location_rental_type'])) {
                    if ($input['location_rental_type'] === 'hour') {
                        $loaiThueApDung = 'Theo giờ';
                    } elseif ($input['location_rental_type'] === 'day') {
                        $loaiThueApDung = 'Theo ngày';
                    }
                }
                
                // Xử lý room_rental_type nếu có (cho địa điểm trong nhà)
                $roomId = $input['room_id'] ?? null;
                $roomRentalType = $input['room_rental_type'] ?? null;
                
                // Nếu có room_rental_type, ưu tiên dùng nó cho LoaiThueApDung
                if ($roomRentalType) {
                    if ($roomRentalType === 'hour') {
                        $loaiThueApDung = 'Theo giờ';
                    } elseif ($roomRentalType === 'day') {
                        $loaiThueApDung = 'Theo ngày';
                    }
                }
                
                error_log("Debug - Update LoaiThueApDung: " . ($loaiThueApDung ?? 'NULL'));
                error_log("Debug - Update room_id: " . ($roomId ?? 'NULL'));
                error_log("Debug - Update room_rental_type: " . ($roomRentalType ?? 'NULL'));
                
                $stmt = $pdo->prepare("
                    UPDATE datlichsukien SET
                        TenSuKien = ?,
                        ID_LoaiSK = ?,
                        NgayBatDau = ?,
                        NgayKetThuc = ?,
                        SoNguoiDuKien = ?,
                        NganSach = ?,
                        TongTien = ?,
                        MoTa = ?,
                        GhiChu = ?,
                        ID_DD = ?,
                        LoaiThueApDung = ?,
                        ID_Phong = ?
                    WHERE ID_DatLich = ?
                ");
                
                $result = $stmt->execute([
                    $input['event_name'],
                    $eventTypeId,
                    $startDateTime,
                    $endDateTime,
                    $input['expected_guests'],
                    $input['budget'],
                    $input['total_price'] ?? 0,
                    $input['description'],
                    $input['notes'],
                    $input['location_id'],
                    $loaiThueApDung,
                    $roomId,
                    $editId
                ]);
                
                if ($result) {
                    // Update equipment details
                    $stmt = $pdo->prepare("DELETE FROM chitietdatsukien WHERE ID_DatLich = ?");
                    $stmt->execute([$editId]);
                    
                    // Add new equipment
                    if (!empty($input['equipment_ids']) && is_array($input['equipment_ids'])) {
                        // Get quantities if provided
                        $equipmentQuantities = [];
                        if (!empty($input['equipment_quantities']) && is_array($input['equipment_quantities'])) {
                            foreach ($input['equipment_quantities'] as $eq) {
                                if (isset($eq['id']) && isset($eq['quantity'])) {
                                    $equipmentQuantities[$eq['id']] = intval($eq['quantity']);
                                }
                            }
                        }
                        
                        // Debug log
                        error_log("Update Event - Equipment IDs: " . print_r($input['equipment_ids'], true));
                        error_log("Update Event - Equipment Quantities: " . print_r($input['equipment_quantities'], true));
                        error_log("Update Event - Parsed Quantities: " . print_r($equipmentQuantities, true));
                        
                        foreach ($input['equipment_ids'] as $equipmentId) {
                            // Get equipment price
                            $stmt = $pdo->prepare("SELECT GiaThue FROM thietbi WHERE ID_TB = ?");
                            $stmt->execute([$equipmentId]);
                            $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
                            $price = $equipment ? $equipment['GiaThue'] : 0;
                            
                            // Get quantity (default to 1 if not provided)
                            $quantity = isset($equipmentQuantities[$equipmentId]) ? $equipmentQuantities[$equipmentId] : 1;
                            
                            error_log("Update Event - Equipment ID: {$equipmentId}, Quantity: {$quantity}, Price: {$price}");
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO chitietdatsukien (ID_DatLich, ID_TB, SoLuong, DonGia, GhiChu) 
                                VALUES (?, ?, ?, ?, 'Thiết bị riêng lẻ')
                            ");
                            $stmt->execute([$editId, $equipmentId, $quantity, $price]);
                            
                            error_log("Update Event - Inserted equipment: ID_DatLich={$editId}, ID_TB={$equipmentId}, SoLuong={$quantity}");
                        }
                    }
                    
                    // Thêm combo nếu được chọn
                    // Hỗ trợ cả combo_id (single) và combo_ids (array) để tương thích ngược
                    $comboIds = [];
                    if (!empty($input['combo_ids']) && is_array($input['combo_ids'])) {
                        $comboIds = $input['combo_ids'];
                    } elseif (!empty($input['combo_id'])) {
                        // Tương thích ngược với format cũ
                        $comboIds = [$input['combo_id']];
                    }
                    
                    if (!empty($comboIds)) {
                        foreach ($comboIds as $comboId) {
                            // Lấy giá combo
                            $stmt = $pdo->prepare("SELECT GiaCombo, TenCombo FROM combo WHERE ID_Combo = ?");
                            $stmt->execute([$comboId]);
                            $combo = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($combo) {
                                $stmt = $pdo->prepare("
                                    INSERT INTO chitietdatsukien (ID_DatLich, ID_Combo, SoLuong, DonGia, GhiChu) 
                                    VALUES (?, ?, 1, ?, ?)
                                ");
                                $comboName = $combo['TenCombo'] ?? 'Combo thiết bị';
                                $stmt->execute([$editId, $comboId, $combo['GiaCombo'], "Combo: {$comboName}"]);
                            }
                        }
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Cập nhật sự kiện thành công!']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Lỗi khi cập nhật sự kiện']);
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_event_selected_data':
            $eventId = $_GET['event_id'] ?? null;
            
            if (!$eventId) {
                echo json_encode(['success' => false, 'message' => 'ID sự kiện không hợp lệ']);
                break;
            }
            
            try {
                // Get event location with applied rental type and room information
                $stmt = $pdo->prepare("
                    SELECT d.*, dl.LoaiThueApDung, dl.ID_Phong,
                           p.TenPhong, p.GiaThueGio as PhongGiaThueGio, p.GiaThueNgay as PhongGiaThueNgay, 
                           p.LoaiThue as PhongLoaiThue, p.SucChua as PhongSucChua
                    FROM diadiem d
                    INNER JOIN datlichsukien dl ON d.ID_DD = dl.ID_DD
                    LEFT JOIN phong p ON dl.ID_Phong = p.ID_Phong
                    WHERE dl.ID_DatLich = ?
                ");
                $stmt->execute([$eventId]);
                $location = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get event equipment
                $stmt = $pdo->prepare("
                    SELECT t.*, ct.SoLuong, ct.DonGia 
                    FROM thietbi t
                    INNER JOIN chitietdatsukien ct ON t.ID_TB = ct.ID_TB
                    WHERE ct.ID_DatLich = ?
                ");
                $stmt->execute([$eventId]);
                $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Đảm bảo SoLuong là int
                foreach ($equipment as &$eq) {
                    $eq['SoLuong'] = intval($eq['SoLuong'] ?? 1);
                }
                unset($eq); // Unset reference
                
                // Get event combo
                $stmt = $pdo->prepare("
                    SELECT c.*, ct.SoLuong, ct.DonGia 
                    FROM combo c
                    INNER JOIN chitietdatsukien ct ON c.ID_Combo = ct.ID_Combo
                    WHERE ct.ID_DatLich = ?
                ");
                $stmt->execute([$eventId]);
                $combo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'location' => $location,
                    'equipment' => $equipment,
                    'combo' => $combo
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_event_equipment':
            $eventId = $_GET['event_id'] ?? null;
            
            if (!$eventId) {
                echo json_encode(['success' => false, 'message' => 'ID sự kiện không hợp lệ']);
                break;
            }
            
            try {
                // Get individual equipment
                $stmt = $pdo->prepare("
                    SELECT 
                        ct.*,
                        tb.TenThietBi,
                        tb.LoaiThietBi,
                        tb.HangSX,
                        tb.GiaThue,
                        tb.DonViTinh
                    FROM chitietdatsukien ct
                    LEFT JOIN thietbi tb ON ct.ID_TB = tb.ID_TB
                    WHERE ct.ID_DatLich = ? AND ct.ID_TB IS NOT NULL
                ");
                $stmt->execute([$eventId]);
                $individualEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get combo equipment
                $stmt = $pdo->prepare("
                    SELECT 
                        ct.*,
                        c.TenCombo,
                        c.MoTa as ComboMoTa,
                        c.GiaCombo
                    FROM chitietdatsukien ct
                    LEFT JOIN combo c ON ct.ID_Combo = c.ID_Combo
                    WHERE ct.ID_DatLich = ? AND ct.ID_Combo IS NOT NULL
                ");
                $stmt->execute([$eventId]);
                $comboEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Combine equipment
                $equipment = array_merge($individualEquipment, $comboEquipment);
                
                echo json_encode([
                    'success' => true,
                    'equipment' => $equipment
                ]);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'register_event_for_existing_customer':
            registerEventForExistingCustomer();
            break;
            
        case 'register_event_for_customer':
            registerEventForCustomer();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

// Helper function to get general equipment suggestions based on event type
function getGeneralEquipmentSuggestions($eventType, $pdo) {
    $equipmentSuggestions = [];
    
    // Define equipment suggestions by event type
    $eventTypeEquipment = [
        'Hội nghị - Hội thảo' => ['Âm thanh', 'Hình ảnh'],
        'Văn hóa - Nghệ thuật' => ['Âm thanh', 'Ánh sáng', 'Hình ảnh'],
        'Thương mại - Quảng bá' => ['Hình ảnh', 'Phụ trợ'],
        'Tiệc - Lễ kỷ niệm' => ['Âm thanh', 'Ánh sáng'],
        'Thể thao - Giải trí' => ['Âm thanh', 'Hình ảnh', 'Ánh sáng'],
        'Cộng đồng - Xã hội' => ['Âm thanh', 'Hình ảnh']
    ];
    
    $equipmentTypes = $eventTypeEquipment[$eventType] ?? ['Âm thanh', 'Hình ảnh'];
    
    foreach ($equipmentTypes as $type) {
        $stmt = $pdo->prepare("
            SELECT * FROM thietbi 
            WHERE LoaiThietBi = ? 
            ORDER BY GiaThue ASC 
            LIMIT 3
        ");
        $stmt->execute([$type]);
        $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $equipmentSuggestions = array_merge($equipmentSuggestions, $equipment);
    }
    
    return $equipmentSuggestions;
}

function registerEventForExistingCustomer() {
    global $pdo;
    
    try {
        // Get JSON input
        $input = json_decode(getCachedInput(), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }
        
        // Validate required fields
        $requiredFields = ['customer_id', 'event', 'location'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                echo json_encode(['success' => false, 'message' => "Thiếu thông tin: {$field}"]);
                return;
            }
        }
        
        $customerId = $input['customer_id'];
        $event = $input['event'];
        $locationId = $input['location'];
        $equipment = $input['equipment'] ?? [];
        $adminNotes = $input['adminNotes'] ?? '';
        
        // Validate customer exists
        $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE ID_KhachHang = ?");
        $stmt->execute([$customerId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Khách hàng không tồn tại']);
            return;
        }
        
        // Validate event data
        if (empty($event['name']) || empty($event['type']) || empty($event['startDate']) || empty($event['endDate'])) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện bắt buộc']);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // 1. Calculate total cost
            $event['room_id'] = $input['room_id'] ?? null;
            $totalCost = calculateTotalCost($event, $locationId, $equipment, $input['location_rental_type'] ?? null);
            
        // 2. Create event registration
        $eventId = createEventRegistration($event, $customerId, $locationId, $totalCost, $adminNotes, $input['location_rental_type'] ?? null);
            
            // 3. Add equipment to registration
            if (!empty($equipment)) {
                addEquipmentToRegistration($eventId, $equipment);
            }
            
            // 4. Commit transaction
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Đăng ký sự kiện thành công',
                'event_id' => $eventId,
                'total_cost' => $totalCost
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi đăng ký sự kiện: ' . $e->getMessage()]);
    }
}

function registerEventForCustomer() {
    global $pdo;
    
    try {
        // Get JSON input
        $input = json_decode(getCachedInput(), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }
        
        // Validate required fields
        $requiredFields = ['customer', 'event', 'location'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                echo json_encode(['success' => false, 'message' => "Thiếu thông tin: {$field}"]);
                return;
            }
        }
        
        $customer = $input['customer'];
        $event = $input['event'];
        $locationId = $input['location'];
        $equipment = $input['equipment'] ?? [];
        $adminNotes = $input['adminNotes'] ?? '';
        
        // Validate customer data
        if (empty($customer['name']) || empty($customer['phone'])) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin khách hàng bắt buộc']);
            return;
        }
        
        // Validate event data
        if (empty($event['name']) || empty($event['type']) || empty($event['startDate']) || empty($event['endDate'])) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện bắt buộc']);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // 1. Create or get customer
            $customerId = createOrGetCustomer($customer);
            
            // 2. Calculate total cost
            $totalCost = calculateTotalCost($event, $locationId, $equipment);
            
            // 3. Create event registration
            $eventId = createEventRegistration($event, $customerId, $locationId, $totalCost, $adminNotes);
            
            // 4. Add equipment to registration
            if (!empty($equipment)) {
                addEquipmentToRegistration($eventId, $equipment);
            }
            
            // 5. Commit transaction
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Đăng ký sự kiện thành công',
                'event_id' => $eventId,
                'total_cost' => $totalCost
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi đăng ký sự kiện: ' . $e->getMessage()]);
    }
}

function createOrGetCustomer($customerData) {
    global $pdo;
    
    // Check if customer already exists by phone
    $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE SoDienThoai = ?");
    $stmt->execute([$customerData['phone']]);
    $existingCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingCustomer) {
        // Update existing customer info
        $stmt = $pdo->prepare("
            UPDATE khachhanginfo 
            SET HoTen = ?, DiaChi = ?, NgaySinh = ?, GhiChu = ?
            WHERE ID_KhachHang = ?
        ");
        $stmt->execute([
            $customerData['name'],
            $customerData['address'] ?? null,
            $customerData['birthday'] ?? null,
            $customerData['notes'] ?? null,
            $existingCustomer['ID_KhachHang']
        ]);
        
        return $existingCustomer['ID_KhachHang'];
    } else {
        // Create new customer
        $stmt = $pdo->prepare("
            INSERT INTO khachhanginfo (HoTen, SoDienThoai, DiaChi, NgaySinh, GhiChu, NgayTao)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $customerData['name'],
            $customerData['phone'],
            $customerData['address'] ?? null,
            $customerData['birthday'] ?? null,
            $customerData['notes'] ?? null
        ]);
        
        return $pdo->lastInsertId();
    }
}

function calculateTotalCost($eventData, $locationId, $equipment, $locationRentalType = null) {
    global $pdo;
    
    $totalCost = 0;
    
    // 1. Event type cost
    $stmt = $pdo->prepare("SELECT GiaCoBan FROM loaisukien WHERE ID_LoaiSK = ?");
    $stmt->execute([$eventData['type']]);
    $eventType = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($eventType && $eventType['GiaCoBan']) {
        $totalCost += floatval($eventType['GiaCoBan']);
    }
    
    // 2. Location cost
    if ($locationId) {
        // Kiểm tra xem địa điểm có phòng không (địa điểm trong nhà)
        $stmt = $pdo->prepare("SELECT LoaiDiaDiem FROM diadiem WHERE ID_DD = ?");
        $stmt->execute([$locationId]);
        $locationType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $roomId = $eventData['room_id'] ?? null;
        
        // Nếu là địa điểm trong nhà và có chọn phòng, tính giá theo phòng
        if ($locationType && $locationType['LoaiDiaDiem'] === 'Trong nhà' && $roomId) {
            $stmt = $pdo->prepare("SELECT GiaThueGio, GiaThueNgay, LoaiThue FROM phong WHERE ID_Phong = ?");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($room) {
                $startDate = new DateTime($eventData['startDate']);
                $endDate = new DateTime($eventData['endDate']);
                $durationHours = $startDate->diff($endDate)->h + ($startDate->diff($endDate)->days * 24);
                $durationDays = $startDate->diff($endDate)->days + ($durationHours > 0 ? 1 : 0);
                
                // Priority: User's selection > Database default
                if ($locationRentalType) {
                    if ($locationRentalType === 'hour' && $room['GiaThueGio']) {
                        $totalCost += $durationHours * floatval($room['GiaThueGio']);
                    } elseif ($locationRentalType === 'day' && $room['GiaThueNgay']) {
                        $totalCost += $durationDays * floatval($room['GiaThueNgay']);
                    }
                } elseif ($room['LoaiThue'] === 'Theo giờ' && $room['GiaThueGio']) {
                    $totalCost += $durationHours * floatval($room['GiaThueGio']);
                } elseif ($room['LoaiThue'] === 'Theo ngày' && $room['GiaThueNgay']) {
                    $totalCost += $durationDays * floatval($room['GiaThueNgay']);
                } elseif ($room['LoaiThue'] === 'Cả hai') {
                    // Use the cheaper option
                    $hourlyPrice = $durationHours * floatval($room['GiaThueGio'] ?? 0);
                    $dailyPrice = $durationDays * floatval($room['GiaThueNgay'] ?? 0);
                    if ($hourlyPrice > 0 && $dailyPrice > 0) {
                        $totalCost += min($hourlyPrice, $dailyPrice);
                    } elseif ($hourlyPrice > 0) {
                        $totalCost += $hourlyPrice;
                    } elseif ($dailyPrice > 0) {
                        $totalCost += $dailyPrice;
                    }
                }
            }
        } else {
            // Địa điểm ngoài trời hoặc không chọn phòng, tính giá theo địa điểm
            $stmt = $pdo->prepare("SELECT GiaThueGio, GiaThueNgay, LoaiThue FROM diadiem WHERE ID_DD = ?");
            $stmt->execute([$locationId]);
            $location = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($location) {
                $startDate = new DateTime($eventData['startDate']);
                $endDate = new DateTime($eventData['endDate']);
                $durationHours = $startDate->diff($endDate)->h + ($startDate->diff($endDate)->days * 24);
                $durationDays = $startDate->diff($endDate)->days + ($durationHours > 0 ? 1 : 0);
                
                // Priority: User's selection > Database default
                if ($locationRentalType) {
                    // User has explicitly chosen rental type
                    if ($locationRentalType === 'hour' && $location['GiaThueGio']) {
                        $totalCost += $durationHours * floatval($location['GiaThueGio']);
                    } elseif ($locationRentalType === 'day' && $location['GiaThueNgay']) {
                        $totalCost += $durationDays * floatval($location['GiaThueNgay']);
                    }
                } elseif ($location['LoaiThue'] === 'Theo giờ' && $location['GiaThueGio']) {
                    $totalCost += $durationHours * floatval($location['GiaThueGio']);
                } elseif ($location['LoaiThue'] === 'Theo ngày' && $location['GiaThueNgay']) {
                    $totalCost += $durationDays * floatval($location['GiaThueNgay']);
                } elseif ($location['LoaiThue'] === 'Cả hai') {
                    // Default to daily rental for better UX
                    $totalCost += $durationDays * floatval($location['GiaThueNgay'] ?? 0);
                }
            }
        }
    }
    
    // 3. Equipment cost
    foreach ($equipment as $item) {
        $itemCost = $item['price'] * $item['quantity'];
        $totalCost += $itemCost;
    }
    
    return $totalCost;
}

function createEventRegistration($eventData, $customerId, $locationId, $totalCost, $adminNotes, $locationRentalType = null) {
    global $pdo;
    
    // Convert location_rental_type to database enum values
    $loaiThueApDung = null;
    if ($locationRentalType === 'hour') {
        $loaiThueApDung = 'Theo giờ';
    } elseif ($locationRentalType === 'day') {
        $loaiThueApDung = 'Theo ngày';
    }
    
    $roomId = $eventData['room_id'] ?? null;
    
    $stmt = $pdo->prepare("
        INSERT INTO datlichsukien (
            TenSuKien, MoTa, NgayBatDau, NgayKetThuc, SoNguoiDuKien, 
            NganSach, TongTien, TrangThaiDuyet, TrangThaiThanhToan, 
            GhiChu, NgayTao, ID_KhachHang, ID_DD, ID_LoaiSK, LoaiThueApDung, ID_Phong
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ duyệt', 'Chưa thanh toán', ?, NOW(), ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $eventData['name'],
        $eventData['description'] ?? null,
        $eventData['startDate'],
        $eventData['endDate'],
        $eventData['expectedGuests'] ?? 50,
        $eventData['budget'] ?? 0,
        $totalCost,
        $adminNotes,
        $customerId,
        $locationId,
        $eventData['type'],
        $loaiThueApDung,
        $roomId
    ]);
    
    return $pdo->lastInsertId();
}

function addEquipmentToRegistration($eventId, $equipment) {
    global $pdo;
    
    foreach ($equipment as $item) {
        if ($item['type'] === 'equipment') {
            $stmt = $pdo->prepare("
                INSERT INTO chitietdatsukien (ID_DatLich, ID_TB, SoLuong, DonGia, GhiChu)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $eventId,
                $item['id'],
                $item['quantity'],
                $item['price'],
                "Thiết bị: {$item['name']}"
            ]);
        } elseif ($item['type'] === 'combo') {
            $stmt = $pdo->prepare("
                INSERT INTO chitietdatsukien (ID_DatLich, ID_Combo, SoLuong, DonGia, GhiChu)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $eventId,
                $item['id'],
                $item['quantity'],
                $item['price'],
                "Combo: {$item['name']}"
            ]);
        }
    }
}
?>
