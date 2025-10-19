<?php
// Include admin header
include 'includes/admin-header.php';

// Check if user has event manager privileges
$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? 0;
if (!in_array($userRole, [1, 3])) {
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// Get current user info
$currentUserId = $_SESSION['user']['ID_User'] ?? $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0;
$currentUserName = $_SESSION['user']['HoTen'] ?? $_SESSION['user']['name'] ?? $_SESSION['user_name'] ?? 'Admin';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-calendar-plus"></i>
        Đăng ký sự kiện cho khách hàng
    </h1>
    <p class="page-subtitle">Quản lý sự kiện có thể đăng ký sự kiện thay mặt khách hàng</p>
</div>

<!-- Event Registration Container -->
<div class="admin-registration-container">
    <div class="registration-layout">
        <!-- Customer Selection Sidebar -->
        <div class="customer-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">
                    <h5><i class="fas fa-users"></i> Chọn khách hàng</h5>
                    <div class="customer-count" id="customerCount">
                        <span class="badge bg-primary">0 khách hàng</span>
                    </div>
                </div>
                <div class="search-box">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="customerSearch" placeholder="Tìm kiếm khách hàng...">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch" title="Xóa tìm kiếm">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="sidebar-actions">
                    <button class="btn btn-sm btn-outline-primary" id="refreshCustomers" title="Làm mới danh sách">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success" id="addNewCustomer" title="Thêm khách hàng mới">
                        <i class="fas fa-user-plus"></i>
                    </button>
                </div>
            </div>
            
            <div class="customer-list" id="customerList">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải danh sách khách hàng...</p>
                </div>
            </div>
        </div>
        
        <!-- Main Registration Form -->
        <div class="registration-main">
            <!-- Selected Customer Info -->
            <div class="selected-customer-info" id="selectedCustomerInfo" style="display: none;">
                <div class="customer-card">
                    <div class="customer-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="customer-details">
                        <h6 id="selectedCustomerName">Tên khách hàng</h6>
                        <small id="selectedCustomerEmail" class="text-muted">Email khách hàng</small>
                        <div class="customer-actions">
                            <button class="btn btn-sm btn-outline-secondary" id="changeCustomerBtn">
                                <i class="fas fa-edit"></i> Đổi khách hàng
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Registration Form -->
            <div class="registration-form-container">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" id="step1-indicator">
                        <div class="step-number">1</div>
                        <div class="step-label">Thông tin sự kiện</div>
                    </div>
                    <div class="step" id="step2-indicator">
                        <div class="step-number">2</div>
                        <div class="step-label">Chọn địa điểm</div>
                    </div>
                    <div class="step" id="step3-indicator">
                        <div class="step-number">3</div>
                        <div class="step-label">Thiết bị & Xác nhận</div>
                    </div>
                </div>
                
                <!-- Error/Success Messages -->
                <div class="error-message" id="errorMessage"></div>
                <div class="success-message" id="successMessage"></div>
                
                <!-- Loading Spinner -->
                <div class="loading-spinner" id="loadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang xử lý...</p>
                </div>
                
                <form id="adminEventRegistrationForm">
                    
                    <!-- Step 1: Basic Information -->
                    <div class="form-step active" id="step1">
                        <h3 class="mb-4"><i class="fas fa-info-circle text-primary"></i> Thông tin sự kiện</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eventName" class="form-label">Tên sự kiện *</label>
                                    <input type="text" class="form-control" id="eventName" name="event_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eventType" class="form-label">Loại sự kiện *</label>
                                    <select class="form-select" id="eventType" name="event_type" required>
                                        <option value="">Chọn loại sự kiện</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eventDate" class="form-label">Ngày tổ chức *</label>
                                    <input type="date" class="form-control" id="eventDate" name="event_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eventTime" class="form-label">Giờ bắt đầu *</label>
                                    <input type="time" class="form-control" id="eventTime" name="event_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eventEndDate" class="form-label">Ngày kết thúc</label>
                                    <input type="date" class="form-control" id="eventEndDate" name="event_end_date">
                                    <div class="form-text">Để trống nếu sự kiện chỉ trong 1 ngày</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eventEndTime" class="form-label">Giờ kết thúc</label>
                                    <input type="time" class="form-control" id="eventEndTime" name="event_end_time">
                                    <div class="form-text">Sự kiện có thể kéo dài tối đa 7 ngày</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expectedGuests" class="form-label">Số khách dự kiến</label>
                                    <input type="number" class="form-control" id="expectedGuests" name="expected_guests" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="budget" class="form-label">Ngân sách (VNĐ)</label>
                                    <input type="number" class="form-control" id="budget" name="budget" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Mô tả sự kiện</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Mô tả chi tiết về sự kiện..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes" class="form-label">Ghi chú nội bộ</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Ghi chú nội bộ cho quản lý sự kiện..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Step 2: Location Selection -->
                    <div class="form-step" id="step2">
                        <h3 class="mb-4"><i class="fas fa-map-marker-alt text-primary"></i> Chọn địa điểm</h3>
                        <div id="locationSuggestions">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Đang tải danh sách địa điểm...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3: Equipment & Confirmation -->
                    <div class="form-step" id="step3">
                        <h3 class="mb-4"><i class="fas fa-cogs text-primary"></i> Thiết bị & Xác nhận</h3>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Combo Suggestions -->
                                <div class="mb-4">
                                    <h5><i class="fas fa-box text-primary"></i> Combo thiết bị đề xuất</h5>
                                    <div id="comboSuggestions">
                                        <div class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2">Đang tải combo thiết bị...</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Individual Equipment -->
                                <div class="mb-4">
                                    <h5><i class="fas fa-tools text-primary"></i> Thiết bị riêng lẻ</h5>
                                    <div id="equipmentSuggestions">
                                        <div class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2">Đang tải gợi ý thiết bị...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h5>Tóm tắt đơn hàng</h5>
                                <div class="summary-card" id="orderSummary">
                                    <!-- Summary will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="navigation-buttons">
                        <button type="button" class="btn btn-outline-primary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </button>
                        <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeStep(1)">
                            Tiếp theo <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                            <i class="fas fa-check"></i> Đăng ký sự kiện
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.admin-registration-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    min-height: 80vh;
}

