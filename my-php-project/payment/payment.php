<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit();
}

$pdo = getDBConnection();
$eventId = $_GET['event_id'] ?? null;

if (!$eventId) {
    header('Location: ../events/my-events.php');
    exit();
}

// Get event details
$stmt = $pdo->prepare("
    SELECT dl.*, dd.TenDiaDiem, dd.DiaChi, dd.GiaThue as DiaDiemGia,
           kh.HoTen as KhachHangTen, kh.SoDienThoai
    FROM datlichsukien dl 
    LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
    LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
    WHERE dl.ID_DatLich = ? AND dl.ID_KhachHang = (SELECT ID_KhachHang FROM khachhanginfo WHERE ID_User = ?)
");
$stmt->execute([$eventId, $_SESSION['user']['ID_User']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: ../events/my-events.php');
    exit();
}

// Calculate total amount
$totalAmount = $event['DiaDiemGia'] ?? 0;
$depositAmount = $totalAmount * 0.3; // 30% deposit
$remainingAmount = $totalAmount - $depositAmount;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán sự kiện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .payment-method-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,123,255,0.1);
        }
        .payment-method-card.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .amount-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }
        .banking-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        .qr-code {
            text-align: center;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <!-- Event Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-calendar-alt"></i> Thông tin sự kiện</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Tên sự kiện:</strong> <?= htmlspecialchars($event['TenSuKien']) ?></p>
                                <p><strong>Địa điểm:</strong> <?= htmlspecialchars($event['TenDiaDiem']) ?></p>
                                <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($event['DiaChi']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Ngày bắt đầu:</strong> <?= date('d/m/Y H:i', strtotime($event['NgayBatDau'])) ?></p>
                                <p><strong>Ngày kết thúc:</strong> <?= date('d/m/Y H:i', strtotime($event['NgayKetThuc'])) ?></p>
                                <p><strong>Số người:</strong> <?= $event['SoNguoiDuKien'] ?> người</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-credit-card"></i> Chọn phương thức thanh toán</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Momo Payment -->
                            <div class="col-md-6">
                                <div class="payment-method-card" data-method="Momo">
                                    <div class="text-center">
                                        <div class="payment-icon text-danger">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <h5>Ví MoMo</h5>
                                        <p class="text-muted">Thanh toán nhanh qua ví điện tử</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Banking Payment -->
                            <div class="col-md-6">
                                <div class="payment-method-card" data-method="Chuyển khoản">
                                    <div class="text-center">
                                        <div class="payment-icon text-primary">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <h5>Chuyển khoản ngân hàng</h5>
                                        <p class="text-muted">Chuyển khoản qua ngân hàng</p>
                                    </div>
                                </div>
                            </div>

                            <!-- ZaloPay -->
                            <div class="col-md-6">
                                <div class="payment-method-card" data-method="ZaloPay">
                                    <div class="text-center">
                                        <div class="payment-icon text-info">
                                            <i class="fas fa-wallet"></i>
                                        </div>
                                        <h5>ZaloPay</h5>
                                        <p class="text-muted">Thanh toán qua ZaloPay</p>
                                    </div>
                                </div>
                            </div>

                            <!-- SePay -->
                            <div class="col-md-6">
                                <div class="payment-method-card" data-method="SePay">
                                    <div class="text-center">
                                        <div class="payment-icon text-success">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <h5>SePay</h5>
                                        <p class="text-muted">Thanh toán qua SePay</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cash -->
                            <div class="col-md-6">
                                <div class="payment-method-card" data-method="Tiền mặt">
                                    <div class="text-center">
                                        <div class="payment-icon text-success">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <h5>Tiền mặt</h5>
                                        <p class="text-muted">Thanh toán trực tiếp tại văn phòng</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banking Information (hidden by default) -->
                <div class="card mb-4" id="bankingInfo" style="display: none;">
                    <div class="card-header">
                        <h4><i class="fas fa-university"></i> Thông tin chuyển khoản</h4>
                    </div>
                    <div class="card-body">
                        <div class="banking-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Ngân hàng:</strong> Vietcombank</p>
                                    <p><strong>Số tài khoản:</strong> 1234567890</p>
                                    <p><strong>Chủ tài khoản:</strong> CÔNG TY TNHH SỰ KIỆN ABC</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Nội dung chuyển khoản:</strong></p>
                                    <code>THANH TOAN <?= $event['ID_DatLich'] ?> <?= strtoupper($event['TenSuKien']) ?></code>
                                </div>
                            </div>
                            <div class="qr-code">
                                <img src="../img/qr-code-banking.png" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                                <p class="text-muted mt-2">Quét mã QR để chuyển khoản</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cash Payment Information (hidden by default) -->
                <div class="card mb-4" id="cashInfo" style="display: none;">
                    <div class="card-header">
                        <h4><i class="fas fa-money-bill-wave"></i> Thông tin thanh toán tiền mặt</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Bạn cần đến văn phòng công ty để thanh toán trực tiếp bằng tiền mặt.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-building"></i> Thông tin công ty</h6>
                                <p><strong>Tên công ty:</strong> CÔNG TY TNHH QUẢN LÝ SỰ KIỆN ABC</p>
                                <p><strong>Địa chỉ:</strong> 123 Đường Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh</p>
                                <p><strong>Số điện thoại:</strong> (028) 1234-5678</p>
                                <p><strong>Email:</strong> info@eventabc.com</p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-clock"></i> Giờ làm việc</h6>
                                <p><strong>Thứ 2 - Thứ 6:</strong> 8:00 - 17:00</p>
                                <p><strong>Thứ 7:</strong> 8:00 - 12:00</p>
                                <p><strong>Chủ nhật:</strong> Nghỉ</p>
                                <p><strong>Nghỉ lễ:</strong> Theo quy định nhà nước</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <h6><i class="fas fa-map-marker-alt"></i> Hướng dẫn đường đi</h6>
                            <p class="text-muted">
                                Từ trung tâm TP.HCM, đi theo đường Nguyễn Huệ về hướng Bến Nghé. 
                                Văn phòng nằm ở tầng 5, tòa nhà ABC Plaza, đối diện với Vincom Center.
                            </p>
                        </div>
                        <div class="mt-3">
                            <h6><i class="fas fa-exclamation-triangle text-warning"></i> Lưu ý quan trọng</h6>
                            <ul class="text-muted">
                                <li>Vui lòng mang theo CMND/CCCD để xác minh danh tính</li>
                                <li>Chuẩn bị đúng số tiền theo hóa đơn</li>
                                <li>Nhận biên lai thanh toán sau khi hoàn tất</li>
                                <li>Thanh toán sẽ được xác nhận trong vòng 24 giờ</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Payment Summary -->
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-receipt"></i> Tóm tắt thanh toán</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Loại thanh toán</label>
                            <select class="form-select" id="paymentType">
                                <option value="Đặt cọc">Đặt cọc (30%)</option>
                                <option value="Thanh toán đủ">Thanh toán đủ (100%)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số tiền</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="amount" readonly>
                                <span class="input-group-text">VNĐ</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="note" rows="3" placeholder="Ghi chú thêm (tùy chọn)"></textarea>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-primary btn-lg" id="proceedPayment" disabled>
                                <i class="fas fa-credit-card"></i> Tiến hành thanh toán
                            </button>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> 
                                Thông tin thanh toán được mã hóa và bảo mật
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang xử lý thanh toán...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedMethod = null;
        let totalAmount = <?= $totalAmount ?>;
        let depositAmount = <?= $depositAmount ?>;
        let remainingAmount = <?= $remainingAmount ?>;

        // Update amount based on payment type
        function updateAmount() {
            const paymentType = document.getElementById('paymentType').value;
            const amount = paymentType === 'Đặt cọc' ? depositAmount : totalAmount;
            document.getElementById('amount').value = amount;
        }

        // Payment method selection
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove previous selection
                document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('selected'));
                
                // Add selection to clicked card
                this.classList.add('selected');
                selectedMethod = this.dataset.method;
                
                // Show/hide banking info and cash info
                const bankingInfo = document.getElementById('bankingInfo');
                const cashInfo = document.getElementById('cashInfo');
                const paymentTypeSelect = document.getElementById('paymentType');
                
                if (selectedMethod === 'Chuyển khoản') {
                    bankingInfo.style.display = 'block';
                    cashInfo.style.display = 'none';
                    // Banking must be deposit
                    paymentTypeSelect.value = 'Đặt cọc';
                    paymentTypeSelect.disabled = true;
                    updateAmount();
                } else if (selectedMethod === 'Tiền mặt') {
                    bankingInfo.style.display = 'none';
                    cashInfo.style.display = 'block';
                    // Cash must be full payment
                    paymentTypeSelect.value = 'Thanh toán đủ';
                    paymentTypeSelect.disabled = true;
                    updateAmount();
                } else if (selectedMethod === 'Momo' || selectedMethod === 'ZaloPay') {
                    bankingInfo.style.display = 'none';
                    cashInfo.style.display = 'none';
                    // E-wallet must be deposit
                    paymentTypeSelect.value = 'Đặt cọc';
                    paymentTypeSelect.disabled = true;
                    updateAmount();
                } else if (selectedMethod === 'SePay') {
                    bankingInfo.style.display = 'block';
                    cashInfo.style.display = 'none';
                    // SePay must be deposit
                    paymentTypeSelect.value = 'Đặt cọc';
                    paymentTypeSelect.disabled = true;
                    updateAmount();
                } else {
                    bankingInfo.style.display = 'none';
                    cashInfo.style.display = 'none';
                    paymentTypeSelect.disabled = false;
                }
                
                // Enable proceed button
                document.getElementById('proceedPayment').disabled = false;
            });
        });

        // Payment type change
        document.getElementById('paymentType').addEventListener('change', updateAmount);

        // Proceed payment
        document.getElementById('proceedPayment').addEventListener('click', function() {
            if (!selectedMethod) {
                alert('Vui lòng chọn phương thức thanh toán');
                return;
            }

            const amount = document.getElementById('amount').value;
            const note = document.getElementById('note').value;
            const paymentType = document.getElementById('paymentType').value;

            // Show loading modal
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();

            // Create payment
            const formData = new FormData();
            formData.append('action', 'create_payment');
            formData.append('event_id', <?= $event['ID_DatLich'] ?>);
            formData.append('amount', amount);
            formData.append('payment_type', paymentType === 'Đặt cọc' ? 'deposit' : 'full');
            formData.append('payment_method', selectedMethod === 'Tiền mặt' ? 'cash' : 
                           selectedMethod === 'Chuyển khoản' ? 'banking' : 
                           selectedMethod === 'Momo' ? 'momo' : 
                           selectedMethod === 'SePay' ? 'sepay' : 'zalo');
            formData.append('note', note);

            fetch('../src/controllers/payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loadingModal.hide();
                
                if (data.success) {
                    if (selectedMethod === 'Momo' && data.momo_url) {
                        // Redirect to Momo payment
                        window.location.href = data.momo_url;
                    } else if (selectedMethod === 'Chuyển khoản') {
                        // Show banking info and redirect to payment status
                        alert('Vui lòng chuyển khoản theo thông tin bên dưới và xác nhận thanh toán');
                        window.location.href = `payment-status.php?payment_id=${data.payment_id}`;
                    } else if (selectedMethod === 'SePay') {
                        // Show SePay form
                        showSePayForm(data.payment_id, amount);
                    } else if (selectedMethod === 'Tiền mặt') {
                        // For cash, set to processing and return to My Events
                        alert('Yêu cầu thanh toán tiền mặt đã được tạo. Trạng thái: Đang xử lý.');
                        window.location.href = `../events/my-events.php`;
                    } else {
                        // Other payment methods
                        alert('Thanh toán đã được tạo. Vui lòng liên hệ admin để xác nhận.');
                        window.location.href = '../events/my-events.php';
                    }
                } else {
                    alert('Lỗi: ' + (data.error || 'Không thể tạo thanh toán'));
                }
            })
            .catch(error => {
                loadingModal.hide();
                alert('Lỗi: ' + error.message);
            });
        });

        // SePay form function
        function showSePayForm(paymentId, amount) {
            // Create modal for SePay form
            const modalHtml = `
                <div class="modal fade" id="sepayModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-university"></i>
                                    Thanh toán qua SePay
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <h6>Số tiền: <strong>${formatCurrency(amount)} VNĐ</strong></h6>
                                    <p class="text-muted">Vui lòng click vào nút bên dưới để chuyển đến trang thanh toán SePay</p>
                                </div>
                                <div id="sepayFormContainer">
                                    <div class="text-center">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Đang tải...</span>
                                        </div>
                                        <p class="mt-2">Đang tải form thanh toán...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('sepayModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('sepayModal'));
            modal.show();
            
            // Load SePay form
            fetch(`../src/controllers/payment.php?action=get_sepay_form&event_id=<?= $event['ID_DatLich'] ?>&amount=${amount}&payment_type=deposit`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('sepayFormContainer').innerHTML = data.form_html;
                    } else {
                        document.getElementById('sepayFormContainer').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Lỗi tải form SePay: ${data.error}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('sepayFormContainer').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Lỗi tải form SePay: ${error.message}
                        </div>
                    `;
                });
        }

        // Initialize
        updateAmount();
    </script>
</body>
</html>
