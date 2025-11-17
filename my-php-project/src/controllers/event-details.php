<?php
// Event details API
require_once __DIR__ . '/../../config/database.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập']);
    exit;
}

$user = $_SESSION['user'];

try {
    $pdo = getDBConnection();
    
    $action = $_GET['action'] ?? '';
    $eventId = $_GET['event_id'] ?? '';
    
    if ($action === 'get_event_details') {
        // Get event details
        $stmt = $pdo->prepare("
            SELECT dl.*, d.TenDiaDiem, d.DiaChi, d.SucChua, d.GiaThueGio, d.GiaThueNgay, d.LoaiThue, d.LoaiDiaDiem, d.HinhAnh as DiaDiemHinhAnh,
                   ls.TenLoai, ls.GiaCoBan, ls.MoTa as LoaiMoTa,
                   k.HoTen, k.SoDienThoai,
                   p.ID_Phong, p.TenPhong as TenPhong, p.GiaThueGio as PhongGiaThueGio, p.GiaThueNgay as PhongGiaThueNgay, p.LoaiThue as PhongLoaiThue,
                   COALESCE(equipment_total.TongGiaThietBi, 0) as TongGiaThietBi,
                   s.TrangThaiThucTe as TrangThaiSuKien
            FROM datlichsukien dl
            INNER JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
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
            WHERE dl.ID_DatLich = ? AND k.ID_User = ?
        ");
        $stmt->execute([$eventId, $user['ID_User']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            throw new Exception('Sự kiện không tồn tại hoặc không thuộc về bạn');
        }
        
        // Get event reviews
        $stmt = $pdo->prepare("
            SELECT dg.*, k.HoTen as TenKhachHang
            FROM danhgia dg
            LEFT JOIN datlichsukien dl ON dg.ID_SuKien = dl.ID_DatLich
            LEFT JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
            WHERE dg.ID_SuKien = ?
            ORDER BY dg.ThoiGianDanhGia DESC
        ");
        $stmt->execute([$eventId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get event equipment details
        $stmt = $pdo->prepare("
            SELECT ctds.*, tb.TenThietBi, tb.HinhAnh as ThietBiHinhAnh, tb.LoaiThietBi, tb.HangSX, tb.DonViTinh,
                   cb.TenCombo
            FROM chitietdatsukien ctds
            LEFT JOIN thietbi tb ON ctds.ID_TB = tb.ID_TB
            LEFT JOIN combo cb ON ctds.ID_Combo = cb.ID_Combo
            WHERE ctds.ID_DatLich = ?
        ");
        $stmt->execute([$eventId]);
        $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'event' => $event,
            'reviews' => $reviews,
            'equipment' => $equipment
        ]);
        
    } else {
        throw new Exception('Action không hợp lệ');
    }
    
} catch (Exception $e) {
    error_log("Event Details API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
