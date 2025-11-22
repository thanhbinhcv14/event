<?php
session_start();

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
    <title>Trang chủ - Hệ thống tổ chức sự kiện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="img/logo/logo.jpg">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Sidebar Styles - Màu dịu nhẹ đồng bộ với chat.php */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 300px;
            background: linear-gradient(135deg, #c5d9f0 0%, #d5c9ed 50%, #e5c9ea 100%);
            color: #333;
            z-index: 1000;
            transform: translateX(-100%);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            box-shadow: 0 0 30px rgba(197, 217, 240, 0.3);
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .sidebar-header {
            padding: 30px 20px;
            border-bottom: 1px solid rgba(197, 217, 240, 0.3);
            text-align: center;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(10px);
        }
        
        
        .sidebar-header h5 {
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        
        .sidebar-header small {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .user-avatar {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .user-avatar img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .user-avatar:hover img {
            transform: scale(1.05);
            border-color: rgba(255, 255, 255, 0.6);
        }
        
        .status-indicator {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 16px;
            height: 16px;
            background: #4ade80;
            border: 3px solid white;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(74, 222, 128, 0); }
            100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
        }
        
        .sidebar-menu {
            padding: 25px 0;
        }
        
        .sidebar-menu .menu-item {
            display: flex;
            align-items: center;
            padding: 18px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 4px solid transparent;
            position: relative;
            font-weight: 500;
        }
        
        .sidebar-menu .menu-item:hover {
            background: rgba(255, 255, 255, 0.15);
            border-left-color: #fff;
            color: white;
            transform: translateX(5px);
            box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu .menu-item i {
            width: 24px;
            margin-right: 15px;
            font-size: 1.1rem;
            text-align: center;
        }
        
        .sidebar-menu .menu-item.active {
            background: rgba(255, 255, 255, 0.2);
            border-left-color: #fff;
            font-weight: 600;
            transform: translateX(8px);
        }
        
        .sidebar-menu .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.2), transparent);
            transition: width 0.3s ease;
        }
        
        .sidebar-menu .menu-item:hover::before {
            width: 100%;
        }
        
        .sidebar-menu .menu-group {
            margin: 30px 0;
        }
        
        .sidebar-menu .menu-group-title {
            padding: 15px 25px 10px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
            position: relative;
        }
        
        .sidebar-menu .menu-group-title::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 25px;
            width: 30px;
            height: 2px;
            background: linear-gradient(90deg, #fff, transparent);
        }
        
        .sidebar-toggle {
            position: fixed;
            top: 80px;
            left: 25px;
            z-index: 1100;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 1.4rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }
        
        .sidebar-toggle:hover {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6);
            background: linear-gradient(135deg, #5a6fe0, #8a4dc5, #ff6b9d);
        }
        
        .sidebar-toggle i {
            transition: transform 0.4s ease;
        }
        
        /* Ẩn sidebar và toggle cho khách hàng */
        .customer-role .sidebar-toggle,
        .customer-role .sidebar,
        .customer-role .sidebar-overlay {
            display: none !important;
        }
        
        /* Điều chỉnh main content cho khách hàng */
        .customer-role .main-content {
            margin-left: 0 !important;
        }
        
        /* Sidebar scrollbar styling */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        /* Sidebar animation effects */
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }
        
        .sidebar.show::before {
            transform: translateX(100%);
        }
        
        /* Khi sidebar mở, icon xoay */
        .sidebar.show ~ .sidebar-toggle i,
        .sidebar.show + * .sidebar-toggle i {
            transform: rotate(90deg);
        }
        
        /* Alternative selector for icon rotation */
        body.sidebar-open .sidebar-toggle i {
            transform: rotate(90deg);
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .main-content {
            transition: margin-left 0.3s ease;
        }
        
        .main-content.sidebar-open {
            margin-left: 280px;
        }
        
        /* Role-specific styling */
        .role-admin { border-left: 3px solid #dc3545; }
        .role-manager { border-left: 3px solid #fd7e14; }
        .role-event-manager { border-left: 3px solid #20c997; }
        .role-staff { border-left: 3px solid #6f42c1; }
        .role-customer { border-left: 3px solid #0dcaf0; }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
            }
            
            .main-content.sidebar-open {
                margin-left: 0;
            }
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .banner-carousel {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
        }
        
        .banner-slide {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 2s ease-in-out;
        }
        
        .banner-slide.active {
            opacity: 0.3;
        }
        
        .banner-slide:nth-child(1) {
            background-image: url('img/banner/banner1.jpg');
        }
        
        .banner-slide:nth-child(2) {
            background-image: url('img/banner/banner2.jpg');
        }
        
        .banner-slide:nth-child(3) {
            background-image: url('img/banner/banner3.jpeg');
        }
        
        .banner-indicators {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 3;
        }
        
        .banner-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .banner-dot.active {
            background: white;
            transform: scale(1.2);
        }
        
        .hero-image-container {
            position: relative;
            display: inline-block;
            width: 100%;
            height: 400px;
            overflow: hidden;
            border-radius: 15px;
        }
        
        .hero-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 2s ease-in-out;
            border-radius: 15px;
        }
        
        .hero-image.active {
            opacity: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
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
        
        /* Right side buttons - compact and aligned right */
        .navbar .d-flex.gap-1 {
            margin-left: 1rem;
            margin-right: 0;
        }
        
        /* Reduce padding for navbar buttons */
        .navbar .btn {
            padding: 0.4rem 0.8rem !important;
            font-size: 0.9rem;
        }
        
        .navbar .navbar-event-btn {
            padding: 8px 18px !important;
            font-size: 0.9rem;
        }
        
        /* Align buttons to right edge */
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
        
        .navbar-nav .nav-link i {
            margin-right: 0.5rem;
            font-size: 0.9rem;
            width: 16px;
            text-align: center;
        }
        
        .navbar-nav .dropdown-toggle::after {
            margin-left: 0.5rem;
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
        
        /* Discount Cart Icon Styles */
        .discount-cart-btn {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .discount-cart-btn:hover {
            transform: scale(1.1);
        }
        
        .discount-cart-btn .badge {
            font-size: 0.7rem;
            padding: 0.25em 0.5em;
            animation: pulse-badge 2s ease-in-out infinite;
        }
        
        @keyframes pulse-badge {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        
        /* Navbar discount cart link styles */
        .nav-link.position-relative {
            padding-right: 1.5rem !important;
        }
        
        .nav-link .badge {
            font-size: 0.65rem;
            padding: 0.2em 0.5em;
            animation: pulse-badge 2s ease-in-out infinite;
            margin-left: 5px;
            min-width: 18px;
            text-align: center;
            line-height: 1.2;
            z-index: 10;
        }
        
        .nav-link:hover .badge {
            animation-play-state: paused;
        }
        
        /* Ensure badge is visible when displayed */
        #discountCartBadge[style*="display: block"],
        #discountCartBadge:not([style*="display: none"]) {
            display: inline-block !important;
        }
        
        /* Discount Cart Modal Styles */
        .list-group-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .list-group-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .list-group-item .badge {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            letter-spacing: 0.5px;
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
            border-color: rgba(197, 217, 240, 0.6);
        }
        
        .navbar-event-dropdown .dropdown-item:first-child:hover {
            background: linear-gradient(135deg, #e5eff8 0%, #f0e8f7 100%);
            transform: translateX(5px) scale(1.02);
            border-color: rgba(102, 126, 234, 0.4);
            box-shadow: 0 4px 12px rgba(197, 217, 240, 0.3);
        }
        
        .navbar-event-dropdown .dropdown-item i {
            color: #667eea;
            width: 20px;
            text-align: center;
        }
        
        .navbar-event-dropdown .dropdown-divider {
            margin: 8px 0;
            border-color: rgba(197, 217, 240, 0.3);
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
        
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        /* Event Card Styles */
        .event-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }
        
        .event-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .event-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .event-card:hover .event-image {
            transform: scale(1.05);
        }
        
        .event-content {
            padding: 1.5rem;
        }
        
        .event-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .event-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .event-meta {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        .event-meta i {
            margin-right: 0.5rem;
            color: #667eea;
        }
        
        .event-location {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: #666;
        }
        
        .event-location i {
            margin-right: 0.5rem;
            color: #667eea;
        }
        
        .event-budget {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #28a745;
            font-weight: 600;
        }
        
        .event-budget i {
            margin-right: 0.5rem;
        }
        
        .event-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-event-detail {
            flex: 1;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-event-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-event-register {
            flex: 1;
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-event-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .event-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .event-status.status-upcoming {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }
        
        .event-status.status-ongoing {
            background: linear-gradient(45deg, #ffc107, #e0a800);
            animation: pulse 2s infinite;
        }
        
        .event-status.status-completed {
            background: linear-gradient(45deg, #6c757d, #495057);
        }
        
        .event-status.status-default {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .no-events {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
        }
        
        .no-events i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }
        
        /* Discount Code Card Styles */
        .discount-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .discount-card .text-center {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .discount-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #ff9800, #ff5722);
        }
        
        .discount-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #ffc107;
        }
        
        .discount-code-badge {
            display: inline-block;
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 1px;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
            text-transform: uppercase;
        }
        
        .discount-code-badge.badge-percent {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        }
        
        .discount-code-badge.badge-amount {
            background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);
        }
        
        .discount-value {
            font-size: 2rem;
            font-weight: 700;
            color: #28a745;
            margin: 1rem 0;
        }
        
        .discount-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            min-height: 40px;
        }
        
        .discount-conditions {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .discount-conditions i {
            color: #ffc107;
            margin-right: 0.5rem;
        }
        
        .btn-save-discount {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            margin-top: auto;
            cursor: pointer;
        }
        
        .btn-save-discount:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .btn-save-discount.saved {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
        
        .btn-save-discount.saved:hover {
            background: linear-gradient(135deg, #5a6268 0%, #3d4146 100%);
        }
        
        .discount-end-date {
            font-size: 0.85rem;
            color: #dc3545;
            margin-top: 0.5rem;
            font-weight: 500;
        }
        
        .no-discount-codes {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
        }
        
        .no-discount-codes i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .banner-section {
            background: url('img/banner/banner2.jpg') center/cover;
            padding: 80px 0;
            color: white;
            position: relative;
        }
        
        .banner-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
        }
        
        .banner-content {
            position: relative;
            z-index: 2;
        }
        
        .stats-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 60px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 50px 0 20px;
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
        
        .floating-chat-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse-glow 2s infinite;
        }
        
        .floating-chat-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }
        
        .floating-chat-btn.pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            }
            50% {
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4), 0 0 0 10px rgba(102, 126, 234, 0.1);
            }
            100% {
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            }
        }
        
        @keyframes pulse-glow {
            0% { 
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            }
            50% { 
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.6), 0 0 0 8px rgba(102, 126, 234, 0.2);
            }
            100% { 
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            }
        }
        
        /* Chat Widget Styles */
        .chat-widget {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 10000;
            transform: translateY(100%);
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .chat-widget.show {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .chat-widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-widget-body {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            min-height: 0;
        }
        
        .chat-widget-footer {
            padding: 15px;
            border-top: 1px solid #dee2e6;
            background: white;
            border-radius: 0 0 15px 15px;
        }
        
        .message {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
        }
        
        .message.user {
            justify-content: flex-end;
        }
        
        .message.assistant {
            justify-content: flex-start;
        }
        
        .message-content {
            max-width: 80%;
            padding: 0.75rem 1rem;
            border-radius: 18px;
            position: relative;
        }
        
        .message.user .message-content {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.assistant .message-content {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #e9ecef;
            border-bottom-left-radius: 4px;
        }
        
        /* Typing indicator animation */
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 12px 16px;
            background: #f0f0f0;
            border-radius: 18px;
            min-width: 60px;
            justify-content: center;
        }
        
        .typing-indicator span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            animation: typing-bounce 1.4s ease-in-out infinite;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
        }
        
        .typing-indicator span:nth-child(1) {
            animation-delay: 0s;
        }
        
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing-bounce {
            0%, 60%, 100% {
                transform: translateY(0) scale(1);
                opacity: 0.6;
            }
            30% {
                transform: translateY(-12px) scale(1.1);
                opacity: 1;
            }
        }
        
        /* Loading indicator trong message assistant */
        .message.assistant #loadingIndicator,
        #loadingIndicator {
            margin-bottom: 12px;
        }
        
        .message.assistant .typing-indicator {
            background: #f5f5f5;
        }
        
        /* Style for links in chat messages */
        .message-content a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px solid transparent;
            transition: all 0.3s ease;
        }
        
        .message-content a:hover {
            color: #764ba2;
            border-bottom-color: #764ba2;
            text-decoration: none;
        }
        
        .message-content a:visited {
            color: #667eea;
        }
        
        
        .quick-suggestions {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 15px;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            border: 1px solid #dee2e6;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 10 !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }
        
        .suggestion-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
            border: 2px solid #e9ecef !important;
            border-radius: 25px !important;
            padding: 12px 16px !important;
            cursor: pointer !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-size: 0.9rem !important;
            color: #495057 !important;
            font-weight: 500 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            position: relative !important;
            overflow: hidden !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .suggestion-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }
        
        .suggestion-item:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            border-color: #667eea;
        }
        
        .suggestion-item:hover::before {
            left: 100%;
        }
        
        .suggestion-item i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }
        
        .suggestion-item:hover i {
            transform: scale(1.1);
        }
        
        .chat-message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .chat-message.sent {
            justify-content: flex-end;
        }
        
        .chat-message.received {
            justify-content: flex-start;
        }
        
        .message-bubble {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .message-bubble.sent {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .message-bubble.received {
            background: white;
            color: #333;
            border: 1px solid #dee2e6;
            border-bottom-left-radius: 5px;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .chat-input-group {
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 8px 15px;
            outline: none;
            font-size: 0.9rem;
        }
        
        .chat-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .chat-send-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .chat-send-btn:hover {
            transform: scale(1.1);
        }
        
        .chat-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1001;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 70px;
            height: 70px;
            font-size: 1.8rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5), 
                        0 6px 15px rgba(118, 75, 162, 0.4),
                        0 0 0 0 rgba(102, 126, 234, 0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            backdrop-filter: blur(15px);
            animation: float 3s ease-in-out infinite, pulse-glow 2s ease-in-out infinite;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-toggle:hover {
            transform: scale(1.2) translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.7), 
                        0 10px 25px rgba(118, 75, 162, 0.5),
                        0 0 0 12px rgba(102, 126, 234, 0.15);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 30%, #f093fb 70%, #f5576c 100%);
            animation-play-state: paused;
        }
        
        .chat-toggle:active {
            transform: scale(1.05) translateY(0px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
            animation: ripple 0.6s ease-out;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-8px);
            }
        }
        
        @keyframes ripple {
            0% {
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5),
                            0 0 0 0 rgba(102, 126, 234, 0.7);
            }
            50% {
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5),
                            0 0 0 20px rgba(102, 126, 234, 0.3);
            }
            100% {
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5),
                            0 0 0 40px rgba(102, 126, 234, 0);
            }
        }
        
        .chat-toggle .fa-comments {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @keyframes pulse-glow {
            0% { 
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5), 
                            0 6px 15px rgba(118, 75, 162, 0.4),
                            0 0 0 0 rgba(102, 126, 234, 0.3);
            }
            50% { 
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6), 
                            0 6px 15px rgba(118, 75, 162, 0.5),
                            0 0 0 8px rgba(102, 126, 234, 0.2);
            }
            100% { 
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5), 
                            0 6px 15px rgba(118, 75, 162, 0.4),
                            0 0 0 0 rgba(102, 126, 234, 0.3);
            }
        }
        
        .online-indicator {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 8px;
            animation: blink 1s infinite;
        }
        
        /* Chat Toggle Tooltip */
        .chat-toggle::before {
            content: "Chat với nhân viên hỗ trợ";
            position: absolute;
            right: 80px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1002;
            backdrop-filter: blur(10px);
        }
        
        .chat-toggle::after {
            content: "";
            position: absolute;
            right: 70px;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-left-color: rgba(0, 0, 0, 0.8);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1002;
        }
        
        .chat-toggle:hover::before,
        .chat-toggle:hover::after {
            opacity: 1;
            visibility: visible;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .container {
                padding: 0 1rem;
            }
            
            .hero-section h1 {
                font-size: 3rem;
            }
            
            .service-card {
                margin-bottom: 1.5rem;
            }
        }
        
        @media (max-width: 992px) {
            .hero-section {
                padding: 80px 0;
            }
            
            .hero-section h1 {
                font-size: 2.8rem;
            }
            
            .hero-section p {
                font-size: 1.2rem;
            }
            
            .banner-content {
                text-align: center;
            }
            
            .banner-content .col-lg-4 {
                margin-top: 2rem;
            }
            
            .stat-item {
                margin-bottom: 2rem;
            }
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
            
            .navbar-toggler {
                border: none;
                padding: 0.25rem 0.5rem;
            }
            
            .navbar-toggler:focus {
                box-shadow: none;
            }
            
            .sidebar-toggle {
                top: 70px;
                left: 15px;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .floating-chat-btn {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .chat-toggle {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                bottom: 20px;
                right: 20px;
            }
            
            .chat-toggle::before {
                right: 70px;
                font-size: 0.8rem;
                padding: 6px 10px;
            }
            
            .chat-toggle::after {
                right: 60px;
            }
            
            .hero-section {
                padding: 60px 0;
                text-align: center;
            }
            
            .hero-section h1 {
                font-size: 2.5rem;
                line-height: 1.2;
            }
            
            .hero-section p {
                font-size: 1.1rem;
                margin-bottom: 2rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }
            
            .hero-buttons .btn {
                width: 100%;
                max-width: 300px;
                padding: 12px 24px;
            }
            
            .service-card {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .service-icon {
                margin: 0 auto 1rem;
            }
            
            .banner-content {
                text-align: center;
            }
            
            .banner-content .col-lg-4 {
                margin-top: 2rem;
            }
            
            .banner-content ul {
                text-align: left;
            }
            
            .stat-item {
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .footer-content {
                text-align: center;
            }
            
            .footer-content .col-md-3 {
                margin-bottom: 2rem;
            }
            
            .social-links {
                justify-content: center;
                margin-top: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .hero-section p {
                font-size: 1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .service-card {
                padding: 1.5rem;
            }
            
            .service-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .stat-item {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .banner-content .bg-white {
                padding: 1.5rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .floating-chat-btn {
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
            }
            
            .chat-toggle {
                width: 55px;
                height: 55px;
                font-size: 1.3rem;
                bottom: 15px;
                right: 15px;
            }
            
            .chat-toggle::before {
                right: 65px;
                font-size: 0.75rem;
                padding: 5px 8px;
            }
            
            .chat-toggle::after {
                right: 55px;
            }
            
            .container {
                padding: 0 0.5rem;
            }
            
            .hero-section h1 {
                font-size: 1.8rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .service-card {
                padding: 1rem;
            }
            
            .stat-item {
                padding: 0.8rem;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body class="main-content <?php echo ($userRole == 5) ? 'customer-role' : ''; ?>">
    <!-- Sidebar Toggle Button -->
    <?php if ($user): ?>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <?php endif; ?>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <?php if ($user): ?>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="user-avatar">
                <img src="img/logo/logo.jpg" alt="Avatar">
                <div class="status-indicator"></div>
            </div>
            <h5 class="mb-0"><?php echo htmlspecialchars($user['Email'] ?? 'User'); ?></h5>
            <small class="text-light">
                <?php
                $roleNames = [
                    1 => 'Quản trị viên',
                    2 => 'Quản lý tổ chức', 
                    3 => 'Quản lý sự kiện',
                    4 => 'Nhân viên',
                    5 => 'Khách hàng'
                ];
                echo $roleNames[$userRole] ?? 'Khách hàng';
                ?>
            </small>
        </div>
        
        <div class="sidebar-menu">
            <!-- Dashboard -->
            <div class="menu-group">
                <div class="menu-group-title">Tổng quan</div>
                <a href="#home" class="menu-item" onclick="toggleSidebar()">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
                <a href="profile.php" class="menu-item">
                    <i class="fas fa-user"></i> Thông tin cá nhân
                </a>
            </div>
            
            <?php if (in_array($userRole, [1, 2])): ?>
            <!-- Admin/Manager Functions -->
            <div class="menu-group">
                <div class="menu-group-title">Quản lý</div>
                
                <!-- Event Management -->
                <a href="admin/event-registrations.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i> Duyệt sự kiện
                </a>
                
                <!-- Customer Management -->
                <a href="admin/customeredit_content.php" class="menu-item">
                    <i class="fas fa-users"></i> Quản lý khách hàng
                </a>
                
                <!-- Staff Management -->
                <a href="admin/accstaff.php" class="menu-item">
                    <i class="fas fa-user-tie"></i> Quản lý nhân viên
                </a>
                
                <!-- Device Management -->
                <a href="admin/device.php" class="menu-item">
                    <i class="fas fa-tools"></i> Quản lý thiết bị
                </a>
                
                <!-- Location Management -->
                <a href="admin/locations.php" class="menu-item">
                    <i class="fas fa-map-marker-alt"></i> Quản lý địa điểm
                </a>
                
                <!-- Statistics -->
                <a href="admin/index.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i> Thống kê báo cáo
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($userRole == 3): ?>
            <!-- Event Manager Functions -->
            <div class="menu-group">
                <div class="menu-group-title">Sự kiện</div>
                
                <!-- Event Management -->
                <a href="admin/event-registrations.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i> Xem đăng ký sự kiện
                </a>
                
                <!-- Customer Management -->
                <a href="admin/customeredit_content.php" class="menu-item">
                    <i class="fas fa-users"></i> Quản lý khách hàng
                </a>
                
                <!-- Location Management -->
                <a href="admin/locations.php" class="menu-item">
                    <i class="fas fa-map-marker-alt"></i> Quản lý địa điểm
                </a>
                
                <!-- Statistics -->
                <a href="admin/index.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i> Thống kê báo cáo
                </a>
            </div>
            <?php endif; ?>
            <?php if (in_array($userRole, [1, 2, 3, 4, 5])): ?>
            <!-- Chat System -->
            <div class="menu-group">
                <div class="menu-group-title">Hỗ trợ</div>
                <a href="chat.php" class="menu-item">
                    <i class="fas fa-comments"></i> Chat trực tuyến
                </a>
                <!-- <?php if (in_array($userRole, [1, 2, 3, 4])): ?>
                <a href="admin/chat-support.php" class="menu-item">
                    <i class="fas fa-headset"></i> Chat hỗ trợ
                </a>
                <?php endif; ?> -->
            </div>
            <?php endif; ?>
            
            <?php if ($userRole == 5): ?>
            <!-- Customer Functions -->
            <div class="menu-group">
                <div class="menu-group-title">Sự kiện</div>
                <a href="events/register.php" class="menu-item">
                    <i class="fas fa-calendar-plus"></i> Đăng ký sự kiện
                </a>
                <a href="events/my-events.php" class="menu-item">
                    <i class="fas fa-list-alt"></i> Sự kiện của tôi
                </a>
            </div>
            
            <div class="menu-group">
                <div class="menu-group-title">Hỗ trợ</div>
                <a href="chat.php" class="menu-item">
                    <i class="fas fa-comments"></i> Chat trực tuyến
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Account -->
            <div class="menu-group">
                <div class="menu-group-title">Tài khoản</div>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                        <a class="nav-link" href="privacy-policy.php">
                            <i class="fas fa-shield-alt me-1"></i>Chính sách bảo mật
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

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <!-- Animated Banner Carousel -->
        <div class="banner-carousel">
            <div class="banner-slide active"></div>
            <div class="banner-slide"></div>
            <div class="banner-slide"></div>
        </div>
        
        <!-- Banner Indicators -->
        <div class="banner-indicators">
            <div class="banner-dot active" data-slide="0"></div>
            <div class="banner-dot" data-slide="1"></div>
            <div class="banner-dot" data-slide="2"></div>
        </div>
        
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="display-4 fw-bold mb-4">Tổ chức sự kiện chuyên nghiệp</h1>
                    <p class="lead mb-4">Chúng tôi cung cấp dịch vụ tổ chức sự kiện hoàn hảo với đội ngũ chuyên nghiệp và trang thiết bị hiện đại.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="services.php" class="btn btn-primary btn-lg">
                            <i class="fa fa-calendar-alt me-2"></i>Xem dịch vụ
                        </a>
                        <?php if (!$user): ?>
                        <a href="register.php" class="btn btn-outline-light btn-lg">
                            <i class="fa fa-user-plus me-2"></i>Đăng ký ngay
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-image-container">
                        <img src="img/banner/banner1.jpg" alt="Event Planning" class="img-fluid rounded-3 shadow-lg hero-image active" data-banner="0">
                        <img src="img/banner/banner2.jpg" alt="Event Planning" class="img-fluid rounded-3 shadow-lg hero-image" data-banner="1">
                        <img src="img/banner/banner3.jpeg" alt="Event Planning" class="img-fluid rounded-3 shadow-lg hero-image" data-banner="2">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Events Section -->
    <section id="featured-events" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Sự kiện nổi bật</h2>
                <p class="lead text-muted">Khám phá các sự kiện đang diễn ra, sắp diễn ra và đã hoàn thành</p>
            </div>
            <div class="row g-4" id="events-container">
                <!-- Events will be loaded here via JavaScript -->
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Dịch vụ của chúng tôi</h2>
                <p class="lead text-muted">Cung cấp giải pháp tổ chức sự kiện toàn diện</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-birthday-cake"></i>
                        </div>
                        <h4>Tiệc sinh nhật</h4>
                        <p>Tổ chức tiệc sinh nhật đáng nhớ với không gian ấm cúng và dịch vụ chuyên nghiệp.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-heart"></i>
                        </div>
                        <h4>Đám cưới</h4>
                        <p>Làm cho ngày cưới của bạn trở nên hoàn hảo với dịch vụ tổ chức đám cưới cao cấp.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-briefcase"></i>
                        </div>
                        <h4>Sự kiện doanh nghiệp</h4>
                        <p>Tổ chức hội nghị, hội thảo và các sự kiện doanh nghiệp chuyên nghiệp.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-graduation-cap"></i>
                        </div>
                        <h4>Lễ tốt nghiệp</h4>
                        <p>Kỷ niệm thành tích học tập với lễ tốt nghiệp trang trọng và ý nghĩa.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-music"></i>
                        </div>
                        <h4>Concert & Show</h4>
                        <p>Tổ chức các buổi biểu diễn, concert với hệ thống âm thanh ánh sáng chuyên nghiệp.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fa fa-calendar-check"></i>
                        </div>
                        <h4>Sự kiện tùy chỉnh</h4>
                        <p>Thiết kế và tổ chức sự kiện theo yêu cầu riêng của khách hàng.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Discount Codes Section -->
    <section id="discount-codes" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Mã giảm giá đang có</h2>
                <p class="lead text-muted">Nhấn "Lưu lại" để thêm mã vào giỏ. Xem mã đã lưu bằng icon <i class="fas fa-ticket-alt text-warning"></i> trên thanh menu</p>
            </div>
            <div class="row g-4" id="discountCodesContainer">
                <!-- Discount codes will be loaded here via JavaScript -->
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
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

    <!-- Banner Section -->
    <section class="banner-section">
        <div class="container">
            <div class="row align-items-center banner-content">
                <div class="col-lg-8">
                    <h2 class="display-5 fw-bold mb-4">Tại sao chọn chúng tôi?</h2>
                    <ul class="list-unstyled">
                        <li class="mb-3"><i class="fa fa-check-circle me-2"></i> Đội ngũ chuyên nghiệp với nhiều năm kinh nghiệm</li>
                        <li class="mb-3"><i class="fa fa-check-circle me-2"></i> Trang thiết bị hiện đại, chất lượng cao</li>
                        <li class="mb-3"><i class="fa fa-check-circle me-2"></i> Dịch vụ khách hàng 24/7</li>
                        <li class="mb-3"><i class="fa fa-check-circle me-2"></i> Giá cả cạnh tranh, minh bạch</li>
                        <li class="mb-3"><i class="fa fa-check-circle me-2"></i> Cam kết chất lượng 100%</li>
                    </ul>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="bg-white rounded-3 p-4 shadow">
                        <h4 class="text-dark mb-3">Đặt dịch vụ ngay</h4>
                        <?php if ($user): ?>
                            <a href="events/register.php" class="btn btn-primary btn-lg w-100">
                                <i class="fa fa-calendar-plus me-2"></i>Đăng ký sự kiện
                            </a>
                        <?php else: ?>
                            <p class="text-muted mb-3">Vui lòng đăng ký để đặt dịch vụ</p>
                            <a href="register.php" class="btn btn-primary btn-lg w-100">
                                <i class="fa fa-user-plus me-2"></i>Đăng ký ngay
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">500</div>
                        <div>Sự kiện đã tổ chức</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">1000</div>
                        <div>Khách hàng hài lòng</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">5</div>
                        <div>Năm kinh nghiệm</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div>Hỗ trợ khách hàng</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                        <li><a href="privacy-policy.php">Chính sách bảo mật</a></li>
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

    <!-- Chat Widget - Hỗ trợ trực tuyến (Available for all users) -->
    <div class="chat-widget" id="chatWidget">
        <div class="chat-widget-header">
            <div class="d-flex align-items-center">
                <i class="fas fa-headset me-2"></i>
                <div>
                    <h6 class="mb-0">Hỗ trợ trực tuyến</h6>
                    <small>Nhân viên tư vấn</small>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-light" onclick="closeChatWidget()" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="chat-widget-body" id="chatMessages">
            
            <!-- Quick Suggestions -->
            <div class="quick-suggestions" id="quickSuggestions">
                <div class="suggestion-item" onclick="sendQuickMessage('Tôi muốn đăng ký sự kiện')">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Đăng ký sự kiện</span>
                </div>
                <div class="suggestion-item" onclick="sendQuickMessage('Tôi muốn xem giá dịch vụ')">
                    <i class="fas fa-dollar-sign"></i>
                    <span>Xem giá</span>
                </div>
                <div class="suggestion-item" onclick="sendQuickMessage('Tôi muốn thanh toán')">
                    <i class="fas fa-credit-card"></i>
                    <span>Thanh toán</span>
                </div>
                <div class="suggestion-item" onclick="sendQuickMessage('Tôi muốn kiểm tra trạng thái sự kiện')">
                    <i class="fas fa-search"></i>
                    <span>Trạng thái</span>
                </div>
                <div class="suggestion-item" onclick="sendQuickMessage('Tôi cần hỗ trợ')">
                    <i class="fas fa-question-circle"></i>
                    <span>Hỗ trợ</span>
                </div>
                <div class="suggestion-item" onclick="sendQuickMessage('Tôi muốn hủy sự kiện')">
                    <i class="fas fa-times-circle"></i>
                    <span>Hủy sự kiện</span>
                </div>
            </div>
        </div>
        
        <div class="chat-widget-footer">
            <div class="input-group">
                <input type="text" class="form-control" id="chatInput" placeholder="Nhập câu hỏi..." maxlength="500">
                <button class="btn btn-primary" type="button" onclick="sendChatMessage()" id="sendChatBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Chat Button -->
    <button class="floating-chat-btn" onclick="openChatWidget()" title="Chat hỗ trợ trực tuyến" id="floatingChatBtn">
        <i class="fas fa-comments"></i>
    </button>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chat Widget Script -->
    <script src="assets/js/gemini-chat-widget.js"></script>
    <!-- Socket.IO with fallback -->
    <script>
        // Auto-detect Socket.IO server URL
        const getSocketServerURL = function() {
            const protocol = window.location.protocol;
            if (window.location.hostname.includes('sukien.info.vn')) {
                // ✅ QUAN TRỌNG: Dùng wss:// (secure WebSocket) cho production
                // Nếu trang web dùng HTTPS, dùng wss:// cho WebSocket
                if (protocol === 'https:') {
                    return 'wss://ws.sukien.info.vn';  // Secure WebSocket
                } else {
                    return 'ws://ws.sukien.info.vn';   // Non-secure WebSocket (chỉ cho development)
                }
            }
            return 'http://localhost:3000';  // Localhost development
        };
        
        const socketServerURL = getSocketServerURL();
        
        // Try to load Socket.IO from WebSocket server first
        const socketScript = document.createElement('script');
        socketScript.src = socketServerURL + '/socket.io/socket.io.js';
        socketScript.onerror = function() {
            console.warn('WebSocket server not available, using CDN fallback');
            const cdnScript = document.createElement('script');
            cdnScript.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
            cdnScript.onload = function() {
                console.log('Socket.IO loaded from CDN');
            };
            cdnScript.onerror = function() {
                console.error('Failed to load Socket.IO from both WebSocket server and CDN');
            };
            document.head.appendChild(cdnScript);
        };
        socketScript.onload = function() {
            console.log('Socket.IO loaded from WebSocket server:', socketServerURL);
        };
        document.head.appendChild(socketScript);
    </script>
    <script>
        // Sidebar Toggle Functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const mainContent = document.querySelector('.main-content');
            const body = document.body;
            
            if (sidebar && overlay && mainContent) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                mainContent.classList.toggle('sidebar-open');
                body.classList.toggle('sidebar-open');
            }
        }
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (sidebar && sidebar.classList.contains('show')) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    toggleSidebar();
                }
            }
        });
        
        // Close sidebar on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar && sidebar.classList.contains('show')) {
                    toggleSidebar();
                }
            }
        });
        
        // Banner Carousel Functionality
        class BannerCarousel {
            constructor() {
                this.slides = document.querySelectorAll('.banner-slide');
                this.dots = document.querySelectorAll('.banner-dot');
                this.heroImages = document.querySelectorAll('.hero-image');
                this.currentSlide = 0;
                this.slideInterval = null;
                this.init();
            }

            init() {
                this.startAutoSlide();
                this.addDotListeners();
                this.addPauseOnHover();
            }

            startAutoSlide() {
                this.slideInterval = setInterval(() => {
                    this.nextSlide();
                }, 4000); // Change slide every 4 seconds
            }

            nextSlide() {
                this.currentSlide = (this.currentSlide + 1) % this.slides.length;
                this.updateSlide();
            }

            goToSlide(slideIndex) {
                this.currentSlide = slideIndex;
                this.updateSlide();
            }

            updateSlide() {
                // Remove active class from all slides, dots, and hero images
                this.slides.forEach(slide => slide.classList.remove('active'));
                this.dots.forEach(dot => dot.classList.remove('active'));
                this.heroImages.forEach(image => image.classList.remove('active'));

                // Add active class to current slide, dot, and hero image
                this.slides[this.currentSlide].classList.add('active');
                this.dots[this.currentSlide].classList.add('active');
                this.heroImages[this.currentSlide].classList.add('active');
            }

            addDotListeners() {
                this.dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        this.goToSlide(index);
                        this.resetAutoSlide();
                    });
                });
            }

            addPauseOnHover() {
                const heroSection = document.querySelector('.hero-section');
                heroSection.addEventListener('mouseenter', () => {
                    clearInterval(this.slideInterval);
                });
                heroSection.addEventListener('mouseleave', () => {
                    this.startAutoSlide();
                });
            }

            resetAutoSlide() {
                clearInterval(this.slideInterval);
                this.startAutoSlide();
            }
        }

        // Initialize banner carousel when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            new BannerCarousel();
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                // Skip if href is just '#' or empty
                if (!href || href === '#' || href.length <= 1) {
                    return; // Let default behavior handle it
                }
                
                e.preventDefault();
                try {
                    const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    }
                } catch (error) {
                    console.warn('Invalid selector for smooth scroll:', href);
                }
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });
        
        // Chat Widget is now handled by chat-widget.php
        
        // ✅ Đã bỏ logic auto-hide chat widget để chat không bị đóng khi load trang
        // Chat widget sẽ giữ nguyên trạng thái mở/đóng dựa trên localStorage
    </script>
    
    
    <script>
        // Check if user is logged in (from PHP)
        const isUserLoggedIn = <?php echo isset($user) && $user ? 'true' : 'false'; ?>;
        
        // Ensure jQuery is loaded before chat widget
        $(document).ready(function() {
            console.log('jQuery loaded successfully');
            loadFeaturedEvents();
            loadDiscountCodes();
            
            // Start auto-refresh for discount codes
            startDiscountCodesAutoRefresh();
            
            // Refresh discount codes when page becomes visible (user switches back to tab)
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    console.log('Page became visible, refreshing discount codes...');
                    loadDiscountCodes(true);
                }
            });
            
            // Refresh discount codes when window gains focus
            $(window).on('focus', function() {
                console.log('Window gained focus, refreshing discount codes...');
                loadDiscountCodes(true);
            });
            
            // When user logs in, clear discount codes from previous user
            // This ensures each user only sees their own saved codes
            if (isUserLoggedIn) {
                const currentUserId = <?= json_encode($_SESSION['user']['ID_User'] ?? null) ?>;
                const lastUserId = localStorage.getItem('lastLoggedInUserId');
                
                // If different user logged in, clear saved discount codes
                if (lastUserId && lastUserId !== String(currentUserId)) {
                    console.log('Different user logged in, clearing saved discount codes');
                    localStorage.removeItem('savedDiscountCodes');
                    updateDiscountCartBadge();
                }
                
                // Store current user ID
                localStorage.setItem('lastLoggedInUserId', String(currentUserId));
            } else {
                // If user is not logged in, clear the stored user ID
                localStorage.removeItem('lastLoggedInUserId');
            }
            
            // Check and remove expired/used discount codes
            // This works for both logged in and not logged in users
            // For logged in users: removes used "first time" codes
            // For all users: removes expired codes
            checkAndRemoveUsedDiscountCodes();
            
            updateDiscountCartBadge();
            
            // Add Enter key listener for chat input
            document.getElementById('chatInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendChatMessage();
                }
            });
            
            // Auto-load discount cart when modal is shown
            $('#discountCartModal').on('show.bs.modal', function() {
                if (!isUserLoggedIn) {
                    // Close modal and redirect to login
                    $(this).modal('hide');
                    if (confirm('Bạn cần đăng nhập để xem mã giảm giá đã lưu. Bạn có muốn đăng nhập ngay không?')) {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                    }
                    return false;
                }
                loadDiscountCart();
            });
            
            // Check for pending discount codes after login (from sessionStorage)
            const pendingCodes = JSON.parse(sessionStorage.getItem('pendingDiscountCodes') || '[]');
            if (pendingCodes.length > 0 && isUserLoggedIn) {
                // Transfer pending codes to localStorage
                let savedCodes = getSavedDiscountCodes();
                pendingCodes.forEach(code => {
                    if (!savedCodes.includes(code)) {
                        savedCodes.push(code);
                    }
                });
                localStorage.setItem('savedDiscountCodes', JSON.stringify(savedCodes));
                sessionStorage.removeItem('pendingDiscountCodes');
                updateDiscountCartBadge();
                showNotification('Đã thêm ' + pendingCodes.length + ' mã giảm giá vào giỏ!', 'success');
            }
        });
        
        // Check and remove expired/used discount codes
        function checkAndRemoveUsedDiscountCodes() {
            const savedCodes = getSavedDiscountCodes();
            if (savedCodes.length === 0) {
                return;
            }
            
            // Check which codes are still available (not expired, not used, still active)
            // API get_available_codes already filters expired codes and used "first time" codes
            $.ajax({
                url: 'src/controllers/magiamgia-controller.php',
                method: 'GET',
                data: {
                    action: 'get_available_codes'
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.success && response.codes) {
                        const availableCodes = response.codes.map(c => c.code);
                        const removedCodes = [];
                        const updatedSavedCodes = savedCodes.filter(code => {
                            const isAvailable = availableCodes.includes(code);
                            if (!isAvailable) {
                                removedCodes.push(code);
                            }
                            return isAvailable;
                        });
                        
                        // If any codes were removed (expired or used), update localStorage
                        if (updatedSavedCodes.length !== savedCodes.length) {
                            localStorage.setItem('savedDiscountCodes', JSON.stringify(updatedSavedCodes));
                            updateDiscountCartBadge();
                            console.log('Removed expired/used discount codes from localStorage:', removedCodes);
                            
                            // Show notification if codes were removed (only if page is visible)
                            if (removedCodes.length > 0 && document.visibilityState === 'visible') {
                                const message = removedCodes.length === 1 
                                    ? `Mã giảm giá "${removedCodes[0]}" đã hết hạn hoặc đã được sử dụng và đã được xóa khỏi giỏ.`
                                    : `${removedCodes.length} mã giảm giá đã hết hạn hoặc đã được sử dụng và đã được xóa khỏi giỏ.`;
                                showNotification(message, 'info');
                            }
                        }
                    } else {
                        // If API returns no codes, check if all saved codes are expired
                        // Only clear if we have saved codes but API returns none
                        if (savedCodes.length > 0) {
                            localStorage.setItem('savedDiscountCodes', JSON.stringify([]));
                            updateDiscountCartBadge();
                            console.log('All discount codes expired or inactive, cleared localStorage');
                            
                            // Show notification if page is visible
                            if (document.visibilityState === 'visible') {
                                showNotification('Tất cả mã giảm giá đã lưu đã hết hạn và đã được xóa khỏi giỏ.', 'info');
                            }
                        }
                    }
                },
                error: function() {
                    console.error('Error checking expired/used discount codes');
                    // On error, don't clear codes - keep them until next successful check
                }
            });
        }
        
        // Load discount codes with cache busting
        function loadDiscountCodes(forceRefresh = false) {
            console.log('Loading discount codes...', forceRefresh ? '(forced refresh)' : '');
            
            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();
            
            $.ajax({
                url: 'src/controllers/magiamgia-controller.php',
                method: 'GET',
                data: {
                    action: 'get_available_codes',
                    _t: timestamp  // Cache busting parameter
                },
                cache: false,  // Disable browser cache
                dataType: 'json',
                success: function(response) {
                    console.log('Discount codes API response:', response);
                    
                    // Handle string response (if JSON not parsed)
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error('Error parsing JSON response:', e);
                            displayNoDiscountCodes();
                            return;
                        }
                    }
                    
                    if (response && response.success && response.codes && Array.isArray(response.codes) && response.codes.length > 0) {
                        console.log('Displaying', response.codes.length, 'discount codes');
                        displayDiscountCodes(response.codes);
                    } else {
                        console.log('No discount codes found or invalid response');
                        if (response && response.error) {
                            console.error('API Error:', response.error);
                            showNotification('Không thể tải mã giảm giá: ' + response.error, 'error');
                        }
                        displayNoDiscountCodes();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading discount codes:', error);
                    console.error('XHR status:', xhr.status);
                    console.error('XHR response:', xhr.responseText);
                    console.error('Status:', status);
                    
                    // Try to parse response as JSON to see if there's an error message
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        console.error('Error response:', errorResponse);
                    } catch (e) {
                        console.error('Could not parse error response as JSON');
                    }
                    
                    displayNoDiscountCodes();
                }
            });
        }
        
        // Auto-refresh discount codes periodically (every 5 minutes)
        let discountCodesRefreshInterval = null;
        
        function startDiscountCodesAutoRefresh() {
            // Clear existing interval if any
            if (discountCodesRefreshInterval) {
                clearInterval(discountCodesRefreshInterval);
            }
            
            // Refresh every 5 minutes (300000 ms)
            discountCodesRefreshInterval = setInterval(function() {
                console.log('Auto-refreshing discount codes...');
                loadDiscountCodes(true);
            }, 300000); // 5 minutes
        }
        
        function stopDiscountCodesAutoRefresh() {
            if (discountCodesRefreshInterval) {
                clearInterval(discountCodesRefreshInterval);
                discountCodesRefreshInterval = null;
            }
        }
        
        // Display discount codes
        function displayDiscountCodes(codes) {
            const container = $('#discountCodesContainer');
            let html = '';
            
            if (!codes || codes.length === 0) {
                displayNoDiscountCodes();
                return;
            }
            
            codes.forEach(function(code) {
                const savedCodes = getSavedDiscountCodes();
                const isSaved = savedCodes.includes(code.code);
                
                const minAmountText = code.min_amount > 0 
                    ? `Đơn hàng tối thiểu: ${new Intl.NumberFormat('vi-VN').format(code.min_amount)} VNĐ` 
                    : 'Không có điều kiện tối thiểu';
                
                // Format end date properly - use server formatted date if available
                let endDateFormatted = code.end_date_display || 'Không xác định';
                if (!endDateFormatted || endDateFormatted === 'Không xác định') {
                    if (code.end_date) {
                        try {
                            // Handle both datetime and date formats
                            const endDate = new Date(code.end_date);
                            if (!isNaN(endDate.getTime())) {
                                endDateFormatted = endDate.toLocaleDateString('vi-VN', {
                                    day: '2-digit',
                                    month: '2-digit',
                                    year: 'numeric'
                                });
                            }
                        } catch (e) {
                            console.error('Error formatting date:', e);
                        }
                    }
                }
                
                // Determine badge color based on code type
                const badgeClass = code.type === 'Phần trăm' ? 'badge-percent' : 'badge-amount';
                
                html += `
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="discount-card">
                            <div class="text-center">
                                <div class="discount-code-badge ${badgeClass}">${code.code}</div>
                                <h5 class="mb-2 mt-3">${code.name || 'Mã giảm giá'}</h5>
                                <div class="discount-value">${code.display_text || 'Giảm giá'}</div>
                                <p class="discount-description">${code.description || 'Mã giảm giá đặc biệt'}</p>
                                <div class="discount-conditions">
                                    <i class="fas fa-info-circle"></i>
                                    ${minAmountText}
                                </div>
                                <div class="discount-end-date">
                                    <i class="fas fa-clock"></i> Hết hạn: ${endDateFormatted}
                                </div>
                                <button class="btn-save-discount ${isSaved ? 'saved' : ''}" 
                                        onclick="saveDiscountCode('${code.code}', this)"
                                        data-code="${code.code}"
                                        type="button">
                                    <i class="fas ${isSaved ? 'fa-check' : 'fa-shopping-cart'}"></i>
                                    ${isSaved ? 'Đã lưu' : 'Thêm vào giỏ'}
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.html(html);
        }
        
        // Display no discount codes message
        function displayNoDiscountCodes() {
            const container = $('#discountCodesContainer');
            container.html(`
                <div class="col-12">
                    <div class="no-discount-codes">
                        <i class="fas fa-ticket-alt"></i>
                        <h4>Hiện chưa có mã giảm giá nào</h4>
                        <p>Vui lòng quay lại sau để xem các mã giảm giá mới!</p>
                    </div>
                </div>
            `);
        }
        
        // Save discount code to localStorage
        function saveDiscountCode(code, buttonElement) {
            // Check if user is logged in
            if (!isUserLoggedIn) {
                // Show login prompt
                if (confirm('Bạn cần đăng nhập để lưu mã giảm giá. Bạn có muốn đăng nhập ngay không?')) {
                    // Save code to sessionStorage temporarily
                    let pendingCodes = JSON.parse(sessionStorage.getItem('pendingDiscountCodes') || '[]');
                    if (!pendingCodes.includes(code)) {
                        pendingCodes.push(code);
                        sessionStorage.setItem('pendingDiscountCodes', JSON.stringify(pendingCodes));
                    }
                    // Redirect to login with return URL
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href) + '&action=save_discount';
                }
                return;
            }
            
            let savedCodes = getSavedDiscountCodes();
            
            if (savedCodes.includes(code)) {
                // Remove from saved list
                savedCodes = savedCodes.filter(c => c !== code);
                localStorage.setItem('savedDiscountCodes', JSON.stringify(savedCodes));
                buttonElement.classList.remove('saved');
                buttonElement.innerHTML = '<i class="fas fa-shopping-cart"></i> Thêm vào giỏ';
                showNotification('Đã xóa mã giảm giá khỏi giỏ', 'info');
                updateDiscountCartBadge();
                // Refresh cart modal if open
                if ($('#discountCartModal').hasClass('show')) {
                    loadDiscountCart();
                }
            } else {
                // Add to saved list
                savedCodes.push(code);
                localStorage.setItem('savedDiscountCodes', JSON.stringify(savedCodes));
                buttonElement.classList.add('saved');
                buttonElement.innerHTML = '<i class="fas fa-check"></i> Đã lưu';
                showNotification('Đã thêm mã giảm giá vào giỏ!', 'success');
                updateDiscountCartBadge();
                // Refresh cart modal if open
                if ($('#discountCartModal').hasClass('show')) {
                    loadDiscountCart();
                }
            }
        }
        
        // Update discount cart badge
        function updateDiscountCartBadge() {
            const savedCodes = getSavedDiscountCodes();
            const badge = document.getElementById('discountCartBadge');
            const badge2 = document.getElementById('discountCartBadge2');
            
            if (badge) {
                if (savedCodes.length > 0) {
                    badge.textContent = savedCodes.length;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
            
            if (badge2) {
                if (savedCodes.length > 0) {
                    badge2.textContent = savedCodes.length;
                    badge2.style.display = 'block';
                } else {
                    badge2.style.display = 'none';
                }
            }
        }
        
        // Open discount cart modal
        function openDiscountCartModal() {
            console.log('openDiscountCartModal called, isUserLoggedIn:', isUserLoggedIn);
            
            // Check if user is logged in
            if (!isUserLoggedIn) {
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
            
            // Update button on homepage if visible
            const button = document.querySelector(`button[data-code="${code}"]`);
            if (button) {
                button.classList.remove('saved');
                button.innerHTML = '<i class="fas fa-bookmark"></i> Lưu lại';
            }
            
            showNotification('Đã xóa mã khỏi giỏ', 'info');
        }
        
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
        
        // Load featured events
        function loadFeaturedEvents() {
            console.log('Loading featured events...');
            console.log('AJAX URL:', 'src/controllers/events.php?action=get_featured_events');
            
            $.ajax({
                url: 'src/controllers/events.php?action=get_featured_events',
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    console.log('AJAX request started...');
                },
                success: function(response) {
                    console.log('Events API response:', response);
                    console.log('Response type:', typeof response);
                    console.log('Response success:', response.success);
                    console.log('Response events length:', response.events ? response.events.length : 'undefined');
                    
                    if (response.success && response.events && response.events.length > 0) {
                        console.log('Displaying', response.events.length, 'events');
                        displayEvents(response.events);
                    } else {
                        console.log('No events found, displaying no events message');
                        console.log('Reason: success=' + response.success + ', events=' + (response.events ? response.events.length : 'undefined'));
                        displayNoEvents();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading events:', error);
                    console.error('XHR status:', xhr.status);
                    console.error('Response text:', xhr.responseText);
                    console.error('Status:', status);
                    displayNoEvents();
                }
            });
        }
        
        // Display events
        function displayEvents(events) {
            console.log('displayEvents called with:', events);
            const container = $('#events-container');
            console.log('Container found:', container.length);
            let html = '';
            
            events.forEach(function(event) {
                html += `
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="event-card">
                            <div class="event-status status-${getStatusClass(event.TrangThaiSuKien)}">${event.TrangThaiSuKien}</div>
                            <img src="${event.HinhAnhURL}" alt="${event.TenSuKien}" class="event-image">
                            <div class="event-content">
                                <h3 class="event-title">${event.TenSuKien}</h3>
                                <p class="event-description">${event.MoTa || 'Không có mô tả'}</p>
                                
                                <div class="event-meta">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>${formatDateTime(event.NgayBatDau)} - ${formatDateTime(event.NgayKetThuc)}</span>
                                </div>
                                
                                <div class="event-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>${event.TenDiaDiem}</span>
                                </div>
                                
                                <div class="event-meta">
                                    <i class="fas fa-users"></i>
                                    <span>${event.SoNguoiDuKien} người</span>
                                </div>
                                
                                <div class="event-budget">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>${event.NganSach}</span>
                                </div>
                                
                                <div class="event-actions">
                                    <button class="btn-event-detail" onclick="viewEventDetail(${event.ID_DatLich})">
                                        <i class="fas fa-eye"></i> Chi tiết
                                    </button>
                                    <button class="btn-event-register" onclick="registerForEvent(${event.ID_DatLich})">
                                        <i class="fas fa-calendar-plus"></i> Đăng ký
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            console.log('HTML generated:', html.substring(0, 200) + '...');
            container.html(html);
            console.log('HTML inserted into container');
        }
        
        // Get status class for styling
        function getStatusClass(status) {
            switch(status) {
                case 'Đang diễn ra':
                    return 'ongoing';
                case 'Sắp diễn ra':
                    return 'upcoming';
                case 'Đã hoàn thành':
                    return 'completed';
                default:
                    return 'default';
            }
        }
        
        // Format date and time
        function formatDateTime(dateTimeString) {
            if (!dateTimeString) return 'N/A';
            
            try {
                // Parse the date string (format: dd/mm/yyyy hh:mm)
                const parts = dateTimeString.split(' ');
                if (parts.length !== 2) return dateTimeString;
                
                const datePart = parts[0]; // dd/mm/yyyy
                const timePart = parts[1]; // hh:mm
                
                const dateParts = datePart.split('/');
                if (dateParts.length !== 3) return dateTimeString;
                
                const day = dateParts[0];
                const month = dateParts[1];
                const year = dateParts[2];
                
                // Create a more readable format
                return `${day}/${month}/${year} ${timePart}`;
            } catch (error) {
                console.error('Error formatting date:', error, dateTimeString);
                return dateTimeString;
            }
        }
        
        // Display no events message
        function displayNoEvents() {
            const container = $('#events-container');
            container.html(`
                <div class="col-12">
                    <div class="no-events">
                        <i class="fas fa-calendar-times"></i>
                        <h4>Chưa có sự kiện nào</h4>
                        <p>Hiện tại chưa có sự kiện nào được duyệt. Vui lòng quay lại sau!</p>
                    </div>
                </div>
            `);
        }
        
        // View event detail
        function viewEventDetail(eventId) {
            // Redirect to event detail page
            window.location.href = `event-detail.php?id=${eventId}`;
        }
        
        // Register for event
        function registerForEvent(eventId) {
            <?php if ($user): ?>
                // User is logged in, redirect to registration
                window.location.href = `events/register.php?event_id=${eventId}`;
            <?php else: ?>
                // User not logged in, redirect to login
                if (confirm('Bạn cần đăng nhập để đăng ký sự kiện. Bạn có muốn đăng nhập ngay không?')) {
                    window.location.href = 'login.php';
                }
            <?php endif; ?>
        }

        // Gemini AI Chat Widget - Load external script
        
    </script>
    
    
</body>
</html>
