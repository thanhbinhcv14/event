# 📋 Checklist Test CSRF Protection

## ✅ Các file đã có CSRF Protection - Cần test

### 1. **Payment System** 💳

#### File: `payment/payment.php`
- **URL test**: `http://localhost/event/my-php-project/payment/payment.php?event_id=XXX`
- **Actions cần test**:
  - [ ] Tạo thanh toán tiền mặt (POST `create_payment`)
  - [ ] Tạo thanh toán chuyển khoản (POST `create_payment`)
  - [ ] Tạo thanh toán SePay (POST `create_sepay_payment`)
- **Cách test**:
  1. Mở trang thanh toán
  2. Chọn phương thức thanh toán
  3. Nhấn "Tiến hành thanh toán"
  4. Kiểm tra Network tab: Request phải có `csrf_token` trong body
  5. Nếu không có token → Lỗi 403 "CSRF token không hợp lệ"

#### File: `src/controllers/payment.php`
- **URL test**: `http://localhost/event/my-php-project/src/controllers/payment.php`
- **Actions cần test**:
  - [ ] `create_payment` (POST) - Tạo thanh toán
  - [ ] `update_payment_status` (POST) - Cập nhật trạng thái
  - [ ] `confirm_cash_payment` (POST) - Xác nhận tiền mặt
  - [ ] `confirm_banking_payment` (POST) - Xác nhận chuyển khoản
  - [ ] `cancel_payment` (POST) - Hủy thanh toán
- **Actions KHÔNG cần CSRF** (read-only):
  - [ ] `get_payment_history` (GET) - Lấy lịch sử
  - [ ] `get_payment_status` (GET) - Lấy trạng thái
  - [ ] `get_payment_list` (GET) - Lấy danh sách
  - [ ] `get_payment_stats` (GET) - Lấy thống kê
- **Cách test**:
  1. Mở Browser DevTools → Network tab
  2. Thực hiện action (ví dụ: tạo thanh toán)
  3. Kiểm tra request:
     - Phải có `csrf_token` trong body hoặc header `X-CSRF-Token`
     - Nếu gửi request không có token → Phải nhận 403 error
  4. Test với Postman/curl:
     ```bash
     # Test không có token (phải fail)
     curl -X POST http://localhost/event/my-php-project/src/controllers/payment.php \
       -d "action=create_payment&event_id=1&amount=100000"
     # Kết quả: {"success":false,"error":"CSRF token không hợp lệ...","code":"CSRF_TOKEN_INVALID"}
     ```

---

### 2. **Event Registration** 📝

#### File: `events/register.php`
- **URL test**: `http://localhost/event/my-php-project/events/register.php`
- **Actions cần test**:
  - [ ] Đăng ký sự kiện mới (POST `register`)
  - [ ] Cập nhật sự kiện (POST `update_event`)
- **Cách test**:
  1. Mở trang đăng ký sự kiện
  2. Điền form và submit
  3. Kiểm tra Network tab: Request phải có `csrf_token`
  4. Test với token sai → Phải nhận 403 error

#### File: `src/controllers/event-register.php`
- **URL test**: `http://localhost/event/my-php-project/src/controllers/event-register.php`
- **Actions cần test**:
  - [ ] `register` (POST) - Đăng ký sự kiện
  - [ ] `update_event` (POST) - Cập nhật sự kiện
- **Actions KHÔNG cần CSRF** (read-only):
  - [ ] `get_csrf_token` (GET) - Lấy token
  - [ ] `get_event_types` (GET) - Lấy loại sự kiện
  - [ ] `get_locations` (GET) - Lấy địa điểm
- **Cách test**:
  1. Test với Postman:
     ```bash
     # 1. Lấy CSRF token
     curl http://localhost/event/my-php-project/src/controllers/event-register.php?action=get_csrf_token
     
     # 2. Sử dụng token để đăng ký (phải thành công)
     curl -X POST http://localhost/event/my-php-project/src/controllers/event-register.php \
       -H "Content-Type: application/json" \
       -d '{"action":"register","csrf_token":"TOKEN_HERE","event_name":"Test Event"}'
     
     # 3. Test không có token (phải fail)
     curl -X POST http://localhost/event/my-php-project/src/controllers/event-register.php \
       -d '{"action":"register","event_name":"Test Event"}'
     ```

---

### 3. **Admin Panel** 👨‍💼

