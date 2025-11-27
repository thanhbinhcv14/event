# Tóm tắt: Tính KPI khi có Sự cố

## ⚠️ Vấn đề hiện tại

**Công thức KPI hiện tại:**
```
KPI = ((Thời gian dự kiến - Thời gian thực tế) / Thời gian dự kiến) * 100
```

**Vấn đề:** Khi có sự cố, thời gian thực tế tăng lên → KPI âm, nhưng không công bằng vì sự cố có thể không phải lỗi của nhân viên.

## ✅ Giải pháp: Trừ thời gian xử lý sự cố

### Công thức mới:
```
Thời gian thực tế (điều chỉnh) = Thời gian thực tế - Thời gian xử lý sự cố
KPI = ((Thời gian dự kiến - Thời gian thực tế điều chỉnh) / Thời gian dự kiến) * 100
```

### Ví dụ:
- **Dự kiến**: 2 giờ (120 phút)
- **Thực tế**: 2.5 giờ (150 phút)
- **Thời gian xử lý sự cố**: 1 giờ (60 phút)
- **Thời gian thực tế điều chỉnh**: 150 - 60 = 90 phút
- **KPI**: ((120 - 90) / 120) * 100 = **+25%** ✅

## 🔧 Implementation

### 1. Thêm trường vào bảng `baocaosuco` (hoặc `baocao`):

```sql
ALTER TABLE `baocaosuco` 
ADD COLUMN `ThoiGianBatDauXuLy` datetime DEFAULT NULL,
ADD COLUMN `ThoiGianKetThucXuLy` datetime DEFAULT NULL,
ADD COLUMN `ThoiGianXuLy` int(11) DEFAULT NULL COMMENT 'Thời gian xử lý sự cố (giây)';
```

### 2. Ghi nhận thời gian khi quản lý xử lý:

- **"Đang xử lý"**: Ghi `ThoiGianBatDauXuLy = NOW()`
- **"Đã xử lý"**: Ghi `ThoiGianKetThucXuLy = NOW()` và tính `ThoiGianXuLy`

### 3. Tính lại KPI:

```php
// Lấy tổng thời gian xử lý sự cố
$issueTime = SUM(ThoiGianXuLy) WHERE ID_Task = ? AND TrangThai = 'Đã xử lý'

// Tính KPI điều chỉnh
$adjustedActualDuration = $actualDuration - $issueTime;
$kpi = (($scheduledDuration - $adjustedActualDuration) / $scheduledDuration) * 100;
```

## 📋 Flow

```
Nhân viên báo sự cố
  ↓
Quản lý xử lý → "Đang xử lý" → Ghi ThoiGianBatDauXuLy
  ↓
Quản lý hoàn thành → "Đã xử lý" → Ghi ThoiGianKetThucXuLy, tính ThoiGianXuLy
  ↓
Tính lại KPI cho nhiệm vụ (trừ ThoiGianXuLy)
```

## 🎯 Lợi ích

1. **Công bằng**: Chỉ tính thời gian làm việc thực tế
2. **Chính xác**: Phản ánh đúng hiệu suất
3. **Linh hoạt**: Áp dụng cho nhiều loại sự cố

