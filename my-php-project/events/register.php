<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký sự kiện - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header-section h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .header-section p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .form-section {
            padding: 3rem;
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
        
        .suggestion-card .form-select-sm {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
            z-index: 10;
            position: relative;
        }
        
        .suggestion-card .form-select-sm:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
        }
        
        .suggestion-card .form-select-sm:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        
        /* Location Image Styles */
        .location-image-container {
            position: relative;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .location-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .location-image-container:hover .location-image {
            transform: scale(1.05);
        }
        
        .image-overlay {
            position: absolute;
            top: 8px;
            right: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .image-overlay .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        .location-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        /* Responsive adjustments for location images */
        @media (max-width: 768px) {
            .location-image-container {
                height: 100px;
                margin-bottom: 1rem;
            }
            
            .location-title {
                font-size: 1rem;
            }
            
            /* Responsive filter layout */
            .row.mb-4 .col-md-3,
            .row.mb-4 .col-md-2 {
                margin-bottom: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            /* Stack filters vertically on very small screens */
            .row.mb-4 {
                flex-direction: column;
            }
            
            .row.mb-4 .col-md-3,
            .row.mb-4 .col-md-2 {
                width: 100%;
                max-width: 100%;
                flex: 0 0 100%;
            }
        }
        
        .equipment-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #667eea;
        }
        
        .combo-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .combo-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .combo-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }
        
        .combo-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .combo-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .combo-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .combo-description {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .combo-equipment {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .combo-equipment h6 {
            color: #495057;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .equipment-list {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .equipment-item-combo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .equipment-item-combo:last-child {
            border-bottom: none;
        }
        
        .equipment-name {
            font-weight: 500;
            color: #333;
        }
        
        .equipment-quantity {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .equipment-type {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .equipment-list {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
        
        .summary-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .summary-item:last-child {
            margin-bottom: 0;
            font-weight: 600;
            border-top: 1px solid #dee2e6;
            padding-top: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .form-section {
                padding: 2rem 1.5rem;
            }
            
            .header-section h1 {
                font-size: 2rem;
            }
            
            .step-indicator {
                flex-direction: column;
                align-items: center;
            }
            
            .step {
                margin: 0.5rem 0;
            }
            
            /* Mobile layout for event type and price */
            .d-flex.align-items-end.gap-3 {
                flex-direction: column;
                align-items: stretch !important;
                gap: 1rem !important;
            }
            
            #eventTypePrice {
                width: 100% !important;
                min-width: auto !important;
                max-width: none !important;
            }
            
            #eventTypePrice .alert {
                min-width: auto !important;
                max-width: none !important;
            }
        }
        
        /* Equipment Selection Styles */
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
        
        .equipment-category {
            margin-bottom: 2rem;
        }
        
        .category-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .equipment-type {
            font-size: 1.1rem;
            font-weight: 600;
            color: #495057;
        }
        
        .equipment-details {
            font-size: 0.9rem;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
        
        /* Combo Card Styles */
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
        
        .combo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .combo-title {
            color: #495057;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .combo-price {
            color: #667eea;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .combo-description {
            color: #6c757d;
            font-style: italic;
            margin-bottom: 15px;
        }
        
        .combo-equipment h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .equipment-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .equipment-item-combo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .equipment-item-combo:last-child {
            border-bottom: none;
        }
        
        .equipment-name {
            color: #495057;
            font-size: 0.9rem;
        }
        
        .equipment-quantity {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .combo-footer {
            margin-top: 15px;
            text-align: center;
        }
        
        .combo-footer .btn {
            border-radius: 20px;
            padding: 8px 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-container">
            <!-- Header -->
            <div class="header-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1><i class="fas fa-calendar-plus"></i> Đăng ký sự kiện</h1>
                        <p>Điền thông tin để đăng ký sự kiện của bạn</p>
                    </div>
                    <div>
                        <a href="../index.php" class="btn btn-light btn-lg">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Form Section -->
            <div class="form-section">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" id="step1-indicator">
                        <div class="step-number">1</div>
                        <div class="step-label">Thông tin cơ bản</div>
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
                
                <form id="eventRegistrationForm">
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
                                    <div class="d-flex align-items-end gap-3">
                                        <div class="flex-grow-1">
                                            <label for="eventType" class="form-label">Loại sự kiện *</label>
                                            <select class="form-select" id="eventType" name="event_type" required>
                                                <option value="">Chọn loại sự kiện</option>
                                            </select>
                                        </div>
                                        <div id="eventTypePrice" class="text-center" style="display: none;">
                                            <div class="alert alert-info mb-0 py-2 px-2" style="min-width: 160px; max-width: 180px;">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <i class="fas fa-info-circle" style="font-size: 0.8rem;"></i>
                                                    <div>
                                                        <div class="fw-bold text-primary" style="font-size: 0.75rem; line-height: 1.2;">Giá cơ bản</div>
                                                        <div class="fw-bold" style="font-size: 0.9rem; line-height: 1.2;" id="eventTypePriceValue">0 VNĐ</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eventDate" class="form-label">Ngày bắt đầu *</label>
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
                                    <label for="eventEndDate" class="form-label">Ngày kết thúc *</label>
                                    <input type="date" class="form-control" id="eventEndDate" name="event_end_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="eventEndTime" class="form-label">Giờ kết thúc *</label>
                                    <input type="time" class="form-control" id="eventEndTime" name="event_end_time" required>
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
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Mô tả chi tiết về sự kiện của bạn..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Step 2: Location Selection -->
                    <div class="form-step" id="step2">
                        <h3 class="mb-4"><i class="fas fa-map-marker-alt text-primary"></i> Chọn địa điểm</h3>
                        
                        <!-- Search and Filter Section -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="locationSearch" class="form-label">
                                        <i class="fas fa-search"></i> Tìm kiếm địa điểm
                                    </label>
                                    <input type="text" class="form-control" id="locationSearch" placeholder="Nhập tên địa điểm...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="locationTypeFilter" class="form-label">
                                        <i class="fas fa-filter"></i> Loại địa điểm
                                    </label>
                                    <select class="form-select" id="locationTypeFilter">
                                        <option value="">Tất cả loại</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="priceTypeFilter" class="form-label">
                                        <i class="fas fa-clock"></i> Loại giá
                                    </label>
                                    <select class="form-select" id="priceTypeFilter">
                                        <option value="">Tất cả loại giá</option>
                                        <option value="hour">Theo giờ</option>
                                        <option value="day">Theo ngày</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="priceRangeFilter" class="form-label">
                                        <i class="fas fa-dollar-sign"></i> Khoảng giá
                                    </label>
                                    <select class="form-select" id="priceRangeFilter">
                                        <option value="">Tất cả giá</option>
                                        <option value="0-1000000">Dưới 1 triệu</option>
                                        <option value="1000000-5000000">1 - 5 triệu</option>
                                        <option value="5000000-10000000">5 - 10 triệu</option>
                                        <option value="10000000-20000000">10 - 20 triệu</option>
                                        <option value="20000000-50000000">20 - 50 triệu</option>
                                        <option value="50000000-100000000">50 - 100 triệu</option>
                                        <option value="100000000-999999999">Trên 100 triệu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="clearLocationFilters()">
                                        <i class="fas fa-times"></i> Xóa lọc
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Suggested Locations -->
                        <div class="mb-4">
                            <h5><i class="fas fa-star text-warning"></i> Địa điểm đề xuất</h5>
                            <div id="suggestedLocations">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Đang tải địa điểm đề xuất...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- All Locations -->
                        <div>
                            <h5><i class="fas fa-list text-primary"></i> Tất cả địa điểm</h5>
                            <div id="allLocations">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Đang tải danh sách địa điểm...</p>
                                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentStep = 1;
        let selectedLocation = null;
        let selectedEquipment = [];
        let selectedCombo = null;
        let eventTypes = [];
        let locations = [];
        let allLocations = [];
        let suggestedLocations = [];
        let equipmentSuggestions = [];
        let comboSuggestions = [];
        let locationTypes = [];
        
        // Initialize the form
        $(document).ready(function() {
            loadEventTypes();
            setMinDate();
            
            // Check if we're editing an existing event
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            if (editId) {
                loadEventForEdit(editId);
            }
        });
        
        // Set minimum date to today
        function setMinDate() {
            const today = new Date().toISOString().split('T')[0];
            $('#eventDate').attr('min', today);
            $('#eventEndDate').attr('min', today);
        }
        
        // Helper function to check if event start time is at least 12 hours from now
        function checkMinimum12Hours(eventDate, eventTime) {
            if (!eventDate || !eventTime) return { valid: true };
            
            const eventStartDateTime = new Date(eventDate + 'T' + eventTime);
            const now = new Date();
            const minDateTime = new Date(now.getTime() + (12 * 60 * 60 * 1000)); // Add 12 hours
            
            if (eventStartDateTime < minDateTime) {
                const hoursLeft = Math.ceil((eventStartDateTime - now) / (1000 * 60 * 60));
                return {
                    valid: false,
                    hoursLeft: hoursLeft,
                    minDateTime: minDateTime
                };
            }
            
            return { valid: true };
        }
        
        // Auto-set end date when start date changes
        $('#eventDate').on('change', function() {
            const startDate = $(this).val();
            const startTime = $('#eventTime').val();
            
            if (startDate) {
                $('#eventEndDate').attr('min', startDate);
                // If end date is before start date, set it to start date
                const endDate = $('#eventEndDate').val();
                if (endDate && endDate < startDate) {
                    $('#eventEndDate').val(startDate);
                }
                
                // Check 12-hour minimum
                if (startTime) {
                    const checkResult = checkMinimum12Hours(startDate, startTime);
                    if (!checkResult.valid) {
                        showError(`Sự kiện phải được đăng ký trước ít nhất 12 giờ. Thời gian bắt đầu hiện tại chỉ còn ${checkResult.hoursLeft} giờ. Vui lòng chọn thời gian muộn hơn.`);
                    }
                }
            }
        });
        
        // Check 12-hour minimum when start time changes
        $('#eventTime').on('change', function() {
            const startDate = $('#eventDate').val();
            const startTime = $(this).val();
            
            if (startDate && startTime) {
                const checkResult = checkMinimum12Hours(startDate, startTime);
                if (!checkResult.valid) {
                    showError(`Sự kiện phải được đăng ký trước ít nhất 12 giờ. Thời gian bắt đầu hiện tại chỉ còn ${checkResult.hoursLeft} giờ. Vui lòng chọn thời gian muộn hơn.`);
                    $(this).focus();
                }
            }
        });
        
        // Validate end date when it changes
        $('#eventEndDate').on('change', function() {
            const startDate = $('#eventDate').val();
            const endDate = $(this).val();
            
            if (startDate && endDate && endDate < startDate) {
                showError('Ngày kết thúc không được trước ngày bắt đầu');
                $(this).focus();
            }
        });
        
        // Validate end time when it changes (if same date)
        $('#eventEndTime').on('change', function() {
            const startDate = $('#eventDate').val();
            const endDate = $('#eventEndDate').val();
            const startTime = $('#eventTime').val();
            const endTime = $(this).val();
            
            if (startDate === endDate && startTime && endTime && endTime <= startTime) {
                showError('Giờ kết thúc phải sau giờ bắt đầu khi cùng ngày');
                $(this).focus();
            }
            
            // Check if event end time is in the past
            if (endDate && endTime) {
                const eventEndDateTime = new Date(endDate + 'T' + endTime);
                const now = new Date();
                
                if (eventEndDateTime < now) {
                    showError('Cảnh báo: Thời gian kết thúc sự kiện đã qua. Bạn không thể đăng ký sự kiện với thời gian trong quá khứ.');
                    $(this).focus();
                }
            }
        });
        
        // Load event data for editing
        function loadEventForEdit(eventId) {
            // First load event types, then load event data
            $.get('../src/controllers/event-types.php?action=get_public', function(typesData) {
                if (typesData.success) {
                    eventTypes = typesData.event_types;
                    const select = $('#eventType');
                    select.empty().append('<option value="">Chọn loại sự kiện</option>');
                    eventTypes.forEach(type => {
                        select.append(`<option value="${type.ID_LoaiSK}" data-price="${type.GiaCoBan}">${type.TenLoai}</option>`);
                    });
                    
                    // Add event listener for event type change
                    $('#eventType').on('change', function() {
                        const selectedOption = $(this).find('option:selected');
                        const price = selectedOption.data('price');
                        
                        if (price && price > 0) {
                            const formattedPrice = new Intl.NumberFormat('vi-VN').format(price);
                            $('#eventTypePriceValue').text(formattedPrice);
                            $('#eventTypePrice').show();
                        } else {
                            $('#eventTypePrice').hide();
                        }
                        
                        // Update order summary if we're on step 3
                        if (currentStep === 3) {
                            updateOrderSummary();
                        }
                    });
                    
                    // Now load event data
                    $.get(`../src/controllers/event-register.php?action=get_event_for_edit&event_id=${eventId}`, function(data) {
                        if (data.success) {
                            const event = data.event;
                            
                            // Fill form fields
                            $('#eventName').val(event.TenSuKien);
                            $('#description').val(event.MoTa);
                            $('#eventDate').val(event.NgayBatDau.split(' ')[0]);
                            $('#eventTime').val(event.NgayBatDau.split(' ')[1]);
                            $('#eventEndDate').val(event.NgayKetThuc.split(' ')[0]);
                            $('#eventEndTime').val(event.NgayKetThuc.split(' ')[1]);
                            $('#expectedGuests').val(event.SoNguoiDuKien);
                            $('#budget').val(event.NganSach);
                            $('#eventType').val(event.ID_LoaiSK);
                            
                            // Show event type price
                            const selectedEventTypeOption = $('#eventType option:selected');
                            const price = selectedEventTypeOption.data('price');
                            if (price && price > 0) {
                                const formattedPrice = new Intl.NumberFormat('vi-VN').format(price);
                                $('#eventTypePriceValue').text(formattedPrice);
                                $('#eventTypePrice').show();
                            }
                            
                            // Update header to show edit mode
                            $('.header-section h1').text('Chỉnh sửa sự kiện');
                            $('.header-section p').text('Cập nhật thông tin sự kiện của bạn');
                            $('#submitBtn').html('<i class="fas fa-save"></i> Cập nhật sự kiện');
                            
                            // Load location and equipment data
                            loadLocationSuggestions();
                            loadEquipmentSuggestions();
                            loadComboSuggestions();
                            
                            // Load selected location and equipment after a short delay
                            setTimeout(() => {
                                loadSelectedData(eventId);
                            }, 1000);
                            
                        } else {
                            alert('Lỗi khi tải dữ liệu sự kiện: ' + data.message);
                            window.location.href = 'my-events.php';
                        }
                    }, 'json').fail(function() {
                        alert('Lỗi khi tải dữ liệu sự kiện');
                        window.location.href = 'my-events.php';
                    });
                } else {
                    alert('Lỗi khi tải loại sự kiện: ' + typesData.error);
                }
            }, 'json').fail(function() {
                alert('Lỗi kết nối khi tải loại sự kiện');
            });
        }
        
        // Load selected data for editing
        function loadSelectedData(eventId) {
            $.get(`../src/controllers/event-register.php?action=get_event_selected_data&event_id=${eventId}`, function(data) {
                if (data.success) {
                    console.log('Loaded selected data:', data);
                    
                    // Set selected location
                    if (data.location) {
                        selectedLocation = data.location;
                        // If location has "Cả hai" rental type, use the applied rental type from database
                        if (selectedLocation.LoaiThue === 'Cả hai') {
                            // Use LoaiThueApDung from database if available, otherwise default to hour
                            if (selectedLocation.LoaiThueApDung) {
                                selectedLocation.selectedRentalType = selectedLocation.LoaiThueApDung === 'Theo giờ' ? 'hour' : 'day';
                                console.log('Using LoaiThueApDung from database:', selectedLocation.LoaiThueApDung, '->', selectedLocation.selectedRentalType);
                            } else {
                                selectedLocation.selectedRentalType = 'hour'; // Default fallback
                                console.log('No LoaiThueApDung found, using default hour');
                            }
                        }
                        console.log('Set selected location:', selectedLocation);
                        displaySuggestedLocations();
                        displayAllLocations();
                    }
                    
                    // Set selected equipment
                    if (data.equipment && data.equipment.length > 0) {
                        selectedEquipment = data.equipment;
                        console.log('Set selected equipment:', selectedEquipment);
                        displayEquipmentSuggestions();
                    }
                    
                    // Set selected combo
                    if (data.combo) {
                        selectedCombo = data.combo;
                        console.log('Set selected combo:', selectedCombo);
                        displayComboSuggestions();
                    }
                    
                    // Update order summary after a short delay
                    setTimeout(() => {
                        console.log('Force updating order summary after loading selected data');
                        updateOrderSummary();
                    }, 500);
                    
                    // Also try to update immediately
                    console.log('Immediate update order summary');
                    updateOrderSummary();
                }
            }, 'json').fail(function() {
                console.log('Failed to load selected data');
            });
        }
        
        // Force update order summary (for edit mode)
        function forceUpdateOrderSummary() {
            console.log('Force updating order summary');
            if (selectedLocation) {
                updateOrderSummary();
            } else {
                console.log('No selected location, cannot force update');
            }
        }
        
        // Load event types
        function loadEventTypes() {
            $.get('../src/controllers/event-types.php?action=get_public', function(data) {
                if (data.success) {
                    eventTypes = data.event_types;
                    const select = $('#eventType');
                    select.empty().append('<option value="">Chọn loại sự kiện</option>');
                    eventTypes.forEach(type => {
                        select.append(`<option value="${type.ID_LoaiSK}" data-price="${type.GiaCoBan}">${type.TenLoai}</option>`);
                    });
                    
                    // Add event listener for event type change
                    $('#eventType').on('change', function() {
                        const selectedOption = $(this).find('option:selected');
                        const price = selectedOption.data('price');
                        
                        if (price && price > 0) {
                            const formattedPrice = new Intl.NumberFormat('vi-VN').format(price);
                            $('#eventTypePriceValue').text(formattedPrice);
                            $('#eventTypePrice').show();
                        } else {
                            $('#eventTypePrice').hide();
                        }
                        
                        // Update order summary if we're on step 3
                        if (currentStep === 3) {
                            updateOrderSummary();
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
                const requiredFields = ['eventName', 'eventType', 'eventDate', 'eventTime', 'eventEndDate', 'eventEndTime'];
                for (let field of requiredFields) {
                    if (!$(`#${field}`).val()) {
                        showError(`Vui lòng điền đầy đủ thông tin bắt buộc`);
                        $(`#${field}`).focus();
                        return false;
                    }
                }
                
                // Validate dates
                const eventDate = $('#eventDate').val();
                const eventEndDate = $('#eventEndDate').val();
                const eventTime = $('#eventTime').val();
                const eventEndTime = $('#eventEndTime').val();
                
                const eventStartDateObj = new Date(eventDate);
                const eventEndDateObj = new Date(eventEndDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (eventStartDateObj < today) {
                    showError('Ngày bắt đầu không được là ngày trong quá khứ');
                    return false;
                }
                
                if (eventEndDateObj < eventStartDateObj) {
                    showError('Ngày kết thúc không được trước ngày bắt đầu');
                    return false;
                }
                
                // Check if event start time is at least 12 hours from now
                const checkResult = checkMinimum12Hours(eventDate, eventTime);
                if (!checkResult.valid) {
                    const minDateTimeStr = checkResult.minDateTime.toLocaleString('vi-VN', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    showError(`Sự kiện phải được đăng ký trước ít nhất 12 giờ. Thời gian bắt đầu bạn chọn chỉ còn ${checkResult.hoursLeft} giờ nữa. Vui lòng chọn thời gian sau ${minDateTimeStr}.`);
                    $('#eventDate').focus();
                    return false;
                }
                
                // Validate time if same date
                if (eventStartDateObj.getTime() === eventEndDateObj.getTime()) {
                    if (eventTime >= eventEndTime) {
                        showError('Giờ kết thúc phải sau giờ bắt đầu khi cùng ngày');
                        return false;
                    }
                }
                
                // Check if event end time is in the past
                const eventEndDateTime = new Date(eventEndDate);
                eventEndDateTime.setHours(parseInt(eventEndTime.split(':')[0]), parseInt(eventEndTime.split(':')[1]), 0, 0);
                const now = new Date();
                
                if (eventEndDateTime < now) {
                    showError('Cảnh báo: Thời gian kết thúc sự kiện đã qua. Bạn không thể đăng ký sự kiện với thời gian trong quá khứ. Vui lòng chọn thời gian trong tương lai.');
                    $('#eventEndDate').focus();
                    return false;
                }
            } else if (currentStep === 2) {
                if (!selectedLocation) {
                    showError('Vui lòng chọn địa điểm');
                    return false;
                }
                
                // Validate rental type selection for locations with "Cả hai" option
                if (selectedLocation.LoaiThue === 'Cả hai' && !selectedLocation.selectedRentalType) {
                    showError('Vui lòng chọn loại thuê (theo giờ hoặc theo ngày) cho địa điểm này');
                    return false;
                }
            } else if (currentStep === 3) {
                // Validate step 3 - equipment selection is optional
                // No specific validation needed for step 3
                console.log('Step 3 validation passed');
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
                showError('Vui lòng chọn loại sự kiện trước');
                return;
            }
            
            // Find the event type name from the loaded event types
            const eventType = eventTypes.find(type => type.ID_LoaiSK == eventTypeId);
            if (!eventType) {
                showError('Không tìm thấy thông tin loại sự kiện');
                return;
            }
            
            // Load suggested locations based on event type
            $('#suggestedLocations').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải địa điểm đề xuất...</p>
                </div>
            `);
            
            // Load all locations
            $('#allLocations').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải tất cả địa điểm...</p>
                </div>
            `);
            
            // Load suggested locations
            $.get('../src/controllers/event-register.php?action=get_locations_by_type&event_type=' + encodeURIComponent(eventType.TenLoai), function(data) {
                if (data.success) {
                    suggestedLocations = data.locations;
                    displaySuggestedLocations();
                } else {
                    $('#suggestedLocations').html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Không tìm thấy địa điểm đề xuất cho loại sự kiện này.
                        </div>
                    `);
                }
            }, 'json').fail(function() {
                $('#suggestedLocations').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Lỗi khi tải địa điểm đề xuất.
                    </div>
                `);
            });
            
            // Load all locations
            $.get('../src/controllers/event-register.php?action=get_all_locations', function(data) {
                if (data.success) {
                    allLocations = data.locations;
                    locationTypes = [...new Set(data.locations.map(loc => loc.LoaiDiaDiem))].filter(type => type);
                    loadLocationTypeFilter();
                    displayAllLocations();
                    setupLocationFilters();
                } else {
                    $('#allLocations').html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Không có địa điểm nào trong hệ thống.
                        </div>
                    `);
                }
            }, 'json').fail(function() {
                $('#allLocations').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Lỗi khi tải danh sách địa điểm.
                    </div>
                `);
            });
        }
        
        // Helper function to get location price text
        function getLocationPriceText(location, selectedType = null) {
            if (!location) return 'Chưa có giá';
            
            // If a specific type is selected, show only that price
            if (selectedType === 'hour' && location.GiaThueGio && location.GiaThueGio > 0) {
                return `${new Intl.NumberFormat('vi-VN').format(location.GiaThueGio)}/giờ`;
            }
            if (selectedType === 'day' && location.GiaThueNgay && location.GiaThueNgay > 0) {
                return `${new Intl.NumberFormat('vi-VN').format(location.GiaThueNgay)}/ngày`;
            }
            
            // Default: show all available prices
            const prices = [];
            if (location.GiaThueGio && location.GiaThueGio > 0) {
                prices.push(`${new Intl.NumberFormat('vi-VN').format(location.GiaThueGio)}/giờ`);
            }
            if (location.GiaThueNgay && location.GiaThueNgay > 0) {
                prices.push(`${new Intl.NumberFormat('vi-VN').format(location.GiaThueNgay)}/ngày`);
            }
            
            if (prices.length === 0) return 'Chưa có giá';
            if (prices.length === 1) return prices[0];
            return prices.join(' | ');
        }
        
        // Update location price display when user selects rental type
        function updateLocationPrice(locationId, rentalType, section) {
            console.log('updateLocationPrice called:', {locationId, rentalType, section});
            
            const location = allLocations.find(loc => loc.ID_DD === locationId);
            if (!location) {
                console.log('Location not found:', locationId);
                return;
            }
            
            console.log('Found location:', location);
            
            // Update the price display immediately
            const priceText = getLocationPriceText(location, rentalType);
            const priceElementId = section === 'suggested' ? `price-suggested-${locationId}` : `price-all-${locationId}`;
            $(`#${priceElementId}`).text(priceText);
            
            console.log('Updated price element:', priceElementId, 'with text:', priceText);
            
            // Update selected location data if this location is currently selected
            if (selectedLocation && selectedLocation.ID_DD === locationId) {
                console.log('Updating selectedLocation.selectedRentalType from', selectedLocation.selectedRentalType, 'to', rentalType);
                selectedLocation.selectedRentalType = rentalType;
                console.log('Updated selectedLocation:', selectedLocation);
                
                // Also update the location in allLocations array to persist the selection
                const locationInAllLocations = allLocations.find(loc => loc.ID_DD === locationId);
                if (locationInAllLocations) {
                    locationInAllLocations.selectedRentalType = rentalType;
                    console.log('Updated location in allLocations:', locationInAllLocations);
                }
                
                // Update all dropdowns for this location to show the same selection
                $(`.suggestion-card[data-location-id="${locationId}"] select`).val(rentalType);
                
                // Update order summary with new price
                updateOrderSummary();
            } else {
                console.log('Location not currently selected or no selectedLocation');
                
                // Even if not selected, store the preference for future reference
                const locationInAllLocations = allLocations.find(loc => loc.ID_DD === locationId);
                if (locationInAllLocations) {
                    locationInAllLocations.selectedRentalType = rentalType;
                    console.log('Stored preference for non-selected location:', locationInAllLocations);
                }
            }
        }
        
        // Calculate total price for form submission
        function calculateTotalPrice() {
            let totalPrice = 0;
            
            // Get event type price
            const selectedEventTypeOption = $('#eventType option:selected');
            const eventTypePrice = parseFloat(selectedEventTypeOption.data('price')) || 0;
            totalPrice += eventTypePrice;
            
            console.log('calculateTotalPrice - Event type price:', eventTypePrice);
            console.log('calculateTotalPrice - Selected location:', selectedLocation);
            
            // Calculate location price based on rental type and duration
            if (selectedLocation) {
                const eventDate = $('#eventDate').val();
                const eventTime = $('#eventTime').val();
                const eventEndDate = $('#eventEndDate').val();
                const eventEndTime = $('#eventEndTime').val();
                
                console.log('calculateTotalPrice - Event dates:', {eventDate, eventTime, eventEndDate, eventEndTime});
                
                if (eventDate && eventTime && eventEndDate && eventEndTime) {
                    const startDate = new Date(eventDate + ' ' + eventTime);
                    const endDate = new Date(eventEndDate + ' ' + eventEndTime);
                    const durationMs = endDate - startDate;
                    const durationHours = Math.ceil(durationMs / (1000 * 60 * 60));
                    const durationDays = Math.ceil(durationMs / (1000 * 60 * 60 * 24));
                    
                    console.log('calculateTotalPrice - Duration:', {durationHours, durationDays});
                    console.log('calculateTotalPrice - Location rental type:', selectedLocation.LoaiThue);
                    console.log('calculateTotalPrice - Location prices:', {GiaThueGio: selectedLocation.GiaThueGio, GiaThueNgay: selectedLocation.GiaThueNgay});
                    
                    // Calculate location price based on rental type
                    if (selectedLocation.LoaiThue === 'Theo giờ' && selectedLocation.GiaThueGio) {
                        const locationPrice = durationHours * parseFloat(selectedLocation.GiaThueGio);
                        totalPrice += locationPrice;
                        console.log('calculateTotalPrice - Hourly price added:', locationPrice);
                    } else if (selectedLocation.LoaiThue === 'Theo ngày' && selectedLocation.GiaThueNgay) {
                        const locationPrice = durationDays * parseFloat(selectedLocation.GiaThueNgay);
                        totalPrice += locationPrice;
                        console.log('calculateTotalPrice - Daily price added:', locationPrice);
                    } else if (selectedLocation.LoaiThue === 'Cả hai') {
                        // Check if user has selected a specific rental type
                        if (selectedLocation.selectedRentalType === 'hour' && selectedLocation.GiaThueGio) {
                            const locationPrice = durationHours * parseFloat(selectedLocation.GiaThueGio);
                            totalPrice += locationPrice;
                            console.log('calculateTotalPrice - Selected hourly price added:', locationPrice);
                        } else if (selectedLocation.selectedRentalType === 'day' && selectedLocation.GiaThueNgay) {
                            const locationPrice = durationDays * parseFloat(selectedLocation.GiaThueNgay);
                            totalPrice += locationPrice;
                            console.log('calculateTotalPrice - Selected daily price added:', locationPrice);
                        } else {
                            // Default: Use the cheaper option
                            const hourlyPrice = durationHours * parseFloat(selectedLocation.GiaThueGio || 0);
                            const dailyPrice = durationDays * parseFloat(selectedLocation.GiaThueNgay || 0);
                            
                            console.log('calculateTotalPrice - Both prices calculated:', {hourlyPrice, dailyPrice});
                            
                            if (hourlyPrice > 0 && dailyPrice > 0) {
                                // Don't add to total until user chooses rental type
                                console.log('calculateTotalPrice - Both prices available, waiting for user choice:', {hourlyPrice, dailyPrice});
                            } else if (hourlyPrice > 0) {
                                totalPrice += hourlyPrice;
                                console.log('calculateTotalPrice - Only hourly available:', hourlyPrice);
                            } else if (dailyPrice > 0) {
                                totalPrice += dailyPrice;
                                console.log('calculateTotalPrice - Only daily available:', dailyPrice);
                            }
                        }
                    }
                }
            }
            
            // Add combo price if selected
            if (selectedCombo) {
                const comboPrice = parseFloat(selectedCombo.GiaCombo) || 0;
                totalPrice += comboPrice;
                console.log('calculateTotalPrice - Combo price added:', comboPrice);
            }
            
            // Add individual equipment prices
            if (selectedEquipment.length > 0) {
                let equipmentTotal = 0;
                selectedEquipment.forEach(equipment => {
                    const equipmentPrice = parseFloat(equipment.GiaThue) || 0;
                    equipmentTotal += equipmentPrice;
                });
                totalPrice += equipmentTotal;
                console.log('calculateTotalPrice - Equipment total added:', equipmentTotal);
            }
            
            console.log('calculateTotalPrice - Final total price:', totalPrice);
            return totalPrice;
        }
        function displaySuggestedLocations() {
            if (suggestedLocations.length === 0) {
                $('#suggestedLocations').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Không có địa điểm đề xuất cho loại sự kiện này.
                    </div>
                `);
                return;
            }
            
            let html = '';
            suggestedLocations.forEach(location => {
                // Determine which rental type to show based on selected location
                let selectedRentalType = null;
                if (selectedLocation && selectedLocation.ID_DD === location.ID_DD) {
                    // Use the stored selection for the currently selected location
                    selectedRentalType = selectedLocation.selectedRentalType || 'hour';
                } else if (location.LoaiThue === 'Cả hai') {
                    // For non-selected locations with "Cả hai", check if we have a stored preference
                    // First check if this location was previously selected and has a stored preference
                    const storedLocation = allLocations.find(loc => loc.ID_DD === location.ID_DD);
                    if (storedLocation && storedLocation.selectedRentalType) {
                        selectedRentalType = storedLocation.selectedRentalType;
                    } else {
                        selectedRentalType = 'hour'; // Default to hourly for 'Cả hai' locations
                    }
                }
                const priceText = getLocationPriceText(location, selectedRentalType);
                const isSelected = selectedLocation && selectedLocation.ID_DD === location.ID_DD;
                const imagePath = location.HinhAnh ? `../img/diadiem/${location.HinhAnh}` : '../img/diadiem/default.php';
                html += `
                    <div class="suggestion-card ${isSelected ? 'selected' : ''}" onclick="selectLocation(${location.ID_DD})" data-location-id="${location.ID_DD}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="location-image-container">
                                    <img src="${imagePath}" alt="${location.TenDiaDiem}" class="location-image" 
                                         onerror="this.src='../img/diadiem/default.php'">
                                    <div class="image-overlay">
                                        <i class="fas fa-star text-warning"></i>
                                        <span class="badge bg-warning text-dark">Đề xuất</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h5 class="location-title">
                                    ${location.TenDiaDiem}
                                </h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-map-marker-alt"></i> ${location.DiaChi}
                                </p>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-users"></i> Sức chứa: ${location.SucChua} người
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-home"></i> ${location.LoaiDiaDiem}
                                    <span class="badge bg-info ms-2">${location.LoaiThue || 'Cả hai'}</span>
                                </p>
                                ${location.MoTa ? `<p class="mt-2 text-muted small">${location.MoTa}</p>` : ''}
                            </div>
                            <div class="col-md-4 text-end">
                                <h5 class="text-primary" id="price-suggested-${location.ID_DD}">${priceText}</h5>
                                <small class="text-muted">Giá thuê</small>
                                ${location.LoaiThue === 'Cả hai' ? `
                                    <div class="mt-2">
                                        <select class="form-select form-select-sm" 
                                                onchange="updateLocationPrice(${location.ID_DD}, this.value, 'suggested')" 
                                                style="min-width: 120px;"
                                                data-location-id="${location.ID_DD}">
                                            <option value="hour" ${selectedRentalType === 'hour' ? 'selected' : ''}>Theo giờ</option>
                                            <option value="day" ${selectedRentalType === 'day' ? 'selected' : ''}>Theo ngày</option>
                                        </select>
                                        <small class="text-muted d-block mt-1">Chọn loại thuê</small>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#suggestedLocations').html(html);
            
            // Debug: Check if dropdowns are rendered
            console.log('Suggested locations rendered. Dropdowns found:', $('.suggestion-card .form-select-sm').length);
            
            // Update dropdown values for selected location
            if (selectedLocation && selectedLocation.LoaiThue === 'Cả hai') {
                $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"] select`).val(selectedLocation.selectedRentalType || 'hour');
            }
        }
        
        // Display all locations
        function displayAllLocations(filteredLocations = null) {
            const locationsToShow = filteredLocations || allLocations;
            
            if (locationsToShow.length === 0) {
                $('#allLocations').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Không tìm thấy địa điểm nào phù hợp với bộ lọc.
                    </div>
                `);
                return;
            }
            
            let html = '';
            locationsToShow.forEach(location => {
                // Determine which rental type to show based on selected location
                let selectedRentalType = null;
                if (selectedLocation && selectedLocation.ID_DD === location.ID_DD) {
                    // Use the stored selection for the currently selected location
                    selectedRentalType = selectedLocation.selectedRentalType || 'hour';
                } else if (location.LoaiThue === 'Cả hai') {
                    // For non-selected locations with "Cả hai", check if we have a stored preference
                    // First check if this location was previously selected and has a stored preference
                    const storedLocation = allLocations.find(loc => loc.ID_DD === location.ID_DD);
                    if (storedLocation && storedLocation.selectedRentalType) {
                        selectedRentalType = storedLocation.selectedRentalType;
                    } else {
                        selectedRentalType = 'hour'; // Default to hourly for 'Cả hai' locations
                    }
                }
                const priceText = getLocationPriceText(location, selectedRentalType);
                const isSelected = selectedLocation && selectedLocation.ID_DD === location.ID_DD;
                const imagePath = location.HinhAnh ? `../img/diadiem/${location.HinhAnh}` : '../img/diadiem/default.php';
                html += `
                    <div class="suggestion-card ${isSelected ? 'selected' : ''}" onclick="selectLocation(${location.ID_DD})" data-location-id="${location.ID_DD}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="location-image-container">
                                    <img src="${imagePath}" alt="${location.TenDiaDiem}" class="location-image" 
                                         onerror="this.src='../img/diadiem/default.php'">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h5 class="location-title">${location.TenDiaDiem}</h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-map-marker-alt"></i> ${location.DiaChi}
                                </p>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-users"></i> Sức chứa: ${location.SucChua} người
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-home"></i> ${location.LoaiDiaDiem}
                                    <span class="badge bg-info ms-2">${location.LoaiThue || 'Cả hai'}</span>
                                </p>
                                ${location.MoTa ? `<p class="mt-2 text-muted small">${location.MoTa}</p>` : ''}
                            </div>
                            <div class="col-md-4 text-end">
                                <h5 class="text-primary" id="price-all-${location.ID_DD}">${priceText}</h5>
                                <small class="text-muted">Giá thuê</small>
                                ${location.LoaiThue === 'Cả hai' ? `
                                    <div class="mt-2">
                                        <select class="form-select form-select-sm" 
                                                onchange="updateLocationPrice(${location.ID_DD}, this.value, 'all')" 
                                                style="min-width: 120px;"
                                                data-location-id="${location.ID_DD}">
                                            <option value="hour" ${selectedRentalType === 'hour' ? 'selected' : ''}>Theo giờ</option>
                                            <option value="day" ${selectedRentalType === 'day' ? 'selected' : ''}>Theo ngày</option>
                                        </select>
                                        <small class="text-muted d-block mt-1">Chọn loại thuê</small>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#allLocations').html(html);
            
            // Debug: Check if dropdowns are rendered
            console.log('All locations rendered. Dropdowns found:', $('.suggestion-card .form-select-sm').length);
            
            // Update dropdown values for selected location
            if (selectedLocation && selectedLocation.LoaiThue === 'Cả hai') {
                $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"] select`).val(selectedLocation.selectedRentalType || 'hour');
            }
        }
        
        // Load location type filter options
        function loadLocationTypeFilter() {
            const select = $('#locationTypeFilter');
            select.empty().append('<option value="">Tất cả loại</option>');
            locationTypes.forEach(type => {
                select.append(`<option value="${type}">${type}</option>`);
            });
        }
        
        // Setup location filters
        function setupLocationFilters() {
            // Search filter
            $('#locationSearch').on('input', function() {
                filterLocations();
            });
            
            // Type filter
            $('#locationTypeFilter').on('change', function() {
                filterLocations();
            });
            
            // Price type filter
            $('#priceTypeFilter').on('change', function() {
                filterLocations();
            });
            
            // Price range filter
            $('#priceRangeFilter').on('change', function() {
                filterLocations();
            });
        }
        
        // Filter locations based on search and filters
        function filterLocations() {
            const searchTerm = $('#locationSearch').val().toLowerCase();
            const selectedType = $('#locationTypeFilter').val();
            const priceType = $('#priceTypeFilter').val();
            const priceRange = $('#priceRangeFilter').val();
            
            let filtered = allLocations.filter(location => {
                // Search filter
                const matchesSearch = !searchTerm || 
                    location.TenDiaDiem.toLowerCase().includes(searchTerm) ||
                    location.DiaChi.toLowerCase().includes(searchTerm) ||
                    (location.MoTa && location.MoTa.toLowerCase().includes(searchTerm));
                
                // Type filter
                const matchesType = !selectedType || location.LoaiDiaDiem === selectedType;
                
                // Price type filter
                let matchesPriceType = true;
                if (priceType) {
                    if (priceType === 'hour') {
                        matchesPriceType = location.LoaiThue === 'Theo giờ' || location.LoaiThue === 'Cả hai';
                    } else if (priceType === 'day') {
                        matchesPriceType = location.LoaiThue === 'Theo ngày' || location.LoaiThue === 'Cả hai';
                    }
                }
                
                // Price range filter
                let matchesPrice = true;
                if (priceRange) {
                    const [minPrice, maxPrice] = priceRange.split('-').map(Number);
                    
                    // Determine which price to check based on price type filter
                    let locationPrice = 0;
                    if (priceType === 'hour' && location.GiaThueGio) {
                        locationPrice = parseFloat(location.GiaThueGio);
                    } else if (priceType === 'day' && location.GiaThueNgay) {
                        locationPrice = parseFloat(location.GiaThueNgay);
                    } else {
                        // If no specific price type selected, check both prices
                        const hourlyPrice = parseFloat(location.GiaThueGio) || 0;
                        const dailyPrice = parseFloat(location.GiaThueNgay) || 0;
                        locationPrice = Math.max(hourlyPrice, dailyPrice);
                    }
                    
                    matchesPrice = locationPrice >= minPrice && locationPrice <= maxPrice;
                }
                
                return matchesSearch && matchesType && matchesPriceType && matchesPrice;
            });
            
            displayAllLocations(filtered);
        }
        
        // Clear all location filters
        function clearLocationFilters() {
            $('#locationSearch').val('');
            $('#locationTypeFilter').val('');
            $('#priceTypeFilter').val('');
            $('#priceRangeFilter').val('');
            displayAllLocations();
        }
        
        // Select location
        function selectLocation(locationId) {
            // Find location from both suggested and all locations
            selectedLocation = suggestedLocations.find(loc => loc.ID_DD === locationId) || 
                             allLocations.find(loc => loc.ID_DD === locationId);
            
            // Update UI
            $('.suggestion-card').removeClass('selected');
            $(`.suggestion-card[data-location-id="${locationId}"]`).addClass('selected');
            
            // If location has "Cả hai" rental type, ensure we have a selectedRentalType
            if (selectedLocation && selectedLocation.LoaiThue === 'Cả hai') {
                // Only set default if not already set (preserve user's previous choice)
                if (!selectedLocation.selectedRentalType) {
                    selectedLocation.selectedRentalType = 'hour'; // Set default only if no previous choice
                    console.log('Set default rental type to hour for location:', selectedLocation.ID_DD);
                } else {
                    console.log('Preserving existing rental type choice:', selectedLocation.selectedRentalType, 'for location:', selectedLocation.ID_DD);
                }
                
                // Update all dropdowns for this location to show the selected value
                setTimeout(() => {
                    $(`.suggestion-card[data-location-id="${locationId}"] select`).val(selectedLocation.selectedRentalType);
                    // Update price display
                    updateLocationPrice(locationId, selectedLocation.selectedRentalType, 'suggested');
                    updateLocationPrice(locationId, selectedLocation.selectedRentalType, 'all');
                }, 100);
            }
            
            console.log('Selected location:', selectedLocation);
            
            // Update order summary if we're on step 3
            if (currentStep === 3) {
                updateOrderSummary();
            }
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
            
            // Load all available equipment (not just suggestions)
            $('#equipmentSuggestions').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải danh sách thiết bị...</p>
                </div>
            `);
            
            // Get all available equipment instead of just suggestions
            $.get(`../src/controllers/event-register.php?action=get_all_equipment`, function(data) {
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
            }, 'json').fail(function() {
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
            
            // Try to get combo suggestions for this event type first
            $.get(`../src/controllers/event-register.php?action=get_combo_suggestions&event_type=${encodeURIComponent(eventType)}`, function(data) {
                if (data.success && data.combos.length > 0) {
                    comboSuggestions = data.combos;
                    displayComboSuggestions();
                } else {
                    // If no specific combos for this event type, get all available combos
                    $.get(`../src/controllers/event-register.php?action=get_all_combos`, function(data) {
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
                    }, 'json').fail(function() {
                        $('#comboSuggestions').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Lỗi khi tải combo thiết bị.
                            </div>
                        `);
                    });
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
                            <div class="combo-footer">
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-check"></i> Chọn combo này
                                </button>
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
            
            // Debug: Log selected combo
            console.log('Selected Combo:', selectedCombo);
            console.log('Combo Price:', selectedCombo ? selectedCombo.GiaCombo : 'No combo');
            
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
                // Add equipment if not already selected
                if (existingIndex === -1) {
                    selectedEquipment.push({
                        ID_TB: equipmentId,
                        TenThietBi: equipmentName,
                        GiaThue: price,
                        SoLuong: 1
                    });
                }
                // Add selected class
                card.classList.add('selected');
            } else {
                // Remove equipment if selected
                if (existingIndex !== -1) {
                    selectedEquipment.splice(existingIndex, 1);
                }
                // Remove selected class
                card.classList.remove('selected');
            }
            
            updateOrderSummary();
        }
        
        // Update order summary
        function updateOrderSummary() {
            console.log('=== updateOrderSummary called ===');
            console.log('selectedLocation:', selectedLocation);
            
            if (!selectedLocation) {
                console.log('No selected location, cannot update order summary');
                // Try to get location from the form if in edit mode
                const urlParams = new URLSearchParams(window.location.search);
                const editId = urlParams.get('edit');
                if (editId) {
                    console.log('In edit mode, trying to load selected data again');
                    loadSelectedData(editId);
                }
                return;
            }
            
            // Get form values first
            const eventName = $('#eventName').val();
            const eventDate = $('#eventDate').val();
            const eventTime = $('#eventTime').val();
            const eventEndDate = $('#eventEndDate').val();
            const eventEndTime = $('#eventEndTime').val();
            
            // Calculate location price based on rental type and duration
            let locationPriceNum = 0;
            let locationPriceText = 'Chưa có giá';
            
            if (selectedLocation && eventDate && eventTime && eventEndDate && eventEndTime) {
                const startDate = new Date(eventDate + ' ' + eventTime);
                const endDate = new Date(eventEndDate + ' ' + eventEndTime);
                const durationMs = endDate - startDate;
                const durationHours = Math.ceil(durationMs / (1000 * 60 * 60));
                const durationDays = Math.ceil(durationMs / (1000 * 60 * 60 * 24));
                
                // Calculate price based on rental type
                console.log('Calculating location price:', {
                    LoaiThue: selectedLocation.LoaiThue,
                    selectedRentalType: selectedLocation.selectedRentalType,
                    GiaThueGio: selectedLocation.GiaThueGio,
                    GiaThueNgay: selectedLocation.GiaThueNgay,
                    durationHours: durationHours,
                    durationDays: durationDays
                });
                
                if (selectedLocation.LoaiThue === 'Theo giờ' && selectedLocation.GiaThueGio) {
                    locationPriceNum = durationHours * parseFloat(selectedLocation.GiaThueGio);
                    locationPriceText = `${new Intl.NumberFormat('vi-VN').format(selectedLocation.GiaThueGio)}/giờ × ${durationHours} giờ`;
                    console.log('Using hourly pricing:', locationPriceNum);
                } else if (selectedLocation.LoaiThue === 'Theo ngày' && selectedLocation.GiaThueNgay) {
                    locationPriceNum = durationDays * parseFloat(selectedLocation.GiaThueNgay);
                    locationPriceText = `${new Intl.NumberFormat('vi-VN').format(selectedLocation.GiaThueNgay)}/ngày × ${durationDays} ngày`;
                    console.log('Using daily pricing:', locationPriceNum);
                } else if (selectedLocation.LoaiThue === 'Cả hai') {
                    // Check if user has selected a specific rental type
                    console.log('Both rental types available, checking selectedRentalType:', selectedLocation.selectedRentalType);
                    if (selectedLocation.selectedRentalType === 'hour' && selectedLocation.GiaThueGio) {
                        locationPriceNum = durationHours * parseFloat(selectedLocation.GiaThueGio);
                        locationPriceText = `${new Intl.NumberFormat('vi-VN').format(selectedLocation.GiaThueGio)}/giờ × ${durationHours} giờ`;
                        console.log('Using selected hourly pricing:', locationPriceNum);
                    } else if (selectedLocation.selectedRentalType === 'day' && selectedLocation.GiaThueNgay) {
                        locationPriceNum = durationDays * parseFloat(selectedLocation.GiaThueNgay);
                        locationPriceText = `${new Intl.NumberFormat('vi-VN').format(selectedLocation.GiaThueNgay)}/ngày × ${durationDays} ngày`;
                        console.log('Using selected daily pricing:', locationPriceNum);
                    } else {
                        // Default: Use the cheaper option, but show both options
                        const hourlyPrice = durationHours * parseFloat(selectedLocation.GiaThueGio || 0);
                        const dailyPrice = durationDays * parseFloat(selectedLocation.GiaThueNgay || 0);
                        
                        if (hourlyPrice > 0 && dailyPrice > 0) {
                            // Show both options and ask user to choose
                            locationPriceText = `Vui lòng chọn loại thuê: ${new Intl.NumberFormat('vi-VN').format(selectedLocation.GiaThueGio)}/giờ × ${durationHours} giờ = ${new Intl.NumberFormat('vi-VN').format(hourlyPrice)} VNĐ hoặc ${new Intl.NumberFormat('vi-VN').format(selectedLocation.GiaThueNgay)}/ngày × ${durationDays} ngày = ${new Intl.NumberFormat('vi-VN').format(dailyPrice)} VNĐ`;
                            locationPriceNum = 0; // Don't calculate until user chooses
                        } else if (hourlyPrice > 0) {
                            locationPriceNum = hourlyPrice;
                            locationPriceText = `${new Intl.NumberFormat('vi-VN').format(selectedLocation.GiaThueGio)}/giờ × ${durationHours} giờ`;
                        } else if (dailyPrice > 0) {
                            locationPriceNum = dailyPrice;
                            locationPriceText = `${new Intl.NumberFormat('vi-VN').format(selectedLocation.GiaThueNgay)}/ngày × ${durationDays} ngày`;
                        }
                    }
                }
            }
            
            const locationPrice = new Intl.NumberFormat('vi-VN').format(locationPriceNum);
            
            // Get event type price
            const selectedEventTypeOption = $('#eventType option:selected');
            const eventTypePrice = parseFloat(selectedEventTypeOption.data('price')) || 0;
            const eventTypePriceFormatted = new Intl.NumberFormat('vi-VN').format(eventTypePrice);
            
            console.log('Selected location:', selectedLocation);
            console.log('Location price:', locationPriceNum);
            console.log('Event type price:', eventTypePrice);
            
            let totalPrice = locationPriceNum + eventTypePrice;
            let comboPrice = 0;
            
            let html = `
                <div class="summary-item">
                    <span>Sự kiện:</span>
                    <span>${eventName}</span>
                </div>
                <div class="summary-item">
                    <span>Ngày bắt đầu:</span>
                    <span>${formatDate(eventDate)}</span>
                </div>
                <div class="summary-item">
                    <span>Giờ bắt đầu:</span>
                    <span>${eventTime}</span>
                </div>
                <div class="summary-item">
                    <span>Ngày kết thúc:</span>
                    <span>${formatDate(eventEndDate)}</span>
                </div>
                <div class="summary-item">
                    <span>Giờ kết thúc:</span>
                    <span>${eventEndTime}</span>
                </div>
                <div class="summary-item">
                    <span>Địa điểm:</span>
                    <span>${selectedLocation.TenDiaDiem}</span>
                </div>
                <div class="summary-item">
                    <span>Giá thuê địa điểm:</span>
                    <span>${locationPriceText}</span>
                </div>
                <div class="summary-item">
                    <span>Tổng giá địa điểm:</span>
                    <span>${locationPrice} VNĐ</span>
                </div>
                <div class="summary-item">
                    <span>Loại sự kiện:</span>
                    <span>${selectedEventTypeOption.text()}</span>
                </div>
                <div class="summary-item">
                    <span>Giá loại sự kiện:</span>
                    <span>${eventTypePriceFormatted} VNĐ</span>
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
            
            // Debug: Log values to console
            console.log('Event Type Price:', eventTypePrice);
            console.log('Location Price:', locationPriceNum);
            console.log('Combo Price:', comboPrice);
            console.log('Equipment Total:', selectedEquipment.length > 0 ? selectedEquipment.reduce((sum, eq) => sum + (parseFloat(eq.GiaThue) || 0), 0) : 0);
            console.log('Total Price:', totalPrice);
            console.log('Selected Location:', selectedLocation);
            console.log('Selected Equipment:', selectedEquipment);
            console.log('Selected Combo:', selectedCombo);
            
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
            $('#errorMessage').text(message).show();
            $('#successMessage').hide();
            setTimeout(() => {
                $('#errorMessage').hide();
            }, 5000);
        }
        
        // Show success message
        function showSuccess(message) {
            $('#successMessage').text(message).show();
            $('#errorMessage').hide();
        }
        
        // Form submission
        $('#eventRegistrationForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted, current step:', currentStep);
            
            if (!validateCurrentStep()) {
                console.log('Validation failed');
                return;
            }
            
            console.log('Validation passed, proceeding with submission');
            $('#loadingSpinner').show();
            $('#submitBtn').prop('disabled', true);
            
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            
            const formData = {
                event_name: $('#eventName').val(),
                event_type: $('#eventType').val(),
                event_date: $('#eventDate').val(),
                event_time: $('#eventTime').val(),
                event_end_date: $('#eventEndDate').val(),
                event_end_time: $('#eventEndTime').val(),
                expected_guests: $('#expectedGuests').val(),
                budget: $('#budget').val(),
                description: $('#description').val(),
                location_id: selectedLocation ? selectedLocation.ID_DD : null,
                location_rental_type: selectedLocation ? selectedLocation.selectedRentalType : null,
                equipment_ids: selectedEquipment.map(eq => eq.ID_TB),
                combo_id: selectedCombo ? selectedCombo.ID_Combo : null,
                total_price: calculateTotalPrice()
            };
            
            console.log('Form data:', formData);
            console.log('Selected location:', selectedLocation);
            console.log('Selected equipment:', selectedEquipment);
            console.log('Selected combo:', selectedCombo);
            
            // Add edit ID if we're editing
            if (editId) {
                formData.edit_id = editId;
            }
            
            $.ajax({
                url: `../src/controllers/event-register.php?action=${editId ? 'update_event' : 'register'}`,
                type: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                dataType: 'json',
                success: function(data) {
                    console.log('AJAX success response:', data);
                    $('#loadingSpinner').hide();
                    $('#submitBtn').prop('disabled', false);
                    
                    if (data.success) {
                        const message = editId ? 'Cập nhật sự kiện thành công!' : 'Đăng ký sự kiện thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.';
                        showSuccess(message);
                        setTimeout(() => {
                            window.location.href = 'my-events.php';
                        }, 2000);
                    } else {
                        showError('Lỗi: ' + (data.error || data.message));
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', xhr, status, error);
                    $('#loadingSpinner').hide();
                    $('#submitBtn').prop('disabled', false);
                    showError('Lỗi kết nối. Vui lòng thử lại.');
                }
            });
        });
    </script>
</body>
</html>
