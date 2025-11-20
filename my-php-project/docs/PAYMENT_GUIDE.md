# Hướng Dẫn Hệ Thống Thanh Toán

## 📋 Tổng Quan

Hệ thống thanh toán hỗ trợ 2 phương thức:
- **SePay Banking**: Thanh toán qua ngân hàng (chuyển khoản, QR code)
- **Tiền mặt**: Thanh toán trực tiếp tại công ty

Hệ thống hỗ trợ 2 loại thanh toán:
- **Đặt cọc**: 30% tổng giá trị sự kiện
- **Thanh toán đủ**: 100% tổng giá trị sự kiện

**Quy tắc quan trọng**: Khách hàng **PHẢI** đặt cọc trước khi có thể thanh toán đủ (trừ trường hợp đặc biệt).

---

## 🔄 Quy Trình Thanh Toán Tổng Thể

```
[Đăng ký sự kiện] 
    ↓
[Admin duyệt sự kiện] 
    ↓
[Khách hàng đặt cọc] → [Xác nhận đặt cọc] → [Trạng thái: "Đã đặt cọc"]
    ↓
[Khách hàng thanh toán đủ] → [Xác nhận thanh toán đủ] → [Trạng thái: "Đã thanh toán đủ"]
```

---

## 💳 Tích Hợp SePay

### Cấu Hình SePay

**File**: `config/sepay.php`

```php
// Merchant Information
SEPAY_PARTNER_CODE = 'SP-LIVE-BT953B7A'
SEPAY_SECRET_KEY = 'spsk_live_...'
SEPAY_API_TOKEN = 'BN3FCA9DRCGR6TTHY110...'

// Webhook Configuration
SEPAY_CALLBACK_URL = 'https://sukien.info.vn/hooks/sepay-payment.php'
SEPAY_WEBHOOK_TOKEN = 'BN3FCA9DRCGR6TTHY110...' // API Token cho webhook
SEPAY_IPN_SECRET_KEY = 'Thanhbinh1@' // Secret Key từ IPN config

// Environment
SEPAY_ENVIRONMENT = 'production' // hoặc 'sandbox'
```

### SePay PHP SDK

Hệ thống sử dụng **SePay PHP SDK chính thức** (`sepay/sepay-pg`):

```bash
composer require sepay/sepay-pg
```

**SDK được sử dụng để:**
- Tạo checkout URL (POST form)
- Generate form fields với signature tự động
- Query order details từ SePay API

### Tạo Checkout URL

**Function**: `createSePayCheckoutURL()` trong `src/controllers/payment.php`

```php
use SePay\SePayClient;
use SePay\Builders\CheckoutBuilder;

// Khởi tạo SePay Client
$sepay = new SePayClient(
    $partnerCode,
    $secretKey,
    SePayClient::ENVIRONMENT_PRODUCTION
);

// Tạo checkout data
$checkoutData = CheckoutBuilder::make()
    ->currency('VND')
    ->orderAmount(intval($amount))
    ->operation('PURCHASE')
    ->orderDescription($orderDescription)
    ->orderInvoiceNumber($orderInvoice)
    ->successUrl($baseUrl . '/payment/success.php')
    ->errorUrl($baseUrl . '/payment/error.php')
    ->cancelUrl($baseUrl . '/payment/failure.php')
    ->build();

// Generate form fields với signature
$formFields = $sepay->checkout()->generateFormFields($checkoutData);

// Lấy checkout URL
$checkoutUrl = $sepay->checkout()->getCheckoutUrl('production');
```

**Lưu ý quan trọng:**
- SePay yêu cầu **POST form**, không phải GET redirect
- SDK tự động tạo signature
- Checkout URL: `https://pay.sepay.vn/v1/checkout/init`

### Webhook Handler

**File**: `hooks/sepay-payment.php`

**Chức năng:**
- Nhận webhook từ SePay khi có giao dịch
- Xác thực webhook bằng API Token hoặc Secret Key
- Parse payment ID từ content (`SEPAY{eventId}{paymentId}`)
- Cập nhật trạng thái thanh toán trong database
- Ghi log vào `hook_log.txt` và database

