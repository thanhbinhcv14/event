<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent any output before JSON
ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            getAllCodes();
            break;
            
        case 'get':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            getCode();
            break;
            
        case 'add':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            addCode();
            break;
            
        case 'update':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            updateCode();
            break;
            
        case 'delete':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            deleteCode();
            break;
            
        case 'get_stats':
            if (!checkAdminAccess()) {
                echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
                exit();
            }
            getCodeStats();
            break;
            
        case 'validate_code':
            // Public action - validate discount code
            validateCode();
            break;
            
        case 'get_available_codes':
            // Public action - get available discount codes
            getAvailableCodes();
            break;
            
        default:
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ'], JSON_UNESCAPED_UNICODE);
            exit;
            break;
    }
} catch (Exception $e) {
    error_log("Ma Giam Gia Controller - System Error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

function checkAdminAccess() {
    $userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
    return isset($_SESSION['user']) && in_array($userRole, [1, 2]);
}

function getAllCodes() {
    global $pdo;
    
    try {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $sortBy = $_GET['sort_by'] ?? 'ID_MaGiamGia';
        
        $sql = "SELECT * FROM magiamgia WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (MaCode LIKE ? OR TenMa LIKE ? OR MoTa LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($status)) {
            $sql .= " AND TrangThai = ?";
            $params[] = $status;
        }
        
        // Validate sort column
        $allowedSorts = ['ID_MaGiamGia', 'MaCode', 'TenMa', 'NgayTao', 'NgayBatDau', 'TrangThai'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'ID_MaGiamGia';
        }
        
        $sql .= " ORDER BY {$sortBy} DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'codes' => $codes]);
    } catch (Exception $e) {
        error_log("Get All Codes Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy danh sách mã giảm giá']);
    }
}

function getCode() {
    global $pdo;
    
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID mã giảm giá']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM magiamgia WHERE ID_MaGiamGia = ?");
        $stmt->execute([$id]);
        $code = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($code) {
            echo json_encode(['success' => true, 'code' => $code]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy mã giảm giá']);
        }
    } catch (Exception $e) {
        error_log("Get Code Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy thông tin mã giảm giá']);
    }
}

function addCode() {
    global $pdo;
    
    $input = $_POST;
    
    // Validate required fields
    $requiredFields = ['MaCode', 'TenMa', 'LoaiGiamGia', 'GiaTriGiamGia', 'NgayBatDau', 'NgayKetThuc'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
            return;
        }
    }
    
    // Validate MaCode format (no spaces, alphanumeric and underscore only)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $input['MaCode'])) {
        echo json_encode(['success' => false, 'error' => 'Mã code chỉ được chứa chữ cái, số và dấu gạch dưới']);
        return;
    }
    
    // Check if MaCode already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM magiamgia WHERE MaCode = ?");
    $stmt->execute([$input['MaCode']]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Mã code đã tồn tại']);
        return;
    }
    
    // Validate discount value
    if (!is_numeric($input['GiaTriGiamGia']) || $input['GiaTriGiamGia'] < 0) {
        echo json_encode(['success' => false, 'error' => 'Giá trị giảm giá phải là số dương']);
        return;
    }
    
    // Validate percentage (max 100%)
    if ($input['LoaiGiamGia'] === 'Phần trăm' && $input['GiaTriGiamGia'] > 100) {
        echo json_encode(['success' => false, 'error' => 'Phần trăm giảm giá không được vượt quá 100%']);
        return;
    }
    
    // Validate dates
    $ngayBatDau = strtotime($input['NgayBatDau']);
    $ngayKetThuc = strtotime($input['NgayKetThuc']);
    if ($ngayBatDau === false || $ngayKetThuc === false) {
        echo json_encode(['success' => false, 'error' => 'Ngày không hợp lệ']);
        return;
    }
    if ($ngayKetThuc <= $ngayBatDau) {
        echo json_encode(['success' => false, 'error' => 'Ngày kết thúc phải sau ngày bắt đầu']);
        return;
    }
    
    // Validate SoTienToiThieu
    $soTienToiThieu = isset($input['SoTienToiThieu']) && $input['SoTienToiThieu'] !== '' 
        ? floatval($input['SoTienToiThieu']) 
        : 0;
    if ($soTienToiThieu < 0) {
        echo json_encode(['success' => false, 'error' => 'Số tiền tối thiểu phải >= 0']);
        return;
    }
    
    // Validate usage limits
    $soLanSuDungToiDa = !empty($input['SoLanSuDungToiDa']) ? intval($input['SoLanSuDungToiDa']) : null;
    $soLanSuDungTongCong = !empty($input['SoLanSuDungTongCong']) ? intval($input['SoLanSuDungTongCong']) : null;
    
    if ($soLanSuDungToiDa !== null && $soLanSuDungToiDa < 1) {
        echo json_encode(['success' => false, 'error' => 'Số lần sử dụng tối đa phải >= 1']);
        return;
    }
    
    if ($soLanSuDungTongCong !== null && $soLanSuDungTongCong < 1) {
        echo json_encode(['success' => false, 'error' => 'Số lần sử dụng tổng cộng phải >= 1']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO magiamgia (
                MaCode, TenMa, MoTa, LoaiGiamGia, GiaTriGiamGia, 
                SoTienToiThieu, SoLanSuDungToiDa, SoLanSuDungTongCong, 
                SoLanDaSuDung, NgayBatDau, NgayKetThuc, TrangThai, 
                NgayTao, NgayCapNhat
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $input['MaCode'],
            $input['TenMa'],
            $input['MoTa'] ?? null,
            $input['LoaiGiamGia'],
            $input['GiaTriGiamGia'],
            $soTienToiThieu,
            $soLanSuDungToiDa,
            $soLanSuDungTongCong,
            $input['NgayBatDau'],
            $input['NgayKetThuc'],
            $input['TrangThai'] ?? 'Hoạt động'
        ]);
        
        $codeId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Thêm mã giảm giá thành công',
            'code_id' => $codeId
        ]);
    } catch (Exception $e) {
        error_log("Add Code Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi thêm mã giảm giá: ' . $e->getMessage()]);
    }
}

