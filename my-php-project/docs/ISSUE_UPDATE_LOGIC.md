# Logic Cập nhật Sự cố

## 📋 Logic hiện tại

### 1. **Nhân viên báo sự cố** (Tạo mới)
- **Hàm**: `reportIssue()` trong `staff-schedule.php`
- **Hành động**: 
  - INSERT vào `baocaosuco` với `TrangThai = 'Mới'`
  - UPDATE `lichlamviec` hoặc `chitietkehoach` với `TrangThai = 'Báo sự cố'`
- **Dữ liệu lưu**:
  - `ID_NhanVien`: Người báo cáo
  - `ID_QuanLy`: Quản lý nhận báo cáo
  - `ID_Task`: ID nhiệm vụ
  - `LoaiTask`: 'lichlamviec' hoặc 'chitietkehoach'
  - `TieuDe`: "Báo sự cố: [Tên nhiệm vụ]"
  - `MoTa`: Ghi chú của nhân viên
  - `MucDo`: Mặc định 'Trung bình'
  - `TrangThai`: 'Mới'

### 2. **Quản lý xử lý sự cố** (Cập nhật trạng thái)
- **Hàm**: `updateReportStatus()` trong `manager-reports.php`
- **Hành động**: 
  - UPDATE `baocaosuco.TrangThai` (Mới → Đang xử lý → Đã xử lý → Đã đóng)
  - Nếu trạng thái = 'Đã xử lý' hoặc 'Đã đóng': UPDATE task về 'Đang làm'
- **Quyền**: Chỉ quản lý (Role 2) mới được cập nhật

## ⚠️ Vấn đề: Nhân viên không thể cập nhật sự cố

**Hiện tại**: Nhân viên chỉ có thể **tạo mới** sự cố, không thể **cập nhật** sự cố đã tạo.

**Vấn đề**:
- Nhân viên muốn sửa `MoTa` (ghi chú) sau khi đã báo cáo
- Nhân viên muốn thay đổi `MucDo` (mức độ) nếu tình hình thay đổi
- Nhân viên muốn thêm thông tin bổ sung

## ✅ Đề xuất Logic Cập nhật Sự cố

### Quy tắc cập nhật:

1. **Nhân viên có thể cập nhật sự cố** nếu:
   - `TrangThai = 'Mới'` (chưa được quản lý xử lý)
   - `ID_NhanVien` khớp với nhân viên đang đăng nhập

2. **Nhân viên KHÔNG thể cập nhật** nếu:
   - `TrangThai != 'Mới'` (đã được quản lý xử lý)
   - `ID_NhanVien` không khớp

3. **Các trường có thể cập nhật**:
   - `MoTa`: Ghi chú/ mô tả sự cố
   - `MucDo`: Mức độ (Thấp, Trung bình, Cao, Khẩn cấp)
   - `TieuDe`: Tiêu đề (nếu cần)

4. **Các trường KHÔNG thể cập nhật**:
   - `ID_Task`: Nhiệm vụ không thể thay đổi
   - `LoaiTask`: Loại task không thể thay đổi
   - `TrangThai`: Chỉ quản lý mới được cập nhật
   - `ID_QuanLy`: Quản lý được gán không thể thay đổi

### Flow cập nhật:

```
Nhân viên báo sự cố
  ↓
Trạng thái: "Mới"
  ↓
Nhân viên có thể cập nhật (MoTa, MucDo, TieuDe)
  ↓
Quản lý xử lý → Trạng thái: "Đang xử lý"
  ↓
Nhân viên KHÔNG thể cập nhật nữa
  ↓
Quản lý hoàn thành → Trạng thái: "Đã xử lý" hoặc "Đã đóng"
```

## 🔧 Implementation

### 1. Thêm hàm `updateIssue()` trong `staff-schedule.php`

