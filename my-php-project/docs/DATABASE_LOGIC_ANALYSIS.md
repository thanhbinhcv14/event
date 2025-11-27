# Phân tích Logic và Đề xuất Rút gọn Database

## 1. Logic hiện tại của các bảng

### 1.1. Cấu trúc phân cấp

```
sukien (Sự kiện)
  └── kehoachthuchien (Kế hoạch thực hiện)
        └── chitietkehoach (Chi tiết kế hoạch - các bước)
              └── lichlamviec (Lịch làm việc - nhiệm vụ cho từng nhân viên)
                    ├── baocaotiendo (Báo cáo tiến độ)
                    └── baocaosuco (Báo cáo sự cố)
```

### 1.2. Chi tiết từng bảng

#### **kehoachthuchien** (Kế hoạch thực hiện)
- **Mục đích**: Lưu kế hoạch tổng thể cho một sự kiện
- **Quan hệ**: 
  - `ID_SuKien` → `sukien.ID_SuKien` (1 kế hoạch cho 1 sự kiện)
  - `ID_NhanVien` → `nhanvieninfo.ID_NhanVien` (người tạo/quản lý kế hoạch)
- **Trường quan trọng**: 
  - `NgayBatDau`, `NgayKetThuc`: Thời gian tổng thể của kế hoạch
  - `TrangThai`: Trạng thái tổng thể (Chưa bắt đầu, Đang thực hiện, Hoàn thành)

#### **chitietkehoach** (Chi tiết kế hoạch - Các bước)
- **Mục đích**: Lưu các bước cụ thể trong kế hoạch
- **Quan hệ**: 
  - `ID_KeHoach` → `kehoachthuchien.ID_KeHoach` (nhiều bước cho 1 kế hoạch)
  - `ID_NhanVien` → `nhanvieninfo.ID_NhanVien` (nhân viên chính phụ trách bước - có thể NULL)
- **Trường quan trọng**: 
  - `TenBuoc`, `MoTa`: Mô tả bước
  - `NgayBatDau`, `NgayKetThuc`: Thời gian của bước
  - `TrangThai`: Trạng thái bước (Chưa làm, Đang làm, Hoàn thành)
  - `DaCongBo`: Đã công bố cho nhân viên chưa

#### **lichlamviec** (Lịch làm việc)
- **Mục đích**: Lưu nhiệm vụ cụ thể cho từng nhân viên
- **Quan hệ**: 
  - `ID_DatLich` → `datlichsukien.ID_DatLich` (nhiệm vụ thuộc sự kiện nào)
  - `ID_NhanVien` → `nhanvieninfo.ID_NhanVien` (nhân viên được giao)
  - `ID_KeHoach` → `kehoachthuchien.ID_KeHoach` (thuộc kế hoạch nào - nullable)
  - `ID_ChiTiet` → `chitietkehoach.ID_ChiTiet` (thuộc bước nào - NOT NULL)
- **Trường quan trọng**: 
  - `NhiemVu`, `CongViec`: Mô tả nhiệm vụ
  - `NgayBatDau`, `NgayKetThuc`: Thời gian dự kiến
  - `ThoiGianBatDauThucTe`, `ThoiGianKetThucThucTe`: Thời gian thực tế (khi nhân viên START/END)
  - `KPI`: Chỉ số hiệu suất
  - `TrangThai`: Trạng thái nhiệm vụ (Chưa làm, Đang làm, Hoàn thành, Báo sự cố)
  - `TienDo`: Tiến độ (%)
  - `ThoiGianHoanThanh`: Thời gian hoàn thành

#### **baocaotiendo** (Báo cáo tiến độ)
- **Mục đích**: Nhân viên báo cáo tiến độ công việc
- **Quan hệ**: 
  - `ID_NhanVien` → `nhanvieninfo.ID_NhanVien` (người báo cáo)
  - `ID_QuanLy` → `nhanvieninfo.ID_NhanVien` (người nhận báo cáo)
  - `ID_Task` + `LoaiTask`: Tham chiếu đến `lichlamviec.ID_LLV` hoặc `chitietkehoach.ID_ChiTiet`
