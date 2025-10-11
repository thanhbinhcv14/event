<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getConnection();
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_featured_events':
            getFeaturedEvents($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
}

function getFeaturedEvents($pdo) {
    try {
        // Lấy các sự kiện đã được duyệt và sắp diễn ra
        $sql = "
            SELECT 
                d.ID_DatLich,
                d.TenSuKien,
                d.MoTa,
                d.NgayBatDau,
                d.NgayKetThuc,
                d.SoNguoiDuKien,
                d.NganSach,
                d.TrangThaiDuyet,
                dd.TenDiaDiem,
                dd.DiaChi,
                dd.SucChua,
                dd.GiaThue,
                dd.HinhAnh,
                ls.TenLoaiSK,
                ls.MoTa as MoTaLoaiSK
            FROM datlichsukien d
            LEFT JOIN diadiem dd ON d.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON d.ID_LoaiSK = ls.ID_LoaiSK
            WHERE d.TrangThaiDuyet = 'Đã duyệt'
            AND d.NgayBatDau > NOW()
            ORDER BY d.NgayBatDau ASC
            LIMIT 6
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dữ liệu
        foreach ($events as &$event) {
            $event['NgayBatDau'] = date('d/m/Y H:i', strtotime($event['NgayBatDau']));
            $event['NgayKetThuc'] = date('d/m/Y H:i', strtotime($event['NgayKetThuc']));
            $event['NganSach'] = number_format($event['NganSach'], 0, ',', '.') . ' VNĐ';
            $event['GiaThue'] = number_format($event['GiaThue'], 0, ',', '.') . ' VNĐ';
            
            // Tạo URL hình ảnh
            if ($event['HinhAnh']) {
                $event['HinhAnhURL'] = '../img/diadiem/' . $event['HinhAnh'];
            } else {
                $event['HinhAnhURL'] = '../img/logo/logo.jpg'; // Hình mặc định
            }
        }
        
        echo json_encode([
            'success' => true,
            'events' => $events
        ]);
        
    } catch (Exception $e) {
        error_log('Error in getFeaturedEvents: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch events'
        ]);
    }
}
?>