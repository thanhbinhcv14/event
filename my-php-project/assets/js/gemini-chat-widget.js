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
            
            // ✅ Lưu trạng thái mở vào localStorage
            localStorage.setItem('chatWidgetOpen', 'true');
            
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
        
        // ✅ Lưu trạng thái đóng vào localStorage
        localStorage.setItem('chatWidgetOpen', 'false');
        
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
        return;
    }
    
    // Thêm tin nhắn của người dùng
    addChatMessage(message, 'user');
    input.value = '';
    
    // Hiển thị chỉ báo đang tải
    showLoadingIndicator();
    
    try {
        // Xác định đường dẫn API - sử dụng đường dẫn tương đối đơn giản
        const currentPath = window.location.pathname;
        
        // Sử dụng đường dẫn tương đối đơn giản - giống như events.php trong index.php
        let apiUrl = 'src/controllers/gemini-ai.php';
        
        // Nếu đang ở subdirectory (như admin/, events/), cần lùi lại
        if (currentPath.includes('/admin/') || currentPath.includes('/events/')) {
            apiUrl = '../src/controllers/gemini-ai.php';
        }
        
        // Tạo đường dẫn tuyệt đối để fallback
        const basePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
        const absoluteUrl = window.location.origin + basePath + '/src/controllers/gemini-ai.php';
        
        const requestBody = new URLSearchParams({
            action: 'chat',
            message: message,
            history: JSON.stringify(conversationHistory.map(msg => ({
                role: msg.role,
                content: msg.content
            })))
        });
        
        // Thử gọi API với đường dẫn tương đối trước
        let response;
        let usedUrl = apiUrl;
        
        try {
            response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: requestBody
            });
        } catch (error) {
            response = null;
        }
        
        // Nếu thất bại hoặc 404, thử đường dẫn tuyệt đối
        if (!response || response.status === 404) {
            usedUrl = absoluteUrl;
            try {
                response = await fetch(absoluteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: requestBody
                });
            } catch (absError) {
                hideLoadingIndicator();
                addChatMessage('Xin lỗi, không thể kết nối với server. Vui lòng kiểm tra đường dẫn API và thử lại.', 'assistant');
                return;
            }
        }
        
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
            data = JSON.parse(responseText);
        } catch (parseError) {
            hideLoadingIndicator();
            addChatMessage('Xin lỗi, không thể đọc phản hồi từ server. Vui lòng thử lại sau.', 'assistant');
            return;
        }
        
        hideLoadingIndicator();
        
        if (data.success) {
            addChatMessage(data.message, 'assistant');
        } else {
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
                <span></span><span></span><span></span>
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
// Đã xóa quick suggestions - không còn sử dụng

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
    } else if (e.persisted === false && !isNavigating) {
        // Trang đang được đóng (không phải refresh) - xóa lịch sử chat
        localStorage.removeItem('geminiChatHistory');
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
    
    // ✅ Khôi phục trạng thái mở/đóng của chat widget từ localStorage
    const savedChatState = localStorage.getItem('chatWidgetOpen');
    if (savedChatState === 'true') {
        // Đợi một chút để đảm bảo DOM đã sẵn sàng
        setTimeout(function() {
            const chatWidget = document.getElementById('chatWidget');
            if (chatWidget) {
                chatWidget.classList.add('show');
                isChatOpen = true;
                
                const chatBtn = document.getElementById('floatingChatBtn') || document.querySelector('.floating-chat-btn');
                if (chatBtn) {
                    chatBtn.innerHTML = '<i class="fas fa-times"></i>';
                    chatBtn.title = 'Đóng chat';
                }
                
                // Khôi phục lịch sử nếu có
                if (conversationHistory.length > 0) {
                    restoreConversation();
                } else {
                    showWelcomeMessage();
                }
            }
        }, 100);
    }
    
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
    
    // Tự động mở chat box sau 5 giây nếu người dùng chưa mở và chưa có trạng thái lưu
    let autoOpenTimer = null;
    let userHasInteracted = false;
    
    // Theo dõi các tương tác của người dùng
    ['scroll', 'click', 'mousemove', 'keydown', 'touchstart'].forEach(function(eventType) {
        document.addEventListener(eventType, function() {
            userHasInteracted = true;
        }, { once: true, passive: true });
    });
    
    // Tự động mở sau 5 giây nếu người dùng chưa tương tác, chưa mở chat và không có trạng thái lưu
    if (savedChatState !== 'true') {
        autoOpenTimer = setTimeout(function() {
            if (!isChatOpen && !userHasInteracted) {
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
    }
    
    // Hủy auto-open nếu người dùng đã mở chat thủ công
    // Lưu reference đến hàm openChatWidget gốc
    const originalOpenChatWidget = openChatWidget;
    
    // Override hàm openChatWidget để hủy timer khi mở thủ công
    window.openChatWidget = function() {
        if (autoOpenTimer) {
            clearTimeout(autoOpenTimer);
            autoOpenTimer = null;
        }
        originalOpenChatWidget();
    };
});

