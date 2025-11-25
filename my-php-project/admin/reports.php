<?php
// Include admin header
include 'includes/admin-header.php';

// Chỉ cho phép Role 1 (QTV) truy cập
if ($user['ID_Role'] != 1) {
    echo '<script>alert("Bạn không có quyền truy cập trang này!"); window.location.href = "index.php";</script>';
    exit;
}

// Get statistics data
$statsData = [];
try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();
    
    // ========== THỐNG KÊ KHÁCH HÀNG ==========
    // Tổng số khách hàng
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5");
    $statsData['total_customers'] = $stmt->fetchColumn();
    
    // Khách hàng hoạt động
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5 AND TrangThai = 'Hoạt động'");
    $statsData['active_customers'] = $stmt->fetchColumn();
    
    // Khách hàng chưa xác minh
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5 AND TrangThai = 'Chưa xác minh'");
    $statsData['pending_customers'] = $stmt->fetchColumn();
    
    // Khách hàng bị khóa
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE ID_Role = 5 AND TrangThai = 'Bị khóa'");
    $statsData['blocked_customers'] = $stmt->fetchColumn();
    
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
    $statsData['total_staff'] = $stmt->fetchColumn();
    
    // Nhân viên theo role
    $stmt = $pdo->query("
        SELECT 
            r.TenRole,
            COUNT(*) as count
        FROM users u
        INNER JOIN roles r ON u.ID_Role = r.ID_Role
        WHERE u.ID_Role IN (2, 3, 4)
        GROUP BY u.ID_Role, r.TenRole
        ORDER BY u.ID_Role
    ");
    $statsData['staff_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Nhân viên hoạt động
    $stmt = $pdo->query("SELECT COUNT(*) FROM nhanvieninfo WHERE TrangThai = 'Hoạt động'");
    $statsData['active_staff'] = $stmt->fetchColumn();
    
    // Nhân viên nghỉ việc
    $stmt = $pdo->query("SELECT COUNT(*) FROM nhanvieninfo WHERE TrangThai = 'Nghỉ việc'");
    $statsData['inactive_staff'] = $stmt->fetchColumn();
    
    // Nhân viên tuyển dụng theo tháng (12 tháng gần nhất)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(NgayTao, '%Y-%m') as month,
            COUNT(*) as count
        FROM nhanvieninfo 
        WHERE NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
        ORDER BY month ASC
    ");
    $statsData['staff_by_month'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ========== THỐNG KÊ BÀI VIẾT ==========
    // Tổng số bài viết
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts");
    $statsData['total_posts'] = $stmt->fetchColumn();
    
    // Bài viết đã xuất bản
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'");
    $statsData['published_posts'] = $stmt->fetchColumn();
    
    // Bài viết bản nháp
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'");
    $statsData['draft_posts'] = $stmt->fetchColumn();
    
    // Bài viết đã lưu trữ
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'archived'");
    $statsData['archived_posts'] = $stmt->fetchColumn();
    
    // Tổng lượt xem
    $stmt = $pdo->query("SELECT SUM(views) FROM blog_posts");
    $statsData['total_views'] = $stmt->fetchColumn() ?: 0;
    
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
    $statsData['total_comments'] = $stmt->fetchColumn();
    
    // Comment đã duyệt
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE status = 'approved'");
    $statsData['approved_comments'] = $stmt->fetchColumn();
    
    // Comment chờ duyệt
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE status = 'pending'");
    $statsData['pending_comments'] = $stmt->fetchColumn();
    
    // Comment bị từ chối
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_comments WHERE status = 'rejected'");
    $statsData['rejected_comments'] = $stmt->fetchColumn();
    
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
    $statsData['total_discounts'] = $stmt->fetchColumn();
    
    // Mã giảm giá đang hoạt động
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM magiamgia 
        WHERE NgayBatDau <= NOW() AND NgayKetThuc >= NOW()
    ");
    $statsData['active_discounts'] = $stmt->fetchColumn();
    
    // Mã giảm giá đã hết hạn
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM magiamgia 
        WHERE NgayKetThuc < NOW()
    ");
    $statsData['expired_discounts'] = $stmt->fetchColumn();
    
    // Mã giảm giá chưa bắt đầu
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM magiamgia 
        WHERE NgayBatDau > NOW()
    ");
    $statsData['upcoming_discounts'] = $stmt->fetchColumn();
    
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
    $stmt = $pdo->query("SELECT SUM(SoLanSuDung) FROM magiamgia");
    $statsData['total_discount_uses'] = $stmt->fetchColumn() ?: 0;
    
} catch (Exception $e) {
    error_log("Reports data error: " . $e->getMessage());
    $statsData = [
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
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-bar"></i>
                Thống kê báo cáo
            </h1>
            <p class="page-subtitle">Tổng quan thống kê về khách hàng, nhân viên, bài viết, bình luận và mã giảm giá</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <!-- Khách hàng -->
            <div class="stat-card">
                <div class="stat-icon customers">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= $statsData['total_customers'] ?></div>
                <div class="stat-label">Tổng khách hàng</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number"><?= $statsData['active_customers'] ?></div>
                <div class="stat-label">Khách hàng hoạt động</div>
            </div>
            
            <!-- Nhân viên -->
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-number"><?= $statsData['total_staff'] ?></div>
                <div class="stat-label">Tổng nhân viên</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number"><?= $statsData['active_staff'] ?></div>
                <div class="stat-label">Nhân viên hoạt động</div>
            </div>
            
            <!-- Bài viết -->
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-blog"></i>
                </div>
                <div class="stat-number"><?= $statsData['total_posts'] ?></div>
                <div class="stat-label">Tổng bài viết</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-number"><?= number_format($statsData['total_views']) ?></div>
                <div class="stat-label">Tổng lượt xem</div>
            </div>
            
            <!-- Comment -->
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-number"><?= $statsData['total_comments'] ?></div>
                <div class="stat-label">Tổng bình luận</div>
            </div>
            
            <!-- Mã giảm giá -->
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-number"><?= $statsData['total_discounts'] ?></div>
                <div class="stat-label">Tổng mã giảm giá</div>
            </div>
        </div>
        
    <style>
        /* Thu gọn stats-cards */
        .stats-cards {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
            gap: 1rem !important;
            margin-bottom: 1.5rem !important;
        }
        
        .stat-card {
            padding: 1rem !important;
        }
        
        .stat-icon {
            width: 45px !important;
            height: 45px !important;
            font-size: 1.2rem !important;
            margin-bottom: 0.75rem !important;
        }
        
        .stat-number {
            font-size: 1.5rem !important;
            margin-bottom: 0.25rem !important;
        }
        
        .stat-label {
            font-size: 0.85rem !important;
        }
        
        /* Đảm bảo chart-wrapper có height */
        .chart-wrapper {
            min-height: 300px !important;
            height: 300px !important;
        }
        
        .chart-container {
            padding: 1.5rem !important;
            margin-bottom: 1.5rem !important;
        }
        
        .chart-title {
            font-size: 1.2rem !important;
            margin-bottom: 1rem !important;
        }
    </style>

        <!-- Charts Section -->
        <div class="row">
            <!-- Thống kê khách hàng -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Trạng thái khách hàng
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="customersStatusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Thống kê nhân viên -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Nhân viên theo vai trò
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="staffRoleChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Thống kê bài viết -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Trạng thái bài viết
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="postsStatusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Thống kê comment -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Trạng thái bình luận
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="commentsStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Thống kê mã giảm giá -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Trạng thái mã giảm giá
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="discountsStatusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Mã giảm giá theo loại -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar"></i>
                        Mã giảm giá theo loại
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="discountsTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Khách hàng theo tháng -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Khách hàng đăng ký theo tháng (12 tháng gần nhất)
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="customersMonthlyChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Nhân viên theo tháng -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Nhân viên tuyển dụng theo tháng (12 tháng gần nhất)
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="staffMonthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Bài viết theo tháng -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Bài viết đăng theo tháng (12 tháng gần nhất)
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="postsMonthlyChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Comment theo tháng -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Bình luận theo tháng (12 tháng gần nhất)
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="commentsMonthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <!-- Bài viết theo loại sự kiện -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar"></i>
                        Bài viết theo loại sự kiện
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="postsByTypeChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Top 10 bài viết có nhiều comment -->
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar"></i>
                        Top 10 bài viết có nhiều bình luận nhất
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="topPostsCommentsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

<script>
// Đợi Chart.js load xong
function waitForChartJS(callback) {
    if (typeof Chart !== 'undefined') {
        callback();
    } else {
        setTimeout(function() {
            waitForChartJS(callback);
        }, 100);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    waitForChartJS(function() {
        initializeCharts();
    });
});

function initializeCharts() {
    // Kiểm tra Chart.js đã load chưa
    if (typeof Chart === 'undefined') {
        console.error('Chart.js chưa được load');
        return;
    }
    // ========== KHÁCH HÀNG ==========
    // Trạng thái khách hàng
    const customersStatusCtx = document.getElementById('customersStatusChart');
    if (customersStatusCtx) {
        new Chart(customersStatusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Hoạt động', 'Chưa xác minh', 'Bị khóa'],
                datasets: [{
                    data: [
                        <?= $statsData['active_customers'] ?>,
                        <?= $statsData['pending_customers'] ?>,
                        <?= $statsData['blocked_customers'] ?>
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                }
            }
        });
    }
    
    // Khách hàng theo tháng
    const customersMonthlyCtx = document.getElementById('customersMonthlyChart');
    if (customersMonthlyCtx) {
        const customersMonthlyData = <?= json_encode($statsData['customers_by_month']) ?>;
        new Chart(customersMonthlyCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: customersMonthlyData.length > 0 ? customersMonthlyData.map(item => item.month) : [],
                datasets: [{
                    label: 'Số khách hàng',
                    data: customersMonthlyData.length > 0 ? customersMonthlyData.map(item => item.count) : [],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: { legend: { display: true, position: 'top', labels: { font: { size: 12 } } } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
            }
        });
    }
    
    // ========== NHÂN VIÊN ==========
    // Nhân viên theo vai trò
    const staffRoleCtx = document.getElementById('staffRoleChart');
    if (staffRoleCtx) {
        const staffRoleData = <?= json_encode($statsData['staff_by_role']) ?>;
        new Chart(staffRoleCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: staffRoleData.length > 0 ? staffRoleData.map(item => item.TenRole) : [],
                datasets: [{
                    data: staffRoleData.length > 0 ? staffRoleData.map(item => item.count) : [],
                    backgroundColor: ['#007bff', '#17a2b8', '#6f42c1'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                }
            }
        });
    }
    
    // Nhân viên theo tháng
    const staffMonthlyCtx = document.getElementById('staffMonthlyChart');
    if (staffMonthlyCtx) {
        const staffMonthlyData = <?= json_encode($statsData['staff_by_month']) ?>;
        new Chart(staffMonthlyCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: staffMonthlyData.length > 0 ? staffMonthlyData.map(item => item.month) : [],
                datasets: [{
                    label: 'Số nhân viên',
                    data: staffMonthlyData.length > 0 ? staffMonthlyData.map(item => item.count) : [],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: { legend: { display: true, position: 'top', labels: { font: { size: 12 } } } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
            }
        });
    }
    
    // ========== BÀI VIẾT ==========
    // Trạng thái bài viết
    const postsStatusCtx = document.getElementById('postsStatusChart');
    if (postsStatusCtx) {
        new Chart(postsStatusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Đã xuất bản', 'Bản nháp', 'Đã lưu trữ'],
                datasets: [{
                    data: [
                        <?= $statsData['published_posts'] ?>,
                        <?= $statsData['draft_posts'] ?>,
                        <?= $statsData['archived_posts'] ?>
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#6c757d'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                }
            }
        });
    }
    
    // Bài viết theo tháng
    const postsMonthlyCtx = document.getElementById('postsMonthlyChart');
    if (postsMonthlyCtx) {
        const postsMonthlyData = <?= json_encode($statsData['posts_by_month']) ?>;
        new Chart(postsMonthlyCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: postsMonthlyData.length > 0 ? postsMonthlyData.map(item => item.month) : [],
                datasets: [{
                    label: 'Số bài viết',
                    data: postsMonthlyData.length > 0 ? postsMonthlyData.map(item => item.count) : [],
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: { legend: { display: true, position: 'top', labels: { font: { size: 12 } } } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
            }
        });
    }
    
    // Bài viết theo loại sự kiện
    const postsByTypeCtx = document.getElementById('postsByTypeChart');
    if (postsByTypeCtx) {
        const postsByTypeData = <?= json_encode($statsData['posts_by_type']) ?>;
        new Chart(postsByTypeCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: postsByTypeData.length > 0 ? postsByTypeData.map(item => item.TenLoai || 'Chưa phân loại') : [],
                datasets: [{
                    label: 'Số bài viết',
                    data: postsByTypeData.length > 0 ? postsByTypeData.map(item => item.count) : [],
                    backgroundColor: '#667eea',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
            }
        });
    }
    
    // ========== COMMENT ==========
    // Trạng thái comment
    const commentsStatusCtx = document.getElementById('commentsStatusChart');
    if (commentsStatusCtx) {
        new Chart(commentsStatusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Đã duyệt', 'Chờ duyệt', 'Bị từ chối'],
                datasets: [{
                    data: [
                        <?= $statsData['approved_comments'] ?>,
                        <?= $statsData['pending_comments'] ?>,
                        <?= $statsData['rejected_comments'] ?>
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                }
            }
        });
    }
    
    // Comment theo tháng
    const commentsMonthlyCtx = document.getElementById('commentsMonthlyChart');
    if (commentsMonthlyCtx) {
        const commentsMonthlyData = <?= json_encode($statsData['comments_by_month']) ?>;
        new Chart(commentsMonthlyCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: commentsMonthlyData.length > 0 ? commentsMonthlyData.map(item => item.month) : [],
                datasets: [{
                    label: 'Số bình luận',
                    data: commentsMonthlyData.length > 0 ? commentsMonthlyData.map(item => item.count) : [],
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: { legend: { display: true, position: 'top', labels: { font: { size: 12 } } } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
            }
        });
    }
    
    // Top 10 bài viết có nhiều comment
    const topPostsCommentsCtx = document.getElementById('topPostsCommentsChart');
    if (topPostsCommentsCtx) {
        const topPostsCommentsData = <?= json_encode($statsData['top_posts_comments']) ?>;
        new Chart(topPostsCommentsCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: topPostsCommentsData.length > 0 ? topPostsCommentsData.map(item => item.title ? item.title.substring(0, 30) + '...' : 'Chưa có tiêu đề') : [],
                datasets: [{
                    label: 'Số bình luận',
                    data: topPostsCommentsData.length > 0 ? topPostsCommentsData.map(item => item.comment_count) : [],
                    backgroundColor: '#6f42c1',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, y: { ticks: { font: { size: 10 } } } }
            }
        });
    }
    
    // ========== MÃ GIẢM GIÁ ==========
    // Trạng thái mã giảm giá
    const discountsStatusCtx = document.getElementById('discountsStatusChart');
    if (discountsStatusCtx) {
        new Chart(discountsStatusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Đang hoạt động', 'Đã hết hạn', 'Chưa bắt đầu'],
                datasets: [{
                    data: [
                        <?= $statsData['active_discounts'] ?>,
                        <?= $statsData['expired_discounts'] ?>,
                        <?= $statsData['upcoming_discounts'] ?>
                    ],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.5,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                }
            }
        });
    }
    
    // Mã giảm giá theo loại
    const discountsTypeCtx = document.getElementById('discountsTypeChart');
    if (discountsTypeCtx) {
        const discountsTypeData = <?= json_encode($statsData['discounts_by_type']) ?>;
        new Chart(discountsTypeCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: discountsTypeData.length > 0 ? discountsTypeData.map(item => item.LoaiGiamGia) : [],
                datasets: [{
                    label: 'Số mã',
                    data: discountsTypeData.length > 0 ? discountsTypeData.map(item => item.count) : [],
                    backgroundColor: '#17a2b8',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 2,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
            }
        });
    }
}
</script>

<?php include 'includes/admin-footer.php'; ?>
