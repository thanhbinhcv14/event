<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check if user is logged in and has role 4 (Staff)
if (!isset($_SESSION['user']) || $_SESSION['user']['ID_Role'] != 4) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_detailed_stats':
        getDetailedStats();
        break;
    case 'get_performance_report':
        getPerformanceReport();
        break;
    case 'get_work_summary':
        getWorkSummary();
        break;
    case 'export_report':
        exportReport();
        break;
    case 'submit_progress_report':
        submitProgressReport();
        break;
    case 'get_progress_reports':
        getProgressReports();
        break;
    case 'get_managers':
        getManagers();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

function getDetailedStats() {
    try {
        $pdo = getDBConnection();
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
        
        $staffId = $staff['ID_NhanVien'];
        
        // Get assignments from both lichlamviec and chitietkehoach
        $assignments = [];
        
        // From lichlamviec
        $stmt = $pdo->prepare("
            SELECT 
                'lichlamviec' as source,
                ID_LLV as id,
                NhiemVu as task,
                TrangThai,
                Tiendo,
                NgayTao,
                NgayCapNhat,
                HanHoanThanh
            FROM lichlamviec 
            WHERE ID_NhanVien = ?
        ");
        $stmt->execute([$staffId]);
        $lichlamviecData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // From chitietkehoach
        $stmt = $pdo->prepare("
            SELECT 
                'chitietkehoach' as source,
                ID_ChiTiet as id,
                TenBuoc as task,
                TrangThai,
                COALESCE(TienDoPhanTram, 0) as Tiendo,
                NgayBatDau as NgayTao,
                NgayKetThuc as NgayCapNhat,
                NgayKetThuc as HanHoanThanh
            FROM chitietkehoach 
            WHERE ID_NhanVien = ?
        ");
        $stmt->execute([$staffId]);
        $chitietkehoachData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine data
        $allAssignments = array_merge($lichlamviecData, $chitietkehoachData);
        
        // Calculate statistics
        $totalAssignments = count($allAssignments);
        $completedAssignments = count(array_filter($allAssignments, function($a) { 
            return $a['TrangThai'] === 'Hoàn thành'; 
        }));
        $inProgressAssignments = count(array_filter($allAssignments, function($a) { 
            return $a['TrangThai'] === 'Đang làm' || $a['TrangThai'] === 'Đang thực hiện'; 
        }));
        $overdueAssignments = count(array_filter($allAssignments, function($a) { 
            return $a['TrangThai'] !== 'Hoàn thành' && 
                   $a['HanHoanThanh'] && 
                   strtotime($a['HanHoanThanh']) < time(); 
        }));
        
        // Calculate average completion time
        $completedTasks = array_filter($allAssignments, function($a) { 
            return $a['TrangThai'] === 'Hoàn thành' && $a['NgayCapNhat']; 
        });
        $totalHours = 0;
        foreach ($completedTasks as $task) {
            $totalHours += (strtotime($task['NgayCapNhat']) - strtotime($task['NgayTao'])) / 3600;
        }
        $avgCompletionTime = count($completedTasks) > 0 ? $totalHours / count($completedTasks) : 0;
        
        $stats = [
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
            'in_progress_assignments' => $inProgressAssignments,
            'overdue_assignments' => $overdueAssignments,
            'completion_rate' => $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100, 2) : 0,
            'avg_completion_hours' => round($avgCompletionTime, 2),
            'source_breakdown' => [
                'lichlamviec' => count($lichlamviecData),
                'chitietkehoach' => count($chitietkehoachData)
            ]
        ];
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thống kê chi tiết: ' . $e->getMessage()]);
    }
}

function getPerformanceReport() {
    try {
        $pdo = getDBConnection();
        $userId = $_SESSION['user']['ID_User'];
        
        // Get date range from POST
        $startDate = $_POST['start_date'] ?? date('Y-m-01');
        $endDate = $_POST['end_date'] ?? date('Y-m-t');
        
        error_log("DEBUG: Performance report request - start_date: $startDate, end_date: $endDate");
        
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
        
        $staffId = $staff['ID_NhanVien'];
        
        // Get performance data from both sources
        $performanceData = [];
        
        // From lichlamviec
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                COUNT(*) as total_tasks,
                SUM(CASE WHEN TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_tasks,
                AVG(CASE WHEN TrangThai = 'Hoàn thành' THEN TIMESTAMPDIFF(HOUR, NgayTao, NgayCapNhat) END) as avg_hours
            FROM lichlamviec 
            WHERE ID_NhanVien = ? 
            AND DATE(NgayTao) BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
        ");
        $stmt->execute([$staffId, $startDate, $endDate]);
        $lichlamviecData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("DEBUG: lichlamviec data count: " . count($lichlamviecData));
        
        // From chitietkehoach
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(NgayBatDau, '%Y-%m') as month,
                COUNT(*) as total_tasks,
                SUM(CASE WHEN TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_tasks,
                AVG(CASE WHEN TrangThai = 'Hoàn thành' THEN TIMESTAMPDIFF(HOUR, NgayBatDau, NgayKetThuc) END) as avg_hours
            FROM chitietkehoach 
            WHERE ID_NhanVien = ? 
            AND DATE(NgayBatDau) BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(NgayBatDau, '%Y-%m')
        ");
        $stmt->execute([$staffId, $startDate, $endDate]);
        $chitietkehoachData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("DEBUG: chitietkehoach data count: " . count($chitietkehoachData));
        
        // Combine data by month
        $monthlyData = [];
        foreach ($lichlamviecData as $data) {
            $month = $data['month'];
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'month' => $month,
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'avg_hours' => 0
                ];
            }
            $monthlyData[$month]['total_tasks'] += $data['total_tasks'];
            $monthlyData[$month]['completed_tasks'] += $data['completed_tasks'];
        }
        
        foreach ($chitietkehoachData as $data) {
            $month = $data['month'];
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'month' => $month,
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'avg_hours' => 0
                ];
            }
            $monthlyData[$month]['total_tasks'] += $data['total_tasks'];
            $monthlyData[$month]['completed_tasks'] += $data['completed_tasks'];
        }
        
        // Convert to array and sort
        $performanceData = array_values($monthlyData);
        usort($performanceData, function($a, $b) {
            return strcmp($a['month'], $b['month']);
        });
        
        echo json_encode(['success' => true, 'performance_data' => $performanceData]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy báo cáo hiệu suất: ' . $e->getMessage()]);
    }
}

