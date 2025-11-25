<?php
// Controller cho Dashboard - Helper functions để lấy dữ liệu cho view
// File này có thể được require_once từ view để gọi helper functions

// Kiểm tra session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra database connection
require_once __DIR__ . '/../../config/database.php';

/**
 * Lấy dữ liệu dashboard cho Role 1 (Admin)
 * @param PDO $pdo Database connection
 * @return array Dashboard data
 */
function getDashboardDataForAdmin($pdo) {
    $data = [];
    
    // Total event registrations
    $stmt = $pdo->query("SELECT COUNT(*) AS total_registrations FROM datlichsukien");
    $data['total_registrations'] = $stmt->fetchColumn();
    
    // Pending registrations
    $stmt = $pdo->query("SELECT COUNT(*) AS pending_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
    $data['pending_registrations'] = $stmt->fetchColumn();
    
    // Approved registrations
    $stmt = $pdo->query("SELECT COUNT(*) AS approved_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
    $data['approved_registrations'] = $stmt->fetchColumn();
    
    // Rejected registrations
    $stmt = $pdo->query("SELECT COUNT(*) AS rejected_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Từ chối'");
    $data['rejected_registrations'] = $stmt->fetchColumn();
    
    // Total locations
    $stmt = $pdo->query("SELECT COUNT(*) AS total_locations FROM diadiem");
    $data['total_locations'] = $stmt->fetchColumn();
    
    // Total equipment
    $stmt = $pdo->query("SELECT COUNT(*) AS total_equipment FROM thietbi");
    $data['total_equipment'] = $stmt->fetchColumn();
    
    // Total staff
    $stmt = $pdo->query("SELECT COUNT(*) AS total_staff FROM nhanvieninfo");
    $data['total_staff'] = $stmt->fetchColumn();
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) AS total_customers FROM users WHERE ID_Role = 5");
    $data['total_customers'] = $stmt->fetchColumn();
    
    // Recent registrations
    $stmt = $pdo->query("
        SELECT dl.*, kh.HoTen AS TenKhachHang, dd.TenDiaDiem, lsk.TenLoai
        FROM datlichsukien dl
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        JOIN loaisukien lsk ON dl.ID_LoaiSK = lsk.ID_LoaiSK
        ORDER BY dl.NgayTao DESC
        LIMIT 5
    ");
    $data['recent_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $data;
}

/**
 * Lấy dữ liệu dashboard cho Role 2 (Quản lý tổ chức)
 * @param PDO $pdo Database connection
 * @return array Dashboard data
 */
function getDashboardDataForManager($pdo) {
    $data = [];
    
    // Pending registrations (cần duyệt)
    $stmt = $pdo->query("SELECT COUNT(*) AS pending_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
    $data['pending_registrations'] = $stmt->fetchColumn();
    
    // Approved registrations
    $stmt = $pdo->query("SELECT COUNT(*) AS approved_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
    $data['approved_registrations'] = $stmt->fetchColumn();
    
    // Total locations (quản lý)
    $stmt = $pdo->query("SELECT COUNT(*) AS total_locations FROM diadiem");
    $data['total_locations'] = $stmt->fetchColumn();
    
    // Active locations
    $stmt = $pdo->query("SELECT COUNT(*) AS active_locations FROM diadiem WHERE TrangThaiHoatDong = 'Hoạt động'");
    $data['active_locations'] = $stmt->fetchColumn();
    
    // Total rooms
    $stmt = $pdo->query("SELECT COUNT(*) AS total_rooms FROM phong WHERE TrangThai = 'Sẵn sàng'");
    $data['total_rooms'] = $stmt->fetchColumn();
    
    // Total staff
    $stmt = $pdo->query("SELECT COUNT(*) AS total_staff FROM nhanvieninfo");
    $data['total_staff'] = $stmt->fetchColumn();
    
    // Total equipment
    $stmt = $pdo->query("SELECT COUNT(*) AS total_equipment FROM thietbi WHERE TrangThai = 'Sẵn sàng'");
    $data['total_equipment'] = $stmt->fetchColumn();
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) AS total_customers FROM users WHERE ID_Role = 5");
    $data['total_customers'] = $stmt->fetchColumn();
    
    // Pending payments
    $stmt = $pdo->query("SELECT COUNT(*) AS pending_payments FROM thanhtoan WHERE TrangThai = 'Chờ thanh toán'");
    $data['pending_payments'] = $stmt->fetchColumn();
    
    // Recent pending registrations
    $stmt = $pdo->query("
        SELECT dl.*, kh.HoTen AS TenKhachHang, dd.TenDiaDiem, lsk.TenLoai
        FROM datlichsukien dl
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        JOIN loaisukien lsk ON dl.ID_LoaiSK = lsk.ID_LoaiSK
        WHERE dl.TrangThaiDuyet = 'Chờ duyệt'
        ORDER BY dl.NgayTao DESC
        LIMIT 5
    ");
    $data['recent_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $data;
}

/**
 * Lấy dữ liệu dashboard cho Role 3 (Quản lý sự kiện)
 * @param PDO $pdo Database connection
 * @return array Dashboard data
 */
function getDashboardDataForEventManager($pdo) {
    $data = [];
    
    // Total registrations (đã đăng ký)
    $stmt = $pdo->query("SELECT COUNT(*) AS total_registrations FROM datlichsukien");
    $data['total_registrations'] = $stmt->fetchColumn();
    
    // Pending registrations (chờ duyệt)
    $stmt = $pdo->query("SELECT COUNT(*) AS pending_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
    $data['pending_registrations'] = $stmt->fetchColumn();
    
    // Approved registrations
    $stmt = $pdo->query("SELECT COUNT(*) AS approved_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
    $data['approved_registrations'] = $stmt->fetchColumn();
    
    // Upcoming events (sắp diễn ra)
    $stmt = $pdo->query("
        SELECT COUNT(*) AS upcoming_events 
        FROM datlichsukien 
        WHERE TrangThaiDuyet = 'Đã duyệt' 
        AND NgayBatDau >= CURDATE()
    ");
    $data['upcoming_events'] = $stmt->fetchColumn();
    
    // Today's events
    $stmt = $pdo->query("
        SELECT COUNT(*) AS today_events 
        FROM datlichsukien 
        WHERE TrangThaiDuyet = 'Đã duyệt' 
        AND DATE(NgayBatDau) = CURDATE()
    ");
    $data['today_events'] = $stmt->fetchColumn();
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) AS total_customers FROM users WHERE ID_Role = 5");
    $data['total_customers'] = $stmt->fetchColumn();
    
    // Recent registrations
    $stmt = $pdo->query("
        SELECT dl.*, kh.HoTen AS TenKhachHang, dd.TenDiaDiem, lsk.TenLoai
        FROM datlichsukien dl
        JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        JOIN loaisukien lsk ON dl.ID_LoaiSK = lsk.ID_LoaiSK
        ORDER BY dl.NgayTao DESC
        LIMIT 5
    ");
    $data['recent_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $data;
}

/**
 * Lấy dữ liệu dashboard cho Role 4 (Nhân viên)
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return array Dashboard data
 */
function getDashboardDataForStaff($pdo, $userId) {
    $data = [];
    
    // Get staff ID
    $stmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ? LIMIT 1");
    $stmt->execute([$userId]);
    $staffInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $staffId = $staffInfo ? $staffInfo['ID_NhanVien'] : null;
    
    if (!$staffId) {
        // No staff info found
        return [
            'total_assignments' => 0,
            'pending_tasks' => 0,
            'completed_tasks' => 0,
            'today_tasks' => 0,
            'upcoming_tasks' => 0,
            'recent_assignments' => []
        ];
    }
    
    // Total assignments (tổng nhiệm vụ)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total_assignments 
        FROM lichlamviec 
        WHERE ID_NhanVien = ?
    ");
    $stmt->execute([$staffId]);
    $data['total_assignments'] = $stmt->fetchColumn();
    
    // Pending tasks (nhiệm vụ chưa hoàn thành)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS pending_tasks 
        FROM lichlamviec 
        WHERE ID_NhanVien = ? 
        AND TrangThai IN ('Chưa bắt đầu', 'Đang thực hiện')
    ");
    $stmt->execute([$staffId]);
    $data['pending_tasks'] = $stmt->fetchColumn();
    
    // Completed tasks (nhiệm vụ đã hoàn thành)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS completed_tasks 
        FROM lichlamviec 
        WHERE ID_NhanVien = ? 
        AND TrangThai = 'Hoàn thành'
    ");
    $stmt->execute([$staffId]);
    $data['completed_tasks'] = $stmt->fetchColumn();
    
    // Today's tasks (nhiệm vụ hôm nay)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS today_tasks 
        FROM lichlamviec 
        WHERE ID_NhanVien = ? 
        AND DATE(NgayBatDau) = CURDATE()
        AND TrangThai != 'Hoàn thành'
    ");
    $stmt->execute([$staffId]);
    $data['today_tasks'] = $stmt->fetchColumn();
    
    // Upcoming tasks (nhiệm vụ sắp tới - 7 ngày)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS upcoming_tasks 
        FROM lichlamviec 
        WHERE ID_NhanVien = ? 
        AND NgayBatDau BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND TrangThai != 'Hoàn thành'
    ");
    $stmt->execute([$staffId]);
    $data['upcoming_tasks'] = $stmt->fetchColumn();
    
    // Recent assignments
    $stmt = $pdo->prepare("
        SELECT llv.*, dl.TenSuKien, dd.TenDiaDiem
        FROM lichlamviec llv
        LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        WHERE llv.ID_NhanVien = ?
        ORDER BY llv.NgayBatDau DESC
        LIMIT 5
    ");
    $stmt->execute([$staffId]);
    $data['recent_assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $data;
}

// Chỉ thực thi API actions nếu có action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action) {
    // API mode - xử lý các action
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'get_dashboard_data':
            $userRole = $_SESSION['user']['ID_Role'] ?? null;
            $userId = $_SESSION['user']['ID_User'] ?? null;
            
            if (!$userRole) {
                echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
                exit;
            }
            
            try {
                $pdo = getDBConnection();
                
                switch ($userRole) {
                    case 1:
                        $data = getDashboardDataForAdmin($pdo);
                        break;
                    case 2:
                        $data = getDashboardDataForManager($pdo);
                        break;
                    case 3:
                        $data = getDashboardDataForEventManager($pdo);
                        break;
                    case 4:
                        $data = getDashboardDataForStaff($pdo, $userId);
                        break;
                    default:
                        $data = [];
                }
                
                echo json_encode(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
            exit;
    }
}