- **Trường quan trọng**: 
  - `TienDo`: Tiến độ (%)
  - `GhiChu`: Ghi chú
  - `TrangThai`: Trạng thái báo cáo

#### **baocaosuco** (Báo cáo sự cố)
- **Mục đích**: Nhân viên báo cáo sự cố trong quá trình làm việc
- **Quan hệ**: 
  - `ID_NhanVien` → `nhanvieninfo.ID_NhanVien` (người báo cáo)
  - `ID_QuanLy` → `nhanvieninfo.ID_NhanVien` (người nhận báo cáo)
  - `ID_Task` + `LoaiTask`: Tham chiếu đến `lichlamviec.ID_LLV` hoặc `chitietkehoach.ID_ChiTiet`
- **Trường quan trọng**: 
  - `TieuDe`, `MoTa`: Mô tả sự cố
  - `MucDo`: Mức độ (Thấp, Trung bình, Cao, Khẩn cấp)
  - `TrangThai`: Trạng thái xử lý (Mới, Đang xử lý, Đã xử lý, Đã đóng)

## 2. Vấn đề và Trùng lặp

### 2.1. Trùng lặp dữ liệu

1. **Trạng thái và tiến độ**:
   - `chitietkehoach.TrangThai` vs `lichlamviec.TrangThai`
   - `lichlamviec.TienDo` vs `baocaotiendo.TienDo`
   - `lichlamviec.ThoiGianHoanThanh` vs `baocaotiendo` (có thể tính từ `ThoiGianKetThucThucTe`)

2. **Thông tin nhiệm vụ**:
   - `chitietkehoach.TenBuoc` vs `lichlamviec.NhiemVu` (thường giống nhau)
   - `chitietkehoach.MoTa` vs `lichlamviec.CongViec` (có thể trùng lặp)

3. **Báo cáo**:
   - `baocaotiendo` và `baocaosuco` có cấu trúc tương tự, chỉ khác mục đích
   - Cả hai đều có `ID_Task` + `LoaiTask` để tham chiếu đến task

### 2.2. Phức tạp không cần thiết

1. **chitietkehoach.ID_NhanVien**:
   - Có thể NULL, nhưng thực tế nhiệm vụ được giao qua `lichlamviec`
   - Có thể bỏ nếu chỉ dùng `lichlamviec` để giao việc

2. **lichlamviec.ID_KeHoach**:
   - Có thể tính từ `ID_ChiTiet` → `chitietkehoach.ID_KeHoach`
   - Có thể bỏ để giảm trùng lặp

3. **baocaotiendo và baocaosuco**:
   - Có thể gộp thành 1 bảng `baocao` với trường `LoaiBaoCao` (enum: 'Tiến độ', 'Sự cố')

## 3. Đề xuất Rút gọn

### 3.1. Gộp báo cáo thành 1 bảng

**Bảng mới: `baocao`**

