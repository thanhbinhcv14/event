# 🔍 Hướng dẫn Kiểm tra và Debug WebRTC Video Call

## 📋 Checklist Kiểm tra Video Call

### 1. Kiểm tra Console Logs (F12)

Mở **Developer Tools** (F12) → Tab **Console** và kiểm tra các log sau:

#### ✅ Khi khởi tạo Peer Connection:
```
✅ Peer connection created
✅ Added local track: video [tên camera]
✅ Added local track: audio [tên microphone]
```

#### ✅ Khi tạo Offer (Caller):
```
📞 Admin Caller: Creating offer...
✅ Offer created: {type: "offer", sdp: "..."}
✅ Local description set
✅ Offer sent via socket
```

#### ✅ Khi nhận Offer (Receiver):
```
📞 Admin received WebRTC offer: {...}
✅ Remote description (offer) set
✅ Answer created: {type: "answer", sdp: "..."}
✅ Answer sent via socket
```

#### ✅ Khi nhận Answer (Caller):
```
📞 Admin received WebRTC answer: {...}
✅ Remote description (answer) set
```

#### ✅ Khi nhận ICE Candidates:
```
📞 ICE candidate generated: {...}
✅ ICE candidate sent via socket
📞 Admin received ICE candidate: {...}
✅ ICE candidate added
```

#### ✅ Khi nhận Remote Stream:
```
📞 ontrack event fired: {...}
📞 Remote video tracks: [MediaStreamTrack]
✅ Remote stream có 1 video track(s)
📞 Remote audio tracks: [MediaStreamTrack]
✅ Remote stream có 1 audio track(s)
✅ Remote video assigned to video element
✅ Remote video playing successfully
📹 Remote video element state: {
    paused: false,
    videoWidth: 640,
    videoHeight: 480,
    ...
}
```

#### ✅ Khi ICE Connection thành công:
```
✅ ICE connection established!
✅ Peer connection established successfully!
```

---

## 🔴 Các Lỗi Thường Gặp và Cách Sửa

### ❌ Lỗi 1: "No video tracks in remote stream"
**Nguyên nhân:**
- Bên kia chưa gửi video track
- Offer/Answer không đúng
- addTrack chưa được gọi trước khi tạo offer

**Cách kiểm tra:**
```javascript
// Trong console, gõ:
peerConnection.getReceivers().forEach(receiver => {
    console.log('Receiver track:', receiver.track);
});
```

**Cách sửa:**
- Đảm bảo bên kia đã gọi `getUserMedia({ video: true })`
- Đảm bảo `addTrack()` được gọi TRƯỚC khi tạo offer

---

### ❌ Lỗi 2: "Remote video element not found"
**Nguyên nhân:**
- Element `#remoteVideo` không tồn tại trong DOM
- Video container bị ẩn

**Cách kiểm tra:**
```javascript
// Trong console, gõ:
const remoteVideo = document.getElementById('remoteVideo');
console.log('Remote video element:', remoteVideo);
console.log('Video container:', document.getElementById('videoCallContainer'));
```

**Cách sửa:**
- Kiểm tra HTML có element `<video id="remoteVideo">`
- Đảm bảo video container được hiển thị: `$('#videoCallContainer').show()`

---

### ❌ Lỗi 3: "Error playing remote video: NotAllowedError"
**Nguyên nhân:**
- Browser autoplay policy chặn video
- User chưa tương tác với trang

**Cách kiểm tra:**
```javascript
// Trong console, gõ:
const remoteVideo = document.getElementById('remoteVideo');
remoteVideo.play().then(() => {
    console.log('✅ Video can play');
}).catch(err => {
    console.error('❌ Cannot play:', err);
});
```

**Cách sửa:**
- Click vào video container hoặc bất kỳ đâu trên trang
- Code đã tự động xử lý: thêm event listeners để play khi user click

---

### ❌ Lỗi 4: "ICE connection failed"
**Nguyên nhân:**
- NAT/Firewall chặn P2P connection
- TURN server không hoạt động
- Mạng không ổn định

**Cách kiểm tra:**
```javascript
// Trong console, gõ:
console.log('ICE connection state:', peerConnection.iceConnectionState);
console.log('Connection state:', peerConnection.connectionState);
```

**Cách sửa:**
- Kiểm tra TURN server có hoạt động không
- Thử test trên mạng khác (4G, WiFi khác)
- Kiểm tra firewall có chặn UDP không

---

### ❌ Lỗi 5: "Remote video playing but no image"
**Nguyên nhân:**
- Video element bị ẩn hoặc có CSS che
- Video container không hiển thị
- Video element không có kích thước