**Xác thực Webhook:**
```php
// Header: Authorization: Apikey {TOKEN}
// Hoặc: Secret Key từ IPN config
```

**Content Format:**
- Pattern: `SEPAY{suffix}` với suffix = eventId + paymentId (3-10 ký tự)
- Ví dụ: `SEPAY2220` (eventId=22, paymentId=20)
- Fallback: Match theo amount nếu không parse được từ content

---

## 📝 Chi Tiết Từng Bước

### BƯỚC 1: TẠO THANH TOÁN (createPayment)

#### 1.1. Validation Đầu Vào
- Kiểm tra: `event_id`, `amount`, `payment_method` có đầy đủ không
- Kiểm tra: `amount` phải là số và > 0
- Kiểm tra: `payment_method` chỉ hỗ trợ `'sepay'` hoặc `'cash'`

#### 1.2. Kiểm Tra Quyền Truy Cập
- Kiểm tra sự kiện có tồn tại không
- Kiểm tra sự kiện có thuộc về người dùng đang đăng nhập không
- Kiểm tra sự kiện đã được duyệt chưa (`TrangThaiDuyet = 'Đã duyệt'`)

#### 1.3. Validation Cho Thanh Toán Đủ

**Nếu loại thanh toán là "Thanh toán đủ":**

```php
✅ Kiểm tra 1: Đã có thanh toán đặt cọc thành công chưa?
   - Query: COUNT(*) FROM thanhtoan 
            WHERE ID_DatLich = ? 
            AND LoaiThanhToan = 'Đặt cọc' 
            AND TrangThai = 'Thành công'
   - Nếu = 0 → Lỗi: "Bạn cần đặt cọc trước khi thanh toán đủ"

✅ Kiểm tra 2: Trạng thái sự kiện có phải "Đã đặt cọc" không?
   - Nếu không → Lỗi: "Trạng thái thanh toán không hợp lệ"

✅ Kiểm tra 3: Deadline thanh toán đủ
   - Deadline = NgayBatDau - 7 ngày
   - Nếu hiện tại > deadline → Lỗi: "Đã quá hạn thanh toán đủ"
   - Nếu còn ≤ 3 ngày → Cảnh báo (nhưng vẫn cho phép)
```

**Ngoại lệ:** Nếu sự kiện diễn ra trong vòng 7 ngày từ ngày đăng ký, cho phép thanh toán đủ ngay (không cần đặt cọc).

#### 1.4. Tạo Bản Ghi Thanh Toán
- Tạo mã giao dịch: `TXN` + `YmdHis` + random(1000-9999)
- Lưu vào bảng `thanhtoan` với:
  * `TrangThai = 'Đang xử lý'`
  * `LoaiThanhToan = 'Đặt cọc'` hoặc `'Thanh toán đủ'`
  * `PhuongThuc = 'Chuyển khoản'` (nếu sepay) hoặc `'Tiền mặt'`

#### 1.5. Tạo SePay Checkout URL (Nếu SePay)
- Sử dụng SePay SDK để tạo checkout URL
- Generate POST form với signature tự động
- Trả về `form_html` và `form_fields` để client-side submit

#### 1.6. Tạo QR Code (Fallback)
- Nếu không có checkout URL, tạo VietQR với thông tin ngân hàng
- Format content: `SEPAY{eventId}{paymentId}`

---

### BƯỚC 2: XÁC NHẬN THANH TOÁN

Có 3 cách xác nhận thanh toán:

#### 2.1. Xác Nhận Tiền Mặt (confirmCashPayment)
```php
Input: payment_id, confirm_note (tùy chọn)

1. Lấy thông tin thanh toán
2. Cập nhật thanhtoan.TrangThai = 'Thành công'
3. Cập nhật datlichsukien.TrangThaiThanhToan:
   - Nếu LoaiThanhToan = 'Đặt cọc' → 'Đã đặt cọc'
   - Nếu LoaiThanhToan = 'Thanh toán đủ' → 'Đã thanh toán đủ' (ghi đè)
4. Ghi log nếu chuyển từ "Đã đặt cọc" → "Đã thanh toán đủ"
5. Thêm lịch sử thanh toán
```

