<?php
// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền truy cập - Chỉ Admin (role 1) và Quản lý sự kiện (role 3) mới có quyền
$userRole = isset($_SESSION['user']['ID_Role']) ? intval($_SESSION['user']['ID_Role']) : 0;
if (!isset($_SESSION['user']) || !in_array($userRole, [1, 3])) {
    header('Location: index.php');
    exit;
}

// Bao gồm header admin
include 'includes/admin-header.php';
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-users"></i>
                Quản lý khách hàng
            </h1>
            <p class="page-subtitle">Quản lý thông tin tài khoản khách hàng</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number" id="totalCustomers">0</div>
                <div class="stat-label">Tổng khách hàng</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number" id="activeCustomers">0</div>
                <div class="stat-label">Hoạt động</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-number" id="pendingCustomers">0</div>
                <div class="stat-label">Chờ xác thực</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon rejected">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-number" id="blockedCustomers">0</div>
                <div class="stat-label">Bị khóa</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tìm kiếm</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="Nhập tên, email hoặc số điện thoại...">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Hoạt động">Hoạt động</option>
                        <option value="Chưa xác minh">Chưa xác minh</option>
                        <option value="Bị khóa">Bị khóa</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Sắp xếp</label>
                    <select class="form-select" id="sortBy">
                        <option value="HoTen">Tên khách hàng</option>
                        <option value="Email">Email</option>
                        <option value="NgayTao">Ngày tạo</option>
                        <option value="SoDienThoai">Số điện thoại</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                        <button class="btn btn-outline-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Xóa
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
                    Danh sách khách hàng
                </h3>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> Thêm khách hàng
                    </button>
                    
        </div>
    </div>
    
        <div class="table-responsive">
            <table class="table table-hover" id="customersTable">
                <thead>
                    <tr>
                            <th>ID</th>
                            <th>Thông tin khách hàng</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
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

        <!-- Add/Edit Customer Modal -->
        <div class="modal fade" id="customerModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
      <div class="modal-header">
                        <h5 class="modal-title" id="customerModalTitle">
                            <i class="fas fa-plus"></i>
                            Thêm khách hàng mới
                        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
                        <form id="customerForm">
                            <input type="hidden" id="customerId" name="id">
                            
                            <div class="row">
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="customerName" name="HoTen" required>
        </div>
                                </div>
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="customerPhone" name="SoDienThoai" required>
        </div>
        </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="customerEmail" name="Email" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu <span class="text-danger">*</span> <span class="text-muted" id="passwordHint">(bắt buộc cho khách hàng mới)</span></label>
            <div class="input-group">
                                            <input type="password" class="form-control" id="customerPassword" name="MatKhau">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('customerPassword')">
                                                <i class="fas fa-eye" id="customerPasswordIcon"></i>
                </button>
            </div>
        </div>
        </div>
        </div>
                            
                            <div class="row">
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Ngày sinh</label>
                                        <input type="date" class="form-control" id="customerBirthday" name="NgaySinh">
            </div>
        </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                        <select class="form-select" id="customerStatus" name="TrangThai" required>
                                            <option value="Hoạt động">Hoạt động</option>
                                            <option value="Chưa xác minh">Chưa xác minh</option>
                                            <option value="Bị khóa">Bị khóa</option>
                                        </select>
      </div>
  </div>
</div>
                            
        <div class="mb-3">
                                <label class="form-label">Địa chỉ</label>
                                <textarea class="form-control" id="customerAddress" name="DiaChi" rows="2"></textarea>
        </div>
                            
                        </form>
        </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="saveCustomer()">
                            <i class="fas fa-save"></i> Lưu
                        </button>
            </div>
        </div>
        </div>
        </div>

        <!-- View Customer Modal -->
        <div class="modal fade" id="viewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eye"></i>
                            Chi tiết khách hàng
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
                    <div class="modal-body" id="viewModalBody">
                        <!-- Content will be loaded via AJAX -->
      </div>
      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
