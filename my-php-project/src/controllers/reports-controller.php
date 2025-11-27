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
 * Tạo điều kiện WHERE dựa trên filter type
 * @param string $filterType Loại filter: 'all', 'date', 'month', 'year', 'range'
 * @param string $dateField Tên trường ngày trong database (mặc định: 'NgayTao')
 * @return string WHERE clause
 */
function buildDateFilter($filterType = 'all', $dateField = 'NgayTao') {
    // Debug logging
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        error_log("buildDateFilter called: filterType=$filterType, dateField=$dateField");
        error_log("GET params: " . print_r($_GET, true));
    }
    
    if ($filterType === 'all' || empty($filterType)) {
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("buildDateFilter: Returning empty (filterType=all)");
        }
        return '';
    }
    
    $whereClause = '';
    
    if ($filterType === 'date') {
        if (isset($_GET['filter_date']) && !empty(trim($_GET['filter_date']))) {
            $date = trim($_GET['filter_date']);
            // Validate format YYYY-MM-DD
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                // Xử lý timestamp: DATE() hoạt động với cả DATE và TIMESTAMP
                // Đảm bảo so sánh đúng với timestamp bằng cách dùng DATE()
                $whereClause = "DATE($dateField) = DATE('$date')";
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                    error_log("buildDateFilter: date filter applied: $whereClause");
                    error_log("buildDateFilter: date value: $date");
                }
            } else {
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                    error_log("buildDateFilter: Invalid date format: $date");
                }
            }
        }
    } elseif ($filterType === 'month') {
        if (isset($_GET['filter_month']) && !empty(trim($_GET['filter_month']))) {
            $month = trim($_GET['filter_month']);
            // Validate format YYYY-MM
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                // Xử lý timestamp: DATE_FORMAT hoạt động với cả DATE và TIMESTAMP
                // Đảm bảo so sánh đúng bằng cách dùng DATE_FORMAT với cả 2 bên
                $whereClause = "DATE_FORMAT($dateField, '%Y-%m') = '$month'";
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                    error_log("buildDateFilter: month filter applied: $whereClause");
                    error_log("buildDateFilter: month value: $month");
                    // Test query để xem dữ liệu có sẵn
                    try {
                        $pdo = getDBConnection();
                        $testQuery = "SELECT DATE_FORMAT($dateField, '%Y-%m') as month, COUNT(*) as count FROM (SELECT $dateField FROM users WHERE ID_Role = 5 LIMIT 10) as test GROUP BY DATE_FORMAT($dateField, '%Y-%m')";
                        $testStmt = $pdo->query($testQuery);
                        $testResults = $testStmt->fetchAll(PDO::FETCH_ASSOC);
                        error_log("Sample months from $dateField: " . print_r($testResults, true));
                    } catch (Exception $e) {
                        error_log("Test query error: " . $e->getMessage());
                    }
                }
            } else {
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                    error_log("buildDateFilter: Invalid month format: $month");
                }
            }
        }
    } elseif ($filterType === 'year') {
        if (isset($_GET['filter_year']) && !empty($_GET['filter_year'])) {
            $year = intval($_GET['filter_year']);
            if ($year > 0) {
                // Xử lý timestamp: YEAR() hoạt động với cả DATE và TIMESTAMP
                $whereClause = "YEAR($dateField) = $year";
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                    error_log("buildDateFilter: year filter applied: $whereClause");
                }
            }
        }
    } elseif ($filterType === 'range') {
        if (isset($_GET['filter_date_from']) && isset($_GET['filter_date_to'])) {
            $dateFrom = trim($_GET['filter_date_from']);
            $dateTo = trim($_GET['filter_date_to']);
            if (!empty($dateFrom) && !empty($dateTo)) {
                // Validate format YYYY-MM-DD
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                    // Xử lý timestamp: DATE() hoạt động với cả DATE và TIMESTAMP
                    // Đảm bảo so sánh đúng với timestamp bằng cách dùng DATE()
                    $whereClause = "DATE($dateField) BETWEEN DATE('$dateFrom') AND DATE('$dateTo')";
                    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                        error_log("buildDateFilter: range filter applied: $whereClause");
                        error_log("buildDateFilter: date from: $dateFrom, date to: $dateTo");
                    }
                } else {
                    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                        error_log("buildDateFilter: Invalid date range format: from=$dateFrom, to=$dateTo");
                    }
                }
            }
        }
    }
    
    if (isset($_GET['debug']) && $_GET['debug'] == '1' && empty($whereClause)) {
        error_log("buildDateFilter: No filter clause generated for filterType=$filterType");
        error_log("buildDateFilter: filter_month=" . ($_GET['filter_month'] ?? 'not set'));
    }
    
    return $whereClause;
}

