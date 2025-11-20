# Hướng Dẫn Cấu Hình Webhook SePay

## 📋 Tổng Quan

Webhook SePay được sử dụng để tự động cập nhật trạng thái thanh toán khi có giao dịch chuyển khoản.

**File webhook handler**: `hooks/sepay-payment.php`  
**Webhook URL**: `https://sukien.info.vn/hooks/sepay-payment.php`

---

## ✅ Checklist Cấu Hình

### 1. SePay Dashboard Configuration

**Đăng nhập**: https://my.sepay.vn → Tab **"IPN"**

| Mục | Giá trị | Ghi chú |
|-----|--------|---------|
| **IPN URL** | `https://sukien.info.vn/hooks/sepay-payment.php` | **QUAN TRỌNG**: Phải đúng URL |
| **Kích hoạt IPN** | **ON** (màu xanh) | Phải bật |
| **Content Type** | `application/json` | Phải đúng |
| **Auth Type** | `Secret Key` hoặc `API Token` | Tùy cấu hình |
| **Secret Key** | `Thanhbinh1@` (nếu Auth Type = Secret Key) | Từ IPN config |
| **API Token** | `BN3FCA9DRCGR6TTHY110...` | Từ API config |

### 2. File Configuration

**File**: `config/sepay.php`

```php
define('SEPAY_CALLBACK_URL', 'https://sukien.info.vn/hooks/sepay-payment.php');
define('SEPAY_WEBHOOK_TOKEN', 'BN3FCA9DRCGR6TTHY110...'); // API Token
define('SEPAY_IPN_SECRET_KEY', 'Thanhbinh1@'); // Secret Key từ IPN config
define('SEPAY_MATCH_PATTERN', 'SEPAY'); // Pattern để match content
```

### 3. Webhook Handler

**File**: `hooks/sepay-payment.php`

**Chức năng:**
- ✅ Nhận POST request từ SePay
- ✅ Xác thực bằng API Token hoặc Secret Key
- ✅ Parse payment ID từ content (`SEPAY{eventId}{paymentId}`)
- ✅ Cập nhật trạng thái thanh toán
- ✅ Ghi log vào `hook_log.txt` và database

---

## 🔐 Xác Thực Webhook

### Format Header

```http
Authorization: Apikey {API_TOKEN}
Content-Type: application/json
```

### Xác Thực Trong Code

```php
// Kiểm tra API Token từ header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Apikey ', '', $authHeader);

// Hoặc kiểm tra Secret Key từ IPN config
if ($token === SEPAY_WEBHOOK_TOKEN || $token === SEPAY_IPN_SECRET_KEY) {
    // Xác thực thành công
}
```

---

## 📝 Content Format

### Format Đúng

```
SEPAY{eventId}{paymentId}
```

**Ví dụ:**
- `SEPAY2220` (eventId=22, paymentId=20)
- `SEPAY12345` (eventId=123, paymentId=45)

**Lưu ý:**
- ✅ Không có dấu gạch dưới, khoảng trắng, hoặc ký tự đặc biệt
- ✅ Suffix từ 3-10 ký tự số
- ❌ SAI: `SEPAY22938_2391` (có dấu gạch dưới)
- ❌ SAI: `SEPAY 2220` (có khoảng trắng)

### Fallback Matching

Nếu không parse được từ content, webhook sẽ:
1. Match theo amount (với tolerance ±0.01 VND)
2. Tìm payment trong vòng 48 giờ gần nhất
3. Match payment đang "Đang xử lý"

---

## 🧪 Test Webhook

### Test 1: Kiểm Tra Endpoint

```bash
curl -X GET "https://sukien.info.vn/hooks/sepay-payment.php?test=1"
```

**Kết quả mong đợi:**
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

### Test 2: Test POST Request

```bash
curl -X POST "https://sukien.info.vn/hooks/sepay-payment.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Apikey BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP" \
  -d '{
    "gateway": "VietinBank",
    "transactionDate": "2024-01-01 10:00:00",
    "accountNumber": "100872918542",
    "content": "SEPAY2220",
    "transferType": "in",
    "transferAmount": 5000,
    "referenceCode": "REF123",
    "id": "SEPAY_TX_123"
  }'
```

