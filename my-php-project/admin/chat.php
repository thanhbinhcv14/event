<?php
// Bao gồm header admin
include 'includes/admin-header.php';

// Kiểm tra quyền admin/nhân viên
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? 0;
if (!in_array($userRole, [1, 2, 3, 4])) {
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// Lấy thông tin user hiện tại - Xử lý nhiều cấu trúc session
$currentUserId = $_SESSION['user']['ID_User'] ?? $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;
$currentUserRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? 0;

// Lấy tên user từ bảng phù hợp dựa trên role
$currentUserName = 'Admin'; // Giá trị mặc định
if ($currentUserId > 0) {
    try {
        $pdo = getDBConnection();
        
        // Kiểm tra nếu user là nhân viên (role 1,2,3,4) - lấy từ nhanvieninfo
        if (in_array($currentUserRole, [1, 2, 3, 4])) {
            $stmt = $pdo->prepare("SELECT HoTen FROM nhanvieninfo WHERE ID_User = ?");
            $stmt->execute([$currentUserId]);
            $staffData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($staffData) {
                $currentUserName = $staffData['HoTen'];
            }
        } else {
            // Kiểm tra nếu user là khách hàng (role 5) - lấy từ khachhanginfo
            $stmt = $pdo->prepare("SELECT HoTen FROM khachhanginfo WHERE ID_User = ?");
            $stmt->execute([$currentUserId]);
            $customerData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($customerData) {
                $currentUserName = $customerData['HoTen'];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting user name: " . $e->getMessage());
        $currentUserName = 'Admin'; // Giá trị dự phòng
    }
}

// Lấy tên role từ database
$currentRoleName = 'Admin'; // Giá trị mặc định
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

// Ghi log debug cho user hiện tại
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
                        <small id="chatUserStatus" class="text-muted" style="display: none;"></small>
                    </div>
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
                <div class="chat-quick-replies">
                    <button class="btn btn-sm btn-outline-secondary quick-reply" data-message="Xin chào! Tôi có thể giúp gì cho bạn?" title="Chào hỏi">
                        <i class="fas fa-hand"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary quick-reply" data-message="Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất." title="Cảm ơn">
                        <i class="fas fa-thumbs-up"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary quick-reply" data-message="Bạn có thể cho tôi biết thêm chi tiết về vấn đề này không?" title="Hỏi thêm">
                        <i class="fas fa-question-circle"></i>
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

<!-- Call Modal -->
<div class="modal fade" id="callModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="call-avatar mb-3">
                    <i class="fas fa-user fa-3x"></i>
                </div>
                <h5 id="callerName">Đang gọi...</h5>
                <p id="callType" class="text-muted">Cuộc gọi thoại</p>
                <div class="call-status mb-3" id="callStatus">Đang kết nối...</div>
                <div class="call-controls" id="callControls">
                    <button class="btn btn-success btn-lg me-2" onclick="acceptCall()">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="btn btn-danger btn-lg" onclick="rejectCall()">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Video Call Container -->
<div class="video-call-container" id="videoCallContainer" style="display: none;">
    <video id="remoteVideo" class="remote-video" autoplay playsinline></video>
    <video id="localVideo" class="local-video" autoplay playsinline muted></video>
    <div class="video-controls">
        <button class="btn btn-light btn-sm me-2" id="muteBtn" onclick="toggleMute()">
            <i class="fas fa-microphone"></i>
        </button>
        <button class="btn btn-light btn-sm me-2" id="cameraBtn" onclick="toggleCamera()">
            <i class="fas fa-video"></i>
        </button>
        <button class="btn btn-danger btn-sm" onclick="endVideoCall()">
            <i class="fas fa-phone-slash"></i>
        </button>
    </div>
</div>

<!-- Audio element cho voice call (ẩn) -->
<audio id="remoteAudio" autoplay playsinline style="display: none;" volume="1.0"></audio>

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
    align-items: center;
}
.chat-input-group button {
    width: 50px;
    height: 50px;
    min-width: 50px;
    min-height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}
.chat-input-group #sendButton {
    width: 50px;
    height: 50px;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    font-size: 1.2rem;
}
.chat-input-group #voiceCallButton {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    box-shadow: 0 4px 20px rgba(23, 162, 184, 0.3);
    font-size: 1.1rem;
}
.chat-input-group #videoCallButton {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    box-shadow: 0 4px 20px rgba(220, 53, 69, 0.3);
    font-size: 1.1rem;
}
.chat-input-group #attachButton {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
    box-shadow: 0 4px 20px rgba(108, 117, 125, 0.3);
    font-size: 1.1rem;
}
.chat-input-group button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
}
.chat-input-group button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
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

