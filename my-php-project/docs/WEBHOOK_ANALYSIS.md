# 🔍 Phân tích Webhook - Tại sao không nhận được webhook

## 📊 Tình trạng hiện tại

### Từ logs và debug page:

1. **POST Requests: 0** ❌
   - SePay chưa gửi webhook thật đến server
   - Chỉ có GET requests (test mode)

2. **Payment đang chờ:**
   - ID: 20
   - Content trong GhiChu: `SEPAY22938_2391`
   - **⚠️ Vấn đề:** Format này có dấu gạch dưới `_` và 2 phần số

3. **Format đúng phải là:**
   - `SEPAY{suffix}` với suffix = eventId + paymentId (3-10 ký tự số)
   - Ví dụ: `SEPAY2220` (eventId=22, paymentId=20)

---

## 🔴 Vấn đề chính

### 1. SePay chưa gửi webhook (POST Requests = 0)

**Nguyên nhân có thể:**
- ❌ IPN URL trong SePay Dashboard SAI
- ❌ Trạng thái IPN chưa được kích hoạt
- ❌ SePay chưa nhận diện được giao dịch
- ❌ Content chuyển khoản không khớp pattern

### 2. Format Content có vấn đề

**Trong GhiChu:** `SEPAY22938_2391`
- Có dấu gạch dưới `_` → SePay có thể không nhận diện được
- Format đúng: `SEPAY2220` (không có dấu gạch dưới)

**Code hiện tại:**
```php
$suffix = $eventIdStr . $insertedIdStr; // "22" + "20" = "2220"
$transferContent = 'SEPAY' . $suffix;    // "SEPAY2220"
```

**Vậy tại sao trong GhiChu lại có `SEPAY22938_2391`?**
- Có thể là payment cũ với format khác
- Hoặc có lỗi trong quá trình tạo content

---

## ✅ Checklist kiểm tra và sửa

### Bước 1: Kiểm tra SePay Dashboard (QUAN TRỌNG NHẤT)

1. **Đăng nhập SePay Dashboard:** https://my.sepay.vn
2. **Vào Tab "IPN"**
3. **Kiểm tra các mục sau:**

   **a) IPN URL:**
   ```
   Phải là: https://sukien.info.vn/hooks/sepay-payment.php
   ❌ SAI nếu là: https://sukien.info.vn/
   ```

   **b) Kích hoạt IPN:**
   ```
   Phải bật: ON (màu xanh)
   ```

   **c) Content Type:**
   ```
   Phải là: application/json
   ```

   **d) Auth Type:**
   ```
   Có thể là: "Secret Key" hoặc "Không có"
   Secret Key: Kiểm tra xem có đúng với API Token không
   ```

### Bước 2: Kiểm tra format content khi tạo payment mới

1. **Tạo payment mới** (không dùng payment cũ)
2. **Kiểm tra GhiChu** phải có format:
   ```
   TransferContent: SEPAY{suffix}
   ```
   Ví dụ: `TransferContent: SEPAY2220` (không có dấu gạch dưới)

3. **Khi chuyển khoản:**
   - Nội dung chuyển khoản phải là: `SEPAY{suffix}`
   - Ví dụ: `SEPAY2220`
   - **KHÔNG được có** dấu gạch dưới, khoảng trắng, hoặc ký tự đặc biệt

### Bước 3: Test lại

1. **Tạo payment mới:**
   - Event ID: 22
   - Payment ID: 20 (hoặc ID mới)
   - Content mong đợi: `SEPAY2220`

2. **Chuyển khoản:**
   - Số tiền: Đúng với payment
   - Nội dung: `SEPAY2220` (chính xác, không có ký tự thừa)

3. **Đợi 1-2 phút** sau khi chuyển khoản

4. **Kiểm tra logs:**
   - Truy cập: `https://sukien.info.vn/hooks/debug-webhook.php`
   - Xem POST Requests có tăng lên không
   - Xem raw logs có webhook từ SePay không

---

## 🔧 Cách sửa nhanh

### 1. Sửa IPN URL trong SePay Dashboard

1. Vào SePay Dashboard → Tab "IPN"
2. Sửa IPN URL thành: `https://sukien.info.vn/hooks/sepay-payment.php`
3. Nhấn "Cập nhật"
4. Đợi 2-3 phút

### 2. Tạo payment mới (không dùng payment cũ)

Payment ID 20 có content `SEPAY22938_2391` - format này có vấn đề. Tạo payment mới để đảm bảo format đúng.

