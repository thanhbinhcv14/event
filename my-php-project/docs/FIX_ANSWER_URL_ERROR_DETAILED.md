# 🔍 Giải thích chi tiết lỗi ANSWER_URL_SCCO_INCORRECT_FORMAT

## ❌ Lỗi hiện tại

```
[Stringee] Call failed: ANSWER_URL_SCCO_INCORRECT_FORMAT
Lỗi Answer URL: ANSWER_URL_SCCO_INCORRECT_FORMAT
Vui lòng kiểm tra Answer URL trong Stringee Dashboard.
```

## 📋 Nguyên nhân có thể

### 1. **Answer URL trong Stringee Dashboard chưa được cập nhật** ⚠️ (Nguyên nhân phổ biến nhất)

**Vấn đề:**
- Answer URL trong Stringee Dashboard vẫn đang dùng URL helper của Stringee:
  ```
  https://developer.stringee.com/scco_helper/simple_project_answer_url?record=false&appToPhone=auto&recordFormat=mp3
  ```
- URL helper này **KHÔNG hoạt động** với production và sẽ trả về format không đúng.

**Giải pháp:**
1. Đăng nhập vào [Stringee Console](https://console.stringee.com/)
2. Vào project của bạn
3. Click **"Detail"** (biểu tượng wrench) hoặc click vào tên project
4. Chọn tab **"Config URL"**
5. Cập nhật **Answer URL** thành:
   ```
   https://sukien.info.vn/my-php-project/src/controllers/stringee-callback.php?type=answer
   ```
   (Thay `sukien.info.vn` và `/my-php-project` bằng domain và path thực tế của bạn)
6. Click **"Save"**

### 2. **Response từ callback không đúng format SCCO**

**Format SCCO yêu cầu:**
```json
{
  "action": "connect",
  "from": {
    "type": "internal",
    "number": "user_id_1",
    "alias": "User Name 1"
  },
  "to": {
    "type": "internal",
    "number": "user_id_2",
    "alias": "User Name 2"
  },
  "customData": "",
  "timeout": 60,
  "maxConnectTime": 0,
  "peerToPeerCall": true
}
```

**Các lỗi thường gặp:**
- ❌ Thiếu field bắt buộc: `action`, `from`, `to`, `timeout`, `maxConnectTime`, `peerToPeerCall`
- ❌ `action` không phải là `"connect"`
- ❌ `from.number` hoặc `to.number` bị rỗng (`""`)
- ❌ `timeout` hoặc `maxConnectTime` không phải là integer (là string)
- ❌ `peerToPeerCall` không phải là boolean (là string `"true"`)

**Kiểm tra:**
- Chạy file test: `test-stringee-callback.php` để xem response format
- Kiểm tra logs trong `stringee-callback.php` (xem error_log)

### 3. **Có output trước JSON response**

**Vấn đề:**
- Có whitespace, BOM, hoặc error messages trước JSON
- PHP warnings/notices được output
- Output từ các file include trước đó

**Giải pháp:**
- File `stringee-callback.php` đã có output buffering để xử lý vấn đề này
- Đảm bảo không có output nào trước `<?php` tag
- Tắt error display trong production: `ini_set('display_errors', 0);`

### 4. **Response không phải là valid JSON**

**Vấn đề:**
- JSON bị lỗi syntax
- Có ký tự đặc biệt không được escape
- Encoding không đúng (UTF-8)

**Giải pháp:**
- Sử dụng `json_encode()` với flags: `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`
- Đảm bảo tất cả string values đã được cast thành string: `(string)$value`

## 🔧 Cách kiểm tra và fix

### Bước 1: Kiểm tra Answer URL trong Dashboard

1. Mở [Stringee Console](https://console.stringee.com/)
2. Vào project → Detail → Config URL
3. Kiểm tra Answer URL:
   - ✅ **Đúng:** `https://yourdomain.com/my-php-project/src/controllers/stringee-callback.php?type=answer`
   - ❌ **Sai:** `https://developer.stringee.com/scco_helper/...`

### Bước 2: Test callback URL

**Cách 1: Dùng browser**
```
https://yourdomain.com/my-php-project/src/controllers/stringee-callback.php?type=answer
```
Nếu thấy JSON response (có thể là error), nghĩa là URL accessible.

**Cách 2: Dùng curl**
```bash
curl -X POST https://yourdomain.com/my-php-project/src/controllers/stringee-callback.php?type=answer \
  -H "Content-Type: application/json" \
  -d '{
    "action": "connect",
    "from": {"type": "internal", "number": "user1", "alias": "User 1"},
    "to": {"type": "internal", "number": "user2", "alias": "User 2"},
    "customData": "test=1"
  }'
```

**Cách 3: Dùng test script**
Mở file `test-stringee-callback.php` trong browser để xem:
- Answer URL hiện tại
- SCCO Response format
- Validation results

### Bước 3: Kiểm tra logs

Kiểm tra error logs của PHP để xem:
- Response được tạo ra như thế nào
- Có lỗi gì trong quá trình xử lý không

**Vị trí logs:**
- XAMPP: `C:\xampp\apache\logs\error.log`
- Linux: `/var/log/apache2/error.log` hoặc `/var/log/php-fpm/error.log`

**Tìm trong logs:**
```
Stringee Answer Callback Response: ...
```

### Bước 4: Kiểm tra response format

Chạy file test: `test-stringee-callback.php` để xem:
- ✅ SCCO Response format có đúng không
- ✅ Tất cả fields có đầy đủ không
- ✅ Types có đúng không (int, boolean, string)

## ✅ Checklist

- [ ] Answer URL trong Stringee Dashboard đã được cập nhật đúng (không phải URL helper)
- [ ] Answer URL accessible từ internet (test bằng curl hoặc browser)
- [ ] SCCO Response format đúng (chạy `test-stringee-callback.php`)
- [ ] Không có output trước JSON response
- [ ] Tất cả fields trong SCCO đều có giá trị hợp lệ
- [ ] `from.number` và `to.number` không rỗng
- [ ] `timeout` và `maxConnectTime` là integer
- [ ] `peerToPeerCall` là boolean `true`
- [ ] Test thực tế một cuộc gọi

## 🚨 Lưu ý quan trọng

1. **Answer URL phải là HTTPS** cho production (Stringee yêu cầu)
2. **Answer URL phải accessible** từ internet (không phải localhost)
3. **Sau khi cập nhật Answer URL**, có thể mất vài phút để Stringee cập nhật
4. **Clear browser cache** sau khi fix để đảm bảo code mới được load

## 📞 Nếu vẫn còn lỗi

1. Kiểm tra lại Answer URL trong Stringee Dashboard
2. Test callback URL bằng curl
3. Xem logs để biết response thực tế
4. Chạy `test-stringee-callback.php` để validate format
5. Đảm bảo không có output trước JSON