function updateCode() {
    global $pdo;
    
    $input = $_POST;
    $id = $input['ID_MaGiamGia'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID mã giảm giá']);
        return;
    }
    
    // Check if code exists
    $stmt = $pdo->prepare("SELECT * FROM magiamgia WHERE ID_MaGiamGia = ?");
    $stmt->execute([$id]);
    $existingCode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingCode) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy mã giảm giá']);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['MaCode', 'TenMa', 'LoaiGiamGia', 'GiaTriGiamGia', 'NgayBatDau', 'NgayKetThuc'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
            return;
        }
    }
    
    // Validate MaCode format
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $input['MaCode'])) {
        echo json_encode(['success' => false, 'error' => 'Mã code chỉ được chứa chữ cái, số và dấu gạch dưới']);
        return;
    }
    
    // Check if MaCode already exists (excluding current record)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM magiamgia WHERE MaCode = ? AND ID_MaGiamGia != ?");
    $stmt->execute([$input['MaCode'], $id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Mã code đã tồn tại']);
        return;
    }
    
    // Validate discount value
    if (!is_numeric($input['GiaTriGiamGia']) || $input['GiaTriGiamGia'] < 0) {
        echo json_encode(['success' => false, 'error' => 'Giá trị giảm giá phải là số dương']);
        return;
    }
    
    // Validate percentage
    if ($input['LoaiGiamGia'] === 'Phần trăm' && $input['GiaTriGiamGia'] > 100) {
        echo json_encode(['success' => false, 'error' => 'Phần trăm giảm giá không được vượt quá 100%']);
        return;
    }
    
    // Validate dates
    $ngayBatDau = strtotime($input['NgayBatDau']);
    $ngayKetThuc = strtotime($input['NgayKetThuc']);
    if ($ngayBatDau === false || $ngayKetThuc === false) {
        echo json_encode(['success' => false, 'error' => 'Ngày không hợp lệ']);
        return;
    }
    if ($ngayKetThuc <= $ngayBatDau) {
        echo json_encode(['success' => false, 'error' => 'Ngày kết thúc phải sau ngày bắt đầu']);
        return;
    }
    
    // Validate SoTienToiThieu
    $soTienToiThieu = isset($input['SoTienToiThieu']) && $input['SoTienToiThieu'] !== '' 
        ? floatval($input['SoTienToiThieu']) 
        : 0;
    
    // Validate usage limits
    $soLanSuDungToiDa = !empty($input['SoLanSuDungToiDa']) ? intval($input['SoLanSuDungToiDa']) : null;
    $soLanSuDungTongCong = !empty($input['SoLanSuDungTongCong']) ? intval($input['SoLanSuDungTongCong']) : null;
    
    // Check if new total limit is less than current usage
    if ($soLanSuDungTongCong !== null && $soLanSuDungTongCong < $existingCode['SoLanDaSuDung']) {
        echo json_encode([
            'success' => false, 
            'error' => "Số lần sử dụng tổng cộng mới ({$soLanSuDungTongCong}) không được nhỏ hơn số lần đã sử dụng ({$existingCode['SoLanDaSuDung']})"
        ]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE magiamgia 
            SET MaCode = ?, TenMa = ?, MoTa = ?, LoaiGiamGia = ?, GiaTriGiamGia = ?,
                SoTienToiThieu = ?, SoLanSuDungToiDa = ?, SoLanSuDungTongCong = ?,
                NgayBatDau = ?, NgayKetThuc = ?, TrangThai = ?, NgayCapNhat = NOW()
            WHERE ID_MaGiamGia = ?
        ");
        
        $stmt->execute([
            $input['MaCode'],
            $input['TenMa'],
            $input['MoTa'] ?? null,
            $input['LoaiGiamGia'],
            $input['GiaTriGiamGia'],
            $soTienToiThieu,
            $soLanSuDungToiDa,
            $soLanSuDungTongCong,
            $input['NgayBatDau'],
            $input['NgayKetThuc'],
            $input['TrangThai'] ?? 'Hoạt động',
            $id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật mã giảm giá thành công']);
    } catch (Exception $e) {
        error_log("Update Code Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi cập nhật mã giảm giá: ' . $e->getMessage()]);
    }
}

function deleteCode() {
    global $pdo;
    
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Thiếu ID mã giảm giá']);
        return;
    }
    
    // Check if code exists
    $stmt = $pdo->prepare("SELECT MaCode, SoLanDaSuDung FROM magiamgia WHERE ID_MaGiamGia = ?");
    $stmt->execute([$id]);
    $code = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$code) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy mã giảm giá']);
        return;
    }
    
    // Check if code has been used
    if ($code['SoLanDaSuDung'] > 0) {
        echo json_encode([
            'success' => false, 
            'error' => "Không thể xóa mã '{$code['MaCode']}' vì đã được sử dụng {$code['SoLanDaSuDung']} lần"
        ]);
        return;
    }
    
    // Check if code is being used in any orders
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM datlichsukien WHERE ID_MaGiamGia = ?");
    $stmt->execute([$id]);
    $usageCount = $stmt->fetchColumn();
    
    if ($usageCount > 0) {
        echo json_encode([
            'success' => false, 
            'error' => "Không thể xóa mã '{$code['MaCode']}' vì đang được sử dụng trong {$usageCount} đơn đặt lịch"
        ]);
        return;
    }
    
    try {
        // Delete usage history first
        $stmt = $pdo->prepare("DELETE FROM magiamgia_sudung WHERE ID_MaGiamGia = ?");
        $stmt->execute([$id]);
        
        // Delete the code
        $stmt = $pdo->prepare("DELETE FROM magiamgia WHERE ID_MaGiamGia = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Xóa mã giảm giá thành công']);
    } catch (Exception $e) {
        error_log("Delete Code Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi xóa mã giảm giá']);
    }
}

function getCodeStats() {
    global $pdo;
    
    try {
        // Total codes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM magiamgia");
        $stmt->execute();
        $total = $stmt->fetchColumn();
        
        // Active codes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM magiamgia WHERE TrangThai = 'Hoạt động'");
        $stmt->execute();
        $active = $stmt->fetchColumn();
        
        // Total usage
        $stmt = $pdo->prepare("SELECT SUM(SoLanDaSuDung) FROM magiamgia");
        $stmt->execute();
        $totalUsage = $stmt->fetchColumn() ?: 0;
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total' => (int)$total,
                'active' => (int)$active,
                'total_usage' => (int)$totalUsage
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get Code Stats Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi lấy thống kê']);
    }
}

function validateCode() {
    global $pdo;
    
    $code = $_GET['code'] ?? $_POST['code'] ?? '';
    $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
    $totalAmount = floatval($_GET['total_amount'] ?? $_POST['total_amount'] ?? 0);
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'error' => 'Vui lòng nhập mã giảm giá']);
        return;
    }
    
    try {
        // Get code info
        $stmt = $pdo->prepare("SELECT * FROM magiamgia WHERE MaCode = ?");
        $stmt->execute([$code]);
        $codeData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$codeData) {
            echo json_encode(['success' => false, 'error' => 'Mã giảm giá không tồn tại']);
            return;
        }
        
        // Check status
        if ($codeData['TrangThai'] !== 'Hoạt động') {
            echo json_encode(['success' => false, 'error' => 'Mã giảm giá không còn hoạt động']);
            return;
        }
        
        // Check date validity
        $now = time();
        $ngayBatDau = strtotime($codeData['NgayBatDau']);
        $ngayKetThuc = strtotime($codeData['NgayKetThuc']);
        
        if ($now < $ngayBatDau) {
            echo json_encode(['success' => false, 'error' => 'Mã giảm giá chưa có hiệu lực']);
            return;
        }
        
        if ($now > $ngayKetThuc) {
            echo json_encode(['success' => false, 'error' => 'Mã giảm giá đã hết hạn']);
            return;
        }
        
        // Check minimum amount
        if ($codeData['SoTienToiThieu'] > 0 && $totalAmount < $codeData['SoTienToiThieu']) {
            echo json_encode([
                'success' => false, 
                'error' => "Đơn hàng tối thiểu phải từ " . number_format($codeData['SoTienToiThieu'], 0, ',', '.') . " VNĐ"
            ]);
            return;
        }
        
        // Check total usage limit
        if ($codeData['SoLanSuDungTongCong'] !== null && 
            $codeData['SoLanDaSuDung'] >= $codeData['SoLanSuDungTongCong']) {
            echo json_encode(['success' => false, 'error' => 'Mã giảm giá đã hết lượt sử dụng']);
            return;
        }
        
        // Check per-user usage limit (if user_id provided)
        if ($userId && $codeData['SoLanSuDungToiDa'] !== null) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM magiamgia_sudung 
                WHERE ID_MaGiamGia = ? AND ID_User = ?
            ");
            $stmt->execute([$codeData['ID_MaGiamGia'], $userId]);
            $userUsageCount = $stmt->fetchColumn();
            
            if ($userUsageCount >= $codeData['SoLanSuDungToiDa']) {
                echo json_encode(['success' => false, 'error' => 'Bạn đã sử dụng hết lượt cho mã giảm giá này']);
                return;
            }
        }
        
        // Calculate discount amount
        $discountAmount = 0;
        if ($codeData['LoaiGiamGia'] === 'Phần trăm') {
            $discountAmount = ($totalAmount * $codeData['GiaTriGiamGia']) / 100;
        } else {
            $discountAmount = $codeData['GiaTriGiamGia'];
            // Don't allow discount more than total amount
            if ($discountAmount > $totalAmount) {
                $discountAmount = $totalAmount;
            }
        }
        
        $finalAmount = $totalAmount - $discountAmount;
        
        echo json_encode([
            'success' => true,
            'code' => $codeData,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'message' => 'Mã giảm giá hợp lệ'
        ]);
        
    } catch (Exception $e) {
        error_log("Validate Code Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi khi kiểm tra mã giảm giá']);
    }
}

