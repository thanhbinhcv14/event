<?php
// Include admin header
include 'includes/admin-header.php';

// Check if user has admin/staff privileges
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? 0;
if (!in_array($userRole, [1, 2, 3, 4])) {
    echo '<script>window.location.href = "../index.php";</script>';
    exit;
}

// Get current user info - Handle multiple session structures
$currentUserId = $_SESSION['user']['ID_User'] ?? $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;
$currentUserName = $_SESSION['user']['HoTen'] ?? $_SESSION['user']['name'] ?? $_SESSION['user_name'] ?? 'Admin';
$currentUserRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? 0;

// Get role name from database
$currentRoleName = 'Admin'; // Default fallback
if ($currentUserRole > 0) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT RoleName FROM phanquyen WHERE ID_Role = ?");
        $stmt->execute([$currentUserRole]);
        $roleData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($roleData) {
            $currentRoleName = $roleData['RoleName'];
        }
    } catch (Exception $e) {
        error_log("Error getting role name: " . $e->getMessage());
    }
}

// Debug logging for current user
error_log("Admin chat - Current user ID: " . $currentUserId);
error_log("Admin chat - Current user name: " . $currentUserName);
error_log("Admin chat - Current user role: " . $currentUserRole);
error_log("Admin chat - Current role name: " . $currentRoleName);
error_log("Admin chat - Session data: " . json_encode($_SESSION));
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
                <div class="online-count">
                    <span class="badge bg-success" id="onlineCount">0</span> trực tuyến
                </div>
            </div>
            
            <div class="customer-search">
                <div class="input-group">
                    <input type="text" class="form-control" id="customerSearch" placeholder="Tìm kiếm khách hàng...">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
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
                                <span id="adminRole"><?php echo htmlspecialchars($currentRoleName); ?></span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="btn btn-sm btn-outline-primary" id="refreshChat">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info" id="transferChat" disabled>
                        <i class="fas fa-exchange-alt"></i> Chuyển
                    </button>
                    <button class="btn btn-sm btn-outline-danger" id="endChat" disabled>
                        <i class="fas fa-times"></i> Kết thúc
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

<!-- Quick Reply Modal -->
<div class="modal fade" id="quickReplyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Trả lời nhanh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="quick-reply-templates">
                    <div class="template-item" data-template="greeting">
                        <strong>Chào hỏi</strong>
                        <p>Xin chào! Tôi có thể giúp gì cho bạn?</p>
                    </div>
                    <div class="template-item" data-template="thanks">
                        <strong>Cảm ơn</strong>
                        <p>Cảm ơn bạn đã liên hệ! Chúng tôi sẽ hỗ trợ bạn ngay.</p>
                    </div>
                    <div class="template-item" data-template="wait">
                        <strong>Chờ đợi</strong>
                        <p>Vui lòng chờ một chút, tôi đang kiểm tra thông tin cho bạn.</p>
                    </div>
                    <div class="template-item" data-template="end">
                        <strong>Kết thúc</strong>
                        <p>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi. Chúc bạn một ngày tốt lành!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Chat Modal -->
<div class="modal fade" id="transferChatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chuyển cuộc trò chuyện</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Chuyển đến:</label>
                    <select class="form-select" id="transferTo">
                        <option value="">Chọn nhân viên hỗ trợ</option>
                        <!-- Options will be loaded dynamically -->
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ghi chú (tùy chọn):</label>
                    <textarea class="form-control" id="transferNote" rows="3" placeholder="Lý do chuyển cuộc trò chuyện..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="confirmTransfer">Chuyển</button>
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
    transition: all 0.3s ease;
}

.status-online {
    background: #28a745;
    box-shadow: 0 0 6px rgba(40, 167, 69, 0.6);
    animation: pulse-online 2s infinite;
}

.status-offline {
    background: #6c757d;
    opacity: 0.6;
}

