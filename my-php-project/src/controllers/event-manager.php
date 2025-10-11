<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and has event manager privileges
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit();
}

$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (!in_array($userRole, [1, 2, 3])) {
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit();
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_customers':
            // Lấy danh sách khách hàng để đăng ký sự kiện thay mặt
            $stmt = $pdo->prepare("
                SELECT k.ID_KhachHang, k.HoTen, k.SoDienThoai, k.DiaChi, u.Email
                FROM khachhanginfo k
                INNER JOIN users u ON k.ID_User = u.ID_User
                WHERE u.TrangThai = 'Hoạt động' OR u.TrangThai IS NULL
                ORDER BY k.HoTen
            ");
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'customers' => $customers]);
            break;
            
        case 'get_locations':
            // Lấy danh sách địa điểm
            $stmt = $pdo->prepare("SELECT * FROM diadiem WHERE TrangThaiHoatDong = 'Hoạt động' ORDER BY TenDiaDiem");
            $stmt->execute();
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'locations' => $locations]);
            break;
            
        case 'get_equipment':
            // Lấy danh sách thiết bị
            $stmt = $pdo->prepare("SELECT * FROM thietbi WHERE TrangThai = 'Sẵn sàng' ORDER BY TenThietBi");
            $stmt->execute();
            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'equipment' => $equipment]);
            break;
            
        case 'get_event_types':
            // Lấy danh sách loại sự kiện
            $stmt = $pdo->prepare("SELECT * FROM loaisukien ORDER BY TenLoai");
            $stmt->execute();
            $eventTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'event_types' => $eventTypes]);
            break;
            
        case 'register_for_customer':
            // Đăng ký sự kiện thay mặt khách hàng
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            // Validate required fields
            $requiredFields = ['customer_id', 'event_name', 'event_date', 'event_time', 'location_id'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                    exit();
                }
            }
            
            // Validate date
            $eventDate = $input['event_date'];
            $today = date('Y-m-d');
            if ($eventDate < $today) {
                echo json_encode(['success' => false, 'error' => 'Ngày tổ chức không được là ngày trong quá khứ']);
                exit();
            }
            
            // Get event type ID
            $eventType = $input['event_type'] ?? '';
            $eventTypeId = null;
            if ($eventType) {
                $stmt = $pdo->prepare("SELECT ID_LoaiSK FROM loaisukien WHERE TenLoai = ?");
                $stmt->execute([$eventType]);
                $eventTypeData = $stmt->fetch(PDO::FETCH_ASSOC);
                $eventTypeId = $eventTypeData ? $eventTypeData['ID_LoaiSK'] : null;
            }
            
            // Prepare event datetime
            $eventDateTime = $input['event_date'] . ' ' . $input['event_time'];
            $endDateTime = date('Y-m-d H:i:s', strtotime($eventDateTime . ' +2 hours')); // Default 2 hours duration
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Insert into datlichsukien table
                $sql = "INSERT INTO datlichsukien (
                    ID_KhachHang, TenSuKien, MoTa, NgayBatDau, NgayKetThuc, 
                    ID_DD, ID_LoaiSK, SoNguoiDuKien, NganSach, 
                    TrangThaiDuyet, TrangThaiThanhToan, GhiChu
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $input['customer_id'],
                    $input['event_name'],
                    $input['description'] ?? '',
                    $eventDateTime,
                    $endDateTime,
                    $input['location_id'],
                    $eventTypeId,
                    $input['expected_guests'] ?? null,
                    $input['budget'] ?? null,
                    'Chờ duyệt',
                    'Chưa thanh toán',
                    'Đăng ký bởi quản lý sự kiện'
                ]);
                
                if (!$result) {
                    throw new Exception('Lỗi khi tạo đơn đặt lịch sự kiện');
                }
                
                $datLichId = $pdo->lastInsertId();
                
                // If equipment was selected, add it to chitietdatsukien table
                if (!empty($input['equipment_ids']) && is_array($input['equipment_ids'])) {
                    foreach ($input['equipment_ids'] as $equipmentId) {
                        // Get equipment price
                        $stmt = $pdo->prepare("SELECT GiaThue FROM thietbi WHERE ID_TB = ?");
                        $stmt->execute([$equipmentId]);
                        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($equipment) {
                            $stmt = $pdo->prepare("
                                INSERT INTO chitietdatsukien (ID_DatLich, ID_TB, SoLuong, DonGia, GhiChu) 
                                VALUES (?, ?, 1, ?, 'Thiết bị đề xuất bởi quản lý sự kiện')
                            ");
                            $stmt->execute([$datLichId, $equipmentId, $equipment['GiaThue']]);
                        }
                    }
                }
                
                $pdo->commit();
                
                echo json_encode(['success' => true, 'message' => 'Đăng ký sự kiện thay mặt khách hàng thành công', 'dat_lich_id' => $datLichId]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Lỗi khi đăng ký sự kiện: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_customer_registrations':
            // Lấy danh sách đăng ký của khách hàng
            $customerId = $_GET['customer_id'] ?? '';
            if (!$customerId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID khách hàng']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                SELECT dl.*, d.TenDiaDiem, ls.TenLoai
                FROM datlichsukien dl
                LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
                LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
                WHERE dl.ID_KhachHang = ?
                ORDER BY dl.NgayTao DESC
            ");
            $stmt->execute([$customerId]);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'registrations' => $registrations]);
            break;
            
        case 'update_registration':
            // Cập nhật đăng ký sự kiện
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $registrationId = $input['registration_id'] ?? '';
            if (!$registrationId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID đăng ký']);
                exit();
            }
            
            try {
                $pdo->beginTransaction();
                
                // Update registration
                $stmt = $pdo->prepare("
                    UPDATE datlichsukien 
                    SET TenSuKien = ?, MoTa = ?, NgayBatDau = ?, NgayKetThuc = ?, 
                        ID_DD = ?, ID_LoaiSK = ?, SoNguoiDuKien = ?, NganSach = ?, 
                        GhiChu = ?, NgayCapNhat = CURRENT_TIMESTAMP
                    WHERE ID_DatLich = ?
                ");
                
                $eventDateTime = $input['event_date'] . ' ' . $input['event_time'];
                $endDateTime = date('Y-m-d H:i:s', strtotime($eventDateTime . ' +2 hours'));
                
                $result = $stmt->execute([
                    $input['event_name'],
                    $input['description'] ?? '',
                    $eventDateTime,
                    $endDateTime,
                    $input['location_id'],
                    $input['event_type_id'] ?? null,
                    $input['expected_guests'] ?? null,
                    $input['budget'] ?? null,
                    'Cập nhật bởi quản lý sự kiện',
                    $registrationId
                ]);
                
                if (!$result) {
                    throw new Exception('Lỗi khi cập nhật đăng ký');
                }
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Cập nhật đăng ký thành công']);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Lỗi khi cập nhật đăng ký: ' . $e->getMessage()]);
            }
            break;
            
        case 'delete_registration':
            // Xóa đăng ký sự kiện
            $registrationId = $_GET['registration_id'] ?? $_POST['registration_id'] ?? '';
            if (!$registrationId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID đăng ký']);
                exit();
            }
            
            try {
                $pdo->beginTransaction();
                
                // Delete equipment details first
                $stmt = $pdo->prepare("DELETE FROM chitietdatsukien WHERE ID_DatLich = ?");
                $stmt->execute([$registrationId]);
                
                // Delete registration
                $stmt = $pdo->prepare("DELETE FROM datlichsukien WHERE ID_DatLich = ?");
                $result = $stmt->execute([$registrationId]);
                
                if (!$result) {
                    throw new Exception('Lỗi khi xóa đăng ký');
                }
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Xóa đăng ký thành công']);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Lỗi khi xóa đăng ký: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_registration_details':
            // Lấy chi tiết đăng ký
            $registrationId = $_GET['registration_id'] ?? '';
            if (!$registrationId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID đăng ký']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                SELECT dl.*, d.TenDiaDiem, d.DiaChi, ls.TenLoai, k.HoTen, k.SoDienThoai
                FROM datlichsukien dl
                LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
                LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
                LEFT JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
                WHERE dl.ID_DatLich = ?
            ");
            $stmt->execute([$registrationId]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$registration) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy đăng ký']);
                exit();
            }
            
            // Get equipment details
            $stmt = $pdo->prepare("
                SELECT ct.*, t.TenThietBi, t.LoaiThietBi
                FROM chitietdatsukien ct
                LEFT JOIN thietbi t ON ct.ID_TB = t.ID_TB
                WHERE ct.ID_DatLich = ?
            ");
            $stmt->execute([$registrationId]);
            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $registration['equipment'] = $equipment;
            
            echo json_encode(['success' => true, 'registration' => $registration]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
