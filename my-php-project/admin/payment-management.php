<?php
// Include admin header
include 'includes/admin-header.php';
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-credit-card"></i>
                Quản lý thanh toán
            </h1>
            <p class="page-subtitle">Quản lý và theo dõi các giao dịch thanh toán</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-number" id="totalPayments">0</div>
                <div class="stat-label">Tổng giao dịch</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="successfulPayments">0</div>
                <div class="stat-label">Thành công</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number" id="pendingPayments">0</div>
                <div class="stat-label">Đang xử lý</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon rejected">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number" id="totalAmount">0</div>
                <div class="stat-label">Tổng tiền (VNĐ)</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tìm kiếm</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="Mã giao dịch, tên khách hàng...">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Đang xử lý">Đang xử lý</option>
                        <option value="Chờ thanh toán">Chờ thanh toán</option>
                        <option value="Thành công">Thành công</option>
                        <option value="Thất bại">Thất bại</option>
                        <option value="Đã hủy">Đã hủy</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Phương thức</label>
                    <select class="form-select" id="methodFilter">
                        <option value="">Tất cả phương thức</option>
                        <option value="Momo">Momo</option>
                        <option value="Chuyển khoản">Chuyển khoản</option>
                        <option value="ZaloPay">ZaloPay</option>
                        <option value="Tiền mặt">Tiền mặt</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Loại thanh toán</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">Tất cả loại</option>
                        <option value="Đặt cọc">Đặt cọc</option>
                        <option value="Thanh toán đủ">Thanh toán đủ</option>
                        <option value="Hoàn tiền">Hoàn tiền</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                        <button class="btn btn-outline-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Xóa
                        </button>
                        <button class="btn btn-success" onclick="exportPayments()">
                            <i class="fas fa-download"></i> Xuất Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">
                    <i class="fas fa-list"></i>
                    Danh sách thanh toán
                </h3>
                <div class="action-buttons">
                    
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mã giao dịch</th>
                            <th>Khách hàng</th>
                            <th>Sự kiện</th>
                            <th>Số tiền</th>
                            <th>Phương thức</th>
                            <th>Loại thanh toán</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Details Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalTitle">
                            <i class="fas fa-receipt"></i>
                            Chi tiết thanh toán
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="paymentModalBody">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" id="updateStatusBtn" onclick="showStatusUpdate()">
                            <i class="fas fa-edit"></i> Cập nhật trạng thái
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Update Modal -->
        <div class="modal fade" id="statusModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit"></i>
                            Cập nhật trạng thái thanh toán
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="statusForm">
                            <input type="hidden" id="paymentId" name="payment_id">
                            <div class="mb-3">
                                <label class="form-label">Trạng thái hiện tại</label>
                                <input type="text" class="form-control" id="currentStatus" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Trạng thái mới</label>
                                <select class="form-select" id="newStatus" name="status" required>
                                    <option value="Đang xử lý">Đang xử lý</option>
                                    <option value="Thành công">Thành công</option>
                                    <option value="Thất bại">Thất bại</option>
                                    <option value="Đã hủy">Đã hủy</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea class="form-control" id="statusNote" rows="3" placeholder="Ghi chú về việc thay đổi trạng thái"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="updatePaymentStatus()">
                            <i class="fas fa-save"></i> Cập nhật
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Confirmation Modal -->
        <div class="modal fade" id="cashConfirmModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-money-bill-wave"></i>
                            Xác nhận thanh toán tiền mặt
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Chỉ xác nhận khi khách hàng đã thanh toán tiền mặt trực tiếp tại văn phòng.
                        </div>
                        
                        <form id="cashConfirmForm">
                            <input type="hidden" id="cashPaymentId" name="payment_id">
                            
                            <div class="mb-3">
                                <label class="form-label">Thông tin thanh toán</label>
                                <div id="cashPaymentInfo" class="border rounded p-3 bg-light">
                                    <!-- Payment info will be loaded here -->
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ghi chú xác nhận</label>
                                <textarea class="form-control" id="confirmNote" name="confirm_note" rows="3" 
                                         placeholder="Ghi chú về việc xác nhận thanh toán (tùy chọn)"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                                    <label class="form-check-label" for="confirmCheck">
                                        Tôi xác nhận khách hàng đã thanh toán tiền mặt đầy đủ
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-success" onclick="processCashConfirmation()">
                            <i class="fas fa-check-circle"></i> Xác nhận thanh toán
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <style>
        /* Remove modal backdrop completely */
        .modal-backdrop {
            display: none !important;
        }
        
        /* Ensure body doesn't get locked when modal is open */
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }
        
        /* Optional: Add a subtle overlay effect if you want some visual indication */
        .modal.show {
            background-color: rgba(0, 0, 0, 0.1);
        }
        </style>

    <script>
        let paymentsTable;
        let currentPaymentId = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for AdminPanel to be available
            if (typeof AdminPanel === 'undefined') {
                console.error('AdminPanel not loaded');
                setTimeout(() => {
                    if (typeof AdminPanel === 'undefined') {
                        console.error('AdminPanel still not available after timeout');
                        alert('AdminPanel không được tải. Vui lòng reload trang.');
                    } else {
                        initializePage();
                    }
                }, 1000);
            } else {
                initializePage();
            }
        });
        
        function initializePage() {
            initializeDataTable();
            loadStatistics();
            setupEventListeners();
        }

        function initializeDataTable() {
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables not available');
                AdminPanel.showError('DataTables không khả dụng');
                return;
            }

            try {
                paymentsTable = $('#paymentsTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '../src/controllers/payment.php',
                        type: 'GET',
                        data: function(d) {
                            d.action = 'get_payment_list';
                        },
                        dataSrc: function(json) {
                            if (json.success && json.payments) {
                                return json.payments;
                            } else {
                                console.error('Invalid data format:', json);
                                return [];
                            }
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTable AJAX Error:', error);
                            AdminPanel.showError('Không thể tải dữ liệu thanh toán');
                        }
                    },
                    columns: [
                        { data: 'ID_ThanhToan', className: 'text-center' },
                        { data: 'MaGiaoDich' },
                        { 
                            data: 'KhachHangTen',
                            render: function(data, type, row) {
                                return data + '<br><small class="text-muted">' + (row.SoDienThoai || '') + '</small>';
                            }
                        },
                        { data: 'TenSuKien' },
                        { 
                            data: 'SoTien',
                            render: function(data) {
                                return AdminPanel.formatCurrency(data);
                            }
                        },
                        { data: 'PhuongThuc' },
                        { 
                            data: 'LoaiThanhToan',
                            render: function(data) {
                                const typeMap = {
                                    'Đặt cọc': { class: 'warning', text: 'Đặt cọc', icon: 'fa-hand-holding-usd' },
                                    'Thanh toán đủ': { class: 'success', text: 'Thanh toán đủ', icon: 'fa-check-circle' },
                                    'Hoàn tiền': { class: 'info', text: 'Hoàn tiền', icon: 'fa-undo' }
                                };
                                const type = typeMap[data] || { class: 'secondary', text: data, icon: 'fa-question' };
                                return `<span class="badge bg-${type.class}">
                                            <i class="fas ${type.icon}"></i> ${type.text}
                                        </span>`;
                            }
                        },
                        { 
                            data: 'TrangThai',
                            render: function(data) {
                                const statusMap = {
                                    'Đang xử lý': { class: 'warning', text: 'Đang xử lý', icon: 'fa-clock' },
                                    'Chờ thanh toán': { class: 'info', text: 'Chờ thanh toán', icon: 'fa-hourglass-half' },
                                    'Thành công': { class: 'success', text: 'Thành công', icon: 'fa-check-circle' },
                                    'Thất bại': { class: 'danger', text: 'Thất bại', icon: 'fa-times-circle' },
                                    'Đã hủy': { class: 'secondary', text: 'Đã hủy', icon: 'fa-ban' }
                                };
                                const status = statusMap[data] || { class: 'secondary', text: data, icon: 'fa-question' };
                                return `<span class="badge bg-${status.class}">
                                            <i class="fas ${status.icon}"></i> ${status.text}
                                        </span>`;
                            }
                        },
                        { 
                            data: 'NgayThanhToan',
                            render: function(data) {
                                return AdminPanel.formatDate(data, 'dd/mm/yyyy hh:mm');
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            render: function(data, type, row) {
                                return `
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm" onclick="viewPayment(${row.ID_ThanhToan})" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="editPayment(${row.ID_ThanhToan})" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                `;
                            }
                        }
                    ],
                    order: [[0, 'desc']],
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                    },
                    dom: 'rtip',
                    info: false,
                    paging: false
                });
            } catch (error) {
                console.error('Error initializing DataTable:', error);
                AdminPanel.showError('Lỗi khởi tạo bảng dữ liệu');
            }
        }

        function loadStatistics() {
            AdminPanel.makeAjaxRequest('../src/controllers/payment.php', {
                action: 'get_payment_stats'
            })
            .then(response => {
                if (response.success && response.stats) {
                    $('#totalPayments').text(response.stats.total || 0);
                    $('#successfulPayments').text(response.stats.successful || 0);
                    $('#totalAmount').text(AdminPanel.formatCurrency(response.stats.total_amount || 0));
                    
                    // Calculate pending payments
                    const pending = (response.stats.total || 0) - (response.stats.successful || 0);
                    $('#pendingPayments').text(pending);
                }
            })
            .catch(error => {
                console.error('Statistics load error:', error);
            });
        }

        function setupEventListeners() {
            // Search input with debounce
            let searchTimeout;
            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyFilters();
                }, 300);
            });

            // Filter change events
            $('#statusFilter, #methodFilter, #typeFilter').on('change', function() {
                applyFilters();
            });
        }

        function applyFilters() {
            const searchValue = $('#searchInput').val();
            const statusFilter = $('#statusFilter').val();
            const methodFilter = $('#methodFilter').val();
            const typeFilter = $('#typeFilter').val();
            
            // Apply search to DataTable
            paymentsTable.search(searchValue).draw();
            
            // Apply column filters
            if (statusFilter) {
                paymentsTable.column(7).search(statusFilter); // Trạng thái is now column 7
            } else {
                paymentsTable.column(7).search('');
            }
            
            if (methodFilter) {
                paymentsTable.column(5).search(methodFilter); // Phương thức is still column 5
            } else {
                paymentsTable.column(5).search('');
            }
            
            if (typeFilter) {
                paymentsTable.column(6).search(typeFilter); // Loại thanh toán is now column 6
            } else {
                paymentsTable.column(6).search('');
            }
            
            // Redraw table
            paymentsTable.draw();
        }

        function clearFilters() {
            $('#searchInput').val('');
            $('#statusFilter').val('');
            $('#methodFilter').val('');
            $('#typeFilter').val('');
            
            // Clear all DataTable filters
            paymentsTable.search('');
            paymentsTable.columns().search('');
            paymentsTable.draw();
        }

        function viewPayment(paymentId) {
            AdminPanel.showLoading('#paymentModalBody');
            
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();

            AdminPanel.makeAjaxRequest('../src/controllers/payment.php', {
                action: 'get_payment_status',
                payment_id: paymentId
            })
            .then(response => {
                if (response.success && response.payment) {
                    const payment = response.payment;
                    currentPaymentId = paymentId;
                    
                    $('#paymentModalBody').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-receipt"></i> Thông tin thanh toán</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>ID:</strong></td><td>${payment.ID_ThanhToan}</td></tr>
                                    <tr><td><strong>Mã giao dịch:</strong></td><td>${payment.MaGiaoDich}</td></tr>
                                    <tr><td><strong>Số tiền:</strong></td><td>${AdminPanel.formatCurrency(payment.SoTien)} VNĐ</td></tr>
                                    <tr><td><strong>Phương thức:</strong></td><td>${payment.PhuongThuc}</td></tr>
                                    <tr><td><strong>Loại thanh toán:</strong></td><td><span class="badge bg-${getPaymentTypeClass(payment.LoaiThanhToan)}">${payment.LoaiThanhToan}</span></td></tr>
                                    <tr><td><strong>Trạng thái:</strong></td><td><span class="badge bg-${getStatusClass(payment.TrangThai)}">${payment.TrangThai}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-calendar"></i> Thông tin sự kiện</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Tên sự kiện:</strong></td><td>${payment.TenSuKien}</td></tr>
                                    <tr><td><strong>Ngày bắt đầu:</strong></td><td>${AdminPanel.formatDate(payment.NgayBatDau, 'dd/mm/yyyy hh:mm')}</td></tr>
                                    <tr><td><strong>Ngày kết thúc:</strong></td><td>${AdminPanel.formatDate(payment.NgayKetThuc, 'dd/mm/yyyy hh:mm')}</td></tr>
                                    <tr><td><strong>Ngày thanh toán:</strong></td><td>${AdminPanel.formatDate(payment.NgayThanhToan, 'dd/mm/yyyy hh:mm')}</td></tr>
                                </table>
                            </div>
                        </div>
                        ${payment.GhiChu ? `<div class="mt-3"><strong>Ghi chú:</strong><br>${payment.GhiChu}</div>` : ''}
                    `);
                } else {
                    $('#paymentModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.error || 'Không thể tải chi tiết thanh toán'}
                        </div>
                    `);
                }
            })
            .catch(error => {
                $('#paymentModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải chi tiết thanh toán
                    </div>
                `);
            });
        }

        function showStatusUpdate() {
            if (!currentPaymentId) return;
            
            // Get current payment status
            AdminPanel.makeAjaxRequest('../src/controllers/payment.php', {
                action: 'get_payment_status',
                payment_id: currentPaymentId
            })
            .then(response => {
                if (response.success && response.payment) {
                    $('#paymentId').val(currentPaymentId);
                    $('#currentStatus').val(response.payment.TrangThai);
                    $('#newStatus').val(response.payment.TrangThai);
                    $('#statusNote').val('');
                    
                    const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
                    statusModal.show();
                }
            });
        }

        // Edit payment: open the status update modal for the selected payment
        function editPayment(paymentId) {
            try {
                console.log('editPayment called with ID:', paymentId);
                if (typeof AdminPanel === 'undefined') {
                    console.error('AdminPanel not available in editPayment');
                    alert('AdminPanel không được tải');
                    return;
                }
                currentPaymentId = paymentId;
                showStatusUpdate();
            } catch (error) {
                console.error('Error in editPayment:', error);
                alert('Lỗi trong editPayment: ' + error.message);
            }
        }

        // Update payment status from the status modal
        function updatePaymentStatus() {
            try {
                console.log('updatePaymentStatus called');
                if (typeof AdminPanel === 'undefined') {
                    console.error('AdminPanel not available in updatePaymentStatus');
                    alert('AdminPanel không được tải');
                    return;
                }
                
                const paymentId = document.getElementById('paymentId').value || currentPaymentId;
                const newStatus = document.getElementById('newStatus').value;
                const note = document.getElementById('statusNote').value || '';

                if (!paymentId) {
                    AdminPanel.showError('Thiếu ID thanh toán');
                    return;
                }
                if (!newStatus) {
                    AdminPanel.showError('Vui lòng chọn trạng thái mới');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'update_payment_status');
                formData.append('payment_id', paymentId);
                formData.append('status', newStatus);
                formData.append('note', note);

                AdminPanel.makeAjaxRequest('../src/controllers/payment.php', formData, 'POST')
                .then(response => {
                    if (response.success) {
                        AdminPanel.showSuccess('Cập nhật trạng thái thành công');
                        // Close modal
                        const modalEl = document.getElementById('statusModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        // Refresh table and stats
                        if (paymentsTable) paymentsTable.ajax.reload();
                        loadStatistics();
                    } else {
                        AdminPanel.showError(response.error || 'Cập nhật trạng thái thất bại');
                    }
                })
                .catch(() => {
                    AdminPanel.showError('Có lỗi xảy ra khi cập nhật trạng thái');
                });
            } catch (error) {
                console.error('Error in updatePaymentStatus:', error);
                alert('Lỗi trong updatePaymentStatus: ' + error.message);
            }
        }

        function confirmCashPayment(paymentId) {
            // Get payment details
            AdminPanel.makeAjaxRequest('../src/controllers/payment.php', {
                action: 'get_payment_status',
                payment_id: paymentId
            })
            .then(response => {
                if (response.success && response.payment) {
                    const payment = response.payment;
                    
                    // Set payment ID
                    $('#cashPaymentId').val(paymentId);
                    
                    // Display payment info
                    $('#cashPaymentInfo').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Mã giao dịch:</strong> ${payment.MaGiaoDich}</p>
                                <p><strong>Số tiền:</strong> ${AdminPanel.formatCurrency(payment.SoTien)} VNĐ</p>
                                <p><strong>Phương thức:</strong> ${payment.PhuongThuc}</p>
                                <p><strong>Loại thanh toán:</strong> <span class="badge bg-${getPaymentTypeClass(payment.LoaiThanhToan)}">${payment.LoaiThanhToan}</span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Khách hàng:</strong> ${payment.KhachHangTen}</p>
                                <p><strong>Sự kiện:</strong> ${payment.TenSuKien}</p>
                                <p><strong>Trạng thái:</strong> <span class="badge bg-${getStatusClass(payment.TrangThai)}">${payment.TrangThai}</span></p>
                                <p><strong>Ngày tạo:</strong> ${AdminPanel.formatDate(payment.NgayThanhToan, 'dd/mm/yyyy hh:mm')}</p>
                            </div>
                        </div>
                    `);
                    
                    // Clear form
                    $('#confirmNote').val('');
                    $('#confirmCheck').prop('checked', false);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('cashConfirmModal'));
                    modal.show();
                } else {
                    AdminPanel.showError('Không thể tải thông tin thanh toán');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi tải thông tin thanh toán');
            });
        }

        function processCashConfirmation() {
            if (!$('#confirmCheck').is(':checked')) {
                AdminPanel.showError('Vui lòng xác nhận rằng khách hàng đã thanh toán tiền mặt');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'confirm_cash_payment');
            formData.append('payment_id', $('#cashPaymentId').val());
            formData.append('confirm_note', $('#confirmNote').val());

            AdminPanel.makeAjaxRequest('../src/controllers/payment.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess('Xác nhận thanh toán tiền mặt thành công');
                    bootstrap.Modal.getInstance(document.getElementById('cashConfirmModal')).hide();
                    paymentsTable.ajax.reload();
                    loadStatistics();
                } else {
                    AdminPanel.showError(response.error || 'Có lỗi xảy ra khi xác nhận thanh toán');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi xác nhận thanh toán');
            });
        }

        

        function exportPayments() {
            // Export functionality would be implemented here
            AdminPanel.showInfo('Chức năng xuất Excel sẽ được triển khai');
        }

        function getStatusClass(status) {
            const statusMap = {
                'Đang xử lý': 'warning',
                'Chờ thanh toán': 'info',
                'Thành công': 'success',
                'Thất bại': 'danger',
                'Đã hủy': 'secondary'
            };
            return statusMap[status] || 'secondary';
        }

        function getPaymentTypeClass(type) {
            const typeMap = {
                'Đặt cọc': 'warning',
                'Thanh toán đủ': 'success',
                'Hoàn tiền': 'info'
            };
            return typeMap[type] || 'secondary';
        }

        // Auto refresh every 30 seconds
        setInterval(() => {
            loadStatistics();
        }, 30000);
    </script>

    <style>
        .action-buttons .btn {
            margin: 0 2px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
    </style>

<?php include 'includes/admin-footer.php'; ?>