#### File: `admin/payment-management.php`
- **URL test**: `http://localhost/event/my-php-project/admin/payment-management.php`
- **Actions cần test**:
  - [ ] Xác nhận thanh toán tiền mặt (POST `confirm_cash_payment`)
  - [ ] Xác nhận thanh toán chuyển khoản (POST `confirm_banking_payment`)
  - [ ] Cập nhật trạng thái thanh toán (POST `update_payment_status`)
- **Cách test**:
  1. Đăng nhập admin
  2. Vào trang "Quản lý thanh toán"
  3. Thực hiện các action (xác nhận, cập nhật)
  4. Kiểm tra Network tab: jQuery AJAX requests phải tự động có `csrf_token`
  5. Test bằng cách:
     - Mở Console → Gỡ token khỏi request → Phải nhận 403 error

#### File: `admin/includes/admin-header.php`
- **Tất cả trang admin** sử dụng header này
- **Cách test**:
  1. Mở bất kỳ trang admin nào
  2. Kiểm tra Console: Phải có log "CSRF token fetched" (nếu có log)
  3. Kiểm tra Network tab: Tất cả POST requests phải có `csrf_token`
  4. Test các trang admin:
     - [ ] `admin/index.php` - Dashboard
     - [ ] `admin/payment-management.php` - Quản lý thanh toán
     - [ ] `admin/event-planning.php` - Lên kế hoạch
     - [ ] `admin/locations.php` - Quản lý địa điểm
     - [ ] `admin/device.php` - Quản lý thiết bị

---

## 🧪 Test Cases Chi Tiết

### Test Case 1: Tạo thanh toán không có CSRF token
```javascript
// Mở Console trên payment/payment.php
fetch('../src/controllers/payment.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        action: 'create_payment',
        event_id: 1,
        amount: 100000
        // Không có csrf_token
    })
})
.then(r => r.json())
.then(console.log);
// Kết quả mong đợi: {"success":false,"error":"CSRF token không hợp lệ...","code":"CSRF_TOKEN_INVALID"}
```

### Test Case 2: Tạo thanh toán với CSRF token hợp lệ
```javascript
// 1. Lấy token
const token = await CSRFHelper.getToken();

// 2. Gửi request với token
fetch('../src/controllers/payment.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        action: 'create_payment',
        event_id: 1,
        amount: 100000,
        csrf_token: token
    })
})
.then(r => r.json())
.then(console.log);
// Kết quả mong đợi: Thành công hoặc lỗi validation khác (KHÔNG phải CSRF error)
```

### Test Case 3: Test jQuery AJAX tự động thêm token
```javascript
// Trên trang admin (đã include csrf-helper.js)
$.ajax({
    url: '../src/controllers/payment.php',
    method: 'POST',
    data: {
        action: 'update_payment_status',
        payment_id: 1,
        status: 'Thành công'
        // Token sẽ được tự động thêm bởi csrf-helper.js
    },
    success: function(data) {
        console.log('Success:', data);
    },
    error: function(xhr) {
        console.log('Error:', xhr.responseJSON);
    }
});
// Kiểm tra Network tab: Request phải có csrf_token
```

### Test Case 4: Test fetchWithCSRF
```javascript
// Trên payment/payment.php
const formData = new FormData();
formData.append('action', 'create_payment');
formData.append('event_id', 1);
formData.append('amount', 100000);

fetchWithCSRF('../src/controllers/payment.php', {
    method: 'POST',
    body: formData
})
.then(r => r.json())
.then(console.log);
// Kiểm tra Network tab: Request phải có csrf_token trong body hoặc header
```

---

## 🔍 Cách kiểm tra CSRF hoạt động

### 1. **Kiểm tra Frontend (Browser DevTools)**

#### Bước 1: Mở DevTools
- F12 hoặc Right-click → Inspect
- Tab: **Network**

#### Bước 2: Thực hiện action
- Ví dụ: Tạo thanh toán, cập nhật trạng thái

#### Bước 3: Kiểm tra Request
1. Tìm request POST trong Network tab
2. Click vào request → Tab **Headers** hoặc **Payload**
3. Kiểm tra:
   - **Form Data** hoặc **Request Payload** phải có `csrf_token`
   - Hoặc **Request Headers** phải có `X-CSRF-Token`

