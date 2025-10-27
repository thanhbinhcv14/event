<?php
// Set error reporting to catch all errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

session_start();

// Try to include database config
try {
    $dbPath = __DIR__ . '/../config/database.php';
    if (!file_exists($dbPath)) {
        throw new Exception('File database.php không tồn tại tại: ' . $dbPath);
    }
    require_once $dbPath;
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi cấu hình database: ' . $e->getMessage()]);
    exit;
}

// Check if user is logged in and has role 4 (Staff)
if (!isset($_SESSION['user']) || $_SESSION['user']['ID_Role'] != 4) {
    // Clear any output buffer
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_assignments':
        getAssignments();
        break;
    case 'update_assignment_status':
        updateAssignmentStatus();
        break;
    case 'update_progress':
        updateProgress();
        break;
    case 'start_work':
        startWork();
        break;
    case 'complete_work':
        completeWork();
        break;
    case 'report_issue':
        reportIssue();
        break;
    case 'get_event_details':
        getEventDetails();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

function getAssignments() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        $userId = $_SESSION['user']['ID_User'];
        
        // Get staff info
        $stmt = $pdo->prepare("
            SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?
        ");
        $stmt->execute([$userId]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin nhân viên']);
            return;
        }
        
        // Get assignments from both lichlamviec and chitietkehoach
        $assignments = [];
        
               // First, try to get from lichlamviec
               $stmt = $pdo->prepare("
                   SELECT 
                       llv.ID_LLV,
                       llv.NhiemVu,
                       llv.NgayBatDau,
                       llv.NgayKetThuc,
                       llv.TrangThai,
                       llv.GhiChu,
                       llv.CongViec,
                       llv.HanHoanThanh,
                       llv.Tiendo,
                       llv.ThoiGianBatDauThucTe,
                       llv.ThoiGianKetThucThucTe,
                       llv.TienDoPhanTram,
                       llv.ThoiGianLamViec,
                       llv.ChamTienDo,
                       llv.GhiChuTienDo,
                       COALESCE(dl.TenSuKien, 'Không xác định') as TenSuKien,
                       COALESCE(dl.NgayBatDau, llv.NgayBatDau) as EventStartDate,
                       COALESCE(dl.NgayKetThuc, llv.NgayKetThuc) as EventEndDate,
                       COALESCE(dd.TenDiaDiem, 'Không xác định') as TenDiaDiem,
                       COALESCE(dd.DiaChi, 'Không xác định') as DiaChi,
                       COALESCE(kh.HoTen, 'Không xác định') as TenKhachHang,
                       COALESCE(kh.SoDienThoai, 'Không xác định') as SoDienThoai,
                       'lichlamviec' as source_table
                   FROM lichlamviec llv
                   LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
                   LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
                   LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                   WHERE llv.ID_NhanVien = ?
                   ORDER BY llv.NgayBatDau ASC
               ");
        $stmt->execute([$staff['ID_NhanVien']]);
        $lichlamviec_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
               // Then, try to get from chitietkehoach
               $stmt = $pdo->prepare("
                   SELECT 
                       ck.ID_ChiTiet as ID_LLV,
                       ck.TenBuoc as NhiemVu,
                       ck.NgayBatDau,
                       ck.NgayKetThuc,
                       ck.TrangThai,
                       ck.GhiChu,
                       ck.TenBuoc as CongViec,
                       ck.NgayKetThuc as HanHoanThanh,
                       '0' as Tiendo,
                       ck.ThoiGianBatDauThucTe,
                       ck.ThoiGianKetThucThucTe,
                       ck.TienDoPhanTram,
                       ck.ThoiGianLamViec,
                       ck.ChamTienDo,
                       ck.GhiChuTienDo,
                       COALESCE(dl.TenSuKien, 'Không xác định') as TenSuKien,
                       COALESCE(dl.NgayBatDau, ck.NgayBatDau) as EventStartDate,
                       COALESCE(dl.NgayKetThuc, ck.NgayKetThuc) as EventEndDate,
                       COALESCE(dd.TenDiaDiem, 'Không xác định') as TenDiaDiem,
                       COALESCE(dd.DiaChi, 'Không xác định') as DiaChi,
                       COALESCE(kh.HoTen, 'Không xác định') as TenKhachHang,
                       COALESCE(kh.SoDienThoai, 'Không xác định') as SoDienThoai,
                       'chitietkehoach' as source_table
                   FROM chitietkehoach ck
                   LEFT JOIN kehoachthuchien kht ON ck.id_kehoach = kht.id_kehoach
                   LEFT JOIN sukien s ON kht.id_sukien = s.ID_SuKien
                   LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
                   LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
                   LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                   WHERE ck.ID_NhanVien = ?
                   ORDER BY ck.NgayBatDau ASC
               ");
        $stmt->execute([$staff['ID_NhanVien']]);
        $chitietkehoach_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine both results
        $assignments = array_merge($lichlamviec_assignments, $chitietkehoach_assignments);
        
        // Debug logs
        error_log("DEBUG: Staff Schedule Controller - Staff ID: " . $staff['ID_NhanVien']);
        error_log("DEBUG: Staff Schedule Controller - lichlamviec assignments: " . count($lichlamviec_assignments));
        error_log("DEBUG: Staff Schedule Controller - chitietkehoach assignments: " . count($chitietkehoach_assignments));
        error_log("DEBUG: Staff Schedule Controller - Total assignments: " . count($assignments));
        
        echo json_encode(['success' => true, 'assignments' => $assignments]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy lịch làm việc: ' . $e->getMessage()]);
    }
}

function updateAssignmentStatus() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? '';
        $newStatus = $_POST['newStatus'] ?? '';
        $progress = $_POST['progress'] ?? '';
        $note = $_POST['note'] ?? '';
        
        if (empty($assignmentId) || empty($newStatus)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $stmt = $pdo->prepare("
            UPDATE lichlamviec 
            SET TrangThai = ?, Tiendo = ?, GhiChu = ?, NgayCapNhat = NOW()
            WHERE ID_LLV = ?
        ");
        $stmt->execute([$newStatus, $progress, $note, $assignmentId]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage()]);
    }
}

function updateProgress() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? '';
        $sourceTable = $_POST['sourceTable'] ?? 'lichlamviec';
        $progress = $_POST['progress'] ?? '';
        $note = $_POST['note'] ?? '';
        
        if (empty($assignmentId) || empty($progress)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        if ($sourceTable === 'chitietkehoach') {
            $sql = "
                UPDATE chitietkehoach 
                SET TienDoPhanTram = ?, GhiChuTienDo = ?, NgayCapNhat = NOW()
                WHERE ID_ChiTiet = ?
            ";
        } else {
            $sql = "
                UPDATE lichlamviec 
                SET TienDoPhanTram = ?, GhiChuTienDo = ?, NgayCapNhat = NOW()
                WHERE ID_LLV = ?
            ";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$progress, $note, $assignmentId]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Cập nhật tiến độ thành công']);
        
    } catch (Exception $e) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật tiến độ: ' . $e->getMessage()]);
    }
}

function reportIssue() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? '';
        $note = $_POST['note'] ?? '';
        
        if (empty($assignmentId) || empty($note)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $stmt = $pdo->prepare("
            UPDATE lichlamviec 
            SET TrangThai = 'Báo sự cố', GhiChu = ?, NgayCapNhat = NOW()
            WHERE ID_LLV = ?
        ");
        $stmt->execute([$note, $assignmentId]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Báo sự cố thành công']);
        
    } catch (Exception $e) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi báo sự cố: ' . $e->getMessage()]);
    }
}

function startWork() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? '';
        $sourceTable = $_POST['sourceTable'] ?? 'lichlamviec';
        $note = $_POST['note'] ?? '';
        
        // Debug logs
        error_log("DEBUG: startWork - assignmentId: " . $assignmentId);
        error_log("DEBUG: startWork - sourceTable: " . $sourceTable);
        error_log("DEBUG: startWork - note: " . $note);
        
        if (empty($assignmentId)) {
            error_log("ERROR: startWork - Missing assignmentId");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin công việc']);
            return;
        }
        
        $currentTime = date('Y-m-d H:i:s');
        error_log("DEBUG: startWork - currentTime: " . $currentTime);
        
        // Try to update with basic fields first
        if ($sourceTable === 'chitietkehoach') {
            $sql = "
                UPDATE chitietkehoach 
                SET TrangThai = 'Đang thực hiện'
                WHERE ID_ChiTiet = ?
            ";
        } else {
            $sql = "
                UPDATE lichlamviec 
                SET TrangThai = 'Đang làm'
                WHERE ID_LLV = ?
            ";
        }
        
        error_log("DEBUG: startWork - SQL: " . $sql);
        error_log("DEBUG: startWork - Params: " . json_encode([$assignmentId]));
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$assignmentId]);
        
        error_log("DEBUG: startWork - Update result: " . ($result ? 'true' : 'false'));
        error_log("DEBUG: startWork - Rows affected: " . $stmt->rowCount());
        
        if (!$result) {
            error_log("ERROR: startWork - Failed to execute update");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái công việc']);
            return;
        }
        
        // Try to update additional fields if they exist
        try {
            if ($sourceTable === 'chitietkehoach') {
                $sql2 = "
                    UPDATE chitietkehoach 
                    SET ThoiGianBatDauThucTe = ?, 
                        GhiChuTienDo = ?
                    WHERE ID_ChiTiet = ?
                ";
            } else {
                $sql2 = "
                    UPDATE lichlamviec 
                    SET ThoiGianBatDauThucTe = ?, 
                        GhiChuTienDo = ?
                    WHERE ID_LLV = ?
                ";
            }
            
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([$currentTime, $note, $assignmentId]);
            error_log("DEBUG: startWork - Additional fields updated successfully");
        } catch (Exception $e) {
            error_log("DEBUG: startWork - Could not update additional fields: " . $e->getMessage());
            // Continue anyway, basic update was successful
        }
        
        error_log("DEBUG: startWork - Success");
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Bắt đầu làm việc thành công']);
        
    } catch (Exception $e) {
        error_log("ERROR: startWork - Exception: " . $e->getMessage());
        error_log("ERROR: startWork - Stack trace: " . $e->getTraceAsString());
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi bắt đầu làm việc: ' . $e->getMessage()]);
    }
}

