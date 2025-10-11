<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="../img/logo/logo.jpg">
    <title>Đăng ký - Hệ thống sự kiện</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../img/banner/banner2.jpg') center/cover;
            opacity: 0.2;
            z-index: 1;
        }
        
        .container-register-center {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            padding: 20px;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 3rem 2.5rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 700px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-section img {
            height: 60px;
            width: auto;
            margin-bottom: 1rem;
        }
        
        .register-title {
            color: #333;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .register-subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 0;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #333;
            font-size: 0.95rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .text-center {
            text-align: center;
            margin-top: 20px;
        }
        
        .link-login {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: color 0.3s ease;
        }
        
        .link-login:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .alert {
            display: none;
            margin-bottom: 20px;
            border-radius: 12px;
            border: none;
            padding: 12px 16px;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: -15px;
            margin-bottom: 20px;
            padding: 8px 12px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }
        
        .form-check {
            margin-bottom: 20px;
            padding-left: 2rem;
        }
        
        .form-check-input {
            width: 1.2rem;
            height: 1.2rem;
            margin-top: 0.1rem;
            border: 2px solid #e9ecef;
            border-radius: 6px;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .form-check-label {
            font-size: 0.95rem;
            color: #555;
            line-height: 1.4;
        }
        
        .social-section {
            border-top: 1px solid #e9ecef;
        }
        
        .social-title {
            color: #666;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-facebook {
            background: #3b5998;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .btn-facebook:hover {
            background: #2d4373;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 89, 152, 0.4);
        }
        
        .btn-google {
            background: #dd4b39;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .btn-google:hover {
            background: #c23321;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(221, 75, 57, 0.4);
        }
        
        .btn-facebook i, .btn-google i {
            font-size: 1.2rem;
        }
        
        .social-buttons {
            display: flex;
            gap: 12px;
        }
        
        .back-to-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            z-index: 3;
        }
        
        .back-to-home:hover {
            color: #f8f9fa;
            transform: translateX(-5px);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .mb-3 {
            flex: 1;
        }
        
        /* Email Section Styles */
        .email-section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        
        .email-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .email-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .email-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 15px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .email-title h4 {
            color: #333;
            font-weight: 700;
            font-size: 1.3rem;
            margin: 0 0 5px 0;
        }
        
        .email-title p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
            font-weight: 500;
        }
        
        .email-input {
            border: 2px solid #667eea !important;
            background: white !important;
            font-size: 1.1rem !important;
            padding: 15px 20px !important;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
        }
        
        .email-input:focus {
            border-color: #764ba2 !important;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
        }
        
        .email-note {
            margin-top: 8px;
            padding: 8px 12px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }
        
        .email-note small {
            color: #555;
            font-weight: 500;
        }
        
        /* Birthday Input Styles */
        .birthday-label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #333;
            font-size: 0.95rem;
        }
        
        .help-icon {
            color: #999;
            font-size: 0.8rem;
            cursor: help;
        }
        
        .birthday-inputs {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .birthday-select {
            flex: 1;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .birthday-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
            outline: none;
        }
        
        .birthday-select option {
            padding: 8px;
        }
        
        @media (max-width: 1200px) {
            .container-register-center {
                padding: 0 1rem;
            }
        }
        
        @media (max-width: 992px) {
            .register-container {
                padding: 2.5rem 2rem;
                margin: 1rem;
            }
            
            .register-title {
                font-size: 2.2rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        
        @media (max-width: 768px) {
            .container-register-center {
                padding: 1rem;
            }
            
            .register-container {
                padding: 2rem 1.5rem;
                margin: 0.5rem;
                max-width: 100%;
            }
            
            .register-title {
                font-size: 2rem;
            }
            
            .form-control, .form-select {
                padding: 12px 16px;
                font-size: 1rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 12px 20px;
                font-size: 1rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .email-header {
                flex-direction: column;
                text-align: center;
            }
            
            .email-icon {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .birthday-inputs {
                flex-direction: column;
                gap: 8px;
            }
            
            .back-to-home {
                top: 15px;
                left: 15px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .register-container {
                padding: 1.5rem 1rem;
                margin: 0.25rem;
            }
            
            .register-title {
                font-size: 1.8rem;
            }
            
            .form-control, .form-select {
                padding: 10px 14px;
                font-size: 0.95rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 10px 16px;
                font-size: 0.95rem;
            }
            
            .back-to-home {
                top: 10px;
                left: 10px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .container-register-center {
                padding: 0.5rem;
            }
            
            .register-container {
                padding: 1rem 0.8rem;
                margin: 0;
            }
            
            .register-title {
                font-size: 1.6rem;
            }
            
            .form-control, .form-select {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-to-home">
        <i class="fa fa-arrow-left me-2"></i>Về trang chủ
    </a>
    
    <div class="container-register-center">
        <div class="register-container">
            <div class="logo-section">
                <img src="../img/logo/logo.jpg" alt="Logo">
                <h1 class="register-title">Đăng ký tài khoản</h1>
            </div>
            
            <div class="alert alert-danger" role="alert"></div>
            
            <form id="registerForm" autocomplete="off">
                <div class="form-row">
                <div class="mb-3">
                        <label for="fullname" class="form-label">
                            <i class="fa fa-user me-2"></i>Họ và tên
                        </label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required placeholder="Nhập họ và tên">
                </div>
                <div class="mb-3">
                        <label for="phone" class="form-label">
                            <i class="fa fa-phone me-2"></i>Số điện thoại
                        </label>
                        <input type="tel" class="form-control" id="phone" name="phone" required placeholder="Nhập số điện thoại">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">
                        <i class="fa fa-map-marker-alt me-2"></i>Địa chỉ
                    </label>
                    <input type="text" class="form-control" id="address" name="address" required placeholder="Nhập địa chỉ">
                </div>
                
                <div class="mb-3">
                    <label class="form-label birthday-label">
                        <i class="fa fa-calendar me-2"></i>Ngày sinh
                        <i class="fa fa-question-circle ms-2 help-icon" title="Chọn ngày sinh của bạn"></i>
                    </label>
                    <div class="birthday-inputs">
                        <select class="form-control birthday-select" id="day" name="day" required>
                            <option value="">Ngày</option>
                        </select>
                        <select class="form-control birthday-select" id="month" name="month" required>
                            <option value="">Tháng</option>
                        </select>
                        <select class="form-control birthday-select" id="year" name="year" required>
                            <option value="">Năm</option>
                        </select>
                    </div>
                    <input type="hidden" id="birthday" name="birthday">
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label email-label">
                        <i class="fa fa-envelope me-2"></i>Email đăng nhập
                        <small class="email-subtitle">(Sẽ dùng để đăng nhập)</small>
                    </label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Nhập email của bạn">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fa fa-lock me-2"></i>Mật khẩu
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Nhập mật khẩu">
                    <div class="password-requirements">
                        <i class="fa fa-info-circle me-1"></i>
                        Mật khẩu phải có ít nhất 6 ký tự, bao gồm chữ hoa, chữ thường và số
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">
                        <i class="fa fa-lock me-2"></i>Xác nhận mật khẩu
                    </label>
                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required placeholder="Nhập lại mật khẩu">
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="terms" required>
                    <label class="form-check-label" for="terms">
                        Tôi đồng ý với <a href="#" class="link-login">điều khoản sử dụng</a> và <a href="#" class="link-login">chính sách bảo mật</a>
                    </label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-user-plus me-2"></i>Đăng ký
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <p>Đã có tài khoản? <a href="login.php" class="link-login">Đăng nhập ngay</a></p>
                </div>
            </form>
            
            <!-- Social Login Section -->
            <div class="social-section">
                <p class="social-title text-center">Hoặc đăng ký bằng</p>
                <div class="social-buttons">
                    <a href="social-login.php?provider=Facebook" class="btn btn-facebook d-flex align-items-center justify-content-center">
                        <i class="fab fa-facebook-f me-2"></i> Facebook
                    </a>
                    <a href="social-login.php?provider=Google" class="btn btn-google d-flex align-items-center justify-content-center">
                        <i class="fab fa-google me-2"></i> Google
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Populate birthday dropdowns
            function populateBirthdayDropdowns() {
                // Populate days (1-31)
                const daySelect = $('#day');
                for (let i = 1; i <= 31; i++) {
                    daySelect.append(`<option value="${i}">${i}</option>`);
                }
                
                // Populate months
                const monthSelect = $('#month');
                const months = [
                    'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                    'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
                ];
                months.forEach((month, index) => {
                    monthSelect.append(`<option value="${index + 1}">${month}</option>`);
                });
                
                // Populate years (current year - 100 to current year)
                const yearSelect = $('#year');
                const currentYear = new Date().getFullYear();
                for (let i = currentYear; i >= currentYear - 100; i--) {
                    yearSelect.append(`<option value="${i}">${i}</option>`);
                }
            }
            
            // Update hidden birthday field when dropdowns change
            function updateBirthdayField() {
                const day = $('#day').val();
                const month = $('#month').val();
                const year = $('#year').val();
                
                if (day && month && year) {
                    const birthday = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                    $('#birthday').val(birthday);
                } else {
                    $('#birthday').val('');
                }
            }
            
            // Initialize dropdowns
            populateBirthdayDropdowns();
            
            // Bind change events
            $('#day, #month, #year').on('change', updateBirthdayField);
            
            function showAlert(message, type = 'danger') {
                const alert = $('.alert');
                alert.removeClass('alert-success alert-danger')
                     .addClass(`alert-${type}`)
                     .text(message)
                     .fadeIn();
                setTimeout(() => {
                    alert.fadeOut();
                }, 3000);
            }

            function validatePassword(password) {
                const regex = /^(?=[A-Z])(?=.*[a-zA-Z])(?=.*\d)[A-Za-z\d]{6,}$/;
                return regex.test(password);
            }
            function validateEmail(email) {
                // Regex kiểm tra định dạng email chuẩn
                const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return regex.test(email);
            }
            function validatePhoneVN(phone) {
                // Regex cho số điện thoại VN
                const regex = /^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/;
                return regex.test(phone);
            }


            $('#registerForm').on('submit', function(e) {
                e.preventDefault();

                const fullname = $('#fullname').val();
                const phone = $('#phone').val();
                const address = $('#address').val();
                const birthday = $('#birthday').val();
                const email = $('#email').val();
                const password = $('#password').val();
                const confirmPassword = $('#confirmPassword').val();
                const roleId = 5; // Mặc định là khách hàng

                if (!validatePassword(password)) {
                    showAlert('Mật khẩu phải có ít nhất 6 ký tự, bao gồm chữ hoa, chữ thường và số');
                    return;
                }
                if (!validateEmail(email)) {
                    showAlert('Email không hợp lệ, vui lòng nhập lại');
                    return;
                }


                if (password !== confirmPassword) {
                    showAlert('Mật khẩu xác nhận không khớp');
                    return;
                }

                $.ajax({
                    url: '../src/controllers/register.php',
                    type: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    data: JSON.stringify({
                        email: email,
                        password: password,
                        fullname: fullname,
                        phone: phone,
                        address: address,
                        birthday: birthday,
                        role: roleId
                    }),
                    success: function(response) {
                        if (response.message) {
                            showAlert('Đăng ký thành công!', 'success');
                            setTimeout(() => {
                                window.location.href = 'login.php';
                            }, 1000);
                        } else if (response.error) {
                            showAlert(response.error);
                        } else {
                            showAlert('Đăng ký thất bại. Vui lòng thử lại.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Có lỗi xảy ra, vui lòng thử lại sau';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            msg = xhr.responseJSON.error;
                        }
                        showAlert(msg);
                    }
                });
            });
        });
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</body>
</html>