<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Public actions that don't require authentication
$publicActions = ['get_public', 'get_all_public'];

try {
    switch ($action) {
        case 'get_public':
        case 'get_all_public':
            getAllEventTypesPublic();
            break;
            
        case 'get_all':
            // Check admin privileges for admin actions
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            getAllEventTypes();
            break;
            
        case 'get':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            getEventType();
            break;
            
        case 'add':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            addEventType();
            break;
            
        case 'update':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            updateEventType();
            break;
            
        case 'delete':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            deleteEventType();
            break;
            
        case 'get_stats':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            getEventTypeStats();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
} catch (Exception $e) {
    error_log("Event Types Controller - System Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

function checkAdminAccess() {
    $userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
    return isset($_SESSION['user']) && in_array($userRole, [1, 2]);
}

function getAllEventTypesPublic() {
    global $pdo;
    
    try {
        $sql = "
            SELECT 
                ID_LoaiSK,
                TenLoai,
                MoTa,
                GiaCoBan,
                NgayTao,
                NgayCapNhat
            FROM loaisukien 
            ORDER BY TenLoai ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $eventTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'event_types' => $eventTypes
        ]);
    } catch (Exception $e) {
        error_log("Get All Event Types Public Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Không thể tải danh sách loại sự kiện'
        ]);
    }
}

function getAllEventTypes() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM loaisukien ORDER BY TenLoai");
        $stmt->execute();
        $eventTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'event_types' => $eventTypes]);
    } catch (Exception $e) {
        error_log("Get All Event Types Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy danh sách loại sự kiện']);
    }
}

function getEventType() {
    global $pdo;
    
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID loại sự kiện']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM loaisukien WHERE ID_LoaiSK = ?");
        $stmt->execute([$id]);
        $eventType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($eventType) {
            echo json_encode(['success' => true, 'event_type' => $eventType]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy loại sự kiện']);
        }
    } catch (Exception $e) {
        error_log("Get Event Type Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy thông tin loại sự kiện']);
    }
}

function addEventType() {
    global $pdo;
    
    $input = $_POST;
    
    // Validate required fields
    $requiredFields = ['TenLoai', 'GiaCoBan'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
            return;
        }
    }
    
    // Validate price
    if (!is_numeric($input['GiaCoBan']) || $input['GiaCoBan'] < 0) {
        echo json_encode(['success' => false, 'error' => 'Giá cơ bản phải là số dương']);
        return;
    }
    
    // Check if event type name already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loaisukien WHERE TenLoai = ?");
    $stmt->execute([$input['TenLoai']]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Tên loại sự kiện đã tồn tại']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO loaisukien (TenLoai, MoTa, GiaCoBan, NgayTao, NgayCapNhat) 
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $input['TenLoai'],
            $input['MoTa'] ?? null,
            $input['GiaCoBan']
        ]);
        
        $eventTypeId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Thêm loại sự kiện thành công',
            'event_type_id' => $eventTypeId
        ]);
    } catch (Exception $e) {
        error_log("Add Event Type Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi thêm loại sự kiện']);
    }
}

