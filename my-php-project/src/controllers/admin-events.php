<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Debug session
error_log("Admin Events Controller - Session data: " . print_r($_SESSION, true));
error_log("Admin Events Controller - Action: " . ($_GET['action'] ?? $_POST['action'] ?? 'none'));

// Kiểm tra người dùng đã đăng nhập và có role phù hợp
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập', 'debug' => 'No session user']);
    exit;
}

$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (!in_array($userRole, [1, 2, 3])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập', 'debug' => 'Invalid role: ' . $userRole]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_events':
        getEvents();
        break;
    case 'get_event_details':
        getEventDetails();
        break;
    case 'update_event':
        updateEvent();
        break;
    case 'delete_event':
        deleteEvent();
        break;
    case 'get_event_equipment':
        getEventEquipment();
        break;
    case 'add_event_equipment':
        addEventEquipment();
        break;
    case 'remove_event_equipment':
        removeEventEquipment();
        break;
    case 'get_registrations':
        getRegistrations();
        break;
    case 'get_registration_stats':
        getRegistrationStats();
        break;
    case 'get_registration_details':
        getRegistrationDetails();
        break;
    case 'update_registration_status':
        updateRegistrationStatus();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

function getEvents() {
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
                dl.TongTien,
                dl.TrangThaiDuyet,
                dl.TrangThaiThanhToan,
                dl.GhiChu,
                dl.NgayTao,
                dl.NgayCapNhat,
                dd.TenDiaDiem,
                dd.DiaChi,
                ls.TenLoai as TenLoaiSK,
                kh.HoTen as TenKhachHang,
                kh.SoDienThoai
                    FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
                    LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                    ORDER BY dl.NgayTao DESC
                ");
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'events' => $events]);
                
            } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách sự kiện: ' . $e->getMessage()]);
    }
}

