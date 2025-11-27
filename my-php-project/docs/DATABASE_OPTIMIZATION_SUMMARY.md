# Tóm tắt Logic và Đề xuất Rút gọn Database

## 📊 Logic hiện tại

### Cấu trúc phân cấp:
```
sukien (Sự kiện)
  └── kehoachthuchien (Kế hoạch thực hiện)
        └── chitietkehoach (Chi tiết kế hoạch - các bước)
              └── lichlamviec (Lịch làm việc - nhiệm vụ cho từng nhân viên)
                    ├── baocaotiendo (Báo cáo tiến độ)
                    └── baocaosuco (Báo cáo sự cố)
```

## 🔍 Phân tích từng bảng

### 1. **kehoachthuchien** (Kế hoạch thực hiện)
- **Mục đích**: Kế hoạch tổng thể cho 1 sự kiện
- **Quan hệ**: 
  - `ID_SuKien` → `sukien` (1 kế hoạch : 1 sự kiện)
  - `ID_NhanVien` → Người tạo/quản lý kế hoạch
- **Trường chính**: `NgayBatDau`, `NgayKetThuc`, `TrangThai`

### 2. **chitietkehoach** (Chi tiết kế hoạch - Các bước)
- **Mục đích**: Các bước cụ thể trong kế hoạch
- **Quan hệ**: 
  - `ID_KeHoach` → `kehoachthuchien` (nhiều bước : 1 kế hoạch)
  - `ID_NhanVien` → Nhân viên chính phụ trách (có thể NULL) ⚠️ **CÓ THỂ BỎ**
- **Trường chính**: `TenBuoc`, `MoTa`, `NgayBatDau`, `NgayKetThuc`, `TrangThai`, `DaCongBo`

### 3. **lichlamviec** (Lịch làm việc)
- **Mục đích**: Nhiệm vụ cụ thể cho từng nhân viên
- **Quan hệ**: 
  - `ID_DatLich` → `datlichsukien` (nhiệm vụ thuộc sự kiện)
  - `ID_NhanVien` → Nhân viên được giao
  - `ID_KeHoach` → `kehoachthuchien` ⚠️ **CÓ THỂ BỎ** (tính từ `ID_ChiTiet`)
  - `ID_ChiTiet` → `chitietkehoach` (thuộc bước nào)
- **Trường chính**: 
  - `NhiemVu`, `CongViec`
  - `NgayBatDau`, `NgayKetThuc` (dự kiến)
  - `ThoiGianBatDauThucTe`, `ThoiGianKetThucThucTe` (thực tế)
  - `KPI`, `TrangThai`
  - `TienDo` ⚠️ **CÓ THỂ BỎ** (tính từ thời gian thực tế)
  - `ThoiGianHoanThanh` ⚠️ **CÓ THỂ BỎ** (trùng với `ThoiGianKetThucThucTe`)

### 4. **baocaotiendo** (Báo cáo tiến độ)
- **Mục đích**: Nhân viên báo cáo tiến độ
- **Quan hệ**: 
  - `ID_Task` + `LoaiTask` → `lichlamviec` hoặc `chitietkehoach`
- **Trường chính**: `TienDo`, `GhiChu`, `TrangThai`

### 5. **baocaosuco** (Báo cáo sự cố)
- **Mục đích**: Nhân viên báo cáo sự cố
- **Quan hệ**: 
  - `ID_Task` + `LoaiTask` → `lichlamviec` hoặc `chitietkehoach`
- **Trường chính**: `TieuDe`, `MoTa`, `MucDo`, `TrangThai`

## ⚠️ Vấn đề trùng lặp

1. **baocaotiendo** và **baocaosuco**: Cấu trúc giống nhau, chỉ khác mục đích → **CÓ THỂ GỘP**
2. **chitietkehoach.ID_NhanVien**: Nhiệm vụ được giao qua `lichlamviec` → **CÓ THỂ BỎ**
3. **lichlamviec.ID_KeHoach**: Có thể tính từ `ID_ChiTiet` → **CÓ THỂ BỎ**
4. **lichlamviec.TienDo**: Có thể tính từ thời gian thực tế → **CÓ THỂ BỎ**
5. **lichlamviec.ThoiGianHoanThanh**: Trùng với `ThoiGianKetThucThucTe` → **CÓ THỂ BỎ**

## ✅ Đề xuất Rút gọn

### Bước 1: Gộp 2 bảng báo cáo thành 1 bảng `baocao`

**Bảng mới:**
```sql
CREATE TABLE `baocao` (
  `ID_BaoCao` int(11) NOT NULL AUTO_INCREMENT,
  `ID_NhanVien` int(11) NOT NULL,
  `ID_QuanLy` int(11) NOT NULL,
  `ID_Task` int(11) NOT NULL,
  `LoaiTask` enum('lichlamviec','chitietkehoach') NOT NULL,
  `LoaiBaoCao` enum('Tiến độ','Sự cố') NOT NULL,  -- ⭐ MỚI
  `TienDo` int(11) DEFAULT NULL,  -- Chỉ dùng cho 'Tiến độ'
  `TieuDe` varchar(255) DEFAULT NULL,  -- Chỉ dùng cho 'Sự cố'
  `MucDo` enum(...) DEFAULT NULL,  -- Chỉ dùng cho 'Sự cố'
  `MoTa` text DEFAULT NULL,  -- Dùng cho cả 2 loại
  `TrangThai` varchar(50) DEFAULT NULL,
  `NgayBaoCao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID_BaoCao`)
);
```

**Lợi ích:**
- Giảm từ 2 bảng xuống 1 bảng
- Dễ query và báo cáo tổng hợp
- Dễ thêm loại báo cáo mới

### Bước 2: Loại bỏ các trường trùng lặp

**chitietkehoach:**
- ❌ Bỏ `ID_NhanVien` (nhiệm vụ được giao qua `lichlamviec`)

**lichlamviec:**
- ❌ Bỏ `ID_KeHoach` (tính từ `ID_ChiTiet` → `chitietkehoach.ID_KeHoach`)
- ❌ Bỏ `TienDo` (tính từ thời gian thực tế)
- ❌ Bỏ `ThoiGianHoanThanh` (trùng với `ThoiGianKetThucThucTe`)

## 📈 Kết quả sau khi rút gọn

### Trước:
- 5 bảng: `kehoachthuchien`, `chitietkehoach`, `lichlamviec`, `baocaotiendo`, `baocaosuco`
- Nhiều trường trùng lặp

### Sau:
- 4 bảng: `kehoachthuchien`, `chitietkehoach`, `lichlamviec`, `baocao` (gộp 2 bảng báo cáo)
- Ít trùng lặp hơn
- Dễ bảo trì và mở rộng hơn

## 🚀 Migration Script

Xem file: `database/optimize_planning_tables.sql`

## ⚠️ Lưu ý

1. **Backup database** trước khi migration
2. **Cập nhật code** sử dụng các bảng/trường cũ:
   - `baocaotiendo` → `baocao WHERE LoaiBaoCao = 'Tiến độ'`
   - `baocaosuco` → `baocao WHERE LoaiBaoCao = 'Sự cố'`
   - `lichlamviec.ID_KeHoach` → JOIN qua `chitietkehoach`
   - `lichlamviec.TienDo` → Tính từ thời gian thực tế
3. **Test kỹ** trước khi xóa các bảng cũ
4. **Kiểm tra foreign keys** và constraints

