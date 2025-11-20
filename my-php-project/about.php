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
    <title>Giới thiệu - Event Management System</title>
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
        
        .content-container {
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
        
        .about-card {
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
        
        .about-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .about-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }
        
        .about-card:hover::before {
            left: 100%;
        }
        
        .about-icon {
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
        
        .about-card h3 {
            color: #333;
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .about-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .team-member {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .team-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 3rem;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
            animation: avatarFloat 3s ease-in-out infinite;
        }
        
        @keyframes avatarFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .team-member h4 {
            color: #333;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .team-member p {
            color: #666;
            margin-bottom: 0;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: statsShine 8s ease-in-out infinite;
        }
        
        @keyframes statsShine {
            0%, 100% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
        }
        
        .stats-number {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .stats-label {
            font-size: 1.2rem;
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
                        <a class="nav-link" href="services.php">
                            <i class="fas fa-concierge-bell me-1"></i>Dịch vụ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">
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
            <h1><i class="fas fa-info-circle"></i> Giới thiệu về chúng tôi</h1>
            <p>Chúng tôi là đội ngũ chuyên nghiệp với nhiều năm kinh nghiệm trong lĩnh vực tổ chức sự kiện</p>
        </div>
    </div>
    
    <!-- Content Section -->
    <div class="container">
        <div class="content-container">
            <!-- Company Story -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="about-card">
                        <div class="about-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3>Câu chuyện của chúng tôi</h3>
                        <p>Được thành lập vào năm 2020, chúng tôi bắt đầu với một tầm nhìn đơn giản: tạo ra những sự kiện đáng nhớ và ý nghĩa. Từ những sự kiện nhỏ đầu tiên, chúng tôi đã phát triển thành một công ty tổ chức sự kiện hàng đầu với hơn 1000 sự kiện thành công.</p>
                        <p>Chúng tôi tin rằng mỗi sự kiện đều có câu chuyện riêng và chúng tôi ở đây để giúp bạn kể câu chuyện đó một cách hoàn hảo nhất.</p>
                    </div>
                </div>
            </div>
            
            <!-- Mission & Vision -->
            <div class="row mb-5">
                <div class="col-lg-6 col-md-6 mb-4">
                    <div class="about-card">
                        <div class="about-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Sứ mệnh</h3>
                        <p>Chúng tôi cam kết mang đến những trải nghiệm sự kiện tuyệt vời nhất cho khách hàng, với sự chuyên nghiệp, sáng tạo và tận tâm trong từng chi tiết.</p>
                    </div>
                </div>
                
                <div class="col-lg-6 col-md-6 mb-4">
                    <div class="about-card">
                        <div class="about-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Tầm nhìn</h3>
                        <p>Trở thành công ty tổ chức sự kiện hàng đầu tại Việt Nam, được khách hàng tin tưởng và yêu mến với những sự kiện đáng nhớ và ý nghĩa.</p>
                    </div>
                </div>
            </div>
            
            <!-- Values -->
            <div class="row mb-5">
                <div class="col-12">
                    <h2 class="text-center mb-4" style="color: #333; font-weight: 700;">Giá trị cốt lõi</h2>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="about-card text-center">
                        <div class="about-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Đam mê</h3>
                        <p>Chúng tôi đam mê tạo ra những sự kiện đặc biệt và ý nghĩa.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="about-card text-center">
                        <div class="about-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3>Chất lượng</h3>
                        <p>Cam kết mang đến chất lượng dịch vụ tốt nhất cho khách hàng.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="about-card text-center">
                        <div class="about-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3>Sáng tạo</h3>
                        <p>Luôn tìm kiếm những ý tưởng mới và độc đáo cho sự kiện.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="about-card text-center">
                        <div class="about-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3>Tin cậy</h3>
                        <p>Xây dựng mối quan hệ tin cậy và lâu dài với khách hàng.</p>
                    </div>
                </div>
            </div>
            
            <!-- Team Section -->
            <div class="row mb-5">
                <div class="col-12">
                    <h2 class="text-center mb-4" style="color: #333; font-weight: 700;">Đội ngũ của chúng tôi</h2>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-member">
                        <div class="team-avatar">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h4>Nguyễn Văn A</h4>
                        <p>Giám đốc điều hành</p>
                        <p>10+ năm kinh nghiệm</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-member">
                        <div class="team-avatar">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h4>Trần Thị B</h4>
                        <p>Giám đốc sáng tạo</p>
                        <p>8+ năm kinh nghiệm</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-member">
                        <div class="team-avatar">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <h4>Lê Văn C</h4>
                        <p>Trưởng phòng kỹ thuật</p>
                        <p>6+ năm kinh nghiệm</p>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="row">
                <div class="col-12">
                    <h2 class="text-center mb-4" style="color: #333; font-weight: 700;">Thành tựu của chúng tôi</h2>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">1000+</div>
                        <div class="stats-label">Sự kiện đã tổ chức</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">500+</div>
                        <div class="stats-label">Khách hàng hài lòng</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">50+</div>
                        <div class="stats-label">Nhân viên chuyên nghiệp</div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-number">5</div>
                        <div class="stats-label">Năm kinh nghiệm</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>
