# 🔍 Hướng dẫn kiểm tra Webhook SePay - Webhook không nhận được

## ⚠️ Vấn đề: Đã thanh toán nhưng webhook không nhận được

Từ thông tin payment của bạn:
- **Nội dung chuyển khoản**: `SEPAY2220`
- **Mã giao dịch**: `SEPAY_1763555938_2391`
- **Trạng thái**: Đang xử lý

---

## ✅ Checklist kiểm tra (theo thứ tự ưu tiên)

### 1. 🔴 QUAN TRỌNG NHẤT: Kiểm tra IPN URL trong SePay Dashboard

**Bước 1:** Đăng nhập SePay Dashboard → Tab **"IPN"**

**Bước 2:** Kiểm tra trường **"IPN URL *"**

**Phải là:**
```
https://sukien.info.vn/hooks/sepay-payment.php
```

**❌ SAI nếu là:**
- `https://sukien.info.vn/` (thiếu đường dẫn file)
- `https://sukien.info.vn/my-php-project/hooks/sepay-payment.php` (có thêm my-php-project)
- Bất kỳ URL nào khác

**Cách sửa:**
1. Sửa IPN URL thành: `https://sukien.info.vn/hooks/sepay-payment.php`
2. Nhấn **"Cập nhật"**
3. Đợi 1-2 phút để SePay cập nhật cấu hình

---

### 2. 🔴 Kiểm tra Trạng thái IPN

**Trong SePay Dashboard → Tab "IPN":**

**Phải bật:**
- ✅ **"Kích hoạt IPN"** = **ON** (màu xanh)

**❌ Nếu tắt:**
- Webhook sẽ không được gửi
- Bật lại và nhấn "Cập nhật"

---

### 3. 🔴 Kiểm tra Auth Type và Token

**Trong SePay Dashboard → Tab "IPN":**

**Auth Type:**
- Có thể là: **"Secret Key"** hoặc **"Không có"**
- Tùy thuộc vào cách SePay gửi webhook

**Secret Key (nếu Auth Type = Secret Key):**
- Có thể là: `Thanhbinh1@` hoặc API Token
- **Lưu ý:** Code hiện tại xác thực bằng **API Token** từ header `Authorization: Apikey {TOKEN}`

**Trong code (`config/sepay.php`):**
```php
SEPAY_WEBHOOK_TOKEN = 'BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP'
```

**Nếu SePay gửi Secret Key thay vì API Token:**
- Cần cập nhật `SEPAY_WEBHOOK_TOKEN` trong `config/sepay.php` thành Secret Key từ Dashboard
- Hoặc liên hệ SePay support để xác nhận cách xác thực

---

### 4. 🔴 Kiểm tra Cấu trúc mã thanh toán

**Trong SePay Dashboard → Tab "Phương thức thanh toán":**

**Cấu trúc mã thanh toán phải là:**
- **Prefix:** `SEPAY`
- **Suffix:** Số nguyên, từ 3 đến 10 ký tự
- **Ví dụ:** `SEPAY2220` (eventId=22, paymentId=20)

**Kiểm tra:**
- Nội dung chuyển khoản của bạn: `SEPAY2220` ✅ (đúng format)
- SePay phải nhận diện được pattern này

---

### 5. 🔴 Kiểm tra file webhook có tồn tại không

**Test URL:**
```
https://sukien.info.vn/hooks/sepay-payment.php?test=1
```

**Kết quả mong đợi:**
```json
{
    "success": true,
    "message": "Webhook endpoint is accessible (TEST MODE)",
    ...
}
```

**❌ Nếu lỗi 404:**
- File không tồn tại tại đường dẫn đó
- Cần kiểm tra cấu trúc thư mục trên server

---

### 6. 🔴 Kiểm tra logs

**Truy cập:**
```
https://sukien.info.vn/hooks/debug-webhook.php
```

**Kiểm tra:**
1. **Số POST requests:** Có tăng lên không?
2. **Số webhook thành công:** Có "Token verified successfully" không?
3. **Raw logs:** Xem có request nào từ SePay không?

**Nếu không có POST requests:**
- SePay chưa gửi webhook → Kiểm tra lại cấu hình IPN trong Dashboard

**Nếu có POST nhưng lỗi "Invalid token":**
- Token không khớp → Kiểm tra lại Secret Key/API Token

