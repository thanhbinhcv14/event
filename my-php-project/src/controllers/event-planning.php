<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

// Start session
session_start();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and has role 2
if (!isLoggedIn()) {
    error_log("Event Planning API: User not logged in. Session data: " . print_r($_SESSION, true));
    error_log("Event Planning API: Request URI: " . $_SERVER['REQUEST_URI']);
    error_log("Event Planning API: Request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập', 'message' => 'Vui lòng đăng nhập để tiếp tục']);
    exit;
}

$user = getCurrentUser();
if (!in_array($user['ID_Role'], [1, 2, 3])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Debug action
error_log("Event Planning Action: " . $action);
error_log("All GET data: " . json_encode($_GET));
error_log("All POST data: " . json_encode($_POST));

try {
    $pdo = getDBConnection();

    switch ($action) {
        case 'get_events':
            getEvents($pdo);
            break;
            
        case 'create_plan':
            createPlan($pdo);
            break;
            
        case 'update_plan':
            updatePlanById($pdo);
            break;
            
        case 'delete_plan':
            deletePlan($pdo);
            break;
            
        case 'add_plan_step':
            addPlanStep($pdo);
            break;
            
        case 'get_staff':
            getStaff($pdo);
            break;
            
        case 'get_staff_list':
            getStaffList($pdo);
            break;
            
        case 'get_plan_details':
            getPlanDetails($pdo);
            break;
            
        case 'get_event_steps':
            getEventSteps($pdo);
            break;
            
        case 'get_staff_tasks':
        getStaffTasks($pdo);
        break;
    case 'get_plan_steps':
            getPlanSteps($pdo);
            break;
            
        case 'add_event_step':
            addEventStep($pdo);
            break;
            
        case 'get_approved_events':
            getApprovedEvents($pdo);
            break;
            
        case 'get_plans':
            getPlans($pdo);
            break;
            
        case 'auto_approve_events':
            autoApproveEvents($pdo);
            break;
            
        case 'update_step_status':
            updateStepStatus($pdo);
            break;
            
        case 'delete_step':
            deleteStep($pdo);
            break;
            
        case 'get_plan':
            getPlanById($pdo);
            break;
            
        case 'update_plan':
            updatePlanById($pdo);
            break;
            
        case 'get_step':
            getStepById($pdo);
            break;
            
        case 'update_step':
            updateStepById($pdo);
            break;
            
        case 'delete_plan':
            deletePlan($pdo);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ']);
            break;
    }
} catch (Exception $e) {
    error_log("Event Planning Error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    exit;
}

function getEvents($pdo) {
    try {
        // Get all approved events with their planning status
        $sql = "
            SELECT 
                dl.ID_DatLich,
                dl.TenSuKien,
                dl.MoTa,
                dl.NgayBatDau,
                dl.NgayKetThuc,
                dl.SoNguoiDuKien,
                dl.NganSach,
                dl.TrangThaiDuyet,
                dd.TenDiaDiem,
                dd.DiaChi,
                dd.HinhAnh,
                ls.TenLoai as TenLoaiSK,
                CASE 
                    WHEN kp.ID_KeHoach IS NOT NULL THEN COALESCE(kp.TrangThai, 'Đã lập kế hoạch')
                    ELSE 'Chưa lập kế hoạch'
                END as TrangThaiKeHoach,
                kp.NgayBatDau as NgayBatDauThucHien,
                kp.NoiDung as MoTaKeHoach,
                kp.ngay_tao as GhiChu
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN kehoachthuchien kp ON dl.ID_DatLich = kp.ID_SuKien
            WHERE dl.TrangThaiDuyet = 'Đã duyệt'
            ORDER BY dl.NgayBatDau DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'events' => $events
        ]);
        
    } catch (Exception $e) {
        error_log("Get Events Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi lấy danh sách sự kiện: ' . $e->getMessage()
        ]);
    }
}


function getPlan($pdo) {
    try {
        $eventId = $_GET['event_id'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode([
                'success' => false,
                'error' => 'ID sự kiện không hợp lệ'
            ]);
            return;
        }
        
        $sql = "
            SELECT 
                kp.*,
                dl.TenSuKien,
                dd.TenDiaDiem
            FROM kehoachthuchien kp
            JOIN datlichsukien dl ON kp.ID_SuKien = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE kp.ID_SuKien = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$eventId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            echo json_encode([
                'success' => true,
                'plan' => $plan
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Không tìm thấy kế hoạch'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Get Plan Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi lấy thông tin kế hoạch: ' . $e->getMessage()
        ]);
    }
}


function deletePlan($pdo) {
    try {
        $planId = $_POST['planId'] ?? '';
        $eventId = $_POST['event_id'] ?? '';
        
        if (!empty($planId)) {
            // Delete by plan ID
            // Delete related steps first
            $stmt = $pdo->prepare("DELETE FROM chitietkehoach WHERE ID_KeHoach = ?");
            $stmt->execute([$planId]);
            
            // Delete the plan
            $stmt = $pdo->prepare("DELETE FROM kehoachthuchien WHERE ID_KeHoach = ?");
            $result = $stmt->execute([$planId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Xóa kế hoạch thành công'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Không thể xóa kế hoạch'
                ]);
            }
            return;
        } elseif (!empty($eventId)) {
            // Delete by event ID (legacy)
            $sql = "DELETE FROM kehoachthuchien WHERE ID_SuKien = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$eventId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Xóa kế hoạch thành công'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Không thể xóa kế hoạch'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Thiếu ID kế hoạch hoặc ID sự kiện'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Delete Plan Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi xóa kế hoạch: ' . $e->getMessage()
        ]);
    }
}

