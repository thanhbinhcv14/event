/**
 * Chat Widget - Hỗ trợ trực tuyến
 * Tích hợp với hệ thống để hỗ trợ chat thông minh với database
 */

let isChatOpen = false;
let conversationHistory = [];
let isLoading = false;

// Tải lịch sử cuộc trò chuyện từ localStorage
function loadConversationHistory() {
    const saved = localStorage.getItem('geminiChatHistory');
    if (saved) {
        conversationHistory = JSON.parse(saved);
    }
}

// Lưu lịch sử cuộc trò chuyện vào localStorage
function saveConversationHistory() {
    localStorage.setItem('geminiChatHistory', JSON.stringify(conversationHistory));
}

// Mở widget chat
function openChatWidget() {
    const chatWidget = document.getElementById('chatWidget');
    const chatBtn = document.getElementById('floatingChatBtn') || document.querySelector('.floating-chat-btn');
    
    if (!isChatOpen) {
        loadConversationHistory();
        
        if (chatWidget) {
            chatWidget.classList.add('show');
            isChatOpen = true;
            
            if (chatBtn) {
                chatBtn.innerHTML = '<i class="fas fa-times"></i>';
                chatBtn.title = 'Đóng chat';
            }
            
            // Hiển thị tin nhắn chào mừng nếu chưa có lịch sử
            if (conversationHistory.length === 0) {
                const chatMessages = document.getElementById('chatMessages');
                if (chatMessages) {
                    chatMessages.innerHTML = '';
                }
                showWelcomeMessage();
            } else {
                restoreConversation();
            }
        }
    } else {
        closeChatWidget();
    }
}

// Đóng widget chat
function closeChatWidget() {
    const chatWidget = document.getElementById('chatWidget');
    const chatBtn = document.getElementById('floatingChatBtn') || document.querySelector('.floating-chat-btn');
    
    if (chatWidget) {
        chatWidget.classList.remove('show');
        isChatOpen = false;
        
        if (chatBtn) {
            chatBtn.innerHTML = '<i class="fas fa-comments"></i>';
            chatBtn.title = 'Chat hỗ trợ trực tuyến';
        }
    }
}

// Hiển thị tin nhắn chào mừng
function showWelcomeMessage() {
    const welcomeMsg = "Xin chào bạn! Tôi là nhân viên tư vấn. Tôi có thể giúp bạn:\n\n" +
        "📅 Đăng ký sự kiện\n" +
        "💰 Tư vấn giá cả và dịch vụ\n" +
        "🏢 Tìm địa điểm phù hợp\n" +
        "🎵 Chọn thiết bị cần thiết\n" +
        "💡 Đưa ra gợi ý tối ưu\n\n" +
        "Bạn cần hỗ trợ gì?";
    
    addChatMessage(welcomeMsg, 'assistant');
    showQuickSuggestions();
}

