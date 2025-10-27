<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán thành công - Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin: 20px 0;
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2 class="mb-3">Thanh toán thành công!</h2>
        <p class="text-muted mb-4">Cảm ơn bạn đã thanh toán. Giao dịch đã được xử lý thành công.</p>
        
        <?php if (isset($_GET['amount'])): ?>
        <div class="amount">
            <?php echo number_format($_GET['amount']) . ' VNĐ'; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['order_id'])): ?>
        <p class="text-muted">
            <strong>Mã giao dịch:</strong> <?php echo htmlspecialchars($_GET['order_id']); ?>
        </p>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="/event/my-php-project/events/my-events.php" class="btn-custom">
                <i class="fas fa-calendar-alt me-2"></i>Xem sự kiện của tôi
            </a>
            <a href="/event/my-php-project/" class="btn-custom">
                <i class="fas fa-home me-2"></i>Về trang chủ
            </a>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                Bạn sẽ nhận được email xác nhận trong vài phút tới.
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
