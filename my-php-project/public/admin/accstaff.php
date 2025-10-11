<?php
// Include admin header
include 'includes/admin-header.php';
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-user-tie"></i>
                Quản lý nhân viên
            </h1>
            <p class="page-subtitle">Quản lý thông tin tài khoản nhân viên</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-number" id="totalStaff">0</div>
                <div class="stat-label">Tổng nhân viên</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number" id="activeStaff">0</div>
                <div class="stat-label">Hoạt động</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-number" id="pendingStaff">0</div>
                <div class="stat-label">Chờ xác thực</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon rejected">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-number" id="blockedStaff">0</div>
                <div class="stat-label">Bị khóa</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <div class="form-group">
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
                
                <div class="form-group">
                    <label class="form-label">Vai trò</label>
                    <select class="form-select" id="roleFilter">
                        <option value="">Tất cả vai trò</option>
                        <option value="1">Quản trị viên</option>
                        <option value="2">Quản lý tổ chức</option>
                        <option value="3">Quản lý sự kiện</option>
                        <option value="4">Nhân viên</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Hoạt động">Hoạt động</option>
                        <option value="Chưa xác minh">Chưa xác minh</option>
                        <option value="Bị khóa">Bị khóa</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Sắp xếp</label>
                    <select class="form-select" id="sortBy">
                        <option value="HoTen">Tên nhân viên</option>
                        <option value="Email">Email</option>
                        <option value="ID_Role">Vai trò</option>
                        <option value="NgayTao">Ngày tạo</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                    <button class="btn btn-outline-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Xóa lọc
                    </button>
    </div>
</div>
            </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">
                    <i class="fas fa-list"></i>
                    Danh sách nhân viên
                </h3>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> Thêm nhân viên
                    </button>
                    <button class="btn btn-info" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i> Làm mới
            </button>
        </div>
    </div>
    
        <div class="table-responsive">
            <table class="table table-hover" id="staffTable">
                <thead>
                    <tr>
                            <th>ID</th>
                            <th>Thông tin nhân viên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Vai trò</th>
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

        <!-- Add/Edit Staff Modal -->
        <div class="modal fade" id="staffModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
      <div class="modal-header">
                        <h5 class="modal-title" id="staffModalTitle">
                            <i class="fas fa-plus"></i>
                            Thêm nhân viên mới
                        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
                        <form id="staffForm">
                            <input type="hidden" id="staffId" name="id">
                            
                            <div class="row">
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="staffName" name="HoTen" required>
        </div>
                                </div>
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="staffEmail" name="Email" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mật khẩu <span class="text-danger">*</span> <span class="text-muted" id="passwordHint">(bắt buộc cho nhân viên mới)</span></label>
            <div class="input-group">
                                            <input type="password" class="form-control" id="staffPassword" name="MatKhau">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('staffPassword')">
                                                <i class="fas fa-eye" id="staffPasswordIcon"></i>
                </button>
            </div>
        </div>
        </div>
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="staffPhone" name="SoDienThoai" required>
        </div>
        </div>
        </div>
                            
                            <div class="row">
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Ngày sinh</label>
                                        <input type="date" class="form-control" id="staffBirthday" name="NgaySinh">
            </div>
        </div>
                                <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Chức vụ</label>
                                        <input type="text" class="form-control" id="staffPosition" name="ChucVu">
        </div>
                                </div>
                            </div>
                            
        <div class="mb-3">
                                <label class="form-label">Địa chỉ</label>
                                <textarea class="form-control" id="staffAddress" name="DiaChi" rows="2"></textarea>
        </div>
                            
                            <div class="row">
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                                        <select class="form-select" id="staffRole" name="ID_Role" required>
                                            <option value="">Chọn vai trò</option>
                                            <option value="1">Quản trị viên</option>
                                            <option value="2">Quản lý tổ chức</option>
                                            <option value="3">Quản lý sự kiện</option>
                                            <option value="4">Nhân viên</option>
                </select>
            </div>
        </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                        <select class="form-select" id="staffStatus" name="TrangThai" required>
                                            <option value="Hoạt động">Hoạt động</option>
                                            <option value="Chưa xác minh">Chưa xác minh</option>
                                            <option value="Bị khóa">Bị khóa</option>
                                        </select>
      </div>
  </div>
