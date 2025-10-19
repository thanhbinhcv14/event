<?php
session_start();
require_once __DIR__ . '/../src/auth/auth.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dịch vụ - Event Management System</title>
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
        
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1><i class="fas fa-star"></i> Dịch vụ của chúng tôi</h1>
            <p>Chúng tôi cung cấp các dịch vụ tổ chức sự kiện chuyên nghiệp và đa dạng</p>
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
                            <i class="fas fa-video"></i>
                        </div>
                        <h3>Ghi hình sự kiện</h3>
                        <p>Dịch vụ quay phim và chụp ảnh chuyên nghiệp để lưu giữ những khoảnh khắc đẹp.</p>
                        <ul class="service-features">
                            <li>Camera chuyên nghiệp</li>
                            <li>Quay đa góc</li>
                            <li>Chỉnh sửa video</li>
                            <li>Giao hàng nhanh</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Service 5 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3>Catering</h3>
                        <p>Dịch vụ ăn uống cao cấp với thực đơn đa dạng phù hợp với mọi sự kiện.</p>
                        <ul class="service-features">
                            <li>Thực đơn đa dạng</li>
                            <li>Đầu bếp chuyên nghiệp</li>
                            <li>Phục vụ tận nơi</li>
                            <li>Vệ sinh an toàn</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Service 6 -->
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
    
    <!-- Chat Widget - AI Assistant -->
    <?php include 'chat-widget.php'; ?>
</body>
</html>
