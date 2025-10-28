<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Check if event ID is provided
$eventId = $_GET['event_id'] ?? '';
if (empty($eventId)) {
    header('Location: events/my-events.php');
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
        WHERE dl.ID_DatLich = ? AND dl.ID_KhachHang = ?
    ");
    $stmt->execute([$eventId, $user['ID_User']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: events/my-events.php');
        exit;
    }
    
    // Check if event is completed
    if ($event['TrangThaiSuKien'] !== 'Hoàn thành') {
        header('Location: events/my-events.php');
        exit;
    }
    
    // Check if payment is completed
    if ($event['TrangThaiThanhToan'] !== 'Đã thanh toán đủ') {
        header('Location: events/my-events.php');
        exit;
    }
    
    // Check if user already reviewed this event
    $stmt = $pdo->prepare("
        SELECT * FROM danhgia 
        WHERE ID_SuKien = ? AND ID_KhachHang = ?
    ");
    $stmt->execute([$eventId, $user['ID_User']]);
    $existingReview = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error getting event details: " . $e->getMessage());
    header('Location: events/my-events.php');
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
        
        .review-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 2rem auto;
            max-width: 800px;
            overflow: hidden;
        }
        
        .review-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .review-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .event-info {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .review-content {
            padding: 2rem;
        }
        
        .rating-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .rating-title {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .star-rating {
            display: flex;
            gap: 5px;
            margin-bottom: 1rem;
        }
        
        .star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .star:hover,
        .star.active {
            color: #ffc107;
        }
        
        .rating-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .rating-label {
            min-width: 120px;
            font-weight: 500;
        }
        
        .comment-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .comment-title {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 12px 30px;
            font-weight: bold;
            transition: transform 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
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
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #155724;
        }
        
        .existing-review {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="review-container">
            <!-- Review Header -->
            <div class="review-header">
                <h1 class="review-title">
                    <i class="fas fa-star"></i>
                    Đánh giá sự kiện
                </h1>
                <div class="event-info">
                    <strong><?= htmlspecialchars($event['TenSuKien']) ?></strong><br>
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['TenDiaDiem']) ?>
                </div>
            </div>
            
            <!-- Review Content -->
            <div class="review-content">
                <?php if ($existingReview): ?>
                    <div class="existing-review">
                        <i class="fas fa-info-circle"></i>
                        <strong>Bạn đã đánh giá sự kiện này!</strong><br>
                        Điểm đánh giá: <?= $existingReview['DiemDanhGia'] ?>/5 sao<br>
                        Thời gian: <?= date('d/m/Y H:i', strtotime($existingReview['ThoiGianDanhGia'])) ?>
                    </div>
                <?php endif; ?>
                
                <form id="reviewForm">
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    
                    <!-- Overall Rating -->
                    <div class="rating-section">
                        <div class="rating-title">
                            <i class="fas fa-star"></i>
                            Đánh giá tổng thể
                        </div>
                        <div class="star-rating" data-rating="overall">
                            <i class="fas fa-star star" data-value="1"></i>
                            <i class="fas fa-star star" data-value="2"></i>
                            <i class="fas fa-star star" data-value="3"></i>
                            <i class="fas fa-star star" data-value="4"></i>
                            <i class="fas fa-star star" data-value="5"></i>
                        </div>
                        <input type="hidden" name="overall_rating" value="0">
                    </div>
                    
                    <!-- Detailed Ratings -->
                    <div class="rating-section">
                        <div class="rating-title">
                            <i class="fas fa-chart-bar"></i>
                            Đánh giá chi tiết
                        </div>
                        
                        <div class="rating-item">
                            <div class="rating-label">Địa điểm:</div>
                            <div class="star-rating" data-rating="location">
                                <i class="fas fa-star star" data-value="1"></i>
                                <i class="fas fa-star star" data-value="2"></i>
                                <i class="fas fa-star star" data-value="3"></i>
                                <i class="fas fa-star star" data-value="4"></i>
                                <i class="fas fa-star star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="location_rating" value="0">
                        </div>
                        
                        <div class="rating-item">
                            <div class="rating-label">Thiết bị:</div>
                            <div class="star-rating" data-rating="equipment">
                                <i class="fas fa-star star" data-value="1"></i>
                                <i class="fas fa-star star" data-value="2"></i>
                                <i class="fas fa-star star" data-value="3"></i>
                                <i class="fas fa-star star" data-value="4"></i>
                                <i class="fas fa-star star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="equipment_rating" value="0">
                        </div>
                        
                        <div class="rating-item">
                            <div class="rating-label">Nhân viên:</div>
                            <div class="star-rating" data-rating="staff">
                                <i class="fas fa-star star" data-value="1"></i>
                                <i class="fas fa-star star" data-value="2"></i>
                                <i class="fas fa-star star" data-value="3"></i>
                                <i class="fas fa-star star" data-value="4"></i>
                                <i class="fas fa-star star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="staff_rating" value="0">
                        </div>
                    </div>
                    
                    <!-- Comment Section -->
                    <div class="comment-section">
                        <div class="comment-title">
                            <i class="fas fa-comment"></i>
                            Bình luận
                        </div>
                        <textarea 
                            name="comment" 
                            class="form-control" 
                            rows="5" 
                            placeholder="Hãy chia sẻ trải nghiệm của bạn về sự kiện này..."
                            maxlength="1000"
                        ><?= htmlspecialchars($existingReview['NoiDung'] ?? '') ?></textarea>
                        <small class="text-muted">Tối đa 1000 ký tự</small>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn-submit me-3">
                            <i class="fas fa-paper-plane"></i>
                            <?= $existingReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' ?>
                        </button>
                        <a href="events/my-events.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        document.querySelectorAll('.star-rating').forEach(rating => {
            const stars = rating.querySelectorAll('.star');
            const hiddenInput = rating.parentElement.querySelector('input[type="hidden"]');
            
            stars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    const value = index + 1;
                    hiddenInput.value = value;
                    
                    // Update star display
                    stars.forEach((s, i) => {
                        s.classList.toggle('active', i < value);
                    });
                });
                
                star.addEventListener('mouseenter', () => {
                    stars.forEach((s, i) => {
                        s.classList.toggle('active', i <= index);
                    });
                });
            });
            
            rating.addEventListener('mouseleave', () => {
                const currentValue = parseInt(hiddenInput.value) || 0;
                stars.forEach((s, i) => {
                    s.classList.toggle('active', i < currentValue);
                });
            });
        });
        
        // Form submission
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const overallRating = formData.get('overall_rating');
            
            if (overallRating == 0) {
                alert('Vui lòng chọn điểm đánh giá tổng thể!');
                return;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
            submitBtn.disabled = true;
            
            fetch('src/controllers/review-controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const successDiv = document.createElement('div');
                    successDiv.className = 'success-message';
                    successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    
                    this.insertBefore(successDiv, this.firstChild);
                    
                    // Scroll to top
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    
                    // Reset form after 2 seconds
                    setTimeout(() => {
                        if (!data.isUpdate) {
                            this.reset();
                            document.querySelectorAll('.star').forEach(star => {
                                star.classList.remove('active');
                            });
                        }
                    }, 2000);
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi gửi đánh giá!');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Load existing review data
        <?php if ($existingReview): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Set overall rating
            const overallStars = document.querySelector('[data-rating="overall"]').querySelectorAll('.star');
            const overallValue = <?= $existingReview['DiemDanhGia'] ?>;
            document.querySelector('input[name="overall_rating"]').value = overallValue;
            overallStars.forEach((star, i) => {
                star.classList.toggle('active', i < overallValue);
            });
            
            // Set detailed ratings
            const locationValue = <?= $existingReview['DanhGiaDiaDiem'] ?? 0 ?>;
            if (locationValue > 0) {
                document.querySelector('input[name="location_rating"]').value = locationValue;
                const locationStars = document.querySelector('[data-rating="location"]').querySelectorAll('.star');
                locationStars.forEach((star, i) => {
                    star.classList.toggle('active', i < locationValue);
                });
            }
            
            const equipmentValue = <?= $existingReview['DanhGiaThietBi'] ?? 0 ?>;
            if (equipmentValue > 0) {
                document.querySelector('input[name="equipment_rating"]').value = equipmentValue;
                const equipmentStars = document.querySelector('[data-rating="equipment"]').querySelectorAll('.star');
                equipmentStars.forEach((star, i) => {
                    star.classList.toggle('active', i < equipmentValue);
                });
            }
            
            const staffValue = <?= $existingReview['DanhGiaNhanVien'] ?? 0 ?>;
            if (staffValue > 0) {
                document.querySelector('input[name="staff_rating"]').value = staffValue;
                const staffStars = document.querySelector('[data-rating="staff"]').querySelectorAll('.star');
                staffStars.forEach((star, i) => {
                    star.classList.toggle('active', i < staffValue);
                });
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