```php
function updateIssue() {
    try {
        ob_clean();
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $reportId = $_POST['reportId'] ?? '';
        $mota = $_POST['mota'] ?? '';
        $mucdo = $_POST['mucdo'] ?? 'Trung bình';
        $tieude = $_POST['tieude'] ?? '';
        
        if (empty($reportId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID báo cáo']);
            return;
        }
        
        // Get staff info
        $userId = $_SESSION['user']['ID_User'];
        $stmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $staffId = $stmt->fetchColumn();
        
        if (!$staffId) {
            throw new Exception("Staff not found");
        }
        
        // Kiểm tra quyền: chỉ nhân viên tạo báo cáo mới được cập nhật
        $stmt = $pdo->prepare("
            SELECT ID_NhanVien, TrangThai 
            FROM baocaosuco 
            WHERE ID_BaoCao = ?
        ");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            throw new Exception("Báo cáo không tồn tại");
        }
        
        // Chỉ cho phép cập nhật nếu:
        // 1. Nhân viên tạo báo cáo
        // 2. Trạng thái vẫn là "Mới" (chưa được quản lý xử lý)
        if ($report['ID_NhanVien'] != $staffId) {
            throw new Exception("Bạn không có quyền cập nhật báo cáo này");
        }
        
        if ($report['TrangThai'] != 'Mới') {
            throw new Exception("Không thể cập nhật báo cáo đã được xử lý");
        }
        
        // Cập nhật báo cáo
        $updateFields = [];
        $params = [];
        
        if (!empty($mota)) {
            $updateFields[] = "MoTa = ?";
            $params[] = $mota;
        }
        
        if (!empty($mucdo)) {
            $updateFields[] = "MucDo = ?";
            $params[] = $mucdo;
        }
        
        if (!empty($tieude)) {
            $updateFields[] = "TieuDe = ?";
            $params[] = $tieude;
        }
        
        if (empty($updateFields)) {
            throw new Exception("Không có thông tin nào để cập nhật");
        }
        
        $updateFields[] = "NgayCapNhat = NOW()";
        $params[] = $reportId;
        
        $sql = "UPDATE baocaosuco SET " . implode(", ", $updateFields) . " WHERE ID_BaoCao = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$result) {
            throw new Exception("Không thể cập nhật báo cáo");
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Cập nhật sự cố thành công']);
        
    } catch (Exception $e) {
        error_log("ERROR: updateIssue - " . $e->getMessage());
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
```

### 2. Thêm action trong switch case

```php
case 'update_issue':
    updateIssue();
    break;
```

### 3. Sau khi gộp thành bảng `baocao`

**Query cập nhật sẽ thay đổi:**

```php
// Thay vì:
UPDATE baocaosuco SET ...

// Sẽ là:
UPDATE baocao 
SET MoTa = ?, MucDo = ?, NgayCapNhat = NOW()
WHERE ID_BaoCao = ? 
  AND ID_NhanVien = ? 
  AND TrangThai = 'Mới'
  AND LoaiBaoCao = 'Sự cố'
```

## 📊 Sau khi migration sang bảng `baocao`

### Logic cập nhật với bảng mới:

```php
function updateIssue() {
    // ... validation code ...
    
    // Kiểm tra quyền và trạng thái
    $stmt = $pdo->prepare("
        SELECT ID_NhanVien, TrangThai 
        FROM baocao 
        WHERE ID_BaoCao = ? 
          AND LoaiBaoCao = 'Sự cố'
    ");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ... validation ...
    
    // Cập nhật
    $sql = "UPDATE baocao 
            SET MoTa = ?, MucDo = ?, TieuDe = ?, NgayCapNhat = NOW()
            WHERE ID_BaoCao = ? 
              AND LoaiBaoCao = 'Sự cố'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mota, $mucdo, $tieude, $reportId]);
}
```

## 🎯 Tóm tắt

1. **Nhân viên có thể cập nhật sự cố** khi:
   - Trạng thái = 'Mới'
   - Là người tạo báo cáo

2. **Các trường có thể cập nhật**:
   - `MoTa` (mô tả)
   - `MucDo` (mức độ)
   - `TieuDe` (tiêu đề)

3. **Sau khi migration**:
   - Thêm điều kiện `LoaiBaoCao = 'Sự cố'` vào query
   - Logic tương tự, chỉ đổi tên bảng