#### Bước 4: Test không có token
1. Mở Console
2. Gửi request không có token:
   ```javascript
   fetch('../src/controllers/payment.php', {
       method: 'POST',
       body: JSON.stringify({action: 'create_payment'})
   })
   ```
3. Kết quả phải là: `{"success":false,"error":"CSRF token không hợp lệ..."}`

### 2. **Kiểm tra Backend (Server Logs)**

#### Kiểm tra error log:
```bash
# Xem PHP error log
tail -f /path/to/php_error.log

# Hoặc xem Apache error log
tail -f /var/log/apache2/error.log
```

#### Tìm CSRF errors:
- Tìm các dòng có "CSRF token không hợp lệ"
- Nếu có nhiều → Có thể có vấn đề với token generation

### 3. **Test với Postman/curl**

#### Test 1: Request không có token (phải fail)
```bash
curl -X POST http://localhost/event/my-php-project/src/controllers/payment.php \
  -H "Content-Type: application/json" \
  -d '{"action":"create_payment","event_id":1,"amount":100000}'
```

**Kết quả mong đợi:**
```json
{
  "success": false,
  "error": "CSRF token không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang.",
  "code": "CSRF_TOKEN_INVALID"
}
```

#### Test 2: Request có token hợp lệ (phải thành công hoặc lỗi validation khác)
```bash
# 1. Lấy token (cần session cookie)
curl -c cookies.txt http://localhost/event/my-php-project/src/controllers/event-register.php?action=get_csrf_token

# 2. Sử dụng token
curl -X POST http://localhost/event/my-php-project/src/controllers/payment.php \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"action":"create_payment","event_id":1,"amount":100000,"csrf_token":"TOKEN_HERE"}'
```

---

## ✅ Checklist Test Tổng Quan

### Frontend Tests
- [ ] `payment/payment.php` - Form thanh toán có token
- [ ] `events/register.php` - Form đăng ký có token
- [ ] `admin/payment-management.php` - AJAX requests có token
- [ ] Tất cả trang admin - jQuery AJAX tự động thêm token

### Backend Tests
- [ ] `src/controllers/payment.php` - Verify CSRF cho modify actions
- [ ] `src/controllers/event-register.php` - Verify CSRF cho register/update
- [ ] Test không có token → Phải trả về 403
- [ ] Test token sai → Phải trả về 403
- [ ] Test token đúng → Phải xử lý request

### Integration Tests
- [ ] Tạo thanh toán từ frontend → Thành công
- [ ] Cập nhật trạng thái từ admin → Thành công
- [ ] Đăng ký sự kiện → Thành công
- [ ] Request không có token → Bị chặn

---

## 🐛 Troubleshooting

### Vấn đề: Token không được gửi
**Nguyên nhân có thể:**
1. Chưa include `csrf-helper.js`
2. jQuery chưa load trước `csrf-helper.js`
3. Sử dụng `fetch()` thay vì `fetchWithCSRF()`

**Giải pháp:**
- Kiểm tra thứ tự load script
- Đảm bảo jQuery load trước `csrf-helper.js`
- Sử dụng `fetchWithCSRF()` cho fetch requests

### Vấn đề: Token không hợp lệ
**Nguyên nhân có thể:**
1. Session không khớp
2. Token đã hết hạn (mặc định 1 giờ)
3. Token không được lưu đúng trong session

**Giải pháp:**
- Kiểm tra session có tồn tại không
- Refresh trang để lấy token mới
- Kiểm tra `csrf_token` trong session

### Vấn đề: GET request bị chặn
**Nguyên nhân:**
- CSRF protection được áp dụng cho GET requests (sai)

**Giải pháp:**
- Chỉ áp dụng CSRF cho POST/PUT/DELETE
- GET requests không cần CSRF

---

## 📝 Ghi chú Test

- **Test trên môi trường development trước**
- **Test với nhiều trình duyệt khác nhau** (Chrome, Firefox, Edge)
- **Test với nhiều user khác nhau** (mỗi user có session riêng)
- **Test token expiration** (đợi 1 giờ để token hết hạn)
- **Test với token từ session khác** (phải fail)

---

## 🎯 Kết quả mong đợi

Sau khi test, bạn phải thấy:
1. ✅ Tất cả POST requests có `csrf_token`
2. ✅ Requests không có token → 403 error
3. ✅ Requests có token hợp lệ → Xử lý bình thường
4. ✅ GET requests không bị chặn
5. ✅ Token tự động refresh khi hết hạn