// Khôi phục cuộc trò chuyện trước đó
function restoreConversation() {
    const chatMessages = document.getElementById('chatMessages');
    if (!chatMessages) return;
    
    chatMessages.innerHTML = '';
    
    conversationHistory.forEach(msg => {
        addChatMessageToDOM(msg.content, msg.role === 'user' ? 'user' : 'assistant');
    });
    
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Gửi tin nhắn chat
async function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message || isLoading) {
        console.log('Message empty or already loading');
        return;
    }
    
    // Thêm tin nhắn của người dùng
    addChatMessage(message, 'user');
    input.value = '';
    
    // Hiển thị chỉ báo đang tải
    showLoadingIndicator();
    
    try {
        console.log('Sending message to API:', message);
        console.log('Conversation history:', conversationHistory);
        
        // Xác định đường dẫn API - sử dụng đường dẫn tương đối đơn giản
        // Giống như cách các controller khác được gọi trong project (events.php, etc.)
        const currentPath = window.location.pathname;
        console.log('Current path:', currentPath);
        
        // Sử dụng đường dẫn tương đối đơn giản - giống như events.php trong index.php
        // Từ index.php: 'src/controllers/gemini-ai.php'
        let apiUrl = 'src/controllers/gemini-ai.php';
        
        // Nếu đang ở subdirectory (như admin/, events/), cần lùi lại
        if (currentPath.includes('/admin/') || currentPath.includes('/events/')) {
            apiUrl = '../src/controllers/gemini-ai.php';
        }
        
        console.log('API URL (relative):', apiUrl);
        
        // Tạo đường dẫn tuyệt đối để fallback
        // Từ /event/my-php-project/index.php -> /event/my-php-project/src/controllers/gemini-ai.php
        const basePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
        const absoluteUrl = window.location.origin + basePath + '/src/controllers/gemini-ai.php';
        console.log('API URL (absolute fallback):', absoluteUrl);
        
        const requestBody = new URLSearchParams({
            action: 'chat',
            message: message,
            history: JSON.stringify(conversationHistory.map(msg => ({
                role: msg.role,
                content: msg.content
            })))
        });
        
        console.log('Request body:', requestBody.toString());
        
        // Thử gọi API với đường dẫn tương đối trước
        let response;
        let usedUrl = apiUrl;
        let fetchError = null;
        
        try {
            console.log('Attempting fetch with relative URL:', apiUrl);
            response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: requestBody
            });
            console.log('Fetch successful, status:', response.status);
        } catch (error) {
            console.error('Fetch error with relative URL:', error);
            fetchError = error;
            response = null;
        }
        
        // Nếu thất bại hoặc 404, thử đường dẫn tuyệt đối
        if (!response || response.status === 404) {
            console.warn('Relative URL failed or returned 404, trying absolute URL:', absoluteUrl);
            usedUrl = absoluteUrl;
            try {
                response = await fetch(absoluteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: requestBody
                });
                console.log('Absolute URL fetch successful, status:', response.status);
            } catch (absError) {
                console.error('Absolute URL also failed:', absError);
                hideLoadingIndicator();
                addChatMessage('Xin lỗi, không thể kết nối với server. Vui lòng kiểm tra đường dẫn API và thử lại.', 'assistant');
                return;
            }
        }
        
        console.log('Final used API URL:', usedUrl);
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Kiểm tra response status
        if (!response || !response.ok) {
            const status = response ? response.status : 'No response';
            let errorText = 'Unknown error';
            try {
                errorText = response ? await response.text() : 'No response received';
            } catch (e) {
                errorText = 'Cannot read error response';
            }
            console.error('API Error - Status:', status);
            console.error('API Error - Response:', errorText);
            hideLoadingIndicator();
            
            // Hiển thị thông báo lỗi chi tiết hơn
            let errorMessage = 'Xin lỗi, có lỗi xảy ra từ server';
            if (status === 404) {
                errorMessage += ' (Không tìm thấy file API). Vui lòng kiểm tra đường dẫn: ' + usedUrl;
            } else if (status === 500) {
                errorMessage += ' (Lỗi server). Vui lòng thử lại sau.';
            } else {
                errorMessage += ' (HTTP ' + status + '). Vui lòng thử lại sau.';
            }
            addChatMessage(errorMessage, 'assistant');
            return;
        }
        
        // Parse JSON response
        let data;
        try {
            const responseText = await response.text();
            console.log('Response text:', responseText);
            data = JSON.parse(responseText);
            console.log('Parsed data:', data);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            hideLoadingIndicator();
            addChatMessage('Xin lỗi, không thể đọc phản hồi từ server. Vui lòng thử lại sau.', 'assistant');
            return;
        }
        
        hideLoadingIndicator();
        
        if (data.success) {
            console.log('Success! Message:', data.message);
            addChatMessage(data.message, 'assistant');
            
            // Hiển thị gợi ý nếu có
            if (data.suggestions && data.suggestions.length > 0) {
                console.log('Showing suggestions:', data.suggestions);
                showSuggestions(data.suggestions);
            } else {
                console.log('Showing quick suggestions');
                showQuickSuggestions();
            }
        } else {
            console.error('API returned error:', data.error);
            hideLoadingIndicator();
            const errorMsg = data.error || 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            addChatMessage('Xin lỗi, ' + errorMsg, 'assistant');
        }
    } catch (error) {
        console.error('Error calling Gemini AI:', error);
        console.error('Error details:', {
            name: error.name,
            message: error.message,
            stack: error.stack
        });
        hideLoadingIndicator();
        addChatMessage('Xin lỗi, không thể kết nối với server. Vui lòng kiểm tra kết nối mạng và thử lại sau.', 'assistant');
    }
}

// Thêm tin nhắn vào chat
function addChatMessage(text, sender) {
    // Thêm vào lịch sử
    conversationHistory.push({
        role: sender === 'user' ? 'user' : 'assistant',
        content: text,
        timestamp: new Date().toISOString()
    });
    
    // Chỉ giữ lại 50 tin nhắn cuối cùng
    if (conversationHistory.length > 50) {
        conversationHistory = conversationHistory.slice(-50);
    }
    
    saveConversationHistory();
    
    // Thêm vào DOM
    addChatMessageToDOM(text, sender);
}

