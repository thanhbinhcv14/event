<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2, 3])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
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
    case 'update_event_status':
        updateEventStatus();
        break;
    case 'assign_staff':
        assignStaff();
        break;
    case 'get_event_staff':
        getEventStaff();
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
                dl.TrangThaiDuyet,
                dl.TrangThaiThanhToan,
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
                kh.DiaChi as KhachHangDiaChi
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

function updateEventStatus() {
    try {
        $pdo = getDBConnection();
        
        $eventId = $_POST['event_id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($eventId) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE datlichsukien SET TrangThaiDuyet = ? WHERE ID_DatLich = ?");
        $stmt->execute([$status, $eventId]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage()]);
    }
}

function assignStaff() {
    try {
        $pdo = getDBConnection();
        
        $eventId = $_POST['event_id'] ?? '';
        $staffId = $_POST['staff_id'] ?? '';
        $role = $_POST['role'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($eventId) || empty($staffId) || empty($role)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO event_staff_assignments (ID_DatLich, ID_NhanVien, VaiTro, GhiChu, NgayTao)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$eventId, $staffId, $role, $notes]);
        
        echo json_encode(['success' => true, 'message' => 'Phân công nhân viên thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi phân công nhân viên: ' . $e->getMessage()]);
    }
}

function getEventStaff() {
    try {
        $pdo = getDBConnection();
        
        $eventId = $_GET['event_id'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện']);
            return;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                esa.ID_Assignment,
                esa.VaiTro,
                esa.GhiChu,
                esa.NgayTao,
                nv.HoTen as TenNhanVien,
                nv.SoDienThoai,
                nv.ChucVu
            FROM event_staff_assignments esa
            LEFT JOIN nhanvieninfo nv ON esa.ID_NhanVien = nv.ID_NhanVien
            WHERE esa.ID_DatLich = ?
            ORDER BY esa.NgayTao DESC
        ");
        $stmt->execute([$eventId]);
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'staff' => $staff]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage()]);
    }
}
?>