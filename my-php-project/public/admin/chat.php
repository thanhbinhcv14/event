<?php
// Include admin header
include 'includes/admin-header.php';

// Check if user is admin or event manager
$userRole = $_SESSION['user']['role'] ?? 0;
if (!in_array($userRole, [1, 3])) {
    echo '<script>window.location.href = "../index.php";</script>';
    exit;
}

// Get current user info - Handle multiple session structures
$currentUserId = $_SESSION['user']['ID_User'] ?? $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;
$currentUserName = $_SESSION['user']['HoTen'] ?? $_SESSION['user']['name'] ?? $_SESSION['user_name'] ?? 'Admin';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-comments"></i>
        Chat Hỗ trợ Khách hàng
    </h1>
    <p class="page-subtitle">Trả lời tin nhắn và hỗ trợ khách hàng trực tuyến</p>
</div>

<!-- Chat Container -->
<div class="chat-admin-container">
    <div class="chat-layout">
        <!-- Sidebar - Conversations List -->
        <div class="chat-sidebar">
            <div class="chat-sidebar-header">
                <h5><i class="fas fa-comments"></i> Cuộc trò chuyện</h5>
                <div class="connection-status" id="connectionStatus">
                    <i class="fas fa-spinner fa-spin"></i> Đang kết nối...
                </div>
            </div>
            
            <div class="conversations-list" id="conversationsList">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải cuộc trò chuyện...</p>
                </div>
            </div>
        </div>
        
        <!-- Main Chat Area -->
        <div class="chat-main">
            <!-- Chat Header -->
            <div class="chat-header" id="chatHeader">
                <div class="chat-user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <h6 id="chatUserName">Chọn cuộc trò chuyện</h6>
                        <small id="chatUserStatus" class="text-muted">Chưa chọn</small>
                        <div class="admin-info" id="adminInfo" style="display: none;">
                            <small class="text-muted">
                                <i class="fas fa-user-shield"></i> 
                                <span id="adminName"><?php echo htmlspecialchars($currentUserName); ?></span> | 
                                <span id="adminRole">Admin</span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="btn btn-sm btn-outline-primary" id="refreshChat">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" id="endChat">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages">
                <div class="chat-welcome">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h5>Chào mừng đến với Chat Hỗ trợ!</h5>
                    <p>Chọn một cuộc trò chuyện để bắt đầu hỗ trợ khách hàng.</p>
                </div>
            </div>
            
            <!-- Typing Indicator -->
            <div class="typing-indicator" id="typingIndicator" style="display: none;">
                <i class="fas fa-circle fa-xs"></i>
                <i class="fas fa-circle fa-xs"></i>
                <i class="fas fa-circle fa-xs"></i>
                <span class="ms-2">Đang nhập...</span>
            </div>
            
            <!-- Chat Input -->
            <div class="chat-input" id="chatInput" style="display: none;">
                <div class="chat-input-group">
                    <input type="text" id="messageInput" placeholder="Nhập tin nhắn..." disabled>
                    <button type="button" id="sendButton" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="chat-quick-replies">
                    <button class="btn btn-sm btn-outline-secondary quick-reply" data-message="Xin chào! Tôi có thể giúp gì cho bạn?">
                        <i class="fas fa-hand-wave"></i> Chào hỏi
                    </button>
                    <button class="btn btn-sm btn-outline-secondary quick-reply" data-message="Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.">
                        <i class="fas fa-thumbs-up"></i> Cảm ơn
                    </button>
                    <button class="btn btn-sm btn-outline-secondary quick-reply" data-message="Bạn có thể cho tôi biết thêm chi tiết về vấn đề này không?">
                        <i class="fas fa-question-circle"></i> Hỏi thêm
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-admin-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    height: calc(100vh - 200px);
    min-height: 600px;
}

.chat-layout {
    display: flex;
    height: 100%;
}

.chat-sidebar {
    width: 350px;
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
}

.chat-sidebar-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    background: white;
}

.chat-sidebar-header h5 {
    margin: 0;
    color: #333;
    font-weight: 600;
}

.connection-status {
    font-size: 0.8rem;
    margin-top: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    text-align: center;
}

.connection-status.connected {
    background: #d4edda;
    color: #155724;
}

.connection-status.disconnected {
    background: #f8d7da;
    color: #721c24;
}

.conversations-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem;
}

