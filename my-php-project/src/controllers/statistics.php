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
    case 'get_dashboard_stats':
        getDashboardStats();
        break;
    case 'get_event_stats':
        getEventStats();
        break;
    case 'get_revenue_stats':
        getRevenueStats();
        break;
    case 'get_staff_stats':
        getStaffStats();
        break;
    case 'get_customer_stats':
        getCustomerStats();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

function getDashboardStats() {
    try {
        $pdo = getDBConnection();
        
        // Total events
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_events FROM datlichsukien");
        $stmt->execute();
        $totalEvents = $stmt->fetch(PDO::FETCH_ASSOC)['total_events'];
        
        // Approved events
        $stmt = $pdo->prepare("SELECT COUNT(*) as approved_events FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
        $stmt->execute();
        $approvedEvents = $stmt->fetch(PDO::FETCH_ASSOC)['approved_events'];
        
        // Pending events
        $stmt = $pdo->prepare("SELECT COUNT(*) as pending_events FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
        $stmt->execute();
        $pendingEvents = $stmt->fetch(PDO::FETCH_ASSOC)['pending_events'];
        
        // Total customers
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_customers FROM khachhanginfo");
        $stmt->execute();
        $totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];
        
        // Total staff
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_staff FROM nhanvieninfo");
        $stmt->execute();
        $totalStaff = $stmt->fetch(PDO::FETCH_ASSOC)['total_staff'];
        
        // Total revenue
        $stmt = $pdo->prepare("SELECT SUM(NganSach) as total_revenue FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
        $stmt->execute();
        $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
        
        $stats = [
            'total_events' => (int)$totalEvents,
            'approved_events' => (int)$approvedEvents,
            'pending_events' => (int)$pendingEvents,
            'total_customers' => (int)$totalCustomers,
            'total_staff' => (int)$totalStaff,
            'total_revenue' => (float)$totalRevenue,
            'approval_rate' => $totalEvents > 0 ? round(($approvedEvents / $totalEvents) * 100, 2) : 0
        ];
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê tổng quan: ' . $e->getMessage()]);
    }
}

function getEventStats() {
    try {
        $pdo = getDBConnection();
        
        // Events by month
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                COUNT(*) as event_count,
                SUM(NganSach) as revenue
            FROM datlichsukien 
            WHERE NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute();
        $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Events by type
        $stmt = $pdo->prepare("
            SELECT 
                ls.TenLoai as event_type,
                COUNT(*) as event_count,
                SUM(dl.NganSach) as revenue
            FROM datlichsukien dl
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            WHERE dl.NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY ls.TenLoai
            ORDER BY event_count DESC
        ");
        $stmt->execute();
        $typeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Events by status
        $stmt = $pdo->prepare("
            SELECT 
                TrangThaiDuyet as status,
                COUNT(*) as event_count
            FROM datlichsukien
            GROUP BY TrangThaiDuyet
        ");
        $stmt->execute();
        $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'monthly_stats' => $monthlyStats,
            'type_stats' => $typeStats,
            'status_stats' => $statusStats
        ];
        
        echo json_encode(['success' => true, 'event_stats' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê sự kiện: ' . $e->getMessage()]);
    }
}

function getRevenueStats() {
    try {
        $pdo = getDBConnection();
        
        // Revenue by month
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                SUM(NganSach) as revenue,
                COUNT(*) as event_count
            FROM datlichsukien 
            WHERE TrangThaiDuyet = 'Đã duyệt' 
            AND NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute();
        $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Revenue by location
        $stmt = $pdo->prepare("
            SELECT 
                dd.TenDiaDiem as location,
                SUM(dl.NganSach) as revenue,
                COUNT(*) as event_count
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE dl.TrangThaiDuyet = 'Đã duyệt'
            GROUP BY dd.TenDiaDiem
            ORDER BY revenue DESC
        ");
        $stmt->execute();
        $locationRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Total revenue
        $stmt = $pdo->prepare("SELECT SUM(NganSach) as total_revenue FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
        $stmt->execute();
        $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
        
        $stats = [
            'monthly_revenue' => $monthlyRevenue,
            'location_revenue' => $locationRevenue,
            'total_revenue' => (float)$totalRevenue
        ];
        
        echo json_encode(['success' => true, 'revenue_stats' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê doanh thu: ' . $e->getMessage()]);
    }
}

function getStaffStats() {
    try {
$pdo = getDBConnection();

        // Staff performance
        $stmt = $pdo->prepare("
            SELECT 
                nv.HoTen as staff_name,
                nv.ChucVu as position,
                COUNT(llv.ID_LichLamViec) as total_assignments,
                SUM(CASE WHEN llv.TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_assignments,
                AVG(CASE WHEN llv.TrangThai = 'Hoàn thành' THEN TIMESTAMPDIFF(HOUR, llv.NgayTao, llv.NgayCapNhat) END) as avg_completion_hours
            FROM nhanvieninfo nv
            LEFT JOIN lichlamviec llv ON nv.ID_NhanVien = llv.ID_NhanVien
            WHERE nv.TrangThai = 'Hoạt động'
            GROUP BY nv.ID_NhanVien
            ORDER BY completed_assignments DESC
        ");
        $stmt->execute();
        $staffPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Staff by position
        $stmt = $pdo->prepare("
            SELECT 
                ChucVu as position,
                COUNT(*) as staff_count
            FROM nhanvieninfo
            WHERE TrangThai = 'Hoạt động'
            GROUP BY ChucVu
            ORDER BY staff_count DESC
        ");
        $stmt->execute();
        $positionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'staff_performance' => $staffPerformance,
            'position_stats' => $positionStats
        ];
        
        echo json_encode(['success' => true, 'staff_stats' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê nhân viên: ' . $e->getMessage()]);
    }
}

function getCustomerStats() {
    try {
        $pdo = getDBConnection();
        
        // Customer registration by month
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(kh.NgayTao, '%Y-%m') as month,
                COUNT(*) as customer_count
            FROM khachhanginfo kh
            WHERE kh.NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(kh.NgayTao, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute();
        $monthlyCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top customers by events
        $stmt = $pdo->prepare("
            SELECT 
                kh.HoTen as customer_name,
                kh.SoDienThoai,
                COUNT(dl.ID_DatLich) as event_count,
                SUM(dl.NganSach) as total_spent
            FROM khachhanginfo kh
            LEFT JOIN datlichsukien dl ON kh.ID_KhachHang = dl.ID_KhachHang
            GROUP BY kh.ID_KhachHang
            HAVING event_count > 0
            ORDER BY event_count DESC, total_spent DESC
            LIMIT 10
        ");
        $stmt->execute();
        $topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Customer activity
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN u.LastLogin >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_customers
            FROM khachhanginfo kh
            LEFT JOIN users u ON kh.ID_User = u.ID_User
        ");
        $stmt->execute();
        $customerActivity = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats = [
            'monthly_customers' => $monthlyCustomers,
            'top_customers' => $topCustomers,
            'customer_activity' => $customerActivity
        ];
        
        echo json_encode(['success' => true, 'customer_stats' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê khách hàng: ' . $e->getMessage()]);
    }
}
?>