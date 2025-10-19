<?php
/**
 * Admin Event Registration Controller
 * Cho phép quản lý sự kiện (role 3) đăng ký sự kiện thay mặt khách hàng
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../auth/auth.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi tải file: ' . $e->getMessage()]);
    exit;
}


if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$userId = getCurrentUserId();
$userRole = getCurrentUserRole();

// Chỉ cho phép Admin (1) và Event Manager (3)
if (!in_array($userRole, [1, 3])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Không có quyền truy cập']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();

    switch ($action) {
        case 'get_customers':
            getCustomers($pdo);
            break;
        case 'get_event_types':
            getEventTypes($pdo);
            break;
        case 'get_locations_by_type':
            getLocationsByType($pdo);
            break;
        case 'get_all_equipment':
            getAllEquipment($pdo);
            break;
        case 'get_combo_suggestions':
            getComboSuggestions($pdo);
            break;
        case 'get_all_combos':
            getAllCombos($pdo);
            break;
        case 'register_event':
            registerEvent($pdo, $userId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action không hợp lệ: ' . $action]);
            break;
    }

} catch (Exception $e) {
    error_log('Admin Event Register error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}

/**
 * Lấy danh sách khách hàng
 */
function getCustomers($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.ID_User,
                u.Email,
                u.TrangThai,
                COALESCE(kh.HoTen, u.Email) as HoTen,
                kh.SoDienThoai,
                kh.DiaChi
            FROM users u
            LEFT JOIN khachhanginfo kh ON u.ID_User = kh.ID_User
            WHERE u.ID_Role = 5 AND u.TrangThai = 'Hoạt động'
            ORDER BY COALESCE(kh.HoTen, u.Email) ASC
        ");
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'customers' => $customers]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi khi tải danh sách khách hàng: ' . $e->getMessage()]);
    }
}

/**
 * Lấy danh sách loại sự kiện
 */
function getEventTypes($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT ID_LoaiSK, TenLoai, MoTa
            FROM loaisukien
            ORDER BY TenLoai ASC
        ");
        $stmt->execute();
        $eventTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'event_types' => $eventTypes]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi khi tải loại sự kiện: ' . $e->getMessage()]);
    }
}

/**
 * Lấy địa điểm theo loại sự kiện
 */
function getLocationsByType($pdo) {
    $eventType = $_GET['event_type'] ?? '';
    
    if (empty($eventType)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu loại sự kiện']);
        return;
    }
    
    try {
        // Use the diadiem_loaisk relationship table to get locations for specific event type
        $stmt = $pdo->prepare("
            SELECT 
                dd.ID_DD,
                dd.TenDiaDiem,
                dd.DiaChi,
                dd.SucChua,
                dd.LoaiDiaDiem,
                dd.GiaThue,
                dd.MoTa,
                dd.TrangThaiHoatDong as TrangThai,
                ls.TenLoai as LoaiSuKien
            FROM diadiem dd
            INNER JOIN diadiem_loaisk dl ON dd.ID_DD = dl.ID_DD
            INNER JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
            WHERE dd.TrangThaiHoatDong = 'Hoạt động'
            AND dl.ID_LoaiSK = ?
            ORDER BY dd.GiaThue ASC
        ");
        $stmt->execute([$eventType]);
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'locations' => $locations]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi khi tải địa điểm: ' . $e->getMessage()]);
    }
}

/**
 * Lấy tất cả thiết bị
 */
function getAllEquipment($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                tb.ID_TB,
                tb.TenThietBi,
                tb.LoaiThietBi,
                tb.HangSX,
                tb.GiaThue,
                tb.DonViTinh,
                tb.TrangThai,
                tb.MoTa,
                tb.SoLuong,
                tb.HinhAnh
            FROM thietbi tb
            WHERE tb.TrangThai = 'Sẵn sàng'
            ORDER BY tb.LoaiThietBi ASC, tb.TenThietBi ASC
        ");
        $stmt->execute();
        $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
        echo json_encode(['success' => true, 'equipment' => $equipment]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi khi tải thiết bị: ' . $e->getMessage()]);
    }
}

