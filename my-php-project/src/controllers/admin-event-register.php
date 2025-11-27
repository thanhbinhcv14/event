<?php
// Bắt đầu output buffering để ngăn chặn output không mong muốn
ob_start();

// Tắt hiển thị lỗi nhưng vẫn ghi log lỗi
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Đặt header JSON trước
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once __DIR__ . '/../../config/database.php';

// Lấy action từ GET, POST, hoặc JSON body
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$jsonInput = null;

// Nếu không tìm thấy action và request là POST với JSON, thử lấy từ JSON body
if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonInput = file_get_contents('php://input');
    $input = json_decode($jsonInput, true);
    if ($input && isset($input['action'])) {
        $action = $input['action'];
    }
}

// Luôn đọc JSON input cho POST requests (cho các hàm cần nó)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$jsonInput) {
    $jsonInput = file_get_contents('php://input');
}

// Lưu jsonInput toàn cục để sử dụng trong các hàm
$GLOBALS['jsonInput'] = $jsonInput;

// Kiểm tra người dùng đã đăng nhập và có vai trò phù hợp (chỉ cho các action nhạy cảm)
$sensitiveActions = ['approve_registration', 'reject_registration'];
if (in_array($action, $sensitiveActions)) {
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2, 3])) {
        echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
        exit;
    }
}

switch ($action) {
    case 'get_event_registrations':
        getEventRegistrations();
        break;
    case 'get_registration_details':
        getRegistrationDetails();
        break;
    case 'approve_registration':
        approveRegistration();
        break;
    case 'reject_registration':
        rejectRegistration();
        break;
    case 'get_pending_registrations':
        getPendingRegistrations();
        break;
    case 'get_customers':
        getCustomers();
        break;
    case 'get_event_types':
        getEventTypes();
        break;
    case 'get_locations':
        getLocations();
        break;
    case 'get_locations_by_type':
        getLocationsByType();
        break;
    case 'get_equipment_combos':
        getEquipmentCombos();
        break;
    case 'get_equipment':
        getEquipment();
        break;
    case 'register_event':
        registerEvent();
        break;
    case 'register_event_for_existing_customer':
        registerEventForExistingCustomer();
        break;
    case 'delete_registration':
        deleteRegistration();
        break;
    case 'get_registration_for_edit':
        getRegistrationForEdit();
        break;
    case 'update_registration':
        updateRegistration();
        break;
    default:
        if (ob_get_level() > 0) {
            ob_clean();
        }
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ'], JSON_UNESCAPED_UNICODE);
        exit;
}

function getEventRegistrations() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                dl.ID_DatLich,
                dl.TenSuKien,
                dl.MoTa,
                dl.NgayBatDau,
                dl.NgayKetThuc,
                dl.SoNguoiDuKien,
                dl.NganSach,
                dl.TrangThaiDuyet,
                dl.TrangThaiThanhToan,
                dl.GhiChu,
                dl.NgayTao,
                dd.TenDiaDiem,
                dd.DiaChi,
                ls.TenLoai as TenLoaiSK,
                kh.HoTen as TenKhachHang,
                kh.SoDienThoai,
                kh.DiaChi as KhachHangDiaChi
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            ORDER BY dl.NgayTao DESC
        ");
        $stmt->execute();
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'registrations' => $registrations]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách đăng ký sự kiện: ' . $e->getMessage()]);
    }
}

function getRegistrationDetails() {
    try {
        $pdo = getDBConnection();
        
        $registrationId = $_GET['registration_id'] ?? '';
        
        if (empty($registrationId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đăng ký']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                dl.*,
                dd.TenDiaDiem,
                dd.DiaChi,
                dd.MoTa as DiaDiemMoTa,
                dd.SucChua,
                dd.GiaThueGio,
                dd.GiaThueNgay,
                dd.LoaiThue,
                ls.TenLoai as TenLoaiSK,
                ls.MoTa as LoaiSKMoTa,
                kh.HoTen as TenKhachHang,
                kh.SoDienThoai,
                kh.DiaChi as KhachHangDiaChi,
                kh.NgaySinh
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            WHERE dl.ID_DatLich = ?
        ");
        $stmt->execute([$registrationId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đăng ký sự kiện']);
            return;
        }
        
        // Lấy thiết bị đã đăng ký từ chitietdatsukien (nơi lưu thiết bị khi đăng ký)
        $equipment = [];
        
        // Lấy thiết bị riêng lẻ
        $stmt = $pdo->prepare("
            SELECT 
                ct.ID_CT,
                ct.ID_DatLich,
                ct.ID_TB,
                ct.ID_Combo,
                ct.SoLuong,
                ct.DonGia,
                ct.GhiChu,
                t.TenThietBi,
                t.LoaiThietBi,
                t.HangSX,
                t.GiaThue,
                t.DonViTinh,
                t.TrangThai,
                t.MoTa,
                'equipment' as type
            FROM chitietdatsukien ct
            LEFT JOIN thietbi t ON ct.ID_TB = t.ID_TB
            WHERE ct.ID_DatLich = ? AND ct.ID_TB IS NOT NULL
        ");
        $stmt->execute([$registrationId]);
        $individualEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy combo thiết bị
        $stmt = $pdo->prepare("
            SELECT 
                ct.ID_CT,
                ct.ID_DatLich,
                ct.ID_TB,
                ct.ID_Combo,
                ct.SoLuong,
                ct.DonGia,
                ct.GhiChu,
                c.TenCombo,
                c.MoTa,
                c.GiaCombo,
                'combo' as type
            FROM chitietdatsukien ct
            LEFT JOIN combo c ON ct.ID_Combo = c.ID_Combo
            WHERE ct.ID_DatLich = ? AND ct.ID_Combo IS NOT NULL
        ");
        $stmt->execute([$registrationId]);
        $comboEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Kết hợp thiết bị
        $equipment = array_merge($individualEquipment, $comboEquipment);
        
        $registration['equipment'] = $equipment;
        $registration['individual_equipment'] = $individualEquipment;
        $registration['combo_equipment'] = $comboEquipment;
        
        echo json_encode(['success' => true, 'registration' => $registration]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy chi tiết đăng ký: ' . $e->getMessage()]);
    }
}



function getPendingRegistrations() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                dl.ID_DatLich,
                dl.TenSuKien,
                dl.MoTa,
                dl.NgayBatDau,
                dl.NgayKetThuc,
                dl.SoNguoiDuKien,
                dl.NganSach,
                dl.GhiChu,
                dl.NgayTao,
                dd.TenDiaDiem,
                dd.DiaChi,
                ls.TenLoai as TenLoaiSK,
                kh.HoTen as TenKhachHang,
                kh.SoDienThoai
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            WHERE dl.TrangThaiDuyet = 'Chờ duyệt'
            ORDER BY dl.NgayTao ASC
        ");
        $stmt->execute();
        $pendingRegistrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'pending_registrations' => $pendingRegistrations]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách đăng ký chờ duyệt: ' . $e->getMessage()]);
    }
}

function getCustomers() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                kh.ID_KhachHang,
                kh.HoTen,
                u.Email,
                kh.SoDienThoai,
                kh.DiaChi,
                u.ID_User,
                COUNT(dl.ID_DatLich) as event_count,
                MAX(dl.NgayTao) as last_event_date
            FROM khachhanginfo kh
            LEFT JOIN users u ON kh.ID_User = u.ID_User
            LEFT JOIN datlichsukien dl ON kh.ID_KhachHang = dl.ID_KhachHang
            GROUP BY kh.ID_KhachHang
            ORDER BY kh.HoTen ASC
        ");
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'customers' => $customers]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách khách hàng: ' . $e->getMessage()]);
    }
}

function getEventTypes() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                ID_LoaiSK,
                TenLoai,
                MoTa,
                GiaCoBan
            FROM loaisukien
            ORDER BY TenLoai ASC
        ");
        $stmt->execute();
        $eventTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'event_types' => $eventTypes]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách loại sự kiện: ' . $e->getMessage()]);
    }
}

function getLocations() {
    try {
        $pdo = getDBConnection();
        
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
                HinhAnh
            FROM diadiem
            WHERE TrangThaiHoatDong = 'Hoạt động'
            ORDER BY TenDiaDiem ASC
        ");
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'locations' => $locations]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách địa điểm: ' . $e->getMessage()]);
    }
}

