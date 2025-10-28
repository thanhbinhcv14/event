<?php
require_once 'config/database.php';

// Check if event ID is provided
$eventId = $_GET['event_id'] ?? '';
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
            s.TrangThaiThucTe as TrangThaiSuKien
        FROM datlichsukien dl
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        LEFT JOIN sukien s ON dl.ID_DatLich = s.ID_DatLich
        WHERE dl.ID_DatLich = ?
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: index.php');
        exit;
    }
    
    // Get reviews for this event
    $stmt = $pdo->prepare("
        SELECT 
            dg.*,
            kh.HoTen as TenKhachHang
        FROM danhgia dg
        LEFT JOIN khachhanginfo kh ON dg.ID_KhachHang = kh.ID_KhachHang
        WHERE dg.ID_SuKien = ?
        ORDER BY dg.ThoiGianDanhGia DESC
    ");
    $stmt->execute([$eventId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate average rating
    $totalRating = 0;
    $ratingCount = count($reviews);
    foreach ($reviews as $review) {
        $totalRating += $review['DiemDanhGia'];
    }
    $averageRating = $ratingCount > 0 ? round($totalRating / $ratingCount, 1) : 0;
    
} catch (Exception $e) {
    error_log("Error getting event reviews: " . $e->getMessage());
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh giá sự kiện - <?= htmlspecialchars($event['TenSuKien']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .reviews-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 2rem auto;
            max-width: 1000px;
            overflow: hidden;
        }
        
        .reviews-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .reviews-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .event-info {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .reviews-content {
            padding: 2rem;
        }
        
        .rating-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .average-rating {
            font-size: 3rem;
            font-weight: bold;
            color: #ffc107;
            margin-bottom: 0.5rem;
        }
        
        .rating-stars {
            font-size: 1.5rem;
            color: #ffc107;
            margin-bottom: 1rem;
        }
        
        .rating-count {
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .review-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .reviewer-info {
            display: flex;
            align-items: center;
        }
        
        .reviewer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            margin-right: 1rem;
        }
        
        .reviewer-name {
            font-weight: bold;
            color: #333;
        }
        
        .review-rating {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .review-content {
            color: #555;
            line-height: 1.6;
        }
        
        .detailed-ratings {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        
        .detailed-rating {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
        }
        
        .detailed-rating i {
            color: #ffc107;
            margin-right: 0.3rem;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
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
        
        .no-reviews {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .no-reviews i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reviews-container">
            <!-- Reviews Header -->
            <div class="reviews-header">
                <h1 class="reviews-title">
                    <i class="fas fa-star"></i>
                    Đánh giá sự kiện
                </h1>
                <div class="event-info">
                    <strong><?= htmlspecialchars($event['TenSuKien']) ?></strong><br>
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['TenDiaDiem']) ?>
                </div>
            </div>
            
            <!-- Reviews Content -->
            <div class="reviews-content">
                <?php if ($ratingCount > 0): ?>
                    <!-- Rating Summary -->
                    <div class="rating-summary">
                        <div class="average-rating"><?= $averageRating ?></div>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $averageRating ? '' : 'text-muted' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-count">
                            Dựa trên <?= $ratingCount ?> đánh giá
                        </div>
                    </div>
                    
                    <!-- Reviews List -->
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <div class="reviewer-avatar">
                                            <?= strtoupper(substr($review['TenKhachHang'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="reviewer-name"><?= htmlspecialchars($review['TenKhachHang']) ?></div>
                                            <div class="review-date">
                                                <?= date('d/m/Y H:i', strtotime($review['ThoiGianDanhGia'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $review['DiemDanhGia'] ? '' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <?php if ($review['NoiDung']): ?>
                                    <div class="review-content">
                                        <?= nl2br(htmlspecialchars($review['NoiDung'])) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($review['DanhGiaDiaDiem'] || $review['DanhGiaThietBi'] || $review['DanhGiaNhanVien']): ?>
                                    <div class="detailed-ratings">
                                        <?php if ($review['DanhGiaDiaDiem']): ?>
                                            <div class="detailed-rating">
                                                <i class="fas fa-map-marker-alt"></i>
                                                Địa điểm: <?= $review['DanhGiaDiaDiem'] ?>/5
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($review['DanhGiaThietBi']): ?>
                                            <div class="detailed-rating">
                                                <i class="fas fa-cogs"></i>
                                                Thiết bị: <?= $review['DanhGiaThietBi'] ?>/5
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($review['DanhGiaNhanVien']): ?>
                                            <div class="detailed-rating">
                                                <i class="fas fa-user"></i>
                                                Nhân viên: <?= $review['DanhGiaNhanVien'] ?>/5
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- No Reviews -->
                    <div class="no-reviews">
                        <i class="fas fa-star"></i>
                        <h4>Chưa có đánh giá nào</h4>
                        <p>Sự kiện này chưa có đánh giá từ khách hàng.</p>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="event-detail.php?id=<?= $eventId ?>" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Quay lại chi tiết sự kiện
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