### 3. Chuyển khoản với content đúng format

- Content: `SEPAY{suffix}` (ví dụ: `SEPAY2220`)
- Không có dấu gạch dưới, khoảng trắng, hoặc ký tự đặc biệt

---

## 📝 Lưu ý quan trọng

1. **POST Requests = 0** nghĩa là SePay chưa gửi webhook → Vấn đề ở cấu hình SePay Dashboard

2. **Content format** phải khớp chính xác:
   - ✅ Đúng: `SEPAY2220`
   - ❌ Sai: `SEPAY22938_2391` (có dấu gạch dưới)
   - ❌ Sai: `SEPAY 2220` (có khoảng trắng)
   - ❌ Sai: `SEPAY-2220` (có dấu gạch ngang)

3. **Đợi 2-3 phút** sau khi sửa cấu hình trong SePay Dashboard

4. **Test với payment mới**, không dùng payment cũ có format sai

---

## 🆘 Nếu vẫn không nhận được webhook

1. **Liên hệ SePay Support:**
   - Mã đơn vị: `SP-LIVE-BT953B7A`
   - IPN URL: `https://sukien.info.vn/hooks/sepay-payment.php`
   - Thời gian giao dịch: [Thời gian bạn chuyển khoản]
   - Nội dung chuyển khoản: `SEPAY2220`
   - Số tiền: [Số tiền]

2. **Kiểm tra server logs:**
   - Apache/Nginx error logs
   - Có request nào đến `/hooks/sepay-payment.php` không?
   - Có lỗi 404, 500 không?

3. **Kiểm tra firewall:**
   - Firewall có chặn request từ SePay không?
   - Cần whitelist IP của SePay (nếu có)

---

## ✅ Tóm tắt

**Vấn đề chính:**
1. POST Requests = 0 → SePay chưa gửi webhook
2. Content format có vấn đề: `SEPAY22938_2391` (có dấu gạch dưới)

**Giải pháp:**
1. ✅ Kiểm tra và sửa IPN URL trong SePay Dashboard
2. ✅ Tạo payment mới với format đúng
3. ✅ Chuyển khoản với content: `SEPAY{suffix}` (không có dấu gạch dưới)
4. ✅ Đợi 2-3 phút và kiểm tra lại logs


## 📊 Tình trạng hiện tại

### Từ logs và debug page:

1. **POST Requests: 0** ❌
   - SePay chưa gửi webhook thật đến server
   - Chỉ có GET requests (test mode)

2. **Payment đang chờ:**
   - ID: 20
   - Content trong GhiChu: `SEPAY22938_2391`
   - **⚠️ Vấn đề:** Format này có dấu gạch dưới `_` và 2 phần số

3. **Format đúng phải là:**
   - `SEPAY{suffix}` với suffix = eventId + paymentId (3-10 ký tự số)
   - Ví dụ: `SEPAY2220` (eventId=22, paymentId=20)

---

## 🔴 Vấn đề chính

### 1. SePay chưa gửi webhook (POST Requests = 0)

**Nguyên nhân có thể:**
- ❌ IPN URL trong SePay Dashboard SAI
- ❌ Trạng thái IPN chưa được kích hoạt
- ❌ SePay chưa nhận diện được giao dịch
- ❌ Content chuyển khoản không khớp pattern

### 2. Format Content có vấn đề

**Trong GhiChu:** `SEPAY22938_2391`
- Có dấu gạch dưới `_` → SePay có thể không nhận diện được
- Format đúng: `SEPAY2220` (không có dấu gạch dưới)

**Code hiện tại:**
```php
$suffix = $eventIdStr . $insertedIdStr; // "22" + "20" = "2220"
$transferContent = 'SEPAY' . $suffix;    // "SEPAY2220"
```

**Vậy tại sao trong GhiChu lại có `SEPAY22938_2391`?**
- Có thể là payment cũ với format khác
- Hoặc có lỗi trong quá trình tạo content

---

## ✅ Checklist kiểm tra và sửa

### Bước 1: Kiểm tra SePay Dashboard (QUAN TRỌNG NHẤT)

