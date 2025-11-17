# Hướng dẫn Setup SePay Webhook theo tài liệu chính thức

## Bước 1: Tạo bảng `tb_transactions` trong database

Bảng này sẽ lưu tất cả giao dịch từ SePay Webhook theo mẫu từ [tài liệu SePay](https://docs.sepay.vn/lap-trinh-webhooks.html).

### Cách 1: Chạy SQL script (Khuyến nghị)

```sql
-- Chạy file: database/sepay_transactions.sql
-- Hoặc chạy trực tiếp trong phpMyAdmin
```

### Cách 2: Chạy SQL trực tiếp

```sql
USE event; -- Thay 'event' bằng tên database của bạn

CREATE TABLE IF NOT EXISTS `tb_transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `gateway` varchar(100) NOT NULL,
    `transaction_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    `account_number` varchar(100) DEFAULT NULL,
    `sub_account` varchar(250) DEFAULT NULL,
    `amount_in` decimal(20,2) NOT NULL DEFAULT 0.00,
    `amount_out` decimal(20,2) NOT NULL DEFAULT 0.00,
    `accumulated` decimal(20,2) NOT NULL DEFAULT 0.00,
    `code` varchar(250) DEFAULT NULL,
    `transaction_content` text DEFAULT NULL,
    `reference_number` varchar(255) DEFAULT NULL,
    `body` text DEFAULT NULL,
    `transfer_type` enum('in','out') DEFAULT NULL,
    `transfer_amount` decimal(20,2) DEFAULT NULL,
    `sepay_transaction_id` varchar(100) DEFAULT NULL,
    `payment_id` int(11) DEFAULT NULL,
    `processed` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_account_number` (`account_number`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_transaction_content` (`transaction_content`(255)),
    KEY `idx_payment_id` (`payment_id`),
    KEY `idx_processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `tb_transactions`
  ADD CONSTRAINT `fk_transaction_payment` FOREIGN KEY (`payment_id`) REFERENCES `thanhtoan` (`ID_ThanhToan`) ON DELETE SET NULL;
```

## Bước 2: Cấu hình Webhook trên SePay

1. Đăng nhập vào [SePay](https://my.sepay.vn)
2. Vào menu **Tích hợp WebHooks**
3. Nhấn **"+ Thêm webhooks"**
4. Điền thông tin:
   - **Đặt tên**: Tên webhook (ví dụ: "Xác thực thanh toán")
   - **Chọn sự kiện**: "Có tiền vào"
   - **Chọn điều kiện**: Chọn tài khoản ngân hàng của bạn
   - **Gọi đến URL**: `https://sukien.info.vn/hooks/sepay-payment.php`
   - **Là WebHooks xác thực thanh toán?**: "Đúng"
   - **Kiểu chứng thực**: "API Key"
   - **API Key**: `BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP`
   - **Request Content type**: `application/json`
   - **Trạng thái**: "Kích hoạt"
5. Nhấn **"Thêm"** để lưu

## Bước 3: Kiểm tra Webhook hoạt động

### Cách 1: Giả lập giao dịch (Khuyến nghị)

1. Đăng nhập vào SePay
2. Vào menu **Giao dịch** → **Giả lập giao dịch**
3. Chọn đúng **Tài khoản ngân hàng** đã cấu hình webhook
4. Nhập thông tin:
   - **Số tiền**: Số tiền cần test
   - **Nội dung chuyển khoản**: `SK{eventId}_SEPAY_{timestamp}_{random}` (lấy từ QR code)
   - **Loại**: Tiền vào (in)
5. Nhấn **"Tạo giả lập"**

### Cách 2: Kiểm tra trong SePay

1. Vào menu **Giao dịch** → Chọn biểu tượng **Pay** tại cột **Tự động**
2. Hoặc vào **Nhật ký WebHooks** để xem log

## Bước 4: Kiểm tra dữ liệu đã được lưu

### Kiểm tra bảng `tb_transactions`

```sql
USE event;

-- Xem tất cả giao dịch
SELECT * FROM tb_transactions ORDER BY created_at DESC;

-- Xem giao dịch chưa xử lý
SELECT * FROM tb_transactions WHERE processed = 0;

-- Xem giao dịch đã xử lý
SELECT * FROM tb_transactions WHERE processed = 1;

-- Xem giao dịch theo payment_id
SELECT * FROM tb_transactions WHERE payment_id = ?;

-- Thống kê giao dịch
SELECT 
    transfer_type,
    COUNT(*) as total,
    SUM(amount_in) as total_in,
    SUM(amount_out) as total_out,
    SUM(accumulated) as total_accumulated
FROM tb_transactions
GROUP BY transfer_type;
```

### Kiểm tra bảng `webhook_logs`

```sql
-- Xem tất cả webhook logs
SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT 10;

-- Xem webhook chưa xử lý
SELECT * FROM webhook_logs WHERE processed = 0;
```

### Kiểm tra file log

- File log: `hooks/hook_log.txt`
- Xem log real-time: `tail -f hooks/hook_log.txt` (Linux/Mac)

## Cấu trúc dữ liệu Webhook từ SePay

Theo [tài liệu SePay](https://docs.sepay.vn/lap-trinh-webhooks.html), webhook gửi JSON với các trường:

```json
{
  "gateway": "VietinBank",
  "transactionDate": "2024-01-01T10:00:00",
  "accountNumber": "100872918542",
  "subAccount": null,
  "transferType": "in",
  "transferAmount": 1000000,
  "accumulated": 5000000,
  "code": null,
  "content": "SK20_SEPAY_1762094590_1284",
  "referenceCode": "REF123",
  "description": "Thanh toan QR",
  "id": "sepay_transaction_id"
}
```

## Quy trình xử lý Webhook

1. **Nhận webhook** → Lưu vào `tb_transactions` (tất cả giao dịch)
2. **Parse content** → Tìm payment ID từ nội dung chuyển khoản
3. **Tìm payment** → Tìm payment record trong `thanhtoan`
4. **Xác minh số tiền** → Kiểm tra số tiền có khớp không
5. **Cập nhật trạng thái**:
   - Payment: `Đang xử lý` → `Thành công`
   - Event: Cập nhật `TrangThaiThanhToan`
   - `tb_transactions`: `processed = 1`

## Troubleshooting

### Webhook không được gọi
- ✅ Kiểm tra URL webhook trong SePay config có đúng không
- ✅ Kiểm tra webhook có được kích hoạt không
- ✅ Kiểm tra tài khoản ngân hàng có đúng không
- ✅ Kiểm tra firewall/server có chặn request từ SePay không

### Webhook bị lỗi 401 Unauthorized
- ✅ Kiểm tra API Key trong `config/sepay.php` có đúng không
- ✅ Kiểm tra SePay có gửi header `Authorization: Apikey {API_KEY}` không
- ✅ Xem log trong `hooks/hook_log.txt`

### Không tìm thấy Payment
- ✅ Kiểm tra nội dung chuyển khoản có đúng format `SK{eventId}_{paymentId}` không
- ✅ Kiểm tra `MaGiaoDich` trong database có khớp không
- ✅ Xem log parsing trong `hooks/hook_log.txt`

### Dữ liệu không được lưu vào `tb_transactions`
- ✅ Kiểm tra bảng `tb_transactions` đã được tạo chưa
- ✅ Kiểm tra quyền database user
- ✅ Xem error log trong PHP error log

## Tài liệu tham khảo

- [SePay Webhooks Documentation](https://docs.sepay.vn/lap-trinh-webhooks.html)
- [SePay Laravel Package](https://github.com/sepayvn/laravel-sepay)