```sql
CREATE TABLE `baocao` (
  `ID_BaoCao` int(11) NOT NULL AUTO_INCREMENT,
  `ID_NhanVien` int(11) NOT NULL COMMENT 'Người báo cáo',
  `ID_QuanLy` int(11) NOT NULL COMMENT 'Người nhận báo cáo',
  `ID_Task` int(11) NOT NULL COMMENT 'ID nhiệm vụ (lichlamviec.ID_LLV hoặc chitietkehoach.ID_ChiTiet)',
  `LoaiTask` enum('lichlamviec','chitietkehoach') NOT NULL COMMENT 'Loại task được báo cáo',
  `LoaiBaoCao` enum('Tiến độ','Sự cố') NOT NULL COMMENT 'Loại báo cáo',
  -- Các trường cho báo cáo tiến độ
  `TienDo` int(11) DEFAULT NULL COMMENT 'Tiến độ (%) - chỉ dùng cho báo cáo tiến độ',
  -- Các trường cho báo cáo sự cố
  `TieuDe` varchar(255) DEFAULT NULL COMMENT 'Tiêu đề - chỉ dùng cho báo cáo sự cố',
  `MucDo` enum('Thấp','Trung bình','Cao','Khẩn cấp') DEFAULT NULL COMMENT 'Mức độ - chỉ dùng cho báo cáo sự cố',
  -- Các trường chung
  `MoTa` text DEFAULT NULL COMMENT 'Mô tả/Ghi chú',
  `TrangThai` varchar(50) DEFAULT NULL COMMENT 'Trạng thái xử lý',
  `NgayBaoCao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID_BaoCao`),
  KEY `ID_NhanVien` (`ID_NhanVien`),
  KEY `ID_QuanLy` (`ID_QuanLy`),
  KEY `ID_Task` (`ID_Task`, `LoaiTask`),
  KEY `LoaiBaoCao` (`LoaiBaoCao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Lợi ích**:
- Giảm từ 2 bảng xuống 1 bảng
- Dễ query và báo cáo tổng hợp
- Giảm trùng lặp code

### 3.2. Tối ưu chitietkehoach

**Loại bỏ `ID_NhanVien`**:
- Nhiệm vụ được giao qua `lichlamviec`, không cần lưu ở `chitietkehoach`
- Giảm trùng lặp và phức tạp

**Giữ lại**:
- `ID_KeHoach`: Cần thiết để liên kết với kế hoạch
- `TenBuoc`, `MoTa`: Mô tả bước
- `NgayBatDau`, `NgayKetThuc`: Thời gian dự kiến của bước
- `TrangThai`: Trạng thái tổng thể của bước (tính từ các `lichlamviec`)
- `DaCongBo`: Cần thiết để kiểm soát việc công bố

### 3.3. Tối ưu lichlamviec

**Loại bỏ `ID_KeHoach`**:
- Có thể tính từ `ID_ChiTiet` → `chitietkehoach.ID_KeHoach`
- Giảm trùng lặp dữ liệu

**Giữ lại**:
- `ID_DatLich`: Cần thiết để liên kết với sự kiện
- `ID_NhanVien`: Nhân viên được giao
- `ID_ChiTiet`: Liên kết với bước trong kế hoạch
- `NhiemVu`, `CongViec`: Mô tả nhiệm vụ
- `NgayBatDau`, `NgayKetThuc`: Thời gian dự kiến
- `ThoiGianBatDauThucTe`, `ThoiGianKetThucThucTe`: Thời gian thực tế
- `KPI`: Chỉ số hiệu suất
- `TrangThai`: Trạng thái nhiệm vụ
- `TienDo`: Tiến độ (%)
- `ThoiGianHoanThanh`: Thời gian hoàn thành

**Có thể loại bỏ**:
- `TienDo`: Có thể tính từ `ThoiGianBatDauThucTe` và `ThoiGianKetThucThucTe`
- `ThoiGianHoanThanh`: Trùng với `ThoiGianKetThucThucTe`

### 3.4. Cấu trúc sau khi rút gọn

```
sukien (Sự kiện)
  └── kehoachthuchien (Kế hoạch thực hiện)
        └── chitietkehoach (Chi tiết kế hoạch - các bước)
              └── lichlamviec (Lịch làm việc - nhiệm vụ cho từng nhân viên)
                    └── baocao (Báo cáo - gộp tiến độ và sự cố)
```

## 4. Migration Script

### 4.1. Gộp baocaotiendo và baocaosuco