/**
 * Lấy combo gợi ý theo loại sự kiện
 */
function getComboSuggestions($pdo) {
    $eventType = $_GET['event_type'] ?? '';
    
    try {
        if (!empty($eventType)) {
        $stmt = $pdo->prepare("
            SELECT 
                c.ID_Combo,
                c.TenCombo,
                c.MoTa,
                c.GiaCombo
            FROM combo c
            WHERE (
                c.TenCombo LIKE ? OR
                c.MoTa LIKE ?
            )
            ORDER BY c.GiaCombo ASC
        ");
            $searchTerm = "%$eventType%";
            $stmt->execute([$searchTerm, $searchTerm]);
        } else {
            $stmt = $pdo->prepare("
                SELECT 
                    c.ID_Combo,
                    c.TenCombo,
                    c.MoTa,
                    c.GiaCombo
                FROM combo c
                ORDER BY c.GiaCombo ASC
            ");
            $stmt->execute();
        }
        
        $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy chi tiết thiết bị cho mỗi combo
        foreach ($combos as &$combo) {
            $stmt = $pdo->prepare("
                SELECT 
                    ct.ID_TB,
                    tb.TenThietBi,
                    ct.SoLuong
                FROM combo_thietbi ct
                JOIN thietbi tb ON ct.ID_TB = tb.ID_TB
                WHERE ct.ID_Combo = ?
                ORDER BY tb.TenThietBi ASC
            ");
            $stmt->execute([$combo['ID_Combo']]);
            $combo['equipment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['success' => true, 'combos' => $combos]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi khi tải combo: ' . $e->getMessage()]);
    }
}

/**
 * Lấy tất cả combo
 */
function getAllCombos($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.ID_Combo,
                c.TenCombo,
                c.MoTa,
                c.GiaCombo
            FROM combo c
            ORDER BY c.GiaCombo ASC
        ");
        $stmt->execute();
        $combos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy chi tiết thiết bị cho mỗi combo
        foreach ($combos as &$combo) {
            $stmt = $pdo->prepare("
                SELECT 
                    ct.ID_TB,
                    tb.TenThietBi,
                    ct.SoLuong
                FROM combo_thietbi ct
                JOIN thietbi tb ON ct.ID_TB = tb.ID_TB
                WHERE ct.ID_Combo = ?
                ORDER BY tb.TenThietBi ASC
            ");
            $stmt->execute([$combo['ID_Combo']]);
            $combo['equipment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['success' => true, 'combos' => $combos]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi khi tải combo: ' . $e->getMessage()]);
    }
}

/**
 * Đăng ký sự kiện cho khách hàng
 */
function registerEvent($pdo, $adminUserId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $customerId = $input['customer_id'] ?? '';
    $eventName = $input['event_name'] ?? '';
    $eventType = $input['event_type'] ?? '';
    $eventDate = $input['event_date'] ?? '';
    $eventTime = $input['event_time'] ?? '';
    $eventEndDate = $input['event_end_date'] ?? '';
    $eventEndTime = $input['event_end_time'] ?? '';
    $expectedGuests = $input['expected_guests'] ?? null;
    $budget = $input['budget'] ?? null;
    $description = $input['description'] ?? '';
    $notes = $input['notes'] ?? '';
    $locationId = $input['location_id'] ?? null;
    $equipmentIds = $input['equipment_ids'] ?? [];
    $comboId = $input['combo_id'] ?? null;
    
    // Validate required fields
    if (empty($customerId) || empty($eventName) || empty($eventType) || empty($eventDate) || empty($eventTime)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin bắt buộc']);
        return;
    }
    
    // Validate location is required
    if (empty($locationId)) {
        echo json_encode(['success' => false, 'error' => 'Vui lòng chọn địa điểm cho sự kiện']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Kiểm tra khách hàng có tồn tại không
        $stmt = $pdo->prepare("SELECT ID_User, Email, ID_Role FROM users WHERE ID_User = ? AND ID_Role = 5");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new Exception('Khách hàng không tồn tại. ID: ' . $customerId);
        }
        
        // Tạo sự kiện
        $startDateTime = $eventDate . ' ' . $eventTime;
        $endDateTime = $startDateTime; // Default to start time if no end time specified
        
        if (!empty($eventEndDate) && !empty($eventEndTime)) {
            $endDateTime = $eventEndDate . ' ' . $eventEndTime;
        } elseif (!empty($eventEndDate)) {
            $endDateTime = $eventEndDate . ' ' . $eventTime;
        } elseif (!empty($eventEndTime)) {
            $endDateTime = $eventDate . ' ' . $eventEndTime;
        }
        
        // Ensure end time is after start time
        if (strtotime($endDateTime) <= strtotime($startDateTime)) {
            // Add 2 hours if end time is same or before start time
            $endDateTime = date('Y-m-d H:i:s', strtotime($startDateTime) + 7200); // +2 hours
        }
        
        
        // Set status based on user role
        // Admin (1) and Event Manager (3) can approve events directly
        $eventStatus = 'Đã duyệt'; // Approved status for admin/manager created events
        
        // Tạo đặt lịch sự kiện trước
        $stmt = $pdo->prepare("
            INSERT INTO datlichsukien (
                ID_KhachHang, TenSuKien, MoTa, NgayBatDau, NgayKetThuc, 
                ID_DD, ID_LoaiSK, SoNguoiDuKien, NganSach, 
                TrangThaiDuyet, GhiChu, NgayTao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $customerId, $eventName, $description, $startDateTime, $endDateTime,
            $locationId, $eventType, $expectedGuests, $budget,
            $eventStatus, $notes
        ]);
        
        if (!$result) {
            throw new Exception('Không thể tạo đặt lịch sự kiện');
        }
        
        $datLichId = $pdo->lastInsertId();
        
        // Tạo sự kiện thực tế
        $stmt = $pdo->prepare("
            INSERT INTO sukien (
                ID_DatLich, TenSuKien, NgayBatDauThucTe, NgayKetThucThucTe, 
                TrangThaiThucTe, NgayTao
            ) VALUES (?, ?, ?, ?, 'Đang chuẩn bị', NOW())
        ");
        
        $result = $stmt->execute([
            $datLichId, $eventName, $startDateTime, $endDateTime
        ]);
        
        if (!$result) {
            throw new Exception('Không thể tạo sự kiện');
        }
        
        $eventId = $pdo->lastInsertId();
        
        // Thêm combo thiết bị nếu được chọn
        if ($comboId) {
            $stmt = $pdo->prepare("
                INSERT INTO chitietdatsukien (ID_DatLich, ID_Combo, SoLuong, DonGia, GhiChu)
                VALUES (?, ?, 1, 0, 'Combo được chọn bởi quản lý sự kiện')
            ");
            $result = $stmt->execute([$datLichId, $comboId]);
            if (!$result) {
                throw new Exception('Không thể thêm combo thiết bị');
            }
        }
        
        // Thêm thiết bị riêng lẻ nếu được chọn
        if (!empty($equipmentIds)) {
            foreach ($equipmentIds as $equipmentId) {
                // Lấy giá thiết bị
                $stmt = $pdo->prepare("SELECT GiaThue FROM thietbi WHERE ID_TB = ?");
                $stmt->execute([$equipmentId]);
                $equipmentPrice = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("
                    INSERT INTO chitietdatsukien (ID_DatLich, ID_TB, SoLuong, DonGia, GhiChu)
                    VALUES (?, ?, 1, ?, 'Thiết bị được chọn bởi quản lý sự kiện')
                ");
                $result = $stmt->execute([$datLichId, $equipmentId, $equipmentPrice]);
                if (!$result) {
                    throw new Exception('Không thể thêm thiết bị');
                }
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Đăng ký sự kiện thành công - Sự kiện đã được duyệt',
            'event_id' => $eventId,
            'datlich_id' => $datLichId,
            'status' => $eventStatus
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Lỗi khi đăng ký sự kiện: ' . $e->getMessage()]);
    }
}
?>
