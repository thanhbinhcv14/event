# SePay Integration Guide

## Tích hợp SePay cho quản lý tài khoản ngân hàng

### 1. Hiểu về SePay

**SePay KHÔNG phải là Payment Gateway** như MoMo hay VNPay. SePay là:
- **Dịch vụ quản lý tài khoản ngân hàng**
- **Nhận thông báo giao dịch** qua webhook
- **Tạo QR Code** để khách hàng chuyển khoản
- **Theo dõi số dư** real-time

### 2. Cấu hình SePay

1. **Cấu hình đã hoàn thành:**
   - File `config/sepay.php` đã được cấu hình với credentials production
   - Partner Code: `SP-LIVE-BT953B7A`
   - Secret Key: `spsk_live_dpzV8LVbzmCuMswSbVdQitHANPatgLLn`
   - Environment: `production`

2. **API Endpoint:**
   - Base URL: `https://my.sepay.vn/userapi`
   - Bank Accounts API: `/bankaccounts/list`
   - Webhook URL: `https://yourdomain.com/event/my-php-project/payment/sepay-callback.php`

### 3. Cách sử dụng SePay

#### Luồng thanh toán với SePay:
1. **Khách hàng chọn SePay** → Hiển thị thông tin chuyển khoản
2. **Hiển thị QR Code** → Khách hàng quét để chuyển khoản
3. **SePay gửi webhook** → Hệ thống tự động xác nhận thanh toán
4. **Cập nhật trạng thái** → Sự kiện được xác nhận

#### Trong Frontend (payment.php):
```javascript
// Khi người dùng chọn SePay
if (selectedMethod === 'SePay') {
    showBankTransferInfo(paymentId, amount);
}
```

#### Trong Backend (payment controller):
```php
// Tạo thanh toán SePay (chuyển khoản)
case 'create_sepay_payment':
    createSePayPayment();
    break;

// Xử lý webhook từ SePay
case 'sepay_callback':
    processSePayCallback();
    break;
```

### 4. API SePay

Theo [tài liệu chính thức](https://docs.sepay.vn/api-tai-khoan-ngan-hang.html):

#### Lấy danh sách tài khoản:
```
GET https://my.sepay.vn/userapi/bankaccounts/list
```

#### Lấy chi tiết tài khoản:
```
GET https://my.sepay.vn/userapi/bankaccounts/details/{bank_account_id}
```

#### Đếm số tài khoản:
```
GET https://my.sepay.vn/userapi/bankaccounts/count
```

### 5. Cấu trúc file

```
my-php-project/
├── config/
│   └── sepay.php                 # Cấu hình SePay
├── vendor/
│   └── sepay/
│       ├── SePayClient.php       # Client chính
│       ├── CheckoutService.php   # Service checkout (không dùng)
│       └── autoload.php          # Autoloader
├── payment/
│   └── sepay-callback.php        # Xử lý webhook
└── src/controllers/
    └── payment.php               # Controller thanh toán
```

### 6. Lưu ý quan trọng

- **SePay không xử lý thanh toán** trực tiếp
- **Chỉ nhận thông báo** khi có tiền vào tài khoản
- **Cần cấu hình webhook** để nhận thông báo
- **QR Code** được tạo để khách hàng chuyển khoản
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
