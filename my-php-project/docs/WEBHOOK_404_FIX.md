# Hướng dẫn sửa lỗi Webhook 404

## 🔴 Vấn đề

SePay đang gửi webhook đến: `https://sukien.info.vn/hooks/sepay-payment.php`
Nhưng server trả về **404 Not Found** vì file không tồn tại tại đường dẫn đó.

**File thực tế nằm ở**: `my-php-project/hooks/sepay-payment.php`

## ✅ Giải pháp

Có 3 cách để sửa lỗi này:

### Cách 1: Cập nhật URL trong SePay Dashboard (Khuyến nghị)

1. Đăng nhập vào SePay Dashboard: https://my.sepay.vn
2. Vào **Tích hợp & Thông báo** → **Tích hợp WebHooks**
3. Tìm webhook cho tài khoản **VietinBank - 100872918542**
4. Cập nhật URL webhook từ:
   ```
   https://sukien.info.vn/hooks/sepay-payment.php
   ```
   Thành:
   ```
   https://sukien.info.vn/my-php-project/hooks/sepay-payment.php
   ```
5. Lưu cấu hình
6. Test lại bằng cách thực hiện một giao dịch thử

**Ưu điểm**: Đơn giản, không cần thay đổi code
**Nhược điểm**: Cần quyền truy cập SePay dashboard

---

### Cách 2: Tạo file wrapper ở root

1. Upload file `hooks/sepay-payment-wrapper.php` lên root server:
   - Đường dẫn: `/hooks/sepay-payment.php` (ở root, không phải trong my-php-project)
   
2. Hoặc tạo file mới ở root với nội dung:
   ```php
   <?php
   // SePay Webhook Wrapper
   // File này ở root: /hooks/sepay-payment.php
   
   $webhookFile = __DIR__ . '/../my-php-project/hooks/sepay-payment.php';
   
   if (!file_exists($webhookFile)) {
       $webhookFile = __DIR__ . '/my-php-project/hooks/sepay-payment.php';
   }
   
   if (!file_exists($webhookFile)) {
       http_response_code(500);
       header('Content-Type: application/json');
       echo json_encode([
           'success' => false,
           'error' => 'Webhook handler not found',
           'timestamp' => date('Y-m-d H:i:s')
       ]);
       exit;
   }
   
   require_once $webhookFile;
   ```

3. Đảm bảo file có quyền đọc (chmod 644)

**Ưu điểm**: Không cần thay đổi cấu hình SePay
**Nhược điểm**: Cần quyền truy cập root server

---

### Cách 3: Tạo symlink (Linux/Unix)

Nếu server chạy Linux/Unix và có quyền SSH:

```bash
# Tạo thư mục hooks ở root (nếu chưa có)
mkdir -p /path/to/root/hooks

# Tạo symlink
ln -s /path/to/my-php-project/hooks/sepay-payment.php /path/to/root/hooks/sepay-payment.php
```

**Ưu điểm**: Không cần duplicate file
**Nhược điểm**: Cần quyền SSH và server phải hỗ trợ symlink

---

## 🧪 Kiểm tra sau khi sửa

### 1. Test endpoint có thể truy cập

```bash
curl -X GET "https://sukien.info.vn/hooks/sepay-payment.php?test=1"
```

**Kết quả mong đợi**:
```json
{
    "success": true,
    "message": "Webhook endpoint is accessible (TEST MODE)",
    "config": {
        "webhook_token_configured": true,
        "webhook_token_length": 64
    }
}
```

### 2. Kiểm tra trong SePay Dashboard

1. Vào **Giao dịch** → Chọn một giao dịch
2. Xem phần **WebHooks đã bắn**
3. Status Code phải là **200** (không phải 404)

### 3. Test với giao dịch thật

1. Thực hiện một giao dịch chuyển khoản nhỏ (ví dụ: 1,000 VNĐ)
2. Kiểm tra log trong `hooks/hook_log.txt`
3. Kiểm tra trạng thái thanh toán trong database

---

## 📝 Lưu ý quan trọng

1. **Sau khi sửa, phải test lại** để đảm bảo webhook hoạt động
2. **Kiểm tra log** trong `hooks/hook_log.txt` để xem webhook có nhận được request không
3. **Kiểm tra database** để xem trạng thái thanh toán có được cập nhật không
4. **Nếu vẫn lỗi 404**, kiểm tra:
   - File có tồn tại không
   - Quyền truy cập file (chmod)
   - Cấu hình web server (Apache/Nginx)
   - .htaccess có chặn truy cập không

---

## 🔍 Debug

Nếu vẫn gặp lỗi, kiểm tra:

1. **File có tồn tại không**:
   ```bash
   ls -la /path/to/root/hooks/sepay-payment.php
   ```

2. **Quyền truy cập**:
   ```bash
   chmod 644 /path/to/root/hooks/sepay-payment.php
   ```

3. **Log webhook**:
   ```bash
   tail -f /path/to/my-php-project/hooks/hook_log.txt
   ```

4. **Error log của PHP**:
   ```bash
   tail -f /var/log/php_errors.log
   ```

---

## ✅ Checklist

- [ ] Đã xác định được vấn đề (404 Not Found)
- [ ] Đã chọn phương pháp sửa (Cách 1/2/3)
- [ ] Đã thực hiện sửa lỗi
- [ ] Đã test endpoint với `?test=1`
- [ ] Đã kiểm tra trong SePay Dashboard (Status Code = 200)
- [ ] Đã test với giao dịch thật
- [ ] Đã kiểm tra log và database

---

**Ngày cập nhật**: 2025-11-19
**Trạng thái**: Đang chờ xử lý

