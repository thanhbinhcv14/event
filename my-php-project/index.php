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
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 300px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            z-index: 1000;
            transform: translateX(-100%);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .sidebar-header {
            padding: 30px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        
        .sidebar-header h5 {
            font-weight: 600;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .sidebar-header small {
            font-size: 0.85rem;
            opacity: 0.9;
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
            z-index: 1000;
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
            z-index: 1000;
            transform: translateY(100%);
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
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
            height: 350px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
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
            
            <?php if ($userRole == 3): ?>
            <!-- Event Manager Additional Functions -->
            <div class="menu-group">
                <div class="menu-group-title">Đăng ký sự kiện</div>
                <a href="admin/event-manager.php" class="menu-item">
                    <i class="fas fa-calendar-plus"></i> Đăng ký thay mặt
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
                        <button class="floating-chat-btn" onclick="openChatWidget()" title="Chat Hỗ Trợ AI">
                            <i class="fas fa-robot"></i>
                        </button>
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

    <!-- Chat Widget - AI Assistant (Available for all users) -->
    <div class="chat-widget" id="chatWidget">
        <div class="chat-widget-header">
            <div class="d-flex align-items-center">
                <i class="fas fa-robot me-2"></i>
                <div>
                    <h6 class="mb-0">Chat Hỗ Trợ AI</h6>
                    <small>Trợ lý thông minh</small>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-light" onclick="closeChatWidget()">
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
                <button class="btn btn-primary" type="button" onclick="sendChatMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Socket.IO with fallback -->
    <script>
        // Try to load Socket.IO from local server first
        const socketScript = document.createElement('script');
        socketScript.src = 'http://localhost:3000/socket.io/socket.io.js';
        socketScript.onerror = function() {
            console.warn('Local Socket.IO server not available, using CDN fallback');
            const cdnScript = document.createElement('script');
            cdnScript.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
            cdnScript.onload = function() {
                console.log('Socket.IO loaded from CDN');
            };
            cdnScript.onerror = function() {
                console.error('Failed to load Socket.IO from both local server and CDN');
            };
            document.head.appendChild(cdnScript);
        };
        socketScript.onload = function() {
            console.log('Socket.IO loaded from local server');
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
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
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
        
        // Auto-hide chat widget after 30 seconds of inactivity
        let chatInactivityTimer;
        function resetChatTimer() {
            clearTimeout(chatInactivityTimer);
            chatInactivityTimer = setTimeout(() => {
                const chatWidget = document.getElementById('chatWidget');
                const chatToggle = document.querySelector('.chat-toggle');
                if (chatWidget && chatWidget.classList.contains('show')) {
                    chatWidget.classList.remove('show');
                    if (chatToggle) chatToggle.style.display = 'block';
                }
            }, 30000);
        }
        
        // Reset timer on any interaction
        document.addEventListener('click', resetChatTimer);
        document.addEventListener('keypress', resetChatTimer);
    </script>
    
    
    <script>
        // Ensure jQuery is loaded before chat widget
        $(document).ready(function() {
            console.log('jQuery loaded successfully');
            loadFeaturedEvents();
            
            // Add Enter key listener for chat input
            document.getElementById('chatInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendChatMessage();
                }
            });
        });
        
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
            // Redirect to event detail page or show modal
            alert('Chức năng xem chi tiết sự kiện sẽ được phát triển!');
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

        // Chat Widget Toggle
        let isChatOpen = false;
        
        // Smart AI Memory System
        let conversationHistory = [];
        let userPreferences = {
            eventType: null,
            budget: null,
            location: null,
            timePreference: null
        };
        
        // Load conversation history from localStorage
        function loadConversationHistory() {
            const saved = localStorage.getItem('chatHistory');
            if (saved) {
                conversationHistory = JSON.parse(saved);
            }
        }
        
        // Save conversation history to localStorage
        function saveConversationHistory() {
            localStorage.setItem('chatHistory', JSON.stringify(conversationHistory));
        }
        
        // Clear conversation history when user leaves
        function clearConversationHistory() {
            conversationHistory = [];
            userPreferences = {
                eventType: null,
                budget: null,
                location: null,
                timePreference: null
            };
            localStorage.removeItem('chatHistory');
            localStorage.removeItem('userPreferences');
        }
        
        // Auto-response after 5 minutes of inactivity
        let inactivityTimer = null;
        let lastActivityTime = Date.now();
        
        function resetInactivityTimer() {
            lastActivityTime = Date.now();
            if (inactivityTimer) {
                clearTimeout(inactivityTimer);
            }
            
            // Set timer for 5 minutes (300000ms)
            inactivityTimer = setTimeout(() => {
                if (isChatOpen) {
                    addChatMessage("Bạn có cần hỗ trợ thêm gì không? Tôi sẵn sàng giúp đỡ!", 'assistant');
                    // Show quick suggestions after auto-response
                    setTimeout(() => {
                        forceShowQuickSuggestions();
                    }, 500);
                }
            }, 300000); // 5 minutes
        }
        
        // Load user preferences from localStorage
        function loadUserPreferences() {
            const saved = localStorage.getItem('userPreferences');
            if (saved) {
                userPreferences = JSON.parse(saved);
            }
        }
        
        // Save user preferences to localStorage
        function saveUserPreferences() {
            localStorage.setItem('userPreferences', JSON.stringify(userPreferences));
        }
        
        function openChatWidget() {
            const chatWidget = document.querySelector('.chat-widget');
            const chatBtn = document.querySelector('.floating-chat-btn');
            
            if (!isChatOpen) {
                // Load conversation history and preferences
                loadConversationHistory();
                loadUserPreferences();
                
                // Show chat widget
                if (chatWidget) {
                    chatWidget.classList.add('show');
                    isChatOpen = true;
                    
                    // Change button icon to close
            if (chatBtn) {
                        chatBtn.innerHTML = '<i class="fas fa-times"></i>';
                        chatBtn.title = 'Đóng chat';
                    }
                    
                    // Start inactivity timer when chat opens
                    resetInactivityTimer();
                    
                    // Show smart welcome message if no previous conversation
                    if (conversationHistory.length === 0) {
                        // Clear any existing messages first
                        const chatMessages = document.getElementById('chatMessages');
                        if (chatMessages) {
                            chatMessages.innerHTML = '';
                        }
                        
                        // Show quick suggestions immediately - no delay
                        const quickSuggestions = document.getElementById('quickSuggestions');
                        if (quickSuggestions) {
                            quickSuggestions.style.display = 'grid';
                            quickSuggestions.style.visibility = 'visible';
                            quickSuggestions.style.opacity = '1';
                            quickSuggestions.style.position = 'relative';
                            quickSuggestions.style.zIndex = '10';
                            console.log('Quick suggestions should be visible now');
                        } else {
                            console.log('Quick suggestions element not found!');
                            // Try to find it by class name
                            const quickSuggestionsByClass = document.querySelector('.quick-suggestions');
                            console.log('Quick suggestions by class:', quickSuggestionsByClass);
                            if (quickSuggestionsByClass) {
                                quickSuggestionsByClass.style.display = 'grid';
                                quickSuggestionsByClass.style.visibility = 'visible';
                                quickSuggestionsByClass.style.opacity = '1';
                                quickSuggestionsByClass.style.position = 'relative';
                                quickSuggestionsByClass.style.zIndex = '10';
                                console.log('Quick suggestions found by class and made visible');
                            } else {
                                // Create quick suggestions if they don't exist
                                createQuickSuggestions();
                            }
                        }
                        
                setTimeout(() => {
                            showSmartWelcome();
                        }, 500);
                    } else {
                        // Restore previous conversation
                        restoreConversation();
                        // Show quick suggestions for existing conversations too
                        setTimeout(() => { forceShowQuickSuggestions(); }, 500);
                    }
                }
            } else {
                // Hide chat widget
                closeChatWidget();
            }
        }
        
        // Smart welcome message based on user preferences
        function showSmartWelcome() {
            let welcomeMessage = `Xin chào! Tôi có thể giúp bạn đăng ký sự kiện, tính toán chi phí và hỗ trợ chuẩn bị. Bạn cần hỗ trợ gì?`;
            
            addChatMessage(welcomeMessage, 'assistant');
            
            // Show smart suggestions immediately after welcome message
            setTimeout(() => {
                showSmartSuggestions();
            }, 500);
        }
        
        // Force show quick suggestions
        function forceShowQuickSuggestions() {
            const quickSuggestions = document.getElementById('quickSuggestions');
            if (quickSuggestions) {
                quickSuggestions.style.display = 'grid';
                quickSuggestions.style.visibility = 'visible';
                quickSuggestions.style.opacity = '1';
                showSmartSuggestions();
            }
        }
        
        // Ensure quick suggestions are always visible
        function ensureQuickSuggestionsVisible() {
            const quickSuggestions = document.getElementById('quickSuggestions');
            if (quickSuggestions) {
                quickSuggestions.style.display = 'grid';
                quickSuggestions.style.visibility = 'visible';
                quickSuggestions.style.opacity = '1';
                console.log('Quick suggestions should be visible now');
            } else {
                console.log('Quick suggestions element not found!');
                // Try to create it if it doesn't exist
                createQuickSuggestions();
            }
        }
        
        // Create quick suggestions if they don't exist
        function createQuickSuggestions() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages && !document.getElementById('quickSuggestions')) {
                const quickSuggestions = document.createElement('div');
                quickSuggestions.className = 'quick-suggestions';
                quickSuggestions.id = 'quickSuggestions';
                quickSuggestions.innerHTML = `
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
                `;
                chatMessages.appendChild(quickSuggestions);
                console.log('Quick suggestions created and added to chat');
            }
        }
        
        // Test function to show quick suggestions
        function testQuickSuggestions() {
            console.log('Testing quick suggestions...');
            const quickSuggestions = document.getElementById('quickSuggestions');
            console.log('Quick suggestions element:', quickSuggestions);
            if (quickSuggestions) {
                quickSuggestions.style.display = 'block';
                showSmartSuggestions();
                console.log('Quick suggestions should be visible now');
            } else {
                console.log('Quick suggestions element not found!');
            }
        }
        
        // Restore previous conversation
        function restoreConversation() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.innerHTML = '';
            
            // Add last 10 messages
            const recentMessages = conversationHistory.slice(-10);
            recentMessages.forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.sender}`;
                
                // For assistant messages, allow HTML (including links)
                // For user messages, escape HTML for security
                const content = msg.sender === 'assistant' ? msg.text : escapeHtml(msg.text);
                
                messageDiv.innerHTML = `
                    <div class="message-content">
                        <div>${content}</div>
                    </div>
                `;
                
                chatMessages.appendChild(messageDiv);
            });
            
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function closeChatWidget() {
            const chatWidget = document.querySelector('.chat-widget');
            const chatBtn = document.querySelector('.floating-chat-btn');
            
            if (chatWidget) {
                chatWidget.classList.remove('show');
                isChatOpen = false;
                
                // Change button icon back to robot
                if (chatBtn) {
                    chatBtn.innerHTML = '<i class="fas fa-robot"></i>';
                    chatBtn.title = 'Chat Hỗ Trợ AI';
                }
            }
        }
        
        // Send chat message
        function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add user message
            addChatMessage(message, 'user');
            input.value = '';
            
            // Simulate AI response
            setTimeout(() => {
                const response = generateAIResponse(message);
                addChatMessage(response, 'assistant');
            }, 1000);
        }
        
        // Add message to chat
        function addChatMessage(text, sender) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}`;
            
            // For assistant messages, allow HTML (including links)
            // For user messages, escape HTML for security
            const content = sender === 'assistant' ? text : escapeHtml(text);
            
            messageDiv.innerHTML = `
                <div class="message-content">
                    <div>${content}</div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Save to conversation history
            conversationHistory.push({
                text: text,
                sender: sender,
                timestamp: new Date().toISOString()
            });
            
            // Keep only last 50 messages
            if (conversationHistory.length > 50) {
                conversationHistory = conversationHistory.slice(-50);
            }
            
            saveConversationHistory();
            
            // Reset inactivity timer when user sends a message
            if (sender === 'user') {
                resetInactivityTimer();
            }
        }
        
        // Smart AI Knowledge Base
        const aiKnowledge = {
            // Event types and their requirements
            eventTypes: {
                'hội nghị': {
                    equipment: ['Micro không dây', 'Loa', 'Máy chiếu', 'Màn hình LED'],
                    location: 'Hội trường lớn',
                    duration: '4-8 giờ',
                    capacity: '50-500 người'
                },
                'tiệc cưới': {
                    equipment: ['Hệ thống âm thanh', 'Ánh sáng trang trí', 'Bàn ghế', 'Khán đài'],
                    location: 'Sân khấu hoặc ngoài trời',
                    duration: '6-12 giờ',
                    capacity: '100-300 người'
                },
                'hội thảo': {
                    equipment: ['Micro', 'Máy chiếu', 'Bảng trắng', 'Ghế ngồi'],
                    location: 'Phòng họp',
                    duration: '2-4 giờ',
                    capacity: '20-100 người'
                },
                'sự kiện thể thao': {
                    equipment: ['Hệ thống âm thanh', 'Màn hình LED', 'Ghế khán đài', 'Thiết bị thể thao'],
                    location: 'Sân vận động',
                    duration: '2-6 giờ',
                    capacity: '200-1000 người'
                }
            },
            
            // Pricing information
            pricing: {
                'hội trường lớn': '2,000,000 - 5,000,000 VNĐ/ngày',
                'phòng họp': '500,000 - 1,500,000 VNĐ/ngày',
                'ngoài trời': '1,000,000 - 3,000,000 VNĐ/ngày',
                'sân khấu': '1,500,000 - 4,000,000 VNĐ/ngày'
            },
            
            // Equipment pricing
            equipmentPricing: {
                'âm thanh': '500,000 - 2,000,000 VNĐ/bộ',
                'ánh sáng': '300,000 - 1,500,000 VNĐ/bộ',
                'video': '800,000 - 3,000,000 VNĐ/bộ',
                'nội thất': '200,000 - 800,000 VNĐ/bộ'
            }
        };
        
        // Smart context analysis with memory
        function analyzeContext(message) {
            const lowerMessage = message.toLowerCase();
            const context = {
                intent: null,
                eventType: null,
                urgency: 'normal',
                userType: 'customer',
                hasPreviousContext: conversationHistory.length > 0,
                userPreferences: userPreferences
            };
            
            // Detect intent with smart patterns
            const intentPatterns = {
                'register': ['đăng ký', 'tạo', 'tổ chức', 'làm', 'muốn'],
                'pricing': ['giá', 'phí', 'chi phí', 'tiền', 'bao nhiêu', 'cost'],
                'status': ['trạng thái', 'kiểm tra', 'xem', 'như thế nào', 'đâu'],
                'cancel': ['hủy', 'xóa', 'thôi', 'không muốn', 'dừng'],
                'help': ['giúp', 'hỗ trợ', 'không biết', 'làm sao', 'như thế nào'],
                'modify': ['sửa', 'thay đổi', 'cập nhật', 'chỉnh'],
                'compare': ['so sánh', 'khác nhau', 'nào tốt hơn', 'chọn']
            };
            
            for (const [intent, patterns] of Object.entries(intentPatterns)) {
                if (patterns.some(pattern => lowerMessage.includes(pattern))) {
                    context.intent = intent;
                    break;
                }
            }
            
            // Detect event type with fuzzy matching
            for (const [type, info] of Object.entries(aiKnowledge.eventTypes)) {
                if (lowerMessage.includes(type) || lowerMessage.includes(type.replace(' ', ''))) {
                    context.eventType = type;
                    // Update user preferences
                    userPreferences.eventType = type;
                    saveUserPreferences();
                    break;
                }
            }
            
            // Detect urgency with smart patterns
            const urgentPatterns = ['gấp', 'khẩn cấp', 'ngay', 'lập tức', 'urgent', 'asap'];
            if (urgentPatterns.some(pattern => lowerMessage.includes(pattern))) {
                context.urgency = 'urgent';
            }
            
            // Detect budget preferences
            const budgetPatterns = [
                { pattern: 'rẻ', budget: 'low' },
                { pattern: 'tiết kiệm', budget: 'low' },
                { pattern: 'vừa phải', budget: 'medium' },
                { pattern: 'trung bình', budget: 'medium' },
                { pattern: 'cao cấp', budget: 'high' },
                { pattern: 'premium', budget: 'high' },
                { pattern: 'sang trọng', budget: 'high' }
            ];
            
            for (const { pattern, budget } of budgetPatterns) {
                if (lowerMessage.includes(pattern)) {
                    userPreferences.budget = budget;
                    saveUserPreferences();
                    break;
                }
            }
            
            // Detect location preferences
            const locationPatterns = [
                { pattern: 'trong nhà', location: 'indoor' },
                { pattern: 'ngoài trời', location: 'outdoor' },
                { pattern: 'hội trường', location: 'hall' },
                { pattern: 'phòng họp', location: 'meeting' }
            ];
            
            for (const { pattern, location } of locationPatterns) {
                if (lowerMessage.includes(pattern)) {
                    userPreferences.location = location;
                    saveUserPreferences();
                    break;
                }
            }
            
            // Analyze conversation history for context
            if (conversationHistory.length > 0) {
                const recentMessages = conversationHistory.slice(-5);
                const recentText = recentMessages.map(msg => msg.text).join(' ').toLowerCase();
                
                // Check for follow-up questions
                if (recentText.includes('đăng ký') && lowerMessage.includes('tiếp theo')) {
                    context.intent = 'register_followup';
                }
                
                // Check for clarification requests
                if (lowerMessage.includes('có thể') || lowerMessage.includes('được không')) {
                    context.intent = 'clarification';
                }
            }
            
            return context;
        }
        
        // Generate smart AI response
        function generateAIResponse(message) {
            const context = analyzeContext(message);
            const lowerMessage = message.toLowerCase();
            
            // Personalized greeting
            if (lowerMessage.includes('xin chào') || lowerMessage.includes('hello') || lowerMessage.includes('hi')) {
                return `Xin chào! Tôi có thể giúp bạn đăng ký sự kiện, tính toán chi phí và hỗ trợ chuẩn bị. Bạn cần hỗ trợ gì?`;
            }
            
            // Event registration with smart suggestions
            if (context.intent === 'register' || lowerMessage.includes('đăng ký') || lowerMessage.includes('sự kiện')) {
                if (context.eventType) {
                    const eventInfo = aiKnowledge.eventTypes[context.eventType];
                    let response = `Tuyệt vời! Bạn muốn tổ chức ${context.eventType.toUpperCase()}. Đây là gợi ý thông minh của tôi:\n\n📋 **Thiết bị cần thiết:**\n${eventInfo.equipment.map(item => `• ${item}`).join('\n')}\n\n🏢 **Địa điểm phù hợp:** ${eventInfo.location}\n⏰ **Thời gian dự kiến:** ${eventInfo.duration}\n👥 **Sức chứa:** ${eventInfo.capacity}\n\n💰 **Chi phí ước tính:** ${aiKnowledge.pricing[eventInfo.location]}\n\n`;
                    
                    // Add personalized suggestions based on user preferences
                    if (userPreferences.budget === 'low') {
                        response += `💡 **Gợi ý tiết kiệm:**\n• Chọn thiết bị cơ bản\n• Tận dụng combo giảm giá\n• Đặt trước 1 tháng để có giá tốt\n\n`;
                    } else if (userPreferences.budget === 'high') {
                        response += `💎 **Gợi ý cao cấp:**\n• Thiết bị premium chất lượng cao\n• Dịch vụ VIP\n• Hỗ trợ 24/7\n\n`;
                    }
                    
                    if (userPreferences.location === 'outdoor') {
                        response += `🌤️ **Lưu ý ngoài trời:**\n• Chuẩn bị mái che phòng mưa\n• Hệ thống âm thanh chống ồn\n• Điện năng dự phòng\n\n`;
                    }
                    
                    response += `Bạn có muốn tôi tạo kế hoạch chi tiết không?`;
                    return response;
                } else {
                    let response = `Tôi có thể giúp bạn đăng ký sự kiện! Hãy cho tôi biết:\n\nLoại sự kiện: Hội nghị, Tiệc cưới, Hội thảo, Thể thao?\nSố lượng người: Bao nhiêu người tham dự?\nThời gian: Khi nào tổ chức?\nĐịa điểm: Trong nhà hay ngoài trời?\n\n`;
                    
                    // Add smart suggestions based on previous preferences
                    if (userPreferences.eventType) {
                        response += `Gợi ý: Tôi thấy bạn quan tâm đến ${userPreferences.eventType}. Bạn có muốn tiếp tục với loại sự kiện này không?\n\n`;
                    }
                    
                    response += `Để đăng ký sự kiện, bạn có thể truy cập: <a href='register.php' target='_blank'>Trang đăng ký sự kiện</a>\n\nTôi sẽ phân tích và đưa ra gợi ý tối ưu nhất!`;
                    return response;
                }
            }
            
            // Payment processing
            if (lowerMessage.includes('thanh toán') || lowerMessage.includes('payment') || lowerMessage.includes('tiền')) {
                return "THANH TOÁN DỊCH VỤ\n\nTôi có thể giúp bạn:\n\nPhương thức thanh toán:\n• Chuyển khoản ngân hàng\n• Thanh toán trực tiếp\n• Thanh toán online (VNPay, MoMo)\n• Thanh toán bằng thẻ tín dụng\n\nThông tin cần thiết:\n• Số tài khoản ngân hàng\n• Mã số sự kiện\n• Số tiền cần thanh toán\n• Thời hạn thanh toán\n\nHỗ trợ thanh toán:\n• Hướng dẫn từng bước\n• Kiểm tra trạng thái thanh toán\n• Xử lý sự cố thanh toán\n\nĐể thanh toán trực tuyến, bạn có thể truy cập: <a href='payment.php' target='_blank'>Trang thanh toán</a>\n\nBạn cần hỗ trợ thanh toán gì cụ thể?";
            }
            
            // Smart pricing analysis with dynamic pricing
            if (context.intent === 'pricing' || lowerMessage.includes('giá') || lowerMessage.includes('phí')) {
                let response = "BẢNG GIÁ DỊCH VỤ (GIÁ BIẾN ĐỘNG)\n\n";
                
                // Add dynamic pricing explanation
                response += "💰 GIÁ BIẾN ĐỘNG THEO THỜI GIAN:\n";
                response += "• Buổi sáng (6:00-12:00): Giá gốc\n";
                response += "• Buổi chiều (12:00-18:00): +10%\n";
                response += "• Buổi tối (18:00-22:00): +25%\n";
                response += "• Ban đêm (22:00-6:00): +30%\n\n";
                
                response += "📅 GIÁ BIẾN ĐỘNG THEO NGÀY:\n";
                response += "• Ngày thường (T2-T6): Giá gốc\n";
                response += "• Cuối tuần (T7-CN): +20%\n";
                response += "• Ngày lễ: +40%\n\n";
                
                if (context.eventType) {
                    const eventInfo = aiKnowledge.eventTypes[context.eventType];
                    response += `${context.eventType.toUpperCase()}:\n`;
                    response += `Địa điểm: ${aiKnowledge.pricing[eventInfo.location]} (có thể tăng 10-40%)\n`;
                    response += `Âm thanh: ${aiKnowledge.equipmentPricing['âm thanh']} (giá cố định)\n`;
                    response += `Ánh sáng: ${aiKnowledge.equipmentPricing['ánh sáng']} (giá cố định)\n`;
                    response += `Video: ${aiKnowledge.equipmentPricing['video']} (giá cố định)\n`;
                    response += `Nội thất: ${aiKnowledge.equipmentPricing['nội thất']} (giá cố định)\n\n`;
                    response += `Tổng ước tính: ${calculateTotalCost(eventInfo)} (chưa tính giá động)\n\n`;
                } else {
                    response += "ĐỊA ĐIỂM (GIÁ BIẾN ĐỘNG):\n";
                    for (const [location, price] of Object.entries(aiKnowledge.pricing)) {
                        response += `• ${location}: ${price} (có thể tăng 10-40%)\n`;
                    }
                    response += "\nTHIẾT BỊ (GIÁ CỐ ĐỊNH):\n";
                    for (const [equipment, price] of Object.entries(aiKnowledge.equipmentPricing)) {
                        response += `• ${equipment}: ${price}\n`;
                    }
                }
                
                // Add savings suggestions
                response += "\n💡 GỢI Ý TIẾT KIỆM:\n";
                response += "• Chọn buổi sáng để tiết kiệm 25-30%\n";
                response += "• Tránh cuối tuần và ngày lễ\n";
                response += "• Đặt trước 1-2 tháng để có giá tốt\n\n";
                
                response += "Để xem bảng giá chi tiết và tính giá động, bạn có thể truy cập: <a href='services.php' target='_blank'>Trang dịch vụ</a>";
                return response;
            }
            
            // Status checking with smart insights
            if (context.intent === 'status' || lowerMessage.includes('trạng thái')) {
                return "KIỂM TRA TRẠNG THÁI SỰ KIỆN\n\nTôi có thể giúp bạn:\n\nXem trạng thái sự kiện:\n• Chờ duyệt (thời gian xử lý: 1-2 ngày)\n• Đã duyệt (có thể bắt đầu chuẩn bị)\n• Từ chối (tôi sẽ giải thích lý do)\n\nPhân tích tiến độ:\n• Thời gian còn lại\n• Công việc cần làm\n• Rủi ro tiềm ẩn\n\nGợi ý tối ưu:\n• Cải thiện kế hoạch\n• Giảm chi phí\n• Tăng hiệu quả\n\nBạn muốn kiểm tra sự kiện nào?";
            }
            
            // Smart help system
            if (context.intent === 'help' || lowerMessage.includes('giúp') || lowerMessage.includes('không biết')) {
                return "HỆ THỐNG HỖ TRỢ KHÁCH HÀNG\n\nTôi có thể giúp bạn:\n\nPhân tích nhu cầu:\n• Xác định loại sự kiện phù hợp\n• Tính toán chi phí chính xác\n• Đề xuất timeline phù hợp\n\nGợi ý hữu ích:\n• Thiết bị cần thiết\n• Địa điểm lý tưởng\n• Cách tiết kiệm chi phí\n\nHỗ trợ chuẩn bị:\n• Tạo kế hoạch chi tiết\n• Gửi thông báo nhắc nhở\n• Theo dõi tiến độ\n\nHãy mô tả sự kiện bạn muốn tổ chức!";
            }
            
            // Smart cancellation
            if (context.intent === 'cancel' || lowerMessage.includes('hủy')) {
                return "HỦY SỰ KIỆN\n\nTrước khi hủy, hãy cân nhắc:\n\nChi phí hủy:\n• Phí hủy: 10-30% tổng chi phí\n• Hoàn tiền: 70-90% (tùy thời điểm)\n\nGiải pháp thay thế:\n• Hoãn sự kiện\n• Chuyển đổi loại sự kiện\n• Giảm quy mô\n\nHỗ trợ:\n• Tôi có thể tìm giải pháp thay thế\n• Liên hệ admin để thương lượng\n• Đề xuất cách tối ưu chi phí\n\nBạn có chắc muốn hủy không? Tôi có thể giúp tìm giải pháp tốt hơn!";
            }
            
            // Smart gratitude response
            if (lowerMessage.includes('cảm ơn') || lowerMessage.includes('thank') || lowerMessage.includes('tốt')) {
                const responses = [
                    "Rất vui được giúp đỡ bạn! 😊 Nếu có thêm câu hỏi, đừng ngại hỏi nhé!",
                    "Không có gì! Tôi luôn sẵn sàng hỗ trợ bạn! 🚀",
                    "Cảm ơn bạn đã tin tưởng! Tôi sẽ tiếp tục cải thiện để phục vụ tốt hơn! 💪",
                    "Rất hạnh phúc khi được giúp đỡ bạn! Chúc bạn có sự kiện thành công! 🎉"
                ];
                return responses[Math.floor(Math.random() * responses.length)];
            }
            
            // Smart time-based responses
            if (lowerMessage.includes('giờ') || lowerMessage.includes('thời gian') || lowerMessage.includes('khi nào')) {
                const now = new Date();
                const timeInfo = {
                    hour: now.getHours(),
                    day: now.getDay(),
                    date: now.getDate(),
                    month: now.getMonth() + 1
                };
                
                let timeResponse = `🕐 **THÔNG TIN THỜI GIAN THÔNG MINH**\n\n`;
                timeResponse += `⏰ Hiện tại: ${now.toLocaleString('vi-VN')}\n`;
                timeResponse += `📅 Ngày trong tuần: ${getDayName(timeInfo.day)}\n`;
                timeResponse += `🌅 Thời gian tốt nhất để tổ chức sự kiện: 9h-17h (thứ 2-6)\n`;
                timeResponse += `🎉 Cuối tuần: 8h-22h (thứ 7, CN)\n\n`;
                timeResponse += `💡 **Gợi ý thông minh:**\n`;
                
                if (timeInfo.hour < 9) {
                    timeResponse += `• Sáng sớm: Phù hợp sự kiện thể thao, yoga\n`;
                } else if (timeInfo.hour < 12) {
                    timeResponse += `• Buổi sáng: Tốt cho hội thảo, hội nghị\n`;
                } else if (timeInfo.hour < 18) {
                    timeResponse += `• Buổi chiều: Lý tưởng cho tiệc cưới, sự kiện cộng đồng\n`;
                } else {
                    timeResponse += `• Buổi tối: Hoàn hảo cho tiệc, ca nhạc, giải trí\n`;
                }
                
                return timeResponse;
            }
            
            // Smart weather and seasonal suggestions
            if (lowerMessage.includes('thời tiết') || lowerMessage.includes('mùa') || lowerMessage.includes('nhiệt độ')) {
                const month = new Date().getMonth() + 1;
                let seasonalAdvice = "🌤️ **GỢI Ý THEO MÙA THÔNG MINH**\n\n";
                
                if (month >= 3 && month <= 5) {
                    seasonalAdvice += "🌸 **MÙA XUÂN (Tháng 3-5):**\n";
                    seasonalAdvice += "• Thời tiết mát mẻ, ít mưa\n";
                    seasonalAdvice += "• Phù hợp: Tiệc cưới, sự kiện ngoài trời\n";
                    seasonalAdvice += "• Lưu ý: Chuẩn bị mái che phòng mưa\n";
                } else if (month >= 6 && month <= 8) {
                    seasonalAdvice += "☀️ **MÙA HÈ (Tháng 6-8):**\n";
                    seasonalAdvice += "• Nắng nóng, nhiệt độ cao\n";
                    seasonalAdvice += "• Phù hợp: Sự kiện trong nhà, hội trường\n";
                    seasonalAdvice += "• Lưu ý: Cần điều hòa, nước uống\n";
                } else if (month >= 9 && month <= 11) {
                    seasonalAdvice += "🍂 **MÙA THU (Tháng 9-11):**\n";
                    seasonalAdvice += "• Thời tiết dễ chịu, ít mưa\n";
                    seasonalAdvice += "• Phù hợp: Mọi loại sự kiện\n";
                    seasonalAdvice += "• Lưu ý: Thời điểm vàng cho sự kiện\n";
                } else {
                    seasonalAdvice += "❄️ **MÙA ĐÔNG (Tháng 12-2):**\n";
                    seasonalAdvice += "• Lạnh, có thể có mưa\n";
                    seasonalAdvice += "• Phù hợp: Sự kiện trong nhà\n";
                    seasonalAdvice += "• Lưu ý: Cần sưởi ấm, che mưa\n";
                }
                
                return seasonalAdvice;
            }
            
            // Smart follow-up suggestions
            if (context.intent === 'register_followup') {
                return `🚀 **BƯỚC TIẾP THEO THÔNG MINH**\n\nDựa trên cuộc trò chuyện trước, tôi khuyến nghị:\n\n📋 **Danh sách việc cần làm:**\n1. Xác nhận thông tin sự kiện\n2. Chọn thiết bị phù hợp\n3. Đặt lịch kiểm tra địa điểm\n4. Chuẩn bị tài liệu cần thiết\n\n💡 **Gợi ý tối ưu:**\n• Tôi có thể tạo timeline chi tiết\n• Gửi checklist chuẩn bị\n• Nhắc nhở các mốc thời gian quan trọng\n\nBạn muốn tôi tạo kế hoạch chi tiết không?`;
            }
            
            // Smart clarification responses
            if (context.intent === 'clarification') {
                return `🤔 **LÀM RÕ THÔNG TIN THÔNG MINH**\n\nTôi hiểu bạn cần làm rõ thêm. Dựa trên ngữ cảnh, tôi có thể giúp:\n\n🎯 **Phân tích chi tiết:**\n• Giải thích rõ hơn về dịch vụ\n• So sánh các lựa chọn\n• Đưa ra ví dụ cụ thể\n\n💡 **Gợi ý thông minh:**\n• Tôi có thể tạo demo trực quan\n• Cung cấp tài liệu tham khảo\n• Kết nối với chuyên gia\n\nBạn muốn tôi giải thích chi tiết về vấn đề gì?`;
            }
            
            // Smart comparison suggestions
            if (context.intent === 'compare') {
                return `⚖️ **SO SÁNH THÔNG MINH**\n\nTôi có thể giúp bạn so sánh:\n\n🏢 **Địa điểm:**\n• Hội trường vs Phòng họp\n• Trong nhà vs Ngoài trời\n• Sức chứa và tiện nghi\n\n🎵 **Thiết bị:**\n• Cơ bản vs Cao cấp\n• Chi phí vs Chất lượng\n• Phù hợp với loại sự kiện\n\n💰 **Gói dịch vụ:**\n• Tiết kiệm vs Premium\n• Bao gồm vs Không bao gồm\n• Giá trị thực tế\n\nBạn muốn so sánh cụ thể gì?`;
            }
            
            // Smart modification suggestions
            if (context.intent === 'modify') {
                return `✏️ **CHỈNH SỬA THÔNG MINH**\n\nTôi có thể giúp bạn:\n\n📝 **Cập nhật thông tin:**\n• Thay đổi thời gian sự kiện\n• Điều chỉnh số lượng người\n• Chuyển đổi loại sự kiện\n\n🔄 **Tối ưu hóa:**\n• Giảm chi phí không cần thiết\n• Cải thiện trải nghiệm\n• Tăng hiệu quả\n\n⚠️ **Lưu ý quan trọng:**\n• Phí thay đổi có thể áp dụng\n• Cần xác nhận lại với admin\n• Thời gian xử lý: 1-2 ngày\n\nBạn muốn thay đổi gì cụ thể?`;
            }
            
            // Default smart response with learning capability
            return `🤖 **PHÂN TÍCH THÔNG MINH**\n\nTôi hiểu bạn đang hỏi về: "${message}"\n\nDựa trên phân tích AI và lịch sử cuộc trò chuyện, tôi khuyến nghị:\n\n🎯 **Hành động tiếp theo:**\n1. Xác định rõ nhu cầu cụ thể\n2. Tham khảo các gợi ý của tôi\n3. Liên hệ hỗ trợ nếu cần\n\n💡 **Gợi ý thông minh:**\n• Tôi có thể tạo kế hoạch chi tiết\n• Tính toán chi phí chính xác\n• Đề xuất giải pháp tối ưu\n• Học hỏi từ cuộc trò chuyện trước\n\n🔍 **Tìm hiểu thêm:**\n• Hỏi cụ thể hơn về vấn đề\n• Mô tả chi tiết nhu cầu\n• Chia sẻ ngân sách dự kiến\n\nBạn muốn tôi phân tích sâu hơn không?`;
        }
        
        // Helper functions
        function calculateTotalCost(eventInfo) {
            // Simple cost calculation (in real app, this would be more sophisticated)
            const baseCost = 2000000; // Base cost
            const equipmentCost = eventInfo.equipment.length * 500000; // Equipment cost
            const capacityCost = eventInfo.capacity.includes('100') ? 1000000 : 2000000; // Capacity cost
            return `${(baseCost + equipmentCost + capacityCost).toLocaleString('vi-VN')} VNĐ`;
        }
        
        function getDayName(day) {
            const days = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
            return days[day];
        }
        
        // Escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        // Send quick message from suggestions
        function sendQuickMessage(message) {
            const input = document.getElementById('chatInput');
            input.value = message;
            sendChatMessage();
            
            // Hide quick suggestions after use
            const quickSuggestions = document.getElementById('quickSuggestions');
            if (quickSuggestions) {
                quickSuggestions.style.display = 'none';
            }
        }
        
        // Show smart quick suggestions based on context
        function showSmartSuggestions() {
            const quickSuggestions = document.getElementById('quickSuggestions');
            if (!quickSuggestions) return;
            
            // Clear existing suggestions
            quickSuggestions.innerHTML = '';
            
            // Generate suggestions based on user preferences and context
            let suggestions = [];
            
            if (userPreferences.eventType) {
                suggestions.push({
                    icon: 'fas fa-calendar-check',
                    text: `Tiếp tục ${userPreferences.eventType}`,
                    message: `Tôi muốn tiếp tục với ${userPreferences.eventType}`
                });
            }
            
            if (userPreferences.budget === 'low') {
                suggestions.push({
                    icon: 'fas fa-piggy-bank',
                    text: 'Gói tiết kiệm',
                    message: 'Tôi muốn xem gói dịch vụ tiết kiệm'
                });
            } else if (userPreferences.budget === 'high') {
                suggestions.push({
                    icon: 'fas fa-crown',
                    text: 'Gói cao cấp',
                    message: 'Tôi muốn xem gói dịch vụ cao cấp'
                });
            }
            
            // Default suggestions
            suggestions.push(
                {
                    icon: 'fas fa-calendar-plus',
                    text: 'Đăng ký sự kiện',
                    message: 'Tôi muốn đăng ký sự kiện'
                },
                {
                    icon: 'fas fa-dollar-sign',
                    text: 'Xem giá',
                    message: 'Tôi muốn xem giá dịch vụ'
                },
                {
                    icon: 'fas fa-credit-card',
                    text: 'Thanh toán',
                    message: 'Tôi muốn thanh toán'
                },
                {
                    icon: 'fas fa-search',
                    text: 'Trạng thái',
                    message: 'Tôi muốn kiểm tra trạng thái sự kiện'
                },
                {
                    icon: 'fas fa-question-circle',
                    text: 'Hỗ trợ',
                    message: 'Tôi cần hỗ trợ'
                },
                {
                    icon: 'fas fa-times-circle',
                    text: 'Hủy sự kiện',
                    message: 'Tôi muốn hủy sự kiện'
                }
            );
            
            // Limit to 6 suggestions
            suggestions = suggestions.slice(0, 6);
            
            // Create suggestion items
            suggestions.forEach(suggestion => {
                const item = document.createElement('div');
                item.className = 'suggestion-item';
                item.onclick = () => sendQuickMessage(suggestion.message);
                item.innerHTML = `
                    <i class="${suggestion.icon}"></i>
                    <span>${suggestion.text}</span>
                `;
                quickSuggestions.appendChild(item);
            });
        }
        
        // Event listeners for page lifecycle
        window.addEventListener('beforeunload', function() {
            // Clear conversation history when user leaves
            clearConversationHistory();
        });
        
        window.addEventListener('unload', function() {
            // Clear conversation history when user leaves
            clearConversationHistory();
        });
        
        // Start inactivity timer when page loads
        document.addEventListener('DOMContentLoaded', function() {
            resetInactivityTimer();
            
            // Ensure quick suggestions are visible on page load
            setTimeout(() => {
                const quickSuggestions = document.getElementById('quickSuggestions');
                if (quickSuggestions) {
                    quickSuggestions.style.display = 'grid';
                    quickSuggestions.style.visibility = 'visible';
                    quickSuggestions.style.opacity = '1';
                    console.log('Quick suggestions made visible on page load');
                }
            }, 100);
        });
        
    </script>
    
    
</body>
</html>
