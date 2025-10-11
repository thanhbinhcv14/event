<?php
require_once '../../config/database.php';
require_once '../../src/auth/auth.php';

// Start session
session_start();

// Check if user is logged in and has role 2
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$user = getCurrentUser();
if ($user['ID_Quyen'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();

    switch ($action) {
        case 'get_events':
            getEvents($pdo);
            break;
            
        case 'create_plan':
            createPlan($pdo);
            break;
            
        case 'get_plan':
            getPlan($pdo);
            break;
            
        case 'update_plan':
            updatePlan($pdo);
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
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
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
                    WHEN kp.id_kehoach IS NOT NULL THEN COALESCE(kp.trangthai, 'Đã lập kế hoạch')
                    ELSE 'Chưa lập kế hoạch'
                END as TrangThaiKeHoach,
                kp.ngay_batdau as NgayBatDauThucHien,
                kp.noidung as MoTaKeHoach,
                kp.ngay_tao as GhiChu
            FROM datlichsukien dl
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            LEFT JOIN kehoachthuchien kp ON dl.ID_DatLich = kp.id_sukien
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

function createPlan($pdo) {
    try {
        $eventId = $_POST['event_id'] ?? '';
        $planName = $_POST['plan_name'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $content = $_POST['content'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($eventId) || empty($planName) || empty($startDate) || empty($endDate) || empty($content)) {
            echo json_encode([
                'success' => false,
                'error' => 'Vui lòng điền đầy đủ thông tin bắt buộc'
            ]);
            return;
        }
        
        // Check if plan already exists for this event
        $checkSql = "SELECT id_kehoach FROM kehoachthuchien WHERE id_sukien = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$eventId]);
        
        if ($checkStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'error' => 'Kế hoạch cho sự kiện này đã tồn tại'
            ]);
            return;
        }
        
        // Insert new plan
        $sql = "
            INSERT INTO kehoachthuchien 
            (id_sukien, ten_kehoach, noidung, ngay_batdau, ngay_ketthuc, trangthai, id_nhanvien, ngay_tao)
            VALUES (?, ?, ?, ?, ?, 'Chưa bắt đầu', ?, NOW())
        ";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $eventId,
            $planName,
            $content,
            $startDate,
            $endDate,
            $_SESSION['user']['ID_User']
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Tạo kế hoạch thành công'
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
            JOIN datlichsukien dl ON kp.id_sukien = dl.ID_DatLich
            LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            WHERE kp.id_sukien = ?
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

function updatePlan($pdo) {
    try {
        $eventId = $_POST['event_id'] ?? '';
        $planName = $_POST['plan_name'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $content = $_POST['content'] ?? '';
        $status = $_POST['status'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode([
                'success' => false,
                'error' => 'ID sự kiện không hợp lệ'
            ]);
            return;
        }
        
        $sql = "
            UPDATE kehoachthuchien 
            SET ten_kehoach = ?, noidung = ?, ngay_batdau = ?, ngay_ketthuc = ?, trangthai = ?
            WHERE id_sukien = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $planName,
            $content,
            $startDate,
            $endDate,
            $status,
            $eventId
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
        error_log("Update Plan Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi khi cập nhật kế hoạch: ' . $e->getMessage()
        ]);
    }
}

function deletePlan($pdo) {
    try {
        $eventId = $_POST['event_id'] ?? '';
        
        if (empty($eventId)) {
            echo json_encode([
                'success' => false,
                'error' => 'ID sự kiện không hợp lệ'
            ]);
            return;
        }
        
        $sql = "DELETE FROM kehoachthuchien WHERE id_sukien = ?";
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
                'error' => 'Lỗi khi xóa kế hoạch'
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
?>