function completeWork() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? '';
        $sourceTable = $_POST['sourceTable'] ?? 'lichlamviec';
        $progress = $_POST['progress'] ?? 100;
        $note = $_POST['note'] ?? '';
        
        // Debug logs
        error_log("DEBUG: completeWork - assignmentId: " . $assignmentId);
        error_log("DEBUG: completeWork - sourceTable: " . $sourceTable);
        error_log("DEBUG: completeWork - progress: " . $progress);
        error_log("DEBUG: completeWork - note: " . $note);
        
        if (empty($assignmentId)) {
            error_log("ERROR: completeWork - Missing assignmentId");
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin công việc']);
            return;
        }
        
        $currentTime = date('Y-m-d H:i:s');
        error_log("DEBUG: completeWork - currentTime: " . $currentTime);
        
        // Get deadline to check if late
        if ($sourceTable === 'chitietkehoach') {
            $stmt = $pdo->prepare("SELECT NgayKetThuc FROM chitietkehoach WHERE ID_ChiTiet = ?");
            $stmt->execute([$assignmentId]);
            $deadline = $stmt->fetchColumn();
            error_log("DEBUG: completeWork - chitietkehoach deadline: " . $deadline);
        } else {
            $stmt = $pdo->prepare("SELECT NgayKetThuc FROM lichlamviec WHERE ID_LLV = ?");
            $stmt->execute([$assignmentId]);
            $deadline = $stmt->fetchColumn();
            error_log("DEBUG: completeWork - lichlamviec deadline: " . $deadline);
        }
        
        $isLate = $currentTime > $deadline;
        error_log("DEBUG: completeWork - isLate: " . ($isLate ? 'true' : 'false'));
        
        if ($sourceTable === 'chitietkehoach') {
            $sql = "
                UPDATE chitietkehoach 
                SET TrangThai = 'Hoàn thành', 
                    ThoiGianKetThucThucTe = ?, 
                    TienDoPhanTram = ?,
                    ChamTienDo = ?,
                    GhiChuTienDo = ?
                WHERE ID_ChiTiet = ?
            ";
        } else {
            $sql = "
                UPDATE lichlamviec 
                SET TrangThai = 'Hoàn thành', 
                    ThoiGianKetThucThucTe = ?, 
                    TienDoPhanTram = ?,
                    ChamTienDo = ?,
                    GhiChuTienDo = ?
                WHERE ID_LLV = ?
            ";
        }
        
        error_log("DEBUG: completeWork - SQL: " . $sql);
        error_log("DEBUG: completeWork - Params: " . json_encode([$currentTime, $progress, $isLate ? 1 : 0, $note, $assignmentId]));
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$currentTime, $progress, $isLate ? 1 : 0, $note, $assignmentId]);
        
        error_log("DEBUG: completeWork - Update result: " . ($result ? 'true' : 'false'));
        error_log("DEBUG: completeWork - Rows affected: " . $stmt->rowCount());
        
        if (!$result) {
            error_log("ERROR: completeWork - Failed to execute update");
            echo json_encode(['success' => false, 'message' => 'Không thể cập nhật trạng thái công việc']);
            return;
        }
        
        $message = 'Hoàn thành công việc thành công';
        if ($isLate) {
            $message .= ' (Chậm tiến độ)';
        }
        
        error_log("DEBUG: completeWork - Success: " . $message);
        echo json_encode(['success' => true, 'message' => $message, 'isLate' => $isLate]);
        
    } catch (Exception $e) {
        error_log("ERROR: completeWork - Exception: " . $e->getMessage());
        error_log("ERROR: completeWork - Stack trace: " . $e->getTraceAsString());
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi hoàn thành công việc: ' . $e->getMessage()]);
    }
}

