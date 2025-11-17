# Hướng dẫn Test Webhook SePay

## ⚠️ Lưu ý quan trọng

**Webhook KHÔNG được gọi khi:**
- Tạo QR code thanh toán
- Nhấn nút "Xác nhận thanh toán" trên hệ thống
- Tạo payment record trong database

**Webhook CHỈ được gọi khi:**
- Khách hàng **thực sự chuyển tiền** vào tài khoản ngân hàng của SePay
- SePay phát hiện có tiền vào tài khoản và tự động gọi webhook

## Cách Test Webhook

### Bước 1: Tạo Payment và QR Code
1. Đăng nhập vào hệ thống
2. Chọn sự kiện cần thanh toán
3. Nhấn "Xác nhận thanh toán"
4. Hệ thống sẽ tạo payment record với:
   - `MaGiaoDich`: `SEPAY_{timestamp}_{random}`
   - `TrangThai`: `Đang xử lý`
   - Nội dung chuyển khoản: `SK{eventId}_SEPAY_{timestamp}_{random}`

### Bước 2: Giả lập giao dịch (Theo tài liệu SePay)
1. Đăng nhập vào tài khoản SePay: https://my.sepay.vn
2. Vào menu **Giao dịch** → **Giả lập giao dịch**
3. Chọn đúng **Tài khoản ngân hàng** đã cấu hình webhook
4. Nhập thông tin:
   - **Số tiền**: Số tiền của payment đã tạo
   - **Nội dung chuyển khoản**: `SK{eventId}_SEPAY_{timestamp}_{random}` (lấy từ QR code)
   - **Loại**: Tiền vào (in)
5. Nhấn "Tạo giả lập"

### Bước 3: Kiểm tra Webhook
1. Sau khi tạo giả lập, SePay sẽ tự động gọi webhook
2. Kiểm tra log webhook:
   - File log: `hooks/hook_log.txt`
   - Database: Bảng `webhook_logs`
3. Kiểm tra payment status:
   - Payment status sẽ tự động chuyển từ "Đang xử lý" → "Thành công"
   - Event status sẽ được cập nhật

### Bước 4: Xem kết quả trong SePay
1. Vào menu **Giao dịch** → Chọn biểu tượng **Pay** tại cột **Tự động**
2. Hoặc vào **Nhật ký WebHooks** để xem log webhook

## Cấu hình Webhook hiện tại

- **URL**: `https://sukien.info.vn/hooks/sepay-payment.php`
- **API Key**: `BN3FCA9DRCGR6TTHY110MIEYIKPANZBI8QZO9W0KXOEQISYSWDLMPWLFQX6HSPJP`
- **Authentication**: API Key (Header: `Authorization: Apikey {API_KEY}`)
- **Content Type**: `application/json`
- **Pattern**: `SK` (tìm nội dung chuyển khoản có format `SK{eventId}_{paymentId}`)

## Format dữ liệu Webhook từ SePay

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

## Xử lý Webhook trong hệ thống

1. **Xác thực**: Kiểm tra API Key trong header `Authorization: Apikey {API_KEY}`
2. **Parse Content**: Tìm payment ID từ nội dung chuyển khoản (format: `SK{eventId}_{paymentId}`)
3. **Tìm Payment**: Tìm payment record trong database theo `MaGiaoDich`
4. **Xác minh số tiền**: Kiểm tra số tiền nhận được có khớp với payment không
5. **Cập nhật trạng thái**: 
   - Payment: `Đang xử lý` → `Thành công`
   - Event: Cập nhật `TrangThaiThanhToan` nếu cần

## Troubleshooting

### Webhook không được gọi
- ✅ Kiểm tra URL webhook trong SePay config có đúng không
- ✅ Kiểm tra webhook có được kích hoạt (Status: Kích hoạt) không
- ✅ Kiểm tra tài khoản ngân hàng có đúng với webhook config không
- ✅ Kiểm tra firewall/server có chặn request từ SePay không

### Webhook bị lỗi 401 Unauthorized
- ✅ Kiểm tra API Key trong `config/sepay.php` có đúng không
- ✅ Kiểm tra SePay có gửi header `Authorization: Apikey {API_KEY}` không
- ✅ Xem log trong `hooks/hook_log.txt` để debug

### Không tìm thấy Payment
- ✅ Kiểm tra nội dung chuyển khoản có đúng format `SK{eventId}_{paymentId}` không
- ✅ Kiểm tra `MaGiaoDich` trong database có khớp với payment ID trong content không
- ✅ Xem log parsing trong `hooks/hook_log.txt`

## Tài liệu tham khảo

- [SePay Webhooks Documentation](https://docs.sepay.vn/lap-trinh-webhooks.html)
- [SePay Laravel Package](https://github.com/sepayvn/laravel-sepay)

