// Floating Chat Widget JavaScript
class FloatingChatWidget {
    constructor(options = {}) {
        this.currentUserId = options.currentUserId || 0;
        this.currentUserName = options.currentUserName || 'User';
        this.userRole = options.userRole || 5;
        this.isChatOpen = false;
        
        // AI Knowledge Base
        this.aiKnowledge = {
            event_registration: {
                title: "Đăng ký sự kiện",
                response: "Để đăng ký sự kiện:\n\n1. Chọn loại sự kiện\n2. Chọn địa điểm\n3. Đặt ngày giờ\n4. Chọn thiết bị\n5. Điền thông tin\n6. Xác nhận\n\nBạn cần hướng dẫn chi tiết không?",
                suggestions: ["Hướng dẫn chi tiết", "Xem sự kiện", "Kiểm tra trạng thái"]
            },
            event_status: {
                title: "Trạng thái sự kiện",
                response: "Kiểm tra trạng thái:\n\n1. Vào 'Sự kiện của tôi'\n2. Xem trạng thái: Chờ duyệt, Đã duyệt, Từ chối\n3. Liên hệ admin nếu cần\n\nTrạng thái hiện tại của bạn là gì?",
                suggestions: ["Kiểm tra sự kiện", "Liên hệ admin", "Xem lịch sử"]
            },
            payment: {
                title: "Thanh toán",
                response: "Phương thức thanh toán:\n\n1. Chuyển khoản ngân hàng\n2. Thanh toán trực tiếp\n3. Thanh toán online\n\nPhí tính theo loại sự kiện, thời gian, thiết bị, địa điểm.",
                suggestions: ["Xem bảng giá", "Hướng dẫn thanh toán", "Liên hệ tài chính"]
            },
            equipment: {
                title: "Thiết bị sự kiện",
                response: "Thiết bị có sẵn:\n\n🎵 Âm thanh: Micro, Loa, Mixer\n🎬 Video: Máy chiếu, Màn hình\n💡 Ánh sáng: Đèn sân khấu\n🪑 Nội thất: Bàn ghế, Khán đài\n\nBạn cần thiết bị gì?",
                suggestions: ["Xem danh sách", "Kiểm tra tình trạng", "Đặt trước"]
            },
            location: {
                title: "Địa điểm tổ chức",
                response: "Địa điểm có sẵn:\n\n🏢 Hội trường lớn: Sự kiện quy mô lớn\n🏛️ Phòng họp: Hội thảo, sự kiện nhỏ\n🌳 Ngoài trời: Sự kiện cộng đồng\n🎪 Sân khấu: Biểu diễn, ca nhạc\n\nBạn muốn tổ chức gì?",
                suggestions: ["Xem địa điểm", "Kiểm tra lịch", "Đặt địa điểm"]
            },
            support: {
                title: "Hỗ trợ kỹ thuật",
                response: "Tôi có thể giúp:\n\n🔧 Vấn đề đăng nhập\n📱 Lỗi giao diện\n💾 Khôi phục dữ liệu\n🔄 Đồng bộ thông tin\n\nMô tả vấn đề bạn gặp phải?",
                suggestions: ["Lỗi đăng nhập", "Không tải trang", "Mất dữ liệu", "Liên hệ admin"]
            }
        };
        
        this.init();
    }
    
    init() {
        this.createWidget();
        this.bindEvents();
        this.autoOpen();
    }
    
