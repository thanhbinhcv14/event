# WebRTC Migration Guide - Chuyển từ Stringee sang WebRTC

## ✅ Đã hoàn thành

1. **Tạo `webrtc-helper.js`** - File helper xử lý WebRTC peer-to-peer connections
   - `initiateCall()` - Caller tạo offer
   - `handleIncomingOffer()` - Receiver xử lý offer và tạo answer
   - `handleIncomingAnswer()` - Caller xử lý answer
   - `handleICECandidate()` - Xử lý ICE candidates (NAT traversal)
   - `cleanup()` - Dọn dẹp resources

2. **Cập nhật Socket.IO server** (`socket-server.js`)
   - Thêm events: `call-offer`, `call-answer`, `ice-candidate`
   - Forward signaling messages giữa caller và receiver

3. **Cập nhật `chat.php`** (một phần)
   - Xóa Stringee SDK loading code
   - Thêm WebRTC helper script
   - Cập nhật `initiateCall()` function để dùng WebRTC

## 🔄 Cần hoàn thành

### 1. Cập nhật `chat.php` - Các hàm còn lại

#### a) Xóa `setupStringeeEventHandlers()` và thay bằng WebRTC handlers
```javascript
// XÓA:
function setupStringeeEventHandlers() { ... }

// THAY BẰNG:
function setupWebRTCEventHandlers() {
    if (!socket || !window.WebRTCHelper) return;
    
    // Handle incoming offer (receiver)
    socket.on('call-offer', async (data) => {
        if (data.receiver_id == currentUserId) {
            await window.WebRTCHelper.handleIncomingOffer(data);
        }
    });
    
    // Handle incoming answer (caller)
    socket.on('call-answer', async (data) => {
        if (currentCall && data.call_id == currentCall.id) {
            await window.WebRTCHelper.handleIncomingAnswer(data);
        }
    });
    
    // Handle ICE candidates
    socket.on('ice-candidate', async (data) => {
        if (currentCall && data.call_id == currentCall.id) {
            await window.WebRTCHelper.handleICECandidate(data);
        }
    });
}
```

#### b) Cập nhật `acceptCall()` function
```javascript
async function acceptCall() {
    if (!currentCall) return;
    
    try {
        // Accept call trên server
        const response = await $.post(getApiPath('src/controllers/call-controller.php?action=accept_call'), {
            call_id: currentCall.id
        });
        
        if (response.success) {
            // Emit call_accepted event
            socket.emit('call_accepted', {
                call_id: currentCall.id,
                caller_id: currentCall.caller_id,
                receiver_id: currentUserId
            });
            
            // WebRTC sẽ tự động handle offer/answer qua socket events
            // Không cần gọi thêm function nào
        }
    } catch (error) {
        console.error('Error accepting call:', error);
    }
}
```

#### c) Cập nhật `cleanupCall()` function
```javascript
function cleanupCall() {
    $('#callModal').removeClass('show').css('display', 'none');
    $('#videoCallContainer').removeClass('show').css({
        'display': 'none',
        'visibility': 'hidden',
        'opacity': '0'
    });
    
    if (window.WebRTCHelper) {
        window.WebRTCHelper.cleanup();
    }
    
    currentCall = null;
}
```

#### d) Cập nhật `call_initiated` socket event handler
```javascript
socket.on('call_initiated', data => {
    if (data.receiver_id == currentUserId) {
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
        
        showCallModal('incoming', callerName, data.call_type);
        
        // KHÔNG cần init WebRTC ở đây
        // WebRTC sẽ được init khi receiver accept call
    }
});
```

### 2. Cập nhật `admin/chat.php` - Tương tự như `chat.php`

### 3. Xóa Stringee-related files
- `assets/js/stringee-helper.js`
- `src/controllers/stringee-controller.php`
- `src/controllers/stringee-callback.php`
- `config/stringee.php` (hoặc giữ lại nhưng không dùng)

### 4. Cập nhật `call-controller.php`
- Xóa tất cả Stringee-related code
- Giữ lại call session management (initiate, accept, reject, end)

## 📋 WebRTC Flow

### Caller (Người gọi):
1. User clicks "Call" → `initiateCall()`
2. Create call session trên server
3. Emit `call_initiated` event qua Socket.IO
4. `WebRTCHelper.initiateCall()`:
   - Get user media (audio/video)
   - Create RTCPeerConnection
   - Create offer
   - Emit `call-offer` qua Socket.IO

### Receiver (Người nhận):
1. Nhận `call_initiated` event → Hiển thị modal
2. User clicks "Accept" → `acceptCall()`
3. Emit `call_accepted` event
4. Nhận `call-offer` event → `handleIncomingOffer()`:
   - Get user media
   - Create RTCPeerConnection
   - Set remote description (offer)
   - Create answer
   - Emit `call-answer` qua Socket.IO

### Caller nhận answer:
1. Nhận `call-answer` event → `handleIncomingAnswer()`
2. Set remote description (answer)
3. Connection established!

### ICE Candidates:
- Cả 2 bên tự động emit `ice-candidate` events
- Forward qua Socket.IO
- Add vào peer connection

## ⚠️ Lưu ý

1. **STUN/TURN servers**: Hiện tại dùng Google STUN servers (free). Nếu có vấn đề NAT traversal, cần setup TURN server.

2. **Browser compatibility**: WebRTC được hỗ trợ bởi tất cả modern browsers (Chrome, Firefox, Safari, Edge).

3. **HTTPS required**: WebRTC yêu cầu HTTPS (hoặc localhost) để access camera/microphone.

4. **Permissions**: User phải cho phép access camera/microphone.

## 🧪 Testing

1. Test voice call giữa 2 users
2. Test video call giữa 2 users
3. Test call rejection
4. Test call timeout
5. Test call end
6. Test với NAT/firewall (cần TURN server nếu có vấn đề)

