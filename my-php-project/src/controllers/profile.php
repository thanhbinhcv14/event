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
$userRole = $user['ID_Role'] ?? $user['role'] ?? null;
$userId = $user['ID_User'] ?? $user['id'] ?? null;

// Determine user type and table name
$userTable = 'users';
$userInfoTable = '';
$userInfoFields = [];

if ($userRole == 5) {
    // Customer
    $userInfoTable = 'khachhanginfo';
    $userInfoFields = ['HoTen', 'SoDienThoai', 'DiaChi', 'NgaySinh'];
} else {
    // Staff (roles 1,2,3,4)
    $userInfoTable = 'nhanvieninfo';
    $userInfoFields = ['HoTen', 'SoDienThoai', 'DiaChi', 'NgaySinh', 'ChucVu', 'Luong', 'NgayVaoLam'];
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();

    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    if (!$userId || !$userRole) {
        echo json_encode(['success' => false, 'error' => 'Thông tin người dùng không hợp lệ - User ID: ' . $userId . ', Role: ' . $userRole]);
        exit();
    }
    
    switch ($action) {
        case 'get':
        case 'get_profile':
            // Get user profile data
            $stmt = $pdo->prepare("
                SELECT u.*, ui.* 
                FROM {$userTable} u 
                LEFT JOIN {$userInfoTable} ui ON u.ID_User = ui.ID_User 
                WHERE u.ID_User = ?
            ");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                echo json_encode(['success' => true, 'user' => $userData]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Không tìm thấy thông tin người dùng']);
            }
            break;
            
        case 'update':
            // Update user profile
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            // Validate required fields
            $requiredFields = ['fullname', 'phone', 'address', 'birthday'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    echo json_encode(['success' => false, 'error' => "Trường {$field} không được để trống"]);
                    exit();
                }
            }
            
            // Validate phone format (Vietnamese)
            if (!preg_match('/^(?:\+84|0)(3|5|7|8|9)[0-9]{8}$/', $input['phone'])) {
                echo json_encode(['success' => false, 'error' => 'Số điện thoại không hợp lệ']);
                exit();
            }
            
            // Validate password if provided
            if (!empty($input['new_password'])) {
                if (strlen($input['new_password']) < 6) {
                    echo json_encode(['success' => false, 'error' => 'Mật khẩu phải có ít nhất 6 ký tự']);
                    exit();
                }
                
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,}$/', $input['new_password'])) {
                    echo json_encode(['success' => false, 'error' => 'Mật khẩu phải bao gồm chữ hoa, chữ thường và số']);
                    exit();
                }
                
                if ($input['new_password'] !== $input['confirm_password']) {
                    echo json_encode(['success' => false, 'error' => 'Mật khẩu xác nhận không khớp']);
                    exit();
                }
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Update password if provided
                if (!empty($input['new_password'])) {
                    $hashedPassword = password_hash($input['new_password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE {$userTable} SET Password = ? WHERE ID_User = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                }
                
                // Prepare data for user info table
                $updateFields = [];
                $updateValues = [];
                
                // Map form fields to database fields
                $fieldMapping = [
                    'fullname' => 'HoTen',
                    'phone' => 'SoDienThoai',
                    'address' => 'DiaChi',
                    'birthday' => 'NgaySinh'
                ];
                
                // Add staff-specific fields if applicable
                if ($userRole != 5) {
                    $fieldMapping['chucvu'] = 'ChucVu';
                    $fieldMapping['luong'] = 'Luong';
                    $fieldMapping['ngayvaolam'] = 'NgayVaoLam';
                }
                
                foreach ($fieldMapping as $formField => $dbField) {
                    if (isset($input[$formField]) && $input[$formField] !== '') {
                        $updateFields[] = "{$dbField} = ?";
                        $updateValues[] = $input[$formField];
                    }
                }
                
                if (!empty($updateFields)) {
                    // Check if user info record exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$userInfoTable} WHERE ID_User = ?");
                    $stmt->execute([$userId]);
                    $exists = $stmt->fetchColumn() > 0;
                    
                    if ($exists) {
                        // Update existing record
                        $updateValues[] = $userId;
                        $sql = "UPDATE {$userInfoTable} SET " . implode(', ', $updateFields) . " WHERE ID_User = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($updateValues);
                    } else {
                        // Insert new record
                        $updateValues[] = $userId;
                        $fields = array_keys($fieldMapping);
                        $placeholders = str_repeat('?,', count($updateValues) - 1) . '?';
                        $sql = "INSERT INTO {$userInfoTable} (" . implode(', ', array_values($fieldMapping)) . ", ID_User) VALUES ({$placeholders})";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($updateValues);
                    }
                }
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công']);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Lỗi cập nhật: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Profile Controller Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}