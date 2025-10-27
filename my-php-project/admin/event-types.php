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
                        
                </div>
            </div>
            
            <div id="eventTypesList">
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải danh sách loại sự kiện...</p>
                </div>
            </div>
            
            <!-- Empty State -->
            <div class="empty-state" id="emptyState" style="display: none;">
                <i class="fas fa-tags"></i>
                <h3>Chưa có loại sự kiện nào</h3>
                <p>Hãy thêm loại sự kiện đầu tiên để bắt đầu quản lý.</p>
                <button class="btn btn-primary" onclick="showAddEventTypeModal()">
                    <i class="fas fa-plus"></i> Thêm loại sự kiện
                </button>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
        let allEventTypes = [];
        let filteredEventTypes = [];
        
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadEventTypes();
            loadStatistics();
            
            // Setup filters - only add listeners if elements exist
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', filterEventTypes);
            }
            
            const priceFilter = document.getElementById('priceFilter');
            if (priceFilter) {
                priceFilter.addEventListener('change', filterEventTypes);
            }
            
            const sortBy = document.getElementById('sortBy');
            if (sortBy) {
                sortBy.addEventListener('change', filterEventTypes);
            }
        });
        
        // Load event types
        function loadEventTypes() {
            document.getElementById('eventTypesList').innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải danh sách loại sự kiện...</p>
                </div>
            `;
            
            fetch('../src/controllers/event-types.php?action=get_all')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allEventTypes = data.event_types;
                        filteredEventTypes = allEventTypes;
                        displayEventTypes();
                        updateStatistics();
                    } else {
                        showError('Không thể tải danh sách loại sự kiện: ' + data.error);
                        document.getElementById('eventTypesList').innerHTML = '';
                    }
                })
                .catch(error => {
                    showError('Lỗi kết nối khi tải danh sách loại sự kiện');
                    document.getElementById('eventTypesList').innerHTML = '';
                });
        }
        
        // Display event types
        function displayEventTypes() {
            if (filteredEventTypes.length === 0) {
                document.getElementById('eventTypesList').innerHTML = '';
                document.getElementById('emptyState').style.display = 'block';
                return;
            }
            
            document.getElementById('emptyState').style.display = 'none';
            
            let html = '';
            filteredEventTypes.forEach(eventType => {
                const price = new Intl.NumberFormat('vi-VN').format(eventType.GiaCoBan);
                const createdDate = formatDateTime(eventType.NgayTao);
                const updatedDate = formatDateTime(eventType.NgayCapNhat);
                
                html += `
                    <div class="event-type-card fade-in">
                        <div class="event-type-header">
                            <div class="flex-grow-1">
                                <h3 class="event-type-title">${eventType.TenLoai}</h3>
                                <div class="event-type-price">${price} VNĐ</div>
                            </div>
                        </div>
                        
                        <div class="event-type-description">
                            ${eventType.MoTa || 'Không có mô tả'}
                        </div>
                        
                        <div class="event-type-meta">
                            <small class="text-muted">
                                <i class="fas fa-calendar-plus"></i> Tạo: ${createdDate}
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-calendar-edit"></i> Cập nhật: ${updatedDate}
                            </small>
                        </div>
                        
                        <div class="action-buttons mt-3">
                            <button class="btn btn-primary btn-sm" onclick="viewEventType(${eventType.ID_LoaiSK})">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="editEventType(${eventType.ID_LoaiSK})">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteEventType(${eventType.ID_LoaiSK})">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('eventTypesList').innerHTML = html;
        }
        
        // Filter event types
        function filterEventTypes() {
            const searchInput = document.getElementById('searchInput');
            const priceFilterEl = document.getElementById('priceFilter');
            const sortByEl = document.getElementById('sortBy');
            
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const priceFilter = priceFilterEl ? priceFilterEl.value : '';
            const sortBy = sortByEl ? sortByEl.value : '';
            
            filteredEventTypes = allEventTypes.filter(eventType => {
                const matchesSearch = !searchTerm || 
                    eventType.TenLoai.toLowerCase().includes(searchTerm) ||
                    (eventType.MoTa && eventType.MoTa.toLowerCase().includes(searchTerm));
                
                let matchesPrice = true;
                if (priceFilter) {
                    const price = eventType.GiaCoBan;
                    switch(priceFilter) {
                        case '0-5000000':
                            matchesPrice = price < 5000000;
                            break;
                        case '5000000-10000000':
                            matchesPrice = price >= 5000000 && price < 10000000;
                            break;
                        case '10000000-20000000':
                            matchesPrice = price >= 10000000 && price < 20000000;
                            break;
                        case '20000000+':
                            matchesPrice = price >= 20000000;
                            break;
                    }
                }
                
                return matchesSearch && matchesPrice;
            });
            
            // Sort results
            if (sortBy === 'TenLoai') {
                filteredEventTypes.sort((a, b) => a.TenLoai.localeCompare(b.TenLoai));
            } else if (sortBy === 'GiaCoBan') {
                filteredEventTypes.sort((a, b) => b.GiaCoBan - a.GiaCoBan);
            } else if (sortBy === 'NgayTao') {
                filteredEventTypes.sort((a, b) => new Date(b.NgayTao) - new Date(a.NgayTao));
            }
            
            displayEventTypes();
        }
        
        // Clear search
        function clearSearch() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = '';
            }
            filterEventTypes();
        }
        
        // Clear all filters
        function clearFilters() {
            const searchInput = document.getElementById('searchInput');
            const priceFilterEl = document.getElementById('priceFilter');
            const sortByEl = document.getElementById('sortBy');
            
            if (searchInput) {
                searchInput.value = '';
            }
            if (priceFilterEl) {
                priceFilterEl.value = '';
            }
            if (sortByEl) {
                sortByEl.value = 'TenLoai';
            }
            filterEventTypes();
        }
        
        // Update statistics
        function updateStatistics() {
            const total = allEventTypes.length;
            const active = allEventTypes.length; // All are active for now
            const totalEvents = 0; // This would need to be calculated from events table
            
            document.getElementById('totalEventTypes').textContent = total;
            document.getElementById('activeEventTypes').textContent = active;
            document.getElementById('totalEvents').textContent = totalEvents;
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
        
        // Format date time
        function formatDateTime(dateTimeString) {
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
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
                    const createdDate = formatDateTime(eventType.NgayTao);
                    const updatedDate = formatDateTime(eventType.NgayCapNhat);
                    
                    document.getElementById('viewEventTypeModalTitle').textContent = eventType.TenLoai;
                    document.getElementById('viewEventTypeModalBody').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle text-primary"></i> Thông tin cơ bản</h6>
                                <table class="table table-borderless">
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
                    
                    loadEventTypes();
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
            const eventType = allEventTypes.find(et => et.ID_LoaiSK === id);
            if (!eventType) return;
            
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
                            loadEventTypes();
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
        }
        
        // Show error message
        function showError(message) {
            const errorEl = document.getElementById('errorMessage');
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            setTimeout(() => {
                errorEl.style.display = 'none';
            }, 5000);
        }
        
        // Show success message
        function showSuccess(message) {
            const successEl = document.getElementById('successMessage');
            successEl.textContent = message;
            successEl.style.display = 'block';
            setTimeout(() => {
                successEl.style.display = 'none';
            }, 5000);
        }
    </script>

<?php include 'includes/admin-footer.php'; ?>