---

### 7. 🔴 Kiểm tra Content Type

**Trong SePay Dashboard → Tab "IPN":**

**Content Type phải là:**
```
application/json
```

**❌ Nếu là `application/x-www-form-urlencoded`:**
- Code hiện tại chỉ xử lý JSON
- Cần sửa lại trong Dashboard

---

### 8. 🔴 Kiểm tra tài khoản ngân hàng

**Trong SePay Dashboard → Tab "Phương thức thanh toán":**

**Tài khoản thụ hưởng:**
- **Ngân hàng:** VietinBank
- **Số tài khoản:** 100872918542
- **Chủ tài khoản:** BUI THANH BINH
- **Trạng thái:** Mặc định (có dấu sao ⭐)

**Kiểm tra:**
- Tài khoản này có được kích hoạt IPN không?
- Có phải là tài khoản mặc định không?

---

## 🔧 Các bước debug chi tiết

### Bước 1: Kiểm tra SePay Dashboard

1. Đăng nhập: https://my.sepay.vn
2. Vào **Tab "IPN"**
3. Chụp màn hình cấu hình IPN và so sánh với checklist trên

### Bước 2: Test webhook endpoint

1. Truy cập: `https://sukien.info.vn/hooks/sepay-payment.php?test=1`
2. Xem response có thành công không

### Bước 3: Xem logs

1. Truy cập: `https://sukien.info.vn/hooks/debug-webhook.php`
2. Xem có POST requests nào không
3. Xem raw logs để tìm lỗi

### Bước 4: Test lại payment

1. Tạo payment mới
2. Chuyển khoản với nội dung: `SEPAY{eventId}{paymentId}`
3. Đợi 1-2 phút
4. Kiểm tra lại logs

---

## 🆘 Nếu vẫn không nhận được webhook

### 1. Liên hệ SePay Support

**Thông tin cần cung cấp:**
- Mã đơn vị: `SP-LIVE-BT953B7A`
- IPN URL: `https://sukien.info.vn/hooks/sepay-payment.php`
- Thời gian giao dịch: [Thời gian bạn chuyển khoản]
- Nội dung chuyển khoản: `SEPAY2220`
- Số tiền: [Số tiền bạn chuyển]

**Câu hỏi:**
- SePay có gửi webhook đến URL trên không?
- Nếu có, tại sao webhook không đến được server?
- Có lỗi gì trong hệ thống SePay không?

### 2. Kiểm tra server logs

**Xem Apache/Nginx error logs:**
- Có request nào đến `/hooks/sepay-payment.php` không?
- Có lỗi 404, 500, hoặc lỗi khác không?

### 3. Kiểm tra firewall

- Firewall có chặn request từ SePay không?
- Cần whitelist IP của SePay (nếu có)

---

## 📝 Tóm tắt các điểm cần kiểm tra

| # | Điểm kiểm tra | Trạng thái | Ghi chú |
|---|---------------|------------|---------|
| 1 | IPN URL = `https://sukien.info.vn/hooks/sepay-payment.php` | ⬜ | **QUAN TRỌNG NHẤT** |
| 2 | Kích hoạt IPN = ON | ⬜ | Phải bật |
| 3 | Content Type = `application/json` | ⬜ | Phải đúng |
| 4 | Auth Type và Token đúng | ⬜ | Kiểm tra token |
| 5 | Cấu trúc mã = `SEPAY` + số | ⬜ | Đã đúng (`SEPAY2220`) |
| 6 | File webhook tồn tại | ⬜ | Test URL |
| 7 | Logs có POST requests | ⬜ | Xem debug-webhook.php |
| 8 | Tài khoản ngân hàng đúng | ⬜ | VietinBank 100872918542 |

---

## ✅ Sau khi sửa xong

1. **Đợi 2-3 phút** để SePay cập nhật cấu hình
2. **Test lại** bằng cách tạo payment mới
3. **Chuyển khoản** với nội dung đúng format
4. **Kiểm tra logs** sau 1-2 phút
5. **Xác nhận** payment đã được cập nhật

---

## 📞 Liên hệ hỗ trợ

Nếu vẫn không giải quyết được, vui lòng cung cấp:
1. Screenshot cấu hình IPN trong SePay Dashboard
2. Kết quả test từ `debug-webhook.php`
3. Raw logs từ `hook_log.txt`
4. Thời gian và thông tin giao dịch


