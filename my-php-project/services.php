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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
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
        
        /* Service Cards Styles */
        .service-item-card {
            background: white;
            border-radius: 15px;
            padding: 0;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .service-item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
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
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        
        .service-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
            gap: 0.75rem;
        }
        
        .service-item-title {
            font-size: 1.1rem;
            font-weight: 600;
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
        
        .service-item-description {
            color: #6c757d;
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 0.75rem;
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
            padding-top: 0.75rem;
            border-top: 1px solid #e9ecef;
            margin-top: auto;
        }
        
        .service-item-capacity {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .btn-register-now {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
        }
        
        .btn-register-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .btn-register-now i {
            font-size: 0.85rem;
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
        
        /* Tab Styles - Modern Design */
        .nav-tabs {
            border-bottom: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 0.4rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 0.4rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            margin-right: 0;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background: transparent;
            font-size: 0.9rem;
        }
        
        .nav-tabs .nav-link i {
            font-size: 1.1rem;
        }
        
        .nav-tabs .nav-link.active {
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }
        
        .nav-tabs .nav-link:hover:not(.active) {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }
        
        /* Search Bar Styles */
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.75rem 1rem 0.75rem 3rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .search-box input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .search-results-info {
            margin-top: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
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
        
        /* Dropdown menu cho nút Sự kiện */
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
        
        /* Empty state styles */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        .empty-state h4 {
            color: #495057;
            margin-bottom: 0.5rem;
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
            
            .nav-tabs {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .nav-tabs .nav-link {
                padding: 0.75rem 1.5rem;
                width: 100%;
                justify-content: center;
            }
            
            .search-container {
                padding: 1rem;
            }
            
            .filter-buttons {
                justify-content: center;
            }
            
            .filter-btn {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
            
            .navbar-event-btn {
                margin-right: 0;
                margin-bottom: 10px;
                width: 100%;
                justify-content: center;
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
                        <a class="nav-link active" href="services.php">
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
                        <!-- Nút Sự kiện nổi bật bên phải -->
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
                        <!-- Search Bar for Locations -->
                        <div class="search-container">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="locationSearch" class="form-control" 
                                       placeholder="Tìm kiếm địa điểm theo tên, địa chỉ, loại địa điểm...">
                            </div>
                            <div class="filter-buttons" id="locationFilters">
                                <button class="filter-btn active" data-filter="all">Tất cả</button>
                                <button class="filter-btn" data-filter="Trong nhà">Trong nhà</button>
                                <button class="filter-btn" data-filter="Ngoài trời">Ngoài trời</button>
                            </div>
                            <div class="search-results-info" id="locationResultsInfo" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                <span id="locationResultsCount">0</span> kết quả tìm thấy
                            </div>
                        </div>
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
                        <!-- Search Bar for Equipment -->
                        <div class="search-container">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="equipmentSearch" class="form-control" 
                                       placeholder="Tìm kiếm thiết bị theo tên, loại, hãng sản xuất...">
                            </div>
                            <div class="filter-buttons" id="equipmentFilters">
                                <button class="filter-btn active" data-filter="all">Tất cả</button>
                                <button class="filter-btn" data-filter="Âm thanh">Âm thanh</button>
                                <button class="filter-btn" data-filter="Hình ảnh">Hình ảnh</button>
                                <button class="filter-btn" data-filter="Ánh sáng">Ánh sáng</button>
                                <button class="filter-btn" data-filter="Phụ trợ">Phụ trợ</button>
                            </div>
                            <div class="search-results-info" id="equipmentResultsInfo" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                <span id="equipmentResultsCount">0</span> kết quả tìm thấy
                            </div>
                        </div>
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
                        <!-- Search Bar for Combos -->
                        <div class="search-container">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="comboSearch" class="form-control" 
                                       placeholder="Tìm kiếm combo theo tên, mô tả...">
                            </div>
                            <div class="search-results-info" id="comboResultsInfo" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                <span id="comboResultsCount">0</span> kết quả tìm thấy
                            </div>
                        </div>
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
    
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
        });
        
        // Discount Cart Modal Functions
        const isUserLoggedIn = <?php echo isset($user) && $user ? 'true' : 'false'; ?>;
        
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
        
        // Update discount cart badge
        function updateDiscountCartBadge() {
            const savedCodes = getSavedDiscountCodes();
            const badge = document.getElementById('discountCartBadge');
            
            if (badge) {
                if (savedCodes.length > 0) {
                    badge.textContent = savedCodes.length;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
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
            showNotification('Đã xóa mã khỏi giỏ', 'info');
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
        
        // Update badge on page load
        $(document).ready(function() {
            updateDiscountCartBadge();
            
            // Auto-load discount cart when modal is shown
            $('#discountCartModal').on('show.bs.modal', function() {
                if (!isUserLoggedIn) {
                    $(this).modal('hide');
                    if (confirm('Bạn cần đăng nhập để xem mã giảm giá đã lưu. Bạn có muốn đăng nhập ngay không?')) {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                    }
                    return false;
                }
                loadDiscountCart();
            });
        });
        
        // Global data storage
        let allLocations = [];
        let allEquipment = [];
        let allCombos = [];
        
        // Current filters
        let locationFilter = 'all';
        let equipmentFilter = 'all';
        
        // Load services data
        document.addEventListener('DOMContentLoaded', function() {
            loadLocations();
            
            // Setup search and filter handlers
            setupSearchHandlers();
            setupFilterHandlers();
            
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
        
        // Setup search handlers
        function setupSearchHandlers() {
            // Location search
            const locationSearch = document.getElementById('locationSearch');
            if (locationSearch) {
                locationSearch.addEventListener('input', function() {
                    filterLocations();
                });
            }
            
            // Equipment search
            const equipmentSearch = document.getElementById('equipmentSearch');
            if (equipmentSearch) {
                equipmentSearch.addEventListener('input', function() {
                    filterEquipment();
                });
            }
            
            // Combo search
            const comboSearch = document.getElementById('comboSearch');
            if (comboSearch) {
                comboSearch.addEventListener('input', function() {
                    filterCombos();
                });
            }
        }
        
        // Setup filter button handlers
        function setupFilterHandlers() {
            // Location filters
            const locationFilters = document.querySelectorAll('#locationFilters .filter-btn');
            locationFilters.forEach(btn => {
                btn.addEventListener('click', function() {
                    locationFilters.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    locationFilter = this.dataset.filter;
                    filterLocations();
                });
            });
            
            // Equipment filters
            const equipmentFilters = document.querySelectorAll('#equipmentFilters .filter-btn');
            equipmentFilters.forEach(btn => {
                btn.addEventListener('click', function() {
                    equipmentFilters.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    equipmentFilter = this.dataset.filter;
                    filterEquipment();
                });
            });
        }
        
        // Filter locations
        function filterLocations() {
            const searchTerm = document.getElementById('locationSearch').value.toLowerCase().trim();
            const filtered = allLocations.filter(location => {
                // Search filter
                const matchesSearch = !searchTerm || 
                    location.TenDiaDiem.toLowerCase().includes(searchTerm) ||
                    (location.DiaChi && location.DiaChi.toLowerCase().includes(searchTerm)) ||
                    (location.LoaiDiaDiem && location.LoaiDiaDiem.toLowerCase().includes(searchTerm)) ||
                    (location.MoTa && location.MoTa.toLowerCase().includes(searchTerm));
                
                // Type filter
                const matchesFilter = locationFilter === 'all' || location.LoaiDiaDiem === locationFilter;
                
                return matchesSearch && matchesFilter;
            });
            
            displayLocations(filtered);
            updateResultsInfo('location', filtered.length, allLocations.length);
        }
        
        // Filter equipment
        function filterEquipment() {
            const searchTerm = document.getElementById('equipmentSearch').value.toLowerCase().trim();
            const filtered = allEquipment.filter(item => {
                // Search filter
                const matchesSearch = !searchTerm || 
                    item.TenThietBi.toLowerCase().includes(searchTerm) ||
                    (item.LoaiThietBi && item.LoaiThietBi.toLowerCase().includes(searchTerm)) ||
                    (item.HangSX && item.HangSX.toLowerCase().includes(searchTerm)) ||
                    (item.MoTa && item.MoTa.toLowerCase().includes(searchTerm));
                
                // Type filter
                const matchesFilter = equipmentFilter === 'all' || item.LoaiThietBi === equipmentFilter;
                
                return matchesSearch && matchesFilter;
            });
            
            displayEquipment(filtered);
            updateResultsInfo('equipment', filtered.length, allEquipment.length);
        }
        
        // Filter combos
        function filterCombos() {
            const searchTerm = document.getElementById('comboSearch').value.toLowerCase().trim();
            const filtered = allCombos.filter(combo => {
                return !searchTerm || 
                    combo.TenCombo.toLowerCase().includes(searchTerm) ||
                    (combo.MoTa && combo.MoTa.toLowerCase().includes(searchTerm)) ||
                    (combo.ThietBiTrongCombo && combo.ThietBiTrongCombo.toLowerCase().includes(searchTerm));
            });
            
            displayCombos(filtered);
            updateResultsInfo('combo', filtered.length, allCombos.length);
        }
        
        // Update results info
        function updateResultsInfo(type, filteredCount, totalCount) {
            const infoElement = document.getElementById(type + 'ResultsInfo');
            const countElement = document.getElementById(type + 'ResultsCount');
            
            if (infoElement && countElement) {
                if (filteredCount < totalCount) {
                    countElement.textContent = filteredCount;
                    infoElement.style.display = 'flex';
                } else {
                    infoElement.style.display = 'none';
                }
            }
        }
        
        // Load locations
        function loadLocations() {
            fetch('src/controllers/services-controller.php?action=get_locations')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allLocations = data.locations;
                        displayLocations(allLocations);
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
                        allEquipment = data.equipment;
                        displayEquipment(allEquipment);
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
                        allCombos = data.combos;
                        displayCombos(allCombos);
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
                const searchTerm = document.getElementById('locationSearch').value.trim();
                if (searchTerm) {
                    container.innerHTML = `
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h4>Không tìm thấy kết quả</h4>
                                <p>Không có địa điểm nào phù hợp với từ khóa "<strong>${searchTerm}</strong>"</p>
                            </div>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-map-marker-alt"></i>
                                <h4>Chưa có địa điểm</h4>
                                <p>Chưa có địa điểm nào được thêm vào hệ thống</p>
                            </div>
                        </div>
                    `;
                }
                return;
            }
            
            let html = '';
            locations.forEach(location => {
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
                                
                                <div class="service-item-details">
                                    <div class="service-item-detail">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>${location.DiaChi}</span>
                                    </div>
                                </div>
                                
                                <div class="service-item-description">
                                    ${location.MoTa || 'Không có mô tả'}
                                </div>
                                
                                <div class="service-item-stats">
                                    <div class="service-item-capacity">
                                        <i class="fas fa-users"></i> ${location.SucChua || 'Chưa xác định'} người
                                    </div>
                                    <button class="btn-register-now" onclick="window.location.href='events/register.php'">
                                        <i class="fas fa-calendar-plus"></i> Đăng ký ngay
                                    </button>
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
                const searchTerm = document.getElementById('equipmentSearch').value.trim();
                if (searchTerm) {
                    container.innerHTML = `
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h4>Không tìm thấy kết quả</h4>
                                <p>Không có thiết bị nào phù hợp với từ khóa "<strong>${searchTerm}</strong>"</p>
                            </div>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-cogs"></i>
                                <h4>Chưa có thiết bị</h4>
                                <p>Chưa có thiết bị nào được thêm vào hệ thống</p>
                            </div>
                        </div>
                    `;
                }
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
                const searchTerm = document.getElementById('comboSearch').value.trim();
                if (searchTerm) {
                    container.innerHTML = `
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h4>Không tìm thấy kết quả</h4>
                                <p>Không có combo nào phù hợp với từ khóa "<strong>${searchTerm}</strong>"</p>
                            </div>
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-box"></i>
                                <h4>Chưa có combo</h4>
                                <p>Chưa có combo nào được thêm vào hệ thống</p>
                            </div>
                        </div>
                    `;
                }
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
        
    </script>
    
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
    
</body>
</html>
