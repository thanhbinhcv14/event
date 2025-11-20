<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$eventId = $_GET['id'] ?? '';

if (empty($eventId)) {
    header('Location: events/my-events.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get event details
    $stmt = $pdo->prepare("
        SELECT dl.*, d.TenDiaDiem, d.DiaChi, d.SucChua, d.GiaThueGio, d.GiaThueNgay, d.LoaiThue, d.HinhAnh as DiaDiemHinhAnh,
               ls.TenLoai, ls.GiaCoBan, ls.MoTa as LoaiMoTa,
               k.HoTen, k.SoDienThoai,
               COALESCE(equipment_total.TongGiaThietBi, 0) as TongGiaThietBi,
               s.TrangThaiThucTe as TrangThaiSuKien
        FROM datlichsukien dl
        INNER JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
        LEFT JOIN diadiem d ON dl.ID_DD = d.ID_DD
        LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
        LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
        LEFT JOIN (
            SELECT ID_DatLich, SUM(DonGia * SoLuong) as TongGiaThietBi
            FROM chitietdatsukien
            WHERE ID_TB IS NOT NULL OR ID_Combo IS NOT NULL
            GROUP BY ID_DatLich
        ) equipment_total ON dl.ID_DatLich = equipment_total.ID_DatLich
        WHERE dl.ID_DatLich = ? AND k.ID_User = ?
    ");
    $stmt->execute([$eventId, $user['ID_User']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: events/my-events.php');
        exit;
    }
    
    // Get event reviews
    $stmt = $pdo->prepare("
        SELECT dg.*, k.HoTen as TenKhachHang
        FROM danhgia dg
        LEFT JOIN datlichsukien dl ON dg.ID_SuKien = dl.ID_DatLich
        LEFT JOIN khachhanginfo k ON dl.ID_KhachHang = k.ID_KhachHang
        WHERE dg.ID_SuKien = ?
        ORDER BY dg.ThoiGianDanhGia DESC
    ");
    $stmt->execute([$eventId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get event equipment details
    $stmt = $pdo->prepare("
        SELECT ctds.*, tb.TenThietBi, tb.HinhAnh as ThietBiHinhAnh, tb.LoaiThietBi,
               cb.TenCombo
        FROM chitietdatsukien ctds
        LEFT JOIN thietbi tb ON ctds.ID_TB = tb.ID_TB
        LEFT JOIN combo cb ON ctds.ID_Combo = cb.ID_Combo
        WHERE ctds.ID_DatLich = ?
    ");
    $stmt->execute([$eventId]);
    $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Event Details Page Error: " . $e->getMessage());
    header('Location: events/my-events.php');
    exit;
}

// Format date and time
function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

// Format price
function formatPrice($price) {
    return number_format($price, 0, ',', '.');
}

// Get status badge class
function getStatusClass($status) {
    switch ($status) {
        case 'Đã duyệt': return 'bg-success';
        case 'Chờ duyệt': return 'bg-warning';
        case 'Từ chối': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// Get payment badge class
function getPaymentClass($status) {
    switch ($status) {
        case 'Đã thanh toán đủ': return 'bg-success';
        case 'Chưa thanh toán': return 'bg-danger';
        case 'Thanh toán một phần': return 'bg-warning';
        default: return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['TenSuKien']) ?> - Chi tiết sự kiện</title>
    <link rel="icon" href="img/logo/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            overflow: hidden;
        }
        
        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .event-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .event-location {
            font-size: 1.2rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .event-image-section {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .event-main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .event-status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .info-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .info-item i {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            margin-right: 1.5rem;
            font-size: 1.3rem;
        }
        
        .info-content {
            flex-grow: 1;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }
        
        .reviews-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .review-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
            transition: all 0.3s ease;
        }
        
        .review-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .review-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .review-stars {
            color: #ffc107;
            font-size: 1.3rem;
        }
        
        .review-author {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }
        
        .review-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .review-content {
            color: #555;
            line-height: 1.6;
            font-style: italic;
            font-size: 1rem;
        }
        
        .equipment-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .equipment-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .equipment-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        .equipment-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }
        
        .equipment-info {
            flex-grow: 1;
        }
        
        .equipment-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .equipment-details {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .equipment-price {
            font-weight: 600;
            color: #28a745;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn-action {
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
            min-width: 150px;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .btn-register {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-reviews {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .empty-reviews {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }
        
        .empty-reviews i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        @media (max-width: 768px) {
            .event-title {
                font-size: 2rem;
            }
            
            .event-header {
                padding: 2rem 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-action {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container">
            <!-- Event Header -->
            <div class="event-header">
                <h1 class="event-title"><?= htmlspecialchars($event['TenSuKien']) ?></h1>
                <div class="event-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?= htmlspecialchars($event['TenDiaDiem'] ?? 'Chưa xác định') ?></span>
                </div>
            </div>
            
            <div class="container-fluid p-4">
                <!-- Event Image -->
                <div class="event-image-section">
                    <?php 
                    $locationImage = $event['DiaDiemHinhAnh'] ? 
                        "img/diadiem/{$event['DiaDiemHinhAnh']}" : 
                        'img/diadiem/default.jpg';
                    ?>
                    <img src="<?= $locationImage ?>" alt="<?= htmlspecialchars($event['TenDiaDiem']) ?>" 
                         class="event-main-image" onerror="this.src='img/diadiem/default.jpg'">
                    <span class="event-status-badge <?= getStatusClass($event['TrangThaiDuyet']) ?>">
                        <?= htmlspecialchars($event['TrangThaiDuyet']) ?>
                    </span>
                </div>
                
                <!-- Event Information -->
                <div class="info-section">
                    <h3 class="mb-4"><i class="fas fa-calendar-alt text-primary"></i> Thông tin sự kiện</h3>
                    
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <div class="info-content">
                            <div class="info-label">Ngày tổ chức</div>
                            <div class="info-value"><?= formatDateTime($event['NgayBatDau']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <div class="info-content">
                            <div class="info-label">Thời gian</div>
                            <div class="info-value">
                                <?= date('H:i', strtotime($event['NgayBatDau'])) ?> - 
                                <?= date('H:i', strtotime($event['NgayKetThuc'])) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="info-content">
                            <div class="info-label">Địa điểm</div>
                            <div class="info-value"><?= htmlspecialchars($event['TenDiaDiem'] ?? 'Chưa xác định') ?></div>
                        </div>
                    </div>
                    
                    <?php if ($event['DiaChi']): ?>
                    <div class="info-item">
                        <i class="fas fa-building"></i>
                        <div class="info-content">
                            <div class="info-label">Địa chỉ</div>
                            <div class="info-value"><?= htmlspecialchars($event['DiaChi']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <div class="info-content">
                            <div class="info-label">Số người tham gia</div>
                            <div class="info-value"><?= $event['SoNguoiDuKien'] ?? 'Chưa xác định' ?> người</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-wallet"></i>
                        <div class="info-content">
                            <div class="info-label">Ngân sách</div>
                            <div class="info-value text-success"><?= formatPrice($event['NganSach']) ?> VNĐ</div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="info-section">
                    <h3 class="mb-4"><i class="fas fa-user text-primary"></i> Thông tin liên hệ</h3>
                    
                    <div class="info-item">
                        <i class="fas fa-user"></i>
                        <div class="info-content">
                            <div class="info-label">Người tổ chức</div>
                            <div class="info-value"><?= htmlspecialchars($event['HoTen']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div class="info-content">
                            <div class="info-label">Số điện thoại</div>
                            <div class="info-value"><?= htmlspecialchars($event['SoDienThoai']) ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Equipment Section -->
                <?php if (!empty($equipment)): ?>
                <div class="equipment-section">
                    <h4 class="mb-3"><i class="fas fa-cogs text-primary"></i> Thiết bị đã đặt</h4>
                    <?php foreach ($equipment as $item): ?>
                        <?php 
                        $itemName = $item['TenThietBi'] ?? $item['TenCombo'] ?? 'Thiết bị';
                        $itemImage = $item['ThietBiHinhAnh'];
                        $imagePath = $itemImage ? "img/thietbi/{$itemImage}" : 'img/thietbi/default.jpg';
                        $itemType = $item['TenThietBi'] ? 'Thiết bị' : 'Combo';
                        ?>
                        <div class="equipment-item">
                            <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($itemName) ?>" 
                                 class="equipment-image" onerror="this.src='img/thietbi/default.jpg'">
                            <div class="equipment-info">
                                <div class="equipment-name"><?= htmlspecialchars($itemName) ?></div>
                                <div class="equipment-details">
                                    <?= $itemType ?> • Số lượng: <?= $item['SoLuong'] ?>
                                    <?php if ($item['LoaiThietBi']): ?>
                                        • Loại: <?= htmlspecialchars($item['LoaiThietBi']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="equipment-price"><?= formatPrice($item['DonGia']) ?> VNĐ</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Reviews Section -->
                <div class="reviews-section">
                    <h4 class="mb-3"><i class="fas fa-star text-primary"></i> Đánh giá của khách hàng</h4>
                    
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <?php 
                            $reviewTime = $review['ThoiGianDanhGia'] ? 
                                date('d/m/Y H:i', strtotime($review['ThoiGianDanhGia'])) : 
                                'Vừa đánh giá';
                            $stars = str_repeat('⭐', $review['DiemDanhGia']);
                            ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-rating">
                                        <span class="review-stars"><?= $stars ?></span>
                                        <span class="review-author"><?= htmlspecialchars($review['TenKhachHang'] ?? 'Khách hàng') ?></span>
                                    </div>
                                    <div class="review-time"><?= $reviewTime ?></div>
                                </div>
                                <?php if ($review['NoiDung']): ?>
                                <div class="review-content">"<?= htmlspecialchars($review['NoiDung']) ?>"</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-reviews">
                            <i class="fas fa-star"></i>
                            <h5>Chưa có đánh giá nào</h5>
                            <p>Sự kiện này chưa có đánh giá từ khách hàng.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if ($event['TrangThaiDuyet'] === 'Đã duyệt'): ?>
                    <button class="btn btn-action btn-register" onclick="registerEvent()">
                        <i class="fas fa-calendar-plus"></i> Đăng ký tham gia
                    </button>
                    <?php endif; ?>
                    
                    <?php if (!empty($reviews)): ?>
                    <button class="btn btn-action btn-reviews" onclick="scrollToReviews()">
                        <i class="fas fa-star"></i> Xem đánh giá
                    </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-action btn-back" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function registerEvent() {
            alert('Chức năng đăng ký tham gia sẽ được phát triển trong tương lai!');
        }
        
        function scrollToReviews() {
            document.querySelector('.reviews-section').scrollIntoView({
                behavior: 'smooth'
            });
        }
        
        function goBack() {
            window.history.back();
        }
        
        // Add smooth scrolling for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in animation for sections
            const sections = document.querySelectorAll('.info-section, .reviews-section, .equipment-section');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });
            
            sections.forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'all 0.6s ease';
                observer.observe(section);
            });
        });
    </script>
</body>
</html>
