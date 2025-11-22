<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="img/logo/logo.jpg">
    <title>Đăng nhập - Hệ thống sự kiện</title>
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
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('img/banner/banner1.jpg') center/cover;
            opacity: 0.2;
            z-index: 1;
        }
        
        .container-login-center {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            padding: 5px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 3rem 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-section img {
            height: 60px;
            width: auto;
            margin-bottom: 0.5rem;
        }
        
        .login-title {
            color: #333;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
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
        
        .link-register {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: color 0.3s ease;
        }
        
        .link-register:hover {
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
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert .btn {
            white-space: nowrap;
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
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .container-login {
                padding: 0 1rem;
            }
        }
        
        @media (max-width: 992px) {
            .login-container {
                padding: 2.5rem 2rem;
                margin: 1rem;
            }
            
            .login-title {
                font-size: 2.2rem;
            }
            
            .login-subtitle {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 768px) {
            .container-login {
                padding: 1rem;
            }
            
            .login-container {
                padding: 2rem 1.5rem;
                margin: 0.5rem;
                max-width: 100%;
            }
            
            .login-title {
                font-size: 2rem;
            }
            
            .login-subtitle {
                font-size: 1rem;
            }
            
            .form-control, .form-select {
                padding: 12px 16px;
                font-size: 1rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 12px 20px;
                font-size: 1rem;
            }
            
            .social-login {
                flex-direction: column;
                gap: 1rem;
            }
            
            .social-login .btn {
                width: 100%;
                justify-content: center;
            }
            
            .back-to-home {
                top: 15px;
                left: 15px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 1.5rem 1rem;
                margin: 0.25rem;
            }
            
            .login-title {
                font-size: 1.8rem;
            }
            
            .login-subtitle {
                font-size: 0.95rem;
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
            .container-login {
                padding: 0.5rem;
            }
            
            .login-container {
                padding: 1rem 0.8rem;
                margin: 0;
            }
            
            .login-title {
                font-size: 1.6rem;
            }
            
            .login-subtitle {
                font-size: 0.9rem;
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
    
    <div class="container-login-center">
        <div class="login-container">
            <div class="logo-section">
                <img src="img/logo/logo.jpg" alt="Logo">
                <h1 class="login-title">Đăng nhập</h1>
            </div>
            
            <div class="alert alert-danger" role="alert" style="display: none;"></div>
            
            <form id="loginForm" autocomplete="off">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fa fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="Nhập email của bạn">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fa fa-lock me-2"></i>Mật khẩu
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Nhập mật khẩu">
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-sign-in-alt me-2"></i>Đăng nhập
                    </button>
                   
                </div>
                <div class="text-center mt-3">
                    <p>Chưa có tài khoản? <a href="register.php" class="link-register">Đăng ký ngay</a></p>
                </div>
            </form>
            
            <!-- Social Login Section -->
            <div class="social-section">
                <p class="social-title text-center">Hoặc đăng nhập bằng</p>
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
            // Clear any existing alerts and form data
            $('.alert').hide();
            $('#email').val('');
            $('#password').val('');
            
            // Get CSRF token on page load
            let csrfToken = null;
            $.get('src/controllers/login.php?action=get_csrf_token', function(response) {
                if (response && response.success) {
                    csrfToken = response.csrf_token;
                    console.log('CSRF token loaded:', csrfToken ? 'OK' : 'FAILED');
                } else {
                    console.error('Failed to get CSRF token - invalid response:', response);
                }
            }).fail(function(xhr, status, error) {
                console.error('Failed to get CSRF token:', status, error);
                console.error('Response:', xhr.responseText);
            });
            
            function showAlert(message, type = 'danger') {
                const alert = $('.alert');
                alert.removeClass('alert-success alert-danger alert-warning')
                     .addClass(`alert-${type}`)
                     .html(`<i class="fas fa-exclamation-circle me-2"></i>${message}`)
                     .show();
            }

            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                const email = $('#email').val();
                const password = $('#password').val();

                console.log('Login attempt:', { email, password: '***' });
                
                // Validate input
                if (!email || !password) {
                    showAlert('Vui lòng nhập đầy đủ thông tin');
                    return;
                }
                
                // Check if CSRF token is available
                if (!csrfToken) {
                    showAlert('Đang tải token bảo mật, vui lòng thử lại...');
                    // Retry getting token
                    $.get('src/controllers/login.php?action=get_csrf_token', function(response) {
                        if (response && response.success) {
                            csrfToken = response.csrf_token;
                            console.log('CSRF token retried:', csrfToken ? 'OK' : 'FAILED');
                            // Retry login
                            $('#loginForm').submit();
                        } else {
                            showAlert('Không thể tải token bảo mật. Vui lòng tải lại trang.');
                        }
                    }).fail(function() {
                        showAlert('Không thể tải token bảo mật. Vui lòng tải lại trang.');
                    });
                    return;
                }
                
                console.log('Submitting login with CSRF token:', csrfToken ? 'Present' : 'Missing');

                 $.ajax({
                     url: 'src/controllers/login.php',
                     type: 'POST',
                     contentType: 'application/json',
                     data: JSON.stringify({ 
                         email: email, 
                         password: password,
                         csrf_token: csrfToken
                     }),
                     beforeSend: function() {
                         console.log('Sending login request...');
                     },
                     success: function(response) {
                         console.log('Login response:', response);
                         if (response.success && response.redirect) {
                             console.log('Login successful, redirecting...');
                             window.location.href = response.redirect;
                         } else if (response.error) {
                             showAlert(response.error || response.message || 'Có lỗi xảy ra');
                         }
                     },
                    error: function(xhr) {
                       console.error('Login error:', xhr);
                       let msg = 'Có lỗi xảy ra, vui lòng thử lại sau';
                       
                       if (xhr.status === 403) {
                           // CSRF token invalid
                           msg = 'Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang.';
                           // Refresh CSRF token
                           $.get('src/controllers/login.php?action=get_csrf_token', function(response) {
                               if (response.success) {
                                   csrfToken = response.csrf_token;
                               }
                           });
                       } else if (xhr.responseJSON) {
                           msg = xhr.responseJSON.error || xhr.responseJSON.message || msg;
                       } else if (xhr.responseText) {
                           try {
                               const response = JSON.parse(xhr.responseText);
                               msg = response.error || response.message || msg;
                           } catch (e) {
                               console.log('Response text:', xhr.responseText);
                           }
                       }
                       showAlert(msg);
                   }
                 });
             });
         });
    </script>
</body>
</html>
