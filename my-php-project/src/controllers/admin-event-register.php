<?php
session_start();
require_once '../../config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Check if user is logged in and has appropriate role (only for sensitive actions)
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
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
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
                dd.GiaThue,
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
        
        // Get registered equipment
        $stmt = $pdo->prepare("
            SELECT 
                st.ID_SuKien_ThietBi,
                t.TenThietBi,
                t.MoTa,
                t.GiaThue,
                t.TrangThai,
                st.SoLuong,
                st.GhiChu
            FROM sukien_thietbi st
            LEFT JOIN thietbi t ON st.ID_ThietBi = t.ID_ThietBi
            WHERE st.ID_SuKien = ?
        ");
        $stmt->execute([$registration['ID_SuKien']]);
        $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $registration['equipment'] = $equipment;
        
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
                u.ID_User
            FROM khachhanginfo kh
            LEFT JOIN users u ON kh.ID_User = u.ID_User
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
                MoTa
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
                GiaThue,
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
                dd.GiaThue,
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
        
        // Get equipment details for each combo
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
        
        // Get form data
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
        
        // Validate required fields
        if (empty($customerId) || empty($eventName) || empty($eventType) || empty($eventDate) || empty($startTime) || empty($endTime) || empty($locationId)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
            return;
        }
        
        // Combine date and time
        $startDateTime = $eventDate . ' ' . $startTime . ':00';
        $endDateTime = $eventDate . ' ' . $endTime . ':00';
        
        $pdo->beginTransaction();
        
        try {
            // Insert event registration
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
            
            // Insert selected combos
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
            
            // Insert selected equipment
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
?>