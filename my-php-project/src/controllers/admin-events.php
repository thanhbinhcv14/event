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

// Check if user is logged in and is admin
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit();
}

$user = $_SESSION['user'];
$userRole = $user['ID_Role'] ?? $user['role'] ?? null;

// Check if user has admin privileges (role 1 = Admin, role 2 = Quản lý tổ chức, role 3 = Quản lý sự kiện)
if (!in_array($userRole, [1, 2, 3])) {
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit();
}

// Check specific permissions for role 3
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$restrictedActions = ['approve', 'reject', 'update_registration_status'];

if ($userRole == 3 && in_array($action, $restrictedActions)) {
    echo json_encode(['success' => false, 'error' => 'Role 3 không có quyền duyệt/từ chối sự kiện']);
    exit();
}

// Include Socket.IO client
require_once __DIR__ . '/../socket/socket-client.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'get_registrations':
            // Get all event registrations with related information
            try {
                $stmt = $pdo->query("
                    SELECT dl.*, 
                           d.TenDiaDiem, d.DiaChi, d.SucChua, d.GiaThue,
                           ls.TenLoai,
                           COALESCE(k.HoTen, 'Khách hàng không xác định') as HoTen, 
                           k.SoDienThoai
                    FROM datlichsukien dl
                    LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
                    LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
                    LEFT JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
                    ORDER BY dl.NgayTao DESC
                ");
                $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'registrations' => $registrations]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_registration_details':
            // Get detailed information about a specific registration
            $registrationId = $_GET['id'] ?? $_GET['registration_id'] ?? '';
            
            if (!$registrationId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID đăng ký']);
                exit();
            }
            
            try {
                // Get registration details
                $stmt = $pdo->prepare("
                    SELECT dl.*, 
                           d.TenDiaDiem, d.DiaChi, d.SucChua, d.GiaThue, d.MoTa as DiaDiemMoTa,
                           ls.TenLoai, ls.MoTa as LoaiSKMoTa,
                           k.HoTen, k.SoDienThoai, k.DiaChi as KhachHangDiaChi
                    FROM datlichsukien dl
                    LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
                    LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
                    LEFT JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
                    WHERE dl.ID_DatLich = ?
                ");
                $stmt->execute([$registrationId]);
                $registration = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$registration) {
                    echo json_encode(['success' => false, 'error' => 'Không tìm thấy đăng ký']);
                    exit();
                }
                
                // Get equipment details if any
                $stmt = $pdo->prepare("
                    SELECT ct.*, t.TenThietBi, t.LoaiThietBi, t.GiaThue, t.DonViTinh
                    FROM chitietdatsukien ct
                    LEFT JOIN thietbi t ON ct.ID_TB = t.ID_TB
                    WHERE ct.ID_DatLich = ?
                ");
                $stmt->execute([$registrationId]);
                $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $registration['equipment'] = $equipment;
                
                // Generate HTML for the modal
                $html = generateRegistrationDetailsHTML($registration);
                
                echo json_encode(['success' => true, 'html' => $html, 'registration' => $registration]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'approve':
            // Approve registration (alias for update_registration_status)
            $input = $_POST;
            $input['action'] = 'update_registration_status';
            $input['status'] = 'Đã duyệt';
            $input['registration_id'] = $input['id'] ?? '';
            unset($input['id']);
            $_POST = $input;
            // Fall through to update_registration_status case
            
        case 'reject':
            // Reject registration (alias for update_registration_status)
            $input = $_POST;
            $input['action'] = 'update_registration_status';
            $input['status'] = 'Từ chối';
            $input['registration_id'] = $input['id'] ?? '';
            unset($input['id']);
            $_POST = $input;
            // Fall through to update_registration_status case
            
        case 'update_registration_status':
            // Update registration approval status
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $registrationId = $input['registration_id'] ?? '';
            $status = $input['status'] ?? '';
            $note = $input['note'] ?? '';
            
            if (!$registrationId || !$status) {
                echo json_encode(['success' => false, 'error' => 'Thiếu thông tin bắt buộc']);
                exit();
            }
            
            if (!in_array($status, ['Chờ duyệt', 'Đã duyệt', 'Từ chối'])) {
                echo json_encode(['success' => false, 'error' => 'Trạng thái không hợp lệ']);
                exit();
            }
            
            try {
                $pdo->beginTransaction();
                
                // Update registration status
                $stmt = $pdo->prepare("
                    UPDATE datlichsukien 
                    SET TrangThaiDuyet = ?, GhiChu = ?, NgayCapNhat = CURRENT_TIMESTAMP
                    WHERE ID_DatLich = ?
                ");
                $result = $stmt->execute([$status, $note, $registrationId]);
                
                if (!$result) {
                    throw new Exception('Lỗi khi cập nhật trạng thái');
                }
                
                // If approved, create event record in sukien table
                if ($status === 'Đã duyệt') {
                    // Check if event already exists
                    $stmt = $pdo->prepare("SELECT ID_SuKien FROM sukien WHERE ID_DatLich = ?");
                    $stmt->execute([$registrationId]);
                    $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existingEvent) {
                        // Create event record
                        $stmt = $pdo->prepare("
                            INSERT INTO sukien (
                                ID_DatLich, MaSuKien, TenSuKien, NgayBatDauThucTe, NgayKetThucThucTe,
                                DiaDiemThucTe, TrangThaiThucTe, TongChiPhiThucTe, GhiChuQuanLy
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        // Generate event code
                        $eventCode = 'EV' . date('Ymd') . str_pad($registrationId, 4, '0', STR_PAD_LEFT);
                        
                        // Get registration details for event creation
                        $stmt2 = $pdo->prepare("
                            SELECT dl.TenSuKien, dl.NgayBatDau, dl.NgayKetThuc, d.TenDiaDiem, d.GiaThue
                            FROM datlichsukien dl
                            LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
                            WHERE dl.ID_DatLich = ?
                        ");
                        $stmt2->execute([$registrationId]);
                        $regDetails = $stmt2->fetch(PDO::FETCH_ASSOC);
                        
                        $stmt->execute([
                            $registrationId,
                            $eventCode,
                            $regDetails['TenSuKien'],
                            $regDetails['NgayBatDau'],
                            $regDetails['NgayKetThuc'],
                            $regDetails['TenDiaDiem'],
                            'Đang chuẩn bị',
                            $regDetails['GiaThue'] ?? 0,
                            $note
                        ]);
                    }
                }
                
                $pdo->commit();
                
                // Send notification to customer
                $stmt = $pdo->prepare("
                    SELECT k.HoTen, u.Email
                    FROM khachhanginfo k
                    INNER JOIN users u ON k.ID_User = u.ID_User
                    INNER JOIN datlichsukien dl ON k.ID_KhachHang = dl.ID_KhachHang
                    WHERE dl.ID_DatLich = ?
                ");
                $stmt->execute([$registrationId]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($customer) {
                    notifyRegistrationStatusUpdate($registrationId, $status, $customer['HoTen'], $customer['Email']);
                }
                
                echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_registration_stats':
            // Get registration statistics
            try {
                $stats = [];
                
                // Total registrations
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM datlichsukien");
                $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Pending registrations
                $stmt = $pdo->query("SELECT COUNT(*) as pending FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
                $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
                
                // Approved registrations
                $stmt = $pdo->query("SELECT COUNT(*) as approved FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
                $stats['approved'] = $stmt->fetch(PDO::FETCH_ASSOC)['approved'];
                
                // Rejected registrations
                $stmt = $pdo->query("SELECT COUNT(*) as rejected FROM datlichsukien WHERE TrangThaiDuyet = 'Từ chối'");
                $stats['rejected'] = $stmt->fetch(PDO::FETCH_ASSOC)['rejected'];
                
                // Recent registrations (last 7 days)
                $stmt = $pdo->query("
                    SELECT COUNT(*) as recent 
                    FROM datlichsukien 
                    WHERE NgayTao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ");
                $stats['recent'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
                
                echo json_encode(['success' => true, 'stats' => $stats]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_event_stats':
            // Get event statistics for dashboard
            try {
                $stats = [];
                
                // Total events
                $stmt = $pdo->query("SELECT COUNT(*) as total_events FROM datlichsukien");
                $stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_events'];
                
                // Pending events
                $stmt = $pdo->query("SELECT COUNT(*) as pending_events FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
                $stats['pending_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_events'];
                
                // Approved events
                $stmt = $pdo->query("SELECT COUNT(*) as approved_events FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
                $stats['approved_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['approved_events'];
                
                echo json_encode(['success' => true, 'stats' => $stats]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        case 'delete_registration':
            // Delete a registration (soft delete by updating status)
            $registrationId = $_GET['registration_id'] ?? '';
            
            if (!$registrationId) {
                echo json_encode(['success' => false, 'error' => 'Thiếu ID đăng ký']);
                exit();
            }
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE datlichsukien 
                    SET TrangThaiDuyet = 'Từ chối', GhiChu = CONCAT(IFNULL(GhiChu, ''), ' [Đã xóa bởi admin]')
                    WHERE ID_DatLich = ?
                ");
                $result = $stmt->execute([$registrationId]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Đã xóa đăng ký thành công']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Lỗi khi xóa đăng ký']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

// Helper function to generate HTML for registration details
function generateRegistrationDetailsHTML($registration) {
    $statusClass = '';
    $statusIcon = '';
    switch($registration['TrangThaiDuyet']) {
        case 'Chờ duyệt':
            $statusClass = 'warning';
            $statusIcon = 'fa-clock';
            break;
        case 'Đã duyệt':
            $statusClass = 'success';
            $statusIcon = 'fa-check-circle';
            break;
        case 'Từ chối':
            $statusClass = 'danger';
            $statusIcon = 'fa-times-circle';
            break;
        default:
            $statusClass = 'secondary';
            $statusIcon = 'fa-question';
    }
    
    $equipmentHtml = '';
    if (!empty($registration['equipment'])) {
        $equipmentHtml = '<div class="mt-3">
            <h6><i class="fas fa-tools"></i> Thiết bị đã đặt</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tên thiết bị</th>
                            <th>Loại</th>
                            <th>Số lượng</th>
                            <th>Giá thuê</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach($registration['equipment'] as $equipment) {
            $equipmentHtml .= '<tr>
                <td>' . htmlspecialchars($equipment['TenThietBi']) . '</td>
                <td>' . htmlspecialchars($equipment['LoaiThietBi']) . '</td>
                <td>' . $equipment['SoLuong'] . ' ' . ($equipment['DonViTinh'] ?? 'cái') . '</td>
                <td>' . number_format($equipment['GiaThue'], 0, ',', '.') . ' VNĐ</td>
            </tr>';
        }
        
        $equipmentHtml .= '</tbody>
                </table>
            </div>
        </div>';
    }
    
    return '
    <div class="row">
        <div class="col-md-6">
            <h6><i class="fas fa-calendar-alt"></i> Thông tin sự kiện</h6>
            <table class="table table-sm">
                <tr><td><strong>Tên sự kiện:</strong></td><td>' . htmlspecialchars($registration['TenSuKien']) . '</td></tr>
                <tr><td><strong>Loại sự kiện:</strong></td><td>' . htmlspecialchars($registration['TenLoai']) . '</td></tr>
                <tr><td><strong>Ngày bắt đầu:</strong></td><td>' . date('d/m/Y H:i', strtotime($registration['NgayBatDau'])) . '</td></tr>
                <tr><td><strong>Ngày kết thúc:</strong></td><td>' . date('d/m/Y H:i', strtotime($registration['NgayKetThuc'])) . '</td></tr>
                <tr><td><strong>Trạng thái:</strong></td><td><span class="badge bg-' . $statusClass . '"><i class="fas ' . $statusIcon . '"></i> ' . $registration['TrangThaiDuyet'] . '</span></td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6><i class="fas fa-user"></i> Thông tin khách hàng</h6>
            <table class="table table-sm">
                <tr><td><strong>Họ tên:</strong></td><td>' . htmlspecialchars($registration['HoTen']) . '</td></tr>
                <tr><td><strong>Số điện thoại:</strong></td><td>' . htmlspecialchars($registration['SoDienThoai']) . '</td></tr>
                <tr><td><strong>Địa chỉ:</strong></td><td>' . htmlspecialchars($registration['KhachHangDiaChi'] ?? 'Không có') . '</td></tr>
            </table>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-6">
            <h6><i class="fas fa-map-marker-alt"></i> Thông tin địa điểm</h6>
            <table class="table table-sm">
                <tr><td><strong>Tên địa điểm:</strong></td><td>' . htmlspecialchars($registration['TenDiaDiem']) . '</td></tr>
                <tr><td><strong>Địa chỉ:</strong></td><td>' . htmlspecialchars($registration['DiaChi']) . '</td></tr>
                <tr><td><strong>Sức chứa:</strong></td><td>' . number_format($registration['SucChua']) . ' người</td></tr>
                <tr><td><strong>Giá thuê:</strong></td><td>' . number_format($registration['GiaThue'], 0, ',', '.') . ' VNĐ</td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6><i class="fas fa-info-circle"></i> Thông tin khác</h6>
            <table class="table table-sm">
                <tr><td><strong>Ngày tạo:</strong></td><td>' . date('d/m/Y H:i', strtotime($registration['NgayTao'])) . '</td></tr>
                <tr><td><strong>Cập nhật:</strong></td><td>' . date('d/m/Y H:i', strtotime($registration['NgayCapNhat'])) . '</td></tr>
                <tr><td><strong>Ghi chú:</strong></td><td>' . htmlspecialchars($registration['GhiChu'] ?? 'Không có') . '</td></tr>
            </table>
        </div>
    </div>
    
    ' . $equipmentHtml;
}

// Helper function to send notification about registration status update
function notifyRegistrationStatusUpdate($registrationId, $status, $customerName, $customerEmail) {
    // This would integrate with your notification system
    // For now, we'll just log it
    error_log("Registration status update notification: ID=$registrationId, Status=$status, Customer=$customerName ($customerEmail)");
    
    // You can implement email notification, SMS, or push notification here
    // Example:
    // sendEmail($customerEmail, "Cập nhật trạng thái đăng ký sự kiện", "Đăng ký của bạn đã được $status");
}
?>