## ⚠️ Vấn đề: Đã thanh toán nhưng webhook không nhận được

Từ thông tin payment của bạn:
- **Nội dung chuyển khoản**: `SEPAY2220`
- **Mã giao dịch**: `SEPAY_1763555938_2391`
- **Trạng thái**: Đang xử lý

---

## ✅ Checklist kiểm tra (theo thứ tự ưu tiên)

### 1. 🔴 QUAN TRỌNG NHẤT: Kiểm tra IPN URL trong SePay Dashboard

**Bước 1:** Đăng nhập SePay Dashboard → Tab **"IPN"**

**Bước 2:** Kiểm tra trường **"IPN URL *"**

**Phải là:**
```
https://sukien.info.vn/hooks/sepay-payment.php
```

**❌ SAI nếu là:**
- `https://sukien.info.vn/` (thiếu đường dẫn file)
- `https://sukien.info.vn/my-php-project/hooks/sepay-payment.php` (có thêm my-php-project)
- Bất kỳ URL nào khác

**Cách sửa:**
1. Sửa IPN URL thành: `https://sukien.info.vn/hooks/sepay-payment.php`
2. Nhấn **"Cập nhật"**
3. Đợi 1-2 phút để SePay cập nhật cấu hình

---

### 2. 🔴 Kiểm tra Trạng thái IPN

**Trong SePay Dashboard → Tab "IPN":**

**Phải bật:**
- ✅ **"Kích hoạt IPN"** = **ON** (màu xanh)

**❌ Nếu tắt:**
- Webhook sẽ không được gửi
- Bật lại và nhấn "Cập nhật"

---

### 3. 🔴 Kiểm tra Auth Type và Token

**Trong SePay Dashboard → Tab "IPN":**

**Auth Type:**
- Có thể là: **"Secret Key"** hoặc **"Không có"**
- Tùy thuộc vào cách SePay gửi webhook

**Secret Key (nếu Auth Type = Secret Key):**
- Có thể là: `Thanhbinh1@` hoặc API Token
- **Lưu ý:** Code hiện tại xác thực bằng **API Token** từ header `Authorization: Apikey {TOKEN}`

**Trong code (`config/sepay.php`):**
```php
SEPAY_WEBHOOK_TOKEN = 'BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP'
```

**Nếu SePay gửi Secret Key thay vì API Token:**
- Cần cập nhật `SEPAY_WEBHOOK_TOKEN` trong `config/sepay.php` thành Secret Key từ Dashboard
- Hoặc liên hệ SePay support để xác nhận cách xác thực

---

### 4. 🔴 Kiểm tra Cấu trúc mã thanh toán

**Trong SePay Dashboard → Tab "Phương thức thanh toán":**

**Cấu trúc mã thanh toán phải là:**
- **Prefix:** `SEPAY`
- **Suffix:** Số nguyên, từ 3 đến 10 ký tự
- **Ví dụ:** `SEPAY2220` (eventId=22, paymentId=20)

**Kiểm tra:**
- Nội dung chuyển khoản của bạn: `SEPAY2220` ✅ (đúng format)
- SePay phải nhận diện được pattern này

---

### 5. 🔴 Kiểm tra file webhook có tồn tại không

**Test URL:**
```
https://sukien.info.vn/hooks/sepay-payment.php?test=1
```

**Kết quả mong đợi:**
```json
{
    "success": true,
    "message": "Webhook endpoint is accessible (TEST MODE)",
    ...
}
```

**❌ Nếu lỗi 404:**
- File không tồn tại tại đường dẫn đó
- Cần kiểm tra cấu trúc thư mục trên server

---

### 6. 🔴 Kiểm tra logs

**Truy cập:**
```
https://sukien.info.vn/hooks/debug-webhook.php
```

**Kiểm tra:**
1. **Số POST requests:** Có tăng lên không?
2. **Số webhook thành công:** Có "Token verified successfully" không?
3. **Raw logs:** Xem có request nào từ SePay không?

**Nếu không có POST requests:**
- SePay chưa gửi webhook → Kiểm tra lại cấu hình IPN trong Dashboard

**Nếu có POST nhưng lỗi "Invalid token":**
- Token không khớp → Kiểm tra lại Secret Key/API Token

---

### 7. 🔴 Kiểm tra Content Type

