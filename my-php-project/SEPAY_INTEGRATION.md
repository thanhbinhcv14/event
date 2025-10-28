# SePay Integration Guide

## Tích hợp SePay cho thanh toán ngân hàng

### 1. Cấu hình SePay

1. **Cấu hình đã hoàn thành:**
   - File `config/sepay.php` đã được cấu hình với credentials production
   - Partner Code: `SP-LIVE-BT953B7A`
   - Secret Key: `spsk_live_dpzV8LVbzmCuMswSbVdQitHANPatgLLn`
   - Environment: `production`

2. **Cập nhật Callback URL:**
   - Trong SePay Dashboard, cập nhật Callback URL thành: `https://yourdomain.com/event/my-php-project/payment/sepay-callback.php`
   - Thay `yourdomain.com` bằng domain thực của bạn

### 2. Cách sử dụng

#### Trong Frontend (payment.php):
```javascript
// Khi người dùng chọn SePay
if (selectedMethod === 'SePay') {
    showSePayForm(paymentId, amount);
}
```

#### Trong Backend (payment controller):
```php
// Tạo thanh toán SePay
case 'create_sepay_payment':
    createSePayPayment();
    break;

// Lấy form SePay
case 'get_sepay_form':
    getSePayForm();
    break;

// Xử lý callback
case 'sepay_callback':
    processSePayCallback();
    break;
```

### 3. Luồng thanh toán SePay

1. **Người dùng chọn SePay** → Hiển thị modal với form SePay
2. **Click "Thanh toán qua ngân hàng"** → Chuyển đến trang SePay
3. **Hoàn tất thanh toán** → SePay gửi callback về `sepay-callback.php`
4. **Cập nhật trạng thái** → Hệ thống cập nhật trạng thái thanh toán

### 4. Cấu trúc file

```
my-php-project/
├── config/
│   └── sepay.php                 # Cấu hình SePay
├── vendor/
│   └── sepay/
│       ├── SePayClient.php       # Client chính
│       ├── CheckoutService.php   # Service checkout
│       ├── Builders/
│       │   └── CheckoutBuilder.php
│       └── autoload.php          # Autoloader
├── payment/
│   └── sepay-callback.php        # Xử lý callback
└── src/controllers/
    └── payment.php               # Controller chính (đã cập nhật)
```

### 5. Testing

1. **Sandbox Mode:**
   - Sử dụng test credentials
   - Kiểm tra callback hoạt động
   - Test các trường hợp thành công/thất bại

2. **Production Mode:**
   - Cập nhật credentials thực
   - Đảm bảo callback URL đúng
   - Test với số tiền nhỏ trước

### 6. Troubleshooting

#### Lỗi thường gặp:

1. **"AdminPanel không được tải":**
   - Kiểm tra admin-script.js có load đúng không
   - Kiểm tra console browser có lỗi JavaScript không

2. **"SePay API Error":**
   - Kiểm tra credentials trong config/sepay.php
   - Kiểm tra network connection
   - Kiểm tra callback URL

3. **"Payment not found":**
   - Kiểm tra MaGiaoDich có đúng format không
   - Kiểm tra database connection

### 7. Security Notes

- **Không commit credentials** vào Git
- **Sử dụng HTTPS** cho production
- **Validate signature** trong callback
- **Log tất cả transactions** để audit

### 8. API Reference

#### SePayClient Methods:
- `checkout()` - Tạo checkout service
- `getPartnerCode()` - Lấy partner code
- `getSecretKey()` - Lấy secret key
- `getBaseUrl()` - Lấy base URL

#### CheckoutBuilder Methods:
- `paymentMethod($method)` - Set phương thức thanh toán
- `currency($currency)` - Set loại tiền tệ
- `orderInvoiceNumber($number)` - Set mã đơn hàng
- `orderAmount($amount)` - Set số tiền
- `operation($operation)` - Set loại giao dịch
- `orderDescription($description)` - Set mô tả
- `build()` - Tạo data array

#### CheckoutService Methods:
- `generateFormHtml($data)` - Tạo HTML form
- `createPayment($data)` - Tạo payment qua API