@keyframes pulse-online {
    0% {
        box-shadow: 0 0 6px rgba(40, 167, 69, 0.6);
    }
    50% {
        box-shadow: 0 0 12px rgba(40, 167, 69, 0.8);
    }
    100% {
        box-shadow: 0 0 6px rgba(40, 167, 69, 0.6);
    }
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
    transition: all 0.3s ease;
    animation: messageSlideIn 0.3s ease-out;
}

@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
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
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    transition: all 0.3s ease;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    border: none;
    font-weight: 500;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.quick-reply:hover {
    background: linear-gradient(45deg, #5a6fd8, #6a4190);
    color: white;
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.quick-reply:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.quick-reply.active {
    background: linear-gradient(45deg, #4a5bc4, #5a2f7a);
    transform: scale(0.95);
    opacity: 0.8;
}

.quick-reply:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.online-count {
    margin-top: 0.5rem;
    font-size: 0.8rem;
}

.customer-search {
    padding: 0.75rem;
    border-bottom: 1px solid #dee2e6;
}

.template-item {
    padding: 15px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.template-item:hover {
    background: #f8f9fa;
    border-color: #667eea;
}

.template-item strong {
    color: #333;
    display: block;
    margin-bottom: 5px;
}

.template-item p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
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

/* New message notification styles */
.new-message-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    z-index: 9999;
    font-size: 14px;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.new-message-notification.show {
    transform: translateX(0);
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
    setUserOnline(); // Set admin online
    loadConversations();
    loadOnlineUsers();
    setupEventHandlers();
    showAdminInfo();
    startAutoRefresh();
    
    // Set user offline when page is closed
    $(window).on('beforeunload', function() {
        setUserOffline();
    });
});

// Set user online
function setUserOnline() {
    $.ajax({
        url: '../../src/controllers/chat-controller.php?action=set_user_online',
        type: 'POST',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                console.log('Admin set online successfully');
            } else {
                console.error('Failed to set admin online:', data.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error setting admin online:', error);
        }
    });
}

// Set user offline
function setUserOffline() {
    $.ajax({
        url: '../../src/controllers/chat-controller.php?action=set_user_offline',
        type: 'POST',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                console.log('Admin set offline successfully');
            } else {
                console.error('Failed to set admin offline:', data.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error setting admin offline:', error);
        }
    });
}

// Update user activity
function updateUserActivity() {
    $.ajax({
        url: '../../src/controllers/chat-controller.php?action=update_activity',
        type: 'POST',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                console.log('Activity updated successfully');
            } else {
                console.error('Failed to update activity:', data.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error updating activity:', error);
        }
    });
}

// Show admin information
function showAdminInfo() {
    $('#adminName').text(currentUserName);
    $('#adminRole').text('Admin');
    $('#adminInfo').show();
}

// Initialize Socket.IO connection with better fallback
function initializeSocket() {
    console.log('Initializing Socket.IO...');
    
    // Check if Socket.IO is available
    if (typeof io === 'undefined') {
        console.warn('Socket.IO not loaded, using AJAX fallback');
        isConnected = false;
        updateConnectionStatus('disconnected', 'Chế độ offline - Sử dụng AJAX');
        startPollingMode();
        return;
    }
    
    console.log('Socket.IO available, creating connection...');
    chatSocket = io('http://localhost:3000', {
        timeout: 3000,
        reconnection: true,
        reconnectionAttempts: 5,
        reconnectionDelay: 1000,
        forceNew: true,
        transports: ['websocket', 'polling']
    });
    
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
        
        // Rejoin current conversation if any
        if (currentConversationId) {
            chatSocket.emit('join_conversation', { conversation_id: currentConversationId });
        }
        
        // Load online users when connected
        loadOnlineUsers();
    });
    
    console.log('Socket.IO event handlers set up successfully');
    
    chatSocket.on('disconnect', function() {
        console.log('Admin chat disconnected');
        isConnected = false;
        updateConnectionStatus('disconnected', 'Mất kết nối');
    });
    
    chatSocket.on('reconnect', function() {
        console.log('Admin chat reconnected');
        isConnected = true;
        updateConnectionStatus('connected', 'Đã kết nối lại');
        
        // Re-authenticate
        chatSocket.emit('authenticate', {
            userId: currentUserId,
            userRole: <?php echo $userRole; ?>,
            userName: currentUserName
        });
        
        // Rejoin current conversation if any
        if (currentConversationId) {
            chatSocket.emit('join_conversation', { conversation_id: currentConversationId });
        }
    });
    
    chatSocket.on('connect_error', function(error) {
        console.error('Admin chat connection error:', error);
        isConnected = false;
        updateConnectionStatus('disconnected', 'Lỗi kết nối - Chế độ offline');
        startPollingMode();
    });
    
    chatSocket.on('new_message', function(data) {
        console.log('Admin received new message:', data);
        if (data.conversation_id === currentConversationId) {
            addMessageToChat(data, false);
            // Scroll to bottom immediately
            setTimeout(scrollToBottom, 100);
        }
        updateConversationPreview(data.conversation_id, data.message);
        
        // Update conversation list for real-time sync
        loadConversations();
        
        // Update online count when new message received
        loadOnlineUsers();
    });
    
    chatSocket.on('typing', function(data) {
        console.log('User typing:', data);
        if (data.conversation_id === currentConversationId && data.user_id !== currentUserId) {
            showTypingIndicator(data.user_name);
        }
    });
    
    chatSocket.on('stop_typing', function(data) {
        console.log('User stopped typing:', data);
        if (data.conversation_id === currentConversationId && data.user_id !== currentUserId) {
            hideTypingIndicator();
        }
    });
    
    chatSocket.on('message_read', function(data) {
        console.log('Message read:', data);
        if (data.conversation_id === currentConversationId) {
            updateMessageReadStatus(data.message_id);
        }
    });
    
    chatSocket.on('conversation_updated', function(data) {
        console.log('Conversation updated:', data);
        if (data.conversation_id === currentConversationId) {
            // Refresh conversation list
            loadConversations();
        }
    });
    
    // Handle broadcast messages
    chatSocket.on('broadcast_message', function(data) {
        console.log('Admin received broadcast message:', data);
        if (data.conversation_id === currentConversationId && data.userId !== currentUserId) {
            addMessageToChat(data.message, false);
            scrollToBottom();
        }
        updateConversationPreview(data.conversation_id, data.message.message || data.message.text);
    });
    
    // Handle user online status updates
    chatSocket.on('user_online', function(data) {
        console.log('User came online:', data);
        loadOnlineUsers();
    });
    
    chatSocket.on('user_offline', function(data) {
        console.log('User went offline:', data);
        loadOnlineUsers();
    });
    
    // Handle online users count update
    chatSocket.on('online_count_update', function(data) {
        console.log('Online count updated:', data);
        $('#onlineCount').text(data.count);
        
        // Update badge color based on count
        const badge = $('#onlineCount');
        if (data.count > 0) {
            badge.removeClass('bg-secondary').addClass('bg-success');
        } else {
            badge.removeClass('bg-success').addClass('bg-secondary');
        }
    });
}

// Load conversations
function loadConversations() {
    $.ajax({
        url: '../../src/controllers/chat-controller.php?action=get_conversations',
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

// Load online users count
function loadOnlineUsers() {
    console.log('Loading online users...');
    $.ajax({
        url: '../../src/controllers/chat-controller.php?action=get_online_count',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Online count response:', data);
            if (data.success) {
                const count = data.count || 0;
                $('#onlineCount').text(count);
                
                // Update badge color based on count
                const badge = $('#onlineCount');
                if (count > 0) {
                    badge.removeClass('bg-secondary').addClass('bg-success');
                } else {
                    badge.removeClass('bg-success').addClass('bg-secondary');
                }
                
                console.log('Online count updated:', count);
                
                // Debug information
                if (data.debug && data.debug.online_users) {
                    console.log('Debug online users:', data.debug.online_users);
                    console.log('Debug query time:', data.debug.query_time);
                }
            } else {
                console.error('Failed to load online count:', data.error);
                $('#onlineCount').text('?');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading online count:', error);
            console.error('XHR response:', xhr.responseText);
            $('#onlineCount').text('?');
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
        
        // Debug: Log conversation data
        console.log('Conversation:', conv.id, 'User:', conv.other_user_name, 'Online:', conv.is_online);
        
        html += `
            <div class="conversation-item" onclick="selectConversation(${conv.id})" data-conversation-id="${conv.id}">
                <div class="conversation-user">
                    <span>
                        <span class="status-indicator ${conv.is_online ? 'status-online' : 'status-offline'}" 
                              title="${conv.is_online ? 'Đang online' : 'Đang offline'}"></span>
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
    console.log('Admin selecting conversation:', conversationId);
    currentConversationId = conversationId;
    
    // Update user activity
    updateUserActivity();
    
    // Update UI
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-conversation-id="${conversationId}"]`).addClass('active');
    
    // Show chat header and input
    $('#chatHeader').show();
    $('#chatInput').show();
    
    // Enable input
    $('#messageInput').prop('disabled', false);
    $('#sendButton').prop('disabled', false);
    
    // Join conversation room for real-time updates
    if (isConnected && chatSocket) {
        chatSocket.emit('join_conversation', { conversation_id: conversationId });
    }
    
    // Load messages with real-time updates
    loadMessagesWithRealTime(conversationId);
}

// Load messages for conversation
function loadMessages(conversationId) {
    console.log('loadMessages called with conversationId:', conversationId);
    
    // Show loading state only if no messages are currently displayed
    if ($('#chatMessages .message').length === 0) {
        $('#chatMessages').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Đang tải tin nhắn...</p>
            </div>
        `);
    }
    
    $.ajax({
        url: `../../src/controllers/chat-controller.php?action=get_messages&conversation_id=${conversationId}`,
        type: 'GET',
        dataType: 'json',
        timeout: 10000,
        success: function(data) {
            console.log('Messages loaded:', data);
            if (data.success) {
                displayMessages(data.messages);
                
                // Emit message read event for real-time updates
                if (isConnected && chatSocket) {
                    chatSocket.emit('messages_loaded', { 
                        conversation_id: conversationId,
                        userId: currentUserId
                    });
                }
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
            
            $('#chatMessages').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    ${errorMessage}
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
    
    // Add animation for new messages
    $('#chatMessages').html(html);
    
    // Animate new messages
    $('.message').each(function(index) {
        $(this).css({
            opacity: 0,
            transform: 'translateY(20px)'
        }).delay(index * 50).animate({
            opacity: 1
        }, 300).css('transform', 'translateY(0)');
    });
    
    scrollToBottom();
}

// Create message HTML
function createMessageHTML(message) {
    // Kiểm tra dữ liệu message hợp lệ
    if (!message || typeof message !== 'object') {
        console.warn('Invalid message data:', message);
        return '';
    }
    
    // Debug logging
    console.log('Creating message HTML for:', message);
    
    // Xử lý thời gian với fallback
    let time = '--:--';
    try {
        if (message.created_at) {
            const date = new Date(message.created_at);
            if (!isNaN(date.getTime())) {
                time = date.toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } else {
                console.warn('Invalid date:', message.created_at);
                // Fallback to current time if date is invalid
                time = new Date().toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        } else {
            // Use current time if no created_at
            time = new Date().toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    } catch (e) {
        console.warn('Date parsing error:', e, 'for date:', message.created_at);
        // Fallback to current time
        time = new Date().toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // In admin chat, messages from admin (currentUserId) are on the right
    // Messages from customers (other users) are on the left
    const isSent = message.sender_id == currentUserId;
    const messageText = message.message || 'Tin nhắn trống';
    const messageId = message.id || message.message_id || `temp-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    const isRead = message.IsRead == 1;
    
    // Debug logging
    console.log('Message details:', {
        messageId: messageId,
        time: time,
        isSent: isSent,
        messageText: messageText,
        isRead: isRead,
        sender_id: message.sender_id,
        currentUserId: currentUserId
    });
    
    // Xử lý tin nhắn đặc biệt
    if (messageText === 'Tin nhắn trống') {
        console.warn('Empty message detected for conversation:', message.conversation_id);
    }
    
    return `
        <div class="message ${isSent ? 'sent' : 'received'}" data-message-id="${messageId}">
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
    console.log('Admin adding message to chat:', message, 'isSent:', isSent);
    const messageHTML = createMessageHTML(message);
    
    // Chỉ thêm nếu messageHTML hợp lệ
    if (messageHTML) {
        // Check for duplicate messages
        const messageId = message.id || message.message_id || '';
        if (messageId && $(`.message[data-message-id="${messageId}"]`).length > 0) {
            console.log('Duplicate message detected, skipping:', messageId);
            return;
        }
        
        // Remove welcome screen
        $('#chatMessages .chat-welcome').remove();
        
        // Add message with animation
        const $messageElement = $(messageHTML);
        $messageElement.css({
            opacity: 0,
            transform: 'translateY(20px)'
        });
        $('#chatMessages').append($messageElement);
        
        // Animate message appearance
        $messageElement.animate({
            opacity: 1
        }, 300).css('transform', 'translateY(0)');
        
        // Scroll to bottom immediately
        scrollToBottom();
        
        // Update conversation list if not connected
        if (!isConnected) {
            setTimeout(function() {
                loadConversations();
            }, 500);
        }
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
    
    // Quick replies - Use event delegation for dynamically added elements
    console.log('Setting up quick reply event handlers');
    $(document).on('click', '.quick-reply', function(e) {
        console.log('Quick reply event triggered');
        console.log('Event target:', e.target);
        console.log('Event currentTarget:', e.currentTarget);
        console.log('Button element:', $(this));
        console.log('Button data message:', $(this).data('message'));
        e.preventDefault();
        e.stopPropagation();
        
        try {
            console.log('Quick reply clicked');
            const message = $(this).data('message');
            const button = $(this);
            
            console.log('Message:', message);
            console.log('Current conversation ID:', currentConversationId);
            console.log('Button element:', button);
            
            if (!message) {
                console.error('No message data found');
                alert('Không tìm thấy dữ liệu tin nhắn');
                return;
            }
            
            if (!currentConversationId) {
                console.error('No conversation selected');
                alert('Vui lòng chọn cuộc trò chuyện trước khi gửi tin nhắn');
                return;
            }
            
            // Add visual feedback
            button.addClass('active');
            button.prop('disabled', true);
            
            // Set message and send
            $('#messageInput').val(message);
            console.log('Message set in input:', $('#messageInput').val());
            
            // Add animation
            button.css({
                'transform': 'scale(0.95)',
                'opacity': '0.8'
            });
            
            // Send message
            console.log('Calling sendMessage()');
            sendMessage();
            
            // Reset button state after a short delay
            setTimeout(() => {
                button.removeClass('active');
                button.prop('disabled', false);
                button.css({
                    'transform': 'scale(1)',
                    'opacity': '1'
                });
            }, 1000);
        } catch (error) {
            console.error('Quick reply error:', error);
            alert('Lỗi khi gửi tin nhắn nhanh: ' + error.message);
        }
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
    
    // Transfer chat
    $('#transferChat').click(function() {
        const modal = new bootstrap.Modal(document.getElementById('transferChatModal'));
        modal.show();
    });
    
    // Customer search
    $('#customerSearch').on('input', function() {
        const query = $(this).val();
        if (query.length >= 2) {
            searchConversations(query);
        } else {
            loadConversations();
        }
    });
}

// Send message
function sendMessage() {
    const message = $('#messageInput').val().trim();
    if (!message || !currentConversationId) return;
    
    // Update user activity
    updateUserActivity();
    
    // Show loading state
    const sendButton = $('#sendButton');
    const originalText = sendButton.html();
    sendButton.html('<i class="fas fa-spinner fa-spin"></i>');
    sendButton.prop('disabled', true);
    
    $.ajax({
        url: '../../src/controllers/chat-controller.php?action=send_message',
        type: 'POST',
        dataType: 'json',
        timeout: 10000,
        data: {
            conversation_id: currentConversationId,
            message: message
        },
        success: function(data) {
            console.log('Message sent:', data);
            if (data.success) {
                $('#messageInput').val('');
                
                // Add message immediately for instant feedback
                addMessageToChat(data.message, true);
                scrollToBottom();
                
                // Emit real-time events (only for other users, not self)
                if (isConnected && chatSocket) {
                    chatSocket.emit('broadcast_message', {
                        conversation_id: currentConversationId,
                        message: data.message,
                        userId: currentUserId,
                        timestamp: new Date().toISOString()
                    });
                    
                    chatSocket.emit('stop_typing', {
                        conversation_id: currentConversationId,
                        user_id: currentUserId
                    });
                } else {
                    // If not connected, trigger immediate refresh for other users
                    setTimeout(function() {
                        loadConversations();
                        if (currentConversationId) {
                            loadMessages(currentConversationId);
                        }
                    }, 1000);
                }
                
                // Update conversation preview immediately
                updateConversationPreview(currentConversationId, data.message.message || data.message.text);
            } else {
                alert('Lỗi khi gửi tin nhắn: ' + (data.error || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
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

// Update connection status
function updateConnectionStatus(status, message) {
    const statusEl = $('#connectionStatus');
    statusEl.removeClass('connected disconnected').addClass(status);
    
    if (status === 'connected') {
        statusEl.html(`<i class="fas fa-check-circle text-success"></i> <span class="text-success">${message}</span>`);
        statusEl.css('background', 'linear-gradient(135deg, #d4edda, #c3e6cb)');
    } else {
        statusEl.html(`<i class="fas fa-exclamation-circle text-warning"></i> <span class="text-warning">${message}</span>`);
        statusEl.css('background', 'linear-gradient(135deg, #f8d7da, #f5c6cb)');
    }
    
    // Update real-time status
    if (status === 'connected') {
        console.log('Admin real-time connection established');
        // Rejoin current conversation if any
        if (currentConversationId && chatSocket) {
            chatSocket.emit('join_conversation', { conversation_id: currentConversationId });
        }
    } else {
        console.log('Admin real-time connection lost, using fallback mode');
        // Start polling mode if not already started
        if (!isConnected) {
            startPollingMode();
        }
    }
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

// Update message read status
function updateMessageReadStatus(messageId) {
    $(`.message[data-message-id="${messageId}"] .message-time`).html(function() {
        return $(this).html().replace('<i class="fas fa-check text-muted"></i>', '<i class="fas fa-check-double text-primary"></i>');
    });
}

// Auto-refresh conversations every 30 seconds if not connected
function startAutoRefresh() {
    if (!isConnected) {
        setInterval(function() {
            loadConversations();
            loadOnlineUsers();
        }, 30000);
    }
    
    // Update user activity every 2 minutes to maintain online status
    setInterval(function() {
        updateUserActivity();
    }, 120000); // 2 minutes
}

// Start polling mode for real-time messaging
function startPollingMode() {
    console.log('Starting polling mode for real-time messaging');
    
    // Poll for new messages every 2 seconds
    setInterval(function() {
        if (currentConversationId) {
            checkForNewMessages();
        }
        loadConversations();
        loadOnlineUsers();
    }, 2000);
    
    // Poll for conversation updates every 5 seconds
    setInterval(function() {
        loadConversations();
    }, 5000);
}

// Check for new messages in current conversation
function checkForNewMessages() {
    if (!currentConversationId) return;
    
    $.getJSON('../../src/controllers/chat-controller.php?action=get_messages&conversation_id=' + currentConversationId, function(res) {
        if (res.success && res.messages) {
            const currentMessageCount = $('#chatMessages .message').length;
            const newMessageCount = res.messages.length;
            
            if (newMessageCount > currentMessageCount) {
                // New messages detected, reload and scroll to bottom
                displayMessages(res.messages);
                scrollToBottom();
                
                // Show notification for new messages
                showNewMessageNotification();
            }
        }
    }).fail(function() {
        console.log('Failed to check for new messages');
    });
}

// Show notification for new messages
function showNewMessageNotification() {
    // Create notification element
    const notification = $('<div class="new-message-notification">Tin nhắn mới!</div>');
    $('body').append(notification);
    
    // Animate notification
    notification.css({
        position: 'fixed',
        top: '20px',
        right: '20px',
        background: '#28a745',
        color: 'white',
        padding: '10px 20px',
        borderRadius: '5px',
        zIndex: 9999,
        fontSize: '14px',
        fontWeight: 'bold',
        boxShadow: '0 4px 12px rgba(0,0,0,0.3)',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease'
    });
    
    // Show notification
    setTimeout(() => {
        notification.css('transform', 'translateX(0)');
    }, 100);
    
    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.css('transform', 'translateX(100%)');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Real-time message update handler
function handleRealTimeMessage(data) {
    console.log('Admin handling real-time message:', data);
    
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
    console.log('Admin loading messages with real-time updates for:', conversationId);
    
    // Load messages immediately
    loadMessages(conversationId);
    
    // Set up real-time listeners for this conversation
    if (isConnected && chatSocket) {
        chatSocket.emit('join_conversation', { conversation_id: conversationId });
    }
}

// Broadcast message instantly to all connected users
function broadcastMessageInstantly(messageData) {
    if (isConnected && chatSocket) {
        chatSocket.emit('broadcast_message', {
            conversation_id: currentConversationId,
            message: messageData,
            userId: currentUserId,
            timestamp: new Date().toISOString()
        });
    }
}

// Handle instant message broadcasting - moved inside initializeSocket function

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

// Search conversations
function searchConversations(query) {
    $.ajax({
        url: '../../src/controllers/chat-controller.php?action=search_conversations',
        type: 'GET',
        data: { query: query },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                conversations = data.conversations;
                displayConversations();
            }
        },
        error: function(xhr, status, error) {
            console.error('Search error:', error);
        }
    });
}

// Load transfer options
function loadTransferOptions() {
    const options = [
        { value: 'support1', text: 'Nhân viên hỗ trợ 1' },
        { value: 'support2', text: 'Nhân viên hỗ trợ 2' },
        { value: 'manager', text: 'Quản lý' }
    ];
    
    const select = $('#transferTo');
    select.empty().append('<option value="">Chọn nhân viên hỗ trợ</option>');
    options.forEach(option => {
        select.append(`<option value="${option.value}">${option.text}</option>`);
    });
}

// Confirm transfer
$('#confirmTransfer').click(function() {
    const transferTo = $('#transferTo').val();
    const transferNote = $('#transferNote').val();
    
    if (transferTo && currentConversationId) {
        $.ajax({
            url: '../../src/controllers/chat-controller.php?action=transfer_chat',
            type: 'POST',
            data: {
                conversation_id: currentConversationId,
                transfer_to: transferTo,
                note: transferNote
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    alert('Đã chuyển cuộc trò chuyện thành công');
                    bootstrap.Modal.getInstance(document.getElementById('transferChatModal')).hide();
                } else {
                    alert('Lỗi chuyển cuộc trò chuyện: ' + data.error);
                }
            },
            error: function() {
                alert('Lỗi chuyển cuộc trò chuyện');
            }
        });
    } else {
        alert('Vui lòng chọn người nhận chuyển cuộc trò chuyện');
    }
});

// Quick reply template selection
$(document).on('click', '.template-item', function() {
    const templateText = $(this).find('p').text();
    $('#messageInput').val(templateText);
    bootstrap.Modal.getInstance(document.getElementById('quickReplyModal')).hide();
});

// Initialize transfer options on page load
$(document).ready(function() {
    loadTransferOptions();
});

// Auto refresh conversations every 30 seconds
setInterval(() => {
    if (isConnected) {
        loadConversations();
    }
}, 30000);
</script>

<?php include 'includes/admin-footer.php'; ?>
