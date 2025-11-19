<?php
/**
 * Quick Webhook Configuration Checker
 * Kiểm tra nhanh cấu hình webhook SePay
 */

require_once __DIR__ . '/../config/sepay.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra cấu hình Webhook SePay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
        }
        .check-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #ccc;
        }
        .check-item.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .check-item.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .check-item.danger {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .check-item.info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
        }
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">
            <i class="fas fa-check-circle"></i> Kiểm tra cấu hình Webhook SePay
        </h1>
        
        <div class="alert alert-warning">
            <strong><i class="fas fa-exclamation-triangle"></i> Lưu ý:</strong> 
            Bạn cần kiểm tra các điểm sau trong <strong>SePay Dashboard</strong> (https://my.sepay.vn)
        </div>
        
        <!-- Checklist -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list-check"></i> Checklist kiểm tra</h5>
            </div>
            <div class="card-body">
                
                <!-- Item 1: IPN URL -->
                <div class="check-item danger">
                    <h6><i class="fas fa-link"></i> 1. IPN URL (QUAN TRỌNG NHẤT)</h6>
                    <p><strong>Trong SePay Dashboard → Tab "IPN" → Trường "IPN URL *"</strong></p>
                    <p><strong>Phải là:</strong></p>
                    <div class="code-block">https://sukien.info.vn/hooks/sepay-payment.php</div>
                    <p class="mt-2"><strong>❌ SAI nếu là:</strong></p>
                    <ul>
                        <li><code>https://sukien.info.vn/</code> (thiếu đường dẫn file)</li>
                        <li><code>https://sukien.info.vn/my-php-project/hooks/sepay-payment.php</code> (có thêm my-php-project)</li>
                        <li>Bất kỳ URL nào khác</li>
                    </ul>
                    <p class="mt-2"><strong>✅ Cách sửa:</strong></p>
                    <ol>
                        <li>Vào SePay Dashboard → Tab "IPN"</li>
                        <li>Sửa IPN URL thành: <code>https://sukien.info.vn/hooks/sepay-payment.php</code></li>
                        <li>Nhấn <strong>"Cập nhật"</strong></li>
                        <li>Đợi 1-2 phút để SePay cập nhật</li>
                    </ol>
                </div>
                
                <!-- Item 2: Trạng thái IPN -->
                <div class="check-item danger">
                    <h6><i class="fas fa-power-off"></i> 2. Trạng thái IPN</h6>
                    <p><strong>Trong SePay Dashboard → Tab "IPN" → "Kích hoạt IPN"</strong></p>
                    <p><strong>Phải bật:</strong> ✅ <strong>ON</strong> (màu xanh)</p>
                    <p><strong>❌ Nếu tắt:</strong> Webhook sẽ không được gửi</p>
                    <p class="mt-2"><strong>✅ Cách sửa:</strong> Bật lại và nhấn "Cập nhật"</p>
                </div>
                
                <!-- Item 3: Content Type -->
                <div class="check-item warning">
                    <h6><i class="fas fa-file-code"></i> 3. Content Type</h6>
                    <p><strong>Trong SePay Dashboard → Tab "IPN" → "Content Type"</strong></p>
                    <p><strong>Phải là:</strong> <code>application/json</code></p>
                    <p><strong>❌ Nếu là:</strong> <code>application/x-www-form-urlencoded</code> → Cần sửa lại</p>
                </div>
                
                <!-- Item 4: Auth Type và Token -->
                <div class="check-item warning">
                    <h6><i class="fas fa-key"></i> 4. Auth Type và Token</h6>
                    <p><strong>Trong SePay Dashboard → Tab "IPN"</strong></p>
                    <p><strong>Auth Type:</strong> Có thể là "Secret Key" hoặc "Không có"</p>
                    <p><strong>Secret Key (nếu có):</strong> Có thể là <code>Thanhbinh1@</code> hoặc API Token</p>
                    <p class="mt-2"><strong>⚠️ Lưu ý:</strong></p>
                    <ul>
                        <li>Code hiện tại xác thực bằng <strong>API Token</strong> từ header <code>Authorization: Apikey {TOKEN}</code></li>
                        <li>Trong code: <code><?php echo substr(SEPAY_WEBHOOK_TOKEN, 0, 20); ?>...</code></li>
                        <li>Nếu SePay gửi Secret Key thay vì API Token, cần cập nhật <code>SEPAY_WEBHOOK_TOKEN</code> trong <code>config/sepay.php</code></li>
                    </ul>
                </div>
                
                <!-- Item 5: Cấu trúc mã thanh toán -->
                <div class="check-item success">
                    <h6><i class="fas fa-barcode"></i> 5. Cấu trúc mã thanh toán</h6>
                    <p><strong>Trong SePay Dashboard → Tab "Phương thức thanh toán"</strong></p>
                    <p><strong>Phải là:</strong></p>
                    <ul>
                        <li><strong>Prefix:</strong> <code>SEPAY</code></li>
                        <li><strong>Suffix:</strong> Số nguyên, từ 3 đến 10 ký tự</li>
                    </ul>
                    <p><strong>✅ Ví dụ:</strong> <code>SEPAY2220</code> (eventId=22, paymentId=20)</p>
                    <p><strong>✅ Nội dung của bạn:</strong> <code>SEPAY2220</code> → Đúng format!</p>
                </div>
                
                <!-- Item 6: Test endpoint -->
                <div class="check-item info">
                    <h6><i class="fas fa-vial"></i> 6. Test Webhook Endpoint</h6>
                    <p><strong>Test URL:</strong></p>
                    <p><a href="sepay-payment.php?test=1" target="_blank" class="btn btn-sm btn-primary">
                        <i class="fas fa-external-link-alt"></i> Test Endpoint
                    </a></p>
                    <p><strong>Kết quả mong đợi:</strong> JSON response với <code>"success": true</code></p>
                    <p><strong>❌ Nếu lỗi 404:</strong> File không tồn tại → Kiểm tra cấu trúc thư mục</p>
                </div>
                
                <!-- Item 7: Kiểm tra logs -->
                <div class="check-item info">
                    <h6><i class="fas fa-file-alt"></i> 7. Kiểm tra Logs</h6>
                    <p><strong>Xem logs:</strong></p>
                    <p><a href="debug-webhook.php" target="_blank" class="btn btn-sm btn-info">
                        <i class="fas fa-bug"></i> Xem Debug Webhook
                    </a></p>
                    <p><strong>Kiểm tra:</strong></p>
                    <ul>
                        <li>Số <strong>POST requests</strong> có tăng lên không?</li>
                        <li>Có <strong>"Token verified successfully"</strong> không?</li>
                        <li>Raw logs có request nào từ SePay không?</li>
                    </ul>
                </div>
                
                <!-- Item 8: Tài khoản ngân hàng -->
                <div class="check-item success">
                    <h6><i class="fas fa-university"></i> 8. Tài khoản ngân hàng</h6>
                    <p><strong>Trong SePay Dashboard → Tab "Phương thức thanh toán"</strong></p>
                    <p><strong>Phải là:</strong></p>
                    <ul>
                        <li><strong>Ngân hàng:</strong> VietinBank</li>
                        <li><strong>Số tài khoản:</strong> 100872918542</li>
                        <li><strong>Chủ tài khoản:</strong> BUI THANH BINH</li>
                        <li><strong>Trạng thái:</strong> Mặc định (có dấu sao ⭐)</li>
                    </ul>
                </div>
                
            </div>
        </div>
        
        <!-- Thông tin cấu hình hiện tại -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Cấu hình hiện tại trong code</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th>Mã đơn vị</th>
                        <td><code><?php echo SEPAY_PARTNER_CODE; ?></code></td>
                    </tr>
                    <tr>
                        <th>Webhook URL</th>
                        <td><code><?php echo SEPAY_CALLBACK_URL; ?></code></td>
                    </tr>
                    <tr>
                        <th>Webhook Token (20 ký tự đầu)</th>
                        <td><code><?php echo substr(SEPAY_WEBHOOK_TOKEN, 0, 20); ?>...</code></td>
                    </tr>
                    <tr>
                        <th>Match Pattern</th>
                        <td><code><?php echo SEPAY_MATCH_PATTERN; ?></code></td>
                    </tr>
                    <tr>
                        <th>Environment</th>
                        <td><code><?php echo SEPAY_ENVIRONMENT; ?></code></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Hướng dẫn debug -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-question-circle"></i> Nếu vẫn không nhận được webhook</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Đợi 2-3 phút</strong> sau khi sửa cấu hình trong SePay Dashboard</li>
                    <li><strong>Test lại</strong> bằng cách tạo payment mới và chuyển khoản</li>
                    <li><strong>Kiểm tra logs</strong> tại <a href="debug-webhook.php" target="_blank">debug-webhook.php</a></li>
                    <li><strong>Liên hệ SePay Support</strong> nếu vẫn không nhận được webhook:
                        <ul>
                            <li>Mã đơn vị: <code><?php echo SEPAY_PARTNER_CODE; ?></code></li>
                            <li>IPN URL: <code><?php echo SEPAY_CALLBACK_URL; ?></code></li>
                            <li>Thời gian giao dịch: [Thời gian bạn chuyển khoản]</li>
                            <li>Nội dung chuyển khoản: <code>SEPAY2220</code></li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="text-center">
            <a href="debug-webhook.php" class="btn btn-info">
                <i class="fas fa-bug"></i> Xem Debug Webhook
            </a>
            <a href="sepay-payment.php?test=1" class="btn btn-success" target="_blank">
                <i class="fas fa-vial"></i> Test Endpoint
            </a>
            <a href="../docs/WEBHOOK_TROUBLESHOOTING.md" class="btn btn-primary" target="_blank">
                <i class="fas fa-book"></i> Xem hướng dẫn chi tiết
            </a>
        </div>
        
    </div>
</body>
</html>

/**
 * Quick Webhook Configuration Checker
 * Kiểm tra nhanh cấu hình webhook SePay
 */

require_once __DIR__ . '/../config/sepay.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra cấu hình Webhook SePay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
        }
        .check-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #ccc;
        }
        .check-item.success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .check-item.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .check-item.danger {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .check-item.info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
        }
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">
            <i class="fas fa-check-circle"></i> Kiểm tra cấu hình Webhook SePay
        </h1>
        
        <div class="alert alert-warning">
            <strong><i class="fas fa-exclamation-triangle"></i> Lưu ý:</strong> 
            Bạn cần kiểm tra các điểm sau trong <strong>SePay Dashboard</strong> (https://my.sepay.vn)
        </div>
        
        <!-- Checklist -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list-check"></i> Checklist kiểm tra</h5>
            </div>
            <div class="card-body">
                
                <!-- Item 1: IPN URL -->
                <div class="check-item danger">
                    <h6><i class="fas fa-link"></i> 1. IPN URL (QUAN TRỌNG NHẤT)</h6>
                    <p><strong>Trong SePay Dashboard → Tab "IPN" → Trường "IPN URL *"</strong></p>
                    <p><strong>Phải là:</strong></p>
                    <div class="code-block">https://sukien.info.vn/hooks/sepay-payment.php</div>
                    <p class="mt-2"><strong>❌ SAI nếu là:</strong></p>
                    <ul>
                        <li><code>https://sukien.info.vn/</code> (thiếu đường dẫn file)</li>
                        <li><code>https://sukien.info.vn/my-php-project/hooks/sepay-payment.php</code> (có thêm my-php-project)</li>
                        <li>Bất kỳ URL nào khác</li>
                    </ul>
                    <p class="mt-2"><strong>✅ Cách sửa:</strong></p>
                    <ol>
                        <li>Vào SePay Dashboard → Tab "IPN"</li>
                        <li>Sửa IPN URL thành: <code>https://sukien.info.vn/hooks/sepay-payment.php</code></li>
                        <li>Nhấn <strong>"Cập nhật"</strong></li>
                        <li>Đợi 1-2 phút để SePay cập nhật</li>
                    </ol>
                </div>
                
                <!-- Item 2: Trạng thái IPN -->
                <div class="check-item danger">
                    <h6><i class="fas fa-power-off"></i> 2. Trạng thái IPN</h6>
                    <p><strong>Trong SePay Dashboard → Tab "IPN" → "Kích hoạt IPN"</strong></p>
                    <p><strong>Phải bật:</strong> ✅ <strong>ON</strong> (màu xanh)</p>
                    <p><strong>❌ Nếu tắt:</strong> Webhook sẽ không được gửi</p>
                    <p class="mt-2"><strong>✅ Cách sửa:</strong> Bật lại và nhấn "Cập nhật"</p>
                </div>
                
                <!-- Item 3: Content Type -->
                <div class="check-item warning">
                    <h6><i class="fas fa-file-code"></i> 3. Content Type</h6>
                    <p><strong>Trong SePay Dashboard → Tab "IPN" → "Content Type"</strong></p>
                    <p><strong>Phải là:</strong> <code>application/json</code></p>
                    <p><strong>❌ Nếu là:</strong> <code>application/x-www-form-urlencoded</code> → Cần sửa lại</p>
                </div>
                
                <!-- Item 4: Auth Type và Token -->
                <div class="check-item warning">
                    <h6><i class="fas fa-key"></i> 4. Auth Type và Token</h6>
                    <p><strong>Trong SePay Dashboard → Tab "IPN"</strong></p>
                    <p><strong>Auth Type:</strong> Có thể là "Secret Key" hoặc "Không có"</p>
                    <p><strong>Secret Key (nếu có):</strong> Có thể là <code>Thanhbinh1@</code> hoặc API Token</p>
                    <p class="mt-2"><strong>⚠️ Lưu ý:</strong></p>
                    <ul>
                        <li>Code hiện tại xác thực bằng <strong>API Token</strong> từ header <code>Authorization: Apikey {TOKEN}</code></li>
                        <li>Trong code: <code><?php echo substr(SEPAY_WEBHOOK_TOKEN, 0, 20); ?>...</code></li>
                        <li>Nếu SePay gửi Secret Key thay vì API Token, cần cập nhật <code>SEPAY_WEBHOOK_TOKEN</code> trong <code>config/sepay.php</code></li>
                    </ul>
                </div>
                
                <!-- Item 5: Cấu trúc mã thanh toán -->
                <div class="check-item success">
                    <h6><i class="fas fa-barcode"></i> 5. Cấu trúc mã thanh toán</h6>
                    <p><strong>Trong SePay Dashboard → Tab "Phương thức thanh toán"</strong></p>
                    <p><strong>Phải là:</strong></p>
                    <ul>
                        <li><strong>Prefix:</strong> <code>SEPAY</code></li>
                        <li><strong>Suffix:</strong> Số nguyên, từ 3 đến 10 ký tự</li>
                    </ul>
                    <p><strong>✅ Ví dụ:</strong> <code>SEPAY2220</code> (eventId=22, paymentId=20)</p>
                    <p><strong>✅ Nội dung của bạn:</strong> <code>SEPAY2220</code> → Đúng format!</p>
                </div>
                
                <!-- Item 6: Test endpoint -->
                <div class="check-item info">
                    <h6><i class="fas fa-vial"></i> 6. Test Webhook Endpoint</h6>
                    <p><strong>Test URL:</strong></p>
                    <p><a href="sepay-payment.php?test=1" target="_blank" class="btn btn-sm btn-primary">
                        <i class="fas fa-external-link-alt"></i> Test Endpoint
                    </a></p>
                    <p><strong>Kết quả mong đợi:</strong> JSON response với <code>"success": true</code></p>
                    <p><strong>❌ Nếu lỗi 404:</strong> File không tồn tại → Kiểm tra cấu trúc thư mục</p>
                </div>
                
                <!-- Item 7: Kiểm tra logs -->
                <div class="check-item info">
                    <h6><i class="fas fa-file-alt"></i> 7. Kiểm tra Logs</h6>
                    <p><strong>Xem logs:</strong></p>
                    <p><a href="debug-webhook.php" target="_blank" class="btn btn-sm btn-info">
                        <i class="fas fa-bug"></i> Xem Debug Webhook
                    </a></p>
                    <p><strong>Kiểm tra:</strong></p>
                    <ul>
                        <li>Số <strong>POST requests</strong> có tăng lên không?</li>
                        <li>Có <strong>"Token verified successfully"</strong> không?</li>
                        <li>Raw logs có request nào từ SePay không?</li>
                    </ul>
                </div>
                
                <!-- Item 8: Tài khoản ngân hàng -->
                <div class="check-item success">
                    <h6><i class="fas fa-university"></i> 8. Tài khoản ngân hàng</h6>
                    <p><strong>Trong SePay Dashboard → Tab "Phương thức thanh toán"</strong></p>
                    <p><strong>Phải là:</strong></p>
                    <ul>
                        <li><strong>Ngân hàng:</strong> VietinBank</li>
                        <li><strong>Số tài khoản:</strong> 100872918542</li>
                        <li><strong>Chủ tài khoản:</strong> BUI THANH BINH</li>
                        <li><strong>Trạng thái:</strong> Mặc định (có dấu sao ⭐)</li>
                    </ul>
                </div>
                
            </div>
        </div>
        
        <!-- Thông tin cấu hình hiện tại -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Cấu hình hiện tại trong code</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th>Mã đơn vị</th>
                        <td><code><?php echo SEPAY_PARTNER_CODE; ?></code></td>
                    </tr>
                    <tr>
                        <th>Webhook URL</th>
                        <td><code><?php echo SEPAY_CALLBACK_URL; ?></code></td>
                    </tr>
                    <tr>
                        <th>Webhook Token (20 ký tự đầu)</th>
                        <td><code><?php echo substr(SEPAY_WEBHOOK_TOKEN, 0, 20); ?>...</code></td>
                    </tr>
                    <tr>
                        <th>Match Pattern</th>
                        <td><code><?php echo SEPAY_MATCH_PATTERN; ?></code></td>
                    </tr>
                    <tr>
                        <th>Environment</th>
                        <td><code><?php echo SEPAY_ENVIRONMENT; ?></code></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Hướng dẫn debug -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-question-circle"></i> Nếu vẫn không nhận được webhook</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Đợi 2-3 phút</strong> sau khi sửa cấu hình trong SePay Dashboard</li>
                    <li><strong>Test lại</strong> bằng cách tạo payment mới và chuyển khoản</li>
                    <li><strong>Kiểm tra logs</strong> tại <a href="debug-webhook.php" target="_blank">debug-webhook.php</a></li>
                    <li><strong>Liên hệ SePay Support</strong> nếu vẫn không nhận được webhook:
                        <ul>
                            <li>Mã đơn vị: <code><?php echo SEPAY_PARTNER_CODE; ?></code></li>
                            <li>IPN URL: <code><?php echo SEPAY_CALLBACK_URL; ?></code></li>
                            <li>Thời gian giao dịch: [Thời gian bạn chuyển khoản]</li>
                            <li>Nội dung chuyển khoản: <code>SEPAY2220</code></li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="text-center">
            <a href="debug-webhook.php" class="btn btn-info">
                <i class="fas fa-bug"></i> Xem Debug Webhook
            </a>
            <a href="sepay-payment.php?test=1" class="btn btn-success" target="_blank">
                <i class="fas fa-vial"></i> Test Endpoint
            </a>
            <a href="../docs/WEBHOOK_TROUBLESHOOTING.md" class="btn btn-primary" target="_blank">
                <i class="fas fa-book"></i> Xem hướng dẫn chi tiết
            </a>
        </div>
        
    </div>
</body>
</html>

