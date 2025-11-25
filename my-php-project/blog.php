<?php
session_start();

// Lấy thông tin user và role
$user = $_SESSION['user'] ?? null;
$userRole = $user['ID_Role'] ?? $user['role'] ?? null;
$currentUserId = $user['ID_User'] ?? $user['id'] ?? $_SESSION['user_id'] ?? 0;
$currentUserName = $user['HoTen'] ?? $user['name'] ?? $_SESSION['user_name'] ?? 'User';

$typeId = $_GET['type_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài viết - Hệ thống tổ chức sự kiện</title>
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
        }
        
        .navbar-event-dropdown .dropdown-item i {
            color: #667eea;
            width: 20px;
        }
        
        /* Responsive cho nút Sự kiện */
        @media (max-width: 768px) {
            .navbar-event-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
            
            .navbar-event-btn span {
                display: none;
            }
            
            .navbar-event-dropdown {
                min-width: 200px;
            }
        }
        
        .blog-post {
            background: white;
            border-radius: 15px;
            padding: 0;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .blog-post:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .blog-post-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        /* Hình ảnh chi tiết bài viết sát nav */
        .blog-post .blog-post-image[style*="height: 500px"] {
            margin-top: 0 !important;
            display: block;
        }
        
        .blog-post:hover .blog-post-image {
            transform: scale(1.1);
        }
        
        .blog-post-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .blog-post-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .blog-post-meta {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        
        .blog-post-excerpt {
            color: #555;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .blog-post-content {
            color: #555;
            line-height: 1.8;
            margin-bottom: 2rem;
        }
        
        .blog-post-content img {
            max-width: 100%;
            border-radius: 10px;
            margin: 1rem 0;
        }
        
        .blog-post .btn {
            margin-top: auto;
        }
        
        .comments-section {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-top: 1.5rem;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }
        
        .comment-item {
            border-bottom: 1px solid #e9ecef;
            padding: 0.6rem 0;
            transition: background-color 0.3s ease;
        }
        
        .comment-item:hover {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding-left: 8px;
            padding-right: 8px;
        }
        
        .comment-item:last-child {
            border-bottom: none;
        }
        
        .comment-author {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        
        .comment-author i {
            font-size: 0.85rem;
        }
        
        .comment-actions {
            margin-top: 0.3rem;
        }
        
        .comment-actions .btn {
            font-size: 0.8rem;
            padding: 0.2rem 0.6rem;
        }
        
        .comment-content {
            color: #555;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .comment-date {
            font-size: 0.75rem;
            color: #999;
        }
        
        .comment-date i {
            font-size: 0.7rem;
        }
        
        .comment-form {
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        
        .comment-form textarea {
            font-size: 0.9rem;
        }
        
        .comment-form .btn {
            font-size: 0.85rem;
            padding: 0.4rem 1rem;
        }
        
        /* Highlight comment khi scroll đến từ admin */
        .comment-item.highlight-comment {
            background-color: #fff3cd !important;
            border: 2px solid #ffc107 !important;
            border-radius: 8px !important;
            padding: 0.8rem !important;
            animation: highlightPulse 2s ease-in-out;
        }
        
        @keyframes highlightPulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
            }
        }
        
        /* Reply form */
        .comment-item .reply-form {
            margin-top: 0.5rem;
            padding: 0.6rem;
            background-color: white;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .comment-item.reply {
            margin-top: 0.5rem;
            padding-left: 0.8rem;
        }
        
        /* Phân trang comments */
        .pagination {
            margin-top: 1.5rem;
        }
        
        .pagination .page-link {
            color: #667eea;
            border-color: #667eea;
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-color: #667eea;
        }
        
        .pagination .page-link:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .no-posts {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
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
                        <a class="nav-link" href="du-an.php">
                            <i class="fas fa-project-diagram me-1"></i>Dự án
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="blog.php">
                            <i class="fas fa-blog me-1"></i>Bài viết
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

    <!-- Bài viết Header -->
    <section class="hero-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 0; margin-top: 70px; margin-bottom: 40px;">
        <div class="container">
            <div class="text-center">
                <h1 class="display-4 fw-bold mb-3">Bài viết sự kiện</h1>
                <p class="lead">Khám phá các bài viết về tổ chức sự kiện</p>
            </div>
        </div>
    </section>

    <!-- Bài viết Posts Section -->
    <section class="py-0">
        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            <div id="blogPostsContainer">
                <div class="text-center py-5">
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
        
        const postId = <?php echo isset($_GET['post_id']) ? intval($_GET['post_id']) : 'null'; ?>;
        const typeId = <?php echo isset($_GET['type_id']) ? intval($_GET['type_id']) : 'null'; ?>;
        const isUserLoggedIn = <?php echo isset($user) && $user ? 'true' : 'false'; ?>;
        const currentUserId = <?php echo $currentUserId; ?>;
        
        $(document).ready(function() {
            if (postId) {
                // Load single post detail
                loadBlogPostDetail(postId);
            } else if (typeId) {
                // Load posts by event type
                loadBlogPostsByType(typeId);
            } else {
                // Load all blog posts
                loadAllBlogPosts();
            }
        });
        
        function loadAllBlogPosts() {
            $.ajax({
                url: 'src/controllers/blog.php?action=get_all_public',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.posts && response.posts.length > 0) {
                        displayBlogPosts(response.posts);
                    } else {
                        displayNoPosts('Chưa có bài viết nào');
                    }
                },
                error: function() {
                    displayNoPosts('Lỗi khi tải bài viết');
                }
            });
        }
        
        function loadBlogPostsByType(typeId) {
            $.ajax({
                url: 'src/controllers/blog.php?action=get_posts_by_type&type_id=' + typeId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.posts && response.posts.length > 0) {
                        displayBlogPosts(response.posts);
                    } else {
                        displayNoPosts('Chưa có bài viết nào cho loại sự kiện này');
                    }
                },
                error: function() {
                    displayNoPosts('Lỗi khi tải bài viết');
                }
            });
        }
        
        function loadBlogPostDetail(postId) {
            $.ajax({
                url: 'src/controllers/blog.php?action=get_post_details&post_id=' + postId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.post) {
                        displayBlogPostDetail(response.post);
                        // Sau khi load post detail, kiểm tra hash và scroll đến comment
                        setTimeout(function() {
                            scrollToCommentFromHash();
                        }, 800);
                    } else {
                        displayNoPosts('Không tìm thấy bài viết');
                    }
                },
                error: function() {
                    displayNoPosts('Lỗi khi tải bài viết');
                }
            });
        }
        
        function displayBlogPosts(posts) {
            const container = $('#blogPostsContainer');
            let html = '';
            
            posts.forEach(function(post) {
                const postId = post.id || post.ID_BlogPost;
                const title = post.title || post.TieuDe || 'Bài viết';
                const excerpt = post.excerpt || post.NoiDungTomTat || '';
                const createdDate = post.created_at || post.NgayDang || '';
                const author = post.AuthorEmail || post.TenTacGia || 'Admin';
                const views = post.views || 0;
                const imageUrl = post.featured_image || post.HinhAnhDaiDienURL || 'img/logo/default-blog.jpg';
                
                html += `
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="blog-post" style="cursor: pointer;" onclick="viewBlogPost(${postId})">
                            <img src="${imageUrl}" alt="${title}" class="blog-post-image">
                            <div class="blog-post-body">
                                <h3 class="blog-post-title">${title}</h3>
                                <div class="blog-post-meta">
                                    <i class="fas fa-calendar text-primary"></i> ${createdDate} | 
                                    <i class="fas fa-eye text-info"></i> ${views} lượt xem
                                </div>
                                <p class="blog-post-excerpt">${excerpt || 'Khám phá bài viết này...'}</p>
                                <button class="btn btn-primary w-100">
                                    <i class="fas fa-arrow-right me-2"></i>Đọc thêm
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.html(`<div class="row g-4">${html}</div>`);
        }
        
        function viewBlogPost(postId) {
            window.location.href = `blog.php?post_id=${postId}`;
        }
        
        function displayBlogPostDetail(post) {
            const container = $('#blogPostsContainer');
            const imageUrl = post.featured_image || post.HinhAnhDaiDienURL || 'img/logo/default-blog.jpg';
            const html = `
                <div class="blog-post-detail-wrapper" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-top: 20px;">
                    ${imageUrl && imageUrl !== 'img/logo/default-blog.jpg' ? `
                        <img src="${imageUrl}" alt="${post.title}" class="blog-post-image" style="width: 100%; height: 400px; object-fit: cover; display: block;">
                    ` : ''}
                    <div class="blog-post-body" style="padding: 2rem;">
                        <h2 class="blog-post-title" style="font-size: 2.5rem; -webkit-line-clamp: unset;">${post.title}</h2>
                        <div class="blog-post-meta" style="font-size: 1rem; margin-bottom: 1.5rem;">
                            <i class="fas fa-calendar text-primary"></i> ${post.created_at} | 
                            <i class="fas fa-user text-success"></i> ${post.TenTacGia || post.AuthorEmail || 'Admin'} | 
                            <i class="fas fa-eye text-info"></i> ${post.views} lượt xem
                        </div>
                        <div class="blog-post-content">
                            ${post.content}
                        </div>
                    <div class="comments-section">
                        <h4 class="mb-3"><i class="fas fa-comments"></i> Bình luận</h4>
                        ${isUserLoggedIn ? `
                            <div class="comment-form mb-3">
                                <div class="mb-2">
                                    <textarea class="form-control" id="comment-${post.id}" rows="2" placeholder="Viết bình luận của bạn..."></textarea>
                                </div>
                                <button class="btn btn-primary btn-sm" onclick="addComment(${post.id})">
                                    <i class="fas fa-paper-plane"></i> Gửi bình luận
                                </button>
                            </div>
                        ` : `
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle"></i> Bạn cần <a href="login.php">đăng nhập</a> để bình luận
                            </div>
                        `}
                        <div class="comments-list" id="comments-${post.id}">
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Đang tải...</span>
                                </div>
                            </div>
                        </div>
                        <div id="comments-pagination-${post.id}" class="mt-3"></div>
                    </div>
                </div>
            </div>
            `;
            
            container.html(html);
            loadComments(post.id);
        }
        
        function loadComments(postId) {
            $.ajax({
                url: 'src/controllers/blog.php?action=get_comments&post_id=' + postId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Load Comments Response:', response);
                    if (response.success && response.comments) {
                        displayComments(postId, response.comments, 1); // Trang 1
                        // Scroll đến comment nếu có hash trong URL
                        setTimeout(function() {
                            scrollToCommentFromHash();
                        }, 500);
                    } else {
                        console.warn('Load Comments: No comments or error', response);
                        displayComments(postId, [], 1);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Load Comments AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    displayComments(postId, [], 1);
                }
            });
        }
        
        function scrollToCommentFromHash() {
            // Kiểm tra hash trong URL (ví dụ: #comment-13)
            const hash = window.location.hash;
            if (hash && hash.startsWith('#comment-')) {
                const commentId = hash.substring(9); // Bỏ '#comment-'
                const commentElement = document.getElementById('comment-' + commentId);
                
                if (commentElement) {
                    // Đảm bảo comment đã được render và visible
                    const checkVisible = setInterval(function() {
                        if ($(commentElement).is(':visible')) {
                            clearInterval(checkVisible);
                            
                            // Scroll đến comment với offset cho navbar
                            $('html, body').animate({
                                scrollTop: $(commentElement).offset().top - 200
                            }, 200);
                            
                            // Highlight comment (thêm class để làm nổi bật)
                            $(commentElement).addClass('highlight-comment');
                            setTimeout(function() {
                                $(commentElement).removeClass('highlight-comment');
                            }, 2000);
                        }
                    }, 100);
                    
                    // Timeout sau 3 giây nếu không tìm thấy
                    setTimeout(function() {
                        clearInterval(checkVisible);
                        if (!$(commentElement).is(':visible')) {
                            console.warn('Comment element not visible after timeout:', 'comment-' + commentId);
                        }
                    }, 3000);
                } else {
                    console.warn('Comment element not found:', 'comment-' + commentId);
                    // Thử lại sau 1 giây (có thể comments chưa load xong)
                    setTimeout(function() {
                        scrollToCommentFromHash();
                    }, 1000);
                }
            }
        }
        
        // Lắng nghe sự kiện hashchange để scroll khi hash thay đổi
        $(window).on('hashchange', function() {
            setTimeout(function() {
                scrollToCommentFromHash();
            }, 500);
        });
        
        // Biến lưu tất cả comments (để phân trang)
        let allComments = {};
        
        function displayComments(postId, comments, page = 1) {
            const container = $('#comments-' + postId);
            const paginationContainer = $('#comments-pagination-' + postId);
            
            if (container.length === 0) {
                console.error('Comment container not found: #comments-' + postId);
                return;
            }
            
            // Lưu tất cả comments
            allComments[postId] = comments || [];
            
            if (!comments || comments.length === 0) {
                container.html('<p class="text-muted">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>');
                paginationContainer.html('');
                return;
            }
            
            // Phân trang: 10 comments mỗi trang
            const commentsPerPage = 10;
            const totalPages = Math.ceil(comments.length / commentsPerPage);
            const startIndex = (page - 1) * commentsPerPage;
            const endIndex = startIndex + commentsPerPage;
            const paginatedComments = comments.slice(startIndex, endIndex);
            
            console.log('Displaying comments:', paginatedComments.length, 'of', comments.length, 'comments (Page', page, 'of', totalPages + ')');
            
            // Hiển thị comments
            let html = '';
            paginatedComments.forEach(function(comment) {
                html += renderComment(comment, postId, 0);
            });
            
            container.html(html);
            
            // Hiển thị phân trang
            if (totalPages > 1) {
                let paginationHtml = '<nav aria-label="Phân trang bình luận"><ul class="pagination justify-content-center">';
                
                // Nút Previous
                if (page > 1) {
                    paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadCommentsPage(${postId}, ${page - 1}); return false;">Trước</a></li>`;
                } else {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">Trước</span></li>`;
                }
                
                // Số trang
                for (let i = 1; i <= totalPages; i++) {
                    if (i === page) {
                        paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                    } else {
                        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadCommentsPage(${postId}, ${i}); return false;">${i}</a></li>`;
                    }
                }
                
                // Nút Next
                if (page < totalPages) {
                    paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadCommentsPage(${postId}, ${page + 1}); return false;">Sau</a></li>`;
                } else {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">Sau</span></li>`;
                }
                
                paginationHtml += '</ul></nav>';
                paginationContainer.html(paginationHtml);
            } else {
                paginationContainer.html('');
            }
        }
        
        function loadCommentsPage(postId, page) {
            if (allComments[postId] && allComments[postId].length > 0) {
                displayComments(postId, allComments[postId], page);
                // Scroll đến phần comments
                $('html, body').animate({
                    scrollTop: $('#comments-' + postId).offset().top - 100
                }, 300);
            }
        }
        
        function renderComment(comment, postId, level) {
            const indent = level * 30;
            const isReply = level > 0;
            let html = `
                <div class="comment-item ${isReply ? 'reply' : ''}" id="comment-${comment.id}" style="margin-left: ${indent}px; ${isReply ? 'border-left: 2px solid #667eea; padding-left: 0.8rem; margin-top: 0.5rem; background-color: #f8f9fa;' : ''}">
                    <div class="comment-author">
                        <i class="fas fa-user-circle"></i> ${comment.UserName || comment.UserEmail || 'Người dùng'}
                        ${isReply && comment.ParentUserName ? `<small class="text-muted ms-2"><i class="fas fa-reply"></i> Trả lời ${comment.ParentUserName || comment.ParentUserEmail || 'Người dùng'}</small>` : ''}
                    </div>
                    <div class="comment-content">${comment.content}</div>
                    <div class="comment-date">
                        <i class="fas fa-clock"></i> ${comment.created_at}
                    </div>
                    ${isUserLoggedIn ? `
                        <div class="comment-actions mt-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="showReplyForm(${comment.id}, ${postId}, '${(comment.UserName || comment.UserEmail || 'Người dùng').replace(/'/g, "\\'")}')">
                                <i class="fas fa-reply"></i> Trả lời
                            </button>
                        </div>
                        <div id="reply-form-${comment.id}" class="reply-form" style="display: none;">
                            <div class="mb-2">
                                <textarea class="form-control" id="reply-content-${comment.id}" rows="2" placeholder="Trả lời ${comment.UserName || comment.UserEmail || 'Người dùng'}..."></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary" onclick="addReply(${comment.id}, ${postId})">
                                    <i class="fas fa-paper-plane"></i> Gửi
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="hideReplyForm(${comment.id})">
                                    <i class="fas fa-times"></i> Hủy
                                </button>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
            
            // Hiển thị replies nếu có
            if (comment.replies && comment.replies.length > 0) {
                comment.replies.forEach(function(reply) {
                    html += renderComment(reply, postId, level + 1);
                });
            }
            
            return html;
        }
        
        function showReplyForm(commentId, postId, userName) {
            $('#reply-form-' + commentId).show();
            $('#reply-content-' + commentId).focus();
        }
        
        function hideReplyForm(commentId) {
            $('#reply-form-' + commentId).hide();
            $('#reply-content-' + commentId).val('');
        }
        
        function addReply(parentCommentId, postId) {
            const content = $('#reply-content-' + parentCommentId).val().trim();
            
            if (!content) {
                alert('Vui lòng nhập nội dung trả lời');
                return;
            }
            
            if (!isUserLoggedIn) {
                alert('Bạn cần đăng nhập để trả lời');
                window.location.href = 'login.php';
                return;
            }
            
            $.ajax({
                url: 'src/controllers/blog.php',
                method: 'POST',
                data: {
                    action: 'add_comment',
                    post_id: postId,
                    parent_comment_id: parentCommentId,
                    content: content
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#reply-content-' + parentCommentId).val('');
                        hideReplyForm(parentCommentId);
                        loadComments(postId);
                        // Scroll đến phần comments
                        setTimeout(function() {
                            $('html, body').animate({
                                scrollTop: $('#comments-' + postId).offset().top - 100
                            }, 300);
                        }, 500);
                        alert('Đã thêm trả lời thành công!');
                    } else {
                        let errorMsg = response.error || 'Lỗi khi thêm trả lời';
                        if (response.debug) {
                            console.error('Error details:', response.debug);
                        }
                        alert(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    alert('Lỗi khi thêm trả lời. Vui lòng kiểm tra console để xem chi tiết.');
                }
            });
        }
        
        function addComment(postId) {
            const content = $('#comment-' + postId).val().trim();
            
            if (!content) {
                alert('Vui lòng nhập nội dung bình luận');
                return;
            }
            
            if (!isUserLoggedIn) {
                alert('Bạn cần đăng nhập để bình luận');
                window.location.href = 'login.php';
                return;
            }
            
            $.ajax({
                url: 'src/controllers/blog.php',
                method: 'POST',
                data: {
                    action: 'add_comment',
                    post_id: postId,
                    content: content
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#comment-' + postId).val('');
                        // Reload comments để hiển thị comment mới (về trang 1)
                        loadComments(postId);
                        // Scroll đến phần comments
                        setTimeout(function() {
                            $('html, body').animate({
                                scrollTop: $('#comments-' + postId).offset().top - 100
                            }, 300);
                        }, 500);
                        // Chỉ hiển thị alert nếu không có reload flag
                        if (!response.reload) {
                            alert('Đã thêm bình luận thành công!');
                        }
                    } else {
                        let errorMsg = response.error || 'Lỗi khi thêm bình luận';
                        if (response.debug) {
                            console.error('Error details:', response.debug);
                        }
                        alert(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    alert('Lỗi khi thêm bình luận. Vui lòng kiểm tra console để xem chi tiết.');
                }
            });
        }
        
        function displayNoPosts(message) {
            const container = $('#blogPostsContainer');
            container.html(`
                <div class="no-posts">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h4>${message}</h4>
                </div>
            `);
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