1. **Đăng nhập SePay Dashboard:** https://my.sepay.vn
2. **Vào Tab "IPN"**
3. **Kiểm tra các mục sau:**

   **a) IPN URL:**
   ```
   Phải là: https://sukien.info.vn/hooks/sepay-payment.php
   ❌ SAI nếu là: https://sukien.info.vn/
   ```

   **b) Kích hoạt IPN:**
   ```
   Phải bật: ON (màu xanh)
   ```

   **c) Content Type:**
   ```
   Phải là: application/json
   ```

   **d) Auth Type:**
   ```
   Có thể là: "Secret Key" hoặc "Không có"
   Secret Key: Kiểm tra xem có đúng với API Token không
   ```

### Bước 2: Kiểm tra format content khi tạo payment mới

1. **Tạo payment mới** (không dùng payment cũ)
2. **Kiểm tra GhiChu** phải có format:
   ```
   TransferContent: SEPAY{suffix}
   ```
   Ví dụ: `TransferContent: SEPAY2220` (không có dấu gạch dưới)

3. **Khi chuyển khoản:**
   - Nội dung chuyển khoản phải là: `SEPAY{suffix}`
   - Ví dụ: `SEPAY2220`
   - **KHÔNG được có** dấu gạch dưới, khoảng trắng, hoặc ký tự đặc biệt

### Bước 3: Test lại

1. **Tạo payment mới:**
   - Event ID: 22
   - Payment ID: 20 (hoặc ID mới)
   - Content mong đợi: `SEPAY2220`

2. **Chuyển khoản:**
   - Số tiền: Đúng với payment
   - Nội dung: `SEPAY2220` (chính xác, không có ký tự thừa)

3. **Đợi 1-2 phút** sau khi chuyển khoản

4. **Kiểm tra logs:**
   - Truy cập: `https://sukien.info.vn/hooks/debug-webhook.php`
   - Xem POST Requests có tăng lên không
   - Xem raw logs có webhook từ SePay không

---

## 🔧 Cách sửa nhanh

### 1. Sửa IPN URL trong SePay Dashboard

1. Vào SePay Dashboard → Tab "IPN"
2. Sửa IPN URL thành: `https://sukien.info.vn/hooks/sepay-payment.php`
3. Nhấn "Cập nhật"
4. Đợi 2-3 phút

### 2. Tạo payment mới (không dùng payment cũ)

Payment ID 20 có content `SEPAY22938_2391` - format này có vấn đề. Tạo payment mới để đảm bảo format đúng.

### 3. Chuyển khoản với content đúng format

- Content: `SEPAY{suffix}` (ví dụ: `SEPAY2220`)
- Không có dấu gạch dưới, khoảng trắng, hoặc ký tự đặc biệt

---

## 📝 Lưu ý quan trọng

1. **POST Requests = 0** nghĩa là SePay chưa gửi webhook → Vấn đề ở cấu hình SePay Dashboard

2. **Content format** phải khớp chính xác:
   - ✅ Đúng: `SEPAY2220`
   - ❌ Sai: `SEPAY22938_2391` (có dấu gạch dưới)
   - ❌ Sai: `SEPAY 2220` (có khoảng trắng)
   - ❌ Sai: `SEPAY-2220` (có dấu gạch ngang)

3. **Đợi 2-3 phút** sau khi sửa cấu hình trong SePay Dashboard

4. **Test với payment mới**, không dùng payment cũ có format sai

---

## 🆘 Nếu vẫn không nhận được webhook

1. **Liên hệ SePay Support:**
   - Mã đơn vị: `SP-LIVE-BT953B7A`
   - IPN URL: `https://sukien.info.vn/hooks/sepay-payment.php`
   - Thời gian giao dịch: [Thời gian bạn chuyển khoản]
   - Nội dung chuyển khoản: `SEPAY2220`
   - Số tiền: [Số tiền]

2. **Kiểm tra server logs:**
   - Apache/Nginx error logs
   - Có request nào đến `/hooks/sepay-payment.php` không?
   - Có lỗi 404, 500 không?

3. **Kiểm tra firewall:**
   - Firewall có chặn request từ SePay không?
   - Cần whitelist IP của SePay (nếu có)

---

## ✅ Tóm tắt

**Vấn đề chính:**
1. POST Requests = 0 → SePay chưa gửi webhook
2. Content format có vấn đề: `SEPAY22938_2391` (có dấu gạch dưới)

**Giải pháp:**
1. ✅ Kiểm tra và sửa IPN URL trong SePay Dashboard
2. ✅ Tạo payment mới với format đúng
3. ✅ Chuyển khoản với content: `SEPAY{suffix}` (không có dấu gạch dưới)
4. ✅ Đợi 2-3 phút và kiểm tra lại logs