    createWidget() {
        // Create floating chat container
        const container = document.createElement('div');
        container.className = 'floating-chat-container';
        container.innerHTML = `
            <button class="chat-button" id="chatButton">
                <i class="fas fa-robot"></i>
            </button>
            
            <div class="chat-widget" id="chatWidget">
                <div class="chat-header">
                    <h1 class="chat-title">
                        <i class="fas fa-robot"></i>
                        Chat Hỗ Trợ AI
                    </h1>
                    <button class="close-button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="chat-body">
                    <div class="quick-questions" id="quickQuestions">
                        <button class="question-card" data-category="event_registration">
                            <i class="fas fa-calendar-plus"></i>
                            <div class="question-title">Đăng ký</div>
                            <div class="question-desc">Hướng dẫn</div>
                        </button>

                        <button class="question-card" data-category="event_status">
                            <i class="fas fa-info-circle"></i>
                            <div class="question-title">Trạng thái</div>
                            <div class="question-desc">Kiểm tra</div>
                        </button>

                        <button class="question-card" data-category="payment">
                            <i class="fas fa-credit-card"></i>
                            <div class="question-title">Thanh toán</div>
                            <div class="question-desc">Hướng dẫn</div>
                        </button>

                        <button class="question-card" data-category="equipment">
                            <i class="fas fa-tools"></i>
                            <div class="question-title">Thiết bị</div>
                            <div class="question-desc">Thông tin</div>
                        </button>

                        <button class="question-card" data-category="location">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="question-title">Địa điểm</div>
                            <div class="question-desc">Danh sách</div>
                        </button>

                        <button class="question-card" data-category="support">
                            <i class="fas fa-headset"></i>
                            <div class="question-title">Hỗ trợ</div>
                            <div class="question-desc">Giải quyết</div>
                        </button>
                    </div>

                    <div class="chat-messages" id="chatMessages">
                        <div class="message assistant">
                            <div class="message-content">
                                <div>Xin chào! Tôi có thể giúp bạn với các câu hỏi về hệ thống. Chọn một chủ đề hoặc nhập câu hỏi.</div>
                                <div class="message-time">${new Date().toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'})}</div>
                            </div>
                        </div>
                    </div>

                    <div class="ai-thinking" id="aiThinking">
                        <i class="fas fa-brain"></i>
                        <span>AI đang suy nghĩ</span>
                        <span class="thinking-dots">...</span>
                    </div>

                    <div class="smart-suggestions" id="smartSuggestions" style="display: none;">
                        <div class="suggestion-title">Gợi ý:</div>
                        <div id="suggestionItems"></div>
                    </div>

                    <div class="chat-input">
                        <input type="text" id="messageInput" placeholder="Nhập câu hỏi..." maxlength="500">
                        <button class="send-button" id="sendButton">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(container);
    }
    
    bindEvents() {
        // Toggle chat
        document.getElementById('chatButton').addEventListener('click', () => this.toggleChat());
        document.querySelector('.close-button').addEventListener('click', () => this.toggleChat());
        
        // Quick questions
        document.querySelectorAll('.question-card').forEach(card => {
            card.addEventListener('click', () => {
                const category = card.dataset.category;
                this.askQuestion(category);
            });
        });
        
        // Send message
        document.getElementById('sendButton').addEventListener('click', () => this.sendMessage());
        
        // Enter key
        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
    }
    
    toggleChat() {
        const chatWidget = document.getElementById('chatWidget');
        const chatButton = document.getElementById('chatButton');
        
        this.isChatOpen = !this.isChatOpen;
        
        if (this.isChatOpen) {
            chatWidget.classList.add('show');
            chatButton.classList.add('pulse');
            // Ensure chatbox is positioned at bottom right
            chatWidget.style.bottom = '20px';
            chatWidget.style.right = '20px';
        } else {
            chatWidget.classList.remove('show');
            chatButton.classList.remove('pulse');
        }
    }
    
    analyzeContext(message) {
        const keywords = {
            'đăng ký': 'event_registration',
            'sự kiện': 'event_registration',
            'trạng thái': 'event_status',
            'thanh toán': 'payment',
            'tiền': 'payment',
            'thiết bị': 'equipment',
            'địa điểm': 'location',
            'lỗi': 'support',
            'hỗ trợ': 'support',
            'giúp': 'support'
        };

        const lowerMessage = message.toLowerCase();
        for (const [keyword, category] of Object.entries(keywords)) {
            if (lowerMessage.includes(keyword)) {
                return category;
            }
        }
        return null;
    }
    
    askQuestion(category) {
        const question = this.aiKnowledge[category];
        if (question) {
            this.addMessage(question.title, 'user');
            setTimeout(() => {
                this.showAIThinking();
                setTimeout(() => {
                    this.hideAIThinking();
                    this.addMessage(question.response, 'assistant');
                    this.showSmartSuggestions(question.suggestions);
                }, 1200);
            }, 300);
        }
    }
    
    sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message) return;
        
        this.addMessage(message, 'user');
        input.value = '';
        
        this.showAIThinking();
        
        setTimeout(() => {
            const context = this.analyzeContext(message);
            let response = '';
            let suggestions = [];
            
            if (context && this.aiKnowledge[context]) {
                response = this.aiKnowledge[context].response;
                suggestions = this.aiKnowledge[context].suggestions;
            } else {
                response = this.generateSmartResponse(message);
                suggestions = this.generateSmartSuggestions(message);
            }
            
            this.hideAIThinking();
            this.addMessage(response, 'assistant');
            this.showSmartSuggestions(suggestions);
        }, 1200);
    }
    
    generateSmartResponse(message) {
        const lowerMessage = message.toLowerCase();
        
        if (lowerMessage.includes('cảm ơn') || lowerMessage.includes('thank')) {
            return "Không có gì! Tôi rất vui được giúp đỡ bạn. 😊";
        }
        
        if (lowerMessage.includes('xin chào') || lowerMessage.includes('hello')) {
            return `Xin chào ${this.currentUserName}! Tôi là trợ lý AI. Tôi có thể giúp bạn với các vấn đề về sự kiện, đăng ký, thanh toán. Bạn cần hỗ trợ gì?`;
        }
        
        if (lowerMessage.includes('giờ') || lowerMessage.includes('thời gian')) {
            return `Hiện tại là ${new Date().toLocaleString('vi-VN')}. Hệ thống hoạt động 24/7!`;
        }
        
        return "Tôi hiểu câu hỏi của bạn. Dựa trên phân tích, tôi khuyến nghị bạn kiểm tra thông tin trong tài khoản hoặc liên hệ hỗ trợ. Bạn có muốn tôi hướng dẫn chi tiết hơn không?";
    }
    
    generateSmartSuggestions(message) {
        const lowerMessage = message.toLowerCase();
        
        if (lowerMessage.includes('đăng ký')) {
            return ["Hướng dẫn đăng ký", "Xem sự kiện", "Kiểm tra trạng thái"];
        }
        
        if (lowerMessage.includes('thanh toán')) {
            return ["Xem bảng giá", "Hướng dẫn thanh toán", "Liên hệ tài chính"];
        }
        
        return ["Xem thêm thông tin", "Liên hệ hỗ trợ", "Hướng dẫn chi tiết"];
    }
    
    addMessage(text, sender) {
        const chatMessages = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        
        const time = new Date().toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        messageDiv.innerHTML = `
            <div class="message-content">
                <div>${this.escapeHtml(text)}</div>
                <div class="message-time">${time}</div>
            </div>
        `;
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    showAIThinking() {
        document.getElementById('aiThinking').classList.add('show');
    }
    
    hideAIThinking() {
        document.getElementById('aiThinking').classList.remove('show');
    }
    
    showSmartSuggestions(suggestions) {
        const container = document.getElementById('smartSuggestions');
        const items = document.getElementById('suggestionItems');
        
        items.innerHTML = '';
        suggestions.forEach(suggestion => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.textContent = suggestion;
            item.onclick = () => {
                document.getElementById('messageInput').value = suggestion;
                this.sendMessage();
            };
            items.appendChild(item);
        });
        
        container.style.display = 'block';
    }
    
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    autoOpen() {
        // Auto-open chat after 3 seconds
        setTimeout(() => {
            if (!this.isChatOpen) {
                this.toggleChat();
            }
        }, 3000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if Font Awesome is loaded
    if (typeof FontAwesome === 'undefined') {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
        document.head.appendChild(link);
    }
    
    // Initialize floating chat widget
    window.floatingChat = new FloatingChatWidget({
        currentUserId: window.currentUserId || 0,
        currentUserName: window.currentUserName || 'User',
        userRole: window.userRole || 5
    });
});
