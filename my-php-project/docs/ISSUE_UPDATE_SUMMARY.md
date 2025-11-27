# Tóm tắt Logic Cập nhật Sự cố

## 🔄 Flow hiện tại

### 1. Nhân viên báo sự cố (Tạo mới)
```
Nhân viên → reportIssue() → INSERT baocaosuco (TrangThai = 'Mới')
```

### 2. Quản lý xử lý sự cố
```
Quản lý → updateReportStatus() → UPDATE baocaosuco.TrangThai
(Mới → Đang xử lý → Đã xử lý → Đã đóng)
```

## ❌ Vấn đề

**Nhân viên không thể cập nhật sự cố đã tạo!**

## ✅ Giải pháp

### Quy tắc cập nhật:

1. **Nhân viên có thể cập nhật** nếu:
   - ✅ `TrangThai = 'Mới'` (chưa được quản lý xử lý)
   - ✅ `ID_NhanVien` = nhân viên đang đăng nhập

2. **Nhân viên KHÔNG thể cập nhật** nếu:
   - ❌ `TrangThai != 'Mới'` (đã được xử lý)
   - ❌ `ID_NhanVien` không khớp

3. **Các trường có thể cập nhật**:
   - `MoTa`: Mô tả sự cố
   - `MucDo`: Mức độ (Thấp, Trung bình, Cao, Khẩn cấp)
   - `TieuDe`: Tiêu đề

### Flow đầy đủ:

```
Nhân viên báo sự cố
  ↓
Trạng thái: "Mới"
  ↓
✅ Nhân viên có thể cập nhật (MoTa, MucDo, TieuDe)
  ↓
Quản lý xử lý → Trạng thái: "Đang xử lý"
  ↓
❌ Nhân viên KHÔNG thể cập nhật nữa
  ↓
Quản lý hoàn thành → Trạng thái: "Đã xử lý" / "Đã đóng"
```

## 🔧 Implementation

### Với bảng `baocaosuco` (hiện tại):

```php
UPDATE baocaosuco 
SET MoTa = ?, MucDo = ?, TieuDe = ?, NgayCapNhat = NOW()
WHERE ID_BaoCao = ? 
  AND ID_NhanVien = ? 
  AND TrangThai = 'Mới'
```

### Với bảng `baocao` (sau migration):

```php
UPDATE baocao 
SET MoTa = ?, MucDo = ?, TieuDe = ?, NgayCapNhat = NOW()
WHERE ID_BaoCao = ? 
  AND ID_NhanVien = ? 
  AND TrangThai = 'Mới'
  AND LoaiBaoCao = 'Sự cố'
```

## 📝 Lưu ý

1. **Chỉ cập nhật khi TrangThai = 'Mới'**: Đảm bảo quản lý chưa xử lý
2. **Kiểm tra quyền**: Chỉ nhân viên tạo báo cáo mới được cập nhật
3. **Không thể thay đổi**: ID_Task, LoaiTask, TrangThai, ID_QuanLy