/**
 * Lấy tất cả dữ liệu thống kê cho Reports (Role 1 - QTV)
 * @param PDO $pdo Database connection
 * @param string $filterType Loại filter: 'all', 'date', 'month', 'year', 'range'
 * @return array Statistics data
 */
function getReportsDataForAdmin($pdo, $filterType = 'all') {
    $statsData = [];
    
    try {
        // Kiểm tra kết nối database
        if (!$pdo) {
            throw new Exception("Không thể kết nối database");
        }
        
        // ========== THỐNG KÊ KHÁCH HÀNG ==========
        // Áp dụng filter cho tất cả query khách hàng
        $dateFilter = buildDateFilter($filterType, 'NgayTao');
        $whereBase = "ID_Role = 5";
        if ($dateFilter) {
            $whereBase .= " AND $dateFilter";
        } else {
            // Nếu không có filter, lấy 12 tháng gần nhất
            $whereBase .= " AND NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        // Debug logging
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("getReportsDataForAdmin: filterType=$filterType");
            error_log("getReportsDataForAdmin: dateFilter=" . ($dateFilter ?: 'empty'));
            error_log("getReportsDataForAdmin: whereBase=$whereBase");
        }
        
        // Tổng số khách hàng
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE $whereBase");
        $statsData['total_customers'] = (int)$stmt->fetchColumn();
        
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("getReportsDataForAdmin: total_customers=" . $statsData['total_customers']);
        }
        
        // Khách hàng hoạt động
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE $whereBase AND TrangThai = 'Hoạt động'");
        $statsData['active_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng chưa xác minh
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE $whereBase AND TrangThai = 'Chưa xác minh'");
        $statsData['pending_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng bị khóa
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE $whereBase AND TrangThai = 'Bị khóa'");
        $statsData['blocked_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng đăng ký theo tháng
        $whereClause = "ID_Role = 5";
        if ($dateFilter) {
            $whereClause .= " AND $dateFilter";
        } else {
            $whereClause .= " AND NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                COUNT(*) as count
            FROM users 
            WHERE $whereClause
            GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['customers_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ NHÂN VIÊN ==========
        // Áp dụng filter cho nhân viên
        $dateFilterStaff = buildDateFilter($filterType, 'u.NgayTao');
        $whereStaffBase = "u.ID_Role IN (2, 3, 4)";
        if ($dateFilterStaff) {
            $whereStaffBase .= " AND $dateFilterStaff";
        } else {
            // Nếu không có filter, lấy 12 tháng gần nhất
            $whereStaffBase .= " AND u.NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        // Tổng số nhân viên
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE $whereStaffBase
        ");
        $statsData['total_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên theo role
        $stmt = $pdo->query("
            SELECT 
                r.RoleName as TenRole,
                COUNT(*) as count
            FROM users u
            INNER JOIN phanquyen r ON u.ID_Role = r.ID_Role
            WHERE $whereStaffBase
            GROUP BY u.ID_Role, r.RoleName
            ORDER BY u.ID_Role
        ");
        $statsData['staff_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nhân viên hoạt động (dựa vào users.TrangThai)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE $whereStaffBase AND u.TrangThai = 'Hoạt động'
        ");
        $statsData['active_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên không hoạt động (dựa vào users.TrangThai)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE $whereStaffBase AND u.TrangThai != 'Hoạt động'
        ");
        $statsData['inactive_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên tuyển dụng theo tháng (12 tháng gần nhất hoặc theo filter)
        // Lấy từ users vì nhanvieninfo có thể không có NgayTao
        $whereClause = "1=1";
        if ($dateFilterStaff) {
            $whereClause = $dateFilterStaff;
        } else {
            $whereClause = "u.NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(u.NgayTao, '%Y-%m') as month,
                COUNT(*) as count
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE $whereClause
            GROUP BY DATE_FORMAT(u.NgayTao, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['staff_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ BÀI VIẾT ==========
        // Áp dụng filter cho bài viết
        $dateFilterPosts = buildDateFilter($filterType, 'created_at');
        $wherePostsBase = "1=1";
        if ($dateFilterPosts) {
            $wherePostsBase = $dateFilterPosts;
        } else {
            // Nếu không có filter, lấy 12 tháng gần nhất
            $wherePostsBase = "created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        // Tổng số bài viết
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE $wherePostsBase");
        $statsData['total_posts'] = (int)$stmt->fetchColumn();
        
        // Bài viết đã xuất bản
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE $wherePostsBase AND status = 'published'");
        $statsData['published_posts'] = (int)$stmt->fetchColumn();
        
        // Bài viết bản nháp
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE $wherePostsBase AND status = 'draft'");
        $statsData['draft_posts'] = (int)$stmt->fetchColumn();
        
        // Bài viết đã lưu trữ
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE $wherePostsBase AND status = 'archived'");
        $statsData['archived_posts'] = (int)$stmt->fetchColumn();
        
        // Tổng lượt xem
        $stmt = $pdo->query("SELECT SUM(views) FROM blog_posts WHERE $wherePostsBase");
        $result = $stmt->fetchColumn();
        $statsData['total_views'] = $result ? (int)$result : 0;
        
        // Bài viết theo loại sự kiện (áp dụng filter)
        $wherePostsTypeBase = "1=1";
        if ($dateFilterPosts) {
            $wherePostsTypeBase = $dateFilterPosts;
        }
        $stmt = $pdo->query("
            SELECT 
                ls.TenLoai,
                COUNT(bp.id) as count
            FROM blog_posts bp
            LEFT JOIN loaisukien ls ON bp.event_type_id = ls.ID_LoaiSK
            WHERE $wherePostsTypeBase
            GROUP BY bp.event_type_id, ls.TenLoai
            ORDER BY count DESC
        ");
        $statsData['posts_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Bài viết đăng theo tháng (12 tháng gần nhất hoặc theo filter)
        $whereClause = "1=1";
        if ($dateFilterPosts) {
            $whereClause = $dateFilterPosts;
        } else {
            $whereClause = "created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM blog_posts 
            WHERE $whereClause
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['posts_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ COMMENT ==========
        // Áp dụng filter cho comment
        $dateFilterComments = buildDateFilter($filterType, 'created_at');
        $whereCommentsBase = "1=1";
        if ($dateFilterComments) {
            $whereCommentsBase = $dateFilterComments;
        } else {
            // Nếu không có filter, lấy 12 tháng gần nhất
            $whereCommentsBase = "created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        // Tổng số comment
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE $whereCommentsBase");
        $statsData['total_comments'] = (int)$stmt->fetchColumn();
        
        // Comment đã duyệt
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE $whereCommentsBase AND status = 'approved'");
        $statsData['approved_comments'] = (int)$stmt->fetchColumn();
        
        // Comment chờ duyệt
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE $whereCommentsBase AND status = 'pending'");
        $statsData['pending_comments'] = (int)$stmt->fetchColumn();
        
        // Comment bị từ chối
        $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE $whereCommentsBase AND status = 'rejected'");
        $statsData['rejected_comments'] = (int)$stmt->fetchColumn();
        
        // Comment theo bài viết (top 10 bài viết có nhiều comment nhất) - áp dụng filter cho comment
        $whereCommentsTopBase = "1=1";
        if ($dateFilterComments) {
            $whereCommentsTopBase = $dateFilterComments;
        }
        $stmt = $pdo->query("
            SELECT 
                bp.title,
                COUNT(bc.id) as comment_count
            FROM blog_posts bp
            LEFT JOIN blog_comments bc ON bp.id = bc.post_id
            WHERE $whereCommentsTopBase
            GROUP BY bp.id, bp.title
            ORDER BY comment_count DESC
            LIMIT 10
        ");
        $statsData['top_posts_comments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Comment theo tháng (12 tháng gần nhất hoặc theo filter)
        $whereClause = "1=1";
        if ($dateFilterComments) {
            $whereClause = $dateFilterComments;
        } else {
            $whereClause = "created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM blog_comments 
            WHERE $whereClause
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
 * @param string $filterType Loại filter: 'all', 'date', 'month', 'year', 'range'
 * @return array Statistics data
 */
function getReportsDataForManager($pdo, $filterType = 'all') {
    $statsData = [];
    
    try {
        // Kiểm tra kết nối database
        if (!$pdo) {
            throw new Exception("Không thể kết nối database");
        }
        
        // ========== THỐNG KÊ ĐĂNG KÝ SỰ KIỆN ==========
        // Áp dụng filter cho đăng ký sự kiện
        $dateFilterReg = buildDateFilter($filterType, 'NgayTao');
        $whereRegBase = "1=1";
        if ($dateFilterReg) {
            $whereRegBase = $dateFilterReg;
        } else {
            // Nếu không có filter, lấy 12 tháng gần nhất
            $whereRegBase = "NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        // Debug logging
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("getReportsDataForManager: filterType=$filterType");
            error_log("getReportsDataForManager: dateFilterReg=" . ($dateFilterReg ?: 'empty'));
            error_log("getReportsDataForManager: whereRegBase=$whereRegBase");
        }
        
        // Tổng số đăng ký
        $stmt = $pdo->query("SELECT COUNT(*) FROM datlichsukien WHERE $whereRegBase");
        $statsData['total_registrations'] = (int)$stmt->fetchColumn();
        
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("getReportsDataForManager: total_registrations=" . $statsData['total_registrations']);
        }
        
        // Đăng ký chờ duyệt
        $stmt = $pdo->query("SELECT COUNT(*) FROM datlichsukien WHERE $whereRegBase AND TrangThaiDuyet = 'Chờ duyệt'");
        $statsData['pending_registrations'] = (int)$stmt->fetchColumn();
        
        // Đăng ký đã duyệt
        $stmt = $pdo->query("SELECT COUNT(*) FROM datlichsukien WHERE $whereRegBase AND TrangThaiDuyet = 'Đã duyệt'");
        $statsData['approved_registrations'] = (int)$stmt->fetchColumn();
        
        // Đăng ký bị từ chối
        $stmt = $pdo->query("SELECT COUNT(*) FROM datlichsukien WHERE $whereRegBase AND TrangThaiDuyet = 'Từ chối'");
        $statsData['rejected_registrations'] = (int)$stmt->fetchColumn();
        
        // Đăng ký theo tháng (12 tháng gần nhất hoặc theo filter)
        $whereClause = "1=1";
        if ($dateFilterReg) {
            $whereClause = $dateFilterReg;
        } else {
            $whereClause = "NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                COUNT(*) as count
            FROM datlichsukien 
            WHERE $whereClause
            GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['registrations_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Đăng ký theo loại sự kiện (áp dụng filter)
        $whereRegTypeBase = "1=1";
        if ($dateFilterReg) {
            $whereRegTypeBase = $dateFilterReg;
        }
        $stmt = $pdo->query("
            SELECT 
                ls.TenLoai,
                COUNT(dl.ID_DatLich) as count
            FROM datlichsukien dl
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            WHERE $whereRegTypeBase
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
        // Áp dụng filter cho nhân viên (theo ngày tuyển dụng)
        $dateFilterStaff = buildDateFilter($filterType, 'u.NgayTao');
        $whereStaffBase = "u.ID_Role IN (3, 4)";
        if ($dateFilterStaff) {
            $whereStaffBase .= " AND $dateFilterStaff";
        } else {
            // Nếu không có filter, lấy 12 tháng gần nhất
            $whereStaffBase .= " AND u.NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        // Tổng số nhân viên
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE $whereStaffBase
        ");
        $statsData['total_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên hoạt động (dựa vào users.TrangThai)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE $whereStaffBase AND u.TrangThai = 'Hoạt động'
        ");
        $statsData['active_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên không hoạt động (dựa vào users.TrangThai)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM nhanvieninfo nv
            INNER JOIN users u ON nv.ID_User = u.ID_User
            WHERE $whereStaffBase AND u.TrangThai != 'Hoạt động'
        ");
        $statsData['inactive_staff'] = (int)$stmt->fetchColumn();
        
        // Nhân viên theo role
        $stmt = $pdo->query("
            SELECT 
                r.RoleName as TenRole,
                COUNT(*) as count
            FROM users u
            INNER JOIN phanquyen r ON u.ID_Role = r.ID_Role
            WHERE $whereStaffBase
            GROUP BY u.ID_Role, r.RoleName
            ORDER BY u.ID_Role
        ");
        $statsData['staff_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ THANH TOÁN ==========
        // Áp dụng filter cho thanh toán
        $dateFilterPayments = buildDateFilter($filterType, 'NgayThanhToan');
        $wherePaymentsBase = "1=1";
        if ($dateFilterPayments) {
            $wherePaymentsBase = $dateFilterPayments;
        } else {
            // Nếu không có filter, lấy 12 tháng gần nhất
            $wherePaymentsBase = "NgayThanhToan >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        // Tổng số thanh toán
        $stmt = $pdo->query("SELECT COUNT(*) FROM thanhtoan WHERE $wherePaymentsBase");
        $statsData['total_payments'] = (int)$stmt->fetchColumn();
        
        // Thanh toán chờ xử lý
        $stmt = $pdo->query("SELECT COUNT(*) FROM thanhtoan WHERE $wherePaymentsBase AND TrangThai = 'Đang xử lý'");
        $statsData['pending_payments'] = (int)$stmt->fetchColumn();
        
        // Thanh toán đã hoàn thành
        $stmt = $pdo->query("SELECT COUNT(*) FROM thanhtoan WHERE $wherePaymentsBase AND TrangThai = 'Thành công'");
        $statsData['completed_payments'] = (int)$stmt->fetchColumn();
        
        // Thanh toán thất bại
        $stmt = $pdo->query("SELECT COUNT(*) FROM thanhtoan WHERE $wherePaymentsBase AND TrangThai = 'Thất bại'");
        $statsData['failed_payments'] = (int)$stmt->fetchColumn();
        
        // Tổng doanh thu (từ thanh toán thành công)
        $stmt = $pdo->query("SELECT SUM(SoTien) FROM thanhtoan WHERE $wherePaymentsBase AND TrangThai = 'Thành công'");
        $result = $stmt->fetchColumn();
        $statsData['total_revenue'] = $result ? (float)$result : 0;
        
        // Thanh toán theo tháng (12 tháng gần nhất hoặc theo filter)
        $whereClause = "1=1";
        if ($dateFilterPayments) {
            $whereClause = $dateFilterPayments;
        } else {
            $whereClause = "NgayThanhToan >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(NgayThanhToan, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(SoTien) as revenue
            FROM thanhtoan 
            WHERE $whereClause
            GROUP BY DATE_FORMAT(NgayThanhToan, '%Y-%m')
            ORDER BY month ASC
        ");
        $statsData['payments_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ========== THỐNG KÊ KHÁCH HÀNG ==========
        // Áp dụng filter cho khách hàng
        $dateFilterCustomers = buildDateFilter($filterType, 'NgayTao');
        $whereCustomersBase = "ID_Role = 5";
        if ($dateFilterCustomers) {
            $whereCustomersBase .= " AND $dateFilterCustomers";
        } else {
            // Nếu không có filter, lấy 12 tháng gần nhất
            $whereCustomersBase .= " AND NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        // Tổng số khách hàng
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE $whereCustomersBase");
        $statsData['total_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng hoạt động
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE $whereCustomersBase AND TrangThai = 'Hoạt động'");
        $statsData['active_customers'] = (int)$stmt->fetchColumn();
        
        // Khách hàng đăng ký theo tháng
        $whereClause = "ID_Role = 5";
        if ($dateFilterCustomers) {
            $whereClause .= " AND $dateFilterCustomers";
        } else {
            $whereClause .= " AND NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
        }
        
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                COUNT(*) as count
            FROM users 
            WHERE $whereClause
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