function getLocationsByType() {
    try {
        $pdo = getDBConnection();
        $eventTypeId = $_GET['event_type'] ?? '';
        
        if (empty($eventTypeId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin loại sự kiện']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                dd.ID_DD,
                dd.TenDiaDiem,
                dd.DiaChi,
                dd.SucChua,
                dd.GiaThueGio,
                dd.GiaThueNgay,
                dd.LoaiThue,
                dd.LoaiDiaDiem,
                dd.HinhAnh
            FROM diadiem dd
            LEFT JOIN diadiem_loaisk dl ON dd.ID_DD = dl.ID_DD
            WHERE dd.TrangThaiHoatDong = 'Hoạt động'
            AND (dl.ID_LoaiSK = ? OR dl.ID_LoaiSK IS NULL)
            ORDER BY dd.TenDiaDiem ASC
        ");
        $stmt->execute([$eventTypeId]);
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'locations' => $locations]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách địa điểm theo loại: ' . $e->getMessage()]);
    }
}

function getEquipmentCombos() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                c.ID_Combo,
                c.TenCombo,
                c.MoTa,
                c.GiaCombo
            FROM combo c
            ORDER BY c.TenCombo ASC
        ");
        $stmt->execute();
        $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy chi tiết thiết bị cho mỗi combo
        foreach ($combos as &$combo) {
            $equipmentStmt = $pdo->prepare("
                SELECT 
                    ct.SoLuong,
                    t.TenThietBi,
                    t.GiaThue,
                    t.HinhAnh
                FROM combo_thietbi ct
                LEFT JOIN thietbi t ON ct.ID_TB = t.ID_TB
                WHERE ct.ID_Combo = ?
            ");
            $equipmentStmt->execute([$combo['ID_Combo']]);
            $combo['equipment'] = $equipmentStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['success' => true, 'combos' => $combos]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách combo thiết bị: ' . $e->getMessage()]);
    }
}

function getEquipment() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                t.ID_TB as ID_ThietBi,
                t.TenThietBi,
                t.MoTa,
                t.GiaThue,
                t.HinhAnh,
                t.TrangThai,
                t.LoaiThietBi
            FROM thietbi t
            WHERE t.TrangThai = 'Sẵn sàng'
            ORDER BY t.TenThietBi ASC
        ");
        $stmt->execute();
        $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'equipment' => $equipment]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách thiết bị: ' . $e->getMessage()]);
    }
}

function approveRegistration() {
    try {
        $pdo = getDBConnection();
        $eventId = $_POST['event_id'] ?? '';
        $approvalNote = $_POST['approval_note'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện']);
            return;
        }
        
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiDuyet = 'Đã duyệt', 
                GhiChu = CONCAT(IFNULL(GhiChu, ''), ' | Duyệt bởi: ', ?, ' | Ghi chú: ', ?)
            WHERE ID_DatLich = ?
        ");
        
        $stmt->execute([
            $_SESSION['user']['HoTen'] ?? 'Admin',
            $approvalNote,
            $eventId
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Duyệt sự kiện thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện hoặc đã được duyệt']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi duyệt sự kiện: ' . $e->getMessage()]);
    }
}

function rejectRegistration() {
    try {
        $pdo = getDBConnection();
        $eventId = $_POST['event_id'] ?? '';
        $rejectionReason = $_POST['rejection_reason'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện']);
            return;
        }
        
        $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TrangThaiDuyet = 'Từ chối', 
                GhiChu = CONCAT(IFNULL(GhiChu, ''), ' | Từ chối bởi: ', ?, ' | Lý do: ', ?)
            WHERE ID_DatLich = ?
        ");
        
        $stmt->execute([
            $_SESSION['user']['HoTen'] ?? 'Admin',
            $rejectionReason,
            $eventId
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Từ chối sự kiện thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện hoặc đã được xử lý']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi từ chối sự kiện: ' . $e->getMessage()]);
    }
}

