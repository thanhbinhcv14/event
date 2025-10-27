<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Debug session
error_log("Admin Events Controller - Session data: " . print_r($_SESSION, true));
error_log("Admin Events Controller - Action: " . ($_GET['action'] ?? $_POST['action'] ?? 'none'));

// Check if user is logged in and has appropriate role
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
        }
        
        $response = ['success' => true, 'registrations' => $registrations];
        error_log("Sending response: " . json_encode($response));
        echo json_encode($response);
                
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
        $pdo = getDBConnection();
        
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đăng ký']);
            return;
        }
        
        // Get registration details
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
                ls.TenLoai,
                ls.MoTa as LoaiSKMoTa,
                kh.HoTen,
                kh.SoDienThoai,
                kh.DiaChi as KhachHangDiaChi
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            WHERE dl.ID_DatLich = ?
        ");
        $stmt->execute([$id]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);
        
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
            $stmt->execute([$id]);
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
            $stmt->execute([$id]);
            $comboEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combine equipment
            $equipment = array_merge($individualEquipment, $comboEquipment);
            
            error_log("Found " . count($equipment) . " equipment items from chitietdatsukien");
            error_log("Individual equipment: " . count($individualEquipment));
            error_log("Combo equipment: " . count($comboEquipment));
    
} catch (Exception $e) {
            error_log("Error getting equipment: " . $e->getMessage());
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
                    <tr><td><strong>Địa chỉ:</strong></td><td>' . htmlspecialchars($registration['DiaChi']) . '</td></tr>
                    <tr><td><strong>Sức chứa:</strong></td><td>' . number_format($registration['SucChua'] ?: 0) . ' người</td></tr>
                    <tr><td><strong>Giá thuê/giờ:</strong></td><td>' . number_format($registration['GiaThueGio'] ?: 0) . ' VNĐ</td></tr>
                    <tr><td><strong>Giá thuê/ngày:</strong></td><td>' . number_format($registration['GiaThueNgay'] ?: 0) . ' VNĐ</td></tr>
                    <tr><td><strong>Loại thuê:</strong></td><td>' . htmlspecialchars($registration['LoaiThue'] ?: 'Chưa xác định') . '</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5><i class="fas fa-user text-info"></i> Khách hàng</h5>
                <table class="table table-sm">
                    <tr><td><strong>Họ tên:</strong></td><td>' . htmlspecialchars($registration['HoTen']) . '</td></tr>
                    <tr><td><strong>Số điện thoại:</strong></td><td>' . htmlspecialchars($registration['SoDienThoai']) . '</td></tr>
                    <tr><td><strong>Địa chỉ:</strong></td><td>' . htmlspecialchars($registration['KhachHangDiaChi'] ?: 'Không có') . '</td></tr>
                </table>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <h5><i class="fas fa-money-bill-wave text-success"></i> Thông tin giá tiền</h5>
                <table class="table table-sm">
                    <tr><td><strong>Tổng tiền:</strong></td><td><span class="badge bg-success fs-6">' . number_format($registration['TongTien'] ?: 0) . ' VNĐ</span></td></tr>
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
                if (!empty($item['TenCombo'])) {
                    // Combo equipment
                    $html .= '<tr>
                        <td><strong><i class="fas fa-box text-primary"></i> ' . htmlspecialchars($item['TenCombo']) . '</strong></td>
                        <td><span class="badge bg-info">Combo</span></td>
                        <td>N/A</td>
                        <td><span class="badge bg-primary">' . ($item['SoLuong'] ?: '1') . '</span></td>
                        <td>combo</td>
                        <td><strong class="text-success">' . number_format($item['DonGia'] ?: $item['GiaCombo'] ?: 0) . ' VNĐ</strong></td>
                        <td>' . htmlspecialchars($item['GhiChu'] ?: 'Combo thiết bị') . '</td>
                    </tr>';
                } else {
                    // Individual equipment
                    $html .= '<tr>
                        <td><strong><i class="fas fa-cog text-primary"></i> ' . htmlspecialchars($item['TenThietBi'] ?: 'N/A') . '</strong></td>
                        <td>' . htmlspecialchars($item['LoaiThietBi'] ?: 'N/A') . '</td>
                        <td>' . htmlspecialchars($item['HangSX'] ?: 'N/A') . '</td>
                        <td><span class="badge bg-primary">' . ($item['SoLuong'] ?: '1') . '</span></td>
                        <td>' . htmlspecialchars($item['DonViTinh'] ?: 'cái') . '</td>
                        <td><strong class="text-success">' . number_format($item['DonGia'] ?: $item['GiaThue'] ?: 0) . ' VNĐ</strong></td>
                        <td>' . htmlspecialchars($item['GhiChu'] ?: 'Thiết bị riêng lẻ') . '</td>
                    </tr>';
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
            
            $message = 'Cập nhật trạng thái thành công';
            if ($status === 'Đã duyệt') {
                $message .= ' và đã tạo sự kiện để quản lý';
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
?>