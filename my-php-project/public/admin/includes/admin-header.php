<?php
// Admin Header Template
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has admin access
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], [1, 2, 3, 4])) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $_SESSION['user'];
$isAdmin = $user['role'] == 1;
$isManager = in_array($user['role'], [2, 3]);
$isStaff = $user['role'] == 4;

// Get current page name
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Page titles mapping
$pageTitles = [
    'index' => 'Dashboard',
    'event-registrations' => 'Quản lý đăng ký sự kiện',
    'locations' => 'Quản lý địa điểm',
    'device' => 'Quản lý thiết bị',
    'customeredit_content' => 'Quản lý khách hàng',
    'accstaff' => 'Quản lý nhân viên',
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
    
    <!-- Favicon -->
    <link rel="icon" href="../img/logo/logo.jpg">
    
    <style>
        /* Page-specific styles can be added here */
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
        }
        
        .page-loading .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--primary-color);
        }
        
        /* Chat Badge */
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
            <a href="index.php" class="menu-item <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <?php if ($isAdmin || $isManager): ?>
            <a href="event-registrations.php" class="menu-item <?= $currentPage === 'event-registrations' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Đăng ký sự kiện</span>
            </a>
            
            <a href="locations.php" class="menu-item <?= $currentPage === 'locations' ? 'active' : '' ?>">
                <i class="fas fa-map-marker-alt"></i>
                <span>Địa điểm</span>
            </a>
            
            <a href="device.php" class="menu-item <?= $currentPage === 'device' ? 'active' : '' ?>">
                <i class="fas fa-tools"></i>
                <span>Thiết bị</span>
            </a>
            
            <a href="customeredit_content.php" class="menu-item <?= $currentPage === 'customeredit_content' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Khách hàng</span>
            </a>
            
            <a href="accstaff.php" class="menu-item <?= $currentPage === 'accstaff' ? 'active' : '' ?>">
                <i class="fas fa-user-tie"></i>
                <span>Nhân viên</span>
            </a>
            <?php endif; ?>
            
            <!-- Chat Support -->
            <?php if (in_array($user['role'], [1, 3])): ?>
            <a href="chat.php" class="menu-item <?= $currentPage === 'chat' ? 'active' : '' ?>">
                <i class="fas fa-comments"></i>
                <span>Chat Hỗ trợ</span>
                <span class="chat-badge" id="chatBadge" style="display: none;">0</span>
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
                <button class="toggle-btn" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <button class="toggle-btn d-md-none ms-2" id="mobileToggle">
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
                            echo $roleNames[$user['role']] ?? 'Người dùng';
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