.conversation-item {
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: white;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.conversation-item:hover {
    background: #e9ecef;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.conversation-item.active {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border-color: #667eea;
    border-left: 4px solid #667eea;
}

.conversation-user {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.conversation-preview {
    font-size: 0.9rem;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 0.25rem;
}

.conversation-time {
    font-size: 0.8rem;
    color: #adb5bd;
}

.conversation-badge {
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
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

.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
}

.chat-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-user-info {
    display: flex;
    align-items: center;
}

.user-avatar {
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

.chat-actions {
    display: flex;
    gap: 0.5rem;
}

.chat-messages {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    background: #f8f9fa;
}

.chat-welcome {
    text-align: center;
    padding: 2rem;
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

.typing-indicator {
    padding: 0.5rem 1rem;
    color: #6c757d;
    font-style: italic;
    font-size: 0.9rem;
}

.chat-input {
    padding: 1rem;
    background: white;
    border-top: 1px solid #dee2e6;
}

.chat-input-group {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}
.chat-input-group #sendButton {
    width: 50px;
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

.chat-quick-replies {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.quick-reply {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    border-radius: 15px;
    transition: all 0.3s ease;
}

.quick-reply:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

@media (max-width: 768px) {
    .chat-layout {
        flex-direction: column;
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
let chatSocket;
let currentConversationId = null;
let conversations = [];
let isConnected = false;
let currentUserId = <?php echo $currentUserId; ?>;
let currentUserName = '<?php echo htmlspecialchars($currentUserName); ?>';

// Initialize chat
$(document).ready(function() {
    initializeSocket();
    loadConversations();
    setupEventHandlers();
    showAdminInfo();
});

// Show admin information
function showAdminInfo() {
    $('#adminName').text(currentUserName);
    $('#adminRole').text('Admin');
    $('#adminInfo').show();
}

// Initialize Socket.IO connection
function initializeSocket() {
    chatSocket = io('http://localhost:3000');
    
    chatSocket.on('connect', function() {
        console.log('Admin chat connected');
        isConnected = true;
        updateConnectionStatus('connected', 'Đã kết nối');
        
        // Join admin room
        chatSocket.emit('authenticate', {
            userId: currentUserId,
            userRole: <?php echo $userRole; ?>,
            userName: currentUserName
        });
    });
    
    chatSocket.on('disconnect', function() {
        console.log('Admin chat disconnected');
        isConnected = false;
        updateConnectionStatus('disconnected', 'Mất kết nối');
    });
    
    chatSocket.on('new_message', function(data) {
        if (data.conversation_id === currentConversationId) {
            addMessageToChat(data, false);
        }
        updateConversationPreview(data.conversation_id, data.message);
    });
    
    chatSocket.on('typing', function(data) {
        if (data.conversation_id === currentConversationId) {
            showTypingIndicator(data.user_name);
        }
    });
    
    chatSocket.on('stop_typing', function(data) {
        if (data.conversation_id === currentConversationId) {
            hideTypingIndicator();
        }
    });
}

// Load conversations
function loadConversations() {
    $.ajax({
        url: '../../src/controllers/chat.php?action=get_conversations',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Conversations loaded:', data);
            if (data.success) {
                conversations = data.conversations;
                displayConversations();
            } else {
                $('#conversationsList').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        ${data.error || 'Không thể tải danh sách cuộc trò chuyện.'}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            $('#conversationsList').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Lỗi kết nối: ${error}
                </div>
            `);
        }
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
        
        const unreadCount = conv.unread_count || 0;
        
        html += `
            <div class="conversation-item" onclick="selectConversation(${conv.id})" data-conversation-id="${conv.id}">
                <div class="conversation-user">
                    <span>
                        <span class="status-indicator ${conv.is_online ? 'status-online' : 'status-offline'}"></span>
                        ${conv.other_user_name}
                    </span>
                    ${unreadCount > 0 ? `<span class="conversation-badge">${unreadCount}</span>` : ''}
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
    
    // Show chat header and input
    $('#chatHeader').show();
    $('#chatInput').show();
    
    // Enable input
    $('#messageInput').prop('disabled', false);
    $('#sendButton').prop('disabled', false);
    
    // Load messages
    loadMessages(conversationId);
    
    // Join conversation room
    chatSocket.emit('join_conversation', { conversation_id: conversationId });
}

// Load messages for conversation
function loadMessages(conversationId) {
    $.ajax({
        url: `../../src/controllers/chat.php?action=get_messages&conversation_id=${conversationId}`,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Messages loaded:', data);
            if (data.success) {
                displayMessages(data.messages);
            } else {
                $('#chatMessages').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        ${data.error || 'Không thể tải tin nhắn.'}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            $('#chatMessages').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Lỗi kết nối: ${error}
                </div>
            `);
        }
    });
}

// Display messages
function displayMessages(messages) {
    // Kiểm tra dữ liệu messages hợp lệ
    if (!Array.isArray(messages)) {
        console.error('Invalid messages data:', messages);
        $('#chatMessages').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                Lỗi dữ liệu tin nhắn
            </div>
        `);
        return;
    }
    
    if (messages.length === 0) {
        $('#chatMessages').html(`
            <div class="chat-welcome">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <h5>Bắt đầu cuộc trò chuyện</h5>
                <p>Gửi tin nhắn đầu tiên để bắt đầu!</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    let validMessageCount = 0;
    
    messages.forEach((message, index) => {
        const messageHTML = createMessageHTML(message);
        if (messageHTML) {
            html += messageHTML;
            validMessageCount++;
        } else {
            console.warn(`Invalid message at index ${index}:`, message);
        }
    });
    
    // Nếu không có tin nhắn hợp lệ nào
    if (validMessageCount === 0) {
        $('#chatMessages').html(`
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Không có tin nhắn hợp lệ để hiển thị
            </div>
        `);
        return;
    }
    
    $('#chatMessages').html(html);
    scrollToBottom();
}

// Create message HTML
function createMessageHTML(message) {
    // Kiểm tra dữ liệu message hợp lệ
    if (!message || typeof message !== 'object') {
        console.warn('Invalid message data:', message);
        return '';
    }
    
    // Xử lý thời gian với fallback
    let time = '--:--';
    try {
        if (message.created_at) {
            time = new Date(message.created_at).toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    } catch (e) {
        console.warn('Invalid date format:', message.created_at);
    }
    
    const isSent = message.sender_id == currentUserId;
    const messageText = message.message || 'Tin nhắn trống';
    
    // Xử lý tin nhắn đặc biệt
    if (messageText === 'Tin nhắn trống') {
        console.warn('Empty message detected for conversation:', message.conversation_id);
    }
    
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
    
    // Chỉ thêm nếu messageHTML hợp lệ
    if (messageHTML) {
        $('#chatMessages .chat-welcome').remove();
        $('#chatMessages').append(messageHTML);
        scrollToBottom();
    } else {
        console.warn('Failed to create message HTML for:', message);
    }
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
    
    // Quick replies
    $('.quick-reply').click(function() {
        const message = $(this).data('message');
        $('#messageInput').val(message);
        sendMessage();
    });
    
    // Typing indicator
    let typingTimer;
    $('#messageInput').on('input', function() {
        if (currentConversationId && isConnected) {
            chatSocket.emit('typing', {
                conversation_id: currentConversationId,
                user_id: currentUserId
            });
            
            clearTimeout(typingTimer);
            typingTimer = setTimeout(function() {
                chatSocket.emit('stop_typing', {
                    conversation_id: currentConversationId,
                    user_id: currentUserId
                });
            }, 1000);
        }
    });
    
    // Refresh chat
    $('#refreshChat').click(function() {
        if (currentConversationId) {
            loadMessages(currentConversationId);
        }
    });
    
    // End chat
    $('#endChat').click(function() {
        if (confirm('Bạn có chắc muốn kết thúc cuộc trò chuyện này?')) {
            currentConversationId = null;
            $('#chatHeader').hide();
            $('#chatInput').hide();
            $('#chatMessages').html(`
                <div class="chat-welcome">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h5>Chào mừng đến với Chat Hỗ trợ!</h5>
                    <p>Chọn một cuộc trò chuyện để bắt đầu hỗ trợ khách hàng.</p>
                </div>
            `);
        }
    });
}

// Send message
function sendMessage() {
    const message = $('#messageInput').val().trim();
    if (!message || !currentConversationId || !isConnected) return;
    
    $.ajax({
        url: '../../src/controllers/chat.php?action=send_message',
        type: 'POST',
        dataType: 'json',
        data: {
            conversation_id: currentConversationId,
            message: message
        },
        success: function(data) {
            console.log('Message sent:', data);
            if (data.success) {
                $('#messageInput').val('');
                addMessageToChat(data.message, true);
            } else {
                alert('Lỗi khi gửi tin nhắn: ' + (data.error || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response:', xhr.responseText);
            alert('Lỗi kết nối: ' + error);
        }
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
    `).show();
}

// Hide typing indicator
function hideTypingIndicator() {
    $('#typingIndicator').hide();
}

// Update conversation preview
function updateConversationPreview(conversationId, message) {
    const convEl = $(`.conversation-item[data-conversation-id="${conversationId}"]`);
    if (convEl.length) {
        // Xử lý message an toàn
        const safeMessage = message || 'Tin nhắn mới';
        convEl.find('.conversation-preview').text(safeMessage);
        
        // Cập nhật thời gian với error handling
        try {
            const currentTime = new Date().toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit'
            });
            convEl.find('.conversation-time').text(currentTime);
        } catch (e) {
            console.warn('Error updating conversation time:', e);
            convEl.find('.conversation-time').text('--:--');
        }
    }
}

// Scroll to bottom
function scrollToBottom() {
    const messagesEl = $('#chatMessages');
    if (messagesEl.length && messagesEl[0]) {
        try {
            messagesEl.scrollTop(messagesEl[0].scrollHeight);
        } catch (e) {
            console.warn('Error scrolling to bottom:', e);
        }
    }
}

// Escape HTML
function escapeHtml(text) {
    // Kiểm tra nếu text là null, undefined hoặc không phải string
    if (!text || typeof text !== 'string') {
        return '';
    }
    
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Auto refresh conversations every 30 seconds
setInterval(() => {
    if (isConnected) {
        loadConversations();
    }
}, 30000);
</script>

<?php include 'includes/admin-footer.php'; ?>
