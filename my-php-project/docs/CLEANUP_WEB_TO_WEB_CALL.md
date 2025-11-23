# 🧹 Cleanup Web-to-Web Call - Xóa các phần không cần thiết

## ✅ Đã kiểm tra và xác nhận

### 1. **Database Schema - `call_sessions`** ✅

```sql
CREATE TABLE `call_sessions` (
  `id` int(11) NOT NULL,
  `stringee_call_id` varchar(255) DEFAULT NULL,
  `conversation_id` int(11) NOT NULL,
  `caller_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `call_type` enum('voice','video') NOT NULL,
  `status` enum('initiated','ringing','accepted','rejected','ended','missed') DEFAULT 'initiated',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
)
```

**✅ Đủ điều kiện:**
- ✅ `id` - Primary key
- ✅ `stringee_call_id` - Lưu Stringee call ID
- ✅ `conversation_id` - Foreign key đến conversations
- ✅ `caller_id` - Foreign key đến users
- ✅ `receiver_id` - Foreign key đến users
- ✅ `call_type` - 'voice' hoặc 'video'
- ✅ `status` - Trạng thái call
- ✅ `started_at`, `ended_at`, `duration` - Thời gian và duration
- ✅ Indexes: `idx_stringee_call_id`, `idx_call_sessions_status`, `idx_call_sessions_conversation`

### 2. **SDK Loading** ✅

**File:** `chat.php` và `admin/chat.php`

**✅ Đã load đúng từ `latest.sdk.bundle.min.js`:**
```javascript
const localSDKPath = '<?php echo BASE_PATH; ?>/assets/Stringee/StringeeWebSDK_2.9.0/latest.sdk.bundle.min.js';
// Ưu tiên: Local → CDN fallback
```

**✅ Kiểm tra SDK:**
```javascript
if (typeof StringeeClient !== 'undefined' && typeof StringeeCall !== 'undefined') {
    // SDK đã load
}
```

### 3. **Code đã được đơn giản hóa** ✅

**✅ Đã xóa:**
- ❌ Error messages về `ANSWER_URL_SCCO_INCORRECT_FORMAT` (không cần cho Web-to-Web)
- ❌ Các thông báo về Answer URL trong Dashboard

**✅ Giữ lại (cần thiết):**
- ✅ `stringee-helper.js` - Web SDK integration
- ✅ `call-controller.php` - Call session management
- ✅ `stringee-controller.php` - Token generation
- ✅ `stringee-callback.php` - Optional (cho App-to-Phone, không dùng cho Web-to-Web)

---

## 📋 Checklist

### **Database** ✅
- [x] `call_sessions` table có đủ columns
- [x] `stringee_call_id` column tồn tại
- [x] Indexes đã được tạo
- [x] Foreign keys đã được thiết lập

### **SDK Loading** ✅
- [x] Load từ `latest.sdk.bundle.min.js` (local)
- [x] Fallback về CDN nếu local fail
- [x] Kiểm tra `StringeeClient` và `StringeeCall` available

### **Code Cleanup** ✅
- [x] Xóa error messages về Answer URL
- [x] Xóa thông báo về SCCO
- [x] Giữ lại logic Web-to-Web call

### **Files cần thiết:**
- ✅ `assets/js/stringee-helper.js` - Web SDK helper
- ✅ `src/controllers/call-controller.php` - Call session management
- ✅ `src/controllers/stringee-controller.php` - Token generation
- ✅ `chat.php` - Frontend chat (customer)
- ✅ `admin/chat.php` - Frontend chat (admin/staff)

### **Files không cần (nhưng có thể giữ để tương thích):**
- ⚠️ `src/controllers/stringee-callback.php` - Chỉ cần cho App-to-Phone

---

## ⚠️ QUAN TRỌNG: Tắt Programmable Contact Center (PCC)

**Nếu vẫn gặp lỗi `ANSWER_URL_SCCO_INCORRECT_FORMAT`:**

1. **Đăng nhập Stringee Dashboard:** https://dashboard.stringee.com/
2. **Vào Project → Settings**
3. **Tìm "Programmable Contact Center" (PCC)**
4. **TẮT** (Turn OFF) chế độ này
5. **Lưu** cấu hình

**Lý do:**
- Khi PCC **BẬT**, mọi call (kể cả Web-to-Web) đều yêu cầu Answer URL + SCCO
- Khi PCC **TẮT**, Web-to-Web call hoạt động bình thường, không cần Answer URL

**Xem chi tiết:** `docs/FIX_PCC_PROGRAMMABLE_CONTACT_CENTER.md`

## ✅ Kết luận

**Tất cả đã sẵn sàng cho Web-to-Web Call:**
- ✅ Database đủ điều kiện
- ✅ SDK được load đúng từ `latest.sdk.bundle.min.js`
- ✅ Code đã được đơn giản hóa
- ✅ Đã xóa các phần không cần thiết
- ⚠️ **Cần tắt PCC trong Stringee Dashboard** (nếu vẫn lỗi)