function addPlanStep($pdo) {
    try {
        $eventId = $_POST['event_id'] ?? $_POST['eventId'] ?? '';
        $stepName = $_POST['stepName'] ?? '';
        $stepDescription = $_POST['stepContent'] ?? $_POST['stepDescription'] ?? '';
        $stepStartDate = $_POST['stepStartDate'] ?? '';
        $stepEndDate = $_POST['stepEndDate'] ?? '';
        $staffId = $_POST['staffId'] ?? '';
        $priority = $_POST['priority'] ?? '';
        $note = $_POST['note'] ?? '';
        
        // Debug variables
        error_log("=== ADD PLAN STEP DEBUG ===");
        error_log("eventId: '$eventId'");
        error_log("stepName: '$stepName'");
        error_log("stepDescription: '$stepDescription'");
        error_log("stepStartDate: '$stepStartDate'");
        error_log("stepEndDate: '$stepEndDate'");
        error_log("staffId: '$staffId'");
        error_log("priority: '$priority'");
        error_log("note: '$note'");
        error_log("POST data: " . print_r($_POST, true));
        
        if (empty($eventId) || empty($stepName) || empty($stepStartDate) || empty($stepEndDate)) {
            $missing = [];
            if (empty($eventId)) $missing[] = 'eventId';
            if (empty($stepName)) $missing[] = 'stepName';
            if (empty($stepStartDate)) $missing[] = 'stepStartDate';
            if (empty($stepEndDate)) $missing[] = 'stepEndDate';
            
            echo json_encode([
                'success' => false,
                'error' => 'Vui lòng điền đầy đủ thông tin bắt buộc. Thiếu: ' . implode(', ', $missing)
            ]);
            return;
        }
        
        // Get plan ID from event ID - tìm kế hoạch thông qua datlichsukien
        $planStmt = $pdo->prepare("
            SELECT kht.ID_KeHoach 
            FROM kehoachthuchien kht
            LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
            WHERE s.ID_DatLich = ? OR kht.ID_SuKien = ?
        ");
        $planStmt->execute([$eventId, $eventId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            echo json_encode([
                'success' => false,
                'error' => 'Không tìm thấy kế hoạch cho sự kiện này'
            ]);
            return;
        }
        
        // Insert new step
        $sql = "
            INSERT INTO chitietkehoach 
            (ID_KeHoach, TenBuoc, MoTa, NgayBatDau, NgayKetThuc, TrangThai, ID_NhanVien, GhiChu)
            VALUES (?, ?, ?, ?, ?, 'Chưa làm', ?, ?)
        ";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $plan['ID_KeHoach'],
            $stepName,
            $stepDescription,
            $stepStartDate,
            $stepEndDate,
            $staffId ?: null,
            $note
        ]);
        
        // If step was created successfully and staff is assigned, create work schedule
        if ($result && $staffId) {
            $stepId = $pdo->lastInsertId();
            
            // Get event details for work schedule
            $eventStmt = $pdo->prepare("
                SELECT dl.ID_DatLich, dl.TenSuKien
                FROM datlichsukien dl
                LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
                WHERE s.ID_SuKien = ? OR dl.ID_DatLich = ?
            ");
            $eventStmt->execute([$eventId, $eventId]);
            $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                // Create work schedule entry
                $scheduleSql = "
                    INSERT INTO lichlamviec 
                    (ID_DatLich, ID_NhanVien, NhiemVu, NgayBatDau, NgayKetThuc, TrangThai, ID_ChiTiet, CongViec, NgayTao)
                    VALUES (?, ?, ?, ?, ?, 'Chưa làm', ?, ?, NOW())
                ";
                
                $scheduleStmt = $pdo->prepare($scheduleSql);
                $scheduleResult = $scheduleStmt->execute([
                    $event['ID_DatLich'],
                    $staffId,
                    $stepName,
                    $stepStartDate,
                    $stepEndDate,
                    $stepId,
                    $stepDescription
                ]);
                
                error_log("Work schedule created for plan step: " . ($scheduleResult ? 'SUCCESS' : 'FAILED'));
                if ($scheduleResult) {
                    error_log("Work schedule ID: " . $pdo->lastInsertId());
                }
            }
        }
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Thêm bước kế hoạch thành công' . ($staffId ? ' và đã tạo lịch làm việc' : '')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi thêm bước kế hoạch'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Add Plan Step Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi thêm bước kế hoạch: ' . $e->getMessage()
        ]);
    }
}

function getStaff($pdo) {
    try {
        $sql = "
            SELECT 
                nv.ID_NhanVien,
                nv.HoTen,
                nv.ChucVu,
                nv.SoDienThoai,
                nv.DiaChi
            FROM nhanvieninfo nv
            LEFT JOIN users u ON nv.ID_User = u.ID_User
            WHERE u.TrangThai = 'Hoạt động' AND u.ID_Role = 2
            ORDER BY nv.HoTen ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'staff' => $staff
        ]);
        
    } catch (Exception $e) {
        error_log("Get Staff Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage()
        ]);
    }
}

function getStaffList($pdo) {
    try {
        $sql = "
            SELECT 
                nv.ID_NhanVien,
                nv.HoTen,
                nv.ChucVu
            FROM nhanvieninfo nv
            LEFT JOIN users u ON nv.ID_User = u.ID_User
            WHERE u.TrangThai = 'Hoạt động' AND u.ID_Role = 4
            ORDER BY nv.HoTen ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'staff' => $staff
        ]);
        
    } catch (Exception $e) {
        error_log("Get Staff List Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage()
        ]);
    }
}

function getPlanDetails($pdo) {
    try {
        $eventId = $_GET['event_id'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode(['success' => false, 'error' => 'Thiếu thông tin sự kiện']);
            return;
        }
        
        $sql = "
            SELECT 
                k.*,
                dl.TenSuKien,
                dl.NgayBatDau,
                dl.NgayKetThuc
            FROM kehoachthuchien k
            LEFT JOIN datlichsukien dl ON k.ID_SuKien = dl.ID_DatLich
            WHERE k.ID_SuKien = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$eventId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            echo json_encode(['success' => true, 'plan' => $plan]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy kế hoạch']);
        }
        
    } catch (Exception $e) {
        error_log("Get Plan Details Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy chi tiết kế hoạch: ' . $e->getMessage()]);
    }
}

function getPlanSteps($pdo) {
    try {
        $planId = $_GET['plan_id'] ?? '';
        
        if (empty($planId)) {
            echo json_encode(['success' => false, 'error' => 'Thiếu thông tin kế hoạch']);
            return;
        }
        
        $sql = "
            SELECT 
                c.*,
                nv.HoTen as TenNhanVien,
                nv.ChucVu,
                nv.SoDienThoai
            FROM chitietkehoach c
            LEFT JOIN nhanvieninfo nv ON c.ID_NhanVien = nv.ID_NhanVien
            WHERE c.ID_KeHoach = ?
            ORDER BY c.NgayBatDau ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$planId]);
        $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'steps' => $steps]);
        
    } catch (Exception $e) {
        error_log("Get Plan Steps Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy danh sách bước: ' . $e->getMessage()]);
    }
}

function getEventSteps($pdo) {
    try {
        $eventId = $_GET['event_id'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode(['success' => false, 'error' => 'Thiếu thông tin sự kiện']);
            return;
        }
        
        $sql = "
            SELECT 
                c.*,
                nv.HoTen as TenNhanVien,
                nv.ChucVu,
                nv.SoDienThoai,
                COALESCE(llv.Tiendo, '0%') as Tiendo,
                COALESCE(llv.GhiChu, NULL) as GhiChuTienDo,
                GROUP_CONCAT(DISTINCT CONCAT(llv_nv.HoTen, ' - ', llv_nv.ChucVu) SEPARATOR ', ') as AllStaffNames
            FROM chitietkehoach c
            LEFT JOIN kehoachthuchien k ON c.ID_KeHoach = k.ID_KeHoach
            LEFT JOIN sukien s ON k.ID_SuKien = s.ID_SuKien
            LEFT JOIN nhanvieninfo nv ON c.ID_NhanVien = nv.ID_NhanVien
            LEFT JOIN lichlamviec llv ON llv.ID_ChiTiet = c.ID_ChiTiet
            LEFT JOIN nhanvieninfo llv_nv ON llv.ID_NhanVien = llv_nv.ID_NhanVien
            WHERE s.ID_DatLich = ? OR k.ID_SuKien = ?
            GROUP BY c.ID_ChiTiet
            ORDER BY c.NgayBatDau ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$eventId, $eventId]);
        $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // For each step, get all assigned staff IDs from lichlamviec
        foreach ($steps as &$step) {
            $staffStmt = $pdo->prepare("
                SELECT DISTINCT llv.ID_NhanVien, nv.HoTen, nv.ChucVu
                FROM lichlamviec llv
                LEFT JOIN nhanvieninfo nv ON llv.ID_NhanVien = nv.ID_NhanVien
                WHERE llv.ID_ChiTiet = ?
            ");
            $staffStmt->execute([$step['ID_ChiTiet']]);
            $step['assignedStaff'] = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($step);
        
        error_log("DEBUG: getEventSteps - eventId: $eventId");
        error_log("DEBUG: getEventSteps - steps count: " . count($steps));
        if (!empty($steps)) {
            error_log("DEBUG: getEventSteps - first step: " . json_encode($steps[0]));
        }
        
        echo json_encode(['success' => true, 'steps' => $steps]);
        
    } catch (Exception $e) {
        error_log("Get Event Steps Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy danh sách bước: ' . $e->getMessage()]);
    }
}

function getApprovedEvents($pdo) {
    try {
        $sql = "
            SELECT 
                dl.ID_DatLich,
                dl.TenSuKien,
                dl.NgayBatDau,
                dl.NgayKetThuc,
                dl.SoNguoiDuKien,
                dl.NganSach,
                dl.TrangThaiDuyet,
                COALESCE(dd.TenDiaDiem, 'Chưa xác định') as TenDiaDiem,
                COALESCE(dd.DiaChi, 'Chưa xác định') as DiaChi,
                COALESCE(ls.TenLoai, 'Chưa phân loại') as TenLoaiSK,
                COALESCE(kh.HoTen, 'Chưa có thông tin') as TenKhachHang,
                COALESCE(kh.SoDienThoai, 'Chưa có') as SoDienThoai
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            WHERE dl.TrangThaiDuyet = 'Đã duyệt'
            ORDER BY dl.NgayBatDau ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'events' => $events]);
        
    } catch (Exception $e) {
        error_log("Get Approved Events Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy danh sách sự kiện: ' . $e->getMessage()]);
    }
}

