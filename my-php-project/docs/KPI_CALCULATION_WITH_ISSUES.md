# Tính KPI khi có Báo cáo Sự cố

## 📊 Logic tính KPI hiện tại

### Công thức hiện tại:
```php
KPI = ((Thời gian dự kiến - Thời gian thực tế) / Thời gian dự kiến) * 100
```

**Ví dụ:**
- Dự kiến: 2 giờ (120 phút)
- Thực tế: 1.5 giờ (90 phút)
- KPI = ((120 - 90) / 120) * 100 = **+25%** (sớm)

- Dự kiến: 1 giờ (60 phút)
- Thực tế: 1.25 giờ (75 phút)
- KPI = ((60 - 75) / 60) * 100 = **-25%** (trễ)

## ⚠️ Vấn đề khi có Sự cố

### Tình huống:
1. Nhân viên bắt đầu công việc: **10:00**
2. Dự kiến hoàn thành: **12:00** (2 giờ)
3. **10:30**: Phát hiện sự cố → Báo cáo sự cố
4. **10:30 - 11:30**: Xử lý sự cố (1 giờ)
5. **11:30 - 12:30**: Tiếp tục công việc
6. Hoàn thành: **12:30**

### Tính KPI theo logic hiện tại:
- Thời gian dự kiến: 2 giờ (120 phút)
- Thời gian thực tế: 2.5 giờ (150 phút) - từ 10:00 đến 12:30
- KPI = ((120 - 150) / 120) * 100 = **-25%** ❌

**Vấn đề**: KPI âm không phản ánh đúng hiệu suất vì:
- Nhân viên không phải lỗi gây ra sự cố
- Thời gian xử lý sự cố không nên tính vào KPI
- Công việc thực tế chỉ mất 1.5 giờ (10:00-10:30 + 11:30-12:30)

## ✅ Đề xuất Logic Tính KPI khi có Sự cố

### Phương án 1: Trừ thời gian xử lý sự cố (Khuyến nghị)

**Công thức mới:**
```php
Thời gian thực tế (điều chỉnh) = Thời gian thực tế - Thời gian xử lý sự cố
KPI = ((Thời gian dự kiến - Thời gian thực tế điều chỉnh) / Thời gian dự kiến) * 100
```

**Cách tính thời gian xử lý sự cố:**
- Thời gian từ khi báo sự cố đến khi quản lý xử lý xong (Trạng thái = 'Đã xử lý')
- Hoặc: Thời gian từ khi báo sự cố đến khi nhân viên tiếp tục công việc

**Ví dụ:**
- Dự kiến: 2 giờ (120 phút)
- Thực tế: 2.5 giờ (150 phút)
- Thời gian xử lý sự cố: 1 giờ (60 phút) - từ 10:30 đến 11:30
- Thời gian thực tế điều chỉnh: 150 - 60 = 90 phút
- KPI = ((120 - 90) / 120) * 100 = **+25%** ✅

### Phương án 2: Gia hạn thời gian dự kiến

**Công thức:**
```php
Thời gian dự kiến (điều chỉnh) = Thời gian dự kiến + Thời gian xử lý sự cố
KPI = ((Thời gian dự kiến điều chỉnh - Thời gian thực tế) / Thời gian dự kiến điều chỉnh) * 100
```

**Ví dụ:**
- Dự kiến: 2 giờ (120 phút)
- Thời gian xử lý sự cố: 1 giờ (60 phút)
- Thời gian dự kiến điều chỉnh: 120 + 60 = 180 phút
- Thời gian thực tế: 150 phút
- KPI = ((180 - 150) / 180) * 100 = **+16.67%** ✅

### Phương án 3: Không tính KPI khi có sự cố

**Logic:**
- Nếu có sự cố: KPI = NULL hoặc 0
- Ghi chú: "Có sự cố - không tính KPI"

**Ưu điểm**: Đơn giản, tránh tính toán phức tạp
**Nhược điểm**: Không đánh giá được hiệu suất khi có sự cố

## 🎯 Khuyến nghị: Phương án 1 (Trừ thời gian xử lý sự cố)

### Lý do:
1. **Công bằng**: Chỉ tính thời gian làm việc thực tế
2. **Chính xác**: Phản ánh đúng hiệu suất của nhân viên
3. **Linh hoạt**: Có thể áp dụng cho nhiều loại sự cố

### Implementation:

#### 1. Thêm trường vào bảng `baocaosuco` (hoặc `baocao` sau migration):

```sql
ALTER TABLE `baocaosuco` 
ADD COLUMN `ThoiGianBatDauXuLy` datetime DEFAULT NULL COMMENT 'Thời gian bắt đầu xử lý sự cố',
ADD COLUMN `ThoiGianKetThucXuLy` datetime DEFAULT NULL COMMENT 'Thời gian kết thúc xử lý sự cố (khi TrangThai = Đã xử lý)',
ADD COLUMN `ThoiGianXuLy` int(11) DEFAULT NULL COMMENT 'Thời gian xử lý sự cố (giây)';
```

#### 2. Cập nhật logic khi quản lý xử lý sự cố:

