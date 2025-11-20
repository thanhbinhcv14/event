<?php
// Template Header Admin (đã được dịch)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kết nối database
require_once __DIR__ . '/../../config/database.php';

// Kiểm tra người dùng đã đăng nhập và có quyền admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2, 3, 4])) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'];
$isAdmin = $user['ID_Role'] == 1;
$isManager = in_array($user['ID_Role'], [2, 3]);
$isStaff = $user['ID_Role'] == 4;

// Lấy tên trang hiện tại
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Ánh xạ tiêu đề trang
$pageTitles = [
    'index' => 'Dashboard',
    'event-registrations' => 'Duyệt đăng ký sự kiện',
    'event-registration' => 'Đăng ký sự kiện',
    'event-planning' => 'Lên kế hoạch thực hiện và phân công',
    'event-types' => 'Quản lý loại sự kiện',
    'staff-assignment' => 'Phân công nhân viên',
    'staff-schedule' => 'Lịch làm việc',
    'staff-reports' => 'Báo cáo tiến độ',
    'locations' => 'Quản lý địa điểm',
    'rooms' => 'Quản lý phòng',
    'device' => 'Quản lý thiết bị',
    'payment-management' => 'Quản lý thanh toán',
    'customeredit_content' => 'Quản lý khách hàng',
    'accstaff' => 'Quản lý nhân viên',
    'reports' => 'Thống kê báo cáo',
    'chat' => 'Chat Hỗ trợ'
];

$pageTitle = $pageTitles[$currentPage] ?? 'Quản trị';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin-style.css">
    
    <!-- CSRF Protection Helper - Load before other scripts -->
    <script src="../assets/js/csrf-helper.js"></script>
    
    <!-- Favicon -->
    <link rel="icon" href="img/logo/logo.jpg">
    
    <style>
        /* Có thể thêm các style riêng cho trang ở đây */
        .page-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            pointer-events: none;
        }
        
        .page-loading .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--primary-color);
        }
        
        /* Huy hiệu Chat */
        .chat-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .menu-item {
            position: relative;
            pointer-events: auto !important;
        }
        
        /* Đảm bảo tất cả liên kết điều hướng có thể click */
        .sidebar a, .menu-item, .menu-item * {
            pointer-events: auto !important;
        }
        
        /* Đảm bảo loading trang không chặn điều hướng */
        .page-loading {
            pointer-events: none !important;
        }
        
        /* Giảm kích thước chữ cho tất cả các bảng trong admin - Áp dụng cho tất cả trang */
        .table-container table,
        .table-responsive table,
        table.dataTable,
        .content-area table,
        table.table {
            font-size: 0.875rem !important;
        }
        
        .table-container table thead th,
        .table-responsive table thead th,
        table.dataTable thead th,
        .content-area table thead th,
        table.table thead th {
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            padding: 0.5rem 0.75rem !important;
        }
        
        .table-container table tbody td,
        .table-responsive table tbody td,
        table.dataTable tbody td,
        .content-area table tbody td,
        table.table tbody td {
            font-size: 0.85rem !important;
            padding: 0.5rem 0.75rem !important;
        }
        
        .table-container table .btn-sm,
        .table-responsive table .btn-sm,
        table.dataTable .btn-sm,
        .content-area table .btn-sm,
        table.table .btn-sm {
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
        }
        
        .table-container table .badge,
        .table-responsive table .badge,
        table.dataTable .badge,
        .content-area table .badge,
        table.table .badge {
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
        }
        
        .table-container table .status-badge,
        .table-responsive table .status-badge,
        table.dataTable .status-badge,
        .content-area table .status-badge,
        table.table .status-badge {
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
        }
        
        /* Áp dụng cho các bảng không dùng DataTables */
        .table {
            font-size: 0.875rem !important;
        }
        
        .table thead th {
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            padding: 0.5rem 0.75rem !important;
        }
        
        .table tbody td {
            font-size: 0.85rem !important;
            padding: 0.5rem 0.75rem !important;
        }
        
        .table .btn-sm {
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
        }
        
        .table .badge {
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
        }
        
        .table .status-badge {
            font-size: 0.75rem !important;
            padding: 0.25rem 0.5rem !important;
        }
    </style>
