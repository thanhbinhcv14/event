<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];
$userRole = $user['ID_Role'] ?? $user['role'] ?? null;
$userId = $user['ID_User'] ?? $user['id'] ?? null;

// Determine user type and table name
$userTable = '';
$userInfoTable = '';
$userInfoFields = [];

if ($userRole == 5) {
    // Customer
    $userTable = 'users';
    $userInfoTable = 'khachhanginfo';
    $userInfoFields = ['HoTen', 'SoDienThoai', 'DiaChi', 'NgaySinh'];
} else {
    // Staff (roles 1,2,3,4)
    $userTable = 'users';
    $userInfoTable = 'nhanvieninfo';
    $userInfoFields = ['HoTen', 'SoDienThoai', 'DiaChi', 'NgaySinh', 'ChucVu', 'Luong', 'NgayVaoLam'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="img/logo/logo.jpg">
    <title>Hồ sơ cá nhân - Hệ thống sự kiện</title>
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
            background: url('img/banner/banner1.jpg') center/cover;
            opacity: 0.2;
            z-index: 1;
        }
        
        .container-profile {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            padding: 20px;
        }
        
        .profile-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 3rem 2.5rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .profile-title {
            color: #333;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .profile-subtitle {
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
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .alert {
            display: none;
            margin-bottom: 20px;
            border-radius: 12px;
            border: none;
            padding: 12px 16px;
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
        
        .password-section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        
        .password-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .password-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .password-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            margin-right: 15px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .password-title {
            color: #333;
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-left: 0;
            padding: 8px 12px;
            min-width: 45px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .input-group .btn i {
            font-size: 16px;
            color: #6c757d;
        }
        
        .input-group .btn:hover i {
            color: #495057;
        }
        
        @media (max-width: 1200px) {
            .container-profile {
                padding: 0 1rem;
            }
        }
        
        @media (max-width: 992px) {
            .profile-container {
                padding: 2.5rem 2rem;
                margin: 1rem;
            }
            
            .profile-title {
                font-size: 2.2rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        
        @media (max-width: 768px) {
            .container-profile {
                padding: 1rem;
            }
            
            .profile-container {
                padding: 2rem 1.5rem;
                margin: 0.5rem;
                max-width: 100%;
            }
            
            .profile-title {
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
            .profile-container {
                padding: 1.5rem 1rem;
                margin: 0.25rem;
            }
            
            .profile-title {
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
            .container-profile {
                padding: 0.5rem;
            }
            
            .profile-container {
                padding: 1rem 0.8rem;
                margin: 0;
            }
            
            .profile-title {
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
    <a href="<?php echo $userRole == 5 ? 'index.php' : 'admin/index.php'; ?>" class="back-to-home">
        <i class="fa fa-arrow-left me-2"></i>Về trang chủ
    </a>
    
    <div class="container-profile">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fa fa-user"></i>
                </div>
                <h1 class="profile-title">Hồ sơ cá nhân</h1>
                <p class="profile-subtitle">Chỉnh sửa thông tin tài khoản của bạn</p>
            </div>
            
            <div class="alert alert-danger" role="alert"></div>
            <div class="alert alert-success" role="alert"></div>
            
            <form id="profileForm" autocomplete="off">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                
                <div class="form-row">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fa fa-envelope me-2"></i>Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['Email'] ?? $user['email'] ?? ''); ?>" readonly style="background-color: #f8f9fa; color: #6c757d;">
                        <small class="text-muted">Email không thể thay đổi</small>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            <i class="fa fa-phone me-2"></i>Số điện thoại
                        </label>
                        <input type="tel" class="form-control" id="phone" name="phone" required placeholder="Nhập số điện thoại">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="fullname" class="form-label">
                        <i class="fa fa-user me-2"></i>Họ và tên
                    </label>
                    <input type="text" class="form-control" id="fullname" name="fullname" required placeholder="Nhập họ và tên">
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
                
                <?php if ($userRole != 5): ?>
                <!-- Staff specific fields -->
                <div class="form-row">
                    <div class="mb-3">
                        <label for="chucvu" class="form-label">
                            <i class="fa fa-briefcase me-2"></i>Chức vụ
                        </label>
                        <input type="text" class="form-control" id="chucvu" name="chucvu" placeholder="VD: Nhân viên kỹ thuật">
                    </div>
                    <div class="mb-3">
                        <label for="luong" class="form-label">
                            <i class="fa fa-money-bill me-2"></i>Lương
                        </label>
                        <input type="number" class="form-control" id="luong" name="luong" min="0" step="0.01" placeholder="VD: 8000000">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label birthday-label">
                        <i class="fa fa-calendar me-2"></i>Ngày vào làm
                        <i class="fa fa-question-circle ms-2 help-icon" title="Chọn ngày vào làm"></i>
                    </label>
                    <div class="birthday-inputs">
                        <select class="form-control birthday-select" id="day-start" name="day-start">
                            <option value="">Ngày</option>
                        </select>
                        <select class="form-control birthday-select" id="month-start" name="month-start">
                            <option value="">Tháng</option>
                        </select>
                        <select class="form-control birthday-select" id="year-start" name="year-start">
                            <option value="">Năm</option>
                        </select>
                    </div>
                    <input type="hidden" id="ngayvaolam" name="ngayvaolam">
                </div>
                <?php endif; ?>
                
                <!-- Password Change Section -->
                <div class="password-section">
                    <div class="password-header">
                        <div class="password-icon">
                            <i class="fa fa-lock"></i>
                        </div>
                        <h4 class="password-title">Thay đổi mật khẩu</h4>
                    </div>
                    <p class="text-muted mb-3">Để thay đổi mật khẩu, vui lòng nhập mật khẩu mới. Bỏ trống nếu không muốn thay đổi.</p>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            <i class="fa fa-lock me-2"></i>Mật khẩu mới
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', this)">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fa fa-lock me-2"></i>Xác nhận mật khẩu
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu mới">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-2"></i>Cập nhật thông tin
                    </button>
                    <a href="<?php echo $userRole == 5 ? 'index.php' : 'admin/index.php'; ?>" class="btn btn-secondary">
                        <i class="fa fa-arrow-left me-2"></i>Quay lại
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load user data
            loadUserData();
            
            // Populate birthday dropdowns
            function populateBirthdayDropdowns() {
                // Populate days (1-31)
                for (let i = 1; i <= 31; i++) {
                    $('#day, #day-start').append(`<option value="${i}">${i}</option>`);
                }
                
                // Populate months
                const months = [
                    'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                    'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
                ];
                months.forEach((month, index) => {
                    $('#month, #month-start').append(`<option value="${index + 1}">${month}</option>`);
                });
                
                // Populate years (current year - 100 to current year)
                const currentYear = new Date().getFullYear();
                for (let i = currentYear; i >= currentYear - 100; i--) {
                    $('#year, #year-start').append(`<option value="${i}">${i}</option>`);
                }
            }
            
            // Update hidden birthday field when dropdowns change
            function updateBirthdayField(dayId, monthId, yearId, hiddenId) {
                const day = $(dayId).val();
                const month = $(monthId).val();
                const year = $(yearId).val();
                
                if (day && month && year) {
                    const birthday = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                    $(hiddenId).val(birthday);
                } else {
                    $(hiddenId).val('');
                }
            }
            
            // Initialize dropdowns
            populateBirthdayDropdowns();
            
            // Bind change events for birthday
            $('#day, #month, #year').on('change', function() {
                updateBirthdayField('#day', '#month', '#year', '#birthday');
            });
            
            // Bind change events for ngayvaolam (if exists)
            $('#day-start, #month-start, #year-start').on('change', function() {
                updateBirthdayField('#day-start', '#month-start', '#year-start', '#ngayvaolam');
            });
            
            // Load user data
            function loadUserData() {
                console.log('Loading user data...'); // Debug log
                $.get('src/controllers/profile.php?action=get', function(data) {
                    console.log('Profile data response:', data); // Debug log
                    if (data.success) {
                        const user = data.user;
                        
                        // Fill form fields
                        $('#email').val(user.Email || '');
                        $('#phone').val(user.SoDienThoai || '');
                        $('#fullname').val(user.HoTen || '');
                        $('#address').val(user.DiaChi || '');
                        
                        // Set birthday dropdowns
                        if (user.NgaySinh) {
                            const birthday = new Date(user.NgaySinh);
                            $('#day').val(birthday.getDate());
                            $('#month').val(birthday.getMonth() + 1);
                            $('#year').val(birthday.getFullYear());
                            $('#birthday').val(user.NgaySinh);
                        }
                        
                        // Set staff fields if applicable
                        if (user.ChucVu) $('#chucvu').val(user.ChucVu);
                        if (user.Luong) $('#luong').val(user.Luong);
                        
                        if (user.NgayVaoLam) {
                            const ngayvaolam = new Date(user.NgayVaoLam);
                            $('#day-start').val(ngayvaolam.getDate());
                            $('#month-start').val(ngayvaolam.getMonth() + 1);
                            $('#year-start').val(ngayvaolam.getFullYear());
                            $('#ngayvaolam').val(user.NgayVaoLam);
                        }
                    } else {
                        showAlert(data.error || 'Không thể tải thông tin người dùng', 'danger');
                    }
                }, 'json').fail(function(xhr, status, error) {
                    console.error('AJAX Error Details:');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response Text:', xhr.responseText);
                    console.error('Response Status:', xhr.status);
                    showAlert('Lỗi kết nối: ' + xhr.status + ' - ' + error, 'danger');
                });
            }
            
            // Form submission
            $('#profileForm').on('submit', function(e) {
                e.preventDefault();
                
                const newPassword = $('#new_password').val();
                const confirmPassword = $('#confirm_password').val();
                
                // Validate password if provided
                if (newPassword || confirmPassword) {
                    if (!newPassword) {
                        showAlert('Vui lòng nhập mật khẩu mới', 'danger');
                        return;
                    }
                    if (!confirmPassword) {
                        showAlert('Vui lòng xác nhận mật khẩu', 'danger');
                        return;
                    }
                    if (newPassword !== confirmPassword) {
                        showAlert('Mật khẩu xác nhận không khớp', 'danger');
                        return;
                    }
                    if (!validatePassword(newPassword)) {
                        showAlert('Mật khẩu phải có ít nhất 6 ký tự, bao gồm chữ hoa, chữ thường và số', 'danger');
                        return;
                    }
                }
                
                // Validate other fields
                const phone = $('#phone').val();
                if (!validatePhoneVN(phone)) {
                    showAlert('Số điện thoại không hợp lệ', 'danger');
                    return;
                }
                
                const fullname = $('#fullname').val();
                if (!fullname || fullname.length < 2) {
                    showAlert('Họ tên tối thiểu 2 ký tự', 'danger');
                    return;
                }
                
                const address = $('#address').val();
                if (!address || address.trim() === '') {
                    showAlert('Địa chỉ không được để trống', 'danger');
                    return;
                }
                
                const birthday = $('#birthday').val();
                if (!birthday) {
                    showAlert('Ngày sinh không được để trống', 'danger');
                    return;
                }
                
                // Submit form
                $.post('src/controllers/profile.php', $(this).serialize(), function(response) {
                    if (response.success) {
                        showAlert('Cập nhật thông tin thành công!', 'success');
                        // Clear password fields
                        $('#new_password, #confirm_password').val('');
                    } else {
                        showAlert(response.error || 'Cập nhật thất bại', 'danger');
                    }
                }, 'json').fail(function() {
                    showAlert('Lỗi kết nối, vui lòng thử lại', 'danger');
                });
            });
        });
        
        function showAlert(message, type = 'danger') {
            $('.alert').hide();
            $(`.alert-${type}`).text(message).fadeIn();
            setTimeout(() => {
                $(`.alert-${type}`).fadeOut();
            }, 3000);
        }
        
        function validatePassword(password) {
            const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,}$/;
            return regex.test(password);
        }
        
        function validatePhoneVN(phone) {
            const regex = /^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/;
            return regex.test(phone);
        }
        
        function togglePassword(inputId, toggleBtn) {
            const input = document.getElementById(inputId);
            const icon = toggleBtn.querySelector('i');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            
            input.setAttribute('type', type);
            
            // Toggle between eye and eye-slash icons
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
