<?php
ob_start();
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập - cho phép tất cả người dùng đã đăng nhập
// Một số action chỉ dành cho admin sẽ được kiểm tra riêng
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Vui lòng đăng nhập']);
    exit();
}

$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;

$pdo = getDBConnection();

// Lấy action từ GET, POST, hoặc JSON body
// Ưu tiên: GET > POST > JSON body
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Nếu action vẫn rỗng và là POST request, thử đọc từ JSON body
if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonInput = file_get_contents('php://input');
    if (!empty($jsonInput)) {
        $jsonData = json_decode($jsonInput, true);
        if ($jsonData && isset($jsonData['action'])) {
            $action = $jsonData['action'];
        }
    }
}

// Debug logging
error_log("Rooms controller - Action received: " . $action);
error_log("Rooms controller - GET: " . json_encode($_GET));
error_log("Rooms controller - POST: " . json_encode($_POST));
error_log("Rooms controller - Request method: " . $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonInput = file_get_contents('php://input');
    error_log("Rooms controller - JSON input: " . $jsonInput);
}

// Kiểm tra action có rỗng không
if (empty($action)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Action không được cung cấp',
        'debug' => [
            'get' => $_GET,
            'post' => $_POST,
            'method' => $_SERVER['REQUEST_METHOD'],
            'json_input' => $_SERVER['REQUEST_METHOD'] === 'POST' ? file_get_contents('php://input') : null
        ]
    ]);
    exit();
}