function getPlans($pdo) {
    try {
        $eventId = $_GET['event_id'] ?? '';
        
        $whereClause = '';
        $params = [];
        
        if (!empty($eventId)) {
            $whereClause = 'WHERE dl.ID_DatLich = ?';
            $params[] = $eventId;
        }
        
        // First, update all plan statuses before fetching
        updateAllPlanStatuses($pdo, $eventId);
        
        $sql = "
            SELECT 
                kht.ID_KeHoach,
                kht.ID_SuKien,
                kht.TenKeHoach,
                kht.NoiDung,
                kht.NgayBatDau,
                kht.NgayKetThuc,
                kht.TrangThai,
                kht.ID_NhanVien,
                s.ID_DatLich,
                dl.TenSuKien,
                nv.HoTen as TenNhanVien,
                nv.ChucVu,
                nv.SoDienThoai
            FROM kehoachthuchien kht
            LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
            LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
            LEFT JOIN nhanvieninfo nv ON kht.ID_NhanVien = nv.ID_NhanVien
            {$whereClause}
            ORDER BY kht.NgayBatDau ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'plans' => $plans]);
        
    } catch (Exception $e) {
        error_log("Get Plans Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy danh sách kế hoạch: ' . $e->getMessage()]);
    }
}



function autoApproveEvents($pdo) {
    try {
        $sql = "UPDATE datlichsukien SET TrangThaiDuyet = 'Đã duyệt' WHERE TrangThaiDuyet = 'Chờ duyệt' LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute();
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Đã duyệt 5 sự kiện thành công']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không có sự kiện nào để duyệt']);
        }
        
    } catch (Exception $e) {
        error_log("Auto Approve Events Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi duyệt sự kiện: ' . $e->getMessage()]);
    }
}


