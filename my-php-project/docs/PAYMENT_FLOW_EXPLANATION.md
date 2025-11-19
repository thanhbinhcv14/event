# Giải Thích Logic Trình Tự Thanh Toán

## 📋 Tổng Quan

Hệ thống thanh toán hỗ trợ 2 loại thanh toán:
- **Đặt cọc**: 30% tổng giá trị sự kiện
- **Thanh toán đủ**: 100% tổng giá trị sự kiện

**Quy tắc quan trọng**: Khách hàng **PHẢI** đặt cọc trước khi có thể thanh toán đủ.

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

## 📝 Chi Tiết Từng Bước

### **BƯỚC 1: TẠO THANH TOÁN (createPayment)**

#### 1.1. Validation Đầu Vào
```php
- Kiểm tra: event_id, amount, payment_method có đầy đủ không
- Kiểm tra: amount phải là số và > 0
- Kiểm tra: payment_method chỉ hỗ trợ 'sepay' hoặc 'cash'
```

#### 1.2. Kiểm Tra Quyền Truy Cập
```php
- Kiểm tra sự kiện có tồn tại không
- Kiểm tra sự kiện có thuộc về người dùng đang đăng nhập không
- Kiểm tra sự kiện đã được duyệt chưa (TrangThaiDuyet = 'Đã duyệt')
```

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

#### 1.4. Tạo Bản Ghi Thanh Toán
```php
- Tạo mã giao dịch: TXN + YmdHis + random(1000-9999)
- Lưu vào bảng thanhtoan với:
  * TrangThai = 'Đang xử lý'
  * LoaiThanhToan = 'Đặt cọc' hoặc 'Thanh toán đủ'
  * PhuongThuc = 'Chuyển khoản' (nếu sepay) hoặc 'Tiền mặt'
```

#### 1.5. Cập Nhật Trạng Thái Sự Kiện (Nếu Đặt Cọc)
```php
Nếu là đặt cọc VÀ trạng thái hiện tại chưa phải "Đã đặt cọc":
  → Cập nhật datlichsukien.TrangThaiThanhToan = 'Đã đặt cọc'
  → Mục đích: Ẩn nút thanh toán trong UI ngay lập tức
```

#### 1.6. Tính Toán Deadline (Nếu Đặt Cọc)
```php
Nếu là đặt cọc VÀ có NgayBatDau:
  - Deadline = NgayBatDau - 7 ngày
  - Trả về deadline_info trong response:
    * deadline_date: Ngày deadline (Y-m-d H:i:s)
    * deadline_formatted: Ngày deadline (d/m/Y)
    * days_until_deadline: Số ngày còn lại
    * is_past_deadline: Đã quá hạn chưa
    * is_approaching: Đang gần deadline (≤ 3 ngày)
```

#### 1.7. Tạo QR Code
```php
- SePay: Tạo URL VietQR với thông tin ngân hàng
- Tiền mặt: Tạo mã QR đơn giản
- Trả về qr_code và qr_data trong response
```

---

### **BƯỚC 2: XÁC NHẬN THANH TOÁN**

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
```php
Logic tương tự confirmCashPayment
- Cập nhật GhiChu với "Xác nhận chuyển khoản"
```

#### 2.3. Cập Nhật Trạng Thái Thủ Công (updatePaymentStatus)
```php
Input: payment_id, status, note (tùy chọn)

Nếu status = 'Thành công':
  - Cập nhật trạng thái sự kiện tương tự như trên
  
Nếu status = 'Thất bại' hoặc 'Đã hủy':
  - Kiểm tra xem có thanh toán thành công khác không
  - Nếu không có → Đặt lại TrangThaiThanhToan = 'Chưa thanh toán'
  - Nếu có → Giữ nguyên trạng thái hiện tại
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

## ⏰ Deadline Thanh Toán Đủ

### Quy Tắc Thanh Toán Dựa Trên Khoảng Cách Từ Đăng Ký Đến Tổ Chức

**Logic mới**: Quyết định cho phép đặt cọc hay bắt buộc thanh toán đủ dựa trên khoảng cách từ ngày đăng ký đến ngày tổ chức.

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

### Validation Khi Tạo Thanh Toán

```php
Khi tạo thanh toán đặt cọc:
  ✅ Nếu (Ngày tổ chức - Ngày đăng ký) < 7 ngày:
     → Lỗi: "Sự kiện này diễn ra trong vòng 7 ngày, bạn phải thanh toán đủ ngay. Không thể đặt cọc."
     → Không cho phép tạo thanh toán đặt cọc

Khi tạo thanh toán đủ:
  ✅ Nếu (Ngày tổ chức - Ngày đăng ký) ≥ 7 ngày:
     → Kiểm tra đã đặt cọc chưa (bắt buộc)
     → Kiểm tra deadline: Nếu hiện tại > (Ngày đặt cọc + 7 ngày):
        → Lỗi: "Đã quá hạn thanh toán đủ"
        → Thông báo: "Vui lòng đến công ty đóng tiền mặt trước khi sự kiện diễn ra"
        → Cảnh báo: "Nếu không, sự kiện sẽ bị hủy và không hoàn lại cọc"
  
  ✅ Nếu (Ngày tổ chức - Ngày đăng ký) < 7 ngày:
     → Cho phép thanh toán đủ ngay (không cần kiểm tra đặt cọc)
  
  ⚠️ Nếu còn ≤ 3 ngày đến deadline:
     → Cảnh báo nhưng vẫn cho phép thanh toán
     → Hiển thị cảnh báo trong UI
  
  ℹ️ Nếu còn > 3 ngày đến deadline:
     → Hiển thị thông tin deadline bình thường
