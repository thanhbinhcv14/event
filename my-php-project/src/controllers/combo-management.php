<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
header('Content-Type: application/json; charset=utf-8');

// Helper function để trả về JSON với encoding đúng
function jsonResponse($data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Kiểm tra người dùng đã đăng nhập và có quyền admin
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (!isset($_SESSION['user']) || !in_array($userRole, [1, 2])) {
    echo jsonResponse(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit();
}

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            // Lấy danh sách tất cả combo
            $stmt = $pdo->prepare("
                SELECT 
                    c.*,
                    COUNT(DISTINCT ct.ID_TB) as SoThietBi,
                    COUNT(DISTINCT ctds.ID_CT) as SoLanSuDung
                FROM combo c
                LEFT JOIN combo_thietbi ct ON c.ID_Combo = ct.ID_Combo
                LEFT JOIN chitietdatsukien ctds ON c.ID_Combo = ctds.ID_Combo
                GROUP BY c.ID_Combo
                ORDER BY c.NgayTao DESC
            ");
            $stmt->execute();
            $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo jsonResponse(['success' => true, 'combos' => $combos]);
            break;
            
        case 'get':
            // Lấy thông tin combo theo ID
            $comboId = $_POST['id'] ?? $_GET['id'] ?? null;
            
            if (!$comboId) {
                echo jsonResponse(['success' => false, 'error' => 'Thiếu ID combo']);
                break;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM combo WHERE ID_Combo = ?");
            $stmt->execute([$comboId]);
            $combo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($combo) {
                // Lấy danh sách thiết bị trong combo
                $stmt = $pdo->prepare("
                    SELECT 
                        ct.ID_TB,
                        ct.SoLuong,
                        t.TenThietBi,
                        t.LoaiThietBi,
                        t.GiaThue,
                        t.HinhAnh,
                        t.SoLuong as SoLuongTonKho
                    FROM combo_thietbi ct
                    INNER JOIN thietbi t ON ct.ID_TB = t.ID_TB
                    WHERE ct.ID_Combo = ?
                    ORDER BY t.LoaiThietBi, t.TenThietBi
                ");
                $stmt->execute([$comboId]);
                $combo['equipment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo jsonResponse(['success' => true, 'combo' => $combo]);
            } else {
                echo jsonResponse(['success' => false, 'error' => 'Không tìm thấy combo']);
            }
            break;
            
        case 'add':
            // Thêm combo mới
            $tenCombo = trim($_POST['TenCombo'] ?? '');
            $moTa = trim($_POST['MoTa'] ?? '');
            $giaCombo = floatval($_POST['GiaCombo'] ?? 0);
            $equipment = json_decode($_POST['equipment'] ?? '[]', true);
            
            // Validation
            if (empty($tenCombo)) {
                echo jsonResponse(['success' => false, 'error' => 'Tên combo không được để trống']);
                break;
            }
            
            if ($giaCombo < 0) {
                echo jsonResponse(['success' => false, 'error' => 'Giá combo phải lớn hơn hoặc bằng 0']);
                break;
            }
            
            if (empty($equipment) || !is_array($equipment)) {
                echo jsonResponse(['success' => false, 'error' => 'Combo phải có ít nhất một thiết bị']);
                break;
            }
            
            // Kiểm tra trùng tên
            $stmt = $pdo->prepare("SELECT ID_Combo FROM combo WHERE TenCombo = ?");
            $stmt->execute([$tenCombo]);
            if ($stmt->fetch()) {
                echo jsonResponse(['success' => false, 'error' => 'Tên combo đã tồn tại']);
                break;
            }
            
            $pdo->beginTransaction();
            
            try {
                // Thêm combo
                $stmt = $pdo->prepare("INSERT INTO combo (TenCombo, MoTa, GiaCombo) VALUES (?, ?, ?)");
                $stmt->execute([$tenCombo, $moTa, $giaCombo]);
                $comboId = $pdo->lastInsertId();
                
                // Thêm thiết bị vào combo
                $stmt = $pdo->prepare("INSERT INTO combo_thietbi (ID_Combo, ID_TB, SoLuong) VALUES (?, ?, ?)");
                foreach ($equipment as $item) {
                    $deviceId = intval($item['ID_TB'] ?? 0);
                    $quantity = intval($item['SoLuong'] ?? 1);
                    
                    if ($deviceId > 0 && $quantity > 0) {
                        $stmt->execute([$comboId, $deviceId, $quantity]);
                    }
                }
                
                $pdo->commit();
                echo jsonResponse(['success' => true, 'message' => 'Đã thêm combo thành công', 'combo_id' => $comboId]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'update':
            // Cập nhật combo
            $comboId = intval($_POST['ID_Combo'] ?? 0);
            $tenCombo = trim($_POST['TenCombo'] ?? '');
            $moTa = trim($_POST['MoTa'] ?? '');
            $giaCombo = floatval($_POST['GiaCombo'] ?? 0);
            $equipment = json_decode($_POST['equipment'] ?? '[]', true);
            
            if ($comboId <= 0) {
                echo jsonResponse(['success' => false, 'error' => 'ID combo không hợp lệ']);
                break;
            }
            
            // Validation
            if (empty($tenCombo)) {
                echo jsonResponse(['success' => false, 'error' => 'Tên combo không được để trống']);
                break;
            }
            
            if ($giaCombo < 0) {
                echo jsonResponse(['success' => false, 'error' => 'Giá combo phải lớn hơn hoặc bằng 0']);
                break;
            }
            
            if (empty($equipment) || !is_array($equipment)) {
                echo jsonResponse(['success' => false, 'error' => 'Combo phải có ít nhất một thiết bị']);
                break;
            }
            
            // Kiểm tra combo có tồn tại không
            $stmt = $pdo->prepare("SELECT ID_Combo FROM combo WHERE ID_Combo = ?");
            $stmt->execute([$comboId]);
            if (!$stmt->fetch()) {
                echo jsonResponse(['success' => false, 'error' => 'Combo không tồn tại']);
                break;
            }
            
            // Kiểm tra trùng tên (trừ combo hiện tại)
            $stmt = $pdo->prepare("SELECT ID_Combo FROM combo WHERE TenCombo = ? AND ID_Combo != ?");
            $stmt->execute([$tenCombo, $comboId]);
            if ($stmt->fetch()) {
                echo jsonResponse(['success' => false, 'error' => 'Tên combo đã tồn tại']);
                break;
            }
            
            // Cho phép thay đổi giá combo bất cứ lúc nào
            // Giá đã lưu trong chitietdatsukien.DonGia sẽ không bị ảnh hưởng
            // vì giá được lưu tại thời điểm đặt sự kiện và giữ nguyên
            
            $pdo->beginTransaction();
            
            try {
                // Cập nhật combo (bao gồm cả giá)
                $stmt = $pdo->prepare("UPDATE combo SET TenCombo = ?, MoTa = ?, GiaCombo = ? WHERE ID_Combo = ?");
                $stmt->execute([$tenCombo, $moTa, $giaCombo, $comboId]);
                
                // Xóa tất cả thiết bị cũ
                $stmt = $pdo->prepare("DELETE FROM combo_thietbi WHERE ID_Combo = ?");
                $stmt->execute([$comboId]);
                
                // Thêm lại thiết bị
                $stmt = $pdo->prepare("INSERT INTO combo_thietbi (ID_Combo, ID_TB, SoLuong) VALUES (?, ?, ?)");
                foreach ($equipment as $item) {
                    $deviceId = intval($item['ID_TB'] ?? 0);
                    $quantity = intval($item['SoLuong'] ?? 1);
                    
                    if ($deviceId > 0 && $quantity > 0) {
                        $stmt->execute([$comboId, $deviceId, $quantity]);
                    }
                }
                
                $pdo->commit();
                echo jsonResponse(['success' => true, 'message' => 'Đã cập nhật combo thành công']);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'delete':
            // Xóa combo
            $comboId = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            
            if ($comboId <= 0) {
                echo jsonResponse(['success' => false, 'error' => 'ID combo không hợp lệ']);
                break;
            }
            
            // Kiểm tra combo có đang được sử dụng không
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM chitietdatsukien 
                WHERE ID_Combo = ?
            ");
            $stmt->execute([$comboId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                echo jsonResponse([
                    'success' => false, 
                    'error' => 'Không thể xóa combo vì đang được sử dụng trong các sự kiện. Vui lòng xóa hoặc cập nhật các sự kiện liên quan trước.'
                ]);
                break;
            }
            
            $pdo->beginTransaction();
            
            try {
                // Xóa thiết bị trong combo (cascade sẽ tự xóa)
                $stmt = $pdo->prepare("DELETE FROM combo_thietbi WHERE ID_Combo = ?");
                $stmt->execute([$comboId]);
                
                // Xóa combo
                $stmt = $pdo->prepare("DELETE FROM combo WHERE ID_Combo = ?");
                $stmt->execute([$comboId]);
                
                $pdo->commit();
                echo jsonResponse(['success' => true, 'message' => 'Đã xóa combo thành công']);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'get_stats':
            // Lấy thống kê combo
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM combo");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo jsonResponse(['success' => true, 'stats' => ['total' => $total]]);
            break;
            
        default:
            echo jsonResponse(['success' => false, 'error' => 'Action không hợp lệ']);
            break;
    }
} catch (PDOException $e) {
    error_log("Combo Management Error: " . $e->getMessage());
    echo jsonResponse(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Combo Management Error: " . $e->getMessage());
    echo jsonResponse(['success' => false, 'error' => 'Lỗi: ' . $e->getMessage()]);
}
?>

