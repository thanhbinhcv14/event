<?php
// Controller cho Reports - Helper functions để lấy dữ liệu thống kê cho reports.php
// File này có thể được require_once từ view để gọi helper functions

// Kiểm tra session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra database connection
require_once __DIR__ . '/../../config/database.php';

/**
 * Lấy tất cả dữ liệu thống kê cho Reports (Role 1 - QTV)
 * @param PDO $pdo Database connection
 * @return array Statistics data
 */
function getReportsDataForAdmin($pdo) {
    $statsData = [];
    
    try {
        // Kiểm tra kết nối database
        if (!$pdo) {
            throw new Exception("Không thể kết nối database");
        }
        
        // ========== THỐNG KÊ KHÁCH HÀNG ==========
        // Tổng số khách hàng
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5");
        $statsData['total_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng hoạt động
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5 AND TrangThai = 'Hoạt động'");
        $statsData['active_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng chưa xác minh
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5 AND TrangThai = 'Chưa xác minh'");
        $statsData['pending_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng bị khóa
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5 AND TrangThai = 'Bị khóa'");
        $statsData['blocked_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng đăng ký theo tháng (12 tháng gần nhất)
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                COUNT(*) as count
            FROM users 
            WHERE ID_Role = 5 AND NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['customers_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ NHÂN VIÊN ==========
        // Tổng số nhân viên
        $stmt = $pdo->query("SELECT COUNT(*) FROM nhanvieninfo");
        $statsData['total_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên theo role
        $stmt = $pdo->query("
            SELECT 
                r.RoleName as TenRole,
                COUNT(*) as count
            FROM users u
            INNER JOIN phanquyen r ON u.ID_Role = r.ID_Role
            WHERE u.ID_Role IN (2, 3, 4)
            GROUP BY u.ID_Role, r.RoleName
            ORDER BY u.ID_Role
        ");
        $statsData['staff_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nhân viên hoạt động (dựa vào users.TrangThai)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE u.TrangThai = 'Hoạt động'
        ");
        $statsData['active_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên không hoạt động (dựa vào users.TrangThai)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE u.TrangThai != 'Hoạt động'
        ");
        $statsData['inactive_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên tuyển dụng theo tháng (12 tháng gần nhất)
        // Lấy từ users vì nhanvieninfo có thể không có NgayTao
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(u.NgayTao, '%Y-%m') as month,
                COUNT(*) as count
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE u.NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(u.NgayTao, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['staff_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ BÀI VIẾT ==========
        // Tổng số bài viết
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts");
        $statsData['total_posts'] = (int)$stmt->fetchColumn();
        
        // Bài viết đã xuất bản
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'");
        $statsData['published_posts'] = (int)$stmt->fetchColumn();
        
        // Bài viết bản nháp
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'");
        $statsData['draft_posts'] = (int)$stmt->fetchColumn();
        
        // Bài viết đã lưu trữ
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'archived'");
        $statsData['archived_posts'] = (int)$stmt->fetchColumn();
        
        // Tổng lượt xem
        $stmt = $pdo->query("SELECT SUM(views) FROM blog_posts");
        $result = $stmt->fetchColumn();
        $statsData['total_views'] = $result ? (int)$result : 0;
        
        // Bài viết theo loại sự kiện
        $stmt = $pdo->query("
            SELECT 
                ls.TenLoai,
                COUNT(bp.id) as count
            FROM blog_posts bp
            LEFT JOIN loaisukien ls ON bp.event_type_id = ls.ID_LoaiSK
            GROUP BY bp.event_type_id, ls.TenLoai
            ORDER BY count DESC
        ");
        $statsData['posts_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Bài viết đăng theo tháng (12 tháng gần nhất)
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM blog_posts 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['posts_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ COMMENT ==========
        // Tổng số comment
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments");
        $statsData['total_comments'] = (int)$stmt->fetchColumn();
        
        // Comment đã duyệt
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE status = 'approved'");
        $statsData['approved_comments'] = (int)$stmt->fetchColumn();
        
        // Comment chờ duyệt
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE status = 'pending'");
        $statsData['pending_comments'] = (int)$stmt->fetchColumn();
        
        // Comment bị từ chối
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE status = 'rejected'");
        $statsData['rejected_comments'] = (int)$stmt->fetchColumn();
        
        // Comment theo bài viết (top 10 bài viết có nhiều comment nhất)
        $stmt = $pdo->query("
            SELECT 
                bp.title,
                COUNT(bc.id) as comment_count
            FROM blog_posts bp
            LEFT JOIN blog_comments bc ON bp.id = bc.post_id
            GROUP BY bp.id, bp.title
            ORDER BY comment_count DESC
            LIMIT 10
        ");
        $statsData['top_posts_comments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Comment theo tháng (12 tháng gần nhất)
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM blog_comments 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['comments_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ MÃ GIẢM GIÁ ==========
        // Tổng số mã giảm giá
        $stmt = $pdo->query("SELECT COUNT(*) FROM magiamgia");
        $statsData['total_discounts'] = (int)$stmt->fetchColumn();
        
        // Mã giảm giá đang hoạt động
        $stmt = $pdo->query("
            SELECT COUNT(*) FROM magiamgia 
            WHERE NgayBatDau <= NOW() AND NgayKetThuc >= NOW()
        ");
        $statsData['active_discounts'] = (int)$stmt->fetchColumn();
        
        // Mã giảm giá đã hết hạn
        $stmt = $pdo->query("
            SELECT COUNT(*) FROM magiamgia 
            WHERE NgayKetThuc < NOW()
        ");
        $statsData['expired_discounts'] = (int)$stmt->fetchColumn();
        
        // Mã giảm giá chưa bắt đầu
        $stmt = $pdo->query("
            SELECT COUNT(*) FROM magiamgia 
            WHERE NgayBatDau > NOW()
        ");
        $statsData['upcoming_discounts'] = (int)$stmt->fetchColumn();
        
        // Mã giảm giá theo loại
        $stmt = $pdo->query("
            SELECT 
                LoaiGiamGia,
                COUNT(*) as count
            FROM magiamgia
            GROUP BY LoaiGiamGia
            ORDER BY count DESC
        ");
        $statsData['discounts_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Tổng số lần sử dụng mã giảm giá
        $stmt = $pdo->query("SELECT SUM(SoLanDaSuDung) FROM magiamgia");
        $result = $stmt->fetchColumn();
        $statsData['total_discount_uses'] = $result ? (int)$result : 0;
        
    } catch (PDOException $e) {
        error_log("Reports data PDO error (Admin): " . $e->getMessage());
        error_log("PDO Error Info: " . print_r($pdo->errorInfo(), true));
        $statsData = getDefaultReportsData();
    } catch (Exception $e) {
        error_log("Reports data error (Admin): " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        $statsData = getDefaultReportsData();
    }
    
    return $statsData;
}

/**
 * Lấy tất cả dữ liệu thống kê cho Reports (Role 2 - QLTC)
 * @param PDO $pdo Database connection
 * @return array Statistics data
 */
function getReportsDataForManager($pdo) {
    $statsData = [];
    
    try {
        // Kiểm tra kết nối database
        if (!$pdo) {
            throw new Exception("Không thể kết nối database");
        }
        
        // ========== THỐNG KÊ ĐĂNG KÝ SỰ KIỆN ==========
        // Tổng số đăng ký
        $stmt = $pdo->query("SELECT COUNT(*) FROM datlichsukien");
        $statsData['total_registrations'] = (int)$stmt->fetchColumn();
        
        // Đăng ký chờ duyệt
        $stmt = $pdo->query("SELECT COUNT(*) FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
        $statsData['pending_registrations'] = (int)$stmt->fetchColumn();
        
        // Đăng ký đã duyệt
        $stmt = $pdo->query("SELECT COUNT(*) FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
        $statsData['approved_registrations'] = (int)$stmt->fetchColumn();
        
        // Đăng ký bị từ chối
        $stmt = $pdo->query("SELECT COUNT(*) FROM datlichsukien WHERE TrangThaiDuyet = 'Từ chối'");
        $statsData['rejected_registrations'] = (int)$stmt->fetchColumn();
        
        // Đăng ký theo tháng (12 tháng gần nhất)
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                COUNT(*) as count
            FROM datlichsukien 
            WHERE NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['registrations_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Đăng ký theo loại sự kiện
        $stmt = $pdo->query("
            SELECT 
                ls.TenLoai,
                COUNT(dl.ID_DatLich) as count
            FROM datlichsukien dl
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            GROUP BY dl.ID_LoaiSK, ls.TenLoai
            ORDER BY count DESC
        ");
        $statsData['registrations_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ ĐỊA ĐIỂM ==========
        // Tổng số địa điểm
        $stmt = $pdo->query("SELECT COUNT(*) FROM diadiem");
        $statsData['total_locations'] = (int)$stmt->fetchColumn();
        
        // Địa điểm hoạt động
        $stmt = $pdo->query("SELECT COUNT(*) FROM diadiem WHERE TrangThaiHoatDong = 'Hoạt động'");
        $statsData['active_locations'] = (int)$stmt->fetchColumn();
        
        // Địa điểm không hoạt động
        $stmt = $pdo->query("SELECT COUNT(*) FROM diadiem WHERE TrangThaiHoatDong = 'Không hoạt động'");
        $statsData['inactive_locations'] = (int)$stmt->fetchColumn();
        
        // ========== THỐNG KÊ PHÒNG ==========
        // Tổng số phòng
        $stmt = $pdo->query("SELECT COUNT(*) FROM phong");
        $statsData['total_rooms'] = (int)$stmt->fetchColumn();
        
        // Phòng sẵn sàng
        $stmt = $pdo->query("SELECT COUNT(*) FROM phong WHERE TrangThai = 'Sẵn sàng'");
        $statsData['available_rooms'] = (int)$stmt->fetchColumn();
        
        // Phòng đang sử dụng
        $stmt = $pdo->query("SELECT COUNT(*) FROM phong WHERE TrangThai = 'Đang sử dụng'");
        $statsData['occupied_rooms'] = (int)$stmt->fetchColumn();
        
        // ========== THỐNG KÊ THIẾT BỊ ==========
        // Tổng số thiết bị
        $stmt = $pdo->query("SELECT COUNT(*) FROM thietbi");
        $statsData['total_equipment'] = (int)$stmt->fetchColumn();
        
        // Thiết bị sẵn sàng
        $stmt = $pdo->query("SELECT COUNT(*) FROM thietbi WHERE TrangThai = 'Sẵn sàng'");
        $statsData['available_equipment'] = (int)$stmt->fetchColumn();
        
        // Thiết bị đang sử dụng
        $stmt = $pdo->query("SELECT COUNT(*) FROM thietbi WHERE TrangThai = 'Đang sử dụng'");
        $statsData['occupied_equipment'] = (int)$stmt->fetchColumn();
        
        // Thiết bị theo loại
        $stmt = $pdo->query("
            SELECT 
                LoaiThietBi as LoaiTB,
                COUNT(*) as count
            FROM thietbi
            GROUP BY LoaiThietBi
            ORDER BY count DESC
        ");
        $statsData['equipment_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ NHÂN VIÊN ==========
        // Tổng số nhân viên
        $stmt = $pdo->query("SELECT COUNT(*) FROM nhanvieninfo");
        $statsData['total_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên hoạt động (dựa vào users.TrangThai)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE u.TrangThai = 'Hoạt động'
        ");
        $statsData['active_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên không hoạt động (dựa vào users.TrangThai)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE u.TrangThai != 'Hoạt động'
        ");
        $statsData['inactive_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên theo role
        $stmt = $pdo->query("
            SELECT 
                r.RoleName as TenRole,
                COUNT(*) as count
            FROM users u
            INNER JOIN phanquyen r ON u.ID_Role = r.ID_Role
            WHERE u.ID_Role IN (3, 4)
            GROUP BY u.ID_Role, r.RoleName
            ORDER BY u.ID_Role
        ");
        $statsData['staff_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ THANH TOÁN ==========
        // Tổng số thanh toán
        $stmt = $pdo->query("SELECT COUNT(*) FROM thanhtoan");
        $statsData['total_payments'] = (int)$stmt->fetchColumn();
        
        // Thanh toán chờ xử lý
        $stmt = $pdo->query("SELECT COUNT(*) FROM thanhtoan WHERE TrangThai = 'Đang xử lý'");
        $statsData['pending_payments'] = (int)$stmt->fetchColumn();
        
        // Thanh toán đã hoàn thành
        $stmt = $pdo->query("SELECT COUNT(*) FROM thanhtoan WHERE TrangThai = 'Thành công'");
        $statsData['completed_payments'] = (int)$stmt->fetchColumn();
        
        // Thanh toán thất bại
        $stmt = $pdo->query("SELECT COUNT(*) FROM thanhtoan WHERE TrangThai = 'Thất bại'");
        $statsData['failed_payments'] = (int)$stmt->fetchColumn();
        
        // Tổng doanh thu (từ thanh toán thành công)
        $stmt = $pdo->query("SELECT SUM(SoTien) FROM thanhtoan WHERE TrangThai = 'Thành công'");
        $result = $stmt->fetchColumn();
        $statsData['total_revenue'] = $result ? (float)$result : 0;
        
        // Thanh toán theo tháng (12 tháng gần nhất)
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(NgayThanhToan, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(SoTien) as revenue
            FROM thanhtoan 
            WHERE NgayThanhToan >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(NgayThanhToan, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['payments_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ KHÁCH HÀNG ==========
        // Tổng số khách hàng
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5");
        $statsData['total_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng hoạt động
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5 AND TrangThai = 'Hoạt động'");
        $statsData['active_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng đăng ký theo tháng (12 tháng gần nhất)
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                COUNT(*) as count
            FROM users 
            WHERE ID_Role = 5 AND NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['customers_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Reports data PDO error (Manager): " . $e->getMessage());
        error_log("PDO Error Info: " . print_r($pdo->errorInfo(), true));
        $statsData = getDefaultReportsDataForManager();
    } catch (Exception $e) {
        error_log("Reports data error (Manager): " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        $statsData = getDefaultReportsDataForManager();
    }
    
    return $statsData;
}

/**
 * Trả về dữ liệu mặc định khi có lỗi (Role 1)
 * @return array Default statistics data
 */
function getDefaultReportsData() {
    return [
        'total_customers' => 0,
        'active_customers' => 0,
        'pending_customers' => 0,
        'blocked_customers' => 0,
        'customers_by_month' => [],
        'total_staff' => 0,
        'staff_by_role' => [],
        'active_staff' => 0,
        'inactive_staff' => 0,
        'staff_by_month' => [],
        'total_posts' => 0,
        'published_posts' => 0,
        'draft_posts' => 0,
        'archived_posts' => 0,
        'total_views' => 0,
        'posts_by_type' => [],
        'posts_by_month' => [],
        'total_comments' => 0,
        'approved_comments' => 0,
        'pending_comments' => 0,
        'rejected_comments' => 0,
        'top_posts_comments' => [],
        'comments_by_month' => [],
        'total_discounts' => 0,
        'active_discounts' => 0,
        'expired_discounts' => 0,
        'upcoming_discounts' => 0,
        'discounts_by_type' => [],
        'total_discount_uses' => 0
    ];
}

/**
 * Trả về dữ liệu mặc định khi có lỗi (Role 2)
 * @return array Default statistics data
 */
function getDefaultReportsDataForManager() {
    return [
        'total_registrations' => 0,
        'pending_registrations' => 0,
        'approved_registrations' => 0,
        'rejected_registrations' => 0,
        'registrations_by_month' => [],
        'registrations_by_type' => [],
        'total_locations' => 0,
        'active_locations' => 0,
        'inactive_locations' => 0,
        'total_rooms' => 0,
        'available_rooms' => 0,
        'occupied_rooms' => 0,
        'total_equipment' => 0,
        'available_equipment' => 0,
        'occupied_equipment' => 0,
        'equipment_by_type' => [],
        'total_staff' => 0,
        'active_staff' => 0,
        'inactive_staff' => 0,
        'staff_by_role' => [],
        'total_payments' => 0,
        'pending_payments' => 0,
        'completed_payments' => 0,
        'failed_payments' => 0,
        'total_revenue' => 0,
        'payments_by_month' => [],
        'total_customers' => 0,
        'active_customers' => 0,
        'customers_by_month' => []
    ];
}

// Nếu được gọi trực tiếp như API endpoint
if (isset($_GET['action']) && $_GET['action'] === 'get_reports_data') {
    header('Content-Type: application/json');
    
    // Kiểm tra quyền truy cập
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $user = $_SESSION['user'] ?? null;
    if (!$user || ($user['ID_Role'] ?? 0) != 1) {
        echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
        exit;
    }
    
    try {
        $pdo = getDBConnection();
        $statsData = getReportsDataForAdmin($pdo);
        echo json_encode(['success' => true, 'data' => $statsData]);
    } catch (Exception $e) {
        error_log("Reports API error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy dữ liệu thống kê']);
    }
    exit;
}

?>