```php
function updateReportStatus($pdo) {
    // ... existing code ...
    
    if ($status === 'Đang xử lý') {
        // Ghi nhận thời gian bắt đầu xử lý
        $stmt = $pdo->prepare("
            UPDATE baocaosuco 
            SET TrangThai = ?, 
                ThoiGianBatDauXuLy = NOW(),
                NgayCapNhat = NOW()
            WHERE ID_BaoCao = ?
        ");
        $stmt->execute([$status, $reportId]);
    }
    
    if ($status === 'Đã xử lý' || $status === 'Đã đóng') {
        // Tính thời gian xử lý sự cố
        $stmt = $pdo->prepare("
            UPDATE baocaosuco 
            SET TrangThai = ?,
                ThoiGianKetThucXuLy = NOW(),
                ThoiGianXuLy = TIMESTAMPDIFF(SECOND, ThoiGianBatDauXuLy, NOW()),
                NgayCapNhat = NOW()
            WHERE ID_BaoCao = ?
        ");
        $stmt->execute([$status, $reportId]);
        
        // Cập nhật lại KPI cho nhiệm vụ liên quan
        recalculateKPIWithIssue($pdo, $reportId);
    }
}
```

#### 3. Hàm tính lại KPI khi có sự cố:

```php
function recalculateKPIWithIssue($pdo, $reportId) {
    // Lấy thông tin báo cáo sự cố
    $stmt = $pdo->prepare("
        SELECT ID_Task, LoaiTask, ThoiGianXuLy
        FROM baocaosuco
        WHERE ID_BaoCao = ?
    ");
    $stmt->execute([$reportId]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$issue || !$issue['ThoiGianXuLy']) {
        return; // Chưa có thời gian xử lý
    }
    
    // Lấy thông tin nhiệm vụ
    $taskStmt = $pdo->prepare("
        SELECT llv.*
        FROM lichlamviec llv
        WHERE llv.ID_LLV = ?
    ");
    $taskStmt->execute([$issue['ID_Task']]);
    $task = $taskStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task || !$task['ThoiGianKetThucThucTe']) {
        return; // Nhiệm vụ chưa hoàn thành
    }
    
    // Tính lại KPI
    $scheduledStart = new DateTime($task['NgayBatDau']);
    $scheduledEnd = new DateTime($task['NgayKetThuc']);
    $actualStart = new DateTime($task['ThoiGianBatDauThucTe']);
    $actualEnd = new DateTime($task['ThoiGianKetThucThucTe']);
    
    $scheduledDuration = $scheduledEnd->getTimestamp() - $scheduledStart->getTimestamp();
    $actualDuration = $actualEnd->getTimestamp() - $actualStart->getTimestamp();
    
    // Trừ thời gian xử lý sự cố
    $adjustedActualDuration = $actualDuration - $issue['ThoiGianXuLy'];
    
    // Tính KPI mới
    $kpi = 0;
    if ($scheduledDuration > 0) {
        $kpi = (($scheduledDuration - $adjustedActualDuration) / $scheduledDuration) * 100;
    }
    
    // Cập nhật KPI
    $updateStmt = $pdo->prepare("
        UPDATE lichlamviec 
        SET KPI = ?, NgayCapNhat = NOW()
        WHERE ID_LLV = ?
    ");
    $updateStmt->execute([$kpi, $issue['ID_Task']]);
}
```

#### 4. Cập nhật hàm `endTask()` để tính KPI ban đầu (chưa có sự cố):

```php
function endTask($pdo) {
    // ... existing code ...
    
    // Kiểm tra xem có sự cố chưa được xử lý không
    $issueStmt = $pdo->prepare("
        SELECT SUM(COALESCE(ThoiGianXuLy, 0)) as totalIssueTime
        FROM baocaosuco
        WHERE ID_Task = ? 
          AND LoaiTask = 'lichlamviec'
          AND TrangThai IN ('Đã xử lý', 'Đã đóng')
    ");
    $issueStmt->execute([$taskId]);
    $issueTime = $issueStmt->fetchColumn() ?? 0;
    
    // Tính thời gian thực tế điều chỉnh
    $actualDuration = $actualEnd->getTimestamp() - $actualStart->getTimestamp();
    $adjustedActualDuration = $actualDuration - $issueTime;
    
    // Tính KPI
    $kpi = 0;
    if ($scheduledDuration > 0) {
        $kpi = (($scheduledDuration - $adjustedActualDuration) / $scheduledDuration) * 100;
    }
    
    // ... rest of code ...
}
```

## 📊 Sau khi migration sang bảng `baocao`

### Cấu trúc bảng mới:

```sql
ALTER TABLE `baocao` 
ADD COLUMN `ThoiGianBatDauXuLy` datetime DEFAULT NULL COMMENT 'Thời gian bắt đầu xử lý (chỉ cho sự cố)',
ADD COLUMN `ThoiGianKetThucXuLy` datetime DEFAULT NULL COMMENT 'Thời gian kết thúc xử lý (chỉ cho sự cố)',
ADD COLUMN `ThoiGianXuLy` int(11) DEFAULT NULL COMMENT 'Thời gian xử lý (giây) - chỉ cho sự cố';
```

### Query tính KPI:

```php
// Lấy tổng thời gian xử lý sự cố cho nhiệm vụ
$issueStmt = $pdo->prepare("
    SELECT SUM(COALESCE(ThoiGianXuLy, 0)) as totalIssueTime
    FROM baocao
    WHERE ID_Task = ? 
      AND LoaiTask = 'lichlamviec'
      AND LoaiBaoCao = 'Sự cố'
      AND TrangThai IN ('Đã xử lý', 'Đã đóng')
");
```

## 🎯 Tóm tắt

1. **Thêm trường** để lưu thời gian xử lý sự cố
2. **Ghi nhận thời gian** khi quản lý xử lý sự cố
3. **Tính lại KPI** bằng cách trừ thời gian xử lý sự cố
4. **Công bằng** cho nhân viên khi có sự cố không phải lỗi của họ

