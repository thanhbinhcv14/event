<?php
// Include admin header
include 'includes/admin-header.php';
?>
    
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tags"></i>
                Quản lý loại sự kiện
            </h1>
            <p class="page-subtitle">Quản lý các loại sự kiện trong hệ thống</p>
        </div>
            
        <!-- Error/Success Messages -->
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        
        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-number" id="totalEventTypes">0</div>
                <div class="stat-label">Tổng loại sự kiện</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="activeEventTypes">0</div>
                <div class="stat-label">Đang hoạt động</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-number" id="totalEvents">0</div>
                <div class="stat-label">Sự kiện đã tạo</div>
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
                               placeholder="Nhập tên loại sự kiện, mô tả...">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Sắp xếp</label>
                    <select class="form-select" id="sortBy">
                        <option value="TenLoai">Tên loại</option>
                        <option value="GiaCoBan">Giá cơ bản</option>
                        <option value="NgayTao">Ngày tạo</option>
                        <option value="NgayCapNhat">Ngày cập nhật</option>
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
                    Danh sách loại sự kiện
                </h3>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="showAddEventTypeModal()">
                        <i class="fas fa-plus"></i> Thêm loại sự kiện
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="eventTypesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên loại sự kiện</th>
                            <th>Giá cơ bản</th>
                            <th>Mô tả</th>
                            <th>Ngày tạo</th>
                            <th>Ngày cập nhật</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    
    <!-- Add/Edit Event Type Modal -->
    <div class="modal fade" id="eventTypeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventTypeModalTitle">
                        <i class="fas fa-plus"></i> Thêm loại sự kiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="eventTypeForm">
                        <input type="hidden" id="eventTypeId" name="ID_LoaiSK">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="eventTypeName" class="form-label">Tên loại sự kiện <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="eventTypeName" name="TenLoai" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="eventTypePrice" class="form-label">Giá cơ bản (VNĐ) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="eventTypePrice" name="GiaCoBan" min="0" step="1000" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="eventTypeDescription" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="eventTypeDescription" name="MoTa" rows="4" placeholder="Mô tả chi tiết về loại sự kiện..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveEventType()">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Event Type Modal -->
    <div class="modal fade" id="viewEventTypeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewEventTypeModalTitle">
                        <i class="fas fa-eye"></i> Chi tiết loại sự kiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewEventTypeModalBody">
                    <!-- Event type details will be populated here -->
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
        let eventTypesTable;
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
                eventTypesTable = $('#eventTypesTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '../src/controllers/event-types.php',
                        type: 'GET',
                        data: function(d) {
                            return $.extend({
                                action: 'get_all'
                            }, currentFilters);
                        },
                        dataSrc: function(json) {
                            if (json.success && json.event_types) {
                                return json.event_types;
                            } else {
                                console.error('Invalid data format:', json);
                                return [];
                            }
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTable AJAX Error:', error);
                            AdminPanel.showError('Không thể tải dữ liệu loại sự kiện');
                        }
                    },
                    columns: [
                        { 
                            data: 'ID_LoaiSK', 
                            className: 'text-center',
                            render: function(data) {
                                return `<strong>#${data}</strong>`;
                            }
                        },
                        { 
                            data: 'TenLoai',
                            render: function(data) {
                                return `<strong>${data || 'N/A'}</strong>`;
                            }
                        },
                        { 
                            data: 'GiaCoBan',
                            render: function(data) {
                                return data ? AdminPanel.formatCurrency(data) : 'N/A';
                            }
                        },
                        { 
                            data: 'MoTa',
                            render: function(data) {
                                if (!data) return '<span class="text-muted">Không có mô tả</span>';
                                const shortDesc = data.length > 50 ? data.substring(0, 50) + '...' : data;
                                return `<span title="${data}">${shortDesc}</span>`;
                            }
                        },
                        { 
                            data: 'NgayTao',
                            render: function(data) {
                                return data ? AdminPanel.formatDate(data, 'dd/mm/yyyy hh:mm') : 'N/A';
                            }
                        },
                        { 
                            data: 'NgayCapNhat',
                            render: function(data) {
                                return data ? AdminPanel.formatDate(data, 'dd/mm/yyyy hh:mm') : 'N/A';
                            }
                        },
                        { 
                            data: null,
                            orderable: false,
                            className: 'text-center',
                            render: function(data, type, row) {
                                return `
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm" onclick="viewEventType(${row.ID_LoaiSK})" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="editEventType(${row.ID_LoaiSK})" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteEventType(${row.ID_LoaiSK})" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                `;
                            }
                        }
                    ],
                    order: [[1, 'asc']], // Sort by name by default
                    language: {
                        processing: "Đang xử lý...",
                        search: "Tìm kiếm:",
                        lengthMenu: "Hiển thị _MENU_ mục",
                        info: "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ mục",
                        infoEmpty: "Hiển thị 0 đến 0 trong tổng số 0 mục",
                        infoFiltered: "(đã lọc từ _MAX_ mục)",
                        loadingRecords: "Đang tải...",
                        zeroRecords: "Không tìm thấy dữ liệu",
                        emptyTable: "Không có dữ liệu",
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
            // Search input
            $('#searchInput').on('keyup', function() {
                eventTypesTable.search(this.value).draw();
            });
        }

        function applyFilters() {
            const searchTerm = $('#searchInput').val();
            const sortBy = $('#sortBy').val();
            
            // Apply search
            if (searchTerm) {
                eventTypesTable.search(searchTerm).draw();
            } else {
                eventTypesTable.search('').draw();
            }
            
            // Apply sorting
            let sortColumn = 1; // Default to name column
            let sortDir = 'asc';
            
            switch(sortBy) {
                case 'TenLoai':
                    sortColumn = 1;
                    sortDir = 'asc';
                    break;
                case 'GiaCoBan':
                    sortColumn = 2;
                    sortDir = 'desc';
                    break;
                case 'NgayTao':
                    sortColumn = 4;
                    sortDir = 'desc';
                    break;
                case 'NgayCapNhat':
                    sortColumn = 5;
                    sortDir = 'desc';
                    break;
            }
            
            eventTypesTable.order([sortColumn, sortDir]).draw();
        }

        function clearFilters() {
            $('#searchInput').val('');
            $('#sortBy').val('TenLoai');
            eventTypesTable.search('').order([[1, 'asc']]).draw();
        }

        function clearSearch() {
            $('#searchInput').val('');
            eventTypesTable.search('').draw();
        }

        // Load statistics
        function loadStatistics() {
            AdminPanel.makeAjaxRequest('../src/controllers/event-types.php', {
                action: 'get_stats'
            })
            .then(response => {
                if (response.success) {
                    document.getElementById('totalEventTypes').textContent = response.stats.total_event_types || 0;
                    document.getElementById('activeEventTypes').textContent = response.stats.active_event_types || 0;
                    document.getElementById('totalEvents').textContent = response.stats.total_events || 0;
                }
            })
            .catch(error => {
                console.error('Statistics load error:', error);
            });
        }

        // Show add event type modal
        function showAddEventTypeModal() {
            document.getElementById('eventTypeModalTitle').innerHTML = '<i class="fas fa-plus"></i> Thêm loại sự kiện';
            document.getElementById('eventTypeForm').reset();
            document.getElementById('eventTypeId').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('eventTypeModal'));
            modal.show();
        }

        // Edit event type
        function editEventType(id) {
            AdminPanel.makeAjaxRequest('../src/controllers/event-types.php', {
                action: 'get',
                id: id
            })
            .then(response => {
                if (response.success) {
                    const eventType = response.event_type;
                    document.getElementById('eventTypeModalTitle').innerHTML = '<i class="fas fa-edit"></i> Sửa loại sự kiện';
                    document.getElementById('eventTypeId').value = eventType.ID_LoaiSK;
                    document.getElementById('eventTypeName').value = eventType.TenLoai;
                    document.getElementById('eventTypePrice').value = eventType.GiaCoBan;
                    document.getElementById('eventTypeDescription').value = eventType.MoTa || '';
                    
                    const modal = new bootstrap.Modal(document.getElementById('eventTypeModal'));
                    modal.show();
                } else {
                    AdminPanel.showError(response.error || 'Không thể tải thông tin loại sự kiện');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi tải thông tin loại sự kiện');
            });
        }

        // View event type
        function viewEventType(id) {
            AdminPanel.showLoading('#viewEventTypeModalBody');
            
            const modal = new bootstrap.Modal(document.getElementById('viewEventTypeModal'));
            modal.show();

            AdminPanel.makeAjaxRequest('../src/controllers/event-types.php', {
                action: 'get',
                id: id
            })
            .then(response => {
                if (response.success) {
                    const eventType = response.event_type;
                    const price = new Intl.NumberFormat('vi-VN').format(eventType.GiaCoBan);
                    const createdDate = eventType.NgayTao ? AdminPanel.formatDate(eventType.NgayTao, 'dd/mm/yyyy hh:mm') : 'N/A';
                    const updatedDate = eventType.NgayCapNhat ? AdminPanel.formatDate(eventType.NgayCapNhat, 'dd/mm/yyyy hh:mm') : 'N/A';
                    
                    document.getElementById('viewEventTypeModalTitle').textContent = eventType.TenLoai;
                    document.getElementById('viewEventTypeModalBody').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle text-primary"></i> Thông tin cơ bản</h6>
                                <table class="table table-borderless">
                                    <tr><td><strong>ID:</strong></td><td>#${eventType.ID_LoaiSK}</td></tr>
                                    <tr><td><strong>Tên loại:</strong></td><td>${eventType.TenLoai}</td></tr>
                                    <tr><td><strong>Giá cơ bản:</strong></td><td><span class="text-success fw-bold">${price} VNĐ</span></td></tr>
                                    <tr><td><strong>Ngày tạo:</strong></td><td>${createdDate}</td></tr>
                                    <tr><td><strong>Cập nhật:</strong></td><td>${updatedDate}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-align-left text-primary"></i> Mô tả</h6>
                                <p class="text-muted">${eventType.MoTa || 'Không có mô tả'}</p>
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('viewEventTypeModalBody').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.error || 'Không thể tải chi tiết loại sự kiện'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('viewEventTypeModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải chi tiết loại sự kiện
                    </div>
                `;
            });
        }
        
        // Save event type
        function saveEventType() {
            if (!AdminPanel.validateForm('eventTypeForm')) {
                return;
            }
            
            const formData = new FormData(document.getElementById('eventTypeForm'));
            const isEdit = document.getElementById('eventTypeId').value !== '';
            const action = isEdit ? 'update' : 'add';
            
            formData.append('action', action);
            
            AdminPanel.makeAjaxRequest('../src/controllers/event-types.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess(isEdit ? 'Đã cập nhật loại sự kiện thành công' : 'Đã thêm loại sự kiện thành công');
                    
                    // Close modal
                    const modalElement = document.getElementById('eventTypeModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Reload table
                    eventTypesTable.ajax.reload();
                    loadStatistics();
                } else {
                    AdminPanel.showError(response.error || response.message || 'Có lỗi xảy ra khi lưu loại sự kiện');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi lưu loại sự kiện');
            });
        }
        
        // Delete event type
        function deleteEventType(id) {
            // Get event type name for confirmation
            AdminPanel.makeAjaxRequest('../src/controllers/event-types.php', {
                action: 'get',
                id: id
            })
            .then(response => {
                if (response.success) {
                    const eventType = response.event_type;
                    
                    AdminPanel.sweetConfirm(
                        'Xác nhận xóa',
                        `Bạn có chắc chắn muốn xóa loại sự kiện "${eventType.TenLoai}"? Hành động này không thể hoàn tác.`,
                        () => {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('id', id);
                            
                            AdminPanel.makeAjaxRequest('../src/controllers/event-types.php', formData, 'POST')
                            .then(response => {
                                if (response.success) {
                                    AdminPanel.showSuccess('Đã xóa loại sự kiện thành công');
                                    eventTypesTable.ajax.reload();
                                    loadStatistics();
                                } else {
                                    AdminPanel.showError(response.error || response.message || 'Có lỗi xảy ra khi xóa loại sự kiện');
                                }
                            })
                            .catch(error => {
                                AdminPanel.showError('Có lỗi xảy ra khi xóa loại sự kiện');
                            });
                        }
                    );
                } else {
                    AdminPanel.showError('Không thể tải thông tin loại sự kiện');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi tải thông tin loại sự kiện');
            });
        }
    </script>

<?php include 'includes/admin-footer.php'; ?>