function updateEventType() {
    global $pdo;
    
    $input = $_POST;
    $id = $input['ID_LoaiSK'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID loại sự kiện']);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['TenLoai', 'GiaCoBan'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
            return;
        }
    }
    
    // Validate price
    if (!is_numeric($input['GiaCoBan']) || $input['GiaCoBan'] < 0) {
        echo json_encode(['success' => false, 'error' => 'Giá cơ bản phải là số dương']);
        return;
    }
    
    // Check if event type exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loaisukien WHERE ID_LoaiSK = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy loại sự kiện']);
        return;
    }

    // Do not allow edit if referenced by existing data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM datlichsukien WHERE ID_LoaiSK = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Không thể sửa: Loại sự kiện đã được sử dụng trong các đơn đặt lịch']);
        return;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM combo_loaisk WHERE ID_LoaiSK = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Không thể sửa: Loại sự kiện đang được gán cho combo']);
        return;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM diadiem_loaisk WHERE ID_LoaiSK = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Không thể sửa: Loại sự kiện đang được gán cho địa điểm']);
        return;
    }
    
    // Check if event type name already exists (excluding current record)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loaisukien WHERE TenLoai = ? AND ID_LoaiSK != ?");
    $stmt->execute([$input['TenLoai'], $id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Tên loại sự kiện đã tồn tại']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE loaisukien 
            SET TenLoai = ?, MoTa = ?, GiaCoBan = ?, NgayCapNhat = NOW()
            WHERE ID_LoaiSK = ?
        ");
        
        $stmt->execute([
            $input['TenLoai'],
            $input['MoTa'] ?? null,
            $input['GiaCoBan'],
            $id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật loại sự kiện thành công']);
    } catch (Exception $e) {
        error_log("Update Event Type Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi cập nhật loại sự kiện']);
    }
}

function deleteEventType() {
    global $pdo;
    
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID loại sự kiện']);
        return;
    }
    
    // Check if event type exists
    $stmt = $pdo->prepare("SELECT TenLoai FROM loaisukien WHERE ID_LoaiSK = ?");
    $stmt->execute([$id]);
    $eventType = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$eventType) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy loại sự kiện']);
        return;
    }
    
    // Check if event type is being used in events
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM datlichsukien WHERE ID_LoaiSK = ?");
    $stmt->execute([$id]);
    $eventCount = $stmt->fetchColumn();
    
    if ($eventCount > 0) {
        echo json_encode([
            'success' => false, 
            'error' => "Không thể xóa loại sự kiện '{$eventType['TenLoai']}' vì đang được sử dụng trong {$eventCount} sự kiện"
        ]);
        return;
    }
    
    // Check if event type is being used in combo_loaisk
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM combo_loaisk WHERE ID_LoaiSK = ?");
    $stmt->execute([$id]);
    $comboCount = $stmt->fetchColumn();
    
    if ($comboCount > 0) {
        echo json_encode([
            'success' => false, 
            'error' => "Không thể xóa loại sự kiện '{$eventType['TenLoai']}' vì đang được sử dụng trong {$comboCount} combo"
        ]);
        return;
    }
    
    // Check if event type is being used in diadiem_loaisk
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM diadiem_loaisk WHERE ID_LoaiSK = ?");
    $stmt->execute([$id]);
    $locationCount = $stmt->fetchColumn();
    
    if ($locationCount > 0) {
        echo json_encode([
            'success' => false, 
            'error' => "Không thể xóa loại sự kiện '{$eventType['TenLoai']}' vì đang được sử dụng trong {$locationCount} địa điểm"
        ]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM loaisukien WHERE ID_LoaiSK = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Xóa loại sự kiện thành công']);
    } catch (Exception $e) {
        error_log("Delete Event Type Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi xóa loại sự kiện']);
    }
}

function getEventTypeStats() {
    global $pdo;
    
    try {
        // Total event types
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM loaisukien");
        $stmt->execute();
        $totalEventTypes = $stmt->fetchColumn();
        
        // Average price
        $stmt = $pdo->prepare("SELECT AVG(GiaCoBan) FROM loaisukien");
        $stmt->execute();
        $averagePrice = $stmt->fetchColumn();
        
        // Total events using event types
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT dl.ID_DatLich) 
            FROM datlichsukien dl 
            INNER JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
        ");
        $stmt->execute();
        $totalEvents = $stmt->fetchColumn();
        
        // Event types with most events
        $stmt = $pdo->prepare("
            SELECT ls.TenLoai, COUNT(dl.ID_DatLich) as event_count
            FROM loaisukien ls
            LEFT JOIN datlichsukien dl ON ls.ID_LoaiSK = dl.ID_LoaiSK
            GROUP BY ls.ID_LoaiSK, ls.TenLoai
            ORDER BY event_count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $topEventTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_event_types' => $totalEventTypes,
                'average_price' => $averagePrice,
                'total_events' => $totalEvents,
                'top_event_types' => $topEventTypes
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get Event Type Stats Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy thống kê loại sự kiện']);
    }
}
?>