function createPlan($pdo) {
    try {
        $eventId = $_POST['eventId'] ?? $_POST['event_id'] ?? '';
        $planName = $_POST['planName'] ?? $_POST['plan_name'] ?? '';
        $startDate = $_POST['startDate'] ?? $_POST['start_date'] ?? '';
        $endDate = $_POST['endDate'] ?? $_POST['end_date'] ?? '';
        $content = $_POST['planContent'] ?? $_POST['planDescription'] ?? $_POST['content'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        // Force get planContent if content is empty
        if (empty($content) && !empty($_POST['planContent'])) {
            $content = $_POST['planContent'];
        }
        
        // Force get planDescription if content is empty
        if (empty($content) && !empty($_POST['planDescription'])) {
            $content = $_POST['planDescription'];
        }
        
        // Additional fallback - check all possible field names
        if (empty($content)) {
            $content = $_POST['planContent'] ?? $_POST['planDescription'] ?? $_POST['content'] ?? $_POST['plan_content'] ?? '';
        }
        
        // Final fallback - explicitly check planContent field
        if (empty($content) && isset($_POST['planContent'])) {
            $content = $_POST['planContent'];
        }
        
        // Final fallback - explicitly check planDescription field
        if (empty($content) && isset($_POST['planDescription'])) {
            $content = $_POST['planDescription'];
        }
        
        // Ultimate fallback - check if planContent exists and content is still empty
        if (empty($content) && !empty($_POST['planContent'])) {
            $content = trim($_POST['planContent']);
            error_log("Ultimate fallback: Set content from planContent = '$content'");
        }
        
        // Ultimate fallback - check if planDescription exists and content is still empty
        if (empty($content) && !empty($_POST['planDescription'])) {
            $content = trim($_POST['planDescription']);
            error_log("Ultimate fallback: Set content from planDescription = '$content'");
        }
        
        // Debug each field individually
        error_log("=== FIELD DEBUG ===");
        error_log("Field check - eventId: '" . ($_POST['eventId'] ?? 'NOT_SET') . "'");
        error_log("Field check - planName: '" . ($_POST['planName'] ?? 'NOT_SET') . "'");
        error_log("Field check - startDate: '" . ($_POST['startDate'] ?? 'NOT_SET') . "'");
        error_log("Field check - endDate: '" . ($_POST['endDate'] ?? 'NOT_SET') . "'");
        error_log("Field check - planContent: '" . ($_POST['planContent'] ?? 'NOT_SET') . "'");
        error_log("Field check - planDescription: '" . ($_POST['planDescription'] ?? 'NOT_SET') . "'");
        error_log("Field check - content: '" . ($_POST['content'] ?? 'NOT_SET') . "'");
        error_log("Final content value: '$content'");
        error_log("Content empty check: " . (empty($content) ? 'TRUE' : 'FALSE'));
        error_log("Content length: " . strlen($content));
        
        // Debug POST data
        error_log("Create Plan POST data: " . print_r($_POST, true));
        error_log("Raw POST: " . file_get_contents('php://input'));
        error_log("Parsed data - eventId: '$eventId', planName: '$planName', startDate: '$startDate', endDate: '$endDate', content: '$content'");
        
        // Debug all POST keys
        error_log("All POST keys: " . implode(', ', array_keys($_POST)));
        error_log("POST values: " . json_encode($_POST));
        
        // Debug validation
        error_log("Validation check - eventId: '$eventId', planName: '$planName', startDate: '$startDate', endDate: '$endDate', content: '$content'");
        
        if (empty($eventId) || empty($planName) || empty($startDate) || empty($endDate) || empty($content)) {
            $missing = [];
            if (empty($eventId)) $missing[] = 'eventId';
            if (empty($planName)) $missing[] = 'planName';
            if (empty($startDate)) $missing[] = 'startDate';
            if (empty($endDate)) $missing[] = 'endDate';
            if (empty($content)) $missing[] = 'planContent/planDescription';
            
            // Additional debug for content field
            error_log("Content field debug:");
            error_log("  - \$_POST['planContent']: '" . ($_POST['planContent'] ?? 'NOT_SET') . "'");
            error_log("  - \$_POST['planDescription']: '" . ($_POST['planDescription'] ?? 'NOT_SET') . "'");
            error_log("  - \$_POST['content']: '" . ($_POST['content'] ?? 'NOT_SET') . "'");
            error_log("  - Final content: '$content'");
            error_log("  - Content empty check: " . (empty($content) ? 'TRUE' : 'FALSE'));
            
            echo json_encode([
                'success' => false,
                'error' => 'Vui lòng điền đầy đủ thông tin bắt buộc. Thiếu: ' . implode(', ', $missing)
            ]);
            return;
        }
        
        // Check payment status - only allow creating plan if payment is sufficient or deposit is made
        $paymentCheckStmt = $pdo->prepare("
            SELECT TrangThaiThanhToan, TrangThaiDuyet
            FROM datlichsukien
            WHERE ID_DatLich = ?
        ");
        $paymentCheckStmt->execute([$eventId]);
        $paymentInfo = $paymentCheckStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$paymentInfo) {
            echo json_encode([
                'success' => false,
                'error' => 'Không tìm thấy thông tin sự kiện'
            ]);
            return;
        }
        
        // Check if event is approved
        if ($paymentInfo['TrangThaiDuyet'] !== 'Đã duyệt') {
            echo json_encode([
                'success' => false,
                'error' => 'Sự kiện chưa được duyệt. Vui lòng duyệt sự kiện trước khi tạo kế hoạch.'
            ]);
            return;
        }
        
        // Check payment status - must be "Đã thanh toán đủ" or "Đã đặt cọc"
        $paymentStatus = $paymentInfo['TrangThaiThanhToan'] ?? 'Chưa thanh toán';
        if ($paymentStatus !== 'Đã thanh toán đủ' && $paymentStatus !== 'Đã đặt cọc') {
            echo json_encode([
                'success' => false,
                'error' => 'Không thể tạo kế hoạch. Khách hàng phải thanh toán đủ hoặc đã đặt cọc trước khi lên kế hoạch và phân công.'
            ]);
            return;
        }
        
        // Check if plan already exists for this event
        $checkSql = "
            SELECT kht.ID_KeHoach 
            FROM kehoachthuchien kht
            LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
            WHERE s.ID_DatLich = ? OR kht.ID_SuKien = ?
        ";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$eventId, $eventId]);
        
        if ($checkStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'error' => 'Kế hoạch cho sự kiện này đã tồn tại'
            ]);
            return;
        }
        
        // Get current user's employee ID
        $user = getCurrentUser();
        $employeeId = null;
        
        if ($user && isset($user['ID_User'])) {
            $empStmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?");
            $empStmt->execute([$user['ID_User']]);
            $empResult = $empStmt->fetch(PDO::FETCH_ASSOC);
            if ($empResult) {
                $employeeId = $empResult['ID_NhanVien'];
            }
        }
        
        // Get sukien ID from datlichsukien
        $sukienStmt = $pdo->prepare("SELECT ID_SuKien FROM sukien WHERE ID_DatLich = ?");
        $sukienStmt->execute([$eventId]);
        $sukienResult = $sukienStmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug sukien lookup
        error_log("=== SUKIEN LOOKUP DEBUG ===");
        error_log("Looking for sukien with ID_DatLich: '$eventId'");
        error_log("Sukien result: " . print_r($sukienResult, true));
        
        if (!$sukienResult) {
            // Try to create sukien if it doesn't exist
            error_log("Sukien not found, attempting to create one...");
            
            // Get event details from datlichsukien
            $eventStmt = $pdo->prepare("
                SELECT ID_DatLich, TenSuKien, NgayBatDau, NgayKetThuc, ID_DD 
                FROM datlichsukien 
                WHERE ID_DatLich = ?
            ");
            $eventStmt->execute([$eventId]);
            $eventData = $eventStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$eventData) {
                error_log("Event not found in datlichsukien table");
                echo json_encode([
                    'success' => false,
                    'error' => 'Không tìm thấy sự kiện trong hệ thống'
                ]);
                return;
            }
            
            // Create sukien entry
            $createSukienStmt = $pdo->prepare("
                INSERT INTO sukien (ID_DatLich, TenSuKien, NgayBatDauThucTe, NgayKetThucThucTe, TrangThaiThucTe)
                VALUES (?, ?, ?, ?, 'Đang chuẩn bị')
            ");
            $createResult = $createSukienStmt->execute([
                $eventData['ID_DatLich'],
                $eventData['TenSuKien'],
                $eventData['NgayBatDau'],
                $eventData['NgayKetThuc']
            ]);
            
            if (!$createResult) {
                error_log("Failed to create sukien entry");
                echo json_encode([
                    'success' => false,
                    'error' => 'Không thể tạo sự kiện trong hệ thống'
                ]);
                return;
            }
            
            // Get the newly created sukien ID
            $sukienId = $pdo->lastInsertId();
            error_log("Created new sukien with ID: $sukienId");
            $sukienResult = ['ID_SuKien' => $sukienId];
        }
        
        // Insert new plan
        $sql = "
            INSERT INTO kehoachthuchien 
            (ID_SuKien, TenKeHoach, NoiDung, NgayBatDau, NgayKetThuc, TrangThai, ID_NhanVien, NgayTao)
            VALUES (?, ?, ?, ?, ?, 'Chưa bắt đầu', ?, NOW())
        ";
        
        // Debug plan creation
        error_log("=== PLAN CREATION DEBUG ===");
        error_log("Sukien ID: " . $sukienResult['ID_SuKien']);
        error_log("Plan Name: '$planName'");
        error_log("Content: '$content'");
        error_log("Start Date: '$startDate'");
        error_log("End Date: '$endDate'");
        error_log("Employee ID: " . ($employeeId ?? 'NULL'));
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $sukienResult['ID_SuKien'],
            $planName,
            $content,
            $startDate,
            $endDate,
            $employeeId
        ]);
        
        if ($result) {
            $planId = $pdo->lastInsertId();
            echo json_encode([
                'success' => true,
                'message' => 'Tạo kế hoạch thành công',
                'planId' => $planId
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi tạo kế hoạch'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Create Plan Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi tạo kế hoạch: ' . $e->getMessage()
        ]);
    }
}

function updateStepStatus($pdo) {
    try {
        $stepId = $_POST['step_id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($stepId) || empty($status)) {
            echo json_encode([
                'success' => false,
                'error' => 'Thiếu thông tin bắt buộc'
            ]);
            return;
        }
        
        // Validate status value matches database enum
        $validStatuses = ['Chưa làm', 'Đang làm', 'Hoàn thành'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode([
                'success' => false,
                'error' => 'Trạng thái không hợp lệ. Chỉ chấp nhận: ' . implode(', ', $validStatuses)
            ]);
            return;
        }
        
        // Update step status in chitietkehoach
        $sql = "UPDATE chitietkehoach SET TrangThai = ? WHERE ID_ChiTiet = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$status, $stepId]);
        
        if ($result) {
            // Sync status with lichlamviec if exists
            $scheduleSql = "UPDATE lichlamviec SET TrangThai = ? WHERE ID_ChiTiet = ?";
            $scheduleStmt = $pdo->prepare($scheduleSql);
            $scheduleStmt->execute([$status, $stepId]);
            
            // If status is 'Đang làm', update ThoiGianHoanThanh to NULL
            // If status is 'Hoàn thành', update ThoiGianHoanThanh to current time
            if ($status === 'Đang làm') {
                $timeSql = "UPDATE lichlamviec SET ThoiGianHoanThanh = NULL WHERE ID_ChiTiet = ?";
                $timeStmt = $pdo->prepare($timeSql);
                $timeStmt->execute([$stepId]);
            } elseif ($status === 'Hoàn thành') {
                $timeSql = "UPDATE lichlamviec SET ThoiGianHoanThanh = NOW() WHERE ID_ChiTiet = ?";
                $timeStmt = $pdo->prepare($timeSql);
                $timeStmt->execute([$stepId]);
            }
            
            // Auto-update plan status based on step statuses
            updatePlanStatusFromSteps($pdo, $stepId, 'chitietkehoach');
            
            // Check and update event status if all steps are completed
            checkAndUpdateEventStatusFromStep($pdo, $stepId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi cập nhật trạng thái'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Update Step Status Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi cập nhật trạng thái: ' . $e->getMessage()
        ]);
    }
}

function deleteStep($pdo) {
    try {
        $stepId = $_POST['step_id'] ?? '';
        
        if (empty($stepId)) {
            echo json_encode([
                'success' => false,
                'error' => 'Thiếu thông tin bắt buộc'
            ]);
            return;
        }
        
        $sql = "DELETE FROM chitietkehoach WHERE ID_ChiTiet = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$stepId]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Xóa bước thành công'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi xóa bước'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Delete Step Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi xóa bước: ' . $e->getMessage()
        ]);
    }
}

