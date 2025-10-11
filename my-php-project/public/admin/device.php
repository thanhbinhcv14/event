<?php
// Include admin header
include 'includes/admin-header.php';
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tools"></i>
                Quản lý thiết bị
            </h1>
            <p class="page-subtitle">Quản lý thông tin và trạng thái các thiết bị sự kiện</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-number" id="totalDevices">0</div>
                <div class="stat-label">Tổng thiết bị</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="availableDevices">0</div>
                <div class="stat-label">Sẵn sàng</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number" id="rentedDevices">0</div>
                <div class="stat-label">Đang sử dụng</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon rejected">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="stat-number" id="maintenanceDevices">0</div>
                <div class="stat-label">Bảo trì</div>
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
                               placeholder="Nhập tên thiết bị, mô tả hoặc loại...">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Sẵn sàng">Sẵn sàng</option>
                        <option value="Đang sử dụng">Đang sử dụng</option>
                        <option value="Bảo trì">Bảo trì</option>
                        <option value="Hỏng">Hỏng</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Loại thiết bị</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">Tất cả loại</option>
                        <option value="Âm thanh">Âm thanh</option>
                        <option value="Hình ảnh">Hình ảnh</option>
                        <option value="Ánh sáng">Ánh sáng</option>
                        <option value="Phụ trợ">Phụ trợ</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Sắp xếp</label>
                    <select class="form-select" id="sortBy">
                        <option value="TenThietBi">Tên thiết bị</option>
                        <option value="LoaiThietBi">Loại thiết bị</option>
                        <option value="GiaThue">Giá thuê</option>
                        <option value="NgayTao">Ngày tạo</option>
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
                    Danh sách thiết bị
                </h3>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> Thêm thiết bị
                    </button>
                    <button class="btn btn-info" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i> Làm mới
                    </button>
                    </div>
                </div>
                
                    <div class="table-responsive">
                        <table class="table table-hover" id="devicesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên thiết bị</th>
                            <th>Loại</th>
                            <th>Hình ảnh</th>
                            <th>Mô tả</th>
                            <th>Giá thuê</th>
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

        <!-- Add/Edit Device Modal -->
    <div class="modal fade" id="deviceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                        <h5 class="modal-title" id="deviceModalTitle">
                            <i class="fas fa-plus"></i>
                            Thêm thiết bị mới
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                    <div class="modal-body">
                    <form id="deviceForm" enctype="multipart/form-data">
                            <input type="hidden" id="deviceId" name="id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tên thiết bị <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="deviceName" name="TenThietBi" required>
                                    </div>
                            </div>
                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Loại thiết bị <span class="text-danger">*</span></label>
                                        <select class="form-select" id="deviceType" name="LoaiThietBi" required>
                                    <option value="">Chọn loại thiết bị</option>
                                    <option value="Âm thanh">Âm thanh</option>
                                    <option value="Hình ảnh">Hình ảnh</option>
                                    <option value="Ánh sáng">Ánh sáng</option>
                                    <option value="Phụ trợ">Phụ trợ</option>
                                </select>
                            </div>
                        </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" id="deviceDescription" name="MoTa" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Giá thuê (VNĐ) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="devicePrice" name="GiaThue" min="0" step="1000" required>
                                    </div>
                            </div>
                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="deviceQuantity" name="SoLuong" min="1" required>
                        </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select class="form-select" id="deviceStatus" name="TrangThai" required>
                                    <option value="Sẵn sàng">Sẵn sàng</option>
                                    <option value="Đang sử dụng">Đang sử dụng</option>
                                    <option value="Bảo trì">Bảo trì</option>
                                    <option value="Hỏng">Hỏng</option>
                                </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hình ảnh</label>
                            <div id="currentImageContainer" class="mb-2" style="display: none;">
                                <label class="form-label text-muted">Hình ảnh hiện tại:</label>
                                <div class="text-center">
                                    <img id="currentImage" src="" alt="Hình ảnh hiện tại" class="img-fluid rounded" style="max-width: 200px; max-height: 150px; object-fit: cover;">
                                </div>
                            </div>
                            <input type="file" class="form-control" id="deviceImage" name="HinhAnh" accept="image/*">
                            <small class="form-text text-muted">Chọn hình ảnh mới để thay thế hình ảnh hiện tại</small>
                        </div>
                        
                        <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea class="form-control" id="deviceNote" name="GhiChu" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveDevice()">
                            <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </div>
        </div>
    </div>

        <!-- View Device Modal -->
        <div class="modal fade" id="viewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eye"></i>
                            Chi tiết thiết bị
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
        let devicesTable;
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
                devicesTable = $('#devicesTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '../../src/controllers/deviceedit.php',
                    type: 'GET',
                    data: function(d) {
                        d.action = 'get_all';
                        return $.extend(d, currentFilters);
                    },
                    dataSrc: function(json) {
                        if (json.success && json.devices) {
                            return json.devices;
                        } else {
                            console.error('Invalid data format:', json);
                            return [];
                        }
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable AJAX Error:', error);
                        AdminPanel.showError('Không thể tải dữ liệu thiết bị');
                    }
                },
                columns: [
                    { data: 'ID_TB', className: 'text-center' },
                    { data: 'TenThietBi' },
                    { data: 'LoaiThietBi' },
                    { 
                        data: 'HinhAnh',
                        render: function(data) {
                            if (data) {
                                return `<img src="../../img/thietbi/${data}" alt="Hình ảnh thiết bị" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">`;
                            }
                            return '<span class="text-muted">Không có hình ảnh</span>';
                        },
                        className: 'text-center'
                    },
                    { 
                        data: 'MoTa',
                        render: function(data) {
                            return data ? (data.length > 50 ? data.substring(0, 50) + '...' : data) : 'Không có mô tả';
                        }
                    },
                    { 
                        data: 'GiaThue',
                        render: function(data) {
                            return AdminPanel.formatCurrency(data);
                        }
                    },
                    { 
                        data: 'TrangThai',
                        render: function(data) {
                            if (!data) {
                                return '<span class="status-badge status-ready">Sẵn sàng</span>';
                            }
                            const statusMap = {
                                'Sẵn sàng': { class: 'ready', text: 'Sẵn sàng', icon: 'fa-check-circle' },
                                'Đang sử dụng': { class: 'in-use', text: 'Đang sử dụng', icon: 'fa-clock' },
                                'Bảo trì': { class: 'maintenance', text: 'Bảo trì', icon: 'fa-wrench' },
                                'Hỏng': { class: 'broken', text: 'Hỏng', icon: 'fa-exclamation-triangle' }
                            };
                            const status = statusMap[data] || { class: 'ready', text: data, icon: 'fa-question' };
                            return `<span class="status-badge status-${status.class}">
                                        <i class="fas ${status.icon}"></i> ${status.text}
                                    </span>`;
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
                                    <button class="btn btn-info btn-sm" onclick="viewDevice(${row.ID_TB})" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="editDevice(${row.ID_TB})" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteDevice(${row.ID_TB})" title="Xóa">
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
            AdminPanel.makeAjaxRequest('../../src/controllers/deviceedit.php', {
                action: 'get_stats'
            })
            .then(response => {
                if (response.success && response.stats) {
                    $('#totalDevices').text(response.stats.total || 0);
                    // Calculate other stats from get_all data
                    AdminPanel.makeAjaxRequest('../../src/controllers/deviceedit.php', {
                        action: 'get_all'
                    })
                    .then(devicesResponse => {
                        if (devicesResponse.success && devicesResponse.devices) {
                            const devices = devicesResponse.devices;
                            const available = devices.filter(d => d.TrangThai === 'Sẵn sàng').length;
                            const inUse = devices.filter(d => d.TrangThai === 'Đang sử dụng').length;
                            const maintenance = devices.filter(d => d.TrangThai === 'Bảo trì').length;
                            
                            $('#availableDevices').text(available);
                            $('#rentedDevices').text(inUse);
                            $('#maintenanceDevices').text(maintenance);
                        }
                    });
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
            $('#statusFilter, #typeFilter, #sortBy').on('change', function() {
                applyFilters();
            });
        }

        function applyFilters() {
            const searchValue = $('#searchInput').val();
            const statusFilter = $('#statusFilter').val();
            const typeFilter = $('#typeFilter').val();
            const sortBy = $('#sortBy').val();
            
            // Apply search to DataTable
            devicesTable.search(searchValue).draw();
            
            // Apply column filters
            if (statusFilter) {
                devicesTable.column(6).search(statusFilter);
            } else {
                devicesTable.column(6).search('');
            }
            
            if (typeFilter) {
                devicesTable.column(2).search(typeFilter);
            } else {
                devicesTable.column(2).search('');
            }
            
            // Apply sorting
            if (sortBy === 'TenThietBi') {
                devicesTable.order([1, 'asc']).draw();
            } else if (sortBy === 'LoaiThietBi') {
                devicesTable.order([2, 'asc']).draw();
            } else if (sortBy === 'GiaThue') {
                devicesTable.order([5, 'desc']).draw();
            } else if (sortBy === 'NgayTao') {
                devicesTable.order([7, 'desc']).draw();
            }
            
            // Redraw table
            devicesTable.draw();
        }

        function clearFilters() {
            $('#searchInput').val('');
            $('#statusFilter').val('');
            $('#typeFilter').val('');
            $('#sortBy').val('TenThietBi');
            
            // Clear all DataTable filters
            devicesTable.search('');
            devicesTable.columns().search('');
            devicesTable.order([0, 'desc']).draw();
        }

        function clearSearch() {
            $('#searchInput').val('');
            applyFilters();
        }

        function showAddModal() {
            $('#deviceForm')[0].reset();
            $('#deviceId').val('');
            $('#currentImageContainer').hide(); // Ẩn hình ảnh hiện tại khi thêm mới
            $('#deviceModalTitle').html('<i class="fas fa-plus"></i> Thêm thiết bị mới');
            
            const modal = new bootstrap.Modal(document.getElementById('deviceModal'));
            modal.show();
        }

        function editDevice(id) {
            AdminPanel.makeAjaxRequest('../../src/controllers/deviceedit.php', {
                action: 'get',
                id: id
            })
            .then(response => {
                if (response.success) {
                    const device = response.device;
                    $('#deviceId').val(device.ID_TB);
                    $('#deviceName').val(device.TenThietBi);
                    $('#deviceType').val(device.LoaiThietBi);
                    $('#deviceDescription').val(device.MoTa);
                    $('#devicePrice').val(device.GiaThue);
                    $('#deviceQuantity').val(device.SoLuong);
                    $('#deviceStatus').val(device.TrangThai);
                    $('#deviceNote').val(device.GhiChu);
                    
                    // Hiển thị hình ảnh hiện tại nếu có
                    if (device.HinhAnh) {
                        $('#currentImage').attr('src', `../../img/thietbi/${device.HinhAnh}`);
                        $('#currentImageContainer').show();
                    } else {
                        $('#currentImageContainer').hide();
                    }
                    
                    $('#deviceModalTitle').html('<i class="fas fa-edit"></i> Chỉnh sửa thiết bị');
                    
                    const modal = new bootstrap.Modal(document.getElementById('deviceModal'));
                    modal.show();
                } else {
                    AdminPanel.showError(response.message || 'Không thể tải thông tin thiết bị');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi tải thông tin thiết bị');
            });
        }

        function viewDevice(id) {
            AdminPanel.showLoading('#viewModalBody');
            
            const modal = new bootstrap.Modal(document.getElementById('viewModal'));
            modal.show();

            AdminPanel.makeAjaxRequest('../../src/controllers/deviceedit.php', {
                action: 'get',
                id: id
            })
            .then(response => {
                if (response.success) {
                    const device = response.device;
                    const statusMap = {
                        'Sẵn sàng': { class: 'ready', text: 'Sẵn sàng', icon: 'fa-check-circle' },
                        'Đang sử dụng': { class: 'in-use', text: 'Đang sử dụng', icon: 'fa-clock' },
                        'Bảo trì': { class: 'maintenance', text: 'Bảo trì', icon: 'fa-wrench' },
                        'Hỏng': { class: 'broken', text: 'Hỏng', icon: 'fa-exclamation-triangle' }
                    };
                    const status = statusMap[device.TrangThai] || { class: 'ready', text: device.TrangThai || 'Không xác định', icon: 'fa-question' };
                    
                    // Tạo HTML cho hình ảnh
                    let imageHtml = '';
                    if (device.HinhAnh) {
                        imageHtml = `
                            <div class="text-center mb-3">
                                <h6><i class="fas fa-image"></i> Hình ảnh thiết bị</h6>
                                <img src="../../img/thietbi/${device.HinhAnh}" 
                                     alt="${device.TenThietBi}" 
                                     class="img-fluid rounded shadow-sm" 
                                     style="max-height: 200px; max-width: 100%;"
                                     onerror="this.src='../../img/logo/logo.jpg'; this.alt='Hình ảnh không tìm thấy';">
                            </div>
                        `;
                    } else {
                        imageHtml = `
                            <div class="text-center mb-3">
                                <h6><i class="fas fa-image"></i> Hình ảnh thiết bị</h6>
                                <div class="bg-light rounded p-4" style="height: 150px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2 mb-0">Không có hình ảnh</p>
                                </div>
                            </div>
                        `;
                    }
                    
                    $('#viewModalBody').html(`
                        ${imageHtml}
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-tools"></i> Thông tin cơ bản</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Tên thiết bị:</strong></td><td>${device.TenThietBi}</td></tr>
                                    <tr><td><strong>Loại:</strong></td><td>${device.LoaiThietBi}</td></tr>
                                    <tr><td><strong>Hãng sản xuất:</strong></td><td>${device.HangSX || 'Không có'}</td></tr>
                                    <tr><td><strong>Giá thuê:</strong></td><td>${AdminPanel.formatCurrency(device.GiaThue)}</td></tr>
                                    <tr><td><strong>Số lượng:</strong></td><td>${device.SoLuong} ${device.DonViTinh || 'cái'}</td></tr>
                                    <tr><td><strong>Trạng thái:</strong></td><td><span class="status-badge status-${status.class}"><i class="fas ${status.icon}"></i> ${status.text}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle"></i> Thông tin khác</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Mô tả:</strong></td><td>${device.MoTa || 'Không có mô tả'}</td></tr>
                                    <tr><td><strong>Ghi chú:</strong></td><td>${device.GhiChu || 'Không có ghi chú'}</td></tr>
                                    <tr><td><strong>Ngày tạo:</strong></td><td>${AdminPanel.formatDate(device.NgayTao, 'dd/mm/yyyy hh:mm')}</td></tr>
                                    <tr><td><strong>Cập nhật:</strong></td><td>${AdminPanel.formatDate(device.NgayCapNhat, 'dd/mm/yyyy hh:mm')}</td></tr>
                                </table>
                            </div>
                        </div>
                    `);
                } else {
                    $('#viewModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.message || 'Không thể tải chi tiết thiết bị'}
                        </div>
                    `);
                }
            })
            .catch(error => {
                $('#viewModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải chi tiết thiết bị
                    </div>
                `);
            });
        }
        
        function saveDevice() {
            if (!AdminPanel.validateForm('deviceForm')) {
                return;
            }

            const formData = new FormData(document.getElementById('deviceForm'));
            const isEdit = $('#deviceId').val() !== '';
            const action = isEdit ? 'update' : 'add';
            
            // Add action to form data
            formData.append('action', action);

            AdminPanel.makeAjaxRequest('../../src/controllers/deviceedit.php', formData, 'POST')
            .then(response => {
                    if (response.success) {
                    AdminPanel.showSuccess(isEdit ? 'Đã cập nhật thiết bị thành công' : 'Đã thêm thiết bị thành công');
                    bootstrap.Modal.getInstance(document.getElementById('deviceModal')).hide();
                    devicesTable.ajax.reload();
                    loadStatistics();
                    } else {
                    AdminPanel.showError(response.message || 'Có lỗi xảy ra khi lưu thiết bị');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi lưu thiết bị');
            });
        }

        function deleteDevice(id) {
            AdminPanel.sweetConfirm(
                'Xác nhận xóa',
                'Bạn có chắc muốn xóa thiết bị này? Hành động này không thể hoàn tác.',
                () => {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    
                    AdminPanel.makeAjaxRequest('../../src/controllers/deviceedit.php', formData, 'POST')
                    .then(response => {
                        if (response.success) {
                            AdminPanel.showSuccess('Đã xóa thiết bị thành công');
                            devicesTable.ajax.reload();
                            loadStatistics();
                        } else {
                            AdminPanel.showError(response.message || 'Có lỗi xảy ra khi xóa thiết bị');
                        }
                    })
                    .catch(error => {
                        AdminPanel.showError('Có lỗi xảy ra khi xóa thiết bị');
                    });
                }
            );
        }

        function refreshData() {
            devicesTable.ajax.reload();
            loadStatistics();
            AdminPanel.showSuccess('Đã làm mới dữ liệu');
        }

        // Auto refresh every 30 seconds
        setInterval(() => {
            loadStatistics();
        }, 30000);
    </script>

    <style>
        /* Custom status badge styles */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-ready {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-in-use {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-maintenance {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-broken {
            background-color: #f5c6cb;
            color: #721c24;
            border: 1px solid #f1b0b7;
        }
        
        /* Action buttons styling */
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