#### 2.2. Xác Nhận Chuyển Khoản (confirmBankingPayment)
Logic tương tự `confirmCashPayment`
- Cập nhật `GhiChu` với "Xác nhận chuyển khoản"

#### 2.3. Webhook Tự Động (SePay)
Khi SePay gửi webhook:
- Tự động cập nhật trạng thái thanh toán
- Parse payment ID từ content
- Verify amount và cập nhật database

---

## ⏰ Deadline Thanh Toán Đủ

### Quy Tắc Thanh Toán Dựa Trên Khoảng Cách

**Logic**: Quyết định cho phép đặt cọc hay bắt buộc thanh toán đủ dựa trên khoảng cách từ ngày đăng ký đến ngày tổ chức.

```
Nếu (Ngày tổ chức - Ngày đăng ký) < 7 ngày:
  → BẮT BUỘC thanh toán đủ ngay
  → KHÔNG CHO PHÉP đặt cọc
  
Nếu (Ngày tổ chức - Ngày đăng ký) ≥ 7 ngày:
  → Cho phép đặt cọc
  → Deadline thanh toán đủ = Ngày đặt cọc + 7 ngày (luôn luôn)
```

**Ví dụ 1**: Đăng ký gần ngày tổ chức (< 7 ngày)
- Đăng ký: 15/11/2024
- Ngày tổ chức: 20/11/2024
- Khoảng cách: 5 ngày (< 7 ngày)
- **Kết quả**: Bắt buộc thanh toán đủ ngay, không thể đặt cọc

**Ví dụ 2**: Đăng ký xa ngày tổ chức (≥ 7 ngày)
- Đăng ký: 01/11/2024
- Ngày tổ chức: 20/11/2024
- Khoảng cách: 19 ngày (≥ 7 ngày)
- **Kết quả**: Cho phép đặt cọc
  - Nếu đặt cọc: 05/11/2024
  - **Deadline thanh toán đủ: 12/11/2024** (05/11 + 7 ngày)

### Tự Động Hủy Khi Quá Deadline

```php
Khi load danh sách sự kiện (get_my_events):
  1. Kiểm tra các sự kiện đã đặt cọc nhưng chưa thanh toán đủ
  2. Tính deadline cho mỗi sự kiện: Deadline = Ngày đặt cọc + 7 ngày
  3. Nếu hiện tại > deadline VÀ chưa đến ngày tổ chức:
     → Tự động hủy sự kiện (TrangThaiDuyet = 'Đã hủy')
     → Ghi chú: "Tự động hủy: Quá hạn thanh toán đủ (hạn: DD/MM/YYYY). Không hoàn lại cọc."
     → Hủy tất cả thanh toán đang chờ xử lý
```

---

## 🎯 Logic Cập Nhật Trạng Thái Sự Kiện

### Quy Tắc Cập Nhật

| Loại Thanh Toán | Trạng Thái Thanh Toán | Trạng Thái Sự Kiện Mới |
|----------------|----------------------|----------------------|
| Đặt cọc | Thành công | **Đã đặt cọc** |
| Thanh toán đủ | Thành công | **Đã thanh toán đủ** (ghi đè) |
| Bất kỳ | Thất bại/Đã hủy | Chưa thanh toán (nếu không có thanh toán thành công khác) |
| Bất kỳ | Đang xử lý | Giữ nguyên |

### Đặc Biệt: Ghi Đè Trạng Thái

```php
Khi thanh toán đủ được xác nhận thành công:
  - Luôn đặt TrangThaiThanhToan = 'Đã thanh toán đủ'
  - Ghi đè trạng thái "Đã đặt cọc" nếu có
  - Ghi log để theo dõi: "Event #X moved from 'Đã đặt cọc' to 'Đã thanh toán đủ'"
```

---

## 🔐 Bảo Mật và Validation