function getPlanById($pdo) {
    try {
        $planId = $_GET['plan_id'] ?? '';
        
        if (empty($planId)) {
            echo json_encode([
                'success' => false,
                'error' => 'Thiếu thông tin kế hoạch'
            ]);
            return;
        }
        
        $sql = "
            SELECT 
                k.*,
                s.ID_DatLich,
                dl.TenSuKien,
                nv.HoTen as TenNhanVien
            FROM kehoachthuchien k
            LEFT JOIN sukien s ON k.ID_SuKien = s.ID_SuKien
            LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
            LEFT JOIN nhanvieninfo nv ON k.ID_NhanVien = nv.ID_NhanVien
            WHERE k.ID_KeHoach = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug SQL query and result
        error_log("DEBUG: SQL Query executed for plan ID: " . $planId);
        error_log("DEBUG: SQL Query: " . $sql);
        error_log("DEBUG: Plan found: " . ($plan ? 'YES' : 'NO'));
        
        if ($plan) {
            // Thêm debug logs chi tiết cho NoiDung và ID_NhanVien
            error_log("DEBUG: getPlanById - Plan ID: " . $planId);
            error_log("DEBUG: getPlanById - NoiDung: " . ($plan['NoiDung'] ?? 'NULL'));
            error_log("DEBUG: getPlanById - ID_NhanVien: " . ($plan['ID_NhanVien'] ?? 'NULL'));
            error_log("DEBUG: getPlanById - Full plan data: " . json_encode($plan));
            
            echo json_encode([
                'success' => true,
                'plan' => $plan
            ]);
        } else {
            error_log("Get Plan By ID Error: Plan not found for ID " . $planId);
            echo json_encode([
                'success' => false,
                'error' => 'Không tìm thấy kế hoạch'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Get Plan By ID Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi lấy thông tin kế hoạch: ' . $e->getMessage()
        ]);
    }
}

function updatePlanById($pdo) {
    try {
        $planId = $_POST['planId'] ?? '';
        $planName = $_POST['planName'] ?? '';
        $planDescription = $_POST['planDescription'] ?? $_POST['planContent'] ?? '';
        $startDateTime = $_POST['startDateTime'] ?? '';
        $endDateTime = $_POST['endDateTime'] ?? '';
        $status = $_POST['status'] ?? '';
        $managerId = $_POST['managerId'] ?? '';
        
        // Debug variables
        error_log("=== UPDATE PLAN DEBUG ===");
        error_log("All POST data: " . json_encode($_POST));
        error_log("planId: '$planId'");
        error_log("planName: '$planName'");
        error_log("planDescription: '$planDescription'");
        error_log("startDateTime: '$startDateTime'");
        error_log("endDateTime: '$endDateTime'");
        error_log("status: '$status'");
        error_log("managerId: '$managerId'");
        
        if (empty($planId) || empty($planName) || empty($planDescription) || empty($startDateTime) || empty($endDateTime)) {
            $missing = [];
            if (empty($planId)) $missing[] = 'planId';
            if (empty($planName)) $missing[] = 'planName';
            if (empty($planDescription)) $missing[] = 'planDescription';
            if (empty($startDateTime)) $missing[] = 'startDateTime';
            if (empty($endDateTime)) $missing[] = 'endDateTime';
            
            echo json_encode([
                'success' => false,
                'error' => 'Vui lòng điền đầy đủ thông tin bắt buộc. Thiếu: ' . implode(', ', $missing)
            ]);
            return;
        }
        
        // Update plan
        $sql = "
            UPDATE kehoachthuchien 
            SET TenKeHoach = ?, NoiDung = ?, NgayBatDau = ?, NgayKetThuc = ?, 
                TrangThai = ?, ID_NhanVien = ?
            WHERE ID_KeHoach = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $planName,
            $planDescription,
            $startDateTime,
            $endDateTime,
            $status,
            $managerId ?: null,
            $planId
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật kế hoạch thành công'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi cập nhật kế hoạch'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Update Plan By ID Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi cập nhật kế hoạch: ' . $e->getMessage()
        ]);
    }
}

function getStepById($pdo) {
    try {
        $stepId = $_GET['step_id'] ?? '';
        
        error_log("=== GET STEP BY ID DEBUG ===");
        error_log("step_id from GET: '$stepId'");
        error_log("All GET data: " . json_encode($_GET));
        
        if (empty($stepId)) {
            error_log("Step ID is empty");
            echo json_encode([
                'success' => false,
                'error' => 'Thiếu thông tin bước'
            ]);
            return;
        }
        
        $sql = "
            SELECT 
                c.*,
                nv.HoTen as TenNhanVien,
                nv.ChucVu,
                nv.SoDienThoai
            FROM chitietkehoach c
            LEFT JOIN nhanvieninfo nv ON c.ID_NhanVien = nv.ID_NhanVien
            WHERE c.ID_ChiTiet = ?
        ";
        
        error_log("SQL Query: " . $sql);
        error_log("Executing with stepId: " . $stepId);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$stepId]);
        $step = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Step found: " . ($step ? 'YES' : 'NO'));
        if ($step) {
            error_log("Step data: " . json_encode($step));
        }
        
        if ($step) {
            // Get all assigned staff from lichlamviec
            $staffStmt = $pdo->prepare("
                SELECT DISTINCT llv.ID_NhanVien, nv.HoTen, nv.ChucVu
                FROM lichlamviec llv
                LEFT JOIN nhanvieninfo nv ON llv.ID_NhanVien = nv.ID_NhanVien
                WHERE llv.ID_ChiTiet = ?
            ");
            $staffStmt->execute([$stepId]);
            $step['assignedStaff'] = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'step' => $step
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Không tìm thấy bước'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Get Step By ID Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi lấy thông tin bước: ' . $e->getMessage()
        ]);
    }
}

