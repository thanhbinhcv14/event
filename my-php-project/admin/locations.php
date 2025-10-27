<?php
// Include admin header
include 'includes/admin-header.php';
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-map-marker-alt"></i>
                Quản lý địa điểm
            </h1>
            <p class="page-subtitle">Quản lý thông tin và trạng thái các địa điểm tổ chức sự kiện</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="stat-number" id="totalLocations">0</div>
                <div class="stat-label">Tổng địa điểm</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="activeLocations">0</div>
                <div class="stat-label">Hoạt động</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-number" id="maintenanceLocations">0</div>
                <div class="stat-label">Bảo trì</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon rejected">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number" id="inactiveLocations">0</div>
                <div class="stat-label">Ngừng hoạt động</div>
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
                               placeholder="Nhập tên địa điểm, địa chỉ hoặc mô tả...">
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
                        <option value="Bảo trì">Bảo trì</option>
                        <option value="Ngừng hoạt động">Ngừng hoạt động</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Loại địa điểm</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">Tất cả loại</option>
                        <option value="Trong nhà">Trong nhà</option>
                        <option value="Ngoài trời">Ngoài trời</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Sắp xếp</label>
                    <select class="form-select" id="sortBy">
                        <option value="TenDiaDiem">Tên địa điểm</option>
                        <option value="DiaChi">Địa chỉ</option>
                        <option value="SucChua">Sức chứa</option>
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
                    Danh sách địa điểm
                </h3>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> Thêm địa điểm
                    </button>
                    
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="locationsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên địa điểm</th>
                            <th>Loại</th>
                            <th>Địa chỉ</th>
                            <th>Sức chứa</th>
                            <th>Giá thuê/giờ</th>
                            <th>Giá thuê/ngày</th>
                            <th>Loại thuê</th>
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

        <!-- Add/Edit Location Modal -->
        <div class="modal fade" id="locationModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="locationModalTitle">
                            <i class="fas fa-plus"></i>
                            Thêm địa điểm mới
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="locationForm" enctype="multipart/form-data">
                            <input type="hidden" id="locationId" name="id">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tên địa điểm <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="locationName" name="TenDiaDiem" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Loại địa điểm <span class="text-danger">*</span></label>
                                        <select class="form-select" id="locationType" name="LoaiDiaDiem" required>
                                            <option value="">Chọn loại địa điểm</option>
                                            <option value="Trong nhà">Trong nhà</option>
                                            <option value="Ngoài trời">Ngoài trời</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="locationAddress" name="DiaChi" rows="2" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Sức chứa <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="locationCapacity" name="SucChua" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Giá thuê/giờ (VNĐ)</label>
                                        <input type="number" class="form-control" id="locationPriceHour" name="GiaThueGio" min="0" step="1000">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Giá thuê/ngày (VNĐ)</label>
                                        <input type="number" class="form-control" id="locationPriceDay" name="GiaThueNgay" min="0" step="1000">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Loại thuê <span class="text-danger">*</span></label>
                                        <select class="form-select" id="locationRentType" name="LoaiThue" required>
                                            <option value="">Chọn loại thuê</option>
                                            <option value="Theo giờ">Theo giờ</option>
                                            <option value="Theo ngày">Theo ngày</option>
                                            <option value="Cả hai">Cả hai</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" id="locationDescription" name="MoTa" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Hình ảnh</label>
                                <div id="currentImageContainer" class="mb-2" style="display: none;">
                                    <label class="form-label text-muted">Hình ảnh hiện tại:</label>
                                    <div class="text-center">
                                        <img id="currentImage" src="" alt="Hình ảnh hiện tại" class="img-fluid rounded" style="max-width: 200px; max-height: 150px; object-fit: cover;">
                                    </div>
                                </div>
                                <input type="file" class="form-control" id="locationImage" name="HinhAnh" accept="image/*">
                                <small class="form-text text-muted">Chọn hình ảnh mới để thay thế hình ảnh hiện tại</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select class="form-select" id="locationStatus" name="TrangThaiHoatDong" required>
                                    <option value="Hoạt động">Hoạt động</option>
                                    <option value="Bảo trì">Bảo trì</option>
                                    <option value="Ngừng hoạt động">Ngừng hoạt động</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="saveLocation()">
                            <i class="fas fa-save"></i> Lưu
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Location Modal -->
        <div class="modal fade" id="viewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eye"></i>
                            Chi tiết địa điểm
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
        let locationsTable;
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
                locationsTable = $('#locationsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '../src/controllers/locations.php',
                    type: 'GET',
                    data: function(d) {
                        return $.extend({
                            action: 'get_locations',
                            limit: 1000 // Lấy tất cả để client-side filter
                        }, currentFilters);
                    },
                    dataSrc: function(json) {
                        if (json.success && json.locations) {
                            return json.locations;
                        } else {
                            console.error('Invalid data format:', json);
                            return [];
                        }
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTable AJAX Error:', error);
                        AdminPanel.showError('Không thể tải dữ liệu địa điểm');
                    }
                },
                columns: [
                    { data: 'ID_DD', className: 'text-center' },
                    { data: 'TenDiaDiem' },
                    { data: 'LoaiDiaDiem' },
                    { data: 'DiaChi' },
                    { 
                        data: 'SucChua',
                        render: function(data) {
                            return data.toLocaleString() + ' người';
                        }
                    },
                    { 
                        data: 'GiaThueGio',
                        render: function(data) {
                            return data ? AdminPanel.formatCurrency(data) : 'N/A';
                        }
                    },
                    { 
                        data: 'GiaThueNgay',
                        render: function(data) {
                            return data ? AdminPanel.formatCurrency(data) : 'N/A';
                        }
                    },
                    { 
                        data: 'LoaiThue',
                        render: function(data) {
                            if (!data) return '<span class="text-muted">Chưa xác định</span>';
                            const typeClass = data === 'Cả hai' ? 'success' : (data === 'Theo giờ' ? 'info' : 'warning');
                            return `<span class="badge bg-${typeClass}">${data}</span>`;
                        }
                    },
                    { 
                        data: 'TrangThaiHoatDong',
                        render: function(data) {
                            if (!data) return '<span class="status-badge status-unknown">Không xác định</span>';
                            const statusClass = data.toLowerCase().replace(/\s+/g, '-');
                            return `<span class="status-badge status-${statusClass}">${data}</span>`;
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
                                    <button class="btn btn-info btn-sm" onclick="viewLocation(${row.ID_DD})" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="editLocation(${row.ID_DD})" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteLocation(${row.ID_DD})" title="Xóa">
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
                paging: false, // Ẩn phân trang (hiển thị tất cả dữ liệu)
                // Custom filtering
                columnDefs: [
                    {
                        targets: [1, 2, 3], // Tên, Loại, Địa chỉ
                        searchable: true
                    }
                ]
            });
            } catch (error) {
                console.error('Error initializing DataTable:', error);
                AdminPanel.showError('Lỗi khởi tạo bảng dữ liệu');
            }
        }

        function loadStatistics() {
            AdminPanel.makeAjaxRequest('../src/controllers/locations.php', {
                action: 'get_location_stats'
            })
            .then(response => {
                if (response.success) {
                    $('#totalLocations').text(response.stats.total_locations || 0);
                    $('#activeLocations').text(response.stats.active_locations || 0);
                    $('#maintenanceLocations').text(response.stats.maintenance_locations || 0);
                    $('#inactiveLocations').text(response.stats.inactive_locations || 0);
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
            locationsTable.search(searchValue).draw();
            
            // Apply column filters
            if (statusFilter) {
                locationsTable.column(6).search(statusFilter);
            } else {
                locationsTable.column(6).search('');
            }
            
            if (typeFilter) {
                locationsTable.column(2).search(typeFilter);
            } else {
                locationsTable.column(2).search('');
            }
            
            // Apply sorting
            if (sortBy === 'TenDiaDiem') {
                locationsTable.order([1, 'asc']).draw();
            } else if (sortBy === 'DiaChi') {
                locationsTable.order([3, 'asc']).draw();
            } else if (sortBy === 'SucChua') {
                locationsTable.order([4, 'desc']).draw();
            } else if (sortBy === 'NgayTao') {
                locationsTable.order([7, 'desc']).draw();
            }
            
            // Redraw table
            locationsTable.draw();
        }

        function clearFilters() {
            $('#searchInput').val('');
            $('#statusFilter').val('');
            $('#typeFilter').val('');
            $('#sortBy').val('TenDiaDiem');
            
            // Clear all DataTable filters
            locationsTable.search('');
            locationsTable.columns().search('');
            locationsTable.order([0, 'desc']).draw();
        }

        function clearSearch() {
            $('#searchInput').val('');
            applyFilters();
        }

        function showAddModal() {
            $('#locationForm')[0].reset();
            $('#locationId').val('');
            $('#currentImageContainer').hide(); // Ẩn hình ảnh hiện tại khi thêm mới
            $('#locationModalTitle').html('<i class="fas fa-plus"></i> Thêm địa điểm mới');
            
            const modal = new bootstrap.Modal(document.getElementById('locationModal'));
            modal.show();
        }

        function editLocation(id) {
            AdminPanel.makeAjaxRequest('../src/controllers/locations.php', {
                action: 'get_location',
                id: id
            })
            .then(response => {
                if (response.success) {
                    const location = response.location;
                    $('#locationId').val(location.ID_DD);
                    $('#locationName').val(location.TenDiaDiem);
                    $('#locationType').val(location.LoaiDiaDiem);
                    $('#locationAddress').val(location.DiaChi);
                    $('#locationCapacity').val(location.SucChua);
                    $('#locationPriceHour').val(location.GiaThueGio);
                    $('#locationPriceDay').val(location.GiaThueNgay);
                    $('#locationRentType').val(location.LoaiThue);
                    $('#locationDescription').val(location.MoTa);
                    $('#locationStatus').val(location.TrangThaiHoatDong);
                    
                    // Hiển thị hình ảnh hiện tại nếu có
                    if (location.HinhAnh) {
                        $('#currentImage').attr('src', `../img/diadiem/${location.HinhAnh}`);
                        $('#currentImageContainer').show();
                    } else {
                        $('#currentImageContainer').hide();
                    }
                    
                    $('#locationModalTitle').html('<i class="fas fa-edit"></i> Chỉnh sửa địa điểm');
                    
                    const modal = new bootstrap.Modal(document.getElementById('locationModal'));
                    modal.show();
                } else {
                    AdminPanel.showError(response.message || 'Không thể tải thông tin địa điểm');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi tải thông tin địa điểm');
            });
        }

        function viewLocation(id) {
            AdminPanel.showLoading('#viewModalBody');
            
            const modal = new bootstrap.Modal(document.getElementById('viewModal'));
            modal.show();

            AdminPanel.makeAjaxRequest('../src/controllers/locations.php', {
                action: 'get_location',
                id: id
            })
            .then(response => {
                if (response.success) {
                    const location = response.location;
                    $('#viewModalBody').html(`
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-map-marker-alt"></i> Thông tin cơ bản</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Tên địa điểm:</strong></td><td>${location.TenDiaDiem}</td></tr>
                                    <tr><td><strong>Loại:</strong></td><td>${location.LoaiDiaDiem}</td></tr>
                                    <tr><td><strong>Địa chỉ:</strong></td><td>${location.DiaChi}</td></tr>
                                    <tr><td><strong>Sức chứa:</strong></td><td>${location.SucChua.toLocaleString()} người</td></tr>
                                    <tr><td><strong>Giá thuê/giờ:</strong></td><td>${location.GiaThueGio ? AdminPanel.formatCurrency(location.GiaThueGio) : 'Chưa có'}</td></tr>
                                    <tr><td><strong>Giá thuê/ngày:</strong></td><td>${location.GiaThueNgay ? AdminPanel.formatCurrency(location.GiaThueNgay) : 'Chưa có'}</td></tr>
                                    <tr><td><strong>Loại thuê:</strong></td><td><span class="badge bg-${location.LoaiThue === 'Cả hai' ? 'success' : (location.LoaiThue === 'Theo giờ' ? 'info' : 'warning')}">${location.LoaiThue || 'Chưa xác định'}</span></td></tr>
                                    <tr><td><strong>Trạng thái:</strong></td><td><span class="status-badge status-${location.TrangThaiHoatDong ? location.TrangThaiHoatDong.toLowerCase().replace(/\s+/g, '-') : 'unknown'}">${location.TrangThaiHoatDong || 'Không xác định'}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle"></i> Thông tin khác</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Mô tả:</strong></td><td>${location.MoTa || 'Không có mô tả'}</td></tr>
                                    <tr><td><strong>Hình ảnh:</strong></td><td>${location.HinhAnh ? `<img src="../img/diadiem/${location.HinhAnh}" alt="${location.TenDiaDiem}" class="img-fluid rounded" style="max-width: 200px; max-height: 150px; object-fit: cover;">` : 'Không có hình ảnh'}</td></tr>
                                    <tr><td><strong>Ngày tạo:</strong></td><td>${AdminPanel.formatDate(location.NgayTao, 'dd/mm/yyyy hh:mm')}</td></tr>
                                    <tr><td><strong>Cập nhật:</strong></td><td>${AdminPanel.formatDate(location.NgayCapNhat, 'dd/mm/yyyy hh:mm')}</td></tr>
                                </table>
                            </div>
                        </div>
                    `);
                } else {
                    $('#viewModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.message || 'Không thể tải chi tiết địa điểm'}
                        </div>
                    `);
                }
            })
            .catch(error => {
                $('#viewModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải chi tiết địa điểm
                    </div>
                `);
            });
        }

        function saveLocation() {
            if (!AdminPanel.validateForm('locationForm')) {
                return;
            }

            const formData = new FormData(document.getElementById('locationForm'));
            const isEdit = $('#locationId').val() !== '';
            const action = isEdit ? 'update_location' : 'add_location';
            
            // Add action to form data
            formData.append('action', action);
            
            // Debug: Log form data
            console.log('Form data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            AdminPanel.makeAjaxRequest('../src/controllers/locations.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess(isEdit ? 'Đã cập nhật địa điểm thành công' : 'Đã thêm địa điểm thành công');
                    bootstrap.Modal.getInstance(document.getElementById('locationModal')).hide();
                    locationsTable.ajax.reload();
                    loadStatistics();
                } else {
                    AdminPanel.showError(response.message || 'Có lỗi xảy ra khi lưu địa điểm');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi lưu địa điểm');
            });
        }

        function deleteLocation(id) {
            AdminPanel.sweetConfirm(
                'Xác nhận xóa',
                'Bạn có chắc muốn xóa địa điểm này? Hành động này không thể hoàn tác.',
                () => {
                    const formData = new FormData();
                    formData.append('action', 'delete_location');
                    formData.append('id', id);
                    
                    // Debug: Log form data
                    console.log('Delete location - Form data being sent:');
                    for (let [key, value] of formData.entries()) {
                        console.log(key + ': ' + value);
                    }
                    
                    AdminPanel.makeAjaxRequest('../src/controllers/locations.php', formData, 'POST')
                    .then(response => {
                        if (response.success) {
                            AdminPanel.showSuccess('Đã xóa địa điểm thành công');
                            locationsTable.ajax.reload();
                            loadStatistics();
                        } else {
                            AdminPanel.showError(response.message || 'Có lỗi xảy ra khi xóa địa điểm');
                        }
                    })
                    .catch(error => {
                        AdminPanel.showError('Có lỗi xảy ra khi xóa địa điểm');
                    });
                }
            );
        }

        

        // Auto refresh every 30 seconds
        setInterval(() => {
            loadStatistics();
        }, 30000);
    </script>

<?php include 'includes/admin-footer.php'; ?>