### CSRF Protection
```php
- Các action thay đổi dữ liệu yêu cầu CSRF token:
  * create_payment
  * update_payment_status
  * confirm_cash_payment
  * confirm_banking_payment
  * cancel_payment

- Các action chỉ đọc không yêu cầu CSRF:
  * get_payment_history
  * check_payment_status
  * get_payment_status
  * verify_payment
```

### Kiểm Tra Quyền
```php
- Chỉ chủ sở hữu sự kiện mới có thể thanh toán
- Sự kiện phải được duyệt trước khi thanh toán
- Mỗi thanh toán được gắn với user_id thông qua khachhanginfo
```

---

## 📊 Sơ Đồ Luồng Thanh Toán

```
┌─────────────────────────────────────────────────────────────┐
│  KHÁCH HÀNG TẠO THANH TOÁN (createPayment)                  │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  Validation Đầu Vào             │
        │  - Kiểm tra dữ liệu             │
        │  - Kiểm tra quyền               │
        │  - Kiểm tra sự kiện đã duyệt    │
        └─────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  Kiểm tra khoảng cách:          │
        │  (Ngày tổ chức - Ngày đăng ký)  │
        └─────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  Nếu < 7 ngày:                   │
        │  ✅ Không cho đặt cọc            │
        │  ✅ Chỉ cho thanh toán đủ        │
        └─────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  Nếu ≥ 7 ngày:                   │
        │  ✅ Cho phép đặt cọc             │
        │  ✅ Nếu thanh toán đủ:           │
        │     - Đã đặt cọc chưa?          │
        │     - Chưa quá deadline?        │
        │       (Đặt cọc + 7 ngày)         │
        └─────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  Tạo Bản Ghi Thanh Toán         │
        │  - Mã giao dịch                 │
        │  - Trạng thái: "Đang xử lý"    │
        │  - Tạo SePay Checkout URL      │
        │    hoặc QR code                 │
        └─────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  KHÁCH HÀNG THỰC HIỆN THANH TOÁN│
        │  - Submit POST form (SePay)     │
        │  - Hoặc quét QR (SePay)         │
        │  - Hoặc nộp tiền mặt            │
        └─────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  XÁC NHẬN THANH TOÁN            │
        │  - Webhook tự động (SePay)      │
        │  - Admin xác nhận (Tiền mặt)    │
        └─────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  CẬP NHẬT TRẠNG THÁI            │
        │  - thanhtoan.TrangThai =        │
        │    "Thành công"                 │
        │  - datlichsukien.TrangThaiThanhToan:│
        │    * "Đã đặt cọc" (nếu đặt cọc) │
        │    * "Đã thanh toán đủ" (nếu đủ)│
        │  - Ghi log                      │
        │  - Thêm lịch sử                 │
        └─────────────────────────────────┘
```

---

## 🎯 Các Trường Hợp Đặc Biệt

### 1. Thanh Toán Thất Bại/Hủy
```php
Nếu thanh toán bị hủy hoặc thất bại:
  1. Kiểm tra xem có thanh toán thành công khác không
  2. Nếu không có → Đặt lại TrangThaiThanhToan = 'Chưa thanh toán'
  3. Nếu có → Giữ nguyên trạng thái hiện tại
```

### 2. Nhiều Thanh Toán Cùng Lúc
```php
- Hệ thống cho phép nhiều thanh toán "Đang xử lý" cùng lúc
- Chỉ thanh toán đầu tiên thành công sẽ cập nhật trạng thái sự kiện
- Các thanh toán khác vẫn được lưu trong lịch sử
```

### 3. Thanh Toán Đủ Trước Deadline
```php
- Nếu thanh toán đủ được tạo trước deadline nhưng xác nhận sau deadline:
  → Vẫn được chấp nhận (đã tạo trước deadline)
- Chỉ kiểm tra deadline khi TẠO thanh toán, không kiểm tra khi XÁC NHẬN
```

---

## 📝 Lưu Ý Quan Trọng

1. **Quy tắc thanh toán dựa trên khoảng cách**:
   - **< 7 ngày**: Bắt buộc thanh toán đủ ngay, KHÔNG CHO PHÉP đặt cọc
   - **≥ 7 ngày**: Cho phép đặt cọc, sau đó có 7 ngày để thanh toán đủ