**Cách kiểm tra:**
```javascript
// Trong console, gõ:
const remoteVideo = document.getElementById('remoteVideo');
console.log('Video dimensions:', {
    width: remoteVideo.videoWidth,
    height: remoteVideo.videoHeight,
    clientWidth: remoteVideo.clientWidth,
    clientHeight: remoteVideo.clientHeight,
    offsetWidth: remoteVideo.offsetWidth,
    offsetHeight: remoteVideo.offsetHeight
});
console.log('Video container:', {
    display: $('#videoCallContainer').css('display'),
    visibility: $('#videoCallContainer').css('visibility'),
    opacity: $('#videoCallContainer').css('opacity'),
    zIndex: $('#videoCallContainer').css('z-index')
});
```

**Cách sửa:**
- Đảm bảo video container có `display: block`
- Kiểm tra CSS không có `display: none` hoặc `visibility: hidden`
- Đảm bảo video element có kích thước (width/height > 0)

---

## 🧪 Các Bước Test Thủ Công

### Test 1: Kiểm tra Local Video
1. Mở trang admin chat
2. Chọn một conversation
3. Click nút "Gọi video" (icon camera đỏ)
4. **Kiểm tra:** Local video (góc trên bên phải) có hiển thị không?
   - ✅ Có → Camera hoạt động
   - ❌ Không → Kiểm tra quyền truy cập camera

### Test 2: Kiểm tra Remote Video
1. Mở 2 tab trình duyệt:
   - Tab 1: Admin chat (admin/chat.php)
   - Tab 2: Customer chat (chat.php)
2. Admin gọi video cho customer
3. Customer accept call
4. **Kiểm tra trong Console:**
   - Có log "📞 ontrack event fired" không?
   - Có log "✅ Remote stream có 1 video track(s)" không?
   - Có log "✅ Remote video playing successfully" không?
5. **Kiểm tra trên màn hình:**
   - Remote video (màn hình lớn) có hiển thị hình ảnh không?
   - Video có kích thước (width/height > 0) không?

### Test 3: Kiểm tra ICE Connection
1. Mở Console (F12)
2. Gọi video
3. **Kiểm tra logs:**
   - Có log "📞 ICE candidate generated" không?
   - Có log "✅ ICE candidate sent via socket" không?
   - Có log "✅ ICE candidate added" không?
   - Có log "✅ ICE connection established!" không?
4. **Nếu không có:**
   - Kiểm tra WebSocket connection
   - Kiểm tra TURN server
   - Kiểm tra firewall

### Test 4: Kiểm tra Video Tracks
1. Mở Console (F12)
2. Gọi video
3. **Trong console, gõ:**
```javascript
// Kiểm tra local tracks
if (localStream) {
    console.log('Local video tracks:', localStream.getVideoTracks());
    console.log('Local audio tracks:', localStream.getAudioTracks());
}

// Kiểm tra remote tracks
if (remoteStream) {
    console.log('Remote video tracks:', remoteStream.getVideoTracks());
    console.log('Remote audio tracks:', remoteStream.getAudioTracks());
}

// Kiểm tra peer connection receivers
if (peerConnection) {
    peerConnection.getReceivers().forEach((receiver, index) => {
        console.log(`Receiver ${index}:`, {
            track: receiver.track,
            kind: receiver.track.kind,
            enabled: receiver.track.enabled,
            readyState: receiver.track.readyState
        });
    });
}
```

---

## 🔧 Debug Commands (Copy vào Console)

### Command 1: Kiểm tra trạng thái Peer Connection
```javascript
console.log('=== PEER CONNECTION STATE ===');
console.log('Connection state:', peerConnection?.connectionState);
console.log('ICE connection state:', peerConnection?.iceConnectionState);
console.log('ICE gathering state:', peerConnection?.iceGatheringState);
console.log('Signaling state:', peerConnection?.signalingState);
console.log('Local description:', peerConnection?.localDescription);
console.log('Remote description:', peerConnection?.remoteDescription);
```

### Command 2: Kiểm tra Streams
```javascript
console.log('=== STREAMS ===');
console.log('Local stream:', localStream);
console.log('Local video tracks:', localStream?.getVideoTracks());
console.log('Local audio tracks:', localStream?.getAudioTracks());
console.log('Remote stream:', remoteStream);
console.log('Remote video tracks:', remoteStream?.getVideoTracks());
console.log('Remote audio tracks:', remoteStream?.getAudioTracks());
```

