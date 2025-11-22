<?php
session_start();
require_once __DIR__ . '/src/auth/auth.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Lấy vai trò người dùng
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? 0;

// Cho phép admin (1), quản lý sự kiện (3), và khách hàng (5) sử dụng chat
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
    <link rel="icon" href="img/logo/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ✅ Thiết kế lại trang chat - Giao diện hiện đại đồng bộ với index.php */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        
        .chat-container {
            background: #ffffff;
            margin: 0;
            overflow: hidden;
            width: 100%;
            height: 100vh;
            border: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.1);
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            padding: 1rem 1.5rem;
            position: relative;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header-icon {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .header-icon:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }
        
        .header-icon i {
            font-size: 1.3rem;
            color: white;
        }
        
        .header-content h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
            color: white;
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
            position: relative;
            z-index: 1;
            padding: 0.5rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            min-width: 40px;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .connection-status.online {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.3), rgba(40, 167, 69, 0.2));
        }
        
        .connection-status.offline {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.3), rgba(220, 53, 69, 0.2));
        }
        
        .connection-status.connecting {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.3), rgba(255, 193, 7, 0.2));
        }
        
        .connection-text {
            font-size: 0.85rem;
            color: white;
            font-weight: 500;
            white-space: nowrap;
            display: none; /* Ẩn text, chỉ hiển thị icon */
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
            background: rgba(255, 255, 255, 0.6);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }
        
        .btn-home:hover {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
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
            height: calc(100vh - 80px);
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f4f8 100%);
            position: relative;
        }
        
        .chat-sidebar {
            width: 320px;
            background: #ffffff;
            border-right: 1px solid rgba(102, 126, 234, 0.2);
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }
        
        .sidebar-header {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid rgba(102, 126, 234, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        .sidebar-header h6 {
            margin: 0;
            font-weight: 700;
            color: #333;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-header h6 i {
            color: #667eea;
        }
        
        .btn-new-chat {
            width: 38px;
            height: 38px;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .btn-new-chat:hover {
            background: linear-gradient(135deg, #5a6fe0 0%, #8a4dc5 100%);
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem;
        }
        
        /* Custom scrollbar cho sidebar */
        .sidebar-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar-content::-webkit-scrollbar-track {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
        }
        
        .sidebar-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        
        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fe0 0%, #8a4dc5 100%);
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
            width: 40px;
            height: 40px;
            border: 4px solid rgba(102, 126, 234, 0.2);
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-state {
            color: #6c757d;
        }
        
        .loading-state p {
            margin-top: 1rem;
            font-weight: 500;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header-bar {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-bottom: 1px solid rgba(102, 126, 234, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .chat-user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar-small {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 1rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.8);
        }
        
        .user-details h6 {
            margin: 0;
            font-weight: 700;
            color: #333;
            font-size: 1rem;
        }
        
        .user-details small {
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            position: relative;
        }
        
        /* Custom scrollbar cho chat messages */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }
        
        .chat-messages::-webkit-scrollbar-track {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
        }
        
        .chat-messages::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        
        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fe0 0%, #8a4dc5 100%);
        }
        
        .message {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            animation: fadeInMessage 0.3s ease;
        }
        
        @keyframes fadeInMessage {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message.received {
            justify-content: flex-start;
        }
        
        .message-content {
            max-width: 70%;
            padding: 0.85rem 1.2rem;
            border-radius: 20px;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .message-content:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        
        .message.sent .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 6px;
        }
        
        .message.received .message-content {
            background: #ffffff;
            color: #333;
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-bottom-left-radius: 6px;
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
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-top: 1px solid rgba(102, 126, 234, 0.2);
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .chat-input-group {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        
        .chat-input input {
            flex: 1;
            border: 2px solid rgba(197, 217, 240, 0.5);
            border-radius: 25px;
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #ffffff;
        }
        
        .chat-input input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .chat-input button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            color: white;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .chat-input button#sendButton {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .chat-input button#voiceCallButton {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }
        
        .chat-input button#videoCallButton {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .chat-input button#attachButton {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }
        
        .chat-input button:hover:not(:disabled) {
            transform: scale(1.1) translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .chat-input button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(197, 217, 240, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: #ffffff;
            border-radius: 12px;
            margin: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid transparent;
        }
        
        .conversation-item:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f4f8 100%);
            transform: translateX(5px);
            border-color: rgba(102, 126, 234, 0.3);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .conversation-item.active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-left: 4px solid #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        
        .conversation-user {
            font-weight: 700;
            color: #333;
            margin-bottom: 0.4rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .conversation-preview {
            font-size: 0.85rem;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.4rem;
            line-height: 1.4;
        }
        
        .conversation-time {
            font-size: 0.75rem;
            color: #adb5bd;
            font-weight: 500;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
            position: relative;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.8);
        }
        
        .status-online {
            background: #28a745;
            animation: pulse-online 2s infinite;
        }
        
        @keyframes pulse-online {
            0%, 100% {
                box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.8), 0 0 0 4px rgba(40, 167, 69, 0.3);
            }
            50% {
                box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.8), 0 0 0 6px rgba(40, 167, 69, 0.1);
            }
        }
        
        .status-offline {
            background: #6c757d;
        }
        
        .customer-search {
            padding: 1rem;
            border-bottom: 1px solid rgba(197, 217, 240, 0.3);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        .customer-search .input-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .customer-search input {
            flex: 1;
            border: 1px solid rgba(197, 217, 240, 0.5);
            border-radius: 25px;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: #ffffff;
        }
        
        .customer-search input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .customer-search button {
            border-radius: 25px;
            padding: 0.6rem 1rem;
            border: 1px solid rgba(102, 126, 234, 0.3);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: all 0.3s ease;
        }
        
        .customer-search button:hover {
            background: linear-gradient(135deg, #5a6fe0 0%, #8a4dc5 100%);
            transform: scale(1.05);
        }
        
        .typing-indicator {
            display: none;
            padding: 0.75rem 1.25rem;
            color: #6c757d;
            font-style: italic;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            margin: 0.5rem 1rem;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .typing-indicator.show {
            display: block;
            animation: fadeInTyping 0.3s ease;
        }
        
        @keyframes fadeInTyping {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .welcome-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 2rem;
        }
        
        .welcome-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        .welcome-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .welcome-screen h4 {
            color: #333;
            margin-bottom: 0.75rem;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .welcome-screen p {
            color: #6c757d;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .welcome-info {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            border: 1px solid rgba(197, 217, 240, 0.3);
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }
        
        .info-item i {
            color: #667eea;
            font-size: 1rem;
        }
        
        /* Online status styles */
        .manager-card.border-success {
            border-left: 4px solid #28a745 !important;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.08) 0%, rgba(40, 167, 69, 0.03) 100%);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);
        }
        
        .manager-card.border-secondary {
            border-left: 4px solid #6c757d !important;
            background: linear-gradient(135deg, rgba(108, 117, 125, 0.08) 0%, rgba(108, 117, 125, 0.03) 100%);
            border-radius: 12px;
        }
        
        .manager-card.border-danger {
            border-left: 4px solid #dc3545 !important;
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.08) 0%, rgba(220, 53, 69, 0.03) 100%);
            border-radius: 12px;
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
            background: #6c757d;
        }
        
        .notification-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        /* Removed user-info styles - không sử dụng nữa */
        
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
            border-radius: 15px;
            cursor: pointer;
            transition: transform 0.3s ease;
            display: block;
            object-fit: contain;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .media-message img:hover {
            transform: scale(1.03);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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
            flex-direction: column;
        }
        
        .call-modal.show {
            display: flex !important;
        }
        
        .call-container {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 25px;
            padding: 2.5rem;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.2);
            margin: auto;
            position: relative;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }
        
        .call-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3.5rem;
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            animation: pulse-avatar 2s ease-in-out infinite;
        }
        
        @keyframes pulse-avatar {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            }
        }
        
        .call-info h3 {
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .call-info p {
            color: #6c757d;
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        
        .call-controls {
            display: flex;
            justify-content: center;
            gap: 1.25rem;
        }
        
        .call-btn {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            border: none;
            font-size: 1.6rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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
            transform: scale(1.15) translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        
        .call-status {
            margin: 1rem 0;
            font-weight: 700;
            color: #667eea;
            font-size: 1.1rem;
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
            .chat-header {
                padding: 0.75rem 1rem;
            }
            
            .header-content h1 {
                font-size: 1.25rem;
            }
            
            .header-icon {
                width: 38px;
                height: 38px;
                margin-right: 0.5rem;
            }
            
            .header-icon i {
                font-size: 1.1rem;
            }
            
            .btn-home {
                width: 38px;
                height: 38px;
            }
            
            .chat-content {
                flex-direction: column;
                height: calc(100vh - 70px);
            }
            
            .chat-sidebar {
                width: 100%;
                height: 200px;
            }
            
            .sidebar-header {
                padding: 0.75rem;
            }
            
            .welcome-info {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .chat-main {
                height: calc(100vh - 300px);
            }
            
            .conversation-item {
                margin: 0.25rem;
                padding: 0.75rem;
            }
            
            .chat-input {
                padding: 0.75rem 1rem;
            }
            
            .chat-input button {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100vh; margin: 0; padding: 0;">
            <!-- Phần đầu trang -->
            <div class="chat-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="header-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="header-content">
                            <h1>Chat Hỗ trợ</h1>
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
                    <div class="customer-search">
                        <div class="input-group">
                            <input type="text" class="form-control" id="conversationSearch" placeholder="Tìm kiếm cuộc trò chuyện...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
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
                        <input type="file" id="fileInput" accept="image/*,video/*,.pdf,.doc,.docx,.txt,.zip,.rar" multiple style="display: none;">
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
    
    <!-- Audio element cho voice call (ẩn) -->
    <audio id="remoteAudio" autoplay playsinline style="display: none;" volume="1.0"></audio>

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
    <!-- Stringee SDK - Load từ LOCAL trước, sau đó fallback về CDN -->
    <script>
    (function() {
        // ✅ Đường dẫn local SDK (ưu tiên cao nhất)
        const localSDKPath = '<?php echo BASE_PATH; ?>/assets/Stringee/StringeeWebSDK_2.9.0/latest.sdk.bundle.min.js';
        
        // ✅ Danh sách URL để thử (theo thứ tự ưu tiên: Local → CDN)
        const stringeeUrls = [
            localSDKPath,                                                      // ✅ LOCAL SDK (ưu tiên nhất)
            'https://cdn.stringee.com/sdk/web/latest/stringee-web-sdk.min.js', // ✅ CDN URL chính xác (từ Stringee)
            'https://cdn.stringee.com/sdk/web/stringee-web-sdk.min.js',        // CDN URL không có /latest/
            'https://cdn.stringee.com/sdk/web/latest/stringee.js',              // CDN URL cũ
            'https://cdn.stringee.com/sdk/web/stringee.js'                     // CDN URL cũ không có /latest/
        ];
        
        // ✅ Hàm load SDK với URL cụ thể
        function loadStringeeSDK(urlIndex) {
            if (urlIndex >= stringeeUrls.length) {
                console.error('❌ Tất cả URL Stringee SDK đều fail (bao gồm cả local)');
                alert('Không thể tải Stringee SDK. Vui lòng:\n' +
                      '1. Kiểm tra file SDK local có tồn tại không\n' +
                      '2. Kiểm tra kết nối mạng\n' +
                      '3. Liên hệ admin để được hỗ trợ');
                return;
            }
            
            const url = stringeeUrls[urlIndex];
            const isLocal = urlIndex === 0; // URL đầu tiên là local
            console.log(`🔄 ${isLocal ? '📁 LOCAL' : '🌐 CDN'}: Attempting to load Stringee SDK from: ${url} (attempt ${urlIndex + 1}/${stringeeUrls.length})`);
            
            const script = document.createElement('script');
            script.src = url;
            script.async = true;
            script.defer = false;
            
            script.onload = function() {
                // Đợi một chút để SDK khởi tạo xong
                setTimeout(() => {
                    if (typeof StringeeClient !== 'undefined') {
                        window.stringeeSDKLoaded = true;
                        console.log(`✅ Stringee SDK loaded successfully from: ${isLocal ? '📁 LOCAL' : '🌐 CDN'} ${url}`);
                        console.log('✅ StringeeClient is now available:', typeof StringeeClient);
                    } else {
                        console.error(`❌ SDK loaded from ${url} but StringeeClient is undefined`);
                        // Thử URL tiếp theo
                        loadStringeeSDK(urlIndex + 1);
                    }
                }, 500); // Đợi 500ms để SDK khởi tạo
            };
            
            script.onerror = function() {
                console.error(`❌ Failed to load Stringee SDK from: ${url}`);
                // Thử URL tiếp theo
                loadStringeeSDK(urlIndex + 1);
            };
            
            // Thêm vào head
            document.head.appendChild(script);
        }
        
        // ✅ Bắt đầu load từ LOCAL SDK (ưu tiên nhất)
        loadStringeeSDK(0);
    })();
    </script>
    <!-- Stringee Helper Functions -->
    <script src="<?php echo BASE_PATH; ?>/assets/js/stringee-helper.js"></script>
    <!-- Socket.IO - Sử dụng CDN cho production, local server cho development -->
    <script>
    // ✅ Global flag để biết Socket.IO đã load chưa
    window.socketIOLoaded = false;
    window.socketIOReadyCallbacks = [];
    
    // ✅ Hàm để đăng ký callback khi Socket.IO sẵn sàng
    function onSocketIOReady(callback) {
        if (window.socketIOLoaded && typeof io !== 'undefined') {
            // Socket.IO đã load, gọi callback ngay
            callback();
        } else {
            // Chưa load, thêm vào queue
            window.socketIOReadyCallbacks.push(callback);
        }
    }
    
    // ✅ Hàm để trigger tất cả callbacks khi Socket.IO đã load
    function triggerSocketIOReady() {
        window.socketIOLoaded = true;
        console.log('✅ Socket.IO is ready, triggering callbacks...');
        window.socketIOReadyCallbacks.forEach(callback => {
            try {
                callback();
            } catch (e) {
                console.error('Error in Socket.IO ready callback:', e);
            }
        });
        window.socketIOReadyCallbacks = [];
    }
    
    // Tải Socket.IO client
    (function() {
        const hostname = window.location.hostname;
        const isProduction = hostname.includes('sukien.info.vn') || hostname.includes('sukien');
        
        // Cho production, sử dụng CDN trực tiếp (ổn định hơn trên cPanel)
        // Cho localhost, thử local server trước, sau đó fallback về CDN
        let socketScript = document.createElement('script');
        
        if (isProduction) {
            // Production: Sử dụng CDN trực tiếp
            socketScript.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
            socketScript.onload = function() {
                console.log('✅ Socket.IO loaded from CDN (production)');
                if (typeof io !== 'undefined') {
                    triggerSocketIOReady();
                } else {
                    // Đợi thêm một chút nếu io chưa sẵn sàng
                    setTimeout(() => {
                        if (typeof io !== 'undefined') {
                            triggerSocketIOReady();
                        } else {
                            console.error('❌ Socket.IO script loaded but io is undefined');
                        }
                    }, 100);
                }
            };
            socketScript.onerror = function() {
                console.error('❌ Failed to load Socket.IO from CDN');
            };
        } else {
            // Development: Thử local server trước
            socketScript.src = 'http://localhost:3000/socket.io/socket.io.js';
            socketScript.onerror = function() {
                console.warn('⚠️ Local Socket.IO server not available, using CDN fallback');
                const cdnScript = document.createElement('script');
                cdnScript.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
                cdnScript.onload = function() {
                    console.log('✅ Socket.IO loaded from CDN (fallback)');
                    if (typeof io !== 'undefined') {
                        triggerSocketIOReady();
                    } else {
                        setTimeout(() => {
                            if (typeof io !== 'undefined') {
                                triggerSocketIOReady();
                            } else {
                                console.error('❌ Socket.IO script loaded but io is undefined');
                            }
                        }, 100);
                    }
                };
                cdnScript.onerror = function() {
                    console.error('❌ Failed to load Socket.IO from both server and CDN');
                };
                document.head.appendChild(cdnScript);
            };
            socketScript.onload = function() {
                console.log('✅ Socket.IO loaded from local server');
                if (typeof io !== 'undefined') {
                    triggerSocketIOReady();
                } else {
                    setTimeout(() => {
                        if (typeof io !== 'undefined') {
                            triggerSocketIOReady();
                        } else {
                            console.error('❌ Socket.IO script loaded but io is undefined');
                        }
                    }, 100);
                }
            };
        }
        
        document.head.appendChild(socketScript);
    })();
    </script>
    <script>
        // Hàm helper để tự động phát hiện đường dẫn API đúng
        function getApiPath(relativePath) {
            const path = window.location.pathname;
            const hostname = window.location.hostname;
            
            // Domain production (sukien.info.vn) - không có my-php-project
            if (hostname.includes('sukien.info.vn') || hostname.includes('sukien')) {
                return '/' + relativePath;
            }
            
            // Localhost development - giữ nguyên để test local
            if (path.includes('/my-php-project/')) {
                return '/my-php-project/' + relativePath;
            } else if (path.includes('/event/')) {
                return '/event/my-php-project/' + relativePath;
            }
            
            // Fallback: đường dẫn tương đối
            return '../' + relativePath;
        }
        
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
        
        // Biến cho Media và Call (Stringee)
        let currentCall = null;
        let isMuted = false;
        let isCameraOff = false;
        
        // ID của interval cho polling/auto-refresh (để tránh tạo nhiều interval)
        let autoRefreshInterval = null;
        
        // ✅ Flag để tránh gọi initSocket() nhiều lần cùng lúc
        let isInitializingSocket = false;
        
        // ✅ Khởi tạo chat
        $(document).ready(() => {
            // Thiết lập trạng thái kết nối ban đầu
            updateConnectionStatus('connecting', 'Đang kết nối...');
            
            // ✅ QUAN TRỌNG: Đợi Socket.IO load xong rồi mới khởi tạo socket
            onSocketIOReady(function() {
                console.log('🚀 Socket.IO is ready, initializing socket connection...');
                
                // Khởi tạo socket ngay khi Socket.IO đã sẵn sàng
            initSocket();
            });
            
            // ✅ Fallback: Nếu Socket.IO đã load trước khi $(document).ready() chạy
            // Kiểm tra lại sau 100ms để đảm bảo không bỏ sót
            setTimeout(function() {
                if (typeof io !== 'undefined' && !socket && window.socketIOLoaded) {
                    console.log('🚀 Socket.IO already loaded, initializing socket connection (fallback)...');
                    initSocket();
                }
            }, 100);
            
            // ✅ Fallback timeout: Nếu Socket.IO không load trong 5 giây, thử initSocket() anyway
            // (có thể Socket.IO đã load nhưng callback chưa chạy)
            setTimeout(function() {
                if (typeof io !== 'undefined' && !socket) {
                    console.log('🚀 Socket.IO detected after timeout, initializing socket connection...');
                    initSocket();
                } else if (typeof io === 'undefined') {
                    console.warn('⚠️ Socket.IO not loaded after 5 seconds, chat will work in offline mode');
                    updateConnectionStatus('offline', 'Chế độ offline - Socket.IO chưa tải');
                }
            }, 5000);
            
            // Các hàm khác (không phụ thuộc vào socket)
            setUserOnline(); // Đặt người dùng online
            loadConversations();
            setupChatEvents();
            setupMediaEvents();
            // ✅ setupCallSocketEvents() sẽ được gọi trong socket.on('connect')
            // để đảm bảo socket đã kết nối trước khi thiết lập event listeners
            setupQuickReplies(); // Thiết lập nút trả lời nhanh
            setupConversationSearch(); // Thiết lập chức năng tìm kiếm
            startAutoRefresh();
            
            // ✅ Setup Stringee event handlers khi page load
            const checkStringeeHelper = setInterval(function() {
                if (window.StringeeHelper) {
                    clearInterval(checkStringeeHelper);
                    setupStringeeEventHandlers();
                    console.log('✅ Stringee event handlers setup completed');
                }
            }, 100);
            
            setTimeout(function() {
                clearInterval(checkStringeeHelper);
                if (!window.StringeeHelper) {
                    console.warn('⚠️ StringeeHelper chưa được load sau 5 giây');
                }
            }, 5000);
            
            // QUAN TRỌNG: Thêm interval để kiểm tra và reconnect nếu cần
            // Kiểm tra mỗi 10 giây xem socket có đang connected không
            setInterval(() => {
                if (typeof io !== 'undefined' && (!socket || !socket.connected)) {
                    console.log('🔄 Socket not connected, attempting to reconnect...');
                    if (socket) {
                        // Nếu socket tồn tại nhưng không connected, thử reconnect
                        if (socket.disconnected) {
                            socket.connect();
                        } else {
                            // Nếu socket không tồn tại, khởi tạo lại
                            initSocket();
                        }
                    } else {
                        // Nếu socket chưa tồn tại, khởi tạo
                        initSocket();
                    }
                }
            }, 10000); // Kiểm tra mỗi 10 giây
            
            // Đặt người dùng offline khi đóng trang
            $(window).on('beforeunload', function() {
                setUserOffline();
            });
        });
        
        // ✅ Thiết lập nút trả lời nhanh
        function setupQuickReplies() {
            $(document).on('click', '.quick-reply', function(e) {
                e.preventDefault();
                const message = $(this).data('message');
                if (message && currentConversationId) {
                    $('#messageInput').val(message);
                    sendMessage();
                } else if (message && !currentConversationId) {
                    alert('Vui lòng chọn hoặc tạo cuộc trò chuyện trước');
                }
            });
        }
        
        // ✅ Thiết lập tìm kiếm cuộc trò chuyện
        function setupConversationSearch() {
            $('#conversationSearch').on('input', function() {
                const searchTerm = $(this).val().toLowerCase().trim();
                if (searchTerm === '') {
                    displayConversations();
                    return;
                }
                
                const filtered = conversations.filter(conv => {
                    const name = (conv.other_user_name || '').toLowerCase();
                    const preview = (conv.last_message || '').toLowerCase();
                    return name.includes(searchTerm) || preview.includes(searchTerm);
                });
                
                if (filtered.length === 0) {
                    $('#conversationsList').html('<p class="text-center text-muted mt-3" style="font-size: 0.85rem;">Không tìm thấy cuộc trò chuyện nào</p>');
                } else {
                    let html = '';
                    filtered.forEach(conv => {
                        // Xử lý thời gian với kiểm tra hợp lệ
                        let time = '--:--';
                        try {
                            if (conv.updated_at) {
                                const date = new Date(conv.updated_at);
                                if (!isNaN(date.getTime())) {
                                    time = date.toLocaleTimeString('vi-VN', {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });
                                } else {
                                    time = new Date().toLocaleTimeString('vi-VN', {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });
                                }
                            } else {
                                time = new Date().toLocaleTimeString('vi-VN', {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                            }
                        } catch (e) {
                            time = new Date().toLocaleTimeString('vi-VN', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        }
                        const isOnline = conv.is_online === true || conv.is_online === 1 || conv.is_online === '1';
                        html += `
                        <div class="conversation-item" data-id="${conv.id}" onclick="selectConversation(${conv.id})">
                            <div class="conversation-user">
                                <span><span class="status-indicator ${isOnline ? 'status-online' : 'status-offline'}" title="${isOnline ? 'Đang online' : 'Đang offline'}"></span>${conv.other_user_name || 'Người dùng'}</span>
                                ${conv.unread_count > 0 ? `<span class="badge bg-danger rounded-pill" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">${conv.unread_count}</span>` : ''}
                            </div>
                            <div class="conversation-preview">${conv.last_message || 'Chưa có tin nhắn'}</div>
                            <div class="conversation-time">${time}</div>
                        </div>`;
                    });
                    $('#conversationsList').html(html);
                }
            });
        }
        
        // ✅ Kết nối Socket.IO - Tự động reconnect liên tục như admin/chat.php
        function initSocket() {
            // ✅ Tránh gọi nhiều lần cùng lúc
            if (isInitializingSocket) {
                console.log('📡 Socket initialization already in progress, skipping...');
                return;
            }
            
            console.log('🚀 Initializing Socket.IO...');
            
            // Kiểm tra Socket.IO có sẵn không
            if (typeof io === 'undefined') {
                console.warn('⚠️ Socket.IO not loaded, chat will work without real-time features');
                isConnected = false;
                updateConnectionStatus('offline', 'Chế độ offline - Không có kết nối real-time');
                return;
            }
            
            console.log('✅ Socket.IO available, creating connection...');
            
            // QUAN TRỌNG: Nếu socket đã tồn tại và đang connected, không tạo lại
            if (socket && socket.connected) {
                console.log('📡 Socket already connected, skipping re-init');
                return;
            }
            
            // ✅ Đặt flag để tránh gọi lại
            isInitializingSocket = true;
            
            // QUAN TRỌNG: Nếu socket đã tồn tại nhưng disconnected, đóng nó trước khi tạo mới
            if (socket && !socket.connected) {
                console.log('📡 Closing existing disconnected socket before re-init');
                socket.removeAllListeners();
                socket.disconnect();
                socket = null;
            }
            
        // Phát hiện môi trường và thiết lập URL server Socket.IO
        // ✅ FIX: Dùng base URL với mount point, path là relative
        const getSocketServerURL = function() {
            // Hybrid: WebSocket chạy trên VPS riêng (ws.sukien.info.vn)
            // PHP chạy trên shared hosting (sukien.info.vn)
            if (window.location.hostname.includes('sukien.info.vn')) {
                // ✅ QUAN TRỌNG: Dùng wss:// (secure WebSocket) cho production
                // Nếu server Socket.IO hỗ trợ HTTPS, dùng wss://, nếu không dùng ws://
                const protocol = window.location.protocol;
                // Nếu trang web dùng HTTPS, dùng wss:// cho WebSocket
                if (protocol === 'https:') {
                    return 'wss://ws.sukien.info.vn';  // Secure WebSocket
                } else {
                    return 'ws://ws.sukien.info.vn';   // Non-secure WebSocket (chỉ cho development)
                }
            }
            
            // Localhost development
            return 'http://localhost:3000';
        };
        
        const socketServerURL = getSocketServerURL();
        console.log('📡 Connecting to Socket.IO server:', socketServerURL);
        
        // Lấy SOCKET_PATH cho path option
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
        
        // Kiểm tra Socket.IO library đã được tải chưa
        if (typeof io === 'undefined') {
            console.error('❌ Socket.IO library not loaded!');
            updateConnectionStatus('offline', 'Socket.IO library chưa được tải');
            return;
        }
        
        // Tạo kết nối Socket.IO với xử lý lỗi cải thiện
        try {
            // Xác thực biến trước khi tạo kết nối
            if (!socketServerURL) {
                throw new Error('socketServerURL is not defined');
            }
            if (!socketPath) {
                throw new Error('socketPath is not defined');
            }
            
            // QUAN TRỌNG: Tạo socket mới với cấu hình reconnect tự động
            socket = io(socketServerURL, {
                path: socketPath,
                transports: ['polling', 'websocket'], // Thử polling trước, sau đó websocket
                reconnection: true, // Bật tự động reconnect
                reconnectionAttempts: Infinity, // Tiếp tục thử kết nối lại vô hạn
                reconnectionDelay: 1000, // Delay 1 giây trước khi thử lại
                reconnectionDelayMax: 10000, // Delay tối đa 10 giây
                timeout: 20000,
                forceNew: false, // Không force tạo connection mới nếu đã có
                autoConnect: true, // Tự động kết nối ngay khi tạo
                // Thêm query parameters để debug
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
            // ✅ Reset flag khi có lỗi
            isInitializingSocket = false;
            return;
        }

        if (socket && typeof socket.on === 'function') {
            socket.on('connect', () => {
                console.log('✅ Socket.IO connected successfully');
                isConnected = true;
                // ✅ Reset flag khi đã connect thành công
                isInitializingSocket = false;
                updateConnectionStatus('online', 'Đã kết nối realtime');
                
                // Xác thực ngay khi kết nối
                socket.emit('authenticate', {
                    userId: currentUserId,
                    userRole: currentUserRole,
                    userName: currentUserName
                });
                
                // Đảm bảo người dùng ở trong room của mình để nhận cuộc gọi
                socket.emit('join_user_room', { userId: currentUserId });
                console.log('Socket connected, joined user room:', currentUserId);
                
                // Tham gia lại conversation hiện tại nếu có
                if (currentConversationId) {
                    socket.emit('join_conversation', { conversation_id: currentConversationId });
                    console.log('Rejoined conversation:', currentConversationId);
                }
                
                // ✅ Thiết lập call socket events SAU KHI socket đã kết nối
                setupCallSocketEvents();
            });
            
            socket.on('connect_error', (error) => {
                console.error('❌ Socket.IO connection error:', error);
                console.error('Error type:', error.type);
                console.error('Error message:', error.message);
                console.error('Error description:', error.description);
                
                isConnected = false;
                // ✅ Reset flag sau một khoảng thời gian để có thể retry
                // (Socket.IO sẽ tự động retry, nhưng nếu retry quá nhiều lần thì reset flag)
                setTimeout(() => {
                    isInitializingSocket = false;
                }, 2000);
                
                // Hiển thị connecting thay vì offline để người dùng biết đang thử kết nối lại
                updateConnectionStatus('connecting', 'Đang kết nối...');
                
                // Socket.IO sẽ tự động retry với cấu hình reconnection: true
                // Không cần thêm logic retry ở đây
            });
            
            socket.on('disconnect', (reason) => {
                console.warn('⚠️ Socket.IO disconnected:', reason);
                isConnected = false;
                
                // Chỉ hiển thị offline nếu không phải là reconnect attempt
                if (reason !== 'io server disconnect' && reason !== 'transport close') {
                    updateConnectionStatus('offline', 'Đã ngắt kết nối');
                } else {
                    // Nếu là transport close, có thể đang reconnect
                    updateConnectionStatus('connecting', 'Đang kết nối lại...');
                }
            });
            
            socket.on('reconnect_attempt', (attemptNumber) => {
                console.log('🔄 Attempting to reconnect... (attempt', attemptNumber, ')');
                updateConnectionStatus('connecting', 'Đang kết nối lại... (' + attemptNumber + ')');
            });
            
            socket.on('reconnect', (attemptNumber) => {
                console.log('🔄 Socket.IO reconnected after', attemptNumber, 'attempts');
                isConnected = true;
                updateConnectionStatus('online', 'Đã kết nối realtime');
                
                // QUAN TRỌNG: Re-authenticate và rejoin rooms sau khi reconnect
                socket.emit('authenticate', { 
                    userId: currentUserId, 
                    userRole: currentUserRole, 
                    userName: currentUserName 
                });
                socket.emit('join_user_room', { userId: currentUserId });
                console.log('✅ Reconnected, re-authenticated and re-joined user room:', currentUserId);
                
                // Tham gia lại conversation hiện tại nếu có
                if (currentConversationId) {
                    socket.emit('join_conversation', { conversation_id: currentConversationId });
                    console.log('✅ Rejoined conversation:', currentConversationId);
                }
                
                // ✅ Re-setup call socket events after reconnect nếu chưa setup
                if (!socket._callEventsSetup) {
                    setupCallSocketEvents();
                }
            });
            
            socket.on('reconnect_failed', () => {
                console.error('❌ Socket.IO reconnection failed - will retry automatically');
                isConnected = false;
                updateConnectionStatus('connecting', 'Đang thử kết nối lại...');
                
                // QUAN TRỌNG: Socket.IO với reconnectionAttempts: Infinity sẽ tự động retry
                // Nhưng nếu reconnect_failed được gọi, có thể cần khởi tạo lại socket
                // Đợi 5 giây rồi thử lại nếu vẫn chưa connected
                setTimeout(() => {
                    if (!isConnected && (!socket || !socket.connected)) {
                        console.log('🔄 Reconnect failed, reinitializing socket...');
                        // Đóng socket cũ và tạo lại
                        if (socket) {
                            socket.removeAllListeners();
                            socket.disconnect();
                            socket = null;
                        }
                        // Khởi tạo lại socket sau 2 giây
                        setTimeout(() => {
                            initSocket();
                        }, 2000);
                    }
                }, 5000);
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

            // Xử lý tin nhắn broadcast
            socket.on('broadcast_message', data => {
                console.log('Received broadcast message:', data);
                if (data.conversation_id === currentConversationId && data.userId !== currentUserId) {
                    addMessageToChat(data.message, false);
                    scrollToBottom();
                }
            });

            // Xử lý trạng thái đã đọc tin nhắn
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
        
        // ✅ Đặt người dùng online
        function setUserOnline() {
            $.ajax({
                url: getApiPath('src/controllers/chat-controller.php?action=set_user_online'),
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
                    console.error('API URL:', getApiPath('src/controllers/chat-controller.php?action=set_user_online'));
                }
            });
        }
        
        // ✅ Đặt người dùng offline
        function setUserOffline() {
            $.ajax({
                url: getApiPath('src/controllers/chat-controller.php?action=set_user_offline'),
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
                    console.error('API URL:', getApiPath('src/controllers/chat-controller.php?action=set_user_offline'));
                }
            });
        }
        
        // ✅ Hiển thị danh sách hội thoại
        function loadConversations() {
            $.getJSON(getApiPath('src/controllers/chat-controller.php?action=get_conversations'), res => {
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
                        // Xử lý thời gian với kiểm tra hợp lệ
                        let time = '--:--';
                        try {
                            if (c.updated_at) {
                                const date = new Date(c.updated_at);
                                if (!isNaN(date.getTime())) {
                                    time = date.toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
                                } else {
                                    time = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
                                }
                            } else {
                                time = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
                            }
                        } catch (e) {
                            time = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
                        }
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
        
        // Hiển thị lỗi cuộc trò chuyện
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
                
                // Bật input để tạo cuộc trò chuyện mới
                enableInput();
        }
        
        // Đánh dấu tin nhắn đã đọc
        function markMessagesAsRead(conversationId) {
            if (!conversationId) return;
            
            const apiUrl = getApiPath('src/controllers/chat-controller.php?action=mark_as_read');
            
            $.post(apiUrl, {
                conversation_id: conversationId
            }, function(data) {
                if (data.success) {
                    console.log('Messages marked as read');
                    // Tải lại cuộc trò chuyện để cập nhật số tin nhắn chưa đọc
                    loadConversations();
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Error marking messages as read:', error);
                console.error('API URL:', apiUrl);
                console.error('Response:', xhr.responseText);
                console.error('Status:', xhr.status);
            });
        }
        
        // Hiển thị cuộc trò chuyện
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
                
                // Bật input để tạo cuộc trò chuyện mới
                enableInput();
                return;
            }
            
            let html = '';
            conversations.forEach(conv => {
                // Xử lý thời gian với kiểm tra hợp lệ
                let time = '--:--';
                try {
                    if (conv.updated_at) {
                        const date = new Date(conv.updated_at);
                        if (!isNaN(date.getTime())) {
                            time = date.toLocaleTimeString('vi-VN', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        } else {
                            time = new Date().toLocaleTimeString('vi-VN', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        }
                    } else {
                        time = new Date().toLocaleTimeString('vi-VN', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    }
                } catch (e) {
                    time = new Date().toLocaleTimeString('vi-VN', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
                
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
                // Đảm bảo người dùng ở trong room của mình để nhận cuộc gọi
                socket.emit('join_user_room', { userId: currentUserId });
                console.log('Joined conversation room:', id, 'and user room:', currentUserId);
            }
            loadMessages(id);
            markMessagesAsRead(id);
        }
        
        // Bật input khi chưa chọn cuộc trò chuyện
        function enableInput() {
            $('#messageInput').prop('disabled', false);
            $('#sendButton').prop('disabled', false);
        }
        
        // ✅ Load tin nhắn
        function loadMessages(convId){
            const apiUrl = getApiPath(`src/controllers/chat-controller.php?action=get_messages&conversation_id=${convId}`);
            
            $.getJSON(apiUrl, res=>{
                if(!res || !res.success) {
                    console.error('AJAX Error loading messages:', res ? res.error : 'Response is undefined');
                    console.error('Response:', res);
                    console.error('API URL:', apiUrl);
                    $('#chatMessages').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Lỗi tải tin nhắn: ${res ? res.error : 'Không nhận được phản hồi từ server'}
                        </div>
                    `);
                    return;
                }
                let html='';
                if (res.messages && res.messages.length > 0) {
                    res.messages.forEach(m=>{
                        // Bỏ qua tin nhắn rỗng hoặc chỉ có khoảng trắng
                        const messageText = (m.message || m.text || '').trim();
                        if (messageText || m.message_type) { // Chỉ hiển thị nếu có nội dung hoặc là media/file
                            html+=createMessageHTML(m);
                        }
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
        
        // Hiển thị lỗi tin nhắn
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
        
        // Hiển thị tin nhắn
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
                    // Bỏ qua tin nhắn rỗng hoặc chỉ có khoảng trắng
                    const messageText = (message.message || message.text || '').trim();
                    if (messageText || message.message_type) { // Chỉ hiển thị nếu có nội dung hoặc là media/file
                        html += createMessageHTML(message);
                    }
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
            
            // Kiểm tra tin nhắn có nội dung không (bỏ qua tin nhắn rỗng)
            const messageText = (m.message || m.text || '').trim();
            if (!messageText && !m.message_type) {
                // Tin nhắn rỗng và không phải media/file - không hiển thị
                console.warn('Skipping empty message:', m);
                return '';
            }
            
            // Xử lý thời gian với kiểm tra hợp lệ
            let time = '--:--';
            try {
                if (m.created_at) {
                    const date = new Date(m.created_at);
                    if (!isNaN(date.getTime())) {
                        time = date.toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
                    } else {
                        console.warn('Invalid date:', m.created_at);
                        // Fallback về thời gian hiện tại nếu date không hợp lệ
                        time = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
                    }
                } else {
                    // Dùng thời gian hiện tại nếu không có created_at
                    time = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
                }
            } catch (e) {
                console.warn('Date parsing error:', e, 'for date:', m.created_at);
                // Fallback về thời gian hiện tại
                time = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
            }
            
            const messageId = m.id || m.message_id || '';
            const displayText = messageText || (m.message_type ? 'Tin nhắn đa phương tiện' : 'Tin nhắn trống');
            
            return `<div class="message ${isSent?'sent':'received'}" ${messageId ? `data-message-id="${messageId}"` : ''}>
                <div class="message-content">
                    <div>${escapeHtml(displayText)}</div>
                    <div class="message-time">${time}${isSent?(m.IsRead?' <i class="fas fa-check-double text-primary"></i>':' <i class="fas fa-check text-muted"></i>'):''}</div>
                </div>
            </div>`;
        }
        
        // ✅ Thêm tin nhắn vào khung chat
        function addMessageToChat(msg,isSent){
            // Kiểm tra tin nhắn có nội dung không (bỏ qua tin nhắn rỗng)
            const messageText = (msg.message || msg.text || '').trim();
            if (!messageText && !msg.message_type && !msg.file_path) {
                console.warn('Skipping empty message in addMessageToChat:', msg);
                return;
            }
            
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
            if (html) { // Chỉ append nếu có HTML (không phải chuỗi rỗng)
                $('#chatMessages').append(html);
            }
        }
        
        // ✅ Thiết lập sự kiện chat
        function setupChatEvents() {
            // Nút màn hình chào mừng
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
            
            // Hiển thị trạng thái loading
            const sendButton = $('#sendButton');
            const originalText = sendButton.html();
            sendButton.html('<i class="fas fa-spinner fa-spin"></i>');
            sendButton.prop('disabled', true);
            
            $.ajax({
                url: getApiPath('src/controllers/chat-controller.php?action=send_message'),
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
                        
                        // Thêm tin nhắn ngay lập tức để phản hồi tức thì
                        addMessageToChat(res.message, true);
                        scrollToBottom();
                        
                        // Phát sự kiện real-time
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
                        
                        // Cập nhật preview cuộc trò chuyện
                        updateConversationPreview(currentConversationId, res.message.message || res.message.text);
                        
                        // Làm mới danh sách cuộc trò chuyện nếu chưa kết nối
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
                    // Khôi phục trạng thái nút
                    sendButton.html(originalText);
                    sendButton.prop('disabled', false);
                }
            });
        }
        
        // Tạo cuộc trò chuyện mới
        function createNewConversation() {
            console.log('Creating new conversation...');
            
            // Hiển thị trạng thái loading
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo...';
            button.disabled = true;
            
            $.ajax({
                url: getApiPath('src/controllers/chat-controller.php?action=create_conversation'),
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
                    
                    // Bật input
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
                // Khôi phục trạng thái nút
                button.innerHTML = originalText;
                button.disabled = false;
                }
            });
        }
        
        // ✅ Cập nhật trạng thái kết nối - Chỉ hiển thị nút xanh/đỏ (icon)
        function updateConnectionStatus(status, text) {
            const statusEl = $('#connectionStatus');
            const indicator = $('#connectionIndicator .status-dot');
            const textEl = $('#connectionText');
            
            // Cập nhật status dot
            indicator.removeClass('online offline connecting').addClass(status);
            
            // Cập nhật container trạng thái kết nối
            statusEl.removeClass('online offline connecting').addClass(status);
            
            // Ẩn text, chỉ hiển thị icon (nút xanh/đỏ)
            if (textEl.length) {
                textEl.hide(); // Ẩn text
            }
            
            // Cập nhật tooltip với text đầy đủ
            const tooltipText = text || (status === 'online' ? 'Đã kết nối realtime' : status === 'offline' ? 'Chế độ offline' : 'Đang kết nối...');
            indicator.attr('title', tooltipText);
            statusEl.attr('title', tooltipText);
            
            console.log('Connection status updated:', status, text);
        }
        
        // Hiển thị chỉ báo đang nhập
        function showTypingIndicator(userName) {
            $('#typingIndicator').html(`
                <i class="fas fa-circle fa-xs"></i>
                <i class="fas fa-circle fa-xs"></i>
                <i class="fas fa-circle fa-xs"></i>
                <span class="ms-2">${userName} đang nhập...</span>
            `).addClass('show');
        }
        
        // Ẩn chỉ báo đang nhập
        function hideTypingIndicator() {
            $('#typingIndicator').removeClass('show');
        }
        
        // Cập nhật trạng thái đã đọc tin nhắn
        function updateMessageReadStatus(messageId) {
            $(`.message[data-message-id="${messageId}"] .message-time`).html(function() {
                return $(this).html().replace('<i class="fas fa-check text-muted"></i>', '<i class="fas fa-check-double text-primary"></i>');
            });
        }
        
        // ✅ Tự reload hội thoại mỗi 30s khi offline
        function startAutoRefresh(){
            // Xóa interval hiện có trước để tránh trùng lặp
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
            
            // Chỉ bắt đầu nếu chưa kết nối
            autoRefreshInterval = setInterval(() => {
                if (!isConnected) {
                    loadConversations();
                }
            }, 30000);
        }
        
        // Xử lý cập nhật tin nhắn real-time
        function handleRealTimeMessage(data) {
            console.log('Handling real-time message:', data);
            
            // Thêm tin nhắn vào cuộc trò chuyện hiện tại nếu khớp
            if (data.conversation_id === currentConversationId) {
                addMessageToChat(data, false);
            }
            
            // Cập nhật preview cuộc trò chuyện
            updateConversationPreview(data.conversation_id, data.message);
            
            // Cập nhật danh sách cuộc trò chuyện
            loadConversations();
        }
        
        // Tải tin nhắn với cập nhật real-time
        function loadMessagesWithRealTime(conversationId) {
            console.log('Loading messages with real-time updates for:', conversationId);
            
            // Tải tin nhắn ngay lập tức
            loadMessages(conversationId);
            
            // Thiết lập listeners real-time cho cuộc trò chuyện này
            if (isConnected && socket && typeof socket.emit === 'function') {
                socket.emit('join_conversation', { conversation_id: conversationId });
                
                // Lắng nghe tin nhắn mới trong cuộc trò chuyện này
                if (socket && typeof socket.on === 'function') {
                    socket.on('new_message', function(data) {
                        if (data.conversation_id === conversationId) {
                            handleRealTimeMessage(data);
                        }
                    });
                }
            }
        }
        
        // Phát tin nhắn ngay lập tức đến tất cả người dùng đã kết nối
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
        
        // Xử lý phát tin nhắn tức thì
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
        let allManagers = []; // Lưu danh sách tất cả managers để lọc
        
        function showManagerSelection() {
            const modal = new bootstrap.Modal(document.getElementById('managerSelectionModal'));
            modal.show();
            loadAvailableManagers();
        }
        
        // Tải danh sách nhân viên đang online (chỉ role 1 và 3)
        function loadAvailableManagers() {
            $.get(getApiPath('src/controllers/chat-controller.php?action=get_available_managers'), function(data) {
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
            $.get(getApiPath('src/controllers/chat-controller.php?action=get_admin_user'), function(data) {
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
            $.post(getApiPath('src/controllers/chat-controller.php?action=create_conversation'), {
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
            $.post(getApiPath('src/controllers/chat-controller.php?action=create_conversation'), {
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
        
        // Cập nhật preview cuộc trò chuyện
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
        
        // Lấy ID người dùng hiện tại
        function getCurrentUserId() {
            // Nên được thiết lập từ PHP session
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
        
        // ==================== CÁC HÀM MEDIA ====================
        
        // Thiết lập sự kiện media
        function setupMediaEvents() {
            // Xóa event listeners cũ trước khi attach mới (tránh duplicate)
            $('#fileInput').off('change');
            $(document).off('click', '#attachButton');
            
            // Thay đổi file input - Hỗ trợ nhiều files
            $('#fileInput').on('change', function(e) {
                console.log('File input changed');
                const files = e.target.files;
                if (files && files.length > 0) {
                    console.log('Files selected:', files.length);
                    // Upload từng file một (tuần tự để tránh quá tải)
                    uploadMultipleFiles(files);
                    // Reset file input sau khi upload để có thể chọn lại cùng file
                    $(this).val('');
                } else {
                    console.log('No files selected');
                }
            });
            
            // Click nút đính kèm
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
                
                // Kích hoạt click file input
                $('#fileInput').click();
                console.log('File input clicked');
            });
            
            // Nút gọi thoại
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
            
            // Nút gọi video
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
        
        // Upload nhiều files cùng lúc
        function uploadMultipleFiles(files) {
            if (!currentConversationId) {
                alert('Vui lòng chọn cuộc trò chuyện trước');
                return;
            }
            
            const fileArray = Array.from(files);
            console.log('Uploading', fileArray.length, 'files');
            
            // Upload từng file một (tuần tự để tránh quá tải server)
            let uploadIndex = 0;
            
            const uploadNext = () => {
                if (uploadIndex >= fileArray.length) {
                    console.log('All files uploaded');
                    return;
                }
                
                const file = fileArray[uploadIndex];
                uploadIndex++;
                
                uploadFile(file, () => {
                    // Upload file tiếp theo sau khi file hiện tại hoàn thành
                    setTimeout(uploadNext, 300); // Delay 300ms giữa các file
                });
            };
            
            uploadNext();
        }
        
        // Upload file (một file)
        function uploadFile(file, callback) {
            if (!currentConversationId) {
                alert('Vui lòng chọn cuộc trò chuyện trước');
                if (callback) callback();
                return;
            }
            
            // Xác thực kích thước file
            // Video: tối đa 50MB, Hình ảnh: tối đa 10MB, Khác: tối đa 10MB
            const isVideo = file.type.startsWith('video/');
            const isImage = file.type.startsWith('image/');
            const maxSize = isVideo ? (50 * 1024 * 1024) : (10 * 1024 * 1024); // Video: 50MB, Khác: 10MB
            
            if (file.size > maxSize) {
                const maxSizeMB = isVideo ? '50MB' : '10MB';
                alert(`File "${file.name}" quá lớn. Tối đa ${maxSizeMB}`);
                if (callback) callback();
                return;
            }
            
            // Xác thực loại file
            const allowedTypes = [
                // Hình ảnh
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                // Video
                'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm', 'video/x-matroska',
                // Tài liệu
                                 'application/pdf', 'application/msword', 
                                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain', 'application/zip', 'application/x-rar-compressed'
            ];
            
            if (!allowedTypes.includes(file.type)) {
                alert(`Loại file "${file.name}" không được hỗ trợ. Vui lòng chọn file hình ảnh, video, PDF, Word, hoặc text.`);
                if (callback) callback();
                return;
            }
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('conversation_id', currentConversationId);
            
            // Show upload progress với unique ID cho mỗi file
            const progressId = 'uploadProgress_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            const progressHtml = `
                <div class="upload-progress" id="progress_${progressId}">
                    <i class="fas fa-upload"></i>
                    <div>Đang upload ${file.name}...</div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="${progressId}"></div>
                    </div>
                </div>
            `;
            $('#chatMessages').append(progressHtml);
            scrollToBottom();
            
            // Vô hiệu hóa nút đính kèm trong khi upload
            $('#attachButton').prop('disabled', true);
            
            $.ajax({
                url: getApiPath('src/controllers/media-upload.php'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 60000, // Timeout 60 giây
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = evt.loaded / evt.total * 100;
                            $('#' + progressId).css('width', percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    $('#progress_' + progressId).remove();
                    // Chỉ enable attach button nếu không còn progress nào
                    if ($('.upload-progress').length === 0) {
                    $('#attachButton').prop('disabled', false);
                    }
                    // Không reset file input ở đây vì có thể còn files khác đang upload
                    
                    // Kiểm tra response có phải là string (JSON string) không
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
                        
                        // Cập nhật preview cuộc trò chuyện
                        let previewText = response.message.message || '[File]';
                        if (response.message.message_type === 'video') {
                            previewText = '[Video]';
                        } else if (response.message.message_type === 'image') {
                            previewText = '[Hình ảnh]';
                        }
                        updateConversationPreview(currentConversationId, previewText);
                        
                        // Lưu ý: Không emit Socket.IO event ở đây vì message đã được broadcast từ server
                        // Nếu emit sẽ gây duplicate message (1 lần từ AJAX success, 1 lần từ Socket.IO event)
                        
                        // Làm mới danh sách cuộc trò chuyện nếu chưa kết nối
                        if (!isConnected) {
                            setTimeout(function() {
                                loadConversations();
                            }, 500);
                        }
                        
                        // Call callback nếu có
                        if (callback) callback();
                    } else {
                        alert('Lỗi upload "' + file.name + '": ' + (response.error || 'Unknown error'));
                        if (callback) callback();
                    }
                },
                error: function(xhr, status, error) {
                    $('#progress_' + progressId).remove();
                    // Chỉ enable attach button nếu không còn progress nào
                    if ($('.upload-progress').length === 0) {
                    $('#attachButton').prop('disabled', false);
                    }
                    // Không reset file input ở đây vì có thể còn files khác đang upload
                    
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
                            // Giữ thông báo lỗi mặc định
                        }
                    }
                    
                    alert(errorMessage);
                    if (callback) callback();
                }
            });
        }
        
        // Tạo HTML tin nhắn nâng cao cho media
        function createMessageHTML(m) {
            const isSent = m.sender_id == currentUserId;
            
            // Kiểm tra tin nhắn có nội dung không (bỏ qua tin nhắn rỗng)
            const messageText = (m.message || m.text || '').trim();
            if (!messageText && !m.message_type && !m.file_path) {
                // Tin nhắn rỗng và không phải media/file - không hiển thị
                console.warn('Skipping empty message:', m);
                return '';
            }
            
            // Xử lý thời gian với kiểm tra hợp lệ
            let time = '--:--';
            try {
                if (m.created_at) {
                    const date = new Date(m.created_at);
                    if (!isNaN(date.getTime())) {
                        time = date.toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
                    } else {
                        console.warn('Invalid date:', m.created_at);
                        // Fallback về thời gian hiện tại nếu date không hợp lệ
                        time = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
                    }
                } else {
                    // Dùng thời gian hiện tại nếu không có created_at
                    time = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
                }
            } catch (e) {
                console.warn('Date parsing error:', e, 'for date:', m.created_at);
                // Fallback về thời gian hiện tại
                time = new Date().toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'});
            }
            
            let messageContent = '';
            
            // Lấy base path từ vị trí hiện tại - Tự động phát hiện cho cả localhost và production
            const getBasePath = function() {
                const path = window.location.pathname;
                const hostname = window.location.hostname;
                
                // Domain production (sukien.info.vn)
                if (hostname.includes('sukien.info.vn') || hostname.includes('sukien')) {
                    // Nếu ở root, trả về rỗng hoặc '/'
                    if (path === '/' || path.split('/').filter(p => p).length === 0) {
                        return '';
                    }
                    // Trích xuất base path từ vị trí hiện tại
                    // ví dụ: /chat.php -> '' (root), /admin/chat.php -> '' (root)
                    const pathParts = path.split('/').filter(p => p);
                    if (pathParts.length > 0 && pathParts[0] !== 'chat.php' && pathParts[0] !== 'admin') {
                        // Nếu có subdirectory, trả về nó
                        return '/' + pathParts[0] + '/';
                    }
                    // Root domain
                    return '';
                }
                
                // Localhost development - thử phát hiện my-php-project (chỉ cho localhost)
                if (hostname === 'localhost' || hostname === '127.0.0.1') {
                if (path.includes('/my-php-project/')) {
                    return path.substring(0, path.indexOf('/my-php-project/') + '/my-php-project/'.length);
                } else if (path.includes('/event/')) {
                    return path.substring(0, path.indexOf('/event/') + '/event/'.length) + 'my-php-project/';
                    }
                }
                
                // Fallback mặc định - thử lấy từ path hiện tại
                // Nếu đang ở /chat.php, giả định là root
                // Nếu đang ở /admin/chat.php, giả định là root
                const pathParts = path.split('/').filter(p => p && p !== 'chat.php' && p !== 'admin');
                if (pathParts.length > 0) {
                    // Có subdirectory
                    return '/' + pathParts[0] + '/';
                }
                
                // Root
                return '';
            };
            const basePath = getBasePath();
            
            if (m.message_type === 'image') {
                // Sửa đường dẫn file - đảm bảo định dạng đường dẫn đúng
                let imagePath = m.file_path || '';
                
                // Chuẩn hóa đường dẫn - xóa '../' và prefix 'my-php-project/' nếu có
                if (imagePath.startsWith('../')) {
                    imagePath = imagePath.substring(3);
                }
                if (imagePath.startsWith('my-php-project/')) {
                    imagePath = imagePath.substring(15);
                }
                
                // Kiểm tra đường dẫn đã chứa base path chưa (để tránh trùng lặp)
                let pathAlreadyHasBase = false;
                if (basePath && basePath !== '') {
                    const basePathNoSlash = basePath.startsWith('/') ? basePath.substring(1) : basePath;
                    if (imagePath.includes(basePathNoSlash) || imagePath.startsWith('/' + basePathNoSlash)) {
                        pathAlreadyHasBase = true;
                    }
                }
                
                // Xóa leading slash tạm thời để xử lý
                const hadLeadingSlash = imagePath.startsWith('/');
                if (hadLeadingSlash) {
                    imagePath = imagePath.substring(1);
                }
                
                // Chỉ thêm base path nếu chưa có
                if (!imagePath.startsWith('http') && imagePath.length > 0) {
                    if (pathAlreadyHasBase) {
                        // Đường dẫn đã có base, chỉ đảm bảo leading slash
                        if (!imagePath.startsWith('/')) {
                            imagePath = '/' + imagePath;
                        }
                    } else {
                        // Thêm base path
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
                
                // Sử dụng thumbnail nếu có để hiển thị, nhưng dùng bản gốc để preview
                let displayImagePath = imagePath;
                if (m.thumbnail_path && !imagePath.startsWith('http')) {
                    let thumbPath = m.thumbnail_path;
                    
                    // Chuẩn hóa đường dẫn thumbnail
                    if (thumbPath.startsWith('../')) {
                        thumbPath = thumbPath.substring(3);
                    }
                    if (thumbPath.startsWith('my-php-project/')) {
                        thumbPath = thumbPath.substring(15);
                    }
                    
                    // Kiểm tra đường dẫn thumbnail đã chứa base path chưa
                    let thumbAlreadyHasBase = false;
                    if (basePath && basePath !== '') {
                        const basePathNoSlash = basePath.startsWith('/') ? basePath.substring(1) : basePath;
                        if (thumbPath.includes(basePathNoSlash) || thumbPath.startsWith('/' + basePathNoSlash)) {
                            thumbAlreadyHasBase = true;
                        }
                    }
                    
                    // Xóa leading slash tạm thời
                    const thumbHadLeadingSlash = thumbPath.startsWith('/');
                    if (thumbHadLeadingSlash) {
                        thumbPath = thumbPath.substring(1);
                    }
                    
                    // Thêm base path nếu chưa có
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
                    // Sử dụng thumbnail để hiển thị (tải nhanh hơn)
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
            } else if (m.message_type === 'video') {
                // Sửa đường dẫn file - đảm bảo định dạng đường dẫn đúng
                let videoPath = m.file_path || '';
                
                // Chuẩn hóa đường dẫn - xóa '../' và prefix 'my-php-project/' nếu có
                if (videoPath.startsWith('../')) {
                    videoPath = videoPath.substring(3);
                }
                if (videoPath.startsWith('my-php-project/')) {
                    videoPath = videoPath.substring(15);
                }
                
                // Kiểm tra đường dẫn đã chứa base path chưa (để tránh trùng lặp)
                let pathAlreadyHasBase = false;
                if (basePath && basePath !== '') {
                    const basePathNoSlash = basePath.startsWith('/') ? basePath.substring(1) : basePath;
                    if (videoPath.includes(basePathNoSlash) || videoPath.startsWith('/' + basePathNoSlash)) {
                        pathAlreadyHasBase = true;
                    }
                }
                
                // Xóa leading slash tạm thời để xử lý
                const hadLeadingSlash = videoPath.startsWith('/');
                if (hadLeadingSlash) {
                    videoPath = videoPath.substring(1);
                }
                
                // Chỉ thêm base path nếu chưa có
                if (!videoPath.startsWith('http') && videoPath.length > 0) {
                    if (pathAlreadyHasBase) {
                        // Đường dẫn đã có base, chỉ đảm bảo leading slash
                        if (!videoPath.startsWith('/')) {
                            videoPath = '/' + videoPath;
                        }
                    } else {
                        // Thêm base path
                        if (basePath === '') {
                            if (!videoPath.startsWith('/')) {
                                videoPath = '/' + videoPath;
                            }
                        } else {
                            const base = basePath.endsWith('/') ? basePath : basePath + '/';
                            videoPath = base + videoPath;
                            if (!videoPath.startsWith('/')) {
                                videoPath = '/' + videoPath;
                            }
                        }
                    }
                }
                
                messageContent = `
                    <div class="media-message">
                        <video controls style="max-width: 400px; max-height: 400px; width: auto; height: auto; border-radius: 10px; display: block; object-fit: contain;">
                            <source src="${videoPath}" type="${m.mime_type || 'video/mp4'}">
                            Trình duyệt của bạn không hỗ trợ video tag.
                        </video>
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
                // Chỉ hiển thị nếu có nội dung
                const displayText = messageText || 'Tin nhắn trống';
                messageContent = `
                    <div>${escapeHtml(displayText)}</div>
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
        
        // Xem trước hình ảnh
        function previewImage(imagePath) {
            console.log('Preview image called with path:', imagePath);
            
            // Sửa đường dẫn hình ảnh - Tự động phát hiện base path
            const getBasePath = function() {
                const path = window.location.pathname;
                const hostname = window.location.hostname;
                
                // Domain production
                if (hostname.includes('sukien.info.vn') || hostname.includes('sukien')) {
                    const pathParts = path.split('/').filter(p => p);
                    if (pathParts.length > 0 && pathParts[0] !== 'chat.php' && pathParts[0] !== 'admin') {
                        return '/' + pathParts[0] + '/';
                    }
                    return '';
                }
                
                // Localhost (chỉ cho localhost)
                if (hostname === 'localhost' || hostname === '127.0.0.1') {
                if (path.includes('/my-php-project/')) {
                    return path.substring(0, path.indexOf('/my-php-project/') + '/my-php-project/'.length);
                } else if (path.includes('/event/')) {
                    return path.substring(0, path.indexOf('/event/') + '/event/'.length) + 'my-php-project/';
                    }
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
            
            // Xử lý URL tuyệt đối
            if (fixedPath.startsWith('http://') || fixedPath.startsWith('https://')) {
                // Đã là URL tuyệt đối, dùng như vậy
                console.log('Using absolute URL:', fixedPath);
            } else {
                // Chuẩn hóa đường dẫn - xóa '../' và prefix 'my-php-project/' nếu có
                if (fixedPath.startsWith('../')) {
                    fixedPath = fixedPath.substring(3);
                }
                if (fixedPath.startsWith('my-php-project/')) {
                    fixedPath = fixedPath.substring(15);
                }
                
                // Kiểm tra đường dẫn đã chứa base path chưa (để tránh trùng lặp)
                let pathAlreadyHasBase = false;
                if (basePath && basePath !== '') {
                    // Xóa leading slash từ basePath để so sánh
                    const basePathNoSlash = basePath.startsWith('/') ? basePath.substring(1) : basePath;
                    // Kiểm tra fixedPath đã chứa base path chưa
                    if (fixedPath.includes(basePathNoSlash) || fixedPath.startsWith('/' + basePathNoSlash)) {
                        pathAlreadyHasBase = true;
                        console.log('Path already contains base path, skipping addition');
                    }
                }
                
                // Xóa leading slash tạm thời để xử lý
                const hadLeadingSlash = fixedPath.startsWith('/');
                if (hadLeadingSlash) {
                    fixedPath = fixedPath.substring(1);
                }
                
                // Chỉ thêm base path nếu chưa có
                if (fixedPath.length > 0) {
                    if (pathAlreadyHasBase) {
                        // Đường dẫn đã có base, chỉ đảm bảo leading slash
                        if (!fixedPath.startsWith('/')) {
                            fixedPath = '/' + fixedPath;
                        }
                    } else {
                        // Thêm base path
                        if (basePath === '') {
                            if (!fixedPath.startsWith('/')) {
                                fixedPath = '/' + fixedPath;
                            }
                        } else {
                            const base = basePath.endsWith('/') ? basePath : basePath + '/';
                            fixedPath = base + fixedPath;
                            // Đảm bảo leading slash
                            if (!fixedPath.startsWith('/')) {
                                fixedPath = '/' + fixedPath;
                            }
                        }
                    }
                }
                console.log('Fixed path:', fixedPath);
            }
            
            // Đặt src hình ảnh và hiển thị modal
            const $previewImg = $('#previewImage');
            if ($previewImg.length === 0) {
                console.error('Preview image element not found!');
                alert('Không tìm thấy modal preview hình ảnh');
                return;
            }
            
            // Đặt src với xử lý lỗi
            $previewImg.attr('src', fixedPath);
            $previewImg.on('error', function() {
                console.error('Image failed to load:', fixedPath);
                $(this).attr('src', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Lb0BuZyB0aGkgdGkgxrDhu6NhbmggaGluaDwvdGV4dD48L3N2Zz4=');
                $(this).after('<div class="text-danger mt-2">Không thể tải hình ảnh. Đường dẫn: ' + fixedPath + '</div>');
            });
            
            // Hiển thị modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('imagePreviewModal'));
            if (modal) {
                modal.show();
            } else {
                const newModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
                newModal.show();
            }
            
            console.log('Modal shown with image path:', fixedPath);
        }
        
        // Định dạng kích thước file
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // ==================== CÁC HÀM CALL (Stringee SDK) ====================
        
        /**
         * Khởi tạo cuộc gọi (Voice hoặc Video) với Stringee
         */
        async function initiateCall(callType) {
            if (!currentConversationId) {
                alert('Vui lòng chọn cuộc trò chuyện trước khi gọi');
                return;
            }
            
            if (!window.StringeeHelper) {
                alert('Stringee SDK chưa được load. Vui lòng refresh trang.');
                return;
            }
            
            try {
                // Tạo call session trên server
                const response = await $.post(getApiPath('src/controllers/call-controller.php?action=initiate_call'), {
                conversation_id: currentConversationId,
                call_type: callType
                });
                
                if (!response.success) {
                    alert('Lỗi khởi tạo cuộc gọi: ' + (response.error || 'Unknown error'));
                    return;
                }
                
                // Lưu thông tin call
                    currentCall = {
                        id: response.call_id,
                        type: response.call_type,
                        receiver_id: response.receiver_id,
                        receiver_name: response.receiver_name,
                        status: response.status
                    };
                    
                // Hiển thị modal
                    showCallModal('outgoing', response.receiver_name, callType);
                    
                // Lấy token và join call với Stringee
                await window.StringeeHelper.getTokenAndJoin(response.call_id, callType, true);
                    
                    // Phát sự kiện call qua socket
                    if (isConnected && socket && typeof socket.emit === 'function') {
                    socket.emit('call_initiated', {
                            call_id: response.call_id,
                            caller_id: currentUserId,
                            receiver_id: response.receiver_id,
                            call_type: callType,
                            conversation_id: currentConversationId
                    });
                }
            } catch (error) {
                console.error('❌ Error initiating call:', error);
                alert('Lỗi khởi tạo cuộc gọi: ' + error.message);
                $('#callModal').removeClass('show').css('display', 'none');
                currentCall = null;
            }
        }
        
        // ✅ Setup Stringee event handlers
        function setupStringeeEventHandlers() {
            if (!window.StringeeHelper) {
                console.warn('⚠️ StringeeHelper chưa được load, không thể setup event handlers');
                return;
            }
            
            // Setup incoming call handler
            window.onStringeeIncomingCall = function(incomingCall) {
                console.log('📞 Incoming call received via Stringee:', incomingCall);
            };
            
            // Setup local stream handler
            window.onStringeeLocalStreamAdded = function(stream) {
                console.log('✅ Local stream added:', stream);
                
                const localVideo = document.getElementById('localVideo');
                if (localVideo && stream.getVideoTracks().length > 0) {
                    localVideo.srcObject = stream;
                    localVideo.play().catch(err => {
                        console.error('❌ Error playing local video:', err);
                    });
                }
            };
            
            // Setup remote stream handler
            window.onStringeeRemoteStreamAdded = function(stream) {
                console.log('✅ Remote stream added:', stream);
                
                const remoteVideo = document.getElementById('remoteVideo');
                if (remoteVideo && stream.getVideoTracks().length > 0) {
                    remoteVideo.srcObject = stream;
                    remoteVideo.play().catch(err => {
                        console.error('❌ Error playing remote video:', err);
                    });
                    
                    $('#videoCallContainer').addClass('show').css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1',
                        'z-index': '10000'
                    });
                }
                
                const remoteAudio = document.getElementById('remoteAudio');
                if (remoteAudio && stream.getAudioTracks().length > 0) {
                    remoteAudio.srcObject = stream;
                    remoteAudio.play().catch(err => {
                        console.error('❌ Error playing remote audio:', err);
                    });
                }
            };
            
            // Setup call answered handler
            window.onCallAnswered = function() {
                console.log('✅ Call answered');
                
                if (currentCall && currentCall.type === 'video') {
                    $('#callModal').removeClass('show').css('display', 'none');
                    $('#videoCallContainer').addClass('show').css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1',
                        'z-index': '10000'
                    });
                    } else {
                    showVoiceCallUI();
                }
            };
            
            // Setup call ended handler
            window.onCallEnded = function() {
                console.log('📞 Call ended');
                cleanupCall();
            };
            
            // Setup call rejected handler
            window.onCallRejected = function() {
                console.log('❌ Call rejected');
                cleanupCall();
            };
            
            // Setup call busy handler
            window.onCallBusy = function() {
                console.log('📞 Call busy');
                cleanupCall();
            };
            
            // Setup call error handler
            window.onCallError = function(error) {
                console.error('❌ Call error:', error);
                alert('Lỗi cuộc gọi: ' + (error.message || error));
                cleanupCall();
            };
        }
        
        // Cleanup call
        function cleanupCall() {
            $('#callModal').removeClass('show').css('display', 'none');
            $('#videoCallContainer').removeClass('show').css({
                'display': 'none',
                'visibility': 'hidden',
                'opacity': '0'
            });
            currentCall = null;
            
            if (window.StringeeHelper) {
                window.StringeeHelper.cleanup();
            }
        }
        
        // Hiển thị modal cuộc gọi
        function showCallModal(type, name, callType) {
            console.log('📞 showCallModal called:', { type, name, callType });
            
            $('#callerName').text(name);
            $('#callType').text(callType === 'video' ? 'Cuộc gọi video' : 'Cuộc gọi thoại');
            
            if (type === 'incoming') {
                $('#callStatus').text('Cuộc gọi đến...');
                // Xóa các nút hiện có trước
                $('#callControls').empty();
                // Thêm cả nút chấp nhận và từ chối - Đồng nhất với admin/chat.php
                $('#callControls').html(`
                    <button class="btn btn-success btn-lg me-2" onclick="acceptCall()" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="btn btn-danger btn-lg" onclick="rejectCall()" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                `);
                console.log('✅ Incoming call - Added accept and reject buttons');
            } else {
                $('#callStatus').text('Đang gọi...');
                // Xóa các nút hiện có trước
                $('#callControls').empty();
                // Thêm nút kết thúc cuộc gọi - Đồng nhất với admin/chat.php
                $('#callControls').html(`
                    <button class="btn btn-danger btn-lg" id="endCallBtn" onclick="endCall()" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; z-index: 10001;">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                `);
                
                // Cũng attach event listener như backup
                $('#endCallBtn').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📞 End call button clicked (outgoing) - via event listener');
                    endCall();
                });
                
                console.log('📤 Outgoing call - Added end button only');
            }
            
            // Hiển thị modal - Đảm bảo căn giữa màn hình
            const modalElement = document.getElementById('callModal');
            if (modalElement) {
                // Force show với CSS để đảm bảo căn giữa
                $(modalElement).addClass('show').css({
                    'display': 'flex',
                    'align-items': 'center',
                    'justify-content': 'center',
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'width': '100%',
                    'height': '100%',
                    'z-index': '10000'
                });
                console.log('✅ Call modal shown with type:', type);
            } else {
                console.error('❌ Call modal element not found!');
            }
            
            // Debug: Kiểm tra các nút có trong DOM không - Đồng nhất với admin/chat.php
            setTimeout(() => {
                const acceptBtn = $('#callControls .btn-success');
                const rejectBtn = $('#callControls .btn-danger');
                const endBtn = $('#callControls .btn-danger');
                console.log('🔍 Button check:', {
                    acceptBtn: acceptBtn.length,
                    rejectBtn: rejectBtn.length,
                    endBtn: endBtn.length,
                    acceptBtnVisible: acceptBtn.is(':visible'),
                    rejectBtnVisible: rejectBtn.is(':visible'),
                    endBtnVisible: endBtn.is(':visible'),
                    callControlsHTML: $('#callControls').html(),
                    modalVisible: $('#callModal').hasClass('show'),
                    modalDisplay: $('#callModal').css('display')
                });
                
                // Force show buttons nếu không hiển thị
                if (type === 'incoming') {
                    if (acceptBtn.length > 0 && !acceptBtn.is(':visible')) {
                        acceptBtn.css('display', 'inline-flex');
                    }
                    if (rejectBtn.length > 0 && !rejectBtn.is(':visible')) {
                        rejectBtn.css('display', 'inline-flex');
                    }
                }
            }, 100);
        }
        
        /**
         * Chấp nhận cuộc gọi với Stringee SDK
         */
        async function acceptCall() {
            if (!currentCall) {
                console.error('No current call to accept');
                return;
            }
            
            if (!window.StringeeHelper) {
                alert('Stringee SDK chưa được load. Vui lòng refresh trang.');
                return;
            }
            
            try {
                // Accept call trên server
                const response = await $.post(getApiPath('src/controllers/call-controller.php?action=accept_call'), {
                call_id: currentCall.id
                });
                
                if (!response.success) {
                    alert('Lỗi chấp nhận cuộc gọi: ' + (response.error || 'Unknown error'));
                    return;
                }
                
                // Lấy token và join call với Stringee
                await window.StringeeHelper.getTokenAndJoin(currentCall.id, currentCall.type, false);
                    
                    // Phát sự kiện accept
                    if (isConnected && socket && typeof socket.emit === 'function') {
                        socket.emit('call_accepted', {
                            call_id: currentCall.id,
                            caller_id: currentCall.caller_id || currentCall.receiver_id,
                            receiver_id: currentUserId
                        });
                    }
            } catch (error) {
                console.error('❌ Error accepting call:', error);
                alert('Lỗi: ' + error.message);
                }
        }
        
        /**
         * Từ chối cuộc gọi
         */
        function rejectCall() {
            if (!currentCall) {
                cleanupCall();
                return;
            }
            
            const callId = currentCall.id;
            const callerId = currentCall.caller_id || currentCall.receiver_id;
            
            // Cleanup Stringee call
            if (window.StringeeHelper) {
                window.StringeeHelper.cleanup();
            }
            
            // Gọi backend để reject
            $.post(getApiPath('src/controllers/call-controller.php?action=reject_call'), {
                call_id: callId
            }, function(response) {
                cleanupCall();
                
                // Phát sự kiện reject
                if (isConnected && socket && typeof socket.emit === 'function') {
                    socket.emit('call_rejected', {
                        call_id: callId,
                        caller_id: callerId,
                        receiver_id: currentUserId
                    });
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Reject call error:', error);
                cleanupCall();
            });
        }
        
        /**
         * Kết thúc cuộc gọi với Stringee SDK
         */
        function endCall() {
            const callId = currentCall ? currentCall.id : null;
            
            // Cleanup Stringee call ngay lập tức
            if (window.StringeeHelper) {
                window.StringeeHelper.endCall();
                window.StringeeHelper.cleanup();
            }
            
            // Cleanup UI
            cleanupCall();
            
            // Gọi backend để kết thúc cuộc gọi (async)
            if (callId) {
            $.post(getApiPath('src/controllers/call-controller.php?action=end_call'), {
                call_id: callId
            }, function(response) {
                // Phát sự kiện end qua socket
                if (isConnected && socket && typeof socket.emit === 'function') {
                    socket.emit('call_ended', {
                        call_id: callId,
                        caller_id: currentUserId
                    });
                    }
                }, 'json').fail(function() {
                // Vẫn phát sự kiện end ngay cả khi backend fail
                if (isConnected && socket && typeof socket.emit === 'function') {
                    socket.emit('call_ended', {
                        call_id: callId,
                        caller_id: currentUserId
                    });
                }
            });
            }
        }
        
        // Làm endCall có thể truy cập toàn cục
        window.endCall = endCall;
        
        // Hiển thị UI cuộc gọi thoại
        function showVoiceCallUI() {
            console.log('📞 showVoiceCallUI called');
            
            // Lấy tên người gọi/người nhận
            const conversation = conversations.find(c => c.id == currentConversationId);
            const otherUserName = conversation ? conversation.other_user_name : 'Người gọi';
            
            console.log('📞 Other user name:', otherUserName);
            
            // Cập nhật call modal để hiển thị trạng thái cuộc gọi đang hoạt động
            $('#callerName').text(otherUserName);
            $('#callType').text('Cuộc gọi thoại');
            $('#callStatus').text('Đang gọi...');
            
            // Xóa các nút hiện có trước
            $('#callControls').empty();
            // Chỉ hiển thị nút kết thúc cuộc gọi - Đồng nhất với admin/chat.php
            $('#callControls').html(`
                <button class="btn btn-danger btn-lg" id="endCallBtn" onclick="endCall()" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; z-index: 10001;">
                    <i class="fas fa-phone-slash"></i>
                </button>
            `);
            
            // Cũng attach event listener như backup
            $('#endCallBtn').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('📞 End call button clicked (voice call) - via event listener');
                endCall();
            });
            
            // QUAN TRỌNG: Đảm bảo modal hiển thị và căn giữa màn hình
            const modalElement = document.getElementById('callModal');
            if (modalElement) {
                // Force show với CSS để đảm bảo căn giữa
                $(modalElement).addClass('show').css({
                    'display': 'flex',
                    'align-items': 'center',
                    'justify-content': 'center',
                    'position': 'fixed',
                    'top': '0',
                    'left': '0',
                    'width': '100%',
                    'height': '100%',
                    'z-index': '10000',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            }
            
            // Ẩn video container nếu đang hiển thị
            $('#videoCallContainer').hide();
            
            console.log('✅ Voice call UI shown with end call button');
            
            // Debug: Kiểm tra nút có trong DOM sau một khoảng delay ngắn
            setTimeout(() => {
                const endBtn = $('#callControls .call-btn.end');
                const modalVisible = $('#callModal').hasClass('show');
                const modalDisplay = $('#callModal').css('display');
                console.log('🔍 End call button check:', {
                    endBtnExists: endBtn.length,
                    endBtnVisible: endBtn.is(':visible'),
                    modalVisible: modalVisible,
                    modalDisplay: modalDisplay,
                    callControlsHTML: $('#callControls').html()
                });
                
                // Force show nếu không hiển thị - Đảm bảo căn giữa
                if (!modalVisible || modalDisplay === 'none') {
                    console.warn('⚠️ Modal not visible, forcing show');
                    $('#callModal').addClass('show').css({
                        'display': 'flex',
                        'align-items': 'center',
                        'justify-content': 'center',
                        'position': 'fixed',
                        'top': '0',
                        'left': '0',
                        'width': '100%',
                        'height': '100%',
                        'z-index': '10000',
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                }
                
                // Force button visibility nếu không hiển thị
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
        
        // Toggle mute với Stringee SDK
        function toggleMute() {
            if (window.StringeeHelper && window.StringeeHelper.toggleMute) {
                    isMuted = window.StringeeHelper.toggleMute();
                    const icon = $('#muteBtn i');
                    if (isMuted) {
                        icon.removeClass('fa-microphone').addClass('fa-microphone-slash');
                    } else {
                        icon.removeClass('fa-microphone-slash').addClass('fa-microphone');
                    }
            }
        }
        
        // Toggle camera với Stringee SDK
        function toggleCamera() {
            if (window.StringeeHelper && window.StringeeHelper.toggleCamera) {
                    isCameraOff = window.StringeeHelper.toggleCamera();
                    const icon = $('#cameraBtn i');
                    if (isCameraOff) {
                        icon.removeClass('fa-video').addClass('fa-video-slash');
                    } else {
                        icon.removeClass('fa-video-slash').addClass('fa-video');
                    }
            }
        }
        
        // End video call
        function endVideoCall() {
            endCall();
        }
        
        // Socket events for calls (Stringee - chỉ cần xử lý call signaling)
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
                        
                        // Force show modal if it doesn't show - Đảm bảo căn giữa
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
                                    $('#callModal').addClass('show').css({
                                        'display': 'flex',
                                        'align-items': 'center',
                                        'justify-content': 'center',
                                        'position': 'fixed',
                                        'top': '0',
                                        'left': '0',
                                        'width': '100%',
                                        'height': '100%',
                                        'z-index': '10000'
                                    });
                                }
                            }
                        }, 100);
                    } else {
                        console.log('❌ Call is not for this user, ignoring');
                        console.log('❌ Receiver ID:', data.receiver_id, 'Current User ID:', currentUserId);
                    }
                });
                
                // Call accepted - Stringee sẽ tự động kết nối khi cả 2 bên join call
                socket.on('call_accepted', data => {
                    console.log('📞 Received call_accepted event:', data);
                    // Logic đã được xử lý trong acceptCall()
                });
                
                // Call rejected
                socket.on('call_rejected', data => {
                    console.log('Received call_rejected event:', data);
                    if (data.caller_id === currentUserId) {
                        $('#callModal').removeClass('show');
                        if (window.StringeeHelper) {
                            window.StringeeHelper.cleanup();
                        }
                        currentCall = null;
                        showNotification(data.message || 'Cuộc gọi bị từ chối', 'warning', 'fa-times-circle');
                    }
                });
                
                // Call ended
                socket.on('call_ended', data => {
                    console.log('📞 Received call_ended event:', data);
                    
                    // QUAN TRỌNG: Cleanup đầy đủ khi bên kia tắt cuộc gọi
                    // Ẩn modal và video container
                    $('#callModal').removeClass('show').css('display', 'none');
                    $('#videoCallContainer').removeClass('show').css({
                        'display': 'none',
                        'visibility': 'hidden',
                        'opacity': '0'
                    });
                    
                    // Cleanup Stringee
                    if (window.StringeeHelper) {
                        window.StringeeHelper.cleanup();
                    }
                    
                    // ✅ Hiển thị thông báo
                    if (data.message) {
                        showNotification(data.message, 'info');
                    } else {
                        showNotification('Cuộc gọi đã kết thúc', 'info');
                    }
                    
                    currentCall = null;
                    console.log('✅ Call cleanup completed');
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