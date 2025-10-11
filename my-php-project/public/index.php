<?php
session_start();

// Lấy thông tin user và role
$user = $_SESSION['user'] ?? null;
$userRole = $user['ID_Role'] ?? $user['role'] ?? null;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - Hệ thống tổ chức sự kiện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../img/logo/logo.jpg">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            z-index: 1000;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .sidebar-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu .menu-item {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: #fff;
            color: white;
        }
        
        .sidebar-menu .menu-item i {
            width: 20px;
            margin-right: 10px;
        }
        
        .sidebar-menu .menu-group {
            margin: 20px 0;
        }
        
        .sidebar-menu .menu-group-title {
            padding: 10px 25px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .sidebar-toggle {
            position: fixed;
            top: 80px; /* đặt thấp xuống dưới navbar */
            left: 25px;
            z-index: 1100;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 55px;
            height: 55px;
            font-size: 1.4rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.4s ease;
        }
        
        .sidebar-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            background: linear-gradient(45deg, #5a6fe0, #8a4dc5);
        }
        
        .sidebar-toggle i {
            transition: transform 0.4s ease;
        }
        
        /* Khi sidebar mở, icon xoay */
        .sidebar.show ~ .sidebar-toggle i,
        .sidebar.show + * .sidebar-toggle i {
            transform: rotate(90deg);
        }
        
        /* Alternative selector for icon rotation */
        body.sidebar-open .sidebar-toggle i {
            transform: rotate(90deg);
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .main-content {
            transition: margin-left 0.3s ease;
        }
        
        .main-content.sidebar-open {
            margin-left: 280px;
        }
        
        /* Role-specific styling */
        .role-admin { border-left: 3px solid #dc3545; }
        .role-manager { border-left: 3px solid #fd7e14; }
        .role-event-manager { border-left: 3px solid #20c997; }
        .role-staff { border-left: 3px solid #6f42c1; }
        .role-customer { border-left: 3px solid #0dcaf0; }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
            }
            
            .main-content.sidebar-open {
                margin-left: 0;
            }
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .banner-carousel {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
        }
        
        .banner-slide {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 2s ease-in-out;
        }
        
        .banner-slide.active {
            opacity: 0.3;
        }
        
        .banner-slide:nth-child(1) {
            background-image: url('../img/banner/banner1.jpg');
        }
        
        .banner-slide:nth-child(2) {
            background-image: url('../img/banner/banner2.jpg');
        }
        
        .banner-slide:nth-child(3) {
            background-image: url('../img/banner/banner3.jpeg');
        }
        
        .banner-indicators {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 3;
        }
        
        .banner-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .banner-dot.active {
            background: white;
            transform: scale(1.2);
        }
        
        .hero-image-container {
            position: relative;
            display: inline-block;
            width: 100%;
            height: 400px;
            overflow: hidden;
            border-radius: 15px;
        }
        
        .hero-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 2s ease-in-out;
            border-radius: 15px;
        }
        
        .hero-image.active {
            opacity: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .navbar-brand img {
            height: 40px;
            width: auto;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 25px;
            padding: 10px 28px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }
        
        .banner-section {
            background: url('../img/banner/banner2.jpg') center/cover;
            padding: 80px 0;
            color: white;
            position: relative;
        }
        
        .banner-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
        }
        
        .banner-content {
            position: relative;
            z-index: 2;
        }
        
        .stats-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 60px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 50px 0 20px;
        }
        
        .footer a {
            color: #ecf0f1;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer a:hover {
            color: #3498db;
        }
        
        .footer-logo {
            border-radius: 5px;
        }
        
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        
        .floating-btn:hover {
            transform: scale(1.1);
            color: white;
        }
        
        /* Chat Widget Styles */
        .chat-widget {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 1000;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            display: none;
        }
        
        .chat-widget.show {
            transform: translateY(0);
            display: block;
        }
        
        .chat-widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-widget-body {
            height: 350px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .chat-widget-footer {
            padding: 15px;
            border-top: 1px solid #dee2e6;
            background: white;
            border-radius: 0 0 15px 15px;
        }
        
        .chat-message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .chat-message.sent {
            justify-content: flex-end;
        }
        
        .chat-message.received {
            justify-content: flex-start;
        }
        
        .message-bubble {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .message-bubble.sent {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .message-bubble.received {
            background: white;
            color: #333;
            border: 1px solid #dee2e6;
            border-bottom-left-radius: 5px;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .chat-input-group {
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 8px 15px;
            outline: none;
            font-size: 0.9rem;
        }
        
        .chat-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .chat-send-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .chat-send-btn:hover {
            transform: scale(1.1);
        }
        
        .chat-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1001;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 70px;
            height: 70px;
            font-size: 1.8rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5), 
                        0 6px 15px rgba(118, 75, 162, 0.4),
                        0 0 0 0 rgba(102, 126, 234, 0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            backdrop-filter: blur(15px);
            animation: float 3s ease-in-out infinite, pulse-glow 2s ease-in-out infinite;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-toggle:hover {
            transform: scale(1.2) translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.7), 
                        0 10px 25px rgba(118, 75, 162, 0.5),
                        0 0 0 12px rgba(102, 126, 234, 0.15);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 30%, #f093fb 70%, #f5576c 100%);
            animation-play-state: paused;
        }
        
        .chat-toggle:active {
            transform: scale(1.05) translateY(0px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
            animation: ripple 0.6s ease-out;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-8px);
            }
        }
        
        @keyframes ripple {
            0% {
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5),
                            0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            50% {
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5),
                            0 0 0 20px rgba(102, 126, 234, 0.3);
            }
            100% {
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5),
                            0 0 0 40px rgba(102, 126, 234, 0);
            }
        }
        
        .chat-toggle .fa-comments {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @keyframes pulse-glow {
            0% { 
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5), 
                            0 6px 15px rgba(118, 75, 162, 0.4),
                            0 0 0 0 rgba(102, 126, 234, 0.3);
            }
            50% { 
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6), 
                            0 6px 15px rgba(118, 75, 162, 0.5),
                            0 0 0 8px rgba(102, 126, 234, 0.2);
            }
            100% { 
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5), 
                            0 6px 15px rgba(118, 75, 162, 0.4),
                            0 0 0 0 rgba(102, 126, 234, 0.3);
            }
        }
        
        .online-indicator {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 8px;
            animation: blink 1s infinite;
        }
        
        /* Chat Toggle Tooltip */
        .chat-toggle::before {
            content: "Chat với nhân viên hỗ trợ";
            position: absolute;
            right: 80px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1002;
            backdrop-filter: blur(10px);
        }
        
        .chat-toggle::after {
            content: "";
            position: absolute;
            right: 70px;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-left-color: rgba(0, 0, 0, 0.8);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1002;
        }
        
        .chat-toggle:hover::before,
        .chat-toggle:hover::after {
            opacity: 1;
            visibility: visible;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                padding: 0 1rem;
            }
            
            .hero-section h1 {
                font-size: 3rem;
            }
            
            .service-card {
                margin-bottom: 1.5rem;
            }
        }
        
        @media (max-width: 992px) {
            .hero-section {
                padding: 80px 0;
            }
            
            .hero-section h1 {
                font-size: 2.8rem;
            }
            
            .hero-section p {
                font-size: 1.2rem;
            }
            
            .banner-content {
                text-align: center;
            }
            
            .banner-content .col-lg-4 {
                margin-top: 2rem;
            }
            
            .stat-item {
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar-toggle {
                top: 70px;
                left: 15px;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .chat-toggle {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                bottom: 20px;
                right: 20px;
            }
            
            .chat-toggle::before {
                right: 70px;
                font-size: 0.8rem;
                padding: 6px 10px;
            }
            
            .chat-toggle::after {
                right: 60px;
            }
            
            .hero-section {
                padding: 60px 0;
                text-align: center;
            }
            
            .hero-section h1 {
                font-size: 2.5rem;
                line-height: 1.2;
            }
            
            .hero-section p {
                font-size: 1.1rem;
                margin-bottom: 2rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }
            
            .hero-buttons .btn {
                width: 100%;
                max-width: 300px;
                padding: 12px 24px;
            }
            
            .service-card {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .service-icon {
                margin: 0 auto 1rem;
            }
            
            .banner-content {
                text-align: center;
            }
            
            .banner-content .col-lg-4 {
                margin-top: 2rem;
            }
            
            .banner-content ul {
                text-align: left;
            }
            
            .stat-item {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .footer-content {
                text-align: center;
            }
            
            .footer-content .col-md-3 {
                margin-bottom: 2rem;
            }
            
            .social-links {
                justify-content: center;
                margin-top: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .hero-section p {
                font-size: 1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .service-card {
                padding: 1.5rem;
            }
            
            .service-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .stat-item {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .banner-content .bg-white {
                padding: 1.5rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .chat-toggle {
                width: 55px;
                height: 55px;
                font-size: 1.3rem;
                bottom: 15px;
                right: 15px;
            }
            
            .chat-toggle::before {
                right: 65px;
                font-size: 0.75rem;
                padding: 5px 8px;
            }
            
            .chat-toggle::after {
                right: 55px;
            }
            
            .container {
                padding: 0 0.5rem;
            }
            
            .hero-section h1 {
                font-size: 1.8rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .service-card {
                padding: 1rem;
            }
            
            .stat-item {
                padding: 0.8rem;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body class="main-content">
    <!-- Sidebar Toggle Button -->
    <?php if ($user): ?>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <?php endif; ?>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <?php if ($user): ?>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../img/logo/logo.jpg" alt="Logo">
            <h5 class="mb-0"><?php echo htmlspecialchars($user['Email'] ?? 'User'); ?></h5>
            <small class="text-light">
                <?php
                $roleNames = [
                    1 => 'Quản trị viên',
                    2 => 'Quản lý tổ chức', 
                    3 => 'Quản lý sự kiện',
                    4 => 'Nhân viên',
                    5 => 'Khách hàng'
                ];
                echo $roleNames[$userRole] ?? 'Khách hàng';
                ?>
            </small>
        </div>
        
        <div class="sidebar-menu">
            <!-- Dashboard -->
            <div class="menu-group">
                <div class="menu-group-title">Tổng quan</div>
                <a href="#home" class="menu-item" onclick="toggleSidebar()">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
                <a href="profile.php" class="menu-item">
                    <i class="fas fa-user"></i> Thông tin cá nhân
                </a>
            </div>
            
            <?php if (in_array($userRole, [1, 2])): ?>
            <!-- Admin/Manager Functions -->
            <div class="menu-group">
                <div class="menu-group-title">Quản lý</div>
                
                <!-- Event Management -->
                <a href="admin/event-registrations.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i> Duyệt sự kiện
                </a>
                
                <!-- Customer Management -->
                <a href="admin/customeredit_content.php" class="menu-item">
                    <i class="fas fa-users"></i> Quản lý khách hàng
                </a>
                
                <!-- Staff Management -->
                <a href="admin/accstaff.php" class="menu-item">
                    <i class="fas fa-user-tie"></i> Quản lý nhân viên
                </a>
                
                <!-- Device Management -->
                <a href="admin/device.php" class="menu-item">
                    <i class="fas fa-tools"></i> Quản lý thiết bị
                </a>
                
                <!-- Location Management -->
                <a href="admin/locations.php" class="menu-item">
                    <i class="fas fa-map-marker-alt"></i> Quản lý địa điểm
                </a>
                
                <!-- Statistics -->
                <a href="admin/index.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i> Thống kê báo cáo
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($userRole == 3): ?>
            <!-- Event Manager Functions -->
            <div class="menu-group">
                <div class="menu-group-title">Sự kiện</div>
                
                <!-- Event Management -->
                <a href="admin/event-registrations.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i> Xem đăng ký sự kiện
                </a>
                
                <!-- Customer Management -->
                <a href="admin/customeredit_content.php" class="menu-item">
                    <i class="fas fa-users"></i> Quản lý khách hàng
                </a>
                
                <!-- Location Management -->
                <a href="admin/locations.php" class="menu-item">
                    <i class="fas fa-map-marker-alt"></i> Quản lý địa điểm
                </a>
                
                <!-- Statistics -->
                <a href="admin/index.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i> Thống kê báo cáo
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($userRole == 3): ?>
            <!-- Event Manager Additional Functions -->
            <div class="menu-group">
                <div class="menu-group-title">Đăng ký sự kiện</div>
                <a href="admin/event-manager.php" class="menu-item">
                    <i class="fas fa-calendar-plus"></i> Đăng ký thay mặt
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (in_array($userRole, [1, 2, 3, 4, 5])): ?>
            <!-- Chat System -->
            <div class="menu-group">
                <div class="menu-group-title">Hỗ trợ</div>
                <a href="chat.php" class="menu-item">
                    <i class="fas fa-comments"></i> Chat trực tuyến
                </a>
                <!-- <?php if (in_array($userRole, [1, 2, 3, 4])): ?>
                <a href="admin/chat-support.php" class="menu-item">
                    <i class="fas fa-headset"></i> Chat hỗ trợ
                </a>
                <?php endif; ?> -->
            </div>
            <?php endif; ?>
            
            <?php if ($userRole == 5): ?>
            <!-- Customer Functions -->
            <div class="menu-group">
                <div class="menu-group-title">Sự kiện</div>
                <a href="events/register.php" class="menu-item">
                    <i class="fas fa-calendar-plus"></i> Đăng ký sự kiện
                </a>
                <a href="events/my-events.php" class="menu-item">
                    <i class="fas fa-list-alt"></i> Sự kiện của tôi
                </a>
            </div>
            
            <div class="menu-group">
                <div class="menu-group-title">Hỗ trợ</div>
                <a href="chat.php" class="menu-item">
                    <i class="fas fa-comments"></i> Chat trực tuyến
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Account -->
            <div class="menu-group">
                <div class="menu-group-title">Tài khoản</div>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="../img/logo/logo.jpg" alt="Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Dịch vụ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Giới thiệu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Liên hệ</a>
                    </li>
                    <?php if ($user): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="events/register.php">
                            <i class="fa fa-calendar-plus me-1"></i>Đăng ký sự kiện
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events/my-events.php">
                            <i class="fa fa-list-alt me-1"></i>Sự kiện của tôi
                        </a>
                    </li>
                    <?php if (in_array($userRole, [1, 2, 3, 4])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/event-registrations.php">
                            <i class="fa fa-cog me-1"></i>Quản lý sự kiện
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <div class="d-flex gap-2">
                    <?php if ($user): ?>
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fa fa-user me-1"></i> Tài khoản
                        </a>
                        <a href="logout.php" class="btn btn-primary">
                            <i class="fa fa-sign-out-alt me-1"></i> Đăng xuất
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-primary">
                            <i class="fa fa-sign-in-alt me-1"></i> Đăng nhập
                        </a>
                        <a href="register.php" class="btn btn-primary">
                            <i class="fa fa-user-plus me-1"></i> Đăng ký
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <!-- Animated Banner Carousel -->
        <div class="banner-carousel">
            <div class="banner-slide active"></div>
            <div class="banner-slide"></div>
            <div class="banner-slide"></div>
        </div>
        
        <!-- Banner Indicators -->
        <div class="banner-indicators">
            <div class="banner-dot active" data-slide="0"></div>
            <div class="banner-dot" data-slide="1"></div>
            <div class="banner-dot" data-slide="2"></div>
        </div>
        
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="display-4 fw-bold mb-4">Tổ chức sự kiện chuyên nghiệp</h1>
                    <p class="lead mb-4">Chúng tôi cung cấp dịch vụ tổ chức sự kiện hoàn hảo với đội ngũ chuyên nghiệp và trang thiết bị hiện đại.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#services" class="btn btn-primary btn-lg">
                            <i class="fa fa-calendar-alt me-2"></i>Xem dịch vụ
                        </a>
                        <?php if (!$user): ?>
                        <a href="register.php" class="btn btn-outline-light btn-lg">
                            <i class="fa fa-user-plus me-2"></i>Đăng ký ngay
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-image-container">
                        <img src="../img/banner/banner1.jpg" alt="Event Planning" class="img-fluid rounded-3 shadow-lg hero-image active" data-banner="0">
                        <img src="../img/banner/banner2.jpg" alt="Event Planning" class="img-fluid rounded-3 shadow-lg hero-image" data-banner="1">
                        <img src="../img/banner/banner3.jpeg" alt="Event Planning" class="img-fluid rounded-3 shadow-lg hero-image" data-banner="2">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Dịch vụ của chúng tôi</h2>
                <p class="lead text-muted">Cung cấp giải pháp tổ chức sự kiện toàn diện</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-birthday-cake"></i>
                        </div>
                        <h4>Tiệc sinh nhật</h4>
                        <p>Tổ chức tiệc sinh nhật đáng nhớ với không gian ấm cúng và dịch vụ chuyên nghiệp.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-heart"></i>
                        </div>
                        <h4>Đám cưới</h4>
                        <p>Làm cho ngày cưới của bạn trở nên hoàn hảo với dịch vụ tổ chức đám cưới cao cấp.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-briefcase"></i>
                        </div>
                        <h4>Sự kiện doanh nghiệp</h4>
                        <p>Tổ chức hội nghị, hội thảo và các sự kiện doanh nghiệp chuyên nghiệp.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-graduation-cap"></i>
                        </div>
                        <h4>Lễ tốt nghiệp</h4>
                        <p>Kỷ niệm thành tích học tập với lễ tốt nghiệp trang trọng và ý nghĩa.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-music"></i>
                        </div>
                        <h4>Concert & Show</h4>
                        <p>Tổ chức các buổi biểu diễn, concert với hệ thống âm thanh ánh sáng chuyên nghiệp.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-calendar-check"></i>
                        </div>
                        <h4>Sự kiện tùy chỉnh</h4>
                        <p>Thiết kế và tổ chức sự kiện theo yêu cầu riêng của khách hàng.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Banner Section -->
    <section class="banner-section">
        <div class="container">
            <div class="row align-items-center banner-content">
                <div class="col-lg-8">
                    <h2 class="display-5 fw-bold mb-4">Tại sao chọn chúng tôi?</h2>
                    <ul class="list-unstyled">
                        <li class="mb-3"><i class="fa fa-check-circle me-2"></i> Đội ngũ chuyên nghiệp với nhiều năm kinh nghiệm</li>
                        <li class="mb-3"><i class="fa fa-check-circle me-2"></i> Trang thiết bị hiện đại, chất lượng cao</li>
                        <li class="mb-3"><i class="fa fa-check-circle me-2"></i> Dịch vụ khách hàng 24/7</li>
                        <li class="mb-3"><i class="fa fa-check-circle me-2"></i> Giá cả cạnh tranh, minh bạch</li>
                        <li class="mb-3"><i class="fa fa-check-circle me-2"></i> Cam kết chất lượng 100%</li>
                    </ul>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="bg-white rounded-3 p-4 shadow">
                        <h4 class="text-dark mb-3">Đặt dịch vụ ngay</h4>
                        <?php if ($user): ?>
                            <a href="events/register.php" class="btn btn-primary btn-lg w-100">
                                <i class="fa fa-calendar-plus me-2"></i>Đăng ký sự kiện
                            </a>
                        <?php else: ?>
                            <p class="text-muted mb-3">Vui lòng đăng ký để đặt dịch vụ</p>
                            <a href="register.php" class="btn btn-primary btn-lg w-100">
                                <i class="fa fa-user-plus me-2"></i>Đăng ký ngay
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">500</div>
                        <div>Sự kiện đã tổ chức</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">1000</div>
                        <div>Khách hàng hài lòng</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">5</div>
                        <div>Năm kinh nghiệm</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div>Hỗ trợ khách hàng</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><img src="../img/logo/logo.jpg" alt="Logo" height="30" class="me-2 footer-logo">Event Management</h5>
                    <p>Chúng tôi cam kết mang đến những sự kiện hoàn hảo và đáng nhớ cho khách hàng.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Dịch vụ</h6>
                    <ul class="list-unstyled">
                        <li><a href="#">Tiệc sinh nhật</a></li>
                        <li><a href="#">Đám cưới</a></li>
                        <li><a href="#">Sự kiện doanh nghiệp</a></li>
                        <li><a href="#">Lễ tốt nghiệp</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Hỗ trợ</h6>
                    <ul class="list-unstyled">
                        <li><a href="#">Liên hệ</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Chính sách</a></li>
                        <li><a href="#">Điều khoản</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6>Liên hệ</h6>
                    <p><i class="fa fa-phone me-2"></i> 0123 456 789</p>
                    <p><i class="fa fa-envelope me-2"></i> info@eventmanagement.com</p>
                    <p><i class="fa fa-map-marker-alt me-2"></i> 123 Đường ABC, Quận 1, TP.HCM</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2025 Event Management. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Chat Widget -->
    <?php if ($user && in_array($userRole, [1, 2, 3, 4, 5])): ?>
    <?php include 'chat-widget.php'; ?>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Socket.IO with fallback -->
    <script>
        // Try to load Socket.IO from local server first
        const socketScript = document.createElement('script');
        socketScript.src = 'http://localhost:3000/socket.io/socket.io.js';
        socketScript.onerror = function() {
            console.warn('Local Socket.IO server not available, using CDN fallback');
            const cdnScript = document.createElement('script');
            cdnScript.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
            cdnScript.onload = function() {
                console.log('Socket.IO loaded from CDN');
            };
            cdnScript.onerror = function() {
                console.error('Failed to load Socket.IO from both local server and CDN');
            };
            document.head.appendChild(cdnScript);
        };
        socketScript.onload = function() {
            console.log('Socket.IO loaded from local server');
        };
        document.head.appendChild(socketScript);
    </script>
    <script>
        // Sidebar Toggle Functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const mainContent = document.querySelector('.main-content');
            const body = document.body;
            
            if (sidebar && overlay && mainContent) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                mainContent.classList.toggle('sidebar-open');
                body.classList.toggle('sidebar-open');
            }
        }
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (sidebar && sidebar.classList.contains('show')) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    toggleSidebar();
                }
            }
        });
        
        // Close sidebar on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar && sidebar.classList.contains('show')) {
                    toggleSidebar();
                }
            }
        });
        
        // Banner Carousel Functionality
        class BannerCarousel {
            constructor() {
                this.slides = document.querySelectorAll('.banner-slide');
                this.dots = document.querySelectorAll('.banner-dot');
                this.heroImages = document.querySelectorAll('.hero-image');
                this.currentSlide = 0;
                this.slideInterval = null;
                this.init();
            }

            init() {
                this.startAutoSlide();
                this.addDotListeners();
                this.addPauseOnHover();
            }

            startAutoSlide() {
                this.slideInterval = setInterval(() => {
                    this.nextSlide();
                }, 4000); // Change slide every 4 seconds
            }

            nextSlide() {
                this.currentSlide = (this.currentSlide + 1) % this.slides.length;
                this.updateSlide();
            }

            goToSlide(slideIndex) {
                this.currentSlide = slideIndex;
                this.updateSlide();
            }

            updateSlide() {
                // Remove active class from all slides, dots, and hero images
                this.slides.forEach(slide => slide.classList.remove('active'));
                this.dots.forEach(dot => dot.classList.remove('active'));
                this.heroImages.forEach(image => image.classList.remove('active'));

                // Add active class to current slide, dot, and hero image
                this.slides[this.currentSlide].classList.add('active');
                this.dots[this.currentSlide].classList.add('active');
                this.heroImages[this.currentSlide].classList.add('active');
            }

            addDotListeners() {
                this.dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        this.goToSlide(index);
                        this.resetAutoSlide();
                    });
                });
            }

            addPauseOnHover() {
                const heroSection = document.querySelector('.hero-section');
                heroSection.addEventListener('mouseenter', () => {
                    clearInterval(this.slideInterval);
                });
                heroSection.addEventListener('mouseleave', () => {
                    this.startAutoSlide();
                });
            }

            resetAutoSlide() {
                clearInterval(this.slideInterval);
                this.startAutoSlide();
            }
        }

        // Initialize banner carousel when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            new BannerCarousel();
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });
        
        // Chat Widget is now handled by chat-widget.php
        
        // Auto-hide chat widget after 30 seconds of inactivity
        let chatInactivityTimer;
        function resetChatTimer() {
            clearTimeout(chatInactivityTimer);
            chatInactivityTimer = setTimeout(() => {
                const chatWidget = document.getElementById('chatWidget');
                const chatToggle = document.querySelector('.chat-toggle');
                if (chatWidget && chatWidget.classList.contains('show')) {
                    chatWidget.classList.remove('show');
                    if (chatToggle) chatToggle.style.display = 'block';
                }
            }, 30000);
        }
        
        // Reset timer on any interaction
        document.addEventListener('click', resetChatTimer);
        document.addEventListener('keypress', resetChatTimer);
    </script>
    
    
    <script>
        // Ensure jQuery is loaded before chat widget
        $(document).ready(function() {
            console.log('jQuery loaded successfully');
        });
    </script>
</body>
</html>
