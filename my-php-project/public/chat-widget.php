<?php
// Chat widget component
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../src/auth/auth.php';

$isLoggedIn = isLoggedIn();
$userId = $isLoggedIn ? getCurrentUserId() : 0;
$currentUserName = $isLoggedIn ? ($_SESSION['user']['HoTen'] ?? $_SESSION['user']['name'] ?? $_SESSION['user_name'] ?? 'User') : 'Guest';
?>

<!-- Chat Widget -->
<div id="chatWidget" class="chat-widget">
    <!-- Chat Toggle Button -->
    <div class="chat-toggle" id="chatToggle">
        <i class="fas fa-comments"></i>
        <span class="chat-badge" id="chatBadge" style="display: none;">0</span>
    </div>
    
    <!-- Chat Window -->
    <div class="chat-window" id="chatWindow" style="display: none;">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-title">
                <i class="fas fa-comments"></i>
                <span id="chatStaffName">Chat Hỗ trợ</span>
                <small id="chatStaffRole" class="d-block" style="font-size: 0.8rem; opacity: 0.8;"></small>
            </div>
            <div class="chat-controls">
                <button class="chat-minimize" id="chatMinimize">
                    <i class="fas fa-minus"></i>
                </button>
                <button class="chat-close" id="chatClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Chat Content -->
        <div class="chat-content">
            <?php if ($isLoggedIn): ?>
                <!-- Logged in user chat -->
                <div class="chat-messages" id="chatMessages">
                    <div class="chat-welcome">
                        <i class="fas fa-comments fa-2x text-primary mb-3"></i>
                        <h5>Chào mừng đến với Chat Hỗ trợ!</h5>
                        <p>Chúng tôi sẽ phản hồi trong thời gian sớm nhất.</p>
                    </div>
                </div>
                
                <!-- Chat Input -->
                <div class="chat-input">
                    <div class="chat-input-group">
                        <input type="text" id="chatMessageInput" placeholder="Nhập tin nhắn..." disabled>
                        <button type="button" id="chatSendButton" disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not logged in -->
                <div class="chat-login">
                    <i class="fas fa-user-lock fa-2x text-muted mb-3"></i>
                    <h5>Vui lòng đăng nhập</h5>
                    <p>Bạn cần đăng nhập để sử dụng chat hỗ trợ.</p>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.chat-widget {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.chat-toggle {
    width: 65px;
    height: 65px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.6rem;
    cursor: pointer;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4), 
                0 4px 10px rgba(118, 75, 162, 0.3);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    border: 3px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-8px);
    }
}

.chat-toggle:hover {
    transform: scale(1.15) translateY(-2px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6), 
                0 8px 20px rgba(118, 75, 162, 0.4),
                0 0 0 8px rgba(102, 126, 234, 0.1);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 30%, #f093fb 70%, #f5576c 100%);
}

.chat-toggle:active {
    transform: scale(1.05) translateY(0px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    animation: ripple 0.6s ease-out;
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

.chat-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(255, 107, 107, 0.4);
    }
    50% {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(255, 107, 107, 0.6);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 4px 12px rgba(255, 107, 107, 0.4);
    }
}