function updateStepById($pdo) {
    try {
        $stepId = $_POST['stepId'] ?? '';
        $stepName = $_POST['stepName'] ?? '';
        $stepDescription = $_POST['stepDescription'] ?? '';
        $startDateTime = $_POST['stepStartDateTime'] ?? '';
        $endDateTime = $_POST['stepEndDateTime'] ?? '';
        $stepNote = $_POST['stepNote'] ?? $_POST['note'] ?? '';
        
        // Handle multiple staff IDs
        $staffIds = [];
        if (isset($_POST['staffId']) && is_array($_POST['staffId'])) {
            $staffIds = array_filter($_POST['staffId'], function($id) {
                return !empty($id);
            });
        } elseif (isset($_POST['staffId']) && !empty($_POST['staffId'])) {
            // Comma-separated staff IDs or single ID
            $staffIdInput = $_POST['staffId'];
            if (strpos($staffIdInput, ',') !== false) {
                $staffIds = array_filter(explode(',', $staffIdInput), function($id) {
                    return !empty(trim($id));
                });
            } else {
                $staffIds = [$staffIdInput];
            }
        }
        
        $staffId = !empty($staffIds) ? $staffIds[0] : null; // First staff for chitietkehoach.ID_NhanVien (backward compatibility)
        
        error_log("=== UPDATE STEP DEBUG ===");
        error_log("stepId: '$stepId'");
        error_log("stepName: '$stepName'");
        error_log("stepDescription: '$stepDescription'");
        error_log("startDateTime: '$startDateTime'");
        error_log("endDateTime: '$endDateTime'");
        error_log("stepNote: '$stepNote'");
        error_log("staffIds: " . json_encode($staffIds));
        error_log("staffId (first): " . ($staffId ?: 'NULL'));
        
        if (empty($stepId) || empty($stepName) || empty($startDateTime) || empty($endDateTime)) {
            $missing = [];
            if (empty($stepId)) $missing[] = 'stepId';
            if (empty($stepName)) $missing[] = 'stepName';
            if (empty($startDateTime)) $missing[] = 'startDateTime';
            if (empty($endDateTime)) $missing[] = 'endDateTime';
            
            echo json_encode([
                'success' => false,
                'error' => 'Vui lòng điền đầy đủ thông tin bắt buộc. Thiếu: ' . implode(', ', $missing)
            ]);
            return;
        }
        
        // Get current step info to check if staff changed
        $currentStmt = $pdo->prepare("SELECT ID_NhanVien, ID_KeHoach FROM chitietkehoach WHERE ID_ChiTiet = ?");
        $currentStmt->execute([$stepId]);
        $currentStep = $currentStmt->fetch(PDO::FETCH_ASSOC);
        
        // Update step
        // Note: GhiChu column doesn't exist in chitietkehoach table
        // If stepNote is provided, append it to MoTa
        $finalDescription = $stepDescription;
        if (!empty($stepNote)) {
            $finalDescription = $stepDescription . "\n\n[Ghi chú: " . $stepNote . "]";
        }
        
        $sql = "
            UPDATE chitietkehoach 
            SET TenBuoc = ?, MoTa = ?, NgayBatDau = ?, NgayKetThuc = ?, 
                ID_NhanVien = ?
            WHERE ID_ChiTiet = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $stepName,
            $finalDescription,
            $startDateTime,
            $endDateTime,
            $staffId ?: null,
            $stepId
        ]);
        
        error_log("Update result: " . ($result ? 'SUCCESS' : 'FAILED'));
        error_log("Rows affected: " . $stmt->rowCount());
        
        // If step was updated successfully, update work schedules for all assigned staff
        if ($result) {
            // Get event details for work schedule
            $eventStmt = $pdo->prepare("
                SELECT dl.ID_DatLich, dl.TenSuKien
                FROM datlichsukien dl
                LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
                LEFT JOIN kehoachthuchien kht ON s.ID_SuKien = kht.ID_SuKien
                WHERE kht.ID_KeHoach = ?
            ");
            $eventStmt->execute([$currentStep['ID_KeHoach']]);
            $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                // Delete all existing work schedules for this step
                $deleteStmt = $pdo->prepare("DELETE FROM lichlamviec WHERE ID_ChiTiet = ?");
                $deleteStmt->execute([$stepId]);
                error_log("Deleted existing work schedules for step $stepId");
                
                // Create new work schedules for each assigned staff
                if (!empty($staffIds)) {
                    $scheduleSql = "
                        INSERT INTO lichlamviec 
                        (ID_DatLich, ID_NhanVien, NhiemVu, NgayBatDau, NgayKetThuc, TrangThai, ID_ChiTiet, CongViec, NgayTao)
                        VALUES (?, ?, ?, ?, ?, 'Chưa làm', ?, ?, NOW())
                    ";
                    
                    $scheduleStmt = $pdo->prepare($scheduleSql);
                    $createdSchedules = 0;
                    
                    foreach ($staffIds as $singleStaffId) {
                        $scheduleResult = $scheduleStmt->execute([
                            $event['ID_DatLich'],
                            $singleStaffId,
                            $stepName,
                            $startDateTime,
                            $endDateTime,
                            $stepId,
                            $stepDescription
                        ]);
                        
                        if ($scheduleResult) {
                            $createdSchedules++;
                            error_log("Work schedule created for staff ID $singleStaffId: SUCCESS");
                        } else {
                            error_log("Work schedule creation failed for staff ID $singleStaffId");
                        }
                    }
                    
                    error_log("Total work schedules created: $createdSchedules / " . count($staffIds));
                }
            }
        }
        
        if ($result) {
            error_log("Update step SUCCESS - returning success response");
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật bước thành công' . ($staffId ? ' và đã cập nhật lịch làm việc' : '')
            ]);
        } else {
            error_log("Update step FAILED - returning error response");
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi cập nhật bước'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Update Step By ID Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi cập nhật bước: ' . $e->getMessage()
        ]);
    }
}

function addEventStep($pdo) {
    try {
        $eventId = $_POST['eventId'] ?? '';
        $stepName = $_POST['stepName'] ?? '';
        $stepDescription = $_POST['stepDescription'] ?? '';
        $startDate = $_POST['stepStartDate'] ?? '';
        $startTime = $_POST['stepStartTime'] ?? '';
        $endDate = $_POST['stepEndDate'] ?? '';
        $endTime = $_POST['stepEndTime'] ?? '';
        $stepNote = $_POST['stepNote'] ?? $_POST['note'] ?? '';
        
        // Handle multiple staff IDs
        $staffIds = [];
        if (isset($_POST['staffId']) && is_array($_POST['staffId'])) {
            $staffIds = array_filter($_POST['staffId'], function($id) {
                return !empty($id);
            });
        } elseif (isset($_POST['stepStaff']) && !empty($_POST['stepStaff'])) {
            // Comma-separated staff IDs or single ID
            $stepStaff = $_POST['stepStaff'];
            if (strpos($stepStaff, ',') !== false) {
                $staffIds = array_filter(explode(',', $stepStaff), function($id) {
                    return !empty(trim($id));
                });
            } else {
                $staffIds = [$stepStaff];
            }
        }
        
        $staffId = !empty($staffIds) ? $staffIds[0] : null; // First staff for chitietkehoach.ID_NhanVien (backward compatibility)
        
        error_log("=== ADD EVENT STEP DEBUG ===");
        error_log("eventId: '$eventId'");
        error_log("stepName: '$stepName'");
        error_log("stepDescription: '$stepDescription'");
        error_log("startDate: '$startDate'");
        error_log("startTime: '$startTime'");
        error_log("endDate: '$endDate'");
        error_log("endTime: '$endTime'");
        error_log("staffIds: " . json_encode($staffIds));
        error_log("staffId (first): " . ($staffId ?: 'NULL'));
        
        if (empty($eventId) || empty($stepName) || empty($startDate) || empty($startTime) || empty($endDate) || empty($endTime)) {
            $missing = [];
            if (empty($eventId)) $missing[] = 'eventId';
            if (empty($stepName)) $missing[] = 'stepName';
            if (empty($startDate)) $missing[] = 'startDate';
            if (empty($startTime)) $missing[] = 'startTime';
            if (empty($endDate)) $missing[] = 'endDate';
            if (empty($endTime)) $missing[] = 'endTime';
            
            echo json_encode([
                'success' => false,
                'error' => 'Vui lòng điền đầy đủ thông tin bắt buộc. Thiếu: ' . implode(', ', $missing)
            ]);
            return;
        }
        
        // Combine date and time
        $startDateTime = $startDate . ' ' . $startTime;
        $endDateTime = $endDate . ' ' . $endTime;
        
        // Validate datetime
        $startDateObj = new DateTime($startDateTime);
        $endDateObj = new DateTime($endDateTime);
        
        if ($endDateObj <= $startDateObj) {
            echo json_encode([
                'success' => false,
                'error' => 'Thời gian kết thúc phải sau thời gian bắt đầu'
            ]);
            return;
        }
        
        // Get plan ID from event ID first
        $planStmt = $pdo->prepare("
            SELECT kht.ID_KeHoach 
            FROM kehoachthuchien kht
            LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
            WHERE s.ID_DatLich = ? OR kht.ID_SuKien = ?
        ");
        $planStmt->execute([$eventId, $eventId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            echo json_encode([
                'success' => false,
                'error' => 'Không tìm thấy kế hoạch cho sự kiện này'
            ]);
            return;
        }
        
        // Insert step
        // Note: GhiChu column doesn't exist in chitietkehoach table
        // If stepNote is provided, append it to MoTa
        $finalDescription = $stepDescription;
        if (!empty($stepNote)) {
            $finalDescription = $stepDescription . "\n\n[Ghi chú: " . $stepNote . "]";
        }
        
        $sql = "
            INSERT INTO chitietkehoach (ID_KeHoach, TenBuoc, MoTa, NgayBatDau, NgayKetThuc, ID_NhanVien, TrangThai)
            VALUES (?, ?, ?, ?, ?, ?, 'Chưa làm')
        ";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $plan['ID_KeHoach'],
            $stepName,
            $finalDescription,
            $startDateTime,
            $endDateTime,
            $staffId ?: null
        ]);
        
        error_log("Insert result: " . ($result ? 'SUCCESS' : 'FAILED'));
        error_log("Rows affected: " . $stmt->rowCount());
        
        // If step was created successfully and staff is assigned, create work schedule for each staff
        if ($result && !empty($staffIds)) {
            $stepId = $pdo->lastInsertId();
            
            // Get event details for work schedule
            $eventStmt = $pdo->prepare("
                SELECT dl.ID_DatLich, dl.TenSuKien
                FROM datlichsukien dl
                WHERE dl.ID_DatLich = ?
            ");
            $eventStmt->execute([$eventId]);
            $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event) {
                // Create work schedule entry for each staff member
                $scheduleSql = "
                    INSERT INTO lichlamviec 
                    (ID_DatLich, ID_NhanVien, NhiemVu, NgayBatDau, NgayKetThuc, TrangThai, ID_ChiTiet, CongViec, NgayTao)
                    VALUES (?, ?, ?, ?, ?, 'Chưa làm', ?, ?, NOW())
                ";
                
                $scheduleStmt = $pdo->prepare($scheduleSql);
                $createdSchedules = 0;
                
                foreach ($staffIds as $singleStaffId) {
                    $scheduleResult = $scheduleStmt->execute([
                        $event['ID_DatLich'],
                        $singleStaffId,
                        $stepName,
                        $startDateTime,
                        $endDateTime,
                        $stepId,
                        $stepDescription
                    ]);
                    
                    if ($scheduleResult) {
                        $createdSchedules++;
                        error_log("Work schedule created for staff ID $singleStaffId: SUCCESS");
                    } else {
                        error_log("Work schedule creation failed for staff ID $singleStaffId");
                    }
                }
                
                error_log("Total work schedules created: $createdSchedules / " . count($staffIds));
            }
        }
        
        // Verify the inserted data
        if ($result) {
            $lastId = $pdo->lastInsertId();
            error_log("Last inserted ID: " . $lastId);
            
            // Check what was actually inserted
            $checkStmt = $pdo->prepare("SELECT * FROM chitietkehoach WHERE ID_ChiTiet = ?");
            $checkStmt->execute([$lastId]);
            $insertedData = $checkStmt->fetch(PDO::FETCH_ASSOC);
            error_log("Inserted data: " . json_encode($insertedData));
        }
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Thêm bước thực hiện thành công'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Lỗi khi thêm bước thực hiện'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Add Event Step Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi thêm bước thực hiện: ' . $e->getMessage()
        ]);
    }
}

