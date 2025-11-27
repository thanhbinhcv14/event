<?php
// Bao gồm header admin
include 'includes/admin-header.php';
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tools"></i>
                Quản lý thiết bị & Combo
            </h1>
            <p class="page-subtitle">Quản lý thông tin thiết bị và combo thiết bị sự kiện</p>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="managementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="devices-tab" data-bs-toggle="tab" data-bs-target="#devices" type="button" role="tab" aria-controls="devices" aria-selected="true">
                    <i class="fas fa-tools"></i> Thiết bị
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="combos-tab" data-bs-toggle="tab" data-bs-target="#combos" type="button" role="tab" aria-controls="combos" aria-selected="false">
                    <i class="fas fa-box"></i> Combo thiết bị
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="managementTabsContent">
            <!-- Devices Tab -->
            <div class="tab-pane fade show active" id="devices" role="tabpanel" aria-labelledby="devices-tab">

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
                            <input type="hidden" id="deviceId" name="ID_TB">
                        
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
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Hãng sản xuất</label>
                                    <input type="text" class="form-control" id="deviceManufacturer" name="HangSX" placeholder="Nhập hãng sản xuất">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Đơn vị tính</label>
                                    <input type="text" class="form-control" id="deviceUnit" name="DonViTinh" placeholder="Ví dụ: Cái, Bộ" value="Cái">
                                </div>
                            </div>
                        </div>
                            
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control" id="deviceDescription" name="MoTa" rows="3" placeholder="Nhập mô tả thiết bị"></textarea>
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
            </div>
            <!-- End Devices Tab -->

            <!-- Combos Tab -->
            <div class="tab-pane fade" id="combos" role="tabpanel" aria-labelledby="combos-tab">
                <!-- Combo Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-number" id="totalCombos">0</div>
                        <div class="stat-label">Tổng combo</div>
                    </div>
                </div>

                <!-- Combo Filter Section -->
                <div class="filter-section mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tìm kiếm</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="comboSearchInput" 
                                       placeholder="Nhập tên combo...">
                                <button class="btn btn-outline-secondary" type="button" onclick="clearComboSearch()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Giá từ (VNĐ)</label>
                            <input type="number" class="form-control" id="comboPriceMin" 
                                   placeholder="Từ" min="0" step="1000">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Giá đến (VNĐ)</label>
                            <input type="number" class="form-control" id="comboPriceMax" 
                                   placeholder="Đến" min="0" step="1000">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tần suất từ</label>
                            <input type="number" class="form-control" id="comboUsageMin" 
                                   placeholder="Từ" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tần suất đến</label>
                            <input type="number" class="form-control" id="comboUsageMax" 
                                   placeholder="Đến" min="0">
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-12">
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary" onclick="applyComboFilters()">
                                    <i class="fas fa-filter"></i> Lọc
                                </button>
                                <button class="btn btn-outline-secondary" onclick="clearComboFilters()">
                                    <i class="fas fa-times"></i> Xóa bộ lọc
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Combo Table Container -->
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0">
                            <i class="fas fa-box"></i>
                            Danh sách combo thiết bị
                        </h3>
                        <div class="action-buttons">
                            <button class="btn btn-success" onclick="showAddComboModal()">
                                <i class="fas fa-plus"></i> Thêm combo
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="combosTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên combo</th>
                                    <th>Mô tả</th>
                                    <th>Số thiết bị</th>
                                    <th>Giá combo</th>
                                    <th>Số lần sử dụng</th>
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
            </div>
            <!-- End Combos Tab -->
        </div>
        <!-- End Tab Content -->

        <!-- Add/Edit Combo Modal -->
        <div class="modal fade" id="comboModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="comboModalTitle">
                            <i class="fas fa-plus"></i>
                            Thêm combo mới
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="comboForm">
                            <input type="hidden" id="comboId" name="ID_Combo">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tên combo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="comboName" name="TenCombo" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Giá combo (VNĐ) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="comboPrice" name="GiaCombo" min="0" step="1000" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" id="comboDescription" name="MoTa" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Thiết bị đã chọn <span class="text-danger">*</span></label>
                                <div id="selectedEquipmentList" class="border rounded p-3 mb-3" style="min-height: 80px; max-height: 200px; overflow-y: auto; background-color: #f8f9fa;">
                                    <p class="text-muted mb-0">Chưa có thiết bị nào được chọn</p>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Chọn thiết bị <span class="text-danger">*</span></label>
                                <div class="border rounded p-2" style="max-height: 350px; overflow-y: auto;">
                                    <div id="equipmentList" class="row g-2">
                                        <!-- Equipment items will be loaded here -->
                                    </div>
                                </div>
                                <small class="form-text text-muted">Chọn thiết bị và số lượng cho combo</small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" onclick="saveCombo()">
                            <i class="fas fa-save"></i> Lưu
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Combo Modal -->
        <div class="modal fade" id="viewComboModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-lg" style="max-width: 800px; width: auto;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eye"></i>
                            Chi tiết combo
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="viewComboModalBody" style="min-height: 200px;">
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
        
        /* Hiệu ứng phóng to hình ảnh khi hover */
        .device-image-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: zoom-in;
            display: inline-block;
        }
        
        .device-image-hover:hover {
            transform: scale(2.5);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            position: relative;
            border-radius: 8px;
        }

        /* Fix table width issues */
        .table-container {
            width: 100%;
            overflow-x: visible;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table-responsive table {
            width: 100% !important;
            min-width: 100%;
            table-layout: auto;
        }

        /* Đảm bảo DataTable không bị thu nhỏ */
        #devicesTable,
        #combosTable {
            width: 100% !important;
            table-layout: auto !important;
        }

        #devicesTable_wrapper,
        #combosTable_wrapper {
            width: 100% !important;
        }

        #devicesTable_wrapper .dataTables_scroll,
        #combosTable_wrapper .dataTables_scroll {
            width: 100% !important;
        }

        /* Fix cho tab content */
        .tab-content {
            width: 100%;
        }

        .tab-pane {
            width: 100%;
        }

        /* Combo Modal - Giống device modal nhưng to hơn một chút */
        #comboModal .modal-dialog {
            max-width: 900px !important;
        }

        #comboModal .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }

        /* Equipment Card Styles - Nhỏ gọn hơn */
        .equipment-card {
            font-size: 0.85rem;
        }

        .equipment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .equipment-card.border-primary {
            border-width: 2px !important;
        }

        /* Selected Equipment List - Dễ quan sát hơn */
        #selectedEquipmentList .table thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
            font-size: 0.8rem;
            padding: 0.5rem;
        }

        #selectedEquipmentList .table tbody td {
            padding: 0.5rem;
            vertical-align: middle;
        }

        #selectedEquipmentList .table tfoot th {
            font-weight: 700;
            padding: 0.75rem 0.5rem;
        }

        /* Responsive cho mobile */
        @media (max-width: 768px) {
            #comboModal .modal-dialog {
                max-width: 95% !important;
            }
            
            .equipment-card {
                font-size: 0.8rem;
            }
        }
    </style>

    <script>
        let devicesTable;
        let combosTable;
        let currentFilters = {};
        let allDevices = [];
        let selectedComboEquipment = [];
        let comboPriceUsageFilter = null;

        // Khởi tạo trang
        document.addEventListener('DOMContentLoaded', function() {
            // Restore tab từ localStorage
            restoreActiveTab();
            
            initializeDataTable();
            initializeCombosTable();
            loadStatistics();
            loadComboStatistics();
            setupEventListeners();
            loadAllDevices();
            
            // Tab change events - Lưu tab hiện tại vào localStorage
            const devicesTab = document.querySelector('#devices-tab');
            const combosTab = document.querySelector('#combos-tab');
            
            if (devicesTab) {
                devicesTab.addEventListener('shown.bs.tab', function() {
                    localStorage.setItem('deviceManagementTab', 'devices');
                    // Đóng tất cả modal đang mở và trigger resize
                    const openModals = document.querySelectorAll('.modal.show');
                    openModals.forEach(modalEl => {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                    });
                    // Reload và adjust devices table
                    if (devicesTable) {
                        setTimeout(() => {
                            devicesTable.columns.adjust();
                            window.dispatchEvent(new Event('resize'));
                        }, 150);
                    }
                });
            }
            
            if (combosTab) {
                combosTab.addEventListener('shown.bs.tab', function() {
                    localStorage.setItem('deviceManagementTab', 'combos');
                    // Đóng tất cả modal đang mở và trigger resize
                    const openModals = document.querySelectorAll('.modal.show');
                    openModals.forEach(modalEl => {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                    });
                    // Reload và adjust combos table
                    if (combosTable) {
                        combosTable.ajax.reload(null, false);
                        setTimeout(() => {
                            combosTable.columns.adjust();
                            window.dispatchEvent(new Event('resize'));
                        }, 150);
                    }
                });
            }
        });

        // Restore active tab từ localStorage
        function restoreActiveTab() {
            const savedTab = localStorage.getItem('deviceManagementTab');
            
            if (savedTab === 'combos') {
                // Remove active class từ devices tab
                const devicesTab = document.querySelector('#devices-tab');
                const combosTab = document.querySelector('#combos-tab');
                const devicesPane = document.querySelector('#devices');
                const combosPane = document.querySelector('#combos');
                
                if (devicesTab && combosTab && devicesPane && combosPane) {
                    devicesTab.classList.remove('active');
                    devicesTab.setAttribute('aria-selected', 'false');
                    combosTab.classList.add('active');
                    combosTab.setAttribute('aria-selected', 'true');
                    
                    devicesPane.classList.remove('show', 'active');
                    combosPane.classList.add('show', 'active');
                    
                    // Trigger Bootstrap tab event để đảm bảo tab được kích hoạt đúng
                    setTimeout(function() {
                        const tab = new bootstrap.Tab(combosTab);
                        tab.show();
                    }, 50);
                }
            }
            // Nếu savedTab là 'devices' hoặc null, giữ mặc định (devices tab active)
        }

        function initializeDataTable() {
            // Kiểm tra DataTables có sẵn không
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables not available');
                AdminPanel.showError('DataTables không khả dụng');
                return;
            }

            try {
                devicesTable = $('#devicesTable').DataTable({
                processing: true,
                serverSide: false,
                autoWidth: false,
                scrollX: false,
                ajax: {
                    url: '../src/controllers/deviceedit.php',
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
                                return `<img src="../img/thietbi/${data}" alt="Hình ảnh thiết bị" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">`;
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
                paging: false, // Ẩn phân trang (hiển thị tất cả dữ liệu)
                drawCallback: function() {
                    // Đảm bảo table không bị thu nhỏ sau khi render
                    this.api().columns.adjust();
                }
            });
            
            // Điều chỉnh lại column width sau khi table được render
            setTimeout(function() {
                if (devicesTable) {
                    devicesTable.columns.adjust();
                }
            }, 100);
            } catch (error) {
                console.error('Error initializing DataTable:', error);
                AdminPanel.showError('Lỗi khởi tạo bảng dữ liệu');
            }
        }

        function loadStatistics() {
            AdminPanel.makeAjaxRequest('../src/controllers/deviceedit.php', {
                action: 'get_stats'
            })
            .then(response => {
                if (response.success && response.stats) {
                    $('#totalDevices').text(response.stats.total || 0);
                    // Tính toán các thống kê khác từ dữ liệu get_all
                    AdminPanel.makeAjaxRequest('../src/controllers/deviceedit.php', {
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
            devicesTable.search(searchValue).draw();
            
            // Áp dụng bộ lọc cột
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
            
            // Áp dụng sắp xếp
            if (sortBy === 'TenThietBi') {
                devicesTable.order([1, 'asc']).draw();
            } else if (sortBy === 'LoaiThietBi') {
                devicesTable.order([2, 'asc']).draw();
            } else if (sortBy === 'GiaThue') {
                devicesTable.order([5, 'desc']).draw();
            } else if (sortBy === 'NgayTao') {
                devicesTable.order([7, 'desc']).draw();
            }
            
            // Vẽ lại bảng
            devicesTable.draw();
        }

        function clearFilters() {
            $('#searchInput').val('');
            $('#statusFilter').val('');
            $('#typeFilter').val('');
            $('#sortBy').val('TenThietBi');
            
            // Xóa tất cả bộ lọc DataTable
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
            // Hiển thị modal ngay lập tức với loading state
            const modal = new bootstrap.Modal(document.getElementById('deviceModal'));
            modal.show();
            
            // Hiển thị loading trong modal body
            const modalBody = document.querySelector('#deviceModal .modal-body');
            const originalContent = modalBody.innerHTML;
            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-3 text-muted">Đang tải dữ liệu...</p>
                </div>
            `;
            
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('../src/controllers/deviceedit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Khôi phục nội dung modal
                modalBody.innerHTML = originalContent;
                
                if (data.success && data.device) {
                    const device = data.device;
                    
                    // Điền dữ liệu vào form
                    $('#deviceId').val(device.ID_TB || '');
                    $('#deviceName').val(device.TenThietBi || '');
                    $('#deviceType').val(device.LoaiThietBi || '');
                    $('#deviceManufacturer').val(device.HangSX || '');
                    $('#deviceUnit').val(device.DonViTinh || 'Cái');
                    $('#deviceDescription').val(device.MoTa || '');
                    $('#devicePrice').val(device.GiaThue || 0);
                    $('#deviceQuantity').val(device.SoLuong || 1);
                    $('#deviceStatus').val(device.TrangThai || 'Sẵn sàng');
                    
                    // Hiển thị hình ảnh hiện tại nếu có
                    if (device.HinhAnh) {
                        $('#currentImage').attr('src', `../img/thietbi/${device.HinhAnh}`);
                        $('#currentImageContainer').show();
                    } else {
                        $('#currentImageContainer').hide();
                    }
                    
                    // Reset file input
                    $('#deviceImage').val('');
                    
                    // Cập nhật tiêu đề modal
                    $('#deviceModalTitle').html('<i class="fas fa-edit"></i> Chỉnh sửa thiết bị');
                    
                    // Xóa các class validation nếu có
                    $('#deviceForm').find('.is-invalid').removeClass('is-invalid');
                } else {
                    modal.hide();
                    AdminPanel.showError(data.error || data.message || 'Không thể tải thông tin thiết bị');
                }
            })
            .catch(error => {
                console.error('Error loading device:', error);
                modal.hide();
                AdminPanel.showError('Có lỗi xảy ra khi tải thông tin thiết bị. Vui lòng thử lại.');
            });
        }

        function viewDevice(id) {
            AdminPanel.showLoading('#viewModalBody');
            
            const modal = new bootstrap.Modal(document.getElementById('viewModal'));
            modal.show();

            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('../src/controllers/deviceedit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const device = data.device;
                    const statusMap = {
                        'Sẵn sàng': { class: 'ready', text: 'Sẵn sàng', icon: 'fa-check-circle' },
                        'Đang sử dụng': { class: 'in-use', text: 'Đang sử dụng', icon: 'fa-clock' },
                        'Bảo trì': { class: 'maintenance', text: 'Bảo trì', icon: 'fa-wrench' },
                        'Hỏng': { class: 'broken', text: 'Hỏng', icon: 'fa-exclamation-triangle' }
                    };
                    const status = statusMap[device.TrangThai] || { class: 'ready', text: device.TrangThai || 'Không xác định', icon: 'fa-question' };
                    
                    $('#viewModalBody').html(`
                        <div class="row" style="font-size: 0.9rem;">
                            <div class="col-md-6">
                                <h6 style="font-size: 0.95rem;"><i class="fas fa-tools"></i> Thông tin cơ bản</h6>
                                <table class="table table-sm table-borderless" style="font-size: 0.9rem;">
                                    <tr><td style="width: 40%; padding: 0.3rem 0;"><strong>ID thiết bị:</strong></td><td style="padding: 0.3rem 0;">${device.ID_TB}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Tên thiết bị:</strong></td><td style="padding: 0.3rem 0;">${device.TenThietBi || 'N/A'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Loại thiết bị:</strong></td><td style="padding: 0.3rem 0;"><span class="badge bg-info">${device.LoaiThietBi || 'N/A'}</span></td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Hãng sản xuất:</strong></td><td style="padding: 0.3rem 0;">${device.HangSX || 'N/A'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Số lượng:</strong></td><td style="padding: 0.3rem 0;">${device.SoLuong ? device.SoLuong.toLocaleString() : 'N/A'} ${device.DonViTinh || 'cái'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Giá thuê:</strong></td><td style="padding: 0.3rem 0;">${device.GiaThue ? AdminPanel.formatCurrency(device.GiaThue) : 'Chưa có'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Trạng thái:</strong></td><td style="padding: 0.3rem 0;"><span class="status-badge status-${status.class}"><i class="fas ${status.icon}"></i> ${status.text}</span></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 style="font-size: 0.95rem;"><i class="fas fa-info-circle"></i> Thông tin khác</h6>
                                <table class="table table-sm table-borderless" style="font-size: 0.9rem;">
                                    <tr><td style="width: 40%; padding: 0.3rem 0;"><strong>Mô tả:</strong></td><td style="padding: 0.3rem 0;">${device.MoTa || 'Không có mô tả'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Hình ảnh:</strong></td><td style="padding: 0.3rem 0;">${device.HinhAnh ? `<img src="../img/thietbi/${device.HinhAnh}" alt="${device.TenThietBi || 'Thiết bị'}" class="img-fluid rounded device-image-hover" style="max-width: 150px; max-height: 120px; object-fit: cover;" onerror="this.src='../img/logo/logo.jpg'">` : 'Không có hình ảnh'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Ngày tạo:</strong></td><td style="padding: 0.3rem 0;">${device.NgayTao ? AdminPanel.formatDate(device.NgayTao, 'dd/mm/yyyy hh:mm') : 'N/A'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Cập nhật:</strong></td><td style="padding: 0.3rem 0;">${device.NgayCapNhat ? AdminPanel.formatDate(device.NgayCapNhat, 'dd/mm/yyyy hh:mm') : 'N/A'}</td></tr>
                                </table>
                            </div>
                        </div>
                    `);
                } else {
                    $('#viewModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${data.error || data.message || 'Không thể tải chi tiết thiết bị'}
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
            
            // Thêm action vào form data
            formData.append('action', action);

            AdminPanel.makeAjaxRequest('../src/controllers/deviceedit.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess(isEdit ? 'Đã cập nhật thiết bị thành công' : 'Đã thêm thiết bị thành công');
                    bootstrap.Modal.getInstance(document.getElementById('deviceModal')).hide();
                    devicesTable.ajax.reload();
                    loadStatistics();
                } else {
                    AdminPanel.showError(response.error || response.message || 'Có lỗi xảy ra khi lưu thiết bị');
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
                    
                    AdminPanel.makeAjaxRequest('../src/controllers/deviceedit.php', formData, 'POST')
                    .then(response => {
                        if (response.success) {
                            AdminPanel.showSuccess('Đã xóa thiết bị thành công');
                            devicesTable.ajax.reload();
                            loadStatistics();
                        } else {
                            AdminPanel.showError(response.error || response.message || 'Có lỗi xảy ra khi xóa thiết bị');
                        }
                    })
                    .catch(error => {
                        AdminPanel.showError('Có lỗi xảy ra khi xóa thiết bị');
                    });
                }
            );
        }

        

        // Tự động làm mới mỗi 30 giây
        setInterval(() => {
            loadStatistics();
            loadComboStatistics();
        }, 30000);

        // ========== COMBO MANAGEMENT FUNCTIONS ==========
        
        function initializeCombosTable() {
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables not available');
                return;
            }

            try {
                combosTable = $('#combosTable').DataTable({
                    processing: true,
                    serverSide: false,
                    autoWidth: false,
                    scrollX: false,
                    ajax: {
                        url: '../src/controllers/combo-management.php',
                        type: 'GET',
                        data: function(d) {
                            d.action = 'get_all';
                            return $.extend(d, currentFilters);
                        },
                        dataSrc: function(json) {
                            if (json.success && json.combos) {
                                return json.combos;
                            } else {
                                console.error('Invalid data format:', json);
                                return [];
                            }
                        },
                        error: function(xhr, error, thrown) {
                            console.error('Combo DataTable AJAX Error:', error);
                            AdminPanel.showError('Không thể tải dữ liệu combo');
                        }
                    },
                    columns: [
                        { data: 'ID_Combo', className: 'text-center' },
                        { data: 'TenCombo' },
                        { 
                            data: 'MoTa',
                            render: function(data) {
                                return data ? (data.length > 50 ? data.substring(0, 50) + '...' : data) : 'Không có mô tả';
                            }
                        },
                        { 
                            data: 'SoThietBi',
                            className: 'text-center',
                            render: function(data) {
                                return data || 0;
                            }
                        },
                        { 
                            data: 'GiaCombo',
                            render: function(data) {
                                return AdminPanel.formatCurrency(data);
                            }
                        },
                        { 
                            data: 'SoLanSuDung',
                            className: 'text-center',
                            render: function(data) {
                                return data || 0;
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
                                        <button class="btn btn-info btn-sm" onclick="viewCombo(${row.ID_Combo})" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="editCombo(${row.ID_Combo})" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteCombo(${row.ID_Combo})" title="Xóa">
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
                    dom: 'rtip',
                    info: false,
                    paging: false,
                    drawCallback: function() {
                        // Đảm bảo table không bị thu nhỏ sau khi render
                        this.api().columns.adjust();
                    }
                });
                
                // Điều chỉnh lại column width sau khi table được render
                setTimeout(function() {
                    if (combosTable) {
                        combosTable.columns.adjust();
                    }
                }, 100);
            } catch (error) {
                console.error('Error initializing Combo DataTable:', error);
                AdminPanel.showError('Lỗi khởi tạo bảng combo');
            }
        }

        function loadComboStatistics() {
            AdminPanel.makeAjaxRequest('../src/controllers/combo-management.php', {
                action: 'get_stats'
            })
            .then(response => {
                if (response.success && response.stats) {
                    $('#totalCombos').text(response.stats.total || 0);
                }
            })
            .catch(error => {
                console.error('Combo statistics load error:', error);
            });
        }

        function loadAllDevices() {
            AdminPanel.makeAjaxRequest('../src/controllers/deviceedit.php', {
                action: 'get_all'
            })
            .then(response => {
                if (response.success && response.devices) {
                    allDevices = response.devices;
                    renderEquipmentList();
                }
            })
            .catch(error => {
                console.error('Error loading devices:', error);
            });
        }

        function renderEquipmentList() {
            const container = document.getElementById('equipmentList');
            if (!container) return;
            
            container.innerHTML = '';
            
            if (allDevices.length === 0) {
                container.innerHTML = '<p class="text-muted">Không có thiết bị nào</p>';
                return;
            }
            
            // Group by type
            const grouped = {};
            allDevices.forEach(device => {
                const type = device.LoaiThietBi || 'Khác';
                if (!grouped[type]) {
                    grouped[type] = [];
                }
                grouped[type].push(device);
            });
            
            Object.keys(grouped).forEach(type => {
                const typeDiv = document.createElement('div');
                typeDiv.className = 'col-12 mb-3';
                typeDiv.innerHTML = `<h6 class="text-primary"><i class="fas fa-tag"></i> ${type}</h6>`;
                
                const devicesDiv = document.createElement('div');
                devicesDiv.className = 'row g-2';
                
                grouped[type].forEach(device => {
                    const isSelected = selectedComboEquipment.find(eq => eq.ID_TB == device.ID_TB);
                    const quantity = isSelected ? isSelected.SoLuong : 0;
                    
                    const deviceCard = document.createElement('div');
                    deviceCard.className = 'col-md-6 col-lg-4 col-xl-3';
                    deviceCard.innerHTML = `
                        <div class="card ${isSelected ? 'border-primary bg-light' : ''} equipment-card" style="cursor: pointer; transition: all 0.2s;" onclick="toggleEquipment(${device.ID_TB})">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-start">
                                    <input type="checkbox" class="form-check-input mt-1 me-2" ${isSelected ? 'checked' : ''} 
                                           onchange="toggleEquipment(${device.ID_TB})" onclick="event.stopPropagation()">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <h6 class="mb-1" style="font-size: 0.85rem; font-weight: 600; line-height: 1.2;">${device.TenThietBi || 'N/A'}</h6>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <small class="text-primary fw-bold">${AdminPanel.formatCurrency(device.GiaThue || 0)}</small>
                                            ${device.HangSX ? `<small class="text-muted" style="font-size: 0.7rem;">${device.HangSX}</small>` : ''}
                                        </div>
                                        ${isSelected ? `
                                            <div class="mt-2 pt-2 border-top">
                                                <label class="form-label small mb-1" style="font-size: 0.75rem;">Số lượng:</label>
                                                <input type="number" class="form-control form-control-sm" 
                                                       value="${quantity}" min="1" max="${device.SoLuong || 999}"
                                                       onchange="updateEquipmentQuantity(${device.ID_TB}, this.value)"
                                                       onclick="event.stopPropagation()"
                                                       style="font-size: 0.8rem;">
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    devicesDiv.appendChild(deviceCard);
                });
                
                typeDiv.appendChild(devicesDiv);
                container.appendChild(typeDiv);
            });
        }

        function toggleEquipment(deviceId) {
            const device = allDevices.find(d => d.ID_TB == deviceId);
            if (!device) return;
            
            const index = selectedComboEquipment.findIndex(eq => eq.ID_TB == deviceId);
            
            if (index >= 0) {
                // Remove
                selectedComboEquipment.splice(index, 1);
            } else {
                // Add
                selectedComboEquipment.push({
                    ID_TB: deviceId,
                    TenThietBi: device.TenThietBi,
                    SoLuong: 1
                });
            }
            
            renderEquipmentList();
            renderSelectedEquipment();
        }

        function updateEquipmentQuantity(deviceId, quantity) {
            const index = selectedComboEquipment.findIndex(eq => eq.ID_TB == deviceId);
            if (index >= 0) {
                const qty = parseInt(quantity) || 1;
                selectedComboEquipment[index].SoLuong = qty;
                renderSelectedEquipment();
            }
        }

        function renderSelectedEquipment() {
            const container = document.getElementById('selectedEquipmentList');
            if (!container) return;
            
            if (selectedComboEquipment.length === 0) {
                container.innerHTML = '<p class="text-muted mb-0 text-center py-2"><i class="fas fa-info-circle"></i> Chưa có thiết bị nào được chọn</p>';
                return;
            }
            
            let totalCost = 0;
            let html = '<div class="table-responsive" style="max-height: 180px; overflow-y: auto;">';
            html += '<table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">';
            html += '<thead class="table-light sticky-top"><tr><th style="width: 40%;">Thiết bị</th><th style="width: 15%;" class="text-center">SL</th><th style="width: 20%;" class="text-end">Đơn giá</th><th style="width: 20%;" class="text-end">Thành tiền</th><th style="width: 5%;" class="text-center"></th></tr></thead><tbody>';
            
            selectedComboEquipment.forEach((item, index) => {
                const device = allDevices.find(d => d.ID_TB == item.ID_TB);
                const price = device ? parseFloat(device.GiaThue || 0) : 0;
                const total = price * item.SoLuong;
                totalCost += total;
                
                html += `
                    <tr>
                        <td><strong style="font-size: 0.9rem;">${item.TenThietBi || 'N/A'}</strong></td>
                        <td class="text-center"><span class="badge bg-primary">${item.SoLuong}</span></td>
                        <td class="text-end">${AdminPanel.formatCurrency(price)}</td>
                        <td class="text-end"><strong class="text-success">${AdminPanel.formatCurrency(total)}</strong></td>
                        <td class="text-center">
                            <button class="btn btn-danger btn-sm" onclick="removeEquipment(${item.ID_TB})" title="Xóa">
                                <i class="fas fa-times" style="font-size: 0.7rem;"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody>';
            html += `<tfoot class="table-light"><tr><th colspan="3" class="text-end">Tổng cộng:</th><th class="text-end text-success" style="font-size: 1rem;">${AdminPanel.formatCurrency(totalCost)}</th><th></th></tr></tfoot>`;
            html += '</table></div>';
            container.innerHTML = html;
        }

        function removeEquipment(deviceId) {
            const index = selectedComboEquipment.findIndex(eq => eq.ID_TB == deviceId);
            if (index >= 0) {
                selectedComboEquipment.splice(index, 1);
                renderEquipmentList();
                renderSelectedEquipment();
            }
        }

        function showAddComboModal() {
            $('#comboForm')[0].reset();
            $('#comboId').val('');
            $('#comboModalTitle').html('<i class="fas fa-plus"></i> Thêm combo mới');
            $('#comboPrice').prop('readonly', false);
            $('#comboPrice').removeClass('bg-light');
            $('#comboPrice').next('.form-text.text-warning').remove();
            selectedComboEquipment = [];
            renderEquipmentList();
            renderSelectedEquipment();
            
            const modal = new bootstrap.Modal(document.getElementById('comboModal'));
            modal.show();
        }

        function editCombo(id) {
            // Hiển thị modal ngay lập tức với loading state
            const modalElement = document.getElementById('comboModal');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            // Hiển thị loading trong modal body
            const modalBody = modalElement.querySelector('.modal-body');
            const originalContent = modalBody.innerHTML;
            modalBody.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-3 text-muted">Đang tải dữ liệu...</p>
                </div>
            `;
            
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('../src/controllers/combo-management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Khôi phục nội dung modal
                modalBody.innerHTML = originalContent;
                
                if (data.success && data.combo) {
                    const combo = data.combo;
                    
                    // Điền dữ liệu vào form
                    $('#comboId').val(combo.ID_Combo || '');
                    $('#comboName').val(combo.TenCombo || '');
                    $('#comboDescription').val(combo.MoTa || '');
                    $('#comboPrice').val(combo.GiaCombo || 0);
                    
                    // Đảm bảo trường giá luôn có thể chỉnh sửa
                    // Giá đã lưu trong chitietdatsukien sẽ không bị ảnh hưởng
                    $('#comboPrice').prop('readonly', false);
                    $('#comboPrice').removeClass('bg-light');
                    $('#comboPrice').next('.form-text.text-warning').remove();
                    
                    // Load equipment
                    selectedComboEquipment = (combo.equipment || []).map(eq => ({
                        ID_TB: eq.ID_TB,
                        TenThietBi: eq.TenThietBi,
                        SoLuong: eq.SoLuong
                    }));
                    
                    renderEquipmentList();
                    renderSelectedEquipment();
                    
                    // Cập nhật tiêu đề modal
                    $('#comboModalTitle').html('<i class="fas fa-edit"></i> Chỉnh sửa combo');
                    
                    // Xóa các class validation nếu có
                    $('#comboForm').find('.is-invalid').removeClass('is-invalid');
                } else {
                    modal.hide();
                    AdminPanel.showError(data.error || data.message || 'Không thể tải thông tin combo');
                }
            })
            .catch(error => {
                console.error('Error loading combo:', error);
                modal.hide();
                AdminPanel.showError('Có lỗi xảy ra khi tải thông tin combo. Vui lòng thử lại.');
            });
        }

        function viewCombo(id) {
            // Đóng modal cũ nếu có
            const existingModal = bootstrap.Modal.getInstance(document.getElementById('viewComboModal'));
            if (existingModal) {
                existingModal.hide();
            }
            
            // Hiển thị loading
            AdminPanel.showLoading('#viewComboModalBody');
            
            // Tạo modal mới và hiển thị
            const modalElement = document.getElementById('viewComboModal');
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true
            });
            
            // Event listener để fix layout khi modal được hiển thị
            const resizeHandler = function() {
                // Trigger resize để fix layout
                setTimeout(() => {
                    window.dispatchEvent(new Event('resize'));
                    // Force modal to recalculate size
                    const modalDialog = modalElement.querySelector('.modal-dialog');
                    if (modalDialog) {
                        modalDialog.style.width = 'auto';
                        modalDialog.style.maxWidth = '800px';
                    }
                }, 50);
            };
            
            // Remove existing listener nếu có
            modalElement.removeEventListener('shown.bs.modal', resizeHandler);
            // Add new listener
            modalElement.addEventListener('shown.bs.modal', resizeHandler, { once: true });
            
            modal.show();

            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            
            fetch('../src/controllers/combo-management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const combo = data.combo;
                    let html = `
                        <div class="row" style="font-size: 0.9rem;">
                            <div class="col-md-6">
                                <h6 style="font-size: 0.95rem;"><i class="fas fa-info-circle text-primary"></i> Thông tin cơ bản</h6>
                                <table class="table table-sm table-borderless" style="font-size: 0.9rem;">
                                    <tr><td style="width: 40%; padding: 0.3rem 0;"><strong>ID Combo:</strong></td><td style="padding: 0.3rem 0;">${combo.ID_Combo}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Tên combo:</strong></td><td style="padding: 0.3rem 0;">${combo.TenCombo || 'N/A'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Giá combo:</strong></td><td style="padding: 0.3rem 0;">${AdminPanel.formatCurrency(combo.GiaCombo || 0)}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Mô tả:</strong></td><td style="padding: 0.3rem 0;">${combo.MoTa || 'Không có mô tả'}</td></tr>
                                    <tr><td style="padding: 0.3rem 0;"><strong>Ngày tạo:</strong></td><td style="padding: 0.3rem 0;">${AdminPanel.formatDate(combo.NgayTao, 'dd/mm/yyyy hh:mm')}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 style="font-size: 0.95rem;"><i class="fas fa-tools text-primary"></i> Thiết bị trong combo</h6>
                                ${combo.equipment && combo.equipment.length > 0 ? `
                                    <table class="table table-sm table-borderless" style="font-size: 0.9rem;">
                                        <thead>
                                            <tr>
                                                <th>Thiết bị</th>
                                                <th>Số lượng</th>
                                                <th>Đơn giá</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${combo.equipment.map(eq => `
                                                <tr>
                                                    <td>${eq.TenThietBi || 'N/A'}</td>
                                                    <td>${eq.SoLuong || 0}</td>
                                                    <td>${AdminPanel.formatCurrency(eq.GiaThue || 0)}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                ` : '<p class="text-muted">Không có thiết bị nào</p>'}
                            </div>
                        </div>
                    `;
                    $('#viewComboModalBody').html(html);
                    
                    // Trigger resize sau khi content được load
                    setTimeout(() => {
                        window.dispatchEvent(new Event('resize'));
                    }, 100);
                } else {
                    $('#viewComboModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${data.error || data.message || 'Không thể tải chi tiết combo'}
                        </div>
                    `);
                }
            })
            .catch(error => {
                $('#viewComboModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải chi tiết combo
                    </div>
                `);
            });
        }

        function saveCombo() {
            if (!AdminPanel.validateForm('comboForm')) {
                return;
            }

            if (selectedComboEquipment.length === 0) {
                AdminPanel.showError('Vui lòng chọn ít nhất một thiết bị cho combo');
                return;
            }

            const formData = new FormData(document.getElementById('comboForm'));
            const isEdit = $('#comboId').val() !== '';
            const action = isEdit ? 'update' : 'add';
            
            formData.append('action', action);
            formData.append('equipment', JSON.stringify(selectedComboEquipment));

            AdminPanel.makeAjaxRequest('../src/controllers/combo-management.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess(isEdit ? 'Đã cập nhật combo thành công' : 'Đã thêm combo thành công');
                    bootstrap.Modal.getInstance(document.getElementById('comboModal')).hide();
                    combosTable.ajax.reload();
                    loadComboStatistics();
                } else {
                    AdminPanel.showError(response.error || response.message || 'Có lỗi xảy ra khi lưu combo');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi lưu combo');
            });
        }

        function deleteCombo(id) {
            AdminPanel.sweetConfirm(
                'Xác nhận xóa',
                'Bạn có chắc muốn xóa combo này? Hành động này không thể hoàn tác.',
                () => {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    
                    AdminPanel.makeAjaxRequest('../src/controllers/combo-management.php', formData, 'POST')
                    .then(response => {
                        if (response.success) {
                            AdminPanel.showSuccess('Đã xóa combo thành công');
                            combosTable.ajax.reload();
                            loadComboStatistics();
                        } else {
                            AdminPanel.showError(response.error || response.message || 'Có lỗi xảy ra khi xóa combo');
                        }
                    })
                    .catch(error => {
                        AdminPanel.showError('Có lỗi xảy ra khi xóa combo');
                    });
                }
            );
        }

        function applyComboFilters() {
            const searchValue = $('#comboSearchInput').val() || '';
            const priceMin = parseFloat($('#comboPriceMin').val()) || 0;
            const priceMax = parseFloat($('#comboPriceMax').val()) || Infinity;
            const usageMin = parseInt($('#comboUsageMin').val()) || 0;
            const usageMax = parseInt($('#comboUsageMax').val()) || Infinity;
            
            // Xóa filter cũ nếu có
            if (comboPriceUsageFilter !== null) {
                $.fn.dataTable.ext.search.pop();
                comboPriceUsageFilter = null;
            }
            
            // Áp dụng tìm kiếm
            combosTable.search(searchValue);
            
            // Tạo filter function mới cho giá tiền và tần suất
            comboPriceUsageFilter = function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'combosTable') {
                    return true;
                }
                
                // Lấy giá từ cột 4 (GiaCombo) - đã được format currency
                const priceText = data[4] || '0';
                const price = parseFloat(priceText.replace(/[^\d]/g, '')) || 0;
                
                // Lấy tần suất từ cột 5 (SoLanSuDung)
                const usage = parseInt(data[5]) || 0;
                
                // Filter giá tiền
                const priceMatch = (priceMin === 0 && priceMax === Infinity) || 
                                  (price >= priceMin && price <= priceMax);
                
                // Filter tần suất sử dụng
                const usageMatch = (usageMin === 0 && usageMax === Infinity) || 
                                  (usage >= usageMin && usage <= usageMax);
                
                return priceMatch && usageMatch;
            };
            
            // Thêm filter mới
            $.fn.dataTable.ext.search.push(comboPriceUsageFilter);
            
            combosTable.draw();
        }

        function clearComboFilters() {
            $('#comboSearchInput').val('');
            $('#comboPriceMin').val('');
            $('#comboPriceMax').val('');
            $('#comboUsageMin').val('');
            $('#comboUsageMax').val('');
            
            // Xóa custom filter nếu có
            if (comboPriceUsageFilter !== null) {
                const index = $.fn.dataTable.ext.search.indexOf(comboPriceUsageFilter);
                if (index !== -1) {
                    $.fn.dataTable.ext.search.splice(index, 1);
                }
                comboPriceUsageFilter = null;
            }
            
            combosTable.search('').draw();
        }

        function clearComboSearch() {
            $('#comboSearchInput').val('');
            applyComboFilters();
        }
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