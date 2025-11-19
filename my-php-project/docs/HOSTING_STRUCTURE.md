# Cấu trúc Hosting - sukien.info.vn

## Cấu trúc thư mục trên hosting

```
/home/vhieqivuhosting/public_html/
├── .env                    # File cấu hình môi trường (DB, API keys)
├── .htaccess              # Apache config cho root
├── index.php             # File chính
├── config/
│   ├── config.php        # Cấu hình chung
│   ├── database.php      # Kết nối database
│   └── sepay.php         # Cấu hình SePay
├── hooks/
│   ├── .htaccess         # Apache config cho webhook (Authorization header)
│   ├── sepay-payment.php # Webhook handler chính
│   ├── hook_log.txt      # Log file (tự động tạo)
│   └── check-paths.php   # File kiểm tra đường dẫn
├── payment/
│   ├── payment.php       # Trang thanh toán
│   ├── success.php        # Trang thành công
│   └── failure.php       # Trang thất bại
├── src/
│   └── controllers/
│       └── payment.php    # Payment controller
└── database/
    └── event.sql         # Database schema
```

## Đường dẫn quan trọng

### 1. Webhook URL
```
https://sukien.info.vn/hooks/sepay-payment.php
```

### 2. File log webhook
```
/home/vhieqivuhosting/public_html/hooks/hook_log.txt
```

### 3. Cấu hình SePay
- File: `/home/vhieqivuhosting/public_html/config/sepay.php`
- Webhook URL: `https://sukien.info.vn/hooks/sepay-payment.php`
- Pattern: `SEPAY` (suffix 3-10 ký tự)

## Kiểm tra cấu trúc

### 1. Truy cập file kiểm tra
```
https://sukien.info.vn/hooks/check-paths.php
```

File này sẽ kiểm tra:
- ✓ Đường dẫn các file config
- ✓ Quyền ghi file log
- ✓ Kết nối database
- ✓ Cấu hình SePay
- ✓ Webhook URL
- ✓ .htaccess files

### 2. Kiểm tra webhook endpoint
```
https://sukien.info.vn/hooks/sepay-payment.php?test=1
```

## Cấu hình cần thiết

### 1. File .env (nếu chưa có)
Tạo file `.env` trong `/home/vhieqivuhosting/public_html/`:

```env
DB_HOST=localhost
DB_NAME=vhieqivuhosting_event
DB_USER=vhieqivuhosting_user
DB_PASS=your_password
JWT_SECRET=your_jwt_secret
```

### 2. Quyền file log
Đảm bảo thư mục `hooks/` có quyền ghi:
```bash
chmod 755 /home/vhieqivuhosting/public_html/hooks/
chmod 666 /home/vhieqivuhosting/public_html/hooks/hook_log.txt
```

### 3. SePay Dashboard
- **IPN URL**: `https://sukien.info.vn/hooks/sepay-payment.php`
- **IPN Status**: Bật
- **Auth Type**: Secret Key hoặc API Token
- **Secret Key**: `Thanhbinh1@` (nếu Auth Type = Secret Key)
- **API Token**: `BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP`

## Xử lý sự cố

### 1. Webhook không nhận được
- Kiểm tra log: `https://sukien.info.vn/hooks/hook_log.txt`
- Kiểm tra cấu hình SePay Dashboard
- Kiểm tra firewall/security settings

### 2. Lỗi kết nối database
- Kiểm tra file `.env` có đúng thông tin không
- Kiểm tra database user có quyền truy cập không

### 3. Lỗi Authorization header
- Kiểm tra `.htaccess` trong thư mục `hooks/`
- Đảm bảo mod_rewrite và mod_setenvif đã bật

## Ghi chú

- Tất cả đường dẫn sử dụng `__DIR__` sẽ tự động điều chỉnh theo vị trí file
- File log `hook_log.txt` sẽ tự động tạo khi webhook được gọi
- Đảm bảo thư mục `hooks/` có quyền ghi để tạo file log

