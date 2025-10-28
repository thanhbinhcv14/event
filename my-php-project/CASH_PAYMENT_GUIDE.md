# 💰 Hướng dẫn sử dụng hệ thống thanh toán tiền mặt

## 🔧 Các vấn đề đã được sửa

### 1. **Lỗi SQL Column not found**
- ✅ Đã loại bỏ các cột `MaQR` và `QRCodeData` không tồn tại
- ✅ Cập nhật tất cả queries để chỉ sử dụng các cột có sẵn

### 2. **Lỗi Parameter mismatch**
- ✅ Sửa JavaScript để gửi đúng tên parameters
- ✅ Cập nhật controller để nhận đúng parameters
- ✅ Thêm mapping cho ZaloPay

### 3. **Cập nhật thông tin địa chỉ**
- ✅ Địa chỉ chính xác: 123 Đường Nguyễn Huệ, Quận 1, TP.HCM
- ✅ Số điện thoại: (028) 1234-5678
- ✅ Giờ làm việc chi tiết

## 🚀 Cách test hệ thống

### **Bước 1: Test tạo thanh toán**
1. Truy cập: `http://localhost/event/my-php-project/test-cash-payment.php`
2. Kiểm tra kết quả test

### **Bước 2: Test thực tế**
1. Tạo sự kiện mới
2. Chọn thanh toán tiền mặt
3. Xem thông tin địa chỉ công ty
4. Tạo thanh toán
5. Vào admin panel để xác nhận

## 📋 Quy trình hoạt động

### **Người dùng:**
1. Chọn "Tiền mặt" → Hiển thị địa chỉ công ty
2. Click "Tiến hành thanh toán" → Chuyển đến trang chờ
3. Đến văn phòng: 123 Đường Nguyễn Huệ, Quận 1, TP.HCM
4. Thanh toán trực tiếp → Nhận biên lai

### **Quản lý:**
1. Vào Admin → Quản lý thanh toán
2. Tìm thanh toán tiền mặt "Đang xử lý"
3. Click nút xanh "Xác nhận thanh toán tiền mặt"
4. Điền ghi chú → Tick checkbox → Xác nhận
5. Hệ thống tự động cập nhật trạng thái

## 🔍 Kiểm tra lỗi

### **Nếu gặp lỗi SQL:**
```sql
-- Kiểm tra cấu trúc bảng thanhtoan
DESCRIBE thanhtoan;

-- Kiểm tra dữ liệu
SELECT * FROM thanhtoan WHERE PhuongThuc = 'Tiền mặt' ORDER BY NgayThanhToan DESC LIMIT 5;
```

### **Nếu gặp lỗi JavaScript:**
- Mở Developer Tools (F12)
- Kiểm tra Console tab
- Xem Network tab khi tạo thanh toán

### **Nếu gặp lỗi PHP:**
- Kiểm tra error log của Apache/PHP
- Đảm bảo session đã được start
- Kiểm tra quyền truy cập database

## 📞 Thông tin hỗ trợ

- **Địa chỉ:** 123 Đường Nguyễn Huệ, Quận 1, TP.HCM
- **Điện thoại:** (028) 1234-5678
- **Email:** info@eventabc.com
- **Giờ làm việc:** Thứ 2-6: 8:00-17:00, Thứ 7: 8:00-12:00

## ✅ Checklist hoàn thành

- [x] Sửa lỗi SQL column not found
- [x] Cập nhật JavaScript parameters
- [x] Thêm mapping ZaloPay
- [x] Cập nhật địa chỉ công ty
- [x] Tạo script test
- [x] Tạo tài liệu hướng dẫn

**Hệ thống thanh toán tiền mặt đã sẵn sàng sử dụng!** 🎉