function registerEvent() {
    try {
        $pdo = getDBConnection();
        
        // Lấy dữ liệu form
        $customerId = $_POST['customer_id'] ?? '';
        $eventName = $_POST['event_name'] ?? '';
        $eventType = $_POST['event_type'] ?? '';
        $eventDate = $_POST['event_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        $locationId = $_POST['location_id'] ?? '';
        $expectedGuests = $_POST['expected_guests'] ?? '';
        $budget = $_POST['budget'] ?? '';
        $description = $_POST['description'] ?? '';
        $selectedCombos = $_POST['selected_combos'] ?? [];
        $selectedEquipment = $_POST['selected_equipment'] ?? [];
        
        // Xác thực các trường bắt buộc
        if (empty($customerId) || empty($eventName) || empty($eventType) || empty($eventDate) || empty($startTime) || empty($endTime) || empty($locationId)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
            return;
        }
        
        // Kết hợp ngày và giờ
        $startDateTime = $eventDate . ' ' . $startTime . ':00';
        $endDateTime = $eventDate . ' ' . $endTime . ':00';
        
        $pdo->beginTransaction();
        
        try {
            // Thêm đăng ký sự kiện
            $stmt = $pdo->prepare("
                INSERT INTO datlichsukien (
                    ID_KhachHang, TenSuKien, MoTa, NgayBatDau, NgayKetThuc,
                    ID_DD, ID_LoaiSK, SoNguoiDuKien, NganSach, TrangThaiDuyet,
                    TrangThaiThanhToan, GhiChu
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Chờ duyệt', 'Chưa thanh toán', ?)
            ");
            
            $stmt->execute([
                $customerId, $eventName, $description, $startDateTime, $endDateTime,
                $locationId, $eventType, $expectedGuests, $budget, 'Đăng ký bởi quản lý sự kiện'
            ]);
            
            $eventId = $pdo->lastInsertId();
            
            // Thêm các combo đã chọn
            if (!empty($selectedCombos)) {
                foreach ($selectedCombos as $combo) {
                    $comboData = json_decode($combo, true);
                    $comboStmt = $pdo->prepare("
                        INSERT INTO sukien_combo (ID_SuKien, ID_Combo, SoLuong, GhiChu)
                        VALUES (?, ?, ?, ?)
                    ");
                    $comboStmt->execute([
                        $eventId, $comboData['id'], $comboData['quantity'], 
                        'Giá: ' . number_format($comboData['price']) . ' VNĐ, Tổng: ' . number_format($comboData['total']) . ' VNĐ'
                    ]);
                }
            }
            
            // Thêm thiết bị đã chọn
            if (!empty($selectedEquipment)) {
                foreach ($selectedEquipment as $equipment) {
                    $equipmentData = json_decode($equipment, true);
                    $equipmentStmt = $pdo->prepare("
                        INSERT INTO sukien_thietbi (ID_SuKien, ID_TB, SoLuong, GhiChu)
                        VALUES (?, ?, ?, ?)
                    ");
                    $equipmentStmt->execute([
                        $eventId, $equipmentData['id'], $equipmentData['quantity'], 
                        'Giá: ' . number_format($equipmentData['price']) . ' VNĐ, Tổng: ' . number_format($equipmentData['total']) . ' VNĐ'
                    ]);
                }
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Đăng ký sự kiện thành công! Sự kiện đang chờ duyệt.']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi đăng ký sự kiện: ' . $e->getMessage()]);
    }
}

function registerEventForExistingCustomer() {
    try {
        $pdo = getDBConnection();
        
        // Lấy JSON input - thử nhiều nguồn
        $jsonInput = $GLOBALS['jsonInput'] ?? null;
        if (!$jsonInput && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $jsonInput = file_get_contents('php://input');
        }
        
        if (!$jsonInput) {
            echo json_encode(['success' => false, 'message' => 'Không nhận được dữ liệu']);
            return;
        }
        
        $input = json_decode($jsonInput, true);
        
        if (!$input || json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ: ' . json_last_error_msg()]);
            return;
        }
        
        // Xác thực các trường bắt buộc
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
        $roomId = $input['room_id'] ?? null;
        $equipment = $input['equipment'] ?? [];
        $adminNotes = $input['adminNotes'] ?? '';
        
        // Lấy thông tin nhân viên nếu người dùng là role 3 (Quản lý sự kiện)
        $staffInfo = null;
        $staffId = null;
        $staffName = null;
        if (isset($_SESSION['user']) && $_SESSION['user']['ID_Role'] == 3) {
            $userId = $_SESSION['user']['ID_User'] ?? null;
            if ($userId) {
                $stmt = $pdo->prepare("
                    SELECT nv.ID_NhanVien, nv.HoTen
                    FROM nhanvieninfo nv
                    WHERE nv.ID_User = ?
                ");
                $stmt->execute([$userId]);
                $staffInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($staffInfo) {
                    $staffId = $staffInfo['ID_NhanVien'];
                    $staffName = $staffInfo['HoTen'];
                }
            }
        }
        
        // Xác thực khách hàng tồn tại
        $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE ID_KhachHang = ?");
        $stmt->execute([$customerId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Khách hàng không tồn tại']);
            return;
        }
        
        // Xác thực dữ liệu sự kiện
        if (empty($event['name']) || empty($event['type']) || empty($event['startDate']) || empty($event['endDate'])) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện bắt buộc']);
            return;
        }
        
        // Xác thực ràng buộc ngày giờ
        try {
            // Phân tích chuỗi datetime
            $startDateTime = new DateTime($event['startDate']);
            $endDateTime = new DateTime($event['endDate']);
            $now = new DateTime();
            
            // 1. Ràng buộc ngày: Ngày bắt đầu không được trong quá khứ
            if ($startDateTime < $now) {
                echo json_encode(['success' => false, 'message' => 'Ngày bắt đầu không được trong quá khứ']);
                return;
            }
            
            // 2. Ràng buộc ngày: Ngày kết thúc phải sau ngày bắt đầu
            if ($endDateTime <= $startDateTime) {
                echo json_encode(['success' => false, 'message' => 'Ngày kết thúc phải sau ngày bắt đầu']);
                return;
            }
            
            // 3. Ràng buộc 12 giờ: Phải đăng ký trước ít nhất 12 giờ so với thời gian bắt đầu
            $minDateTime = clone $now;
            $minDateTime->modify('+12 hours');
            if ($startDateTime < $minDateTime) {
                $hoursDiff = ($startDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
                $hoursLeft = max(0, floor($hoursDiff));
                $minDateTimeStr = $minDateTime->format('d/m/Y H:i');
                echo json_encode([
                    'success' => false, 
                    'message' => "Sự kiện phải được đăng ký trước ít nhất 12 giờ. Thời gian bắt đầu bạn chọn chỉ còn {$hoursLeft} giờ nữa. Vui lòng chọn thời gian sau {$minDateTimeStr}."
                ]);
                return;
            }
            
            // 4. Ràng buộc giờ: Nếu cùng ngày, giờ kết thúc phải sau giờ bắt đầu
            $startDateOnly = clone $startDateTime;
            $startDateOnly->setTime(0, 0, 0);
            $endDateOnly = clone $endDateTime;
            $endDateOnly->setTime(0, 0, 0);
            
            if ($startDateOnly->format('Y-m-d') === $endDateOnly->format('Y-m-d')) {
                // Cùng ngày - kiểm tra giờ
                if ($startDateTime >= $endDateTime) {
                    echo json_encode(['success' => false, 'message' => 'Giờ kết thúc phải sau giờ bắt đầu khi cùng ngày']);
                    return;
                }
            }
            
            // 5. Ràng buộc giờ: Thời gian kết thúc không được trong quá khứ
            if ($endDateTime < $now) {
                echo json_encode(['success' => false, 'message' => 'Thời gian kết thúc không được trong quá khứ']);
                return;
            }
            
            // 6. Ràng buộc thời lượng tối thiểu: Sự kiện phải kéo dài ít nhất 1 giờ
            $durationHours = ($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 3600;
            if ($durationHours < 1) {
                echo json_encode(['success' => false, 'message' => 'Sự kiện phải kéo dài ít nhất 1 giờ']);
                return;
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xác thực thời gian: ' . $e->getMessage()]);
            return;
        }
        
        // Bắt đầu transaction
        $pdo->beginTransaction();
        
        try {
            // 1. Tính tổng chi phí (bao gồm chi phí phòng nếu đã chọn)
            $totalCost = calculateTotalCost($event, $locationId, $equipment, $input['location_rental_type'] ?? null, $roomId);
            
            // 2. Tạo đăng ký sự kiện (kèm thông tin nhân viên nếu role 3)
            $eventId = createEventRegistration($event, $customerId, $locationId, $totalCost, $adminNotes, $input['location_rental_type'] ?? null, $staffId, $staffName, $roomId);
            
            // 3. Thêm thiết bị vào đăng ký
            error_log("REGISTER EVENT - Equipment received: " . json_encode($equipment));
            error_log("REGISTER EVENT - Equipment type: " . gettype($equipment));
            error_log("REGISTER EVENT - Equipment is array: " . (is_array($equipment) ? 'yes' : 'no'));
            if (is_array($equipment)) {
                error_log("REGISTER EVENT - Equipment count: " . count($equipment));
                foreach ($equipment as $idx => $item) {
                    error_log("REGISTER EVENT - Equipment item #{$idx}: " . json_encode($item));
                }
            }
            
            if (!empty($equipment) && is_array($equipment) && count($equipment) > 0) {
                error_log("REGISTER EVENT - Calling addEquipmentToRegistration with " . count($equipment) . " items");
                try {
                    // Truyền $pdo để sử dụng cùng transaction
                    addEquipmentToRegistration($eventId, $equipment, $pdo);
                    error_log("REGISTER EVENT - Equipment added successfully");
                } catch (Exception $e) {
                    error_log("REGISTER EVENT - Error adding equipment: " . $e->getMessage());
                    error_log("REGISTER EVENT - Stack trace: " . $e->getTraceAsString());
                    throw $e;
                }
            } else {
                error_log("REGISTER EVENT - No equipment to add (empty or not array)");
            }
            
            // 4. Commit transaction
            $pdo->commit();
            
            // 5. Gửi email thông báo cho khách hàng
            error_log("REGISTER EVENT - Attempting to send email to customer ID: $customerId, Event ID: $eventId");
            try {
                $emailSent = sendEventRegistrationEmail($customerId, $eventId, $event, $totalCost, $roomId);
                if ($emailSent) {
                    error_log("REGISTER EVENT - Email sent successfully");
                } else {
                    error_log("REGISTER EVENT - Email sending failed (returned false)");
                }
            } catch (Exception $e) {
                // Ghi log lỗi nhưng không làm thất bại đăng ký
                error_log("REGISTER EVENT - Exception when sending email notification: " . $e->getMessage());
                error_log("REGISTER EVENT - Error stack trace: " . $e->getTraceAsString());
            }
            
            // Xóa mọi output trước khi gửi JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }
            echo json_encode([
                'success' => true, 
                'message' => 'Đăng ký sự kiện thành công',
                'event_id' => $eventId,
                'total_cost' => $totalCost
            ], JSON_UNESCAPED_UNICODE);
            exit; // Thoát để ngăn chặn output thêm
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error in registerEventForExistingCustomer transaction: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Error in registerEventForExistingCustomer: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        // Xóa mọi output trước khi gửi JSON
        if (ob_get_level() > 0) {
            ob_clean();
        }
        echo json_encode(['success' => false, 'message' => 'Lỗi khi đăng ký sự kiện: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit; // Thoát để ngăn chặn output thêm
    }
}

function calculateTotalCost($eventData, $locationId, $equipment, $locationRentalType = null, $roomId = null) {
    $pdo = getDBConnection();
    $totalCost = 0;
    
    // 1. Chi phí loại sự kiện - LUÔN thêm giá loại sự kiện nếu có
    $stmt = $pdo->prepare("SELECT GiaCoBan FROM loaisukien WHERE ID_LoaiSK = ?");
    $stmt->execute([$eventData['type']]);
    $eventType = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($eventType && $eventType['GiaCoBan'] && floatval($eventType['GiaCoBan']) > 0) {
        $totalCost += floatval($eventType['GiaCoBan']);
    }
    
    // 2. Chi phí địa điểm (chỉ khi không chọn phòng, hoặc phòng không có giá riêng)
    if ($locationId && !$roomId) {
        $stmt = $pdo->prepare("SELECT GiaThueGio, GiaThueNgay, LoaiThue FROM diadiem WHERE ID_DD = ?");
        $stmt->execute([$locationId]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($location) {
            $startDate = new DateTime($eventData['startDate']);
            $endDate = new DateTime($eventData['endDate']);
            $durationHours = $startDate->diff($endDate)->h + ($startDate->diff($endDate)->days * 24);
            $durationDays = $startDate->diff($endDate)->days + ($durationHours > 0 ? 1 : 0);
            
            // Ưu tiên: Lựa chọn người dùng > Mặc định database
            if ($locationRentalType) {
                // Người dùng đã chọn rõ ràng loại thuê
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
                // Mặc định thuê theo ngày để UX tốt hơn
                $totalCost += $durationDays * floatval($location['GiaThueNgay'] ?? 0);
            }
        }
    }
    
    // 3. Chi phí phòng (nếu đã chọn phòng)
    if ($roomId) {
        $stmt = $pdo->prepare("SELECT GiaThueGio, GiaThueNgay, LoaiThue FROM phong WHERE ID_Phong = ?");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room) {
            $startDate = new DateTime($eventData['startDate']);
            $endDate = new DateTime($eventData['endDate']);
            $durationHours = $startDate->diff($endDate)->h + ($startDate->diff($endDate)->days * 24);
            $durationDays = $startDate->diff($endDate)->days + ($durationHours > 0 ? 1 : 0);
            
            // Sử dụng loại thuê địa điểm cho giá phòng
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
                // Mặc định thuê theo ngày
                $totalCost += $durationDays * floatval($room['GiaThueNgay'] ?? 0);
            }
        }
    }
    
    // 4. Chi phí thiết bị
    foreach ($equipment as $item) {
        $itemCost = $item['price'] * $item['quantity'];
        $totalCost += $itemCost;
    }
    
    return $totalCost;
}

function createEventRegistration($eventData, $customerId, $locationId, $totalCost, $adminNotes, $locationRentalType = null, $staffId = null, $staffName = null, $roomId = null) {
    $pdo = getDBConnection();
    
    // Chuyển đổi location_rental_type sang giá trị enum database
    $loaiThueApDung = null;
    if ($locationRentalType === 'hour') {
        $loaiThueApDung = 'Theo giờ';
    } elseif ($locationRentalType === 'day') {
        $loaiThueApDung = 'Theo ngày';
    }
    
    // Xây dựng GhiChu với thông tin nhân viên nếu có
    $ghiChu = '';
    if ($staffId && $staffName) {
        $ghiChu = "Đăng ký bởi quản lý sự kiện - [NHANVIEN_ID:{$staffId}] - [NHANVIEN_NAME:{$staffName}]";
        if (!empty($adminNotes)) {
            $ghiChu .= " | " . $adminNotes;
        }
    } else {
        $ghiChu = "Đăng ký bởi quản lý sự kiện";
        if (!empty($adminNotes)) {
            $ghiChu .= " | " . $adminNotes;
        }
    }
    
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
        $ghiChu,
        $customerId,
        $locationId,
        $eventData['type'],
        $loaiThueApDung,
        $roomId
    ]);
    
    return $pdo->lastInsertId();
}

function addEquipmentToRegistration($eventId, $equipment, $pdo = null) {
    // Sử dụng kết nối PDO được cung cấp nếu có (cho transaction), nếu không tạo mới
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    
    error_log("addEquipmentToRegistration - Called with eventId: " . $eventId);
    error_log("addEquipmentToRegistration - Equipment: " . json_encode($equipment));
    
    if (!is_array($equipment)) {
        error_log("addEquipmentToRegistration - ERROR: Equipment is not an array!");
        throw new Exception("Dữ liệu thiết bị không hợp lệ: không phải mảng");
    }
    
    if (empty($equipment)) {
        error_log("addEquipmentToRegistration - WARNING: Equipment array is empty");
        return; // No equipment to add, but not an error
    }
    
    error_log("addEquipmentToRegistration - Processing " . count($equipment) . " items");
    
    foreach ($equipment as $index => $item) {
        error_log("addEquipmentToRegistration - Processing item #" . $index . ": " . json_encode($item));
        
        // Xác thực cấu trúc item
        if (!isset($item['type']) || !isset($item['id']) || !isset($item['quantity'])) {
            error_log("addEquipmentToRegistration - ERROR: Invalid item structure. Item: " . json_encode($item));
            error_log("addEquipmentToRegistration - ERROR: Type: " . (isset($item['type']) ? $item['type'] : 'missing') . ", ID: " . (isset($item['id']) ? $item['id'] : 'missing') . ", Quantity: " . (isset($item['quantity']) ? $item['quantity'] : 'missing'));
            throw new Exception("Dữ liệu thiết bị không hợp lệ: thiếu thông tin bắt buộc (type, id, quantity). Item: " . json_encode($item));
        }
        
        // Xác thực type hợp lệ
        if ($item['type'] !== 'equipment' && $item['type'] !== 'combo') {
            error_log("addEquipmentToRegistration - ERROR: Invalid type: " . $item['type']);
            throw new Exception("Loại thiết bị không hợp lệ: " . $item['type'] . ". Phải là 'equipment' hoặc 'combo'");
        }
        
        // Xác thực id và quantity là số dương
        if (intval($item['id']) <= 0) {
            error_log("addEquipmentToRegistration - ERROR: Invalid ID: " . $item['id']);
            throw new Exception("ID thiết bị/combo không hợp lệ: " . $item['id']);
        }
        
        if (intval($item['quantity']) <= 0) {
            error_log("addEquipmentToRegistration - ERROR: Invalid quantity: " . $item['quantity']);
            throw new Exception("Số lượng không hợp lệ: " . $item['quantity']);
        }
        
        $itemId = intval($item['id']);
        $quantity = intval($item['quantity']);
        $price = isset($item['price']) ? floatval($item['price']) : 0;
        
        // Nếu giá là 0 hoặc không được cung cấp, lấy từ database
        if ($price <= 0) {
        if ($item['type'] === 'equipment') {
                $stmt = $pdo->prepare("SELECT GiaThue FROM thietbi WHERE ID_TB = ?");
                $stmt->execute([$itemId]);
                $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($equipment) {
                    $price = floatval($equipment['GiaThue']);
                    error_log("addEquipmentToRegistration - Fetched price from database for equipment ID {$itemId}: {$price}");
                } else {
                    error_log("addEquipmentToRegistration - ERROR: Equipment ID {$itemId} not found in database");
                    throw new Exception("Không tìm thấy thiết bị với ID: {$itemId}");
                }
            } elseif ($item['type'] === 'combo') {
                $stmt = $pdo->prepare("SELECT GiaCombo FROM combo WHERE ID_Combo = ?");
                $stmt->execute([$itemId]);
                $combo = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($combo) {
                    $price = floatval($combo['GiaCombo']);
                    error_log("addEquipmentToRegistration - Fetched price from database for combo ID {$itemId}: {$price}");
                } else {
                    error_log("addEquipmentToRegistration - ERROR: Combo ID {$itemId} not found in database");
                    throw new Exception("Không tìm thấy combo với ID: {$itemId}");
                }
            }
        }
        
        // Xác thực giá vẫn hợp lệ
        if ($price <= 0) {
            error_log("addEquipmentToRegistration - ERROR: Invalid price after fetch: {$price}");
            throw new Exception("Giá thiết bị/combo không hợp lệ: {$price}");
        }
        
        error_log("addEquipmentToRegistration - Item #" . $index . " - Type: " . $item['type'] . ", ID: " . $itemId . ", Quantity: " . $quantity . ", Price: " . $price);
        
        if ($item['type'] === 'equipment') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO chitietdatsukien (ID_DatLich, ID_TB, SoLuong, DonGia, GhiChu)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $note = "Giá: " . number_format($price) . " VNĐ, Tổng: " . number_format($price * $quantity) . " VNĐ";
                
                error_log("addEquipmentToRegistration - Inserting equipment - EventId: " . $eventId . ", TB_ID: " . $itemId . ", Quantity: " . $quantity . ", Price: " . $price);
                
                $result = $stmt->execute([
                    $eventId,
                    $itemId,
                    $quantity,
                    $price,
                    $note
                ]);
                
                if ($result) {
                    error_log("addEquipmentToRegistration - Equipment inserted successfully. Row ID: " . $pdo->lastInsertId());
                } else {
                    $errorInfo = $stmt->errorInfo();
                    error_log("addEquipmentToRegistration - ERROR inserting equipment: " . json_encode($errorInfo));
                    throw new Exception("Lỗi khi thêm thiết bị: " . ($errorInfo[2] ?? 'Unknown error'));
                }
            } catch (PDOException $e) {
                error_log("addEquipmentToRegistration - PDO Exception: " . $e->getMessage());
                error_log("addEquipmentToRegistration - Error Code: " . $e->getCode());
                if ($e->getCode() == 'HY000' && strpos($e->getMessage(), 'Lock wait timeout') !== false) {
                    throw new Exception("Lỗi timeout khi thêm thiết bị. Vui lòng thử lại sau vài giây.");
                }
                throw new Exception("Lỗi khi thêm thiết bị: " . $e->getMessage());
            }
        } elseif ($item['type'] === 'combo') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO chitietdatsukien (ID_DatLich, ID_Combo, SoLuong, DonGia, GhiChu)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $note = "Giá: " . number_format($price) . " VNĐ, Tổng: " . number_format($price * $quantity) . " VNĐ";
                
                error_log("addEquipmentToRegistration - Inserting combo - EventId: " . $eventId . ", Combo_ID: " . $itemId . ", Quantity: " . $quantity . ", Price: " . $price);
                
                $result = $stmt->execute([
                    $eventId,
                    $itemId,
                    $quantity,
                    $price,
                    $note
                ]);
                
                if ($result) {
                    error_log("addEquipmentToRegistration - Combo inserted successfully. Row ID: " . $pdo->lastInsertId());
                } else {
                    $errorInfo = $stmt->errorInfo();
                    error_log("addEquipmentToRegistration - ERROR inserting combo: " . json_encode($errorInfo));
                    throw new Exception("Lỗi khi thêm combo: " . ($errorInfo[2] ?? 'Unknown error'));
                }
            } catch (PDOException $e) {
                error_log("addEquipmentToRegistration - PDO Exception: " . $e->getMessage());
                error_log("addEquipmentToRegistration - Error Code: " . $e->getCode());
                if ($e->getCode() == 'HY000' && strpos($e->getMessage(), 'Lock wait timeout') !== false) {
                    throw new Exception("Lỗi timeout khi thêm combo. Vui lòng thử lại sau vài giây.");
                }
                throw new Exception("Lỗi khi thêm combo: " . $e->getMessage());
            }
        } else {
            error_log("addEquipmentToRegistration - ERROR: Unknown equipment type: " . $item['type']);
            throw new Exception("Loại thiết bị không hợp lệ: " . $item['type']);
        }
    }
    
    error_log("addEquipmentToRegistration - Completed successfully");
}

function deleteRegistration() {
    try {
        $pdo = getDBConnection();
        
        // Kiểm tra người dùng đã đăng nhập và là role 3
        if (!isset($_SESSION['user']) || $_SESSION['user']['ID_Role'] != 3) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không có quyền xóa sự kiện'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $registrationId = $_POST['registration_id'] ?? '';
        
        if (empty($registrationId)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Thiếu ID đăng ký'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Kiểm tra xem sự kiện có được đăng ký bởi quản lý sự kiện này không
        $stmt = $pdo->prepare("
            SELECT ID_DatLich, GhiChu 
            FROM datlichsukien 
            WHERE ID_DatLich = ?
        ");
        $stmt->execute([$registrationId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đăng ký sự kiện'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Kiểm tra xem sự kiện có được đăng ký bởi quản lý sự kiện không
        $isEventManagerEvent = $registration['GhiChu'] && (
            strpos($registration['GhiChu'], 'Đăng ký bởi quản lý sự kiện') !== false || 
            strpos($registration['GhiChu'], 'Đăng ký bởi') !== false ||
            strpos($registration['GhiChu'], 'quản lý sự kiện') !== false
        );
        
        if (!$isEventManagerEvent) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Bạn chỉ có thể xóa các sự kiện do bạn đăng ký'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Kiểm tra xem sự kiện đã được duyệt chưa - không cho phép xóa nếu đã duyệt
        $stmt = $pdo->prepare("
            SELECT TrangThaiDuyet 
            FROM datlichsukien 
            WHERE ID_DatLich = ?
        ");
        $stmt->execute([$registrationId]);
        $status = $stmt->fetchColumn();
        
        if ($status === 'Đã duyệt') {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không thể xóa sự kiện đã được duyệt'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Bắt đầu transaction
        $pdo->beginTransaction();
        
        try {
            // Xóa các bản ghi liên quan trước
            // 1. Xóa chi tiết đăng ký sự kiện (thiết bị, combo)
            $stmt = $pdo->prepare("DELETE FROM chitietdatsukien WHERE ID_DatLich = ?");
            $stmt->execute([$registrationId]);
            
            // 2. Xóa thanh toán (nếu có)
            $stmt = $pdo->prepare("DELETE FROM thanhtoan WHERE ID_DatLich = ?");
            $stmt->execute([$registrationId]);
            
            // 3. Xóa sự kiện (nếu có)
            $stmt = $pdo->prepare("DELETE FROM sukien WHERE ID_DatLich = ?");
            $stmt->execute([$registrationId]);
            
            // 4. Xóa đăng ký sự kiện
            $stmt = $pdo->prepare("DELETE FROM datlichsukien WHERE ID_DatLich = ?");
            $stmt->execute([$registrationId]);
            
            // Commit transaction
            $pdo->commit();
            
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Đã xóa sự kiện thành công'], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (Exception $e) {
            // Hoàn tác transaction nếu có lỗi
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        ob_clean();
        error_log("Delete registration error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa sự kiện: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function getRegistrationForEdit() {
    try {
        $pdo = getDBConnection();
        
        // Kiểm tra người dùng đã đăng nhập và là role 3
        if (!isset($_SESSION['user']) || $_SESSION['user']['ID_Role'] != 3) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không có quyền chỉnh sửa sự kiện'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $registrationId = $_GET['registration_id'] ?? '';
        
        if (empty($registrationId)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Thiếu ID đăng ký'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Lấy thông tin đăng ký kèm thông tin mã giảm giá
        $stmt = $pdo->prepare("
            SELECT 
                dl.*,
                dd.ID_DD,
                dd.TenDiaDiem,
                dd.DiaChi as DiaDiemDiaChi,
                dd.LoaiThue,
                dd.LoaiDiaDiem,
                dd.GiaThueGio,
                dd.GiaThueNgay,
                ls.ID_LoaiSK,
                ls.TenLoai,
                kh.ID_KhachHang,
                kh.HoTen,
                kh.SoDienThoai,
                kh.DiaChi as KhachHangDiaChi,
                u.Email,
                m.MaCode,
                m.TenMa,
                m.LoaiGiamGia,
                m.GiaTriGiamGia
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            LEFT JOIN users u ON kh.ID_User = u.ID_User
            LEFT JOIN magiamgia m ON dl.ID_MaGiamGia = m.ID_MaGiamGia
            WHERE dl.ID_DatLich = ?
        ");
        $stmt->execute([$registrationId]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registration) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đăng ký sự kiện'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Kiểm tra xem sự kiện có được đăng ký bởi quản lý sự kiện này không
        $isEventManagerEvent = $registration['GhiChu'] && (
            strpos($registration['GhiChu'], 'Đăng ký bởi quản lý sự kiện') !== false || 
            strpos($registration['GhiChu'], 'Đăng ký bởi') !== false ||
            strpos($registration['GhiChu'], 'quản lý sự kiện') !== false
        );
        
        if (!$isEventManagerEvent) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Bạn chỉ có thể chỉnh sửa các sự kiện do bạn đăng ký'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Kiểm tra xem sự kiện đã được duyệt chưa - không cho phép sửa nếu đã duyệt
        if ($registration['TrangThaiDuyet'] === 'Đã duyệt') {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không thể chỉnh sửa sự kiện đã được duyệt'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Lấy thiết bị đã đăng ký
        $equipment = [];
        
        // Lấy thiết bị riêng lẻ
        $stmt = $pdo->prepare("
            SELECT 
                ct.*,
                t.TenThietBi,
                t.LoaiThietBi,
                t.HangSX,
                t.GiaThue,
                t.DonViTinh
            FROM chitietdatsukien ct
            LEFT JOIN thietbi t ON ct.ID_TB = t.ID_TB
            WHERE ct.ID_DatLich = ? AND ct.ID_TB IS NOT NULL
        ");
        $stmt->execute([$registrationId]);
        $individualEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy combo thiết bị
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
        $stmt->execute([$registrationId]);
        $comboEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Định dạng thiết bị cho frontend
        $equipment = [];
        foreach ($individualEquipment as $item) {
            // Lấy giá từ DonGia nếu có, nếu không từ GiaThue
            $price = $item['DonGia'] ?? $item['GiaThue'] ?? 0;
            
            // Nếu giá vẫn là 0, thử parse từ GhiChu
            if ($price == 0 && !empty($item['GhiChu'])) {
                // Thử trích xuất giá từ định dạng GhiChu: "Giá: X VNĐ, Tổng: Y VNĐ"
                if (preg_match('/Giá:\s*([\d,\.]+)\s*VNĐ/', $item['GhiChu'], $matches)) {
                    $price = floatval(str_replace(',', '', $matches[1]));
                }
            }
            
            $equipment[] = [
                'ID_TB' => $item['ID_TB'],
                'ID_Combo' => null,
                'TenThietBi' => $item['TenThietBi'],
                'TenCombo' => null,
                'SoLuong' => $item['SoLuong'],
                'DonGia' => $price,
                'GiaThue' => $item['GiaThue'],
                'GiaCombo' => null,
                'DonViTinh' => $item['DonViTinh'],
                'type' => 'equipment'
            ];
        }
        foreach ($comboEquipment as $item) {
            // Lấy giá từ DonGia nếu có, nếu không từ GiaCombo
            $price = $item['DonGia'] ?? $item['GiaCombo'] ?? 0;
            
            // Nếu giá vẫn là 0, thử parse từ GhiChu
            if ($price == 0 && !empty($item['GhiChu'])) {
                // Thử trích xuất giá từ định dạng GhiChu: "Giá: X VNĐ, Tổng: Y VNĐ"
                if (preg_match('/Giá:\s*([\d,\.]+)\s*VNĐ/', $item['GhiChu'], $matches)) {
                    $price = floatval(str_replace(',', '', $matches[1]));
                }
            }
            
            $equipment[] = [
                'ID_TB' => null,
                'ID_Combo' => $item['ID_Combo'],
                'TenThietBi' => null,
                'TenCombo' => $item['TenCombo'],
                'SoLuong' => $item['SoLuong'],
                'DonGia' => $price,
                'GiaThue' => null,
                'GiaCombo' => $item['GiaCombo'],
                'DonViTinh' => 'combo',
                'type' => 'combo'
            ];
        }
        
        // Lấy thông tin phòng nếu có
        $roomInfo = null;
        if ($registration['ID_Phong']) {
            $stmt = $pdo->prepare("
                SELECT 
                    p.ID_Phong,
                    p.TenPhong,
                    p.SucChua,
                    p.GiaThueGio,
                    p.GiaThueNgay,
                    p.LoaiThue,
                    p.MoTa,
                    p.TrangThai
                FROM phong p
                WHERE p.ID_Phong = ?
            ");
            $stmt->execute([$registration['ID_Phong']]);
            $roomInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Xác định loại thuê từ LoaiThueApDung
        $rentalType = null;
        if ($registration['LoaiThueApDung'] === 'Theo giờ') {
            $rentalType = 'hour';
        } elseif ($registration['LoaiThueApDung'] === 'Theo ngày') {
            $rentalType = 'day';
        }
        
        // Định dạng response
        $response = [
            'success' => true,
            'registration' => [
                'customer' => [
                    'ID_KhachHang' => $registration['ID_KhachHang'],
                    'HoTen' => $registration['HoTen'],
                    'SoDienThoai' => $registration['SoDienThoai'],
                    'DiaChi' => $registration['KhachHangDiaChi'],
                    'Email' => $registration['Email'] ?? ''
                ],
                'event' => [
                    'TenSuKien' => $registration['TenSuKien'],
                    'MoTa' => $registration['MoTa'],
                    'NgayBatDau' => $registration['NgayBatDau'],
                    'NgayKetThuc' => $registration['NgayKetThuc'],
                    'SoNguoiDuKien' => $registration['SoNguoiDuKien'],
                    'NganSach' => $registration['NganSach'],
                    'ID_LoaiSK' => $registration['ID_LoaiSK']
                ],
                'location' => [
                    'ID_DD' => $registration['ID_DD'],
                    'TenDiaDiem' => $registration['TenDiaDiem'],
                    'DiaChi' => $registration['DiaDiemDiaChi'],
                    'LoaiThue' => $registration['LoaiThue'] ?? 'Cả hai',
                    'LoaiDiaDiem' => $registration['LoaiDiaDiem'] ?? '',
                    'GiaThueGio' => $registration['GiaThueGio'],
                    'GiaThueNgay' => $registration['GiaThueNgay']
                ],
                'room' => $roomInfo,
                'rentalType' => $rentalType,
                'equipment' => $equipment,
                'adminNotes' => $registration['GhiChu'] ?? '',
                'discountCode' => $registration['MaCode'] ? [
                    'MaCode' => $registration['MaCode'],
                    'TenMa' => $registration['TenMa'],
                    'LoaiGiamGia' => $registration['LoaiGiamGia'],
                    'GiaTriGiamGia' => $registration['GiaTriGiamGia'],
                    'SoTienGiamGia' => $registration['SoTienGiamGia'] ?? 0,
                    'ID_MaGiamGia' => $registration['ID_MaGiamGia']
                ] : null
            ]
        ];
        
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        ob_clean();
        error_log("Get registration for edit error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy dữ liệu sự kiện: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function updateRegistration() {
    try {
        $pdo = getDBConnection();
        
        // Kiểm tra người dùng đã đăng nhập và là role 3
        if (!isset($_SESSION['user']) || $_SESSION['user']['ID_Role'] != 3) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không có quyền cập nhật sự kiện'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Lấy JSON input
        $jsonInput = $GLOBALS['jsonInput'] ?? null;
        if (!$jsonInput && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $jsonInput = file_get_contents('php://input');
        }
        
        if (!$jsonInput) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không nhận được dữ liệu'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $input = json_decode($jsonInput, true);
        
        if (!$input || json_last_error() !== JSON_ERROR_NONE) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $registrationId = $input['registration_id'] ?? '';
        $customerId = $input['customer_id'] ?? '';
        $event = $input['event'] ?? [];
        $locationId = $input['location'] ?? null;
        $roomId = $input['room_id'] ?? null;
        $equipment = $input['equipment'] ?? [];
        $adminNotes = $input['adminNotes'] ?? '';
        
        // Ghi log dữ liệu thiết bị để debug
        error_log("UPDATE REGISTRATION - Equipment received: " . json_encode($equipment));
        error_log("UPDATE REGISTRATION - Equipment type: " . gettype($equipment));
        error_log("UPDATE REGISTRATION - Equipment is array: " . (is_array($equipment) ? 'yes' : 'no'));
        if (is_array($equipment)) {
            error_log("UPDATE REGISTRATION - Equipment count: " . count($equipment));
        }
        
        if (empty($registrationId) || empty($customerId) || empty($event)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Kiểm tra quyền sở hữu
        $stmt = $pdo->prepare("
            SELECT ID_DatLich, TrangThaiDuyet, GhiChu 
            FROM datlichsukien 
            WHERE ID_DatLich = ? AND ID_KhachHang = ?
        ");
        $stmt->execute([$registrationId, $customerId]);
        $existingRegistration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingRegistration) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đăng ký sự kiện'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Kiểm tra xem sự kiện có được đăng ký bởi quản lý sự kiện này không
        $isEventManagerEvent = $existingRegistration['GhiChu'] && (
            strpos($existingRegistration['GhiChu'], 'Đăng ký bởi quản lý sự kiện') !== false || 
            strpos($existingRegistration['GhiChu'], 'Đăng ký bởi') !== false ||
            strpos($existingRegistration['GhiChu'], 'quản lý sự kiện') !== false
        );
        
        if (!$isEventManagerEvent) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Bạn chỉ có thể cập nhật các sự kiện do bạn đăng ký'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Kiểm tra xem sự kiện đã được duyệt chưa
        if ($existingRegistration['TrangThaiDuyet'] === 'Đã duyệt') {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật sự kiện đã được duyệt'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Xác thực dữ liệu sự kiện
        if (empty($event['name']) || empty($event['type']) || empty($event['startDate']) || empty($event['endDate'])) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện bắt buộc'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Xác thực ràng buộc ngày giờ
        try {
            // Phân tích chuỗi datetime
            $startDateTime = new DateTime($event['startDate']);
            $endDateTime = new DateTime($event['endDate']);
            $now = new DateTime();
            
            // 1. Ràng buộc ngày: Ngày bắt đầu không được trong quá khứ
            if ($startDateTime < $now) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Ngày bắt đầu không được trong quá khứ'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 2. Ràng buộc ngày: Ngày kết thúc phải sau ngày bắt đầu
            if ($endDateTime <= $startDateTime) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Ngày kết thúc phải sau ngày bắt đầu'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 3. Ràng buộc 12 giờ: Phải đăng ký trước ít nhất 12 giờ so với thời gian bắt đầu
            $minDateTime = clone $now;
            $minDateTime->modify('+12 hours');
            if ($startDateTime < $minDateTime) {
                $hoursDiff = ($startDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
                $hoursLeft = max(0, floor($hoursDiff));
                $minDateTimeStr = $minDateTime->format('d/m/Y H:i');
                ob_clean();
                echo json_encode([
                    'success' => false, 
                    'message' => "Sự kiện phải được đăng ký trước ít nhất 12 giờ. Thời gian bắt đầu bạn chọn chỉ còn {$hoursLeft} giờ nữa. Vui lòng chọn thời gian sau {$minDateTimeStr}."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 4. Ràng buộc giờ: Nếu cùng ngày, giờ kết thúc phải sau giờ bắt đầu
            $startDateOnly = clone $startDateTime;
            $startDateOnly->setTime(0, 0, 0);
            $endDateOnly = clone $endDateTime;
            $endDateOnly->setTime(0, 0, 0);
            
            if ($startDateOnly->format('Y-m-d') === $endDateOnly->format('Y-m-d')) {
                // Cùng ngày - kiểm tra giờ
                if ($startDateTime >= $endDateTime) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Giờ kết thúc phải sau giờ bắt đầu khi cùng ngày'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
            
            // 5. Ràng buộc giờ: Thời gian kết thúc không được trong quá khứ
            if ($endDateTime < $now) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Thời gian kết thúc không được trong quá khứ'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // 6. Ràng buộc thời lượng tối thiểu: Sự kiện phải kéo dài ít nhất 1 giờ
            $durationHours = ($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 3600;
            if ($durationHours < 1) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Sự kiện phải kéo dài ít nhất 1 giờ'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
        } catch (Exception $e) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xác thực thời gian: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Đặt timeout cho transaction này để tránh lock wait timeout
        $pdo->setAttribute(PDO::ATTR_TIMEOUT, 30);
        
        // Bắt đầu transaction
        $pdo->beginTransaction();
        
        try {
            // 1. Tính tổng chi phí (bao gồm chi phí phòng nếu đã chọn)
            $totalCost = calculateTotalCost($event, $locationId, $equipment, $input['location_rental_type'] ?? null, $roomId);
            
            // 2. Cập nhật đăng ký sự kiện
            $stmt = $pdo->prepare("
                UPDATE datlichsukien 
                SET 
                    TenSuKien = ?,
                    ID_LoaiSK = ?,
                    MoTa = ?,
                    NgayBatDau = ?,
                    NgayKetThuc = ?,
                    SoNguoiDuKien = ?,
                    NganSach = ?,
                    ID_DD = ?,
                    ID_Phong = ?,
                    TongTien = ?,
                    GhiChu = CONCAT(IFNULL(GhiChu, ''), ' | Cập nhật lúc: ', NOW())
                WHERE ID_DatLich = ?
            ");
            
            // Phân tích datetime từ định dạng datetime-local (YYYY-MM-DDTHH:mm) sang định dạng MySQL (YYYY-MM-DD HH:mm:ss)
            $startDateTime = $event['startDate'];
            $endDateTime = $event['endDate'];
            
            // Chuyển đổi từ định dạng datetime-local sang định dạng datetime MySQL
            if (strpos($startDateTime, 'T') !== false) {
                $startDateTime = str_replace('T', ' ', $startDateTime) . ':00';
            } else if (strpos($startDateTime, ' ') === false) {
                // Nếu chỉ có ngày, thêm giờ mặc định
                $startDateTime = $startDateTime . ' 00:00:00';
            }
            
            if (strpos($endDateTime, 'T') !== false) {
                $endDateTime = str_replace('T', ' ', $endDateTime) . ':00';
            } else if (strpos($endDateTime, ' ') === false) {
                // Nếu chỉ có ngày, thêm giờ mặc định
                $endDateTime = $endDateTime . ' 23:59:59';
            }
            
            $stmt->execute([
                $event['name'],
                $event['type'],
                $event['description'] ?? '',
                $startDateTime,
                $endDateTime,
                $event['expectedGuests'] ?? 0,
                $event['budget'] ?? 0,
                $locationId,
                $roomId,
                $totalCost,
                $registrationId
            ]);
            
            // 3. Xóa thiết bị cũ
            // Sử dụng DELETE đơn giản không có subquery để giảm thiểu thời gian lock
            error_log("UPDATE REGISTRATION - Deleting old equipment for registration ID: {$registrationId}");
            $stmt = $pdo->prepare("DELETE FROM chitietdatsukien WHERE ID_DatLich = ?");
            $stmt->execute([$registrationId]);
            $deletedCount = $stmt->rowCount();
            error_log("UPDATE REGISTRATION - Deleted {$deletedCount} old equipment records");
            
            // 4. Thêm thiết bị mới
            error_log("UPDATE REGISTRATION - Before adding equipment. Equipment: " . json_encode($equipment));
            error_log("UPDATE REGISTRATION - Equipment empty check: " . (empty($equipment) ? 'true' : 'false'));
            error_log("UPDATE REGISTRATION - Equipment is array: " . (is_array($equipment) ? 'yes' : 'no'));
            if (is_array($equipment)) {
                error_log("UPDATE REGISTRATION - Equipment count: " . count($equipment));
            }
            
            if (!empty($equipment) && is_array($equipment) && count($equipment) > 0) {
                error_log("UPDATE REGISTRATION - Calling addEquipmentToRegistration with " . count($equipment) . " items");
                try {
                    // Truyền $pdo để sử dụng cùng transaction
                    addEquipmentToRegistration($registrationId, $equipment, $pdo);
                    error_log("UPDATE REGISTRATION - Equipment added successfully");
                } catch (Exception $e) {
                    error_log("UPDATE REGISTRATION - Error adding equipment: " . $e->getMessage());
                    error_log("UPDATE REGISTRATION - Stack trace: " . $e->getTraceAsString());
                    throw new Exception("Lỗi khi thêm thiết bị: " . $e->getMessage());
                }
            } else {
                error_log("UPDATE REGISTRATION - No equipment to add (empty or not array)");
            }
            
            // 5. Commit transaction
            $pdo->commit();
            
            ob_clean();
            echo json_encode([
                'success' => true, 
                'message' => 'Cập nhật sự kiện thành công',
                'event_id' => $registrationId,
                'total_cost' => $totalCost
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        ob_clean();
        error_log("Update registration error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật sự kiện: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Gửi email thông báo đăng ký sự kiện cho khách hàng
 */
function sendEventRegistrationEmail($customerId, $eventId, $eventData, $totalCost, $roomId = null) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../../config/config.php';
    
    $pdo = getDBConnection();
    
    // Log thông tin đầu vào
    error_log("SENDING EMAIL - Customer ID: $customerId, Event ID: $eventId, Room ID: " . ($roomId ?? 'null'));
    
    // Lấy thông tin khách hàng và email - Query chính xác hơn
    $stmt = $pdo->prepare("
        SELECT 
            kh.ID_KhachHang,
            kh.HoTen,
            kh.ID_User,
            u.Email,
            dl.ID_DatLich,
            dl.TenSuKien,
            dl.NgayBatDau,
            dl.NgayKetThuc,
            dl.SoNguoiDuKien,
            d.ID_DD,
            d.TenDiaDiem,
            d.DiaChi,
            lsk.ID_LoaiSK,
            lsk.TenLoai as TenLoaiSK
        FROM khachhanginfo kh
        INNER JOIN users u ON kh.ID_User = u.ID_User
        LEFT JOIN datlichsukien dl ON dl.ID_DatLich = ? AND dl.ID_KhachHang = kh.ID_KhachHang
        LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
        LEFT JOIN loaisukien lsk ON dl.ID_LoaiSK = lsk.ID_LoaiSK
        WHERE kh.ID_KhachHang = ?
    ");
    $stmt->execute([$eventId, $customerId]);
    $customerInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log kết quả query
    if ($customerInfo) {
        error_log("SENDING EMAIL - Customer found: " . json_encode([
            'ID_KhachHang' => $customerInfo['ID_KhachHang'] ?? 'N/A',
            'HoTen' => $customerInfo['HoTen'] ?? 'N/A',
            'Email' => $customerInfo['Email'] ?? 'N/A',
            'ID_User' => $customerInfo['ID_User'] ?? 'N/A',
            'TenSuKien' => $customerInfo['TenSuKien'] ?? 'N/A',
            'ID_DatLich' => $customerInfo['ID_DatLich'] ?? 'N/A'
        ], JSON_UNESCAPED_UNICODE));
    } else {
        error_log("SENDING EMAIL - Customer not found in first query for Customer ID: $customerId, Event ID: $eventId");
    }
    
    // Nếu không tìm thấy hoặc không có email, thử query lại chỉ với customerId
    if (!$customerInfo || empty($customerInfo['Email'])) {
        error_log("SENDING EMAIL - First query failed or email empty, trying fallback query for customer ID: $customerId");
        $stmt = $pdo->prepare("
            SELECT 
                kh.ID_KhachHang,
                kh.HoTen,
                kh.ID_User,
                u.Email
            FROM khachhanginfo kh
            INNER JOIN users u ON kh.ID_User = u.ID_User
            WHERE kh.ID_KhachHang = ?
        ");
        $stmt->execute([$customerId]);
        $fallbackInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fallbackInfo) {
            error_log("SENDING EMAIL - Fallback query result: " . json_encode([
                'ID_KhachHang' => $fallbackInfo['ID_KhachHang'] ?? 'N/A',
                'HoTen' => $fallbackInfo['HoTen'] ?? 'N/A',
                'Email' => $fallbackInfo['Email'] ?? 'N/A',
                'ID_User' => $fallbackInfo['ID_User'] ?? 'N/A'
            ], JSON_UNESCAPED_UNICODE));
        } else {
            error_log("SENDING EMAIL - Fallback query returned no results for customer ID: $customerId");
        }
        
        if ($fallbackInfo && !empty($fallbackInfo['Email'])) {
            // Merge thông tin từ fallback
            if (!$customerInfo) {
                $customerInfo = $fallbackInfo;
            } else {
                $customerInfo['Email'] = $fallbackInfo['Email'];
                $customerInfo['HoTen'] = $fallbackInfo['HoTen'];
            }
            error_log("SENDING EMAIL - Fallback query successful, email found: " . $customerInfo['Email']);
            
            // Nếu thiếu thông tin sự kiện, lấy từ datlichsukien
            if (empty($customerInfo['TenSuKien']) && !empty($eventId)) {
                $eventStmt = $pdo->prepare("
                    SELECT 
                        dl.TenSuKien,
                        dl.NgayBatDau,
                        dl.NgayKetThuc,
                        dl.SoNguoiDuKien,
                        d.TenDiaDiem,
                        d.DiaChi,
                        lsk.TenLoai as TenLoaiSK
                    FROM datlichsukien dl
                    LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
                    LEFT JOIN loaisukien lsk ON dl.ID_LoaiSK = lsk.ID_LoaiSK
                    WHERE dl.ID_DatLich = ? AND dl.ID_KhachHang = ?
                ");
                $eventStmt->execute([$eventId, $customerId]);
                $eventInfo = $eventStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($eventInfo) {
                    $customerInfo = array_merge($customerInfo, $eventInfo);
                    error_log("SENDING EMAIL - Event info merged successfully");
                }
            }
        } else {
            error_log("SENDING EMAIL - Fallback query also failed for customer ID: $customerId");
        }
    }
    
    if (!$customerInfo) {
        error_log("Cannot send email: Customer info not found for customer ID: $customerId, Event ID: $eventId");
        return false;
    }
    
    if (empty($customerInfo['Email'])) {
        error_log("Cannot send email: Customer email is empty for customer ID: $customerId, Event ID: $eventId");
        error_log("Customer info: " . json_encode($customerInfo, JSON_UNESCAPED_UNICODE));
        return false;
    }
    
    error_log("Preparing to send email to: " . $customerInfo['Email'] . " for customer: " . $customerInfo['HoTen']);
    
    // Lấy thông tin phòng nếu có
    $roomInfo = null;
    if (!empty($roomId)) {
        $roomStmt = $pdo->prepare("SELECT TenPhong FROM phong WHERE ID_Phong = ?");
        $roomStmt->execute([$roomId]);
        $roomInfo = $roomStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Định dạng ngày tháng - Kiểm tra xem có thông tin ngày tháng không
    $formattedStartDate = 'Chưa xác định';
    $formattedEndDate = 'Chưa xác định';
    if (!empty($customerInfo['NgayBatDau'])) {
        try {
            $startDate = new DateTime($customerInfo['NgayBatDau']);
            $formattedStartDate = $startDate->format('d/m/Y H:i');
        } catch (Exception $e) {
            error_log("SENDING EMAIL - Error parsing start date: " . $e->getMessage());
        }
    }
    if (!empty($customerInfo['NgayKetThuc'])) {
        try {
            $endDate = new DateTime($customerInfo['NgayKetThuc']);
            $formattedEndDate = $endDate->format('d/m/Y H:i');
        } catch (Exception $e) {
            error_log("SENDING EMAIL - Error parsing end date: " . $e->getMessage());
        }
    }
    
    // Tạo link xem sự kiện
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'];
    $viewEventUrl = $baseUrl . '/event/my-php-project/event-details.php?id=' . $eventId;
    
    // Tạo nội dung email
    $customerName = htmlspecialchars($customerInfo['HoTen']);
    $eventName = htmlspecialchars($customerInfo['TenSuKien'] ?? $eventData['name'] ?? 'Sự kiện');
    $locationName = htmlspecialchars($customerInfo['TenDiaDiem'] ?? 'Chưa xác định');
    $locationAddress = htmlspecialchars($customerInfo['DiaChi'] ?? 'Chưa xác định');
    $eventType = htmlspecialchars($customerInfo['TenLoaiSK'] ?? 'Chưa xác định');
    $expectedGuests = $customerInfo['SoNguoiDuKien'] ?? 0;
    $roomName = $roomInfo ? htmlspecialchars($roomInfo['TenPhong']) : null;
    
    $emailSubject = "Xác nhận đăng ký sự kiện: $eventName";
    
    $emailBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; padding: 20px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #667eea; }
            .info-row { margin: 10px 0; }
            .info-label { font-weight: bold; color: #555; }
            .info-value { color: #333; }
            .total-cost { background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center; }
            .total-cost h3 { margin: 0; color: #856404; }
            .payment-button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
            .footer { text-align: center; margin-top: 30px; color: #777; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Xác nhận đăng ký sự kiện</h1>
                <p>Cảm ơn bạn đã đăng ký sự kiện với chúng tôi!</p>
            </div>
            <div class='content'>
                <p>Xin chào <strong>$customerName</strong>,</p>
                <p>Sự kiện của bạn đã được đăng ký thành công. Vui lòng kiểm tra thông tin dưới đây:</p>
                
                <div class='info-box'>
                    <h3 style='margin-top: 0; color: #667eea;'>Thông tin sự kiện</h3>
                    <div class='info-row'>
                        <span class='info-label'>Tên sự kiện:</span>
                        <span class='info-value'>$eventName</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Loại sự kiện:</span>
                        <span class='info-value'>$eventType</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Ngày bắt đầu:</span>
                        <span class='info-value'>$formattedStartDate</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Ngày kết thúc:</span>
                        <span class='info-value'>$formattedEndDate</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Số khách dự kiến:</span>
                        <span class='info-value'>$expectedGuests người</span>
                    </div>
                </div>
                
                <div class='info-box'>
                    <h3 style='margin-top: 0; color: #667eea;'>Thông tin địa điểm</h3>
                    <div class='info-row'>
                        <span class='info-label'>Địa điểm:</span>
                        <span class='info-value'>$locationName</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Địa chỉ:</span>
                        <span class='info-value'>$locationAddress</span>
                    </div>
                    " . ($roomName ? "
                    <div class='info-row'>
                        <span class='info-label'>Phòng:</span>
                        <span class='info-value'>$roomName</span>
                    </div>
                    " : "") . "
                </div>
                
                <div class='total-cost'>
                    <h3>Tổng chi phí: " . number_format($totalCost, 0, ',', '.') . " VNĐ</h3>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <p><strong>Vui lòng xem chi tiết sự kiện của bạn.</strong></p>
                    <a href='$viewEventUrl' class='payment-button'>👁️ Xem ngay</a>
                </div>
                
                <div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;'>
                    <p style='margin: 0;'><strong>Lưu ý:</strong></p>
                    <ul style='margin: 10px 0; padding-left: 20px;'>
                        <li>Sự kiện của bạn đang ở trạng thái <strong>Chờ duyệt</strong></li>
                        <li>Sau khi được duyệt, bạn sẽ nhận được email thông báo và có thể thanh toán</li>
                        <li>Nếu có thắc mắc, vui lòng liên hệ với chúng tôi</li>
                    </ul>
                </div>
                
                <div class='footer'>
                    <p>Trân trọng,<br>Đội ngũ quản lý sự kiện</p>
                    <p>Email này được gửi tự động, vui lòng không trả lời email này.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Kiểm tra xem PHPMailer có tồn tại không
    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer class not found. Please install PHPMailer via Composer.");
        return false;
    }
    
    // Cấu hình PHPMailer
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Log cấu hình SMTP (không log password)
        error_log("SMTP Configuration - Host: " . SMTP_HOST . ", Port: " . SMTP_PORT . ", Encryption: " . SMTP_ENCRYPTION);
        error_log("SMTP Configuration - Username: " . SMTP_USERNAME . ", From: " . SMTP_FROM_EMAIL);
        
        // Cài đặt server - Sử dụng cấu hình từ config.php
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Thiết lập timeout
        $mail->Timeout = 30;
        
        // Bật debug mode để log chi tiết vào error_log (không hiển thị trên màn hình)
        $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            // Chỉ log vào error_log, không hiển thị trên màn hình
            error_log("PHPMailer Debug [Level $level]: $str");
        };
        
        // Người nhận
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($customerInfo['Email'], $customerName);
        
        // Nội dung
        $mail->isHTML(true);
        $mail->Subject = $emailSubject;
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($emailBody); // Phiên bản văn bản thuần
        
        // Log thông tin email trước khi gửi
        error_log("SENDING EMAIL - To: " . $customerInfo['Email'] . ", Subject: " . $emailSubject);
        error_log("SENDING EMAIL - Event ID: " . $eventId . ", Total Cost: " . number_format($totalCost, 0, ',', '.') . " VNĐ");
        error_log("SENDING EMAIL - Event Name: " . ($customerInfo['TenSuKien'] ?? 'N/A'));
        error_log("SENDING EMAIL - Location: " . ($customerInfo['TenDiaDiem'] ?? 'N/A'));
        
        // Gửi email và kiểm tra kết quả
        $sendResult = $mail->send();
        
        if ($sendResult) {
            error_log("EMAIL SENT SUCCESSFULLY - To: " . $customerInfo['Email'] . " for event ID: " . $eventId);
            return true;
        } else {
            error_log("EMAIL SEND FAILED - To: " . $customerInfo['Email'] . " for event ID: " . $eventId);
            error_log("EMAIL SEND FAILED - PHPMailer ErrorInfo: " . ($mail->ErrorInfo ?? 'No error info'));
            return false;
        }
        
    } catch (Exception $e) {
        error_log("EMAIL SEND EXCEPTION - To: " . ($customerInfo['Email'] ?? 'unknown') . " for event ID: " . $eventId);
        error_log("EMAIL SEND EXCEPTION - PHPMailer ErrorInfo: " . ($mail->ErrorInfo ?? 'No error info'));
        error_log("EMAIL SEND EXCEPTION - Exception Message: " . $e->getMessage());
        error_log("EMAIL SEND EXCEPTION - Exception Code: " . $e->getCode());
        error_log("EMAIL SEND EXCEPTION - Stack trace: " . $e->getTraceAsString());
        return false;
    }
}
?>