function getStaffTasks($pdo) {
    try {
        // Check if user is logged in and has role 4 (Staff)
        if (!isset($_SESSION['user']) || $_SESSION['user']['ID_Role'] != 4) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
            return;
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
        
        $staffId = $staff['ID_NhanVien'];
        
        // Get tasks from chitietkehoach
        $stmt = $pdo->prepare("
            SELECT 
                ck.ID_ChiTiet,
                ck.TenBuoc,
                ck.TrangThai,
                ck.MoTa,
                ck.NgayBatDau,
                ck.NgayKetThuc,
                dl.TenSuKien,
                dd.TenDiaDiem
            FROM chitietkehoach ck
            LEFT JOIN kehoachthuchien kht ON ck.ID_KeHoach = kht.ID_KeHoach
            LEFT JOIN datlichsukien dl ON kht.ID_DatLich = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE ck.ID_NhanVien = ?
            ORDER BY ck.NgayBatDau DESC
        ");
        $stmt->execute([$staffId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'tasks' => $tasks]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách công việc: ' . $e->getMessage()]);
    }
}

/**
 * Check and update event status if all steps are completed (for event-planning.php)
 */
function checkAndUpdateEventStatusFromStep($pdo, $stepId) {
    try {
        error_log("DEBUG: checkAndUpdateEventStatusFromStep - stepId: " . $stepId);
        
        // Get event ID from the step
        $stmt = $pdo->prepare("
            SELECT s.ID_SuKien 
            FROM chitietkehoach ctk
            JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
            JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
            WHERE ctk.ID_ChiTiet = ?
        ");
        $stmt->execute([$stepId]);
        $eventId = $stmt->fetchColumn();
        
        if (!$eventId) {
            error_log("DEBUG: checkAndUpdateEventStatusFromStep - No event ID found");
            return;
        }
        
        error_log("DEBUG: checkAndUpdateEventStatusFromStep - Event ID: " . $eventId);
        
        // Check if all steps for this event are completed
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_steps,
                SUM(CASE WHEN TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_steps
            FROM (
                SELECT TrangThai FROM chitietkehoach ctk
                JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
                WHERE kht.ID_SuKien = ?
                
                UNION ALL
                
                SELECT TrangThai FROM lichlamviec llv
                JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
                JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
                WHERE s.ID_SuKien = ?
            ) as all_steps
        ");
        $stmt->execute([$eventId, $eventId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalSteps = $result['total_steps'];
        $completedSteps = $result['completed_steps'];
        
        error_log("DEBUG: checkAndUpdateEventStatusFromStep - Total steps: " . $totalSteps . ", Completed: " . $completedSteps);
        
        // If all steps are completed, update event status
        if ($totalSteps > 0 && $totalSteps == $completedSteps) {
            error_log("DEBUG: checkAndUpdateEventStatusFromStep - All steps completed, updating event status");
            
            // Update event status to completed
            $stmt = $pdo->prepare("UPDATE sukien SET TrangThai = 'Hoàn thành' WHERE ID_SuKien = ?");
            $stmt->execute([$eventId]);
            
            // Also update datlichsukien status if exists
            $stmt = $pdo->prepare("
                UPDATE datlichsukien dl
                JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
                SET dl.TrangThai = 'Hoàn thành'
                WHERE s.ID_SuKien = ?
            ");
            $stmt->execute([$eventId]);
            
            error_log("DEBUG: checkAndUpdateEventStatusFromStep - Event status updated to 'Hoàn thành'");
        } else {
            error_log("DEBUG: checkAndUpdateEventStatusFromStep - Not all steps completed yet");
        }
        
    } catch (Exception $e) {
        error_log("ERROR: checkAndUpdateEventStatusFromStep - Exception: " . $e->getMessage());
    }
}

/**
 * Auto-update plan status (kehoachthuchien) based on step statuses (chitietkehoach)
 */
function updatePlanStatusFromSteps($pdo, $assignmentId, $sourceTable) {
    try {
        // Get plan ID from assignment
        $planId = null;
        
        if ($sourceTable === 'chitietkehoach') {
            // Get plan ID directly from chitietkehoach
            $stmt = $pdo->prepare("SELECT ID_KeHoach FROM chitietkehoach WHERE ID_ChiTiet = ?");
            $stmt->execute([$assignmentId]);
            $planId = $stmt->fetchColumn();
        } else if ($sourceTable === 'lichlamviec') {
            // Get plan ID from lichlamviec -> chitietkehoach -> kehoachthuchien
            $stmt = $pdo->prepare("
                SELECT kht.ID_KeHoach 
                FROM lichlamviec llv
                JOIN chitietkehoach ctk ON llv.ID_ChiTiet = ctk.ID_ChiTiet
                JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
                WHERE llv.ID_LLV = ?
            ");
            $stmt->execute([$assignmentId]);
            $planId = $stmt->fetchColumn();
        }
        
        if (!$planId) {
            error_log("DEBUG: updatePlanStatusFromSteps - No plan ID found for assignment: " . $assignmentId);
            return;
        }
        
        error_log("DEBUG: updatePlanStatusFromSteps - Plan ID: " . $planId);
        
        // Get all steps for this plan with their actual status from lichlamviec
        // A step is considered "Hoàn thành" only if ALL assigned staff have completed it
        // A step is considered "Đang làm" if at least one staff is working on it
        $stmt = $pdo->prepare("
            SELECT 
                ck.ID_ChiTiet,
                ck.TrangThai as chitiet_status,
                COUNT(llv.ID_LLV) as total_assignments,
                SUM(CASE WHEN llv.TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_assignments,
                SUM(CASE WHEN llv.TrangThai = 'Đang làm' THEN 1 ELSE 0 END) as inprogress_assignments,
                SUM(CASE WHEN llv.TrangThai = 'Chưa làm' THEN 1 ELSE 0 END) as notstarted_assignments
            FROM chitietkehoach ck
            LEFT JOIN lichlamviec llv ON ck.ID_ChiTiet = llv.ID_ChiTiet
            WHERE ck.ID_KeHoach = ?
            GROUP BY ck.ID_ChiTiet, ck.TrangThai
        ");
        $stmt->execute([$planId]);
        $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($steps)) {
            error_log("DEBUG: updatePlanStatusFromSteps - No steps found for plan: " . $planId);
            return;
        }
        
        $totalSteps = count($steps);
        $completedSteps = 0;
        $inProgressSteps = 0;
        
        foreach ($steps as $step) {
            $totalAssignments = (int)$step['total_assignments'];
            $completedAssignments = (int)$step['completed_assignments'];
            $inProgressAssignments = (int)$step['inprogress_assignments'];
            
            // If step has assignments in lichlamviec, use those to determine status
            if ($totalAssignments > 0) {
                // Step is completed only if ALL assignments are completed
                if ($completedAssignments === $totalAssignments && $totalAssignments > 0) {
                    $completedSteps++;
                } 
                // Step is in progress if at least one assignment is in progress or completed (but not all)
                else if ($inProgressAssignments > 0 || $completedAssignments > 0) {
                    $inProgressSteps++;
                }
            } else {
                // No assignments in lichlamviec, use chitietkehoach status
                if ($step['chitiet_status'] === 'Hoàn thành') {
                    $completedSteps++;
                } else if ($step['chitiet_status'] === 'Đang làm') {
                    $inProgressSteps++;
                }
            }
        }
        
        error_log("DEBUG: updatePlanStatusFromSteps - Total: $totalSteps, Completed: $completedSteps, In Progress: $inProgressSteps");
        
        // Determine new plan status
        $newPlanStatus = null;
        if ($completedSteps === $totalSteps && $totalSteps > 0) {
            // All steps completed
            $newPlanStatus = 'Hoàn thành';
        } else if ($inProgressSteps > 0 || $completedSteps > 0) {
            // At least one step is in progress or completed
            $newPlanStatus = 'Đang thực hiện';
        } else {
            // All steps are "Chưa làm"
            $newPlanStatus = 'Chưa bắt đầu';
        }
        
        // Update plan status
        if ($newPlanStatus) {
            $updateStmt = $pdo->prepare("
                UPDATE kehoachthuchien 
                SET TrangThai = ? 
                WHERE ID_KeHoach = ?
            ");
            $updateResult = $updateStmt->execute([$newPlanStatus, $planId]);
            
            if ($updateResult) {
                error_log("DEBUG: updatePlanStatusFromSteps - Plan status updated to: " . $newPlanStatus);
            } else {
                error_log("ERROR: updatePlanStatusFromSteps - Failed to update plan status");
            }
        }
    } catch (Exception $e) {
        error_log("ERROR: updatePlanStatusFromSteps - " . $e->getMessage());
    }
}

/**
 * Update status for all plans (or plans for a specific event)
 */
function updateAllPlanStatuses($pdo, $eventId = null) {
    try {
        $whereClause = '';
        $params = [];
        
        if (!empty($eventId)) {
            $whereClause = 'WHERE dl.ID_DatLich = ?';
            $params[] = $eventId;
        }
        
        // Get all plan IDs
        $sql = "
            SELECT DISTINCT kht.ID_KeHoach
            FROM kehoachthuchien kht
            LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
            LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
            {$whereClause}
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $planIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Update status for each plan
        foreach ($planIds as $planId) {
            // Get all steps for this plan with their actual status from lichlamviec
            $stepStmt = $pdo->prepare("
                SELECT 
                    ck.ID_ChiTiet,
                    ck.TrangThai as chitiet_status,
                    COUNT(llv.ID_LLV) as total_assignments,
                    SUM(CASE WHEN llv.TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_assignments,
                    SUM(CASE WHEN llv.TrangThai = 'Đang làm' THEN 1 ELSE 0 END) as inprogress_assignments
                FROM chitietkehoach ck
                LEFT JOIN lichlamviec llv ON ck.ID_ChiTiet = llv.ID_ChiTiet
                WHERE ck.ID_KeHoach = ?
                GROUP BY ck.ID_ChiTiet, ck.TrangThai
            ");
            $stepStmt->execute([$planId]);
            $steps = $stepStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($steps)) {
                continue;
            }
            
            $totalSteps = count($steps);
            $completedSteps = 0;
            $inProgressSteps = 0;
            
            foreach ($steps as $step) {
                $totalAssignments = (int)$step['total_assignments'];
                $completedAssignments = (int)$step['completed_assignments'];
                $inProgressAssignments = (int)$step['inprogress_assignments'];
                
                // If step has assignments in lichlamviec, use those to determine status
                if ($totalAssignments > 0) {
                    // Step is completed only if ALL assignments are completed
                    if ($completedAssignments === $totalAssignments && $totalAssignments > 0) {
                        $completedSteps++;
                    } 
                    // Step is in progress if at least one assignment is in progress or completed (but not all)
                    else if ($inProgressAssignments > 0 || $completedAssignments > 0) {
                        $inProgressSteps++;
                    }
                } else {
                    // No assignments in lichlamviec, use chitietkehoach status
                    if ($step['chitiet_status'] === 'Hoàn thành') {
                        $completedSteps++;
                    } else if ($step['chitiet_status'] === 'Đang làm') {
                        $inProgressSteps++;
                    }
                }
            }
            
            // Determine new plan status
            $newPlanStatus = null;
            if ($completedSteps === $totalSteps && $totalSteps > 0) {
                // All steps completed
                $newPlanStatus = 'Hoàn thành';
            } else if ($inProgressSteps > 0 || $completedSteps > 0) {
                // At least one step is in progress or completed
                $newPlanStatus = 'Đang thực hiện';
            } else {
                // All steps are "Chưa làm"
                $newPlanStatus = 'Chưa bắt đầu';
            }
            
            // Update plan status
            if ($newPlanStatus) {
                $updateStmt = $pdo->prepare("
                    UPDATE kehoachthuchien 
                    SET TrangThai = ? 
                    WHERE ID_KeHoach = ?
                ");
                $updateStmt->execute([$newPlanStatus, $planId]);
            }
        }
        
    } catch (Exception $e) {
        error_log("ERROR: updateAllPlanStatuses - " . $e->getMessage());
    }
}

?>