### Command 3: Kiểm tra Video Elements
```javascript
console.log('=== VIDEO ELEMENTS ===');
const remoteVideo = document.getElementById('remoteVideo');
const localVideo = document.getElementById('localVideo');
console.log('Remote video:', {
    element: remoteVideo,
    srcObject: remoteVideo?.srcObject,
    paused: remoteVideo?.paused,
    videoWidth: remoteVideo?.videoWidth,
    videoHeight: remoteVideo?.videoHeight,
    clientWidth: remoteVideo?.clientWidth,
    clientHeight: remoteVideo?.clientHeight
});
console.log('Local video:', {
    element: localVideo,
    srcObject: localVideo?.srcObject,
    paused: localVideo?.paused
});
```

### Command 4: Kiểm tra Video Container
```javascript
console.log('=== VIDEO CONTAINER ===');
const container = document.getElementById('videoCallContainer');
console.log('Container:', {
    element: container,
    display: container?.style.display || getComputedStyle(container).display,
    visibility: container?.style.visibility || getComputedStyle(container).visibility,
    opacity: container?.style.opacity || getComputedStyle(container).opacity,
    zIndex: container?.style.zIndex || getComputedStyle(container).zIndex,
    width: container?.offsetWidth,
    height: container?.offsetHeight
});
```

### Command 5: Force Play Video (Nếu bị autoplay block)
```javascript
const remoteVideo = document.getElementById('remoteVideo');
if (remoteVideo && remoteVideo.srcObject) {
    remoteVideo.play().then(() => {
        console.log('✅ Video playing');
    }).catch(err => {
        console.error('❌ Cannot play:', err);
    });
}
```

### Command 6: Force Show Video Container
```javascript
const container = document.getElementById('videoCallContainer');
if (container) {
    container.style.display = 'block';
    container.style.visibility = 'visible';
    container.style.opacity = '1';
    container.style.zIndex = '10000';
    console.log('✅ Video container forced to show');
}
```

---

## 📊 Flow Diagram - Video Call Process

```
1. User clicks "Video Call" button
   ↓
2. startVideoCall() called
   ↓
3. getUserMedia({ video: true, audio: true })
   ↓
4. initializePeerConnection()
   ↓
5. addTrack() - Add local tracks to peer connection
   ↓
6. createOffer() - Create WebRTC offer
   ↓
7. setLocalDescription(offer)
   ↓
8. Send offer via WebSocket (webrtc_offer event)
   ↓
9. Receiver receives offer
   ↓
10. setRemoteDescription(offer)
   ↓
11. createAnswer()
   ↓
12. setLocalDescription(answer)
   ↓
13. Send answer via WebSocket (webrtc_answer event)
   ↓
14. Caller receives answer
   ↓
15. setRemoteDescription(answer)
   ↓
16. ICE candidates exchange (via ice_candidate events)
   ↓
17. ontrack event fired when remote stream received
   ↓
18. remoteVideo.srcObject = remoteStream
   ↓
19. remoteVideo.play()
   ↓
20. ✅ Video displayed!
```

---

## 🎯 Quick Test Checklist

- [ ] Local video hiển thị (góc trên bên phải)
- [ ] Console có log "✅ Remote video playing successfully"
- [ ] Console có log "✅ Remote stream có 1 video track(s)"
- [ ] Console có log "✅ ICE connection established!"
- [ ] Video container có `display: block`
- [ ] Remote video element có `videoWidth > 0` và `videoHeight > 0`
- [ ] Remote video element có `paused: false`
- [ ] Không có lỗi trong console

---

## 💡 Tips

1. **Luôn mở Console (F12)** khi test để xem logs
2. **Test trên 2 trình duyệt khác nhau** (Chrome + Firefox) hoặc 2 tab khác nhau
3. **Kiểm tra quyền truy cập camera/microphone** trong browser settings
4. **Nếu không thấy video:**
   - Click vào video container để trigger autoplay
   - Kiểm tra CSS có che video không
   - Kiểm tra video element có kích thước không
5. **Nếu ICE connection failed:**
   - Thử test trên mạng khác (4G, WiFi khác)
   - Kiểm tra TURN server có hoạt động không
   - Kiểm tra firewall settings

---

## 🆘 Nếu Vẫn Không Hoạt Động

1. **Copy toàn bộ console logs** và gửi cho developer
2. **Chụp màn hình** video container và console
3. **Chạy các debug commands** ở trên và gửi kết quả
4. **Kiểm tra network tab** xem WebSocket có kết nối không
5. **Kiểm tra browser compatibility** (Chrome, Firefox, Safari)