function getAvailableCodes() {
    global $pdo;
    
    try {
        // Clean any previous output
        ob_clean();
        
        $now = date('Y-m-d H:i:s');
        error_log("DEBUG getAvailableCodes: Current time = " . $now);
        
        // First, let's check all codes in database
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM magiamgia WHERE TrangThai = 'Hoạt động'");
        $checkStmt->execute();
        $totalActive = $checkStmt->fetchColumn();
        error_log("DEBUG: Total active codes in database: " . $totalActive);
        
        // Debug: Check all codes first to see what's in database
        $debugStmt = $pdo->prepare("
            SELECT 
                ID_MaGiamGia,
                MaCode,
                TenMa,
                TrangThai,
                NgayBatDau,
                NgayKetThuc,
                SoLanSuDungTongCong,
                SoLanDaSuDung,
                SoLanSuDungToiDa
            FROM magiamgia
            ORDER BY ID_MaGiamGia
        ");
        $debugStmt->execute();
        $allCodes = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("DEBUG: All codes in database: " . count($allCodes));
        foreach ($allCodes as $dbCode) {
            error_log("DEBUG: DB Code - ID: {$dbCode['ID_MaGiamGia']}, Code: {$dbCode['MaCode']}, Status: {$dbCode['TrangThai']}, Start: {$dbCode['NgayBatDau']}, End: {$dbCode['NgayKetThuc']}, Used: {$dbCode['SoLanDaSuDung']}, MaxTotal: " . ($dbCode['SoLanSuDungTongCong'] ?? 'NULL'));
        }
        
        // Get active discount codes that are currently valid or upcoming
        // Check: TrangThai = 'Hoạt động', not expired, and not exceeded total usage limit
        // Show codes that:
        // 1. Are currently active (NgayBatDau <= now AND NgayKetThuc >= now)
        // 2. Or are upcoming (NgayBatDau > now AND NgayKetThuc >= now) - will be marked as "Sắp có hiệu lực"
        $stmt = $pdo->prepare("
            SELECT 
                ID_MaGiamGia,
                MaCode,
                TenMa,
                MoTa,
                LoaiGiamGia,
                GiaTriGiamGia,
                SoTienToiThieu,
                NgayBatDau,
                NgayKetThuc,
                SoLanSuDungTongCong,
                SoLanDaSuDung,
                SoLanSuDungToiDa,
                TrangThai,
                CASE 
                    WHEN NgayBatDau > ? THEN 'upcoming'
                    WHEN NgayBatDau <= ? AND NgayKetThuc >= ? THEN 'active'
                    ELSE 'expired'
                END as status_type
            FROM magiamgia
            WHERE TrangThai = 'Hoạt động'
            AND NgayKetThuc >= ?
            AND (SoLanSuDungTongCong IS NULL OR SoLanDaSuDung < SoLanSuDungTongCong)
            ORDER BY 
                CASE 
                    WHEN NgayBatDau > ? THEN 2
                    WHEN NgayBatDau <= ? AND NgayKetThuc >= ? THEN 1
                    ELSE 3
                END,
                GiaTriGiamGia DESC, 
                NgayKetThuc ASC
        ");
        $stmt->execute([$now, $now, $now, $now, $now, $now, $now]);
        $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("DEBUG getAvailableCodes: Found " . count($codes) . " codes from database after SQL filtering");
        error_log("DEBUG: Current time used for filtering: " . $now);
        
        // Debug: Log all codes found after SQL filter
        foreach ($codes as $code) {
            error_log("DEBUG: Code passed SQL filter - " . $code['MaCode'] . " | Start: " . $code['NgayBatDau'] . " | End: " . $code['NgayKetThuc']);
        }
        
        // Debug: Check which codes were filtered out and why
        foreach ($allCodes as $dbCode) {
            if ($dbCode['TrangThai'] !== 'Hoạt động') {
                error_log("DEBUG: Code {$dbCode['MaCode']} filtered out - Status: {$dbCode['TrangThai']} (not 'Hoạt động')");
                continue;
            }
            if ($dbCode['NgayBatDau'] > $now) {
                error_log("DEBUG: Code {$dbCode['MaCode']} filtered out - Start date {$dbCode['NgayBatDau']} is in the future");
                continue;
            }
            if ($dbCode['NgayKetThuc'] < $now) {
                error_log("DEBUG: Code {$dbCode['MaCode']} filtered out - End date {$dbCode['NgayKetThuc']} has passed");
                continue;
            }
            if ($dbCode['SoLanSuDungTongCong'] !== null && $dbCode['SoLanDaSuDung'] >= $dbCode['SoLanSuDungTongCong']) {
                error_log("DEBUG: Code {$dbCode['MaCode']} filtered out - Usage limit reached ({$dbCode['SoLanDaSuDung']}/{$dbCode['SoLanSuDungTongCong']})");
                continue;
            }
        }
        
        // Get user ID if logged in
        $userId = null;
        if (isset($_SESSION['user']) && isset($_SESSION['user']['ID_User'])) {
            $userId = $_SESSION['user']['ID_User'];
        }
        
        // Format the codes for display
        $formattedCodes = [];
        foreach ($codes as $code) {
            error_log("DEBUG: Processing code: " . $code['MaCode'] . " - " . $code['TenMa']);
            
            // Check if this is a "Lần đầu sử dụng" code and user has already used it
            // Only filter for logged-in users, show to non-logged-in users so they can see it
            $isFirstTimeCode = (stripos($code['TenMa'], 'Lần đầu') !== false || 
                               stripos($code['MoTa'], 'lần đầu') !== false ||
                               ($code['SoLanSuDungToiDa'] == 1 && $code['SoLanSuDungToiDa'] !== null));
            
            if ($isFirstTimeCode && $userId) {
                // Check if user has already used this code
                $usageStmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM magiamgia_sudung 
                    WHERE ID_MaGiamGia = ? AND ID_User = ?
                ");
                $usageStmt->execute([$code['ID_MaGiamGia'], $userId]);
                $usageCount = $usageStmt->fetchColumn();
                
                // If user has already used this "first time" code, skip it
                if ($usageCount > 0) {
                    error_log("DEBUG: User {$userId} has already used code {$code['MaCode']}, skipping...");
                    continue;
                }
            }
            
            // Log that code is being added to formatted list
            error_log("DEBUG: Adding code to formatted list - " . $code['MaCode'] . " - " . $code['TenMa']);
            
            // Format end date for display
            $endDate = $code['NgayKetThuc'];
            $endDateFormatted = '';
            if ($endDate) {
                try {
                    $dateObj = new DateTime($endDate);
                    $endDateFormatted = $dateObj->format('Y-m-d H:i:s'); // ISO format for JavaScript
                } catch (Exception $e) {
                    error_log("Error formatting date: " . $e->getMessage());
                    $endDateFormatted = $endDate;
                }
            }
            
            // Determine if code is upcoming (not yet active)
            $isUpcoming = isset($code['status_type']) && $code['status_type'] === 'upcoming';
            $startDate = $code['NgayBatDau'];
            $startDateDisplay = '';
            if ($isUpcoming && $startDate) {
                try {
                    $startDateObj = new DateTime($startDate);
                    $startDateDisplay = $startDateObj->format('d/m/Y H:i');
                } catch (Exception $e) {
                    error_log("Error formatting start date: " . $e->getMessage());
                }
            }
            
            $formattedCodes[] = [
                'id' => (int)$code['ID_MaGiamGia'],
                'code' => $code['MaCode'],
                'name' => $code['TenMa'],
                'description' => $code['MoTa'] ?? '',
                'type' => $code['LoaiGiamGia'],
                'value' => floatval($code['GiaTriGiamGia']),
                'min_amount' => floatval($code['SoTienToiThieu'] ?? 0),
                'end_date' => $endDateFormatted,
                'end_date_display' => $endDate ? date('d/m/Y', strtotime($endDate)) : '',
                'start_date_display' => $startDateDisplay,
                'is_upcoming' => $isUpcoming,
                'display_text' => $code['LoaiGiamGia'] === 'Phần trăm' 
                    ? "Giảm " . number_format($code['GiaTriGiamGia'], 2, ',', '.') . "%" 
                    : "Giảm " . number_format($code['GiaTriGiamGia'], 0, ',', '.') . " VNĐ"
            ];
        }
        
        error_log("DEBUG getAvailableCodes: Returning " . count($formattedCodes) . " formatted codes");
        
        $response = [
            'success' => true,
            'codes' => $formattedCodes
        ];
        
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        error_log("Get Available Codes Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        ob_clean();
        echo json_encode([
            'success' => false, 
            'error' => 'Lỗi khi lấy danh sách mã giảm giá: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
