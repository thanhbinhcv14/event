<?php
// Bao gồm header admin
include 'includes/admin-header.php';
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-door-open"></i>
                Quản lý phòng
            </h1>
            <p class="page-subtitle">Quản lý thông tin và trạng thái các phòng của địa điểm trong nhà</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="stat-number" id="totalRooms">0</div>
                <div class="stat-label">Tổng phòng</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="availableRooms">0</div>
                <div class="stat-label">Sẵn sàng</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-number" id="maintenanceRooms">0</div>
                <div class="stat-label">Bảo trì</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon rejected">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number" id="inUseRooms">0</div>
                <div class="stat-label">Đang sử dụng</div>
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
                               placeholder="Nhập tên phòng hoặc mô tả...">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Địa điểm</label>
                    <select class="form-select" id="locationFilter">
                        <option value="">Tất cả địa điểm</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Sẵn sàng">Sẵn sàng</option>
                        <option value="Đang sử dụng">Đang sử dụng</option>
                        <option value="Bảo trì">Bảo trì</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Sắp xếp</label>
                    <select class="form-select" id="sortBy">
                        <option value="TenPhong">Tên phòng</option>
                        <option value="SucChua">Sức chứa</option>
                        <option value="GiaThueGio">Giá thuê/giờ</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Lọc
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
                    Danh sách phòng
                </h3>
                <div class="action-buttons">
                    <?php if ($user['ID_Role'] == 2): ?>
                    <button class="btn btn-success" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> Thêm phòng
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="roomsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Địa điểm</th>
                            <th>Tên phòng</th>
                            <th>Sức chứa</th>
                            <th>Giá thuê/giờ</th>
                            <th>Giá thuê/ngày</th>
                            <th>Loại thuê</th>
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

        <!-- Add/Edit Room Modal -->
        <div class="modal fade" id="roomModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="roomModalTitle">
                            <i class="fas fa-plus"></i>
                            Thêm phòng mới
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="roomForm">
                            <input type="hidden" id="roomId" name="ID_Phong">
                            
                            <div class="mb-3">
                                <label class="form-label">Địa điểm <span class="text-danger">*</span></label>
                                <select class="form-select" id="roomLocation" name="ID_DD" required>
                                    <option value="">Chọn địa điểm trong nhà</option>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Chỉ có thể thêm phòng cho địa điểm trong nhà. 
                                    <span id="indoorLocationsCount" class="text-primary"></span>
                                </small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Tên phòng <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="roomName" name="TenPhong" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Sức chứa <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="roomCapacity" name="SucChua" min="1" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Giá thuê/giờ (VNĐ)</label>
                                        <input type="number" class="form-control" id="roomPriceHour" name="GiaThueGio" min="0" step="1000">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Giá thuê/ngày (VNĐ)</label>
                                        <input type="number" class="form-control" id="roomPriceDay" name="GiaThueNgay" min="0" step="1000">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Loại thuê <span class="text-danger">*</span></label>
                                        <select class="form-select" id="roomRentType" name="LoaiThue" required>
                                            <option value="Cả hai">Cả hai</option>
                                            <option value="Theo giờ">Theo giờ</option>
                                            <option value="Theo ngày">Theo ngày</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" id="roomDescription" name="MoTa" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Hình ảnh phòng</label>
                                <input type="file" class="form-control" id="roomImage" name="HinhAnh" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> Định dạng: JPG, PNG, GIF, WEBP. Tối đa 5MB.
                                </small>
                                <div id="roomImagePreview" class="mt-2" style="display: none;">
                                    <img id="roomImagePreviewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                                    <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeRoomImage()">
                                        <i class="fas fa-times"></i> Xóa ảnh
                                    </button>
                                </div>
                                <div id="roomCurrentImage" class="mt-2" style="display: none;">
                                    <label class="form-label text-muted">Ảnh hiện tại:</label>
                                    <img id="roomCurrentImageImg" src="" alt="Current" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd;">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select class="form-select" id="roomStatus" name="TrangThai" required>
                                    <option value="Sẵn sàng">Sẵn sàng</option>
                                    <option value="Đang sử dụng">Đang sử dụng</option>
                                    <option value="Bảo trì">Bảo trì</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="saveRoom()">
                            <i class="fas fa-save"></i> Lưu
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Details Modal -->
        <div class="modal fade" id="roomDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-info-circle"></i>
                            Chi tiết phòng
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="roomDetailsContent">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-warning" id="btnEditFromDetails" onclick="" style="display: none;">
                            <i class="fas fa-edit"></i> Chỉnh sửa
                        </button>
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
        
        /* Hiệu ứng phóng to hình ảnh khi hover */
        .room-image-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: zoom-in;
            display: inline-block;
        }
        
        .room-image-hover:hover {
            transform: scale(2.5);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            position: relative;
            border-radius: 8px;
        }
    </style>

    <script>
        // Đảm bảo jQuery đã được load trước khi sử dụng
        (function() {
            // Kiểm tra jQuery đã load chưa
            if (typeof jQuery === 'undefined') {
                console.error('jQuery chưa được load. Vui lòng đợi...');
                // Thử lại sau 100ms
                setTimeout(arguments.callee, 100);
                return;
            }
            
            // jQuery đã sẵn sàng, khởi tạo code
            $(document).ready(function() {
                initRoomsPage();
            });
        })();
        
        let roomsTable;
        let currentFilters = {};
        let allLocations = [];

        // Khởi tạo trang
        function initRoomsPage() {
            loadLocations();
            initializeDataTable();
            loadStatistics();
            setupEventListeners();
        }

        function loadLocations() {
            return AdminPanel.makeAjaxRequest('../src/controllers/locations.php', {
                action: 'get_locations',
                limit: 1000
            })
            .then(response => {
                if (response.success && response.locations) {
                    // Chỉ lấy địa điểm trong nhà
                    allLocations = response.locations.filter(loc => loc.LoaiDiaDiem === 'Trong nhà');
                    
                    // Cập nhật filter dropdown (có thể để tất cả hoặc chỉ trong nhà)
                    const locationFilter = $('#locationFilter');
                    locationFilter.empty();
                    locationFilter.append('<option value="">Tất cả địa điểm</option>');
                    allLocations.forEach(loc => {
                        locationFilter.append(`<option value="${loc.ID_DD}">${loc.TenDiaDiem}</option>`);
                    });
                    
                    // Cập nhật dropdown trong modal (chỉ hiển thị địa điểm trong nhà)
                    const roomLocation = $('#roomLocation');
                    roomLocation.empty();
                    roomLocation.append('<option value="">Chọn địa điểm trong nhà</option>');
                    allLocations.forEach(loc => {
                        roomLocation.append(`<option value="${loc.ID_DD}">${loc.TenDiaDiem}</option>`);
                    });
                    
                    // Cập nhật thông báo số lượng địa điểm trong nhà
                    const countText = allLocations.length > 0 
                        ? `Có ${allLocations.length} địa điểm trong nhà có thể thêm phòng` 
                        : 'Chưa có địa điểm trong nhà nào. Vui lòng thêm địa điểm trong nhà trước.';
                    $('#indoorLocationsCount').text(countText);
                    
                    // Nếu không có địa điểm trong nhà nào, disable dropdown và hiển thị thông báo
                    if (allLocations.length === 0) {
                        $('#roomLocation').prop('disabled', true).append('<option value="" disabled>Không có địa điểm trong nhà</option>');
                    } else {
                        $('#roomLocation').prop('disabled', false);
                    }
                }
                return response;
            })
            .catch(error => {
                console.error('Load locations error:', error);
                throw error;
            });
        }

        function initializeDataTable() {
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables not available');
                AdminPanel.showError('DataTables không khả dụng');
                return;
            }

            try {
                roomsTable = $('#roomsTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '../src/controllers/rooms.php',
                        type: 'GET',
                        data: function(d) {
                            return $.extend({
                                action: 'get_rooms'
                            }, currentFilters);
                        },
                        dataSrc: function(json) {
                            console.log('DataTable response:', json);
                            if (json.success && json.data) {
                                return json.data;
                            } else {
                                console.error('Invalid data format:', json);
                                if (json.error) {
                                    AdminPanel.showError(json.error);
                                }
                                return [];
                            }
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTable AJAX Error:', error);
                            console.error('XHR status:', xhr.status);
                            console.error('XHR response:', xhr.responseText);
                            
                            // Thử parse response để lấy thông báo lỗi
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.error) {
                                    AdminPanel.showError(response.error);
                                } else {
                                    AdminPanel.showError('Không thể tải dữ liệu phòng: ' + error);
                                }
                            } catch (e) {
                                AdminPanel.showError('Không thể tải dữ liệu phòng: ' + error);
                            }
                        }
                    },
                    columns: [
                        { data: 'ID_Phong', className: 'text-center' },
                        { data: 'TenDiaDiem' },
                        { data: 'TenPhong' },
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
                            data: 'TrangThai',
                            render: function(data) {
                                if (!data) return '<span class="status-badge status-unknown">Không xác định</span>';
                                const statusClass = data.toLowerCase().replace(/\s+/g, '-');
                                return `<span class="status-badge status-${statusClass}">${data}</span>`;
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            render: function(data, type, row) {
                                const userRole = <?= $user['ID_Role'] ?? 0 ?>;
                                const isFinanceManager = userRole == 2;
                                return `
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm" onclick="viewRoomDetails(${row.ID_Phong})" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        ${isFinanceManager ? `
                                        <button class="btn btn-warning btn-sm" onclick="editRoom(${row.ID_Phong})" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteRoom(${row.ID_Phong})" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        ` : ''}
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
            AdminPanel.makeAjaxRequest('../src/controllers/rooms.php', {
                action: 'get_rooms'
            })
            .then(response => {
                if (response.success && response.data) {
                    const rooms = response.data;
                    $('#totalRooms').text(rooms.length);
                    $('#availableRooms').text(rooms.filter(r => r.TrangThai === 'Sẵn sàng').length);
                    $('#maintenanceRooms').text(rooms.filter(r => r.TrangThai === 'Bảo trì').length);
                    $('#inUseRooms').text(rooms.filter(r => r.TrangThai === 'Đang sử dụng').length);
                }
            })
            .catch(error => {
                console.error('Statistics load error:', error);
            });
        }

        function setupEventListeners() {
            let searchTimeout;
            $('#searchInput').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    applyFilters();
                }, 300);
            });

            $('#statusFilter, #locationFilter, #sortBy').on('change', function() {
                applyFilters();
            });
        }

        function applyFilters() {
            const searchValue = $('#searchInput').val();
            const statusFilter = $('#statusFilter').val();
            const locationFilter = $('#locationFilter').val();
            
            currentFilters = {};
            if (searchValue) currentFilters.search = searchValue;
            if (statusFilter) currentFilters.status = statusFilter;
            if (locationFilter) currentFilters.location_id = locationFilter;
            
            roomsTable.ajax.reload();
        }

        function clearSearch() {
            $('#searchInput').val('');
            applyFilters();
        }

        function showAddModal() {
            // Đảm bảo đã load danh sách địa điểm trong nhà
            if (allLocations.length === 0) {
                loadLocations();
            }
            
            $('#roomModalTitle').html('<i class="fas fa-plus"></i> Thêm phòng mới');
            $('#roomForm')[0].reset();
            $('#roomId').val('');
            $('#roomLocation').val('');
            $('#roomLocation').prop('disabled', false);
            $('#roomImagePreview').hide();
            $('#roomCurrentImage').hide();
            $('#roomImage').val('');
            const bsModal = new bootstrap.Modal(document.getElementById('roomModal'));
            bsModal.show();
        }
        
        // Xử lý preview hình ảnh khi chọn file
        $('#roomImage').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Kiểm tra kích thước (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    AdminPanel.showError('Kích thước file quá lớn. Tối đa 5MB');
                    $(this).val('');
                    return;
                }
                
                // Kiểm tra định dạng
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    AdminPanel.showError('Định dạng file không được hỗ trợ. Chỉ chấp nhận: JPG, PNG, GIF, WEBP');
                    $(this).val('');
                    return;
                }
                
                // Hiển thị preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#roomImagePreviewImg').attr('src', e.target.result);
                    $('#roomImagePreview').show();
                    $('#roomCurrentImage').hide();
                };
                reader.readAsDataURL(file);
            }
        });
        
        function removeRoomImage() {
            $('#roomImage').val('');
            $('#roomImagePreview').hide();
            $('#roomImagePreviewImg').attr('src', '');
        }

        function editRoom(roomId) {
            AdminPanel.makeAjaxRequest('../src/controllers/rooms.php', {
                action: 'get_room',
                id: roomId
            })
            .then(response => {
                if (response.success && response.data) {
                    const room = response.data;
                    
                    // Đảm bảo dropdown đã load địa điểm trong nhà
                    if (allLocations.length === 0) {
                        loadLocations().then(() => {
                            populateRoomForm(room);
                        });
                    } else {
                        populateRoomForm(room);
                    }
                } else {
                    AdminPanel.showError(response.error || 'Không tìm thấy phòng');
                }
            })
            .catch(error => {
                AdminPanel.showError('Lỗi khi tải thông tin phòng');
            });
        }
        
        function populateRoomForm(room) {
            $('#roomId').val(room.ID_Phong);
            $('#roomLocation').val(room.ID_DD);
            $('#roomName').val(room.TenPhong);
            $('#roomCapacity').val(room.SucChua);
            $('#roomPriceHour').val(room.GiaThueGio);
            $('#roomPriceDay').val(room.GiaThueNgay);
            $('#roomRentType').val(room.LoaiThue);
            $('#roomDescription').val(room.MoTa);
            $('#roomStatus').val(room.TrangThai);
            
            // Hiển thị hình ảnh hiện tại nếu có
            if (room.HinhAnh) {
                const imagePath = `../img/phong/${room.HinhAnh}`;
                $('#roomCurrentImageImg').attr('src', imagePath);
                $('#roomCurrentImage').show();
                $('#roomImagePreview').hide();
            } else {
                $('#roomCurrentImage').hide();
            }
            
            // Reset file input
            $('#roomImage').val('');
            
            // Kiểm tra địa điểm của phòng có phải trong nhà không
            const locationExists = allLocations.find(loc => loc.ID_DD == room.ID_DD);
            if (!locationExists) {
                AdminPanel.showError('Địa điểm của phòng này không phải là địa điểm trong nhà');
                return;
            }
            
            $('#roomModalTitle').html('<i class="fas fa-edit"></i> Chỉnh sửa phòng');
            const bsModal = new bootstrap.Modal(document.getElementById('roomModal'));
            bsModal.show();
        }

        function saveRoom() {
            // Disable nút để tránh double submit
            const saveBtn = $('button[onclick="saveRoom()"]');
            const originalText = saveBtn.html();
            saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
            
            try {
                // Kiểm tra địa điểm có phải trong nhà không
                const selectedLocationId = $('#roomLocation').val();
                if (!selectedLocationId) {
                    AdminPanel.showError('Vui lòng chọn địa điểm trong nhà');
                    saveBtn.prop('disabled', false).html(originalText);
                    return;
                }
                
                // Kiểm tra địa điểm đã chọn có trong danh sách địa điểm trong nhà không
                const selectedLocation = allLocations.find(loc => loc.ID_DD == selectedLocationId);
                if (!selectedLocation) {
                    AdminPanel.showError('Địa điểm đã chọn không phải là địa điểm trong nhà. Vui lòng chọn lại.');
                    saveBtn.prop('disabled', false).html(originalText);
                    return;
                }
                
                // Validate form
                const roomName = $('#roomName').val() ? $('#roomName').val().trim() : '';
                if (!roomName) {
                    AdminPanel.showError('Vui lòng nhập tên phòng');
                    saveBtn.prop('disabled', false).html(originalText);
                    return;
                }
                
                const capacity = parseInt($('#roomCapacity').val());
                if (!capacity || isNaN(capacity) || capacity < 1) {
                    AdminPanel.showError('Vui lòng nhập sức chứa hợp lệ (ít nhất 1 người)');
                    saveBtn.prop('disabled', false).html(originalText);
                    return;
                }
                
                // Validate giá thuê nếu có
                const priceHour = $('#roomPriceHour').val();
                if (priceHour && (isNaN(parseFloat(priceHour)) || parseFloat(priceHour) < 0)) {
                    AdminPanel.showError('Giá thuê/giờ phải là số không âm');
                    saveBtn.prop('disabled', false).html(originalText);
                    return;
                }
                
                const priceDay = $('#roomPriceDay').val();
                if (priceDay && (isNaN(parseFloat(priceDay)) || parseFloat(priceDay) < 0)) {
                    AdminPanel.showError('Giá thuê/ngày phải là số không âm');
                    saveBtn.prop('disabled', false).html(originalText);
                    return;
                }
                
                // Tạo FormData để hỗ trợ upload file
                const formData = new FormData();
                formData.append('action', $('#roomId').val() ? 'update_room' : 'add_room');
                if ($('#roomId').val()) {
                    formData.append('ID_Phong', $('#roomId').val());
                }
                formData.append('ID_DD', selectedLocationId);
                formData.append('TenPhong', roomName);
                formData.append('SucChua', capacity);
                if (priceHour) formData.append('GiaThueGio', parseFloat(priceHour));
                if (priceDay) formData.append('GiaThueNgay', parseFloat(priceDay));
                formData.append('LoaiThue', $('#roomRentType').val());
                if ($('#roomDescription').val()) {
                    formData.append('MoTa', $('#roomDescription').val().trim());
                }
                formData.append('TrangThai', $('#roomStatus').val());
                
                // Thêm file hình ảnh nếu có
                const imageFile = $('#roomImage')[0].files[0];
                if (imageFile) {
                    formData.append('HinhAnh', imageFile);
                }

                // Debug: Log form data (không log file)
                console.log('Saving room with data:', {
                    action: formData.get('action'),
                    ID_Phong: formData.get('ID_Phong'),
                    ID_DD: formData.get('ID_DD'),
                    TenPhong: formData.get('TenPhong'),
                    hasImage: !!imageFile
                });
                
                // Gửi FormData với AJAX
                $.ajax({
                    url: '../src/controllers/rooms.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Parse JSON response
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                        
                        console.log('Save room response:', response);
                        saveBtn.prop('disabled', false).html(originalText);
                        if (response.success) {
                            AdminPanel.showSuccess(response.message || 'Lưu phòng thành công');
                            const bsModal = bootstrap.Modal.getInstance(document.getElementById('roomModal'));
                            if (bsModal) {
                                bsModal.hide();
                            }
                            roomsTable.ajax.reload();
                            loadStatistics();
                        } else {
                            const errorMsg = response.error || 'Lỗi khi lưu phòng';
                            console.error('Save room failed:', response);
                            if (response.debug) {
                                console.error('Debug info:', response.debug);
                            }
                            AdminPanel.showError(errorMsg);
                        }
                    },
                    error: function(xhr, status, error) {
                        saveBtn.prop('disabled', false).html(originalText);
                        console.error('Save room error:', error);
                        console.error('XHR status:', xhr.status);
                        console.error('XHR response:', xhr.responseText);
                        
                        let errorMsg = 'Lỗi khi lưu phòng: ' + (error || 'Lỗi không xác định');
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error) {
                                errorMsg = response.error;
                            }
                        } catch (e) {
                            // Ignore parse error
                        }
                        AdminPanel.showError(errorMsg);
                    }
                });
            } catch (error) {
                saveBtn.prop('disabled', false).html(originalText);
                console.error('Save room validation error:', error);
                AdminPanel.showError('Lỗi xác thực dữ liệu: ' + error.message);
            }
        }

        function viewRoomDetails(roomId) {
            if (!roomId) {
                AdminPanel.showError('ID phòng không hợp lệ');
                return;
            }
            
            AdminPanel.showLoading('#roomDetailsContent');
            
            const modal = new bootstrap.Modal(document.getElementById('roomDetailsModal'));
            modal.show();
            
            // Ẩn nút chỉnh sửa tạm thời
            $('#btnEditFromDetails').hide();
            
            AdminPanel.makeAjaxRequest('../src/controllers/rooms.php', {
                action: 'get_room',
                id: roomId
            })
            .then(response => {
                if (response.success && response.data) {
                    const room = response.data;
                    $('#roomDetailsContent').html(`
                        <div class="row" style="font-size: 0.9rem;">
                            <div class="col-md-6">
                                <h6 style="font-size: 0.95rem;"><i class="fas fa-door-open"></i> Thông tin cơ bản</h6>
                                <table class="table table-sm table-borderless" style="font-size: 0.9rem;">
                                    <tr><td style="width: 40%; padding: 0.3rem 0;"><strong>ID Phòng:</strong></td><td style="padding: 0.3rem 0;">${room.ID_Phong}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Địa điểm:</strong></td><td style="padding: 0.3rem 0;">${room.TenDiaDiem || 'N/A'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Tên phòng:</strong></td><td style="padding: 0.3rem 0;">${room.TenPhong || 'N/A'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Sức chứa:</strong></td><td style="padding: 0.3rem 0;">${room.SucChua ? room.SucChua.toLocaleString() + ' người' : 'N/A'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Giá thuê/giờ:</strong></td><td style="padding: 0.3rem 0;">${room.GiaThueGio ? AdminPanel.formatCurrency(room.GiaThueGio) : 'Chưa có'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Giá thuê/ngày:</strong></td><td style="padding: 0.3rem 0;">${room.GiaThueNgay ? AdminPanel.formatCurrency(room.GiaThueNgay) : 'Chưa có'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Loại thuê:</strong></td><td style="padding: 0.3rem 0;"><span class="badge bg-${room.LoaiThue === 'Cả hai' ? 'success' : (room.LoaiThue === 'Theo giờ' ? 'info' : 'warning')}">${room.LoaiThue || 'Chưa xác định'}</span></td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Trạng thái:</strong></td><td style="padding: 0.3rem 0;"><span class="status-badge status-${room.TrangThai ? room.TrangThai.toLowerCase().replace(/\s+/g, '-') : 'unknown'}">${room.TrangThai || 'Không xác định'}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 style="font-size: 0.95rem;"><i class="fas fa-info-circle"></i> Thông tin khác</h6>
                                <table class="table table-sm table-borderless" style="font-size: 0.9rem;">
                                    <tr><td style="width: 40%; padding: 0.3rem 0;"><strong>Mô tả:</strong></td><td style="padding: 0.3rem 0;">${room.MoTa || 'Không có mô tả'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Hình ảnh:</strong></td><td style="padding: 0.3rem 0;">${room.HinhAnh ? `<img src="../img/phong/${room.HinhAnh}" alt="${room.TenPhong || 'Phòng'}" class="img-fluid rounded room-image-hover" style="max-width: 150px; max-height: 120px; object-fit: cover;" onerror="this.src='../img/phong/default.php'">` : 'Không có hình ảnh'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Ngày tạo:</strong></td><td style="padding: 0.3rem 0;">${room.NgayTao ? AdminPanel.formatDate(room.NgayTao, 'dd/mm/yyyy hh:mm') : 'N/A'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Cập nhật:</strong></td><td style="padding: 0.3rem 0;">${room.NgayCapNhat ? AdminPanel.formatDate(room.NgayCapNhat, 'dd/mm/yyyy hh:mm') : 'N/A'}</td></tr>
                                </table>
                            </div>
                        </div>
                    `);
                    
                    // Hiển thị nút chỉnh sửa và gán sự kiện (chỉ nếu là quản lý tài chính)
                    const userRole = <?= $user['ID_Role'] ?? 0 ?>;
                    if (userRole == 2) {
                        $('#btnEditFromDetails').show().attr('onclick', `editRoom(${roomId})`);
                    } else {
                        $('#btnEditFromDetails').hide();
                    }
                } else {
                    $('#roomDetailsContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.error || 'Không thể tải chi tiết phòng'}
                        </div>
                    `);
                }
            })
            .catch(error => {
                $('#roomDetailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải chi tiết phòng
                    </div>
                `);
            });
        }

        function deleteRoom(roomId) {
            if (!roomId) {
                AdminPanel.showError('ID phòng không hợp lệ');
                return;
            }
            
            if (!confirm('Bạn có chắc chắn muốn xóa phòng này không?\n\nLưu ý: Không thể xóa phòng nếu đang có sự kiện đang diễn ra hoặc sắp diễn ra.')) {
                return;
            }

            // Disable nút xóa để tránh double click
            const deleteBtn = $(`button[onclick="deleteRoom(${roomId})"]`);
            const originalHtml = deleteBtn.html();
            deleteBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            const formData = new FormData();
            formData.append('action', 'delete_room');
            formData.append('id', roomId);
            
            AdminPanel.makeAjaxRequest('../src/controllers/rooms.php', formData, 'POST')
            .then(response => {
                deleteBtn.prop('disabled', false).html(originalHtml);
                if (response.success) {
                    AdminPanel.showSuccess(response.message || 'Xóa phòng thành công');
                    roomsTable.ajax.reload();
                    loadStatistics();
                } else {
                    // Hiển thị lỗi cụ thể từ server (error hoặc message)
                    const errorMessage = response.error || response.message || 'Có lỗi xảy ra khi xóa phòng';
                    AdminPanel.showError(errorMessage);
                }
            })
            .catch(error => {
                deleteBtn.prop('disabled', false).html(originalHtml);
                console.error('Delete room error:', error);
                // Thử parse error response để lấy thông báo cụ thể
                let errorMessage = 'Lỗi khi xóa phòng';
                if (error.response) {
                    try {
                        const errorResponse = typeof error.response === 'string' ? JSON.parse(error.response) : error.response;
                        errorMessage = errorResponse.error || errorResponse.message || errorMessage;
                    } catch (e) {
                        errorMessage = error.message || errorMessage;
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }
                AdminPanel.showError(errorMessage);
            });
        }
    </script>

<?php include 'includes/admin-footer.php'; ?>

