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
                                <small>Chỉ hiển thị nhân viên đang online</small>
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
                <button class="call-btn accept" id="acceptCallBtn">
                    <i class="fas fa-phone"></i>
                </button>
                <button class="call-btn reject" id="rejectCallBtn">
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
            <button class="video-control-btn end" id="endVideoCallBtn" style="cursor: pointer;">
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
    <!-- WebRTC Helper Functions -->
    <script src="<?php echo BASE_PATH; ?>/assets/js/webrtc-helper.js"></script>
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
                const apiPath = '/' + relativePath;
                console.log('📡 Production API Path:', apiPath);
                return apiPath;
            }
            
            // Localhost development - xử lý nhiều trường hợp
            // Ưu tiên kiểm tra path đầy đủ trước
            if (path.includes('/event/my-php-project/')) {
                const apiPath = '/event/my-php-project/' + relativePath;
                console.log('📡 Localhost API Path (from /event/my-php-project/):', apiPath);
                return apiPath;
            } else if (path.includes('/my-php-project/')) {
                const apiPath = '/my-php-project/' + relativePath;
                console.log('📡 Localhost API Path (from /my-php-project/):', apiPath);
                return apiPath;
            } else if (path.includes('/event/')) {
                // Nếu có /event/ nhưng chưa có /my-php-project/, thêm vào
                const apiPath = '/event/my-php-project/' + relativePath;
                console.log('📡 Localhost API Path (from /event/):', apiPath);
                return apiPath;
            }
            
            // Fallback: đường dẫn tương đối
            const apiPath = '../' + relativePath;
            console.log('📡 Fallback API Path (relative):', apiPath);
            return apiPath;
        }
        
        let socket = null;
        let currentConversationId = null;
        // ✅ Set để lưu các message IDs đã thêm (tránh duplicate)
        const addedMessageIds = new Set();
        
        // ✅ Lưu last message ID để chỉ lấy messages mới
        let lastMessageId = null;
        
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
        
        // Biến cho Media và Call (WebRTC)
        // Note: currentCall được khai báo trong phần call functions
        let isMuted = false;
        let isCameraOff = false;
        
        // ID của interval cho polling/auto-refresh (để tránh tạo nhiều interval)
        let autoRefreshInterval = null;
        let pollingInterval1 = null;
        let pollingInterval2 = null;
        let activityInterval = null;
        
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
            
            // ✅ Initialize WebRTC Helper khi socket đã kết nối
            if (socket && window.WebRTCHelper) {
                window.WebRTCHelper.init(socket);
                console.log('✅ WebRTC Helper initialized');
            }
            
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
        
        // ✅ Expose các hàm call ra global scope sau khi tất cả đã được định nghĩa
        // Đảm bảo các hàm có sẵn khi modal được hiển thị
        $(document).ready(function() {
            // Đợi một chút để đảm bảo tất cả các hàm đã được định nghĩa
            setTimeout(function() {
                if (typeof acceptCall !== 'undefined') {
                    window.acceptCall = acceptCall;
                    console.log('✅ acceptCall exposed to global scope');
                }
                if (typeof rejectCall !== 'undefined') {
                    window.rejectCall = rejectCall;
                    console.log('✅ rejectCall exposed to global scope');
                }
                if (typeof endCall !== 'undefined') {
                    window.endCall = endCall;
                    console.log('✅ endCall exposed to global scope');
                }
                
                // ✅ Thêm event listeners cho các nút trong HTML ban đầu (nếu có)
                $('#acceptCallBtn').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📞 Accept button clicked (from initial HTML)');
                    if (typeof acceptCall === 'function') {
                        acceptCall();
                    } else if (typeof window.acceptCall === 'function') {
                        window.acceptCall();
                    } else {
                        console.error('❌ acceptCall function not found');
                        alert('Lỗi: Hàm acceptCall không tìm thấy. Vui lòng refresh trang.');
                    }
                });
                
                $('#rejectCallBtn').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📞 Reject button clicked (from initial HTML)');
                    if (typeof rejectCall === 'function') {
                        rejectCall();
                    } else if (typeof window.rejectCall === 'function') {
                        window.rejectCall();
                    } else {
                        console.error('❌ rejectCall function not found');
                        alert('Lỗi: Hàm rejectCall không tìm thấy. Vui lòng refresh trang.');
                    }
                });
                
                // ✅ Thêm event listener cho nút end call trong video container
                $(document).off('click.endVideoCall', '#endVideoCallBtn').on('click.endVideoCall', '#endVideoCallBtn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📞 End video call button clicked');
                    
                    if (typeof endVideoCall === 'function') {
                        endVideoCall();
                    } else if (typeof endCall === 'function') {
                        endCall();
                    } else if (typeof window.endCall === 'function') {
                        window.endCall();
                    } else {
                        console.error('❌ endCall/endVideoCall function not found');
                        alert('Lỗi: Hàm endCall không tìm thấy. Vui lòng refresh trang.');
                    }
                });
                
                // ✅ Expose endVideoCall ra global scope
                if (typeof endVideoCall !== 'undefined') {
                    window.endVideoCall = endVideoCall;
                    console.log('✅ endVideoCall exposed to global scope');
                }
            }, 500); // Đợi 500ms để đảm bảo tất cả các hàm đã được định nghĩa
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
                // ✅ Bật polling mode khi Socket.IO không khả dụng
                startPollingMode();
                // ✅ Reset flag
                isInitializingSocket = false;
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
                
                // ✅ Dừng polling mode khi đã kết nối
                stopPollingMode();
                
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
                
                // ✅ Initialize WebRTC Helper và setup event handlers
                if (window.WebRTCHelper) {
                    window.WebRTCHelper.init(socket);
                    setupWebRTCEventHandlers();
                    console.log('✅ WebRTC Helper initialized');
                }
            });
            
            // ✅ Flag để tránh log lỗi quá nhiều lần
            let connectionErrorLogged = false;
            let connectionErrorCount = 0;
            
            socket.on('connect_error', (error) => {
                connectionErrorCount++;
                
                // Chỉ log chi tiết lần đầu và mỗi 10 lần để tránh spam console
                if (!connectionErrorLogged || connectionErrorCount % 10 === 0) {
                    console.error('❌ Socket.IO connection error:', error);
                    console.error('Error type:', error.type);
                    console.error('Error message:', error.message);
                    console.error('Error description:', error.description);
                    console.error('Connection URL:', socketServerURL);
                    console.error('Connection Path:', socketPath);
                    console.error('Full connection URL:', socketServerURL + socketPath);
                    connectionErrorLogged = true;
                } else {
                    // Log ngắn gọn cho các lần sau
                    console.warn(`⚠️ Socket.IO connection error (attempt ${connectionErrorCount}):`, error.message || error.type);
                }
                
                isConnected = false;
                
                // ✅ Reset flag sau một khoảng thời gian để có thể retry
                setTimeout(() => {
                    isInitializingSocket = false;
                }, 2000);
                
                // ✅ Nếu lỗi là connection refused và đã thử nhiều lần, chuyển sang fallback mode
                if (connectionErrorCount >= 3 && (error.message?.includes('refused') || error.type === 'TransportError')) {
                    console.warn('⚠️ Socket.IO server không khả dụng sau nhiều lần thử, chuyển sang fallback mode');
                    updateConnectionStatus('offline', 'Chế độ offline - Sử dụng AJAX');
                    startPollingMode();
                } else {
                    // Hiển thị connecting để người dùng biết đang thử kết nối lại
                    updateConnectionStatus('connecting', 'Đang kết nối...');
                }
                
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
                
                // ✅ Dừng polling mode khi đã kết nối lại
                stopPollingMode();
                
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
                
                // ✅ Bỏ qua nếu là tin nhắn của chính mình (so sánh đúng kiểu)
                const senderId = data.user_id || (data.message && data.message.sender_id) || null;
                const isOwnMessage = senderId && (String(senderId) === String(currentUserId));
                
                if (isOwnMessage && data.skip_sender) {
                    console.log('Skipping own message (skip_sender flag)');
                    return;
                }
                
                if (data.conversation_id == currentConversationId) {
                    // ✅ QUAN TRỌNG: Không bỏ qua media messages từ PHP (message_type có giá trị)
                    // Media messages từ PHP cần được hiển thị để đảm bảo đồng bộ
                    const isMediaMessage = data.message && (
                        data.message.message_type || 
                        data.message.file_path || 
                        data.message.file_name
                    );
                    
                    // ✅ Chỉ bỏ qua text messages của chính mình (không phải media)
                    if (isOwnMessage && !isMediaMessage) {
                        console.log('Skipping own text message (user_id check)');
                        return;
                    }
                    
                    // ✅ Nếu là media message từ PHP, luôn hiển thị (kể cả của chính mình)
                    if (isMediaMessage) {
                        console.log('Received media message from PHP, displaying...');
                    }
                    
                    // ✅ Server emit: { conversation_id, message, user_id, user_name, timestamp }
                    // message có thể là string hoặc object
                    let messageData;
                    if (typeof data.message === 'object' && data.message !== null) {
                        // Nếu message là object (có id, sender_id, etc.)
                        messageData = data.message;
                    } else {
                        // Nếu message là string, tạo object từ data
                        messageData = {
                            id: data.message_id || null,
                            conversation_id: data.conversation_id,
                            sender_id: data.user_id,
                            message: data.message || data.text || '',
                            created_at: data.timestamp || new Date().toISOString(),
                            sender_name: data.user_name || 'Người dùng',
                            IsRead: 0
                        };
                    }
                    
                    // ✅ Đảm bảo conversation_id được set đúng
                    if (!messageData.conversation_id) {
                        messageData.conversation_id = currentConversationId;
                    }
                    
                    // ✅ Đảm bảo có created_at
                    if (!messageData.created_at && data.timestamp) {
                        messageData.created_at = data.timestamp;
                    }
                    
                    addMessageToChat(messageData, false);
                    scrollToBottom();
                    markMessagesAsRead(currentConversationId);
                    
                    // ✅ Cập nhật preview của conversation hiện tại
                    const messageText = messageData.message || messageData.text || '';
                    updateConversationPreview(currentConversationId, messageText);
                } else {
                    // ✅ Cập nhật preview của conversation khác ngay lập tức
                    const messageText = typeof data.message === 'string' ? data.message : (data.message?.message || data.message?.text || '');
                    if (messageText) {
                        updateConversationPreview(data.conversation_id, messageText);
                    }
                    
                    // ✅ Reload danh sách conversation để cập nhật unread count và timestamp
                    loadConversations();
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
                // ✅ So sánh đúng kiểu và chỉ bỏ qua nếu là tin nhắn của chính mình
                const senderId = data.userId || (data.message && data.message.sender_id) || null;
                const isOwnMessage = senderId && (String(senderId) === String(currentUserId));
                
                // ✅ QUAN TRỌNG: Media messages từ PHP cần được hiển thị cho tất cả (kể cả người gửi)
                const isMediaMessage = data.message && (
                    data.message.message_type || 
                    data.message.file_path || 
                    data.message.file_name
                );
                
                // ✅ Hiển thị nếu: (1) không phải tin nhắn của chính mình, HOẶC (2) là media message
                if (data.conversation_id == currentConversationId && (!isOwnMessage || isMediaMessage)) {
                    // ✅ Đảm bảo conversation_id được set đúng
                    if (data.message && !data.message.conversation_id) {
                        data.message.conversation_id = currentConversationId;
                    }
                    
                    // ✅ Đảm bảo có created_at
                    if (data.message && !data.message.created_at && data.timestamp) {
                        data.message.created_at = data.timestamp;
                    }
                    
                    addMessageToChat(data.message, false);
                    scrollToBottom();
                    
                    // ✅ Cập nhật preview của conversation hiện tại
                    const messageText = data.message?.message || data.message?.text || '';
                    if (messageText) {
                        updateConversationPreview(currentConversationId, messageText);
                    }
                } else if (data.conversation_id != currentConversationId) {
                    // ✅ Cập nhật preview của conversation khác
                    const messageText = data.message?.message || data.message?.text || '';
                    if (messageText) {
                        updateConversationPreview(data.conversation_id, messageText);
                    }
                }
            });

            // 🖼️ Xử lý event image_uploaded để load hình ảnh realtime
            socket.on('image_uploaded', data => {
                console.log('🖼️ Received image_uploaded event:', data);
                
                if (data.conversation_id == currentConversationId) {
                    // ✅ Đảm bảo conversation_id được set đúng
                    if (data.message && !data.message.conversation_id) {
                        data.message.conversation_id = currentConversationId;
                    }
                    
                    // ✅ Đảm bảo có created_at
                    if (data.message && !data.message.created_at && data.timestamp) {
                        data.message.created_at = data.timestamp;
                    }
                    
                    // ✅ Force reload hình ảnh bằng cách thêm timestamp vào URL
                    if (data.message && data.message.file_path) {
                        const timestamp = new Date().getTime();
                        data.message.file_path = data.message.file_path + (data.message.file_path.includes('?') ? '&' : '?') + '_t=' + timestamp;
                    }
                    
                    addMessageToChat(data.message, false);
                    scrollToBottom();
                    
                    // ✅ Cập nhật preview của conversation hiện tại
                    const messageText = data.message?.message || '[Hình ảnh]';
                    updateConversationPreview(currentConversationId, messageText);
                } else if (data.conversation_id != currentConversationId) {
                    // ✅ Cập nhật preview của conversation khác
                    const messageText = data.message?.message || '[Hình ảnh]';
                    updateConversationPreview(data.conversation_id, messageText);
                    loadConversations();
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
            const apiUrl = getApiPath('src/controllers/chat-controller.php?action=get_conversations');
            console.log('🔍 Loading conversations from:', apiUrl);
            console.log('🔍 Current location:', window.location.href);
            console.log('🔍 Current pathname:', window.location.pathname);
            
            $.ajax({
                url: apiUrl,
                type: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(res) {
                    if (!res || !res.success) {
                        console.error('Error loading conversations:', res ? res.error : 'Response is undefined');
                        $('#conversationsList').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <strong>Lỗi tải cuộc trò chuyện</strong><br>
                                <small>${res ? res.error : 'Không nhận được phản hồi từ server'}</small>
                            </div>
                        `);
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
                },
                error: function(xhr, status, error) {
                    console.error('❌ AJAX Error loading conversations:', error);
                    console.error('❌ Status:', status);
                    console.error('❌ XHR Status:', xhr.status);
                    console.error('❌ Response:', xhr.responseText);
                    console.error('❌ Request URL:', apiUrl);
                    
                    let errorMessage = 'Lỗi kết nối';
                    if (xhr.status === 404) {
                        errorMessage = 'Không tìm thấy API endpoint. Vui lòng kiểm tra đường dẫn.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Lỗi server. Vui lòng thử lại sau.';
                    } else if (status === 'timeout') {
                        errorMessage = 'Timeout. Vui lòng kiểm tra kết nối mạng.';
                    } else if (xhr.responseText && xhr.responseText.includes('<!doctype')) {
                        errorMessage = 'Server trả về trang HTML thay vì JSON. Có thể đường dẫn không đúng.';
                    } else {
                        errorMessage = `Lỗi: ${error || status || 'Unknown error'}`;
                    }
                    
                    $('#conversationsList').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <strong>${errorMessage}</strong><br>
                            <small>URL: ${apiUrl}</small><br>
                            <small>Status: ${xhr.status} - ${error}</small><br>
                            <button class="btn btn-sm btn-outline-secondary mt-2" onclick="loadConversations()">
                                <i class="fas fa-refresh"></i> Thử lại
                            </button>
                        </div>
                    `);
                }
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
            
            // ✅ Reset tracking khi chuyển conversation
            addedMessageIds.clear();
            lastMessageId = null;
            
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
            
            // ✅ Reset tracking khi load lại toàn bộ messages
            addedMessageIds.clear();
            lastMessageId = null;
            
            let html = '';
            messages.forEach((message, index) => {
                console.log(`Processing message ${index}:`, message);
                try {
                    // Bỏ qua tin nhắn rỗng hoặc chỉ có khoảng trắng
                    const messageText = (message.message || message.text || '').trim();
                    if (messageText || message.message_type) { // Chỉ hiển thị nếu có nội dung hoặc là media/file
                        html += createMessageHTML(message);
                        
                        // ✅ Track message ID để tránh duplicate
                        const messageId = message.id || message.message_id;
                        if (messageId) {
                            addedMessageIds.add(messageId);
                            // ✅ So sánh messageId (có thể là string hoặc number)
                            const messageIdNum = parseInt(messageId);
                            const lastIdNum = lastMessageId ? parseInt(lastMessageId) : 0;
                            if (!lastMessageId || messageIdNum > lastIdNum) {
                                lastMessageId = messageIdNum;
                            }
                        }
                    }
                } catch (error) {
                    console.error(`Error processing message ${index}:`, error, message);
                    html += '<div class="message error"><div class="message-content"><div>Lỗi hiển thị tin nhắn</div></div></div>';
                }
            });
            
            $('#chatMessages').html(html);
            scrollToBottom();
        }
        
        // ✅ Tạo HTML tin nhắn - Sử dụng hàm nâng cao ở dưới (dòng 3784) để xử lý media/file
        // Hàm này đã bị xóa để tránh duplicate, sử dụng hàm createMessageHTML() nâng cao ở dưới
        
        // ✅ Thêm tin nhắn vào khung chat
        function addMessageToChat(msg,isSent){
            console.log('Adding message to chat:', msg, 'isSent:', isSent, 'currentConversationId:', currentConversationId);
            
            // Kiểm tra tin nhắn có nội dung không (bỏ qua tin nhắn rỗng)
            const messageText = (msg.message || msg.text || '').trim();
            if (!messageText && !msg.message_type && !msg.file_path) {
                console.warn('Skipping empty message in addMessageToChat:', msg);
                return;
            }
            
            // ✅ Kiểm tra duplicate dựa trên message_id + conversation_id
            const messageId = msg.id || msg.message_id;
            if (messageId) {
                // Kiểm tra trong Set (nhanh hơn)
                if (addedMessageIds.has(messageId)) {
                    console.log('Message already exists in Set, skipping duplicate:', messageId);
                    return;
                }
                
                // Kiểm tra trong DOM (backup check)
                if ($(`.message[data-message-id="${messageId}"]`).length > 0) {
                    console.log('Message already exists in DOM, skipping duplicate:', messageId);
                    addedMessageIds.add(messageId); // Thêm vào Set để tránh check lại
                    return;
                }
                
                // ✅ Kiểm tra conversation_id phải khớp (chỉ check nếu có conversation_id)
                if (msg.conversation_id) {
                    if (String(msg.conversation_id) != String(currentConversationId)) {
                        console.log('Message belongs to different conversation, skipping:', messageId, 'Expected:', currentConversationId, 'Got:', msg.conversation_id);
                        return;
                    }
                } else {
                    // Tự động set conversation_id nếu chưa có
                    msg.conversation_id = currentConversationId;
                }
                
                // ✅ Thêm vào Set để đánh dấu đã thêm
                addedMessageIds.add(messageId);
            }
            
            const html=createMessageHTML(msg);
            if (html) { // Chỉ append nếu có HTML (không phải chuỗi rỗng)
                $('#chatMessages').append(html);
                
                // ✅ Cập nhật lastMessageId (so sánh số để đảm bảo đúng)
                if (messageId) {
                    const messageIdNum = parseInt(messageId);
                    const lastIdNum = lastMessageId ? parseInt(lastMessageId) : 0;
                    if (!lastMessageId || messageIdNum > lastIdNum) {
                        lastMessageId = messageIdNum;
                    }
                }
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
                        
                        // Thêm tin nhắn ngay lập tức để phản hồi tức thì (optimistic update)
                        addMessageToChat(res.message, true);
                        scrollToBottom();
                        
                        // Phát sự kiện real-time (chỉ cho người khác, không phải mình)
                        if (isConnected && socket) {
                            if (socket && typeof socket.emit === 'function') {
                                socket.emit('new_message', {
                                    conversation_id: currentConversationId,
                                    message: res.message,
                                    user_id: currentUserId,
                                    user_name: currentUserName,
                                    skip_sender: true // ✅ Flag để bỏ qua ở client của người gửi
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
        // Start polling mode for real-time messaging when Socket.IO is unavailable
        function startPollingMode() {
            // Prevent multiple polling modes from running
            if (pollingInterval1 || pollingInterval2) {
                console.log('Polling mode already running, skipping...');
                return;
            }
            
            console.log('Starting polling mode for real-time messaging');
            
            // Poll for new messages every 2 seconds
            pollingInterval1 = setInterval(function() {
                if (!isConnected) {
                    if (currentConversationId) {
                        checkForNewMessages();
                    }
                    loadConversations();
                }
            }, 2000);
            
            // Poll for conversation updates every 5 seconds
            pollingInterval2 = setInterval(function() {
                if (!isConnected) {
                    loadConversations();
                }
            }, 5000);
        }
        
        // Stop polling mode
        function stopPollingMode() {
            console.log('Stopping polling mode...');
            if (pollingInterval1) {
                clearInterval(pollingInterval1);
                pollingInterval1 = null;
            }
            if (pollingInterval2) {
                clearInterval(pollingInterval2);
                pollingInterval2 = null;
            }
        }
        
        // Check for new messages in current conversation
        function checkForNewMessages() {
            if (!currentConversationId) return;
            
            // ✅ Chỉ lấy messages mới hơn lastMessageId
            const apiUrl = getApiPath('src/controllers/chat-controller.php?action=get_messages&conversation_id=' + currentConversationId) + 
                           (lastMessageId ? '&last_id=' + lastMessageId : '');
            
            $.getJSON(apiUrl, function(res) {
                if (res.success && res.messages && res.messages.length > 0) {
                    // ✅ Chỉ thêm messages mới, không reload toàn bộ
                    res.messages.forEach(function(msg) {
                        // Kiểm tra xem message đã tồn tại chưa
                        const messageId = msg.id || msg.message_id;
                        if (messageId && !addedMessageIds.has(messageId)) {
                            // ✅ Đảm bảo conversation_id được set
                            if (!msg.conversation_id) {
                                msg.conversation_id = currentConversationId;
                            }
                            
                            // ✅ Thêm tất cả messages từ polling (cả của mình và người khác)
                            addMessageToChat(msg, msg.sender_id == currentUserId);
                            
                            // ✅ Cập nhật lastMessageId (so sánh số để đảm bảo đúng)
                            const messageIdNum = parseInt(messageId);
                            const lastIdNum = lastMessageId ? parseInt(lastMessageId) : 0;
                            if (!lastMessageId || messageIdNum > lastIdNum) {
                                lastMessageId = messageIdNum;
                            }
                        }
                    });
                    
                    // Scroll to bottom nếu có messages mới
                    if (res.messages.length > 0) {
                        scrollToBottom();
                    }
                }
            }).fail(function() {
                console.log('Failed to check for new messages');
            });
        }
        
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
        
        // ✅ Xóa duplicate socket listeners - đã có ở trên (dòng 2117 và 2150)
        
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
            if (!conversationId || !message) {
                console.warn('updateConversationPreview: Missing conversationId or message', conversationId, message);
                return;
            }
            
            const convEl = $(`.conversation-item[data-conversation-id="${conversationId}"]`);
            if (convEl.length) {
                // ✅ Cập nhật preview message (giới hạn độ dài)
                const previewText = message.length > 50 ? message.substring(0, 50) + '...' : message;
                convEl.find('.conversation-preview').text(previewText);
                
                // ✅ Cập nhật timestamp
                convEl.find('.conversation-time').text(new Date().toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                }));
                
                // ✅ Di chuyển conversation lên đầu danh sách (nếu không phải conversation hiện tại)
                if (conversationId != currentConversationId) {
                    const convItem = convEl.parent();
                    if (convItem.length) {
                        convItem.prependTo('#conversationsList');
                    }
                }
                
                console.log('Conversation preview updated:', conversationId, previewText);
            } else {
                // ✅ Nếu không tìm thấy conversation trong DOM, reload danh sách
                console.log('Conversation not found in DOM, reloading conversations list');
                loadConversations();
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
                // ✅ Chỉ hiển thị nếu có nội dung text (không phải rỗng)
                if (!messageText || messageText.trim() === '') {
                    console.warn('Skipping message with empty text:', m);
                    return '';
                }
                
                const displayText = messageText;
                messageContent = `
                    <div>${escapeHtml(displayText)}</div>
                    <div class="message-time">${time}${isSent?(m.IsRead?' <i class="fas fa-check-double text-primary"></i>':' <i class="fas fa-check text-muted"></i>'):''}</div>
                `;
            }
            
            // ✅ Đảm bảo messageContent không rỗng trước khi tạo HTML
            if (!messageContent || messageContent.trim() === '') {
                console.warn('Skipping message with empty content:', m);
                return '';
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
        
        // ==================== CÁC HÀM CALL (WebRTC + Socket.IO) ====================
        
        // Biến lưu trữ call state
        let currentCall = null;
        let callTimer = null; // Timer để tính giây cuộc gọi
        let callStartTime = null; // Thời gian bắt đầu cuộc gọi
        let pendingOffer = null; // ✅ Lưu offer nếu đến trước khi user accept
        
        /**
         * Khởi tạo cuộc gọi (Voice hoặc Video) với WebRTC
         */
        async function initiateCall(callType) {
            if (!currentConversationId) {
                alert('Vui lòng chọn cuộc trò chuyện trước khi gọi');
                return;
            }
            
            if (!window.WebRTCHelper) {
                alert('WebRTC Helper chưa được load. Vui lòng refresh trang.');
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
                
                // Hiển thị modal cho caller (outgoing call)
                showCallModal('outgoing', response.receiver_name, callType);
                
                // Phát sự kiện call qua socket (receiver sẽ nhận và hiển thị modal với accept/reject buttons)
                if (isConnected && socket && typeof socket.emit === 'function') {
                    // ✅ Lấy tên người gọi từ conversation
                    const conversation = conversations.find(c => c.id == currentConversationId);
                    const callerName = conversation ? conversation.other_user_name : 'Người gọi';
                    
                    socket.emit('call_initiated', {
                        call_id: response.call_id,
                        caller_id: currentUserId,
                        receiver_id: response.receiver_id,
                        call_type: callType,
                        conversation_id: currentConversationId,
                        caller_name: callerName // ✅ Gửi tên caller để receiver hiển thị
                    });
                }
                
                // ✅ QUAN TRỌNG: KHÔNG gửi offer ngay lập tức
                // Chờ receiver accept call trước, sau đó mới gửi offer
                // Offer sẽ được gửi khi caller nhận được 'receiver_accepted' event
                console.log('✅ Call initiated, waiting for receiver to accept before sending offer...');
                
                // ✅ KHÔNG gọi WebRTCHelper.initiateCall() ở đây
                // Sẽ được gọi trong receiver_accepted handler
                
            } catch (error) {
                console.error('❌ Error initiating call:', error);
                console.error('❌ Error details:', {
                    name: error.name,
                    message: error.message,
                    stack: error.stack
                });
                
                // ✅ Xử lý lỗi permission cụ thể
                let errorMessage = 'Lỗi khởi tạo cuộc gọi: ' + (error.message || 'Unknown error');
                
                if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError' || 
                    error.message?.includes('Permission dismissed') || error.message?.includes('permission')) {
                    errorMessage = 'Bạn đã từ chối quyền truy cập camera/microphone.\n\n' +
                                  'Vui lòng:\n' +
                                  '1. Click vào biểu tượng khóa ở thanh địa chỉ\n' +
                                  '2. Cho phép quyền truy cập Camera và Microphone\n' +
                                  '3. Thử lại cuộc gọi';
                } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                    errorMessage = 'Không tìm thấy thiết bị camera/microphone.\n\nVui lòng kiểm tra thiết bị của bạn.';
                } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                    errorMessage = 'Không thể truy cập camera/microphone.\n\nThiết bị có thể đang được sử dụng bởi ứng dụng khác.';
                }
                
                alert(errorMessage);
                
                // ✅ Cleanup khi có lỗi
                $('#callModal').removeClass('show').css('display', 'none');
                currentCall = null;
                if (window.WebRTCHelper) {
                    window.WebRTCHelper.cleanup();
                }
                
                // ✅ End call trên server nếu đã tạo call session
                if (currentCall && currentCall.id) {
                    try {
                        await $.post(getApiPath('src/controllers/call-controller.php?action=end_call'), {
                            call_id: currentCall.id
                        });
                    } catch (e) {
                        console.error('Error ending call on server:', e);
                    }
                }
            }
        }
        
        /**
         * Setup WebRTC event handlers
         */
        function setupWebRTCEventHandlers() {
            if (!socket || !window.WebRTCHelper) {
                console.warn('⚠️ Socket or WebRTC Helper not available');
                return;
            }
            
            // Handle incoming offer (receiver)
            socket.on('call-offer', async (data) => {
                console.log('📞 Received call offer:', data);
                console.log('📞 Checking receiver_id:', data.receiver_id, 'vs currentUserId:', currentUserId);
                console.log('📞 Checking call_id:', data.call_id, 'vs currentCall.id:', currentCall?.id);
                console.log('📞 Current call status:', currentCall?.status);
                
                // ✅ Kiểm tra receiver_id và call_id
                // ✅ QUAN TRỌNG: Chỉ xử lý offer nếu đã accept call (status = 'accepted')
                if (data.receiver_id == currentUserId && currentCall && data.call_id == currentCall.id) {
                    // ✅ QUAN TRỌNG: Chỉ xử lý offer nếu call đã được accept
                    if (currentCall.status !== 'accepted' && currentCall.status !== 'connected') {
                        console.warn('⚠️ Call not accepted yet, saving offer for later. Status:', currentCall.status);
                        console.warn('⚠️ User needs to accept call first before handling offer.');
                        // ✅ Lưu offer để xử lý sau khi accept
                        pendingOffer = data;
                        console.log('✅ Offer saved, will be processed after user accepts call');
                        return;
                    }
                    
                    console.log('✅ Offer is for this user and call is accepted, handling...');
                    try {
                        // ✅ Cập nhật status trong modal
                        $('#callStatus').text('Đang thiết lập kết nối...');
                        
                        // ✅ Đảm bảo currentCall.status = 'accepted' trước khi xử lý offer
                        if (currentCall.status !== 'accepted' && currentCall.status !== 'connected') {
                            console.log('⚠️ Updating call status to accepted before handling offer');
                            currentCall.status = 'accepted';
                        }
                        
                        await window.WebRTCHelper.handleIncomingOffer(data);
                        console.log('✅ Offer handled successfully');
                        
                        // ✅ Sau khi handle offer thành công, đợi connection established
                        // onCallConnected() sẽ được gọi tự động khi connection state = 'connected'
                    } catch (error) {
                        console.error('❌ Error handling incoming offer:', error);
                        console.error('❌ Error stack:', error.stack);
                        
                        // ✅ Xử lý lỗi permission cụ thể
                        let errorMessage = 'Lỗi thiết lập cuộc gọi: ' + (error.message || 'Unknown error');
                        if (error.message && error.message.includes('Permission dismissed')) {
                            errorMessage = error.message;
                        }
                        
                        alert(errorMessage);
                        cleanupCall();
                    }
                } else {
                    console.warn('⚠️ Offer not for this user or no current call');
                    console.warn('⚠️ Receiver ID match:', data.receiver_id == currentUserId);
                    console.warn('⚠️ Call ID match:', data.call_id == currentCall?.id);
                    console.warn('⚠️ Current call:', currentCall);
                    
                    // ✅ Nếu không có currentCall, có thể user chưa accept call
                    // Lưu offer để xử lý sau khi accept (nếu cần)
                    if (!currentCall) {
                        console.warn('⚠️ No current call, offer will be ignored. User needs to accept call first.');
                    }
                }
            });
            
            // Handle incoming answer (caller)
            socket.on('call-answer', async (data) => {
                console.log('📞 Received call answer:', data);
                console.log('📞 Checking call_id:', data.call_id, 'vs currentCall.id:', currentCall?.id);
                
                if (currentCall && data.call_id == currentCall.id) {
                    console.log('✅ Answer is for current call, handling...');
                    try {
                        // ✅ Cập nhật status trong modal
                        $('#callStatus').text('Đang kết nối...');
                        
                        await window.WebRTCHelper.handleIncomingAnswer(data);
                        console.log('✅ Answer handled successfully');
                    } catch (error) {
                        console.error('❌ Error handling incoming answer:', error);
                        // ✅ Không cleanup nếu là InvalidStateError (có thể do answer đã được set rồi)
                        if (error.name === 'InvalidStateError') {
                            console.warn('⚠️ InvalidStateError: Remote description may already be set, continuing...');
                            // Không hiển thị alert hoặc cleanup vì có thể call vẫn hoạt động bình thường
                        } else {
                            alert('Lỗi thiết lập cuộc gọi: ' + (error.message || 'Unknown error'));
                            cleanupCall();
                        }
                    }
                } else {
                    console.warn('⚠️ Answer not for current call');
                }
            });
            
            // Handle ICE candidates
            socket.on('ice-candidate', async (data) => {
                if (currentCall && data.call_id == currentCall.id) {
                    try {
                        await window.WebRTCHelper.handleICECandidate(data);
                    } catch (error) {
                        console.error('❌ Error handling ICE candidate:', error);
                    }
                }
            });
            
            // WebRTC call connected
            window.onCallConnected = function() {
                console.log('✅ WebRTC call connected');
                console.log('✅ Current call:', currentCall);
                console.log('✅ Current call status:', currentCall?.status);
                
                // ✅ Đảm bảo currentCall tồn tại
                if (!currentCall) {
                    console.warn('⚠️ No current call, cannot switch UI');
                    return;
                }
                
                // ✅ QUAN TRỌNG: Chỉ chuyển UI nếu call đã được accept (status = 'accepted' hoặc 'connected')
                // Nếu status vẫn là 'ringing', có thể user chưa accept call
                if (currentCall.status !== 'accepted' && currentCall.status !== 'connected') {
                    console.warn('⚠️ Call not accepted yet, status:', currentCall.status);
                    console.warn('⚠️ Waiting for call to be accepted...');
                    // ✅ Cập nhật status thành 'accepted' nếu đang là 'ringing' (fallback)
                    if (currentCall.status === 'ringing') {
                        console.log('⚠️ Call status is ringing, updating to accepted (fallback)');
                        currentCall.status = 'accepted';
                    } else {
                        return; // Không chuyển UI nếu status không hợp lệ
                    }
                }
                
                // ✅ Cập nhật status thành 'connected'
                currentCall.status = 'connected';
                
                // ✅ QUAN TRỌNG: Reset mute/camera state về trạng thái ban đầu khi call được kết nối
                resetMuteCameraState();
                
                // ✅ Bắt đầu timer tính giây cuộc gọi
                startCallTimer();
                
                if (currentCall.type === 'video') {
                    console.log('✅ Switching to video call UI');
                    // ✅ Đóng modal call
                    $('#callModal').removeClass('show').css('display', 'none');
                    
                    // ✅ Hiển thị video call container
                    const videoContainer = $('#videoCallContainer');
                    videoContainer.addClass('show').css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1',
                        'z-index': '10000',
                        'position': 'fixed',
                        'top': '0',
                        'left': '0',
                        'width': '100%',
                        'height': '100%',
                        'background': '#000'
                    });
                    
                    // ✅ Đảm bảo nút end call trong video container hoạt động
                    // Gắn lại event listener cho nút end call (vì có thể HTML đã được tạo động)
                    setTimeout(() => {
                        $(document).off('click.endVideoCall', '#endVideoCallBtn').on('click.endVideoCall', '#endVideoCallBtn', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            console.log('📞 End video call button clicked (from onCallConnected)');
                            
                            if (typeof endVideoCall === 'function') {
                                endVideoCall();
                            } else if (typeof endCall === 'function') {
                                endCall();
                            } else if (typeof window.endCall === 'function') {
                                window.endCall();
                            } else {
                                console.error('❌ endCall/endVideoCall function not found');
                                alert('Lỗi: Hàm endCall không tìm thấy. Vui lòng refresh trang.');
                            }
                        });
                    }, 100);
                    
                    console.log('✅ Video call UI displayed');
                } else {
                    console.log('✅ Switching to voice call UI with timer');
                    // ✅ Cập nhật modal để hiển thị timer thay vì "Đang gọi..."
                    const conversation = conversations.find(c => c.id == currentConversationId);
                    const otherUserName = conversation ? conversation.other_user_name : (currentCall?.caller_name || 'Người gọi');
                    
                    $('#callerName').text(otherUserName);
                    $('#callType').text('Cuộc gọi thoại');
                    $('#callStatus').text('00:00'); // ✅ Timer sẽ tự động cập nhật
                    $('#callControls').html(`
                        <button class="btn btn-danger btn-lg" id="endCallBtnVoice" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; cursor: pointer;">
                            <i class="fas fa-phone-slash"></i>
                        </button>
                    `);
                    
                    // ✅ Gắn event listener cho nút end call (thay vì dùng onclick)
                    // ✅ QUAN TRỌNG: Sử dụng event delegation để đảm bảo hoạt động ngay cả khi element được tạo động
                    $(document).off('click.endCallVoice', '#endCallBtnVoice').on('click.endCallVoice', '#endCallBtnVoice', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('📞 End call button clicked (voice call, from onCallConnected)');
                        
                        if (typeof endCall === 'function') {
                            endCall();
                        } else if (typeof window.endCall === 'function') {
                            window.endCall();
                        } else {
                            console.error('❌ endCall function not found');
                            alert('Lỗi: Hàm endCall không tìm thấy. Vui lòng refresh trang.');
                        }
                    });
                    
                    // ✅ Đảm bảo modal vẫn hiển thị
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
                    
                    $('#videoCallContainer').hide();
                }
            };
            
            // WebRTC call disconnected
            window.onCallDisconnected = function() {
                console.log('📞 WebRTC call disconnected');
                cleanupCall();
            };
            
            // WebRTC call failed
            window.onCallFailed = function(message) {
                console.error('❌ WebRTC call failed:', message);
                alert('Cuộc gọi thất bại: ' + message);
                cleanupCall();
            };
            
            // Local stream handler
            window.onLocalStream = function(stream) {
                console.log('✅ Local stream:', stream);
                const localVideo = document.getElementById('localVideo');
                if (localVideo && stream.getVideoTracks().length > 0) {
                    // ✅ QUAN TRỌNG: Không play() lại ở đây vì đã play trong webrtc-helper.js
                    // Chỉ set srcObject nếu chưa được set để tránh AbortError
                    if (localVideo.srcObject !== stream) {
                        localVideo.srcObject = stream;
                    }
                }
                
                // ✅ Xử lý local audio nếu có
                const localAudio = document.getElementById('localAudio');
                if (localAudio && stream.getAudioTracks().length > 0) {
                    // ✅ QUAN TRỌNG: Không play() lại ở đây vì đã play trong webrtc-helper.js
                    if (localAudio.srcObject !== stream) {
                        localAudio.srcObject = stream;
                    }
                }
            };
            
            // Remote stream handler
            window.onRemoteStream = function(stream) {
                console.log('✅ Remote stream:', stream);
                const remoteVideo = document.getElementById('remoteVideo');
                if (remoteVideo && stream.getVideoTracks().length > 0) {
                    // ✅ QUAN TRỌNG: Không play() lại ở đây vì đã play trong webrtc-helper.js
                    // Chỉ set srcObject nếu chưa được set để tránh AbortError
                    if (remoteVideo.srcObject !== stream) {
                        remoteVideo.srcObject = stream;
                    }
                    // ✅ Chỉ show UI, không play() lại
                    $('#videoCallContainer').addClass('show').css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1',
                        'z-index': '10000'
                    });
                }
                
                // ✅ QUAN TRỌNG: Không play() audio ở đây vì đã play trong webrtc-helper.js
                // Chỉ set srcObject nếu chưa được set để tránh AbortError
                const remoteAudio = document.getElementById('remoteAudio');
                if (remoteAudio && stream.getAudioTracks().length > 0) {
                    if (remoteAudio.srcObject !== stream) {
                        remoteAudio.srcObject = stream;
                    }
                }
            };
        }
        
        /**
         * Cleanup call
         */
        function cleanupCall() {
            // ✅ Dừng timer cuộc gọi
            stopCallTimer();
            
            $('#callModal').removeClass('show').css('display', 'none');
            $('#videoCallContainer').removeClass('show').css({
                'display': 'none',
                'visibility': 'hidden',
                'opacity': '0'
            });
            
            if (window.WebRTCHelper) {
                window.WebRTCHelper.cleanup();
            }
            
            currentCall = null;
            pendingOffer = null; // ✅ Xóa pending offer khi cleanup
        }
        
        /**
         * Hiển thị modal cuộc gọi
         */
        function showCallModal(type, name, callType) {
            $('#callerName').text(name);
            $('#callType').text(callType === 'video' ? 'Cuộc gọi video' : 'Cuộc gọi thoại');
            
            if (type === 'incoming') {
                $('#callStatus').text('Cuộc gọi đến...');
                $('#callControls').html(`
                    <button class="btn btn-success btn-lg me-2" id="acceptCallBtn" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; cursor: pointer;">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="btn btn-danger btn-lg" id="rejectCallBtn" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; cursor: pointer;">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                `);
                
                // ✅ Đảm bảo các hàm có sẵn trong global scope
                if (typeof window.acceptCall === 'undefined') {
                    window.acceptCall = acceptCall;
                }
                if (typeof window.rejectCall === 'undefined') {
                    window.rejectCall = rejectCall;
                }
                
                // ✅ Sử dụng event listener thay vì onclick để tránh vấn đề scope
                // ✅ Sử dụng event delegation với namespace để đảm bảo hoạt động ngay cả khi element được tạo động
                // ✅ QUAN TRỌNG: Sử dụng namespace để tránh conflict với các event listeners khác
                $(document).off('click.acceptCall', '#acceptCallBtn').on('click.acceptCall', '#acceptCallBtn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation(); // ✅ Ngăn các event handlers khác xử lý
                    
                    console.log('📞 ========== Accept button clicked (event delegation) ==========');
                    console.log('📞 Button element:', this);
                    console.log('📞 Current call:', currentCall);
                    console.log('📞 Is button disabled?', $(this).prop('disabled'));
                    
                    // ✅ Kiểm tra nếu button đã bị disable (đã được click)
                    if ($(this).prop('disabled')) {
                        console.warn('⚠️ Accept button already clicked, ignoring...');
                        return;
                    }
                    
                    // ✅ Disable button ngay lập tức để tránh double click
                    $(this).prop('disabled', true);
                    
                    // ✅ Gọi hàm acceptCall trực tiếp với error handling tốt hơn
                    try {
                        if (typeof acceptCall === 'function') {
                            console.log('✅ Calling acceptCall function directly');
                            // ✅ Gọi hàm và xử lý cả promise rejection và synchronous errors
                            const result = acceptCall();
                            if (result && typeof result.catch === 'function') {
                                result.catch(err => {
                                    console.error('❌ Error in acceptCall promise:', err);
                                    // ✅ Re-enable button nếu có lỗi
                                    $(this).prop('disabled', false);
                                    alert('Lỗi khi chấp nhận cuộc gọi: ' + (err.message || 'Unknown error'));
                                });
                            }
                        } else if (typeof window.acceptCall === 'function') {
                            console.log('✅ Calling window.acceptCall function');
                            const result = window.acceptCall();
                            if (result && typeof result.catch === 'function') {
                                result.catch(err => {
                                    console.error('❌ Error in window.acceptCall promise:', err);
                                    // ✅ Re-enable button nếu có lỗi
                                    $(this).prop('disabled', false);
                                    alert('Lỗi khi chấp nhận cuộc gọi: ' + (err.message || 'Unknown error'));
                                });
                            }
                        } else {
                            console.error('❌ acceptCall function not found');
                            console.error('❌ Available functions:', {
                                acceptCall: typeof acceptCall,
                                windowAcceptCall: typeof window.acceptCall,
                                rejectCall: typeof rejectCall,
                                windowRejectCall: typeof window.rejectCall
                            });
                            // ✅ Re-enable button nếu có lỗi
                            $(this).prop('disabled', false);
                            alert('Lỗi: Hàm acceptCall không tìm thấy. Vui lòng refresh trang.');
                        }
                    } catch (syncError) {
                        // ✅ Bắt synchronous errors (không phải promise rejection)
                        console.error('❌ Synchronous error calling acceptCall:', syncError);
                        console.error('❌ Error stack:', syncError.stack);
                        // ✅ Re-enable button nếu có lỗi
                        $(this).prop('disabled', false);
                        alert('Lỗi khi gọi hàm acceptCall: ' + (syncError.message || 'Unknown error'));
                    }
                });
                
                // ✅ Sử dụng event delegation cho reject button
                $(document).off('click', '#rejectCallBtn').on('click', '#rejectCallBtn', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📞 Reject button clicked (event delegation)');
                    
                    if (typeof rejectCall === 'function') {
                        rejectCall();
                    } else if (typeof window.rejectCall === 'function') {
                        window.rejectCall();
                    } else {
                        console.error('❌ rejectCall function not found');
                        alert('Lỗi: Hàm rejectCall không tìm thấy. Vui lòng refresh trang.');
                    }
                });
                
                console.log('✅ Accept/Reject buttons created with event listeners, functions available:', {
                    acceptCall: typeof acceptCall,
                    windowAcceptCall: typeof window.acceptCall,
                    rejectCall: typeof rejectCall,
                    windowRejectCall: typeof window.rejectCall,
                    currentCall: currentCall
                });
            } else {
                $('#callStatus').text('Đang gọi...');
                $('#callControls').html(`
                    <button class="btn btn-danger btn-lg" id="endCallBtnOutgoing" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; cursor: pointer;">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                `);
                
                // ✅ Gắn event listener cho nút end call (thay vì dùng onclick)
                $(document).off('click.endCallOutgoing', '#endCallBtnOutgoing').on('click.endCallOutgoing', '#endCallBtnOutgoing', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📞 End call button clicked (outgoing call)');
                    
                    if (typeof endCall === 'function') {
                        endCall();
                    } else if (typeof window.endCall === 'function') {
                        window.endCall();
                    } else {
                        console.error('❌ endCall function not found');
                        alert('Lỗi: Hàm endCall không tìm thấy. Vui lòng refresh trang.');
                    }
                });
            }
            
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
        
        /**
         * Chấp nhận cuộc gọi
         */
        async function acceptCall() {
            // ✅ Wrap toàn bộ hàm trong try-catch để bắt mọi lỗi
            try {
                console.log('📞 ========== acceptCall() FUNCTION CALLED ==========');
                console.log('📞 Current call:', currentCall);
                
                if (!currentCall) {
                    console.error('❌ No current call to accept');
                    alert('Không có cuộc gọi để chấp nhận');
                    return;
                }
                
                if (!window.WebRTCHelper) {
                    console.error('❌ WebRTC Helper not available');
                    alert('WebRTC Helper chưa được load. Vui lòng refresh trang.');
                    return;
                }
                
                console.log('📞 Accepting call:', currentCall);
                // ✅ Đảm bảo call_id là number (không phải string)
                const callId = parseInt(currentCall.id);
                if (isNaN(callId)) {
                    console.error('❌ Invalid call ID:', currentCall.id);
                    alert('ID cuộc gọi không hợp lệ');
                    return;
                }
                
                console.log('📞 Sending accept_call request with call_id:', callId);
                
                // Accept call trên server
                const response = await $.ajax({
                    url: getApiPath('src/controllers/call-controller.php?action=accept_call'),
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        call_id: callId
                    },
                    timeout: 10000
                });
                
                console.log('📞 Accept call response:', response);
                
                if (!response || !response.success) {
                    const errorMsg = response?.error || 'Unknown error';
                    console.error('❌ Accept call failed:', errorMsg);
                    alert('Lỗi chấp nhận cuộc gọi: ' + errorMsg);
                    
                    // ✅ Nếu call không tồn tại, cleanup và đóng modal
                    if (errorMsg.includes('không tồn tại') || errorMsg.includes('không thể chấp nhận')) {
                        console.warn('⚠️ Call not found, cleaning up...');
                        cleanupCall();
                    }
                    return;
                }
                
                console.log('✅ Call accepted successfully on server');
                
                // ✅ Đảm bảo currentCall tồn tại trước khi cập nhật
                if (!currentCall) {
                    console.error('❌ currentCall is null when updating call info');
                    return;
                }
                
                // ✅ Cập nhật currentCall với thông tin từ server
                if (response.call_id) {
                    currentCall.id = response.call_id;
                }
                if (response.caller_id) {
                    currentCall.caller_id = response.caller_id;
                }
                if (response.call_type) {
                    currentCall.type = response.call_type;
                }
                // ✅ QUAN TRỌNG: Cập nhật status thành 'accepted' để onCallConnected() có thể chuyển UI
                currentCall.status = 'accepted';
                
                console.log('✅ Current call status updated to:', currentCall.status);
                
                // Phát sự kiện accept qua socket
                if (isConnected && socket && typeof socket.emit === 'function' && currentCall) {
                    // ✅ Đảm bảo caller_id có giá trị (tránh null reference)
                    const callerId = currentCall.caller_id || currentCall.receiver_id || currentUserId;
                    
                    console.log('📞 Emitting call_accepted event:', {
                        call_id: currentCall.id,
                        caller_id: callerId,
                        receiver_id: currentUserId
                    });
                    
                    socket.emit('call_accepted', {
                        call_id: currentCall.id,
                        caller_id: callerId,
                        receiver_id: currentUserId
                    });
                }
                
                // ✅ QUAN TRỌNG: Nếu có pending offer (offer đến trước khi accept), xử lý ngay
                if (pendingOffer && pendingOffer.call_id == currentCall.id) {
                    console.log('✅ Found pending offer, processing now...');
                    try {
                        $('#callStatus').text('Đang thiết lập kết nối...');
                        await window.WebRTCHelper.handleIncomingOffer(pendingOffer);
                        console.log('✅ Pending offer handled successfully');
                        pendingOffer = null; // ✅ Xóa pending offer sau khi xử lý
                    } catch (error) {
                        console.error('❌ Error handling pending offer:', error);
                        alert('Lỗi thiết lập cuộc gọi: ' + (error.message || 'Unknown error'));
                        cleanupCall();
                    }
                }
                
                // ✅ KHÔNG đóng modal ngay - đợi đến khi call connected
                // Modal sẽ được đóng trong onCallConnected() khi WebRTC connection established
                // Chỉ cập nhật status trong modal
                $('#callStatus').text('Đang kết nối...');
                
                // ✅ Disable nút accept/reject để tránh click nhiều lần
                $('#callControls button').prop('disabled', true);
                
                // ✅ QUAN TRỌNG: Đợi một chút để đảm bảo socket event được emit
                // Sau đó caller sẽ nhận receiver_accepted và gửi lại offer
                console.log('✅ Call accepted, waiting for caller to resend offer...');
                
                // ✅ Fallback: Nếu không nhận được offer sau 2 giây, log warning
                setTimeout(() => {
                    if (currentCall && currentCall.status === 'accepted') {
                        console.warn('⚠️ Still waiting for offer from caller after 2 seconds...');
                    }
                }, 2000);
                
            } catch (error) {
                console.error('❌ Error accepting call:', error);
                console.error('❌ Error details:', {
                    message: error.message,
                    status: error.status,
                    responseText: error.responseText
                });
                
                let errorMessage = 'Lỗi: ' + (error.message || 'Unknown error');
                
                // ✅ Xử lý lỗi chi tiết hơn
                if (error.responseJSON && error.responseJSON.error) {
                    errorMessage = error.responseJSON.error;
                } else if (error.responseText) {
                    try {
                        const errorResponse = JSON.parse(error.responseText);
                        if (errorResponse.error) {
                            errorMessage = errorResponse.error;
                        }
                    } catch (e) {
                        // Không phải JSON, giữ nguyên error message
                    }
                }
                
                // ✅ Hiển thị lỗi chi tiết hơn
                console.error('❌ Full error object:', error);
                console.error('❌ Error response:', error.responseJSON || error.responseText);
                
                alert('Lỗi chấp nhận cuộc gọi: ' + errorMessage);
                
                // ✅ Cleanup nếu có lỗi
                if (error.status === 404 || errorMessage.includes('không tồn tại')) {
                    cleanupCall();
                } else {
                    // ✅ Nếu không phải lỗi 404, vẫn giữ modal để user có thể thử lại
                    $('#callControls button').prop('disabled', false);
                }
            }
        }
        
        /**
         * Từ chối cuộc gọi
         */
        function rejectCall() {
            console.log('📞 rejectCall() called');
            if (!currentCall) {
                cleanupCall();
                return;
            }
            
            // ✅ Lưu các giá trị cần thiết trước khi cleanup (vì cleanup sẽ set currentCall = null)
            const callId = currentCall.id;
            const callerId = currentCall.caller_id || currentCall.receiver_id || currentUserId;
            
            if (window.WebRTCHelper) {
                window.WebRTCHelper.cleanup();
            }
            
            $.post(getApiPath('src/controllers/call-controller.php?action=reject_call'), {
                call_id: callId
            }, function(response) {
                cleanupCall();
                // ✅ Sử dụng callerId đã lưu trước đó, không đọc từ currentCall (đã null)
                if (isConnected && socket && typeof socket.emit === 'function') {
                    socket.emit('call_rejected', {
                        call_id: callId,
                        caller_id: callerId,
                        receiver_id: currentUserId
                    });
                }
            }, 'json').fail(function() {
                cleanupCall();
                // ✅ Vẫn gửi socket event ngay cả khi server request fail
                if (isConnected && socket && typeof socket.emit === 'function') {
                    socket.emit('call_rejected', {
                        call_id: callId,
                        caller_id: callerId,
                        receiver_id: currentUserId
                    });
                }
            });
        }
        
        /**
         * Kết thúc cuộc gọi
         */
        function endCall() {
            console.log('📞 endCall() called');
            const callId = currentCall ? currentCall.id : null;
            
            if (window.WebRTCHelper) {
                window.WebRTCHelper.endCall();
            }
            
            cleanupCall();
            
            if (callId) {
                $.post(getApiPath('src/controllers/call-controller.php?action=end_call'), {
                    call_id: callId
                }, function(response) {
                    // ✅ Hiển thị message ngay lập tức nếu có
                    if (response.success && response.chat_message) {
                        console.log('📞 Adding call end message to chat:', response.chat_message);
                        addMessageToChat(response.chat_message, response.chat_message.sender_id == currentUserId);
                        scrollToBottom();
                        
                        // ✅ Cập nhật preview
                        const messageText = response.chat_message.message || '[Cuộc gọi]';
                        updateConversationPreview(currentConversationId, messageText);
                    }
                    
                    if (isConnected && socket && typeof socket.emit === 'function') {
                        socket.emit('call_ended', {
                            call_id: callId,
                            caller_id: currentUserId
                        });
                    }
                }, 'json').fail(function() {
                    if (isConnected && socket && typeof socket.emit === 'function') {
                        socket.emit('call_ended', {
                            call_id: callId,
                            caller_id: currentUserId
                        });
                    }
                });
            }
        }
        
        // ✅ Expose các hàm ngay sau khi định nghĩa
        // Đảm bảo các hàm có sẵn ngay lập tức, không cần đợi $(document).ready()
        window.acceptCall = acceptCall;
        window.rejectCall = rejectCall;
        window.endCall = endCall;
        console.log('✅ Call functions exposed to window object immediately after definition');
        
        /**
         * Hiển thị UI cuộc gọi thoại
         */
        function showVoiceCallUI() {
            const conversation = conversations.find(c => c.id == currentConversationId);
            const otherUserName = conversation ? conversation.other_user_name : (currentCall?.caller_name || 'Người gọi');
            
            $('#callerName').text(otherUserName);
            $('#callType').text('Cuộc gọi thoại');
            $('#callStatus').text('00:00'); // ✅ Hiển thị timer bắt đầu từ 00:00
            $('#callControls').html(`
                <button class="btn btn-danger btn-lg" id="endCallBtnVoiceUI" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; cursor: pointer;">
                    <i class="fas fa-phone-slash"></i>
                </button>
            `);
            
            // ✅ Gắn event listener cho nút end call (thay vì dùng onclick)
            $(document).off('click.endCallVoiceUI', '#endCallBtnVoiceUI').on('click.endCallVoiceUI', '#endCallBtnVoiceUI', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('📞 End call button clicked (voice call UI)');
                
                if (typeof endCall === 'function') {
                    endCall();
                } else if (typeof window.endCall === 'function') {
                    window.endCall();
                } else {
                    console.error('❌ endCall function not found');
                    alert('Lỗi: Hàm endCall không tìm thấy. Vui lòng refresh trang.');
                }
            });
            
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
            
            $('#videoCallContainer').hide();
        }
        
        /**
         * Bắt đầu timer tính giây cuộc gọi
         */
        function startCallTimer() {
            // ✅ Dừng timer cũ nếu có
            if (callTimer) {
                clearInterval(callTimer);
                callTimer = null;
            }
            
            // ✅ Lưu thời gian bắt đầu
            callStartTime = Date.now();
            
            console.log('⏱️ Starting call timer');
            
            // ✅ Cập nhật timer mỗi giây
            callTimer = setInterval(function() {
                // ✅ QUAN TRỌNG: Tự động dừng timer nếu call đã kết thúc
                if (!callStartTime || !currentCall) {
                    stopCallTimer();
                    return;
                }
                
                const elapsed = Math.floor((Date.now() - callStartTime) / 1000); // Thời gian đã trôi qua (giây)
                const minutes = Math.floor(elapsed / 60);
                const seconds = elapsed % 60;
                
                // ✅ Format: MM:SS
                const timeString = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                
                // ✅ Cập nhật UI
                $('#callStatus').text(timeString);
                
                console.log('⏱️ Call duration:', timeString);
            }, 1000);
        }
        
        /**
         * Dừng timer cuộc gọi
         */
        function stopCallTimer() {
            if (callTimer) {
                clearInterval(callTimer);
                callTimer = null;
            }
            callStartTime = null;
            console.log('⏱️ Call timer stopped');
        }
        
        /**
         * Toggle mute
         */
        /**
         * Reset mute/camera state về trạng thái ban đầu (đều bật)
         */
        function resetMuteCameraState() {
            // ✅ Reset state variables
            isMuted = false;
            isCameraOff = false;
            
            // ✅ Reset UI icons về trạng thái ban đầu (mic và camera đều bật)
            const muteIcon = $('#muteBtn i');
            if (muteIcon.length) {
                muteIcon.removeClass('fa-microphone-slash').addClass('fa-microphone');
            }
            
            const cameraIcon = $('#cameraBtn i');
            if (cameraIcon.length) {
                cameraIcon.removeClass('fa-video-slash').addClass('fa-video');
            }
            
            console.log('✅ Mute/Camera state reset to default (both enabled)');
        }
        
        /**
         * Toggle mute
         */
        function toggleMute() {
            if (window.WebRTCHelper && window.WebRTCHelper.toggleMute) {
                isMuted = window.WebRTCHelper.toggleMute();
                const icon = $('#muteBtn i');
                icon.toggleClass('fa-microphone fa-microphone-slash');
            }
        }
        
        /**
         * Toggle camera
         */
        function toggleCamera() {
            if (window.WebRTCHelper && window.WebRTCHelper.toggleCamera) {
                isCameraOff = window.WebRTCHelper.toggleCamera();
                const icon = $('#cameraBtn i');
                icon.toggleClass('fa-video fa-video-slash');
            }
        }
        
        /**
         * End video call
         */
        function endVideoCall() {
            endCall();
        }
        
        /**
         * Socket events for calls
         */
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
                    console.log('📞 Received call_initiated event:', data);
                    console.log('📞 Checking receiver_id:', data.receiver_id, 'vs currentUserId:', currentUserId);
                    console.log('📞 Type comparison:', typeof data.receiver_id, typeof currentUserId);
                    console.log('📞 Conversation ID:', data.conversation_id);
                    
                    // ✅ Đảm bảo call_id là number
                    const callId = parseInt(data.call_id);
                    if (isNaN(callId)) {
                        console.error('❌ Invalid call_id in call_initiated event:', data.call_id);
                        return;
                    }
                    
                    // Use == instead of === to handle string/number mismatch
                    if (data.receiver_id == currentUserId || String(data.receiver_id) === String(currentUserId)) {
                        console.log('✅ Call is for this user, showing modal');
                        
                        // ✅ Đảm bảo currentCall được set đúng với tất cả thông tin cần thiết
                        currentCall = {
                            id: callId, // ✅ Dùng parsed number
                            type: data.call_type || 'voice',
                            caller_id: parseInt(data.caller_id) || data.caller_id, // ✅ Đảm bảo là number
                            receiver_id: parseInt(currentUserId), // ✅ Đảm bảo là number
                            conversation_id: parseInt(data.conversation_id) || data.conversation_id,
                            status: 'ringing',
                            caller_name: data.caller_name || 'Người gọi' // ✅ Lưu tên caller nếu có
                        };
                        
                        console.log('📞 currentCall set to:', currentCall);
                        
                        // Lấy tên người gọi từ conversation hoặc event data
                        const conversation = conversations.find(c => c.id == data.conversation_id);
                        const callerName = data.caller_name || (conversation ? conversation.other_user_name : 'Người gọi');
                        
                        console.log('📞 Showing call modal for:', callerName);
                        console.log('📞 Call type:', data.call_type);
                        
                        // WebRTC sẽ được init khi receiver accept call
                        // Không cần init ở đây
                        
                        // Show modal with accept/reject buttons
                        showCallModal('incoming', callerName, data.call_type);
                        
                        // ✅ Đảm bảo event listeners được gắn lại sau khi show modal
                        // Vì showCallModal có thể tạo lại HTML, cần gắn lại listeners
                        setTimeout(() => {
                            // ✅ Gắn lại event listeners cho accept/reject buttons
                            // ✅ Sử dụng event delegation với namespace để đảm bảo hoạt động
                            $(document).off('click.acceptCall', '#acceptCallBtn').on('click.acceptCall', '#acceptCallBtn', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation(); // ✅ Ngăn các event handlers khác xử lý
                                
                                console.log('📞 ========== Accept button clicked (from call_initiated, event delegation) ==========');
                                console.log('📞 Button element:', this);
                                console.log('📞 Current call:', currentCall);
                                console.log('📞 Is button disabled?', $(this).prop('disabled'));
                                
                                // ✅ Kiểm tra nếu button đã bị disable (đã được click)
                                if ($(this).prop('disabled')) {
                                    console.warn('⚠️ Accept button already clicked, ignoring...');
                                    return;
                                }
                                
                                // ✅ Disable button ngay lập tức để tránh double click
                                $(this).prop('disabled', true);
                                
                                // ✅ Gọi hàm acceptCall với error handling tốt hơn
                                try {
                                    if (typeof acceptCall === 'function') {
                                        console.log('✅ Calling acceptCall function directly');
                                        const result = acceptCall();
                                        if (result && typeof result.catch === 'function') {
                                            result.catch(err => {
                                                console.error('❌ Error in acceptCall promise:', err);
                                                // ✅ Re-enable button nếu có lỗi
                                                $(this).prop('disabled', false);
                                                alert('Lỗi khi chấp nhận cuộc gọi: ' + (err.message || 'Unknown error'));
                                            });
                                        }
                                    } else if (typeof window.acceptCall === 'function') {
                                        console.log('✅ Calling window.acceptCall function');
                                        const result = window.acceptCall();
                                        if (result && typeof result.catch === 'function') {
                                            result.catch(err => {
                                                console.error('❌ Error in window.acceptCall promise:', err);
                                                // ✅ Re-enable button nếu có lỗi
                                                $(this).prop('disabled', false);
                                                alert('Lỗi khi chấp nhận cuộc gọi: ' + (err.message || 'Unknown error'));
                                            });
                                        }
                                    } else {
                                        console.error('❌ acceptCall function not found');
                                        // ✅ Re-enable button nếu có lỗi
                                        $(this).prop('disabled', false);
                                        alert('Lỗi: Hàm acceptCall không tìm thấy. Vui lòng refresh trang.');
                                    }
                                } catch (syncError) {
                                    // ✅ Bắt synchronous errors
                                    console.error('❌ Synchronous error calling acceptCall:', syncError);
                                    console.error('❌ Error stack:', syncError.stack);
                                    // ✅ Re-enable button nếu có lỗi
                                    $(this).prop('disabled', false);
                                    alert('Lỗi khi gọi hàm acceptCall: ' + (syncError.message || 'Unknown error'));
                                }
                            });
                            
                            // ✅ Sử dụng event delegation cho reject button
                            $(document).off('click', '#rejectCallBtn').on('click', '#rejectCallBtn', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                console.log('📞 Reject button clicked (from call_initiated, event delegation)');
                                
                                if (typeof rejectCall === 'function') {
                                    rejectCall();
                                } else if (typeof window.rejectCall === 'function') {
                                    window.rejectCall();
                                } else {
                                    console.error('❌ rejectCall function not found');
                                    alert('Lỗi: Hàm rejectCall không tìm thấy. Vui lòng refresh trang.');
                                }
                            });
                        }, 100);
                        
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
                
                // Call accepted - WebRTC sẽ tự động kết nối khi cả 2 bên exchange offer/answer
                socket.on('call_accepted', function(data) {
                    console.log('📞 ========== call_accepted EVENT RECEIVED ==========');
                    console.log('📞 Call accepted data:', data);
                    console.log('📞 Current call:', currentCall);
                    console.log('📞 Current user ID:', currentUserId);
                    console.log('📞 Caller ID from event:', data.caller_id);
                    console.log('📞 Call ID from event:', data.call_id);
                    
                    // ✅ QUAN TRỌNG: Nếu caller (người gọi) nhận được event này, cập nhật status
                    if (data.caller_id == currentUserId && currentCall && data.call_id == currentCall.id) {
                        console.log('✅ Call accepted by receiver, updating caller UI');
                        // ✅ Cập nhật status để onCallConnected() có thể chuyển UI
                        currentCall.status = 'accepted';
                        console.log('✅ Current call status updated to:', currentCall.status);
                    } else {
                        console.warn('⚠️ call_accepted event not for this caller or call');
                        console.warn('⚠️ Conditions check:', {
                            callerIdMatch: data.caller_id == currentUserId,
                            hasCurrentCall: !!currentCall,
                            callIdMatch: data.call_id == currentCall?.id
                        });
                    }
                    
                    // ✅ WebRTC: Logic đã được xử lý trong acceptCall và WebRTC handlers
                    // WebRTC call sẽ được connected qua offer/answer exchange
                    // Note: receiver_accepted event sẽ trigger resending offer
                });
                
                // Receiver accepted - Gửi offer sau khi receiver accept
                socket.on('receiver_accepted', async function(data) {
                    console.log('📞 ========== receiver_accepted EVENT RECEIVED ==========');
                    console.log('📞 Receiver accepted data:', data);
                    console.log('📞 Current call:', currentCall);
                    console.log('📞 Current user ID:', currentUserId);
                    console.log('📞 Caller ID from event:', data.caller_id);
                    console.log('📞 Call ID from event:', data.call_id);
                    
                    // ✅ QUAN TRỌNG: Nếu caller nhận được event này, gửi offer
                    // Kiểm tra cả caller_id và call_id để đảm bảo đúng call
                    const isForThisCaller = data.caller_id == currentUserId || String(data.caller_id) === String(currentUserId);
                    const isForThisCall = currentCall && (data.call_id == currentCall.id || String(data.call_id) === String(currentCall.id));
                    
                    console.log('📞 Event check:', {
                        isForThisCaller: isForThisCaller,
                        isForThisCall: isForThisCall,
                        callerIdFromEvent: data.caller_id,
                        currentUserId: currentUserId,
                        callIdFromEvent: data.call_id,
                        currentCallId: currentCall?.id
                    });
                    
                    if (isForThisCaller && isForThisCall) {
                        console.log('✅ Receiver accepted, sending WebRTC offer...');
                        console.log('✅ Call details:', {
                            callId: currentCall.id,
                            callType: currentCall.type,
                            receiverId: currentCall.receiver_id
                        });
                        
                        // ✅ Gửi offer sau khi receiver accept
                        if (window.WebRTCHelper) {
                            try {
                                // ✅ QUAN TRỌNG: Reset mute/camera state trước khi gửi offer
                                resetMuteCameraState();
                                
                                console.log('📞 Calling WebRTCHelper.initiateCall to send offer...');
                                await window.WebRTCHelper.initiateCall(
                                    currentCall.id,
                                    currentCall.type,
                                    currentCall.receiver_id
                                );
                                console.log('✅ Offer sent successfully');
                            } catch (error) {
                                console.error('❌ Error sending offer:', error);
                                console.error('❌ Error stack:', error.stack);
                                alert('Lỗi gửi offer: ' + (error.message || 'Unknown error'));
                            }
                        } else {
                            console.error('❌ WebRTCHelper not available');
                        }
                    } else {
                        console.warn('⚠️ receiver_accepted event not for this caller or call');
                        console.warn('⚠️ Conditions check:', {
                            callerIdMatch: isForThisCaller,
                            hasCurrentCall: !!currentCall,
                            callIdMatch: isForThisCall
                        });
                    }
                });
                
                // Call rejected
                socket.on('call_rejected', data => {
                    console.log('📞 Received call_rejected event:', data);
                    console.log('📞 Current user ID:', currentUserId);
                    console.log('📞 Caller ID:', data.caller_id);
                    console.log('📞 Receiver ID:', data.receiver_id);
                    
                    // ✅ QUAN TRỌNG: Cleanup nếu là caller (người gọi) - khi receiver (admin) reject
                    // Hoặc nếu là receiver (người nhận) - khi caller reject
                    const isCaller = data.caller_id == currentUserId;
                    const isReceiver = data.receiver_id == currentUserId;
                    
                    if (isCaller || isReceiver) {
                        console.log('✅ Call rejected, cleaning up UI');
                        // ✅ Dừng timer nếu có
                        stopCallTimer();
                        
                        // ✅ Ẩn modal và video container
                        $('#callModal').removeClass('show').css('display', 'none');
                        $('#videoCallContainer').removeClass('show').css({
                            'display': 'none',
                            'visibility': 'hidden',
                            'opacity': '0'
                        });
                        
                        // ✅ Cleanup WebRTC
                        if (window.WebRTCHelper) {
                            window.WebRTCHelper.cleanup();
                        }
                        
                        // ✅ Reset currentCall
                        currentCall = null;
                        
                        // ✅ Hiển thị thông báo
                        showNotification(data.message || 'Cuộc gọi bị từ chối', 'warning', 'fa-times-circle');
                        console.log('✅ Call rejected cleanup completed');
                    } else {
                        console.log('⚠️ Call rejected event not for this user, ignoring');
                    }
                });
                
                // Call ended
                socket.on('call_ended', data => {
                    console.log('📞 Received call_ended event:', data);
                    
                    // ✅ QUAN TRỌNG: Dừng timer TRƯỚC khi cleanup
                    stopCallTimer();
                    
                    // QUAN TRỌNG: Cleanup đầy đủ khi bên kia tắt cuộc gọi
                    // Ẩn modal và video container
                    $('#callModal').removeClass('show').css('display', 'none');
                    $('#videoCallContainer').removeClass('show').css({
                        'display': 'none',
                        'visibility': 'hidden',
                        'opacity': '0'
                    });
                    
                    // Cleanup WebRTC
                    if (window.WebRTCHelper) {
                        window.WebRTCHelper.cleanup();
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
</html>