```sql
-- Tạo bảng mới
CREATE TABLE `baocao` (
  `ID_BaoCao` int(11) NOT NULL AUTO_INCREMENT,
  `ID_NhanVien` int(11) NOT NULL,
  `ID_QuanLy` int(11) NOT NULL,
  `ID_Task` int(11) NOT NULL,
  `LoaiTask` enum('lichlamviec','chitietkehoach') NOT NULL,
  `LoaiBaoCao` enum('Tiến độ','Sự cố') NOT NULL,
  `TienDo` int(11) DEFAULT NULL,
  `TieuDe` varchar(255) DEFAULT NULL,
  `MucDo` enum('Thấp','Trung bình','Cao','Khẩn cấp') DEFAULT NULL,
  `MoTa` text DEFAULT NULL,
  `TrangThai` varchar(50) DEFAULT NULL,
  `NgayBaoCao` timestamp NOT NULL DEFAULT current_timestamp(),
  `NgayCapNhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID_BaoCao`),
  KEY `ID_NhanVien` (`ID_NhanVien`),
  KEY `ID_QuanLy` (`ID_QuanLy`),
  KEY `ID_Task` (`ID_Task`, `LoaiTask`),
  KEY `LoaiBaoCao` (`LoaiBaoCao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrate dữ liệu từ baocaotiendo
INSERT INTO `baocao` (`ID_NhanVien`, `ID_QuanLy`, `ID_Task`, `LoaiTask`, `LoaiBaoCao`, `TienDo`, `MoTa`, `TrangThai`, `NgayBaoCao`)
SELECT `ID_NhanVien`, `ID_QuanLy`, `ID_Task`, `LoaiTask`, 'Tiến độ', `TienDo`, `GhiChu`, `TrangThai`, `NgayBaoCao`
FROM `baocaotiendo`;

-- Migrate dữ liệu từ baocaosuco
INSERT INTO `baocao` (`ID_NhanVien`, `ID_QuanLy`, `ID_Task`, `LoaiTask`, `LoaiBaoCao`, `TieuDe`, `MoTa`, `MucDo`, `TrangThai`, `NgayBaoCao`)
SELECT `ID_NhanVien`, `ID_QuanLy`, `ID_Task`, `LoaiTask`, 'Sự cố', `TieuDe`, `MoTa`, `MucDo`, `TrangThai`, `NgayBaoCao`
FROM `baocaosuco`;

-- Xóa các bảng cũ (SAU KHI ĐÃ KIỂM TRA DỮ LIỆU)
-- DROP TABLE `baocaotiendo`;
-- DROP TABLE `baocaosuco`;
```

### 4.2. Tối ưu chitietkehoach

```sql
-- Loại bỏ ID_NhanVien (nếu không cần thiết)
ALTER TABLE `chitietkehoach` DROP COLUMN `ID_NhanVien`;
```

### 4.3. Tối ưu lichlamviec

```sql
-- Loại bỏ ID_KeHoach (có thể tính từ ID_ChiTiet)
ALTER TABLE `lichlamviec` DROP COLUMN `ID_KeHoach`;

-- Loại bỏ TienDo và ThoiGianHoanThanh (có thể tính từ thời gian thực tế)
ALTER TABLE `lichlamviec` DROP COLUMN `TienDo`;
ALTER TABLE `lichlamviec` DROP COLUMN `ThoiGianHoanThanh`;
```

## 5. Lợi ích của việc rút gọn

1. **Giảm số lượng bảng**: Từ 5 bảng xuống 4 bảng (gộp 2 bảng báo cáo)
2. **Giảm trùng lặp dữ liệu**: Loại bỏ các trường có thể tính toán được
3. **Dễ bảo trì**: Ít bảng hơn, logic rõ ràng hơn
4. **Hiệu suất tốt hơn**: Ít JOIN hơn khi query
5. **Dễ mở rộng**: Bảng `baocao` có thể thêm loại báo cáo mới dễ dàng

## 6. Lưu ý

1. **Backup dữ liệu** trước khi migration
2. **Cập nhật code** sử dụng các bảng cũ
3. **Kiểm tra foreign keys** và constraints
4. **Test kỹ** trước khi xóa các bảng cũ

