<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán thất bại - Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .failure-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .failure-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .btn-custom {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            color: white;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            transition: transform 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            color: white;
        }
        .btn-retry {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
    </style>
</head>
<body>
    <div class="failure-card">
        <div class="failure-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <h2 class="mb-3">Thanh toán thất bại</h2>
        <p class="text-muted mb-4">Rất tiếc, giao dịch của bạn không thể hoàn thành.</p>
        
        <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-warning">
            <strong>Lý do:</strong> <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['order_id'])): ?>
        <p class="text-muted">
            <strong>Mã giao dịch:</strong> <?php echo htmlspecialchars($_GET['order_id']); ?>
        </p>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="javascript:history.back()" class="btn-custom btn-retry">
                <i class="fas fa-redo me-2"></i>Thử lại
            </a>
            <a href="/event/my-php-project/events/my-events.php" class="btn-custom">
                <i class="fas fa-calendar-alt me-2"></i>Xem sự kiện của tôi
            </a>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                Nếu vấn đề vẫn tiếp tục, vui lòng liên hệ hỗ trợ khách hàng.
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
