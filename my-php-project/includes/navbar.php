<?php
// Get user info
$user = null;
$userRole = 0;
if (isLoggedIn()) {
    $user = getCurrentUser();
    $userRole = $user['ID_Role'] ?? 0;
}
?>
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="img/logo/logo.jpg" alt="Logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'active' : ''; ?>" href="services.php">Dịch vụ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>" href="about.php">Giới thiệu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : ''; ?>" href="contact.php">Liên hệ</a>
                </li>
                <?php if ($user): ?>
                <!-- Chức năng dành cho khách hàng -->
                <?php if ($userRole == 5): ?>
                <li class="nav-item">
                    <a class="nav-link" href="events/register.php">
                        <i class="fa fa-calendar-plus me-1"></i>Đăng ký sự kiện
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="events/my-events.php">
                        <i class="fa fa-list-alt me-1"></i>Sự kiện của tôi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="chat.php">
                        <i class="fa fa-comments me-1"></i>Chat hỗ trợ
                    </a>
                </li>
                <?php else: ?>
                <!-- Chức năng dành cho admin/staff -->
                <li class="nav-item">
                    <a class="nav-link" href="events/register.php">
                        <i class="fa fa-calendar-plus me-1"></i>Đăng ký sự kiện
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="events/my-events.php">
                        <i class="fa fa-list-alt me-1"></i>Sự kiện của tôi
                    </a>
                </li>
                <?php if (in_array($userRole, [1, 2, 3, 4])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin/event-registrations.php">
                        <i class="fa fa-cog me-1"></i>Quản lý sự kiện
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
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

<style>
.navbar {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.navbar.scrolled {
    background: rgba(255, 255, 255, 0.98) !important;
    box-shadow: 0 4px 30px rgba(0,0,0,0.15);
}

.navbar-brand img {
    height: 40px;
    width: auto;
}

.navbar-nav .nav-link {
    color: #333 !important;
    font-weight: 500;
    padding: 0.5rem 1rem !important;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
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
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.btn-primary {
    background: linear-gradient(45deg, #667eea, #764ba2);
    border: none;
}

.btn-outline-primary {
    border: 2px solid #667eea;
    color: #667eea;
    border-radius: 25px;
    padding: 10px 28px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .navbar-brand img {
        height: 35px;
    }
}
</style>
