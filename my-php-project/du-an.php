<?php
session_start();

// Lấy thông tin user và role
$user = $_SESSION['user'] ?? null;
$userRole = $user['ID_Role'] ?? $user['role'] ?? null;
$currentUserId = $user['ID_User'] ?? $user['id'] ?? $_SESSION['user_id'] ?? 0;
$currentUserName = $user['HoTen'] ?? $user['name'] ?? $_SESSION['user_name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dự án - Hệ thống tổ chức sự kiện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="img/logo/logo.jpg">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 0;
            background: #f8f9fa;
        }
        
        /* Navbar Styles từ index.php */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            padding: 0.5rem 2rem;
        }
        
        .navbar .container-fluid {
            padding: 0 1rem;
        }
        
        .navbar .d-flex.gap-1 {
            margin-left: 1rem;
            margin-right: 0;
        }
        
        .navbar .btn {
            padding: 0.4rem 0.8rem !important;
            font-size: 0.9rem;
        }
        
        .navbar .navbar-event-btn {
            padding: 8px 18px !important;
            font-size: 0.9rem;
        }
        
        @media (min-width: 992px) {
            .navbar .container-fluid {
                padding-right: 0.5rem;
            }
            
            .navbar .d-flex.gap-1 {
                margin-left: 1rem;
                margin-right: 0;
            }
        }
        
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98) !important;
            box-shadow: 0 4px 30px rgba(0,0,0,0.15);
        }
        
        .navbar-nav .nav-link {
            color: #333 !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .navbar-nav .nav-link:hover {
            color: #667eea !important;
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }
        
        .navbar-nav .nav-link.active {
            color: #667eea !important;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .navbar-nav .nav-link i {
            margin-right: 0.5rem;
            font-size: 0.9rem;
            width: 16px;
            text-align: center;
        }
        
        .navbar-nav .dropdown-toggle::after {
            margin-left: 0.5rem;
        }
        
        /* ✅ Nút Sự kiện nổi bật bên phải navbar - Màu sáng dịu nhẹ */
        .navbar-event-btn {
            background: linear-gradient(135deg, #c5d9f0 0%, #d5c9ed 50%, #e5c9ea 100%);
            color: #5a5a5a !important;
            border: 2px solid rgba(197, 217, 240, 0.5);
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(197, 217, 240, 0.25);
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            margin-right: 10px;
        }
        
        .navbar-event-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }
        
        .navbar-event-btn:hover::before {
            left: 100%;
        }
        
        .navbar-event-btn:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 6px 20px rgba(197, 217, 240, 0.35);
            background: linear-gradient(135deg, #d5e5f5 0%, #e5d9f2 50%, #f5d9ef 100%);
            border-color: rgba(197, 217, 240, 0.8);
        }
        
        .navbar-event-btn:active {
            transform: translateY(0) scale(0.98);
        }
        
        .navbar-event-btn i {
            font-size: 1.2rem;
            color: #667eea;
            animation: bounce-icon-nav 2s ease-in-out infinite;
        }
        
        @keyframes bounce-icon-nav {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-2px);
            }
        }
        
        /* ✅ Dropdown menu cho nút Sự kiện - Màu sáng dịu nhẹ */
        .navbar-event-dropdown {
            min-width: 250px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(197, 217, 240, 0.2);
            border: 1px solid rgba(197, 217, 240, 0.4);
            margin-top: 10px !important;
            background: white;
            padding: 8px;
        }
        
        .navbar-event-dropdown .dropdown-item {
            padding: 12px 20px;
            font-weight: 500;
            border-radius: 10px;
            margin: 4px 0;
            transition: all 0.3s ease;
            color: #5a5a5a;
            border: 1px solid transparent;
        }
        
        .navbar-event-dropdown .dropdown-item:hover {
            background: linear-gradient(135deg, #e8f2fa 0%, #f0e8f7 100%);
            color: #667eea;
            transform: translateX(5px);
            border-color: rgba(197, 217, 240, 0.5);
        }
        
        .navbar-event-dropdown .dropdown-item:first-child {
            background: linear-gradient(135deg, #d5e5f5 0%, #e5d9f2 100%);
            color: #667eea;
            font-weight: 600;
            border-color: rgba(197, 217, 240, 0.6);
        }
        
        .navbar-event-dropdown .dropdown-item:first-child:hover {
            background: linear-gradient(135deg, #e5eff8 0%, #f0e8f7 100%);
            transform: translateX(5px) scale(1.02);
            border-color: rgba(102, 126, 234, 0.4);
            box-shadow: 0 4px 12px rgba(197, 217, 240, 0.3);
        }
        
        .navbar-event-dropdown .dropdown-item i {
            color: #667eea;
            width: 20px;
            text-align: center;
        }
        
        .navbar-event-dropdown .dropdown-divider {
            margin: 8px 0;
            border-color: rgba(197, 217, 240, 0.3);
        }
        
        .navbar-brand img {
            height: 40px;
            width: auto;
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover img {
            transform: scale(1.05);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 20px;
            padding: 6px 18px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            border-color: #667eea;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-radius: 12px;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            color: #333;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #e9ecef;
        }
        
        /* Navbar scroll effect */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.5rem 1rem;
            }
            
            .navbar .container-fluid {
                padding: 0 0.5rem;
            }
            
            .navbar-nav {
                text-align: center;
                padding: 1rem 0;
            }
            
            .navbar-nav .nav-link {
                padding: 0.75rem 1rem !important;
                margin: 0.25rem 0;
                justify-content: center;
            }
            
            .navbar-event-btn {
                margin-right: 0;
                margin-bottom: 10px;
                width: 100%;
                justify-content: center;
            }
            
            .dropdown-menu {
                position: static !important;
                transform: none !important;
                box-shadow: none;
                border: 1px solid #e9ecef;
                margin-top: 0;
            }
            
            .dropdown-item {
                padding: 0.5rem 1rem;
                text-align: center;
                justify-content: center;
            }
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
            margin-top: 70px;
        }
        
        .event-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }
        
        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .event-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .event-card:hover .event-image {
            transform: scale(1.05);
        }
        
        .event-content {
            padding: 1.5rem;
        }
        
        .event-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .event-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .event-meta {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        .event-meta i {
            margin-right: 0.5rem;
            color: #667eea;
        }
        
        .event-location {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        .event-location i {
            margin-right: 0.5rem;
            color: #667eea;
        }
        
        .event-budget {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #28a745;
            font-weight: 600;
        }
        
        .event-budget i {
            margin-right: 0.5rem;
        }
        
        .event-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-event-detail {
            flex: 1;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-event-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-event-register {
            flex: 1;
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-event-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .event-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .event-status.status-upcoming {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }
        
        .event-status.status-ongoing {
            background: linear-gradient(45deg, #ffc107, #e0a800);
            animation: pulse 2s infinite;
        }
        
        .event-status.status-completed {
            background: linear-gradient(45deg, #6c757d, #495057);
        }
        
        .event-status.status-default {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .no-events {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
        }
        
        .no-events i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        /* Footer giống index.php */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 50px 0 20px;
            margin-top: 60px;
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="img/logo/logo.jpg" alt="Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Trang chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">
                            <i class="fas fa-concierge-bell me-1"></i>Dịch vụ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="du-an.php">
                            <i class="fas fa-project-diagram me-1"></i>Dự án
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">
                            <i class="fas fa-blog me-1"></i>Blog
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">
                            <i class="fas fa-info-circle me-1"></i>Giới thiệu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="fas fa-phone me-1"></i>Liên hệ
                        </a>
                    </li>
                    <?php if ($user): ?>
                    <!-- Chức năng dành cho người dùng đã đăng nhập -->
                    <li class="nav-item">
                        <a class="nav-link" href="chat.php">
                            <i class="fas fa-comments me-1"></i>Chat hỗ trợ
                        </a>
                    </li>
                    <!-- ✅ Giỏ mã giảm giá trong menu -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="#" onclick="openDiscountCartModal(); return false;" title="Mã giảm giá đã lưu">
                            <i class="fas fa-ticket-alt me-1"></i>Mã giảm giá
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                  id="discountCartBadge" style="display: none; font-size: 0.65rem; padding: 0.2em 0.5em; min-width: 18px; text-align: center; line-height: 1.2;">0</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex gap-1 align-items-center">
                    <?php if ($user): ?>
                        <!-- ✅ Nút Sự kiện nổi bật bên phải -->
                        <div class="dropdown">
                            <button class="navbar-event-btn dropdown-toggle" type="button" id="eventsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-calendar-plus"></i>
                                <span>Sự kiện</span>
                            </button>
                            <ul class="dropdown-menu navbar-event-dropdown" aria-labelledby="eventsDropdown">
                                <li><a class="dropdown-item" href="events/register.php">
                                    <i class="fas fa-calendar-plus me-2"></i>Đăng ký sự kiện
                                </a></li>
                                <li><a class="dropdown-item" href="events/my-events.php">
                                    <i class="fas fa-list-alt me-2"></i>Sự kiện của tôi
                                </a></li>
                                <?php if (in_array($userRole, [1, 2, 3, 4])): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/event-registrations.php">
                                    <i class="fas fa-cogs me-2"></i>Quản lý sự kiện
                                </a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
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
    <section class="hero-section">
        <div class="container">
            <div class="text-center">
                <h1 class="display-4 fw-bold mb-3">Dự án sự kiện</h1>
                <p class="lead">Khám phá các sự kiện đang diễn ra, sắp diễn ra và đã hoàn thành</p>
            </div>
        </div>
    </section>

    <!-- Featured Events Section -->
    <section id="featured-events" class="py-5">
        <div class="container">
            <div class="row g-4" id="events-container">
                <!-- Events will be loaded here via JavaScript -->
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Discount Cart Modal Functions
        const isUserLoggedInForDiscount = <?php echo isset($user) && $user ? 'true' : 'false'; ?>;
        
        // Get saved discount codes from localStorage
        function getSavedDiscountCodes() {
            try {
                const saved = localStorage.getItem('savedDiscountCodes');
                return saved ? JSON.parse(saved) : [];
            } catch (e) {
                console.error('Error reading saved discount codes:', e);
                return [];
            }
        }
        
        // Update discount cart badge
        function updateDiscountCartBadge() {
            const savedCodes = getSavedDiscountCodes();
            const badge = document.getElementById('discountCartBadge');
            
            if (badge) {
                if (savedCodes.length > 0) {
                    badge.textContent = savedCodes.length;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
        
        // Open discount cart modal
        function openDiscountCartModal() {
            console.log('openDiscountCartModal called, isUserLoggedIn:', isUserLoggedInForDiscount);
            
            // Check if user is logged in
            if (!isUserLoggedInForDiscount) {
                if (confirm('Bạn cần đăng nhập để xem mã giảm giá đã lưu. Bạn có muốn đăng nhập ngay không?')) {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                }
                return false;
            }
            
            try {
                const modalElement = document.getElementById('discountCartModal');
                if (!modalElement) {
                    console.error('Modal element not found');
                    alert('Không tìm thấy modal mã giảm giá');
                    return false;
                }
                
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                loadDiscountCart();
                return false;
            } catch (error) {
                console.error('Error opening discount cart modal:', error);
                alert('Có lỗi xảy ra khi mở giỏ mã giảm giá');
                return false;
            }
        }
        
        // Load discount cart content
        function loadDiscountCart() {
            const savedCodes = getSavedDiscountCodes();
            const container = $('#discountCartContent');
            
            if (savedCodes.length === 0) {
                container.html(`
                    <div class="text-center py-4">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Chưa có mã giảm giá nào được lưu</p>
                        <p class="text-muted small">Lưu mã giảm giá trên trang chủ để sử dụng khi đăng ký sự kiện</p>
                    </div>
                `);
                return;
            }
            
            // Load full discount code details from API
            $.ajax({
                url: 'src/controllers/magiamgia-controller.php',
                method: 'GET',
                data: {
                    action: 'get_available_codes'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.codes) {
                        // Filter only saved codes
                        const savedCodeDetails = response.codes.filter(code => savedCodes.includes(code.code));
                        
                        let html = '<div class="list-group">';
                        
                        if (savedCodeDetails.length > 0) {
                            savedCodeDetails.forEach(function(code) {
                                const minAmountText = code.min_amount > 0 
                                    ? `Đơn hàng tối thiểu: ${new Intl.NumberFormat('vi-VN').format(code.min_amount)} VNĐ` 
                                    : 'Không có điều kiện tối thiểu';
                                
                                const endDate = new Date(code.end_date);
                                const endDateFormatted = endDate.toLocaleDateString('vi-VN', {
                                    day: '2-digit',
                                    month: '2-digit',
                                    year: 'numeric'
                                });
                                
                                html += `
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-warning text-dark me-2" style="font-size: 0.9rem;">${code.code}</span>
                                                    <h6 class="mb-0">${code.name}</h6>
                                                </div>
                                                <p class="mb-1 text-success fw-bold">${code.display_text}</p>
                                                <p class="mb-1 text-muted small">${code.description || 'Mã giảm giá đặc biệt'}</p>
                                                <div class="small text-muted mb-2">
                                                    <i class="fas fa-info-circle text-warning"></i> ${minAmountText}
                                                </div>
                                                <div class="small text-danger">
                                                    <i class="fas fa-clock"></i> Hết hạn: ${endDateFormatted}
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <button class="btn btn-sm btn-outline-primary" onclick="copyDiscountCode('${code.code}')" title="Sao chép mã">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart('${code.code}')" title="Xóa khỏi giỏ">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            // Some saved codes might not be available anymore
                            savedCodes.forEach(function(code) {
                                html += `
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-warning text-dark me-2">${code}</span>
                                                <span class="text-muted small">Mã này có thể đã hết hạn hoặc không còn hoạt động</span>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart('${code}')" title="Xóa khỏi giỏ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                `;
                            });
                        }
                        
                        html += '</div>';
                        container.html(html);
                    } else {
                        container.html(`
                            <div class="text-center py-4">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <p class="text-muted">Không thể tải thông tin mã giảm giá</p>
                            </div>
                        `);
                    }
                },
                error: function() {
                    container.html(`
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                            <p class="text-muted">Lỗi khi tải thông tin mã giảm giá</p>
                        </div>
                    `);
                }
            });
        }
        
        // Copy discount code to clipboard
        function copyDiscountCode(code) {
            navigator.clipboard.writeText(code).then(function() {
                showNotification('Đã sao chép mã: ' + code, 'success');
            }, function() {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = code;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Đã sao chép mã: ' + code, 'success');
            });
        }
        
        // Remove code from cart
        function removeFromCart(code) {
            let savedCodes = getSavedDiscountCodes();
            savedCodes = savedCodes.filter(c => c !== code);
            localStorage.setItem('savedDiscountCodes', JSON.stringify(savedCodes));
            updateDiscountCartBadge();
            loadDiscountCart();
            showNotification('Đã xóa mã khỏi giỏ', 'info');
        }
        
        // Show notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'info'} alert-dismissible fade show`;
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Update badge on page load
        $(document).ready(function() {
            updateDiscountCartBadge();
            
            // Auto-load discount cart when modal is shown
            $('#discountCartModal').on('show.bs.modal', function() {
                if (!isUserLoggedInForDiscount) {
                    $(this).modal('hide');
                    if (confirm('Bạn cần đăng nhập để xem mã giảm giá đã lưu. Bạn có muốn đăng nhập ngay không?')) {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                    }
                    return false;
                }
                loadDiscountCart();
            });
        });
        
        $(document).ready(function() {
            loadFeaturedEvents();
        });
        
        // Load featured events
        function loadFeaturedEvents() {
            console.log('Loading featured events...');
            
            $.ajax({
                url: 'src/controllers/events.php?action=get_featured_events',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Events API response:', response);
                    
                    if (response.success && response.events && response.events.length > 0) {
                        console.log('Displaying', response.events.length, 'events');
                        displayEvents(response.events);
                    } else {
                        console.log('No events found, displaying no events message');
                        displayNoEvents();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading events:', error);
                    displayNoEvents();
                }
            });
        }
        
        // Display events
        function displayEvents(events) {
            const container = $('#events-container');
            let html = '';
            
            events.forEach(function(event) {
                html += `
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="event-card">
                            <div class="event-status status-${getStatusClass(event.TrangThaiSuKien)}">${event.TrangThaiSuKien}</div>
                            <img src="${event.HinhAnhURL}" alt="${event.TenSuKien}" class="event-image">
                            <div class="event-content">
                                <h3 class="event-title">${event.TenSuKien}</h3>
                                <p class="event-description">${event.MoTa || 'Không có mô tả'}</p>
                                
                                <div class="event-meta">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>${formatDateTime(event.NgayBatDau)} - ${formatDateTime(event.NgayKetThuc)}</span>
                                </div>
                                
                                <div class="event-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>${event.TenDiaDiem}</span>
                                </div>
                                
                                <div class="event-meta">
                                    <i class="fas fa-users"></i>
                                    <span>${event.SoNguoiDuKien} người</span>
                                </div>
                                
                                <div class="event-budget">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>${event.NganSach}</span>
                                </div>
                                
                                <div class="event-actions">
                                    <button class="btn-event-detail" onclick="viewEventDetail(${event.ID_DatLich})">
                                        <i class="fas fa-eye"></i> Chi tiết
                                    </button>
                                    <button class="btn-event-register" onclick="registerForEvent(${event.ID_DatLich})">
                                        <i class="fas fa-calendar-plus"></i> Đăng ký
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.html(html);
        }
        
        // Get status class for styling
        function getStatusClass(status) {
            switch(status) {
                case 'Đang diễn ra':
                    return 'ongoing';
                case 'Sắp diễn ra':
                    return 'upcoming';
                case 'Đã hoàn thành':
                    return 'completed';
                default:
                    return 'default';
            }
        }
        
        // Format date and time
        function formatDateTime(dateTimeString) {
            if (!dateTimeString) return 'N/A';
            return dateTimeString;
        }
        
        // Display no events message
        function displayNoEvents() {
            const container = $('#events-container');
            container.html(`
                <div class="col-12">
                    <div class="no-events">
                        <i class="fas fa-calendar-times"></i>
                        <h4>Chưa có sự kiện nào</h4>
                        <p>Hiện tại chưa có sự kiện nào được duyệt. Vui lòng quay lại sau!</p>
                    </div>
                </div>
            `);
        }
        
        // View event detail
        function viewEventDetail(eventId) {
            window.location.href = `event-detail.php?id=${eventId}`;
        }
        
        // Register for event
        function registerForEvent(eventId) {
            <?php if ($user): ?>
                window.location.href = `events/register.php?event_id=${eventId}`;
            <?php else: ?>
                if (confirm('Bạn cần đăng nhập để đăng ký sự kiện. Bạn có muốn đăng nhập ngay không?')) {
                    window.location.href = 'login.php';
                }
            <?php endif; ?>
        }
    </script>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><img src="img/logo/logo.jpg" alt="Logo" height="30" class="me-2 footer-logo">Event Management</h5>
                    <p>Chúng tôi cam kết mang đến những sự kiện hoàn hảo và đáng nhớ cho khách hàng.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Dịch vụ</h6>
                    <ul class="list-unstyled">
                        <li><a href="services.php">Xem tất cả dịch vụ</a></li>
                        <li><a href="services.php">Tiệc sinh nhật</a></li>
                        <li><a href="services.php">Đám cưới</a></li>
                        <li><a href="services.php">Sự kiện doanh nghiệp</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Hỗ trợ</h6>
                    <ul class="list-unstyled">
                        <li><a href="contact.php">Liên hệ</a></li>
                        <li><a href="about.php">Giới thiệu</a></li>
                        <li><a href="contact.php">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6>Liên hệ</h6>
                    <p><i class="fa fa-phone me-2"></i> 0123 456 789</p>
                    <p><i class="fa fa-envelope me-2"></i> info@eventmanagement.com</p>
                    <p><i class="fa fa-map-marker-alt me-2"></i> 12 NVB, Gò Vấp, TP.HCM</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2025 Event Management. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Discount Cart Modal -->
    <div class="modal fade" id="discountCartModal" tabindex="-1" aria-labelledby="discountCartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: white;">
                    <h5 class="modal-title" id="discountCartModalLabel">
                        <i class="fas fa-ticket-alt"></i> Mã giảm giá đã lưu
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="discountCartContent">
                        <div class="text-center py-4">
                            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Chưa có mã giảm giá nào được lưu</p>
                            <p class="text-muted small">Lưu mã giảm giá trên trang chủ để sử dụng khi đăng ký sự kiện</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <a href="events/register.php" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Đăng ký sự kiện
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

