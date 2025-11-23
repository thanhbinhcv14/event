# ✅ Checklist Deploy WebRTC Call System

## 🎯 Mục tiêu
Hệ thống WebRTC call giữa Admin và Customer đã sẵn sàng deploy.

## ✅ Đã hoàn thành

### 1. WebRTC Implementation
- [x] Tạo `webrtc-helper.js` với đầy đủ functions
- [x] Cập nhật Socket.IO server với WebRTC signaling events
- [x] Cập nhật `chat.php` (customer) - xóa Stringee, thêm WebRTC
- [x] Cập nhật `admin/chat.php` (admin) - xóa Stringee, thêm WebRTC

### 2. Stringee Removal
- [x] Xóa Stringee SDK loading code trong `chat.php`
- [x] Xóa Stringee SDK loading code trong `admin/chat.php`
- [x] Thay thế tất cả Stringee references bằng WebRTC
- [x] Cập nhật call functions (initiate, accept, reject, end)

## 🔄 Cần hoàn thành trước khi deploy

### 1. Xóa Stringee Files
```bash
# Xóa các file Stringee không còn dùng
rm assets/js/stringee-helper.js
rm src/controllers/stringee-controller.php
rm src/controllers/stringee-callback.php
rm config/stringee.php
rm test-stringee-callback.php
rm test-stringee-token.php
```

### 2. Kiểm tra WebRTC Helper được load
- [ ] `chat.php` có load `webrtc-helper.js`
- [ ] `admin/chat.php` có load `webrtc-helper.js`
- [ ] WebRTC Helper được init khi socket connect

### 3. Kiểm tra Socket.IO Events
- [ ] `call-offer` event được emit và nhận đúng
- [ ] `call-answer` event được emit và nhận đúng
- [ ] `ice-candidate` event được emit và nhận đúng
- [ ] `call_initiated`, `call_accepted`, `call_rejected`, `call_ended` hoạt động

### 4. Test Call Flow

#### Test Case 1: Customer gọi Admin (Voice)
1. [ ] Customer mở `chat.php`
2. [ ] Customer chọn conversation với Admin
3. [ ] Customer click "Voice Call"
4. [ ] Admin nhận được incoming call modal
5. [ ] Admin click "Accept"
6. [ ] Call kết nối thành công
7. [ ] Cả 2 bên nghe được nhau
8. [ ] End call hoạt động

#### Test Case 2: Admin gọi Customer (Voice)
1. [ ] Admin mở `admin/chat.php`
2. [ ] Admin chọn conversation với Customer
3. [ ] Admin click "Voice Call"
4. [ ] Customer nhận được incoming call modal
5. [ ] Customer click "Accept"
6. [ ] Call kết nối thành công
7. [ ] Cả 2 bên nghe được nhau
8. [ ] End call hoạt động

#### Test Case 3: Video Call
1. [ ] Customer gọi Admin (Video)
2. [ ] Admin accept
3. [ ] Video hiển thị ở cả 2 bên
4. [ ] Audio hoạt động
5. [ ] End call hoạt động

#### Test Case 4: Call Rejection
1. [ ] Customer gọi Admin
2. [ ] Admin click "Reject"
3. [ ] Customer nhận được notification "Call rejected"
4. [ ] Call cleanup đúng

#### Test Case 5: Call Timeout
1. [ ] Customer gọi Admin
2. [ ] Admin không answer trong 30 giây
3. [ ] Call timeout
4. [ ] Customer nhận được notification "Call timeout"

### 5. Browser Compatibility
- [ ] Test trên Chrome/Edge (Chromium)
- [ ] Test trên Firefox
- [ ] Test trên Safari (nếu có)
- [ ] Test trên mobile browsers

### 6. Network Requirements
- [ ] HTTPS enabled (WebRTC yêu cầu HTTPS hoặc localhost)
- [ ] STUN servers accessible (Google STUN servers)
- [ ] TURN server (nếu cần cho NAT traversal - optional)

### 7. Permissions
- [ ] Microphone permission được request và granted
- [ ] Camera permission được request và granted (cho video call)

### 8. Error Handling
- [ ] User deny microphone permission → hiển thị error message
- [ ] User deny camera permission → hiển thị error message
- [ ] Network error → hiển thị error message
- [ ] Call failed → cleanup và hiển thị error

## 📋 Pre-Deploy Checklist

### Code Review
- [ ] Không còn Stringee references trong code
- [ ] Tất cả functions đã được update
- [ ] Error handling đầy đủ
- [ ] Console logs không có errors

### Database
- [ ] `call_sessions` table có đầy đủ columns
- [ ] Call sessions được tạo và update đúng

### Server Configuration
- [ ] Socket.IO server đang chạy
- [ ] CORS configured đúng
- [ ] Port accessible

### Security
- [ ] Authentication check cho call endpoints
- [ ] User authorization check
- [ ] CSRF protection (nếu có)

## 🚀 Deploy Steps

1. **Backup current code**
   ```bash
   cp -r my-php-project my-php-project-backup-$(date +%Y%m%d)
   ```

2. **Remove Stringee files**
   ```bash
   cd my-php-project
   rm assets/js/stringee-helper.js
   rm src/controllers/stringee-controller.php
   rm src/controllers/stringee-callback.php
   rm config/stringee.php
   ```

3. **Upload code to server**

4. **Restart Socket.IO server**
   ```bash
   pm2 restart socket-server
   # hoặc
   node socket-server.js
   ```

5. **Test trên production**
   - Test voice call
   - Test video call
   - Test call rejection
   - Test call timeout

## ⚠️ Known Issues & Solutions

### Issue 1: Call không kết nối được
**Nguyên nhân**: ICE candidates không được exchange đúng
**Giải pháp**: 
- Kiểm tra Socket.IO connection
- Kiểm tra `ice-candidate` events được emit và nhận
- Kiểm tra STUN servers accessible

### Issue 2: Video không hiển thị
**Nguyên nhân**: Camera permission chưa được grant
**Giải pháp**: 
- Request permission trước khi call
- Check `getUserMedia` error

### Issue 3: Audio không hoạt động
**Nguyên nhân**: Microphone permission chưa được grant
**Giải pháp**: 
- Request permission trước khi call
- Check `getUserMedia` error

### Issue 4: NAT Traversal issues
**Nguyên nhân**: Firewall/NAT blocking P2P connection
**Giải pháp**: 
- Setup TURN server
- Update `rtcConfiguration` trong `webrtc-helper.js`

## 📝 Notes

- WebRTC sử dụng P2P connection, không cần server trung gian
- STUN servers (Google) là free và public
- TURN server chỉ cần nếu có vấn đề NAT traversal
- HTTPS required cho production (WebRTC security requirement)

## ✅ Final Verification

Sau khi deploy, verify:
1. [ ] Customer có thể gọi Admin
2. [ ] Admin có thể gọi Customer
3. [ ] Voice call hoạt động
4. [ ] Video call hoạt động
5. [ ] Call rejection hoạt động
6. [ ] Call timeout hoạt động
7. [ ] Call end hoạt động
8. [ ] No console errors
9. [ ] No server errors

---

**Status**: ✅ Code đã sẵn sàng, cần test và xóa Stringee files trước khi deploy

