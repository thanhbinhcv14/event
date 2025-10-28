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
    <title>Chat Hỗ Trợ Thông Minh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .chat-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .chat-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .chat-title {
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .chat-subtitle {
            color: #666;
            font-size: 0.9rem;
            margin: 0.5rem 0 0 0;
        }

        .chat-body {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 0 0 20px 20px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            min-height: 500px;
        }

        .quick-questions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .question-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-align: left;
        }

        .question-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .question-card i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .question-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .question-desc {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .chat-messages {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1rem;
            min-height: 300px;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 1rem;
        }

        .message {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
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

        .message.user {
            justify-content: flex-end;
        }

        .message.assistant {
            justify-content: flex-start;
        }

        .message-content {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 18px;
            position: relative;
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
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }

        .message.user .message-time {
            text-align: right;
        }

        .typing-indicator {
            display: none;
            padding: 0.5rem 1rem;
            color: #666;
            font-style: italic;
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
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            outline: none;
            transition: all 0.3s ease;
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
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .send-button:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .smart-suggestions {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .suggestion-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .suggestion-item {
            background: white;
            padding: 0.5rem 1rem;
            margin: 0.25rem 0;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .suggestion-item:hover {
            background: #667eea;
            color: white;
            transform: translateX(5px);
        }

        .ai-thinking {
            display: none;
            text-align: center;
            padding: 1rem;
            color: #666;
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

        .back-button {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <button class="back-button" onclick="goBack()">
            <i class="fas fa-arrow-left"></i>
        </button>

        <div class="chat-header">
            <h1 class="chat-title">
                <i class="fas fa-robot"></i>
                Chat Hỗ Trợ Thông Minh
            </h1>
            <p class="chat-subtitle">Tôi có thể giúp bạn trả lời các câu hỏi về hệ thống và sự kiện</p>
        </div>

        <div class="chat-body">
            <!-- Quick Questions -->
            <div class="quick-questions" id="quickQuestions">
                <button class="question-card" onclick="askQuestion('event_registration')">
                    <i class="fas fa-calendar-plus"></i>
                    <div class="question-title">Đăng ký sự kiện</div>
                    <div class="question-desc">Hướng dẫn đăng ký sự kiện mới</div>
                </button>

                <button class="question-card" onclick="askQuestion('event_status')">
                    <i class="fas fa-info-circle"></i>
                    <div class="question-title">Trạng thái sự kiện</div>
                    <div class="question-desc">Kiểm tra trạng thái đăng ký</div>
                </button>

                <button class="question-card" onclick="askQuestion('payment')">
                    <i class="fas fa-credit-card"></i>
                    <div class="question-title">Thanh toán</div>
                    <div class="question-desc">Hướng dẫn thanh toán phí sự kiện</div>
                </button>

                <button class="question-card" onclick="askQuestion('equipment')">
                    <i class="fas fa-tools"></i>
                    <div class="question-title">Thiết bị</div>
                    <div class="question-desc">Thông tin về thiết bị sự kiện</div>
                </button>

                <button class="question-card" onclick="askQuestion('location')">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="question-title">Địa điểm</div>
                    <div class="question-desc">Danh sách địa điểm tổ chức</div>
                </button>

                <button class="question-card" onclick="askQuestion('support')">
                    <i class="fas fa-headset"></i>
                    <div class="question-title">Hỗ trợ kỹ thuật</div>
                    <div class="question-desc">Giải quyết vấn đề kỹ thuật</div>
                </button>
            </div>

            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages">
                <div class="message assistant">
                    <div class="message-content">
                        <div>Xin chào! Tôi là trợ lý AI thông minh. Tôi có thể giúp bạn:</div>
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
                <div class="suggestion-title">Gợi ý thông minh:</div>
                <div id="suggestionItems"></div>
            </div>

            <!-- Chat Input -->
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="Nhập câu hỏi của bạn..." maxlength="500">
                <button class="send-button" id="sendButton" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentUserId = <?= $currentUserId ?>;
        let currentUserName = '<?= htmlspecialchars($currentUserName) ?>';
        let userRole = <?= $userRole ?>;
        let conversationId = null;
        let isTyping = false;

        // AI Knowledge Base
        const aiKnowledge = {
            event_registration: {
                title: "Đăng ký sự kiện",
                response: "Để đăng ký sự kiện, bạn cần:\n\n1. Chọn loại sự kiện phù hợp\n2. Chọn địa điểm tổ chức\n3. Đặt ngày giờ sự kiện\n4. Chọn thiết bị cần thiết\n5. Điền thông tin chi tiết\n6. Xác nhận và thanh toán\n\nBạn có muốn tôi hướng dẫn chi tiết từng bước không?",
                suggestions: ["Hướng dẫn chi tiết", "Xem danh sách sự kiện", "Kiểm tra địa điểm có sẵn"]
            },
            event_status: {
                title: "Trạng thái sự kiện",
                response: "Bạn có thể kiểm tra trạng thái sự kiện bằng cách:\n\n1. Vào mục 'Sự kiện của tôi'\n2. Xem trạng thái: Chờ duyệt, Đã duyệt, Từ chối\n3. Nếu cần hỗ trợ, liên hệ admin\n\nTrạng thái hiện tại của bạn là gì?",
                suggestions: ["Kiểm tra sự kiện của tôi", "Liên hệ admin", "Xem lịch sử đăng ký"]
            },
            payment: {
                title: "Thanh toán",
                response: "Hệ thống hỗ trợ các phương thức thanh toán:\n\n1. Chuyển khoản ngân hàng\n2. Thanh toán trực tiếp tại văn phòng\n3. Thanh toán online (nếu có)\n\nPhí sự kiện sẽ được tính dựa trên:\n- Loại sự kiện\n- Thời gian tổ chức\n- Thiết bị sử dụng\n- Địa điểm",
                suggestions: ["Xem bảng giá", "Hướng dẫn thanh toán", "Liên hệ tài chính"]
            },
            equipment: {
                title: "Thiết bị sự kiện",
                response: "Hệ thống cung cấp đầy đủ thiết bị cho sự kiện:\n\n🎵 Âm thanh: Micro, Loa, Mixer\n🎬 Video: Máy chiếu, Màn hình LED\n💡 Ánh sáng: Đèn sân khấu, Đèn trang trí\n🪑 Nội thất: Bàn ghế, Khán đài\n\nBạn cần thiết bị gì cho sự kiện?",
                suggestions: ["Xem danh sách thiết bị", "Kiểm tra tình trạng", "Đặt trước thiết bị"]
            },
            location: {
                title: "Địa điểm tổ chức",
                response: "Chúng tôi có nhiều địa điểm phù hợp:\n\n🏢 Hội trường lớn: Phù hợp sự kiện quy mô lớn\n🏛️ Phòng họp: Sự kiện nhỏ, hội thảo\n🌳 Ngoài trời: Sự kiện cộng đồng\n🎪 Sân khấu: Biểu diễn, ca nhạc\n\nBạn muốn tổ chức sự kiện gì?",
                suggestions: ["Xem danh sách địa điểm", "Kiểm tra lịch trống", "Đặt địa điểm"]
            },
            support: {
                title: "Hỗ trợ kỹ thuật",
                response: "Tôi có thể giúp bạn giải quyết:\n\n🔧 Vấn đề đăng nhập/đăng ký\n📱 Lỗi giao diện\n💾 Khôi phục dữ liệu\n🔄 Đồng bộ thông tin\n\nMô tả chi tiết vấn đề bạn đang gặp phải?",
                suggestions: ["Lỗi đăng nhập", "Không tải được trang", "Mất dữ liệu", "Liên hệ admin"]
            }
        };

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
                    }, 2000);
                }, 500);
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
                    // Generate smart response based on user role and context
                    response = generateSmartResponse(message);
                    suggestions = generateSmartSuggestions(message);
                }
                
                hideAIThinking();
                addMessage(response, 'assistant');
                showSmartSuggestions(suggestions);
            }, 2000);
        }

        // Generate smart response
        function generateSmartResponse(message) {
            const lowerMessage = message.toLowerCase();
            
            if (lowerMessage.includes('cảm ơn') || lowerMessage.includes('thank')) {
                return "Không có gì! Tôi rất vui được giúp đỡ bạn. Nếu có thêm câu hỏi gì, đừng ngại hỏi nhé! 😊";
            }
            
            if (lowerMessage.includes('xin chào') || lowerMessage.includes('hello')) {
                return `Xin chào ${currentUserName}! Tôi là trợ lý AI của hệ thống. Tôi có thể giúp bạn với các vấn đề về sự kiện, đăng ký, thanh toán và nhiều hơn nữa. Bạn cần hỗ trợ gì?`;
            }
            
            if (lowerMessage.includes('giờ') || lowerMessage.includes('thời gian')) {
                return `Hiện tại là ${new Date().toLocaleString('vi-VN')}. Hệ thống hoạt động 24/7 để phục vụ bạn. Bạn có thể đăng ký sự kiện bất cứ lúc nào!`;
            }
            
            if (lowerMessage.includes('admin') || lowerMessage.includes('quản trị')) {
                return "Để liên hệ admin, bạn có thể:\n\n1. Sử dụng chat hỗ trợ trực tiếp\n2. Gửi email đến admin\n3. Gọi hotline hỗ trợ\n\nBạn muốn tôi kết nối với admin không?";
            }
            
            // Default smart response
            return "Tôi hiểu câu hỏi của bạn. Dựa trên phân tích, tôi khuyến nghị bạn:\n\n1. Kiểm tra thông tin trong tài khoản\n2. Liên hệ hỗ trợ nếu cần thiết\n3. Tham khảo hướng dẫn sử dụng\n\nBạn có muốn tôi hướng dẫn chi tiết hơn không?";
        }

        // Generate smart suggestions
        function generateSmartSuggestions(message) {
            const lowerMessage = message.toLowerCase();
            
            if (lowerMessage.includes('đăng ký')) {
                return ["Hướng dẫn đăng ký", "Xem sự kiện có sẵn", "Kiểm tra trạng thái"];
            }
            
            if (lowerMessage.includes('thanh toán')) {
                return ["Xem bảng giá", "Hướng dẫn thanh toán", "Liên hệ tài chính"];
            }
            
            if (lowerMessage.includes('thiết bị')) {
                return ["Xem danh sách thiết bị", "Kiểm tra tình trạng", "Đặt trước"];
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

        // Hide smart suggestions
        function hideSmartSuggestions() {
            document.getElementById('smartSuggestions').style.display = 'none';
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

        // Go back
        function goBack() {
            window.history.back();
        }

        // Event listeners
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Initialize
        $(document).ready(function() {
            console.log('Smart Chat Widget initialized');
            console.log('User:', currentUserName, 'Role:', userRole);
        });
    </script>
</body>
</html>