</head>
<body>
    <!-- Page Loading Overlay -->
    <div class="page-loading" id="pageLoading">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Đang tải...</span>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <h4>Admin Panel</h4>
        </div>
        
        <nav class="sidebar-menu">
            <!-- Dashboard - Tất cả role -->
            <a href="index.php" class="menu-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <!-- Role 1: Admin - Tất cả quyền -->
            <?php if ($user['ID_Role'] == 1): ?>
            <a href="event-registrations.php" class="menu-item <?= $currentPage === 'event-registrations' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Duyệt đăng ký sự kiện</span>
            </a>
            
            <a href="event-planning.php" class="menu-item <?= $currentPage === 'event-planning' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Lên kế hoạch thực hiện và phân công</span>
            </a>
            
            <a href="event-types.php" class="menu-item <?= $currentPage === 'event-types' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i>
                <span>Quản lý loại sự kiện</span>
            </a>
            
            <a href="locations.php" class="menu-item <?= $currentPage === 'locations' ? 'active' : '' ?>">
                <i class="fas fa-map-marker-alt"></i>
                <span>Quản lý địa điểm</span>
            </a>
            
            <a href="rooms.php" class="menu-item <?= $currentPage === 'rooms' ? 'active' : '' ?>">
                <i class="fas fa-door-open"></i>
                <span>Quản lý phòng</span>
            </a>
            
            <a href="accstaff.php" class="menu-item <?= $currentPage === 'accstaff' ? 'active' : '' ?>">
                <i class="fas fa-user-tie"></i>
                <span>Quản lý nhân viên</span>
            </a>
            
            <a href="device.php" class="menu-item <?= $currentPage === 'device' ? 'active' : '' ?>">
                <i class="fas fa-tools"></i>
                <span>Quản lý thiết bị</span>
            </a>
            
            <a href="payment-management.php" class="menu-item <?= $currentPage === 'payment-management' ? 'active' : '' ?>">
                <i class="fas fa-credit-card"></i>
                <span>Quản lý thanh toán</span>
            </a>
            
            <a href="customeredit_content.php" class="menu-item <?= $currentPage === 'customeredit_content' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Quản lý khách hàng</span>
            </a>
            
            <a href="reports.php" class="menu-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Thống kê báo cáo</span>
            </a>
            <?php endif; ?>
            
            <!-- Role 2: Quản lý tổ chức -->
            <?php if ($user['ID_Role'] == 2): ?>
            <a href="event-registrations.php" class="menu-item <?= $currentPage === 'event-registrations' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Duyệt đăng ký sự kiện</span>
            </a>
            
            <a href="event-planning.php" class="menu-item <?= $currentPage === 'event-planning' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Lên kế hoạch thực hiện và phân công</span>
            </a>
            
            <a href="event-types.php" class="menu-item <?= $currentPage === 'event-types' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i>
                <span>Quản lý loại sự kiện</span>
            </a>
            
            <a href="locations.php" class="menu-item <?= $currentPage === 'locations' ? 'active' : '' ?>">
                <i class="fas fa-map-marker-alt"></i>
                <span>Quản lý địa điểm</span>
            </a>
            
            <a href="rooms.php" class="menu-item <?= $currentPage === 'rooms' ? 'active' : '' ?>">
                <i class="fas fa-door-open"></i>
                <span>Quản lý phòng</span>
            </a>
            
            <a href="accstaff.php" class="menu-item <?= $currentPage === 'accstaff' ? 'active' : '' ?>">
                <i class="fas fa-user-tie"></i>
                <span>Quản lý nhân viên</span>
            </a>
            
            <a href="device.php" class="menu-item <?= $currentPage === 'device' ? 'active' : '' ?>">
                <i class="fas fa-tools"></i>
                <span>Quản lý thiết bị</span>
            </a>
            
            <a href="payment-management.php" class="menu-item <?= $currentPage === 'payment-management' ? 'active' : '' ?>">
                <i class="fas fa-credit-card"></i>
                <span>Quản lý thanh toán</span>
            </a>
            
            <a href="customeredit_content.php" class="menu-item <?= $currentPage === 'customeredit_content' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Quản lý khách hàng</span>
            </a>
            
            <a href="reports.php" class="menu-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Thống kê báo cáo</span>
            </a>
            
            <a href="manager-reports.php" class="menu-item <?= $currentPage === 'manager-reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Báo cáo từ nhân viên</span>
            </a>
            <?php endif; ?>
            
            <!-- Role 3: Quản lý sự kiện -->
            <?php if ($user['ID_Role'] == 3): ?>
            <a href="event-registration.php" class="menu-item <?= $currentPage === 'event-registration' ? 'active' : '' ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Đăng ký sự kiện cho khách hàng</span>
            </a>
            
            <a href="event-registrations.php" class="menu-item <?= $currentPage === 'event-registrations' ? 'active' : '' ?>">
                <i class="fas fa-eye"></i>
                <span>Xem duyệt sự kiện</span>
            </a>
            
            <a href="reports.php" class="menu-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Thống kê báo cáo</span>
            </a>
            <?php endif; ?>
            
            <!-- Chat Hỗ trợ - Tất cả role admin/staff -->
            <?php if (in_array($user['ID_Role'], [1, 2, 3, 4])): ?>
            <a href="chat.php" class="menu-item <?= $currentPage === 'chat' ? 'active' : '' ?>">
                <i class="fas fa-comments"></i>
                <span>Chat Hỗ trợ Khách hàng</span>
                <span class="chat-badge" id="chatBadge" style="display: none;">0</span>
            </a>
            <?php endif; ?>
            
            <!-- Role 4: Nhân viên -->
            <?php if ($user['ID_Role'] == 4): ?>
            <a href="staff-schedule.php" class="menu-item <?= $currentPage === 'staff-schedule' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Lịch làm việc</span>
            </a>
            
            <a href="staff-reports.php" class="menu-item <?= $currentPage === 'staff-reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Báo cáo tiến độ</span>
            </a>
            <?php endif; ?>
            
            <hr style="border-color: rgba(255,255,255,0.2); margin: 1rem 0;">
            
            <a href="../logout.php" class="menu-item" onclick="return confirmLogout()">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="d-flex align-items-center">
                <button class="toggle-btn" id="sidebarToggle" title="Thu gọn/ Mở rộng menu">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="mobile-toggle-btn d-md-none ms-2" id="mobileToggle" title="Mở menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0 ms-3 text-gradient"><?= $pageTitle ?></h5>
            </div>
            
            <div class="user-info">
                <div class="d-flex align-items-center">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['Email'] ?? 'A', 0, 1)) ?>
                    </div>
                    <div class="ms-2">
                        <div class="fw-bold"><?= htmlspecialchars($user['Email'] ?? 'Admin') ?></div>
                        <small class="text-muted">
                            <?php
                            $roleNames = [
                                1 => 'Quản trị viên',
                                2 => 'Quản lý tổ chức',
                                3 => 'Quản lý sự kiện',
                                4 => 'Nhân viên'
                            ];
                            echo $roleNames[$user['ID_Role']] ?? 'Người dùng';
                            ?>
                        </small>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Messages Container -->
            <div class="error-message"></div>
            <div class="success-message"></div>
            <div class="warning-message"></div>
            <div class="info-message"></div>
