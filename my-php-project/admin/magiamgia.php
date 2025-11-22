<?php
// Include admin header
include 'includes/admin-header.php';
?>
    
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-ticket-alt"></i>
                Quản lý mã giảm giá
            </h1>
            <p class="page-subtitle">Quản lý các mã giảm giá trong hệ thống</p>
        </div>
            
        <!-- Error/Success Messages -->
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        
        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-number" id="totalCodes">0</div>
                <div class="stat-label">Tổng mã giảm giá</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="activeCodes">0</div>
                <div class="stat-label">Đang hoạt động</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number" id="totalUsage">0</div>
                <div class="stat-label">Tổng lượt sử dụng</div>
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
                               placeholder="Nhập mã code, tên mã...">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả</option>
                        <option value="Hoạt động">Hoạt động</option>
                        <option value="Ngừng">Ngừng</option>
                        <option value="Hết hạn">Hết hạn</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Sắp xếp</label>
                    <select class="form-select" id="sortBy">
                        <option value="MaCode">Mã code</option>
                        <option value="TenMa">Tên mã</option>
                        <option value="NgayTao">Ngày tạo</option>
                        <option value="NgayBatDau">Ngày bắt đầu</option>
                    </select>
                </div>
                
                <div class="col-md-2">
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
                    Danh sách mã giảm giá
                </h3>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="showAddCodeModal()">
                        <i class="fas fa-plus"></i> Thêm mã giảm giá
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="magiamgiaTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mã code</th>
                            <th>Tên mã</th>
                            <th>Loại giảm giá</th>
                            <th>Giá trị</th>
                            <th>Số tiền tối thiểu</th>
                            <th>Số lần sử dụng</th>
                            <th>Thời gian hiệu lực</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    
    <!-- Add/Edit Code Modal -->
    <div class="modal fade" id="codeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="codeModalTitle">
                        <i class="fas fa-plus"></i> Thêm mã giảm giá
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="codeForm">
                        <input type="hidden" id="codeId" name="ID_MaGiamGia">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codeMaCode" class="form-label">Mã code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="codeMaCode" name="MaCode" required 
                                           placeholder="Ví dụ: landausudung" maxlength="50" pattern="[a-zA-Z0-9_]+">
                                    <small class="form-text text-muted">Mã code phải là duy nhất, không có khoảng trắng, chỉ chứa chữ cái, số và dấu gạch dưới</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codeTenMa" class="form-label">Tên mã <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="codeTenMa" name="TenMa" required 
                                           placeholder="Ví dụ: Lần đầu sử dụng">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="codeMoTa" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="codeMoTa" name="MoTa" rows="3" 
                                      placeholder="Mô tả chi tiết về mã giảm giá..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codeLoaiGiamGia" class="form-label">Loại giảm giá <span class="text-danger">*</span></label>
                                    <select class="form-select" id="codeLoaiGiamGia" name="LoaiGiamGia" required onchange="updateDiscountType()">
                                        <option value="Phần trăm">Phần trăm (%)</option>
                                        <option value="Số tiền">Số tiền (VNĐ)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codeGiaTriGiamGia" class="form-label">
                                        <span id="discountLabel">Giá trị giảm (%)</span> <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="codeGiaTriGiamGia" name="GiaTriGiamGia" 
                                           min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codeSoTienToiThieu" class="form-label">Số tiền đơn hàng tối thiểu (VNĐ)</label>
                                    <input type="number" class="form-control" id="codeSoTienToiThieu" name="SoTienToiThieu" 
                                           min="0" step="1000" value="0">
                                    <small class="form-text text-muted">Để 0 nếu không có yêu cầu</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codeSoLanSuDungToiDa" class="form-label">Số lần sử dụng tối đa/tài khoản</label>
                                    <input type="number" class="form-control" id="codeSoLanSuDungToiDa" name="SoLanSuDungToiDa" 
                                           min="1" placeholder="Để trống = không giới hạn">
                                    <small class="form-text text-muted">Ví dụ: 1 lần cho mã "landausudung"</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codeSoLanSuDungTongCong" class="form-label">Số lần sử dụng tổng cộng tối đa</label>
                                    <input type="number" class="form-control" id="codeSoLanSuDungTongCong" name="SoLanSuDungTongCong" 
                                           min="1" placeholder="Để trống = không giới hạn">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codeTrangThai" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select class="form-select" id="codeTrangThai" name="TrangThai" required>
                                        <option value="Hoạt động">Hoạt động</option>
                                        <option value="Ngừng">Ngừng</option>
                                        <option value="Hết hạn">Hết hạn</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codeNgayBatDau" class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="codeNgayBatDau" name="NgayBatDau" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codeNgayKetThuc" class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="codeNgayKetThuc" name="NgayKetThuc" required>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveCode()">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Code Modal -->
    <div class="modal fade" id="viewCodeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewCodeModalTitle">
                        <i class="fas fa-eye"></i> Chi tiết mã giảm giá
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewCodeModalBody">
                    <!-- Code details will be populated here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .modal-backdrop {
            display: none !important;
        }
        
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }
        
        .modal.show {
            background-color: rgba(0, 0, 0, 0.1);
        }
    </style>

    <script>
        let codesTable;
        let currentFilters = {};

        document.addEventListener('DOMContentLoaded', function() {
            initializeDataTable();
            loadStatistics();
            setupEventListeners();
        });

        function initializeDataTable() {
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables not available');
                AdminPanel.showError('DataTables không khả dụng');
                return;
            }

            try {
                codesTable = $('#magiamgiaTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '../src/controllers/magiamgia-controller.php',
                        type: 'GET',
                        data: function(d) {
                            return $.extend({
                                action: 'get_all'
                            }, currentFilters);
                        },
                        dataSrc: function(json) {
                            if (json.success && json.codes) {
                                return json.codes;
                            } else {
                                console.error('Invalid data format:', json);
                                return [];
                            }
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTable AJAX Error:', error);
                            AdminPanel.showError('Không thể tải dữ liệu mã giảm giá');
                        }
                    },
                    columns: [
                        { 
                            data: 'ID_MaGiamGia', 
                            className: 'text-center',
                            render: function(data) {
                                return `<strong>#${data}</strong>`;
                            }
                        },
                        { 
                            data: 'MaCode',
                            render: function(data) {
                                return `<code class="text-primary">${data || 'N/A'}</code>`;
                            }
                        },
                        { 
                            data: 'TenMa',
                            render: function(data) {
                                return `<strong>${data || 'N/A'}</strong>`;
                            }
                        },
                        { 
                            data: 'LoaiGiamGia',
                            render: function(data) {
                                const badge = data === 'Phần trăm' ? 'bg-info' : 'bg-success';
                                return `<span class="badge ${badge}">${data || 'N/A'}</span>`;
                            }
                        },
                        { 
                            data: null,
                            render: function(data, type, row) {
                                if (row.LoaiGiamGia === 'Phần trăm') {
                                    return `<strong>${row.GiaTriGiamGia}%</strong>`;
                                } else {
                                    return `<strong>${AdminPanel.formatCurrency(row.GiaTriGiamGia)}</strong>`;
                                }
                            }
                        },
                        { 
                            data: 'SoTienToiThieu',
                            render: function(data) {
                                return data > 0 ? AdminPanel.formatCurrency(data) : '<span class="text-muted">Không yêu cầu</span>';
                            }
                        },
                        { 
                            data: null,
                            render: function(data, type, row) {
                                const perUser = row.SoLanSuDungToiDa ? `${row.SoLanSuDungToiDa} lần/user` : 'Không giới hạn/user';
                                const total = row.SoLanSuDungTongCong ? `, Tổng: ${row.SoLanSuDungTongCong}` : ', Tổng: Không giới hạn';
                                return `<small>${perUser}${total}</small><br><small class="text-muted">Đã dùng: ${row.SoLanDaSuDung}</small>`;
                            }
                        },
                        { 
                            data: null,
                            render: function(data, type, row) {
                                const start = AdminPanel.formatDate(row.NgayBatDau, 'dd/mm/yyyy hh:mm');
                                const end = AdminPanel.formatDate(row.NgayKetThuc, 'dd/mm/yyyy hh:mm');
                                return `<small>Từ: ${start}<br>Đến: ${end}</small>`;
                            }
                        },
                        { 
                            data: 'TrangThai',
                            render: function(data) {
                                const badges = {
                                    'Hoạt động': 'bg-success',
                                    'Ngừng': 'bg-warning',
                                    'Hết hạn': 'bg-danger'
                                };
                                const badge = badges[data] || 'bg-secondary';
                                return `<span class="badge ${badge}">${data || 'N/A'}</span>`;
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            className: 'text-center',
                            render: function(data, type, row) {
                                return `
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm" onclick="viewCode(${row.ID_MaGiamGia})" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="editCode(${row.ID_MaGiamGia})" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteCode(${row.ID_MaGiamGia})" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                `;
                            }
                        }
                    ],
                    order: [[0, 'desc']],
                    language: {
                        processing: "Đang xử lý...",
                        search: "Tìm kiếm:",
                        lengthMenu: "Hiển thị _MENU_ bản ghi",
                        info: "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ bản ghi",
                        infoEmpty: "Không có dữ liệu",
                        infoFiltered: "(lọc từ _TOTAL_ bản ghi)",
                        zeroRecords: "Không tìm thấy kết quả",
                        paginate: {
                            first: "Đầu",
                            previous: "Trước",
                            next: "Sau",
                            last: "Cuối"
                        }
                    },
                    pageLength: 10,
                    responsive: true
                });
            } catch (error) {
                console.error('DataTable initialization error:', error);
                AdminPanel.showError('Lỗi khởi tạo bảng dữ liệu');
            }
        }

        function setupEventListeners() {
            $('#searchInput').on('keyup', function(e) {
                if (e.key === 'Enter') {
                    applyFilters();
                }
            });
        }

        function loadStatistics() {
            AdminPanel.makeAjaxRequest('../src/controllers/magiamgia-controller.php', {
                action: 'get_stats'
            })
            .then(response => {
                if (response.success) {
                    document.getElementById('totalCodes').textContent = response.stats.total || 0;
                    document.getElementById('activeCodes').textContent = response.stats.active || 0;
                    document.getElementById('totalUsage').textContent = response.stats.total_usage || 0;
                }
            })
            .catch(error => {
                console.error('Statistics load error:', error);
            });
        }

        function applyFilters() {
            currentFilters = {
                search: $('#searchInput').val(),
                status: $('#statusFilter').val(),
                sort_by: $('#sortBy').val()
            };
            
            if (codesTable) {
                codesTable.ajax.reload();
            }
        }

        function clearFilters() {
            $('#searchInput').val('');
            $('#statusFilter').val('');
            $('#sortBy').val('MaCode');
            currentFilters = {};
            
            if (codesTable) {
                codesTable.ajax.reload();
            }
        }

        function clearSearch() {
            $('#searchInput').val('');
            applyFilters();
        }

        function showAddCodeModal() {
            $('#codeForm')[0].reset();
            $('#codeId').val('');
            $('#codeModalTitle').html('<i class="fas fa-plus"></i> Thêm mã giảm giá');
            $('#codeModal').modal('show');
            updateDiscountType();
        }

        function updateDiscountType() {
            const loai = $('#codeLoaiGiamGia').val();
            const label = $('#discountLabel');
            const input = $('#codeGiaTriGiamGia');
            
            if (loai === 'Phần trăm') {
                label.text('Giá trị giảm (%)');
                input.attr('max', '100');
                input.attr('step', '0.01');
            } else {
                label.text('Giá trị giảm (VNĐ)');
                input.removeAttr('max');
                input.attr('step', '1000');
            }
        }

        function editCode(id) {
            AdminPanel.makeAjaxRequest('../src/controllers/magiamgia-controller.php', {
                action: 'get',
                id: id
            })
            .then(response => {
                if (response.success && response.code) {
                    const code = response.code;
                    
                    $('#codeId').val(code.ID_MaGiamGia);
                    $('#codeMaCode').val(code.MaCode);
                    $('#codeTenMa').val(code.TenMa);
                    $('#codeMoTa').val(code.MoTa || '');
                    $('#codeLoaiGiamGia').val(code.LoaiGiamGia);
                    $('#codeGiaTriGiamGia').val(code.GiaTriGiamGia);
                    $('#codeSoTienToiThieu').val(code.SoTienToiThieu || 0);
                    $('#codeSoLanSuDungToiDa').val(code.SoLanSuDungToiDa || '');
                    $('#codeSoLanSuDungTongCong').val(code.SoLanSuDungTongCong || '');
                    $('#codeTrangThai').val(code.TrangThai);
                    
                    // Format datetime for input
                    const ngayBatDau = code.NgayBatDau ? code.NgayBatDau.replace(' ', 'T').substring(0, 16) : '';
                    const ngayKetThuc = code.NgayKetThuc ? code.NgayKetThuc.replace(' ', 'T').substring(0, 16) : '';
                    $('#codeNgayBatDau').val(ngayBatDau);
                    $('#codeNgayKetThuc').val(ngayKetThuc);
                    
                    $('#codeModalTitle').html('<i class="fas fa-edit"></i> Chỉnh sửa mã giảm giá');
                    $('#codeModal').modal('show');
                    updateDiscountType();
                } else {
                    AdminPanel.showError(response.error || 'Không tìm thấy mã giảm giá');
                }
            })
            .catch(error => {
                AdminPanel.showError('Lỗi khi tải thông tin mã giảm giá');
            });
        }

        function viewCode(id) {
            AdminPanel.showLoading('#viewCodeModalBody');
            
            const modal = new bootstrap.Modal(document.getElementById('viewCodeModal'));
            modal.show();

            AdminPanel.makeAjaxRequest('../src/controllers/magiamgia-controller.php', {
                action: 'get',
                id: id
            })
            .then(response => {
                if (response.success && response.code) {
                    const code = response.code;
                    const discountValue = code.LoaiGiamGia === 'Phần trăm' 
                        ? `${code.GiaTriGiamGia}%` 
                        : AdminPanel.formatCurrency(code.GiaTriGiamGia);
                    
                    document.getElementById('viewCodeModalTitle').textContent = code.TenMa;
                    
                    // Build description HTML separately to avoid nested template literal issues
                    let descriptionHtml = '';
                    if (code.MoTa) {
                        descriptionHtml = `<div class="row mt-3"><div class="col-12"><h6><i class="fas fa-align-left text-primary"></i> Mô tả</h6><p class="text-muted">${code.MoTa}</p></div></div>`;
                    }
                    
                    document.getElementById('viewCodeModalBody').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle text-primary"></i> Thông tin cơ bản</h6>
                                <table class="table table-borderless">
                                    <tr><td><strong>ID:</strong></td><td>#${code.ID_MaGiamGia}</td></tr>
                                    <tr><td><strong>Mã code:</strong></td><td><code class="text-primary">${code.MaCode}</code></td></tr>
                                    <tr><td><strong>Tên mã:</strong></td><td>${code.TenMa}</td></tr>
                                    <tr><td><strong>Loại giảm giá:</strong></td><td><span class="badge bg-info">${code.LoaiGiamGia}</span></td></tr>
                                    <tr><td><strong>Giá trị giảm:</strong></td><td><span class="text-success fw-bold">${discountValue}</span></td></tr>
                                    <tr><td><strong>Số tiền tối thiểu:</strong></td><td>${code.SoTienToiThieu > 0 ? AdminPanel.formatCurrency(code.SoTienToiThieu) + ' VNĐ' : 'Không yêu cầu'}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-cog text-primary"></i> Cài đặt sử dụng</h6>
                                <table class="table table-borderless">
                                    <tr><td><strong>Số lần/user:</strong></td><td>${code.SoLanSuDungToiDa || 'Không giới hạn'}</td></tr>
                                    <tr><td><strong>Số lần tổng cộng:</strong></td><td>${code.SoLanSuDungTongCong || 'Không giới hạn'}</td></tr>
                                    <tr><td><strong>Đã sử dụng:</strong></td><td><span class="text-warning fw-bold">${code.SoLanDaSuDung} lần</span></td></tr>
                                    <tr><td><strong>Trạng thái:</strong></td><td><span class="badge bg-success">${code.TrangThai}</span></td></tr>
                                    <tr><td><strong>Ngày bắt đầu:</strong></td><td>${AdminPanel.formatDate(code.NgayBatDau, 'dd/mm/yyyy hh:mm')}</td></tr>
                                    <tr><td><strong>Ngày kết thúc:</strong></td><td>${AdminPanel.formatDate(code.NgayKetThuc, 'dd/mm/yyyy hh:mm')}</td></tr>
                                </table>
                            </div>
                        </div>
                        ${descriptionHtml}
                    `;
                } else {
                    document.getElementById('viewCodeModalBody').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.error || 'Không thể tải chi tiết mã giảm giá'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('viewCodeModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải chi tiết mã giảm giá
                    </div>
                `;
            });
        }

        function saveCode() {
            if (!AdminPanel.validateForm('codeForm')) {
                return;
            }

            const formData = new FormData(document.getElementById('codeForm'));
            const isEdit = document.getElementById('codeId').value !== '';
            const action = isEdit ? 'update' : 'add';
            
            formData.append('action', action);
            
            AdminPanel.makeAjaxRequest('../src/controllers/magiamgia-controller.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess(isEdit ? 'Đã cập nhật mã giảm giá thành công' : 'Đã thêm mã giảm giá thành công');
                    
                    // Close modal
                    const modalElement = document.getElementById('codeModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Reload table
                    codesTable.ajax.reload();
                    loadStatistics();
                } else {
                    AdminPanel.showError(response.error || response.message || 'Có lỗi xảy ra khi lưu mã giảm giá');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi lưu mã giảm giá');
            });
        }

        function deleteCode(id) {
            AdminPanel.makeAjaxRequest('../src/controllers/magiamgia-controller.php', {
                action: 'get',
                id: id
            })
            .then(response => {
                if (response.success && response.code) {
                    const code = response.code;
                    
                    AdminPanel.sweetConfirm(
                        'Xác nhận xóa',
                        `Bạn có chắc chắn muốn xóa mã giảm giá "${code.MaCode}"? Hành động này không thể hoàn tác.`,
                        () => {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('id', id);
                            
                            AdminPanel.makeAjaxRequest('../src/controllers/magiamgia-controller.php', formData, 'POST')
                            .then(response => {
                                if (response.success) {
                                    AdminPanel.showSuccess('Đã xóa mã giảm giá thành công');
                                    codesTable.ajax.reload();
                                    loadStatistics();
                                } else {
                                    AdminPanel.showError(response.error || response.message || 'Có lỗi xảy ra khi xóa mã giảm giá');
                                }
                            })
                            .catch(error => {
                                AdminPanel.showError('Có lỗi xảy ra khi xóa mã giảm giá');
                            });
                        }
                    );
                } else {
                    AdminPanel.showError('Không thể tải thông tin mã giảm giá');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi tải thông tin mã giảm giá');
            });
        }
    </script>

<?php include 'includes/admin-footer.php'; ?>
