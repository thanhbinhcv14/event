<?php
/**
 * SePay Webhook Registration Guide
 * Hướng dẫn đăng ký webhook với SePay
 * 
 * Lưu ý: File này chỉ là hướng dẫn, việc đăng ký webhook phải thực hiện thủ công qua SePay Dashboard
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/sepay.php';

// Calculate webhook URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$webhookUrl = $protocol . '://' . $host . $scriptPath . '/hooks/sepay-payment.php';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký SePay Webhook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .code-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
        }
        .step-card {
            border-left: 4px solid #28a745;
            margin-bottom: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #28a745;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <h1 class="mb-4">
                    <i class="fas fa-link"></i> Hướng dẫn đăng ký SePay Webhook
                </h1>

                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Lưu ý quan trọng:</h5>
                    <p class="mb-0">
                        Việc đăng ký webhook phải được thực hiện <strong>thủ công</strong> qua SePay Dashboard. 
                        File này chỉ cung cấp thông tin cần thiết để bạn thực hiện.
                    </p>
                </div>

                <!-- Webhook URL -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-globe"></i> Webhook URL của bạn</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Copy URL sau và sử dụng khi đăng ký webhook:</strong></p>
                        <div class="code-block">
                            <?php echo htmlspecialchars($webhookUrl); ?>
                        </div>
                        <button class="btn btn-primary mt-2" onclick="copyWebhookUrl()">
                            <i class="fas fa-copy"></i> Copy URL
                        </button>
                    </div>
                </div>

                <!-- Steps -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-list-ol"></i> Các bước đăng ký</h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- Step 1 -->
                        <div class="card step-card mb-3">
                            <div class="card-body">
                                <h5><span class="badge bg-primary">Bước 1</span> Đăng nhập SePay Dashboard</h5>
                                <p>Truy cập <a href="https://my.sepay.vn" target="_blank">https://my.sepay.vn</a> và đăng nhập vào tài khoản của bạn.</p>
                                <div class="warning-box">
                                    <i class="fas fa-key"></i> <strong>Thông tin đăng nhập:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Email/Username: [Nhập thông tin của bạn]</li>
                                        <li>Password: [Nhập password của bạn]</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="card step-card mb-3">
                            <div class="card-body">
                                <h5><span class="badge bg-primary">Bước 2</span> Vào phần Webhook Settings</h5>
                                <p>Sau khi đăng nhập, tìm và click vào menu:</p>
                                <div class="code-block">
                                    <i class="fas fa-cog"></i> <strong>Cài đặt</strong> → <strong>Webhook</strong>
                                </div>
                                <p class="text-muted">Hoặc truy cập trực tiếp: <a href="https://my.sepay.vn/settings/webhook" target="_blank">https://my.sepay.vn/settings/webhook</a></p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="card step-card mb-3">
                            <div class="card-body">
                                <h5><span class="badge bg-primary">Bước 3</span> Thêm Webhook URL mới</h5>
                                <p>Trong trang Webhook Settings, nhấn nút "Thêm webhook mới" hoặc "Add Webhook"</p>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="card step-card mb-3">
                            <div class="card-body">
                                <h5><span class="badge bg-primary">Bước 4</span> Nhập Webhook URL</h5>
                                <p>Trong form đăng ký, nhập URL sau vào trường "Webhook URL":</p>
                                <div class="code-block" id="webhookUrlDisplay">
                                    <?php echo htmlspecialchars($webhookUrl); ?>
                                </div>
                                <button class="btn btn-sm btn-secondary mt-2" onclick="copyWebhookUrl()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="card step-card mb-3">
                            <div class="card-body">
                                <h5><span class="badge bg-primary">Bước 5</span> Cấu hình Authentication</h5>
                                <p>Trong form đăng ký webhook, cần cấu hình authentication:</p>
                                <div class="warning-box">
                                    <ul class="mb-0">
                                        <li><strong>Kiểu chứng thực:</strong> Api Key</li>
                                        <li><strong>API Key:</strong> Nhập webhook token của bạn</li>
                                    </ul>
                                </div>
                                <p class="mt-2">Webhook Token của bạn (copy và sử dụng làm API Key):</p>
                                <div class="code-block" id="webhookTokenDisplay">
                                    <?php echo defined('SEPAY_WEBHOOK_TOKEN') ? SEPAY_WEBHOOK_TOKEN : 'Chưa cấu hình'; ?>
                                </div>
                                <button class="btn btn-sm btn-secondary mt-2" onclick="copyWebhookToken()">
                                    <i class="fas fa-copy"></i> Copy Token
                                </button>
                            </div>
                        </div>

                        <!-- Step 6 -->
                        <div class="card step-card mb-3">
                            <div class="card-body">
                                <h5><span class="badge bg-primary">Bước 6</span> Chọn Events để nhận webhook</h5>
                                <p>Tích chọn các events sau:</p>
                                <div class="success-box">
                                    <ul class="mb-0">
                                        <li><i class="fas fa-check-circle text-success"></i> <strong>bank_transfer_received</strong> - Khi có tiền vào tài khoản</li>
                                        <li><i class="fas fa-check-circle text-success"></i> <strong>bank_transfer_confirmed</strong> - Khi giao dịch được xác nhận</li>
                                    </ul>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Cả 2 events này đều cần thiết để hệ thống tự động cập nhật trạng thái thanh toán
                                </small>
                            </div>
                        </div>

                        <!-- Step 7 -->
                        <div class="card step-card mb-3">
                            <div class="card-body">
                                <h5><span class="badge bg-primary">Bước 7</span> Xác nhận và lưu</h5>
                                <p>Kiểm tra lại thông tin và nhấn nút "Lưu" hoặc "Save" để hoàn tất đăng ký.</p>
                            </div>
                        </div>

                        <!-- Step 8 -->
                        <div class="card step-card mb-3">
                            <div class="card-body">
                                <h5><span class="badge bg-primary">Bước 8</span> Test Webhook (Tùy chọn)</h5>
                                <p>Sau khi đăng ký, bạn có thể test webhook bằng cách:</p>
                                <ol>
                                    <li>Truy cập trang <a href="test-sepay-webhook.php"><strong>Test Webhook</strong></a></li>
                                    <li>Nhập thông tin giao dịch test</li>
                                    <li>Gửi webhook test và kiểm tra kết quả</li>
                                </ol>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Configuration Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Thông tin cấu hình hiện tại</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th>Partner Code</th>
                                <td><code><?php echo defined('SEPAY_PARTNER_CODE') ? SEPAY_PARTNER_CODE : 'Chưa cấu hình'; ?></code></td>
                            </tr>
                            <tr>
                                <th>Environment</th>
                                <td><code><?php echo defined('SEPAY_ENVIRONMENT') ? SEPAY_ENVIRONMENT : 'Chưa cấu hình'; ?></code></td>
                            </tr>
                            <tr>
                                <th>Webhook URL</th>
                                <td><code><?php echo htmlspecialchars($webhookUrl); ?></code></td>
                            </tr>
                            <tr>
                                <th>Webhook Handler</th>
                                <td><code>hooks/sepay-payment.php</code></td>
                            </tr>
                            <tr>
                                <th>Webhook Token</th>
                                <td><code><?php echo defined('SEPAY_WEBHOOK_TOKEN') ? substr(SEPAY_WEBHOOK_TOKEN, 0, 20) . '...' : 'Chưa cấu hình'; ?></code></td>
                            </tr>
                            <tr>
                                <th>Match Pattern</th>
                                <td><code><?php echo defined('SEPAY_MATCH_PATTERN') ? SEPAY_MATCH_PATTERN : 'SK'; ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Troubleshooting -->
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h5><i class="fas fa-tools"></i> Xử lý sự cố</h5>
                    </div>
                    <div class="card-body">
                        <h6>Webhook không hoạt động?</h6>
                        <ul>
                            <li>Kiểm tra URL có đúng không (phải là HTTPS trong production)</li>
                            <li>Kiểm tra firewall/server có block request từ SePay không</li>
                            <li>Kiểm tra SSL certificate có hợp lệ không</li>
                            <li>Xem logs trong file error log của server</li>
                            <li>Test webhook bằng tool <a href="test-sepay-webhook.php">test-sepay-webhook.php</a></li>
                        </ul>

                        <h6 class="mt-3">Cần hỗ trợ?</h6>
                        <ul>
                            <li><strong>SePay Support:</strong> <a href="mailto:support@sepay.vn">support@sepay.vn</a></li>
                            <li><strong>Documentation:</strong> <a href="https://docs.sepay.vn" target="_blank">https://docs.sepay.vn</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body text-center">
                        <a href="test-sepay-webhook.php" class="btn btn-primary me-2">
                            <i class="fas fa-vial"></i> Test Webhook
                        </a>
                        <a href="SEPAY_INTEGRATION_GUIDE.md" class="btn btn-secondary" target="_blank">
                            <i class="fas fa-book"></i> Xem Guide đầy đủ
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyWebhookUrl() {
            const url = document.getElementById('webhookUrlDisplay').textContent.trim();
            navigator.clipboard.writeText(url).then(function() {
                alert('Đã copy URL vào clipboard!');
            }, function(err) {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = url;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Đã copy URL vào clipboard!');
            });
        }
        
        function copyWebhookToken() {
            const token = document.getElementById('webhookTokenDisplay').textContent.trim();
            if (token === 'Chưa cấu hình') {
                alert('Webhook token chưa được cấu hình!');
                return;
            }
            navigator.clipboard.writeText(token).then(function() {
                alert('Đã copy Webhook Token vào clipboard!');
            }, function(err) {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = token;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Đã copy Webhook Token vào clipboard!');
            });
        }
    </script>
</body>
</html>

