<?php
session_start();
require_once __DIR__ . '/../src/auth/auth.php';
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
            padding: 2rem;
            text-align: center;
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
        
        .chat-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
            animation: titleGlow 3s ease-in-out infinite alternate;
        }
        
        @keyframes titleGlow {
            0% { text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3); }
            100% { text-shadow: 0 2px 20px rgba(255, 255, 255, 0.5); }
        }
        
        .chat-header p {
            margin: 1rem 0 0 0;
            opacity: 0.95;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
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
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(222, 226, 230, 0.3);
            overflow-y: auto;
            position: relative;
            z-index: 1;
        }
        
        .chat-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
            pointer-events: none;
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
        
        .no-messages {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
        }
        
        .connection-status {
            padding: 0.75rem 1.5rem;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 0 0 15px 15px;
            position: relative;
            overflow: hidden;
        }
        
        .connection-status::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: statusShine 3s ease-in-out infinite;
        }
        
        @keyframes statusShine {
            0% { left: -100%; }
            50% { left: 100%; }
            100% { left: 100%; }
        }
        
        .connection-status.connected {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            box-shadow: 0 2px 10px rgba(21, 87, 36, 0.2);
        }
        
        .connection-status.disconnected {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            box-shadow: 0 2px 10px rgba(114, 28, 36, 0.2);
        }
        
        @media (max-width: 768px) {
            .chat-content {
                flex-direction: column;
                height: auto;
            }
            
            .chat-sidebar {
                width: 100%;
                height: 200px;
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
                    <div>
                        <h1><i class="fas fa-comments"></i> Chat Hỗ trợ</h1>
                        <p>Liên hệ với đội ngũ hỗ trợ của chúng tôi</p>
                        <div class="user-info" id="userInfo" style="display: none;">
                            <small class="text-muted">
                                <i class="fas fa-user"></i> 
                                <span id="userName">Đang tải...</span> | 
                                <span id="userEmail">Đang tải...</span>
                                <span id="userRole" class="role-badge"></span>
                            </small>
                        </div>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-light">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Connection Status -->
            <div class="connection-status" id="connectionStatus">
                <i class="fas fa-spinner fa-spin"></i> Đang kết nối...
            </div>
            
            <!-- Chat Content -->
            <div class="chat-content">
                <!-- Sidebar -->
                <div class="chat-sidebar">
                    <div class="p-3">
                        <h6 class="mb-3"><i class="fas fa-users"></i> Cuộc trò chuyện</h6>
                        <div id="conversationsList">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Đang tải...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Chat -->
                <div class="chat-main">
                    <!-- Messages -->
                    <div class="chat-messages" id="chatMessages">
                        <div class="no-messages">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h5>Chào mừng đến với Chat Hỗ trợ!</h5>
                            <p>Chọn một cuộc trò chuyện để bắt đầu hoặc tạo cuộc trò chuyện mới.</p>
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
        let socket;
        let currentConversationId = null;
        let conversations = [];
        let isConnected = false;
        
        // Initialize chat
        $(document).ready(function() {
            initializeSocket();
            loadUserInfo();
            loadConversations();
            setupEventHandlers();
        });
        
        // Load user information
        function loadUserInfo() {
            // Use session data directly
            const userData = <?php echo json_encode($_SESSION['user'] ?? []); ?>;
            
            console.log('User data:', userData);
            
            if (userData && Object.keys(userData).length > 0) {
                $('#userName').text(userData.HoTen || userData.Email || 'Người dùng');
                $('#userEmail').text(userData.Email || '');
                
                // Display role badge
                const role = userData.ID_Role || userData.role;
                const roleNames = {
                    1: 'Quản trị viên',
                    2: 'Quản lý tổ chức',
                    3: 'Quản lý sự kiện',
                    4: 'Nhân viên',
                    5: 'Khách hàng'
                };
                
                if (role) {
                    const roleName = roleNames[role] || 'Người dùng';
                    const roleClass = role == 5 ? 'role-customer' : 'role-event-manager';
                    $('#userRole').text(roleName).addClass(roleClass);
                }
                
                $('#userInfo').show();
            } else {
                console.error('No user session data');
                $('#userName').text('Người dùng');
                $('#userEmail').text('Đang tải...');
                $('#userInfo').show();
                
                // Enable input even without user data
                enableInput();
            }
        }
        
        // Initialize Socket.IO connection
        function initializeSocket() {
            // Check if Socket.IO is available
            if (typeof io === 'undefined') {
                console.warn('Socket.IO not loaded, chat will work without real-time features');
                isConnected = false;
                updateConnectionStatus('disconnected', 'Chế độ offline - Không có kết nối real-time');
                
                // Enable input for offline mode
                enableInput();
                return;
            }
            
            try {
                socket = io('http://localhost:3000', {
                    timeout: 3000,
                    reconnection: false, // Disable reconnection to avoid infinite loops
                    forceNew: true
                });
                
                socket.on('connect', function() {
                    console.log('Connected to server');
                    isConnected = true;
                    updateConnectionStatus('connected', 'Đã kết nối');
                    
                    // Join user room
                    socket.emit('join_user_room', {
                        userId: getCurrentUserId()
                    });
                });
                
                socket.on('disconnect', function() {
                    console.log('Disconnected from server');
                    isConnected = false;
                    updateConnectionStatus('disconnected', 'Mất kết nối');
                });
                
                socket.on('connect_error', function(error) {
                    console.error('Connection error:', error);
                    isConnected = false;
                    updateConnectionStatus('disconnected', 'Lỗi kết nối - Chế độ offline');
                });
                
                // Set timeout to show offline mode if connection fails
                setTimeout(function() {
                    if (!isConnected) {
                        updateConnectionStatus('disconnected', 'Lỗi kết nối - Chế độ offline');
                    }
                }, 5000);
                
            } catch (error) {
                console.error('Socket initialization error:', error);
                isConnected = false;
                updateConnectionStatus('disconnected', 'Lỗi khởi tạo - Chế độ offline');
            }
            
            socket.on('new_message', function(data) {
                if (data.conversation_id === currentConversationId) {
                    addMessageToChat(data, false);
                }
                updateConversationPreview(data.conversation_id, data.message);
            });
            
            socket.on('typing', function(data) {
                if (data.conversation_id === currentConversationId) {
                    showTypingIndicator(data.user_name);
                }
            });
            
            socket.on('stop_typing', function(data) {
                if (data.conversation_id === currentConversationId) {
                    hideTypingIndicator();
                }
            });
        }
        
        // Load conversations
        function loadConversations() {
            console.log('Loading conversations...');
            $.get('src/controllers/chat.php?action=get_conversations', function(data) {
                console.log('Conversations response:', data);
                if (data.success) {
                    conversations = data.conversations || [];
                    console.log('Conversations loaded:', conversations);
                    displayConversations();
                } else {
                    console.error('Failed to load conversations:', data.error);
                    $('#conversationsList').html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Không thể tải danh sách cuộc trò chuyện: ${data.error || 'Unknown error'}
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-primary btn-sm" onclick="createNewConversation()">
                                <i class="fas fa-plus"></i> Tạo cuộc trò chuyện mới
                            </button>
                        </div>
                    `);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                
                // Check if response is HTML (likely an error page)
                if (xhr.responseText && xhr.responseText.includes('<')) {
                    $('#conversationsList').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Lỗi server: Phản hồi không hợp lệ từ server
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-primary btn-sm" onclick="createNewConversation()">
                                <i class="fas fa-plus"></i> Tạo cuộc trò chuyện mới
                            </button>
                        </div>
                    `);
                } else {
                    $('#conversationsList').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Lỗi kết nối: ${error}
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-primary btn-sm" onclick="createNewConversation()">
                                <i class="fas fa-plus"></i> Tạo cuộc trò chuyện mới
                            </button>
                        </div>
                    `);
                }
                
                // Enable input for creating new conversation
                enableInput();
            });
        }
        
        // Mark messages as read
        function markMessagesAsRead(conversationId) {
            if (!conversationId) return;
            
            $.post('src/controllers/chat.php?action=mark_as_read', {
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
                            Bạn có thể gửi tin nhắn cho quản lý sự kiện. Tin nhắn sẽ được lưu lại và trả lời khi họ online.
                        </p>
                        <button class="btn btn-primary btn-sm" onclick="createNewConversation()">
                            <i class="fas fa-plus"></i> Tạo cuộc trò chuyện mới
                        </button>
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
        
        // Select conversation
        function selectConversation(conversationId) {
            currentConversationId = conversationId;
            
            // Update UI
            $('.conversation-item').removeClass('active');
            $(`.conversation-item[data-conversation-id="${conversationId}"]`).addClass('active');
            
            // Enable input
            $('#messageInput').prop('disabled', false);
            $('#sendButton').prop('disabled', false);
            
            // Load messages
            loadMessages(conversationId);
        }
        
        // Enable input when no conversation is selected
        function enableInput() {
            $('#messageInput').prop('disabled', false);
            $('#sendButton').prop('disabled', false);
        }
        
        // Load messages for conversation
        function loadMessages(conversationId) {
            console.log('loadMessages called with conversationId:', conversationId);
            
            $.get(`src/controllers/chat.php?action=get_messages&conversation_id=${conversationId}`, function(data) {
                console.log('loadMessages response:', data);
                
                if (data.success) {
                    console.log('Messages loaded successfully:', data.messages);
                    displayMessages(data.messages);
                    
                    // Mark messages as read
                    markMessagesAsRead(conversationId);
                } else {
                    console.error('Failed to load messages:', data.error);
                    $('#chatMessages').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Không thể tải tin nhắn: ${data.error || 'Unknown error'}
                        </div>
                    `);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('AJAX Error loading messages:', status, error);
                console.error('Response:', xhr.responseText);
                $('#chatMessages').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Lỗi kết nối: ${error}
                    </div>
                `);
            });
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
        
        // Create message HTML
        function createMessageHTML(message) {
            // Ensure we have a valid message object
            if (!message || typeof message !== 'object') {
                console.error('Invalid message object:', message);
                return '<div class="message error"><div class="message-content"><div>Invalid message</div></div></div>';
            }
            
            // Get message text safely
            const messageText = message.MessageText || message.message || message.text || '';
            
            // Get timestamp safely
            const timestamp = message.SentAt || message.created_at || message.timestamp || new Date().toISOString();
            const time = new Date(timestamp).toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const isSent = message.sender_id == getCurrentUserId();
            const isRead = message.IsRead == 1;
            
            return `
                <div class="message ${isSent ? 'sent' : 'received'}">
                    <div class="message-content">
                        <div>${escapeHtml(messageText)}</div>
                        <div class="message-time">
                            ${time}
                            ${isSent ? (isRead ? ' <i class="fas fa-check-double text-primary"></i>' : ' <i class="fas fa-check text-muted"></i>') : ''}
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Add message to chat
        function addMessageToChat(message, isSent) {
            const messageHTML = createMessageHTML(message);
            $('#chatMessages .no-messages').remove();
            $('#chatMessages').append(messageHTML);
            scrollToBottom();
        }
        
        // Setup event handlers
        function setupEventHandlers() {
            // Send message
            $('#sendButton').click(function() {
                sendMessage();
            });
            
            $('#messageInput').keypress(function(e) {
                if (e.which === 13) {
                    sendMessage();
                }
            });
            
            // Typing indicator
            let typingTimer;
            $('#messageInput').on('input', function() {
                if (currentConversationId && isConnected) {
                    socket.emit('typing', {
                        conversation_id: currentConversationId,
                        user_id: getCurrentUserId()
                    });
                    
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(function() {
                        socket.emit('stop_typing', {
                            conversation_id: currentConversationId,
                            user_id: getCurrentUserId()
                        });
                    }, 1000);
                }
            });
        }
        
        // Send message
        function sendMessage() {
            const message = $('#messageInput').val().trim();
            if (!message) {
                alert('Vui lòng nhập tin nhắn');
                return;
            }
            
            if (!currentConversationId) {
                alert('Vui lòng chọn cuộc trò chuyện hoặc tạo cuộc trò chuyện mới');
                return;
            }
            
            // Show loading state
            const sendButton = $('#sendButton');
            const originalText = sendButton.html();
            sendButton.html('<i class="fas fa-spinner fa-spin"></i>');
            sendButton.prop('disabled', true);
            
            $.post('src/controllers/chat.php?action=send_message', {
                conversation_id: currentConversationId,
                message: message
            }, function(data) {
                if (data.success) {
                    $('#messageInput').val('');
                    addMessageToChat(data.message, true);
                    
                    // Refresh conversation list if not connected
                    if (!isConnected) {
                        setTimeout(function() {
                            loadConversations();
                        }, 1000);
                    }
                } else {
                    alert('Lỗi khi gửi tin nhắn: ' + data.error);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Send message error:', status, error);
                alert('Lỗi kết nối server. Vui lòng thử lại.');
            }).always(function() {
                // Restore button state
                sendButton.html(originalText);
                sendButton.prop('disabled', false);
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
            
            $.post('src/controllers/chat.php?action=create_conversation', {
                other_user_id: 'auto' // Let server assign staff
            }, function(data) {
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
            }, 'json').fail(function(xhr, status, error) {
                console.error('Create conversation error:', status, error);
                alert('Lỗi kết nối server. Vui lòng thử lại.');
            }).always(function() {
                // Restore button state
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
        
        // Update connection status
        function updateConnectionStatus(status, message) {
            const statusEl = $('#connectionStatus');
            statusEl.removeClass('connected disconnected').addClass(status);
            statusEl.html(`<i class="fas fa-${status === 'connected' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`);
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
        
        // Scroll to bottom
        function scrollToBottom() {
            const messagesEl = $('#chatMessages');
            messagesEl.scrollTop(messagesEl[0].scrollHeight);
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
        
        // Escape HTML
        function escapeHtml(text) {
            // Check if text is undefined or null
            if (text === undefined || text === null) {
                return '';
            }
            
            // Convert to string if not already
            text = String(text);
            
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
    
    <!-- Socket.IO -->
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    
    <!-- Chat Widget -->
    <?php include 'chat-widget.php'; ?>
</body>
</html>