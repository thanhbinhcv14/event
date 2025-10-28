<?php
session_start();

// Kiểm tra session người dùng
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
    <meta name="description" content="Chính sách bảo mật của Hệ thống tổ chức sự kiện. Cam kết bảo vệ thông tin cá nhân và quyền riêng tư của khách hàng.">
    <meta name="keywords" content="chính sách bảo mật, event management, bảo vệ dữ liệu, quyền riêng tư, sự kiện, dịch vụ tổ chức sự kiện">
    <meta name="robots" content="index, follow">
    <title>Chính Sách Bảo Mật | Event Management</title>
    <link rel="icon" href="Hinh/logo/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            font-family: 'Segoe UI', sans-serif;
            color: #333;
            scroll-behavior: smooth;
        }
        
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

        .container {
            max-width: 1100px;
            margin: 100px auto 50px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            padding: 40px 50px;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            text-align: center;
            color: #4a4a4a;
            font-weight: bold;
            margin-bottom: 30px;
        }

        h2 {
            font-size: 1.4rem;
            color: #5e60ce;
            margin-top: 25px;
            border-left: 5px solid #5e60ce;
            padding-left: 10px;
        }

        p, li {
            line-height: 1.8;
            text-align: justify;
            color: #444;
        }

        ul { padding-left: 20px; }

        footer {
            background: rgba(0,0,0,0.8);
            color: #ccc;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
            font-size: 0.9rem;
        }

        a { color: #5e60ce; text-decoration: none; }
        a:hover { color: #5e60ce; }

        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #5e60ce;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            transition: all 0.3s ease;
        }

        .back-to-top.show { display: block; }
        .breadcrumb { background: none; margin-bottom: 20px; }
        
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
            
            .container {
                margin: 80px auto 30px;
                padding: 20px 30px;
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
                        <a class="nav-link active" href="privacy-policy.php">
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

<div class="container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb justify-content-center">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chính sách bảo mật</li>
        </ol>
    </nav>

    <h1><i class="fas fa-shield-alt"></i> Chính Sách Bảo Mật</h1>

    <div class="privacy-content">
        <h2>1. Giới thiệu</h2>
        <p>Chúng tôi – <strong>Hệ thống Tổ chức Sự kiện (Event Management)</strong> – cam kết bảo vệ quyền riêng tư và thông tin cá nhân của khách hàng, đối tác và người dùng. Chính sách này giải thích cách chúng tôi thu thập, sử dụng, lưu trữ và bảo vệ dữ liệu cá nhân của bạn khi sử dụng dịch vụ.</p>

        <h2>2. Mục đích và phạm vi thu thập thông tin</h2>
        <p>Khi bạn đăng ký tài khoản, đặt sự kiện hoặc liên hệ hỗ trợ, chúng tôi có thể thu thập các thông tin sau:</p>
        <ul>
            <li>Họ tên, email, số điện thoại, địa chỉ liên hệ.</li>
            <li>Thông tin thanh toán, hóa đơn, lịch sử đặt sự kiện.</li>
            <li>Dữ liệu kỹ thuật như IP, trình duyệt, thiết bị truy cập.</li>
        </ul>
        <p>Chúng tôi thu thập dữ liệu nhằm:</p>
        <ul>
            <li>Xác minh danh tính và xử lý yêu cầu đặt dịch vụ.</li>
            <li>Cung cấp hỗ trợ và cải thiện trải nghiệm người dùng.</li>
            <li>Gửi thông báo, ưu đãi và cập nhật dịch vụ (nếu bạn đồng ý).</li>
        </ul>

        <h2>3. Cách chúng tôi sử dụng thông tin</h2>
        <ul>
            <li>Quản lý tài khoản, giao dịch và đơn đặt sự kiện.</li>
            <li>Tư vấn, hỗ trợ kỹ thuật và chăm sóc khách hàng.</li>
            <li>Cải thiện chất lượng dịch vụ, phát triển tính năng mới.</li>
            <li>Tuân thủ quy định pháp luật liên quan đến giao dịch điện tử.</li>
        </ul>
        <p><strong>Chúng tôi không chia sẻ, bán hoặc trao đổi dữ liệu cá nhân của bạn</strong> cho bất kỳ bên thứ ba nào khi chưa có sự đồng ý của bạn.</p>

        <h2>4. Bảo mật dữ liệu</h2>
        <p>Chúng tôi áp dụng nhiều biện pháp kỹ thuật để bảo vệ thông tin cá nhân:</p>
        <ul>
            <li>Mã hóa kết nối bằng SSL/TLS.</li>
            <li>Lưu trữ trên máy chủ bảo mật, giới hạn quyền truy cập nội bộ.</li>
            <li>Kiểm tra, cập nhật hệ thống thường xuyên để phòng ngừa rò rỉ dữ liệu.</li>
        </ul>
        <p>Nếu xảy ra sự cố an ninh, chúng tôi sẽ thông báo kịp thời và phối hợp với cơ quan chức năng để xử lý.</p>

        <h2>5. Cookies và công nghệ theo dõi</h2>
        <p>Website có thể sử dụng cookies để ghi nhớ tùy chọn của bạn, phân tích hành vi truy cập và cải thiện trải nghiệm.  
        Bạn có thể tắt cookies trong trình duyệt, tuy nhiên một số tính năng có thể bị hạn chế.</p>

        <h2>6. Quyền của người dùng</h2>
        <ul>
            <li>Truy cập, chỉnh sửa hoặc xóa thông tin cá nhân.</li>
            <li>Yêu cầu ngừng nhận email quảng cáo.</li>
            <li>Đề nghị cung cấp bản sao dữ liệu cá nhân.</li>
        </ul>
        <p>Chúng tôi sẽ xử lý các yêu cầu trên một cách nhanh chóng và minh bạch.</p>

        <h2>7. Chia sẻ với bên thứ ba</h2>
        <p>Chúng tôi chỉ chia sẻ thông tin cá nhân trong các trường hợp sau:</p>
        <ul>
            <li>Đối tác thanh toán, vận chuyển hoặc hỗ trợ kỹ thuật.</li>
            <li>Cơ quan chức năng khi có yêu cầu hợp pháp.</li>
        </ul>

        <h2>8. Thời gian lưu trữ dữ liệu</h2>
        <p>Dữ liệu cá nhân được lưu trữ trong suốt thời gian bạn sử dụng dịch vụ hoặc tối đa 2 năm sau khi ngừng sử dụng, trừ khi pháp luật yêu cầu giữ lâu hơn.</p>

        <h2>9. Bảo vệ trẻ em</h2>
        <p>Chúng tôi không cố ý thu thập thông tin từ người dùng dưới 16 tuổi. Nếu phát hiện vi phạm, dữ liệu sẽ được xóa ngay lập tức để bảo vệ quyền riêng tư của trẻ em.</p>

        <h2>10. Cập nhật chính sách</h2>
        <p>Chính sách này có thể được thay đổi theo thời gian để phù hợp với quy định pháp luật và hoạt động của chúng tôi. Mọi cập nhật sẽ được thông báo công khai trên website.</p>

        <h2>11. Liên hệ</h2>
        <p>Nếu có bất kỳ câu hỏi hoặc yêu cầu nào liên quan đến Chính sách bảo mật, vui lòng liên hệ:</p>
        <ul>
            <li><strong>Email:</strong> support@eventmanagement.vn</li>
            <li><strong>Hotline:</strong> 0909 123 456</li>
            <li><strong>Địa chỉ:</strong> 123 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh</li>
        </ul>

        <p><em>Cập nhật lần cuối: 28/10/2025</em></p>
    </div>
</div>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Event Management. All rights reserved.</p>
</footer>

<button class="back-to-top" id="backToTopBtn"><i class="fas fa-arrow-up"></i></button>

<script>
const backToTop = document.getElementById('backToTopBtn');
window.addEventListener('scroll', () => {
    if (window.scrollY > 300) backToTop.classList.add('show');
    else backToTop.classList.remove('show');
});
backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
