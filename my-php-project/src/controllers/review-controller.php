<?php
require_once __DIR__ . '/../../config/database.php';

// Bắt đầu session nếu chưa bắt đầu
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Đặt content type là JSON
header('Content-Type: application/json');

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để đánh giá']);
    exit;
}

$user = $_SESSION['user'];

try {
    $pdo = getDBConnection();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'submit_review':
            submitReview($pdo, $user);
            break;
            
        case 'get_reviews':
            getReviews($pdo);
            break;
            
        case 'get_user_review':
            getUserReview($pdo, $user);
            break;
            
        default:
            // Xử lý form submission không có action parameter
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                submitReview($pdo, $user);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
    }
    
} catch (Exception $e) {
    error_log("Review Controller Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function submitReview($pdo, $user) {
    try {
        // Ghi log debug
        error_log("Review submission - User ID: " . $user['ID_User']);
        error_log("Review submission - POST data: " . print_r($_POST, true));
        
        $eventId = $_POST['event_id'] ?? '';
        $overallRating = $_POST['overall_rating'] ?? 0;
        $locationRating = $_POST['location_rating'] ?? 0;
        $equipmentRating = $_POST['equipment_rating'] ?? 0;
        $staffRating = $_POST['staff_rating'] ?? 0;
        $comment = $_POST['comment'] ?? '';
        
        error_log("Review submission - Parsed data: eventId=$eventId, overallRating=$overallRating, comment=" . substr($comment, 0, 50));
        
        if (empty($eventId) || $overallRating == 0) {
            throw new Exception('Thiếu thông tin bắt buộc: eventId=' . $eventId . ', rating=' . $overallRating);
        }
        
        // Kiểm tra sự kiện tồn tại và thuộc về người dùng
        $stmt = $pdo->prepare("
            SELECT dl.ID_DatLich, dl.ID_KhachHang, dl.TrangThaiThanhToan, s.TrangThaiThucTe as TrangThaiSuKien
            FROM datlichsukien dl
            LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
            LEFT JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
            WHERE dl.ID_DatLich = ? AND k.ID_User = ?
        ");
        $stmt->execute([$eventId, $user['ID_User']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            throw new Exception('Sự kiện không tồn tại hoặc không thuộc về bạn');
        }
        
        // Kiểm tra sự kiện đã hoàn thành chưa
        if ($event['TrangThaiSuKien'] !== 'Hoàn thành') {
            throw new Exception('Chỉ có thể đánh giá sự kiện đã hoàn thành');
        }
        
        // Kiểm tra thanh toán đã hoàn thành chưa
        if ($event['TrangThaiThanhToan'] !== 'Đã thanh toán đủ') {
            throw new Exception('Chỉ có thể đánh giá sự kiện đã thanh toán thành công');
        }
        
        // Kiểm tra đánh giá đã tồn tại chưa
        $stmt = $pdo->prepare("
            SELECT dg.ID_DanhGia FROM danhgia dg
            LEFT JOIN datlichsukien dl ON dg.ID_SuKien = dl.ID_DatLich
            LEFT JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
            WHERE dg.ID_SuKien = ? AND k.ID_User = ?
        ");
        $stmt->execute([$eventId, $user['ID_User']]);
        $existingReview = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingReview) {
            // Update existing review
            $stmt = $pdo->prepare("
                UPDATE danhgia 
                SET DiemDanhGia = ?, 
                    NoiDung = ?, 
                    DanhGiaDiaDiem = ?, 
                    DanhGiaThietBi = ?, 
                    DanhGiaNhanVien = ?,
                    ThoiGianDanhGia = NOW()
                WHERE ID_DanhGia = ?
            ");
            $stmt->execute([
                $overallRating, 
                $comment, 
                $locationRating, 
                $equipmentRating, 
                $staffRating,
                $existingReview['ID_DanhGia']
            ]);
            
            $message = 'Cập nhật đánh giá thành công!';
            $isUpdate = true;
        } else {
            // Insert new review
            $stmt = $pdo->prepare("
                INSERT INTO danhgia (
                    ID_SuKien, 
                    ID_KhachHang, 
                    DiemDanhGia, 
                    NoiDung, 
                    ThoiGianDanhGia,
                    DanhGiaDiaDiem, 
                    DanhGiaThietBi, 
                    DanhGiaNhanVien,
                    LoaiDanhGia
                ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, 'Sự kiện')
            ");
            
            // Get ID_KhachHang from the event validation query
            $stmt->execute([
                $eventId, 
                $event['ID_KhachHang'], // Use the correct ID_KhachHang from the event
                $overallRating, 
                $comment, 
                $locationRating, 
                $equipmentRating, 
                $staffRating
            ]);
            
            $message = 'Gửi đánh giá thành công!';
            $isUpdate = false;
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'isUpdate' => $isUpdate
        ]);
        
        error_log("Review submitted successfully: " . $message);
        
    } catch (Exception $e) {
        error_log("Error submitting review: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getReviews($pdo) {
    try {
        $eventId = $_GET['event_id'] ?? '';
        
        if (empty($eventId)) {
            throw new Exception('Thiếu thông tin sự kiện');
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                dg.*,
                kh.HoTen as TenKhachHang,
                kh.SoDienThoai
            FROM danhgia dg
            LEFT JOIN khachhanginfo kh ON dg.ID_KhachHang = kh.ID_KhachHang
            WHERE dg.ID_SuKien = ?
            ORDER BY dg.ThoiGianDanhGia DESC
        ");
        $stmt->execute([$eventId]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'reviews' => $reviews]);
        
    } catch (Exception $e) {
        error_log("Error getting reviews: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getUserReview($pdo, $user) {
    try {
        $eventId = $_GET['event_id'] ?? '';
        
        if (empty($eventId)) {
            throw new Exception('Thiếu thông tin sự kiện');
        }
        
        // Validate event exists, belongs to user, is completed and paid
        $stmt = $pdo->prepare("
            SELECT dl.ID_DatLich, dl.TrangThaiThanhToan, s.TrangThaiThucTe as TrangThaiSuKien
            FROM datlichsukien dl
            LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
            LEFT JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
            WHERE dl.ID_DatLich = ? AND k.ID_User = ?
        ");
        $stmt->execute([$eventId, $user['ID_User']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            throw new Exception('Sự kiện không tồn tại hoặc không thuộc về bạn');
        }
        
        if ($event['TrangThaiSuKien'] !== 'Hoàn thành') {
            throw new Exception('Chỉ có thể đánh giá sự kiện đã hoàn thành');
        }
        
        if ($event['TrangThaiThanhToan'] !== 'Đã thanh toán đủ') {
            throw new Exception('Chỉ có thể đánh giá sự kiện đã thanh toán thành công');
        }
        
        $stmt = $pdo->prepare("
            SELECT dg.* FROM danhgia dg
            LEFT JOIN datlichsukien dl ON dg.ID_SuKien = dl.ID_DatLich
            LEFT JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
            WHERE dg.ID_SuKien = ? AND k.ID_User = ?
        ");
        $stmt->execute([$eventId, $user['ID_User']]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'review' => $review]);
        
    } catch (Exception $e) {
        error_log("Error getting user review: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
