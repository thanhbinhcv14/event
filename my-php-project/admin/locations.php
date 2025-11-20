<?php
// Bao gồm header admin
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
                            
                            <!-- Địa chỉ chi tiết -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> Địa chỉ chi tiết
                                </label>
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label small">Số nhà</label>
                                                <input type="text" class="form-control form-control-sm" id="locationSoNha" name="SoNha" placeholder="VD: 194">
                                            </div>
                                            <div class="col-md-9">
                                                <label class="form-label small">Đường/Phố</label>
                                                <input type="text" class="form-control form-control-sm" id="locationDuongPho" name="DuongPho" placeholder="VD: Hoàng Văn Thụ">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Phường/Xã</label>
                                                <select class="form-select form-select-sm" id="locationPhuongXa" name="PhuongXa">
                                                    <option value="">Chọn Phường/Xã</option>
                                                </select>
                                                <input type="text" class="form-control form-control-sm mt-2" id="locationPhuongXaText" placeholder="Hoặc nhập tên Phường/Xã" style="display: none;">
                                                <small class="text-muted">
                                                    <a href="javascript:void(0)" onclick="togglePhuongXaInput()" id="togglePhuongXaLink">Nhập thủ công</a>
                                                </small>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Quận/Huyện <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="locationQuanHuyen" name="QuanHuyen" required>
                                                    <option value="">Chọn Quận/Huyện</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm" id="locationTinhThanh" name="TinhThanh" required>
                                                    <option value="">Chọn Tỉnh/Thành phố</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Địa chỉ đầy đủ sẽ được tự động tạo từ các thành phần trên
                                </small>
                            </div>
                            
                            <!-- Địa chỉ đầy đủ (readonly, tự động tạo) -->
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ đầy đủ (tự động)</label>
                                <textarea class="form-control" id="locationAddress" name="DiaChi" rows="2" readonly style="background-color: #f8f9fa;"></textarea>
                                <small class="form-text text-muted">Trường này được tự động tạo từ các thành phần địa chỉ ở trên</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Sức chứa <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="locationCapacity" name="SucChua" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-4" id="priceHourContainer">
                                    <div class="mb-3">
                                        <label class="form-label">Giá thuê/giờ (VNĐ)</label>
                                        <input type="number" class="form-control" id="locationPriceHour" name="GiaThueGio" min="0" step="1000">
                                    </div>
                                </div>
                                <div class="col-md-4" id="priceDayContainer">
                                    <div class="mb-3">
                                        <label class="form-label">Giá thuê/ngày (VNĐ)</label>
                                        <input type="number" class="form-control" id="locationPriceDay" name="GiaThueNgay" min="0" step="1000">
                                    </div>
                                </div>
                                <div class="col-md-4" id="rentTypeContainer">
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
            // Ô tìm kiếm với debounce
            let searchTimeout;
            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyFilters();
                }, 300);
            });

            // Sự kiện thay đổi bộ lọc
            $('#statusFilter, #typeFilter, #sortBy').on('change', function() {
                applyFilters();
            });
        }

        function applyFilters() {
            const searchValue = $('#searchInput').val();
            const statusFilter = $('#statusFilter').val();
            const typeFilter = $('#typeFilter').val();
            const sortBy = $('#sortBy').val();
            
            // Áp dụng tìm kiếm vào DataTable
            locationsTable.search(searchValue).draw();
            
            // Áp dụng bộ lọc cột
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
            
            // Áp dụng sắp xếp
            if (sortBy === 'TenDiaDiem') {
                locationsTable.order([1, 'asc']).draw();
            } else if (sortBy === 'DiaChi') {
                locationsTable.order([3, 'asc']).draw();
            } else if (sortBy === 'SucChua') {
                locationsTable.order([4, 'desc']).draw();
            } else if (sortBy === 'NgayTao') {
                locationsTable.order([7, 'desc']).draw();
            }
            
            // Vẽ lại bảng
            locationsTable.draw();
        }

        function clearFilters() {
            $('#searchInput').val('');
            $('#statusFilter').val('');
            $('#typeFilter').val('');
            $('#sortBy').val('TenDiaDiem');
            
            // Xóa tất cả bộ lọc DataTable
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
            $('#locationAddress').val(''); // Reset địa chỉ đầy đủ
            $('#locationModalTitle').html('<i class="fas fa-plus"></i> Thêm địa điểm mới');
            
            // Reset dropdowns
            loadProvinces();
            $('#locationQuanHuyen').empty().append('<option value="">Chọn Quận/Huyện</option>');
            $('#locationPhuongXa').empty().append('<option value="">Chọn Phường/Xã</option>');
            $('#locationPhuongXaText').hide();
            $('#locationPhuongXa').show();
            $('#togglePhuongXaLink').text('Nhập thủ công');
            
            // Reset giá thuê fields
            togglePriceFields();
            
            const modal = new bootstrap.Modal(document.getElementById('locationModal'));
            modal.show();
        }
        
        // Tự động cập nhật địa chỉ đầy đủ khi thay đổi các thành phần
        function updateFullAddress() {
            const soNha = $('#locationSoNha').val() || '';
            const duongPho = $('#locationDuongPho').val() || '';
            // Lấy giá trị từ dropdown hoặc input text
            const phuongXa = $('#locationPhuongXaText').is(':visible') 
                ? $('#locationPhuongXaText').val() 
                : ($('#locationPhuongXa').val() || '');
            const quanHuyen = $('#locationQuanHuyen').val() || '';
            const tinhThanh = $('#locationTinhThanh').val() || '';
            
            let fullAddress = '';
            
            if (soNha) {
                fullAddress += soNha + ' ';
            }
            
            if (duongPho) {
                fullAddress += duongPho;
                if (phuongXa || quanHuyen || tinhThanh) {
                    fullAddress += ', ';
                }
            }
            
            if (phuongXa) {
                fullAddress += phuongXa;
                if (quanHuyen || tinhThanh) {
                    fullAddress += ', ';
                }
            }
            
            if (quanHuyen) {
                fullAddress += quanHuyen;
                if (tinhThanh) {
                    fullAddress += ', ';
                }
            }
            
            if (tinhThanh) {
                fullAddress += tinhThanh;
            }
            
            $('#locationAddress').val(fullAddress.trim());
        }
        
        // Dữ liệu tỉnh/thành phố và quận/huyện của Việt Nam
        const vietnamProvinces = {
            'TP.HCM': ['Quận 1', 'Quận 2', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8', 'Quận 9', 'Quận 10', 'Quận 11', 'Quận 12', 'Quận Bình Tân', 'Quận Bình Thạnh', 'Quận Gò Vấp', 'Quận Phú Nhuận', 'Quận Tân Bình', 'Quận Tân Phú', 'Quận Thủ Đức', 'Huyện Bình Chánh', 'Huyện Cần Giờ', 'Huyện Củ Chi', 'Huyện Hóc Môn', 'Huyện Nhà Bè'],
            'Hà Nội': ['Quận Ba Đình', 'Quận Hoàn Kiếm', 'Quận Tây Hồ', 'Quận Long Biên', 'Quận Cầu Giấy', 'Quận Đống Đa', 'Quận Hai Bà Trưng', 'Quận Hoàng Mai', 'Quận Thanh Xuân', 'Quận Sóc Sơn', 'Quận Đông Anh', 'Quận Gia Lâm', 'Quận Nam Từ Liêm', 'Quận Bắc Từ Liêm', 'Quận Mê Linh', 'Quận Hà Đông', 'Quận Sơn Tây', 'Huyện Ba Vì', 'Huyện Phúc Thọ', 'Huyện Đan Phượng', 'Huyện Hoài Đức', 'Huyện Quốc Oai', 'Huyện Thạch Thất', 'Huyện Chương Mỹ', 'Huyện Thanh Oai', 'Huyện Thường Tín', 'Huyện Phú Xuyên', 'Huyện Ứng Hòa', 'Huyện Mỹ Đức'],
            'Đà Nẵng': ['Quận Hải Châu', 'Quận Thanh Khê', 'Quận Sơn Trà', 'Quận Ngũ Hành Sơn', 'Quận Liên Chiểu', 'Quận Cẩm Lệ', 'Huyện Hòa Vang', 'Huyện Hoàng Sa'],
            'Cần Thơ': ['Quận Ninh Kiều', 'Quận Ô Môn', 'Quận Bình Thủy', 'Quận Cái Răng', 'Quận Thốt Nốt', 'Huyện Vĩnh Thạnh', 'Huyện Cờ Đỏ', 'Huyện Phong Điền', 'Huyện Thới Lai'],
            'Hải Phòng': ['Quận Hồng Bàng', 'Quận Ngô Quyền', 'Quận Lê Chân', 'Quận Hải An', 'Quận Kiến An', 'Quận Đồ Sơn', 'Quận Dương Kinh', 'Huyện Thuỷ Nguyên', 'Huyện An Dương', 'Huyện An Lão', 'Huyện Kiến Thuỵ', 'Huyện Tiên Lãng', 'Huyện Vĩnh Bảo', 'Huyện Cát Hải', 'Huyện Bạch Long Vĩ'],
            'An Giang': ['Thành phố Long Xuyên', 'Thành phố Châu Đốc', 'Huyện An Phú', 'Huyện Châu Phú', 'Huyện Châu Thành', 'Huyện Chợ Mới', 'Huyện Phú Tân', 'Huyện Thoại Sơn', 'Huyện Tịnh Biên', 'Huyện Tri Tôn'],
            'Bà Rịa - Vũng Tàu': ['Thành phố Vũng Tàu', 'Thành phố Bà Rịa', 'Huyện Châu Đức', 'Huyện Côn Đảo', 'Huyện Đất Đỏ', 'Huyện Long Điền', 'Huyện Tân Thành', 'Huyện Xuyên Mộc'],
            'Bắc Giang': ['Thành phố Bắc Giang', 'Huyện Yên Thế', 'Huyện Tân Yên', 'Huyện Lạng Giang', 'Huyện Lục Nam', 'Huyện Lục Ngạn', 'Huyện Sơn Động', 'Huyện Yên Dũng', 'Huyện Việt Yên', 'Huyện Hiệp Hòa'],
            'Bắc Kạn': ['Thành phố Bắc Kạn', 'Huyện Pác Nặm', 'Huyện Ba Bể', 'Huyện Ngân Sơn', 'Huyện Bạch Thông', 'Huyện Chợ Đồn', 'Huyện Chợ Mới', 'Huyện Na Rì'],
            'Bạc Liêu': ['Thành phố Bạc Liêu', 'Huyện Hồng Dân', 'Huyện Phước Long', 'Huyện Vĩnh Lợi', 'Huyện Giá Rai', 'Huyện Đông Hải', 'Huyện Hoà Bình'],
            'Bắc Ninh': ['Thành phố Bắc Ninh', 'Huyện Yên Phong', 'Huyện Quế Võ', 'Huyện Tiên Du', 'Huyện Từ Sơn', 'Huyện Thị xã Từ Sơn', 'Huyện Lương Tài', 'Huyện Gia Bình'],
            'Bến Tre': ['Thành phố Bến Tre', 'Huyện Châu Thành', 'Huyện Chợ Lách', 'Huyện Mỏ Cày Bắc', 'Huyện Mỏ Cày Nam', 'Huyện Giồng Trôm', 'Huyện Bình Đại', 'Huyện Ba Tri', 'Huyện Thạnh Phú'],
            'Bình Định': ['Thành phố Quy Nhon', 'Huyện An Lão', 'Huyện Hoài Ân', 'Huyện Hoài Nhơn', 'Huyện Phù Cát', 'Huyện Phù Mỹ', 'Huyện Tây Sơn', 'Huyện Tuy Phước', 'Huyện Vân Canh', 'Huyện Vĩnh Thạnh'],
            'Bình Dương': ['Thành phố Thủ Dầu Một', 'Thị xã Dĩ An', 'Thị xã Thuận An', 'Thị xã Tân Uyên', 'Huyện Bến Cát', 'Huyện Dầu Tiếng', 'Huyện Phú Giáo', 'Huyện Bàu Bàng', 'Huyện Bắc Tân Uyên'],
            'Bình Phước': ['Thành phố Đồng Xoài', 'Thị xã Bình Long', 'Huyện Bù Đăng', 'Huyện Bù Đốp', 'Huyện Bù Gia Mập', 'Huyện Chơn Thành', 'Huyện Đồng Phú', 'Huyện Hớn Quản', 'Huyện Lộc Ninh'],
            'Bình Thuận': ['Thành phố Phan Thiết', 'Thị xã La Gi', 'Huyện Tuy Phong', 'Huyện Bắc Bình', 'Huyện Hàm Thuận Bắc', 'Huyện Hàm Thuận Nam', 'Huyện Tánh Linh', 'Huyện Đức Linh', 'Huyện Hàm Tân', 'Huyện Phú Quí'],
            'Cà Mau': ['Thành phố Cà Mau', 'Huyện Cái Nước', 'Huyện Đầm Dơi', 'Huyện Năm Căn', 'Huyện Ngọc Hiển', 'Huyện Phú Tân', 'Huyện Thới Bình', 'Huyện Trần Văn Thời', 'Huyện U Minh'],
            'Cao Bằng': ['Thành phố Cao Bằng', 'Huyện Bảo Lạc', 'Huyện Bảo Lâm', 'Huyện Hạ Lang', 'Huyện Hà Quảng', 'Huyện Hoà An', 'Huyện Nguyên Bình', 'Huyện Quảng Uyên', 'Huyện Thạch An', 'Huyện Trùng Khánh'],
            'Đắk Lắk': ['Thành phố Buôn Ma Thuột', 'Thị xã Buôn Hồ', 'Huyện Cư Kuin', 'Huyện Cư M\'gar', 'Huyện Ea H\'leo', 'Huyện Ea Kar', 'Huyện Ea Súp', 'Huyện Krông A Na', 'Huyện Krông Bông', 'Huyện Krông Búk', 'Huyện Krông Năng', 'Huyện Krông Pắk', 'Huyện Lắk', 'Huyện M\'Đrắk'],
            'Đắk Nông': ['Thành phố Gia Nghĩa', 'Huyện Cư Jút', 'Huyện Đắk Glong', 'Huyện Đắk Mil', 'Huyện Đắk R\'Lấp', 'Huyện Đắk Song', 'Huyện Krông Nô', 'Huyện Tuy Đức'],
            'Điện Biên': ['Thành phố Điện Biên Phủ', 'Thị xã Mường Lay', 'Huyện Điện Biên', 'Huyện Điện Biên Đông', 'Huyện Mường Ảng', 'Huyện Mường Chà', 'Huyện Mường Nhé', 'Huyện Nậm Pồ', 'Huyện Tủa Chùa', 'Huyện Tuần Giáo'],
            'Đồng Nai': ['Thành phố Biên Hòa', 'Thành phố Long Khánh', 'Huyện Cẩm Mỹ', 'Huyện Định Quán', 'Huyện Long Thành', 'Huyện Nhơn Trạch', 'Huyện Tân Phú', 'Huyện Thống Nhất', 'Huyện Vĩnh Cửu', 'Huyện Xuân Lộc'],
            'Đồng Tháp': ['Thành phố Cao Lãnh', 'Thành phố Sa Đéc', 'Thị xã Hồng Ngự', 'Huyện Cao Lãnh', 'Huyện Châu Thành', 'Huyện Hồng Ngự', 'Huyện Lai Vung', 'Huyện Lấp Vò', 'Huyện Tam Nông', 'Huyện Tân Hồng', 'Huyện Tân Hưng', 'Huyện Thanh Bình', 'Huyện Tháp Mười'],
            'Gia Lai': ['Thành phố Pleiku', 'Thị xã An Khê', 'Thị xã Ayun Pa', 'Huyện Chư Păh', 'Huyện Chư Prông', 'Huyện Chư Sê', 'Huyện Đăk Đoa', 'Huyện Đăk Pơ', 'Huyện Đức Cơ', 'Huyện Ia Grai', 'Huyện Ia Pa', 'Huyện KBang', 'Huyện Kông Chro', 'Huyện Krông Pa', 'Huyện Mang Yang', 'Huyện Phú Thiện'],
            'Hà Giang': ['Thành phố Hà Giang', 'Huyện Bắc Mê', 'Huyện Bắc Quang', 'Huyện Đồng Văn', 'Huyện Hoàng Su Phì', 'Huyện Mèo Vạc', 'Huyện Mù Cang Chải', 'Huyện Quản Bạ', 'Huyện Quang Bình', 'Huyện Vị Xuyên', 'Huyện Xín Mần', 'Huyện Yên Minh'],
            'Hà Nam': ['Thành phố Phủ Lý', 'Huyện Bình Lục', 'Huyện Duy Tiên', 'Huyện Kim Bảng', 'Huyện Lý Nhân', 'Huyện Thanh Liêm'],
            'Hà Tĩnh': ['Thành phố Hà Tĩnh', 'Thị xã Hồng Lĩnh', 'Huyện Can Lộc', 'Huyện Cẩm Xuyên', 'Huyện Đức Thọ', 'Huyện Hương Khê', 'Huyện Hương Sơn', 'Huyện Kỳ Anh', 'Huyện Lộc Hà', 'Huyện Nghi Xuân', 'Huyện Thạch Hà', 'Huyện Vũ Quang'],
            'Hải Dương': ['Thành phố Hải Dương', 'Thành phố Chí Linh', 'Huyện Bình Giang', 'Huyện Cẩm Giàng', 'Huyện Gia Lộc', 'Huyện Kim Thành', 'Huyện Kinh Môn', 'Huyện Nam Sách', 'Huyện Ninh Giang', 'Huyện Thanh Hà', 'Huyện Thanh Miện', 'Huyện Tứ Kỳ'],
            'Hậu Giang': ['Thành phố Vị Thanh', 'Thành phố Ngã Bảy', 'Huyện Châu Thành', 'Huyện Châu Thành A', 'Huyện Long Mỹ', 'Huyện Phụng Hiệp', 'Huyện Vị Thủy'],
            'Hòa Bình': ['Thành phố Hòa Bình', 'Huyện Đà Bắc', 'Huyện Kim Bôi', 'Huyện Cao Phong', 'Huyện Lạc Sơn', 'Huyện Lạc Thủy', 'Huyện Lương Sơn', 'Huyện Mai Châu', 'Huyện Tân Lạc', 'Huyện Yên Thủy'],
            'Hưng Yên': ['Thành phố Hưng Yên', 'Huyện Văn Lâm', 'Huyện Văn Giang', 'Huyện Yên Mỹ', 'Huyện Mỹ Hào', 'Huyện Ân Thi', 'Huyện Khoái Châu', 'Huyện Kim Động', 'Huyện Phù Cừ', 'Huyện Tiên Lữ'],
            'Khánh Hòa': ['Thành phố Nha Trang', 'Thành phố Cam Ranh', 'Thị xã Ninh Hòa', 'Huyện Cam Lâm', 'Huyện Diên Khánh', 'Huyện Khánh Sơn', 'Huyện Khánh Vĩnh', 'Huyện Trường Sa', 'Huyện Vạn Ninh'],
            'Kiên Giang': ['Thành phố Rạch Giá', 'Thành phố Hà Tiên', 'Huyện An Biên', 'Huyện An Minh', 'Huyện Châu Thành', 'Huyện Giồng Riềng', 'Huyện Gò Quao', 'Huyện Hòn Đất', 'Huyện Kiên Hải', 'Huyện Kiên Lương', 'Huyện Phú Quốc', 'Huyện Tân Hiệp', 'Huyện U Minh Thượng', 'Huyện Vĩnh Thuận'],
            'Kon Tum': ['Thành phố Kon Tum', 'Huyện Đắk Glei', 'Huyện Đắk Hà', 'Huyện Đắk Tô', 'Huyện Ia H\'Drai', 'Huyện Kon Plông', 'Huyện Kon Rẫy', 'Huyện Ngọc Hồi', 'Huyện Sa Thầy', 'Huyện Tu Mơ Rông'],
            'Lai Châu': ['Thành phố Lai Châu', 'Huyện Mường Tè', 'Huyện Nậm Nhùn', 'Huyện Phong Thổ', 'Huyện Sìn Hồ', 'Huyện Tam Đường', 'Huyện Tân Uyên', 'Huyện Than Uyên'],
            'Lâm Đồng': ['Thành phố Đà Lạt', 'Thành phố Bảo Lộc', 'Huyện Bảo Lâm', 'Huyện Cát Tiên', 'Huyện Đạ Huoai', 'Huyện Đạ Tẻh', 'Huyện Đam Rông', 'Huyện Đơn Dương', 'Huyện Đức Trọng', 'Huyện Lạc Dương', 'Huyện Lâm Hà'],
            'Lạng Sơn': ['Thành phố Lạng Sơn', 'Huyện Bắc Sơn', 'Huyện Bình Gia', 'Huyện Cao Lộc', 'Huyện Chi Lăng', 'Huyện Đình Lập', 'Huyện Hữu Lũng', 'Huyện Lộc Bình', 'Huyện Tràng Định', 'Huyện Văn Lãng', 'Huyện Văn Quan'],
            'Lào Cai': ['Thành phố Lào Cai', 'Huyện Bát Xát', 'Huyện Bảo Thắng', 'Huyện Bảo Yên', 'Huyện Bắc Hà', 'Huyện Mường Khương', 'Huyện Sa Pa', 'Huyện Si Ma Cai', 'Huyện Văn Bàn'],
            'Long An': ['Thành phố Tân An', 'Thị xã Kiến Tường', 'Huyện Bến Lức', 'Huyện Cần Đước', 'Huyện Cần Giuộc', 'Huyện Châu Thành', 'Huyện Đức Hòa', 'Huyện Đức Huệ', 'Huyện Mộc Hóa', 'Huyện Tân Hưng', 'Huyện Tân Thạnh', 'Huyện Tân Trụ', 'Huyện Thạnh Hóa', 'Huyện Thủ Thừa', 'Huyện Vĩnh Hưng'],
            'Nam Định': ['Thành phố Nam Định', 'Huyện Mỹ Lộc', 'Huyện Vụ Bản', 'Huyện Ý Yên', 'Huyện Nghĩa Hưng', 'Huyện Nam Trực', 'Huyện Trực Ninh', 'Huyện Xuân Trường', 'Huyện Giao Thủy', 'Huyện Hải Hậu'],
            'Nghệ An': ['Thành phố Vinh', 'Thị xã Cửa Lò', 'Thị xã Thái Hòa', 'Huyện Anh Sơn', 'Huyện Con Cuông', 'Huyện Diễn Châu', 'Huyện Đô Lương', 'Huyện Hưng Nguyên', 'Huyện Kỳ Sơn', 'Huyện Nam Đàn', 'Huyện Nghi Lộc', 'Huyện Nghĩa Đàn', 'Huyện Quế Phong', 'Huyện Quỳ Châu', 'Huyện Quỳ Hợp', 'Huyện Quỳnh Lưu', 'Huyện Tân Kỳ', 'Huyện Thanh Chương', 'Huyện Tương Dương', 'Huyện Yên Thành'],
            'Ninh Bình': ['Thành phố Ninh Bình', 'Thành phố Tam Điệp', 'Huyện Gia Viễn', 'Huyện Hoa Lư', 'Huyện Kim Sơn', 'Huyện Nho Quan', 'Huyện Yên Khánh', 'Huyện Yên Mô'],
            'Ninh Thuận': ['Thành phố Phan Rang - Tháp Chàm', 'Huyện Bác Ái', 'Huyện Ninh Hải', 'Huyện Ninh Phước', 'Huyện Ninh Sơn', 'Huyện Thuận Bắc', 'Huyện Thuận Nam'],
            'Phú Thọ': ['Thành phố Việt Trì', 'Thị xã Phú Thọ', 'Huyện Cẩm Khê', 'Huyện Đoan Hùng', 'Huyện Hạ Hòa', 'Huyện Lâm Thao', 'Huyện Phù Ninh', 'Huyện Tam Nông', 'Huyện Tân Sơn', 'Huyện Thanh Ba', 'Huyện Thanh Sơn', 'Huyện Thanh Thủy', 'Huyện Yên Lập'],
            'Phú Yên': ['Thành phố Tuy Hòa', 'Thị xã Sông Cầu', 'Huyện Đông Hòa', 'Huyện Phú Hòa', 'Huyện Sơn Hòa', 'Huyện Sông Hinh', 'Huyện Tây Hòa', 'Huyện Tuy An'],
            'Quảng Bình': ['Thành phố Đồng Hới', 'Huyện Bố Trạch', 'Huyện Lệ Thủy', 'Huyện Minh Hóa', 'Huyện Quảng Ninh', 'Huyện Quảng Trạch', 'Huyện Tuyên Hóa'],
            'Quảng Nam': ['Thành phố Tam Kỳ', 'Thành phố Hội An', 'Huyện Bắc Trà My', 'Huyện Đại Lộc', 'Huyện Đông Giang', 'Huyện Duy Xuyên', 'Huyện Hiệp Đức', 'Huyện Nam Giang', 'Huyện Nam Trà My', 'Huyện Phước Sơn', 'Huyện Phú Ninh', 'Huyện Tây Giang', 'Huyện Thăng Bình', 'Huyện Tiên Phước'],
            'Quảng Ngãi': ['Thành phố Quảng Ngãi', 'Huyện Ba Tơ', 'Huyện Bình Sơn', 'Huyện Đức Phổ', 'Huyện Lý Sơn', 'Huyện Minh Long', 'Huyện Mộ Đức', 'Huyện Nghĩa Hành', 'Huyện Sơn Hà', 'Huyện Sơn Tịnh', 'Huyện Sơn Tây', 'Huyện Tây Trà', 'Huyện Trà Bồng', 'Huyện Tư Nghĩa'],
            'Quảng Ninh': ['Thành phố Hạ Long', 'Thành phố Móng Cái', 'Thành phố Cẩm Phả', 'Thành phố Uông Bí', 'Thị xã Bình Liêu', 'Thị xã Cô Tô', 'Thị xã Đông Triều', 'Thị xã Quảng Yên', 'Huyện Ba Chẽ', 'Huyện Cẩm Phả', 'Huyện Đầm Hà', 'Huyện Hải Hà', 'Huyện Hoành Bồ', 'Huyện Tiên Yên', 'Huyện Vân Đồn'],
            'Quảng Trị': ['Thành phố Đông Hà', 'Thị xã Quảng Trị', 'Huyện Cam Lộ', 'Huyện Cồn Cỏ', 'Huyện Đa Krông', 'Huyện Gio Linh', 'Huyện Hải Lăng', 'Huyện Hướng Hóa', 'Huyện Triệu Phong', 'Huyện Vĩnh Linh'],
            'Sóc Trăng': ['Thành phố Sóc Trăng', 'Huyện Châu Thành', 'Huyện Cù Lao Dung', 'Huyện Kế Sách', 'Huyện Long Phú', 'Huyện Mỹ Tú', 'Huyện Mỹ Xuyên', 'Huyện Ngã Năm', 'Huyện Thạnh Trị', 'Huyện Trần Đề', 'Huyện Vĩnh Châu'],
            'Sơn La': ['Thành phố Sơn La', 'Huyện Mường La', 'Huyện Mường Chà', 'Huyện Mường Tè', 'Huyện Mường Nhé', 'Huyện Sông Mã', 'Huyện Sốp Cộp', 'Huyện Sông Đà', 'Huyện Than Uyên', 'Huyện Thuận Châu', 'Huyện Tủa Chùa', 'Huyện Tân Uyên', 'Huyện Yên Châu'],
            'Tây Ninh': ['Thành phố Tây Ninh', 'Huyện Bến Cầu', 'Huyện Châu Thành', 'Huyện Dương Minh Châu', 'Huyện Gò Dầu', 'Huyện Hòa Thành', 'Huyện Tân Biên', 'Huyện Tân Châu', 'Huyện Trảng Bàng'],
            'Thái Bình': ['Thành phố Thái Bình', 'Huyện Đông Hưng', 'Huyện Hưng Hà', 'Huyện Kiến Xương', 'Huyện Quỳnh Phụ', 'Huyện Thái Thụy', 'Huyện Tiền Hải', 'Huyện Vũ Thư'],
            'Thái Nguyên': ['Thành phố Thái Nguyên', 'Thành phố Sông Công', 'Thị xã Phổ Yên', 'Huyện Đại Từ', 'Huyện Định Hóa', 'Huyện Đồng Hỷ', 'Huyện Phú Bình', 'Huyện Phú Lương', 'Huyện Võ Nhai'],
            'Thanh Hóa': ['Thành phố Thanh Hóa', 'Thị xã Bỉm Sơn', 'Thị xã Sầm Sơn', 'Huyện Bá Thước', 'Huyện Cẩm Thủy', 'Huyện Đông Sơn', 'Huyện Hà Trung', 'Huyện Hậu Lộc', 'Huyện Hoằng Hóa', 'Huyện Lang Chánh', 'Huyện Mường Lát', 'Huyện Nga Sơn', 'Huyện Ngọc Lặc', 'Huyện Như Thanh', 'Huyện Như Xuân', 'Huyện Nông Cống', 'Huyện Quan Hóa', 'Huyện Quan Sơn', 'Huyện Quảng Xương', 'Huyện Thạch Thành', 'Huyện Thiệu Hóa', 'Huyện Thọ Xuân', 'Huyện Thường Xuân', 'Huyện Tĩnh Gia', 'Huyện Triệu Sơn', 'Huyện Vĩnh Lộc', 'Huyện Yên Định'],
            'Thừa Thiên Huế': ['Thành phố Huế', 'Thị xã Hương Thủy', 'Thị xã Hương Trà', 'Huyện A Lưới', 'Huyện Nam Đông', 'Huyện Phong Điền', 'Huyện Phú Lộc', 'Huyện Phú Vang', 'Huyện Quảng Điền'],
            'Tiền Giang': ['Thành phố Mỹ Tho', 'Thị xã Gò Công', 'Huyện Cái Bè', 'Huyện Cai Lậy', 'Huyện Châu Thành', 'Huyện Chợ Gạo', 'Huyện Gò Công Đông', 'Huyện Gò Công Tây', 'Huyện Tân Phú Đông', 'Huyện Tân Phước'],
            'Trà Vinh': ['Thành phố Trà Vinh', 'Huyện Càng Long', 'Huyện Cầu Kè', 'Huyện Cầu Ngang', 'Huyện Châu Thành', 'Huyện Duyên Hải', 'Huyện Tiểu Cần', 'Huyện Trà Cú'],
            'Tuyên Quang': ['Thành phố Tuyên Quang', 'Huyện Chiêm Hóa', 'Huyện Hàm Yên', 'Huyện Lâm Bình', 'Huyện Na Hang', 'Huyện Sơn Dương', 'Huyện Yên Sơn'],
            'Vĩnh Long': ['Thành phố Vĩnh Long', 'Huyện Bình Minh', 'Huyện Bình Tân', 'Huyện Long Hồ', 'Huyện Mang Thít', 'Huyện Tam Bình', 'Huyện Trà Ôn', 'Huyện Vũng Liêm'],
            'Vĩnh Phúc': ['Thành phố Vĩnh Yên', 'Thành phố Phúc Yên', 'Huyện Bình Xuyên', 'Huyện Lập Thạch', 'Huyện Sông Lô', 'Huyện Tam Đảo', 'Huyện Tam Dương', 'Huyện Vĩnh Tường', 'Huyện Yên Lạc'],
            'Yên Bái': ['Thành phố Yên Bái', 'Thị xã Nghĩa Lộ', 'Huyện Lục Yên', 'Huyện Mù Cang Chải', 'Huyện Trạm Tấu', 'Huyện Trấn Yên', 'Huyện Văn Chấn', 'Huyện Văn Yên', 'Huyện Yên Bình']
        };

        // Load danh sách tỉnh/thành phố vào dropdown
        function loadProvinces() {
            const select = $('#locationTinhThanh');
            select.empty().append('<option value="">Chọn Tỉnh/Thành phố</option>');
            Object.keys(vietnamProvinces).sort().forEach(province => {
                select.append(`<option value="${province}">${province}</option>`);
            });
        }

        // Load danh sách quận/huyện dựa trên tỉnh/thành phố đã chọn
        function loadDistricts(province) {
            const select = $('#locationQuanHuyen');
            select.empty().append('<option value="">Chọn Quận/Huyện</option>');
            
            if (province && vietnamProvinces[province]) {
                vietnamProvinces[province].forEach(district => {
                    select.append(`<option value="${district}">${district}</option>`);
                });
            }
        }

        // Toggle input thủ công cho Phường/Xã
        function togglePhuongXaInput() {
            const select = $('#locationPhuongXa');
            const textInput = $('#locationPhuongXaText');
            const link = $('#togglePhuongXaLink');
            
            if (textInput.is(':visible')) {
                textInput.hide();
                select.show();
                link.text('Nhập thủ công');
                textInput.val('');
            } else {
                select.hide();
                textInput.show();
                link.text('Chọn từ danh sách');
                select.val('');
            }
        }

        // Ẩn/hiện giá thuê dựa trên loại địa điểm
        function togglePriceFields() {
            const locationType = $('#locationType').val();
            const priceHourContainer = $('#priceHourContainer');
            const priceDayContainer = $('#priceDayContainer');
            const rentTypeContainer = $('#rentTypeContainer');
            
            if (locationType === 'Trong nhà') {
                // Ẩn các trường giá thuê cho địa điểm trong nhà
                priceHourContainer.hide();
                priceDayContainer.hide();
                rentTypeContainer.hide();
                
                // Set giá trị về null
                $('#locationPriceHour').val('');
                $('#locationPriceDay').val('');
                $('#locationRentType').val('').removeAttr('required');
            } else if (locationType === 'Ngoài trời') {
                // Hiện các trường giá thuê cho địa điểm ngoài trời
                priceHourContainer.show();
                priceDayContainer.show();
                rentTypeContainer.show();
                
                // Set required cho loại thuê
                $('#locationRentType').attr('required', 'required');
            }
        }

        // Gắn sự kiện cho các trường địa chỉ
        $(document).ready(function() {
            // Tải danh sách tỉnh/thành phố khi trang tải
            loadProvinces();
            
            // Khi chọn tỉnh/thành phố, load quận/huyện
            $('#locationTinhThanh').on('change', function() {
                const province = $(this).val();
                loadDistricts(province);
                updateFullAddress();
            });
            
            // Khi chọn loại địa điểm, ẩn/hiện giá thuê
            $('#locationType').on('change', function() {
                togglePriceFields();
            });
            
            // Gắn sự kiện cho các trường địa chỉ
            $('#locationSoNha, #locationDuongPho, #locationPhuongXa, #locationPhuongXaText, #locationQuanHuyen, #locationTinhThanh').on('input change', function() {
                updateFullAddress();
            });
        });

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
                    
                    // Điền các trường địa chỉ chi tiết
                    $('#locationSoNha').val(location.SoNha || '');
                    $('#locationDuongPho').val(location.DuongPho || '');
                    
                    // Tải tỉnh/thành phố trước
                    loadProvinces();
                    if (location.TinhThanh) {
                        $('#locationTinhThanh').val(location.TinhThanh);
                        // Tải quận/huyện sau khi chọn tỉnh/thành phố
                        loadDistricts(location.TinhThanh);
                    }
                    
                    // Set giá trị quận/huyện và phường/xã
                    if (location.QuanHuyen) {
                        $('#locationQuanHuyen').val(location.QuanHuyen);
                    }
                    
                    if (location.PhuongXa) {
                        // Kiểm tra xem phường/xã có trong dropdown không
                        const phuongXaOption = $('#locationPhuongXa option').filter(function() {
                            return $(this).text() === location.PhuongXa || $(this).val() === location.PhuongXa;
                        });
                        
                        if (phuongXaOption.length > 0) {
                            $('#locationPhuongXa').val(location.PhuongXa);
                            $('#locationPhuongXaText').hide();
                            $('#locationPhuongXa').show();
                        } else {
                            // Nếu không có trong dropdown, dùng input text
                            $('#locationPhuongXaText').val(location.PhuongXa);
                            $('#locationPhuongXa').hide();
                            $('#locationPhuongXaText').show();
                            $('#togglePhuongXaLink').text('Chọn từ danh sách');
                        }
                    }
                    
                    $('#locationAddress').val(location.DiaChi || ''); // Địa chỉ đầy đủ (readonly)
                    
                    // Ẩn/hiện giá thuê dựa trên loại địa điểm
                    togglePriceFields();
                    
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
            
            // Lấy giá trị phường/xã từ dropdown hoặc input text
            const phuongXa = $('#locationPhuongXaText').is(':visible') 
                ? $('#locationPhuongXaText').val() 
                : $('#locationPhuongXa').val();
            
            // Cập nhật giá trị phường/xã trong formData
            formData.set('PhuongXa', phuongXa || '');
            
            // Nếu loại địa điểm là "Trong nhà", set giá thuê về null
            const locationType = $('#locationType').val();
            if (locationType === 'Trong nhà') {
                formData.set('GiaThueGio', '');
                formData.set('GiaThueNgay', '');
                formData.set('LoaiThue', '');
            }
            
            // Thêm action vào form data
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
                    
                    // Debug: Ghi log form data
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

        

        // Tự động làm mới mỗi 30 giây
        setInterval(() => {
            loadStatistics();
        }, 30000);
    </script>

<?php include 'includes/admin-footer.php'; ?>