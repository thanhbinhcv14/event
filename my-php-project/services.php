<?php
session_start();
require_once __DIR__ . '/src/auth/auth.php';

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
    <title>Dịch vụ - Event Management System</title>
    <link rel="icon" href="img/logo/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
            padding-top: 80px;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }
        
        @keyframes backgroundShift {
            0%, 100% { transform: translateX(0) translateY(0); }
            25% { transform: translateX(-10px) translateY(-5px); }
            50% { transform: translateX(10px) translateY(5px); }
            75% { transform: translateX(-5px) translateY(10px); }
        }
        
        .hero-section {
            padding: 100px 0;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: titleGlow 3s ease-in-out infinite alternate;
        }
        
        @keyframes titleGlow {
            0% { text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); }
            100% { text-shadow: 0 4px 30px rgba(255, 255, 255, 0.5); }
        }
        
        .hero-section p {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .services-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            margin: -50px auto 50px;
            padding: 60px 40px;
            max-width: 1200px;
            position: relative;
            z-index: 1;
            animation: containerFloat 6s ease-in-out infinite;
        }
        
        @keyframes containerFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }
        
        .service-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }
        
        .service-card:hover::before {
            left: 100%;
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            animation: iconPulse 2s ease-in-out infinite;
        }
        
        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .service-card h3 {
            color: #333;
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .service-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .service-features {
            list-style: none;
            padding: 0;
        }
        
        .service-features li {
            padding: 0.5rem 0;
            color: #555;
            position: relative;
            padding-left: 1.5rem;
        }
        
        .service-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
        }
        
        .pricing-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .pricing-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pricingShine 8s ease-in-out infinite;
        }
        
        @keyframes pricingShine {
            0%, 100% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
        }
        
        .pricing-card h3 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .pricing-card .price {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .pricing-card p {
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* Navigation Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            padding: 0.5rem 2rem;
        }
        
        .navbar .container-fluid {
            padding: 0 1rem;
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
            font-weight: 600;
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
        
        /* Service Cards Styles */
        .service-item-card {
            background: white;
            border-radius: 20px;
            padding: 0;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            position: relative;
        }
        
        .service-item-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .service-item-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .service-item-image:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .service-item-image[src*="default"] {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 2rem;
        }
        
        .service-item-content {
            padding: 1.5rem;
        }
        
        .service-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 1rem;
        }
        
        .service-item-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
            line-height: 1.3;
            flex: 1;
        }
        
        .service-item-type {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .service-item-price {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 0.6rem 1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
            white-space: nowrap;
            min-width: 120px;
        }
        
        .service-item-description {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .service-item-details {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }
        
        .service-item-detail {
            display: flex;
            align-items: center;
            color: #495057;
            font-size: 0.9rem;
        }
        
        .service-item-detail i {
            color: #6c757d;
            margin-right: 0.5rem;
            width: 16px;
            text-align: center;
        }
        
        .service-item-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
            margin-top: 1rem;
        }
        
        .service-item-capacity {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .service-item-events {
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .service-item-events i {
            color: #28a745;
        }
        
        /* Combo specific styles */
        .combo-equipment-section {
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .combo-section-title {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
        }
        
        .combo-section-title i {
            margin-right: 0.5rem;
        }
        
        .equipment-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .equipment-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.3rem 0;
            font-size: 0.9rem;
            color: #555;
        }
        
        .equipment-item i {
            color: #28a745;
            font-size: 0.8rem;
        }
        
        /* Combo card specific - no image */
        .service-item-card:has(.combo-equipment-section) {
            border-radius: 20px;
        }
        
        /* Tab Styles */
        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 1rem 2rem;
            border-radius: 10px 10px 0 0;
            margin-right: 0.5rem;
        }
        
        .nav-tabs .nav-link.active {
            color: #667eea;
            background: white;
            border-bottom: 2px solid #667eea;
        }
        
        .nav-tabs .nav-link:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #e9ecef;
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
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 20px;
            padding: 6px 18px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
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
            
            .navbar-toggler {
                border: none;
                padding: 0.25rem 0.5rem;
            }
            
            .navbar-toggler:focus {
                box-shadow: none;
            }
        }
        
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
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
                        <a class="nav-link active" href="services.php">
                            <i class="fas fa-concierge-bell me-1"></i>Dịch vụ
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
                    <li class="nav-item">
                        <a class="nav-link" href="privacy-policy.php">
                            <i class="fas fa-shield-alt me-1"></i>Chính sách bảo mật
                        </a>
                    </li>
                    <?php if ($user): ?>
                    <!-- Chức năng dành cho người dùng đã đăng nhập -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="eventsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-calendar-alt me-1"></i>Sự kiện
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="eventsDropdown">
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
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="chat.php">
                            <i class="fas fa-comments me-1"></i>Chat hỗ trợ
                        </a>
                    </li>
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
    <div class="hero-section">
        <div class="container">
            <h1><i class="fas fa-star"></i> Dịch vụ của chúng tôi</h1>
            <p>Chúng tôi cung cấp các dịch vụ tổ chức sự kiện chuyên nghiệp và đa dạng</p>
        </div>
    </div>
    
    <!-- Real Services Section -->
    <div class="container">
        <div class="row mt-5">
            <div class="col-12">
                
                <!-- Service Tabs -->
                <ul class="nav nav-tabs justify-content-center mb-4" id="serviceTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="locations-tab" data-bs-toggle="tab" data-bs-target="#locations" type="button" role="tab">
                            <i class="fas fa-map-marker-alt"></i> Địa điểm
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="equipment-tab" data-bs-toggle="tab" data-bs-target="#equipment" type="button" role="tab">
                            <i class="fas fa-cogs"></i> Thiết bị
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="combos-tab" data-bs-toggle="tab" data-bs-target="#combos" type="button" role="tab">
                            <i class="fas fa-box"></i> Combo
                        </button>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content" id="serviceTabsContent">
                    <!-- Locations Tab -->
                    <div class="tab-pane fade show active" id="locations" role="tabpanel">
                        <div class="row" id="locationsContainer">
                            <div class="col-12 text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Đang tải danh sách địa điểm...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipment Tab -->
                    <div class="tab-pane fade" id="equipment" role="tabpanel">
                        <div class="row" id="equipmentContainer">
                            <div class="col-12 text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Đang tải danh sách thiết bị...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Combos Tab -->
                    <div class="tab-pane fade" id="combos" role="tabpanel">
                        <div class="row" id="combosContainer">
                            <div class="col-12 text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Đang tải danh sách combo...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Services Section -->
    <div class="container">
        <div class="services-container">
            <div class="row">
                <!-- Service 1 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>Tổ chức sự kiện</h3>
                        <p>Chúng tôi chuyên tổ chức các sự kiện từ quy mô nhỏ đến lớn với đội ngũ chuyên nghiệp.</p>
                        <ul class="service-features">
                            <li>Lập kế hoạch chi tiết</li>
                            <li>Quản lý thời gian</li>
                            <li>Điều phối nhân sự</li>
                            <li>Hỗ trợ 24/7</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Service 2 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-microphone"></i>
                        </div>
                        <h3>Thiết bị âm thanh</h3>
                        <p>Cung cấp và lắp đặt hệ thống âm thanh chất lượng cao cho mọi loại sự kiện.</p>
                        <ul class="service-features">
                            <li>Micro không dây</li>
                            <li>Loa chuyên nghiệp</li>
                            <li>Mixer âm thanh</li>
                            <li>Bảo trì thiết bị</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Service 3 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3>Hệ thống ánh sáng</h3>
                        <p>Thiết kế và lắp đặt hệ thống ánh sáng chuyên nghiệp tạo không gian ấn tượng.</p>
                        <ul class="service-features">
                            <li>Đèn LED chuyên nghiệp</li>
                            <li>Hiệu ứng ánh sáng</li>
                            <li>Điều khiển tự động</li>
                            <li>Tiết kiệm năng lượng</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Service 4 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Địa điểm tổ chức</h3>
                        <p>Hỗ trợ tìm kiếm và đặt địa điểm tổ chức sự kiện phù hợp với yêu cầu.</p>
                        <ul class="service-features">
                            <li>Nhiều địa điểm</li>
                            <li>Giá cả hợp lý</li>
                            <li>Vị trí thuận tiện</li>
                            <li>Hỗ trợ đặt chỗ</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Dynamic Pricing Section -->
            <div class="row mt-5">
                <div class="col-12">
                    <h2 class="text-center mb-5" style="color: #333; font-weight: 700;">Bảng giá dịch vụ (Giá biến động)</h2>
                    
                    <!-- Dynamic Pricing Info -->
                    <div class="alert alert-info mb-4">
                        <h5><i class="fas fa-info-circle"></i> Giá Biến Động Theo Thời Gian</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Thời gian trong ngày:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-sun text-warning"></i> Buổi sáng (6:00-12:00): <strong>Giá gốc</strong></li>
                                    <li><i class="fas fa-cloud-sun text-info"></i> Buổi chiều (12:00-18:00): <strong>+10%</strong></li>
                                    <li><i class="fas fa-moon text-primary"></i> Buổi tối (18:00-22:00): <strong>+25%</strong></li>
                                    <li><i class="fas fa-moon text-dark"></i> Ban đêm (22:00-6:00): <strong>+30%</strong></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Ngày trong tuần:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-calendar-day text-success"></i> Ngày thường (T2-T6): <strong>Giá gốc</strong></li>
                                    <li><i class="fas fa-calendar-weekend text-warning"></i> Cuối tuần (T7-CN): <strong>+20%</strong></li>
                                    <li><i class="fas fa-calendar-check text-danger"></i> Ngày lễ: <strong>+40%</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card">
                        <h3>Gói Cơ bản</h3>
                        <div class="price">2.000.000đ</div>
                        <p>Phù hợp cho sự kiện nhỏ</p>
                        <div class="alert alert-warning small">
                            <strong>Lưu ý:</strong> Giá địa điểm có thể tăng 10-40% tùy thời gian
                        </div>
                        <ul class="service-features mt-3">
                            <li>Tối đa 50 khách</li>
                            <li>Thiết bị cơ bản</li>
                            <li>Hỗ trợ 8 giờ</li>
                            <li>Bảo hiểm sự kiện</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card">
                        <h3>Gói Chuyên nghiệp</h3>
                        <div class="price">5.000.000đ</div>
                        <p>Phù hợp cho sự kiện trung bình</p>
                        <div class="alert alert-warning small">
                            <strong>Lưu ý:</strong> Giá địa điểm có thể tăng 10-40% tùy thời gian
                        </div>
                        <ul class="service-features mt-3">
                            <li>Tối đa 150 khách</li>
                            <li>Thiết bị cao cấp</li>
                            <li>Hỗ trợ 12 giờ</li>
                            <li>Quay phim cơ bản</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card">
                        <h3>Gói Cao cấp</h3>
                        <div class="price">10.000.000đ</div>
                        <p>Phù hợp cho sự kiện lớn</p>
                        <div class="alert alert-warning small">
                            <strong>Lưu ý:</strong> Giá địa điểm có thể tăng 10-40% tùy thời gian
                        </div>
                        <ul class="service-features mt-3">
                            <li>Không giới hạn khách</li>
                            <li>Thiết bị chuyên nghiệp</li>
                            <li>Hỗ trợ 24/7</li>
                            <li>Quay phim 4K</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Demo Button -->
                <div class="col-12 text-center mt-4">
                    <a href="dynamic-pricing-demo.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-calculator"></i> Demo Tính Giá Động
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Load services data
        document.addEventListener('DOMContentLoaded', function() {
            loadLocations();
            
            // Load data when tab is clicked
            document.getElementById('equipment-tab').addEventListener('click', function() {
                if (document.getElementById('equipmentContainer').innerHTML.includes('spinner-border')) {
                    loadEquipment();
                }
            });
            
            document.getElementById('combos-tab').addEventListener('click', function() {
                if (document.getElementById('combosContainer').innerHTML.includes('spinner-border')) {
                    loadCombos();
                }
            });
        });
        
        // Load locations
        function loadLocations() {
            fetch('src/controllers/services-controller.php?action=get_locations')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayLocations(data.locations);
                    } else {
                        document.getElementById('locationsContainer').innerHTML = `
                            <div class="col-12 text-center">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Không thể tải danh sách địa điểm: ${data.message}
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading locations:', error);
                    document.getElementById('locationsContainer').innerHTML = `
                        <div class="col-12 text-center">
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Lỗi khi tải danh sách địa điểm
                            </div>
                        </div>
                    `;
                });
        }
        
        // Load equipment
        function loadEquipment() {
            fetch('src/controllers/services-controller.php?action=get_equipment')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayEquipment(data.equipment);
                    } else {
                        document.getElementById('equipmentContainer').innerHTML = `
                            <div class="col-12 text-center">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Không thể tải danh sách thiết bị: ${data.message}
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading equipment:', error);
                    document.getElementById('equipmentContainer').innerHTML = `
                        <div class="col-12 text-center">
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Lỗi khi tải danh sách thiết bị
                            </div>
                        </div>
                    `;
                });
        }
        
        // Load combos
        function loadCombos() {
            fetch('src/controllers/services-controller.php?action=get_combos')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayCombos(data.combos);
                    } else {
                        document.getElementById('combosContainer').innerHTML = `
                            <div class="col-12 text-center">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Không thể tải danh sách combo: ${data.message}
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading combos:', error);
                    document.getElementById('combosContainer').innerHTML = `
                        <div class="col-12 text-center">
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Lỗi khi tải danh sách combo
                            </div>
                        </div>
                    `;
                });
        }
        
        // Display locations
        function displayLocations(locations) {
            const container = document.getElementById('locationsContainer');
            
            if (!locations || locations.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Chưa có địa điểm nào được thêm vào hệ thống
                        </div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            locations.forEach(location => {
                const priceText = getLocationPriceText(location);
                const imagePath = location.HinhAnh ? `img/diadiem/${location.HinhAnh}` : 'img/diadiem/default.php';
                
                html += `
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="service-item-card">
                            <img src="${imagePath}" alt="${location.TenDiaDiem}" class="service-item-image" 
                                 onerror="this.src='img/diadiem/default.php'">
                            
                            <div class="service-item-content">
                                <div class="service-item-header">
                                    <h3 class="service-item-title">${location.TenDiaDiem}</h3>
                                    <div class="service-item-type">${location.LoaiDiaDiem}</div>
                                </div>
                                
                                <div class="service-item-description">
                                    ${location.MoTa || 'Không có mô tả'}
                                </div>
                                
                                <div class="service-item-price">
                                    ${priceText}
                                </div>
                                
                                <div class="service-item-details">
                                    <div class="service-item-detail">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>${location.DiaChi}</span>
                                    </div>
                                </div>
                                
                                <div class="service-item-stats">
                                    <div class="service-item-capacity">
                                        <i class="fas fa-users"></i> ${location.SucChua || 'Chưa xác định'} người
                                    </div>
                                    <div class="service-item-events">
                                        <i class="fas fa-calendar-check"></i> ${location.SoSuKienDaToChuc || 0} sự kiện
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Display equipment
        function displayEquipment(equipment) {
            const container = document.getElementById('equipmentContainer');
            
            if (equipment.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Chưa có thiết bị nào được thêm vào hệ thống
                        </div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            equipment.forEach(item => {
                const imagePath = item.HinhAnh ? `img/thietbi/${item.HinhAnh}` : 'img/thietbi/default.jpg';
                const iconClass = getEquipmentIcon(item.LoaiThietBi);
                const hasImage = item.HinhAnh && item.HinhAnh !== '';
                
                html += `
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="service-item-card">
                            ${hasImage ? 
                                `<img src="${imagePath}" alt="${item.TenThietBi}" class="service-item-image" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">` : 
                                ''
                            }
                            <div class="service-item-image" style="${hasImage ? 'display: none;' : 'display: flex;'}">
                                <i class="${iconClass}"></i>
                            </div>
                            
                            <div class="service-item-content">
                                <div class="service-item-header">
                                    <h3 class="service-item-title">${item.TenThietBi}</h3>
                                    <div class="service-item-price">${formatPrice(item.GiaThue)}</div>
                                </div>
                                
                                <div class="service-item-description">
                                    ${item.MoTa || 'Không có mô tả'}
                                </div>
                                
                                <div class="service-item-details">
                                    <div class="service-item-detail">
                                        <i class="${iconClass}"></i> ${item.LoaiThietBi}
                                    </div>
                                    <div class="service-item-detail">
                                        <i class="fas fa-industry"></i> ${item.HangSX || 'Chưa xác định'}
                                    </div>
                                    <div class="service-item-detail">
                                        <i class="fas fa-ruler"></i> ${item.DonViTinh || 'Chưa xác định'}
                                    </div>
                                </div>
                                
                                <div class="service-item-stats">
                                    <div class="service-item-capacity">
                                        <i class="fas fa-chart-line"></i>
                                        <span>${item.SoLanSuDung || 0} lần sử dụng</span>
                                    </div>
                                    <div class="service-item-events">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>${formatPrice(item.GiaThue)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Display combos
        function displayCombos(combos) {
            const container = document.getElementById('combosContainer');
            
            if (combos.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Chưa có combo nào được thêm vào hệ thống
                        </div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            combos.forEach(combo => {
                const iconClass = getComboIcon(combo.TenCombo);
                const equipmentList = combo.ThietBiTrongCombo ? combo.ThietBiTrongCombo.split(', ') : [];
                
                html += `
                    <div class="col-lg-6 col-md-6 mb-4">
                        <div class="service-item-card">
                            <div class="service-item-content">
                                <div class="service-item-header">
                                    <h3 class="service-item-title">${combo.TenCombo}</h3>
                                    <div class="service-item-price">${formatPrice(combo.GiaCombo)}</div>
                                </div>
                                
                                <div class="service-item-description">
                                    ${combo.MoTa || 'Không có mô tả'}
                                </div>
                                
                                ${equipmentList.length > 0 ? `
                                <div class="combo-equipment-section">
                                    <h6 class="combo-section-title">
                                        <i class="fas fa-list"></i> Thiết bị trong combo:
                                    </h6>
                                    <div class="equipment-list">
                                        ${equipmentList.map(item => `
                                            <div class="equipment-item">
                                                <i class="fas fa-check-circle"></i>
                                                <span>${item}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                                ` : ''}
                                
                                <div class="service-item-stats">
                                    <div class="service-item-capacity">
                                        <i class="fas fa-chart-line"></i>
                                        <span>${combo.SoLanSuDung || 0} lần sử dụng</span>
                                    </div>
                                    <div class="service-item-events">
                                        <i class="fas fa-star"></i>
                                        <span>Combo chất lượng</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Helper functions
        function getEquipmentIcon(equipmentType) {
            const iconMap = {
                'Âm thanh': 'fas fa-volume-up',
                'Hình ảnh': 'fas fa-video',
                'Ánh sáng': 'fas fa-lightbulb',
                'Phụ trợ': 'fas fa-tools'
            };
            return iconMap[equipmentType] || 'fas fa-cog';
        }
        
        function getComboIcon(comboName) {
            if (comboName.includes('Hội nghị')) return 'fas fa-users';
            if (comboName.includes('Tiệc cưới')) return 'fas fa-heart';
            if (comboName.includes('Triển lãm')) return 'fas fa-store';
            if (comboName.includes('Sân khấu')) return 'fas fa-music';
            return 'fas fa-box';
        }
        
        function getLocationPriceText(location) {
            if (location.LoaiThue === 'Theo giờ' && location.GiaThueGio) {
                return `${formatPrice(location.GiaThueGio)}/giờ`;
            }
            if (location.LoaiThue === 'Theo ngày' && location.GiaThueNgay) {
                return `${formatPrice(location.GiaThueNgay)}/ngày`;
            }
            if (location.LoaiThue === 'Cả hai') {
                if (location.GiaThueGio && location.GiaThueNgay) {
                    return `${formatPrice(location.GiaThueGio)}/giờ hoặc ${formatPrice(location.GiaThueNgay)}/ngày`;
                }
            }
            return 'Liên hệ';
        }
        
        function formatPrice(price) {
            if (!price) return 'Liên hệ';
            return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ';
        }
    </script>
    
</body>
</html>
