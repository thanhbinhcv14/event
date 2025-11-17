<?php
/**
 * Debug Payment System
 * Kiểm tra thanh toán và webhook SePay
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

// Kiểm tra đăng nhập (tùy chọn - có thể bỏ để test)
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }
// if (!isset($_SESSION['user'])) {
//     die('Vui lòng đăng nhập');
// }

$pdo = getDBConnection();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Payment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .code-block {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1><i class="fas fa-bug"></i> Debug Payment System</h1>
        <p class="text-muted">Kiểm tra thanh toán và webhook SePay</p>
        
        <div class="row mt-4">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs" id="debugTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button">
                        <i class="fas fa-exchange-alt"></i> Giao dịch SePay (tb_transactions)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button">
                        <i class="fas fa-money-bill-wave"></i> Thanh toán (thanhtoan)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="webhooks-tab" data-bs-toggle="tab" data-bs-target="#webhooks" type="button">
                        <i class="fas fa-webhook"></i> Webhook Logs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button">
                        <i class="fas fa-chart-bar"></i> Thống kê
                    </button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content mt-3" id="debugTabContent">
                <!-- Tab 1: Transactions -->
                <div class="tab-pane fade show active" id="transactions" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-exchange-alt"></i> Giao dịch từ SePay Webhook</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT 
                                        t.*,
                                        th.ID_ThanhToan,
                                        th.SoTien as PaymentAmount,
                                        th.TrangThai as PaymentStatus,
                                        dl.TenSuKien,
                                        dl.ID_DatLich
                                    FROM tb_transactions t
                                    LEFT JOIN thanhtoan th ON t.payment_id = th.ID_ThanhToan
                                    LEFT JOIN datlichsukien dl ON th.ID_DatLich = dl.ID_DatLich
                                    ORDER BY t.created_at DESC
                                    LIMIT 50
                                ");
                                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (empty($transactions)) {
                                    echo '<div class="alert alert-info">Chưa có giao dịch nào</div>';
                                } else {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-striped table-sm">';
                                    echo '<thead><tr>
                                        <th>ID</th>
                                        <th>Thời gian</th>
                                        <th>Gateway</th>
                                        <th>Số TK</th>
                                        <th>Loại</th>
                                        <th>Số tiền</th>
                                        <th>Nội dung</th>
                                        <th>Payment ID</th>
                                        <th>Đã xử lý</th>
                                        <th>Trạng thái</th>
                                    </tr></thead>';
                                    echo '<tbody>';
                                    foreach ($transactions as $tx) {
                                        $processedClass = $tx['processed'] ? 'status-success' : 'status-pending';
                                        $processedText = $tx['processed'] ? 'Đã xử lý' : 'Chưa xử lý';
                                        
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($tx['id']) . '</td>';
                                        echo '<td>' . date('d/m/Y H:i:s', strtotime($tx['transaction_date'])) . '</td>';
                                        echo '<td>' . htmlspecialchars($tx['gateway']) . '</td>';
                                        echo '<td>' . htmlspecialchars($tx['account_number']) . '</td>';
                                        echo '<td><span class="badge bg-info">' . htmlspecialchars($tx['transfer_type']) . '</span></td>';
                                        echo '<td><strong>' . number_format($tx['transfer_amount'], 0, ',', '.') . ' VNĐ</strong></td>';
                                        echo '<td><small>' . htmlspecialchars(substr($tx['transaction_content'], 0, 50)) . '...</small></td>';
                                        echo '<td>' . ($tx['payment_id'] ? '<a href="#payment-' . $tx['payment_id'] . '">#' . $tx['payment_id'] . '</a>' : '-') . '</td>';
                                        echo '<td><span class="status-badge ' . $processedClass . '">' . $processedText . '</span></td>';
                                        echo '<td>' . ($tx['PaymentStatus'] ? htmlspecialchars($tx['PaymentStatus']) : '-') . '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</tbody></table>';
                                    echo '</div>';
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 2: Payments -->
                <div class="tab-pane fade" id="payments" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-money-bill-wave"></i> Thanh toán trong hệ thống</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT 
                                        t.*,
                                        dl.TenSuKien,
                                        dl.ID_DatLich,
                                        kh.HoTen,
                                        (SELECT COUNT(*) FROM tb_transactions WHERE payment_id = t.ID_ThanhToan) as transaction_count
                                    FROM thanhtoan t
                                    INNER JOIN datlichsukien dl ON t.ID_DatLich = dl.ID_DatLich
                                    INNER JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                                    ORDER BY t.NgayThanhToan DESC
                                    LIMIT 50
                                ");
                                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (empty($payments)) {
                                    echo '<div class="alert alert-info">Chưa có thanh toán nào</div>';
                                } else {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-striped table-sm">';
                                    echo '<thead><tr>
                                        <th>ID</th>
                                        <th>Sự kiện</th>
                                        <th>Khách hàng</th>
                                        <th>Số tiền</th>
                                        <th>Loại</th>
                                        <th>Mã GD</th>
                                        <th>Trạng thái</th>
                                        <th>SePay TX ID</th>
                                        <th>Số giao dịch</th>
                                        <th>Thời gian</th>
                                    </tr></thead>';
                                    echo '<tbody>';
                                    foreach ($payments as $pay) {
                                        $statusClass = 'status-pending';
                                        if ($pay['TrangThai'] === 'Thành công') $statusClass = 'status-success';
                                        if ($pay['TrangThai'] === 'Thất bại' || $pay['TrangThai'] === 'Đã hủy') $statusClass = 'status-failed';
                                        
                                        echo '<tr id="payment-' . $pay['ID_ThanhToan'] . '">';
                                        echo '<td><strong>#' . $pay['ID_ThanhToan'] . '</strong></td>';
                                        echo '<td>' . htmlspecialchars($pay['TenSuKien']) . '</td>';
                                        echo '<td>' . htmlspecialchars($pay['HoTen']) . '</td>';
                                        echo '<td><strong>' . number_format($pay['SoTien'], 0, ',', '.') . ' VNĐ</strong></td>';
                                        echo '<td>' . htmlspecialchars($pay['LoaiThanhToan']) . '</td>';
                                        echo '<td><code>' . htmlspecialchars($pay['MaGiaoDich']) . '</code></td>';
                                        echo '<td><span class="status-badge ' . $statusClass . '">' . htmlspecialchars($pay['TrangThai']) . '</span></td>';
                                        echo '<td>' . ($pay['SePayTransactionId'] ? '<code>' . htmlspecialchars($pay['SePayTransactionId']) . '</code>' : '-') . '</td>';
                                        echo '<td><span class="badge bg-secondary">' . $pay['transaction_count'] . '</span></td>';
                                        echo '<td>' . date('d/m/Y H:i:s', strtotime($pay['NgayThanhToan'])) . '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</tbody></table>';
                                    echo '</div>';
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 3: Webhook Logs -->
                <div class="tab-pane fade" id="webhooks" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-webhook"></i> Webhook Logs</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT * FROM webhook_logs
                                    ORDER BY created_at DESC
                                    LIMIT 30
                                ");
                                $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (empty($webhooks)) {
                                    echo '<div class="alert alert-info">Chưa có webhook nào</div>';
                                } else {
                                    foreach ($webhooks as $wh) {
                                        $processedClass = $wh['processed'] ? 'status-success' : 'status-pending';
                                        $data = json_decode($wh['raw_data'], true);
                                        
                                        echo '<div class="card mb-2">';
                                        echo '<div class="card-body">';
                                        echo '<div class="d-flex justify-content-between">';
                                        echo '<div>';
                                        echo '<strong>ID:</strong> ' . $wh['id'] . ' | ';
                                        echo '<strong>Nguồn:</strong> ' . htmlspecialchars($wh['webhook_source']) . ' | ';
                                        echo '<strong>Thời gian:</strong> ' . date('d/m/Y H:i:s', strtotime($wh['created_at'])) . ' | ';
                                        echo '<span class="status-badge ' . $processedClass . '">' . ($wh['processed'] ? 'Đã xử lý' : 'Chưa xử lý') . '</span>';
                                        echo '</div>';
                                        echo '</div>';
                                        
                                        if ($data) {
                                            echo '<div class="mt-2">';
                                            echo '<strong>Dữ liệu:</strong>';
                                            echo '<div class="code-block">';
                                            echo '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        
                                        if ($wh['response']) {
                                            $response = json_decode($wh['response'], true);
                                            echo '<div class="mt-2">';
                                            echo '<strong>Response:</strong>';
                                            echo '<div class="code-block">';
                                            echo '<pre>' . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        
                                        echo '</div></div>';
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 4: Statistics -->
                <div class="tab-pane fade" id="stats" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-pie"></i> Thống kê Giao dịch</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    try {
                                        $stmt = $pdo->query("
                                            SELECT 
                                                transfer_type,
                                                COUNT(*) as total,
                                                SUM(amount_in) as total_in,
                                                SUM(amount_out) as total_out,
                                                SUM(CASE WHEN processed = 1 THEN 1 ELSE 0 END) as processed_count
                                            FROM tb_transactions
                                            GROUP BY transfer_type
                                        ");
                                        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        echo '<table class="table table-sm">';
                                        echo '<thead><tr><th>Loại</th><th>Tổng số</th><th>Tiền vào</th><th>Tiền ra</th><th>Đã xử lý</th></tr></thead>';
                                        echo '<tbody>';
                                        foreach ($stats as $stat) {
                                            echo '<tr>';
                                            echo '<td><span class="badge bg-info">' . htmlspecialchars($stat['transfer_type']) . '</span></td>';
                                            echo '<td>' . $stat['total'] . '</td>';
                                            echo '<td>' . number_format($stat['total_in'], 0, ',', '.') . ' VNĐ</td>';
                                            echo '<td>' . number_format($stat['total_out'], 0, ',', '.') . ' VNĐ</td>';
                                            echo '<td>' . $stat['processed_count'] . '</td>';
                                            echo '</tr>';
                                        }
                                        echo '</tbody></table>';
                                    } catch (Exception $e) {
                                        echo '<div class="alert alert-danger">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-line"></i> Thống kê Thanh toán</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    try {
                                        $stmt = $pdo->query("
                                            SELECT 
                                                TrangThai,
                                                COUNT(*) as total,
                                                SUM(SoTien) as total_amount
                                            FROM thanhtoan
                                            GROUP BY TrangThai
                                        ");
                                        $payStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        echo '<table class="table table-sm">';
                                        echo '<thead><tr><th>Trạng thái</th><th>Số lượng</th><th>Tổng tiền</th></tr></thead>';
                                        echo '<tbody>';
                                        foreach ($payStats as $stat) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($stat['TrangThai']) . '</td>';
                                            echo '<td>' . $stat['total'] . '</td>';
                                            echo '<td>' . number_format($stat['total_amount'], 0, ',', '.') . ' VNĐ</td>';
                                            echo '</tr>';
                                        }
                                        echo '</tbody></table>';
                                    } catch (Exception $e) {
                                        echo '<div class="alert alert-danger">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-exclamation-triangle"></i> Giao dịch chưa xử lý</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT COUNT(*) as count
                                    FROM tb_transactions
                                    WHERE processed = 0 AND transfer_type = 'in'
                                ");
                                $unprocessed = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($unprocessed['count'] > 0) {
                                    echo '<div class="alert alert-warning">';
                                    echo '<strong>Có ' . $unprocessed['count'] . ' giao dịch chưa được xử lý!</strong>';
                                    echo '</div>';
                                    
                                    $stmt = $pdo->query("
                                        SELECT * FROM tb_transactions
                                        WHERE processed = 0 AND transfer_type = 'in'
                                        ORDER BY created_at DESC
                                        LIMIT 10
                                    ");
                                    $unprocessedList = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    echo '<table class="table table-sm table-warning">';
                                    echo '<thead><tr><th>ID</th><th>Thời gian</th><th>Số tiền</th><th>Nội dung</th></tr></thead>';
                                    echo '<tbody>';
                                    foreach ($unprocessedList as $tx) {
                                        echo '<tr>';
                                        echo '<td>' . $tx['id'] . '</td>';
                                        echo '<td>' . date('d/m/Y H:i:s', strtotime($tx['transaction_date'])) . '</td>';
                                        echo '<td>' . number_format($tx['transfer_amount'], 0, ',', '.') . ' VNĐ</td>';
                                        echo '<td><small>' . htmlspecialchars(substr($tx['transaction_content'], 0, 50)) . '...</small></td>';
                                        echo '</tr>';
                                    }
                                    echo '</tbody></table>';
                                } else {
                                    echo '<div class="alert alert-success">Tất cả giao dịch đã được xử lý!</div>';
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>