</div>

                            <div class="row">
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Lương (VNĐ)</label>
                                        <input type="number" class="form-control" id="staffSalary" name="Luong" min="0" step="1000">
        </div>
            </div>
                                <div class="col-md-6">
        <div class="mb-3">
                                        <label class="form-label">Ngày vào làm</label>
                                        <input type="date" class="form-control" id="staffStartDate" name="NgayVaoLam">
        </div>
        </div>
        </div>
                            
                        </form>
        </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="saveStaff()">
                            <i class="fas fa-save"></i> Lưu
                        </button>
            </div>
        </div>
        </div>
        </div>

        <!-- View Staff Modal -->
        <div class="modal fade" id="viewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eye"></i>
                            Chi tiết nhân viên
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

<script>
        let staffTable;
        let currentFilters = {};

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeDataTable();
            loadStatistics();
            setupEventListeners();
        });

        function initializeDataTable() {
            // Check if DataTables is available
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables not available');
                AdminPanel.showError('DataTables không khả dụng');
                return;
            }

            try {
                staffTable = $('#staffTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '../../src/controllers/staffedit.php',
                    type: 'GET',
                    data: function(d) {
                        d.action = 'list';
                        return $.extend(d, currentFilters);
                    },
                    dataSrc: function(json) {
                        // Controller trả về array trực tiếp
                        if (Array.isArray(json)) {
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
                        AdminPanel.showError('Không thể tải dữ liệu nhân viên');
                    }
                },
                columns: [
                    { data: 'ID_User', className: 'text-center' },
                    { 
                        data: 'HoTen',
                        render: function(data, type, row) {
                            return `
                                <div>
                                    <strong>${data}</strong>
                                    ${row.ChucVu ? `<br><small class="text-muted">${row.ChucVu}</small>` : ''}
                                </div>
                            `;
                        }
                    },
                    { data: 'Email' },
                    { data: 'SoDienThoai' },
                    { 
                        data: 'ID_Role',
                        render: function(data) {
                            const roleMap = {
                                1: 'Quản trị viên',
                                2: 'Quản lý tổ chức',
                                3: 'Quản lý sự kiện',
                                4: 'Nhân viên'
                            };
                            return roleMap[data] || 'Không xác định';
                        }
                    },
                    { 
                        data: 'TrangThai',
                        render: function(data) {
                            if (!data) return '<span class="status-badge status-unknown">Không xác định</span>';
                            
                            const statusMap = {
                                'Hoạt động': { class: 'approved', text: 'Hoạt động' },
                                'Chưa xác minh': { class: 'pending', text: 'Chưa xác minh' },
                                'Bị khóa': { class: 'rejected', text: 'Bị khóa' }
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
                                    <button class="btn btn-info btn-sm" onclick="viewStaff(${row.ID_User})" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="editStaff(${row.ID_User})" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteStaff(${row.ID_User})" title="Xóa">
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
                }
            });
            } catch (error) {
                console.error('Error initializing DataTable:', error);
                AdminPanel.showError('Lỗi khởi tạo bảng dữ liệu');
            }
        }

        function loadStatistics() {
            AdminPanel.makeAjaxRequest('../../src/controllers/staffedit.php', {
                action: 'get_staff_stats'
            })
            .then(response => {
                if (response.success) {
                    $('#totalStaff').text(response.stats.total || 0);
                    $('#activeStaff').text(response.stats.active || 0);
                    $('#pendingStaff').text(response.stats.pending || 0);
                    $('#blockedStaff').text(response.stats.blocked || 0);
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
            $('#roleFilter, #statusFilter, #sortBy').on('change', function() {
                applyFilters();
            });
        }

        function applyFilters() {
            currentFilters = {
                search: $('#searchInput').val(),
                role: $('#roleFilter').val(),
                status: $('#statusFilter').val(),
                sort_by: $('#sortBy').val()
            };

            // Remove empty filters
            Object.keys(currentFilters).forEach(key => {
                if (!currentFilters[key]) {
                    delete currentFilters[key];
                }
            });

            staffTable.ajax.reload();
        }

        function clearFilters() {
            $('#searchInput').val('');
            $('#roleFilter').val('');
            $('#statusFilter').val('');
            $('#sortBy').val('HoTen');
            currentFilters = {};
            staffTable.ajax.reload();
        }

        function clearSearch() {
            $('#searchInput').val('');
            applyFilters();
        }

        function showAddModal() {
            $('#staffForm')[0].reset();
            $('#staffId').val('');
            $('#staffPassword').attr('required', 'required'); // Password required for new staff
            $('#passwordHint').text('(bắt buộc cho nhân viên mới)').removeClass('text-muted').addClass('text-danger');
            $('#staffModalTitle').html('<i class="fas fa-plus"></i> Thêm nhân viên mới');
            
            const modal = new bootstrap.Modal(document.getElementById('staffModal'));
            modal.show();
        }

        function editStaff(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            AdminPanel.makeAjaxRequest('../../src/controllers/staffedit.php', formData, 'POST')
            .then(response => {
                if (response && response.ID_User) {
                    const staff = response;
                    $('#staffId').val(staff.ID_User);
                    $('#staffName').val(staff.HoTen);
                    $('#staffEmail').val(staff.Email);
                    $('#staffPassword').val(''); // Don't show password
                    $('#staffPhone').val(staff.SoDienThoai);
                    $('#staffBirthday').val(staff.NgaySinh);
                    $('#staffPosition').val(staff.ChucVu);
                    $('#staffAddress').val(staff.DiaChi);
                    $('#staffRole').val(staff.ID_Role);
                    $('#staffStatus').val(staff.TrangThai);
                    $('#staffSalary').val(staff.Luong);
                    $('#staffStartDate').val(staff.NgayVaoLam);
                    
                    $('#staffPassword').removeAttr('required'); // Password not required for edit
                    $('#passwordHint').text('(để trống nếu không đổi)').removeClass('text-danger').addClass('text-muted');
                    $('#staffModalTitle').html('<i class="fas fa-edit"></i> Chỉnh sửa nhân viên');
                    
                    const modal = new bootstrap.Modal(document.getElementById('staffModal'));
                    modal.show();
                } else {
                    AdminPanel.showError(response.message || 'Không thể tải thông tin nhân viên');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi tải thông tin nhân viên');
            });
        }

        function viewStaff(id) {
            AdminPanel.showLoading('#viewModalBody');
            
            const modal = new bootstrap.Modal(document.getElementById('viewModal'));
            modal.show();

            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            AdminPanel.makeAjaxRequest('../../src/controllers/staffedit.php', formData, 'POST')
            .then(response => {
                if (response && response.ID_User) {
                    const staff = response;
                    const roleMap = {
                        1: 'Quản trị viên',
                        2: 'Quản lý tổ chức',
                        3: 'Quản lý sự kiện',
                        4: 'Nhân viên'
                    };
                    const statusMap = {
                        'Hoạt động': { class: 'approved', text: 'Hoạt động' },
                        'Chưa xác minh': { class: 'pending', text: 'Chưa xác minh' },
                        'Bị khóa': { class: 'rejected', text: 'Bị khóa' }
                    };
                    const status = statusMap[staff.TrangThai] || { class: 'pending', text: staff.TrangThai };
                    
                    $('#viewModalBody').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-user-tie"></i> Thông tin cá nhân</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Họ tên:</strong></td><td>${staff.HoTen}</td></tr>
                                    <tr><td><strong>Email:</strong></td><td>${staff.Email}</td></tr>
                                    <tr><td><strong>Số điện thoại:</strong></td><td>${staff.SoDienThoai}</td></tr>
                                    <tr><td><strong>Ngày sinh:</strong></td><td>${staff.NgaySinh ? AdminPanel.formatDate(staff.NgaySinh, 'dd/mm/yyyy') : 'Không có'}</td></tr>
                                    <tr><td><strong>Chức vụ:</strong></td><td>${staff.ChucVu || 'Không có'}</td></tr>
                                    <tr><td><strong>Trạng thái:</strong></td><td><span class="status-badge status-${status.class}">${status.text}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle"></i> Thông tin khác</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Vai trò:</strong></td><td>${roleMap[staff.ID_Role] || 'Không xác định'}</td></tr>
                                    <tr><td><strong>Địa chỉ:</strong></td><td>${staff.DiaChi || 'Không có địa chỉ'}</td></tr>
                                    <tr><td><strong>Lương:</strong></td><td>${staff.Luong ? AdminPanel.formatCurrency(staff.Luong) : 'Không có'}</td></tr>
                                    <tr><td><strong>Ngày vào làm:</strong></td><td>${staff.NgayVaoLam ? AdminPanel.formatDate(staff.NgayVaoLam, 'dd/mm/yyyy') : 'Không có'}</td></tr>
                                    <tr><td><strong>Ngày tạo:</strong></td><td>${AdminPanel.formatDate(staff.NgayTao, 'dd/mm/yyyy hh:mm')}</td></tr>
                                    <tr><td><strong>Cập nhật:</strong></td><td>${AdminPanel.formatDate(staff.NgayCapNhat, 'dd/mm/yyyy hh:mm')}</td></tr>
                                </table>
                            </div>
                        </div>
                    `);
                } else {
                    $('#viewModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.message || 'Không thể tải chi tiết nhân viên'}
                        </div>
                    `);
                }
            })
            .catch(error => {
                $('#viewModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải chi tiết nhân viên
                    </div>
                `);
            });
        }

        function saveStaff() {
            const isEdit = $('#staffId').val() !== '';
            
            // For edit mode, remove required attribute from password field
            if (isEdit) {
                $('#staffPassword').removeAttr('required');
            } else {
                $('#staffPassword').attr('required', 'required');
            }
            
            // Additional validation for password when adding new staff
            if (!isEdit && !$('#staffPassword').val().trim()) {
                AdminPanel.showError('Mật khẩu không được để trống khi thêm nhân viên mới');
                $('#staffPassword').focus();
                return;
            }
            
            if (!AdminPanel.validateForm('staffForm')) {
                return;
            }
        
            const formData = new FormData(document.getElementById('staffForm'));
            const action = isEdit ? 'update_staff' : 'add_staff';
            
            // Add action to form data
            formData.append('action', action);

            AdminPanel.makeAjaxRequest('../../src/controllers/staffedit.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess(isEdit ? 'Đã cập nhật nhân viên thành công' : 'Đã thêm nhân viên thành công');
                    bootstrap.Modal.getInstance(document.getElementById('staffModal')).hide();
                    staffTable.ajax.reload();
                    loadStatistics();
            } else {
                    AdminPanel.showError(response.message || 'Có lỗi xảy ra khi lưu nhân viên');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi lưu nhân viên');
            });
        }

        function deleteStaff(id) {
            AdminPanel.sweetConfirm(
                'Xác nhận xóa',
                'Bạn có chắc muốn xóa nhân viên này? Hành động này không thể hoàn tác.',
                () => {
                    const formData = new FormData();
                    formData.append('action', 'delete_staff');
                    formData.append('id', id);
                    
                    AdminPanel.makeAjaxRequest('../../src/controllers/staffedit.php', formData, 'POST')
                    .then(response => {
                        if (response.success) {
                            AdminPanel.showSuccess('Đã xóa nhân viên thành công');
                            staffTable.ajax.reload();
                            loadStatistics();
            } else {
                            AdminPanel.showError(response.message || 'Có lỗi xảy ra khi xóa nhân viên');
            }
                    })
                    .catch(error => {
                        AdminPanel.showError('Có lỗi xảy ra khi xóa nhân viên');
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

        function refreshData() {
            staffTable.ajax.reload();
            loadStatistics();
            AdminPanel.showSuccess('Đã làm mới dữ liệu');
        }

        // Auto refresh every 30 seconds
        setInterval(() => {
            loadStatistics();
        }, 30000);
    </script>

<?php include 'includes/admin-footer.php'; ?>