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
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💬</text></svg>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            position: relative;
        }
        
        .header-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }
        
        .header-icon i {
            font-size: 1.2rem;
            color: white;
        }
        
        .header-content h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
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
            position: relative;
            z-index: 1;
            padding: 0.5rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            transition: all 0.3s ease;
            min-width: 40px;
            justify-content: center;
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
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .btn-home:hover {
            background: rgba(255, 255, 255, 0.3);
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
            height: calc(100vh - 80px);
            background: #f8f9fa;
            position: relative;
        }
        
        .chat-sidebar {
            width: 300px;
            background: #ffffff;
            border-right: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
        }
        
        .sidebar-header h6 {
            margin: 0;
            font-weight: 600;
            color: #495057;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-new-chat {
            width: 32px;
            height: 32px;
            border: none;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-new-chat:hover {
            background: #5568d3;
        }
        
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem;
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
            padding: 0.75rem 1rem;
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
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 0.9rem;
        }
        
        .user-details h6 {
            margin: 0;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }
        
        .user-details small {
            font-size: 0.8rem;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #ffffff;
            position: relative;
        }
        
        .message {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: flex-start;
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message.received {
            justify-content: flex-start;
        }
        
        .message-content {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 18px;
            position: relative;
        }
        
        .message.sent .message-content {
            background: #667eea;
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.received .message-content {
            background: #f1f3f5;
            color: #333;
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
            padding: 0.75rem 1rem;
            background: white;
            border-top: 1px solid #dee2e6;
        }
        
        .chat-input-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .chat-input input {
            flex: 1;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        .chat-input input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }
        
        .chat-input button {
            background: #667eea;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            font-size: 1rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .chat-input button#sendButton {
            width: 40px;
            height: 40px;
            background: #28a745;
        }
        
        .chat-input button#voiceCallButton {
            background: #17a2b8;
        }
        
        .chat-input button#videoCallButton {
            background: #dc3545;
        }
        
        .chat-input button#attachButton {
            background: #6c757d;
        }
        
        .chat-input button:hover:not(:disabled) {
            opacity: 0.9;
            transform: scale(1.05);
        }
        
        .chat-input button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .conversation-item {
            padding: 0.75rem;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            background: #ffffff;
            border-radius: 8px;
            margin-bottom: 0.25rem;
        }
        
        .conversation-item:hover {
            background: #f8f9fa;
        }
        
        .conversation-item.active {
            background: #e7f3ff;
            border-left: 3px solid #667eea;
        }
        
        .conversation-user {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        
        .conversation-preview {
            font-size: 0.85rem;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.25rem;
        }
        
        .conversation-time {
            font-size: 0.75rem;
            color: #adb5bd;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
            position: relative;
        }
        
        .status-online {
            background: #28a745;
        }
        
        .status-offline {
            background: #6c757d;
        }
        
        .customer-search {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
            background: #ffffff;
        }
        
        .customer-search .input-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .customer-search input {
            flex: 1;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }
        
        .customer-search button {
            border-radius: 20px;
            padding: 0.5rem 0.75rem;
            border: 1px solid #dee2e6;
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
            padding: 2rem;
        }
        
        .welcome-icon {
            width: 60px;
            height: 60px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        
        .welcome-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .welcome-screen h4 {
            color: #495057;
            margin-bottom: 0.75rem;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .welcome-screen p {
            color: #6c757d;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
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
            font-size: 0.85rem;
        }
        
        .info-item i {
            color: #667eea;
            font-size: 0.9rem;
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
            flex-direction: column;
        }
        
        .call-modal.show {
            display: flex !important;
        }
        
        .call-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            margin: auto;
            position: relative;
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
            .chat-header {
                padding: 0.75rem 1rem;
            }
            
            .header-content h1 {
                font-size: 1.25rem;
            }
            
            .header-icon {
                width: 36px;
                height: 36px;
                margin-right: 0.5rem;
            }
            
            .header-icon i {
                font-size: 1rem;
            }
            
            .btn-home {
                width: 36px;
                height: 36px;
            }
            
            .chat-content {
                flex-direction: column;
                height: calc(100vh - 70px);
            }
            
            .chat-sidebar {
                width: 100%;
                height: 180px;
            }
            
            .sidebar-header {
                padding: 0.75rem;
            }
            
            .welcome-info {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .chat-main {
                height: calc(100vh - 280px);
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
    <!-- WebRTC Adapter.js - Tương thích cross-browser -->
    <script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
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
            // Development: Try local server first
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
        // Helper function để tự động phát hiện đường dẫn API đúng
        function getApiPath(relativePath) {
            const path = window.location.pathname;
            const hostname = window.location.hostname;
            
            // Production domain
            if (hostname.includes('sukien.info.vn') || hostname.includes('sukien')) {
                if (path.includes('/my-php-project/')) {
                    return '/my-php-project/' + relativePath;
                }
                return '/' + relativePath;
            }
            
            // Localhost development
            if (path.includes('/my-php-project/')) {
                return '/my-php-project/' + relativePath;
            } else if (path.includes('/event/')) {
                return '/event/my-php-project/' + relativePath;
            }
            
            // Fallback: relative path
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
        
        // Media and Call variables
        let currentCall = null;
        let localStream = null;
        let remoteStream = null;
        let peerConnection = null;
        let isMuted = false;
        let isCameraOff = false;
        
        // Interval IDs for polling/auto-refresh (to prevent multiple intervals)
        let autoRefreshInterval = null;
        
        // ✅ Flag để tránh gọi initSocket() nhiều lần cùng lúc
        let isInitializingSocket = false;
        
        // ✅ Initialize chat
        $(document).ready(() => {
            // Set initial connecting status
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
            setUserOnline(); // Set user online
            loadConversations();
            setupChatEvents();
            setupMediaEvents();
            // ✅ setupCallSocketEvents() will be called in socket.on('connect')
            // to ensure socket is connected before setting up event listeners
            setupQuickReplies(); // Setup quick reply buttons
            setupConversationSearch(); // Setup search functionality
            startAutoRefresh();
            
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
            
            // Set user offline when page is closed
            $(window).on('beforeunload', function() {
                setUserOffline();
            });
        });
        
        // ✅ Setup quick reply buttons
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
        
        // ✅ Setup conversation search
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
            
            // ✅ Set flag để tránh gọi lại
            isInitializingSocket = true;
            
            // QUAN TRỌNG: Nếu socket đã tồn tại nhưng disconnected, đóng nó trước khi tạo mới
            if (socket && !socket.connected) {
                console.log('📡 Closing existing disconnected socket before re-init');
                socket.removeAllListeners();
                socket.disconnect();
                socket = null;
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
            
            // QUAN TRỌNG: Tạo socket mới với cấu hình reconnect tự động
            socket = io(socketServerURL, {
                path: socketPath,
                transports: ['polling', 'websocket'], // Try polling first, then websocket
                reconnection: true, // Bật tự động reconnect
                reconnectionAttempts: Infinity, // Tiếp tục thử kết nối lại vô hạn
                reconnectionDelay: 1000, // Delay 1 giây trước khi thử lại
                reconnectionDelayMax: 10000, // Delay tối đa 10 giây
                timeout: 20000,
                forceNew: false, // Không force tạo connection mới nếu đã có
                autoConnect: true, // Tự động kết nối ngay khi tạo
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
                
                // Authenticate ngay khi connect
                socket.emit('authenticate', {
                    userId: currentUserId,
                    userRole: currentUserRole,
                    userName: currentUserName
                });
                
                // Ensure user is in their own room for receiving calls
                socket.emit('join_user_room', { userId: currentUserId });
                console.log('Socket connected, joined user room:', currentUserId);
                
                // Tham gia lại conversation hiện tại nếu có
                if (currentConversationId) {
                    socket.emit('join_conversation', { conversation_id: currentConversationId });
                    console.log('Rejoined conversation:', currentConversationId);
                }
                
                // ✅ Setup call socket events AFTER socket is connected
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
        
        // ✅ Set user offline
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
            
            const apiUrl = getApiPath('src/controllers/chat-controller.php?action=mark_as_read');
            
            $.post(apiUrl, {
                conversation_id: conversationId
            }, function(data) {
                if (data.success) {
                    console.log('Messages marked as read');
                    // Reload conversations to update unread count
                    loadConversations();
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Error marking messages as read:', error);
                console.error('API URL:', apiUrl);
                console.error('Response:', xhr.responseText);
                console.error('Status:', xhr.status);
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
        
        // ✅ Cập nhật trạng thái kết nối - Chỉ hiển thị nút xanh/đỏ (icon)
        function updateConnectionStatus(status, text) {
            const statusEl = $('#connectionStatus');
            const indicator = $('#connectionIndicator .status-dot');
            const textEl = $('#connectionText');
            
            // Update status dot
            indicator.removeClass('online offline connecting').addClass(status);
            
            // Update connection status container
            statusEl.removeClass('online offline connecting').addClass(status);
            
            // Ẩn text, chỉ hiển thị icon (nút xanh/đỏ)
            if (textEl.length) {
                textEl.hide(); // Ẩn text
            }
            
            // Update tooltip với text đầy đủ
            const tooltipText = text || (status === 'online' ? 'Đã kết nối realtime' : status === 'offline' ? 'Chế độ offline' : 'Đang kết nối...');
            indicator.attr('title', tooltipText);
            statusEl.attr('title', tooltipText);
            
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
                url: getApiPath('src/controllers/media-upload.php'),
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
            
            $.post(getApiPath('src/controllers/call-controller.php?action=initiate_call'), {
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
                // Add both accept and reject buttons - Đồng nhất với admin/chat.php
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
                // Clear existing buttons first
                $('#callControls').empty();
                // Add end call button - Đồng nhất với admin/chat.php
                $('#callControls').html(`
                    <button class="btn btn-danger btn-lg" id="endCallBtn" onclick="endCall()" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; z-index: 10001;">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                `);
                
                // Also attach event listener as backup
                $('#endCallBtn').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📞 End call button clicked (outgoing) - via event listener');
                    endCall();
                });
                
                console.log('📤 Outgoing call - Added end button only');
            }
            
            // Show modal - Đảm bảo căn giữa màn hình
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
            
            // Debug: Check if buttons are in DOM - Đồng nhất với admin/chat.php
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
                
                // Force show buttons if not visible
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
        
        // Accept call
        function acceptCall() {
            if (!currentCall) {
                console.error('No current call to accept');
                return;
            }
            
            $.post(getApiPath('src/controllers/call-controller.php?action=accept_call'), {
                call_id: currentCall.id
            }, function(response) {
                if (response.success) {
                    if (currentCall.type === 'video') {
                        // For video call, hide modal and show video container
                        $('#callModal').removeClass('show').css('display', 'none');
                        startVideoCall();
                    } else {
                        // For voice call, don't hide modal yet - show active call UI
                        startVoiceCall();
                        
                        // QUAN TRỌNG: Đảm bảo remote audio được play sau khi accept (user interaction)
                        setTimeout(() => {
                            const remoteAudio = document.getElementById('remoteAudio');
                            if (remoteAudio && remoteAudio.srcObject) {
                                remoteAudio.play().then(() => {
                                    console.log('✅ Remote audio played after accepting call');
                                }).catch(err => {
                                    console.warn('⚠️ Could not play audio immediately:', err);
                                });
                            }
                        }, 500);
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
                // Ẩn modal - Đồng nhất với admin/chat.php
                const modalElement = document.getElementById('callModal');
                if (modalElement) {
                    $(modalElement).removeClass('show').css('display', 'none');
                }
                return;
            }
            
            const callId = currentCall.id;
            const callerId = currentCall.caller_id || currentCall.receiver_id;
            
            $.post(getApiPath('src/controllers/call-controller.php?action=reject_call'), {
                call_id: callId
            }, function(response) {
                // Ẩn modal - Đồng nhất với admin/chat.php
                const modalElement = document.getElementById('callModal');
                if (modalElement) {
                    $(modalElement).removeClass('show').css('display', 'none');
                }
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
                // Ẩn modal - Đồng nhất với admin/chat.php
                const modalElement = document.getElementById('callModal');
                if (modalElement) {
                    $(modalElement).removeClass('show').css('display', 'none');
                }
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
            
            // QUAN TRỌNG: Ẩn modal ngay lập tức để người dùng thấy phản hồi
            $('#callModal').removeClass('show').css('display', 'none');
            $('#videoCallContainer').hide().css({
                'display': 'none',
                'visibility': 'hidden',
                'opacity': '0'
            });
            
            // Dừng remote audio nếu đang phát
            const remoteAudio = document.getElementById('remoteAudio');
            if (remoteAudio) {
                remoteAudio.pause();
                remoteAudio.srcObject = null;
                console.log('✅ Remote audio stopped');
            }
            
            // Stop local stream ngay lập tức
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
            
            // Stop remote stream ngay lập tức
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
            
            // Close peer connection ngay lập tức
            if (peerConnection) {
                try {
                    peerConnection.close();
                    peerConnection = null;
                    console.log('✅ Peer connection closed');
                } catch (e) {
                    console.error('Error closing peer connection:', e);
                }
            }
            
            // Lấy callId trước khi clear currentCall
            const callId = currentCall ? currentCall.id : null;
            
            // Clear currentCall ngay lập tức để tránh gọi lại
            currentCall = null;
            
            // Nếu không có callId, chỉ cleanup và return
            if (!callId) {
                console.log('⚠️ No callId, cleanup done');
                return;
            }
            
            console.log('📞 Ending call with ID:', callId);
            
            // Call backend to end call (async, không chặn UI)
            $.post(getApiPath('src/controllers/call-controller.php?action=end_call'), {
                call_id: callId
            }, function(response) {
                console.log('📞 End call response:', response);
                
                // Emit end event via socket
                if (isConnected && socket && typeof socket.emit === 'function') {
                    socket.emit('call_ended', {
                        call_id: callId,
                        caller_id: currentUserId
                    });
                    console.log('✅ Call ended event emitted');
                }
                
                console.log('✅ Call ended successfully');
            }, 'json').fail(function(xhr, status, error) {
                console.error('❌ End call backend error:', error);
                console.error('Response:', xhr.responseText);
                
                // Vẫn emit end event ngay cả khi backend fail
                if (isConnected && socket && typeof socket.emit === 'function') {
                    socket.emit('call_ended', {
                        call_id: callId,
                        caller_id: currentUserId
                    });
                    console.log('✅ Call ended event emitted (despite backend error)');
                }
                
                console.log('✅ Cleanup done despite backend error');
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
            
            // QUAN TRỌNG: Ẩn call modal trước khi hiển thị video container
            $('#callModal').removeClass('show').css('display', 'none');
            
            // Hiển thị video call container với CSS rõ ràng
            $('#videoCallContainer').addClass('show').css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1',
                'z-index': '10000'
            });
            
            console.log('📹 Starting video call, requesting camera and microphone...');
            
            navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(stream => {
                    localStream = stream;
                    console.log('📹 Local stream obtained:', stream);
                    console.log('📹 Local video tracks:', stream.getVideoTracks());
                    console.log('📹 Local audio tracks:', stream.getAudioTracks());
                    
                    const localVideo = document.getElementById('localVideo');
                    if (localVideo) {
                        localVideo.srcObject = stream;
                        console.log('✅ Local video assigned to video element');
                    } else {
                        console.error('❌ Local video element not found!');
                    }
                    
                    // Initialize WebRTC peer connection
                    initializePeerConnection();
                    
                    console.log('✅ Video call started successfully');
                })
                .catch(error => {
                    console.error('❌ Error accessing media devices:', error);
                    console.error('Error details:', {
                        name: error.name,
                        message: error.message
                    });
                    
                    // Ẩn video container nếu có lỗi
                    $('#videoCallContainer').removeClass('show').css('display', 'none');
                    
                    let errorMessage = 'Không thể truy cập camera/microphone';
                    if (error.name === 'NotAllowedError') {
                        errorMessage = 'Vui lòng cho phép truy cập camera và microphone';
                    } else if (error.name === 'NotFoundError') {
                        errorMessage = 'Không tìm thấy camera/microphone';
                    } else if (error.name === 'NotReadableError') {
                        errorMessage = 'Camera/microphone đang được sử dụng bởi ứng dụng khác';
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
                    console.log('📞 Local stream obtained:', stream);
                    console.log('📞 Local audio tracks:', stream.getAudioTracks());
                    
                    // Kiểm tra local audio tracks
                    const localAudioTracks = stream.getAudioTracks();
                    if (localAudioTracks.length === 0) {
                        console.warn('⚠️ Local stream không có audio track!');
                    } else {
                        console.log('✅ Local stream có', localAudioTracks.length, 'audio track(s)');
                        localAudioTracks.forEach((track, index) => {
                            console.log(`  Local audio track ${index}:`, {
                                enabled: track.enabled,
                                kind: track.kind,
                                label: track.label,
                                muted: track.muted,
                                readyState: track.readyState
                            });
                        });
                    }
                    
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
            // Show end call button only - Đồng nhất với admin/chat.php
            $('#callControls').html(`
                <button class="btn btn-danger btn-lg" id="endCallBtn" onclick="endCall()" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; z-index: 10001;">
                    <i class="fas fa-phone-slash"></i>
                </button>
            `);
            
            // Also attach event listener as backup
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
            
            // Debug: Check if button is in DOM after a short delay
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
                
                // Force show if not visible - Đảm bảo căn giữa
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
            // Close existing peer connection if any
            if (peerConnection) {
                try {
                    peerConnection.close();
                } catch (e) {
                    console.warn('Error closing existing peer connection:', e);
                }
            }
            
            // QUAN TRỌNG: Cấu hình WebRTC với STUN và TURN servers
            // STUN: Để tìm public IP/port
            // TURN: Để relay traffic khi P2P không thể kết nối (NAT/firewall)
            const configuration = {
                iceServers: [
                    // STUN servers (miễn phí từ Google)
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                    { urls: 'stun:stun2.l.google.com:19302' },
                    { urls: 'stun:stun3.l.google.com:19302' },
                    { urls: 'stun:stun4.l.google.com:19302' },
                    // TURN servers (miễn phí - cần thay bằng TURN server riêng nếu có)
                    // Option 1: Dùng free TURN server (có thể không ổn định)
                    { 
                        urls: 'turn:openrelay.metered.ca:80',
                        username: 'openrelayproject',
                        credential: 'openrelayproject'
                    },
                    { 
                        urls: 'turn:openrelay.metered.ca:443',
                        username: 'openrelayproject',
                        credential: 'openrelayproject'
                    },
                    { 
                        urls: 'turn:openrelay.metered.ca:443?transport=tcp',
                        username: 'openrelayproject',
                        credential: 'openrelayproject'
                    },
                    // Option 2: Dùng TURN server khác (nếu có)
                    // { 
                    //     urls: 'turn:your-turn-server.com:3478',
                    //     username: 'your-username',
                    //     credential: 'your-password'
                    // }
                ],
                iceCandidatePoolSize: 10 // Tăng pool size để có nhiều candidates hơn
            };
            
            peerConnection = new RTCPeerConnection(configuration);
            console.log('✅ Peer connection created');
            
            // Add local stream to peer connection
            if (localStream) {
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                    console.log('✅ Added local track:', track.kind, track.label);
                });
            } else {
                console.warn('⚠️ No local stream available when initializing peer connection');
            }
            
            // Handle remote stream
            // Best practice từ WebRTC: ontrack có thể được gọi nhiều lần, mỗi lần cho 1 track
            peerConnection.ontrack = event => {
                console.log('📞 ontrack event fired:', event);
                console.log('📞 Event streams:', event.streams);
                console.log('📞 Event track:', event.track);
                console.log('📞 Event track kind:', event.track ? event.track.kind : 'N/A');
                console.log('📞 Event track id:', event.track ? event.track.id : 'N/A');
                console.log('📞 Event track readyState:', event.track ? event.track.readyState : 'N/A');
                
                // QUAN TRỌNG: Lấy stream từ event
                // Best practice: Sử dụng event.streams[0] nếu có, nếu không thì tạo stream mới từ track
                if (event.streams && event.streams.length > 0) {
                    remoteStream = event.streams[0];
                    console.log('📞 Using stream from event.streams[0]');
                } else if (event.track) {
                    // Nếu không có stream, tạo stream mới từ track
                    // Nếu đã có remoteStream, thêm track vào stream đó
                    if (remoteStream) {
                        // Kiểm tra xem track đã có trong stream chưa
                        const existingTrack = remoteStream.getTracks().find(t => t.id === event.track.id);
                        if (!existingTrack) {
                            remoteStream.addTrack(event.track);
                            console.log('📞 Added track to existing remote stream');
                        } else {
                            console.log('📞 Track already in remote stream, skipping');
                        }
                    } else {
                        remoteStream = new MediaStream([event.track]);
                        console.log('📞 Created new MediaStream from track');
                    }
                } else {
                    console.error('❌ No stream or track in ontrack event!');
                    return;
                }
                
                console.log('📞 Remote stream received:', remoteStream);
                console.log('📞 Remote stream ID:', remoteStream.id);
                console.log('📞 Remote stream tracks:', remoteStream.getTracks());
                console.log('📞 Remote stream active:', remoteStream.active);
                
                // QUAN TRỌNG: Đảm bảo stream được cập nhật khi có track mới
                event.track.onended = () => {
                    console.log('📞 Remote track ended:', event.track.kind, event.track.id);
                };
                
                event.track.onmute = () => {
                    console.log('📞 Remote track muted:', event.track.kind, event.track.id);
                };
                
                event.track.onunmute = () => {
                    console.log('📞 Remote track unmuted:', event.track.kind, event.track.id);
                };
                
                // Kiểm tra video tracks trong remote stream
                const videoTracks = remoteStream.getVideoTracks();
                console.log('📞 Remote video tracks:', videoTracks);
                if (videoTracks.length === 0) {
                    console.warn('⚠️ Remote stream không có video track!');
                } else {
                    console.log('✅ Remote stream có', videoTracks.length, 'video track(s)');
                    videoTracks.forEach((track, index) => {
                        console.log(`  Video track ${index}:`, {
                            enabled: track.enabled,
                            kind: track.kind,
                            label: track.label,
                            muted: track.muted,
                            readyState: track.readyState
                        });
                    });
                }
                
                // Kiểm tra audio tracks trong remote stream
                const audioTracks = remoteStream.getAudioTracks();
                console.log('📞 Remote audio tracks:', audioTracks);
                if (audioTracks.length === 0) {
                    console.warn('⚠️ Remote stream không có audio track!');
                } else {
                    console.log('✅ Remote stream có', audioTracks.length, 'audio track(s)');
                    audioTracks.forEach((track, index) => {
                        console.log(`  Audio track ${index}:`, {
                            enabled: track.enabled,
                            kind: track.kind,
                            label: track.label,
                            muted: track.muted,
                            readyState: track.readyState
                        });
                    });
                }
                
                // Cho video call: gán vào remoteVideo
                const remoteVideo = document.getElementById('remoteVideo');
                if (remoteVideo) {
                    remoteVideo.srcObject = remoteStream;
                    // Đảm bảo video element được hiển thị và phát
                    remoteVideo.play().then(() => {
                        console.log('✅ Remote video playing successfully');
                        console.log('📹 Remote video element state:', {
                            paused: remoteVideo.paused,
                            currentTime: remoteVideo.currentTime,
                            readyState: remoteVideo.readyState,
                            videoWidth: remoteVideo.videoWidth,
                            videoHeight: remoteVideo.videoHeight
                        });
                    }).catch(err => {
                        console.error('❌ Error playing remote video:', err);
                        console.error('Error details:', {
                            name: err.name,
                            message: err.message
                        });
                    });
                    console.log('✅ Remote video assigned to video element');
                } else {
                    console.error('❌ Remote video element not found!');
                }
                
                // QUAN TRỌNG: Cho CẢ voice call VÀ video call - đều cần audio
                // Gán remote stream vào remoteAudio để phát âm thanh
                const remoteAudio = document.getElementById('remoteAudio');
                if (remoteAudio) {
                    // Setup audio element
                    remoteAudio.srcObject = remoteStream;
                    remoteAudio.volume = 1.0; // Đảm bảo volume = 100%
                    remoteAudio.muted = false; // Đảm bảo không bị mute
                    
                    console.log('📞 Remote audio setup:', {
                        srcObject: remoteAudio.srcObject ? 'set' : 'null',
                        volume: remoteAudio.volume,
                        muted: remoteAudio.muted,
                        paused: remoteAudio.paused,
                        readyState: remoteAudio.readyState
                    });
                    
                    // QUAN TRỌNG: Luôn thử play audio ngay khi có stream
                    const playAudio = () => {
                        const playPromise = remoteAudio.play();
                        if (playPromise !== undefined) {
                            playPromise.then(() => {
                                console.log('✅ Remote audio playing successfully');
                                console.log('📞 Audio element state:', {
                                    volume: remoteAudio.volume,
                                    muted: remoteAudio.muted,
                                    paused: remoteAudio.paused,
                                    currentTime: remoteAudio.currentTime,
                                    readyState: remoteAudio.readyState,
                                    srcObject: remoteAudio.srcObject ? 'set' : 'null'
                                });
                            }).catch(err => {
                                console.error('❌ Error playing remote audio:', err);
                                console.error('❌ Error details:', {
                                    name: err.name,
                                    message: err.message,
                                    code: err.code
                                });
                                
                                // Nếu bị chặn bởi autoplay policy, thử play khi user click
                                if (err.name === 'NotAllowedError' || err.name === 'NotSupportedError' || err.name === 'AbortError') {
                                    console.warn('⚠️ Browser autoplay policy blocked audio. Audio sẽ phát khi user tương tác.');
                                    
                                    // Thêm event listener để play khi user click vào modal hoặc bất kỳ đâu
                                    const playOnInteraction = (event) => {
                                        console.log('📞 User interaction detected, attempting to play audio...');
                                        remoteAudio.play().then(() => {
                                            console.log('✅ Audio played after user interaction');
                                            document.removeEventListener('click', playOnInteraction);
                                            document.removeEventListener('touchstart', playOnInteraction);
                                            document.removeEventListener('keydown', playOnInteraction);
                                        }).catch(e => {
                                            console.error('❌ Still error after interaction:', e);
                                        });
                                    };
                                    
                                    // Thêm nhiều event listeners để đảm bảo bắt được user interaction
                                    document.addEventListener('click', playOnInteraction, { once: true });
                                    document.addEventListener('touchstart', playOnInteraction, { once: true });
                                    document.addEventListener('keydown', playOnInteraction, { once: true });
                                    
                                    // Đặc biệt: thêm listener vào call modal
                                    const callModal = document.getElementById('callModal');
                                    if (callModal) {
                                        callModal.addEventListener('click', playOnInteraction, { once: true });
                                    }
                                }
                            });
                        }
                    };
                    
                    // Thử play ngay lập tức
                    playAudio();
                    
                    // Thử lại sau 100ms để đảm bảo stream đã sẵn sàng
                    setTimeout(() => {
                        if (remoteAudio.paused) {
                            console.log('📞 Audio still paused, retrying play...');
                            playAudio();
                        }
                    }, 100);
                    
                    // Thử lại sau 500ms nếu vẫn chưa play được
                    setTimeout(() => {
                        if (remoteAudio.paused && remoteAudio.srcObject) {
                            console.log('📞 Audio still paused after 500ms, retrying play...');
                            playAudio();
                        }
                    }, 500);
                    
                    console.log('✅ Remote audio assigned to audio element');
                } else {
                    console.error('❌ Remote audio element not found!');
                }
            };
            
            // Handle ICE candidates
            peerConnection.onicecandidate = event => {
                if (event.candidate) {
                    console.log('📞 ICE candidate generated:', event.candidate);
                    console.log('📞 Candidate type:', event.candidate.type);
                    console.log('📞 Candidate protocol:', event.candidate.protocol);
                    console.log('📞 Candidate priority:', event.candidate.priority);
                    console.log('📞 Candidate foundation:', event.candidate.foundation);
                    
                    // Send ICE candidate to remote peer via socket
                    if (isConnected && socket && currentCall) {
                        socket.emit('ice_candidate', {
                            call_id: currentCall.id,
                            candidate: event.candidate
                        });
                        console.log('✅ ICE candidate sent via socket');
                    } else {
                        console.warn('⚠️ Cannot send ICE candidate:', {
                            isConnected,
                            hasSocket: !!socket,
                            hasCurrentCall: !!currentCall
                        });
                    }
                } else {
                    console.log('📞 ICE gathering complete');
                    const sdp = peerConnection.localDescription ? peerConnection.localDescription.sdp : '';
                    const candidateCount = (sdp.match(/a=candidate:/g) || []).length;
                    console.log('📞 Total ICE candidates:', candidateCount);
                }
            };
            
            // Handle connection state changes
            peerConnection.onconnectionstatechange = () => {
                console.log('📞 Peer connection state:', peerConnection.connectionState);
                console.log('📞 ICE connection state:', peerConnection.iceConnectionState);
                console.log('📞 ICE gathering state:', peerConnection.iceGatheringState);
                console.log('📞 Signaling state:', peerConnection.signalingState);
                
                if (peerConnection.connectionState === 'connected') {
                    console.log('✅ Peer connection established successfully!');
                } else if (peerConnection.connectionState === 'failed' || peerConnection.connectionState === 'disconnected') {
                    console.warn('⚠️ Peer connection failed or disconnected');
                    console.warn('⚠️ ICE connection state:', peerConnection.iceConnectionState);
                    
                    // Thử restart ICE nếu failed
                    if (peerConnection.connectionState === 'failed' && peerConnection.iceConnectionState === 'failed') {
                        console.log('🔄 Attempting to restart ICE...');
                        peerConnection.restartIce();
                    }
                }
            };
            
            // Handle ICE connection state changes
            peerConnection.oniceconnectionstatechange = () => {
                console.log('📞 ICE connection state changed:', peerConnection.iceConnectionState);
                if (peerConnection.iceConnectionState === 'connected' || peerConnection.iceConnectionState === 'completed') {
                    console.log('✅ ICE connection established!');
                } else if (peerConnection.iceConnectionState === 'failed') {
                    console.error('❌ ICE connection failed - may need TURN server');
                } else if (peerConnection.iceConnectionState === 'disconnected') {
                    console.warn('⚠️ ICE connection disconnected');
                }
            };
            
            // Handle ICE gathering state changes
            peerConnection.onicegatheringstatechange = () => {
                console.log('📞 ICE gathering state:', peerConnection.iceGatheringState);
                if (peerConnection.iceGatheringState === 'complete') {
                    console.log('✅ ICE gathering complete');
                }
            };
            
            // QUAN TRỌNG: Tạo offer nếu là caller, hoặc chờ answer nếu là receiver
            // Best practice từ WebRTC: Đợi ICE gathering hoàn tất trước khi tạo offer
            if (currentCall && currentCall.caller_id == currentUserId) {
                // Caller: Đợi ICE gathering hoàn tất rồi mới tạo offer
                console.log('📞 Caller: Waiting for ICE gathering before creating offer...');
                
                const createOfferWhenReady = () => {
                    // Kiểm tra nếu đã có local description thì không tạo lại
                    if (peerConnection.localDescription) {
                        console.log('📞 Local description already set, skipping offer creation');
                        return;
                    }
                    
                    console.log('📞 Caller: Creating offer...');
                    peerConnection.createOffer({
                        offerToReceiveAudio: true,
                        offerToReceiveVideo: currentCall.type === 'video'
                    })
                        .then(offer => {
                            console.log('✅ Offer created:', offer);
                            console.log('📞 Offer type:', offer.type);
                            console.log('📞 Offer SDP:', offer.sdp.substring(0, 200) + '...');
                            return peerConnection.setLocalDescription(offer);
                        })
                        .then(() => {
                            console.log('✅ Local description set');
                            console.log('📞 Local description:', peerConnection.localDescription);
                            
                            // Gửi offer qua socket
                            if (isConnected && socket && currentCall) {
                                socket.emit('webrtc_offer', {
                                    call_id: currentCall.id,
                                    offer: peerConnection.localDescription
                                });
                                console.log('✅ Offer sent via socket');
                            } else {
                                console.error('❌ Cannot send offer: socket not connected or currentCall missing');
                            }
                        })
                        .catch(error => {
                            console.error('❌ Error creating offer:', error);
                            console.error('Error stack:', error.stack);
                        });
                };
                
                // Nếu ICE gathering đã hoàn tất, tạo offer ngay
                if (peerConnection.iceGatheringState === 'complete') {
                    createOfferWhenReady();
                } else {
                    // Đợi ICE gathering hoàn tất
                    peerConnection.addEventListener('icegatheringstatechange', function onIceGatheringStateChange() {
                        if (peerConnection.iceGatheringState === 'complete') {
                            console.log('📞 ICE gathering complete, creating offer...');
                            peerConnection.removeEventListener('icegatheringstatechange', onIceGatheringStateChange);
                            createOfferWhenReady();
                        }
                    });
                    
                    // Timeout sau 3 giây nếu ICE gathering chưa hoàn tất
                    setTimeout(() => {
                        if (!peerConnection.localDescription) {
                            console.warn('⚠️ ICE gathering timeout, creating offer anyway...');
                            createOfferWhenReady();
                        }
                    }, 3000);
                }
            } else if (currentCall && currentCall.receiver_id == currentUserId) {
                // Receiver: Chờ offer từ caller (sẽ được xử lý trong socket event)
                console.log('📞 Receiver: Waiting for offer...');
            }
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
                
                // Call accepted
                socket.on('call_accepted', data => {
                    console.log('📞 Received call_accepted event:', data);
                    if (data.caller_id === currentUserId && currentCall) {
                        $('#callModal').removeClass('show').css('display', 'none');
                        
                        if (currentCall.type === 'video') {
                            startVideoCall();
                        } else {
                            startVoiceCall();
                        }
                        
                        // QUAN TRỌNG: Đảm bảo remote audio được play sau khi caller nhận được accept
                        setTimeout(() => {
                            const remoteAudio = document.getElementById('remoteAudio');
                            if (remoteAudio && remoteAudio.srcObject) {
                                remoteAudio.play().then(() => {
                                    console.log('✅ Remote audio played after call accepted (caller side)');
                                }).catch(err => {
                                    console.warn('⚠️ Could not play audio on caller side:', err);
                                });
                            }
                        }, 500);
                    } else if (data.receiver_id === currentUserId && currentCall) {
                        // Receiver đã accept, tạo answer
                        console.log('📞 Receiver accepted, creating answer...');
                        if (peerConnection && peerConnection.signalingState !== 'stable') {
                            // Đã có offer, tạo answer
                            peerConnection.createAnswer()
                                .then(answer => {
                                    console.log('✅ Answer created:', answer);
                                    return peerConnection.setLocalDescription(answer);
                                })
                                .then(() => {
                                    console.log('✅ Local description (answer) set');
                                    // Gửi answer qua socket
                                    if (isConnected && socket && currentCall) {
                                        socket.emit('webrtc_answer', {
                                            call_id: currentCall.id,
                                            answer: peerConnection.localDescription
                                        });
                                        console.log('✅ Answer sent via socket');
                                    }
                                })
                                .catch(error => {
                                    console.error('❌ Error creating answer:', error);
                                });
                        }
                    }
                });
                
                // WebRTC Offer received (receiver nhận offer từ caller)
                socket.on('webrtc_offer', data => {
                    console.log('📞 Received WebRTC offer:', data);
                    if (currentCall && data.call_id == currentCall.id && currentCall.receiver_id == currentUserId) {
                        if (peerConnection) {
                            // Best practice: Kiểm tra signaling state trước khi set remote description
                            if (peerConnection.signalingState !== 'stable' && peerConnection.signalingState !== 'have-local-offer') {
                                console.warn('⚠️ Signaling state is not stable:', peerConnection.signalingState);
                            }
                            
                            peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer))
                                .then(() => {
                                    console.log('✅ Remote description (offer) set');
                                    console.log('📞 Remote description:', peerConnection.remoteDescription);
                                    console.log('📞 Signaling state after setRemoteDescription:', peerConnection.signalingState);
                                    
                                    // Tạo answer với options
                                    return peerConnection.createAnswer({
                                        voiceActivityDetection: true
                                    });
                                })
                                .then(answer => {
                                    console.log('✅ Answer created:', answer);
                                    console.log('📞 Answer type:', answer.type);
                                    console.log('📞 Answer SDP:', answer.sdp.substring(0, 200) + '...');
                                    return peerConnection.setLocalDescription(answer);
                                })
                                .then(() => {
                                    console.log('✅ Local description (answer) set');
                                    console.log('📞 Local description:', peerConnection.localDescription);
                                    console.log('📞 Signaling state after setLocalDescription:', peerConnection.signalingState);
                                    
                                    // Gửi answer qua socket
                                    if (isConnected && socket && currentCall) {
                                        socket.emit('webrtc_answer', {
                                            call_id: currentCall.id,
                                            answer: peerConnection.localDescription
                                        });
                                        console.log('✅ Answer sent via socket');
                                    } else {
                                        console.error('❌ Cannot send answer: socket not connected or currentCall missing');
                                    }
                                })
                                .catch(error => {
                                    console.error('❌ Error handling offer:', error);
                                    console.error('Error stack:', error.stack);
                                });
                        } else {
                            console.error('❌ Peer connection not initialized when receiving offer');
                        }
                    } else {
                        console.warn('⚠️ Offer received but conditions not met:', {
                            hasCurrentCall: !!currentCall,
                            callIdMatch: currentCall && data.call_id == currentCall.id,
                            isReceiver: currentCall && currentCall.receiver_id == currentUserId
                        });
                    }
                });
                
                // WebRTC Answer received (caller nhận answer từ receiver)
                socket.on('webrtc_answer', data => {
                    console.log('📞 Received WebRTC answer:', data);
                    if (currentCall && data.call_id == currentCall.id && currentCall.caller_id == currentUserId) {
                        if (peerConnection) {
                            // Best practice: Kiểm tra signaling state
                            if (peerConnection.signalingState !== 'have-local-offer') {
                                console.warn('⚠️ Signaling state is not have-local-offer:', peerConnection.signalingState);
                            }
                            
                            peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer))
                                .then(() => {
                                    console.log('✅ Remote description (answer) set');
                                    console.log('📞 Remote description:', peerConnection.remoteDescription);
                                    console.log('📞 Signaling state after setRemoteDescription:', peerConnection.signalingState);
                                })
                                .catch(error => {
                                    console.error('❌ Error setting remote description:', error);
                                    console.error('Error stack:', error.stack);
                                });
                        } else {
                            console.error('❌ Peer connection not initialized when receiving answer');
                        }
                    } else {
                        console.warn('⚠️ Answer received but conditions not met:', {
                            hasCurrentCall: !!currentCall,
                            callIdMatch: currentCall && data.call_id == currentCall.id,
                            isCaller: currentCall && currentCall.caller_id == currentUserId
                        });
                    }
                });
                
                // ICE Candidate received
                socket.on('ice_candidate', data => {
                    console.log('📞 Received ICE candidate:', data);
                    if (currentCall && data.call_id == currentCall.id && peerConnection) {
                        // Best practice: Kiểm tra remote description đã được set chưa
                        if (!peerConnection.remoteDescription) {
                            console.warn('⚠️ Remote description not set yet, storing candidate for later');
                            // Lưu candidate để add sau
                            if (!peerConnection._pendingCandidates) {
                                peerConnection._pendingCandidates = [];
                            }
                            peerConnection._pendingCandidates.push(data.candidate);
                            return;
                        }
                        
                        // Nếu có pending candidates, add chúng trước
                        if (peerConnection._pendingCandidates && peerConnection._pendingCandidates.length > 0) {
                            console.log('📞 Adding', peerConnection._pendingCandidates.length, 'pending candidates first');
                            const pending = peerConnection._pendingCandidates;
                            peerConnection._pendingCandidates = [];
                            
                            pending.forEach(candidate => {
                                peerConnection.addIceCandidate(new RTCIceCandidate(candidate))
                                    .then(() => console.log('✅ Pending ICE candidate added'))
                                    .catch(err => console.error('❌ Error adding pending candidate:', err));
                            });
                        }
                        
                        peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate))
                            .then(() => {
                                console.log('✅ ICE candidate added');
                            })
                            .catch(error => {
                                console.error('❌ Error adding ICE candidate:', error);
                                console.error('Error details:', {
                                    name: error.name,
                                    message: error.message,
                                    candidate: data.candidate
                                });
                            });
                    } else {
                        console.warn('⚠️ ICE candidate received but conditions not met:', {
                            hasCurrentCall: !!currentCall,
                            callIdMatch: currentCall && data.call_id == currentCall.id,
                            hasPeerConnection: !!peerConnection
                        });
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
                    console.log('📞 Received call_ended event:', data);
                    
                    // QUAN TRỌNG: Cleanup đầy đủ khi bên kia tắt cuộc gọi
                    // Ẩn modal và video container
                    $('#callModal').removeClass('show').css('display', 'none');
                    $('#videoCallContainer').removeClass('show').css({
                        'display': 'none',
                        'visibility': 'hidden',
                        'opacity': '0'
                    });
                    
                    // Dừng remote audio nếu đang phát
                    const remoteAudio = document.getElementById('remoteAudio');
                    if (remoteAudio) {
                        remoteAudio.pause();
                        remoteAudio.srcObject = null;
                        console.log('✅ Remote audio stopped');
                    }
                    
                    // Dừng remote video nếu đang phát
                    const remoteVideo = document.getElementById('remoteVideo');
                    if (remoteVideo) {
                        remoteVideo.pause();
                        remoteVideo.srcObject = null;
                        console.log('✅ Remote video stopped');
                    }
                    
                    // Stop local stream
                    if (localStream) {
                        localStream.getTracks().forEach(track => {
                            track.stop();
                            console.log('📞 Stopped local track:', track.kind);
                        });
                        localStream = null;
                        console.log('✅ Local stream stopped');
                    }
                    
                    // Stop remote stream
                    if (remoteStream) {
                        remoteStream.getTracks().forEach(track => {
                            track.stop();
                            console.log('📞 Stopped remote track:', track.kind);
                        });
                        remoteStream = null;
                        console.log('✅ Remote stream stopped');
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