function getWorkSummary() {
    try {
        $pdo = getDBConnection();
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
        
        $staffId = $staff['ID_NhanVien'];
        
        // Get work summary from both sources
        $workSummary = [];
        
        // From lichlamviec
        $stmt = $pdo->prepare("
            SELECT 
                llv.CongViec,
                llv.TrangThai,
                llv.Tiendo,
                llv.HanHoanThanh,
                dl.TenSuKien,
                dd.TenDiaDiem,
                llv.NgayTao,
                llv.NgayCapNhat,
                'lichlamviec' as source
            FROM lichlamviec llv
            LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE llv.ID_NhanVien = ?
            ORDER BY llv.NgayTao DESC
            LIMIT 10
        ");
        $stmt->execute([$staffId]);
        $lichlamviecData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // From chitietkehoach
        $stmt = $pdo->prepare("
            SELECT 
                ctk.TenBuoc as CongViec,
                ctk.TrangThai,
                COALESCE(ctk.TienDoPhanTram, 0) as Tiendo,
                ctk.NgayKetThuc as HanHoanThanh,
                dl.TenSuKien,
                dd.TenDiaDiem,
                ctk.NgayBatDau as NgayTao,
                ctk.NgayKetThuc as NgayCapNhat,
                'chitietkehoach' as source
            FROM chitietkehoach ctk
            LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
            LEFT JOIN datlichsukien dl ON kht.ID_DatLich = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE ctk.ID_NhanVien = ?
            ORDER BY ctk.NgayBatDau DESC
            LIMIT 10
        ");
        $stmt->execute([$staffId]);
        $chitietkehoachData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine and sort by date
        $allWork = array_merge($lichlamviecData, $chitietkehoachData);
        usort($allWork, function($a, $b) {
            return strtotime($b['NgayTao']) - strtotime($a['NgayTao']);
        });
        
        // Limit to 20 most recent
        $workSummary = array_slice($allWork, 0, 20);
        
        echo json_encode(['success' => true, 'work_summary' => $workSummary]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy tóm tắt công việc: ' . $e->getMessage()]);
    }
}

function exportReport() {
    try {
        $pdo = getDBConnection();
        $userId = $_SESSION['user']['ID_User'];
        $format = $_GET['format'] ?? 'csv';
        
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
        
        $staffId = $staff['ID_NhanVien'];
        
        // Get report data
        $stmt = $pdo->prepare("
            SELECT 
                llv.CongViec,
                llv.TrangThai,
                llv.Tiendo,
                llv.HanHoanThanh,
                llv.GhiChu,
                dl.TenSuKien,
                dd.TenDiaDiem,
                llv.NgayTao,
                llv.NgayCapNhat
            FROM lichlamviec llv
            LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE llv.ID_NhanVien = ?
            ORDER BY llv.NgayTao DESC
        ");
        $stmt->execute([$staffId]);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="staff_report_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Công việc', 'Trạng thái', 'Tiến độ', 'Hạn hoàn thành', 'Ghi chú', 'Sự kiện', 'Địa điểm', 'Ngày tạo', 'Ngày cập nhật']);
            
            foreach ($reportData as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
        } else {
            echo json_encode(['success' => true, 'data' => $reportData]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xuất báo cáo: ' . $e->getMessage()]);
    }
}

function submitProgressReport() {
    try {
        $pdo = getDBConnection();
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
        
        $staffId = $staff['ID_NhanVien'];
        $managerId = $_POST['manager_id'] ?? '';
        $taskId = $_POST['task_id'] ?? '';
        $taskType = $_POST['task_type'] ?? ''; // 'lichlamviec' or 'chitietkehoach'
        $progress = $_POST['progress'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($managerId) || empty($taskId) || empty($taskType)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Validate manager exists and has role 2
        $stmt = $pdo->prepare("
            SELECT nv.ID_NhanVien, u.ID_Role 
            FROM nhanvieninfo nv 
            JOIN users u ON nv.ID_User = u.ID_User 
            WHERE nv.ID_NhanVien = ? AND u.ID_Role = 2
        ");
        $stmt->execute([$managerId]);
        $manager = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$manager) {
            echo json_encode(['success' => false, 'message' => 'Quản lý không hợp lệ hoặc không có quyền']);
            return;
        }
        
        // Validate task exists and belongs to staff
        $taskExists = false;
        if ($taskType === 'lichlamviec') {
            $stmt = $pdo->prepare("SELECT ID_LLV FROM lichlamviec WHERE ID_LLV = ? AND ID_NhanVien = ?");
            $stmt->execute([$taskId, $staffId]);
            $taskExists = $stmt->fetch() !== false;
        } elseif ($taskType === 'chitietkehoach') {
            $stmt = $pdo->prepare("SELECT ID_ChiTiet FROM chitietkehoach WHERE ID_ChiTiet = ? AND ID_NhanVien = ?");
            $stmt->execute([$taskId, $staffId]);
            $taskExists = $stmt->fetch() !== false;
        }
        
        if (!$taskExists) {
            echo json_encode(['success' => false, 'message' => 'Công việc không tồn tại hoặc không thuộc về bạn']);
            return;
        }
        
        // Create table if not exists
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS baocaotiendo (
                ID_BaoCao INT AUTO_INCREMENT PRIMARY KEY,
                ID_NhanVien INT NOT NULL,
                ID_QuanLy INT NOT NULL,
                ID_Task INT NOT NULL,
                LoaiTask ENUM('lichlamviec', 'chitietkehoach') NOT NULL,
                TienDo INT DEFAULT 0,
                GhiChu TEXT,
                TrangThai VARCHAR(50),
                NgayBaoCao DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $pdo->exec($createTableSQL);
        
        // Insert progress report
        $stmt = $pdo->prepare("
            INSERT INTO baocaotiendo (
                ID_NhanVien, 
                ID_QuanLy, 
                ID_Task, 
                LoaiTask, 
                TienDo, 
                GhiChu, 
                TrangThai, 
                NgayBaoCao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $staffId, 
            $managerId, 
            $taskId, 
            $taskType, 
            $progress, 
            $notes, 
            $status
        ]);
        
        if ($result) {
            // Update the original task if status is provided
            if (!empty($status)) {
                if ($taskType === 'lichlamviec') {
                    $updateStmt = $pdo->prepare("
                        UPDATE lichlamviec 
                        SET TrangThai = ?, Tiendo = ?, NgayCapNhat = NOW() 
                        WHERE ID_LLV = ? AND ID_NhanVien = ?
                    ");
                    $updateStmt->execute([$status, $progress, $taskId, $staffId]);
                } elseif ($taskType === 'chitietkehoach') {
                    $updateStmt = $pdo->prepare("
                        UPDATE chitietkehoach 
                        SET TrangThai = ?, NgayCapNhat = NOW() 
                        WHERE ID_ChiTiet = ? AND ID_NhanVien = ?
                    ");
                    $updateStmt->execute([$status, $taskId, $staffId]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Gửi báo cáo tiến độ thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi báo cáo']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi báo cáo tiến độ: ' . $e->getMessage()]);
    }
}

function getProgressReports() {
    try {
        $pdo = getDBConnection();
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
        
        $staffId = $staff['ID_NhanVien'];
        
        // Create table if not exists
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS baocaotiendo (
                ID_BaoCao INT AUTO_INCREMENT PRIMARY KEY,
                ID_NhanVien INT NOT NULL,
                ID_QuanLy INT NOT NULL,
                ID_Task INT NOT NULL,
                LoaiTask ENUM('lichlamviec', 'chitietkehoach') NOT NULL,
                TienDo INT DEFAULT 0,
                GhiChu TEXT,
                TrangThai VARCHAR(50),
                NgayBaoCao DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $pdo->exec($createTableSQL);
        
        // Get progress reports
        $stmt = $pdo->prepare("
            SELECT 
                bct.ID_BaoCao,
                bct.TienDo,
                bct.GhiChu,
                bct.TrangThai,
                bct.NgayBaoCao,
                nv.HoTen as TenNhanVien,
                ql.HoTen as TenQuanLy,
                CASE 
                    WHEN bct.LoaiTask = 'lichlamviec' THEN llv.NhiemVu
                    WHEN bct.LoaiTask = 'chitietkehoach' THEN ctk.TenBuoc
                END as TenCongViec
            FROM baocaotiendo bct
            LEFT JOIN nhanvieninfo nv ON bct.ID_NhanVien = nv.ID_NhanVien
            LEFT JOIN nhanvieninfo ql ON bct.ID_QuanLy = ql.ID_NhanVien
            LEFT JOIN lichlamviec llv ON bct.ID_Task = llv.ID_LLV AND bct.LoaiTask = 'lichlamviec'
            LEFT JOIN chitietkehoach ctk ON bct.ID_Task = ctk.ID_ChiTiet AND bct.LoaiTask = 'chitietkehoach'
            WHERE bct.ID_NhanVien = ?
            ORDER BY bct.NgayBaoCao DESC
        ");
        $stmt->execute([$staffId]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'reports' => $reports]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy báo cáo tiến độ: ' . $e->getMessage()]);
    }
}

function getManagers() {
    try {
        $pdo = getDBConnection();
        
        // Get managers (Role 2)
        $stmt = $pdo->prepare("
            SELECT 
                nv.ID_NhanVien,
                nv.HoTen,
                nv.ChucVu,
                u.Email
            FROM nhanvieninfo nv
            JOIN users u ON nv.ID_User = u.ID_User
            WHERE u.ID_Role = 2
            ORDER BY nv.HoTen
        ");
        $stmt->execute();
        $managers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'managers' => $managers]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách quản lý: ' . $e->getMessage()]);
    }
}
?>