function getEventDetails() {
    try {
        $pdo = getDBConnection();
        
        $eventId = $_GET['event_id'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện']);
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
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện']);
            return;
        }
        
        echo json_encode(['success' => true, 'event' => $event]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy chi tiết sự kiện: ' . $e->getMessage()]);
    }
}

function updateEvent() {
    try {
        $pdo = getDBConnection();
        
        $eventId = $_POST['event_id'] ?? '';
        $eventName = $_POST['event_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $locationId = $_POST['location_id'] ?? '';
        $eventTypeId = $_POST['event_type_id'] ?? '';
        $expectedGuests = $_POST['expected_guests'] ?? 0;
        $budget = $_POST['budget'] ?? 0;
        $notes = $_POST['notes'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($eventId) || empty($eventName) || empty($startDate) || empty($endDate)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
                $stmt = $pdo->prepare("
            UPDATE datlichsukien 
            SET TenSuKien = ?, MoTa = ?, NgayBatDau = ?, NgayKetThuc = ?, 
                ID_DD = ?, ID_LoaiSK = ?, SoNguoiDuKien = ?, NganSach = ?, 
                GhiChu = ?, TrangThaiDuyet = ?, NgayCapNhat = NOW()
            WHERE ID_DatLich = ?
        ");
        $stmt->execute([
            $eventName, $description, $startDate, $endDate, 
            $locationId, $eventTypeId, $expectedGuests, $budget, 
            $notes, $status, $eventId
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật sự kiện thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật sự kiện: ' . $e->getMessage()]);
    }
}

function deleteEvent() {
    try {
        $pdo = getDBConnection();
        
        $eventId = $_POST['event_id'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $pdo->beginTransaction();
        
        try {
            // Delete event equipment
            $stmt = $pdo->prepare("DELETE FROM sukien_thietbi WHERE ID_SuKien = (SELECT ID_SuKien FROM datlichsukien WHERE ID_DatLich = ?)");
            $stmt->execute([$eventId]);
            
            // Delete event staff assignments
            $stmt = $pdo->prepare("DELETE FROM event_staff_assignments WHERE ID_DatLich = ?");
            $stmt->execute([$eventId]);
            
            // Delete work schedule
            $stmt = $pdo->prepare("DELETE FROM lichlamviec WHERE ID_DatLich = ?");
            $stmt->execute([$eventId]);
            
            // Delete event
            $stmt = $pdo->prepare("DELETE FROM datlichsukien WHERE ID_DatLich = ?");
            $stmt->execute([$eventId]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Xóa sự kiện thành công']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa sự kiện: ' . $e->getMessage()]);
    }
}

function getEventEquipment() {
    try {
        $pdo = getDBConnection();
        
        $eventId = $_GET['event_id'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện']);
            return;
        }
        
        // Get event's ID_SuKien first
        $stmt = $pdo->prepare("SELECT ID_SuKien FROM datlichsukien WHERE ID_DatLich = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện']);
            return;
        }
        
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
        $stmt->execute([$event['ID_SuKien']]);
        $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'equipment' => $equipment]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thiết bị sự kiện: ' . $e->getMessage()]);
    }
}

function addEventEquipment() {
    try {
        $pdo = getDBConnection();
        
        $eventId = $_POST['event_id'] ?? '';
        $equipmentId = $_POST['equipment_id'] ?? '';
        $quantity = $_POST['quantity'] ?? 1;
        $notes = $_POST['notes'] ?? '';
        
        if (empty($eventId) || empty($equipmentId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Get event's ID_SuKien
        $stmt = $pdo->prepare("SELECT ID_SuKien FROM datlichsukien WHERE ID_DatLich = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện']);
            return;
        }
        
        // Check if equipment is already assigned
        $stmt = $pdo->prepare("SELECT ID_SuKien_ThietBi FROM sukien_thietbi WHERE ID_SuKien = ? AND ID_ThietBi = ?");
        $stmt->execute([$event['ID_SuKien'], $equipmentId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Thiết bị đã được gán cho sự kiện này']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO sukien_thietbi (ID_SuKien, ID_ThietBi, SoLuong, GhiChu)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$event['ID_SuKien'], $equipmentId, $quantity, $notes]);
        
        echo json_encode(['success' => true, 'message' => 'Thêm thiết bị thành công']);
        
            } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm thiết bị: ' . $e->getMessage()]);
    }
}

function removeEventEquipment() {
    try {
        $pdo = getDBConnection();
        
        $assignmentId = $_POST['assignment_id'] ?? '';
        
        if (empty($assignmentId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM sukien_thietbi WHERE ID_SuKien = ? AND ID_TB = ?");
        $stmt->execute([$assignmentId]);
        
        echo json_encode(['success' => true, 'message' => 'Xóa thiết bị thành công']);
        
            } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa thiết bị: ' . $e->getMessage()]);
    }
}

function getRegistrations() {
    try {
        error_log("getRegistrations function called");
        $pdo = getDBConnection();
        
        if (!$pdo) {
            error_log("Database connection failed");
            echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
            return;
        }
        
        error_log("Database connection successful");
        
                $stmt = $pdo->prepare("
            SELECT 
                dl.ID_DatLich,
                dl.TenSuKien,
                dl.MoTa,
                dl.NgayBatDau,
                dl.NgayKetThuc,
                dl.SoNguoiDuKien,
                dl.NganSach,
                dl.TongTien,
                dl.TrangThaiDuyet,
                dl.TrangThaiThanhToan,
                dl.GhiChu,
                dl.NgayTao,
                dl.NgayCapNhat,
                dd.TenDiaDiem,
                dd.DiaChi,
                ls.TenLoai,
                kh.HoTen,
                kh.SoDienThoai
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            ORDER BY dl.NgayTao DESC
        ");
        $stmt->execute();
        $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($registrations) . " registrations");
        if (count($registrations) > 0) {
            error_log("First registration: " . print_r($registrations[0], true));
            // Kiểm tra ID_DatLich
            foreach ($registrations as $index => $reg) {
                if (empty($reg['ID_DatLich']) || $reg['ID_DatLich'] == 0) {
                    error_log("WARNING: Registration at index $index has invalid ID_DatLich: " . var_export($reg['ID_DatLich'], true));
                    error_log("Full registration data: " . print_r($reg, true));
                }
            }
        }
        
        // Đảm bảo ID_DatLich là số nguyên
        foreach ($registrations as &$reg) {
            if (isset($reg['ID_DatLich'])) {
                $reg['ID_DatLich'] = intval($reg['ID_DatLich']);
            }
        }
        unset($reg); // Unset reference
        
        $response = ['success' => true, 'registrations' => $registrations];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                
            } catch (Exception $e) {
        error_log("getRegistrations error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách đăng ký: ' . $e->getMessage()]);
    }
            }
            
function getRegistrationStats() {
            try {
        $pdo = getDBConnection();
                
        // Get total registrations
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM datlichsukien");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
        // Get pending registrations
                $stmt = $pdo->query("SELECT COUNT(*) as pending FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
                
        // Get approved registrations
                $stmt = $pdo->query("SELECT COUNT(*) as approved FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
        $approved = $stmt->fetch(PDO::FETCH_ASSOC)['approved'];
                
        // Get rejected registrations
                $stmt = $pdo->query("SELECT COUNT(*) as rejected FROM datlichsukien WHERE TrangThaiDuyet = 'Từ chối'");
        $rejected = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];
        
        echo json_encode([
            'success' => true, 
            'stats' => [
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected
            ]
        ]);
    
            } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê: ' . $e->getMessage()]);
    }
}

function getRegistrationDetails() {
    try {
        header('Content-Type: application/json; charset=utf-8');
        $pdo = getDBConnection();
        
        // Kiểm tra cả GET và POST
        $id = $_GET['id'] ?? $_POST['id'] ?? '';
        
        // Validate ID
        if (empty($id) || $id === '0' || $id === 0 || !is_numeric($id) || intval($id) <= 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Thiếu thông tin đăng ký hoặc ID không hợp lệ (ID = ' . var_export($id, true) . ')'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }
        
        $id = intval($id); // Đảm bảo ID là số nguyên
        
        // Get registration details - Bao gồm thông tin phòng và giá loại sự kiện
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
                dd.LoaiDiaDiem,
                ls.TenLoai,
                ls.MoTa as LoaiSKMoTa,
                ls.GiaCoBan,
                kh.HoTen,
                kh.SoDienThoai,
                kh.DiaChi as KhachHangDiaChi,
                p.ID_Phong,
                p.TenPhong,
                p.SucChua as PhongSucChua,
                p.GiaThueGio as PhongGiaThueGio,
                p.GiaThueNgay as PhongGiaThueNgay,
                p.LoaiThue as PhongLoaiThue,
                p.MoTa as PhongMoTa
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            LEFT JOIN phong p ON dl.ID_Phong = p.ID_Phong
            WHERE dl.ID_DatLich = ?
        ");
        $stmt->execute([$id]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Parse staff info from GhiChu if exists
        $staffRegisteredInfo = null;
        if (!empty($registration['GhiChu'])) {
            // Check if GhiChu contains staff info pattern: [NHANVIEN_ID:xxx] - [NHANVIEN_NAME:xxx]
            if (preg_match('/\[NHANVIEN_ID:(\d+)\].*?\[NHANVIEN_NAME:(.+?)\]/', $registration['GhiChu'], $matches)) {
                $staffRegisteredId = $matches[1];
                $staffRegisteredName = $matches[2];
                
                // Get full staff info
                $stmt = $pdo->prepare("
                    SELECT nv.ID_NhanVien, nv.HoTen, nv.ChucVu, nv.SoDienThoai
                    FROM nhanvieninfo nv
                    WHERE nv.ID_NhanVien = ?
                ");
                $stmt->execute([$staffRegisteredId]);
                $staffRegisteredInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$staffRegisteredInfo) {
                    // Fallback to name from GhiChu
                    $staffRegisteredInfo = [
                        'ID_NhanVien' => $staffRegisteredId,
                        'HoTen' => $staffRegisteredName,
                        'ChucVu' => 'Quản lý sự kiện',
                        'SoDienThoai' => 'N/A'
                    ];
                }
            }
        }
        
        if (!$registration) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đăng ký']);
            return;
        }
        
        // Get registered equipment
        $equipment = [];
        error_log("Getting equipment for registration ID: " . $id);
        
        try {
            // Get equipment from chitietdatsukien table (correct table based on registration process)
            $equipment = [];
            
            // Get individual equipment
            $stmt = $pdo->prepare("
                SELECT 
                    ct.ID_CT,
                    ct.ID_DatLich,
                    ct.ID_TB,
                    ct.ID_Combo,
                    ct.SoLuong,
                    ct.DonGia,
                    ct.GhiChu,
                    tb.TenThietBi,
                    tb.LoaiThietBi,
                    tb.HangSX,
                    tb.GiaThue,
                    tb.DonViTinh,
                    tb.TrangThai,
                    tb.MoTa
                FROM chitietdatsukien ct
                LEFT JOIN thietbi tb ON ct.ID_TB = tb.ID_TB
                WHERE ct.ID_DatLich = ? AND ct.ID_TB IS NOT NULL
            ");
            $stmt->execute([$id]);
            $individualEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Individual equipment query executed. Found: " . count($individualEquipment));
            if (count($individualEquipment) > 0) {
                error_log("First individual equipment: " . json_encode($individualEquipment[0]));
            }
            
            // Get combo equipment
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
                    c.MoTa as ComboMoTa,
                    c.GiaCombo
                FROM chitietdatsukien ct
                LEFT JOIN combo c ON ct.ID_Combo = c.ID_Combo
                WHERE ct.ID_DatLich = ? AND ct.ID_Combo IS NOT NULL
            ");
            $stmt->execute([$id]);
            $comboEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Combo equipment query executed. Found: " . count($comboEquipment));
            if (count($comboEquipment) > 0) {
                error_log("First combo equipment: " . json_encode($comboEquipment[0]));
            }
            
            // Combine equipment
            $equipment = array_merge($individualEquipment, $comboEquipment);
            
            error_log("Total equipment found: " . count($equipment));
            error_log("Individual equipment count: " . count($individualEquipment));
            error_log("Combo equipment count: " . count($comboEquipment));
            
            // Debug: Check if there's any data in chitietdatsukien for this registration
            $debugStmt = $pdo->prepare("SELECT COUNT(*) as total FROM chitietdatsukien WHERE ID_DatLich = ?");
            $debugStmt->execute([$id]);
            $debugResult = $debugStmt->fetch(PDO::FETCH_ASSOC);
            error_log("Total records in chitietdatsukien for ID_DatLich = $id: " . $debugResult['total']);
            
        } catch (Exception $e) {
            error_log("Error getting equipment: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $equipment = [];
        }
        
        // Generate HTML
        $html = '
        <div class="row">
            <div class="col-md-6">
                <h5><i class="fas fa-info-circle text-primary"></i> Thông tin sự kiện</h5>
                <table class="table table-sm">
                    <tr><td><strong>Tên sự kiện:</strong></td><td>' . htmlspecialchars($registration['TenSuKien']) . '</td></tr>
                    <tr><td><strong>Loại sự kiện:</strong></td><td>' . htmlspecialchars($registration['TenLoai']) . '</td></tr>
                    <tr><td><strong>Mô tả:</strong></td><td>' . htmlspecialchars($registration['MoTa'] ?: 'Không có') . '</td></tr>
                    <tr><td><strong>Số khách dự kiến:</strong></td><td>' . number_format($registration['SoNguoiDuKien'] ?: 0) . ' người</td></tr>
                    <tr><td><strong>Ngân sách:</strong></td><td>' . number_format($registration['NganSach'] ?: 0) . ' VNĐ</td></tr>
                    <tr><td><strong>Ghi chú:</strong></td><td>' . htmlspecialchars($registration['GhiChu'] ?: 'Không có') . '</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5><i class="fas fa-calendar text-success"></i> Thời gian</h5>
                <table class="table table-sm">
                    <tr><td><strong>Ngày bắt đầu:</strong></td><td>' . date('d/m/Y H:i', strtotime($registration['NgayBatDau'])) . '</td></tr>
                    <tr><td><strong>Ngày kết thúc:</strong></td><td>' . date('d/m/Y H:i', strtotime($registration['NgayKetThuc'])) . '</td></tr>
                    <tr><td><strong>Ngày đăng ký:</strong></td><td>' . date('d/m/Y H:i', strtotime($registration['NgayTao'])) . '</td></tr>
                </table>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <h5><i class="fas fa-map-marker-alt text-warning"></i> Địa điểm</h5>
                <table class="table table-sm">
                    <tr><td><strong>Tên địa điểm:</strong></td><td>' . htmlspecialchars($registration['TenDiaDiem']) . '</td></tr>
                    <tr><td><strong>Địa chỉ:</strong></td><td>' . htmlspecialchars($registration['DiaChi']) . '</td></tr>';
        
        // Hiển thị thông tin phòng nếu có
        if (!empty($registration['ID_Phong']) && !empty($registration['TenPhong'])) {
            $html .= '
                    <tr><td><strong>Phòng:</strong></td><td><span class="badge bg-info">' . htmlspecialchars($registration['TenPhong']) . '</span></td></tr>
                    <tr><td><strong>Sức chứa phòng:</strong></td><td>' . number_format($registration['PhongSucChua'] ?: 0) . ' người</td></tr>
                    <tr><td><strong>Giá thuê phòng/giờ:</strong></td><td><strong class="text-success">' . number_format($registration['PhongGiaThueGio'] ?: 0) . ' VNĐ</strong></td></tr>
                    <tr><td><strong>Giá thuê phòng/ngày:</strong></td><td><strong class="text-success">' . number_format($registration['PhongGiaThueNgay'] ?: 0) . ' VNĐ</strong></td></tr>
                    <tr><td><strong>Loại thuê phòng:</strong></td><td>' . htmlspecialchars($registration['PhongLoaiThue'] ?: 'Chưa xác định') . '</td></tr>';
            
            // Hiển thị loại thuê đã áp dụng nếu có
            if (!empty($registration['LoaiThueApDung'])) {
                $html .= '<tr><td><strong>Loại thuê đã áp dụng:</strong></td><td><span class="badge bg-primary">' . htmlspecialchars($registration['LoaiThueApDung']) . '</span></td></tr>';
            }
        } else {
            // Hiển thị giá địa điểm nếu không có phòng
            $html .= '
                    <tr><td><strong>Sức chứa:</strong></td><td>' . number_format($registration['SucChua'] ?: 0) . ' người' . ($registration['LoaiDiaDiem'] === 'Trong nhà' ? ' (tổng các phòng)' : '') . '</td></tr>
                    <tr><td><strong>Giá thuê/giờ:</strong></td><td>' . number_format($registration['GiaThueGio'] ?: 0) . ' VNĐ</td></tr>
                    <tr><td><strong>Giá thuê/ngày:</strong></td><td>' . number_format($registration['GiaThueNgay'] ?: 0) . ' VNĐ</td></tr>
                    <tr><td><strong>Loại thuê:</strong></td><td>' . htmlspecialchars($registration['LoaiThue'] ?: 'Chưa xác định') . '</td></tr>';
            
            // Hiển thị loại thuê đã áp dụng nếu có
            if (!empty($registration['LoaiThueApDung'])) {
                $html .= '<tr><td><strong>Loại thuê đã áp dụng:</strong></td><td><span class="badge bg-primary">' . htmlspecialchars($registration['LoaiThueApDung']) . '</span></td></tr>';
            }
        }
        
        $html .= '
                </table>
            </div>
            <div class="col-md-6">
                <h5><i class="fas fa-user text-info"></i> Khách hàng</h5>
                <table class="table table-sm">
                    <tr><td><strong>Họ tên:</strong></td><td>' . htmlspecialchars($registration['HoTen']) . '</td></tr>
                    <tr><td><strong>Số điện thoại:</strong></td><td>' . htmlspecialchars($registration['SoDienThoai']) . '</td></tr>
                    <tr><td><strong>Địa chỉ:</strong></td><td>' . htmlspecialchars($registration['KhachHangDiaChi'] ?: 'Không có') . '</td></tr>
                </table>';
        
        // Add staff registration info if available
        if ($staffRegisteredInfo) {
            $html .= '
                <h5 class="mt-3"><i class="fas fa-user-tie text-warning"></i> Nhân viên phụ trách đăng ký</h5>
                <table class="table table-sm">
                    <tr><td><strong>Họ tên:</strong></td><td>' . htmlspecialchars($staffRegisteredInfo['HoTen']) . '</td></tr>
                    <tr><td><strong>Chức vụ:</strong></td><td>' . htmlspecialchars($staffRegisteredInfo['ChucVu'] ?? 'Quản lý sự kiện') . '</td></tr>
                    <tr><td><strong>Số điện thoại:</strong></td><td>' . htmlspecialchars($staffRegisteredInfo['SoDienThoai'] ?? 'N/A') . '</td></tr>
                </table>';
        }
        
        $html .= '
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <h5><i class="fas fa-money-bill-wave text-success"></i> Thông tin giá tiền</h5>
                <table class="table table-sm">
                    <tr><td><strong>Tổng tiền:</strong></td><td><span class="badge bg-success fs-6">' . number_format($registration['TongTien'] ?: 0) . ' VNĐ</span></td></tr>';
        
        // Hiển thị giá loại sự kiện nếu có
        if (!empty($registration['GiaCoBan']) && floatval($registration['GiaCoBan']) > 0) {
            $html .= '<tr><td><strong>Giá loại sự kiện:</strong></td><td><span class="text-primary">' . number_format($registration['GiaCoBan']) . ' VNĐ</span></td></tr>';
        }
        
        // Tính và hiển thị giá địa điểm/phòng
        $startDate = new DateTime($registration['NgayBatDau']);
        $endDate = new DateTime($registration['NgayKetThuc']);
        $durationHours = ($endDate->getTimestamp() - $startDate->getTimestamp()) / 3600;
        $durationDays = $durationHours < 24 ? 1 : ceil($durationHours / 24);
        
        $locationCost = 0;
        $locationCostLabel = '';
        
        // Nếu có phòng, tính giá phòng
        if (!empty($registration['ID_Phong']) && !empty($registration['TenPhong'])) {
            // Ưu tiên LoaiThueApDung (loại thuê đã chọn khi đăng ký)
            if (!empty($registration['LoaiThueApDung'])) {
                // Có loại thuê đã chọn rõ ràng
                if ($registration['LoaiThueApDung'] === 'Theo giờ' && !empty($registration['PhongGiaThueGio'])) {
                    $locationCost = $durationHours * floatval($registration['PhongGiaThueGio']);
                    $locationCostLabel = 'Giá thuê phòng (' . htmlspecialchars($registration['TenPhong']) . ' - Theo giờ):';
                } elseif ($registration['LoaiThueApDung'] === 'Theo ngày' && !empty($registration['PhongGiaThueNgay'])) {
                    $locationCost = $durationDays * floatval($registration['PhongGiaThueNgay']);
                    $locationCostLabel = 'Giá thuê phòng (' . htmlspecialchars($registration['TenPhong']) . ' - Theo ngày):';
                }
            } else {
                // Không có LoaiThueApDung, dùng PhongLoaiThue mặc định
                if ($registration['PhongLoaiThue'] === 'Theo giờ' && !empty($registration['PhongGiaThueGio'])) {
                    $locationCost = $durationHours * floatval($registration['PhongGiaThueGio']);
                    $locationCostLabel = 'Giá thuê phòng (' . htmlspecialchars($registration['TenPhong']) . ' - Theo giờ):';
                } elseif ($registration['PhongLoaiThue'] === 'Theo ngày' && !empty($registration['PhongGiaThueNgay'])) {
                    $locationCost = $durationDays * floatval($registration['PhongGiaThueNgay']);
                    $locationCostLabel = 'Giá thuê phòng (' . htmlspecialchars($registration['TenPhong']) . ' - Theo ngày):';
                } elseif ($registration['PhongLoaiThue'] === 'Cả hai') {
                    // Mặc định theo ngày nếu chưa chọn
                    $locationCost = $durationDays * floatval($registration['PhongGiaThueNgay'] ?? 0);
                    $locationCostLabel = 'Giá thuê phòng (' . htmlspecialchars($registration['TenPhong']) . ' - Theo ngày, mặc định):';
                }
            }
        } else {
            // Tính giá địa điểm
            // Ưu tiên LoaiThueApDung (loại thuê đã chọn khi đăng ký)
            if (!empty($registration['LoaiThueApDung'])) {
                // Có loại thuê đã chọn rõ ràng
                if ($registration['LoaiThueApDung'] === 'Theo giờ' && !empty($registration['GiaThueGio'])) {
                    $locationCost = $durationHours * floatval($registration['GiaThueGio']);
                    $locationCostLabel = 'Giá thuê địa điểm (Theo giờ):';
                } elseif ($registration['LoaiThueApDung'] === 'Theo ngày' && !empty($registration['GiaThueNgay'])) {
                    $locationCost = $durationDays * floatval($registration['GiaThueNgay']);
                    $locationCostLabel = 'Giá thuê địa điểm (Theo ngày):';
                }
            } else {
                // Không có LoaiThueApDung, dùng LoaiThue mặc định
                if ($registration['LoaiThue'] === 'Theo giờ' && !empty($registration['GiaThueGio'])) {
                    $locationCost = $durationHours * floatval($registration['GiaThueGio']);
                    $locationCostLabel = 'Giá thuê địa điểm (Theo giờ):';
                } elseif ($registration['LoaiThue'] === 'Theo ngày' && !empty($registration['GiaThueNgay'])) {
                    $locationCost = $durationDays * floatval($registration['GiaThueNgay']);
                    $locationCostLabel = 'Giá thuê địa điểm (Theo ngày):';
                } elseif ($registration['LoaiThue'] === 'Cả hai') {
                    // Mặc định theo ngày
                    $locationCost = $durationDays * floatval($registration['GiaThueNgay'] ?? 0);
                    $locationCostLabel = 'Giá thuê địa điểm (Theo ngày, mặc định):';
                }
            }
        }
        
        if ($locationCost > 0) {
            $html .= '<tr><td><strong>' . $locationCostLabel . '</strong></td><td><span class="text-success">' . number_format($locationCost) . ' VNĐ</span></td></tr>';
        }
        
        // Tính tổng chi phí thiết bị
        $equipmentCost = 0;
        if (!empty($equipment)) {
            foreach ($equipment as $item) {
                $itemPrice = floatval($item['DonGia'] ?? $item['GiaThue'] ?? $item['GiaCombo'] ?? 0);
                $itemQuantity = intval($item['SoLuong'] ?? 1);
                $equipmentCost += $itemPrice * $itemQuantity;
            }
        }
        
        if ($equipmentCost > 0) {
            $html .= '<tr><td><strong>Tổng giá thiết bị:</strong></td><td><span class="text-info">' . number_format($equipmentCost) . ' VNĐ</span></td></tr>';
        }
        
        // Tính tổng từ các thành phần để so sánh
        $calculatedTotal = 0;
        $eventTypePrice = floatval($registration['GiaCoBan'] ?? 0);
        
        // LUÔN thêm giá loại sự kiện nếu có (giống logic frontend)
        if ($eventTypePrice > 0) {
            $calculatedTotal += $eventTypePrice;
        }
        
        $calculatedTotal += $locationCost;
        $calculatedTotal += $equipmentCost;
        
        // Hiển thị breakdown và so sánh
        $html .= '<tr><td colspan="2"><hr class="my-2"></td></tr>';
        $html .= '<tr><td><strong>Tổng tính toán:</strong></td><td><span class="badge bg-' . 
                 (abs($calculatedTotal - floatval($registration['TongTien'])) < 1 ? 'success' : 'warning') . '">' . 
                 number_format($calculatedTotal) . ' VNĐ</span></td></tr>';
        
        $html .= '
                    <tr><td><strong>Ngân sách:</strong></td><td>' . number_format($registration['NganSach'] ?: 0) . ' VNĐ</td></tr>
                    <tr><td><strong>Trạng thái thanh toán:</strong></td><td><span class="badge bg-' . ($registration['TrangThaiThanhToan'] === 'Đã thanh toán đủ' ? 'success' : ($registration['TrangThaiThanhToan'] === 'Đã đặt cọc' ? 'warning' : 'secondary')) . '">' . htmlspecialchars($registration['TrangThaiThanhToan']) . '</span></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5><i class="fas fa-info-circle text-primary"></i> Thông tin sự kiện</h5>
                <table class="table table-sm">
                    <tr><td><strong>Loại sự kiện:</strong></td><td>' . htmlspecialchars($registration['TenLoai']) . '</td></tr>
                    <tr><td><strong>Số người dự kiến:</strong></td><td>' . number_format($registration['SoNguoiDuKien'] ?: 0) . ' người</td></tr>
                    <tr><td><strong>Trạng thái duyệt:</strong></td><td><span class="badge bg-' . ($registration['TrangThaiDuyet'] === 'Đã duyệt' ? 'success' : ($registration['TrangThaiDuyet'] === 'Từ chối' ? 'danger' : 'warning')) . '">' . htmlspecialchars($registration['TrangThaiDuyet']) . '</span></td></tr>
                </table>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <h5><i class="fas fa-cogs text-secondary"></i> Thiết bị đã đăng ký</h5>';
        
        if (!empty($equipment)) {
            $html .= '<div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Tên thiết bị</th>
                            <th>Loại</th>
                            <th>Hãng</th>
                            <th>Số lượng</th>
                            <th>Đơn vị</th>
                            <th>Giá thuê</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>';
        
            foreach ($equipment as $item) {
                // Check if it's combo or individual equipment
                if (!empty($item['TenCombo']) || !empty($item['ID_Combo'])) {
                    // Combo equipment
                    $comboName = htmlspecialchars($item['TenCombo'] ?? 'Combo không xác định');
                    $comboQuantity = intval($item['SoLuong'] ?? 1);
                    $comboPrice = floatval($item['DonGia'] ?? $item['GiaCombo'] ?? 0);
                    $comboNote = htmlspecialchars($item['GhiChu'] ?? 'Combo thiết bị');
                    
                    $html .= '<tr>
                        <td><strong><i class="fas fa-box text-primary"></i> ' . $comboName . '</strong></td>
                        <td><span class="badge bg-info">Combo</span></td>
                        <td>N/A</td>
                        <td><span class="badge bg-primary">' . $comboQuantity . '</span></td>
                        <td>combo</td>
                        <td><strong class="text-success">' . number_format($comboPrice) . ' VNĐ</strong></td>
                        <td>' . $comboNote . '</td>
                    </tr>';
                } else if (!empty($item['TenThietBi']) || !empty($item['ID_TB'])) {
                    // Individual equipment
                    $equipName = htmlspecialchars($item['TenThietBi'] ?? 'Thiết bị không xác định');
                    $equipType = htmlspecialchars($item['LoaiThietBi'] ?? 'N/A');
                    $equipBrand = htmlspecialchars($item['HangSX'] ?? 'N/A');
                    $equipQuantity = intval($item['SoLuong'] ?? 1);
                    $equipUnit = htmlspecialchars($item['DonViTinh'] ?? 'cái');
                    $equipPrice = floatval($item['DonGia'] ?? $item['GiaThue'] ?? 0);
                    $equipNote = htmlspecialchars($item['GhiChu'] ?? 'Thiết bị riêng lẻ');
                    
                    $html .= '<tr>
                        <td><strong><i class="fas fa-cog text-primary"></i> ' . $equipName . '</strong></td>
                        <td>' . $equipType . '</td>
                        <td>' . $equipBrand . '</td>
                        <td><span class="badge bg-primary">' . $equipQuantity . '</span></td>
                        <td>' . $equipUnit . '</td>
                        <td><strong class="text-success">' . number_format($equipPrice) . ' VNĐ</strong></td>
                        <td>' . $equipNote . '</td>
                    </tr>';
                } else {
                    // Unknown type - log for debugging
                    error_log("Unknown equipment type: " . json_encode($item));
                }
            }
            
            $html .= '</tbody>
                </table>
            </div>';
        } else {
            $html .= '<div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Chưa có thiết bị nào được đăng ký cho sự kiện này.
                <br><small>Debug: Registration ID = ' . $id . '</small>
            </div>';
        }
        
        $html .= '</div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <h5><i class="fas fa-info-circle text-primary"></i> Trạng thái</h5>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Duyệt:</strong> 
                        <span class="badge bg-' . ($registration['TrangThaiDuyet'] == 'Đã duyệt' ? 'success' : ($registration['TrangThaiDuyet'] == 'Từ chối' ? 'danger' : 'warning')) . '">
                            ' . $registration['TrangThaiDuyet'] . '
                        </span>
                    </div>
                    <div class="col-md-4">
                        <strong>Thanh toán:</strong> 
                        <span class="badge bg-' . ($registration['TrangThaiThanhToan'] == 'Đã thanh toán' ? 'success' : 'warning') . '">
                            ' . $registration['TrangThaiThanhToan'] . '
                        </span>
                    </div>
            </div>
            </div>
        </div>';
        
        echo json_encode(['success' => true, 'html' => $html]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy chi tiết đăng ký: ' . $e->getMessage()]);
    }
}

function updateRegistrationStatus() {
    try {
        $pdo = getDBConnection();
        
        $registrationId = $_POST['registration_id'] ?? '';
        $status = $_POST['status'] ?? '';
        $note = $_POST['note'] ?? '';
        
        if (empty($registrationId) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Validate status
        $validStatuses = ['Chờ duyệt', 'Đã duyệt', 'Từ chối'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update registration status
            $stmt = $pdo->prepare("
                UPDATE datlichsukien 
                SET TrangThaiDuyet = ?, GhiChu = ?, NgayCapNhat = NOW()
                WHERE ID_DatLich = ?
            ");
            $stmt->execute([$status, $note, $registrationId]);
            
            // If status is 'Đã duyệt', create event in sukien table
            if ($status === 'Đã duyệt') {
                // Check if event already exists in sukien table
                $checkStmt = $pdo->prepare("SELECT ID_SuKien FROM sukien WHERE ID_DatLich = ?");
                $checkStmt->execute([$registrationId]);
                
                if (!$checkStmt->fetch()) {
                    // Get registration details
                    $regStmt = $pdo->prepare("
                        SELECT dl.*, dd.TenDiaDiem, dd.DiaChi, dd.GiaThueGio, dd.GiaThueNgay, dd.LoaiThue,
                               ls.TenLoai, ls.GiaCoBan
                        FROM datlichsukien dl
                        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
                        LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
                        WHERE dl.ID_DatLich = ?
                    ");
                    $regStmt->execute([$registrationId]);
                    $registration = $regStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($registration) {
                        // Generate event code
                        $eventCode = 'EV' . date('Ymd') . str_pad($registrationId, 4, '0', STR_PAD_LEFT);
                        
                        // Calculate total cost
                        $totalCost = 0;
                        
                        // Add event type cost
                        if ($registration['GiaCoBan']) {
                            $totalCost += floatval($registration['GiaCoBan']);
                        }
                        
                        // Add location rental cost
                        if ($registration['GiaThueGio'] || $registration['GiaThueNgay']) {
                            $startDate = new DateTime($registration['NgayBatDau']);
                            $endDate = new DateTime($registration['NgayKetThuc']);
                            $durationHours = $startDate->diff($endDate)->h + ($startDate->diff($endDate)->days * 24);
                            $durationDays = $startDate->diff($endDate)->days + ($durationHours > 0 ? 1 : 0);
                            
                            if ($registration['LoaiThue'] === 'Theo giờ' && $registration['GiaThueGio']) {
                                $totalCost += $durationHours * floatval($registration['GiaThueGio']);
                            } elseif ($registration['LoaiThue'] === 'Theo ngày' && $registration['GiaThueNgay']) {
                                $totalCost += $durationDays * floatval($registration['GiaThueNgay']);
                            } elseif ($registration['LoaiThue'] === 'Cả hai') {
                                $hourlyPrice = $durationHours * floatval($registration['GiaThueGio'] ?? 0);
                                $dailyPrice = $durationDays * floatval($registration['GiaThueNgay'] ?? 0);
                                $totalCost += min($hourlyPrice, $dailyPrice);
                            }
                        }
                        
                        // Add equipment cost
                        $equipStmt = $pdo->prepare("
                            SELECT SUM(ct.DonGia * ct.SoLuong) as total_equipment_cost
                            FROM chitietdatsukien ct
                            WHERE ct.ID_DatLich = ? AND (ct.ID_TB IS NOT NULL OR ct.ID_Combo IS NOT NULL)
                        ");
                        $equipStmt->execute([$registrationId]);
                        $equipmentResult = $equipStmt->fetch(PDO::FETCH_ASSOC);
                        if ($equipmentResult && $equipmentResult['total_equipment_cost']) {
                            $totalCost += floatval($equipmentResult['total_equipment_cost']);
                        }
                        
                        // Insert into sukien table
                        $insertStmt = $pdo->prepare("
                            INSERT INTO sukien (
                                ID_DatLich, MaSuKien, TenSuKien, NgayBatDauThucTe, NgayKetThucThucTe,
                                DiaDiemThucTe, TrangThaiThucTe, TongChiPhiThucTe, GhiChuQuanLy
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $insertStmt->execute([
                            $registrationId,
                            $eventCode,
                            $registration['TenSuKien'],
                            $registration['NgayBatDau'],
                            $registration['NgayKetThuc'],
                            $registration['TenDiaDiem'] . ($registration['DiaChi'] ? ' - ' . $registration['DiaChi'] : ''),
                            'Đang chuẩn bị',
                            $totalCost,
                            'Sự kiện được duyệt tự động từ đăng ký ID: ' . $registrationId . ($note ? ' - Ghi chú: ' . $note : '')
                        ]);
                        
                        $eventId = $pdo->lastInsertId();
                        
                        // Log the creation
                        error_log("Created event ID: $eventId for registration ID: $registrationId with code: $eventCode");
                    }
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Nếu duyệt sự kiện, gửi email thông báo cho khách hàng
            if ($status === 'Đã duyệt') {
                try {
                    sendApprovalEmail($registrationId, $note);
                } catch (Exception $e) {
                    // Ghi log lỗi nhưng không làm thất bại việc duyệt
                    error_log("Error sending approval email: " . $e->getMessage());
                }
            }
            
            $message = 'Cập nhật trạng thái thành công';
            if ($status === 'Đã duyệt') {
                $message .= ' và đã tạo sự kiện để quản lý. Email thông báo đã được gửi đến khách hàng.';
            }
            
            echo json_encode(['success' => true, 'message' => $message]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage()]);
    }
}

/**
 * Gửi email thông báo khi sự kiện được duyệt
 */
function sendApprovalEmail($registrationId, $note = '') {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../../config/config.php';
    
    $pdo = getDBConnection();
    
    // Lấy thông tin đăng ký và khách hàng
    $stmt = $pdo->prepare("
        SELECT 
            dl.ID_DatLich,
            dl.TenSuKien,
            dl.NgayBatDau,
            dl.NgayKetThuc,
            dl.SoNguoiDuKien,
            dl.TongTien,
            dl.GhiChu,
            kh.ID_KhachHang,
            kh.HoTen,
            kh.ID_User,
            u.Email,
            dd.TenDiaDiem,
            dd.DiaChi,
            ls.TenLoai as TenLoaiSK,
            p.TenPhong,
            p.ID_Phong
        FROM datlichsukien dl
        INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        INNER JOIN users u ON kh.ID_User = u.ID_User
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
        LEFT JOIN phong p ON dl.ID_Phong = p.ID_Phong
        WHERE dl.ID_DatLich = ?
    ");
    $stmt->execute([$registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration || empty($registration['Email'])) {
        error_log("Cannot send approval email: Registration not found or email is empty for ID: $registrationId");
        return false;
    }
    
    // Định dạng ngày tháng
    $formattedStartDate = 'Chưa xác định';
    $formattedEndDate = 'Chưa xác định';
    if (!empty($registration['NgayBatDau'])) {
        try {
            $startDate = new DateTime($registration['NgayBatDau']);
            $formattedStartDate = $startDate->format('d/m/Y H:i');
        } catch (Exception $e) {
            error_log("Error parsing start date: " . $e->getMessage());
        }
    }
    if (!empty($registration['NgayKetThuc'])) {
        try {
            $endDate = new DateTime($registration['NgayKetThuc']);
            $formattedEndDate = $endDate->format('d/m/Y H:i');
        } catch (Exception $e) {
            error_log("Error parsing end date: " . $e->getMessage());
        }
    }
    
    // Tạo link xem sự kiện
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'];
    $viewEventUrl = $baseUrl . '/event/my-php-project/event-details.php?id=' . $registrationId;
    
    // Tạo nội dung email
    $customerName = htmlspecialchars($registration['HoTen']);
    $eventName = htmlspecialchars($registration['TenSuKien']);
    $locationName = htmlspecialchars($registration['TenDiaDiem'] ?? 'Chưa xác định');
    $locationAddress = htmlspecialchars($registration['DiaChi'] ?? '');
    $eventType = htmlspecialchars($registration['TenLoaiSK'] ?? 'Chưa xác định');
    $expectedGuests = number_format($registration['SoNguoiDuKien'] ?? 0);
    $totalCost = number_format($registration['TongTien'] ?? 0);
    $roomName = !empty($registration['TenPhong']) ? htmlspecialchars($registration['TenPhong']) : null;
    $approvalNote = !empty($note) ? htmlspecialchars($note) : 'Sự kiện của bạn đã được duyệt thành công.';
    
    $emailSubject = "Sự kiện của bạn đã được duyệt - " . $eventName;
    
    $emailBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea; }
            .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
            .info-row:last-child { border-bottom: none; }
            .info-label { font-weight: bold; color: #666; }
            .info-value { color: #333; }
            .payment-button { display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
            .payment-button:hover { background: #218838; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 Sự kiện của bạn đã được duyệt!</h1>
                <p>Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi</p>
            </div>
            <div class="content">
                <p>Xin chào <strong>' . $customerName . '</strong>,</p>
                <p>' . $approvalNote . '</p>
                
                <div class="info-box">
                    <h3 style="margin-top: 0; color: #667eea;">Thông tin sự kiện</h3>
                    <div class="info-row">
                        <span class="info-label">Tên sự kiện:</span>
                        <span class="info-value">' . $eventName . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Loại sự kiện:</span>
                        <span class="info-value">' . $eventType . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày bắt đầu:</span>
                        <span class="info-value">' . $formattedStartDate . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày kết thúc:</span>
                        <span class="info-value">' . $formattedEndDate . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số khách dự kiến:</span>
                        <span class="info-value">' . $expectedGuests . ' người</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Địa điểm:</span>
                        <span class="info-value">' . $locationName . ($locationAddress ? ' - ' . $locationAddress : '') . '</span>
                    </div>';
    
    if ($roomName) {
        $emailBody .= '
                    <div class="info-row">
                        <span class="info-label">Phòng:</span>
                        <span class="info-value">' . $roomName . '</span>
                    </div>';
    }
    
    $emailBody .= '
                    <div class="info-row">
                        <span class="info-label">Tổng chi phí:</span>
                        <span class="info-value" style="color: #28a745; font-weight: bold; font-size: 18px;">' . $totalCost . ' VNĐ</span>
                    </div>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <p style="font-size: 16px; font-weight: bold; color: #333;">Vui lòng xem chi tiết sự kiện của bạn</p>
                    <a href="' . $viewEventUrl . '" class="payment-button">👁️ Xem ngay</a>
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ffc107;">
                    <p style="margin: 0; color: #856404;">
                        <strong>⚠️ Lưu ý:</strong> Sau khi xem chi tiết, bạn có thể thanh toán để hoàn tất đăng ký sự kiện. Vui lòng thanh toán trong thời gian quy định để đảm bảo sự kiện được tổ chức đúng như kế hoạch.
                    </p>
                </div>
                
                <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.</p>
                <p>Trân trọng,<br><strong>Đội ngũ quản lý sự kiện</strong></p>
            </div>
            <div class="footer">
                <p>Email này được gửi tự động, vui lòng không trả lời email này.</p>
            </div>
        </div>
    </body>
    </html>';
    
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Người gửi và người nhận
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($registration['Email'], $customerName);
        
        // Nội dung
        $mail->isHTML(true);
        $mail->Subject = $emailSubject;
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($emailBody);
        
        // Gửi email
        $sendResult = $mail->send();
        
        if ($sendResult) {
            error_log("Approval email sent successfully to: " . $registration['Email'] . " for registration ID: " . $registrationId);
            return true;
        } else {
            error_log("Approval email send failed to: " . $registration['Email'] . " for registration ID: " . $registrationId);
            error_log("PHPMailer ErrorInfo: " . ($mail->ErrorInfo ?? 'No error info'));
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Exception sending approval email: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}
?>