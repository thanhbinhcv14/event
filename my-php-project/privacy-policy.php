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
    <title>Chính sách quyền riêng tư - Event Management System</title>
    <link rel="icon" href="img/logo/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: white;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
            padding-top: 80px;
        }
        
        .hero-section {
            padding: 40px 0 20px;
            text-align: center;
            color: #333;
            position: relative;
            background: white;
        }
        
        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #333;
        }
        
        .hero-section p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .content-container {
            background: white;
            border-radius: 15px;
            box-shadow: 
                0 10px 40px rgba(0, 0, 0, 0.12),
                0 0 0 1px rgba(0, 0, 0, 0.05);
            margin: -40px auto 40px;
            padding: 40px 30px;
            max-width: 1100px;
            position: relative;
            z-index: 1;
            border: 1px solid #e9ecef;
        }
        
        .policy-section {
            margin-bottom: 2.5rem;
        }
        
        .policy-section h2 {
            color: #667eea;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #667eea;
        }
        
        .policy-section h3 {
            color: #333;
            font-size: 1.4rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .policy-section p {
            color: #555;
            line-height: 1.8;
            margin-bottom: 1rem;
            text-align: justify;
        }
        
        .policy-section ul {
            color: #555;
            line-height: 1.8;
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        
        .policy-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .last-updated {
            text-align: right;
            color: #999;
            font-size: 0.9rem;
            font-style: italic;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
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
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0 1.5rem;
            margin-top: 4rem;
        }
        
        .footer h5, .footer h6 {
            color: white;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        .footer p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }
        
        .footer .footer-logo {
            filter: brightness(0) invert(1);
        }
        
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .content-container {
                padding: 30px 20px;
                margin: -30px 15px 30px;
            }
            
            .policy-section h2 {
                font-size: 1.5rem;
            }
            
            .policy-section h3 {
                font-size: 1.2rem;
            }
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
                        <a class="nav-link" href="blog.php">
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
                    <li class="nav-item">
                        <a class="nav-link" href="chat.php">
                            <i class="fas fa-comments me-1"></i>Chat hỗ trợ
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex gap-1 align-items-center">
                    <?php if ($user): ?>
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($currentUserName); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Hồ sơ</a></li>
                                <li><a class="dropdown-item" href="events/my-events.php"><i class="fas fa-calendar me-2"></i>Sự kiện của tôi</a></li>
                                <?php if (in_array($userRole, [1, 2, 3, 4])): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/index.php"><i class="fas fa-cog me-2"></i>Quản trị</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-sign-in-alt me-1"></i>Đăng nhập
                        </a>
                        <a href="register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i>Đăng ký
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1><i class="fas fa-shield-alt me-2"></i>Chính sách quyền riêng tư</h1>
            <p>Cam kết bảo vệ thông tin cá nhân của bạn</p>
        </div>
    </section>

    <!-- Content -->
    <div class="container">
        <div class="content-container">
            <div class="policy-section">
                <h2>1. Giới thiệu</h2>
                <p>
                    Chúng tôi tại Event Management System ("chúng tôi", "của chúng tôi" hoặc "công ty") cam kết bảo vệ quyền riêng tư của bạn. 
                    Chính sách quyền riêng tư này giải thích cách chúng tôi thu thập, sử dụng, tiết lộ và bảo vệ thông tin cá nhân của bạn 
                    khi bạn sử dụng dịch vụ của chúng tôi.
                </p>
            </div>

            <div class="policy-section">
                <h2>2. Thông tin chúng tôi thu thập</h2>
                <h3>2.1. Thông tin bạn cung cấp</h3>
                <p>Chúng tôi thu thập thông tin mà bạn cung cấp trực tiếp cho chúng tôi, bao gồm:</p>
                <ul>
                    <li>Thông tin đăng ký: Tên, email, số điện thoại, địa chỉ</li>
                    <li>Thông tin sự kiện: Loại sự kiện, ngày giờ, địa điểm, số lượng khách</li>
                    <li>Thông tin thanh toán: Phương thức thanh toán, thông tin thẻ (được mã hóa)</li>
                    <li>Thông tin liên hệ: Khi bạn liên hệ với chúng tôi qua email, điện thoại hoặc chat</li>
                </ul>

                <h3>2.2. Thông tin tự động thu thập</h3>
                <p>Khi bạn sử dụng dịch vụ của chúng tôi, chúng tôi có thể tự động thu thập:</p>
                <ul>
                    <li>Thông tin thiết bị: Địa chỉ IP, loại trình duyệt, hệ điều hành</li>
                    <li>Thông tin sử dụng: Trang bạn truy cập, thời gian truy cập, liên kết bạn click</li>
                    <li>Cookies và công nghệ tương tự để cải thiện trải nghiệm của bạn</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>3. Cách chúng tôi sử dụng thông tin</h2>
                <p>Chúng tôi sử dụng thông tin thu thập được để:</p>
                <ul>
                    <li>Cung cấp, duy trì và cải thiện dịch vụ của chúng tôi</li>
                    <li>Xử lý đăng ký sự kiện và thanh toán của bạn</li>
                    <li>Gửi thông báo về sự kiện, cập nhật và thông tin quan trọng</li>
                    <li>Hỗ trợ khách hàng và phản hồi yêu cầu của bạn</li>
                    <li>Phát hiện, ngăn chặn và giải quyết các vấn đề kỹ thuật, gian lận hoặc bảo mật</li>
                    <li>Tuân thủ các nghĩa vụ pháp lý và quy định</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>4. Chia sẻ thông tin</h2>
                <p>Chúng tôi không bán thông tin cá nhân của bạn. Chúng tôi có thể chia sẻ thông tin trong các trường hợp sau:</p>
                <ul>
                    <li><strong>Nhà cung cấp dịch vụ:</strong> Với các bên thứ ba giúp chúng tôi vận hành dịch vụ (như dịch vụ thanh toán, hosting)</li>
                    <li><strong>Yêu cầu pháp lý:</strong> Khi được yêu cầu bởi pháp luật hoặc cơ quan có thẩm quyền</li>
                    <li><strong>Bảo vệ quyền lợi:</strong> Để bảo vệ quyền, tài sản hoặc an toàn của chúng tôi, người dùng hoặc người khác</li>
                    <li><strong>Với sự đồng ý của bạn:</strong> Trong các trường hợp khác với sự đồng ý rõ ràng của bạn</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>5. Bảo mật thông tin</h2>
                <p>
                    Chúng tôi áp dụng các biện pháp bảo mật kỹ thuật và tổ chức phù hợp để bảo vệ thông tin cá nhân của bạn 
                    khỏi truy cập trái phép, mất mát, phá hủy hoặc thay đổi. Tuy nhiên, không có phương thức truyền tải 
                    qua Internet hoặc lưu trữ điện tử nào là 100% an toàn.
                </p>
            </div>

            <div class="policy-section">
                <h2>6. Quyền của bạn</h2>
                <p>Bạn có quyền:</p>
                <ul>
                    <li>Truy cập và nhận bản sao thông tin cá nhân của bạn</li>
                    <li>Yêu cầu sửa đổi hoặc cập nhật thông tin không chính xác</li>
                    <li>Yêu cầu xóa thông tin cá nhân của bạn (theo quy định pháp luật)</li>
                    <li>Từ chối nhận email marketing (bạn có thể hủy đăng ký bất cứ lúc nào)</li>
                    <li>Rút lại sự đồng ý đã cung cấp trước đó</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2>7. Cookies</h2>
                <p>
                    Chúng tôi sử dụng cookies và công nghệ tương tự để cải thiện trải nghiệm của bạn, phân tích cách bạn sử dụng 
                    dịch vụ và hỗ trợ các tính năng. Bạn có thể kiểm soát cookies thông qua cài đặt trình duyệt của mình.
                </p>
            </div>

            <div class="policy-section">
                <h2>8. Liên kết đến trang web khác</h2>
                <p>
                    Dịch vụ của chúng tôi có thể chứa liên kết đến các trang web của bên thứ ba. Chúng tôi không chịu trách nhiệm 
                    về các chính sách quyền riêng tư hoặc nội dung của các trang web đó. Chúng tôi khuyến khích bạn đọc chính sách 
                    quyền riêng tư của bất kỳ trang web nào bạn truy cập.
                </p>
            </div>

            <div class="policy-section">
                <h2>9. Thay đổi chính sách</h2>
                <p>
                    Chúng tôi có thể cập nhật chính sách quyền riêng tư này theo thời gian. Chúng tôi sẽ thông báo cho bạn về 
                    bất kỳ thay đổi nào bằng cách đăng chính sách mới trên trang này và cập nhật ngày "Cập nhật lần cuối" ở cuối trang.
                </p>
            </div>

            <div class="policy-section">
                <h2>10. Liên hệ</h2>
                <p>Nếu bạn có câu hỏi về chính sách quyền riêng tư này, vui lòng liên hệ với chúng tôi:</p>
                <ul>
                    <li><strong>Email:</strong> info@eventmanagement.com</li>
                    <li><strong>Điện thoại:</strong> 0123 456 789</li>
                    <li><strong>Địa chỉ:</strong> 12 NVB, Gò Vấp, TP.HCM</li>
                </ul>
            </div>

            <div class="last-updated">
                <p><strong>Cập nhật lần cuối:</strong> <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>
    </div>

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
                        <li><a href="privacy-policy.php">Chính sách quyền riêng tư</a></li>
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
            <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center">
                <p>&copy; 2025 Event Management. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

