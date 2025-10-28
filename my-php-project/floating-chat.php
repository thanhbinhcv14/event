<?php
// Include database connection
require_once 'config/database.php';
require_once 'src/auth/auth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user info
$currentUserId = $_SESSION['user']['ID_User'] ?? $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;
$currentUserName = $_SESSION['user']['HoTen'] ?? $_SESSION['user']['name'] ?? $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? $_SESSION['user_role'] ?? 5;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Floating Chat Widget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .floating-chat {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .chat-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
        }

        .chat-button.pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            }
            50% {
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4), 0 0 0 10px rgba(102, 126, 234, 0.1);
            }
            100% {
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
            }
        }

        .chat-widget {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 1001;
        }

        .chat-widget.show {
            display: flex;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-button {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-button:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .chat-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 1rem;
        }

        .quick-questions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .question-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-align: left;
            font-size: 0.8rem;
        }

        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }

        .question-card i {
            font-size: 1rem;
            margin-bottom: 0.25rem;
            display: block;
        }

        .question-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
        }

        .question-desc {
            font-size: 0.7rem;
            opacity: 0.9;
        }

        .chat-messages {
            flex: 1;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 1rem;
            overflow-y: auto;
            min-height: 150px;
        }

        .message {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: flex-start;
            animation: messageSlideIn 0.3s ease-out;
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            justify-content: flex-end;
        }

        .message.assistant {
            justify-content: flex-start;
        }

        .message-content {
            max-width: 80%;
            padding: 0.4rem 0.6rem;
            border-radius: 12px;
            position: relative;
            font-size: 0.8rem;
        }

        .message.user .message-content {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.assistant .message-content {
            background: white;
            color: #333;
            border: 1px solid #e9ecef;
            border-bottom-left-radius: 4px;
        }

        .message-time {
            font-size: 0.6rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .message.user .message-time {
            text-align: right;
        }

        .typing-indicator {
            display: none;
            padding: 0.4rem;
            color: #666;
            font-style: italic;
            font-size: 0.75rem;
        }

        .typing-indicator.show {
            display: block;
        }

        .chat-input {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .chat-input input {
            flex: 1;
            padding: 0.4rem 0.6rem;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            outline: none;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }

        .chat-input input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .send-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.7rem;
        }

        .send-button:hover {
            transform: scale(1.1);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }

        .send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .smart-suggestions {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }

        .suggestion-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
        }

        .suggestion-item {
            background: white;
            padding: 0.3rem 0.6rem;
            margin: 0.2rem 0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            font-size: 0.7rem;
        }

        .suggestion-item:hover {
            background: #667eea;
            color: white;
            transform: translateX(3px);
        }

        .ai-thinking {
            display: none;
            text-align: center;
            padding: 0.4rem;
            color: #666;
            font-size: 0.75rem;
        }

        .ai-thinking.show {
            display: block;
        }

        .thinking-dots {
            display: inline-block;
            animation: thinking 1.5s infinite;
        }

        @keyframes thinking {
            0%, 20% { opacity: 0; }
            50% { opacity: 1; }
            80%, 100% { opacity: 0; }
        }

        @media (max-width: 768px) {
            .chat-widget {
                width: 300px;
                height: 400px;
                bottom: 80px;
                right: 10px;
            }
            
            .floating-chat {
                bottom: 15px;
                right: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="floating-chat">
        <button class="chat-button" id="chatButton" onclick="toggleChat()">
            <i class="fas fa-robot"></i>
        </button>
        
        <div class="chat-widget" id="chatWidget">
            <div class="chat-header">
                <h1 class="chat-title">
                    <i class="fas fa-robot"></i>
                    Chat Hỗ Trợ AI
                </h1>
                <button class="close-button" onclick="toggleChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="chat-body">
                <!-- Quick Questions -->
                <div class="quick-questions" id="quickQuestions">
                    <button class="question-card" onclick="askQuestion('event_registration')">
                        <i class="fas fa-calendar-plus"></i>
                        <div class="question-title">Đăng ký</div>
                        <div class="question-desc">Hướng dẫn</div>
                    </button>

                    <button class="question-card" onclick="askQuestion('event_status')">
                        <i class="fas fa-info-circle"></i>
                        <div class="question-title">Trạng thái</div>
                        <div class="question-desc">Kiểm tra</div>
                    </button>

                    <button class="question-card" onclick="askQuestion('payment')">
                        <i class="fas fa-credit-card"></i>
                        <div class="question-title">Thanh toán</div>
                        <div class="question-desc">Hướng dẫn</div>
                    </button>

                    <button class="question-card" onclick="askQuestion('equipment')">
                        <i class="fas fa-tools"></i>
                        <div class="question-title">Thiết bị</div>
                        <div class="question-desc">Thông tin</div>
                    </button>

                    <button class="question-card" onclick="askQuestion('location')">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="question-title">Địa điểm</div>
                        <div class="question-desc">Danh sách</div>
                    </button>

                    <button class="question-card" onclick="askQuestion('support')">
                        <i class="fas fa-headset"></i>
                        <div class="question-title">Hỗ trợ</div>
                        <div class="question-desc">Giải quyết</div>
                    </button>
                </div>

                <!-- Chat Messages -->
                <div class="chat-messages" id="chatMessages">
                    <div class="message assistant">
                        <div class="message-content">
                            <div>Xin chào! Tôi có thể giúp bạn với các câu hỏi về hệ thống. Chọn một chủ đề hoặc nhập câu hỏi.</div>
                            <div class="message-time"><?= date('H:i') ?></div>
                        </div>
                    </div>
                </div>

                <!-- AI Thinking Indicator -->
                <div class="ai-thinking" id="aiThinking">
                    <i class="fas fa-brain"></i>
                    <span>AI đang suy nghĩ</span>
                    <span class="thinking-dots">...</span>
                </div>

                <!-- Smart Suggestions -->
                <div class="smart-suggestions" id="smartSuggestions" style="display: none;">
                    <div class="suggestion-title">Gợi ý:</div>
                    <div id="suggestionItems"></div>
                </div>

                <!-- Chat Input -->
                <div class="chat-input">
                    <input type="text" id="messageInput" placeholder="Nhập câu hỏi..." maxlength="500">
                    <button class="send-button" id="sendButton" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentUserId = <?= $currentUserId ?>;
        let currentUserName = '<?= htmlspecialchars($currentUserName) ?>';
        let userRole = <?= $userRole ?>;
        let isChatOpen = false;

        // AI Knowledge Base
        const aiKnowledge = {
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

        // Toggle chat
        function toggleChat() {
            const chatWidget = document.getElementById('chatWidget');
            const chatButton = document.getElementById('chatButton');
            
            isChatOpen = !isChatOpen;
            
            if (isChatOpen) {
                chatWidget.classList.add('show');
                chatButton.classList.add('pulse');
            } else {
                chatWidget.classList.remove('show');
                chatButton.classList.remove('pulse');
            }
        }

        // Smart context analysis
        function analyzeContext(message) {
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

        // Ask predefined question
        function askQuestion(category) {
            const question = aiKnowledge[category];
            if (question) {
                addMessage(question.title, 'user');
                setTimeout(() => {
                    showAIThinking();
                    setTimeout(() => {
                        hideAIThinking();
                        addMessage(question.response, 'assistant');
                        showSmartSuggestions(question.suggestions);
                    }, 1200);
                }, 300);
            }
        }

        // Send message
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            addMessage(message, 'user');
            input.value = '';
            
            // Show AI thinking
            showAIThinking();
            
            // Analyze context and respond
            setTimeout(() => {
                const context = analyzeContext(message);
                let response = '';
                let suggestions = [];
                
                if (context && aiKnowledge[context]) {
                    response = aiKnowledge[context].response;
                    suggestions = aiKnowledge[context].suggestions;
                } else {
                    response = generateSmartResponse(message);
                    suggestions = generateSmartSuggestions(message);
                }
                
                hideAIThinking();
                addMessage(response, 'assistant');
                showSmartSuggestions(suggestions);
            }, 1200);
        }

        // Generate smart response
        function generateSmartResponse(message) {
            const lowerMessage = message.toLowerCase();
            
            if (lowerMessage.includes('cảm ơn') || lowerMessage.includes('thank')) {
                return "Không có gì! Tôi rất vui được giúp đỡ bạn. 😊";
            }
            
            if (lowerMessage.includes('xin chào') || lowerMessage.includes('hello')) {
                return `Xin chào ${currentUserName}! Tôi là trợ lý AI. Tôi có thể giúp bạn với các vấn đề về sự kiện, đăng ký, thanh toán. Bạn cần hỗ trợ gì?`;
            }
            
            if (lowerMessage.includes('giờ') || lowerMessage.includes('thời gian')) {
                return `Hiện tại là ${new Date().toLocaleString('vi-VN')}. Hệ thống hoạt động 24/7!`;
            }
            
            return "Tôi hiểu câu hỏi của bạn. Dựa trên phân tích, tôi khuyến nghị bạn kiểm tra thông tin trong tài khoản hoặc liên hệ hỗ trợ. Bạn có muốn tôi hướng dẫn chi tiết hơn không?";
        }

        // Generate smart suggestions
        function generateSmartSuggestions(message) {
            const lowerMessage = message.toLowerCase();
            
            if (lowerMessage.includes('đăng ký')) {
                return ["Hướng dẫn đăng ký", "Xem sự kiện", "Kiểm tra trạng thái"];
            }
            
            if (lowerMessage.includes('thanh toán')) {
                return ["Xem bảng giá", "Hướng dẫn thanh toán", "Liên hệ tài chính"];
            }
            
            return ["Xem thêm thông tin", "Liên hệ hỗ trợ", "Hướng dẫn chi tiết"];
        }

        // Add message to chat
        function addMessage(text, sender) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}`;
            
            const time = new Date().toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            messageDiv.innerHTML = `
                <div class="message-content">
                    <div>${escapeHtml(text)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Show AI thinking
        function showAIThinking() {
            document.getElementById('aiThinking').classList.add('show');
        }

        // Hide AI thinking
        function hideAIThinking() {
            document.getElementById('aiThinking').classList.remove('show');
        }

        // Show smart suggestions
        function showSmartSuggestions(suggestions) {
            const container = document.getElementById('smartSuggestions');
            const items = document.getElementById('suggestionItems');
            
            items.innerHTML = '';
            suggestions.forEach(suggestion => {
                const item = document.createElement('div');
                item.className = 'suggestion-item';
                item.textContent = suggestion;
                item.onclick = () => {
                    document.getElementById('messageInput').value = suggestion;
                    sendMessage();
                };
                items.appendChild(item);
            });
            
            container.style.display = 'block';
        }

        // Escape HTML
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

        // Event listeners
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Initialize
        $(document).ready(function() {
            console.log('Floating Chat Widget initialized');
            
            // Auto-open chat after 3 seconds
            setTimeout(() => {
                if (!isChatOpen) {
                    toggleChat();
                }
            }, 3000);
        });
    </script>
</body>
</html>
