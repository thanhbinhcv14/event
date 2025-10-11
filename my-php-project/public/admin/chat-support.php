<?php
// Include admin header
include 'includes/admin-header.php';

// Get user info
$user = $_SESSION['user'];
$userRole = $user['ID_Role'] ?? $user['role'] ?? null;
$userName = $user['Email'] ?? 'User';
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-comments"></i>
                Chat hỗ trợ khách hàng
            </h1>
            <p class="page-subtitle">Hỗ trợ khách hàng trực tuyến và quản lý cuộc trò chuyện</p>
        </div>

        <!-- Chat Support Interface -->
        <div class="chat-support-container">
            <div class="row">
                <!-- Customer List -->
                <div class="col-lg-4">
                    <div class="chat-sidebar">
                        <div class="chat-sidebar-header">
                            <h5><i class="fas fa-users"></i> Danh sách khách hàng</h5>
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
                        
                        <div class="customer-list" id="customerList">
                            <!-- Customer list will be loaded here -->
                        </div>
                    </div>
                </div>
                
                <!-- Chat Area -->
                <div class="col-lg-8">
                    <div class="chat-main">
                        <div class="chat-header" id="chatHeader">
                            <div class="chat-user-info">
                                <div class="user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="user-details">
                                    <h6 id="currentUserName">Chọn khách hàng để bắt đầu chat</h6>
                                    <small id="currentUserStatus" class="text-muted">Chưa có cuộc trò chuyện</small>
                                </div>
                            </div>
                            <div class="chat-actions">
                                <button class="btn btn-sm btn-outline-primary" id="transferChat" disabled>
                                    <i class="fas fa-exchange-alt"></i> Chuyển
                                </button>
                                <button class="btn btn-sm btn-outline-danger" id="endChat" disabled>
                                    <i class="fas fa-times"></i> Kết thúc
                                </button>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chatMessages">
                            <div class="welcome-message">
                                <div class="text-center">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <h5>Chào mừng đến với hệ thống chat hỗ trợ</h5>
                                    <p class="text-muted">Chọn một khách hàng từ danh sách bên trái để bắt đầu cuộc trò chuyện</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chat-input-area" id="chatInputArea" style="display: none;">
                            <div class="chat-input-container">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="messageInput" placeholder="Nhập tin nhắn...">
                                    <button class="btn btn-primary" id="sendMessage" disabled>
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                                <div class="chat-options">
                                    <button class="btn btn-sm btn-outline-secondary" id="attachFile">
                                        <i class="fas fa-paperclip"></i> Đính kèm
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" id="quickReply">
                                        <i class="fas fa-reply"></i> Trả lời nhanh
                                    </button>
                                </div>
                            </div>
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
        .chat-support-container {
            height: calc(100vh - 200px);
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .chat-sidebar {
            height: 100%;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
        }
        
        .chat-sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            background: white;
        }
        
        .chat-sidebar-header h5 {
            margin: 0;
            color: #495057;
        }
        
        .online-count {
            margin-top: 5px;
        }
        
        .customer-search {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .customer-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }
        
        .customer-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f3f4;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .customer-item:hover {
            background: #e9ecef;
        }
        
        .customer-item.active {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .customer-info {
            flex: 1;
        }
        
        .customer-name {
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .customer-last-message {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 2px 0 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .customer-meta {
            text-align: right;
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .customer-status {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-online { background: #28a745; }
        .status-away { background: #ffc107; }
        .status-offline { background: #6c757d; }
        
        .chat-main {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .user-details h6 {
            margin: 0;
            color: #333;
        }
        
        .chat-actions {
            display: flex;
            gap: 10px;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .welcome-message {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .message {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .message.sent {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        .message.sent .message-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .message.received .message-avatar {
            background: #6c757d;
        }
        
        .message-content {
            max-width: 70%;
            background: white;
            padding: 12px 16px;
            border-radius: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .message.sent .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .message-text {
            margin: 0;
            word-wrap: break-word;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .message.sent .message-time {
            color: rgba(255,255,255,0.8);
        }
        
        .chat-input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #dee2e6;
        }
        
        .chat-input-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .chat-options {
            display: flex;
            gap: 10px;
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
        
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #6c757d;
            font-style: italic;
            margin-bottom: 10px;
        }
        
        .typing-dots {
            display: flex;
            gap: 3px;
        }
        
        .typing-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #6c757d;
            animation: typing 1.4s infinite;
        }
        
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        
        @media (max-width: 768px) {
            .chat-support-container {
                height: calc(100vh - 150px);
            }
            
            .chat-sidebar {
                display: none;
            }
            
            .col-lg-8 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        </style>

        <script>
        let currentChatId = null;
        let currentCustomerId = null;
        let chatSocket = null;
        let typingTimer = null;
        
        // Initialize chat support
        document.addEventListener('DOMContentLoaded', function() {
            initializeChatSupport();
            loadCustomers();
            setupEventListeners();
        });
        
        function initializeChatSupport() {
            // Initialize WebSocket connection
            connectWebSocket();
            
            // Load quick reply templates
            loadQuickReplyTemplates();
            
            // Load transfer options
            loadTransferOptions();
        }
        
        function connectWebSocket() {
            // Simulate WebSocket connection
            console.log('Connecting to chat support WebSocket...');
        }
        
        function loadCustomers() {
            fetch('../../src/controllers/chat-support.php?action=get_customers')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        renderCustomerList(data.customers);
                        updateOnlineCount(data.customers.filter(c => c.status === 'online').length);
                    } else {
                        console.error('Error loading customers:', data.error);
                        // Show error message
                        document.getElementById('customerList').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Lỗi tải danh sách khách hàng: ${data.error}
                            </div>
                        `;
                        updateOnlineCount(0);
                    }
                })
                .catch(error => {
                    console.error('Error loading customers:', error);
                    document.getElementById('customerList').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Lỗi kết nối: ${error.message}
                        </div>
                    `;
                    updateOnlineCount(0);
                });
        }
        
        function renderCustomerList(customers) {
            const customerList = document.getElementById('customerList');
            customerList.innerHTML = '';
            
            customers.forEach(customer => {
                const customerItem = document.createElement('div');
                customerItem.className = 'customer-item';
                customerItem.dataset.customerId = customer.id;
                
                customerItem.innerHTML = `
                    <div class="customer-avatar">
                        ${customer.name.charAt(0)}
                    </div>
                    <div class="customer-info">
                        <h6 class="customer-name">${customer.name}</h6>
                        <p class="customer-last-message">${customer.lastMessage}</p>
                    </div>
                    <div class="customer-meta">
                        <div>
                            <span class="customer-status status-${customer.status}"></span>
                            ${customer.lastMessageTime}
                        </div>
                        ${customer.unreadCount > 0 ? `<span class="badge bg-primary">${customer.unreadCount}</span>` : ''}
                    </div>
                `;
                
                customerItem.addEventListener('click', () => selectCustomer(customer));
                customerList.appendChild(customerItem);
            });
        }
        
        function selectCustomer(customer) {
            // Remove active class from all customers
            document.querySelectorAll('.customer-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to selected customer
            const customerItem = document.querySelector(`[data-customer-id="${customer.id}"]`);
            if (customerItem) {
                customerItem.classList.add('active');
            }
            
            // Update chat header
            document.getElementById('currentUserName').textContent = customer.name;
            document.getElementById('currentUserStatus').textContent = 
                customer.status === 'online' ? 'Đang trực tuyến' : 
                customer.status === 'away' ? 'Tạm vắng' : 'Ngoại tuyến';
            
            // Show chat input
            document.getElementById('chatInputArea').style.display = 'block';
            document.getElementById('sendMessage').disabled = false;
            document.getElementById('transferChat').disabled = false;
            document.getElementById('endChat').disabled = false;
            
            // Load chat history
            loadChatHistory(customer.id);
            
            currentCustomerId = customer.id;
        }
        
        function loadChatHistory(customerId) {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>';
            
            fetch(`../../src/controllers/chat-support.php?action=get_chat_history&customer_id=${customerId}`)
                .then(response => response.json())
                .then(data => {
                    chatMessages.innerHTML = '';
                    if (data.success && data.messages) {
                        data.messages.forEach(message => {
                            addMessageToChat({
                                id: message.id,
                                sender: message.sender,
                                text: message.text,
                                time: message.time,
                                avatar: message.sender === 'customer' ? 
                                    message.senderName.charAt(0).toUpperCase() : 'S'
                            });
                        });
                    } else {
                        chatMessages.innerHTML = '<div class="text-center text-muted">Chưa có tin nhắn nào</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading chat history:', error);
                    chatMessages.innerHTML = '<div class="text-center text-danger">Lỗi tải lịch sử chat</div>';
                });
        }
        
        function addMessageToChat(message) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${message.sender === 'support' ? 'sent' : 'received'}`;
            
            messageDiv.innerHTML = `
                <div class="message-avatar">${message.avatar}</div>
                <div class="message-content">
                    <p class="message-text">${message.text}</p>
                    <div class="message-time">${message.time}</div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function setupEventListeners() {
            // Send message
            document.getElementById('sendMessage').addEventListener('click', sendMessage);
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
            
            // Quick reply
            document.getElementById('quickReply').addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('quickReplyModal'));
                modal.show();
            });
            
            // Transfer chat
            document.getElementById('transferChat').addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('transferChatModal'));
                modal.show();
            });
            
            // End chat
            document.getElementById('endChat').addEventListener('click', endChat);
            
            // Customer search
            document.getElementById('customerSearch').addEventListener('input', function(e) {
                searchCustomers(e.target.value);
            });
        }
        
        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (message && currentCustomerId) {
                // Disable send button
                const sendBtn = document.getElementById('sendMessage');
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Send message to server
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('customer_id', currentCustomerId);
                formData.append('message', message);
                
                fetch('../../src/controllers/chat-support.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Add message to chat
                        addMessageToChat({
                            id: data.message.id,
                            sender: 'support',
                            text: message,
                            time: data.message.time,
                            avatar: 'S'
                        });
                        messageInput.value = '';
                    } else {
                        alert('Lỗi gửi tin nhắn: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    alert('Lỗi gửi tin nhắn');
                })
                .finally(() => {
                    // Re-enable send button
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                });
            }
        }
        
        function showTypingIndicator() {
            const chatMessages = document.getElementById('chatMessages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'typing-indicator';
            typingDiv.id = 'typingIndicator';
            typingDiv.innerHTML = `
                <span>Khách hàng đang nhập...</span>
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            `;
            chatMessages.appendChild(typingDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function hideTypingIndicator() {
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }
        
        function loadQuickReplyTemplates() {
            fetch('../../src/controllers/chat-support.php?action=get_quick_replies')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update quick reply templates in modal
                        const templateContainer = document.querySelector('.quick-reply-templates');
                        if (templateContainer) {
                            templateContainer.innerHTML = '';
                            data.quickReplies.forEach(template => {
                                const templateDiv = document.createElement('div');
                                templateDiv.className = 'template-item';
                                templateDiv.dataset.template = template.id;
                                templateDiv.innerHTML = `
                                    <strong>${template.title}</strong>
                                    <p>${template.text}</p>
                                `;
                                templateContainer.appendChild(templateDiv);
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading quick replies:', error);
                });
        }
        
        function loadTransferOptions() {
            // For now, use static options. In real implementation, load from database
            const transferSelect = document.getElementById('transferTo');
            const options = [
                { value: 'support1', text: 'Nhân viên hỗ trợ 1' },
                { value: 'support2', text: 'Nhân viên hỗ trợ 2' },
                { value: 'manager', text: 'Quản lý' }
            ];
            
            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.text;
                transferSelect.appendChild(optionElement);
            });
        }
        
        function searchCustomers(query) {
            if (query.length < 2) {
                loadCustomers(); // Reload all customers
                return;
            }
            
            fetch(`../../src/controllers/chat-support.php?action=search_customers&query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderCustomerList(data.customers);
                    } else {
                        console.error('Error searching customers:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error searching customers:', error);
                });
        }
        
        function endChat() {
            if (confirm('Bạn có chắc muốn kết thúc cuộc trò chuyện này?')) {
                if (currentCustomerId) {
                    const formData = new FormData();
                    formData.append('action', 'end_chat');
                    formData.append('customer_id', currentCustomerId);
                    
                    fetch('../../src/controllers/chat-support.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Đã kết thúc cuộc trò chuyện');
                        } else {
                            alert('Lỗi kết thúc cuộc trò chuyện: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error ending chat:', error);
                        alert('Lỗi kết thúc cuộc trò chuyện');
                    });
                }
                
                // Reset chat interface
                document.getElementById('chatMessages').innerHTML = `
                    <div class="welcome-message">
                        <div class="text-center">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h5>Chào mừng đến với hệ thống chat hỗ trợ</h5>
                            <p class="text-muted">Chọn một khách hàng từ danh sách bên trái để bắt đầu cuộc trò chuyện</p>
                        </div>
                    </div>
                `;
                
                document.getElementById('chatInputArea').style.display = 'none';
                document.getElementById('currentUserName').textContent = 'Chọn khách hàng để bắt đầu chat';
                document.getElementById('currentUserStatus').textContent = 'Chưa có cuộc trò chuyện';
                
                // Remove active customer
                document.querySelectorAll('.customer-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                currentCustomerId = null;
            }
        }
        
        function updateOnlineCount(count) {
            document.getElementById('onlineCount').textContent = count;
        }
        
        // Quick reply template selection
        document.addEventListener('click', function(e) {
            if (e.target.closest('.template-item')) {
                const template = e.target.closest('.template-item');
                const templateText = template.querySelector('p').textContent;
                
                document.getElementById('messageInput').value = templateText;
                bootstrap.Modal.getInstance(document.getElementById('quickReplyModal')).hide();
            }
        });
        
        // Confirm transfer
        document.getElementById('confirmTransfer').addEventListener('click', function() {
            const transferTo = document.getElementById('transferTo').value;
            const transferNote = document.getElementById('transferNote').value;
            
            if (transferTo && currentCustomerId) {
                const formData = new FormData();
                formData.append('action', 'transfer_chat');
                formData.append('customer_id', currentCustomerId);
                formData.append('transfer_to', transferTo);
                formData.append('note', transferNote);
                
                fetch('../../src/controllers/chat-support.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Đã chuyển cuộc trò chuyện thành công');
                        bootstrap.Modal.getInstance(document.getElementById('transferChatModal')).hide();
                    } else {
                        alert('Lỗi chuyển cuộc trò chuyện: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error transferring chat:', error);
                    alert('Lỗi chuyển cuộc trò chuyện');
                });
            } else {
                alert('Vui lòng chọn người nhận chuyển cuộc trò chuyện');
            }
        });
        </script>

<?php include 'includes/admin-footer.php'; ?>