---

## 🔍 Debug Webhook

### 1. Kiểm Tra Logs

**File log**: `hooks/hook_log.txt`

```bash
tail -f hooks/hook_log.txt
```

**Database log**: Bảng `webhook_logs`

```sql
SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT 10;
```

### 2. Kiểm Tra Database

```sql
-- Xem payments đang chờ xử lý
SELECT * FROM thanhtoan 
WHERE TrangThai = 'Đang xử lý' 
ORDER BY ID_ThanhToan DESC;

-- Xem webhook logs
SELECT * FROM webhook_logs 
ORDER BY created_at DESC 
LIMIT 20;
```

### 3. Kiểm Tra SePay Dashboard

1. Vào **Giao dịch** → Chọn một giao dịch
2. Xem phần **WebHooks đã bắn**
3. Status Code phải là **200** (không phải 404 hoặc 500)

---

## 🆘 Xử Lý Sự Cố

### Vấn Đề 1: Webhook Không Nhận Được

**Nguyên nhân có thể:**
- ❌ IPN URL trong SePay Dashboard SAI
- ❌ IPN chưa được kích hoạt
- ❌ Firewall chặn request từ SePay
- ❌ File webhook không tồn tại tại URL đó

**Giải pháp:**
1. Kiểm tra IPN URL trong SePay Dashboard
2. Đảm bảo IPN đã được bật
3. Kiểm tra file `hooks/sepay-payment.php` có tồn tại không
4. Test endpoint với `?test=1`

### Vấn Đề 2: Lỗi "Invalid Token"

**Nguyên nhân:**
- Token trong header không khớp với config

**Giải pháp:**
1. Kiểm tra `SEPAY_WEBHOOK_TOKEN` trong `config/sepay.php`
2. Kiểm tra `SEPAY_IPN_SECRET_KEY` nếu Auth Type = Secret Key
3. Xác nhận token trong SePay Dashboard

### Vấn Đề 3: "Payment Not Found"

**Nguyên nhân:**
- Content format không đúng
- Payment ID không tồn tại
- Amount không khớp

**Giải pháp:**
1. Kiểm tra content format: `SEPAY{eventId}{paymentId}`
2. Kiểm tra payment có tồn tại trong database không
3. Kiểm tra amount có khớp không (tolerance ±0.01 VND)

### Vấn Đề 4: Lỗi 404

**Nguyên nhân:**
- File webhook không tồn tại tại URL đó
- Cấu trúc thư mục không đúng

**Giải pháp:**
1. Kiểm tra file `hooks/sepay-payment.php` có tồn tại không
2. Kiểm tra cấu trúc thư mục trên server
3. Cập nhật IPN URL trong SePay Dashboard nếu cần

---

## 📝 Lưu Ý Quan Trọng

1. **POST Requests**: Webhook chỉ nhận POST requests từ SePay, GET requests chỉ dùng để test
2. **Content Format**: Phải đúng format `SEPAY{suffix}` không có ký tự đặc biệt
3. **Duplicate Protection**: Webhook kiểm tra `SePayTransactionId` để tránh xử lý trùng lặp
4. **Error Handling**: Tất cả lỗi được ghi log vào `hook_log.txt` và database
5. **Transaction Safety**: Tất cả cập nhật dùng database transaction

---

## ✅ Checklist Hoàn Chỉnh

- [ ] IPN URL trong SePay Dashboard = `https://sukien.info.vn/hooks/sepay-payment.php`
- [ ] IPN đã được kích hoạt (ON)
- [ ] Content Type = `application/json`
- [ ] Auth Type và Token đúng
- [ ] File `hooks/sepay-payment.php` tồn tại
- [ ] File `config/sepay.php` có đầy đủ config
- [ ] Test endpoint với `?test=1` thành công
- [ ] Test POST request với Authorization header thành công
- [ ] Logs được ghi vào `hook_log.txt` và database
- [ ] Webhook tự động cập nhật trạng thái thanh toán

---

**Ngày cập nhật**: 2025-01-20  
**Trạng thái**: Production Ready

