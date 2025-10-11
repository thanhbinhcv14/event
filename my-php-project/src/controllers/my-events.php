<?php
// Set error reporting to prevent HTML errors from being displayed
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit();
}

$user = $_SESSION['user'];
$userId = $user['ID_User'] ?? $user['id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Thông tin người dùng không hợp lệ']);
    exit();
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get':
            // Get user's registered events from sukien table
            $stmt = $pdo->prepare("
                SELECT 
                    s.*,
                    u.Email as user_email,
                    d.TenDiaDiem as event_location,
                    d.DiaChi as location_address,
                    d.SucChua as location_capacity,
                    ls.TenLoai as event_type,
                    c.TenCombo as combo_name,
                    c.GiaCombo as combo_price
                FROM sukien s
                LEFT JOIN users u ON s.ID_User = u.ID_User
                LEFT JOIN diadiem d ON s.ID_DD = d.ID_DD
                LEFT JOIN loaisukien ls ON s.ID_LoaiSK = ls.ID_LoaiSK
                LEFT JOIN sukien_combo sc ON s.ID_SK = sc.ID_SK
                LEFT JOIN combo c ON sc.ID_Combo = c.ID_Combo
                WHERE s.ID_User = ?
                ORDER BY s.NgayTao DESC
            ");
            $stmt->execute([$userId]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform data to match frontend expectations
            $transformedEvents = [];
            foreach ($events as $event) {
                $transformedEvents[] = [
                    'id' => $event['ID_SK'],
                    'event_name' => $event['TenSuKien'],
                    'event_date' => date('Y-m-d', strtotime($event['NgayBatDau'])),
                    'event_time' => date('H:i:s', strtotime($event['NgayBatDau'])),
                    'event_location' => $event['event_location'],
                    'event_type' => $event['event_type'],
                    'description' => $event['MoTa'],
                    'status' => mapStatus($event['TrangThai']),
                    'created_at' => $event['NgayTao'],
                    'user_email' => $event['user_email'],
                    'combo_name' => $event['combo_name'],
                    'combo_price' => $event['combo_price'],
                    'admin_comment' => extractAdminComment($event['MoTa'])
                ];
            }
            
            echo json_encode(['success' => true, 'events' => $transformedEvents]);
            break;
            
        case 'delete':
            // Delete event registration (only if pending)
            $eventId = $_POST['event_id'] ?? $_GET['event_id'] ?? null;
            
            if (!$eventId) {
                echo json_encode(['success' => false, 'error' => 'ID sự kiện không hợp lệ']);
                exit();
            }
            
            // Check if event belongs to user and is pending
            $stmt = $pdo->prepare("SELECT TrangThai FROM sukien WHERE ID_SK = ? AND ID_User = ?");
            $stmt->execute([$eventId, $userId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$event) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy sự kiện']);
                exit();
            }
            
            if ($event['TrangThai'] !== 'Chờ duyệt') {
                echo json_encode(['success' => false, 'error' => 'Chỉ có thể xóa sự kiện đang chờ duyệt']);
                exit();
            }
            
            // Delete event
            $stmt = $pdo->prepare("DELETE FROM sukien WHERE ID_SK = ? AND ID_User = ?");
            $result = $stmt->execute([$eventId, $userId]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Xóa sự kiện thành công']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Lỗi khi xóa sự kiện']);
            }
            break;
            
        case 'cancel_event':
            $eventId = $_POST['event_id'] ?? null;
            
            if (!$eventId) {
                echo json_encode(['success' => false, 'message' => 'ID sự kiện không hợp lệ']);
                break;
            }
            
            // Get customer ID from user session
            $stmt = $pdo->prepare("SELECT ID_KhachHang FROM khachhanginfo WHERE ID_User = ?");
            $stmt->execute([$userId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin khách hàng']);
                break;
            }
            
            $customerId = $customer['ID_KhachHang'];
            
            // Check if event belongs to current user and is not approved yet
            $stmt = $pdo->prepare("
                SELECT ID_DatLich, TrangThaiDuyet 
                FROM datlichsukien 
                WHERE ID_DatLich = ? AND ID_KhachHang = ?
            ");
            $stmt->execute([$eventId, $customerId]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$event) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện hoặc bạn không có quyền hủy sự kiện này']);
                break;
            }
            
            if ($event['TrangThaiDuyet'] !== 'Chờ duyệt') {
                echo json_encode(['success' => false, 'message' => 'Chỉ có thể hủy sự kiện chưa được duyệt']);
                break;
            }
            
            // Delete the event
            $stmt = $pdo->prepare("DELETE FROM datlichsukien WHERE ID_DatLich = ?");
            if ($stmt->execute([$eventId])) {
                echo json_encode(['success' => true, 'message' => 'Hủy sự kiện thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi hủy sự kiện']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

// Helper function to map Vietnamese status to English
function mapStatus($vietnameseStatus) {
    switch($vietnameseStatus) {
        case 'Chờ duyệt': return 'pending';
        case 'Đã duyệt': return 'approved';
        case 'Hủy': return 'rejected';
        default: return 'pending';
    }
}

// Helper function to extract admin comment from description
function extractAdminComment($description) {
    if (strpos($description, '[Ghi chú admin:') !== false) {
        preg_match('/\[Ghi chú admin: ([^\]]+)\]/', $description, $matches);
        return isset($matches[1]) ? trim($matches[1]) : '';
    }
    return '';
}
?>
