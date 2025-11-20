<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Kiểm tra người dùng đã đăng nhập và có quyền admin
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (!isset($_SESSION['user']) || !in_array($userRole, [1, 2])) {
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit();
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            // Lấy danh sách tất cả thiết bị
            $stmt = $pdo->prepare("SELECT * FROM thietbi ORDER BY TenThietBi");
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'devices' => $devices]);
            break;
            
        case 'get':
            // Lấy thông tin thiết bị theo ID
            error_log("Device Controller - POST data: " . print_r($_POST, true));
            error_log("Device Controller - GET data: " . print_r($_GET, true));
            
            $deviceId = $_POST['id'] ?? $_GET['id'] ?? null;
            error_log("Device Controller - Device ID: " . $deviceId);
            
            if (!$deviceId) {
                error_log("Device Controller - Missing device ID");
                echo json_encode(['success' => false, 'error' => 'Thiếu ID thiết bị']);
                break;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM thietbi WHERE ID_TB = ?");
            $stmt->execute([$deviceId]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($device) {
                error_log("Device Controller - Device found: " . $device['TenThietBi']);
                echo json_encode(['success' => true, 'device' => $device]);
            } else {
                error_log("Device Controller - Device not found with ID: " . $deviceId);
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy thiết bị']);
            }
            break;
            
        case 'add':
            // Thêm thiết bị mới
            $input = $_POST;
            
            $requiredFields = ['TenThietBi', 'LoaiThietBi', 'SoLuong', 'GiaThue'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                    exit();
                }
            }
            
            // Xử lý upload ảnh
            $imageName = null;
            if (isset($_FILES['HinhAnh']) && $_FILES['HinhAnh']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../img/thietbi/';
                $fileExtension = pathinfo($_FILES['HinhAnh']['name'], PATHINFO_EXTENSION);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                    $imageName = uniqid() . '_' . time() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $imageName;
                    
                    if (!move_uploaded_file($_FILES['HinhAnh']['tmp_name'], $uploadPath)) {
                        echo json_encode(['success' => false, 'error' => 'Lỗi khi upload ảnh']);
                        exit();
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Định dạng ảnh không được hỗ trợ']);
                    exit();
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO thietbi (TenThietBi, LoaiThietBi, HangSX, SoLuong, DonViTinh, GiaThue, MoTa, HinhAnh, TrangThai, NgayTao, NgayCapNhat) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $input['TenThietBi'],
                $input['LoaiThietBi'],
                $input['HangSX'] ?? null,
                $input['SoLuong'],
                $input['DonViTinh'] ?? 'Cái',
                $input['GiaThue'],
                $input['MoTa'] ?? null,
                $imageName,
                $input['TrangThai'] ?? 'Sẵn sàng'
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Thêm thiết bị thành công']);
            break;
            
        case 'update':
            // Cập nhật thiết bị
            $input = $_POST;
            
            $deviceId = $input['ID_TB'] ?? null;
            if (!$deviceId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID thiết bị']);
                exit();
            }
            
            $requiredFields = ['TenThietBi', 'LoaiThietBi', 'SoLuong', 'GiaThue'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                    exit();
                }
            }
            
            // Lấy ảnh hiện tại
            $stmt = $pdo->prepare("SELECT HinhAnh FROM thietbi WHERE ID_TB = ?");
            $stmt->execute([$deviceId]);
            $currentImage = $stmt->fetchColumn();
            
            // Xử lý upload ảnh mới (nếu có)
            $imageName = $currentImage; // Giữ ảnh cũ nếu không upload ảnh mới
            if (isset($_FILES['HinhAnh']) && $_FILES['HinhAnh']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../img/thietbi/';
                $fileExtension = pathinfo($_FILES['HinhAnh']['name'], PATHINFO_EXTENSION);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                    $imageName = uniqid() . '_' . time() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $imageName;
                    
                    if (move_uploaded_file($_FILES['HinhAnh']['tmp_name'], $uploadPath)) {
                        // Xóa ảnh cũ nếu có
                        if ($currentImage && file_exists($uploadDir . $currentImage)) {
                            unlink($uploadDir . $currentImage);
                        }
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Lỗi khi upload ảnh']);
                        exit();
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Định dạng ảnh không được hỗ trợ']);
                    exit();
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE thietbi 
                SET TenThietBi = ?, LoaiThietBi = ?, HangSX = ?, SoLuong = ?, 
                    DonViTinh = ?, GiaThue = ?, MoTa = ?, HinhAnh = ?, TrangThai = ?, NgayCapNhat = NOW()
                WHERE ID_TB = ?
            ");
            
            $stmt->execute([
                $input['TenThietBi'],
                $input['LoaiThietBi'],
                $input['HangSX'] ?? null,
                $input['SoLuong'],
                $input['DonViTinh'] ?? 'Cái',
                $input['GiaThue'],
                $input['MoTa'] ?? null,
                $imageName,
                $input['TrangThai'] ?? 'Sẵn sàng',
                $deviceId
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Cập nhật thiết bị thành công']);
            break;
            
        case 'delete':
            // Xóa thiết bị
            $deviceId = $_POST['id'] ?? $_GET['id'] ?? null;
            if (!$deviceId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID thiết bị']);
                break;
            }
            
            // Kiểm tra xem thiết bị có đang được sử dụng trong combo không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM combochitiet WHERE ID_TB = ?");
            $stmt->execute([$deviceId]);
            $inCombo = $stmt->fetchColumn() > 0;
            
            if ($inCombo) {
                echo json_encode(['success' => false, 'error' => 'Không thể xóa thiết bị đang được sử dụng trong combo']);
                break;
            }
            
            // Kiểm tra xem thiết bị có đang được sử dụng trong sự kiện không
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sukien_thietbi WHERE ID_TB = ?");
            $stmt->execute([$deviceId]);
            $inEvent = $stmt->fetchColumn() > 0;
            
            if ($inEvent) {
                echo json_encode(['success' => false, 'error' => 'Không thể xóa thiết bị đang được sử dụng trong sự kiện']);
                break;
            }
            
            // Lấy tên ảnh để xóa file
            $stmt = $pdo->prepare("SELECT HinhAnh FROM thietbi WHERE ID_TB = ?");
            $stmt->execute([$deviceId]);
            $imageToDelete = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM thietbi WHERE ID_TB = ?");
            $stmt->execute([$deviceId]);
            
            // Xóa file ảnh nếu có
            if ($imageToDelete && file_exists('../../img/thietbi/' . $imageToDelete)) {
                unlink('../../img/thietbi/' . $imageToDelete);
            }

            echo json_encode(['success' => true, 'message' => 'Xóa thiết bị thành công']);
            break;
            
        case 'get_stats':
            // Lấy thống kê thiết bị
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM thietbi");
            $stmt->execute();
            $total = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT LoaiThietBi, COUNT(*) as count FROM thietbi GROUP BY LoaiThietBi");
            $stmt->execute();
            $byType = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT SUM(SoLuong) as total_quantity FROM thietbi");
            $stmt->execute();
            $totalQuantity = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true, 
                'stats' => [
                    'total' => $total,
                    'byType' => $byType,
                    'totalQuantity' => $totalQuantity
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Device Controller Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