</div>
        </div>


    <style>
        /* Loại bỏ hoàn toàn backdrop của modal */
        .modal-backdrop {
            display: none !important;
        }
        
        /* Đảm bảo body không bị khóa khi modal mở */
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }
        
        /* Tùy chọn: Thêm hiệu ứng overlay nhẹ nếu muốn có chỉ báo trực quan */
        .modal.show {
            background-color: rgba(0, 0, 0, 0.1);
        }
    </style>

    <script>
        let customersTable;
        let currentFilters = {};

        // Khởi tạo trang
        document.addEventListener('DOMContentLoaded', function() {
            initializeDataTable();
            loadStatistics();
            setupEventListeners();
        });

        function initializeDataTable() {
            // Kiểm tra DataTables có sẵn không
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables not available');
                AdminPanel.showError('DataTables không khả dụng');
                return;
            }

            try {
                customersTable = $('#customersTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '../src/controllers/customeredit.php',
                    type: 'GET',
                    data: function(d) {
                        d.action = 'get_customers';
                        return $.extend(d, currentFilters);
                    },
                    dataSrc: function(json) {
                        console.log('DataTable response:', json);
                        // Controller trả về {success: true, customers: [...]}
                        if (json && json.success && Array.isArray(json.customers)) {
                            return json.customers;
                        } else if (Array.isArray(json)) {
                            return json;
                        } else if (json && Array.isArray(json.data)) {
                            return json.data;
                        } else {
                            console.error('Invalid data format:', json);
                            return [];
                        }
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable AJAX Error:', error);
                        AdminPanel.showError('Không thể tải dữ liệu khách hàng');
                    }
                },
                columns: [
                    { data: 'ID_User', className: 'text-center' },
                    { 
                        data: 'HoTen',
                        render: function(data, type, row) {
                            return `
                                <div>
                                    <strong>${data || 'N/A'}</strong>
                                </div>
                            `;
                        }
                    },
                    { data: 'Email' },
                    { data: 'SoDienThoai' },
                    { 
                        data: 'TrangThai',
                        render: function(data) {
                            if (!data) return '<span class="status-badge status-unknown">Không xác định</span>';
                            
                            const statusMap = {
                                'Hoạt động': { class: 'approved', text: 'Hoạt động' },
                                'Chưa xác minh': { class: 'pending', text: 'Chưa xác minh' },
                                'Bị khóa': { class: 'rejected', text: 'Bị khóa' },
                                'Active': { class: 'approved', text: 'Hoạt động' },
                                'Inactive': { class: 'rejected', text: 'Bị khóa' },
                                'Pending': { class: 'pending', text: 'Chưa xác minh' }
                            };
                            const status = statusMap[data] || { class: 'pending', text: data };
                            return `<span class="status-badge status-${status.class}">${status.text}</span>`;
                        }
                    },
                    { 
                        data: 'NgayTao',
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
                                    <button class="btn btn-info btn-sm" onclick="viewCustomer(${row.ID_User})" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="editCustomer(${row.ID_User})" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteCustomer(${row.ID_User})" title="Xóa">
                                        <i class="fas fa-trash"></i>
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
                // Ẩn thanh tìm kiếm và thông tin hiển thị của DataTable
                dom: 'rtip',
                info: false, // Ẩn thông tin "Hiển thị X dữ liệu"
                paging: false // Ẩn phân trang (hiển thị tất cả dữ liệu)
            });
            } catch (error) {
                console.error('Error initializing DataTable:', error);
                AdminPanel.showError('Lỗi khởi tạo bảng dữ liệu');
            }
        }

        function loadStatistics() {
            AdminPanel.makeAjaxRequest('../src/controllers/customeredit.php', {
                action: 'get_customer_stats'
            })
            .then(response => {
                if (response.success) {
                    $('#totalCustomers').text(response.stats.total || 0);
                    $('#activeCustomers').text(response.stats.active || 0);
                    $('#pendingCustomers').text(response.stats.pending || 0);
                    $('#blockedCustomers').text(response.stats.blocked || 0);
                }
            })
            .catch(error => {
                console.error('Statistics load error:', error);
            });
        }

        function setupEventListeners() {
            // Ô tìm kiếm với debounce
            let searchTimeout;
            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyFilters();
                }, 300);
            });

            // Sự kiện thay đổi bộ lọc
            $('#statusFilter, #sortBy').on('change', function() {
                applyFilters();
            });
        }

        function applyFilters() {
            const searchValue = $('#searchInput').val();
            const statusFilter = $('#statusFilter').val();
            const sortBy = $('#sortBy').val();
            
            // Áp dụng tìm kiếm vào DataTable
            customersTable.search(searchValue).draw();
            
            // Áp dụng bộ lọc cột
            if (statusFilter) {
                customersTable.column(4).search(statusFilter);
            } else {
                customersTable.column(4).search('');
            }
            
            // Áp dụng sắp xếp
            if (sortBy === 'HoTen') {
                customersTable.order([1, 'asc']).draw();
            } else if (sortBy === 'Email') {
                customersTable.order([2, 'asc']).draw();
            } else if (sortBy === 'NgayTao') {
                customersTable.order([5, 'desc']).draw();
            } else if (sortBy === 'SoDienThoai') {
                customersTable.order([3, 'asc']).draw();
            }
            
            // Vẽ lại bảng
            customersTable.draw();
        }

        function clearFilters() {
            $('#searchInput').val('');
            $('#statusFilter').val('');
            $('#sortBy').val('HoTen');
            
            // Xóa tất cả bộ lọc DataTable
            customersTable.search('');
            customersTable.columns().search('');
            customersTable.order([0, 'desc']).draw();
        }

        function clearSearch() {
            $('#searchInput').val('');
            applyFilters();
        }

        function showAddModal() {
            $('#customerForm')[0].reset();
            $('#customerId').val('');
            $('#customerPassword').attr('required', 'required'); // Password required for new customer
            $('#passwordHint').text('(bắt buộc cho khách hàng mới)').removeClass('text-muted').addClass('text-danger');
            $('#customerModalTitle').html('<i class="fas fa-plus"></i> Thêm khách hàng mới');
            
            const modal = new bootstrap.Modal(document.getElementById('customerModal'));
            modal.show();
        }

        function editCustomer(id) {
            const formData = new FormData();
            formData.append('action', 'get_customer_details');
            formData.append('id', id);
            
            AdminPanel.makeAjaxRequest('../src/controllers/customeredit.php', formData, 'POST')
            .then(response => {
                if (response && response.success && response.customer && response.customer.ID_User) {
                    const customer = response.customer;
                    $('#customerId').val(customer.ID_User);
                    $('#customerName').val(customer.HoTen);
                    $('#customerEmail').val(customer.Email);
                    $('#customerPassword').val(''); // Don't show password
                    $('#customerPhone').val(customer.SoDienThoai);
                    $('#customerBirthday').val(customer.NgaySinh);
                    $('#customerAddress').val(customer.DiaChi);
                    $('#customerStatus').val(customer.TrangThai || 'Hoạt động');
                    
                    $('#customerPassword').removeAttr('required'); // Password not required for edit
                    $('#passwordHint').text('(để trống nếu không đổi)').removeClass('text-danger').addClass('text-muted');
                    $('#customerModalTitle').html('<i class="fas fa-edit"></i> Chỉnh sửa khách hàng');
                    
                    const modal = new bootstrap.Modal(document.getElementById('customerModal'));
                    modal.show();
                } else {
                    AdminPanel.showError(response.message || 'Không thể tải thông tin khách hàng');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi tải thông tin khách hàng');
            });
        }

        function viewCustomer(id) {
            AdminPanel.showLoading('#viewModalBody');
            
            const modal = new bootstrap.Modal(document.getElementById('viewModal'));
            modal.show();

            const formData = new FormData();
            formData.append('action', 'get_customer_details');
            formData.append('id', id);
            
            AdminPanel.makeAjaxRequest('../src/controllers/customeredit.php', formData, 'POST')
            .then(response => {
                if (response && response.success && response.customer) {
                    const customer = response.customer;
                    const statusMap = {
                        'Hoạt động': { class: 'approved', text: 'Hoạt động' },
                        'Chưa xác minh': { class: 'pending', text: 'Chưa xác minh' },
                        'Bị khóa': { class: 'rejected', text: 'Bị khóa' },
                        'Active': { class: 'approved', text: 'Hoạt động' },
                        'Inactive': { class: 'rejected', text: 'Bị khóa' },
                        'Pending': { class: 'pending', text: 'Chưa xác minh' }
                    };
                    const statusValue = customer.TrangThai || 'Chưa xác định';
                    const status = statusMap[statusValue] || { class: 'pending', text: statusValue };
                    
                    $('#viewModalBody').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-user"></i> Thông tin cá nhân</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>ID Khách hàng:</strong></td><td>#${customer.ID_KhachHang || customer.ID_User || 'N/A'}</td></tr>
                                    <tr><td><strong>ID Tài khoản:</strong></td><td>#${customer.ID_User || 'N/A'}</td></tr>
                                    <tr><td><strong>Họ tên:</strong></td><td>${customer.HoTen || 'Chưa cập nhật'}</td></tr>
                                    <tr><td><strong>Email:</strong></td><td>${customer.Email || 'Chưa có'}</td></tr>
                                    <tr><td><strong>Số điện thoại:</strong></td><td>${customer.SoDienThoai || 'Chưa có'}</td></tr>
                                    <tr><td><strong>Ngày sinh:</strong></td><td>${customer.NgaySinh ? AdminPanel.formatDate(customer.NgaySinh, 'dd/mm/yyyy') : 'Không có'}</td></tr>
                                    <tr><td><strong>Trạng thái:</strong></td><td><span class="status-badge status-${status.class}">${status.text}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle"></i> Thông tin khác</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Địa chỉ:</strong></td><td>${customer.DiaChi || 'Không có địa chỉ'}</td></tr>
                                    <tr><td><strong>Ngày đăng ký:</strong></td><td>${customer.NgayDangKy ? AdminPanel.formatDate(customer.NgayDangKy, 'dd/mm/yyyy hh:mm') : (customer.NgayTao ? AdminPanel.formatDate(customer.NgayTao, 'dd/mm/yyyy hh:mm') : 'Không có')}</td></tr>
                                    <tr><td><strong>Ngày tạo hồ sơ:</strong></td><td>${customer.NgayTao ? AdminPanel.formatDate(customer.NgayTao, 'dd/mm/yyyy hh:mm') : 'Không có'}</td></tr>
                                    <tr><td><strong>Cập nhật lần cuối:</strong></td><td>${customer.NgayCapNhat ? AdminPanel.formatDate(customer.NgayCapNhat, 'dd/mm/yyyy hh:mm') : 'Không có'}</td></tr>
                                </table>
                            </div>
                        </div>
                    `);
                } else {
                    $('#viewModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.message || 'Không thể tải chi tiết khách hàng'}
                        </div>
                    `);
                }
            })
            .catch(error => {
                console.error('Error loading customer details:', error);
                $('#viewModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Không thể tải chi tiết khách hàng: ${error.message || 'Lỗi không xác định'}
                    </div>
                `);
            });
        }

        function saveCustomer() {
            const isEdit = $('#customerId').val() !== '';
            
            // Đối với chế độ chỉnh sửa, xóa thuộc tính required khỏi trường mật khẩu
            if (isEdit) {
                $('#customerPassword').removeAttr('required');
            } else {
                $('#customerPassword').attr('required', 'required');
            }
            
            // Xác thực bổ sung cho mật khẩu khi thêm khách hàng mới
            if (!isEdit && !$('#customerPassword').val().trim()) {
                AdminPanel.showError('Mật khẩu không được để trống khi thêm khách hàng mới');
                $('#customerPassword').focus();
                return;
            }
            
            if (!AdminPanel.validateForm('customerForm')) {
                return;
            }
        
            const formData = new FormData(document.getElementById('customerForm'));
            const action = isEdit ? 'update_customer' : 'add_customer';
            
            // Thêm action vào form data
            formData.append('action', action);

            AdminPanel.makeAjaxRequest('../src/controllers/customeredit.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess(isEdit ? 'Đã cập nhật khách hàng thành công' : 'Đã thêm khách hàng thành công');
                    bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
                    customersTable.ajax.reload();
                    loadStatistics();
            } else {
                    AdminPanel.showError(response.message || 'Có lỗi xảy ra khi lưu khách hàng');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi lưu khách hàng');
            });
        }

        function deleteCustomer(id) {
            AdminPanel.sweetConfirm(
                'Xác nhận xóa',
                'Bạn có chắc muốn xóa khách hàng này? Hành động này không thể hoàn tác.',
                () => {
                    const formData = new FormData();
                    formData.append('action', 'delete_customer');
                    formData.append('id', id);
                    
                    AdminPanel.makeAjaxRequest('../src/controllers/customeredit.php', formData, 'POST')
                    .then(response => {
                        if (response.success) {
                            AdminPanel.showSuccess('Đã xóa khách hàng thành công');
                            customersTable.ajax.reload();
                            loadStatistics();
            } else {
                            AdminPanel.showError(response.message || 'Có lỗi xảy ra khi xóa khách hàng');
            }
                    })
                    .catch(error => {
                        AdminPanel.showError('Có lỗi xảy ra khi xóa khách hàng');
    });
}
            );
}

        function togglePassword(inputId) {
    const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + 'Icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                input.style.textDecoration = 'line-through';
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                input.style.textDecoration = 'none';
            }
        }

        

        // Tự động làm mới mỗi 30 giây
        setInterval(() => {
            loadStatistics();
        }, 30000);
    </script>

<?php include 'includes/admin-footer.php'; ?>

