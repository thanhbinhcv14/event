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
                NgayKetThuc as HanHoanThanh
            FROM lichlamviec 
            WHERE ID_NhanVien = ?
        ");
        $stmt->execute([$staffId]);
        $lichlamviecData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // From chitietkehoach - get progress from lichlamviec if exists
        // Note: chitietkehoach can have multiple staff assigned via lichlamviec
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                'chitietkehoach' as source,
                ctk.ID_ChiTiet as id,
                ctk.TenBuoc as task,
                COALESCE(llv.TrangThai, ctk.TrangThai) as TrangThai,
                COALESCE(llv.TienDo, '0%') as Tiendo,
                ctk.NgayBatDau as NgayTao,
                ctk.NgayKetThuc as NgayCapNhat,
                ctk.NgayKetThuc as HanHoanThanh
            FROM chitietkehoach ctk
            LEFT JOIN lichlamviec llv ON ctk.ID_ChiTiet = llv.ID_ChiTiet AND llv.ID_NhanVien = ?
            WHERE llv.ID_NhanVien = ?
        ");
        $stmt->execute([$staffId, $staffId]);
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
        
        // Calculate pending and issues
        $pendingAssignments = count(array_filter($allAssignments, function($a) { 
            return $a['TrangThai'] === 'Chưa làm' || $a['TrangThai'] === 'Chưa bắt đầu'; 
        }));
        $issueAssignments = count(array_filter($allAssignments, function($a) { 
            return $a['TrangThai'] === 'Báo sự cố'; 
        }));
        
        // Get current tasks (in progress or pending)
        $currentTasks = $inProgressAssignments + $pendingAssignments;
        
        // Get event type stats
        $eventTypeStats = [];
        // Group by event type from lichlamviec
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(ls.TenLoai, 'Chưa phân loại') as event_type,
                COUNT(*) as assignments,
                SUM(CASE WHEN llv.TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed
            FROM lichlamviec llv
            LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            WHERE llv.ID_NhanVien = ?
            GROUP BY ls.TenLoai
        ");
        $stmt->execute([$staffId]);
        $eventTypeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($eventTypeData as $data) {
            $completionRate = $data['assignments'] > 0 ? round(($data['completed'] / $data['assignments']) * 100, 2) : 0;
            $eventTypeStats[] = [
                'event_type' => $data['event_type'],
                'assignments' => (int)$data['assignments'],
                'completed' => (int)$data['completed'],
                'completion_rate' => $completionRate
            ];
        }
        
        $stats = [
            'total_assignments' => $totalAssignments,
            'completed' => $completedAssignments,
            'in_progress' => $inProgressAssignments,
            'pending' => $pendingAssignments,
            'issues' => $issueAssignments,
            'completion_rate' => $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100, 2) : 0,
            'current_tasks' => $currentTasks,
            'overdue_tasks' => $overdueAssignments,
            'avg_completion_hours' => round($avgCompletionTime, 2),
            'source_breakdown' => [
                'lichlamviec' => count($lichlamviecData),
                'chitietkehoach' => count($chitietkehoachData)
            ]
        ];
        
        echo json_encode(['success' => true, 'stats' => $stats, 'eventTypeStats' => $eventTypeStats]);
        
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
        
        // Get detailed reports for the date range
        $reports = [];
        
        // From lichlamviec
        $stmt = $pdo->prepare("
            SELECT 
                llv.NhiemVu,
                llv.TrangThai,
                llv.Tiendo,
                llv.NgayBatDau,
                llv.NgayKetThuc,
                dl.TenSuKien,
                COALESCE(dd.TenDiaDiem, 'N/A') as TenDiaDiem
            FROM lichlamviec llv
            LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE llv.ID_NhanVien = ? 
            AND DATE(llv.NgayTao) BETWEEN ? AND ?
            ORDER BY llv.NgayTao DESC
        ");
        $stmt->execute([$staffId, $startDate, $endDate]);
        $lichlamviecReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // From chitietkehoach
        $stmt = $pdo->prepare("
            SELECT 
                ctk.TenBuoc as NhiemVu,
                ctk.TrangThai,
                COALESCE(llv.TienDo, '0%') as Tiendo,
                ctk.NgayBatDau,
                ctk.NgayKetThuc,
                COALESCE(dl.TenSuKien, 'N/A') as TenSuKien,
                COALESCE(dd.TenDiaDiem, 'N/A') as TenDiaDiem
            FROM chitietkehoach ctk
            LEFT JOIN lichlamviec llv ON ctk.ID_ChiTiet = llv.ID_ChiTiet AND llv.ID_NhanVien = ?
            LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
            LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
            LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE ctk.ID_NhanVien = ? 
            AND DATE(ctk.NgayBatDau) BETWEEN ? AND ?
            ORDER BY ctk.NgayBatDau DESC
        ");
        $stmt->execute([$staffId, $staffId, $startDate, $endDate]);
        $chitietkehoachReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine reports
        $reports = array_merge($lichlamviecReports, $chitietkehoachReports);
        
        // Calculate summary
        $total = count($reports);
        $completed = count(array_filter($reports, function($r) { return $r['TrangThai'] === 'Hoàn thành'; }));
        $inProgress = count(array_filter($reports, function($r) { 
            return $r['TrangThai'] === 'Đang làm' || $r['TrangThai'] === 'Đang thực hiện'; 
        }));
        $issues = count(array_filter($reports, function($r) { return $r['TrangThai'] === 'Báo sự cố'; }));
        
        echo json_encode([
            'success' => true, 
            'performance_data' => $performanceData,
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'issues' => $issues
            ],
            'reports' => $reports
        ]);
        
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
                llv.NgayKetThuc as HanHoanThanh,
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
        
        // From chitietkehoach - get progress from lichlamviec if exists
        $stmt = $pdo->prepare("
            SELECT 
                ctk.TenBuoc as CongViec,
                ctk.TrangThai,
                COALESCE(llv.TienDo, '0%') as Tiendo,
                ctk.NgayKetThuc as HanHoanThanh,
                dl.TenSuKien,
                dd.TenDiaDiem,
                ctk.NgayBatDau as NgayTao,
                ctk.NgayKetThuc as NgayCapNhat,
                'chitietkehoach' as source
            FROM chitietkehoach ctk
            LEFT JOIN lichlamviec llv ON ctk.ID_ChiTiet = llv.ID_ChiTiet AND llv.ID_NhanVien = ?
            LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
            LEFT JOIN datlichsukien dl ON kht.ID_DatLich = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE ctk.ID_NhanVien = ?
            ORDER BY ctk.NgayBatDau DESC
            LIMIT 10
        ");
        $stmt->execute([$staffId, $staffId]);
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
                llv.NgayKetThuc as HanHoanThanh,
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
        
        // Check if report already exists for this task
        $checkStmt = $pdo->prepare("
            SELECT ID_BaoCao FROM baocaotiendo 
            WHERE ID_NhanVien = ? AND ID_Task = ? AND LoaiTask = ?
            ORDER BY NgayBaoCao DESC LIMIT 1
        ");
        $checkStmt->execute([$staffId, $taskId, $taskType]);
        $existingReport = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingReport) {
            // Update existing report
            $stmt = $pdo->prepare("
                UPDATE baocaotiendo 
                SET TienDo = ?, 
                    GhiChu = ?, 
                    TrangThai = ?,
                    NgayBaoCao = NOW()
                WHERE ID_BaoCao = ?
            ");
            $result = $stmt->execute([
                $progress, 
                $notes, 
                $status,
                $existingReport['ID_BaoCao']
            ]);
        } else {
            // Insert new progress report
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
        }
        
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
        
        // Get progress reports with event information
        // Use subquery to get only the latest report for each task (ID_Task + LoaiTask combination)
        $stmt = $pdo->prepare("
            SELECT 
                bct.ID_BaoCao,
                bct.TienDo,
                bct.GhiChu,
                bct.TrangThai,
                bct.NgayBaoCao,
                bct.LoaiTask,
                nv.HoTen as TenNhanVien,
                ql.HoTen as TenQuanLy,
                ql.ChucVu as ChucVuQuanLy,
                CASE 
                    WHEN bct.LoaiTask = 'lichlamviec' THEN COALESCE(llv.NhiemVu, llv.CongViec, 'Nhiệm vụ')
                    WHEN bct.LoaiTask = 'chitietkehoach' THEN COALESCE(ctk.TenBuoc, 'Bước thực hiện')
                END as TenCongViec,
                COALESCE(dl1.TenSuKien, dl2.TenSuKien, 'N/A') as TenSuKien,
                COALESCE(dd1.TenDiaDiem, dd2.TenDiaDiem, 'N/A') as TenDiaDiem
            FROM baocaotiendo bct
            INNER JOIN (
                SELECT ID_Task, LoaiTask, MAX(ID_BaoCao) as MaxID_BaoCao
                FROM baocaotiendo
                WHERE ID_NhanVien = ?
                GROUP BY ID_Task, LoaiTask
            ) latest ON bct.ID_Task = latest.ID_Task 
                AND bct.LoaiTask = latest.LoaiTask 
                AND bct.ID_BaoCao = latest.MaxID_BaoCao
            LEFT JOIN nhanvieninfo nv ON bct.ID_NhanVien = nv.ID_NhanVien
            LEFT JOIN nhanvieninfo ql ON bct.ID_QuanLy = ql.ID_NhanVien
            LEFT JOIN lichlamviec llv ON bct.ID_Task = llv.ID_LLV AND bct.LoaiTask = 'lichlamviec'
            LEFT JOIN datlichsukien dl1 ON llv.ID_DatLich = dl1.ID_DatLich
            LEFT JOIN diadiem dd1 ON dl1.ID_DD = dd1.ID_DD
            LEFT JOIN chitietkehoach ctk ON bct.ID_Task = ctk.ID_ChiTiet AND bct.LoaiTask = 'chitietkehoach'
            LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
            LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
            LEFT JOIN datlichsukien dl2 ON s.ID_DatLich = dl2.ID_DatLich
            LEFT JOIN diadiem dd2 ON dl2.ID_DD = dd2.ID_DD
            WHERE bct.ID_NhanVien = ?
            ORDER BY bct.NgayBaoCao DESC
        ");
        $stmt->execute([$staffId, $staffId]);
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
