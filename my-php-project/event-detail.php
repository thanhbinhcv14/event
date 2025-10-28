<?php
session_start();
require_once 'config/database.php';

// Check if event ID is provided
$eventId = $_GET['id'] ?? '';
if (empty($eventId)) {
    header('Location: index.php');
    exit;
}

// Get event details
try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT 
            dl.*,
            dd.TenDiaDiem,
            dd.DiaChi,
            dd.MoTa as DiaDiemMoTa,
            dd.SucChua,
            dd.GiaThueGio,
            dd.GiaThueNgay,
            dd.LoaiThue,
            dd.HinhAnh as DiaDiemHinhAnh,
            ls.TenLoai as TenLoaiSK,
            ls.MoTa as LoaiSKMoTa,
            kh.HoTen as TenKhachHang,
            kh.SoDienThoai,
            kh.DiaChi as KhachHangDiaChi,
            COALESCE(equipment_total.TongGiaThietBi, 0) as TongGiaThietBi,
            s.TrangThaiThucTe as TrangThaiSuKien
        FROM datlichsukien dl
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
        LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
        LEFT JOIN (
            SELECT ID_DatLich, SUM(DonGia * SoLuong) as TongGiaThietBi
            FROM chitietdatsukien
            WHERE ID_TB IS NOT NULL OR ID_Combo IS NOT NULL
            GROUP BY ID_DatLich
        ) equipment_total ON dl.ID_DatLich = equipment_total.ID_DatLich
        WHERE dl.ID_DatLich = ?
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: index.php');
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
    error_log("Error getting event details: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

// Check if user is logged in
$user = $_SESSION['user'] ?? null;
$isGuest = !$user;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['TenSuKien']) ?> - Chi tiết sự kiện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .event-detail-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 2rem auto;
            max-width: 1200px;
            overflow: hidden;
        }
        
        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .event-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.8rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .event-location {
            font-size: 1.1rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .event-image-section {
            position: relative;
            margin: 0;
            height: 100%;
        }
        
        .event-main-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0;
            box-shadow: none;
        }
        
        .event-status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.8rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            background: #28a745;
            color: white;
        }
        
        .event-content {
            padding: 0;
            display: flex;
            min-height: 500px;
        }
        
        .event-image-column {
            flex: 0 0 50%;
            position: relative;
        }
        
        .event-info-column {
            flex: 0 0 50%;
            padding: 2rem;
            background: #f8f9fa;
            overflow-y: auto;
            max-height: 600px;
        }
        
        .info-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .info-title {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
        }
        
        .info-item:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .info-item i {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            margin-right: 1rem;
            font-size: 1.1rem;
        }
        
        .info-content {
            flex-grow: 1;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.2rem;
            font-weight: 500;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }
        
        .reviews-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
        
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px 30px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px 30px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-reviews {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px 30px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .btn-reviews:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .restricted-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        
        .restricted-info i {
            color: #856404;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .restricted-info p {
            color: #856404;
            margin: 0;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            padding: 2rem;
            background: white;
        }
        
        @media (max-width: 768px) {
            .event-title {
                font-size: 2rem;
            }
            
            .event-header {
                padding: 2rem 1rem;
            }
            
            .event-content {
                flex-direction: column;
                min-height: auto;
            }
            
            .event-image-column {
                flex: 0 0 100%;
                height: 300px;
            }
            
            .event-info-column {
                flex: 0 0 100%;
                padding: 1rem;
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="event-detail-container">
            <!-- Event Header -->
            <div class="event-header">
                <h1 class="event-title"><?= htmlspecialchars($event['TenSuKien']) ?></h1>
                <div class="event-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?= htmlspecialchars($event['TenDiaDiem'] ?? 'Chưa xác định') ?></span>
                </div>
            </div>
            
            <!-- Event Content -->
            <div class="event-content">
                <!-- Left Column - Event Image -->
                <div class="event-image-column">
                    <div class="event-image-section">
                        <?php 
                        $locationImage = $event['DiaDiemHinhAnh'] ? 
                            "img/diadiem/{$event['DiaDiemHinhAnh']}" : 
                            'img/diadiem/default.jpg';
                        ?>
                        <img src="<?= $locationImage ?>" alt="<?= htmlspecialchars($event['TenDiaDiem']) ?>" 
                             class="event-main-image" onerror="this.src='img/diadiem/default.jpg'">
                        <span class="event-status-badge">
                            <?= htmlspecialchars($event['TrangThaiDuyet'] ?? 'Đã duyệt') ?>
                        </span>
                    </div>
                </div>
                
                <!-- Right Column - Event Information -->
                <div class="event-info-column">
                <?php if ($isGuest): ?>
                    <!-- Guest View - Limited Information -->
                    <div class="restricted-info">
                        <i class="fas fa-lock"></i>
                        <p>Đăng nhập để xem thêm thông tin chi tiết về sự kiện này</p>
                    </div>
                    
                    <div class="info-section">
                        <div class="info-title">
                            <i class="fas fa-info-circle"></i>
                            Thông tin cơ bản
                        </div>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="info-content">
                                <div class="info-label">Địa điểm</div>
                                <div class="info-value"><?= htmlspecialchars($event['TenDiaDiem'] ?? 'Chưa xác định') ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-map"></i>
                            <div class="info-content">
                                <div class="info-label">Địa chỉ</div>
                                <div class="info-value"><?= htmlspecialchars($event['DiaChi'] ?? 'Chưa xác định') ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reviews Section for Guest -->
                    <div class="reviews-section">
                        <div class="info-title">
                            <i class="fas fa-star"></i>
                            Đánh giá của khách hàng
                        </div>
                        
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
                    
                    <div class="action-buttons">
                        <a href="index.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                    
                <?php else: ?>
                    <!-- Logged in User View - Full Information -->
                    <div class="info-section">
                        <div class="info-title">
                            <i class="fas fa-calendar-alt"></i>
                            Thông tin sự kiện
                        </div>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="info-content">
                                <div class="info-label">Địa điểm</div>
                                <div class="info-value"><?= htmlspecialchars($event['TenDiaDiem'] ?? 'Chưa xác định') ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-map"></i>
                            <div class="info-content">
                                <div class="info-label">Địa chỉ</div>
                                <div class="info-value"><?= htmlspecialchars($event['DiaChi'] ?? 'Chưa xác định') ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-users"></i>
                            <div class="info-content">
                                <div class="info-label">Số người tham gia</div>
                                <div class="info-value"><?= $event['SoNguoiDuKien'] ?? 'Chưa xác định' ?> người</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <div class="info-content">
                                <div class="info-label">Ngân sách</div>
                                <div class="info-value text-success"><?= number_format($event['NganSach'], 0, ',', '.') ?> VNĐ</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($event['MoTa']): ?>
                    <div class="info-section">
                        <div class="info-title">
                            <i class="fas fa-align-left"></i>
                            Mô tả sự kiện
                        </div>
                        <p><?= htmlspecialchars($event['MoTa']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($event['GhiChu']): ?>
                    <div class="info-section">
                        <div class="info-title">
                            <i class="fas fa-sticky-note"></i>
                            Ghi chú
                        </div>
                        <p><?= htmlspecialchars($event['GhiChu']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-section">
                        <div class="info-title">
                            <i class="fas fa-user"></i>
                            Thông tin liên hệ
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <div class="info-content">
                                <div class="info-label">Người tổ chức</div>
                                <div class="info-value"><?= htmlspecialchars($event['TenKhachHang'] ?? 'Chưa xác định') ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div class="info-content">
                                <div class="info-label">Số điện thoại</div>
                                <div class="info-value"><?= htmlspecialchars($event['SoDienThoai'] ?? 'Chưa xác định') ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipment Section -->
                    <?php if (!empty($equipment)): ?>
                    <div class="equipment-section">
                        <div class="info-title">
                            <i class="fas fa-cogs"></i>
                            Thiết bị đã đặt
                        </div>
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
                                <div class="equipment-price"><?= number_format($item['DonGia'], 0, ',', '.') ?> VNĐ</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Reviews Section -->
                    <div class="reviews-section">
                        <div class="info-title">
                            <i class="fas fa-star"></i>
                            Đánh giá của khách hàng
                        </div>
                        
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
                    
                    <div class="action-buttons">
                        <a href="index.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function scrollToReviews() {
            document.querySelector('.reviews-section').scrollIntoView({
                behavior: 'smooth'
            });
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