**Trong SePay Dashboard → Tab "IPN":**

**Content Type phải là:**
```
application/json
```

**❌ Nếu là `application/x-www-form-urlencoded`:**
- Code hiện tại chỉ xử lý JSON
- Cần sửa lại trong Dashboard

---

### 8. 🔴 Kiểm tra tài khoản ngân hàng

**Trong SePay Dashboard → Tab "Phương thức thanh toán":**

**Tài khoản thụ hưởng:**
- **Ngân hàng:** VietinBank
- **Số tài khoản:** 100872918542
- **Chủ tài khoản:** BUI THANH BINH
- **Trạng thái:** Mặc định (có dấu sao ⭐)

**Kiểm tra:**
- Tài khoản này có được kích hoạt IPN không?
- Có phải là tài khoản mặc định không?

---

## 🔧 Các bước debug chi tiết

### Bước 1: Kiểm tra SePay Dashboard

1. Đăng nhập: https://my.sepay.vn
2. Vào **Tab "IPN"**
3. Chụp màn hình cấu hình IPN và so sánh với checklist trên

### Bước 2: Test webhook endpoint

1. Truy cập: `https://sukien.info.vn/hooks/sepay-payment.php?test=1`
2. Xem response có thành công không

### Bước 3: Xem logs

1. Truy cập: `https://sukien.info.vn/hooks/debug-webhook.php`
2. Xem có POST requests nào không
3. Xem raw logs để tìm lỗi

### Bước 4: Test lại payment

1. Tạo payment mới
2. Chuyển khoản với nội dung: `SEPAY{eventId}{paymentId}`
3. Đợi 1-2 phút
4. Kiểm tra lại logs

---

## 🆘 Nếu vẫn không nhận được webhook

### 1. Liên hệ SePay Support

**Thông tin cần cung cấp:**
- Mã đơn vị: `SP-LIVE-BT953B7A`
- IPN URL: `https://sukien.info.vn/hooks/sepay-payment.php`
- Thời gian giao dịch: [Thời gian bạn chuyển khoản]
- Nội dung chuyển khoản: `SEPAY2220`
- Số tiền: [Số tiền bạn chuyển]

**Câu hỏi:**
- SePay có gửi webhook đến URL trên không?
- Nếu có, tại sao webhook không đến được server?
- Có lỗi gì trong hệ thống SePay không?

### 2. Kiểm tra server logs

**Xem Apache/Nginx error logs:**
- Có request nào đến `/hooks/sepay-payment.php` không?
- Có lỗi 404, 500, hoặc lỗi khác không?

### 3. Kiểm tra firewall

- Firewall có chặn request từ SePay không?
- Cần whitelist IP của SePay (nếu có)

---

## 📝 Tóm tắt các điểm cần kiểm tra

| # | Điểm kiểm tra | Trạng thái | Ghi chú |
|---|---------------|------------|---------|
| 1 | IPN URL = `https://sukien.info.vn/hooks/sepay-payment.php` | ⬜ | **QUAN TRỌNG NHẤT** |
| 2 | Kích hoạt IPN = ON | ⬜ | Phải bật |
| 3 | Content Type = `application/json` | ⬜ | Phải đúng |
| 4 | Auth Type và Token đúng | ⬜ | Kiểm tra token |
| 5 | Cấu trúc mã = `SEPAY` + số | ⬜ | Đã đúng (`SEPAY2220`) |
| 6 | File webhook tồn tại | ⬜ | Test URL |
| 7 | Logs có POST requests | ⬜ | Xem debug-webhook.php |
| 8 | Tài khoản ngân hàng đúng | ⬜ | VietinBank 100872918542 |

---

## ✅ Sau khi sửa xong

1. **Đợi 2-3 phút** để SePay cập nhật cấu hình
2. **Test lại** bằng cách tạo payment mới
3. **Chuyển khoản** với nội dung đúng format
4. **Kiểm tra logs** sau 1-2 phút
5. **Xác nhận** payment đã được cập nhật

---

## 📞 Liên hệ hỗ trợ

Nếu vẫn không giải quyết được, vui lòng cung cấp:
1. Screenshot cấu hình IPN trong SePay Dashboard
2. Kết quả test từ `debug-webhook.php`
3. Raw logs từ `hook_log.txt`
4. Thời gian và thông tin giao dịch