.chat-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-header {
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-title {
    font-weight: 600;
    font-size: 1.1rem;
}

.chat-controls {
    display: flex;
    gap: 0.5rem;
}

.chat-minimize,
.chat-close {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 3px;
    transition: background 0.3s ease;
}

.chat-minimize:hover,
.chat-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.chat-content {
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

.chat-welcome,
.chat-login {
    text-align: center;
    padding: 2rem 1rem;
    color: #6c757d;
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
    max-width: 80%;
    padding: 0.75rem 1rem;
    border-radius: 18px;
    font-size: 0.9rem;
    line-height: 1.4;
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
    border-radius: 20px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
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
    width: 40px;
    height: 40px;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.chat-input button:hover {
    transform: scale(1.1);
}

.chat-input button:disabled {
    opacity: 0.5;
    transform: none;
}

.typing-indicator {
    padding: 0.5rem 1rem;
    color: #6c757d;
    font-style: italic;
    font-size: 0.9rem;
    display: none;
}

.typing-indicator.show {
    display: block;
}

.connection-status {
    padding: 0.5rem 1rem;
    text-align: center;
    font-size: 0.8rem;
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
    .chat-window {
        width: 300px;
        height: 400px;
    }
    
    .chat-widget {
        bottom: 10px;
        right: 10px;
    }
}
</style>

<script>
// Chat Widget JavaScript
$(document).ready(function() {
    let socket;
    let isConnected = false;
    let currentConversationId = null;
    let currentStaffInfo = null;
    let unreadCount = 0;
    
    // Initialize chat widget
    initializeChatWidget();
    
    function initializeChatWidget() {
        // Toggle chat window
        $('#chatToggle').click(function() {
            $('#chatWindow').toggle();
            if ($('#chatWindow').is(':visible')) {
                unreadCount = 0;
                updateBadge();
                
                // Auto-create support conversation if not exists
                if (!currentConversationId) {
                    createSupportConversation();
                }
            }
        });
        
        // Minimize chat
        $('#chatMinimize').click(function() {
            $('#chatWindow').hide();
        });
        
        // Close chat
        $('#chatClose').click(function() {
            $('#chatWindow').hide();
        });
        
        // Send message
        $('#chatSendButton').click(function() {
            sendMessage();
        });
        
        $('#chatMessageInput').keypress(function(e) {
            if (e.which === 13) {
                sendMessage();
            }
        });
        
        // Initialize socket if user is logged in
        <?php if ($isLoggedIn): ?>
        initializeSocket();
        <?php endif; ?>
    }
    
    function initializeSocket() {
        // Check if Socket.IO is available
        if (typeof io === 'undefined') {
            console.warn('Socket.IO not loaded, chat will work without real-time features');
            isConnected = false;
            return;
        }
        
        socket = io('http://localhost:3000');
        
        socket.on('connect', function() {
            console.log('Chat widget connected');
            isConnected = true;
            updateConnectionStatus('connected', 'Đã kết nối');
            
            // Join user room
            socket.emit('join_user_room', {
                userId: <?php echo $userId; ?>
            });
        });
        
        socket.on('disconnect', function() {
            console.log('Chat widget disconnected');
            isConnected = false;
            updateConnectionStatus('disconnected', 'Mất kết nối');
        });
        
        socket.on('connect_error', function(error) {
            console.error('Socket.IO connection error:', error);
            isConnected = false;
            updateConnectionStatus('disconnected', 'Lỗi kết nối');
        });
        
        socket.on('new_message', function(data) {
            if (data.conversation_id === currentConversationId) {
                addMessageToChat(data, false);
            }
            
            // Update badge if chat is closed
            if (!$('#chatWindow').is(':visible')) {
                unreadCount++;
                updateBadge();
            }
        });
        
        socket.on('typing', function(data) {
            showTypingIndicator(data.user_name);
        });
        
        socket.on('stop_typing', function(data) {
            hideTypingIndicator();
        });
    }
    
    function sendMessage() {
        const message = $('#chatMessageInput').val().trim();
        if (!message || !isConnected) return;
        
        if (!currentConversationId) {
            // Create support conversation first
            createSupportConversation().then(() => {
                sendMessageToServer(message);
            });
        } else {
            sendMessageToServer(message);
        }
    }
    
    function createSupportConversation() {
        return $.ajax({
            url: '../../src/controllers/chat-widget.php?action=get_or_create_support_conversation',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    currentConversationId = data.conversation_id;
                    currentStaffInfo = data.staff_info;
                    updateChatHeader();
                    loadMessages();
                } else {
                    showError('Không thể tạo cuộc trò chuyện: ' + data.error);
                }
            },
            error: function(xhr, status, error) {
                showError('Lỗi kết nối: ' + error);
            }
        });
    }
    
    function sendMessageToServer(message) {
        $.ajax({
            url: '../../src/controllers/chat-widget.php?action=send_message',
            type: 'POST',
            dataType: 'json',
            data: {
                conversation_id: currentConversationId,
                message: message
            },
            success: function(data) {
                if (data.success) {
                    addMessageToChat(data.message, true);
                    $('#chatMessageInput').val('');
                    
                    // Emit to Socket.IO for real-time updates (optional)
                    if (socket && isConnected) {
                        try {
                            socket.emit('new_message', {
                                conversation_id: currentConversationId,
                                message: message,
                                sender_id: <?php echo $userId; ?>,
                                sender_name: '<?php echo htmlspecialchars($currentUserName); ?>'
                            });
                        } catch (error) {
                            console.warn('Socket.IO emit failed:', error);
                        }
                    }
                } else {
                    showError('Lỗi khi gửi tin nhắn: ' + data.error);
                }
            },
            error: function(xhr, status, error) {
                showError('Lỗi kết nối: ' + error);
            }
        });
    }
    
    function loadMessages() {
        if (!currentConversationId) return;
        
        $.ajax({
            url: `../../src/controllers/chat-widget.php?action=get_messages&conversation_id=${currentConversationId}`,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    displayMessages(data.messages);
                } else {
                    showError('Không thể tải tin nhắn: ' + data.error);
                }
            },
            error: function(xhr, status, error) {
                showError('Lỗi kết nối: ' + error);
            }
        });
    }
    
    function displayMessages(messages) {
        if (messages.length === 0) {
            $('#chatMessages').html(`
                <div class="chat-welcome">
                    <i class="fas fa-comments fa-2x text-primary mb-3"></i>
                    <h5>Chào mừng đến với Chat Hỗ trợ!</h5>
                    <p>Chúng tôi sẽ phản hồi trong thời gian sớm nhất.</p>
                </div>
            `);
            return;
        }
        
        let html = '';
        messages.forEach(message => {
            const isSent = message.sender_id == <?php echo $userId; ?>;
            html += createMessageHTML(message, isSent);
        });
        
        $('#chatMessages').html(html);
        scrollToBottom();
    }
    
    function createMessageHTML(message, isSent) {
        const time = new Date(message.created_at).toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        return `
            <div class="message ${isSent ? 'sent' : 'received'}">
                <div class="message-content">
                    <div>${escapeHtml(message.message)}</div>
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;
    }
    
    function updateChatHeader() {
        if (currentStaffInfo) {
            $('#chatStaffName').text(currentStaffInfo.HoTen || currentStaffInfo.Email);
            $('#chatStaffRole').text(currentStaffInfo.RoleName || 'Hỗ trợ');
        }
    }
    
    function showError(message) {
        $('#chatMessages').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                ${message}
            </div>
        `);
    }
    
    function addMessageToChat(message, isSent) {
        const time = new Date(message.created_at).toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const messageHTML = `
            <div class="message ${isSent ? 'sent' : 'received'}">
                <div class="message-content">
                    <div>${escapeHtml(message.message)}</div>
                    <div class="message-time">${time}</div>
                </div>
            </div>
        `;
        
        $('#chatMessages .chat-welcome').remove();
        $('#chatMessages').append(messageHTML);
        scrollToBottom();
    }
    
    function showTypingIndicator(userName) {
        $('#chatMessages').append(`
            <div class="typing-indicator show">
                <i class="fas fa-circle fa-xs"></i>
                <i class="fas fa-circle fa-xs"></i>
                <i class="fas fa-circle fa-xs"></i>
                <span class="ms-2">${userName} đang nhập...</span>
            </div>
        `);
        scrollToBottom();
    }
    
    function hideTypingIndicator() {
        $('.typing-indicator').remove();
    }
    
    function updateConnectionStatus(status, message) {
        // You can add connection status indicator here
        console.log('Connection status:', status, message);
    }
    
    function updateBadge() {
        const badge = $('#chatBadge');
        if (unreadCount > 0) {
            badge.text(unreadCount).show();
        } else {
            badge.hide();
        }
    }
    
    function scrollToBottom() {
        const messagesEl = $('#chatMessages');
        messagesEl.scrollTop(messagesEl[0].scrollHeight);
    }
    
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
    
    // Tự động tạo cuộc trò chuyện với staff
    function createAutoConversation() {
        if (!isLoggedIn) return;
        
        $.get('src/controllers/auto-create-conversation.php', function(data) {
            if (data.success) {
                console.log('Auto conversation created:', data.conversation_id);
                // Có thể thêm thông báo chào mừng
                if (data.staff_name) {
                    showNotification('success', `Đã kết nối với ${data.staff_name}. Chào mừng bạn đến với dịch vụ hỗ trợ!`);
                }
                // Reload conversations
                loadConversations();
            }
        }, 'json').fail(function() {
            console.error('Failed to create auto conversation');
        });
    }
    
    // Gọi tạo cuộc trò chuyện tự động khi widget được khởi tạo
    if (isLoggedIn) {
        createAutoConversation();
    }
});
</script>