```

### Tự Động Hủy Khi Quá Deadline

```php
Khi load danh sách sự kiện (get_my_events):
  1. Kiểm tra các sự kiện đã đặt cọc nhưng chưa thanh toán đủ
  2. Tính deadline cho mỗi sự kiện: Deadline = Ngày đặt cọc + 7 ngày
  3. Nếu hiện tại > deadline VÀ chưa đến ngày tổ chức:
     → Tự động hủy sự kiện (TrangThaiDuyet = 'Đã hủy')
     → Ghi chú: "Tự động hủy: Quá hạn thanh toán đủ (hạn: DD/MM/YYYY). Không hoàn lại cọc."
     → Hủy tất cả thanh toán đang chờ xử lý
     → Thông báo cho khách hàng: "Có X sự kiện đã quá hạn thanh toán đủ và đã bị tự động hủy. Tiền cọc không được hoàn lại."
```

### Hiển Thị Trong UI

```javascript
// Trong UI (my-events.php)

1. Sự kiện diễn ra < 7 ngày (RequiresFullPayment = true):
   - Alert vàng: "Sự kiện này diễn ra trong vòng 7 ngày (còn X ngày), bạn PHẢI thanh toán đủ ngay. Không thể đặt cọc."
   - Nút: Chỉ hiển thị "Thanh toán đủ" (màu xanh)
   - Modal: Disable option "Đặt cọc", chỉ cho phép "Thanh toán đủ"

2. Sự kiện đã đặt cọc (≥ 7 ngày):
   - Đã quá hạn: 
     Alert đỏ "Đã quá hạn thanh toán đủ!"
     "⚠️ QUAN TRỌNG: Vui lòng đến công ty đóng tiền mặt trước khi sự kiện diễn ra."
     "Nếu không thanh toán, sự kiện sẽ bị hủy và KHÔNG HOÀN LẠI CỌC."
   
   - Gần deadline (≤ 3 ngày): 
     Alert vàng "Cảnh báo: Còn X ngày nữa đến hạn thanh toán đủ (DD/MM/YYYY - 7 ngày sau khi đặt cọc)"
     "Nếu quá hạn, bạn phải đến công ty đóng tiền mặt và sự kiện sẽ bị hủy nếu không thanh toán."
   
   - Bình thường: 
     Alert xanh "Lưu ý: Hạn thanh toán đủ là DD/MM/YYYY (7 ngày sau khi đặt cọc)"
     "Còn X ngày để thanh toán. Nếu quá hạn, bạn phải đến công ty đóng tiền mặt và sự kiện sẽ bị hủy nếu không thanh toán."
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
        │  - Tạo QR code                  │
        └─────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  Nếu là "Đặt cọc":              │
        │  → Cập nhật sự kiện             │
        │     TrangThaiThanhToan =        │
        │     "Đã đặt cọc"                │
        │  → Tính deadline                │
        └─────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  KHÁCH HÀNG THỰC HIỆN THANH TOÁN│
        │  - Quét QR (SePay)              │
        │  - Hoặc nộp tiền mặt            │
        └─────────────────────────────────┘
                          │
                          ▼
        ┌─────────────────────────────────┐
        │  ADMIN XÁC NHẬN THANH TOÁN       │
        │  - confirmCashPayment           │
        │  - confirmBankingPayment        │
        │  - updatePaymentStatus          │
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
```

### Kiểm Tra Trạng Thái
```php
// Kiểm tra trạng thái thanh toán
GET: /src/controllers/payment.php?action=get_payment_status&payment_id=XXX

// Kiểm tra lịch sử thanh toán
GET: /src/controllers/payment.php?action=get_payment_history
```

---

## ✅ Checklist Khi Test

- [ ] Đặt cọc thành công → Trạng thái sự kiện = "Đã đặt cọc"
- [ ] Thanh toán đủ thành công → Trạng thái sự kiện = "Đã thanh toán đủ"
- [ ] Không thể thanh toán đủ nếu chưa đặt cọc
- [ ] Không thể thanh toán đủ nếu đã quá deadline
- [ ] Deadline được tính đúng (NgayBatDau - 7 ngày)
- [ ] Cảnh báo hiển thị khi gần deadline (≤ 3 ngày)
- [ ] Hủy thanh toán đặt cọc → Trạng thái về "Chưa thanh toán"
- [ ] Hủy thanh toán đủ (nhưng đã có đặt cọc) → Trạng thái về "Đã đặt cọc"
- [ ] Lịch sử thanh toán được ghi đầy đủ
- [ ] QR code được tạo đúng cho SePay và Tiền mặt

---

**Tài liệu này giải thích chi tiết logic trình tự thanh toán trong hệ thống. Nếu có thắc mắc, vui lòng tham khảo code trong `src/controllers/payment.php`.**