2. **Deadline thanh toán đủ**: 
   - Luôn luôn = Ngày đặt cọc + 7 ngày (không phụ thuộc vào ngày tổ chức)
   - Ví dụ: Đặt cọc 01/11 → Deadline: 08/11 (luôn luôn)

3. **Quá deadline phải đóng tiền mặt**: 
   - Nếu quá deadline, khách hàng phải đến công ty đóng tiền mặt
   - Không thể thanh toán online nữa

4. **Tự động hủy nếu quá deadline**: 
   - Hệ thống tự động hủy sự kiện nếu quá deadline và chưa thanh toán đủ
   - Chỉ hủy nếu chưa đến ngày tổ chức

5. **Không hoàn lại cọc**: 
   - Nếu sự kiện bị hủy do quá deadline, tiền cọc KHÔNG được hoàn lại
   - Ghi chú rõ ràng trong database và thông báo cho khách hàng

6. **Ghi đè trạng thái**: 
   - Thanh toán đủ luôn ghi đè trạng thái "Đã đặt cọc"

7. **Transaction safety**: 
   - Tất cả cập nhật đều dùng database transaction để đảm bảo tính nhất quán

8. **Lịch sử đầy đủ**: 
   - Mọi thay đổi trạng thái đều được ghi vào `payment_history`

---

## 🔍 Debug và Logging

### Log Quan Trọng
```php
// Khi chuyển từ "Đã đặt cọc" → "Đã thanh toán đủ"
error_log("Payment progression: Event #{$eventId} moved from 'Đã đặt cọc' to 'Đã thanh toán đủ' via [method] #{$paymentId}");

// SePay Checkout URL
error_log("SePay Checkout URL created using official SDK for merchant: {$partnerCode}");
error_log("SePay Checkout URL: " . substr($checkoutURL, 0, 200) . "...");

// Webhook processing
error_log("Webhook processed successfully for payment ID: {$paymentId}");
```

### Kiểm Tra Trạng Thái
```php
// Kiểm tra trạng thái thanh toán
GET: /src/controllers/payment.php?action=get_payment_status&payment_id=XXX

// Kiểm tra lịch sử thanh toán
GET: /src/controllers/payment.php?action=get_payment_history

// Query SePay order detail
POST: /src/controllers/payment.php?action=get_sepay_order_detail
Body: { "order_id": "INV-..." }
```

---

## ✅ Checklist Khi Test

- [ ] Đặt cọc thành công → Trạng thái sự kiện = "Đã đặt cọc"
- [ ] Thanh toán đủ thành công → Trạng thái sự kiện = "Đã thanh toán đủ"
- [ ] Không thể thanh toán đủ nếu chưa đặt cọc (trừ trường hợp < 7 ngày)
- [ ] Không thể thanh toán đủ nếu đã quá deadline
- [ ] Deadline được tính đúng (Ngày đặt cọc + 7 ngày)
- [ ] Cảnh báo hiển thị khi gần deadline (≤ 3 ngày)
- [ ] Hủy thanh toán đặt cọc → Trạng thái về "Chưa thanh toán"
- [ ] Hủy thanh toán đủ (nhưng đã có đặt cọc) → Trạng thái về "Đã đặt cọc"
- [ ] Lịch sử thanh toán được ghi đầy đủ
- [ ] SePay Checkout URL được tạo đúng và submit POST form thành công
- [ ] Webhook tự động cập nhật trạng thái khi có giao dịch

---

## 📞 Hỗ Trợ

Nếu gặp vấn đề, kiểm tra:
1. **Logs**: `hooks/hook_log.txt` và PHP error log
2. **Database**: Bảng `thanhtoan`, `payment_history`, `webhook_logs`
3. **SePay Dashboard**: IPN URL, Auth Type, Secret Key
4. **Code**: `src/controllers/payment.php`, `hooks/sepay-payment.php`

---

**Tài liệu này giải thích chi tiết hệ thống thanh toán. Nếu có thắc mắc, vui lòng tham khảo code trong `src/controllers/payment.php` và `hooks/sepay-payment.php`.**

