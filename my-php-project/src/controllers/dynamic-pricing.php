<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2, 3])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_pricing_rules':
        getPricingRules();
        break;
    case 'add_pricing_rule':
        addPricingRule();
        break;
    case 'update_pricing_rule':
        updatePricingRule();
        break;
    case 'delete_pricing_rule':
        deletePricingRule();
        break;
    case 'calculate_price':
        calculatePrice();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}

function getPricingRules() {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT 
                pr.ID_PricingRule,
                pr.TenRule,
                pr.MoTa,
                pr.LoaiRule,
                pr.GiaTri,
                pr.TrangThai,
                pr.NgayTao,
                pr.NgayCapNhat
            FROM pricing_rules pr
            ORDER BY pr.NgayTao DESC
        ");
        $stmt->execute();
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'rules' => $rules]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy danh sách quy tắc giá: ' . $e->getMessage()]);
    }
}

function addPricingRule() {
    try {
        $pdo = getDBConnection();
        
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $ruleType = $_POST['rule_type'] ?? '';
        $value = $_POST['value'] ?? '';
        $status = $_POST['status'] ?? 'Hoạt động';
        
        if (empty($name) || empty($ruleType) || empty($value)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO pricing_rules (TenRule, MoTa, LoaiRule, GiaTri, TrangThai, NgayTao)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$name, $description, $ruleType, $value, $status]);
        
        echo json_encode(['success' => true, 'message' => 'Thêm quy tắc giá thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm quy tắc giá: ' . $e->getMessage()]);
    }
}

function updatePricingRule() {
    try {
        $pdo = getDBConnection();
        
        $ruleId = $_POST['rule_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $ruleType = $_POST['rule_type'] ?? '';
        $value = $_POST['value'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (empty($ruleId) || empty($name) || empty($ruleType) || empty($value)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $stmt = $pdo->prepare("
            UPDATE pricing_rules 
            SET TenRule = ?, MoTa = ?, LoaiRule = ?, GiaTri = ?, TrangThai = ?, NgayCapNhat = NOW()
            WHERE ID_PricingRule = ?
        ");
        $stmt->execute([$name, $description, $ruleType, $value, $status, $ruleId]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật quy tắc giá thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật quy tắc giá: ' . $e->getMessage()]);
    }
}

function deletePricingRule() {
    try {
        $pdo = getDBConnection();
        
        $ruleId = $_POST['rule_id'] ?? '';
        
        if (empty($ruleId)) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM pricing_rules WHERE ID_PricingRule = ?");
        $stmt->execute([$ruleId]);
        
        echo json_encode(['success' => true, 'message' => 'Xóa quy tắc giá thành công']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa quy tắc giá: ' . $e->getMessage()]);
    }
}

function calculatePrice() {
    try {
        $pdo = getDBConnection();
        
        $eventType = $_POST['event_type'] ?? '';
        $duration = $_POST['duration'] ?? 0;
        $guestCount = $_POST['guest_count'] ?? 0;
        $location = $_POST['location'] ?? '';
        
        if (empty($eventType) || $duration <= 0 || $guestCount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
            return;
        }
        
        // Get base pricing rules
        $stmt = $pdo->prepare("
            SELECT LoaiRule, GiaTri 
            FROM pricing_rules 
            WHERE TrangThai = 'Hoạt động'
        ");
        $stmt->execute();
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $basePrice = 0;
        $totalPrice = 0;
        
        // Calculate base price
        foreach ($rules as $rule) {
            if ($rule['LoaiRule'] === 'base_price') {
                $basePrice = floatval($rule['GiaTri']);
                break;
            }
        }
        
        $totalPrice = $basePrice;
        
        // Apply duration multiplier
        foreach ($rules as $rule) {
            if ($rule['LoaiRule'] === 'duration_multiplier') {
                $multiplier = floatval($rule['GiaTri']);
                $totalPrice += ($basePrice * $multiplier * $duration);
                break;
            }
        }
        
        // Apply guest count multiplier
        foreach ($rules as $rule) {
            if ($rule['LoaiRule'] === 'guest_multiplier') {
                $multiplier = floatval($rule['GiaTri']);
                $totalPrice += ($basePrice * $multiplier * $guestCount);
                break;
            }
        }
        
        echo json_encode([
            'success' => true, 
            'base_price' => $basePrice,
            'total_price' => $totalPrice,
            'formatted_price' => number_format($totalPrice, 0, ',', '.') . ' VNĐ'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi tính giá: ' . $e->getMessage()]);
    }
}
?>