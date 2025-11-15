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
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .connection-status.online {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(40, 167, 69, 0.1));
        }
        
        .connection-status.offline {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(220, 53, 69, 0.1));
        }
        
        .connection-status.connecting {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2), rgba(255, 193, 7, 0.1));
        }
        
        .connection-text {
            font-size: 0.85rem;
            color: white;
            font-weight: 500;
            white-space: nowrap;
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
        
        .chat-header-bar {
            padding: 1rem;
            background: white;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }
        
        .user-details h6 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }
        
        .user-details small {
            font-size: 0.8rem;
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
            width: 45px;
            height: 45px;
            color: white;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
            margin-left: 0.5rem;
        }
        
        .chat-input button#sendButton {
            width: 55px;
            height: 55px;
            font-size: 1.3rem;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 4px 20px rgba(40, 167, 69, 0.3);
        }
        
        .chat-input button#voiceCallButton {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            box-shadow: 0 4px 20px rgba(23, 162, 184, 0.3);
        }
        
        .chat-input button#videoCallButton {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            box-shadow: 0 4px 20px rgba(220, 53, 69, 0.3);
        }
        
        .chat-input button#attachButton {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            box-shadow: 0 4px 20px rgba(108, 117, 125, 0.3);
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
        
        /* Media Message Styles */
        .media-message {
            max-width: 100%;
            margin: 0.5rem 0;
        }
        
        .media-message img {
            max-width: 300px;
            max-height: 300px;
            width: auto;
            height: auto;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.3s ease;
            display: block;
            object-fit: contain;
        }
        
        .media-message img:hover {
            transform: scale(1.02);
        }
        
        .media-message .file-info {
            background: rgba(255, 255, 255, 0.9);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            margin-top: 0.25rem;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            max-width: 100%;
        }
        
        .media-message .file-info i {
            font-size: 1rem;
            color: #667eea;
        }
        
        .media-message .file-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .media-message .file-size {
            color: #666;
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        /* Voice/Video Call message styling */
        .media-message .file-info {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 1px solid rgba(102, 126, 234, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        /* Call UI Styles */
        .call-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .call-modal.show {
            display: flex;
        }
        
        .call-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .call-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: white;
        }
        
        .call-info h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .call-info p {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .call-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .call-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            font-size: 1.5rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .call-btn.accept {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .call-btn.reject {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .call-btn.end {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .call-btn:hover {
            transform: scale(1.1);
        }
        
        .call-status {
            margin: 1rem 0;
            font-weight: 600;
            color: #667eea;
        }
        
        /* Video Call Styles */
        .video-call-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 10000;
            display: none;
        }
        
        .video-call-container.show {
            display: block;
        }
        
        .remote-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .local-video {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 200px;
            height: 150px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .video-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1rem;
        }
        
        .video-control-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            font-size: 1.2rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .video-control-btn.mute {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .video-control-btn.camera {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .video-control-btn.end {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .video-control-btn:hover {
            transform: scale(1.1);
        }
        
        /* Loading States */
        .upload-progress {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin: 0.5rem 0;
            text-align: center;
        }
        
        .upload-progress .progress-bar {
            width: 100%;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .upload-progress .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s ease;
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
            <!-- Phần đầu trang -->
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
                            <span class="connection-text" id="connectionText">Đang kết nối...</span>
                        </div>
                        <a href="index.php" class="btn-home">
                            <i class="fas fa-home"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Nội dung chat -->
            <div class="chat-content">
                <!-- Thanh bên danh sách cuộc trò chuyện -->
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
                
                <!-- Khu vực chat chính -->
                <div class="chat-main">
                    <!-- Thanh header của cuộc trò chuyện -->
                    <div class="chat-header-bar" id="chatHeaderBar" style="display: none;">
                        <div class="chat-user-info">
                            <div class="user-avatar-small">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="user-details">
                                <h6 id="chatUserName">Chọn cuộc trò chuyện</h6>
                                <small id="chatUserStatus" class="text-muted">Chưa chọn</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Khu vực hiển thị tin nhắn -->
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
                    
                    <!-- Chỉ báo đang nhập -->
                    <div class="typing-indicator" id="typingIndicator">
                        <i class="fas fa-circle fa-xs"></i>
                        <i class="fas fa-circle fa-xs"></i>
                        <i class="fas fa-circle fa-xs"></i>
                        <span class="ms-2">Đang nhập...</span>
                    </div>
                    
                    <!-- Ô nhập tin nhắn -->
                    <div class="chat-input" id="chatInput">
                        <div class="chat-input-group">
                            <input type="text" id="messageInput" placeholder="Nhập tin nhắn..." disabled>
                            <button type="button" id="attachButton" title="Đính kèm file" disabled>
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <button type="button" id="voiceCallButton" title="Gọi thoại" disabled>
                                <i class="fas fa-phone"></i>
                            </button>
                            <button type="button" id="videoCallButton" title="Gọi video" disabled>
                                <i class="fas fa-video"></i>
                            </button>
                            <button type="button" id="sendButton" disabled>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <input type="file" id="fileInput" accept="image/*,.pdf,.doc,.docx,.txt,.zip,.rar" style="display: none;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal chọn quản lý sự kiện -->
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
                            <h6>Lọc theo vai trò:</h6>
                            <select class="form-select mb-3" id="roleFilter">
                                <option value="">Tất cả vai trò</option>
                                <option value="1">Quản trị viên</option>
                                <option value="3">Quản lý sự kiện</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i>
                                <small>Chỉ hiển thị nhân viên đang online (Role 1 và 3)</small>
                            </div>
                        </div>
                    </div>
                    <div id="managersList">
                        <!-- Danh sách nhân viên sẽ được tải vào đây -->
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

    <!-- Modal cuộc gọi -->
    <div class="call-modal" id="callModal">
        <div class="call-container">
            <div class="call-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="call-info">
                <h3 id="callerName">Đang gọi...</h3>
                <p id="callType">Cuộc gọi thoại</p>
                <div class="call-status" id="callStatus">Đang kết nối...</div>
            </div>
            <div class="call-controls" id="callControls">
                <button class="call-btn accept" onclick="acceptCall()">
                    <i class="fas fa-phone"></i>
                </button>
                <button class="call-btn reject" onclick="rejectCall()">
                    <i class="fas fa-phone-slash"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Container cuộc gọi video -->
    <div class="video-call-container" id="videoCallContainer">
        <video id="remoteVideo" class="remote-video" autoplay playsinline></video>
        <video id="localVideo" class="local-video" autoplay playsinline muted></video>
        <div class="video-controls">
            <button class="video-control-btn mute" id="muteBtn" onclick="toggleMute()">
                <i class="fas fa-microphone"></i>
            </button>
            <button class="video-control-btn camera" id="cameraBtn" onclick="toggleCamera()">
                <i class="fas fa-video"></i>
            </button>
            <button class="video-control-btn end" onclick="endVideoCall()">
                <i class="fas fa-phone-slash"></i>
            </button>
        </div>
    </div>

    <!-- Modal xem trước hình ảnh -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xem hình ảnh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="previewImage" src="" alt="Preview" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Socket.IO - Sử dụng CDN cho production, local server cho development -->
    <script>
    // Load Socket.IO client
    (function() {
        const hostname = window.location.hostname;
        const isProduction = hostname.includes('sukien.info.vn') || hostname.includes('sukien');
        
        // For production, use CDN directly (more reliable on cPanel)
        // For localhost, try local server first, then CDN fallback
        let socketScript = document.createElement('script');
        
        if (isProduction) {
            // Production: Use CDN directly
            socketScript.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
            socketScript.onload = function() {
                console.log('Socket.IO loaded from CDN (production)');
            };
            socketScript.onerror = function() {
                console.error('Failed to load Socket.IO from CDN');
            };
        } else {
            // Development: Try local server first
            socketScript.src = 'http://localhost:3000/socket.io/socket.io.js';
            socketScript.onerror = function() {
                console.warn('Local Socket.IO server not available, using CDN fallback');
                const cdnScript = document.createElement('script');
                cdnScript.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
                cdnScript.onload = function() {
                    console.log('Socket.IO loaded from CDN');
                };
                cdnScript.onerror = function() {
                    console.error('Failed to load Socket.IO from both server and CDN');
                };
                document.head.appendChild(cdnScript);
            };
            socketScript.onload = function() {
                console.log('Socket.IO loaded from local server');
            };
        }
        
        document.head.appendChild(socketScript);
    })();
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
        
        // Media and Call variables
        let currentCall = null;
        let localStream = null;
        let remoteStream = null;
        let peerConnection = null;
        let isMuted = false;
        let isCameraOff = false;
        
        // Interval IDs for polling/auto-refresh (to prevent multiple intervals)
        let autoRefreshInterval = null;
        
        // ✅ Initialize chat
        $(document).ready(() => {
            // Set initial connecting status
            updateConnectionStatus('connecting', 'Đang kết nối...');
            
            initSocket();
            setUserOnline(); // Set user online
            loadConversations();
            setupChatEvents();
            setupMediaEvents();
            // ✅ setupCallSocketEvents() will be called in socket.on('connect')
            // to ensure socket is connected before setting up event listeners
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
            
        // Detect environment and set Socket.IO server URL
        // ✅ FIX: Dùng base URL với mount point, path là relative
        const getSocketServerURL = function() {
            const protocol = window.location.protocol;
            
            // Hybrid: WebSocket chạy trên VPS riêng (ws.sukien.info.vn)
            // PHP chạy trên shared hosting (sukien.info.vn)
            if (window.location.hostname.includes('sukien.info.vn')) {
                return protocol + '//ws.sukien.info.vn';  // VPS WebSocket server
            }
            
            // Localhost development
            return 'http://localhost:3000';
        };
        
        const socketServerURL = getSocketServerURL();
        console.log('📡 Connecting to Socket.IO server:', socketServerURL);
        
        // Get SOCKET_PATH for path option
        // ✅ FIX: Path option phải là relative path từ base URL
        // Nếu base URL = 'https://sukien.info.vn/nodeapp', path = '/socket.io'
        // → Socket.IO client tạo request: 'https://sukien.info.vn/nodeapp/socket.io/...'
        const getSocketPath = function() {
            // ✅ SỬA: Luôn dùng relative path '/socket.io'
            // Server sẽ normalize /nodeapp/socket.io → /socket.io
            return '/socket.io';
        };
        
        const socketPath = getSocketPath();
        console.log('📡 Socket.IO path:', socketPath);
        console.log('📡 Full Socket.IO URL:', socketServerURL + socketPath);
        
        // Check if Socket.IO library is loaded
        if (typeof io === 'undefined') {
            console.error('❌ Socket.IO library not loaded!');
            updateConnectionStatus('offline', 'Socket.IO library chưa được tải');
            return;
        }
        
        // Create Socket.IO connection with improved error handling
        try {
            // Validate variables before creating connection
            if (!socketServerURL) {
                throw new Error('socketServerURL is not defined');
            }
            if (!socketPath) {
                throw new Error('socketPath is not defined');
            }
            
            socket = io(socketServerURL, {
                path: socketPath,
                transports: ['polling', 'websocket'], // Try polling first, then websocket
                reconnection: true,
                reconnectionAttempts: Infinity, // Keep trying to reconnect
                reconnectionDelay: 1000,
                reconnectionDelayMax: 10000,
                timeout: 20000,
                forceNew: false,
                autoConnect: true,
                // Add query parameters for debugging
                query: {
                    clientType: 'web',
                    timestamp: Date.now()
                }
            });
            
            console.log('📡 Socket.IO connection initiated');
            console.log('📡 Connection details:', {
                url: socketServerURL,
                path: socketPath,
                fullPath: socketServerURL + socketPath
            });
        } catch (error) {
            console.error('❌ Failed to create Socket.IO connection:', error);
            console.error('Error stack:', error.stack);
            updateConnectionStatus('offline', 'Lỗi tạo kết nối: ' + (error.message || 'Unknown error'));
            return;
        }

        if (socket && typeof socket.on === 'function') {
            socket.on('connect', () => {
                console.log('✅ Socket.IO connected successfully');
                isConnected = true;
                updateConnectionStatus('online', 'Đã kết nối realtime');
                socket.emit('authenticate', {
                    userId: currentUserId,
                    userRole: currentUserRole,
                    userName: currentUserName
                });
                // Ensure user is in their own room for receiving calls
                socket.emit('join_user_room', { userId: currentUserId });
                if (currentConversationId) {
                    socket.emit('join_conversation', { conversation_id: currentConversationId });
                }
                console.log('Socket connected, joined user room:', currentUserId);
                
                // ✅ Setup call socket events AFTER socket is connected
                setupCallSocketEvents();
            });
            
            socket.on('connect_error', (error) => {
                console.error('❌ Socket.IO connection error:', error);
                console.error('Error type:', error.type);
                console.error('Error message:', error.message);
                console.error('Error description:', error.description);
                console.error('Connection URL:', socketServerURL);
                console.error('Connection Path:', socketPath);
                console.error('Full URL:', socketServerURL + socketPath);
                
                // Check if server is reachable
                const healthCheckUrl = socketServerURL + (socketPath.includes('/nodeapp') ? '/nodeapp/health' : '/health');
                console.log('🔍 Checking server health at:', healthCheckUrl);
                
                fetch(healthCheckUrl)
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            console.error('❌ Server health check failed:', response.status);
                            throw new Error('Health check failed');
                        }
                    })
                    .then(data => {
                        console.log('✅ Server is reachable:', data);
                        console.log('💡 Possible causes:');
                        console.log('   - CORS issue');
                        console.log('   - Path mismatch (server expects different path)');
                        console.log('   - Socket.IO server not fully started');
                        console.log('   - Passenger routing issue');
                        console.log('💡 Server path info:', data.path || 'unknown');
                    })
                    .catch(err => {
                        console.error('❌ Cannot reach server:', err);
                        console.log('💡 Server may not be running or URL is incorrect');
                        console.log('💡 Expected server at:', socketServerURL);
                        console.log('💡 Expected Socket.IO at:', socketServerURL + socketPath);
                    });
                
                isConnected = false;
                updateConnectionStatus('offline', 'Lỗi kết nối: ' + (error.message || error.description || 'Unknown error'));
            });
            
            socket.on('disconnect', (reason) => {
                console.warn('⚠️ Socket.IO disconnected:', reason);
                isConnected = false;
                updateConnectionStatus('offline', 'Đã ngắt kết nối');
            });
            
            socket.on('reconnect', (attemptNumber) => {
                console.log('🔄 Socket.IO reconnected after', attemptNumber, 'attempts');
                isConnected = true;
                updateConnectionStatus('online', 'Đã kết nối lại');
                socket.emit('authenticate', { 
                    userId: currentUserId, 
                    userRole: currentUserRole, 
                    userName: currentUserName 
                });
                socket.emit('join_user_room', { userId: currentUserId });
                if (currentConversationId) {
                    socket.emit('join_conversation', { conversation_id: currentConversationId });
                }
                
                // ✅ Re-setup call socket events after reconnect
                if (!socket._callEventsSetup) {
                    setupCallSocketEvents();
                }
            });
            
            socket.on('reconnect_attempt', () => {
                console.log('🔄 Attempting to reconnect...');
            });
            
            socket.on('reconnect_failed', () => {
                console.error('❌ Socket.IO reconnection failed');
                isConnected = false;
                updateConnectionStatus('offline', 'Không thể kết nối lại');
            });
            
            // 🟢 Nhận tin nhắn mới realtime
            socket.on('new_message', data => {
                console.log('Received new message:', data);
                if (data.conversation_id === currentConversationId) {
                    // Kiểm tra xem message có phải là object với thuộc tính message không
                    const messageData = typeof data === 'object' && data.message ? data.message : data;
                    addMessageToChat(messageData, false);
                    scrollToBottom();
                    markMessagesAsRead(currentConversationId);
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
                if (!res.success) {
                    console.error('Error loading conversations:', res.error);
                    $('#conversationsList').html('<p class="text-center text-danger">Lỗi tải cuộc trò chuyện</p>');
                    return;
                }
                const list = res.conversations || [];
                conversations = list; // Cập nhật biến global
                let html = '';
                if (list.length > 0) {
                    list.forEach(c => {
                        const time = new Date(c.updated_at || c.updated_at).toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'});
                        const isOnline = c.is_online === true || c.is_online === 1 || c.is_online === '1';
                        html += `
                        <div class="conversation-item" data-id="${c.id}" onclick="selectConversation(${c.id})">
                            <div class="conversation-user">
                                <span><span class="status-indicator ${isOnline ? 'status-online' : 'status-offline'}" title="${isOnline ? 'Đang online' : 'Đang offline'}"></span>${c.other_user_name || 'Người dùng'}</span>
                                ${c.unread_count > 0 ? `<span class="badge bg-danger rounded-pill">${c.unread_count}</span>` : ''}
                            </div>
                            <div class="conversation-preview">${c.last_message || 'Chưa có tin nhắn'}</div>
                            <div class="conversation-time">${time}</div>
                        </div>`;
                    });
                } else {
                    html = '<p class="text-center text-muted">Chưa có cuộc trò chuyện</p>';
                }
                $('#conversationsList').html(html);
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error loading conversations:', error);
                console.error('Response:', xhr.responseText);
                $('#conversationsList').html('<p class="text-center text-danger">Lỗi kết nối khi tải cuộc trò chuyện</p>');
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
                    // Reload conversations to update unread count
                    loadConversations();
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Error marking messages as read:', error);
            });
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
            
            // Tìm conversation để lấy thông tin người dùng
            const conversation = conversations.find(c => c.id == id);
            if (conversation) {
                // Cập nhật chat header
                $('#chatUserName').text(conversation.other_user_name || 'Người dùng');
                $('#chatUserStatus').text(conversation.is_online ? 'Đang online' : 'Đang offline');
                $('#chatUserStatus').removeClass('text-muted text-success text-danger');
                if (conversation.is_online) {
                    $('#chatUserStatus').addClass('text-success');
                } else {
                    $('#chatUserStatus').addClass('text-danger');
                }
                $('#chatHeaderBar').show();
            }
            
            $('.chat-input').show();
            $('#chatInput').show();
            $('#messageInput,#sendButton,#voiceCallButton,#videoCallButton,#attachButton').prop('disabled',false);
            $('#typingIndicator').hide();
            if (socket && typeof socket.emit === 'function') {
                socket.emit('join_conversation',{conversation_id:id});
                // Also ensure user is in their own room for receiving calls
                socket.emit('join_user_room', { userId: currentUserId });
                console.log('Joined conversation room:', id, 'and user room:', currentUserId);
            }
            loadMessages(id);
            markMessagesAsRead(id);
        }
        
        // Enable input when no conversation is selected
        function enableInput() {
            $('#messageInput').prop('disabled', false);
            $('#sendButton').prop('disabled', false);
        }
        
        // ✅ Load tin nhắn
        function loadMessages(convId){
            $.getJSON(`src/controllers/chat-controller.php?action=get_messages&conversation_id=${convId}`, res=>{
                if(!res.success) {
                    console.error('Error loading messages:', res.error);
                    return;
                }
                let html='';
                if (res.messages && res.messages.length > 0) {
                    res.messages.forEach(m=>{
                        html+=createMessageHTML(m);
                    });
                    $('#chatMessages').html(html);
                    scrollToBottom();
                } else {
                    $('#chatMessages').html(`
                        <div class="welcome-screen">
                            <div class="welcome-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h4>Bắt đầu cuộc trò chuyện</h4>
                            <p>Gửi tin nhắn đầu tiên để bắt đầu!</p>
                        </div>
                    `);
                }
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error loading messages:', error);
                console.error('Response:', xhr.responseText);
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
            const messageId = m.id || m.message_id || '';
            return `<div class="message ${isSent?'sent':'received'}" ${messageId ? `data-message-id="${messageId}"` : ''}>
                <div class="message-content">
                    <div>${escapeHtml(m.message)}</div>
                    <div class="message-time">${time}${isSent?(m.IsRead?' <i class="fas fa-check-double text-primary"></i>':' <i class="fas fa-check text-muted"></i>'):''}</div>
                </div>
            </div>`;
        }
        
        // ✅ Thêm tin nhắn vào khung chat
        function addMessageToChat(msg,isSent){
            // Kiểm tra duplicate dựa trên message_id
            if (msg.id || msg.message_id) {
                const messageId = msg.id || msg.message_id;
                // Kiểm tra xem message đã tồn tại chưa
                if ($(`.message[data-message-id="${messageId}"]`).length > 0) {
                    console.log('Message already exists, skipping duplicate:', messageId);
                    return;
                }
            }
            
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
            const statusEl = $('#connectionStatus');
            const indicator = $('#connectionIndicator .status-dot');
            const textEl = $('#connectionText');
            
            // Update status dot
            indicator.removeClass('online offline connecting').addClass(status);
            
            // Update connection status container
            statusEl.removeClass('online offline connecting').addClass(status);
            
            // Update text
            if (textEl.length) {
                textEl.text(text || 'Đang kết nối...');
            }
            
            // Update tooltip
            indicator.attr('title', text || 'Đang kết nối...');
            statusEl.attr('title', text || 'Đang kết nối...');
            
            console.log('Connection status updated:', status, text);
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
            // Clear existing interval first to prevent duplicates
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
            
            // Only start if not connected
            autoRefreshInterval = setInterval(() => {
                if (!isConnected) {
                    loadConversations();
                }
            }, 30000);
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
        
        // Quản lý chọn nhân viên
        let allManagers = []; // Lưu danh sách tất cả managers để filter
        
        function showManagerSelection() {
            const modal = new bootstrap.Modal(document.getElementById('managerSelectionModal'));
            modal.show();
            loadAvailableManagers();
        }
        
        // Tải danh sách nhân viên đang online (chỉ role 1 và 3)
        function loadAvailableManagers() {
            $.get('src/controllers/chat-controller.php?action=get_available_managers', function(data) {
                if (data.success) {
                    // Lưu danh sách managers
                    allManagers = data.managers || [];
                    
                    if (allManagers.length > 0) {
                        // Áp dụng filter và hiển thị
                        applyFilters();
                    } else {
                        // Nếu không có manager nào online, tự động chuyển cho quản trị viên
                        console.log('Không có nhân viên nào online, tự động chuyển cho quản trị viên');
                        autoAssignToAdmin();
                    }
                } else {
                    // Nếu không load được, tự động chuyển cho quản trị viên
                    console.log('Không load được managers, tự động chuyển cho quản trị viên');
                    autoAssignToAdmin();
                }
            }, 'json').fail(function() {
                // Nếu có lỗi, tự động chuyển cho quản trị viên
                console.log('Lỗi load managers, tự động chuyển cho quản trị viên');
                autoAssignToAdmin();
            });
        }
        
        // Áp dụng filter theo role
        function applyFilters() {
            const role = $('#roleFilter').val();
            
            let filteredManagers = [...allManagers];
            
            // Lọc theo role (ID_Role)
            if (role) {
                filteredManagers = filteredManagers.filter(manager => {
                    return String(manager.ID_Role) === String(role);
                });
            }
            
            // Hiển thị danh sách đã lọc
            if (filteredManagers.length > 0) {
                displayManagers(filteredManagers);
            } else {
                // Nếu không có manager nào phù hợp với filter, tự động chuyển cho quản trị viên
                console.log('Không có nhân viên nào phù hợp với filter, tự động chuyển cho quản trị viên');
                autoAssignToAdmin();
            }
        }
        
        // Tự động chuyển cho quản trị viên (role 1)
        function autoAssignToAdmin() {
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('managerSelectionModal'));
            if (modal) {
                modal.hide();
            }
            
            // Tìm admin (role 1)
            $.get('src/controllers/chat-controller.php?action=get_admin_user', function(data) {
                if (data.success && data.admin_id) {
                    // Tạo conversation với admin
                    createConversationWithManager(data.admin_id);
                    showNotification('Không có nhân viên nào online. Bạn đã được chuyển đến Quản trị viên.', 'info');
                } else {
                    // Fallback: thử tạo conversation với admin ID = 1
                    createConversationWithManager(1);
                    showNotification('Không có nhân viên nào online. Bạn đã được chuyển đến Quản trị viên.', 'info');
                }
            }, 'json').fail(function() {
                // Fallback: thử tạo conversation với admin ID = 1
                createConversationWithManager(1);
                showNotification('Không có nhân viên nào online. Bạn đã được chuyển đến Quản trị viên.', 'info');
            });
        }
        
        // Hiển thị fallback khi không có nhân viên online (không dùng nữa, đã thay bằng autoAssignToAdmin)
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
        
        // Hiển thị danh sách managers
        function displayManagers(managers) {
            let html = '';
            
            // Hiển thị thống kê
            const totalCount = managers.length;
            
            html += `
                <div class="alert alert-info mb-3">
                    <i class="fas fa-users"></i>
                    <strong>${totalCount}</strong> nhân viên đang online
                </div>
            `;
            
            managers.forEach(manager => {
                html += `
                    <div class="card mb-3 manager-card border-success" data-manager-id="${manager.id}">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="card-title mb-1">
                                        <i class="fas fa-user-tie text-primary"></i>
                                        ${manager.name}
                                        <span class="badge bg-success ms-2">ONLINE</span>
                                    </h6>
                                    <p class="card-text text-muted mb-1">
                                        <i class="fas fa-envelope"></i> ${manager.email}
                                    </p>
                                    <p class="card-text text-muted mb-1">
                                        <i class="fas fa-user-tag"></i> ${manager.RoleName || 'Nhân viên'}
                                    </p>
                                    <span class="badge bg-success">
                                        <i class="fas fa-circle"></i> Đang online
                                    </span>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button class="btn btn-success btn-sm" 
                                            onclick="selectManager(${manager.id})"
                                            title="Chat với nhân viên này">
                                        <i class="fas fa-comments"></i> Chat ngay
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#managersList').html(html);
        }
        
        // Chọn manager để chat
        function selectManager(managerId) {
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('managerSelectionModal'));
            modal.hide();
            
            // Tạo conversation với manager được chọn
            createConversationWithManager(managerId);
        }
        
        // Tạo conversation với manager được chọn
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
        
        // Tạo conversation tự động (tự động phân bổ)
        function createAutoConversation() {
            // Đóng modal nếu đang mở
            const modal = bootstrap.Modal.getInstance(document.getElementById('managerSelectionModal'));
            if (modal) modal.hide();
            
            // Tự động chuyển cho quản trị viên nếu không có nhân viên online
            loadAvailableManagers();
        }
        
        function showNotification(message, type = 'info', icon = null) {
            let alertClass, notificationIcon;
            
            // Nếu icon được truyền vào, dùng icon đó, nếu không thì dùng default
            if (icon) {
                notificationIcon = icon;
            } else {
                switch(type) {
                    case 'success':
                        notificationIcon = 'fa-check-circle';
                        break;
                    case 'warning':
                        notificationIcon = 'fa-exclamation-triangle';
                        break;
                    case 'error':
                    case 'danger':
                        notificationIcon = 'fa-exclamation-circle';
                        break;
                    default:
                        notificationIcon = 'fa-info-circle';
                }
            }
            
            switch(type) {
                case 'success':
                    alertClass = 'alert-success';
                    break;
                case 'warning':
                    alertClass = 'alert-warning';
                    break;
                case 'error':
                case 'danger':
                    alertClass = 'alert-danger';
                    break;
                default:
                    alertClass = 'alert-info';
            }
            
            const notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert">
                    <i class="fas ${notificationIcon}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            
            $('body').prepend(notification);
            
            // Tự động ẩn sau 5 giây
            setTimeout(() => {
                notification.alert('close');
            }, 5000);
        }
        
        // Lọc managers theo role
        $(document).on('change', '#roleFilter', function() {
            console.log('Filter changed:', {
                role: $('#roleFilter').val()
            });
            
            // Áp dụng filter nếu managers đã được load
            if (allManagers.length > 0) {
                applyFilters();
            } else {
                // Reload managers nếu chưa load
                loadAvailableManagers();
            }
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
        
        // ==================== MEDIA FUNCTIONS ====================
        
        // Setup media events
        function setupMediaEvents() {
            // Xóa event listeners cũ trước khi attach mới (tránh duplicate)
            $('#fileInput').off('change');
            $(document).off('click', '#attachButton');
            
            // File input change
            $('#fileInput').on('change', function(e) {
                console.log('File input changed');
                const file = e.target.files[0];
                if (file) {
                    console.log('File selected:', file.name, file.type, file.size);
                    uploadFile(file);
                    // Reset file input sau khi upload để có thể chọn lại cùng file
                    $(this).val('');
                } else {
                    console.log('No file selected');
                }
            });
            
            // Attach button click
            $(document).on('click', '#attachButton', function() {
                console.log('Attach button clicked');
                if ($(this).prop('disabled')) {
                    console.log('Attach button is disabled');
                    return;
                }
                if (!currentConversationId) {
                    alert('Vui lòng chọn cuộc trò chuyện trước');
                    return;
                }
                
                // Trigger file input click
                $('#fileInput').click();
                console.log('File input clicked');
            });
            
            // Voice call button
            $(document).on('click', '#voiceCallButton', function() {
                if ($(this).prop('disabled')) {
                    return;
                }
                if (!currentConversationId) {
                    alert('Vui lòng chọn cuộc trò chuyện trước');
                    return;
                }
                initiateCall('voice');
            });
            
            // Video call button
            $(document).on('click', '#videoCallButton', function() {
                if ($(this).prop('disabled')) {
                    return;
                }
                if (!currentConversationId) {
                    alert('Vui lòng chọn cuộc trò chuyện trước');
                    return;
                }
                initiateCall('video');
            });
        }
        
        // Upload file
        function uploadFile(file) {
            if (!currentConversationId) {
                alert('Vui lòng chọn cuộc trò chuyện trước');
                return;
            }
            
            // Validate file size (10MB max)
            const maxSize = 10 * 1024 * 1024; // 10MB
            if (file.size > maxSize) {
                alert('File quá lớn. Tối đa 10MB');
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                                 'application/pdf', 'application/msword', 
                                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                 'text/plain', 'application/zip', 'application/x-rar-compressed'];
            if (!allowedTypes.includes(file.type)) {
                alert('Loại file không được hỗ trợ. Vui lòng chọn file hình ảnh, PDF, Word, hoặc text.');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('conversation_id', currentConversationId);
            
            // Show upload progress
            const progressHtml = `
                <div class="upload-progress">
                    <i class="fas fa-upload"></i>
                    <div>Đang upload ${file.name}...</div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="uploadProgress"></div>
                    </div>
                </div>
            `;
            $('#chatMessages').append(progressHtml);
            scrollToBottom();
            
            // Disable attach button during upload
            $('#attachButton').prop('disabled', true);
            
            $.ajax({
                url: 'src/controllers/media-upload.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 60000, // 60 seconds timeout
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = evt.loaded / evt.total * 100;
                            $('#uploadProgress').css('width', percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    $('.upload-progress').remove();
                    $('#attachButton').prop('disabled', false);
                    $('#fileInput').val(''); // Reset file input
                    
                    // Check if response is a string (JSON string)
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            alert('Lỗi xử lý phản hồi từ server');
                            return;
                        }
                    }
                    
                    if (response.success) {
                        addMessageToChat(response.message, true);
                        scrollToBottom();
                        
                        // Update conversation preview
                        updateConversationPreview(currentConversationId, response.message.message || '[File]');
                        
                        // Note: Không emit Socket.IO event ở đây vì message đã được broadcast từ server
                        // Nếu emit sẽ gây duplicate message (1 lần từ AJAX success, 1 lần từ Socket.IO event)
                        
                        // Refresh conversation list if not connected
                        if (!isConnected) {
                            setTimeout(function() {
                                loadConversations();
                            }, 500);
                        }
                    } else {
                        alert('Lỗi upload: ' + (response.error || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    $('.upload-progress').remove();
                    $('#attachButton').prop('disabled', false);
                    $('#fileInput').val(''); // Reset file input
                    
                    console.error('Upload error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    let errorMessage = 'Lỗi upload file';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Timeout - Upload mất quá nhiều thời gian';
                    } else if (status === 'parsererror') {
                        errorMessage = 'Lỗi phân tích phản hồi từ server';
                    } else if (xhr.status === 413) {
                        errorMessage = 'File quá lớn. Vui lòng chọn file nhỏ hơn';
                    } else if (xhr.status === 415) {
                        errorMessage = 'Loại file không được hỗ trợ';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Lỗi server nội bộ (500)';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Không tìm thấy file upload handler (404)';
                    } else if (xhr.responseText) {
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            errorMessage = errorResponse.error || errorMessage;
                        } catch (e) {
                            // Keep default error message
                        }
                    }
                    
                    alert(errorMessage);
                }
            });
        }
        
        // Enhanced message HTML creation for media
        function createMessageHTML(m) {
            const isSent = m.sender_id == currentUserId;
            const time = new Date(m.created_at).toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
            
            let messageContent = '';
            
            // Get base path from current location - Auto detect for both localhost and production
            const getBasePath = function() {
                const path = window.location.pathname;
                const hostname = window.location.hostname;
                
                // Production domain (sukien.info.vn)
                if (hostname.includes('sukien.info.vn') || hostname.includes('sukien')) {
                    // If at root, return empty or '/'
                    if (path === '/' || path.split('/').filter(p => p).length === 0) {
                        return '';
                    }
                    // Extract base path from current location
                    // e.g., /chat.php -> '' (root), /admin/chat.php -> '' (root)
                    const pathParts = path.split('/').filter(p => p);
                    if (pathParts.length > 0 && pathParts[0] !== 'chat.php' && pathParts[0] !== 'admin') {
                        // If there's a subdirectory, return it
                        return '/' + pathParts[0] + '/';
                    }
                    // Root domain
                    return '';
                }
                
                // Localhost development - try to detect my-php-project
                if (path.includes('/my-php-project/')) {
                    return path.substring(0, path.indexOf('/my-php-project/') + '/my-php-project/'.length);
                } else if (path.includes('/event/')) {
                    return path.substring(0, path.indexOf('/event/') + '/event/'.length) + 'my-php-project/';
                }
                
                // Default fallback - try to get from current path
                // If we're at /chat.php, assume root
                // If we're at /admin/chat.php, assume root
                const pathParts = path.split('/').filter(p => p && p !== 'chat.php' && p !== 'admin');
                if (pathParts.length > 0) {
                    // There's a subdirectory
                    return '/' + pathParts[0] + '/';
                }
                
                // Root
                return '';
            };
            const basePath = getBasePath();
            
            if (m.message_type === 'image') {
                // Fix file path - ensure correct path format
                let imagePath = m.file_path || '';
                
                // Normalize path - remove '../' and 'my-php-project/' prefix if present
                if (imagePath.startsWith('../')) {
                    imagePath = imagePath.substring(3);
                }
                if (imagePath.startsWith('my-php-project/')) {
                    imagePath = imagePath.substring(15);
                }
                
                // Check if path already contains base path (to avoid duplication)
                let pathAlreadyHasBase = false;
                if (basePath && basePath !== '') {
                    const basePathNoSlash = basePath.startsWith('/') ? basePath.substring(1) : basePath;
                    if (imagePath.includes(basePathNoSlash) || imagePath.startsWith('/' + basePathNoSlash)) {
                        pathAlreadyHasBase = true;
                    }
                }
                
                // Remove leading slash temporarily for processing
                const hadLeadingSlash = imagePath.startsWith('/');
                if (hadLeadingSlash) {
                    imagePath = imagePath.substring(1);
                }
                
                // Only add base path if not already present
                if (!imagePath.startsWith('http') && imagePath.length > 0) {
                    if (pathAlreadyHasBase) {
                        // Path already has base, just ensure leading slash
                        if (!imagePath.startsWith('/')) {
                            imagePath = '/' + imagePath;
                        }
                    } else {
                        // Add base path
                        if (basePath === '') {
                            if (!imagePath.startsWith('/')) {
                                imagePath = '/' + imagePath;
                            }
                        } else {
                            const base = basePath.endsWith('/') ? basePath : basePath + '/';
                            imagePath = base + imagePath;
                            if (!imagePath.startsWith('/')) {
                                imagePath = '/' + imagePath;
                            }
                        }
                    }
                }
                
                // Use thumbnail if available for display, but use original for preview
                let displayImagePath = imagePath;
                if (m.thumbnail_path && !imagePath.startsWith('http')) {
                    let thumbPath = m.thumbnail_path;
                    
                    // Normalize thumbnail path
                    if (thumbPath.startsWith('../')) {
                        thumbPath = thumbPath.substring(3);
                    }
                    if (thumbPath.startsWith('my-php-project/')) {
                        thumbPath = thumbPath.substring(15);
                    }
                    
                    // Check if thumbnail path already has base path
                    let thumbAlreadyHasBase = false;
                    if (basePath && basePath !== '') {
                        const basePathNoSlash = basePath.startsWith('/') ? basePath.substring(1) : basePath;
                        if (thumbPath.includes(basePathNoSlash) || thumbPath.startsWith('/' + basePathNoSlash)) {
                            thumbAlreadyHasBase = true;
                        }
                    }
                    
                    // Remove leading slash temporarily
                    const thumbHadLeadingSlash = thumbPath.startsWith('/');
                    if (thumbHadLeadingSlash) {
                        thumbPath = thumbPath.substring(1);
                    }
                    
                    // Add base path if not already present
                    if (!thumbPath.startsWith('http') && thumbPath.length > 0) {
                        if (thumbAlreadyHasBase) {
                            if (!thumbPath.startsWith('/')) {
                                thumbPath = '/' + thumbPath;
                            }
                        } else {
                            if (basePath === '') {
                                if (!thumbPath.startsWith('/')) {
                                    thumbPath = '/' + thumbPath;
                                }
                            } else {
                                const base = basePath.endsWith('/') ? basePath : basePath + '/';
                                thumbPath = base + thumbPath;
                                if (!thumbPath.startsWith('/')) {
                                    thumbPath = '/' + thumbPath;
                                }
                            }
                        }
                    }
                    // Use thumbnail for display (faster loading)
                    displayImagePath = thumbPath;
                }
                
                messageContent = `
                    <div class="media-message">
                        <img src="${displayImagePath}" alt="Image" onclick="previewImage('${imagePath}')" 
                             data-full-image="${imagePath}"
                             style="max-width: 300px; max-height: 300px; width: auto; height: auto; border-radius: 10px; cursor: pointer; transition: transform 0.3s ease; display: block; object-fit: contain;"
                             onmouseover="this.style.transform='scale(1.02)'"
                             onmouseout="this.style.transform='scale(1)'">
                        <div class="message-time">${time}${isSent?(m.IsRead?' <i class="fas fa-check-double text-primary"></i>':' <i class="fas fa-check text-muted"></i>'):''}</div>
                    </div>
                `;
            } else if (m.message_type === 'file') {
                messageContent = `
                    <div class="media-message">
                        <div class="file-info">
                            <div class="file-name">${m.file_name}</div>
                            <div class="file-size">${formatFileSize(m.file_size)}</div>
                        </div>
                        <div class="message-time">${time}${isSent?(m.IsRead?' <i class="fas fa-check-double text-primary"></i>':' <i class="fas fa-check text-muted"></i>'):''}</div>
                    </div>
                `;
            } else if (m.message_type === 'voice_call' || m.message_type === 'video_call') {
                const callType = m.message_type === 'video_call' ? 'Video Call' : 'Voice Call';
                const callIcon = m.message_type === 'video_call' ? 'fa-video' : 'fa-phone';
                messageContent = `
                    <div class="media-message">
                        <div class="file-info" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 8px; font-size: 0.9rem;">
                            <i class="fas ${callIcon}" style="color: #667eea; font-size: 1rem;"></i>
                            <span style="color: #333; font-weight: 500;">${callType}</span>
                        </div>
                        <div class="message-time" style="margin-top: 0.25rem;">${time}${isSent?(m.IsRead?' <i class="fas fa-check-double text-primary"></i>':' <i class="fas fa-check text-muted"></i>'):''}</div>
                    </div>
                `;
            } else {
                messageContent = `
                    <div>${escapeHtml(m.message)}</div>
                    <div class="message-time">${time}${isSent?(m.IsRead?' <i class="fas fa-check-double text-primary"></i>':' <i class="fas fa-check text-muted"></i>'):''}</div>
                `;
            }
            
            const messageId = m.id || m.message_id || '';
            return `<div class="message ${isSent?'sent':'received'}" ${messageId ? `data-message-id="${messageId}"` : ''}>
                <div class="message-content">
                    ${messageContent}
                </div>
            </div>`;
        }
        
        // Preview image
        function previewImage(imagePath) {
            console.log('Preview image called with path:', imagePath);
            
            // Fix image path - Auto detect base path
            const getBasePath = function() {
                const path = window.location.pathname;
                const hostname = window.location.hostname;
                
                // Production domain
                if (hostname.includes('sukien.info.vn') || hostname.includes('sukien')) {
                    const pathParts = path.split('/').filter(p => p);
                    if (pathParts.length > 0 && pathParts[0] !== 'chat.php' && pathParts[0] !== 'admin') {
                        return '/' + pathParts[0] + '/';
                    }
                    return '';
                }
                
                // Localhost
                if (path.includes('/my-php-project/')) {
                    return path.substring(0, path.indexOf('/my-php-project/') + '/my-php-project/'.length);
                } else if (path.includes('/event/')) {
                    return path.substring(0, path.indexOf('/event/') + '/event/'.length) + 'my-php-project/';
                }
                
                const pathParts = path.split('/').filter(p => p && p !== 'chat.php' && p !== 'admin');
                if (pathParts.length > 0) {
                    return '/' + pathParts[0] + '/';
                }
                return '';
            };
            const basePath = getBasePath();
            console.log('Base path detected:', basePath);
            
            let fixedPath = imagePath;
            
            // Handle absolute URL
            if (fixedPath.startsWith('http://') || fixedPath.startsWith('https://')) {
                // Already absolute URL, use as is
                console.log('Using absolute URL:', fixedPath);
            } else {
                // Normalize path - remove '../' and 'my-php-project/' prefix if present
                if (fixedPath.startsWith('../')) {
                    fixedPath = fixedPath.substring(3);
                }
                if (fixedPath.startsWith('my-php-project/')) {
                    fixedPath = fixedPath.substring(15);
                }
                
                // Check if path already contains base path (to avoid duplication)
                let pathAlreadyHasBase = false;
                if (basePath && basePath !== '') {
                    // Remove leading slash from basePath for comparison
                    const basePathNoSlash = basePath.startsWith('/') ? basePath.substring(1) : basePath;
                    // Check if fixedPath already contains base path
                    if (fixedPath.includes(basePathNoSlash) || fixedPath.startsWith('/' + basePathNoSlash)) {
                        pathAlreadyHasBase = true;
                        console.log('Path already contains base path, skipping addition');
                    }
                }
                
                // Remove leading slash temporarily for processing
                const hadLeadingSlash = fixedPath.startsWith('/');
                if (hadLeadingSlash) {
                    fixedPath = fixedPath.substring(1);
                }
                
                // Only add base path if not already present
                if (fixedPath.length > 0) {
                    if (pathAlreadyHasBase) {
                        // Path already has base, just ensure leading slash
                        if (!fixedPath.startsWith('/')) {
                            fixedPath = '/' + fixedPath;
                        }
                    } else {
                        // Add base path
                        if (basePath === '') {
                            if (!fixedPath.startsWith('/')) {
                                fixedPath = '/' + fixedPath;
                            }
                        } else {
                            const base = basePath.endsWith('/') ? basePath : basePath + '/';
                            fixedPath = base + fixedPath;
                            // Ensure leading slash
                            if (!fixedPath.startsWith('/')) {
                                fixedPath = '/' + fixedPath;
                            }
                        }
                    }
                }
                console.log('Fixed path:', fixedPath);
            }
            
            // Set image src and show modal
            const $previewImg = $('#previewImage');
            if ($previewImg.length === 0) {
                console.error('Preview image element not found!');
                alert('Không tìm thấy modal preview hình ảnh');
                return;
            }
            
            // Set src with error handling
            $previewImg.attr('src', fixedPath);
            $previewImg.on('error', function() {
                console.error('Image failed to load:', fixedPath);
                $(this).attr('src', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Lb0BuZyB0aGkgdGkgxrDhu6NhbmggaGluaDwvdGV4dD48L3N2Zz4=');
                $(this).after('<div class="text-danger mt-2">Không thể tải hình ảnh. Đường dẫn: ' + fixedPath + '</div>');
            });
            
            // Show modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('imagePreviewModal'));
            if (modal) {
                modal.show();
            } else {
                const newModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
                newModal.show();
            }
            
            console.log('Modal shown with image path:', fixedPath);
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // ==================== CALL FUNCTIONS ====================
        
        // Initiate call
        function initiateCall(callType) {
            if (!currentConversationId) {
                alert('Vui lòng chọn cuộc trò chuyện trước khi gọi');
                return;
            }
            
            $.post('src/controllers/call-controller.php?action=initiate_call', {
                conversation_id: currentConversationId,
                call_type: callType
            }, function(response) {
                if (response.success) {
                    currentCall = {
                        id: response.call_id,
                        type: response.call_type,
                        receiver_id: response.receiver_id,
                        receiver_name: response.receiver_name,
                        status: response.status
                    };
                    
                    showCallModal('outgoing', response.receiver_name, callType);
                    
                    // Emit call event via socket
                    if (isConnected && socket && typeof socket.emit === 'function') {
                        const callData = {
                            call_id: response.call_id,
                            caller_id: currentUserId,
                            receiver_id: response.receiver_id,
                            call_type: callType,
                            conversation_id: currentConversationId
                        };
                        console.log('📞 Emitting call_initiated event:', callData);
                        socket.emit('call_initiated', callData);
                    } else {
                        console.warn('⚠️ Socket not connected, cannot emit call event');
                    }
                } else {
                    alert('Lỗi khởi tạo cuộc gọi: ' + response.error);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Call initiation error:', error);
                console.error('Response:', xhr.responseText);
                alert('Lỗi kết nối khi khởi tạo cuộc gọi: ' + error);
            });
        }
        
        // Show call modal
        function showCallModal(type, name, callType) {
            console.log('📞 showCallModal called:', { type, name, callType });
            
            $('#callerName').text(name);
            $('#callType').text(callType === 'video' ? 'Cuộc gọi video' : 'Cuộc gọi thoại');
            
            if (type === 'incoming') {
                $('#callStatus').text('Cuộc gọi đến...');
                // Clear existing buttons first
                $('#callControls').empty();
                // Add both accept and reject buttons
                $('#callControls').html(`
                    <button class="call-btn accept" onclick="acceptCall()" style="background: linear-gradient(135deg, #28a745, #20c997); width: 60px; height: 60px; border-radius: 50%; border: none; color: white; font-size: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="call-btn reject" onclick="rejectCall()" style="background: linear-gradient(135deg, #dc3545, #c82333); width: 60px; height: 60px; border-radius: 50%; border: none; color: white; font-size: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                `);
                console.log('✅ Incoming call - Added accept and reject buttons');
            } else {
                $('#callStatus').text('Đang gọi...');
                $('#callControls').html(`
                    <button class="call-btn end" id="endCallBtn" style="background: linear-gradient(135deg, #dc3545, #c82333); width: 60px; height: 60px; border-radius: 50%; border: none; color: white; font-size: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                `);
                
                // Attach event listener to end call button
                $('#endCallBtn').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📞 End call button clicked (outgoing)');
                    endCall();
                });
                
                console.log('📤 Outgoing call - Added end button only');
            }
            
            // Ensure modal is visible
            $('#callModal').addClass('show').css('display', 'flex');
            console.log('✅ Call modal shown with type:', type);
            
            // Debug: Check if buttons are in DOM
            setTimeout(() => {
                const acceptBtn = $('#callControls .call-btn.accept');
                const rejectBtn = $('#callControls .call-btn.reject');
                const modalVisible = $('#callModal').hasClass('show');
                const modalDisplay = $('#callModal').css('display');
                
                console.log('🔍 Button check:', {
                    acceptBtn: acceptBtn.length,
                    rejectBtn: rejectBtn.length,
                    acceptBtnVisible: acceptBtn.is(':visible'),
                    rejectBtnVisible: rejectBtn.is(':visible'),
                    callControlsHTML: $('#callControls').html(),
                    modalVisible: modalVisible,
                    modalDisplay: modalDisplay
                });
                
                // Force show modal and buttons if not visible
                if (!modalVisible || modalDisplay === 'none') {
                    console.warn('⚠️ Modal not visible, forcing show');
                    $('#callModal').addClass('show').css('display', 'flex');
                }
                
                // Force show buttons if not visible
                if (type === 'incoming') {
                    if (acceptBtn.length > 0 && !acceptBtn.is(':visible')) {
                        acceptBtn.css('display', 'flex');
                    }
                    if (rejectBtn.length > 0 && !rejectBtn.is(':visible')) {
                        rejectBtn.css('display', 'flex');
                    }
                }
            }, 100);
        }
        
        // Accept call
        function acceptCall() {
            if (!currentCall) {
                console.error('No current call to accept');
                return;
            }
            
            $.post('src/controllers/call-controller.php?action=accept_call', {
                call_id: currentCall.id
            }, function(response) {
                if (response.success) {
                    if (currentCall.type === 'video') {
                        // For video call, hide modal and show video container
                        $('#callModal').removeClass('show');
                        startVideoCall();
                    } else {
                        // For voice call, keep modal visible and show active call UI
                        // Don't remove 'show' class - just update the UI
                        console.log('📞 Accepting voice call, keeping modal visible');
                        startVoiceCall();
                    }
                    
                    // Emit accept event
                    if (isConnected && socket && typeof socket.emit === 'function') {
                        socket.emit('call_accepted', {
                            call_id: currentCall.id,
                            caller_id: currentCall.caller_id || currentCall.receiver_id,
                            receiver_id: currentUserId
                        });
                    }
                } else {
                    alert('Lỗi chấp nhận cuộc gọi: ' + response.error);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Accept call error:', error);
                alert('Lỗi khi chấp nhận cuộc gọi: ' + error);
            });
        }
        
        // Reject call
        function rejectCall() {
            if (!currentCall) {
                console.error('No current call to reject');
                $('#callModal').removeClass('show');
                return;
            }
            
            const callId = currentCall.id;
            const callerId = currentCall.caller_id || currentCall.receiver_id;
            
            $.post('src/controllers/call-controller.php?action=reject_call', {
                call_id: callId
            }, function(response) {
                $('#callModal').removeClass('show');
                currentCall = null;
                
                // Emit reject event
                if (isConnected && socket && typeof socket.emit === 'function') {
                    socket.emit('call_rejected', {
                        call_id: callId,
                        caller_id: callerId,
                        receiver_id: currentUserId
                    });
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Reject call error:', error);
                $('#callModal').removeClass('show');
                currentCall = null;
            });
        }
        
        // End call
        function endCall() {
            console.log('📞 End call function called');
            console.log('📞 Current call:', currentCall);
            console.log('📞 Local stream:', localStream);
            console.log('📞 Remote stream:', remoteStream);
            console.log('📞 Peer connection:', peerConnection);
            
            // Hide all call UIs immediately
            $('#callModal').removeClass('show');
            $('#videoCallContainer').removeClass('show');
            
            // Stop local stream
            if (localStream) {
                try {
                    localStream.getTracks().forEach(track => {
                        track.stop();
                        console.log('📞 Stopped local track:', track.kind);
                    });
                    localStream = null;
                    console.log('✅ Local stream stopped');
                } catch (e) {
                    console.error('Error stopping local stream:', e);
                }
            }
            
            // Stop remote stream
            if (remoteStream) {
                try {
                    remoteStream.getTracks().forEach(track => {
                        track.stop();
                        console.log('📞 Stopped remote track:', track.kind);
                    });
                    remoteStream = null;
                    console.log('✅ Remote stream stopped');
                } catch (e) {
                    console.error('Error stopping remote stream:', e);
                }
            }
            
            // Close peer connection
            if (peerConnection) {
                try {
                    peerConnection.close();
                    peerConnection = null;
                    console.log('✅ Peer connection closed');
                } catch (e) {
                    console.error('Error closing peer connection:', e);
                }
            }
            
            // If no currentCall, just cleanup and return
            if (!currentCall) {
                console.log('⚠️ No currentCall, cleanup done');
                currentCall = null;
                return;
            }
            
            const callId = currentCall.id;
            console.log('📞 Ending call with ID:', callId);
            
            // Call backend to end call
            $.post('src/controllers/call-controller.php?action=end_call', {
                call_id: callId
            }, function(response) {
                console.log('📞 End call response:', response);
                
                // Hide UIs again (in case they were shown)
                $('#callModal').removeClass('show');
                $('#videoCallContainer').removeClass('show');
                
                // Stop all streams again (in case they weren't stopped)
                if (localStream) {
                    localStream.getTracks().forEach(track => track.stop());
                    localStream = null;
                }
                
                if (remoteStream) {
                    remoteStream.getTracks().forEach(track => track.stop());
                    remoteStream = null;
                }
                
                // Close peer connection again
                if (peerConnection) {
                    peerConnection.close();
                    peerConnection = null;
                }
                
                // Emit end event before clearing currentCall
                if (isConnected && socket && typeof socket.emit === 'function') {
                    socket.emit('call_ended', {
                        call_id: callId,
                        caller_id: currentUserId
                    });
                    console.log('✅ Call ended event emitted');
                }
                
                currentCall = null;
                console.log('✅ Call ended successfully');
            }, 'json').fail(function(xhr, status, error) {
                console.error('❌ End call error:', error);
                console.error('Response:', xhr.responseText);
                
                // Cleanup anyway even if backend call fails
                $('#callModal').removeClass('show');
                $('#videoCallContainer').removeClass('show');
                
                if (localStream) {
                    localStream.getTracks().forEach(track => track.stop());
                    localStream = null;
                }
                
                if (remoteStream) {
                    remoteStream.getTracks().forEach(track => track.stop());
                    remoteStream = null;
                }
                
                if (peerConnection) {
                    peerConnection.close();
                    peerConnection = null;
                }
                
                currentCall = null;
                console.log('✅ Cleanup done despite error');
            });
        }
        
        // Make endCall globally accessible
        window.endCall = endCall;
        
        // Start video call
        function startVideoCall() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Trình duyệt của bạn không hỗ trợ video call. Vui lòng sử dụng trình duyệt khác.');
                return;
            }
            
            $('#videoCallContainer').addClass('show');
            
            navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(stream => {
                    localStream = stream;
                    const localVideo = document.getElementById('localVideo');
                    if (localVideo) {
                        localVideo.srcObject = stream;
                    }
                    
                    // Initialize WebRTC peer connection
                    initializePeerConnection();
                })
                .catch(error => {
                    console.error('Error accessing media devices:', error);
                    $('#videoCallContainer').removeClass('show');
                    let errorMessage = 'Không thể truy cập camera/microphone';
                    if (error.name === 'NotAllowedError') {
                        errorMessage = 'Vui lòng cho phép truy cập camera và microphone';
                    } else if (error.name === 'NotFoundError') {
                        errorMessage = 'Không tìm thấy camera/microphone';
                    }
                    alert(errorMessage);
                });
        }
        
        // Start voice call
        function startVoiceCall() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Trình duyệt của bạn không hỗ trợ voice call. Vui lòng sử dụng trình duyệt khác.');
                return;
            }
            
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    localStream = stream;
                    initializePeerConnection();
                    
                    // Show voice call UI with end call button
                    showVoiceCallUI();
                    
                    // Show voice call indicator
                    showNotification('Cuộc gọi thoại đã bắt đầu', 'success');
                })
                .catch(error => {
                    console.error('Error accessing microphone:', error);
                    let errorMessage = 'Không thể truy cập microphone';
                    if (error.name === 'NotAllowedError') {
                        errorMessage = 'Vui lòng cho phép truy cập microphone';
                    } else if (error.name === 'NotFoundError') {
                        errorMessage = 'Không tìm thấy microphone';
                    }
                    alert(errorMessage);
                });
        }
        
        // Show voice call UI
        function showVoiceCallUI() {
            console.log('📞 showVoiceCallUI called');
            
            // Get caller/receiver name
            const conversation = conversations.find(c => c.id == currentConversationId);
            const otherUserName = conversation ? conversation.other_user_name : 'Người gọi';
            
            console.log('📞 Other user name:', otherUserName);
            
            // Update call modal to show active call state
            $('#callerName').text(otherUserName);
            $('#callType').text('Cuộc gọi thoại');
            $('#callStatus').text('Đang gọi...');
            
            // Clear existing buttons first
            $('#callControls').empty();
            
            // Show end call button only with inline styles to ensure visibility
            $('#callControls').html(`
                <button class="call-btn end" id="endCallBtn" style="background: linear-gradient(135deg, #dc3545, #c82333); width: 60px; height: 60px; border-radius: 50%; border: none; color: white; font-size: 1.5rem; cursor: pointer; display: flex !important; align-items: center; justify-content: center; margin: 0 auto;">
                    <i class="fas fa-phone-slash"></i>
                </button>
            `);
            
            // Attach event listener to end call button
            $('#endCallBtn').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('📞 End call button clicked');
                endCall();
            });
            
            // Ensure modal is visible
            $('#callModal').addClass('show');
            console.log('✅ Voice call UI shown with end call button');
            
            // Debug: Check if button is in DOM after a short delay
            setTimeout(() => {
                const endBtn = $('#callControls .call-btn.end');
                const modalVisible = $('#callModal').hasClass('show');
                console.log('🔍 End call button check:', {
                    endBtnExists: endBtn.length,
                    endBtnVisible: endBtn.is(':visible'),
                    modalVisible: modalVisible,
                    callControlsHTML: $('#callControls').html(),
                    modalDisplay: $('#callModal').css('display')
                });
                
                // Force show if not visible
                if (!modalVisible || $('#callModal').css('display') === 'none') {
                    console.warn('⚠️ Modal not visible, forcing show');
                    $('#callModal').addClass('show').css('display', 'flex');
                }
                
                // Force button visibility if not visible
                if (endBtn.length > 0 && !endBtn.is(':visible')) {
                    console.warn('⚠️ End button not visible, forcing display');
                    endBtn.css({
                        'display': 'flex !important',
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                }
            }, 100);
        }
        
        // Initialize WebRTC peer connection
        function initializePeerConnection() {
            const configuration = {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' }
                ]
            };
            
            peerConnection = new RTCPeerConnection(configuration);
            
            // Add local stream to peer connection
            if (localStream) {
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                });
            }
            
            // Handle remote stream
            peerConnection.ontrack = event => {
                remoteStream = event.streams[0];
                document.getElementById('remoteVideo').srcObject = remoteStream;
            };
            
            // Handle ICE candidates
            peerConnection.onicecandidate = event => {
                if (event.candidate) {
                    // Send ICE candidate to remote peer via socket
                    if (isConnected && socket) {
                        socket.emit('ice_candidate', {
                            call_id: currentCall.id,
                            candidate: event.candidate
                        });
                    }
                }
            };
        }
        
        // Toggle mute
        function toggleMute() {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                if (audioTrack) {
                    audioTrack.enabled = !audioTrack.enabled;
                    isMuted = !audioTrack.enabled;
                    
                    const icon = $('#muteBtn i');
                    if (isMuted) {
                        icon.removeClass('fa-microphone').addClass('fa-microphone-slash');
                    } else {
                        icon.removeClass('fa-microphone-slash').addClass('fa-microphone');
                    }
                }
            }
        }
        
        // Toggle camera
        function toggleCamera() {
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                if (videoTrack) {
                    videoTrack.enabled = !videoTrack.enabled;
                    isCameraOff = !videoTrack.enabled;
                    
                    const icon = $('#cameraBtn i');
                    if (isCameraOff) {
                        icon.removeClass('fa-video').addClass('fa-video-slash');
                    } else {
                        icon.removeClass('fa-video-slash').addClass('fa-video');
                    }
                }
            }
        }
        
        // End video call
        function endVideoCall() {
            endCall();
        }
        
        // Socket events for calls
        function setupCallSocketEvents() {
            // Prevent duplicate event listeners
            if (socket._callEventsSetup) {
                console.log('⚠️ Call socket events already setup, skipping...');
                return;
            }
            
            if (socket && typeof socket.on === 'function') {
                // Mark as setup to prevent duplicates
                socket._callEventsSetup = true;
                // Incoming call
                socket.on('call_initiated', data => {
                    console.log('Received call_initiated event:', data);
                    console.log('Checking receiver_id:', data.receiver_id, 'vs currentUserId:', currentUserId);
                    console.log('Type comparison:', typeof data.receiver_id, typeof currentUserId);
                    
                    // Use == instead of === to handle string/number mismatch
                    if (data.receiver_id == currentUserId || String(data.receiver_id) === String(currentUserId)) {
                        console.log('✅ Call is for this user, showing modal');
                        currentCall = {
                            id: data.call_id,
                            type: data.call_type,
                            caller_id: data.caller_id,
                            receiver_id: currentUserId,
                            conversation_id: data.conversation_id,
                            status: 'ringing'
                        };
                        
                        // Lấy tên người gọi từ conversation
                        const conversation = conversations.find(c => c.id == data.conversation_id);
                        const callerName = conversation ? conversation.other_user_name : 'Người gọi';
                        
                        console.log('📞 Showing call modal for:', callerName);
                        console.log('📞 Call type:', data.call_type);
                        
                        // Show modal with accept/reject buttons
                        showCallModal('incoming', callerName, data.call_type);
                        
                        // Force show modal if it doesn't show
                        setTimeout(() => {
                            const modalElement = document.getElementById('callModal');
                            if (modalElement) {
                                const modalVisible = $('#callModal').hasClass('show');
                                const modalDisplay = $('#callModal').css('display');
                                
                                console.log('🔍 Modal check:', {
                                    modalVisible: modalVisible,
                                    modalDisplay: modalDisplay,
                                    modalElement: modalElement
                                });
                                
                                if (!modalVisible || modalDisplay === 'none') {
                                    console.warn('⚠️ Modal not visible, forcing show');
                                    $('#callModal').addClass('show').css('display', 'flex');
                                }
                            }
                        }, 100);
                    } else {
                        console.log('❌ Call is not for this user, ignoring');
                        console.log('❌ Receiver ID:', data.receiver_id, 'Current User ID:', currentUserId);
                    }
                });
                
                // Call accepted
                socket.on('call_accepted', data => {
                    console.log('Received call_accepted event:', data);
                    if (data.caller_id === currentUserId && currentCall) {
                        $('#callModal').removeClass('show');
                        
                        if (currentCall.type === 'video') {
                            startVideoCall();
                        } else {
                            startVoiceCall();
                        }
                    }
                });
                
                // Call rejected
                socket.on('call_rejected', data => {
                    console.log('Received call_rejected event:', data);
                    if (data.caller_id === currentUserId) {
                        $('#callModal').removeClass('show');
                        currentCall = null;
                        showNotification(data.message || 'Cuộc gọi bị từ chối', 'warning', 'fa-times-circle');
                    }
                });
                
                // Call ended
                socket.on('call_ended', data => {
                    console.log('Received call_ended event:', data);
                    $('#callModal').removeClass('show');
                    $('#videoCallContainer').removeClass('show');
                    
                    if (localStream) {
                        localStream.getTracks().forEach(track => track.stop());
                        localStream = null;
                    }
                    
                    // ✅ Hiển thị thông báo
                    if (data.message) {
                        showNotification(data.message, 'info');
                    }
                    
                    currentCall = null;
                });
                
                // ✅ Call busy - Receiver đang trong cuộc gọi khác
                socket.on('call_busy', data => {
                    console.log('Received call_busy event:', data);
                    $('#callModal').removeClass('show');
                    currentCall = null;
                    
                    showNotification(data.message || `${data.receiver_name} đang bận trong cuộc gọi khác`, 'warning');
                });
                
                // ✅ Call timeout - Cuộc gọi không được trả lời
                socket.on('call_timeout', data => {
                    console.log('Received call_timeout event:', data);
                    $('#callModal').removeClass('show');
                    currentCall = null;
                    
                    showNotification(data.message || 'Cuộc gọi không được trả lời sau 30 giây', 'warning');
                });
                
                // ✅ Call notification - Các thông báo khác về cuộc gọi
                socket.on('call_notification', data => {
                    console.log('Received call_notification event:', data);
                    
                    let notificationType = 'info';
                    let icon = 'fa-info-circle';
                    
                    switch(data.type) {
                        case 'calling':
                            notificationType = 'info';
                            icon = 'fa-phone';
                            break;
                        case 'call_active':
                            notificationType = 'success';
                            icon = 'fa-check-circle';
                            break;
                        case 'call_rejected':
                            notificationType = 'warning';
                            icon = 'fa-times-circle';
                            break;
                        case 'call_ended':
                            notificationType = 'info';
                            icon = 'fa-phone-slash';
                            break;
                        case 'missed_call_busy':
                            notificationType = 'warning';
                            icon = 'fa-exclamation-triangle';
                            break;
                        case 'cannot_call':
                            notificationType = 'danger';
                            icon = 'fa-ban';
                            break;
                        default:
                            notificationType = 'info';
                            icon = 'fa-info-circle';
                    }
                    
                    showNotification(data.message || 'Thông báo cuộc gọi', notificationType, icon);
                });
            } else {
                console.warn('Socket not available for call events');
            }
        }
        
        // Note: setupCallSocketEvents() is now called in socket.on('connect')
        // to ensure socket is connected before setting up event listeners
    </script>

</body>
</html>