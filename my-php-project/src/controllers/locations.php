<?php
ob_start();
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập']);
    exit();
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Các action yêu cầu quyền admin
$adminOnlyActions = ['add_location', 'update_location', 'delete_location', 'get_locations'];

// Kiểm tra quyền admin cho các action chỉ dành cho admin
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (in_array($action, $adminOnlyActions) && !in_array($userRole, [1, 2, 3, 4])) {
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit();
}

try {
    switch ($action) {
        case 'test':
            echo json_encode(['success' => true, 'message' => 'Controller hoạt động bình thường']);
            break;
            
        case 'get_locations':
            // Lấy danh sách địa điểm với filter
            $limit = (int)($_GET['limit'] ?? 10);
            $limit = $limit > 0 ? $limit : 10;
            
            // Lấy filter parameters
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $type = $_GET['type'] ?? '';
            $sortBy = $_GET['sort_by'] ?? 'TenDiaDiem';
            
            // Đảm bảo bảng và cột tồn tại
            $checkTable = $pdo->query("SHOW TABLES LIKE 'diadiem'");
            if ($checkTable->rowCount() == 0) {
                echo json_encode(['success' => false, 'error' => 'Bảng diadiem không tồn tại']);
                break;
            }
            
            $columns = $pdo->query("SHOW COLUMNS FROM diadiem LIKE 'TrangThaiHoatDong'")->fetchAll();
            if (count($columns) == 0) {
                $pdo->exec("ALTER TABLE diadiem ADD COLUMN TrangThaiHoatDong VARCHAR(50) DEFAULT 'Hoạt động'");
            }
            
            // Xây dựng query với bộ lọc
            $whereConditions = [];
            $params = [];
            
            // Bộ lọc tìm kiếm
            if (!empty($search)) {
                $whereConditions[] = "(TenDiaDiem LIKE ? OR DiaChi LIKE ? OR MoTa LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            // Bộ lọc trạng thái
            if (!empty($status)) {
                $whereConditions[] = "TrangThaiHoatDong = ?";
                $params[] = $status;
            }
            
            // Bộ lọc loại
            if (!empty($type)) {
                $whereConditions[] = "LoaiDiaDiem = ?";
                $params[] = $type;
            }
            
            // Xây dựng mệnh đề WHERE
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            // Xác thực cột sắp xếp
            $allowedSortColumns = ['TenDiaDiem', 'DiaChi', 'SucChua', 'NgayTao', 'GiaThueGio', 'GiaThueNgay'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'TenDiaDiem';
            }
            
            // Xây dựng query
            $query = "SELECT * FROM diadiem {$whereClause} ORDER BY {$sortBy} LIMIT {$limit}";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'locations' => $locations]);
            break;
            
        case 'get_location':
            // Lấy thông tin địa điểm theo ID kèm danh sách phòng (nếu là địa điểm trong nhà)
            $locationId = $_POST['id'] ?? $_GET['id'] ?? null;
            $eventDate = $_GET['event_date'] ?? $_POST['event_date'] ?? null;
            $eventEndDate = $_GET['event_end_date'] ?? $_POST['event_end_date'] ?? null;
            
            if (!$locationId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID địa điểm']);
                break;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM diadiem WHERE ID_DD = ?");
            $stmt->execute([$locationId]);
            $location = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($location) {
                // Nếu là địa điểm trong nhà, lấy danh sách phòng sẵn sàng
                $rooms = [];
                if ($location['LoaiDiaDiem'] === 'Trong nhà' || $location['LoaiDiaDiem'] === 'Trong nha') {
                    // Lấy tất cả phòng có trạng thái "Sẵn sàng"
                    $stmt = $pdo->prepare("SELECT * FROM phong WHERE ID_DD = ? AND TrangThai = 'Sẵn sàng' ORDER BY TenPhong");
                    $stmt->execute([$locationId]);
                    $allRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Nếu có ngày sự kiện, lọc các phòng đã bị đặt trong khoảng thời gian đó
                    if ($eventDate && $eventEndDate) {
                        // Tạo datetime từ eventDate và eventEndDate (nếu có thời gian cụ thể thì dùng, không thì dùng 00:00:00 và 23:59:59)
                        $startDateTime = $eventDate . ' 00:00:00';
                        $endDateTime = $eventEndDate . ' 23:59:59';
                        
                        // Lấy danh sách phòng đã được đặt trong khoảng thời gian này
                        // Kiểm tra xem có overlap thời gian không
                        $stmt = $pdo->prepare("SELECT DISTINCT ID_Phong FROM datlichsukien 
                                               WHERE ID_DD = ? 
                                               AND ID_Phong IS NOT NULL
                                               AND TrangThaiDuyet != 'Từ chối'
                                               AND TrangThaiDuyet != 'Đã hủy'
                                               AND (
                                                   (NgayBatDau <= ? AND NgayKetThuc >= ?) OR
                                                   (NgayBatDau <= ? AND NgayKetThuc >= ?) OR
                                                   (NgayBatDau >= ? AND NgayKetThuc <= ?)
                                               )");
                        $stmt->execute([$locationId, $startDateTime, $startDateTime, $endDateTime, $endDateTime, $startDateTime, $endDateTime]);
                        $bookedRoomIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Lọc ra các phòng chưa bị đặt
                        $rooms = array_filter($allRooms, function($room) use ($bookedRoomIds) {
                            return !in_array($room['ID_Phong'], $bookedRoomIds);
                        });
                        $rooms = array_values($rooms);
                        
                        error_log("Filtered rooms: Total=" . count($allRooms) . ", Booked=" . count($bookedRoomIds) . ", Available=" . count($rooms));
                    } else {
                        // Nếu không có ngày, trả về tất cả phòng sẵn sàng
                        $rooms = $allRooms;
                    }
                    
                    // Debug log
                    error_log("=== DEBUG get_location ===");
                    error_log("Location ID: " . $locationId);
                    error_log("LoaiDiaDiem: " . $location['LoaiDiaDiem']);
                    error_log("Event Date: " . ($eventDate ?? 'null'));
                    error_log("Event End Date: " . ($eventEndDate ?? 'null'));
                    error_log("All rooms count: " . count($allRooms ?? []));
                    error_log("Available rooms count: " . count($rooms));
                    
                    // Log room details và đảm bảo giá trị đúng format
                    foreach ($rooms as &$room) {
                        // Đảm bảo GiaThueGio và GiaThueNgay là số (không phải string)
                        $room['GiaThueGio'] = $room['GiaThueGio'] !== null ? (float)$room['GiaThueGio'] : null;
                        $room['GiaThueNgay'] = $room['GiaThueNgay'] !== null ? (float)$room['GiaThueNgay'] : null;
                        
                        error_log("Room: ID={$room['ID_Phong']}, TenPhong={$room['TenPhong']}, GiaThueGio={$room['GiaThueGio']}, GiaThueNgay={$room['GiaThueNgay']}, LoaiThue={$room['LoaiThue']}, TrangThai={$room['TrangThai']}");
                    }
                    unset($room); // Unset reference
                }
                
                $location['rooms'] = $rooms;
                $response = ['success' => true, 'location' => $location];
                error_log("API Response: " . json_encode($response, JSON_UNESCAPED_UNICODE));
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy địa điểm']);
            }
            break;
            
        case 'add_location':
            // Thêm địa điểm mới
            $input = $_POST;
            
            // Debug: Ghi log dữ liệu nhận được
            error_log("Add location - Received data: " . json_encode($input));
            error_log("Add location - FILES data: " . json_encode($_FILES));
            
            // QuanHuyen và TinhThanh là bắt buộc, các trường khác có thể để trống
            $requiredFields = ['TenDiaDiem', 'LoaiDiaDiem', 'SucChua', 'LoaiThue', 'QuanHuyen', 'TinhThanh'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                    exit();
                }
            }
            
            // Xác thực LoaiDiaDiem
            $allowedTypes = ['Trong nhà', 'Ngoài trời'];
            if (!in_array($input['LoaiDiaDiem'], $allowedTypes)) {
                echo json_encode(['success' => false, 'error' => 'Loại địa điểm không hợp lệ']);
                break;
            }
            
            // Xác thực sức chứa
            if (!is_numeric($input['SucChua']) || $input['SucChua'] <= 0) {
                echo json_encode(['success' => false, 'error' => 'Sức chứa phải là số dương']);
                break;
            }
            
            // Kiểm tra tên địa điểm đã tồn tại chưa
            $stmt = $pdo->prepare("SELECT ID_DD FROM diadiem WHERE TenDiaDiem = ?");
            $stmt->execute([$input['TenDiaDiem']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Tên địa điểm đã tồn tại']);
                break;
            }
            
            // Xử lý upload hình ảnh
            $imageName = null;
            if (isset($_FILES['HinhAnh']) && $_FILES['HinhAnh']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../img/diadiem/';
                
                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileInfo = pathinfo($_FILES['HinhAnh']['name']);
                $imageName = uniqid() . '_' . time() . '.' . $fileInfo['extension'];
                $uploadPath = $uploadDir . $imageName;
                
                // Xác thực loại file
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array(strtolower($fileInfo['extension']), $allowedTypes)) {
                    echo json_encode(['success' => false, 'error' => 'Định dạng file không được hỗ trợ. Chỉ chấp nhận: jpg, jpeg, png, gif, webp']);
                    break;
                }
                
                // Xác thực kích thước file (tối đa 5MB)
                if ($_FILES['HinhAnh']['size'] > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'error' => 'Kích thước file quá lớn. Tối đa 5MB']);
                    break;
                }
                
                // Di chuyển file đã upload
                if (!move_uploaded_file($_FILES['HinhAnh']['tmp_name'], $uploadPath)) {
                    echo json_encode(['success' => false, 'error' => 'Không thể upload file']);
                    break;
                }
            }
            
            // Nếu loại địa điểm là "Trong nhà", set giá thuê về null
            $loaiDiaDiem = $input['LoaiDiaDiem'] ?? 'Trong nhà';
            $giaThueGio = null;
            $giaThueNgay = null;
            $loaiThue = null;
            
            if ($loaiDiaDiem === 'Ngoài trời') {
                $giaThueGio = !empty($input['GiaThueGio']) ? $input['GiaThueGio'] : null;
                $giaThueNgay = !empty($input['GiaThueNgay']) ? $input['GiaThueNgay'] : null;
                $loaiThue = $input['LoaiThue'] ?? 'Cả hai';
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO diadiem (TenDiaDiem, LoaiDiaDiem, DiaChi, SoNha, DuongPho, PhuongXa, QuanHuyen, TinhThanh, SucChua, GiaThueGio, GiaThueNgay, LoaiThue, MoTa, TrangThaiHoatDong, HinhAnh) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $input['TenDiaDiem'],
                $loaiDiaDiem,
                $input['DiaChi'] ?? '', // Địa chỉ đầy đủ (sẽ được trigger tự động tạo nếu không có)
                $input['SoNha'] ?? null,
                $input['DuongPho'] ?? null,
                $input['PhuongXa'] ?? null,
                $input['QuanHuyen'] ?? null,
                $input['TinhThanh'] ?? null,
                $input['SucChua'],
                $giaThueGio,
                $giaThueNgay,
                $loaiThue,
                $input['MoTa'] ?? '',
                $input['TrangThaiHoatDong'] ?? 'Hoạt động',
                $imageName
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Thêm địa điểm thành công']);
            break;
            
        case 'update_location':
            // Cập nhật địa điểm
            $input = $_POST;
            
            // Debug: Ghi log dữ liệu nhận được
            error_log("Update location - Received data: " . json_encode($input));
            
            $locationId = $input['id'] ?? null;
            if (!$locationId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID địa điểm']);
                exit();
            }
            
            // QuanHuyen và TinhThanh là bắt buộc, LoaiThue chỉ bắt buộc cho địa điểm ngoài trời
            $requiredFields = ['TenDiaDiem', 'LoaiDiaDiem', 'SucChua', 'QuanHuyen', 'TinhThanh'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                    exit();
                }
            }
            
            // Xác thực LoaiDiaDiem
            $allowedTypes = ['Trong nhà', 'Ngoài trời'];
            if (!in_array($input['LoaiDiaDiem'], $allowedTypes)) {
                echo json_encode(['success' => false, 'error' => 'Loại địa điểm không hợp lệ']);
                break;
            }
            
            // LoaiThue chỉ bắt buộc cho địa điểm ngoài trời
            if (($input['LoaiDiaDiem'] ?? '') === 'Ngoài trời' && empty($input['LoaiThue'])) {
                echo json_encode(['success' => false, 'error' => 'Loại thuê là bắt buộc cho địa điểm ngoài trời']);
                break;
            }
            
            // Xác thực LoaiThue (nếu có)
            if (!empty($input['LoaiThue'])) {
                $allowedRentTypes = ['Theo giờ', 'Theo ngày', 'Cả hai'];
                if (!in_array($input['LoaiThue'], $allowedRentTypes)) {
                    echo json_encode(['success' => false, 'error' => 'Loại thuê không hợp lệ']);
                    break;
                }
            }
            
            // Xác thực sức chứa
            if (!is_numeric($input['SucChua']) || $input['SucChua'] <= 0) {
                echo json_encode(['success' => false, 'error' => 'Sức chứa phải là số dương']);
                break;
            }
            
            // Kiểm tra địa điểm có tồn tại không
            $stmt = $pdo->prepare("SELECT TenDiaDiem FROM diadiem WHERE ID_DD = ?");
            $stmt->execute([$locationId]);
            $existingLocation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingLocation) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy địa điểm']);
                break;
            }
            
            // Kiểm tra tên mới có trùng với địa điểm khác không
            if ($input['TenDiaDiem'] !== $existingLocation['TenDiaDiem']) {
                $stmt = $pdo->prepare("SELECT ID_DD FROM diadiem WHERE TenDiaDiem = ? AND ID_DD != ?");
                $stmt->execute([$input['TenDiaDiem'], $locationId]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Tên địa điểm đã tồn tại']);
                    break;
                }
            }
            
            // Xử lý upload hình ảnh mới (nếu có)
            $imageName = null;
            if (isset($_FILES['HinhAnh']) && $_FILES['HinhAnh']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../img/diadiem/';
                
                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileInfo = pathinfo($_FILES['HinhAnh']['name']);
                $imageName = uniqid() . '_' . time() . '.' . $fileInfo['extension'];
                $uploadPath = $uploadDir . $imageName;
                
                // Xác thực loại file
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array(strtolower($fileInfo['extension']), $allowedTypes)) {
                    echo json_encode(['success' => false, 'error' => 'Định dạng file không được hỗ trợ. Chỉ chấp nhận: jpg, jpeg, png, gif, webp']);
                    break;
                }
                
                // Xác thực kích thước file (tối đa 5MB)
                if ($_FILES['HinhAnh']['size'] > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'error' => 'Kích thước file quá lớn. Tối đa 5MB']);
                    break;
                }
                
                // Di chuyển file đã upload
                if (!move_uploaded_file($_FILES['HinhAnh']['tmp_name'], $uploadPath)) {
                    echo json_encode(['success' => false, 'error' => 'Không thể upload file']);
                    break;
                }
            }
            
            // Nếu loại địa điểm là "Trong nhà", set giá thuê về null
            $loaiDiaDiem = $input['LoaiDiaDiem'] ?? 'Trong nhà';
            $giaThueGio = null;
            $giaThueNgay = null;
            $loaiThue = null;
            
            if ($loaiDiaDiem === 'Ngoài trời') {
                $giaThueGio = !empty($input['GiaThueGio']) ? $input['GiaThueGio'] : null;
                $giaThueNgay = !empty($input['GiaThueNgay']) ? $input['GiaThueNgay'] : null;
                $loaiThue = $input['LoaiThue'] ?? 'Cả hai';
            }
            
            // Nếu có hình ảnh mới, cập nhật cả hình ảnh
            if ($imageName) {
                $stmt = $pdo->prepare("
                    UPDATE diadiem 
                    SET TenDiaDiem = ?, LoaiDiaDiem = ?, DiaChi = ?, SoNha = ?, DuongPho = ?, PhuongXa = ?, QuanHuyen = ?, TinhThanh = ?, SucChua = ?, GiaThueGio = ?, GiaThueNgay = ?, LoaiThue = ?, MoTa = ?, TrangThaiHoatDong = ?, HinhAnh = ?
                    WHERE ID_DD = ?
                ");
                
                $stmt->execute([
                    $input['TenDiaDiem'],
                    $loaiDiaDiem,
                    $input['DiaChi'] ?? '', // Địa chỉ đầy đủ (sẽ được trigger tự động tạo nếu không có)
                    $input['SoNha'] ?? null,
                    $input['DuongPho'] ?? null,
                    $input['PhuongXa'] ?? null,
                    $input['QuanHuyen'] ?? null,
                    $input['TinhThanh'] ?? null,
                    $input['SucChua'],
                    $giaThueGio,
                    $giaThueNgay,
                    $loaiThue,
                    $input['MoTa'] ?? '',
                    $input['TrangThaiHoatDong'] ?? 'Hoạt động',
                    $imageName,
                    $locationId
                ]);
            } else {
                // Nếu không có hình ảnh mới, chỉ cập nhật thông tin khác
                $stmt = $pdo->prepare("
                    UPDATE diadiem 
                    SET TenDiaDiem = ?, LoaiDiaDiem = ?, DiaChi = ?, SoNha = ?, DuongPho = ?, PhuongXa = ?, QuanHuyen = ?, TinhThanh = ?, SucChua = ?, GiaThueGio = ?, GiaThueNgay = ?, LoaiThue = ?, MoTa = ?, TrangThaiHoatDong = ?
                    WHERE ID_DD = ?
                ");
                
                $stmt->execute([
                    $input['TenDiaDiem'],
                    $loaiDiaDiem,
                    $input['DiaChi'] ?? '', // Địa chỉ đầy đủ (sẽ được trigger tự động tạo nếu không có)
                    $input['SoNha'] ?? null,
                    $input['DuongPho'] ?? null,
                    $input['PhuongXa'] ?? null,
                    $input['QuanHuyen'] ?? null,
                    $input['TinhThanh'] ?? null,
                    $input['SucChua'],
                    $giaThueGio,
                    $giaThueNgay,
                    $loaiThue,
                    $input['MoTa'] ?? '',
                    $input['TrangThaiHoatDong'] ?? 'Hoạt động',
                    $locationId
                ]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Cập nhật địa điểm thành công']);
            break;
            
        case 'delete_location':
            // Xóa địa điểm
            try {
                error_log("Delete location - POST data: " . json_encode($_POST));
                error_log("Delete location - GET data: " . json_encode($_GET));
                
                $locationId = $_POST['id'] ?? $_GET['id'] ?? null;
                error_log("Delete location - Location ID: " . $locationId);
                
                if (!$locationId) {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => 'Thiếu ID địa điểm']);
                    break;
                }
                
                // Kiểm tra địa điểm có tồn tại không
                $stmt = $pdo->prepare("SELECT TenDiaDiem, LoaiDiaDiem FROM diadiem WHERE ID_DD = ?");
                $stmt->execute([$locationId]);
                $location = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$location) {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => 'Không tìm thấy địa điểm']);
                    break;
                }
                
                // QUAN TRỌNG: Kiểm tra sự kiện đang diễn ra (NgayBatDau <= NOW() AND NgayKetThuc >= NOW())
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count, 
                           GROUP_CONCAT(DISTINCT TenSuKien SEPARATOR ', ') as events
                    FROM datlichsukien 
                    WHERE ID_DD = ? 
                    AND TrangThaiDuyet = 'Đã duyệt'
                    AND NgayBatDau <= NOW() 
                    AND NgayKetThuc >= NOW()
                ");
                $stmt->execute([$locationId]);
                $activeEvent = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($activeEvent['count'] > 0) {
                    ob_clean();
                    echo json_encode([
                        'success' => false, 
                        'error' => "Không thể xóa địa điểm vì đang có sự kiện đang diễn ra: {$activeEvent['events']}"
                    ]);
                    break;
                }
                
                // Kiểm tra sự kiện sắp diễn ra (NgayBatDau > NOW() AND TrangThaiDuyet = 'Đã duyệt')
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count, 
                           GROUP_CONCAT(DISTINCT TenSuKien SEPARATOR ', ') as events
                    FROM datlichsukien 
                    WHERE ID_DD = ? 
                    AND TrangThaiDuyet = 'Đã duyệt'
                    AND NgayBatDau > NOW()
                ");
                $stmt->execute([$locationId]);
                $upcomingEvent = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($upcomingEvent['count'] > 0) {
                    ob_clean();
                    echo json_encode([
                        'success' => false, 
                        'error' => "Không thể xóa địa điểm vì đang có sự kiện sắp diễn ra: {$upcomingEvent['events']}"
                    ]);
                    break;
                }
                
                // Kiểm tra sự kiện đã được đặt (kể cả chưa duyệt)
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM datlichsukien 
                    WHERE ID_DD = ? 
                    AND TrangThaiDuyet != 'Từ chối'
                ");
                $stmt->execute([$locationId]);
                $bookedEvent = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($bookedEvent['count'] > 0) {
                    ob_clean();
                    echo json_encode([
                        'success' => false, 
                        'error' => "Không thể xóa địa điểm vì đang có {$bookedEvent['count']} sự kiện đã được đặt (kể cả chưa duyệt)"
                    ]);
                    break;
                }
                
                // QUAN TRỌNG: Xóa cascade các phòng thuộc địa điểm (nếu là địa điểm trong nhà)
                if ($location['LoaiDiaDiem'] === 'Trong nhà') {
                    // Kiểm tra phòng có sự kiện đang diễn ra không
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM phong p
                        INNER JOIN datlichsukien d ON p.ID_Phong = d.ID_Phong
                        WHERE p.ID_DD = ?
                        AND d.TrangThaiDuyet = 'Đã duyệt'
                        AND d.NgayBatDau <= NOW() 
                        AND d.NgayKetThuc >= NOW()
                    ");
                    $stmt->execute([$locationId]);
                    $roomActiveEvent = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($roomActiveEvent['count'] > 0) {
                        ob_clean();
                        echo json_encode([
                            'success' => false, 
                            'error' => "Không thể xóa địa điểm vì có phòng đang có sự kiện đang diễn ra"
                        ]);
                        break;
                    }
                    
                    // Xóa các phòng thuộc địa điểm (cascade delete)
                    $stmt = $pdo->prepare("DELETE FROM phong WHERE ID_DD = ?");
                    $stmt->execute([$locationId]);
                    $deletedRooms = $stmt->rowCount();
                    error_log("Deleted {$deletedRooms} rooms for location {$locationId}");
                }
                
                // Xóa địa điểm
                $stmt = $pdo->prepare("DELETE FROM diadiem WHERE ID_DD = ?");
                $result = $stmt->execute([$locationId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    ob_clean();
                    $message = 'Xóa địa điểm thành công';
                    if (isset($deletedRooms) && $deletedRooms > 0) {
                        $message .= " (đã xóa {$deletedRooms} phòng thuộc địa điểm)";
                    }
                    echo json_encode(['success' => true, 'message' => $message]);
                } else {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => 'Không thể xóa địa điểm']);
                }
            } catch (Exception $e) {
                error_log("Delete location error: " . $e->getMessage());
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Lỗi xóa địa điểm: ' . $e->getMessage()]);
            }
            exit();
            
        case 'get_location_stats':
            // Lấy thống kê địa điểm
            $checkTable = $pdo->query("SHOW TABLES LIKE 'diadiem'");
            if ($checkTable->rowCount() == 0) {
                echo json_encode(['success' => false, 'error' => 'Bảng diadiem không tồn tại']);
                break;
            }
            
            // Đảm bảo cột TrangThaiHoatDong tồn tại
            $columns = $pdo->query("SHOW COLUMNS FROM diadiem LIKE 'TrangThaiHoatDong'")->fetchAll();
            if (count($columns) == 0) {
                $pdo->exec("ALTER TABLE diadiem ADD COLUMN TrangThaiHoatDong VARCHAR(50) DEFAULT 'Hoạt động'");
            }
            
            $stats = [];
            $stats['total_locations'] = (int)$pdo->query("SELECT COUNT(*) FROM diadiem")->fetchColumn();
            $stats['active_locations'] = (int)$pdo->query("SELECT COUNT(*) FROM diadiem WHERE TrangThaiHoatDong = 'Hoạt động' OR TrangThaiHoatDong IS NULL")->fetchColumn();
            $stats['inactive_locations'] = (int)$pdo->query("SELECT COUNT(*) FROM diadiem WHERE TrangThaiHoatDong IN ('Bảo trì', 'Ngừng hoạt động')")->fetchColumn();
            $stats['total_capacity'] = (int)($pdo->query("SELECT SUM(SucChua) FROM diadiem WHERE TrangThaiHoatDong = 'Hoạt động' OR TrangThaiHoatDong IS NULL")->fetchColumn() ?? 0);
            
            echo json_encode([
                'success' => true, 
                'stats' => $stats
            ]);
            break;
            
        case 'toggle_status':
            // Chuyển đổi trạng thái địa điểm
            $locationId = $_POST['id'] ?? null;
            $newStatus = $_POST['status'] ?? null;
            
            if (!$locationId || !$newStatus) {
                echo json_encode(['success' => false, 'error' => 'Thiếu thông tin cần thiết']);
                break;
            }
            
            // Kiểm tra địa điểm có tồn tại không
            $stmt = $pdo->prepare("SELECT TenDiaDiem FROM diadiem WHERE ID_DD = ?");
            $stmt->execute([$locationId]);
            $location = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$location) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy địa điểm']);
                break;
            }
            
            // Đảm bảo cột tồn tại
            $columns = $pdo->query("SHOW COLUMNS FROM diadiem LIKE 'TrangThaiHoatDong'")->fetchAll();
            if (count($columns) == 0) {
                $pdo->exec("ALTER TABLE diadiem ADD COLUMN TrangThaiHoatDong VARCHAR(50) DEFAULT 'Hoạt động'");
            }
            
            // Xác thực trạng thái
            $allowedStatuses = ['Hoạt động', 'Bảo trì', 'Ngừng hoạt động'];
            if (!in_array($newStatus, $allowedStatuses, true)) {
                $newStatus = 'Hoạt động';
            }
            
            $stmt = $pdo->prepare("UPDATE diadiem SET TrangThaiHoatDong = ? WHERE ID_DD = ?");
            $result = $stmt->execute([$newStatus, $locationId]);
            
            if ($result) {
                $statusText = $newStatus === 'Hoạt động' ? 'kích hoạt' : ($newStatus === 'Bảo trì' ? 'chuyển bảo trì' : 'tắt hoạt động');
                echo json_encode(['success' => true, 'message' => "Đã {$statusText} địa điểm thành công"]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Lỗi khi cập nhật trạng thái']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

ob_end_flush();
?>