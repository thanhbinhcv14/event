<?php
ob_start();
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and has admin privileges
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (!isset($_SESSION['user']) || !in_array($userRole, [1, 2, 3, 4])) {
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit();
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'test':
            echo json_encode(['success' => true, 'message' => 'Controller hoạt động bình thường']);
            break;
            
        case 'get_locations':
            // Lấy danh sách tất cả địa điểm
            $limit = (int)($_GET['limit'] ?? 10);
            $limit = $limit > 0 ? $limit : 10;
            
            // Ensure table and column exist
            $checkTable = $pdo->query("SHOW TABLES LIKE 'diadiem'");
            if ($checkTable->rowCount() == 0) {
                echo json_encode(['success' => false, 'error' => 'Bảng diadiem không tồn tại']);
                break;
            }
            
            $columns = $pdo->query("SHOW COLUMNS FROM diadiem LIKE 'TrangThaiHoatDong'")->fetchAll();
            if (count($columns) == 0) {
                $pdo->exec("ALTER TABLE diadiem ADD COLUMN TrangThaiHoatDong VARCHAR(50) DEFAULT 'Hoạt động'");
            }
            
            $stmt = $pdo->query("SELECT * FROM diadiem ORDER BY TenDiaDiem LIMIT {$limit}");
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'locations' => $locations]);
            break;
            
        case 'get_location':
            // Lấy thông tin địa điểm theo ID
            $locationId = $_POST['id'] ?? $_GET['id'] ?? null;
            if (!$locationId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID địa điểm']);
                break;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM diadiem WHERE ID_DD = ?");
            $stmt->execute([$locationId]);
            $location = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($location) {
                echo json_encode(['success' => true, 'location' => $location]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy địa điểm']);
            }
            break;
            
        case 'add_location':
            // Thêm địa điểm mới
            $input = $_POST;
            
            // Debug: Log received data
            error_log("Add location - Received data: " . json_encode($input));
            
            $requiredFields = ['TenDiaDiem', 'LoaiDiaDiem', 'DiaChi', 'SucChua'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                    exit();
                }
            }
            
            // Validate LoaiDiaDiem
            $allowedTypes = ['Trong nhà', 'Ngoài trời'];
            if (!in_array($input['LoaiDiaDiem'], $allowedTypes)) {
                echo json_encode(['success' => false, 'error' => 'Loại địa điểm không hợp lệ']);
                break;
            }
            
            // Validate capacity
            if (!is_numeric($input['SucChua']) || $input['SucChua'] <= 0) {
                echo json_encode(['success' => false, 'error' => 'Sức chứa phải là số dương']);
                break;
            }
            
            // Check if location name already exists
            $stmt = $pdo->prepare("SELECT ID_DD FROM diadiem WHERE TenDiaDiem = ?");
            $stmt->execute([$input['TenDiaDiem']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Tên địa điểm đã tồn tại']);
                break;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO diadiem (TenDiaDiem, LoaiDiaDiem, DiaChi, SucChua, MoTa, GiaThue, TrangThaiHoatDong) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $input['TenDiaDiem'],
                $input['LoaiDiaDiem'] ?? 'Trong nhà',
                $input['DiaChi'],
                $input['SucChua'],
                $input['MoTa'] ?? '',
                $input['GiaThue'] ?? 0,
                $input['TrangThaiHoatDong'] ?? 'Hoạt động'
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Thêm địa điểm thành công']);
            break;
            
        case 'update_location':
            // Cập nhật địa điểm
            $input = $_POST;
            
            // Debug: Log received data
            error_log("Update location - Received data: " . json_encode($input));
            
            $locationId = $input['id'] ?? null;
            if (!$locationId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID địa điểm']);
                exit();
            }
            
            $requiredFields = ['TenDiaDiem', 'LoaiDiaDiem', 'DiaChi', 'SucChua'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                    exit();
                }
            }
            
            // Validate LoaiDiaDiem
            $allowedTypes = ['Trong nhà', 'Ngoài trời'];
            if (!in_array($input['LoaiDiaDiem'], $allowedTypes)) {
                echo json_encode(['success' => false, 'error' => 'Loại địa điểm không hợp lệ']);
                break;
            }
            
            // Validate capacity
            if (!is_numeric($input['SucChua']) || $input['SucChua'] <= 0) {
                echo json_encode(['success' => false, 'error' => 'Sức chứa phải là số dương']);
                break;
            }
            
            // Check if location exists
            $stmt = $pdo->prepare("SELECT TenDiaDiem FROM diadiem WHERE ID_DD = ?");
            $stmt->execute([$locationId]);
            $existingLocation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingLocation) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy địa điểm']);
                break;
            }
            
            // Check if new name conflicts with other locations
            if ($input['TenDiaDiem'] !== $existingLocation['TenDiaDiem']) {
                $stmt = $pdo->prepare("SELECT ID_DD FROM diadiem WHERE TenDiaDiem = ? AND ID_DD != ?");
                $stmt->execute([$input['TenDiaDiem'], $locationId]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'Tên địa điểm đã tồn tại']);
                    break;
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE diadiem 
                SET TenDiaDiem = ?, LoaiDiaDiem = ?, DiaChi = ?, SucChua = ?, MoTa = ?, GiaThue = ?, TrangThaiHoatDong = ?
                WHERE ID_DD = ?
            ");
            
            $stmt->execute([
                $input['TenDiaDiem'],
                $input['LoaiDiaDiem'] ?? 'Trong nhà',
                $input['DiaChi'],
                $input['SucChua'],
                $input['MoTa'] ?? '',
                $input['GiaThue'] ?? 0,
                $input['TrangThaiHoatDong'] ?? 'Hoạt động',
                $locationId
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Cập nhật địa điểm thành công']);
            break;
            
        case 'delete_location':
            // Xóa địa điểm
            try {
                error_log("Delete location - POST data: " . json_encode($_POST));
                error_log("Delete location - GET data: " . json_encode($_GET));
                
                $locationId = $_POST['id'] ?? $_GET['id'] ?? null;
                error_log("Delete location - Location ID: " . $locationId);
                
                if (!$locationId) {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => 'Thiếu ID địa điểm']);
                    break;
                }
                
                // Check if location is being used in event registrations
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM datlichsukien WHERE ID_DD = ?");
                $stmt->execute([$locationId]);
                $eventCount = $stmt->fetchColumn();
                
                if ($eventCount > 0) {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => "Không thể xóa địa điểm vì đang được sử dụng trong {$eventCount} sự kiện"]);
                    break;
                }
                
                // Get location name for notification
                $stmt = $pdo->prepare("SELECT TenDiaDiem FROM diadiem WHERE ID_DD = ?");
                $stmt->execute([$locationId]);
                $location = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$location) {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => 'Không tìm thấy địa điểm']);
                    break;
                }
                
                $stmt = $pdo->prepare("DELETE FROM diadiem WHERE ID_DD = ?");
                $result = $stmt->execute([$locationId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Xóa địa điểm thành công']);
                } else {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => 'Không thể xóa địa điểm']);
                }
            } catch (Exception $e) {
                error_log("Delete location error: " . $e->getMessage());
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Lỗi xóa địa điểm: ' . $e->getMessage()]);
            }
            exit();
            
        case 'get_location_stats':
            // Lấy thống kê địa điểm
            $checkTable = $pdo->query("SHOW TABLES LIKE 'diadiem'");
            if ($checkTable->rowCount() == 0) {
                echo json_encode(['success' => false, 'error' => 'Bảng diadiem không tồn tại']);
                break;
            }
            
            // Ensure TrangThaiHoatDong column exists
            $columns = $pdo->query("SHOW COLUMNS FROM diadiem LIKE 'TrangThaiHoatDong'")->fetchAll();
            if (count($columns) == 0) {
                $pdo->exec("ALTER TABLE diadiem ADD COLUMN TrangThaiHoatDong VARCHAR(50) DEFAULT 'Hoạt động'");
            }
            
            $stats = [];
            $stats['total_locations'] = (int)$pdo->query("SELECT COUNT(*) FROM diadiem")->fetchColumn();
            $stats['active_locations'] = (int)$pdo->query("SELECT COUNT(*) FROM diadiem WHERE TrangThaiHoatDong = 'Hoạt động' OR TrangThaiHoatDong IS NULL")->fetchColumn();
            $stats['inactive_locations'] = (int)$pdo->query("SELECT COUNT(*) FROM diadiem WHERE TrangThaiHoatDong IN ('Bảo trì', 'Ngừng hoạt động')")->fetchColumn();
            $stats['total_capacity'] = (int)($pdo->query("SELECT SUM(SucChua) FROM diadiem WHERE TrangThaiHoatDong = 'Hoạt động' OR TrangThaiHoatDong IS NULL")->fetchColumn() ?? 0);
            
            echo json_encode([
                'success' => true, 
                'stats' => $stats
            ]);
            break;
            
        case 'toggle_status':
            // Toggle location status
            $locationId = $_POST['id'] ?? null;
            $newStatus = $_POST['status'] ?? null;
            
            if (!$locationId || !$newStatus) {
                echo json_encode(['success' => false, 'error' => 'Thiếu thông tin cần thiết']);
                break;
            }
            
            // Check if location exists
            $stmt = $pdo->prepare("SELECT TenDiaDiem FROM diadiem WHERE ID_DD = ?");
            $stmt->execute([$locationId]);
            $location = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$location) {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy địa điểm']);
                break;
            }
            
            // Ensure column exists
            $columns = $pdo->query("SHOW COLUMNS FROM diadiem LIKE 'TrangThaiHoatDong'")->fetchAll();
            if (count($columns) == 0) {
                $pdo->exec("ALTER TABLE diadiem ADD COLUMN TrangThaiHoatDong VARCHAR(50) DEFAULT 'Hoạt động'");
            }
            
            // Validate status
            $allowedStatuses = ['Hoạt động', 'Bảo trì', 'Ngừng hoạt động'];
            if (!in_array($newStatus, $allowedStatuses, true)) {
                $newStatus = 'Hoạt động';
            }
            
            $stmt = $pdo->prepare("UPDATE diadiem SET TrangThaiHoatDong = ? WHERE ID_DD = ?");
            $result = $stmt->execute([$newStatus, $locationId]);
            
            if ($result) {
                $statusText = $newStatus === 'Hoạt động' ? 'kích hoạt' : ($newStatus === 'Bảo trì' ? 'chuyển bảo trì' : 'tắt hoạt động');
                echo json_encode(['success' => true, 'message' => "Đã {$statusText} địa điểm thành công"]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Lỗi khi cập nhật trạng thái']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

ob_end_flush();
?>