// Thêm tin nhắn vào DOM
function addChatMessageToDOM(text, sender) {
    const chatMessages = document.getElementById('chatMessages');
    if (!chatMessages) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}`;
    
    // Đối với tin nhắn từ trợ lý, cho phép HTML (bao gồm liên kết)
    // Đối với tin nhắn từ người dùng, escape HTML để bảo mật
    const content = sender === 'assistant' ? text : escapeHtml(text);
    
    messageDiv.innerHTML = `
        <div class="message-content">
            <div>${formatMessage(content)}</div>
        </div>
    `;
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Định dạng tin nhắn (chuyển đổi markdown sang HTML)
function formatMessage(text) {
    // Chuyển đổi **bold** thành <strong>
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // Chuyển đổi *italic* thành <em>
    text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
    
    // Chuyển đổi ngắt dòng
    text = text.replace(/\n/g, '<br>');
    
    // Chuyển đổi liên kết
    text = text.replace(/<a href='([^']+)'[^>]*>([^<]+)<\/a>/g, '<a href="$1" target="_blank" rel="noopener">$2</a>');
    
    return text;
}

// Hiển thị chỉ báo đang tải
function showLoadingIndicator() {
    const chatMessages = document.getElementById('chatMessages');
    if (!chatMessages) return;
    
    // Xóa loading indicator cũ nếu có
    const oldLoading = document.getElementById('loadingIndicator');
    if (oldLoading) {
        oldLoading.remove();
    }
    
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loadingIndicator';
    loadingDiv.className = 'message assistant';
    loadingDiv.innerHTML = `
        <div class="message-content">
            <div class="typing-indicator">
                <span>.</span><span>.</span><span>.</span>
            </div>
        </div>
    `;
    
    chatMessages.appendChild(loadingDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    isLoading = true;
}

// Ẩn chỉ báo đang tải
function hideLoadingIndicator() {
    const loadingDiv = document.getElementById('loadingIndicator');
    if (loadingDiv) {
        loadingDiv.remove();
    }
    isLoading = false;
}

// Hiển thị gợi ý nhanh
function showQuickSuggestions() {
    const quickSuggestions = document.getElementById('quickSuggestions');
    if (!quickSuggestions) return;
    
    quickSuggestions.innerHTML = `
        <div class="suggestion-item" onclick="sendQuickMessage('Tôi muốn đăng ký sự kiện')">
            <i class="fas fa-calendar-plus"></i>
            <span>Đăng ký sự kiện</span>
        </div>
        <div class="suggestion-item" onclick="sendQuickMessage('Tôi muốn xem giá dịch vụ')">
            <i class="fas fa-dollar-sign"></i>
            <span>Xem giá</span>
        </div>
        <div class="suggestion-item" onclick="sendQuickMessage('Tôi muốn xem địa điểm')">
            <i class="fas fa-map-marker-alt"></i>
            <span>Xem địa điểm</span>
        </div>
        <div class="suggestion-item" onclick="sendQuickMessage('Tôi muốn xem thiết bị')">
            <i class="fas fa-tools"></i>
            <span>Xem thiết bị</span>
        </div>
        <div class="suggestion-item" onclick="sendQuickMessage('Tôi cần tư vấn')">
            <i class="fas fa-question-circle"></i>
            <span>Tư vấn</span>
        </div>
        <div class="suggestion-item" onclick="sendQuickMessage('Tôi muốn kiểm tra trạng thái sự kiện')">
            <i class="fas fa-search"></i>
            <span>Trạng thái</span>
        </div>
    `;
    
    quickSuggestions.style.display = 'grid';
}

// Hiển thị gợi ý từ AI
function showSuggestions(suggestions) {
    const quickSuggestions = document.getElementById('quickSuggestions');
    if (!quickSuggestions || !suggestions || suggestions.length === 0) {
        showQuickSuggestions();
        return;
    }
    
    let html = '';
    suggestions.forEach(suggestion => {
        const action = suggestion.action || 'chat';
        const text = suggestion.text || suggestion;
        html += `
            <div class="suggestion-item" onclick="handleSuggestion('${action}', '${escapeHtml(text)}')">
                <i class="fas fa-lightbulb"></i>
                <span>${text}</span>
            </div>
        `;
    });
    
    quickSuggestions.innerHTML = html;
    quickSuggestions.style.display = 'grid';
}

// Xử lý khi click vào gợi ý
function handleSuggestion(action, text) {
    if (action === 'register') {
        window.location.href = 'events/register.php';
    } else if (action === 'pricing') {
        window.location.href = 'services.php';
    } else if (action === 'locations') {
        window.location.href = 'services.php#locations';
    } else if (action === 'equipment') {
        window.location.href = 'services.php#equipment';
    } else {
        sendQuickMessage(text);
    }
}

// Gửi tin nhắn nhanh
function sendQuickMessage(message) {
    const input = document.getElementById('chatInput');
    if (input) {
        input.value = message;
        sendChatMessage();
    }
    
    // Ẩn gợi ý nhanh
    const quickSuggestions = document.getElementById('quickSuggestions');
    if (quickSuggestions) {
        quickSuggestions.style.display = 'none';
    }
}

// Escape HTML để bảo mật
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

// Theo dõi điều hướng để phân biệt refresh và đóng tab
let isNavigating = false;
let isPageRefreshing = false;

// Lắng nghe sự kiện điều hướng (click link, submit form)
document.addEventListener('click', function(e) {
    const target = e.target.closest('a, button[type="submit"]');
    if (target && target.tagName === 'A' && target.href) {
        // Người dùng click vào link - đang điều hướng sang trang khác
        isNavigating = true;
    } else if (target && target.type === 'submit') {
        // Người dùng submit form - đang điều hướng
        isNavigating = true;
    }
}, true);

// Lắng nghe sự kiện submit form
document.addEventListener('submit', function(e) {
    isNavigating = true;
}, true);

// Theo dõi refresh (F5, Ctrl+R, v.v.)
window.addEventListener('beforeunload', function(e) {
    // Kiểm tra xem có phải là refresh (F5, Ctrl+R) không
    // Không thể phát hiện hoàn hảo, nhưng sẽ dùng kết hợp các sự kiện
    if (!isNavigating) {
        // Có thể là refresh hoặc đóng - sẽ kiểm tra trong pagehide
        isPageRefreshing = true;
    }
});

// Xử lý sự kiện pagehide (đáng tin cậy hơn để phát hiện đóng vs refresh)
window.addEventListener('pagehide', function(e) {
    // Nếu persisted là true, trang đang được cache (refresh/điều hướng)
    // Nếu persisted là false, trang đang được đóng
    if (e.persisted === true || isNavigating) {
        // Trang đang được refresh hoặc điều hướng sang trang khác - giữ lịch sử
        console.log('Lịch sử chat được giữ lại: Trang được refresh/điều hướng');
    } else if (e.persisted === false && !isNavigating) {
        // Trang đang được đóng (không phải refresh) - xóa lịch sử chat
        localStorage.removeItem('geminiChatHistory');
        console.log('Lịch sử chat đã được xóa: Trang được đóng');
    }
});

// Đặt lại cờ sau khi điều hướng hoàn tất
window.addEventListener('pageshow', function(e) {
    // Đặt lại cờ điều hướng khi trang được hiển thị
    isNavigating = false;
    isPageRefreshing = false;
});

// Khởi tạo khi trang được tải
document.addEventListener('DOMContentLoaded', function() {
    loadConversationHistory();
    
    // Thêm listener cho phím Enter
    const chatInput = document.getElementById('chatInput');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendChatMessage();
            }
        });
    }
    
    // Tự động mở chat box sau 5 giây nếu người dùng chưa mở
    let autoOpenTimer = null;
    let userHasInteracted = false;
    
    // Theo dõi các tương tác của người dùng
    ['scroll', 'click', 'mousemove', 'keydown', 'touchstart'].forEach(function(eventType) {
        document.addEventListener(eventType, function() {
            userHasInteracted = true;
        }, { once: true, passive: true });
    });
    
    // Tự động mở sau 5 giây nếu người dùng chưa tương tác và chưa mở chat
    autoOpenTimer = setTimeout(function() {
        if (!isChatOpen && !userHasInteracted) {
            console.log('Tự động mở chat box sau 5 giây');
            openChatWidget();
            
            // Thêm animation pulse cho nút chat để thu hút sự chú ý
            const chatBtn = document.getElementById('floatingChatBtn');
            if (chatBtn) {
                chatBtn.classList.add('pulse');
                setTimeout(function() {
                    chatBtn.classList.remove('pulse');
                }, 2000);
            }
        }
    }, 5000);
    
    // Hủy auto-open nếu người dùng đã mở chat thủ công
    // Lưu reference đến hàm openChatWidget gốc
    const originalOpenChatWidget = openChatWidget;
    
    // Override hàm openChatWidget để hủy timer khi mở thủ công
    window.openChatWidget = function() {
        if (autoOpenTimer) {
            clearTimeout(autoOpenTimer);
            autoOpenTimer = null;
            console.log('Đã hủy auto-open vì người dùng mở chat thủ công');
        }
        originalOpenChatWidget();
    };
});