try {
    switch ($action) {
        case 'get_rooms':
            // Lấy danh sách phòng theo địa điểm - Cho phép tất cả người dùng đã đăng nhập
            // (Cần thiết cho form đăng ký sự kiện)
            $locationId = $_GET['location_id'] ?? null;
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $whereConditions = [];
            $params = [];
            
            if ($locationId) {
                $whereConditions[] = "p.ID_DD = ?";
                $params[] = $locationId;
            }
            
            if ($search) {
                $whereConditions[] = "(p.TenPhong LIKE ? OR p.MoTa LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($status) {
                $whereConditions[] = "p.TrangThai = ?";
                $params[] = $status;
            }
            
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
            
            $sql = "SELECT p.*, d.TenDiaDiem, d.LoaiDiaDiem 
                    FROM phong p 
                    INNER JOIN diadiem d ON p.ID_DD = d.ID_DD 
                    {$whereClause}
                    ORDER BY d.TenDiaDiem, p.TenPhong";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $rooms]);
            break;
            
        case 'get_room':
            // Lấy thông tin một phòng - Cho phép tất cả người dùng đã đăng nhập
            // (Cần thiết cho form đăng ký sự kiện)
            $roomId = $_GET['id'] ?? null;
            if (!$roomId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID phòng']);
                break;
            }
            
            $stmt = $pdo->prepare("SELECT p.*, d.TenDiaDiem, d.LoaiDiaDiem 
                                   FROM phong p 
                                   INNER JOIN diadiem d ON p.ID_DD = d.ID_DD 
                                   WHERE p.ID_Phong = ?");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$room) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy phòng']);
                break;
            }
            
            echo json_encode(['success' => true, 'data' => $room]);
            break;
            
        case 'add_room':
            // Thêm phòng mới - Chỉ admin mới có quyền
            if (!in_array($userRole, [1, 2, 3])) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                break;
            }
            // Hỗ trợ cả JSON và form data
            $data = [];
            
            // Ưu tiên đọc từ JSON body nếu là POST request
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $jsonInput = file_get_contents('php://input');
                if (!empty($jsonInput)) {
                    $jsonData = json_decode($jsonInput, true);
                    if ($jsonData && is_array($jsonData)) {
                        $data = $jsonData;
                    }
                }
            }
            
            // Nếu không có dữ liệu từ JSON, dùng $_POST
            if (empty($data)) {
                $data = $_POST;
            }
            
            // Debug logging
            error_log("Add room - Data received: " . json_encode($data));
            
            // Validate các trường bắt buộc
            $required = ['ID_DD', 'TenPhong', 'SucChua'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '') || $data[$field] === null) {
                    echo json_encode(['success' => false, 'error' => "Thiếu thông tin bắt buộc: {$field}"]);
                    exit();
                }
            }
            
            // Validate SucChua phải là số nguyên dương
            if (!is_numeric($data['SucChua']) || intval($data['SucChua']) < 1) {
                echo json_encode(['success' => false, 'error' => 'Sức chứa phải là số nguyên dương (ít nhất 1 người)']);
                exit();
            }
            
            // Validate TenPhong không được trống sau khi trim
            $tenPhong = trim($data['TenPhong']);
            if (empty($tenPhong)) {
                echo json_encode(['success' => false, 'error' => 'Tên phòng không được để trống']);
                exit();
            }
            
            // Kiểm tra địa điểm có tồn tại và là địa điểm trong nhà không
            $stmt = $pdo->prepare("SELECT LoaiDiaDiem, TenDiaDiem FROM diadiem WHERE ID_DD = ?");
            $stmt->execute([$data['ID_DD']]);
            $location = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$location) {
                echo json_encode(['success' => false, 'error' => 'Địa điểm không tồn tại']);
                exit();
            }
            
            if ($location['LoaiDiaDiem'] !== 'Trong nhà') {
                echo json_encode(['success' => false, 'error' => 'Chỉ có thể thêm phòng cho địa điểm trong nhà']);
                exit();
            }
            
            // Validate giá thuê nếu có
            if (isset($data['GiaThueGio']) && $data['GiaThueGio'] !== null && $data['GiaThueGio'] !== '') {
                if (!is_numeric($data['GiaThueGio']) || floatval($data['GiaThueGio']) < 0) {
                    echo json_encode(['success' => false, 'error' => 'Giá thuê/giờ phải là số không âm']);
                    exit();
                }
            }
            
            if (isset($data['GiaThueNgay']) && $data['GiaThueNgay'] !== null && $data['GiaThueNgay'] !== '') {
                if (!is_numeric($data['GiaThueNgay']) || floatval($data['GiaThueNgay']) < 0) {
                    echo json_encode(['success' => false, 'error' => 'Giá thuê/ngày phải là số không âm']);
                    exit();
                }
            }
            
            // Validate LoaiThue
            $loaiThue = $data['LoaiThue'] ?? 'Cả hai';
            if (!in_array($loaiThue, ['Theo giờ', 'Theo ngày', 'Cả hai'])) {
                $loaiThue = 'Cả hai';
            }
            
            // Validate TrangThai
            $trangThai = $data['TrangThai'] ?? 'Sẵn sàng';
            if (!in_array($trangThai, ['Sẵn sàng', 'Đang sử dụng', 'Bảo trì'])) {
                $trangThai = 'Sẵn sàng';
            }
            
            $sql = "INSERT INTO phong (ID_DD, TenPhong, SucChua, GiaThueGio, GiaThueNgay, LoaiThue, MoTa, TrangThai) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $data['ID_DD'],
                $tenPhong,
                intval($data['SucChua']),
                isset($data['GiaThueGio']) && $data['GiaThueGio'] !== '' ? floatval($data['GiaThueGio']) : null,
                isset($data['GiaThueNgay']) && $data['GiaThueNgay'] !== '' ? floatval($data['GiaThueNgay']) : null,
                $loaiThue,
                isset($data['MoTa']) ? trim($data['MoTa']) : null,
                $trangThai
            ]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Thêm phòng thành công', 'id' => $pdo->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Lỗi khi thêm phòng vào database']);
            }
            break;
            
        case 'update_room':
            // Cập nhật phòng - Chỉ admin mới có quyền
            if (!in_array($userRole, [1, 2, 3])) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                break;
            }
            // Hỗ trợ cả JSON và form data
            $data = [];
            
            // Ưu tiên đọc từ JSON body nếu là POST request
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $jsonInput = file_get_contents('php://input');
                if (!empty($jsonInput)) {
                    $jsonData = json_decode($jsonInput, true);
                    if ($jsonData && is_array($jsonData)) {
                        $data = $jsonData;
                    }
                }
            }
            
            // Nếu không có dữ liệu từ JSON, dùng $_POST
            if (empty($data)) {
                $data = $_POST;
            }
            
            // Debug logging
            error_log("Update room - Data received: " . json_encode($data));
            
            $roomId = $data['ID_Phong'] ?? null;
            
            if (!$roomId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID phòng']);
                exit();
            }
            
            // Kiểm tra phòng có tồn tại không
            $stmt = $pdo->prepare("SELECT p.*, d.LoaiDiaDiem FROM phong p 
                                  INNER JOIN diadiem d ON p.ID_DD = d.ID_DD 
                                  WHERE p.ID_Phong = ?");
            $stmt->execute([$roomId]);
            $existingRoom = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingRoom) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy phòng']);
                exit();
            }
            
            // Validate các trường bắt buộc
            if (!isset($data['TenPhong']) || (is_string($data['TenPhong']) && trim($data['TenPhong']) === '')) {
                echo json_encode(['success' => false, 'error' => 'Tên phòng không được để trống']);
                exit();
            }
            
            if (!isset($data['SucChua']) || !is_numeric($data['SucChua']) || intval($data['SucChua']) < 1) {
                echo json_encode(['success' => false, 'error' => 'Sức chứa phải là số nguyên dương (ít nhất 1 người)']);
                exit();
            }
            
            // Nếu có thay đổi địa điểm, kiểm tra địa điểm mới có phải trong nhà không
            if (isset($data['ID_DD']) && $data['ID_DD'] != $existingRoom['ID_DD']) {
                $stmt = $pdo->prepare("SELECT LoaiDiaDiem, TenDiaDiem FROM diadiem WHERE ID_DD = ?");
                $stmt->execute([$data['ID_DD']]);
                $location = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$location) {
                    echo json_encode(['success' => false, 'error' => 'Địa điểm không tồn tại']);
                    exit();
                }
                
                if ($location['LoaiDiaDiem'] !== 'Trong nhà') {
                    echo json_encode(['success' => false, 'error' => 'Chỉ có thể thêm phòng cho địa điểm trong nhà']);
                    exit();
                }
                
                // Kiểm tra xem có sự kiện nào đang sử dụng phòng này không (nếu chuyển địa điểm)
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM datlichsukien 
                                      WHERE ID_Phong = ? 
                                      AND TrangThaiDuyet != 'Từ chối'");
                $stmt->execute([$roomId]);
                $eventCount = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($eventCount['count'] > 0) {
                    echo json_encode(['success' => false, 'error' => 'Không thể thay đổi địa điểm vì phòng đang có sự kiện đã được đặt']);
                    exit();
                }
            }
            
            // Kiểm tra địa điểm hiện tại của phòng có phải trong nhà không
            if ($existingRoom['LoaiDiaDiem'] !== 'Trong nhà') {
                echo json_encode(['success' => false, 'error' => 'Phòng này thuộc địa điểm không phải trong nhà']);
                exit();
            }
            
            // Validate giá thuê nếu có
            if (isset($data['GiaThueGio']) && $data['GiaThueGio'] !== null && $data['GiaThueGio'] !== '') {
                if (!is_numeric($data['GiaThueGio']) || floatval($data['GiaThueGio']) < 0) {
                    echo json_encode(['success' => false, 'error' => 'Giá thuê/giờ phải là số không âm']);
                    exit();
                }
            }
            
            if (isset($data['GiaThueNgay']) && $data['GiaThueNgay'] !== null && $data['GiaThueNgay'] !== '') {
                if (!is_numeric($data['GiaThueNgay']) || floatval($data['GiaThueNgay']) < 0) {
                    echo json_encode(['success' => false, 'error' => 'Giá thuê/ngày phải là số không âm']);
                    exit();
                }
            }
            
            // Validate LoaiThue
            $loaiThue = $data['LoaiThue'] ?? $existingRoom['LoaiThue'] ?? 'Cả hai';
            if (!in_array($loaiThue, ['Theo giờ', 'Theo ngày', 'Cả hai'])) {
                $loaiThue = 'Cả hai';
            }
            
            // Validate TrangThai
            $trangThai = $data['TrangThai'] ?? $existingRoom['TrangThai'] ?? 'Sẵn sàng';
            if (!in_array($trangThai, ['Sẵn sàng', 'Đang sử dụng', 'Bảo trì'])) {
                $trangThai = 'Sẵn sàng';
            }
            
            $sql = "UPDATE phong SET 
                    " . (isset($data['ID_DD']) && $data['ID_DD'] != $existingRoom['ID_DD'] ? "ID_DD = ?, " : "") . "
                    TenPhong = ?, 
                    SucChua = ?, 
                    GiaThueGio = ?, 
                    GiaThueNgay = ?, 
                    LoaiThue = ?, 
                    MoTa = ?, 
                    TrangThai = ? 
                    WHERE ID_Phong = ?";
            
            $params = [];
            if (isset($data['ID_DD']) && $data['ID_DD'] != $existingRoom['ID_DD']) {
                $params[] = $data['ID_DD'];
            }
            $params[] = trim($data['TenPhong']);
            $params[] = intval($data['SucChua']);
            $params[] = isset($data['GiaThueGio']) && $data['GiaThueGio'] !== '' ? floatval($data['GiaThueGio']) : null;
            $params[] = isset($data['GiaThueNgay']) && $data['GiaThueNgay'] !== '' ? floatval($data['GiaThueNgay']) : null;
            $params[] = $loaiThue;
            $params[] = isset($data['MoTa']) ? trim($data['MoTa']) : null;
            $params[] = $trangThai;
            $params[] = $roomId;
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật phòng thành công']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Lỗi khi cập nhật phòng vào database']);
            }
            break;
            
        case 'delete_room':
            // Xóa phòng - Chỉ admin mới có quyền
            if (!in_array($userRole, [1, 2, 3])) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                break;
            }
            $roomId = $_POST['id'] ?? null;
            if (!$roomId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID phòng']);
                exit();
            }
            
            // Kiểm tra phòng có tồn tại không
            $stmt = $pdo->prepare("SELECT p.*, d.TenDiaDiem FROM phong p 
                                  INNER JOIN diadiem d ON p.ID_DD = d.ID_DD 
                                  WHERE p.ID_Phong = ?");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$room) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy phòng']);
                exit();
            }
            
            // QUAN TRỌNG: Kiểm tra sự kiện đang diễn ra (NgayBatDau <= NOW() AND NgayKetThuc >= NOW())
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count, 
                       GROUP_CONCAT(DISTINCT TenSuKien SEPARATOR ', ') as events
                FROM datlichsukien 
                WHERE ID_Phong = ? 
                AND TrangThaiDuyet = 'Đã duyệt'
                AND NgayBatDau <= NOW() 
                AND NgayKetThuc >= NOW()
            ");
            $stmt->execute([$roomId]);
            $activeEvent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($activeEvent['count'] > 0) {
                echo json_encode([
                    'success' => false, 
                    'error' => "Không thể xóa phòng vì đang có sự kiện đang diễn ra: {$activeEvent['events']}"
                ]);
                exit();
            }
            
            // Kiểm tra sự kiện sắp diễn ra (NgayBatDau > NOW() AND TrangThaiDuyet = 'Đã duyệt')
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count, 
                       GROUP_CONCAT(DISTINCT TenSuKien SEPARATOR ', ') as events
                FROM datlichsukien 
                WHERE ID_Phong = ? 
                AND TrangThaiDuyet = 'Đã duyệt'
                AND NgayBatDau > NOW()
            ");
            $stmt->execute([$roomId]);
            $upcomingEvent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($upcomingEvent['count'] > 0) {
                echo json_encode([
                    'success' => false, 
                    'error' => "Không thể xóa phòng vì đang có sự kiện sắp diễn ra: {$upcomingEvent['events']}"
                ]);
                exit();
            }
            
            // Kiểm tra sự kiện đã được đặt (kể cả chưa duyệt)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM datlichsukien 
                WHERE ID_Phong = ? 
                AND TrangThaiDuyet != 'Từ chối'
            ");
            $stmt->execute([$roomId]);
            $bookedEvent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($bookedEvent['count'] > 0) {
                echo json_encode([
                    'success' => false, 
                    'error' => "Không thể xóa phòng vì đang có {$bookedEvent['count']} sự kiện đã được đặt (kể cả chưa duyệt)"
                ]);
                exit();
            }
            
            // Xóa phòng
            $stmt = $pdo->prepare("DELETE FROM phong WHERE ID_Phong = ?");
            $stmt->execute([$roomId]);
            
            echo json_encode(['success' => true, 'message' => 'Xóa phòng thành công']);
            break;
            
        case 'get_available_rooms':
            // Lấy danh sách phòng có sẵn trong khoảng thời gian - Cho phép tất cả người dùng đã đăng nhập
            // (Cần thiết cho form đăng ký sự kiện)
            $locationId = $_GET['location_id'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            if (!$locationId || !$startDate || !$endDate) {
                echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
                break;
            }
            
            // Lấy tất cả phòng của địa điểm
            $stmt = $pdo->prepare("SELECT * FROM phong WHERE ID_DD = ? AND TrangThai = 'Sẵn sàng'");
            $stmt->execute([$locationId]);
            $allRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Lấy các phòng đã được đặt trong khoảng thời gian này
            $stmt = $pdo->prepare("SELECT DISTINCT ID_Phong FROM datlichsukien 
                                   WHERE ID_DD = ? 
                                   AND ID_Phong IS NOT NULL
                                   AND TrangThaiDuyet != 'Từ chối'
                                   AND (
                                       (NgayBatDau <= ? AND NgayKetThuc >= ?) OR
                                       (NgayBatDau <= ? AND NgayKetThuc >= ?) OR
                                       (NgayBatDau >= ? AND NgayKetThuc <= ?)
                                   )");
            $stmt->execute([$locationId, $startDate, $startDate, $endDate, $endDate, $startDate, $endDate]);
            $bookedRooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Lọc ra các phòng có sẵn
            $availableRooms = array_filter($allRooms, function($room) use ($bookedRooms) {
                return !in_array($room['ID_Phong'], $bookedRooms);
            });
            
            echo json_encode(['success' => true, 'data' => array_values($availableRooms)]);
            break;
            
        default:
            error_log("Rooms controller - Invalid action: " . $action);
            echo json_encode([
                'success' => false, 
                'error' => 'Action không hợp lệ: ' . htmlspecialchars($action),
                'debug' => [
                    'received_action' => $action,
                    'available_actions' => ['get_rooms', 'get_room', 'add_room', 'update_room', 'delete_room', 'get_available_rooms']
                ]
            ]);
            break;
    }
} catch (Exception $e) {
    error_log("Rooms controller error: " . $e->getMessage());
    error_log("Rooms controller error trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'error' => 'Lỗi server: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}