.registration-layout {
    display: flex;
    height: 100%;
}

.customer-sidebar {
    width: 350px;
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 1.5rem;
}

.sidebar-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.sidebar-title h5 {
    margin: 0;
    color: #333;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.customer-count {
    display: flex;
    align-items: center;
}

.customer-count .badge {
    font-size: 0.75rem;
    padding: 0.4rem 0.6rem;
    border-radius: 12px;
}

.search-box {
    margin-bottom: 1rem;
}

.search-box .input-group {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.search-box .input-group-text {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    color: #6c757d;
    border-right: none;
}

.search-box .form-control {
    border: 1px solid #e9ecef;
    border-left: none;
    border-right: none;
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.search-box .form-control:focus {
    border-color: #667eea;
    box-shadow: none;
    background: white;
}

.search-box .btn {
    border: 1px solid #e9ecef;
    border-left: none;
    padding: 0.75rem 0.8rem;
    transition: all 0.3s ease;
}

.search-box .btn:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

.sidebar-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.sidebar-actions .btn {
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.sidebar-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
}

.customer-list {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.customer-item {
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: white;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.customer-item:hover {
    background: #e9ecef;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.customer-item.selected {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border-color: #667eea;
    border-left: 4px solid #667eea;
}

.customer-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.customer-email {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.customer-phone {
    font-size: 0.8rem;
    color: #adb5bd;
}

.registration-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
}

.selected-customer-info {
    padding: 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.customer-card {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.customer-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.customer-details h6 {
    margin: 0;
    font-weight: 600;
    color: #333;
}

.customer-details small {
    font-size: 0.9rem;
}

.registration-form-container {
    flex: 1;
    padding: 2rem;
    overflow-y: auto;
}

.step-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 3rem;
}

.step {
    display: flex;
    align-items: center;
    margin: 0 1rem;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 0.5rem;
    transition: all 0.3s ease;
}

.step.active .step-number {
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
}

.step.completed .step-number {
    background: #28a745;
    color: white;
}

.step-label {
    font-weight: 500;
    color: #6c757d;
}

.step.active .step-label {
    color: #667eea;
}

.form-step {
    display: none;
}

.form-step.active {
    display: block;
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-control.is-invalid, .form-select.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    background-color: #fff5f5;
}

.form-control.is-invalid:focus, .form-select.is-invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 0.25rem;
    font-style: italic;
}

.btn-primary {
    background: linear-gradient(45deg, #667eea, #764ba2);
    border: none;
    border-radius: 12px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-success {
    background: linear-gradient(45deg, #28a745, #20c997);
    border: none;
    border-radius: 12px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-success:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
}

.btn-success:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-outline-primary {
    border: 2px solid #667eea;
    color: #667eea;
    border-radius: 12px;
    padding: 12px 30px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: #667eea;
    border-color: #667eea;
    transform: translateY(-2px);
}

.navigation-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
}

.loading-spinner {
    display: none;
    text-align: center;
    padding: 2rem;
}

.error-message {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
    border: 2px solid #f5c6cb;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
    display: none;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.1);
    animation: slideInDown 0.3s ease-out;
}

.success-message {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
    border: 2px solid #c3e6cb;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
    display: none;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
    animation: slideInDown 0.3s ease-out;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Equipment and Location Styles (from register.php) */
.suggestion-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.suggestion-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.suggestion-card.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
}

.combo-card {
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 20px;
    transition: all 0.3s ease;
    cursor: pointer;
    background: white;
    height: 100%;
}

.combo-card:hover {
    border-color: #667eea;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
    transform: translateY(-5px);
}

.combo-card.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.equipment-card {
    border: 2px solid #e9ecef;
    border-radius: 15px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.equipment-card:hover {
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
    transform: translateY(-2px);
}

.equipment-card.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.summary-card {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 20px;
    border: 1px solid #e9ecef;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.summary-item:last-child {
    border-bottom: none;
    font-weight: bold;
    font-size: 1.1rem;
    color: #667eea;
}

@media (max-width: 768px) {
    .registration-layout {
        flex-direction: column;
    }
    
    .customer-sidebar {
        width: 100%;
        height: 200px;
    }
    
    .registration-main {
        height: 400px;
    }
    
    .sidebar-header {
        padding: 1rem;
    }
    
    .sidebar-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .sidebar-actions {
        justify-content: flex-start;
        margin-top: 0.5rem;
    }
    
    .search-box .input-group {
        flex-direction: column;
    }
    
    .search-box .input-group-text,
    .search-box .form-control,
    .search-box .btn {
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .search-box .input-group-text {
        border-bottom: none;
    }
    
    .search-box .form-control {
        border-top: none;
        border-bottom: none;
    }
    
    .search-box .btn {
        border-top: none;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let currentStep = 1;
    let selectedCustomer = null;
    let selectedLocation = null;
    let selectedEquipment = [];
    let selectedCombo = null;
    let eventTypes = [];
    let customers = [];
    let locations = [];
    let equipmentSuggestions = [];
    let comboSuggestions = [];
    
    // Initialize the form
    $(document).ready(function() {
        // Check session status first
        console.log('=== INITIALIZING EVENT REGISTRATION FORM ===');
        
        loadCustomers();
        loadEventTypes();
        setMinDate();
        
        // Handle URL parameters
        handleURLParameters();
        
        // Clear any existing error messages on page load
        $('#errorMessage').hide();
        
        // Add click handler to clear errors when customer is clicked
        $(document).on('click', '.customer-item', function() {
            $('#errorMessage').hide();
        });
        
        // Add click handler for submit button
        $('#submitBtn').on('click', function(e) {
            console.log('Submit button clicked');
            e.preventDefault();
            $('#adminEventRegistrationForm').submit();
        });
    });
    
    
    
    
    
    
    
    
    
    // Handle URL parameters
    function handleURLParameters() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Auto-fill form fields from URL parameters
            if (urlParams.has('event_name')) {
                $('#eventName').val(decodeURIComponent(urlParams.get('event_name')));
            }
            if (urlParams.has('event_type')) {
                $('#eventType').val(urlParams.get('event_type'));
            }
            if (urlParams.has('event_date')) {
                $('#eventDate').val(urlParams.get('event_date'));
            }
            if (urlParams.has('event_time')) {
                $('#eventTime').val(urlParams.get('event_time'));
            }
            if (urlParams.has('event_end_date')) {
                $('#eventEndDate').val(urlParams.get('event_end_date'));
            }
            if (urlParams.has('event_end_time')) {
                $('#eventEndTime').val(urlParams.get('event_end_time'));
            }
            if (urlParams.has('expected_guests')) {
                $('#expectedGuests').val(urlParams.get('expected_guests'));
            }
            if (urlParams.has('budget')) {
                $('#budget').val(urlParams.get('budget'));
            }
            if (urlParams.has('description')) {
                $('#description').val(decodeURIComponent(urlParams.get('description')));
            }
            if (urlParams.has('notes')) {
                $('#notes').val(decodeURIComponent(urlParams.get('notes')));
            }
            
            // If event type is provided, load locations
            if (urlParams.has('event_type')) {
                setTimeout(() => {
                    loadLocationSuggestions();
                }, 1000);
            }
        } catch (error) {
            console.error('Error handling URL parameters:', error);
        }
    }
    
    // Set minimum date to today
    function setMinDate() {
        const today = new Date().toISOString().split('T')[0];
        $('#eventDate').attr('min', today);
        $('#eventEndDate').attr('min', today);
        
        // Add real-time validation
        $('#eventDate').on('change', validateEventDate);
        $('#eventEndDate').on('change', validateEndDate);
        $('#eventTime').on('change', validateEventTime);
        $('#eventEndTime').on('change', validateEndTime);
        
        // Clear validation errors when user starts typing
        $('#eventDate, #eventEndDate, #eventTime, #eventEndTime').on('input', function() {
            $(this).removeClass('is-invalid');
        });
        
        // Update order summary when end date/time changes
        $('#eventEndDate, #eventEndTime').on('change', function() {
            if (selectedLocation) {
                updateOrderSummary();
            }
        });
    }
    
    // Validate event date
    function validateEventDate() {
        const eventDate = $('#eventDate').val();
        const eventTime = $('#eventTime').val();
        
        if (!eventDate) return;
        
        const selectedDate = new Date(eventDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Check if date is in the past
        if (selectedDate < today) {
            showFieldError('eventDate', 'Ngày tổ chức không được là ngày trong quá khứ');
            return false;
        }
        
        // Check if date is today and time is in the past
        if (selectedDate.getTime() === today.getTime() && eventTime) {
            const now = new Date();
            const selectedDateTime = new Date(eventDate + 'T' + eventTime);
            
            if (selectedDateTime < now) {
                showFieldError('eventTime', 'Thời gian sự kiện không được là thời gian trong quá khứ');
                return false;
            }
        }
        
        $('#eventDate').removeClass('is-invalid');
        return true;
    }
    
    // Validate end date
    function validateEndDate() {
        const eventDate = $('#eventDate').val();
        const eventEndDate = $('#eventEndDate').val();
        
        if (!eventEndDate || !eventDate) return;
        
        const startDate = new Date(eventDate);
        const endDate = new Date(eventEndDate);
        
        if (endDate < startDate) {
            showFieldError('eventEndDate', 'Ngày kết thúc không được trước ngày bắt đầu');
            return false;
        }
        
        $('#eventEndDate').removeClass('is-invalid');
        return true;
    }
    
    // Validate event time
    function validateEventTime() {
        const eventDate = $('#eventDate').val();
        const eventTime = $('#eventTime').val();
        
        if (!eventTime || !eventDate) return;
        
        const selectedDateTime = new Date(eventDate + 'T' + eventTime);
        const now = new Date();
        
        // Check if time is in the past (only if date is today)
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(eventDate);
        
        if (selectedDate.getTime() === today.getTime() && selectedDateTime < now) {
            showFieldError('eventTime', 'Thời gian sự kiện không được là thời gian trong quá khứ');
            return false;
        }
        
        // Check if time is reasonable (between 6 AM and 11 PM)
        const hour = selectedDateTime.getHours();
        if (hour < 6 || hour > 23) {
            showFieldError('eventTime', 'Thời gian sự kiện phải trong khoảng 6:00 - 23:00');
            return false;
        }
        
        $('#eventTime').removeClass('is-invalid');
        return true;
    }
    
    // Validate end time
    function validateEndTime() {
        const eventDate = $('#eventDate').val();
        const eventTime = $('#eventTime').val();
        const eventEndDate = $('#eventEndDate').val();
        const eventEndTime = $('#eventEndTime').val();
        
        if (!eventEndTime) return true;
        
        // If no end date, use start date
        const endDate = eventEndDate || eventDate;
        if (!endDate) return true;
        
        // Validate date format before creating Date objects
        if (!eventDate || !eventTime || !endDate || !eventEndTime) {
            return true;
        }
        
        const startDateTime = new Date(eventDate + 'T' + eventTime);
        const endDateTime = new Date(endDate + 'T' + eventEndTime);
        
        // Check if dates are valid
        if (isNaN(startDateTime.getTime()) || isNaN(endDateTime.getTime())) {
            return true;
        }
        
        if (endDateTime <= startDateTime) {
            showFieldError('eventEndTime', 'Thời gian kết thúc phải sau thời gian bắt đầu');
            return false;
        }
        
        // Check if end time is reasonable (more flexible for multi-day events)
        const hour = endDateTime.getHours();
        const startHour = startDateTime.getHours();
        
        // If it's the same day, apply time restrictions
        if (eventDate === eventEndDate) {
            if (hour < 6 || hour > 23) {
                showFieldError('eventEndTime', 'Thời gian kết thúc phải trong khoảng 6:00 - 23:00');
                return false;
            }
        } else {
            // For multi-day events, only check if end time is reasonable (not too early or too late)
            if (hour < 5 || hour > 23) {
                showFieldError('eventEndTime', 'Thời gian kết thúc phải hợp lý (5:00 - 23:00)');
                return false;
            }
        }
        
        $('#eventEndTime').removeClass('is-invalid');
        return true;
    }
    
    // Load customers
    function loadCustomers() {
        $.get('../../src/controllers/admin-event-register.php?action=get_customers', function(data) {
        if (data.success) {
            customers = data.customers;
                displayCustomers();
            } else {
                $('#customerList').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        ${data.error || 'Không thể tải danh sách khách hàng.'}
                    </div>
                `);
            }
        }, 'json').fail(function(xhr, status, error) {
            
            let errorMessage = 'Lỗi kết nối khi tải danh sách khách hàng.';
            if (xhr.status === 0) {
                errorMessage += ' (Không thể kết nối đến server)';
            } else if (xhr.status === 404) {
                errorMessage += ' (Không tìm thấy endpoint)';
            } else if (xhr.status === 500) {
                errorMessage += ' (Lỗi server)';
            } else {
                errorMessage += ` (HTTP ${xhr.status}: ${error})`;
            }
            
            $('#customerList').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    ${errorMessage}
                    <br><small>Debug: ${xhr.responseText.substring(0, 100)}...</small>
                </div>
            `);
        });
    }
    
    // Display customers
    function displayCustomers() {
        // Update customer count
        updateCustomerCount();
        
        if (customers.length === 0) {
            $('#customerList').html(`
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Không có khách hàng nào.
                </div>
            `);
            return;
        }
        
        let html = '';
        customers.forEach(customer => {
            html += `
                <div class="customer-item" onclick="selectCustomer(${customer.ID_User})" data-customer-id="${customer.ID_User}">
                    <div class="customer-name">${customer.HoTen || customer.Email}</div>
                    <div class="customer-email">${customer.Email}</div>
                    ${customer.SoDienThoai ? `<div class="customer-phone">${customer.SoDienThoai}</div>` : ''}
                </div>
            `;
        });
        
        $('#customerList').html(html);
    }
    
    // Update customer count
    function updateCustomerCount() {
        const count = customers.length;
        $('#customerCount .badge').text(`${count} khách hàng`);
    }
    
    // Select customer
    function selectCustomer(customerId) {
        selectedCustomer = customers.find(customer => customer.ID_User === customerId);
        
        if (!selectedCustomer) {
            return;
        }
        
        // Update UI
        $('.customer-item').removeClass('selected');
        $(`.customer-item[data-customer-id="${customerId}"]`).addClass('selected');
        
        // Show selected customer info
        $('#selectedCustomerName').text(selectedCustomer.HoTen || selectedCustomer.Email);
        $('#selectedCustomerEmail').text(selectedCustomer.Email);
        $('#selectedCustomerInfo').show();
        
        // Enable form
        enableForm();
        
        // Clear any existing error messages
        $('#errorMessage').hide();
    }
    
    // Enable form when customer is selected
    function enableForm() {
        $('#eventName, #eventType, #eventDate, #eventTime').prop('disabled', false);
    }
    
    // Change customer
    $('#changeCustomerBtn').click(function() {
        selectedCustomer = null;
        $('#selectedCustomerInfo').hide();
        $('.customer-item').removeClass('selected');
        $('#eventName, #eventType, #eventDate, #eventTime').prop('disabled', true);
    });
    
    // Customer search
    $('#customerSearch').on('input', function() {
        const query = $(this).val().toLowerCase();
        $('.customer-item').each(function() {
            const customerName = $(this).find('.customer-name').text().toLowerCase();
            const customerEmail = $(this).find('.customer-email').text().toLowerCase();
            
            if (customerName.includes(query) || customerEmail.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Show/hide clear button
        if (query.length > 0) {
            $('#clearSearch').show();
        } else {
            $('#clearSearch').hide();
        }
    });
    
    // Clear search
    $('#clearSearch').click(function() {
        $('#customerSearch').val('');
        $('.customer-item').show();
        $(this).hide();
    });
    
    // Refresh customers
    $('#refreshCustomers').click(function() {
        $(this).html('<i class="fas fa-spinner fa-spin"></i>');
        loadCustomers();
        setTimeout(() => {
            $(this).html('<i class="fas fa-sync-alt"></i>');
        }, 1000);
    });
    
    // Add new customer (placeholder functionality)
    $('#addNewCustomer').click(function() {
        alert('Tính năng thêm khách hàng mới sẽ được phát triển trong phiên bản tiếp theo.');
    });
    
    // Load event types
    function loadEventTypes() {
        $.get('../../src/controllers/admin-event-register.php?action=get_event_types', function(data) {
            if (data.success) {
                eventTypes = data.event_types;
                const select = $('#eventType');
                select.empty().append('<option value="">Chọn loại sự kiện</option>');
                eventTypes.forEach(type => {
                    select.append(`<option value="${type.ID_LoaiSK}">${type.TenLoai}</option>`);
                });
                
                // Add event handler for event type change
                select.on('change', function() {
                    if ($(this).val()) {
                        // Pre-load locations when event type is selected
                        loadLocationSuggestions();
                    }
                });
            } else {
                showError('Không thể tải danh sách loại sự kiện: ' + data.error);
            }
        }, 'json').fail(function() {
            showError('Lỗi kết nối khi tải loại sự kiện');
        });
    }
    
    // Change step
    function changeStep(direction) {
        if (direction === 1) {
            if (!validateCurrentStep()) {
                return;
            }
            
            if (currentStep === 1) {
                loadLocationSuggestions();
            } else if (currentStep === 2) {
                loadEquipmentSuggestions();
                updateOrderSummary();
            }
        }
        
        // Hide current step
        $(`#step${currentStep}`).removeClass('active');
        $(`#step${currentStep}-indicator`).removeClass('active').addClass('completed');
        
        // Show next step
        currentStep += direction;
        $(`#step${currentStep}`).addClass('active');
        $(`#step${currentStep}-indicator`).addClass('active');
        
        // Update navigation buttons
        updateNavigationButtons();
    }
    
    // Validate current step
    function validateCurrentStep() {
        if (currentStep === 1) {
            if (!selectedCustomer) {
                showError('Vui lòng chọn khách hàng');
                return false;
            }
            
            const requiredFields = ['eventName', 'eventType', 'eventDate', 'eventTime'];
            for (let field of requiredFields) {
                if (!$(`#${field}`).val()) {
                    showError(`Vui lòng điền đầy đủ thông tin bắt buộc`);
                    $(`#${field}`).focus();
                    return false;
                }
            }
            
            // Validate all date and time fields
            if (!validateEventDate() || !validateEventTime()) {
                return false;
            }
            
            // Validate end date and time if provided
            if ($('#eventEndDate').val() && !validateEndDate()) {
                return false;
            }
            
            if ($('#eventEndTime').val() && !validateEndTime()) {
                return false;
            }
            
            // Additional business logic validation
            const eventDate = $('#eventDate').val();
            const eventTime = $('#eventTime').val();
            const eventEndDate = $('#eventEndDate').val();
            const eventEndTime = $('#eventEndTime').val();
            
            // Check if event duration is reasonable (not more than 7 days)
            if (eventEndDate && eventEndTime) {
                const startDateTime = new Date(eventDate + 'T' + eventTime);
                const endDateTime = new Date(eventEndDate + 'T' + eventEndTime);
                const durationHours = (endDateTime - startDateTime) / (1000 * 60 * 60);
                const durationDays = durationHours / 24;
                
                if (durationDays > 7) {
                    showFieldError('eventEndTime', 'Sự kiện không được kéo dài quá 7 ngày');
                    return false;
                }
                
                // Check for very short events (less than 1 hour)
                if (durationHours < 1) {
                    showFieldError('eventEndTime', 'Sự kiện phải kéo dài ít nhất 1 giờ');
                    return false;
                }
            }
            
        } else if (currentStep === 2) {
            if (!selectedLocation) {
                showError('Vui lòng chọn địa điểm');
                return false;
            }
        }
        
        return true;
    }
    
    // Update navigation buttons
    function updateNavigationButtons() {
        $('#prevBtn').toggle(currentStep > 1);
        $('#nextBtn').toggle(currentStep < 3);
        $('#submitBtn').toggle(currentStep === 3);
    }
    
    // Load location suggestions based on event type
    function loadLocationSuggestions() {
        const eventTypeId = $('#eventType').val();
        if (!eventTypeId) {
            if (currentStep === 2) {
                showError('Vui lòng chọn loại sự kiện trước');
            }
            return;
        }
        
        // Find the event type name from the loaded event types
        const eventType = eventTypes.find(type => type.ID_LoaiSK == eventTypeId);
        if (!eventType) {
            if (currentStep === 2) {
                showError('Không tìm thấy thông tin loại sự kiện');
            }
            return;
        }
        
        // Only show loading if we're on step 2 (location selection)
        if (currentStep === 2) {
            $('#locationSuggestions').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải danh sách địa điểm phù hợp...</p>
                </div>
            `);
        }
        
        $.get('../../src/controllers/admin-event-register.php?action=get_locations_by_type&event_type=' + encodeURIComponent(eventTypeId), function(data) {
            if (data.success) {
                locations = data.locations;
                if (currentStep === 2) {
                    displayLocationSuggestions();
                }
            } else {
                if (currentStep === 2) {
                    $('#locationSuggestions').html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Không tìm thấy địa điểm phù hợp cho loại sự kiện này.
                        </div>
                    `);
                }
            }
        }, 'json').fail(function() {
            if (currentStep === 2) {
                $('#locationSuggestions').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Lỗi khi tải danh sách địa điểm.
                    </div>
                `);
            }
        });
    }
    
    // Display location suggestions
    function displayLocationSuggestions() {
        if (locations.length === 0) {
            $('#locationSuggestions').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Không có địa điểm phù hợp cho loại sự kiện này.
                </div>
            `);
            return;
        }
        
        let html = '';
        locations.forEach(location => {
            const price = new Intl.NumberFormat('vi-VN').format(location.GiaThue);
            html += `
                <div class="suggestion-card" onclick="selectLocation(${location.ID_DD})" data-location-id="${location.ID_DD}">
                    <div class="row">
                        <div class="col-md-8">
                            <h5>${location.TenDiaDiem}</h5>
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt"></i> ${location.DiaChi}
                            </p>
                            <p class="text-muted mb-2">
                                <i class="fas fa-users"></i> Sức chứa: ${location.SucChua} người
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-home"></i> ${location.LoaiDiaDiem}
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <h4 class="text-primary">${price} VNĐ</h4>
                            <small class="text-muted">Giá thuê</small>
                        </div>
                    </div>
                    ${location.MoTa ? `<p class="mt-2 text-muted">${location.MoTa}</p>` : ''}
                </div>
            `;
        });
        
        $('#locationSuggestions').html(html);
    }
    
    // Select location
    function selectLocation(locationId) {
        selectedLocation = locations.find(loc => loc.ID_DD === locationId);
        
        // Update UI
        $('.suggestion-card').removeClass('selected');
        $(`.suggestion-card[data-location-id="${locationId}"]`).addClass('selected');
    }
    
    // Load equipment suggestions
    function loadEquipmentSuggestions() {
        if (!selectedLocation) {
            showError('Vui lòng chọn địa điểm trước');
            return;
        }
        
        const eventType = $('#eventType').val();
        
        // Load combo suggestions
        loadComboSuggestions(eventType);
        
        // Load all available equipment
        $('#equipmentSuggestions').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Đang tải danh sách thiết bị...</p>
            </div>
        `);
        
        $.get(`../../src/controllers/admin-event-register.php?action=get_all_equipment`, function(data) {
            if (data.success) {
                equipmentSuggestions = data.equipment;
                displayEquipmentSuggestions();
            } else {
                $('#equipmentSuggestions').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Không có thiết bị nào có sẵn.
                    </div>
                `);
            }
        }, 'json').fail(function(xhr, status, error) {
            $('#equipmentSuggestions').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Lỗi khi tải danh sách thiết bị.
                </div>
            `);
        });
    }
    
    // Load combo suggestions
    function loadComboSuggestions(eventType) {
        $('#comboSuggestions').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Đang tải combo thiết bị...</p>
            </div>
        `);
        
        $.get(`../../src/controllers/admin-event-register.php?action=get_combo_suggestions&event_type=${encodeURIComponent(eventType)}`, function(data) {
            if (data.success && data.combos.length > 0) {
                comboSuggestions = data.combos;
                displayComboSuggestions();
            } else {
                $.get(`../../src/controllers/admin-event-register.php?action=get_all_combos`, function(data) {
                    if (data.success) {
                        comboSuggestions = data.combos;
                        displayComboSuggestions();
                    } else {
                        $('#comboSuggestions').html(`
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Không có combo thiết bị nào có sẵn.
                            </div>
                        `);
                    }
                }, 'json');
            }
        }, 'json').fail(function() {
            $('#comboSuggestions').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Lỗi khi tải combo thiết bị.
                </div>
            `);
        });
    }
    
    // Display combo suggestions
    function displayComboSuggestions() {
        if (comboSuggestions.length === 0) {
            $('#comboSuggestions').html(`
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Không có combo thiết bị nào có sẵn.
                </div>
            `);
            return;
        }
        
        let html = '<div class="row">';
        comboSuggestions.forEach(combo => {
            const price = new Intl.NumberFormat('vi-VN').format(combo.GiaCombo);
            const isSelected = selectedCombo && selectedCombo.ID_Combo === combo.ID_Combo;
            html += `
                <div class="col-md-6 mb-4">
                    <div class="combo-card ${isSelected ? 'selected' : ''}" onclick="selectCombo(${combo.ID_Combo})" data-combo-id="${combo.ID_Combo}">
                        <div class="combo-header">
                            <h5 class="combo-title">
                                <i class="fas fa-box text-primary"></i>
                                ${combo.TenCombo}
                            </h5>
                            <div class="combo-price">${price} VNĐ</div>
                        </div>
                        <div class="combo-description">${combo.MoTa || 'Combo thiết bị chuyên nghiệp'}</div>
                        <div class="combo-equipment">
                            <h6><i class="fas fa-list text-primary"></i> Danh sách thiết bị</h6>
                            <div class="equipment-list">
                                ${combo.equipment.map(item => `
                                    <div class="equipment-item-combo">
                                        <span class="equipment-name">${item.TenThietBi}</span>
                                        <span class="equipment-quantity">x${item.SoLuong}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        $('#comboSuggestions').html(html);
    }
    
    // Select combo
    function selectCombo(comboId) {
        selectedCombo = comboSuggestions.find(combo => combo.ID_Combo === comboId);
        
        // Update UI
        $('.combo-card').removeClass('selected');
        $(`.combo-card[data-combo-id="${comboId}"]`).addClass('selected');
        
        // Update order summary
        updateOrderSummary();
    }
    
    // Display equipment suggestions
    function displayEquipmentSuggestions() {
        if (equipmentSuggestions.length === 0) {
            $('#equipmentSuggestions').html(`
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Không có thiết bị nào có sẵn.
                </div>
            `);
            return;
        }
        
        // Group equipment by type
        const groupedEquipment = {};
        equipmentSuggestions.forEach(equipment => {
            const type = equipment.LoaiThietBi || 'Khác';
            if (!groupedEquipment[type]) {
                groupedEquipment[type] = [];
            }
            groupedEquipment[type].push(equipment);
        });
        
        let html = '';
        Object.keys(groupedEquipment).sort().forEach(type => {
            html += `
                <div class="equipment-category mb-4">
                    <h6 class="category-title">
                        <i class="fas fa-tools text-primary"></i>
                        ${type}
                    </h6>
                    <div class="row">
            `;
            
            groupedEquipment[type].forEach(equipment => {
                const price = new Intl.NumberFormat('vi-VN').format(equipment.GiaThue);
                const isSelected = selectedEquipment.some(eq => eq.ID_TB === equipment.ID_TB);
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card equipment-card h-100 ${isSelected ? 'selected' : ''}">
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input equipment-checkbox" type="checkbox" 
                                           value="${equipment.ID_TB}" id="equipment_${equipment.ID_TB}"
                                           ${isSelected ? 'checked' : ''}
                                           onchange="toggleEquipment(${equipment.ID_TB}, '${equipment.TenThietBi}', ${equipment.GiaThue})">
                                    <label class="form-check-label w-100" for="equipment_${equipment.ID_TB}">
                                        <div class="equipment-type">
                                            <i class="fas fa-cog text-primary"></i> 
                                            <strong>${equipment.TenThietBi}</strong>
                                        </div>
                                        <div class="equipment-details mt-2">
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="text-muted">Hãng:</small><br>
                                                    <span>${equipment.HangSX || 'N/A'}</span>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Trạng thái:</small><br>
                                                    <span class="badge bg-success">${equipment.TrangThai}</span>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted">Giá:</small><br>
                                                <span class="text-primary fw-bold">${price} VNĐ/${equipment.DonViTinh}</span>
                                            </div>
                                            ${equipment.MoTa ? `<div class="mt-2"><small class="text-muted">Mô tả:</small><br><small>${equipment.MoTa}</small></div>` : ''}
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
        });
        
        $('#equipmentSuggestions').html(html);
    }
    
    // Toggle equipment selection
    function toggleEquipment(equipmentId, equipmentName, price) {
        const checkbox = document.getElementById(`equipment_${equipmentId}`);
        const card = checkbox.closest('.equipment-card');
        const existingIndex = selectedEquipment.findIndex(eq => eq.ID_TB === equipmentId);
        
        if (checkbox.checked) {
            if (existingIndex === -1) {
                selectedEquipment.push({
                    ID_TB: equipmentId,
                    TenThietBi: equipmentName,
                    GiaThue: price,
                    SoLuong: 1
                });
            }
            card.classList.add('selected');
        } else {
            if (existingIndex !== -1) {
                selectedEquipment.splice(existingIndex, 1);
            }
            card.classList.remove('selected');
        }
        
        updateOrderSummary();
    }
    
    // Update order summary
    function updateOrderSummary() {
        if (!selectedLocation) {
            return;
        }
        
        const locationPriceNum = parseFloat(selectedLocation.GiaThue) || 0;
        const locationPrice = new Intl.NumberFormat('vi-VN').format(locationPriceNum);
        const eventName = $('#eventName').val();
        const eventDate = $('#eventDate').val();
        const eventTime = $('#eventTime').val();
        const eventEndDate = $('#eventEndDate').val();
        const eventEndTime = $('#eventEndTime').val();
        
        let totalPrice = locationPriceNum;
        let comboPrice = 0;
        
        // Calculate duration
        let durationText = '';
        if (eventEndDate && eventEndTime) {
            const startDateTime = new Date(eventDate + 'T' + eventTime);
            const endDateTime = new Date(eventEndDate + 'T' + eventEndTime);
            const durationHours = (endDateTime - startDateTime) / (1000 * 60 * 60);
            const durationDays = Math.floor(durationHours / 24);
            const remainingHours = Math.floor(durationHours % 24);
            
            if (durationDays > 0) {
                durationText = `${durationDays} ngày ${remainingHours} giờ`;
            } else {
                durationText = `${remainingHours} giờ`;
            }
        }
        
        let html = `
            <div class="summary-item">
                <span>Sự kiện:</span>
                <span>${eventName}</span>
            </div>
            <div class="summary-item">
                <span>Ngày bắt đầu:</span>
                <span>${formatDate(eventDate)} ${eventTime}</span>
            </div>
        `;
        
        if (eventEndDate && eventEndTime) {
            html += `
                <div class="summary-item">
                    <span>Ngày kết thúc:</span>
                    <span>${formatDate(eventEndDate)} ${eventEndTime}</span>
                </div>
                <div class="summary-item">
                    <span>Thời lượng:</span>
                    <span class="text-primary fw-bold">${durationText}</span>
                </div>
            `;
        }
        
        html += `
            <div class="summary-item">
                <span>Địa điểm:</span>
                <span>${selectedLocation.TenDiaDiem}</span>
            </div>
            <div class="summary-item">
                <span>Giá thuê địa điểm:</span>
                <span>${locationPrice} VNĐ</span>
            </div>
        `;
        
        // Add combo if selected
        if (selectedCombo) {
            comboPrice = parseFloat(selectedCombo.GiaCombo) || 0;
            const comboPriceFormatted = new Intl.NumberFormat('vi-VN').format(comboPrice);
            html += `
                <div class="summary-item">
                    <span>Combo thiết bị:</span>
                    <span>${selectedCombo.TenCombo}</span>
                </div>
                <div class="summary-item">
                    <span>Giá combo:</span>
                    <span>${comboPriceFormatted} VNĐ</span>
                </div>
            `;
            totalPrice += comboPrice;
        }
        
        // Add individual equipment if selected
        if (selectedEquipment.length > 0) {
            html += `<div class="summary-item"><span><strong>Thiết bị riêng lẻ:</strong></span></div>`;
            let equipmentTotal = 0;
            selectedEquipment.forEach(equipment => {
                const equipmentPrice = parseFloat(equipment.GiaThue) || 0;
                const equipmentPriceFormatted = new Intl.NumberFormat('vi-VN').format(equipmentPrice);
                html += `
                    <div class="summary-item" style="margin-left: 15px;">
                        <span>• ${equipment.TenThietBi} (${equipment.SoLuong} cái):</span>
                        <span>${equipmentPriceFormatted} VNĐ</span>
                    </div>
                `;
                equipmentTotal += equipmentPrice;
            });
            totalPrice += equipmentTotal;
        }
        
        const totalPriceFormatted = new Intl.NumberFormat('vi-VN').format(totalPrice);
        html += `
            <div class="summary-item">
                <span><strong>Tổng cộng:</strong></span>
                <span><strong>${totalPriceFormatted} VNĐ</strong></span>
            </div>
        `;
        
        $('#orderSummary').html(html);
    }
    
    // Format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }
    
    // Show error message
    function showError(message) {
        $('#errorMessage').html(`
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <span>${message}</span>
            </div>
        `).show();
        $('#successMessage').hide();
        
        // Scroll to error message
        $('html, body').animate({
            scrollTop: $('#errorMessage').offset().top - 100
        }, 500);
        
        setTimeout(() => {
            $('#errorMessage').hide();
        }, 8000);
    }
    
    // Show field-specific error
    function showFieldError(fieldId, message) {
        const field = $(`#${fieldId}`);
        field.addClass('is-invalid');
        
        // Remove existing error message
        field.siblings('.invalid-feedback').remove();
        
        // Add new error message
        field.after(`<div class="invalid-feedback">${message}</div>`);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            field.removeClass('is-invalid');
            field.siblings('.invalid-feedback').remove();
        }, 5000);
    }
    
    // Show success message
    function showSuccess(message) {
        $('#successMessage').html(`
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <span>${message}</span>
            </div>
        `).show();
        $('#errorMessage').hide();
        
        // Scroll to success message
        $('html, body').animate({
            scrollTop: $('#successMessage').offset().top - 100
        }, 500);
    }
    
    // Reset form to initial state
    function resetForm() {
        // Clear form data
        $('#adminEventRegistrationForm')[0].reset();
        selectedCustomer = null;
        selectedLocation = null;
        selectedEquipment = [];
        selectedCombo = null;
        currentStep = 1;
        
        // Reset UI
        $('#selectedCustomerInfo').hide();
        $('.customer-item').removeClass('selected');
        $('.suggestion-card').removeClass('selected');
        $('.combo-card').removeClass('selected');
        $('.equipment-card').removeClass('selected');
        $('.equipment-checkbox').prop('checked', false);
        
        // Reset steps
        $('.form-step').removeClass('active');
        $('#step1').addClass('active');
        $('.step').removeClass('active completed');
        $('#step1-indicator').addClass('active');
        
        // Update navigation
        updateNavigationButtons();
        
        // Clear messages
        $('#errorMessage').hide();
        $('#successMessage').hide();
    }
    
    // Form submission
    $('#adminEventRegistrationForm').on('submit', function(e) {
        e.preventDefault();
        
        
        
        // Validate all steps before submission
        if (currentStep < 3) {
            showError('Vui lòng hoàn thành tất cả các bước trước khi đăng ký');
            return;
        }
        
        if (!validateCurrentStep()) {
            console.log('Validation failed');
            return;
        }
        
        // Validate customer selection
        if (!selectedCustomer) {
            showError('❌ Vui lòng chọn khách hàng từ danh sách bên trái');
            return;
        }
        
        if (!selectedCustomer.ID_User) {
            showError('❌ Thông tin khách hàng không hợp lệ');
            return;
        }
        
        // Validate location selection
        if (!selectedLocation) {
            showError('❌ Vui lòng chọn địa điểm cho sự kiện');
            return;
        }
        
        if (!selectedLocation.ID_DD) {
            showError('❌ Thông tin địa điểm không hợp lệ');
            return;
        }
        
        // Validate required fields with specific error messages
        const requiredFields = [
            { id: 'eventName', name: 'Tên sự kiện' },
            { id: 'eventType', name: 'Loại sự kiện' },
            { id: 'eventDate', name: 'Ngày tổ chức' },
            { id: 'eventTime', name: 'Giờ bắt đầu' }
        ];
        
        for (let field of requiredFields) {
            if (!$(`#${field.id}`).val()) {
                showError(`❌ Vui lòng nhập ${field.name}`);
                $(`#${field.id}`).addClass('is-invalid');
                $(`#${field.id}`).focus();
                return;
            } else {
                $(`#${field.id}`).removeClass('is-invalid');
            }
        }
        
        
        // Validate date format
        const eventDate = $('#eventDate').val();
        const eventTime = $('#eventTime').val();
        if (eventDate && eventTime) {
            const eventDateTime = new Date(eventDate + 'T' + eventTime);
            if (isNaN(eventDateTime.getTime())) {
                showError('❌ Ngày hoặc giờ bắt đầu không hợp lệ');
                $('#eventDate, #eventTime').addClass('is-invalid');
                return;
            }
            
            // Check if date is in the past
            const now = new Date();
            if (eventDateTime < now) {
                showError('❌ Ngày và giờ bắt đầu không được là thời gian trong quá khứ');
                $('#eventDate, #eventTime').addClass('is-invalid');
                return;
            }
        }
        
        // Validate end date/time if provided
        const eventEndDate = $('#eventEndDate').val();
        const eventEndTime = $('#eventEndTime').val();
        if (eventEndDate && eventEndTime) {
            const endDateTime = new Date(eventEndDate + 'T' + eventEndTime);
            if (isNaN(endDateTime.getTime())) {
                showError('❌ Ngày hoặc giờ kết thúc không hợp lệ');
                $('#eventEndDate, #eventEndTime').addClass('is-invalid');
                return;
            }
            
            // Check if end time is after start time
            if (eventDate && eventTime) {
                const startDateTime = new Date(eventDate + 'T' + eventTime);
                if (endDateTime <= startDateTime) {
                    showError('❌ Thời gian kết thúc phải sau thời gian bắt đầu');
                    $('#eventEndDate, #eventEndTime').addClass('is-invalid');
                    return;
                }
            }
        }
        
        // Validate optional fields if provided
        const expectedGuests = $('#expectedGuests').val();
        if (expectedGuests && (isNaN(expectedGuests) || expectedGuests < 1)) {
            showError('❌ Số khách dự kiến phải là số dương');
            $('#expectedGuests').addClass('is-invalid');
            return;
        }
        
        const budget = $('#budget').val();
        if (budget && (isNaN(budget) || budget < 0)) {
            showError('❌ Ngân sách phải là số không âm');
            $('#budget').addClass('is-invalid');
            return;
        }
        
        // Clear any invalid classes from valid fields
        $('.form-control, .form-select').removeClass('is-invalid');
        
        // Show loading state
        $('#loadingSpinner').show();
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        // Hide any existing error messages
        $('#errorMessage').hide();
        $('#successMessage').hide();
        
        const formData = {
            customer_id: selectedCustomer.ID_User,
            event_name: $('#eventName').val(),
            event_type: $('#eventType').val(),
            event_date: $('#eventDate').val(),
            event_time: $('#eventTime').val(),
            event_end_date: $('#eventEndDate').val() || null,
            event_end_time: $('#eventEndTime').val() || null,
            expected_guests: $('#expectedGuests').val() || null,
            budget: $('#budget').val() || null,
            description: $('#description').val() || '',
            notes: $('#notes').val() || '',
            location_id: selectedLocation ? selectedLocation.ID_DD : null,
            equipment_ids: selectedEquipment.map(eq => eq.ID_TB),
            combo_id: selectedCombo ? selectedCombo.ID_Combo : null
        };
        
        $.ajax({
            url: '../../src/controllers/admin-event-register.php?action=register_event',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            timeout: 30000, // 30 second timeout
            xhrFields: {
                withCredentials: true
            },
            success: function(data) {
                $('#loadingSpinner').hide();
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-check"></i> Đăng ký sự kiện');
                
                if (data.success) {
                    const statusMessage = data.status ? ` (Trạng thái: ${data.status})` : '';
                    showSuccess(`✅ Đăng ký sự kiện thành công! Sự kiện đã được duyệt và tạo cho khách hàng.${statusMessage}`);
                    
                    // Reset form after success
                    setTimeout(() => {
                        resetForm();
                        
                        // Redirect to admin dashboard
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 3000);
                    }, 2000);
                } else {
                    showError('❌ Lỗi: ' + (data.error || data.message || 'Không xác định được lỗi'));
                }
            },
            error: function(xhr, status, error) {
                $('#loadingSpinner').hide();
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-check"></i> Đăng ký sự kiện');
                
                let errorMessage = '❌ Lỗi kết nối. Vui lòng thử lại.';
                
                if (xhr.status === 0) {
                    errorMessage = '❌ Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng.';
                } else if (xhr.status === 404) {
                    errorMessage = '❌ Không tìm thấy trang xử lý. Vui lòng liên hệ quản trị viên.';
                } else if (xhr.status === 500) {
                    errorMessage = '❌ Lỗi server. Vui lòng thử lại sau.';
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = '❌ ' + (response.error || errorMessage);
                    } catch (e) {
                        errorMessage = '❌ Lỗi không xác định: ' + xhr.responseText.substring(0, 200);
                    }
                }
                
                showError(errorMessage);
            }
        });
    });
</script>

<?php include 'includes/admin-footer.php'; ?>
