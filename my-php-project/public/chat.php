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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .chat-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            overflow: hidden;
            max-width: 1200px;
        }
        
        .chat-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .chat-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .chat-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .chat-content {
            display: flex;
            height: 600px;
        }
        
        .chat-sidebar {
            width: 300px;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
        }
        
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 1rem;
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
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.received .message-content {
            background: white;
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
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .chat-input button:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .chat-input button:disabled {
            opacity: 0.5;
            transform: none;
        }
        
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .conversation-item:hover {
            background: #e9ecef;
        }
        
        .conversation-item.active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-left: 4px solid #667eea;
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
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .status-online {
            background: #28a745;
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
            padding: 0.5rem 1rem;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .connection-status.connected {
            background: #d4edda;
            color: #155724;
        }
        
        .connection-status.disconnected {
            background: #f8d7da;
            color: #721c24;
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
                            <input type="text" id="messageInput" placeholder="Nhập tin nhắn..." disabled>
                            <button type="button" id="sendButton" disabled>
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
            $.get('src/controllers/profile.php?action=get_profile', function(data) {
                if (data.success) {
                    $('#userName').text(data.user.HoTen || data.user.Email || 'Người dùng');
                    $('#userEmail').text(data.user.Email || '');
                    $('#userInfo').show();
                } else {
                    console.error('Failed to load user info:', data.error);
                    // Fallback to session data
                    $('#userName').text('Người dùng');
                    $('#userEmail').text('Đang tải...');
                    $('#userInfo').show();
                }
            }, 'json').fail(function() {
                console.error('Failed to load user info');
                // Fallback to session data
                $('#userName').text('Người dùng');
                $('#userEmail').text('Đang tải...');
                $('#userInfo').show();
            });
        }
        
        // Initialize Socket.IO connection
        function initializeSocket() {
            socket = io('http://localhost:3000');
            
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
                    conversations = data.conversations;
                    console.log('Conversations loaded:', conversations);
                    displayConversations();
                } else {
                    console.error('Failed to load conversations:', data.error);
                    $('#conversationsList').html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Không thể tải danh sách cuộc trò chuyện: ${data.error || 'Unknown error'}
                        </div>
                    `);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                $('#conversationsList').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Lỗi kết nối: ${error}
                    </div>
                `);
            });
        }
        
        // Display conversations
        function displayConversations() {
            if (conversations.length === 0) {
                $('#conversationsList').html(`
                    <div class="text-center text-muted">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p>Chưa có cuộc trò chuyện nào</p>
                    </div>
                `);
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
        
        // Load messages for conversation
        function loadMessages(conversationId) {
            console.log('loadMessages called with conversationId:', conversationId);
            
            $.get(`src/controllers/chat.php?action=get_messages&conversation_id=${conversationId}`, function(data) {
                console.log('loadMessages response:', data);
                
                if (data.success) {
                    console.log('Messages loaded successfully:', data.messages);
                    displayMessages(data.messages);
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
            
            return `
                <div class="message ${isSent ? 'sent' : 'received'}">
                    <div class="message-content">
                        <div>${escapeHtml(messageText)}</div>
                        <div class="message-time">${time}</div>
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
            if (!message || !currentConversationId || !isConnected) return;
            
            $.post('src/controllers/chat.php?action=send_message', {
                conversation_id: currentConversationId,
                message: message
            }, function(data) {
                if (data.success) {
                    $('#messageInput').val('');
                    addMessageToChat(data.message, true);
                } else {
                    alert('Lỗi khi gửi tin nhắn: ' + data.error);
                }
            }, 'json');
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
    
    <!-- Chat Widget -->
    <?php include 'chat-widget.php'; ?>
</body>
</html>