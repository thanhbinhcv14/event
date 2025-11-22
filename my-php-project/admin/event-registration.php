<?php
// Include admin header
include 'includes/admin-header.php';
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-plus-circle"></i>
                Đăng ký sự kiện cho khách hàng
            </h1>
            <p class="page-subtitle">
                Nhân viên đăng ký sự kiện thay mặt khách hàng
            </p>
        </div>

        <!-- Registration Form -->
        <div class="registration-container">
            <form id="eventRegistrationForm" class="needs-validation" novalidate>
                <!-- Step 1: Customer Selection -->
                <div class="step-container" id="step1">
                    <div class="step-header">
                        <div class="step-number">1</div>
                        <div class="step-title">
                            <h3><i class="fas fa-user"></i> Chọn khách hàng</h3>
                            <p>Chọn khách hàng có tài khoản trong hệ thống</p>
                        </div>
                    </div>
                    
                    <div class="step-content">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="customerSearch" class="form-label">Tìm kiếm khách hàng</label>
                                    <input type="text" class="form-control" id="customerSearch" placeholder="Nhập tên hoặc số điện thoại khách hàng...">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customerFilter" class="form-label">Lọc theo</label>
                                    <select class="form-select" id="customerFilter">
                                        <option value="">Tất cả khách hàng</option>
                                        <option value="recent">Khách hàng gần đây</option>
                                        <option value="frequent">Khách hàng thường xuyên</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Số lượng hiển thị</label>
                                    <select class="form-select" id="customerLimit">
                                        <option value="10">10 khách hàng</option>
                                        <option value="20" selected>20 khách hàng</option>
                                        <option value="50">50 khách hàng</option>
                                        <option value="all">Tất cả</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="customers-grid" id="customersGrid">
                            <!-- Customers will be loaded here -->
                        </div>
                        
                        <div class="selected-customer-info" id="selectedCustomerInfo" style="display: none;">
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle"></i> Khách hàng đã chọn</h5>
                                <div id="selectedCustomerDetails"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Event Information -->
                <div class="step-container" id="step2">
                    <div class="step-header">
                        <div class="step-number">2</div>
                        <div class="step-title">
                            <h3><i class="fas fa-calendar-alt"></i> Thông tin sự kiện</h3>
                            <p>Nhập thông tin chi tiết sự kiện</p>
                        </div>
                    </div>
                    
                    <div class="step-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eventName" class="form-label required">Tên sự kiện</label>
                                    <input type="text" class="form-control" id="eventName" name="eventName" required>
                                    <div class="invalid-feedback">Vui lòng nhập tên sự kiện</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eventType" class="form-label required">Loại sự kiện</label>
                                    <select class="form-select" id="eventType" name="eventType" required>
                                        <option value="">Chọn loại sự kiện</option>
                                    </select>
                                    <div class="invalid-feedback">Vui lòng chọn loại sự kiện</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="eventDescription" class="form-label">Mô tả sự kiện</label>
                                    <textarea class="form-control" id="eventDescription" name="eventDescription" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="startDate" class="form-label required">Ngày bắt đầu</label>
                                    <input type="datetime-local" class="form-control" id="startDate" name="startDate" required>
                                    <div class="invalid-feedback">Vui lòng chọn ngày bắt đầu</div>
                                    <small class="form-text text-muted">Ngày và giờ bắt đầu sự kiện</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="endDate" class="form-label required">Ngày kết thúc</label>
                                    <input type="datetime-local" class="form-control" id="endDate" name="endDate" required>
                                    <div class="invalid-feedback">Vui lòng chọn ngày kết thúc</div>
                                    <small class="form-text text-muted">Ngày và giờ kết thúc sự kiện</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info" id="durationAlert" style="display: none;">
                                    <i class="fas fa-info-circle"></i>
                                    <span id="durationText">Thời gian sự kiện: </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expectedGuests" class="form-label">Số khách dự kiến</label>
                                    <input type="number" class="form-control" id="expectedGuests" name="expectedGuests" min="1" value="50" placeholder="Nhập số khách dự kiến">
                                    <small class="form-text text-muted">Số lượng khách mời dự kiến</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="budget" class="form-label">Ngân sách dự kiến</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="budget" name="budget" min="0" step="1000" placeholder="Nhập ngân sách">
                                        <span class="input-group-text">VNĐ</span>
                                    </div>
                                    <small class="form-text text-muted">Ngân sách dự kiến cho sự kiện</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Location Selection -->
                <div class="step-container" id="step3">
                    <div class="step-header">
                        <div class="step-number">3</div>
                        <div class="step-title">
                            <h3><i class="fas fa-map-marker-alt"></i> Chọn địa điểm</h3>
                            <p>Chọn địa điểm tổ chức sự kiện</p>
                        </div>
                    </div>
                    
                    <div class="step-content">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="locationSearch" class="form-label">Tìm kiếm địa điểm</label>
                                    <input type="text" class="form-control" id="locationSearch" placeholder="Nhập tên địa điểm hoặc địa chỉ...">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="priceFilter" class="form-label">Lọc theo giá</label>
                                    <select class="form-select" id="priceFilter">
                                        <option value="">Tất cả giá</option>
                                        <option value="0-5000000">Dưới 5 triệu</option>
                                        <option value="5000000-10000000">5-10 triệu</option>
                                        <option value="10000000-20000000">10-20 triệu</option>
                                        <option value="20000000-50000000">20-50 triệu</option>
                                        <option value="50000000-999999999">Trên 50 triệu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rentalTypeFilter" class="form-label">Loại thuê</label>
                                    <select class="form-select" id="rentalTypeFilter">
                                        <option value="">Tất cả loại thuê</option>
                                        <option value="Theo giờ">Theo giờ</option>
                                        <option value="Theo ngày">Theo ngày</option>
                                        <option value="Cả hai">Cả hai</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="locations-grid" id="locationsGrid">
                            <!-- Locations will be loaded here -->
                        </div>
                        
                        <div class="selected-location-info" id="selectedLocationInfo" style="display: none;">
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle"></i> Địa điểm đã chọn</h5>
                                <div id="selectedLocationDetails"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Room Selection Modal -->
                <div class="modal fade" id="roomSelectionModal" tabindex="-1" aria-labelledby="roomSelectionModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="roomSelectionModalLabel">
                                    <i class="fas fa-door-open"></i> Chọn phòng
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="roomSelectionContent">
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Đang tải danh sách phòng...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Equipment Selection -->
                <div class="step-container" id="step4">
                    <div class="step-header">
                        <div class="step-number">4</div>
                        <div class="step-title">
                            <h3><i class="fas fa-cogs"></i> Chọn thiết bị</h3>
                            <p>Chọn thiết bị cần thiết cho sự kiện</p>
                        </div>
                    </div>
                    
                    <div class="step-content">
                        <div class="equipment-tabs">
                            <ul class="nav nav-tabs" id="equipmentTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="combo-tab" data-bs-toggle="tab" data-bs-target="#combo" type="button" role="tab">
                                        <i class="fas fa-box"></i> Combo thiết bị
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="individual-tab" data-bs-toggle="tab" data-bs-target="#individual" type="button" role="tab">
                                        <i class="fas fa-cog"></i> Thiết bị riêng lẻ
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="equipmentTabContent">
                                <div class="tab-pane fade show active" id="combo" role="tabpanel">
                                    <div class="equipment-search mb-3">
                                        <input type="text" class="form-control" id="comboSearch" placeholder="Tìm kiếm combo...">
                                    </div>
                                    <div class="equipment-grid" id="comboEquipmentGrid">
                                        <!-- Combo equipment will be loaded here -->
                                    </div>
                                </div>
                                
                                <div class="tab-pane fade" id="individual" role="tabpanel">
                                    <div class="equipment-search mb-3">
                                        <input type="text" class="form-control" id="equipmentSearch" placeholder="Tìm kiếm thiết bị...">
                                    </div>
                                    <div class="equipment-grid" id="individualEquipmentGrid">
                                        <!-- Individual equipment will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="selected-equipment" id="selectedEquipment" style="display: none;">
                            <h5><i class="fas fa-list"></i> Thiết bị đã chọn</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tên thiết bị</th>
                                            <th>Số lượng</th>
                                            <th>Đơn giá</th>
                                            <th>Thành tiền</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedEquipmentTable">
                                        <!-- Selected equipment will be shown here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Summary & Submit -->
                <div class="step-container" id="step5">
                    <div class="step-header">
                        <div class="step-number">5</div>
                        <div class="step-title">
                            <h3><i class="fas fa-check-circle"></i> Xác nhận đăng ký</h3>
                            <p>Kiểm tra lại thông tin và hoàn tất đăng ký</p>
                        </div>
                    </div>
                    
                    <div class="step-content">
                        <div class="summary-card">
                            <h5><i class="fas fa-user"></i> Thông tin khách hàng</h5>
                            <div id="customerSummary"></div>
                        </div>
                        
                        <div class="summary-card">
                            <h5><i class="fas fa-calendar-alt"></i> Thông tin sự kiện</h5>
                            <div id="eventSummary"></div>
                        </div>
                        
                        <div class="summary-card">
                            <h5><i class="fas fa-map-marker-alt"></i> Địa điểm</h5>
                            <div id="locationSummary"></div>
                        </div>
                        
                        <div class="summary-card">
                            <h5><i class="fas fa-cogs"></i> Thiết bị</h5>
                            <div id="equipmentSummary"></div>
                        </div>
                        
                        <div class="summary-card">
                            <h5><i class="fas fa-money-bill-wave"></i> Tổng chi phí</h5>
                            <div id="costSummary"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="adminNotes" class="form-label">Ghi chú của nhân viên</label>
                            <textarea class="form-control" id="adminNotes" name="adminNotes" rows="3" placeholder="Ghi chú về việc đăng ký này..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="step-navigation">
                    <button type="button" class="btn btn-secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Trước
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeStep(1)">
                        Tiếp <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;" onclick="console.log('Submit button clicked!');">
                        <i class="fas fa-check"></i> Hoàn tất đăng ký
                    </button>
                </div>
            </form>
        </div>

        <!-- Equipment Detail Modal -->
        <div class="modal fade" id="equipmentModal" tabindex="-1" data-bs-backdrop="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="equipmentModalTitle">
                            <i class="fas fa-cog"></i> Chi tiết thiết bị
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="equipmentModalBody">
                        <!-- Equipment details will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" id="addEquipmentBtn">
                            <i class="fas fa-plus"></i> Thêm vào danh sách
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Detail Modal -->
        <div class="modal fade" id="locationModal" tabindex="-1" data-bs-backdrop="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="locationModalTitle">
                            <i class="fas fa-map-marker-alt"></i> Chi tiết địa điểm
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="locationModalBody">
                        <!-- Location details will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" id="selectLocationBtn">
                            <i class="fas fa-check"></i> Chọn địa điểm này
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <style>
        /* Remove modal backdrop */
        .modal-backdrop {
            display: none !important;
        }
        
        .modal {
            background: transparent !important;
        }
        
        .modal.show {
            background: transparent !important;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
            color: #28a745;
        }
        
        .order-summary {
            margin-top: 20px;
        }
        
        .registration-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .step-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .step-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
        }

        .step-title h3 {
            margin: 0;
            font-size: 24px;
        }

        .step-title p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .step-content {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label.required::after {
            content: " *";
            color: red;
        }

        .customers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .customer-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .customer-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0,123,255,0.2);
        }

        .customer-card.selected {
            border-color: #28a745;
            background-color: #f8fff9;
        }

        .locations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .location-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .location-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0,123,255,0.2);
        }

        .location-card.selected {
            border-color: #28a745;
            background-color: #f8fff9;
        }

        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .equipment-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .equipment-card:hover {
            border-color: #007bff;
            box-shadow: 0 2px 10px rgba(0,123,255,0.1);
        }

        .summary-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .summary-card h5 {
            color: #495057;
            margin-bottom: 15px;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }

        .step-navigation {
            text-align: center;
            padding: 30px 0;
            border-top: 1px solid #dee2e6;
            margin-top: 30px;
        }

        .step-navigation .btn {
            margin: 0 10px;
            min-width: 120px;
        }

        .cost-breakdown {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }

        .cost-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .cost-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
            color: #28a745;
        }

        .equipment-tabs .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .equipment-tabs .nav-link {
            border: none;
            color: #6c757d;
        }

        .equipment-tabs .nav-link.active {
            color: #007bff;
            border-bottom: 2px solid #007bff;
        }
    </style>

    <script>
        let currentStep = 1;
        let totalSteps = 5;
        let selectedCustomer = null;
        let selectedLocation = null;
        let selectedEquipment = [];
        let customers = [];
        let eventTypes = [];
        let locations = [];
        let individualEquipment = [];
        let comboEquipment = [];

        // Check if in edit mode
        let isEditMode = false;
        let editRegistrationId = null;
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('edit')) {
            isEditMode = true;
            editRegistrationId = urlParams.get('edit');
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadCustomers();
            loadEventTypes();
            loadLocations();
            loadEquipment();
            setupEventListeners();
            updateStepDisplay();
            
            // If in edit mode, load event data
            if (isEditMode && editRegistrationId) {
                setTimeout(() => {
                    loadEventForEdit(editRegistrationId);
                }, 1500); // Wait for all data to load first
            }
            
            // Additional setup after a delay to ensure all elements are loaded
            setTimeout(() => {
                console.log('=== DELAYED SETUP ===');
                setupEventListeners();
                
                // Also try to attach submit listener directly to submit button
                const submitBtn = document.getElementById('submitBtn');
                if (submitBtn) {
                    console.log('Attaching click listener to submit button...');
                    submitBtn.addEventListener('click', function(e) {
                        console.log('Submit button clicked!');
                        e.preventDefault();
                        handleSubmit(e);
                    });
                    console.log('Submit button click listener attached');
                }
            }, 1000);
        });
        
        function loadEventForEdit(registrationId) {
            console.log('Loading event for edit:', registrationId);
            $.ajax({
                url: '../src/controllers/admin-event-register.php',
                type: 'GET',
                data: {
                    action: 'get_registration_for_edit',
                    registration_id: registrationId
                },
                success: function(response) {
                    console.log('Event data loaded:', response);
                    if (response.success && response.registration) {
                        const reg = response.registration;
                        
                        // 1. Load customer
                        if (reg.customer) {
                            const customer = customers.find(c => c.ID_KhachHang == reg.customer.ID_KhachHang);
                            if (customer) {
                                selectedCustomer = customer;
                                displaySelectedCustomer(customer);
                            }
                        }
                        
                        // 2. Load event details
                        if (reg.event) {
                            document.getElementById('eventName').value = reg.event.TenSuKien || '';
                            document.getElementById('eventType').value = reg.event.ID_LoaiSK || '';
                            document.getElementById('eventDescription').value = reg.event.MoTa || '';
                            
                            // Parse datetime for datetime-local inputs
                            function parseDateTimeForInput(dateTimeString) {
                                if (!dateTimeString) return '';
                                const date = new Date(dateTimeString);
                                const year = date.getFullYear();
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const day = String(date.getDate()).padStart(2, '0');
                                const hours = String(date.getHours()).padStart(2, '0');
                                const minutes = String(date.getMinutes()).padStart(2, '0');
                                return `${year}-${month}-${day}T${hours}:${minutes}`;
                            }
                            
                            document.getElementById('startDate').value = parseDateTimeForInput(reg.event.NgayBatDau);
                            document.getElementById('endDate').value = parseDateTimeForInput(reg.event.NgayKetThuc);
                            document.getElementById('expectedGuests').value = reg.event.SoNguoiDuKien || '';
                            document.getElementById('budget').value = reg.event.NganSach || '';
                        }
                        
                        // 3. Load location
                        if (reg.location) {
                            const location = locations.find(l => l.ID_DD == reg.location.ID_DD);
                            if (location) {
                                // Set rental type if available
                                if (reg.rentalType) {
                                    location.selectedRentalType = reg.rentalType;
                                }
                                
                                // Load room if exists
                                if (reg.room && reg.location.LoaiDiaDiem === 'Trong nhà') {
                                    location.selectedRoomId = reg.room.ID_Phong;
                                    location.selectedRoom = reg.room;
                                    
                                    // Determine rental type from room
                                    if (reg.rentalType) {
                                        location.selectedRoomRentalType = reg.rentalType;
                                    } else if (reg.room.LoaiThue === 'Theo giờ') {
                                        location.selectedRoomRentalType = 'hour';
                                    } else if (reg.room.LoaiThue === 'Theo ngày') {
                                        location.selectedRoomRentalType = 'day';
                                    } else {
                                        location.selectedRoomRentalType = 'day'; // Default
                                    }
                                }
                                
                                selectedLocation = location;
                                displaySelectedLocation(location);
                                
                                // If indoor with room, load and display rooms
                                if (reg.location.LoaiDiaDiem === 'Trong nhà' && reg.room) {
                                    // Room information is already loaded in location.selectedRoom
                                    // The room will be displayed when displaySelectedLocation is called
                                    console.log('Room loaded for indoor location:', reg.room);
                                }
                            }
                        }
                        
                        // 4. Load equipment
                        if (reg.equipment && reg.equipment.length > 0) {
                            selectedEquipment = reg.equipment.map(item => {
                                // Get price - prioritize DonGia, then GiaThue/GiaCombo
                                let price = 0;
                                if (item.DonGia && parseFloat(item.DonGia) > 0) {
                                    price = parseFloat(item.DonGia);
                                } else if (item.GiaThue && parseFloat(item.GiaThue) > 0) {
                                    price = parseFloat(item.GiaThue);
                                } else if (item.GiaCombo && parseFloat(item.GiaCombo) > 0) {
                                    price = parseFloat(item.GiaCombo);
                                }
                                
                                return {
                                    id: item.ID_TB || item.ID_Combo,
                                    type: item.ID_TB ? 'equipment' : 'combo',
                                    name: item.TenThietBi || item.TenCombo,
                                    quantity: parseInt(item.SoLuong) || 1,
                                    price: price,
                                    unit: item.DonViTinh || 'combo'
                                };
                            });
                            updateSelectedEquipment();
                        } else {
                            selectedEquipment = [];
                            updateSelectedEquipment();
                        }
                        
                        // 5. Load admin notes
                        if (reg.adminNotes) {
                            document.getElementById('adminNotes').value = reg.adminNotes;
                        }
                        
                        // 6. Load discount code if exists
                        if (reg.discountCode && reg.discountCode.MaCode) {
                            const discountCode = reg.discountCode;
                            console.log('Discount code loaded:', discountCode);
                            
                            // Display discount code information
                            // Note: Admin form may not have discount code input field
                            // But we can log it for debugging
                            if (window.showNotification) {
                                let discountText = '';
                                if (discountCode.LoaiGiamGia === 'Phần trăm') {
                                    discountText = `${discountCode.GiaTriGiamGia}%`;
                                } else {
                                    const formattedAmount = new Intl.NumberFormat('vi-VN').format(discountCode.GiaTriGiamGia);
                                    discountText = `${formattedAmount} VNĐ`;
                                }
                                const formattedDiscountAmount = new Intl.NumberFormat('vi-VN').format(discountCode.SoTienGiamGia || 0);
                                showNotification(
                                    `Mã giảm giá đã sử dụng: ${discountCode.MaCode} (${discountCode.TenMa || ''}) - Giảm ${discountText} - Đã giảm: ${formattedDiscountAmount} VNĐ`,
                                    'info'
                                );
                            }
                        }
                        
                        // Update summaries
                        updateOrderSummary();
                        updateCostSummary();
                        
                        // Update page title if in edit mode
                        if (isEditMode) {
                            document.querySelector('.page-title').textContent = 'Sửa đăng ký sự kiện';
                            document.querySelector('.page-subtitle').textContent = 'Chỉnh sửa thông tin đăng ký sự kiện';
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading event:', error);
                    alert('Lỗi khi tải dữ liệu sự kiện: ' + error);
                }
            });
        }

        function setupEventListeners() {
            console.log('=== SETUP EVENT LISTENERS ===');
            console.log('Current time:', new Date().toLocaleTimeString());
            
            // Form validation
            const form = document.getElementById('eventRegistrationForm');
            console.log('Form element:', form);
            console.log('Form exists:', !!form);
            
            if (form) {
                console.log('Adding submit event listener to form...');
                console.log('Form HTML:', form.outerHTML.substring(0, 200) + '...');
                
                // Remove any existing listeners first
                form.removeEventListener('submit', handleSubmit);
                
                // Add new listener with check to prevent submission from room selection buttons
                form.addEventListener('submit', function(e) {
                    // Don't submit if the event was triggered by a room selection button
                    if (e.submitter && (e.submitter.classList.contains('btn-choose-room') || e.submitter.closest('.btn-choose-room'))) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                    handleSubmit(e);
                });
                console.log('Submit event listener added successfully');
                
                // Test if listener was added
                console.log('Form onsubmit:', form.onsubmit);
                console.log('Form has event listeners:', form.addEventListener ? 'Yes' : 'No');
                
            } else {
                console.error('Form element not found!');
                console.log('Available elements with "form" in ID:');
                const allElements = document.querySelectorAll('[id*="form"]');
                allElements.forEach(el => console.log('- ' + el.id));
            }

            // Customer search
            const customerSearch = document.getElementById('customerSearch');
            if (customerSearch) {
                customerSearch.addEventListener('input', filterCustomers);
            }
            const customerFilter = document.getElementById('customerFilter');
            if (customerFilter) {
                customerFilter.addEventListener('change', filterCustomers);
            }
            const customerLimit = document.getElementById('customerLimit');
            if (customerLimit) {
                customerLimit.addEventListener('change', filterCustomers);
            }

            // Location search
            const locationSearch = document.getElementById('locationSearch');
            if (locationSearch) {
                locationSearch.addEventListener('input', filterLocations);
            }
            const priceFilter = document.getElementById('priceFilter');
            if (priceFilter) {
                priceFilter.addEventListener('change', filterLocations);
            }
            const rentalTypeFilter = document.getElementById('rentalTypeFilter');
            if (rentalTypeFilter) {
                rentalTypeFilter.addEventListener('change', filterLocations);
            }

            // Equipment search
            const equipmentSearch = document.getElementById('equipmentSearch');
            if (equipmentSearch) {
                equipmentSearch.addEventListener('input', filterIndividualEquipment);
            }
            const comboSearch = document.getElementById('comboSearch');
            if (comboSearch) {
                comboSearch.addEventListener('input', filterComboEquipment);
            }

            // Date validation and duration display
            const startDate = document.getElementById('startDate');
            if (startDate) {
                startDate.addEventListener('change', function() {
                    validateDates();
                    updateDurationDisplay();
                    updateOrderSummary();
                    updateCostSummary();
                });
            }
            const endDate = document.getElementById('endDate');
            if (endDate) {
                endDate.addEventListener('change', function() {
                    validateDates();
                    updateDurationDisplay();
                    updateOrderSummary();
                    updateCostSummary();
                });
            }
        }

        function loadCustomers() {
            fetch('../src/controllers/admin-event-register.php?action=get_customers')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        customers = data.customers;
                        displayCustomers();
                    }
                })
                .catch(error => console.error('Error loading customers:', error));
        }

        function displayCustomers() {
            const grid = document.getElementById('customersGrid');
            grid.innerHTML = '';

            const limit = parseInt(document.getElementById('customerLimit').value) || 20;
            const displayCustomers = customers.slice(0, limit === 0 ? customers.length : limit);

            displayCustomers.forEach(customer => {
                const card = document.createElement('div');
                card.className = 'customer-card';
                card.onclick = () => selectCustomer(customer);

                const registrationCount = customer.event_count || 0;
                const lastEvent = customer.last_event_date ? new Date(customer.last_event_date).toLocaleDateString('vi-VN') : 'Chưa có';

                card.innerHTML = `
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <div class="customer-avatar">
                                <i class="fas fa-user fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${customer.HoTen}</h6>
                            <p class="text-muted mb-1 small">
                                <i class="fas fa-phone"></i> ${customer.SoDienThoai}
                            </p>
                            ${customer.Email ? `<p class="text-muted mb-1 small"><i class="fas fa-envelope"></i> ${customer.Email}</p>` : ''}
                            ${customer.DiaChi ? `<p class="text-muted mb-1 small"><i class="fas fa-map-marker-alt"></i> ${customer.DiaChi}</p>` : ''}
                            <div class="customer-stats">
                                <span class="badge bg-info me-1">${registrationCount} sự kiện</span>
                                <span class="badge bg-secondary">Cuối: ${lastEvent}</span>
                            </div>
                        </div>
                    </div>
                `;

                grid.appendChild(card);
            });
        }

        function displaySelectedCustomer(customer) {
            if (!customer) return;
            
            selectedCustomer = customer;
            
            // Update UI - highlight the customer card if it exists
            document.querySelectorAll('.customer-card').forEach(card => {
                card.classList.remove('selected');
                const cardCustomerId = card.dataset.customerId || card.getAttribute('data-customer-id');
                if (cardCustomerId == customer.ID_KhachHang) {
                    card.classList.add('selected');
                }
            });

            // Show selected customer info
            const infoDiv = document.getElementById('selectedCustomerInfo');
            const detailsDiv = document.getElementById('selectedCustomerDetails');
            
            if (infoDiv && detailsDiv) {
                detailsDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-8">
                            <h6>${customer.HoTen}</h6>
                            <p class="mb-1"><i class="fas fa-phone"></i> ${customer.SoDienThoai}</p>
                            ${customer.Email ? `<p class="mb-1"><i class="fas fa-envelope"></i> ${customer.Email}</p>` : ''}
                            ${customer.DiaChi ? `<p class="mb-0"><i class="fas fa-map-marker-alt"></i> ${customer.DiaChi}</p>` : ''}
                        </div>
                        <div class="col-md-4 text-end">
                            <p class="mb-1"><span class="badge bg-info">${customer.event_count || 0} sự kiện</span></p>
                            <p class="mb-0"><small class="text-muted">Cuối: ${customer.last_event_date ? new Date(customer.last_event_date).toLocaleDateString('vi-VN') : 'Chưa có'}</small></p>
                        </div>
                    </div>
                `;
                
                infoDiv.style.display = 'block';
            }
        }

        function selectCustomer(customer) {
            selectedCustomer = customer;
            
            // Update UI
            document.querySelectorAll('.customer-card').forEach(card => {
                card.classList.remove('selected');
            });
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('selected');
            }

            // Show selected customer info
            displaySelectedCustomer(customer);
        }

        function filterCustomers() {
            const searchTerm = document.getElementById('customerSearch').value.toLowerCase();
            const filter = document.getElementById('customerFilter').value;
            const limit = parseInt(document.getElementById('customerLimit').value) || 20;

            let filteredCustomers = customers;

            // Apply search filter
            if (searchTerm) {
                filteredCustomers = filteredCustomers.filter(customer => 
                    customer.HoTen.toLowerCase().includes(searchTerm) ||
                    customer.SoDienThoai.includes(searchTerm) ||
                    (customer.Email && customer.Email.toLowerCase().includes(searchTerm))
                );
            }

            // Apply category filter
            if (filter === 'recent') {
                filteredCustomers = filteredCustomers.filter(customer => 
                    customer.last_event_date && 
                    new Date(customer.last_event_date) > new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
                );
            } else if (filter === 'frequent') {
                filteredCustomers = filteredCustomers.filter(customer => 
                    (customer.event_count || 0) >= 3
                );
            }

            // Apply limit
            if (limit > 0) {
                filteredCustomers = filteredCustomers.slice(0, limit);
            }

            // Update display
            const grid = document.getElementById('customersGrid');
            grid.innerHTML = '';

            filteredCustomers.forEach(customer => {
                const card = document.createElement('div');
                card.className = 'customer-card';
                card.onclick = () => selectCustomer(customer);

                const registrationCount = customer.event_count || 0;
                const lastEvent = customer.last_event_date ? new Date(customer.last_event_date).toLocaleDateString('vi-VN') : 'Chưa có';

                card.innerHTML = `
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <div class="customer-avatar">
                                <i class="fas fa-user fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${customer.HoTen}</h6>
                            <p class="text-muted mb-1 small">
                                <i class="fas fa-phone"></i> ${customer.SoDienThoai}
                            </p>
                            ${customer.Email ? `<p class="text-muted mb-1 small"><i class="fas fa-envelope"></i> ${customer.Email}</p>` : ''}
                            ${customer.DiaChi ? `<p class="text-muted mb-1 small"><i class="fas fa-map-marker-alt"></i> ${customer.DiaChi}</p>` : ''}
                            <div class="customer-stats">
                                <span class="badge bg-info me-1">${registrationCount} sự kiện</span>
                                <span class="badge bg-secondary">Cuối: ${lastEvent}</span>
                            </div>
                        </div>
                    </div>
                `;

                grid.appendChild(card);
            });
        }

        function loadEventTypes() {
            fetch('../src/controllers/admin-event-register.php?action=get_event_types')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        eventTypes = data.event_types;
                        const select = document.getElementById('eventType');
                        select.innerHTML = '<option value="">Chọn loại sự kiện</option>';
                        eventTypes.forEach(type => {
                            const option = document.createElement('option');
                            option.value = type.ID_LoaiSK;
                            option.textContent = type.TenLoai;
                            option.dataset.price = type.GiaCoBan || 0;
                            select.appendChild(option);
                        });
                        
                        // Add event listener for event type change
                        select.addEventListener('change', function() {
                            updateOrderSummary();
                            updateCostSummary();
                        });
                    }
                })
                .catch(error => console.error('Error loading event types:', error));
        }

        function loadLocations() {
            fetch('../src/controllers/admin-event-register.php?action=get_locations')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        locations = data.locations;
                        // Initialize selectedRentalType for locations with "Cả hai" type
                        locations.forEach(location => {
                            if (location.LoaiThue === 'Cả hai' && !location.selectedRentalType) {
                                location.selectedRentalType = 'day'; // Default to daily
                            }
                        });
                        displayLocations();
                    }
                })
                .catch(error => console.error('Error loading locations:', error));
        }

        function loadEquipment() {
            // Load individual equipment
            fetch('../src/controllers/admin-event-register.php?action=get_equipment')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        individualEquipment = data.equipment;
                        displayIndividualEquipment();
                    }
                })
                .catch(error => console.error('Error loading equipment:', error));

            // Load combo equipment
            fetch('../src/controllers/admin-event-register.php?action=get_equipment_combos')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        comboEquipment = data.combos;
                        displayComboEquipment();
                    }
                })
                .catch(error => console.error('Error loading combos:', error));
        }

        function displayLocations() {
            const grid = document.getElementById('locationsGrid');
            grid.innerHTML = '';

            locations.forEach(location => {
                const card = document.createElement('div');
                card.className = 'location-card';
                card.setAttribute('data-location-id', location.ID_DD);
                
                // For indoor locations, don't auto-select on card click, require room selection first
                if (location.LoaiDiaDiem === 'Trong nhà') {
                    card.onclick = (e) => {
                        // Don't select if clicking on room button or any button
                        if (e.target.closest('.btn-choose-room') || e.target.tagName === 'BUTTON') {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        }
                        selectLocation(location);
                    };
                } else {
                    card.onclick = (e) => {
                        // Prevent form submission if clicking on select dropdown
                        if (e.target.tagName === 'SELECT' || e.target.closest('select')) {
                            e.stopPropagation();
                        }
                        selectLocation(location);
                    };
                }

                const priceText = getLocationPriceText(location);
                const imageUrl = location.HinhAnh ? `../img/diadiem/${location.HinhAnh}` : '../img/logo/logo.jpg';
                
                // Check if this is an indoor location
                const isIndoor = location.LoaiDiaDiem === 'Trong nhà';
                const hasSelectedRoom = location.selectedRoom && location.selectedRoomId;
                
                // Build room display text
                let roomDisplay = '';
                if (isIndoor) {
                    if (hasSelectedRoom) {
                        const room = location.selectedRoom;
                        const rentalType = location.selectedRoomRentalType || 'day';
                        const roomPrice = rentalType === 'hour' ? room.GiaThueGio : room.GiaThueNgay;
                        roomDisplay = `
                            <div class="mt-2">
                                <p class="text-primary mb-1 small">
                                    <i class="fas fa-door-open"></i> Phòng đã chọn: <strong>${room.TenPhong}</strong>
                                </p>
                                <p class="text-success mb-0 small">
                                    ${new Intl.NumberFormat('vi-VN').format(roomPrice)} VNĐ/${rentalType === 'hour' ? 'giờ' : 'ngày'}
                                </p>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-1 btn-choose-room" 
                                        onclick="event.stopPropagation(); event.preventDefault(); return showRoomSelection(${location.ID_DD}, event);">
                                    <i class="fas fa-edit"></i> Đổi phòng
                                </button>
                            </div>
                        `;
                    } else {
                        roomDisplay = `
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-primary btn-choose-room" 
                                        onclick="event.stopPropagation(); event.preventDefault(); return showRoomSelection(${location.ID_DD}, event);">
                                    <i class="fas fa-door-open"></i> Chọn phòng
                                </button>
                            </div>
                        `;
                    }
                }

                card.innerHTML = `
                    <div class="d-flex align-items-start">
                        <img src="${imageUrl}" alt="${location.TenDiaDiem}" class="me-3" style="width: 80px; height: 60px; object-fit: cover; border-radius: 5px;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${location.TenDiaDiem}</h6>
                            <p class="text-muted mb-1 small">${location.DiaChi}</p>
                            ${!isIndoor || hasSelectedRoom ? `<p class="text-success mb-0"><strong id="price-${location.ID_DD}">${priceText}</strong></p>` : '<p class="text-muted mb-0 small">Vui lòng chọn phòng để xem giá</p>'}
                            <p class="text-muted mb-0 small">Sức chứa: ${location.SucChua || 'N/A'} người</p>
                            ${!isIndoor && location.LoaiThue === 'Cả hai' ? `
                                <div class="mt-2">
                                    <select class="form-select form-select-sm" 
                                            onchange="updateLocationRentalType(${location.ID_DD}, this.value)" 
                                            onclick="event.stopPropagation()"
                                            style="min-width: 120px;">
                                        <option value="hour" ${location.selectedRentalType === 'hour' ? 'selected' : ''}>Theo giờ</option>
                                        <option value="day" ${location.selectedRentalType === 'day' ? 'selected' : ''}>Theo ngày</option>
                                    </select>
                                    <small class="text-muted">Chọn loại thuê</small>
                                </div>
                            ` : ''}
                            ${roomDisplay}
                        </div>
                    </div>
                `;

                grid.appendChild(card);
            });
        }

        function showRoomSelection(locationId, event) {
            // Prevent any form submission
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            const location = locations.find(loc => loc.ID_DD === locationId);
            if (!location) {
                alert('Không tìm thấy địa điểm');
                return false;
            }
            
            // Update modal title
            document.getElementById('roomSelectionModalLabel').innerHTML = `
                <i class="fas fa-door-open"></i> Chọn phòng - ${location.TenDiaDiem}
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('roomSelectionModal'));
            modal.show();
            
            // Load rooms
            loadRoomsForLocation(locationId);
            
            return false;
        }
        
        function loadRoomsForLocation(locationId) {
            const contentDiv = document.getElementById('roomSelectionContent');
            contentDiv.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải danh sách phòng...</p>
                </div>
            `;
            
            fetch(`../src/controllers/rooms.php?action=get_rooms&location_id=${locationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        displayRooms(data.data, locationId);
                    } else {
                        contentDiv.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Không có phòng nào cho địa điểm này.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading rooms:', error);
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i> Lỗi khi tải danh sách phòng. Vui lòng thử lại.
                        </div>
                    `;
                });
        }
        
        function displayRooms(rooms, locationId) {
            const contentDiv = document.getElementById('roomSelectionContent');
            const location = locations.find(loc => loc.ID_DD === locationId);
            const selectedRoomId = location?.selectedRoomId;
            const selectedRentalType = location?.selectedRoomRentalType || 'day';
            
            let html = '<div class="row g-3">';
            
            rooms.forEach(room => {
                const isSelected = selectedRoomId == room.ID_Phong;
                const rentalType = room.LoaiThue === 'Theo giờ' ? 'hour' : 
                                  room.LoaiThue === 'Theo ngày' ? 'day' : 
                                  selectedRentalType;
                
                const price = rentalType === 'hour' ? room.GiaThueGio : room.GiaThueNgay;
                const priceText = rentalType === 'hour' ? 
                    `${new Intl.NumberFormat('vi-VN').format(price)} VNĐ/giờ` : 
                    `${new Intl.NumberFormat('vi-VN').format(price)} VNĐ/ngày`;
                
                html += `
                    <div class="col-md-6">
                        <div class="card ${isSelected ? 'border-primary' : ''}" style="cursor: pointer;" 
                             onclick="selectRoom(${locationId}, ${room.ID_Phong}, '${rentalType}')">
                            <div class="card-body">
                                <h6 class="card-title">
                                    ${room.TenPhong}
                                    ${isSelected ? '<span class="badge bg-primary ms-2">Đã chọn</span>' : ''}
                                </h6>
                                <p class="text-muted small mb-2">${room.MoTa || 'Không có mô tả'}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="text-success mb-0"><strong>${priceText}</strong></p>
                                        ${room.SucChua ? `<p class="text-muted small mb-0">Sức chứa: ${room.SucChua} người</p>` : ''}
                                    </div>
                                    ${room.LoaiThue === 'Cả hai' ? `
                                        <div>
                                            <select class="form-select form-select-sm" 
                                                    onclick="event.stopPropagation()"
                                                    onchange="selectRoom(${locationId}, ${room.ID_Phong}, this.value)">
                                                <option value="hour" ${rentalType === 'hour' ? 'selected' : ''}>Theo giờ</option>
                                                <option value="day" ${rentalType === 'day' ? 'selected' : ''}>Theo ngày</option>
                                            </select>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            contentDiv.innerHTML = html;
        }
        
        function selectRoom(locationId, roomId, rentalType = 'day') {
            const location = locations.find(loc => loc.ID_DD === locationId);
            if (!location) return;
            
            // Find room data
            fetch(`../src/controllers/rooms.php?action=get_room&id=${roomId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const room = data.data;
                        
                        // Update location with selected room
                        location.selectedRoomId = roomId;
                        location.selectedRoom = room;
                        location.selectedRoomRentalType = rentalType;
                        
                        // If location is not selected yet, select it
                        if (!selectedLocation || selectedLocation.ID_DD !== locationId) {
                            selectedLocation = location;
                        } else {
                            // Update selectedLocation
                            selectedLocation.selectedRoomId = roomId;
                            selectedLocation.selectedRoom = room;
                            selectedLocation.selectedRoomRentalType = rentalType;
                        }
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('roomSelectionModal'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Refresh location display
                        displayLocations();
                        displaySelectedLocation(location);
                        updateCostSummary();
                    }
                })
                .catch(error => {
                    console.error('Error loading room:', error);
                    alert('Lỗi khi tải thông tin phòng');
                });
        }

        function updateLocationRentalType(locationId, rentalType) {
            const location = locations.find(loc => loc.ID_DD === locationId);
            if (!location) return;
            
            // Store the selected rental type
            location.selectedRentalType = rentalType;
            
            // Update price display
            const priceElement = document.getElementById(`price-${locationId}`);
            if (priceElement) {
                let newPriceText = '';
                if (rentalType === 'hour') {
                    newPriceText = `${new Intl.NumberFormat('vi-VN').format(location.GiaThueGio || 0)} VNĐ/giờ`;
                } else if (rentalType === 'day') {
                    newPriceText = `${new Intl.NumberFormat('vi-VN').format(location.GiaThueNgay || 0)} VNĐ/ngày`;
                }
                priceElement.textContent = newPriceText;
            }
            
            // If this location is currently selected, update selectedLocation
            if (selectedLocation && selectedLocation.ID_DD === locationId) {
                selectedLocation.selectedRentalType = rentalType;
                updateOrderSummary();
                updateCostSummary(); // Also update the cost summary
            }
            
            // Update dropdown selection in the card
            const card = document.querySelector(`[data-location-id="${locationId}"]`);
            if (card) {
                const select = card.querySelector('select');
                if (select) {
                    select.value = rentalType;
                }
            }
        }

        function getLocationPriceText(location) {
            if (location.LoaiThue === 'Theo giờ') {
                return `${new Intl.NumberFormat('vi-VN').format(location.GiaThueGio || 0)} VNĐ/giờ`;
            } else if (location.LoaiThue === 'Theo ngày') {
                return `${new Intl.NumberFormat('vi-VN').format(location.GiaThueNgay || 0)} VNĐ/ngày`;
            } else if (location.LoaiThue === 'Cả hai') {
                // If user has selected a rental type, show only that price
                const rentalType = location.selectedRentalType || 'day'; // Default to day
                if (rentalType === 'hour') {
                    return `${new Intl.NumberFormat('vi-VN').format(location.GiaThueGio || 0)} VNĐ/giờ`;
                } else if (rentalType === 'day') {
                    return `${new Intl.NumberFormat('vi-VN').format(location.GiaThueNgay || 0)} VNĐ/ngày`;
                }
            }
            return 'Liên hệ';
        }

        function displaySelectedLocation(location) {
            if (!location) return;
            
            selectedLocation = location;
            
            // If location has "Cả hai" rental type, use the user's selection or default to day
            if (selectedLocation && selectedLocation.LoaiThue === 'Cả hai') {
                // Use the user's selection from dropdown, or default to day if not set
                selectedLocation.selectedRentalType = selectedLocation.selectedRentalType || 'day';
                console.log('Using selectedRentalType for location:', selectedLocation.TenDiaDiem, 'Type:', selectedLocation.selectedRentalType);
            }
            
            // Update UI - highlight the location card if it exists
            document.querySelectorAll('.location-card').forEach(card => {
                card.classList.remove('selected');
                const cardLocationId = card.dataset.locationId || card.getAttribute('data-location-id');
                if (cardLocationId == location.ID_DD) {
                    card.classList.add('selected');
                }
            });

            // Show selected location info
            const infoDiv = document.getElementById('selectedLocationInfo');
            const detailsDiv = document.getElementById('selectedLocationDetails');
            
            if (infoDiv && detailsDiv) {
                // Build location price text
                let priceText = getLocationPriceText(location);
                if (location.selectedRoom) {
                    const room = location.selectedRoom;
                    const rentalType = location.selectedRoomRentalType || location.selectedRentalType || 'day';
                    if (rentalType === 'hour' && room.GiaThueGio) {
                        priceText = `${room.TenPhong}: ${new Intl.NumberFormat('vi-VN').format(room.GiaThueGio)} VNĐ/giờ`;
                    } else if (rentalType === 'day' && room.GiaThueNgay) {
                        priceText = `${room.TenPhong}: ${new Intl.NumberFormat('vi-VN').format(room.GiaThueNgay)} VNĐ/ngày`;
                    }
                }
                
                detailsDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-8">
                            <h6>${location.TenDiaDiem}${location.selectedRoom ? ` - ${location.selectedRoom.TenPhong}` : ''}</h6>
                            <p class="mb-1">${location.DiaChi}</p>
                            <p class="mb-0 text-success"><strong>${priceText}</strong></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-outline-primary btn-sm" type="button" onclick="viewLocationDetails(${location.ID_DD})">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </button>
                        </div>
                    </div>
                `;
                
                infoDiv.style.display = 'block';
            }
            
            // Update order summary and cost summary
            updateOrderSummary();
            updateCostSummary();
        }

        function selectLocation(location) {
            selectedLocation = location;
            
            // If location has "Cả hai" rental type, use the user's selection or default to day
            if (selectedLocation && selectedLocation.LoaiThue === 'Cả hai') {
                // Use the user's selection from dropdown, or default to day if not set
                selectedLocation.selectedRentalType = selectedLocation.selectedRentalType || 'day';
                console.log('Using selectedRentalType for location:', selectedLocation.TenDiaDiem, 'Type:', selectedLocation.selectedRentalType);
            }
            
            // Update UI
            document.querySelectorAll('.location-card').forEach(card => {
                card.classList.remove('selected');
            });
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('selected');
            }

            // Show selected location info
            displaySelectedLocation(location);
        }

        function updateOrderSummary() {
            // Function removed - order summary section has been removed from UI
            // This function is kept for compatibility but does nothing
            return;
        }

        function filterLocations() {
            const searchTerm = document.getElementById('locationSearch').value.toLowerCase();
            const priceFilter = document.getElementById('priceFilter').value;
            const rentalTypeFilter = document.getElementById('rentalTypeFilter').value;

            const filteredLocations = locations.filter(location => {
                const matchesSearch = location.TenDiaDiem.toLowerCase().includes(searchTerm) ||
                                    location.DiaChi.toLowerCase().includes(searchTerm);
                
                const matchesRentalType = !rentalTypeFilter || location.LoaiThue === rentalTypeFilter;
                
                let matchesPrice = true;
                if (priceFilter) {
                    const [min, max] = priceFilter.split('-').map(Number);
                    const hourlyPrice = location.GiaThueGio || 0;
                    const dailyPrice = location.GiaThueNgay || 0;
                    const price = Math.min(hourlyPrice, dailyPrice);
                    matchesPrice = price >= min && price <= max;
                }

                return matchesSearch && matchesRentalType && matchesPrice;
            });

            // Update display
            const grid = document.getElementById('locationsGrid');
            grid.innerHTML = '';

            filteredLocations.forEach(location => {
                const card = document.createElement('div');
                card.className = 'location-card';
                card.onclick = () => selectLocation(location);

                const priceText = getLocationPriceText(location);
                const imageUrl = location.HinhAnh ? `../img/diadiem/${location.HinhAnh}` : '../img/logo/logo.jpg';

                card.innerHTML = `
                    <div class="d-flex align-items-start">
                        <img src="${imageUrl}" alt="${location.TenDiaDiem}" class="me-3" style="width: 80px; height: 60px; object-fit: cover; border-radius: 5px;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${location.TenDiaDiem}</h6>
                            <p class="text-muted mb-1 small">${location.DiaChi}</p>
                            <p class="text-success mb-0"><strong>${priceText}</strong></p>
                            <p class="text-muted mb-0 small">Sức chứa: ${location.SucChua || 'N/A'} người</p>
                        </div>
                    </div>
                `;

                grid.appendChild(card);
            });
        }

        function displayIndividualEquipment() {
            const grid = document.getElementById('individualEquipmentGrid');
            grid.innerHTML = '';

            individualEquipment.forEach(equipment => {
                const card = document.createElement('div');
                card.className = 'equipment-card';
                card.onclick = () => viewEquipmentDetails(equipment);

                card.innerHTML = `
                    <div class="text-center">
                        <h6 class="mb-2">${equipment.TenThietBi}</h6>
                        <p class="text-muted mb-2 small">${equipment.LoaiThietBi}</p>
                        <p class="text-success mb-0"><strong>${new Intl.NumberFormat('vi-VN').format(equipment.GiaThue || 0)} VNĐ</strong></p>
                        <p class="text-muted mb-0 small">${equipment.DonViTinh || 'cái'}</p>
                    </div>
                `;

                grid.appendChild(card);
            });
        }

        function displayComboEquipment() {
            const grid = document.getElementById('comboEquipmentGrid');
            grid.innerHTML = '';

            comboEquipment.forEach(combo => {
                const card = document.createElement('div');
                card.className = 'equipment-card';
                card.onclick = () => viewComboDetails(combo);

                card.innerHTML = `
                    <div class="text-center">
                        <h6 class="mb-2">${combo.TenCombo}</h6>
                        <p class="text-muted mb-2 small">Combo thiết bị</p>
                        <p class="text-success mb-0"><strong>${new Intl.NumberFormat('vi-VN').format(combo.GiaCombo || 0)} VNĐ</strong></p>
                        <p class="text-muted mb-0 small">combo</p>
                    </div>
                `;

                grid.appendChild(card);
            });
        }

        function filterIndividualEquipment() {
            const searchTerm = document.getElementById('equipmentSearch').value.toLowerCase();
            const filtered = individualEquipment.filter(equipment => 
                equipment.TenThietBi.toLowerCase().includes(searchTerm) ||
                equipment.LoaiThietBi.toLowerCase().includes(searchTerm)
            );

            const grid = document.getElementById('individualEquipmentGrid');
            grid.innerHTML = '';

            filtered.forEach(equipment => {
                const card = document.createElement('div');
                card.className = 'equipment-card';
                card.onclick = () => viewEquipmentDetails(equipment);

                card.innerHTML = `
                    <div class="text-center">
                        <h6 class="mb-2">${equipment.TenThietBi}</h6>
                        <p class="text-muted mb-2 small">${equipment.LoaiThietBi}</p>
                        <p class="text-success mb-0"><strong>${new Intl.NumberFormat('vi-VN').format(equipment.GiaThue || 0)} VNĐ</strong></p>
                        <p class="text-muted mb-0 small">${equipment.DonViTinh || 'cái'}</p>
                    </div>
                `;

                grid.appendChild(card);
            });
        }

        function filterComboEquipment() {
            const searchTerm = document.getElementById('comboSearch').value.toLowerCase();
            const filtered = comboEquipment.filter(combo => 
                combo.TenCombo.toLowerCase().includes(searchTerm)
            );

            const grid = document.getElementById('comboEquipmentGrid');
            grid.innerHTML = '';

            filtered.forEach(combo => {
                const card = document.createElement('div');
                card.className = 'equipment-card';
                card.onclick = () => viewComboDetails(combo);

                card.innerHTML = `
                    <div class="text-center">
                        <h6 class="mb-2">${combo.TenCombo}</h6>
                        <p class="text-muted mb-2 small">Combo thiết bị</p>
                        <p class="text-success mb-0"><strong>${new Intl.NumberFormat('vi-VN').format(combo.GiaCombo || 0)} VNĐ</strong></p>
                        <p class="text-muted mb-0 small">combo</p>
                    </div>
                `;

                grid.appendChild(card);
            });
        }

        function viewLocationDetails(locationId) {
            const location = locations.find(loc => loc.ID_DD === locationId);
            if (!location) return;
            
            document.getElementById('locationModalTitle').innerHTML = `<i class="fas fa-map-marker-alt"></i> ${location.TenDiaDiem}`;
            
            document.getElementById('locationModalBody').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Thông tin địa điểm</h6>
                        <p><strong>Tên:</strong> ${location.TenDiaDiem}</p>
                        <p><strong>Địa chỉ:</strong> ${location.DiaChi}</p>
                        <p><strong>Sức chứa:</strong> ${location.SucChua} người</p>
                        <p><strong>Loại:</strong> ${location.LoaiDiaDiem}</p>
                        <p><strong>Loại thuê:</strong> ${location.LoaiThue || 'Cả hai'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Giá thuê</h6>
                        <p><strong>Giá:</strong> ${getLocationPriceText(location)}</p>
                        ${location.MoTa ? `<p><strong>Mô tả:</strong> ${location.MoTa}</p>` : ''}
                    </div>
                </div>
            `;
            
            document.getElementById('selectLocationBtn').onclick = () => selectLocation(location);
            
            const modal = new bootstrap.Modal(document.getElementById('locationModal'), {
                backdrop: false
            });
            modal.show();
        }

        function viewEquipmentDetails(equipment) {
            document.getElementById('equipmentModalTitle').innerHTML = `<i class="fas fa-cog"></i> ${equipment.TenThietBi}`;
            
            const body = document.getElementById('equipmentModalBody');
            body.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Thông tin thiết bị</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Tên:</strong></td><td>${equipment.TenThietBi}</td></tr>
                            <tr><td><strong>Loại:</strong></td><td>${equipment.LoaiThietBi}</td></tr>
                            <tr><td><strong>Hãng:</strong></td><td>${equipment.HangSX || 'N/A'}</td></tr>
                            <tr><td><strong>Đơn vị:</strong></td><td>${equipment.DonViTinh || 'cái'}</td></tr>
                            <tr><td><strong>Giá thuê:</strong></td><td><strong class="text-success">${new Intl.NumberFormat('vi-VN').format(equipment.GiaThue || 0)} VNĐ</strong></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Mô tả</h6>
                        <p>${equipment.MoTa || 'Không có mô tả'}</p>
                        
                        <h6>Số lượng</h6>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(-1)">-</button>
                            <input type="number" class="form-control text-center" id="equipmentQuantity" value="1" min="1" max="100">
                            <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('addEquipmentBtn').onclick = () => addEquipment(equipment);
            
            const modal = new bootstrap.Modal(document.getElementById('equipmentModal'), {
                backdrop: false
            });
            modal.show();
        }

        function viewComboDetails(combo) {
            document.getElementById('equipmentModalTitle').innerHTML = `<i class="fas fa-box"></i> ${combo.TenCombo}`;
            
            const body = document.getElementById('equipmentModalBody');
            body.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Thông tin combo</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Tên:</strong></td><td>${combo.TenCombo}</td></tr>
                            <tr><td><strong>Loại:</strong></td><td>Combo thiết bị</td></tr>
                            <tr><td><strong>Giá combo:</strong></td><td><strong class="text-success">${new Intl.NumberFormat('vi-VN').format(combo.GiaCombo || 0)} VNĐ</strong></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Mô tả</h6>
                        <p>${combo.MoTa || 'Không có mô tả'}</p>
                        
                        <h6>Số lượng</h6>
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(-1)">-</button>
                            <input type="number" class="form-control text-center" id="equipmentQuantity" value="1" min="1" max="100">
                            <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('addEquipmentBtn').onclick = () => addCombo(combo);
            
            const modal = new bootstrap.Modal(document.getElementById('equipmentModal'), {
                backdrop: false
            });
            modal.show();
        }

        function changeQuantity(delta) {
            const input = document.getElementById('equipmentQuantity');
            const newValue = parseInt(input.value) + delta;
            if (newValue >= 1 && newValue <= 100) {
                input.value = newValue;
            }
        }

        function addEquipment(equipment) {
            const quantity = parseInt(document.getElementById('equipmentQuantity').value);
            const existingIndex = selectedEquipment.findIndex(item => 
                item.type === 'equipment' && item.id === equipment.ID_TB
            );

            if (existingIndex >= 0) {
                selectedEquipment[existingIndex].quantity += quantity;
            } else {
                selectedEquipment.push({
                    type: 'equipment',
                    id: equipment.ID_TB,
                    name: equipment.TenThietBi,
                    price: equipment.GiaThue,
                    quantity: quantity,
                    unit: equipment.DonViTinh || 'cái'
                });
            }

            updateSelectedEquipment();
            bootstrap.Modal.getInstance(document.getElementById('equipmentModal')).hide();
        }

        function addCombo(combo) {
            const quantity = parseInt(document.getElementById('equipmentQuantity').value);
            const existingIndex = selectedEquipment.findIndex(item => 
                item.type === 'combo' && item.id === combo.ID_Combo
            );

            if (existingIndex >= 0) {
                selectedEquipment[existingIndex].quantity += quantity;
            } else {
                selectedEquipment.push({
                    type: 'combo',
                    id: combo.ID_Combo,
                    name: combo.TenCombo,
                    price: combo.GiaCombo,
                    quantity: quantity,
                    unit: 'combo'
                });
            }

            updateSelectedEquipment();
            bootstrap.Modal.getInstance(document.getElementById('equipmentModal')).hide();
        }

        function updateSelectedEquipment() {
            const container = document.getElementById('selectedEquipment');
            const table = document.getElementById('selectedEquipmentTable');

            if (selectedEquipment.length === 0) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';
            table.innerHTML = '';

            selectedEquipment.forEach((item, index) => {
                const row = document.createElement('tr');
                const totalPrice = item.price * item.quantity;
                
                row.innerHTML = `
                    <td>${item.name}</td>
                    <td>
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary" type="button" onclick="event.preventDefault(); event.stopPropagation(); updateEquipmentQuantity(${index}, -1); return false;">-</button>
                            <input type="number" class="form-control text-center" value="${item.quantity}" min="1" max="100" style="width: 60px;" onchange="updateEquipmentQuantityFromInput(${index}, this.value)" oninput="event.preventDefault(); event.stopPropagation();">
                            <button class="btn btn-outline-secondary" type="button" onclick="event.preventDefault(); event.stopPropagation(); updateEquipmentQuantity(${index}, 1); return false;">+</button>
                        </div>
                    </td>
                    <td>${new Intl.NumberFormat('vi-VN').format(item.price)} VNĐ</td>
                    <td><strong>${new Intl.NumberFormat('vi-VN').format(totalPrice)} VNĐ</strong></td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault(); event.stopPropagation(); removeEquipment(${index}); return false;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                
                table.appendChild(row);
            });
        }

        function updateEquipmentQuantity(index, delta) {
            // Prevent any form submission
            if (typeof event !== 'undefined' && event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            if (index >= 0 && index < selectedEquipment.length) {
                const newQuantity = selectedEquipment[index].quantity + delta;
                if (newQuantity >= 1 && newQuantity <= 100) {
                    selectedEquipment[index].quantity = newQuantity;
                    updateSelectedEquipment();
                    updateCostSummary();
                }
            }
            
            return false;
        }
        
        function updateEquipmentQuantityFromInput(index, value) {
            // Prevent any form submission
            if (typeof event !== 'undefined' && event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            if (index >= 0 && index < selectedEquipment.length) {
                const newQuantity = parseInt(value) || 1;
                if (newQuantity >= 1 && newQuantity <= 100) {
                    selectedEquipment[index].quantity = newQuantity;
                    updateSelectedEquipment();
                    updateCostSummary();
                } else {
                    // Reset to valid value
                    const input = event ? event.target : null;
                    if (input) {
                        input.value = selectedEquipment[index].quantity;
                    }
                }
            }
            
            return false;
        }

        function removeEquipment(index) {
            // Prevent any form submission
            if (typeof event !== 'undefined' && event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            if (index >= 0 && index < selectedEquipment.length) {
                selectedEquipment.splice(index, 1);
                updateSelectedEquipment();
                updateCostSummary();
            }
            
            return false;
        }

        function validateDates() {
            const startDate = new Date(document.getElementById('startDate').value);
            const endDate = new Date(document.getElementById('endDate').value);
            const now = new Date();

            // Clear previous validation
            document.getElementById('startDate').setCustomValidity('');
            document.getElementById('endDate').setCustomValidity('');

            // Validate start date
            if (startDate && startDate < now) {
                document.getElementById('startDate').setCustomValidity('Ngày bắt đầu không được trong quá khứ');
                return false;
            }

            // Validate end date
            if (endDate && startDate && endDate <= startDate) {
                document.getElementById('endDate').setCustomValidity('Ngày kết thúc phải sau ngày bắt đầu');
                return false;
            }

            // Validate minimum duration (at least 1 hour)
            if (startDate && endDate) {
                const durationHours = (endDate - startDate) / (1000 * 60 * 60);
                if (durationHours < 1) {
                    document.getElementById('endDate').setCustomValidity('Sự kiện phải kéo dài ít nhất 1 giờ');
                    return false;
                }
            }

            return true;
        }

        function updateDurationDisplay() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const durationAlert = document.getElementById('durationAlert');
            const durationText = document.getElementById('durationText');
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                if (end > start) {
                    const durationMs = end - start;
                    const hours = Math.floor(durationMs / (1000 * 60 * 60));
                    const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
                    
                    let durationString = '';
                    if (hours > 0) {
                        durationString += `${hours} giờ`;
                    }
                    if (minutes > 0) {
                        durationString += (hours > 0 ? ' ' : '') + `${minutes} phút`;
                    }
                    
                    durationText.textContent = `Thời gian sự kiện: ${durationString}`;
                    durationAlert.style.display = 'block';
                } else {
                    durationAlert.style.display = 'none';
                }
            } else {
                durationAlert.style.display = 'none';
            }
        }

        function changeStep(direction) {
            console.log('changeStep called with direction:', direction);
            console.log('Current step before change:', currentStep);
            
            if (direction === 1) {
                // Validate current step
                console.log('Validating current step:', currentStep);
                if (!validateCurrentStep()) {
                    console.log('Validation failed for step:', currentStep);
                    return;
                }
                console.log('Validation passed for step:', currentStep);
            }

            currentStep += direction;
            console.log('Current step after change:', currentStep);
            updateStepDisplay();
        }

        function validateCurrentStep() {
            console.log('=== VALIDATE CURRENT STEP ===');
            console.log('Current step:', currentStep);
            
            const currentStepElement = document.getElementById(`step${currentStep}`);
            console.log('Current step element:', currentStepElement);
            
            const inputs = currentStepElement.querySelectorAll('input[required], select[required]');
            console.log('Required inputs found:', inputs.length);
            
            let isValid = true;
            inputs.forEach((input, index) => {
                console.log(`Input ${index}:`, input.id, 'Value:', input.value, 'Valid:', input.value.trim() !== '');
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Special validation for step 1 (customer selection)
            if (currentStep === 1 && !selectedCustomer) {
                console.log('Step 1 validation failed: No customer selected');
                alert('Vui lòng chọn khách hàng');
                isValid = false;
            }

            // Special validation for step 2 (event information)
            if (currentStep === 2) {
                console.log('Step 2 validation...');
                if (!validateDates()) {
                    console.log('Date validation failed');
                    isValid = false;
                }
                
                // Validate event name length
                const eventName = document.getElementById('eventName').value.trim();
                console.log('Event name:', eventName, 'Length:', eventName.length);
                if (eventName.length < 3) {
                    document.getElementById('eventName').setCustomValidity('Tên sự kiện phải có ít nhất 3 ký tự');
                    document.getElementById('eventName').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('eventName').setCustomValidity('');
                    document.getElementById('eventName').classList.remove('is-invalid');
                }
                
                // Validate expected guests
                const expectedGuests = parseInt(document.getElementById('expectedGuests').value);
                console.log('Expected guests:', expectedGuests);
                if (expectedGuests < 1) {
                    document.getElementById('expectedGuests').setCustomValidity('Số khách dự kiến phải lớn hơn 0');
                    document.getElementById('expectedGuests').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('expectedGuests').setCustomValidity('');
                    document.getElementById('expectedGuests').classList.remove('is-invalid');
                }
            }

            // Special validation for step 3 (location selection)
            if (currentStep === 3 && !selectedLocation) {
                console.log('Step 3 validation failed: No location selected');
                alert('Vui lòng chọn địa điểm');
                isValid = false;
            }

            // Special validation for step 4 (equipment selection) - Optional
            if (currentStep === 4) {
                // Equipment selection is optional, so no validation needed
                console.log('Step 4 validation: Equipment selection is optional');
            }

            console.log('Validation result:', isValid);
            console.log('=== END VALIDATE CURRENT STEP ===');
            return isValid;
        }

        function updateStepDisplay() {
            // Hide all steps
            for (let i = 1; i <= totalSteps; i++) {
                document.getElementById(`step${i}`).style.display = 'none';
            }

            // Show current step
            document.getElementById(`step${currentStep}`).style.display = 'block';

            // Update navigation buttons
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');

            prevBtn.style.display = currentStep > 1 ? 'inline-block' : 'none';
            nextBtn.style.display = currentStep < totalSteps ? 'inline-block' : 'none';
            submitBtn.style.display = currentStep === totalSteps ? 'inline-block' : 'none';

            // Debug: Log button states
            console.log('Current Step:', currentStep);
            console.log('Total Steps:', totalSteps);
            console.log('Submit Button Display:', submitBtn.style.display);
            console.log('Next Button Display:', nextBtn.style.display);
            console.log('Submit Button Element:', submitBtn);
            console.log('Submit Button Disabled:', submitBtn.disabled);
            console.log('Submit Button HTML:', submitBtn.innerHTML);

            // Update step summaries on step 5
            if (currentStep === 5) {
                updateSummaries();
            }
        }

        function updateSummaries() {
            // Customer summary
            if (selectedCustomer) {
                document.getElementById('customerSummary').innerHTML = `
                    <p><strong>Họ tên:</strong> ${selectedCustomer.HoTen}</p>
                    <p><strong>Số điện thoại:</strong> ${selectedCustomer.SoDienThoai}</p>
                    <p><strong>Email:</strong> ${selectedCustomer.Email || 'Không có'}</p>
                    <p><strong>Địa chỉ:</strong> ${selectedCustomer.DiaChi || 'Không có'}</p>
                    <p><strong>Số sự kiện đã đăng ký:</strong> ${selectedCustomer.event_count || 0}</p>
                `;
            } else {
                document.getElementById('customerSummary').innerHTML = '<p class="text-muted">Chưa chọn khách hàng</p>';
            }

            // Event summary
            const eventTypeName = document.getElementById('eventType').selectedOptions[0]?.textContent || 'Chưa chọn';
            document.getElementById('eventSummary').innerHTML = `
                <p><strong>Tên sự kiện:</strong> ${document.getElementById('eventName').value}</p>
                <p><strong>Loại sự kiện:</strong> ${eventTypeName}</p>
                <p><strong>Ngày bắt đầu:</strong> ${formatDateTime(document.getElementById('startDate').value)}</p>
                <p><strong>Ngày kết thúc:</strong> ${formatDateTime(document.getElementById('endDate').value)}</p>
                <p><strong>Số khách dự kiến:</strong> ${document.getElementById('expectedGuests').value} người</p>
            `;

            // Location summary
            if (selectedLocation) {
                document.getElementById('locationSummary').innerHTML = `
                    <p><strong>Tên địa điểm:</strong> ${selectedLocation.TenDiaDiem}</p>
                    <p><strong>Địa chỉ:</strong> ${selectedLocation.DiaChi}</p>
                    <p><strong>Giá thuê:</strong> ${getLocationPriceText(selectedLocation)}</p>
                    <p><strong>Sức chứa:</strong> ${selectedLocation.SucChua || 'N/A'} người</p>
                `;
            }

            // Equipment summary
            if (selectedEquipment.length > 0) {
                let equipmentHtml = '<table class="table table-sm"><thead><tr><th>Thiết bị</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead><tbody>';
                selectedEquipment.forEach(item => {
                    const totalPrice = item.price * item.quantity;
                    equipmentHtml += `
                        <tr>
                            <td>${item.name}</td>
                            <td>${item.quantity} ${item.unit}</td>
                            <td>${new Intl.NumberFormat('vi-VN').format(item.price)} VNĐ</td>
                            <td><strong>${new Intl.NumberFormat('vi-VN').format(totalPrice)} VNĐ</strong></td>
                        </tr>
                    `;
                });
                equipmentHtml += '</tbody></table>';
                document.getElementById('equipmentSummary').innerHTML = equipmentHtml;
            } else {
                document.getElementById('equipmentSummary').innerHTML = '<p class="text-muted">Không có thiết bị nào được chọn</p>';
            }

            // Cost summary
            updateCostSummary();
        }

        function updateCostSummary() {
            let totalCost = 0;
            let breakdown = [];

            // Check if indoor location with room
            const isIndoorWithRoom = selectedLocation && 
                                   selectedLocation.LoaiDiaDiem === 'Trong nhà' && 
                                   selectedLocation.selectedRoom;

            // Event type cost (NOT added if indoor location with room)
            if (!isIndoorWithRoom) {
                const eventTypeSelect = document.getElementById('eventType');
                const selectedEventType = eventTypes.find(type => type.ID_LoaiSK == eventTypeSelect.value);
                if (selectedEventType && selectedEventType.GiaCoBan) {
                    totalCost += parseFloat(selectedEventType.GiaCoBan);
                    breakdown.push({
                        name: `Loại sự kiện: ${selectedEventType.TenLoai}`,
                        amount: parseFloat(selectedEventType.GiaCoBan)
                    });
                }
            }

            // Location/Room cost
            if (selectedLocation) {
                const startDate = new Date(document.getElementById('startDate').value);
                const endDate = new Date(document.getElementById('endDate').value);
                const durationHours = Math.ceil((endDate - startDate) / (1000 * 60 * 60));
                const durationDays = Math.ceil(durationHours / 24);

                let locationCost = 0;
                let locationName = selectedLocation.TenDiaDiem;
                
                // Priority: Room price for indoor locations > Location price
                if (isIndoorWithRoom && selectedLocation.selectedRoom) {
                    const room = selectedLocation.selectedRoom;
                    const rentalType = selectedLocation.selectedRoomRentalType || selectedLocation.selectedRentalType;
                    
                    if (rentalType === 'hour' && room.GiaThueGio) {
                        locationCost = durationHours * parseFloat(room.GiaThueGio);
                        locationName = `${selectedLocation.TenDiaDiem} - ${room.TenPhong} (Theo giờ)`;
                    } else if (rentalType === 'day' && room.GiaThueNgay) {
                        locationCost = durationDays * parseFloat(room.GiaThueNgay);
                        locationName = `${selectedLocation.TenDiaDiem} - ${room.TenPhong} (Theo ngày)`;
                    } else if (room.LoaiThue === 'Theo giờ' && room.GiaThueGio) {
                        locationCost = durationHours * parseFloat(room.GiaThueGio);
                        locationName = `${selectedLocation.TenDiaDiem} - ${room.TenPhong} (Theo giờ)`;
                    } else if (room.LoaiThue === 'Theo ngày' && room.GiaThueNgay) {
                        locationCost = durationDays * parseFloat(room.GiaThueNgay);
                        locationName = `${selectedLocation.TenDiaDiem} - ${room.TenPhong} (Theo ngày)`;
                    } else if (room.LoaiThue === 'Cả hai') {
                        // Default to daily rental
                        locationCost = durationDays * parseFloat(room.GiaThueNgay || 0);
                        locationName = `${selectedLocation.TenDiaDiem} - ${room.TenPhong} (Theo ngày)`;
                    }
                } else {
                    // Use location price (for outdoor locations or indoor without room)
                    // Priority: User's selection > Database default
                    if (selectedLocation.selectedRentalType) {
                        // User has explicitly chosen rental type
                        if (selectedLocation.selectedRentalType === 'hour' && selectedLocation.GiaThueGio) {
                            locationCost = durationHours * parseFloat(selectedLocation.GiaThueGio);
                        } else if (selectedLocation.selectedRentalType === 'day' && selectedLocation.GiaThueNgay) {
                            locationCost = durationDays * parseFloat(selectedLocation.GiaThueNgay);
                        }
                    } else if (selectedLocation.LoaiThue === 'Theo giờ' && selectedLocation.GiaThueGio) {
                        locationCost = durationHours * parseFloat(selectedLocation.GiaThueGio);
                    } else if (selectedLocation.LoaiThue === 'Theo ngày' && selectedLocation.GiaThueNgay) {
                        locationCost = durationDays * parseFloat(selectedLocation.GiaThueNgay);
                    } else if (selectedLocation.LoaiThue === 'Cả hai') {
                        // Default to daily rental for better UX
                        locationCost = durationDays * parseFloat(selectedLocation.GiaThueNgay || 0);
                    }
                }

                if (locationCost > 0) {
                    totalCost += locationCost;
                    breakdown.push({
                        name: `Thuê ${locationName}`,
                        amount: locationCost
                    });
                }
            }

            // Equipment cost
            let equipmentCost = 0;
            selectedEquipment.forEach(item => {
                const itemCost = item.price * item.quantity;
                equipmentCost += itemCost;
                breakdown.push({
                    name: item.name,
                    amount: itemCost
                });
            });

            if (equipmentCost > 0) {
                totalCost += equipmentCost;
            }

            // Display breakdown
            let costHtml = '<div class="cost-breakdown">';
            breakdown.forEach(item => {
                costHtml += `
                    <div class="cost-item">
                        <span>${item.name}</span>
                        <span>${new Intl.NumberFormat('vi-VN').format(item.amount)} VNĐ</span>
                    </div>
                `;
            });
            costHtml += `
                <div class="cost-item">
                    <span><strong>TỔNG CỘNG</strong></span>
                    <span><strong>${new Intl.NumberFormat('vi-VN').format(totalCost)} VNĐ</strong></span>
                </div>
            </div>`;

            document.getElementById('costSummary').innerHTML = costHtml;
        }

        function formatDateTime(dateTimeString) {
            if (!dateTimeString) return '';
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
        }

        // Test submit listener function
        function testSubmitListener() {
            console.log('=== TESTING SUBMIT LISTENER ===');
            
            const form = document.getElementById('eventRegistrationForm');
            if (!form) {
                console.error('Form not found for testing!');
                return;
            }
            
            console.log('Form found for testing');
            console.log('Form onsubmit:', form.onsubmit);
            
            // Try to trigger submit event
            console.log('Triggering submit event...');
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            const result = form.dispatchEvent(submitEvent);
            console.log('Submit event dispatched, result:', result);
            
            // Check if handleSubmit was called
            console.log('Checking if handleSubmit was called...');
        }
        
        // Add test function to window for debugging
        window.testSubmitListener = testSubmitListener;

        function handleSubmit(event) {
            console.log('=== DEBUG SUBMIT BUTTON ===');
            console.log('Event:', event);
            console.log('Event type:', event.type);
            console.log('Event target:', event.target);
            
            event.preventDefault();

            console.log('Validating current step...');
            if (!validateCurrentStep()) {
                console.log('Validation failed!');
                return;
            }
            console.log('Validation passed!');

            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            console.log('Submit button element:', submitBtn);
            console.log('Submit button current HTML:', submitBtn.innerHTML);
            
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            submitBtn.disabled = true;
            
            console.log('Button state changed to loading...');

            // Prepare form data - ensure equipment has valid format
            console.log('=== PREPARING FORM DATA ===');
            console.log('selectedEquipment before format:', selectedEquipment);
            console.log('selectedEquipment length:', selectedEquipment.length);
            
            const formattedEquipment = selectedEquipment.map(item => {
                // Ensure price is a valid number, if 0 or invalid, try to get from item data
                let price = parseFloat(item.price);
                if (isNaN(price) || price <= 0) {
                    // Try to get price from the original equipment/combo data if available
                    if (item.type === 'equipment' && item.originalPrice) {
                        price = parseFloat(item.originalPrice);
                    } else if (item.type === 'combo' && item.originalPrice) {
                        price = parseFloat(item.originalPrice);
                    } else {
                        price = 0; // Will be fetched from database on backend
                    }
                }
                
                const formatted = {
                    type: item.type,
                    id: parseInt(item.id),
                    quantity: parseInt(item.quantity) || 1,
                    price: price
                };
                console.log('Formatted item:', formatted);
                return formatted;
            }).filter(item => {
                // Allow price = 0 as backend will fetch from database
                // But ensure type, id, and quantity are valid
                const isValid = item.type && (item.type === 'equipment' || item.type === 'combo') && 
                               item.id > 0 && item.quantity > 0;
                if (!isValid) {
                    console.warn('Filtered out invalid item:', item);
                }
                return isValid;
            });
            
            console.log('formattedEquipment after filter:', formattedEquipment);
            console.log('formattedEquipment length:', formattedEquipment.length);
            
            const formData = {
                action: isEditMode ? 'update_registration' : 'register_event_for_existing_customer',
                registration_id: isEditMode ? editRegistrationId : null,
                customer_id: selectedCustomer.ID_KhachHang,
                event: {
                    name: document.getElementById('eventName').value,
                    type: document.getElementById('eventType').value,
                    description: document.getElementById('eventDescription').value,
                    startDate: document.getElementById('startDate').value,
                    endDate: document.getElementById('endDate').value,
                    expectedGuests: document.getElementById('expectedGuests').value,
                    budget: document.getElementById('budget').value
                },
                location: selectedLocation ? selectedLocation.ID_DD : null,
                location_rental_type: selectedLocation ? selectedLocation.selectedRentalType : null,
                room_id: selectedLocation && selectedLocation.selectedRoomId ? selectedLocation.selectedRoomId : null,
                equipment: formattedEquipment,
                adminNotes: document.getElementById('adminNotes').value
            };

            console.log('=== FORM DATA ===');
            console.log('Selected Customer:', selectedCustomer);
            console.log('Selected Location:', selectedLocation);
            console.log('Selected Equipment:', selectedEquipment);
            console.log('Form Data:', formData);

            // Submit form
            console.log('Sending fetch request...');
            fetch('../src/controllers/admin-event-register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                console.log('=== RESPONSE RECEIVED ===');
                console.log('Response status:', response.status);
                console.log('Response statusText:', response.statusText);
                console.log('Response headers:', response.headers);
                console.log('Response ok:', response.ok);
                return response.text(); // Get text first to debug
            })
            .then(text => {
                console.log('=== RESPONSE TEXT ===');
                console.log('Raw response:', text);
                console.log('Response length:', text.length);
                
                try {
                    const data = JSON.parse(text);
                    console.log('=== PARSED DATA ===');
                    console.log('Parsed data:', data);
                    console.log('Success:', data.success);
                    console.log('Message:', data.message);
                    
                    if (data.success) {
                        console.log('SUCCESS: Registration completed!');
                        alert('Đăng ký sự kiện thành công!');
                        // Reset form or redirect
                        window.location.href = 'event-registrations.php';
                    } else {
                        console.log('ERROR: Registration failed!');
                        alert('Lỗi: ' + (data.message || 'Có lỗi xảy ra khi đăng ký'));
                        // Reset button state
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        console.log('Button state reset to original');
                    }
                } catch (e) {
                    alert('Lỗi: Phản hồi từ server không hợp lệ');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                alert('Có lỗi xảy ra khi đăng ký sự kiện: ' + error.message);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
    </script>

<?php include 'includes/admin-footer.php'; ?>
