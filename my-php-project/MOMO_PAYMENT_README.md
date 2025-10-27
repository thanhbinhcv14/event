# MoMo Payment Integration

Hệ thống thanh toán MoMo đã được tích hợp dựa trên [MoMo Payment Platform](https://github.com/momo-wallet/payment) chính thức.

## Tính năng

### 1. **MoMo Payment SDK**
- **File:** `vendor/momo/MoMoPayment.php`
- **Chức năng:** 
  - Tạo thanh toán online qua MoMo Gateway
  - Tạo mã QR cho thanh toán offline
  - Xác thực chữ ký số HMAC SHA256
  - Kiểm tra trạng thái thanh toán
  - Hoàn tiền

### 2. **API Endpoints**

#### **Tạo thanh toán MoMo:**
```php
POST /src/controllers/payment.php
{
    "action": "create_momo_payment",
    "event_id": 21,
    "amount": 50000000,
    "payment_type": "deposit" // hoặc "full"
}
```

#### **Kiểm tra trạng thái thanh toán:**
```php
POST /src/controllers/payment.php
{
    "action": "verify_momo_payment",
    "order_id": "EVENT_21_TXN20251026123456"
}
```

#### **Tạo mã QR offline:**
```php
POST /src/controllers/payment.php
{
    "action": "create_payment",
    "event_id": 21,
    "amount": 50000000,
    "payment_method": "momo",
    "payment_type": "deposit"
}
```

### 3. **Webhook Handler**
- **File:** `payment/webhook.php`
- **Chức năng:** Xử lý Instant Payment Notification (IPN) từ MoMo
- **URL:** `http://yourdomain.com/payment/webhook.php`

### 4. **Callback Handler**
- **File:** `payment/callback.php`
- **Chức năng:** Xử lý return từ MoMo Gateway
- **URL:** `http://yourdomain.com/payment/callback.php`

### 5. **Trang kết quả**
- **Success:** `payment/success.php`
- **Failure:** `payment/failure.php`
- **Error:** `payment/error.php`

## Cấu hình

### 1. **Database Configuration**
Cập nhật bảng `payment_config` với thông tin MoMo:

```sql
INSERT INTO payment_config (payment_method, config_key, config_value, is_active) VALUES
('Momo', 'partner_code', 'YOUR_PARTNER_CODE', 1),
('Momo', 'access_key', 'YOUR_ACCESS_KEY', 1),
('Momo', 'secret_key', 'YOUR_SECRET_KEY', 1),
('Momo', 'endpoint', 'https://test-payment.momo.vn/v2/gateway/api/create', 1),
('Momo', 'return_url', 'http://yourdomain.com/payment/callback.php', 1),
('Momo', 'notify_url', 'http://yourdomain.com/payment/webhook.php', 1),
('Momo', 'qr_phone', '0123456789', 1),
('Momo', 'qr_name', 'YOUR_COMPANY_NAME', 1);
```

### 2. **Environment Setup**
- **Test Environment:** `https://test-payment.momo.vn/v2/gateway/api/create`
- **Production Environment:** `https://payment.momo.vn/v2/gateway/api/create`

## Cách sử dụng

### 1. **Thanh toán Online (Gateway)**
```javascript
// Tạo thanh toán MoMo
fetch('/src/controllers/payment.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        action: 'create_momo_payment',
        event_id: 21,
        amount: 50000000,
        payment_type: 'deposit'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Chuyển hướng đến MoMo Gateway
        window.location.href = data.pay_url;
    }
});
```

### 2. **Thanh toán Offline (QR Code)**
```javascript
// Tạo mã QR
fetch('/src/controllers/payment.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        action: 'create_payment',
        event_id: 21,
        amount: 50000000,
        payment_method: 'momo',
        payment_type: 'deposit'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Hiển thị mã QR
        displayQRCode(data.qr_code);
    }
});
```

### 3. **Kiểm tra trạng thái**
```javascript
// Kiểm tra trạng thái thanh toán
fetch('/src/controllers/payment.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        action: 'verify_momo_payment',
        order_id: 'EVENT_21_TXN20251026123456'
    })
})
.then(response => response.json())
.then(data => {
    console.log('Payment status:', data.status);
});
```

## Bảo mật

### 1. **Chữ ký số HMAC SHA256**
- Tất cả request đều được ký số bằng HMAC SHA256
- Webhook và callback đều được xác thực chữ ký

### 2. **Validation**
- Kiểm tra số tiền hợp lệ
- Xác thực quyền truy cập user
- Kiểm tra trạng thái sự kiện

### 3. **Logging**
- Log tất cả webhook và callback
- Log lỗi và exception
- Log lịch sử thanh toán

## Testing

Sử dụng file `test-payment-qr.php` để test các API:

```bash
# Truy cập: http://localhost/event/my-php-project/test-payment-qr.php
```

## Lưu ý

1. **Test Environment:** Sử dụng endpoint test cho development
2. **Production:** Cập nhật endpoint và credentials cho production
3. **Webhook URL:** Phải accessible từ internet
4. **SSL:** Sử dụng HTTPS cho production
5. **Rate Limiting:** Implement rate limiting cho API calls

## Troubleshooting

### 1. **Lỗi chữ ký**
- Kiểm tra secret_key trong config
- Đảm bảo raw data được format đúng

### 2. **Lỗi webhook**
- Kiểm tra URL webhook accessible
- Kiểm tra log file để debug

### 3. **Lỗi callback**
- Kiểm tra return_url trong config
- Kiểm tra session và authentication

## Tài liệu tham khảo

- [MoMo Payment Platform](https://github.com/momo-wallet/payment)
- [MoMo Developer Documentation](https://developers.momo.vn)
- [MoMo API Reference](https://developers.momo.vn/docs)