/* Override cho tất cả các nút trong chat-input */
.chat-input button {
    background: linear-gradient(45deg, #667eea, #764ba2);
    border: none;    
    height: 50px;
    width: 50px;
    color: white;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.chat-input button:hover:not(:disabled) {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.chat-input button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.chat-quick-replies {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e9ecef;
}

/* CSS riêng cho các nút chat nhanh - chỉ icon, không có chữ */
.quick-reply {
    font-size: 1rem;
    padding: 0;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.quick-reply::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.quick-reply:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 50%, #e083eb 100%);
    color: white;
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.quick-reply:hover::before {
    left: 100%;
}

.quick-reply:active {
    transform: translateY(-1px) scale(1.02);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.5);
}

.quick-reply.active {
    background: linear-gradient(135deg, #4a5bc4 0%, #5a2f7a 50%, #d073db 100%);
    transform: scale(0.98);
    opacity: 0.9;
}

.quick-reply:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.quick-reply i {
    font-size: 1.2rem;
    margin: 0;
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

/* Video Call Container Styles */
.video-call-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #000;
    z-index: 10000;
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

.call-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Upload Progress Styles */
.upload-progress {
    padding: 1rem;
    margin: 1rem 0;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #dee2e6;
    text-align: center;
}

.upload-progress i {
    font-size: 1.5rem;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.upload-progress div {
    margin: 0.5rem 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.upload-progress .progress-bar {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 0.5rem;
}

.upload-progress .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.3s ease;
    width: 0%;
}

/* Media Message Styles */
.media-message {
    margin: 0.5rem 0;
    max-width: 100%;
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

.file-info {
    padding: 0.5rem 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    max-width: 100%;
}

.file-info i {
    font-size: 1rem;
    color: #667eea;
}

.file-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 0;
    font-size: 0.9rem;
}

.file-size {
    font-size: 0.75rem;
    color: #6c757d;
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

.media-message .file-info i {
    color: #667eea;
    font-size: 1rem;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Stringee SDK - Load từ LOCAL trước, sau đó fallback về CDN -->
<script>
(function() {
    // ✅ Đường dẫn local SDK (ưu tiên cao nhất)
    // Từ admin/chat.php, file SDK nằm ở ../assets/Stringee/... (relative path từ admin/)
    // Hoặc dùng absolute path từ root
    const localSDKPath = '<?php 
        // Lấy path từ root của project
        $scriptPath = $_SERVER["SCRIPT_NAME"] ?? "";
        $pathParts = explode("/", trim($scriptPath, "/"));
        
        // Tìm vị trí của "admin" trong path
        $adminIndex = array_search("admin", $pathParts);
        
        if ($adminIndex !== false) {
            // Nếu có "admin" trong path, dùng relative path
            echo "../assets/Stringee/StringeeWebSDK_2.9.0/latest.sdk.bundle.min.js";
        } else {
            // Nếu không có "admin", dùng BASE_PATH
            $basePath = defined("BASE_PATH") ? BASE_PATH : "";
            $basePath = rtrim($basePath, "/");
            echo ($basePath ? $basePath . "/" : "") . "assets/Stringee/StringeeWebSDK_2.9.0/latest.sdk.bundle.min.js";
        }
    ?>';
    
    // ✅ Danh sách URL để thử (theo thứ tự ưu tiên: Local → CDN)
    const stringeeUrls = [
        localSDKPath,                                                      // ✅ LOCAL SDK (ưu tiên nhất)
        'https://cdn.stringee.com/sdk/web/latest/stringee-web-sdk.min.js', // CDN URL mới
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
<script src="<?php 
    // Lấy path từ root của project
    $scriptPath = $_SERVER["SCRIPT_NAME"] ?? "";
    $pathParts = explode("/", trim($scriptPath, "/"));
    
    // Tìm vị trí của "admin" trong path
    $adminIndex = array_search("admin", $pathParts);
    
    if ($adminIndex !== false) {
        // Nếu có "admin" trong path, dùng relative path
        echo "../assets/js/stringee-helper.js";
    } else {
        // Nếu không có "admin", dùng BASE_PATH
        $basePath = defined("BASE_PATH") ? BASE_PATH : "";
        $basePath = rtrim($basePath, "/");
        echo ($basePath ? $basePath . "/" : "") . "assets/js/stringee-helper.js";
    }
?>"></script>
<!-- Socket.IO - Dùng CDN cho production, local server cho development -->
<script>
    // Tải Socket.IO client
    (function() {
        const hostname = window.location.hostname;
        const isProduction = hostname.includes('sukien.info.vn') || hostname.includes('sukien');
        
        // Production: Dùng CDN trực tiếp (ổn định hơn trên cPanel)
        // Localhost: Thử local server trước, sau đó fallback CDN
        let socketScript = document.createElement('script');
        
        if (isProduction) {
            // Production: Dùng CDN trực tiếp
            socketScript.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
            socketScript.onload = function() {
                console.log('Socket.IO loaded from CDN (production)');
            };
            socketScript.onerror = function() {
                console.error('Failed to load Socket.IO from CDN');
            };
        } else {
            // Development: Thử local server trước
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
let chatSocket;
let currentConversationId = null;
let conversations = [];
let isConnected = false;
let currentUserId = <?php echo $currentUserId; ?>;
let currentUserName = '<?php echo htmlspecialchars($currentUserName); ?>';

// Biến cho Media và Call (Stringee)
let currentCall = null;
let isMuted = false;
let isCameraOff = false;

// ID của các interval cho polling/auto-refresh (để tránh nhiều interval)
let pollingInterval1 = null;
let pollingInterval2 = null;
let autoRefreshInterval = null;
let activityInterval = null;

// Khởi tạo chat
$(document).ready(function() {
    initializeSocket();
    setUserOnline(); // Đặt admin online
    loadConversations();
    loadOnlineUsers();
    setupEventHandlers();
    startAutoRefresh();
    
    // Đặt user offline khi đóng trang
    $(window).on('beforeunload', function() {
        setUserOffline();
    });
});

// Đặt user online
function setUserOnline() {
    $.ajax({
        url: '../src/controllers/chat-controller.php?action=set_user_online',
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

// Đặt user offline
function setUserOffline() {
    $.ajax({
        url: '../src/controllers/chat-controller.php?action=set_user_offline',
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

// Cập nhật hoạt động của user
function updateUserActivity() {
    $.ajax({
        url: '../src/controllers/chat-controller.php?action=update_activity',
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

// Removed showAdminInfo - không cần thiết

// Khởi tạo kết nối Socket.IO với fallback tốt hơn
function initializeSocket() {
    console.log('Initializing Socket.IO...');
    
    // Kiểm tra Socket.IO có sẵn không
    if (typeof io === 'undefined') {
        console.warn('Socket.IO not loaded, using AJAX fallback');
        isConnected = false;
        updateConnectionStatus('disconnected', 'Chế độ offline - Sử dụng AJAX');
        startPollingMode();
        return;
    }
    
    console.log('Socket.IO available, creating connection...');
    
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
        updateConnectionStatus('disconnected', 'Socket.IO library chưa được tải');
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
        
        chatSocket = io(socketServerURL, {
            path: socketPath,
            transports: ['polling', 'websocket'], // Thử polling trước, sau đó websocket
            timeout: 20000,
            reconnection: true,
            reconnectionAttempts: Infinity, // Tiếp tục thử kết nối lại
            reconnectionDelay: 1000,
            reconnectionDelayMax: 10000,
            forceNew: false,
            autoConnect: true,
            // Thêm query parameters để debug
            query: {
                clientType: 'admin',
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
        updateConnectionStatus('disconnected', 'Lỗi tạo kết nối: ' + (error.message || 'Unknown error'));
        return;
    }
    
    chatSocket.on('connect', function() {
        console.log('✅ Admin chat connected successfully');
        isConnected = true;
        updateConnectionStatus('connected', 'Đã kết nối');
        
        // Dừng chế độ polling khi đã kết nối
        stopPollingMode();
        
        // Tham gia admin room
        chatSocket.emit('authenticate', {
            userId: currentUserId,
            userRole: <?php echo $userRole; ?>,
            userName: currentUserName
        });
        
        // Đảm bảo user ở trong room riêng để nhận cuộc gọi
        chatSocket.emit('join_user_room', { userId: currentUserId });
        console.log('Admin joined user room:', currentUserId);
        
        // Tham gia lại conversation hiện tại nếu có
        if (currentConversationId) {
            chatSocket.emit('join_conversation', { conversation_id: currentConversationId });
        }
        
        // Tải danh sách user online khi đã kết nối
        loadOnlineUsers();
    });
    
    console.log('Socket.IO event handlers set up successfully');
    
    chatSocket.on('connect_error', function(error) {
        console.error('❌ Admin chat connection error:', error);
        console.error('Error type:', error.type);
        console.error('Error message:', error.message);
        console.error('Error description:', error.description);
        console.error('Connection URL:', socketServerURL);
        console.error('Connection Path:', socketPath);
        console.error('Full connection URL:', socketServerURL + socketPath);
        
        // Kiểm tra server có thể truy cập không
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
                console.log('💡 Possible causes: CORS issue, path mismatch, or Socket.IO not properly configured');
                console.log('💡 Server path:', data.path || 'unknown');
            })
            .catch(err => {
                console.error('❌ Cannot reach server:', err);
                console.log('💡 Server may not be running or URL is incorrect');
                console.log('💡 Expected server at:', socketServerURL);
            });
        
        isConnected = false;
        updateConnectionStatus('disconnected', 'Lỗi kết nối: ' + (error.message || error.description || 'Unknown error'));
        // Bắt đầu chế độ polling làm fallback
        if (!isConnected) {
            startPollingMode();
        }
    });
    
    chatSocket.on('disconnect', function(reason) {
        console.warn('⚠️ Admin chat disconnected:', reason);
        isConnected = false;
        updateConnectionStatus('disconnected', 'Mất kết nối');
    });
    
    chatSocket.on('reconnect', function(attemptNumber) {
        console.log('🔄 Admin chat reconnected after', attemptNumber, 'attempts');
        isConnected = true;
        updateConnectionStatus('connected', 'Đã kết nối lại');
        
        // Xác thực lại
        chatSocket.emit('authenticate', {
            userId: currentUserId,
            userRole: <?php echo $userRole; ?>,
            userName: currentUserName
        });
        
        // Đảm bảo user ở trong room riêng để nhận cuộc gọi
        chatSocket.emit('join_user_room', { userId: currentUserId });
        console.log('Admin reconnected, joined user room:', currentUserId);
        
        // Tham gia lại conversation hiện tại nếu có
        if (currentConversationId) {
            chatSocket.emit('join_conversation', { conversation_id: currentConversationId });
        }
    });
    
    chatSocket.on('reconnect_attempt', function() {
        console.log('🔄 Attempting to reconnect...');
    });
    
    chatSocket.on('reconnect_failed', function() {
        console.error('❌ Admin chat reconnection failed');
        isConnected = false;
        updateConnectionStatus('disconnected', 'Không thể kết nối lại');
        startPollingMode();
    });
    
    chatSocket.on('new_message', function(data) {
        console.log('Admin received new message:', data);
        if (data.conversation_id === currentConversationId) {
            addMessageToChat(data, false);
            // Cuộn xuống dưới ngay lập tức
            setTimeout(scrollToBottom, 100);
        }
        updateConversationPreview(data.conversation_id, data.message);
        
        // Cập nhật danh sách conversation để đồng bộ real-time
        loadConversations();
        
        // Cập nhật số lượng online khi nhận tin nhắn mới
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
            // Làm mới danh sách conversation
            loadConversations();
        }
    });
    
    // Xử lý tin nhắn broadcast
    chatSocket.on('broadcast_message', function(data) {
        console.log('Admin received broadcast message:', data);
        if (data.conversation_id === currentConversationId && data.userId !== currentUserId) {
            addMessageToChat(data.message, false);
            scrollToBottom();
        }
        updateConversationPreview(data.conversation_id, data.message.message || data.message.text);
    });
    
    // Xử lý cập nhật trạng thái online của user
    chatSocket.on('user_online', function(data) {
        console.log('User came online:', data);
        loadOnlineUsers();
    });
    
    chatSocket.on('user_offline', function(data) {
        console.log('User went offline:', data);
        loadOnlineUsers();
    });
    
    // Xử lý cập nhật số lượng user online
    chatSocket.on('online_count_update', function(data) {
        console.log('Online count updated:', data);
        $('#onlineCount').text(data.count);
        
        // Cập nhật màu badge dựa trên số lượng
        const badge = $('#onlineCount');
        if (data.count > 0) {
            badge.removeClass('bg-secondary').addClass('bg-success');
        } else {
            badge.removeClass('bg-success').addClass('bg-secondary');
        }
    });
    
    // Thiết lập các event socket cho cuộc gọi
    chatSocket.on('call_initiated', function(data) {
        console.log('📞 Received call_initiated event:', data);
        console.log('📞 Checking receiver_id:', data.receiver_id, 'vs currentUserId:', currentUserId);
        console.log('📞 Type comparison:', typeof data.receiver_id, typeof currentUserId);
        console.log('📞 Conversation ID:', data.conversation_id);
        
        // Dùng == thay vì === để xử lý string/number mismatch
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
            
            const conversation = conversations.find(c => c.id == data.conversation_id);
            const callerName = conversation ? conversation.other_user_name : 'Người gọi';
            
            console.log('📞 Showing call modal for:', callerName);
            console.log('📞 Call type:', data.call_type);
            
            // Hiển thị modal với nút chấp nhận/từ chối
            showCallModal('incoming', callerName, data.call_type);
            
            // Ép hiển thị modal nếu Bootstrap modal không hiển thị
            setTimeout(() => {
                const modalElement = document.getElementById('callModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (!modal || !modal._isShown) {
                        console.warn('⚠️ Modal not shown, forcing show');
                        const newModal = new bootstrap.Modal(modalElement);
                        newModal.show();
                    }
                }
            }, 100);
        } else {
            console.log('❌ Call is not for this user, ignoring');
            console.log('❌ Receiver ID:', data.receiver_id, 'Current User ID:', currentUserId);
        }
    });
    
    chatSocket.on('call_accepted', function(data) {
        console.log('Received call_accepted event:', data);
        // ✅ Stringee: Logic đã được xử lý trong acceptCallInternal, không cần làm gì thêm
        if (data.caller_id === currentUserId && currentCall) {
            // Stringee call đã được join trong acceptCallInternal
            console.log('✅ Call accepted, Stringee call should be connected');
        }
    });
    
    chatSocket.on('call_rejected', function(data) {
        console.log('Received call_rejected event:', data);
        if (data.caller_id === currentUserId) {
            $('#callModal').modal('hide');
            currentCall = null;
            showNotification(data.message || 'Cuộc gọi bị từ chối', 'warning', 'fa-times-circle');
        }
    });
    
    chatSocket.on('call_ended', function(data) {
        console.log('📞 Received call_ended event:', data);
        
        // QUAN TRỌNG: Cleanup đầy đủ khi bên kia tắt cuộc gọi
        // Ẩn modal và video container
        $('#callModal').modal('hide');
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
        
        // Dừng remote video nếu đang phát
        const remoteVideo = document.getElementById('remoteVideo');
        if (remoteVideo) {
            remoteVideo.pause();
            remoteVideo.srcObject = null;
            console.log('✅ Remote video stopped');
        }
        
        // ✅ Cleanup Stringee call
        if (window.StringeeHelper) {
            window.StringeeHelper.cleanup();
        }
        
        // Hiển thị thông báo
        if (data.message) {
            showNotification(data.message, 'info');
        } else {
            showNotification('Cuộc gọi đã kết thúc', 'info');
        }
        
        currentCall = null;
        console.log('✅ Call cleanup completed');
    });
    
    // Call busy - Người nhận đang trong cuộc gọi khác
    chatSocket.on('call_busy', function(data) {
        console.log('Received call_busy event:', data);
        $('#callModal').modal('hide');
        currentCall = null;
        
        showNotification(data.message || `${data.receiver_name} đang bận trong cuộc gọi khác`, 'warning');
    });
    
    // Call timeout - Cuộc gọi không được trả lời
    chatSocket.on('call_timeout', function(data) {
        console.log('Received call_timeout event:', data);
        $('#callModal').modal('hide');
        currentCall = null;
        
        showNotification(data.message || 'Cuộc gọi không được trả lời sau 30 giây', 'warning');
    });
    
    // Call notification - Các thông báo khác về cuộc gọi
    chatSocket.on('call_notification', function(data) {
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
    
    // ==================== Stringee Call Events ====================
    // ✅ Lưu ý: WebRTC signaling events (webrtc_offer, webrtc_answer, ice_candidate) 
    // đã được loại bỏ vì chúng ta đang sử dụng Stringee thay vì WebRTC trực tiếp.
    // Stringee xử lý tất cả signaling và media routing tự động.
}

// Tải danh sách cuộc trò chuyện
function loadConversations() {
    $.ajax({
        url: '../src/controllers/chat-controller.php?action=get_conversations',
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

// Tải số lượng user online
function loadOnlineUsers() {
    console.log('Loading online users...');
    $.ajax({
        url: '../src/controllers/chat-controller.php?action=get_online_count',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Online count response:', data);
            if (data.success) {
                const count = data.count || 0;
                $('#onlineCount').text(count);
                
                // Cập nhật màu badge dựa trên số lượng
                const badge = $('#onlineCount');
                if (count > 0) {
                    badge.removeClass('bg-secondary').addClass('bg-success');
                } else {
                    badge.removeClass('bg-success').addClass('bg-secondary');
                }
                
                console.log('Online count updated:', count);
                
                // Thông tin debug
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

// Hiển thị danh sách cuộc trò chuyện
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
        
        // Debug: Ghi log dữ liệu conversation
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

// Chọn cuộc trò chuyện
function selectConversation(conversationId) {
    console.log('Admin selecting conversation:', conversationId);
    currentConversationId = conversationId;
    
    // Tìm conversation để lấy thông tin người dùng
    const conversation = conversations.find(c => c.id == conversationId);
    if (conversation) {
        // Cập nhật chat header với tên người dùng
        $('#chatUserName').text(conversation.other_user_name || 'Người dùng');
        
        // Cập nhật trạng thái online/offline
        const isOnline = conversation.is_online === true || conversation.is_online === 1 || conversation.is_online === '1';
        const statusText = isOnline ? 'Đang online' : 'Đang offline';
        $('#chatUserStatus').text(statusText);
        $('#chatUserStatus').removeClass('text-muted text-success text-danger');
        if (isOnline) {
            $('#chatUserStatus').addClass('text-success').show();
        } else {
            $('#chatUserStatus').addClass('text-danger').show();
        }
    } else {
        // Nếu không tìm thấy conversation, giữ nguyên text mặc định
        $('#chatUserName').text('Chọn cuộc trò chuyện');
        $('#chatUserStatus').hide();
    }
    
    // Cập nhật hoạt động của user
    updateUserActivity();
    
    // Cập nhật UI
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-conversation-id="${conversationId}"]`).addClass('active');
    
    // Hiển thị header và input chat
    $('#chatHeader').show();
    $('#chatInput').show();
    $('.chat-input').show();
    
    // Bật input và đảm bảo các nút hiển thị
    $('#messageInput').prop('disabled', false);
    $('#sendButton').prop('disabled', false).css('display', 'flex');
    $('#voiceCallButton').prop('disabled', false).css('display', 'flex');
    $('#videoCallButton').prop('disabled', false).css('display', 'flex');
    $('#attachButton').prop('disabled', false).css('display', 'flex');
    
    // Debug: Ghi log để đảm bảo các nút tồn tại
    console.log('Buttons check:', {
        attachButton: $('#attachButton').length,
        voiceCallButton: $('#voiceCallButton').length,
        videoCallButton: $('#videoCallButton').length,
        sendButton: $('#sendButton').length
    });
    
    // Tham gia conversation room để cập nhật real-time
    if (isConnected && chatSocket) {
        chatSocket.emit('join_conversation', { conversation_id: conversationId });
        // Đảm bảo user ở trong room riêng để nhận cuộc gọi
        chatSocket.emit('join_user_room', { userId: currentUserId });
        console.log('Admin joined conversation room:', conversationId, 'and user room:', currentUserId);
    }
    
    // Tải tin nhắn với cập nhật real-time
    loadMessagesWithRealTime(conversationId);
}

// Tải tin nhắn cho conversation
function loadMessages(conversationId) {
    console.log('loadMessages called with conversationId:', conversationId);
    
    // Chỉ hiển thị trạng thái loading nếu chưa có tin nhắn nào được hiển thị
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
        url: `../src/controllers/chat-controller.php?action=get_messages&conversation_id=${conversationId}`,
        type: 'GET',
        dataType: 'json',
        timeout: 10000,
        success: function(data) {
            console.log('Messages loaded:', data);
            if (data.success) {
                displayMessages(data.messages);
                
                // Emit event message read để cập nhật real-time
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

// Hiển thị tin nhắn
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
    
    // Thêm animation cho tin nhắn mới
    $('#chatMessages').html(html);
    
    // Tạo animation cho tin nhắn mới
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

// Tạo HTML cho tin nhắn
function createMessageHTML(message) {
    // Kiểm tra dữ liệu message hợp lệ
    if (!message || typeof message !== 'object') {
        console.warn('Invalid message data:', message);
        return '';
    }
    
    // Ghi log debug
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
                // Fallback về thời gian hiện tại nếu date không hợp lệ
                time = new Date().toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        } else {
            // Dùng thời gian hiện tại nếu không có created_at
            time = new Date().toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    } catch (e) {
        console.warn('Date parsing error:', e, 'for date:', message.created_at);
        // Fallback về thời gian hiện tại
        time = new Date().toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Trong admin chat, tin nhắn từ admin (currentUserId) ở bên phải
    // Tin nhắn từ khách hàng (user khác) ở bên trái
    const isSent = message.sender_id == currentUserId;
    const messageText = message.message || message.text || 'Tin nhắn trống';
    const messageId = message.id || message.message_id || `temp-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    const isRead = message.IsRead == 1;
    const messageType = message.message_type || 'text';
    
    // Ghi log debug
    console.log('Message details:', {
        messageId: messageId,
        time: time,
        isSent: isSent,
        messageText: messageText,
        isRead: isRead,
        messageType: messageType,
        sender_id: message.sender_id,
        currentUserId: currentUserId
    });
    
    // Xử lý tin nhắn đặc biệt (hình ảnh, file, etc.)
    let messageContent = '';
    
    // Lấy base path từ vị trí hiện tại - Tự động phát hiện cho cả localhost và production
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
        const pathParts = path.split('/').filter(p => p && p !== 'chat.php' && p !== 'admin');
        if (pathParts.length > 0) {
            // There's a subdirectory
            return '/' + pathParts[0] + '/';
        }
        
        // Root
        return '';
    };
    const basePath = getBasePath();
    
    if (messageType === 'image') {
        // Fix file path - ensure correct path format
        let imagePath = message.file_path || '';
        
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
        if (message.thumbnail_path && !imagePath.startsWith('http')) {
            let thumbPath = message.thumbnail_path;
            
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
                <div class="message-time">${time}${isSent ? (isRead ? ' <i class="fas fa-check-double text-primary"></i>' : ' <i class="fas fa-check text-muted"></i>') : ''}</div>
            </div>
        `;
    } else if (messageType === 'file') {
        messageContent = `
            <div class="media-message">
                <div class="file-info">
                    <div class="file-name">${message.file_name || 'File'}</div>
                    <div class="file-size">${formatFileSize(message.file_size || 0)}</div>
                </div>
                <div class="message-time">${time}${isSent ? (isRead ? ' <i class="fas fa-check-double text-primary"></i>' : ' <i class="fas fa-check text-muted"></i>') : ''}</div>
            </div>
        `;
    } else if (messageType === 'voice_call' || messageType === 'video_call') {
        const callType = messageType === 'video_call' ? 'Video Call' : 'Voice Call';
        const callIcon = messageType === 'video_call' ? 'fa-video' : 'fa-phone';
        messageContent = `
            <div class="media-message">
                <div class="file-info" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 8px; font-size: 0.9rem;">
                    <i class="fas ${callIcon}" style="color: #667eea; font-size: 1rem;"></i>
                    <span style="color: #333; font-weight: 500;">${callType}</span>
                </div>
                <div class="message-time" style="margin-top: 0.25rem;">${time}${isSent ? (isRead ? ' <i class="fas fa-check-double text-primary"></i>' : ' <i class="fas fa-check text-muted"></i>') : ''}</div>
            </div>
        `;
    } else {
        messageContent = `
            <div>${escapeHtml(messageText)}</div>
            <div class="message-time">
                ${time}
                ${isSent ? (isRead ? ' <i class="fas fa-check-double text-primary"></i>' : ' <i class="fas fa-check text-muted"></i>') : ''}
            </div>
        `;
    }
    
    return `
        <div class="message ${isSent ? 'sent' : 'received'}" data-message-id="${messageId}">
            <div class="message-content">
                ${messageContent}
            </div>
        </div>
    `;
}

// Hàm xem trước hình ảnh
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
    
    // Create modal for image preview
    const modalHtml = `
        <div class="modal fade" id="imagePreviewModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Xem hình ảnh</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="${fixedPath}" alt="Preview" style="max-width: 100%; height: auto; border-radius: 10px; display: block; margin: 0 auto;" 
                             onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y1ZjVmNSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Lb0BuZyB0aGkgdGkgxrDhu6NhbmggaGluaDwvdGV4dD48L3N2Zz4='; this.after('<div class=\\'text-danger mt-2\\'>Không thể tải hình ảnh</div>');">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#imagePreviewModal').remove();
    
    // Append and show modal
    $('body').append(modalHtml);
    
    // Wait a bit for DOM to update
    setTimeout(() => {
        const modalElement = document.getElementById('imagePreviewModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            // Remove modal from DOM when hidden
            $(modalElement).on('hidden.bs.modal', function() {
                $(this).remove();
            });
            
            console.log('Modal shown with image path:', fixedPath);
        } else {
            console.error('Modal element not found after append!');
        }
    }, 100);
}

// Định dạng kích thước file
function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Thêm tin nhắn vào chat
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

// Thiết lập các event handlers
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
    
    // Xóa event listeners cũ trước khi attach mới (tránh duplicate)
    $(document).off('click', '#attachButton');
    $(document).off('click', '#voiceCallButton');
    $(document).off('click', '#videoCallButton');
    $('#fileInput').off('change');
    
    // Attach button
    $(document).on('click', '#attachButton', function() {
        if ($(this).prop('disabled')) return;
        $('#fileInput').click();
    });
    
    // Voice call button
    $(document).on('click', '#voiceCallButton', function() {
        if ($(this).prop('disabled')) return;
        if (!currentConversationId) {
            alert('Vui lòng chọn cuộc trò chuyện trước');
            return;
        }
        initiateCall('voice');
    });
    
    // Video call button
    $(document).on('click', '#videoCallButton', function() {
        if ($(this).prop('disabled')) return;
        if (!currentConversationId) {
            alert('Vui lòng chọn cuộc trò chuyện trước');
            return;
        }
        initiateCall('video');
    });
    
    // File input change
    $('#fileInput').on('change', function(e) {
        const file = e.target.files[0];
        if (file && currentConversationId) {
            uploadFile(file);
            // Reset file input sau khi upload để có thể chọn lại cùng file
            $(this).val('');
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
    
    // Customer search - Tìm kiếm cuộc trò chuyện
    $('#customerSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        if (searchTerm === '') {
            // Hiển thị lại tất cả conversations
            displayConversations();
            return;
        }
        
        // Lọc conversations theo tên khách hàng hoặc preview message
        const filtered = conversations.filter(conv => {
            const name = (conv.other_user_name || '').toLowerCase();
            const preview = (conv.last_message || '').toLowerCase();
            return name.includes(searchTerm) || preview.includes(searchTerm);
        });
        
        // Hiển thị kết quả đã lọc
        if (filtered.length === 0) {
            $('#conversationsList').html('<p class="text-center text-muted mt-3">Không tìm thấy cuộc trò chuyện nào</p>');
        } else {
            let html = '';
            filtered.forEach(conv => {
                const time = new Date(conv.updated_at).toLocaleTimeString('vi-VN', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                const unreadCount = conv.unread_count || 0;
                const isOnline = conv.is_online === true || conv.is_online === 1 || conv.is_online === '1';
                html += `
                <div class="conversation-item" onclick="selectConversation(${conv.id})" data-conversation-id="${conv.id}">
                    <div class="conversation-user">
                        <span>
                            <span class="status-indicator ${isOnline ? 'status-online' : 'status-offline'}" 
                                  title="${isOnline ? 'Đang online' : 'Đang offline'}"></span>
                            ${conv.other_user_name}
                        </span>
                        ${unreadCount > 0 ? `<span class="conversation-badge">${unreadCount}</span>` : ''}
                    </div>
                    <div class="conversation-preview">${conv.last_message || 'Chưa có tin nhắn'}</div>
                    <div class="conversation-time">${time}</div>
                </div>`;
            });
            $('#conversationsList').html(html);
        }
    });
}

// Gửi tin nhắn
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
        url: '../src/controllers/chat-controller.php?action=send_message',
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

// Cập nhật trạng thái kết nối
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

// Hiển thị chỉ báo đang gõ
function showTypingIndicator(userName) {
    $('#typingIndicator').html(`
        <i class="fas fa-circle fa-xs"></i>
        <i class="fas fa-circle fa-xs"></i>
        <i class="fas fa-circle fa-xs"></i>
        <span class="ms-2">${userName} đang nhập...</span>
    `).show();
}

// Ẩn chỉ báo đang gõ
function hideTypingIndicator() {
    $('#typingIndicator').hide();
}

// Cập nhật trạng thái đã đọc tin nhắn
function updateMessageReadStatus(messageId) {
    $(`.message[data-message-id="${messageId}"] .message-time`).html(function() {
        return $(this).html().replace('<i class="fas fa-check text-muted"></i>', '<i class="fas fa-check-double text-primary"></i>');
    });
}

// Tự động làm mới conversations mỗi 30 giây nếu chưa kết nối
function startAutoRefresh() {
    // Clear existing intervals first to prevent duplicates
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
    if (activityInterval) {
        clearInterval(activityInterval);
        activityInterval = null;
    }
    
    // Only start if not connected
    if (!isConnected) {
        autoRefreshInterval = setInterval(function() {
            if (!isConnected) {
                loadConversations();
                loadOnlineUsers();
            }
        }, 30000);
    }
    
    // Update user activity every 2 minutes to maintain online status
    activityInterval = setInterval(function() {
        updateUserActivity();
    }, 120000); // 2 minutes
}

// Start polling mode for real-time messaging
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
            loadOnlineUsers();
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
    
    $.getJSON('../src/controllers/chat-controller.php?action=get_messages&conversation_id=' + currentConversationId, function(res) {
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

// Show notification function (for call notifications and other alerts)
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
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" role="alert" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
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
        url: '../src/controllers/chat-controller.php?action=search_conversations',
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

// Quick reply template selection
$(document).on('click', '.template-item', function() {
    const templateText = $(this).find('p').text();
    $('#messageInput').val(templateText);
    bootstrap.Modal.getInstance(document.getElementById('quickReplyModal')).hide();
});

// Auto refresh conversations every 30 seconds (only when connected)
// Note: This is handled by startAutoRefresh() when disconnected
// and by Socket.IO events when connected, so this global interval is not needed
// Removed to prevent duplicate intervals

// ==================== CALL FUNCTIONS ====================

// ==================== CÁC HÀM CALL (Stringee SDK) ====================

/**
 * Khởi tạo cuộc gọi (Voice hoặc Video) sử dụng Stringee SDK
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
        const response = await $.post('../src/controllers/call-controller.php?action=initiate_call', {
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
            if (isConnected && chatSocket && typeof chatSocket.emit === 'function') {
            chatSocket.emit('call_initiated', {
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
        $('#callModal').modal('hide');
        currentCall = null;
    }
}

// Setup Stringee event handlers
function setupStringeeEventHandlers() {
    if (!window.StringeeHelper) {
        console.warn('⚠️ StringeeHelper chưa được load');
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
            localVideo.play().catch(err => console.error('Error playing local video:', err));
        }
    };
    
    // Setup remote stream handler
    window.onStringeeRemoteStreamAdded = function(stream) {
        console.log('✅ Remote stream added:', stream);
        
        const remoteVideo = document.getElementById('remoteVideo');
        if (remoteVideo && stream.getVideoTracks().length > 0) {
            remoteVideo.srcObject = stream;
            remoteVideo.play().catch(err => console.error('Error playing remote video:', err));
            
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
            remoteAudio.play().catch(err => console.error('Error playing remote audio:', err));
        }
    };
    
    // Setup call answered handler
    window.onCallAnswered = function() {
        console.log('✅ Call answered');
        if (currentCall && currentCall.type === 'video') {
            $('#callModal').modal('hide');
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
    $('#callModal').modal('hide');
    $('#videoCallContainer').hide();
    currentCall = null;
    
    if (window.StringeeHelper) {
        window.StringeeHelper.cleanup();
    }
}



// Show call modal
function showCallModal(type, name, callType) {
    console.log('📞 Admin showCallModal called:', { type, name, callType });
    
    $('#callerName').text(name);
    $('#callType').text(callType === 'video' ? 'Cuộc gọi video' : 'Cuộc gọi thoại');
    
    if (type === 'incoming') {
        $('#callStatus').text('Cuộc gọi đến...');
        // Clear existing buttons first
        $('#callControls').empty();
        // Add both accept and reject buttons with inline styles to ensure visibility
        $('#callControls').html(`
            <button class="btn btn-success btn-lg me-2" onclick="acceptCall()" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
                <i class="fas fa-phone"></i>
            </button>
            <button class="btn btn-danger btn-lg" onclick="rejectCall()" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
                <i class="fas fa-phone-slash"></i>
            </button>
        `);
        console.log('✅ Admin incoming call - Added accept and reject buttons');
    } else {
        $('#callStatus').text('Đang gọi...');
        // Clear existing buttons first
        $('#callControls').empty();
        // Add end call button with direct onclick to ensure it works
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
        
        console.log('📤 Admin outgoing call - Added end button only');
    }
    
    // Show modal using Bootstrap
    const modalElement = document.getElementById('callModal');
    if (modalElement) {
        // ✅ FIX: Đảm bảo modal có z-index cao và hiển thị đúng
        $(modalElement).css({
            'z-index': '10050',
            'display': 'block'
        });
        
        // ✅ FIX: Đảm bảo backdrop có z-index phù hợp
        if ($('.modal-backdrop').length === 0) {
            $('body').append('<div class="modal-backdrop fade show" style="z-index: 10040;"></div>');
        } else {
            $('.modal-backdrop').css('z-index', '10040').addClass('show');
        }
        
        // Try to get existing modal instance
        let modal = bootstrap.Modal.getInstance(modalElement);
        
        // If no instance exists, create new one
        if (!modal) {
            modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: false,
                focus: true
            });
        }
        
        // Show modal
        modal.show();
        console.log('✅ Admin call modal shown with type:', type);
        
        // ✅ FIX: Force show modal với nhiều cách để đảm bảo hiển thị
        setTimeout(() => {
            $(modalElement).addClass('show').css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1',
                'z-index': '10050'
            });
            
            // Đảm bảo backdrop hiển thị
            $('.modal-backdrop').addClass('show').css({
                'z-index': '10040',
                'display': 'block'
            });
            
            // Scroll to top để đảm bảo modal trong viewport
            $('html, body').animate({ scrollTop: 0 }, 0);
        }, 100);
        
        // ✅ FIX: Double check sau 300ms
        setTimeout(() => {
            if (!$(modalElement).hasClass('show') || $(modalElement).css('display') === 'none') {
                console.warn('⚠️ Modal still not visible, forcing show again');
                $(modalElement).removeClass('fade').addClass('show').css({
                    'display': 'block !important',
                    'visibility': 'visible !important',
                    'opacity': '1 !important',
                    'z-index': '10050 !important'
                });
            }
        }, 300);
    } else {
        console.error('❌ Call modal element not found!');
    }
    
    // Debug: Check if buttons are in DOM
    setTimeout(() => {
        const acceptBtn = $('#callControls .btn-success');
        const rejectBtn = $('#callControls .btn-danger');
        const endBtn = $('#callControls .btn-danger');
        console.log('🔍 Admin button check:', {
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

/**
 * ✅ Chấp nhận cuộc gọi với Stringee
 * Viết lại theo stringee-helper.js
 */
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
        const response = await $.post('../src/controllers/call-controller.php?action=accept_call', {
        call_id: currentCall.id
        });
        
        if (!response.success) {
            alert('Lỗi chấp nhận cuộc gọi: ' + (response.error || 'Unknown error'));
            return;
        }
        
        // Lấy token và join call với Stringee
        await window.StringeeHelper.getTokenAndJoin(currentCall.id, currentCall.type, false);
            
            // Emit accept event
            if (isConnected && chatSocket && typeof chatSocket.emit === 'function') {
                chatSocket.emit('call_accepted', {
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
        $('#callModal').modal('hide');
        return;
    }
    
    const callId = currentCall.id;
    const callerId = currentCall.caller_id || currentCall.receiver_id;
    
    // Cleanup Stringee call
    if (window.StringeeHelper) {
        window.StringeeHelper.cleanup();
    }
    
    // Gọi backend để reject
    $.post('../src/controllers/call-controller.php?action=reject_call', {
        call_id: callId
    }, function(response) {
        cleanupCall();
        
        // Emit reject event
        if (isConnected && chatSocket && typeof chatSocket.emit === 'function') {
            chatSocket.emit('call_rejected', {
                call_id: callId,
                caller_id: callerId,
                receiver_id: currentUserId
            });
        }
    }, 'json').fail(function() {
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
    $.post('../src/controllers/call-controller.php?action=end_call', {
        call_id: callId
    }, function(response) {
        // Emit end event via socket
        if (isConnected && chatSocket && typeof chatSocket.emit === 'function') {
            chatSocket.emit('call_ended', {
                call_id: callId,
                caller_id: currentUserId
            });
            }
        }, 'json').fail(function() {
        // Vẫn emit end event ngay cả khi backend fail
        if (isConnected && chatSocket && typeof chatSocket.emit === 'function') {
            chatSocket.emit('call_ended', {
                call_id: callId,
                caller_id: currentUserId
            });
        }
    });
    }
}

// Make endCall globally accessible
window.endCall = endCall;

// Show voice call UI
function showVoiceCallUI() {
    // Get caller/receiver name
    const conversation = conversations.find(c => c.id == currentConversationId);
    const otherUserName = conversation ? conversation.other_user_name : 'Người gọi';
    
    // Update call modal to show active call state
    $('#callerName').text(otherUserName);
    $('#callType').text('Cuộc gọi thoại');
    $('#callStatus').text('Đang gọi...');
    
    // Clear existing buttons first
    $('#callControls').empty();
    // Show end call button only with direct onclick
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
    
    // QUAN TRỌNG: Đảm bảo modal hiển thị và không bị ẩn
    const modalElement = document.getElementById('callModal');
    if (modalElement) {
        let modal = bootstrap.Modal.getInstance(modalElement);
        if (!modal) {
            modal = new bootstrap.Modal(modalElement);
        }
        modal.show();
        
        // Force show với CSS để đảm bảo hiển thị
        $(modalElement).addClass('show').css({
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1',
            'z-index': '10000'
        });
    }
    
    // Ẩn video container nếu đang hiển thị
    $('#videoCallContainer').hide();
    
    console.log('✅ Admin voice call UI shown with end call button');
}

// Setup Stringee event handlers khi page load
$(document).ready(function() {
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
});

// Toggle mute với Stringee SDK
function toggleMute() {
    if (window.StringeeHelper) {
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
    if (window.StringeeHelper) {
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
        url: '../src/controllers/media-upload.php',
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

</script>

<?php include 'includes/admin-footer.php'; ?>

