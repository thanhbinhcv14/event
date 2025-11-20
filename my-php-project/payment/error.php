<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi thanh toán - Event Management</title>
    <link rel="icon" href="../img/logo/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .error-icon {
            font-size: 80px;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .btn-custom {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
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
    <div class="error-card">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 class="mb-3">Đã xảy ra lỗi</h2>
        <p class="text-muted mb-4">Có lỗi xảy ra trong quá trình xử lý thanh toán.</p>
        
        <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-danger">
            <strong>Chi tiết lỗi:</strong> <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="/events/my-events.php" class="btn-custom">
                <i class="fas fa-calendar-alt me-2"></i>Xem sự kiện của tôi
            </a>
            <a href="/" class="btn-custom">
                <i class="fas fa-home me-2"></i>Về trang chủ
            </a>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                Vui lòng thử lại sau hoặc liên hệ hỗ trợ khách hàng nếu vấn đề vẫn tiếp tục.
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