function getEventDetails() {
    try {
        // Clear any output buffer
        ob_clean();
        
        // Check if database function exists
        if (!function_exists('getDBConnection')) {
            throw new Exception('Function getDBConnection không tồn tại');
        }
        
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('Không thể kết nối database');
        }
        
        $assignmentId = $_POST['assignmentId'] ?? $_GET['assignmentId'] ?? '';
        $eventId = $_GET['event_id'] ?? '';
        
        error_log("DEBUG: getEventDetails - assignmentId: " . $assignmentId);
        error_log("DEBUG: getEventDetails - eventId: " . $eventId);
        
        // If assignmentId is provided, get eventId from assignment
        if (!empty($assignmentId)) {
            // Try to get from chitietkehoach first
            $stmt = $pdo->prepare("
                SELECT dl.ID_DatLich 
                FROM chitietkehoach ck
                LEFT JOIN kehoachthuchien kht ON ck.id_kehoach = kht.id_kehoach
                LEFT JOIN sukien s ON kht.id_sukien = s.ID_SuKien
                LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
                WHERE ck.ID_ChiTiet = ?
            ");
            $stmt->execute([$assignmentId]);
            $eventId = $stmt->fetchColumn();
            
            if (!$eventId) {
                // Try to get from lichlamviec
                $stmt = $pdo->prepare("
                    SELECT ID_DatLich 
                    FROM lichlamviec 
                    WHERE ID_LLV = ?
                ");
                $stmt->execute([$assignmentId]);
                $eventId = $stmt->fetchColumn();
            }
        }
        
        if (empty($eventId)) {
            error_log("ERROR: getEventDetails - No eventId found");
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sự kiện']);
            return;
        }
        
        error_log("DEBUG: getEventDetails - Final eventId: " . $eventId);
        
        // Get event details
        $stmt = $pdo->prepare("
            SELECT 
                dl.*,
                dd.TenDiaDiem,
                dd.DiaChi,
                dd.MoTa as DiaDiemMoTa,
                dd.SucChua,
                dd.GiaThue,
                ls.TenLoai as TenLoaiSK,
                ls.MoTa as LoaiSKMoTa,
                kh.HoTen as TenKhachHang,
                kh.SoDienThoai,
                kh.DiaChi as KhachHangDiaChi,
                s.ID_SuKien
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
            WHERE dl.ID_DatLich = ?
        ");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            error_log("ERROR: getEventDetails - Event not found for ID: " . $eventId);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện']);
            return;
        }
        
        error_log("DEBUG: getEventDetails - Event found: " . json_encode($event));
        
        // Get registered equipment (check if ID_SuKien exists)
        $equipment = [];
        if (!empty($event['ID_SuKien'])) {
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        st.ID_SuKien_ThietBi,
                        t.TenThietBi,
                        t.MoTa,
                        t.GiaThue,
                        t.TrangThai as ThietBiTrangThai,
                        st.SoLuong,
                        st.GhiChu
                    FROM sukien_thietbi st
                    LEFT JOIN thietbi t ON st.ID_ThietBi = t.ID_ThietBi
                    WHERE st.ID_SuKien = ?
                ");
                $stmt->execute([$event['ID_SuKien']]);
                $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("DEBUG: Found " . count($equipment) . " equipment items");
            } catch (Exception $e) {
                error_log("DEBUG: Error getting equipment: " . $e->getMessage());
                $equipment = [];
            }
        } else {
            error_log("DEBUG: No ID_SuKien found for event");
        }
        
        // Get combo equipment (check if tables exist first)
        $combos = [];
        $comboEquipment = [];
        
        try {
            // Check if sukien_combo table exists and ID_SuKien is available
            if (!empty($event['ID_SuKien'])) {
                $stmt = $pdo->query("SHOW TABLES LIKE 'sukien_combo'");
                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("
                        SELECT 
                            sc.ID_SuKien,
                            c.ID_Combo,
                            c.TenCombo,
                            c.MoTa as ComboMoTa,
                            c.GiaCombo,
                            sc.SoLuong,
                            sc.GhiChu
                        FROM sukien_combo sc
                        LEFT JOIN combo c ON sc.ID_Combo = c.ID_Combo
                        WHERE sc.ID_SuKien = ?
                    ");
                    $stmt->execute([$event['ID_SuKien']]);
                    $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("DEBUG: Found " . count($combos) . " combos");
                    
                    // Get combo equipment details
                    foreach ($combos as $combo) {
                        try {
                            $stmt = $pdo->prepare("
                                SELECT 
                                    ct.ID_Combo,
                                    t.TenThietBi,
                                    t.MoTa,
                                    t.GiaThue,
                                    t.TrangThai,
                                    ct.SoLuong
                                FROM combo_thietbi ct
                                LEFT JOIN thietbi t ON ct.ID_TB = t.ID_TB
                                WHERE ct.ID_Combo = ?
                            ");
                            $stmt->execute([$combo['ID_Combo']]);
                            $comboEquipment[$combo['ID_Combo']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            error_log("DEBUG: Found " . count($comboEquipment[$combo['ID_Combo']]) . " equipment items for combo " . $combo['ID_Combo']);
                        } catch (Exception $e) {
                            error_log("DEBUG: Error getting combo equipment for combo " . $combo['ID_Combo'] . ": " . $e->getMessage());
                            $comboEquipment[$combo['ID_Combo']] = [];
                        }
                    }
                } else {
                    error_log("DEBUG: sukien_combo table does not exist");
                }
            } else {
                error_log("DEBUG: No ID_SuKien for combo queries");
            }
        } catch (Exception $e) {
            error_log("DEBUG: Error getting combo data: " . $e->getMessage());
        }
        
        // Get registration details
        $registration = null;
        if (!empty($event['ID_DatLich'])) {
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        dl.NgayTao as NgayDangKy,
                        dl.TrangThaiDuyet,
                        dl.NgayCapNhat as NgayDuyet,
                        dl.GhiChu as DangKyGhiChu,
                        dl.GhiChu as LyDoTuChoi,
                        nv.HoTen as NguoiDuyet
                    FROM datlichsukien dl
                    LEFT JOIN nhanvieninfo nv ON dl.ID_NhanVienDuyet = nv.ID_NhanVien
                    WHERE dl.ID_DatLich = ?
                ");
                $stmt->execute([$event['ID_DatLich']]);
                $registration = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("DEBUG: Found registration details");
            } catch (Exception $e) {
                error_log("DEBUG: Error getting registration details: " . $e->getMessage());
            }
        }
        
        $event['equipment'] = $equipment;
        $event['combos'] = $combos;
        $event['comboEquipment'] = $comboEquipment;
        $event['registration'] = $registration;
        
        error_log("DEBUG: getEventDetails - Equipment count: " . count($equipment));
        error_log("DEBUG: getEventDetails - Combos count: " . count($combos));
        error_log("DEBUG: getEventDetails - Registration: " . ($registration ? 'Found' : 'Not found'));
        
        // Ensure we always return JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'event' => $event]);
        
    } catch (Exception $e) {
        error_log("ERROR: getEventDetails - Exception: " . $e->getMessage());
        error_log("ERROR: getEventDetails - Stack trace: " . $e->getTraceAsString());
        
        // Clear any output buffer and ensure we return JSON
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy chi tiết sự kiện: ' . $e->getMessage()]);
    }
}
?>
