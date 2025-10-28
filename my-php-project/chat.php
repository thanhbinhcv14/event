<?php
session_start();
require_once __DIR__ . '/src/auth/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get user role
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? 0;

// Allow admin (1), event manager (3), and customers (5) to use chat
if (!in_array($userRole, [1, 3, 5])) {
    echo '<script>alert("Bạn không có quyền sử dụng chat với nhân viên. Chỉ quản trị viên, quản lý sự kiện và khách hàng mới có thể sử dụng tính năng này."); window.location.href = "index.php";</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Hỗ trợ - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
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
        
        .chat-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            margin: 2rem auto;
            overflow: hidden;
            max-width: 1200px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: containerFloat 6s ease-in-out infinite;
        }
        
        @keyframes containerFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            padding: 1.5rem 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .chat-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: headerShine 8s ease-in-out infinite;
        }
        
        @keyframes headerShine {
            0%, 100% { transform: rotate(0deg) scale(1); }
            50% { transform: rotate(180deg) scale(1.1); }
        }
        
        .header-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .header-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .header-content h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .connection-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .connection-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }
        
        .status-dot.online {
            background: #28a745;
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.3);
            animation: pulse-green 2s infinite;
        }
        
        .status-dot.offline {
            background: #dc3545;
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.3);
            animation: pulse-red 2s infinite;
        }
        
        .status-dot.connecting {
            background: #ffc107;
            box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.3);
            animation: pulse-yellow 1s infinite;
        }
        
        @keyframes pulse-green {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        @keyframes pulse-red {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        @keyframes pulse-yellow {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .status-dot:hover {
            transform: scale(1.2);
        }
        
        .btn-home {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 1;
        }
        
        .btn-home:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
            color: white;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 700;
            margin-left: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: badgePulse 2s ease-in-out infinite;
        }
        
        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .role-customer {
            background: linear-gradient(135deg, #4CAF50, #45a049, #2E7D32);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
        }
        
        .role-event-manager {
            background: linear-gradient(135deg, #2196F3, #1976D2, #1565C0);
            color: white;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.4);
        }
        
        .role-admin {
            background: linear-gradient(135deg, #dc3545, #c82333, #bd2130);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        }
        
        .chat-content {
            display: flex;
            height: 650px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
        }
        
        .chat-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 10% 20%, rgba(102, 126, 234, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(118, 75, 162, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .chat-sidebar {
            width: 320px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-right: 1px solid rgba(222, 226, 230, 0.3);
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-header {
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            border-bottom: 1px solid rgba(222, 226, 230, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .sidebar-header h6 {
            margin: 0;
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-new-chat {
            width: 35px;
            height: 35px;
            border: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-new-chat:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .loading-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }
        
        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-messages {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            position: relative;
        }
        
        .chat-messages::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(102, 126, 234, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(118, 75, 162, 0.03) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .message {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            animation: messageSlide 0.3s ease-out;
        }
        
        @keyframes messageSlide {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message.received {
            justify-content: flex-start;
        }
        
        .message-content {
            max-width: 75%;
            padding: 1rem 1.25rem;
            border-radius: 25px;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .message.sent .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            border-bottom-right-radius: 8px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }
        
        .message.received .message-content {
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            border: 1px solid #e9ecef;
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        
        .message.sent .message-time {
            text-align: right;
        }
        
        .chat-input {
            padding: 1rem;
            background: white;
            border-top: 1px solid #dee2e6;
        }
        
        .chat-input-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .chat-input input {
            flex: 1;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .chat-input input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
        }
        
        .chat-input button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border: none;
            border-radius: 50%;
            width: 55px;
            height: 55px;
            color: white;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .chat-input button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .chat-input button:hover {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
        }
        
        .chat-input button:hover::before {
            left: 100%;
        }
        
        .chat-input button:disabled {
            opacity: 0.6;
            transform: none;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.2);
        }
        
        .conversation-item {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(233, 236, 239, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(5px);
        }
        
        .conversation-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .conversation-item:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        }
        
        .conversation-item:hover::before {
            opacity: 1;
        }
        
        .conversation-item.active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
            border-left: 5px solid #667eea;
            transform: translateX(8px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.2);
        }
        
        .conversation-user {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .conversation-preview {
            font-size: 0.9rem;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .conversation-time {
            font-size: 0.8rem;
            color: #adb5bd;
            margin-top: 0.25rem;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.6rem;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .status-online {
            background: linear-gradient(135deg, #28a745, #20c997);
            animation: statusPulse 2s ease-in-out infinite;
        }
        
        @keyframes statusPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
        }
        
        .status-offline {
            background: #6c757d;
        }
        
        .typing-indicator {
            display: none;
            padding: 0.5rem 1rem;
            color: #6c757d;
            font-style: italic;
        }
        
        .typing-indicator.show {
            display: block;
        }
        
        .welcome-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }
        
        .welcome-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .welcome-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .welcome-screen h4 {
            color: #495057;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .welcome-screen p {
            color: #6c757d;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .welcome-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .welcome-actions .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .welcome-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .welcome-info {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .info-item i {
            color: #667eea;
        }
        
        /* Online status styles */
        .manager-card.border-success {
            border-left: 4px solid #28a745 !important;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(40, 167, 69, 0.02) 100%);
        }
        
        .manager-card.border-secondary {
            border-left: 4px solid #6c757d !important;
            background: linear-gradient(135deg, rgba(108, 117, 125, 0.05) 0%, rgba(108, 117, 125, 0.02) 100%);
        }
        
        .manager-card.border-danger {
            border-left: 4px solid #dc3545 !important;
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.05) 0%, rgba(220, 53, 69, 0.02) 100%);
        }
        
        .badge.bg-success {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Offline button styles */
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        
        .btn-danger:disabled {
            background: #6c757d;
            box-shadow: none;
            transform: none;
        }
        
        /* Offline status indicator */
        .status-offline {
            background: linear-gradient(135deg, #dc3545, #c82333);
            animation: offlinePulse 3s infinite;
        }
        
        @keyframes offlinePulse {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 0.5; }
        }
        
        .notification-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .user-info {
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .user-info span {
            position: relative;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .chat-header {
                padding: 1rem;
            }
            
            .header-content h1 {
                font-size: 1.5rem;
            }
            
            .header-icon {
                width: 45px;
                height: 45px;
                margin-right: 0.75rem;
            }
            
            .header-icon i {
                font-size: 1.2rem;
            }
            
            .btn-home {
                width: 40px;
                height: 40px;
            }
            
            .chat-content {
                flex-direction: column;
                height: auto;
            }
            
            .chat-sidebar {
                width: 100%;
                height: 200px;
            }
            
            .sidebar-header {
                padding: 1rem;
            }
            
            .welcome-actions {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .welcome-actions .btn {
                width: 100%;
                justify-content: center;
            }
            
            .welcome-info {
                flex-direction: column;
                gap: 1rem;
            }
            
            .chat-main {
                height: 400px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="chat-container">
            <!-- Header -->
            <div class="chat-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="header-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="header-content">
                            <h1>Chat Hỗ trợ</h1>
                        <div class="user-info" id="userInfo" style="display: none;">
                                <span id="userName">Đang tải...</span>
                                <span id="userRole" class="role-badge"></span>
                        </div>
                    </div>
                    </div>
                    <div class="header-actions">
                        <div class="connection-status" id="connectionStatus">
                            <div class="connection-indicator" id="connectionIndicator">
                                <div class="status-dot offline"></div>
                            </div>
                        </div>
                        <a href="index.php" class="btn-home">
                            <i class="fas fa-home"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Chat Content -->
            <div class="chat-content">
                <!-- Sidebar -->
                <div class="chat-sidebar">
                    <div class="sidebar-header">
                        <h6><i class="fas fa-comments"></i> Cuộc trò chuyện</h6>
                        <button class="btn-new-chat" id="newChatBtn" title="Tạo cuộc trò chuyện mới">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="sidebar-content">
                        <div id="conversationsList">
                            <div class="loading-state">
                                <div class="spinner"></div>
                                <p>Đang tải cuộc trò chuyện...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Chat -->
                <div class="chat-main">
                    <!-- Messages -->
                    <div class="chat-messages" id="chatMessages">
                        <div class="welcome-screen">
                            <div class="welcome-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h4>Chào mừng đến với Chat Hỗ trợ!</h4>
                            <p>Kết nối trực tiếp với đội ngũ hỗ trợ chuyên nghiệp của chúng tôi</p>
                            <div class="welcome-actions">
                            <div class="welcome-info">
                                <div class="info-item">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Bảo mật cao</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Phản hồi 24/7</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-users"></i>
                                    <span>Đội ngũ chuyên nghiệp</span>
                                </div>
                            </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Typing Indicator -->
                    <div class="typing-indicator" id="typingIndicator">
                        <i class="fas fa-circle fa-xs"></i>
                        <i class="fas fa-circle fa-xs"></i>
                        <i class="fas fa-circle fa-xs"></i>
                        <span class="ms-2">Đang nhập...</span>
                    </div>
                    
                    <!-- Input -->
                    <div class="chat-input">
                        <div class="chat-input-group">
                            <input type="text" id="messageInput" placeholder="Nhập tin nhắn...">
                            <button type="button" id="sendButton">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manager Selection Modal -->
    <div class="modal fade" id="managerSelectionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-tie"></i> Chọn Quản lý Sự kiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Lọc theo chuyên môn:</h6>
                            <select class="form-select mb-3" id="specializationFilter">
                                <option value="">Tất cả chuyên môn</option>
                                <option value="wedding">Đám cưới</option>
                                <option value="corporate">Sự kiện doanh nghiệp</option>
                                <option value="birthday">Tiệc sinh nhật</option>
                                <option value="conference">Hội nghị</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <h6>Lọc theo trạng thái:</h6>
                            <select class="form-select mb-3" id="statusFilter">
                                <option value="">Tất cả trạng thái</option>
                                <option value="online">Đang online</option>
                                <option value="busy">Bận</option>
                                <option value="available">Có thể hỗ trợ</option>
                            </select>
                        </div>
                    </div>
                    <div id="managersList">
                        <!-- Managers will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="createAutoConversation()">
                        <i class="fas fa-magic"></i> Tự động phân bổ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        let socket = null;
        let currentConversationId = null;
        let currentUserId = <?php 
            if (isset($_SESSION['user']['ID_User'])) {
                echo $_SESSION['user']['ID_User'];
            } elseif (isset($_SESSION['user']['id'])) {
                echo $_SESSION['user']['id'];
            } else {
                echo 'null';
            }
        ?>;
        let currentUserName = '<?php echo addslashes($_SESSION['user']['HoTen'] ?? $_SESSION['user']['name'] ?? 'Người dùng'); ?>';
        let currentUserRole = <?php echo $userRole; ?>;
        let conversations = [];
        let isConnected = false;
        let typingTimeout;
        
        // ✅ Initialize chat
        $(document).ready(() => {
            // Set initial connecting status
            updateConnectionStatus('connecting', 'Đang kết nối...');
            
            initSocket();
            setUserOnline(); // Set user online
            loadConversations();
            setupChatEvents();
            showUserInfo();
            startAutoRefresh();
            
            // Set user offline when page is closed
            $(window).on('beforeunload', function() {
                setUserOffline();
            });
        });
        
        // ✅ Hiển thị thông tin user
        function showUserInfo() {
            const userData = <?php echo json_encode($_SESSION['user'] ?? []); ?>;
            
            if (userData && Object.keys(userData).length > 0) {
                $('#userName').text(userData.HoTen || userData.Email || 'Người dùng');
                
                // Display role badge
                const role = userData.ID_Role || userData.role;
                const roleNames = {
                    1: 'Quản trị viên',
                    2: 'Quản lý tổ chức', 
                    3: 'Quản lý sự kiện',
                    4: 'Nhân viên',
                    5: 'Khách hàng'
                };
                
                if (role && [1, 3, 5].includes(parseInt(role))) {
                    const roleName = roleNames[role] || 'Người dùng';
                    let roleClass = '';
                    if (role == 1) roleClass = 'role-admin';
                    else if (role == 3) roleClass = 'role-event-manager';
                    else if (role == 5) roleClass = 'role-customer';
                    
                    $('#userRole').text(roleName).addClass(roleClass);
                }
                
                $('#userInfo').show();
            } else {
                $('#userName').text('Người dùng');
                $('#userInfo').show();
            }
        }
        
        // ✅ Kết nối Socket.IO
        function initSocket() {
            // Check if Socket.IO is available
            if (typeof io === 'undefined') {
                console.warn('Socket.IO not loaded, chat will work without real-time features');
                isConnected = false;
                updateConnectionStatus('offline', 'Chế độ offline - Không có kết nối real-time');
                return;
            }
            
        socket = io('http://localhost:3000', {
            transports: ['websocket', 'polling'],
            reconnection: true,
            reconnectionAttempts: 5
        });

        if (socket && typeof socket.on === 'function') {
            socket.on('connect', () => {
                    isConnected = true;
                updateConnectionStatus('online', 'Đã kết nối realtime');
                socket.emit('authenticate', {
                    userId: currentUserId,
                    userRole: currentUserRole,
                    userName: currentUserName
                });
                if (currentConversationId) socket.emit('join_conversation', { conversation_id: currentConversationId });
            });

            socket.on('disconnect', () => {
                    isConnected = false;
                updateConnectionStatus('offline', 'Mất kết nối realtime');
            });

            socket.on('reconnect', () => {
                isConnected = true;
                updateConnectionStatus('online', 'Kết nối lại thành công');
                socket.emit('authenticate', { userId: currentUserId, userRole: currentUserRole, userName: currentUserName });
                if (currentConversationId) socket.emit('join_conversation', { conversation_id: currentConversationId });
            });
            
            // 🟢 Nhận tin nhắn mới realtime
            socket.on('new_message', data => {
                console.log('Received new message:', data);
                if (data.conversation_id === currentConversationId) {
                    addMessageToChat(data, false);
                    scrollToBottom();
                } else {
                    loadConversations(); // cập nhật preview
                }
            });

            // 🟢 Hiển thị "đang nhập..."
            socket.on('typing', data => {
                console.log('Received typing indicator:', data);
                if (data.conversation_id === currentConversationId && data.user_id !== currentUserId) {
                    $('#typingIndicator').html(`<i class="fas fa-circle fa-xs"></i><i class="fas fa-circle fa-xs"></i><i class="fas fa-circle fa-xs"></i>
                        <span class="ms-2">${data.user_name} đang nhập...</span>`).fadeIn(150);
                    clearTimeout(typingTimeout);
                    typingTimeout = setTimeout(() => $('#typingIndicator').fadeOut(150), 2000);
                }
            });

            // 🟢 Ẩn "đang nhập..."
            socket.on('stop_typing', data => {
                console.log('Received stop typing:', data);
                if (data.conversation_id === currentConversationId && data.user_id !== currentUserId) {
                    $('#typingIndicator').fadeOut(150);
                }
            });

            // Handle broadcast messages
            socket.on('broadcast_message', data => {
                console.log('Received broadcast message:', data);
                if (data.conversation_id === currentConversationId && data.userId !== currentUserId) {
                    addMessageToChat(data.message, false);
                    scrollToBottom();
                }
            });

            // Handle message read status
            socket.on('message_read', data => {
                console.log('Message read status:', data);
                if (data.conversation_id === currentConversationId) {
                    updateMessageReadStatus(data.message_id);
                }
            });
        } else {
            console.warn('Socket not available, using fallback mode');
            isConnected = false;
            updateConnectionStatus('offline', 'Chế độ offline - Socket không khả dụng');
        }
        }
        
        // ✅ Set user online
        function setUserOnline() {
            $.ajax({
                url: 'src/controllers/chat-controller.php?action=set_user_online',
                type: 'POST',
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        console.log('User set online successfully');
                    } else {
                        console.error('Failed to set user online:', data.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error setting user online:', error);
                }
            });
        }
        
        // ✅ Set user offline
        function setUserOffline() {
            $.ajax({
                url: 'src/controllers/chat-controller.php?action=set_user_offline',
                type: 'POST',
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        console.log('User set offline successfully');
                    } else {
                        console.error('Failed to set user offline:', data.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error setting user offline:', error);
                }
            });
        }
        
        // ✅ Hiển thị danh sách hội thoại
        function loadConversations() {
            $.getJSON('src/controllers/chat-controller.php?action=get_conversations', res => {
                if (!res.success) return;
                const list = res.conversations;
                let html = '';
                list.forEach(c => {
                    const time = new Date(c.updated_at).toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});
                    html += `
                    <div class="conversation-item" data-id="${c.id}" onclick="selectConversation(${c.id})">
                        <div class="conversation-user">
                            <span><span class="status-indicator ${c.is_online ? 'status-online' : 'status-offline'}"></span>${c.other_user_name}</span>
                            ${c.unread_count>0?`<span class="conversation-badge">${c.unread_count}</span>`:''}
                        </div>
                        <div class="conversation-preview">${c.last_message||'Chưa có tin nhắn'}</div>
                        <div class="conversation-time">${time}</div>
                    </div>`;
                });
                $('#conversationsList').html(html||'<p class="text-center text-muted">Chưa có cuộc trò chuyện</p>');
            });
        }
        
        // Show conversation error
        function showConversationError(errorMessage) {
                    $('#conversationsList').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                    ${errorMessage}
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-primary btn-sm" onclick="createNewConversation()">
                                <i class="fas fa-plus"></i> Tạo cuộc trò chuyện mới
                            </button>
                    <button class="btn btn-outline-secondary btn-sm ms-2" onclick="loadConversations()">
                        <i class="fas fa-refresh"></i> Thử lại
                            </button>
                        </div>
                    `);
                
                // Enable input for creating new conversation
                enableInput();
        }
        
        // Mark messages as read
        function markMessagesAsRead(conversationId) {
            if (!conversationId) return;
            
            $.post('src/controllers/chat-controller.php?action=mark_as_read', {
                conversation_id: conversationId
            }, function(data) {
                if (data.success) {
                    console.log('Messages marked as read');
                }
            }, 'json');
        }
        
        // Display conversations
        function displayConversations() {
            if (conversations.length === 0) {
                $('#conversationsList').html(`
                    <div class="text-center text-muted">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p>Chưa có cuộc trò chuyện nào</p>
                        <p class="small text-info mb-3">
                            <i class="fas fa-info-circle"></i> 
                            Bạn có thể tạo cuộc trò chuyện mới với nhân viên hỗ trợ. Tin nhắn sẽ được lưu lại và trả lời khi họ online.
                        </p>
                        <div class="conversation-options">
                            <div class="mb-3">
                                <h6>Chọn cách liên hệ:</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button class="btn btn-outline-primary w-100" onclick="createAutoConversation()">
                                            <i class="fas fa-magic"></i> Tự động phân bổ
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button class="btn btn-outline-success w-100" onclick="showManagerSelection()">
                                            <i class="fas fa-user-tie"></i> Chọn quản lý
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                
                // Enable input for creating new conversation
                enableInput();
                return;
            }
            
            let html = '';
            conversations.forEach(conv => {
                const time = new Date(conv.updated_at).toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `
                    <div class="conversation-item" onclick="selectConversation(${conv.id})" data-conversation-id="${conv.id}">
                        <div class="conversation-user">
                            <span class="status-indicator ${conv.is_online ? 'status-online' : 'status-offline'}"></span>
                            ${conv.other_user_name}
                        </div>
                        <div class="conversation-preview">${conv.last_message || 'Chưa có tin nhắn'}</div>
                        <div class="conversation-time">${time}</div>
                    </div>
                `;
            });
            
            $('#conversationsList').html(html);
        }
        
        // ✅ Khi chọn hội thoại
        function selectConversation(id) {
            currentConversationId = id;
            $('.conversation-item').removeClass('active');
            $(`.conversation-item[data-id="${id}"]`).addClass('active');
            $('#chatInput').show();
            $('#messageInput,#sendButton').prop('disabled',false);
            $('#typingIndicator').hide();
            if (socket && typeof socket.emit === 'function') {
                socket.emit('join_conversation',{conversation_id:id});
            }
            loadMessages(id);
        }
        
        // Enable input when no conversation is selected
        function enableInput() {
            $('#messageInput').prop('disabled', false);
            $('#sendButton').prop('disabled', false);
        }
        
        // ✅ Load tin nhắn
        function loadMessages(convId){
            $.getJSON(`src/controllers/chat-controller.php?action=get_messages&conversation_id=${convId}`, res=>{
                if(!res.success) return;
                let html='';
                res.messages.forEach(m=>{
                    html+=createMessageHTML(m);
                });
                $('#chatMessages').html(html);
                scrollToBottom();
            });
        }
        
        // Show message error
        function showMessageError(errorMessage) {
                    $('#chatMessages').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                    ${errorMessage}
                        </div>
                <div class="text-center mt-3">
                    <button class="btn btn-outline-secondary btn-sm" onclick="loadMessages(${currentConversationId})">
                        <i class="fas fa-refresh"></i> Thử lại
                    </button>
                    </div>
                `);
        }
        
        // Display messages
        function displayMessages(messages) {
            console.log('displayMessages called with:', messages);
            
            if (!messages || !Array.isArray(messages)) {
                console.error('Invalid messages array:', messages);
                $('#chatMessages').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Lỗi: Dữ liệu tin nhắn không hợp lệ.
                    </div>
                `);
                return;
            }
            
            if (messages.length === 0) {
                $('#chatMessages').html(`
                    <div class="no-messages">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h5>Bắt đầu cuộc trò chuyện</h5>
                        <p>Gửi tin nhắn đầu tiên để bắt đầu!</p>
                    </div>
                `);
                return;
            }
            
            let html = '';
            messages.forEach((message, index) => {
                console.log(`Processing message ${index}:`, message);
                try {
                    html += createMessageHTML(message);
                } catch (error) {
                    console.error(`Error processing message ${index}:`, error, message);
                    html += '<div class="message error"><div class="message-content"><div>Lỗi hiển thị tin nhắn</div></div></div>';
                }
            });
            
            $('#chatMessages').html(html);
            scrollToBottom();
        }
        
        // ✅ Tạo HTML tin nhắn
        function createMessageHTML(m){
            const isSent=m.sender_id==currentUserId;
            const time=new Date(m.created_at).toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});
            return `<div class="message ${isSent?'sent':'received'}">
                <div class="message-content">
                    <div>${escapeHtml(m.message)}</div>
                    <div class="message-time">${time}${isSent?(m.IsRead?' <i class="fas fa-check-double text-primary"></i>':' <i class="fas fa-check text-muted"></i>'):''}</div>
                </div>
            </div>`;
        }
        
        // ✅ Thêm tin nhắn vào khung chat
        function addMessageToChat(msg,isSent){
            const html=createMessageHTML(msg);
            $('#chatMessages').append(html);
        }
        
        // ✅ Setup chat events
        function setupChatEvents() {
            // Welcome screen buttons
            $('#startAutoChat').click(function() {
                createConversation('auto');
            });
            
            $('#selectManager').click(function() {
                $('#managerSelectionModal').modal('show');
            });
            
            $('#newChatBtn').click(function() {
                $('#managerSelectionModal').modal('show');
            });
            
            // ✅ Gửi tin nhắn realtime
            $('#sendButton').click(sendMessage);
            $('#messageInput').keypress(e=>{ if(e.which===13) sendMessage(); });

            // ✅ Xử lý typing realtime
            let typing=false,typingTimer;
            $('#messageInput').on('input',()=>{
                if(!currentConversationId) return;
                if(!typing){
                    typing=true;
                    if (socket && typeof socket.emit === 'function') {
                        socket.emit('typing',{conversation_id:currentConversationId,user_id:currentUserId,user_name:currentUserName});
                    }
                }
                clearTimeout(typingTimer);
                typingTimer=setTimeout(()=>{
                    typing=false;
                    if (socket && typeof socket.emit === 'function') {
                        socket.emit('stop_typing',{conversation_id:currentConversationId,user_id:currentUserId});
                    }
                },1500);
            });
        }
        
        // ✅ Gửi tin nhắn realtime
        function sendMessage(){
            const text=$('#messageInput').val().trim();
            if(!text||!currentConversationId) return;
            
            // Show loading state
            const sendButton = $('#sendButton');
            const originalText = sendButton.html();
            sendButton.html('<i class="fas fa-spinner fa-spin"></i>');
            sendButton.prop('disabled', true);
            
            $.ajax({
                url: 'src/controllers/chat-controller.php?action=send_message',
                method: 'POST',
                dataType: 'json',
                timeout: 10000,
                data: {
                    conversation_id: currentConversationId,
                    message: text
                },
                success: function(res) {
                    if (res.success) {
                        $('#messageInput').val('');
                        
                        // Add message immediately for instant feedback
                        addMessageToChat(res.message, true);
                        scrollToBottom();
                        
                        // Emit real-time events
                        if (isConnected && socket) {
                            if (socket && typeof socket.emit === 'function') {
                                socket.emit('new_message', {
                                    conversation_id: currentConversationId,
                                    message: res.message.message || res.message.text,
                                    user_id: currentUserId,
                                    user_name: currentUserName
                                });
                                
                                socket.emit('broadcast_message', {
                                    conversation_id: currentConversationId,
                                    message: res.message,
                                    userId: currentUserId,
                                    timestamp: new Date().toISOString()
                                });
                                
                                socket.emit('stop_typing', {
                                    conversation_id: currentConversationId,
                                    user_id: currentUserId
                                });
                            }
                        }
                        
                        // Update conversation preview
                        updateConversationPreview(currentConversationId, res.message.message || res.message.text);
                        
                        // Refresh conversation list if not connected
                        if (!isConnected) {
                            setTimeout(function() {
                                loadConversations();
                            }, 500);
                        }
                    } else {
                        alert('Lỗi khi gửi tin nhắn: ' + res.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Send message error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    let errorMessage = 'Lỗi kết nối server';
                    
                    if (xhr.responseText && xhr.responseText.includes('<!doctype')) {
                        errorMessage = 'Server trả về trang lỗi thay vì JSON';
                    } else if (status === 'timeout') {
                        errorMessage = 'Timeout - Server không phản hồi';
                    } else if (status === 'parsererror') {
                        errorMessage = 'Lỗi phân tích JSON từ server';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Lỗi server nội bộ (500)';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Không tìm thấy file controller (404)';
                    }
                    
                    alert('Lỗi gửi tin nhắn: ' + errorMessage);
                },
                complete: function() {
                    // Restore button state
                    sendButton.html(originalText);
                    sendButton.prop('disabled', false);
                }
            });
        }
        
        // Create new conversation
        function createNewConversation() {
            console.log('Creating new conversation...');
            
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo...';
            button.disabled = true;
            
            $.ajax({
                url: 'src/controllers/chat-controller.php?action=create_conversation',
                method: 'POST',
                dataType: 'json',
                timeout: 10000,
                data: {
                other_user_id: 'auto' // Let server assign staff
                },
                success: function(data) {
                if (data.success) {
                    console.log('Conversation created:', data.conversation_id);
                    currentConversationId = data.conversation_id;
                    
                    // Enable input
                    $('#messageInput').prop('disabled', false);
                    $('#sendButton').prop('disabled', false);
                    
                    loadConversations();
                    loadMessages(data.conversation_id);
                } else {
                    alert('Lỗi khi tạo cuộc trò chuyện: ' + data.error);
                }
                },
                error: function(xhr, status, error) {
                console.error('Create conversation error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    let errorMessage = 'Lỗi kết nối server';
                    
                    if (xhr.responseText && xhr.responseText.includes('<!doctype')) {
                        errorMessage = 'Server trả về trang lỗi thay vì JSON';
                    } else if (status === 'timeout') {
                        errorMessage = 'Timeout - Server không phản hồi';
                    } else if (status === 'parsererror') {
                        errorMessage = 'Lỗi phân tích JSON từ server';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Lỗi server nội bộ (500)';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Không tìm thấy file controller (404)';
                    }
                    
                    alert('Lỗi tạo cuộc trò chuyện: ' + errorMessage);
                },
                complete: function() {
                // Restore button state
                button.innerHTML = originalText;
                button.disabled = false;
                }
            });
        }
        
        // ✅ Cập nhật trạng thái kết nối
        function updateConnectionStatus(status, text) {
            const indicator = $('#connectionIndicator .status-dot');
            indicator.removeClass('online offline connecting').addClass(status);
            
            // Thêm tooltip để hiển thị text khi hover
            indicator.attr('title', text);
        }
        
        // Show typing indicator
        function showTypingIndicator(userName) {
            $('#typingIndicator').html(`
                <i class="fas fa-circle fa-xs"></i>
                <i class="fas fa-circle fa-xs"></i>
                <i class="fas fa-circle fa-xs"></i>
                <span class="ms-2">${userName} đang nhập...</span>
            `).addClass('show');
        }
        
        // Hide typing indicator
        function hideTypingIndicator() {
            $('#typingIndicator').removeClass('show');
        }
        
        // Update message read status
        function updateMessageReadStatus(messageId) {
            $(`.message[data-message-id="${messageId}"] .message-time`).html(function() {
                return $(this).html().replace('<i class="fas fa-check text-muted"></i>', '<i class="fas fa-check-double text-primary"></i>');
            });
        }
        
        // ✅ Tự reload hội thoại mỗi 30s khi offline
        function startAutoRefresh(){
            setInterval(()=>{
                if(!isConnected) loadConversations();
            },30000);
        }
        
        // Real-time message update handler
        function handleRealTimeMessage(data) {
            console.log('Handling real-time message:', data);
            
            // Add message to current conversation if it matches
            if (data.conversation_id === currentConversationId) {
                addMessageToChat(data, false);
            }
            
            // Update conversation preview
            updateConversationPreview(data.conversation_id, data.message);
            
            // Update conversation list
            loadConversations();
        }
        
        // Enhanced message loading with real-time updates
        function loadMessagesWithRealTime(conversationId) {
            console.log('Loading messages with real-time updates for:', conversationId);
            
            // Load messages immediately
            loadMessages(conversationId);
            
            // Set up real-time listeners for this conversation
            if (isConnected && socket && typeof socket.emit === 'function') {
                socket.emit('join_conversation', { conversation_id: conversationId });
                
                // Listen for new messages in this conversation
                if (socket && typeof socket.on === 'function') {
                    socket.on('new_message', function(data) {
                        if (data.conversation_id === conversationId) {
                            handleRealTimeMessage(data);
                        }
                    });
                }
            }
        }
        
        // Broadcast message instantly to all connected users
        function broadcastMessageInstantly(messageData) {
            if (isConnected && socket) {
                socket.emit('broadcast_message', {
                    conversation_id: currentConversationId,
                    message: messageData,
                    userId: getCurrentUserId(),
                    timestamp: new Date().toISOString()
                });
            }
        }
        
        // Handle instant message broadcasting
        if (socket && typeof socket.on === 'function') {
            socket.on('broadcast_message', function(data) {
                console.log('Received broadcast message:', data);
                if (data.conversation_id === currentConversationId && data.userId !== currentUserId) {
                    addMessageToChat(data.message, false);
                }
                updateConversationPreview(data.conversation_id, data.message.message || data.message.text);
            });
        }
        
        // Manager selection functions
        function showManagerSelection() {
            const modal = new bootstrap.Modal(document.getElementById('managerSelectionModal'));
            modal.show();
            loadAvailableManagers();
        }
        
        function loadAvailableManagers() {
            $.get('src/controllers/chat-controller.php?action=get_available_managers', function(data) {
                if (data.success) {
                    // Ưu tiên hiển thị nhân viên online trước
                    const onlineManagers = data.managers.filter(manager => manager.is_online);
                    const offlineManagers = data.managers.filter(manager => !manager.is_online);
                    const sortedManagers = [...onlineManagers, ...offlineManagers];
                    
                    if (sortedManagers.length > 0) {
                        displayManagers(sortedManagers);
                    } else {
                        // Nếu không có manager nào, fallback về admin
                        loadAdminFallback();
                    }
                } else {
                    // Fallback về admin nếu không load được managers
                    loadAdminFallback();
                }
            }, 'json').fail(function() {
                // Fallback về admin nếu có lỗi
                loadAdminFallback();
            });
        }
        
        function loadAdminFallback() {
            $('#managersList').html(`
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Không có nhân viên nào online</strong><br>
                    Bạn sẽ được chuyển đến <strong>Quản trị viên</strong> để được hỗ trợ.
                </div>
                <div class="text-center mt-3">
                    <button class="btn btn-primary" onclick="createConversationWithAdmin()">
                        <i class="fas fa-user-shield"></i> Chat với Quản trị viên
                    </button>
                </div>
            `);
        }
        
        function createConversationWithAdmin() {
            // Tạo conversation với admin (role 1)
            $.post('src/controllers/chat-controller.php?action=create_conversation', {
                other_user_id: 'admin' // Server sẽ tự động tìm admin
            }, function(data) {
                if (data.success) {
                    currentConversationId = data.conversation_id;
                    $('#messageInput').prop('disabled', false);
                    $('#sendButton').prop('disabled', false);
                    loadConversations();
                    loadMessages(data.conversation_id);
                    
                    // Đóng modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('managerSelectionModal'));
                    if (modal) modal.hide();
                } else {
                    alert('Lỗi khi tạo cuộc trò chuyện với quản trị viên: ' + data.error);
                }
            }, 'json');
        }
        
        function displayManagers(managers) {
            let html = '';
            
            // Hiển thị thống kê online
            const onlineCount = managers.filter(m => m.is_online).length;
            const offlineCount = managers.filter(m => !m.is_online).length;
            const totalCount = managers.length;
            
            html += `
                <div class="alert alert-info mb-3">
                    <i class="fas fa-users"></i>
                    <strong>${onlineCount}/${totalCount}</strong> nhân viên đang online
                    ${offlineCount > 0 ? `<br><small class="text-muted"><i class="fas fa-user-slash text-danger"></i> ${offlineCount} nhân viên offline</small>` : ''}
                </div>
            `;
            
            managers.forEach(manager => {
                const statusClass = manager.is_online ? 'success' : 'danger';
                const statusText = manager.is_online ? 'Đang online' : 'Offline';
                const statusIcon = manager.is_online ? 'fa-circle' : 'fa-circle';
                const cardClass = manager.is_online ? 'border-success' : 'border-danger';
                
                html += `
                    <div class="card mb-3 manager-card ${cardClass}" data-manager-id="${manager.id}">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="card-title mb-1">
                                        <i class="fas fa-user-tie text-primary"></i>
                                        ${manager.name}
                                        ${manager.is_online ? 
                                            '<span class="badge bg-success ms-2">ONLINE</span>' : 
                                            '<span class="badge bg-danger ms-2">OFFLINE</span>'
                                        }
                                    </h6>
                                    <p class="card-text text-muted mb-1">
                                        <i class="fas fa-envelope"></i> ${manager.email}
                                    </p>
                                    <p class="card-text text-muted mb-1">
                                        <i class="fas fa-briefcase"></i> ${manager.specialization || 'Tổng quát'}
                                    </p>
                                    <span class="badge bg-${statusClass}">
                                        <i class="fas ${statusIcon}"></i> ${statusText}
                                    </span>
                                    ${!manager.is_online ? 
                                        '<br><small class="text-muted"><i class="fas fa-info-circle"></i> Tin nhắn sẽ được trả lời khi họ online</small>' : 
                                        ''
                                    }
                                </div>
                                <div class="col-md-4 text-end">
                                    <button class="btn ${manager.is_online ? 'btn-success' : 'btn-danger'} btn-sm" 
                                            onclick="selectManager(${manager.id})"
                                            ${!manager.is_online ? 'title="Nhân viên này đang offline - Tin nhắn sẽ được trả lời khi họ online"' : ''}>
                                        <i class="fas ${manager.is_online ? 'fa-comments' : 'fa-user-slash'}"></i> 
                                        ${manager.is_online ? 'Chat ngay' : 'Offline'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Thêm nút fallback về admin nếu không có ai online
            if (onlineCount === 0) {
                html += `
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Không có nhân viên nào online</strong><br>
                        <small class="text-muted">Tất cả nhân viên đang offline. Bạn có thể:</small>
                        <ul class="mb-2 mt-2">
                            <li>Chat với nhân viên offline (tin nhắn sẽ được trả lời khi họ online)</li>
                            <li>Chuyển đến quản trị viên để được hỗ trợ ngay lập tức</li>
                        </ul>
                    </div>
                    <div class="text-center">
                        <button class="btn btn-primary" onclick="createConversationWithAdmin()">
                            <i class="fas fa-user-shield"></i> Chat với Quản trị viên
                        </button>
                    </div>
                `;
            }
            
            $('#managersList').html(html);
        }
        
        function selectManager(managerId) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('managerSelectionModal'));
            modal.hide();
            
            // Kiểm tra trạng thái online của manager
            const managerCard = $(`.manager-card[data-manager-id="${managerId}"]`);
            const isOnline = managerCard.find('.badge.bg-success').length > 0;
            
            if (!isOnline) {
                // Hiển thị thông báo cho nhân viên offline
                showNotification('Nhân viên này đang offline. Tin nhắn sẽ được trả lời khi họ online.', 'warning');
            }
            
            // Create conversation with selected manager
            createConversationWithManager(managerId);
        }
        
        function createConversationWithManager(managerId) {
            $.post('src/controllers/chat-controller.php?action=create_conversation', {
                other_user_id: managerId
            }, function(data) {
                if (data.success) {
                    currentConversationId = data.conversation_id;
                    $('#messageInput').prop('disabled', false);
                    $('#sendButton').prop('disabled', false);
                    loadConversations();
                    loadMessages(data.conversation_id);
                } else {
                    alert('Lỗi khi tạo cuộc trò chuyện: ' + data.error);
                }
            }, 'json');
        }
        
        function createAutoConversation() {
            // Close modal if open
            const modal = bootstrap.Modal.getInstance(document.getElementById('managerSelectionModal'));
            if (modal) modal.hide();
            
            // Tạo conversation tự động với ưu tiên nhân viên online
            $.post('src/controllers/chat-controller.php?action=create_conversation', {
                other_user_id: 'auto_online' // Server sẽ tìm nhân viên online trước
            }, function(data) {
                if (data.success) {
                    currentConversationId = data.conversation_id;
                    $('#messageInput').prop('disabled', false);
                    $('#sendButton').prop('disabled', false);
                    loadConversations();
                    loadMessages(data.conversation_id);
                    
                    // Hiển thị thông báo về người được chọn
                    if (data.assigned_staff) {
                        showNotification(`Đã kết nối với ${data.assigned_staff.name} (${data.assigned_staff.role})`, 'success');
                    }
                } else {
                    // Nếu không tìm được nhân viên online, fallback về admin
                    createConversationWithAdmin();
                }
            }, 'json').fail(function() {
                // Nếu có lỗi, fallback về admin
                createConversationWithAdmin();
            });
        }
        
        function showNotification(message, type = 'info') {
            let alertClass, icon;
            
            switch(type) {
                case 'success':
                    alertClass = 'alert-success';
                    icon = 'fa-check-circle';
                    break;
                case 'warning':
                    alertClass = 'alert-warning';
                    icon = 'fa-exclamation-triangle';
                    break;
                case 'error':
                    alertClass = 'alert-danger';
                    icon = 'fa-exclamation-circle';
                    break;
                default:
                    alertClass = 'alert-info';
                    icon = 'fa-info-circle';
            }
            
            const notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert">
                    <i class="fas ${icon}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            $('body').prepend(notification);
            
            // Tự động ẩn sau 5 giây
            setTimeout(() => {
                notification.alert('close');
            }, 5000);
        }
        
        // Filter managers
        $('#specializationFilter, #statusFilter').on('change', function() {
            // Implement filtering logic here
            console.log('Filter changed');
        });
        
        // Update conversation preview
        function updateConversationPreview(conversationId, message) {
            const convEl = $(`.conversation-item[data-conversation-id="${conversationId}"]`);
            if (convEl.length) {
                convEl.find('.conversation-preview').text(message);
                convEl.find('.conversation-time').text(new Date().toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                }));
            }
        }
        
        // ✅ Cuộn xuống cuối
        function scrollToBottom(){
            const el=$('#chatMessages');
            el.scrollTop(el[0].scrollHeight);
        }
        
        // Get current user ID
        function getCurrentUserId() {
            // This should be set from PHP session
            return window.currentUserId || <?php 
                if (isset($_SESSION['user']['ID_User'])) {
                    echo $_SESSION['user']['ID_User'];
                } elseif (isset($_SESSION['user']['id'])) {
                    echo $_SESSION['user']['id'];
                } else {
                    echo 'null';
                }
            ?>;
        }
        
        // ✅ Escape HTML an toàn
        function escapeHtml(text){
            if (!text || typeof text !== 'string') {
                return '';
            }
            return text.replace(/[&<>"']/g, function(m) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return map[m] || m;
            });
        }
    </script>
    
    <!-- Socket.IO -->
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>

</body>
</html>