<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and has role 2 or 3 (Quản lý tổ chức hoặc Quản lý sự kiện)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2, 3])) {
    header('Location: login.php');
    exit;
}

// Data will be loaded via AJAX API calls
// Fallback: Load data directly if API fails
$approvedEvents = [];
$existingPlans = [];

try {
    $pdo = getDBConnection();
    
    // Get approved events
    $sql = "
        SELECT 
            dl.ID_DatLich,
            dl.TenSuKien,
            dl.NgayBatDau,
            dl.NgayKetThuc,
            dl.SoNguoiDuKien,
            dl.NganSach,
            dl.TrangThaiDuyet,
            COALESCE(dd.TenDiaDiem, 'Chưa xác định') as TenDiaDiem,
            COALESCE(dd.DiaChi, 'Chưa xác định') as DiaChi,
            COALESCE(ls.TenLoai, 'Chưa phân loại') as TenLoaiSK,
            COALESCE(kh.HoTen, 'Chưa có thông tin') as TenKhachHang,
            COALESCE(kh.SoDienThoai, 'Chưa có') as SoDienThoai
        FROM datlichsukien dl
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
        LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE dl.TrangThaiDuyet = 'Đã duyệt'
        ORDER BY dl.NgayBatDau ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
                $approvedEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get existing plans
    $sql = "
        SELECT 
            kht.id_kehoach,
            kht.id_sukien,
            kht.ten_kehoach,
            kht.noidung,
            kht.ngay_batdau,
            kht.ngay_ketthuc,
            kht.trangthai,
            s.ID_DatLich,
            dl.TenSuKien
        FROM kehoachthuchien kht
        LEFT JOIN sukien s ON kht.id_sukien = s.ID_SuKien
        LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
        ORDER BY kht.ngay_batdau ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $existingPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error loading fallback data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lên kế hoạch thực hiện và phân công - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Page Layout */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        /* Statistics Cards */
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-card p {
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0;
        }
        
        /* Event Planning Cards */
        .planning-card {
            background: white;
            border: 1px solid #e9ecef;
            padding: 1rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
        }
        
        .planning-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .planning-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }
        
        .planning-card:hover::before {
            opacity: 1;
        }
        
        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            border-radius: 20px 20px 0 0;
        }
        
        .event-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            animation: shimmer 4s ease-in-out infinite;
        }
        
        .event-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(30deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(30deg); }
        }
        
        .event-header h5 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .event-header p {
            margin-bottom: 0;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            font-size: 1rem;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 2rem;
            background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
            position: relative;
        }
        
        .card-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e9ecef, transparent);
        }
        
        .card-body .row {
            margin-bottom: 1.25rem;
            padding: 0.75rem;
            background: rgba(255,255,255,0.7);
            border-radius: 12px;
            border: 1px solid rgba(233, 236, 239, 0.5);
            transition: all 0.3s ease;
        }
        
        .card-body .row:hover {
            background: rgba(255,255,255,0.9);
            border-color: #667eea;
            transform: translateX(5px);
        }
        
        .card-body .row:last-child {
            margin-bottom: 0;
        }
        
        .card-body small {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-body strong {
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        /* Buttons */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.875rem 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            border: none;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 50%, #e085f0 100%);
        }
        
        .btn-outline-info {
            border: 2px solid #17a2b8;
            color: #17a2b8;
            background: transparent;
        }
        
        .btn-outline-info:hover {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            border-color: #17a2b8;
            color: white;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(23, 162, 184, 0.4);
        }
        
        /* Existing Plans Section */
        .existing-plans-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .existing-plans-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #28a745, #20c997, #17a2b8);
        }
        
        .existing-plans-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border-color: #28a745;
        }
        
        .existing-plans-card h6 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .existing-plans-card .card-text {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 0.6rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Timeline for Steps */
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 8px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #667eea;
            border: 4px solid white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .timeline-item .card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .timeline-item .card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        /* Modal Improvements */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
            padding: 1.5rem;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            border-top: 1px solid #f0f0f0;
            padding: 1.5rem;
            border-radius: 0 0 15px 15px;
        }
        
        /* Form Improvements */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        /* Loading States */
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .stats-card h3 {
                font-size: 2rem;
            }
            
            .planning-card {
                margin-bottom: 1.5rem;
            }
            
            .event-header {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
        
        /* Remove modal backdrop completely */
        .modal-backdrop {
            display: none !important;
        }
        
        /* Ensure body doesn't get locked when modal is open */
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }
        
        /* Ensure page loading overlay doesn't block interactions */
        .page-loading {
            pointer-events: none !important;
        }
        
        /* Ensure sidebar and navigation are clickable */
        .sidebar, .sidebar a, .menu-item {
            pointer-events: auto !important;
        }
        
        /* Alert Improvements */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1.25rem 1.5rem;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h4 {
            color: #495057;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-calendar-alt"></i>
                        Lên kế hoạch thực hiện và phân công
                    </h1>
                    <p class="page-subtitle">Tạo và quản lý kế hoạch thực hiện cho các sự kiện đã được duyệt</p>
                </div>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-primary" id="approvedEventsCount">-</h3>
                            <p>Sự kiện đã duyệt</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-success" id="totalPlansCount">-</h3>
                            <p>Kế hoạch đã tạo</p>
                            </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-warning" id="inProgressPlansCount">-</h3>
                            <p>Đang thực hiện</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <h3 class="text-info" id="completedPlansCount">-</h3>
                            <p>Hoàn thành</p>
                            </div>
                        </div>
                    </div>

                <!-- Events List -->
                <div class="row" id="eventsList">
                    <div class="col-12">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                            <p class="mt-2">Đang tải danh sách sự kiện...</p>
                        </div>
                    </div>
                </div>

                <!-- Existing Plans -->
                <div class="mt-5" id="existingPlansSection" style="display: none;">
                    <h4 class="mb-3">
                        <i class="fas fa-list-check"></i>
                        Kế hoạch đã tạo
                    </h4>
                    <div class="row" id="existingPlansList">
                        <!-- Plans will be loaded here via JavaScript -->
                        </div>
                                </div>
                            </div>
                            </div>
                        </div>

    <!-- Create Plan Modal -->
    <div class="modal fade" id="createPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i> Tạo kế hoạch
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                <div class="modal-body">
                    <form id="createPlanForm">
                        <input type="hidden" id="eventId" name="eventId">
                                        
                                        <div class="mb-3">
                            <label for="planName" class="form-label">Tên kế hoạch</label>
                            <input type="text" class="form-control" id="planName" name="planName" required>
                                        </div>
                                        
                                        <div class="mb-3">
                            <label for="planContent" class="form-label">Nội dung kế hoạch</label>
                            <textarea class="form-control" id="planContent" name="planContent" rows="4" required></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                <label for="startDate" class="form-label">Ngày bắt đầu</label>
                                <input type="date" class="form-control" id="startDate" name="startDate" required>
                                            </div>
                                            <div class="col-md-6">
                                <label for="endDate" class="form-label">Ngày kết thúc</label>
                                <input type="date" class="form-control" id="endDate" name="endDate" required>
                                                </div>
                                            </div>
                        
                                                <div class="mb-3">
                            <label for="assignedStaff" class="form-label">Nhân viên phụ trách</label>
                            <select class="form-select" id="assignedStaff" name="assignedStaff">
                                <option value="">Chọn nhân viên</option>
                            </select>
                                                </div>
                    </form>
                                            </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="savePlan()">Tạo kế hoạch</button>
                </div>
                                                </div>
                                            </div>
                                        </div>
                                        
    <!-- Manage Steps Modal -->
    <div class="modal fade" id="manageStepsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cogs"></i> Quản lý bước thực hiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="stepEventId" name="eventId">
                    
                    <!-- Add Step Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-plus"></i> Thêm bước mới
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="addStepForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="stepName" class="form-label">Tên bước</label>
                                        <input type="text" class="form-control" id="stepName" name="stepName" required>
                                    </div>
                                    <div class="col-md-6">
                                            <label for="stepStaff" class="form-label">Nhân viên phụ trách</label>
                                            <select class="form-select" id="stepStaff" name="staffId">
                                            <option value="">Chọn nhân viên</option>
                                            </select>
                                    </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                    <label for="stepDescription" class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="stepDescription" name="stepDescription" rows="2"></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="stepStartDate" class="form-label">Ngày bắt đầu</label>
                                        <input type="date" class="form-control" id="stepStartDate" name="stepStartDate" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="stepStartTime" class="form-label">Giờ bắt đầu</label>
                                        <input type="time" class="form-control" id="stepStartTime" name="stepStartTime" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="stepEndDate" class="form-label">Ngày kết thúc</label>
                                        <input type="date" class="form-control" id="stepEndDate" name="stepEndDate" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="stepEndTime" class="form-label">Giờ kết thúc</label>
                                        <input type="time" class="form-control" id="stepEndTime" name="stepEndTime" required>
                                    </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="stepNote" class="form-label">Ghi chú</label>
                                    <textarea class="form-control" id="stepNote" name="note" rows="2"></textarea>
                                        </div>
                                        
                                <div class="text-end">
                                            <button type="button" class="btn btn-primary" onclick="addStep()">
                                        <i class="fas fa-plus"></i> Thêm bước
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                    
                    <!-- Steps List -->
                    <div id="stepsList">
                        <!-- Steps will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Global variables to store data
        let approvedEvents = <?= json_encode($approvedEvents) ?>;
        let existingPlans = <?= json_encode($existingPlans) ?>;
        
        // Hide page loading overlay when page is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            const pageLoading = document.getElementById('pageLoading');
            if (pageLoading) {
                pageLoading.style.display = 'none';
            }
            
            // Load initial data
            loadPageData();
        });
        
        // Also hide loading overlay on window load as fallback
        window.addEventListener('load', function() {
                const pageLoading = document.getElementById('pageLoading');
                if (pageLoading) {
                    pageLoading.style.display = 'none';
                }
        });
        
        // Load page data - using PHP data directly for now
        function loadPageData() {
            console.log('Loading page data from PHP variables');
            // Data is already loaded from PHP, just display it
            displayEvents();
            displayPlans();
            updateStatistics();
        }
        
        // Display events in the UI
        function displayEvents() {
            const eventsList = document.getElementById('eventsList');
            
            if (approvedEvents.length === 0) {
                eventsList.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h4>Chưa có sự kiện nào được duyệt</h4>
                            <p>Vui lòng duyệt sự kiện trước khi tạo kế hoạch thực hiện.</p>
                        </div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            approvedEvents.forEach(event => {
                const startDate = new Date(event.NgayBatDau).toLocaleDateString('vi-VN');
                const endDate = new Date(event.NgayKetThuc).toLocaleDateString('vi-VN');
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="planning-card">
                            <div class="event-header">
                                <h5 class="mb-1">${escapeHtml(event.TenSuKien)}</h5>
                                <p class="mb-0">
                                    <i class="fas fa-calendar"></i>
                                    ${startDate} - ${endDate}
                                </p>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Địa điểm:</small><br>
                                        <strong>${escapeHtml(event.TenDiaDiem)}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Loại sự kiện:</small><br>
                                        <strong>${escapeHtml(event.TenLoaiSK)}</strong>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Số người:</small><br>
                                        <strong>${Number(event.SoNguoiDuKien).toLocaleString()}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Ngân sách:</small><br>
                                        <strong>${Number(event.NganSach).toLocaleString()} VNĐ</strong>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Khách hàng:</small><br>
                                    <strong>${escapeHtml(event.TenKhachHang)}</strong><br>
                                    <small class="text-muted">${escapeHtml(event.SoDienThoai)}</small>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary" onclick="createPlan(${event.ID_DatLich}, '${escapeHtml(event.TenSuKien)}')">
                                        <i class="fas fa-plus"></i> Tạo kế hoạch
                                    </button>
                                    <button class="btn btn-outline-info" onclick="manageSteps(${event.ID_DatLich}, '${escapeHtml(event.TenSuKien)}')">
                                        <i class="fas fa-cogs"></i> Quản lý bước
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            eventsList.innerHTML = html;
        }
        
        // Display plans in the UI
        function displayPlans() {
            const existingPlansSection = document.getElementById('existingPlansSection');
            const existingPlansList = document.getElementById('existingPlansList');
            
            if (existingPlans.length === 0) {
                existingPlansSection.style.display = 'none';
                return;
            }
            
            existingPlansSection.style.display = 'block';
            
            let html = '';
            existingPlans.forEach(plan => {
                const startDate = new Date(plan.ngay_batdau).toLocaleDateString('vi-VN');
                const endDate = new Date(plan.ngay_ketthuc).toLocaleDateString('vi-VN');
                const statusClass = plan.trangthai === 'Hoàn thành' ? 'success' : 
                                  plan.trangthai === 'Đang thực hiện' ? 'warning' : 'secondary';
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="existing-plans-card">
                            <h6>${escapeHtml(plan.ten_kehoach)}</h6>
                            <p class="card-text">${escapeHtml(plan.noidung)}</p>
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i>
                                ${startDate} - ${endDate}
                            </small>
                            <div class="mt-2">
                                <span class="badge bg-${statusClass}">
                                    ${escapeHtml(plan.trangthai)}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            existingPlansList.innerHTML = html;
        }
        
        // Update statistics
        function updateStatistics() {
            document.getElementById('approvedEventsCount').textContent = approvedEvents.length;
            document.getElementById('totalPlansCount').textContent = existingPlans.length;
            
            const inProgressCount = existingPlans.filter(p => p.trangthai === 'Đang thực hiện').length;
            const completedCount = existingPlans.filter(p => p.trangthai === 'Hoàn thành').length;
            
            document.getElementById('inProgressPlansCount').textContent = inProgressCount;
            document.getElementById('completedPlansCount').textContent = completedCount;
        }
        
        // Show error message for events
        function showEventsError() {
            document.getElementById('eventsList').innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h4>Lỗi khi tải dữ liệu</h4>
                        <p>Không thể tải danh sách sự kiện. Vui lòng thử lại sau.</p>
                        <button class="btn btn-primary" onclick="loadPageData()">Thử lại</button>
                    </div>
                </div>
            `;
        }
        
        // Utility function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function createPlan(eventId, eventName) {
            console.log('Creating plan for eventId:', eventId, 'eventName:', eventName);
            document.getElementById('eventId').value = eventId;
            document.querySelector('#createPlanModal .modal-title').innerHTML = 
                '<i class="fas fa-plus-circle"></i> Tạo kế hoạch cho: ' + eventName;
            
            // Load staff options
            loadStaffOptions();
            
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDate').value = today;
            
            // Clear form fields
            document.getElementById('planName').value = '';
            document.getElementById('planContent').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('assignedStaff').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('createPlanModal'));
            modal.show();
        }

        function loadStaffOptions() {
            fetch('src/controllers/event-planning.php?action=get_staff', {
                credentials: 'same-origin'
            })
                .then(handleFetchResponse)
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('assignedStaff');
                        select.innerHTML = '<option value="">Chọn nhân viên</option>';
                        data.staff.forEach(staff => {
                            select.innerHTML += `<option value="${staff.ID_NhanVien}">${staff.HoTen} - ${staff.ChucVu}</option>`;
                        });
                    }
                })
                .catch(error => console.error('Error loading staff:', error));
        }

        function savePlan() {
            const form = document.getElementById('createPlanForm');
            const formData = new FormData(form);
            formData.append('action', 'create_plan');
            
            // Check required fields
            const eventId = formData.get('eventId');
            const planName = formData.get('planName');
            const planContent = formData.get('planContent');
            const startDate = formData.get('startDate');
            const endDate = formData.get('endDate');
            
            if (!eventId || !planName || !planContent || !startDate || !endDate) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                return;
            }
            
            // Validate dates
            const startDateObj = new Date(startDate);
            const endDateObj = new Date(endDate);
            
            if (endDateObj < startDateObj) {
                alert('Ngày kết thúc phải sau ngày bắt đầu');
                return;
            }

            fetch('src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Tạo kế hoạch thành công');
                    // Reload page to get fresh data
                    location.reload();
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tạo kế hoạch: ' + error.message);
            });
        }

        function manageSteps(eventId, eventName) {
            document.getElementById('stepEventId').value = eventId;
            document.querySelector('#manageStepsModal .modal-title').innerHTML = 
                '<i class="fas fa-cogs"></i> Quản lý bước thực hiện: ' + eventName;
            
            // Load existing steps
            loadSteps(eventId);
            
            // Load staff options
            loadStaffOptionsForSteps();
            
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('stepStartDate').value = today;
            
            const modal = new bootstrap.Modal(document.getElementById('manageStepsModal'));
            modal.show();
        }

        function loadSteps(eventId) {
            console.log('Loading steps for event:', eventId);
            fetch(`src/controllers/event-planning.php?action=get_event_steps&event_id=${eventId}`, {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Steps data:', data);
                    if (data.success && data.steps && data.steps.length > 0) {
                        let html = '<div class="timeline">';
                        data.steps.forEach((step, index) => {
                            const statusClass = step.TrangThai === 'Hoàn thành' ? 'success' : 
                                             step.TrangThai === 'Đang thực hiện' ? 'warning' : 'secondary';
                            html += `
                                <div class="timeline-item">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6>Bước ${index + 1}: ${step.TenBuoc}</h6>
                                                    <p class="text-muted">${step.MoTa}</p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar"></i>
                                                        ${new Date(step.NgayBatDau).toLocaleDateString('vi-VN')} - 
                                                        ${new Date(step.NgayKetThuc).toLocaleDateString('vi-VN')}
                                                    </small>
                                                    ${step.TenNhanVien ? `<br><small class="text-muted"><i class="fas fa-user"></i> ${step.TenNhanVien}</small>` : ''}
                                                    ${step.GhiChu ? `<br><small class="text-muted"><i class="fas fa-sticky-note"></i> ${step.GhiChu}</small>` : ''}
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-${statusClass}">${step.TrangThai}</span>
                                                    <div class="btn-group-vertical mt-2">
                                                        <button class="btn btn-sm btn-outline-success" onclick="updateStepStatus(${step.ID_ChiTiet}, 'Hoàn thành')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteStep(${step.ID_ChiTiet})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        document.getElementById('stepsList').innerHTML = html;
                    } else {
                        document.getElementById('stepsList').innerHTML = '<p class="text-muted">Chưa có bước thực hiện nào.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading steps:', error);
                    document.getElementById('stepsList').innerHTML = '<p class="text-danger">Lỗi khi tải danh sách bước thực hiện.</p>';
                });
        }

        function loadStaffOptionsForSteps() {
            fetch('src/controllers/event-planning.php?action=get_staff_list', {
                credentials: 'same-origin'
            })
                .then(handleFetchResponse)
                .then(data => {
                    const select = document.getElementById('stepStaff');
                    select.innerHTML = '<option value="">Chọn nhân viên</option>';
                    
                    if (data.success && data.staff) {
                        data.staff.forEach(staff => {
                            const option = document.createElement('option');
                            option.value = staff.ID_NhanVien;
                            option.textContent = staff.HoTen + ' - ' + staff.ChucVu;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading staff:', error));
        }

        function addStep() {
            const form = document.getElementById('addStepForm');
            const formData = new FormData(form);
            formData.append('action', 'add_plan_step');
            formData.append('eventId', document.getElementById('stepEventId').value);
            
            // Get date and time values
            const startDate = formData.get('stepStartDate');
            const startTime = formData.get('stepStartTime');
            const endDate = formData.get('stepEndDate');
            const endTime = formData.get('stepEndTime');
            
            // Combine date and time
            const startDateTime = startDate + ' ' + startTime;
            const endDateTime = endDate + ' ' + endTime;
            
            // Update form data with combined datetime
            formData.set('stepStartDateTime', startDateTime);
            formData.set('stepEndDateTime', endDateTime);
            
            // Validate dates
            const startDateObj = new Date(startDateTime);
            const endDateObj = new Date(endDateTime);
            
            if (endDateObj < startDateObj) {
                alert('Ngày kết thúc phải sau ngày bắt đầu');
                return;
            }

            fetch('src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Thêm bước kế hoạch thành công');
                    form.reset();
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('stepStartDate').value = today;
                    loadSteps(document.getElementById('stepEventId').value);
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thêm bước kế hoạch: ' + error.message);
            });
        }

        function updateStepStatus(stepId, status) {
            const formData = new FormData();
            formData.append('action', 'update_step_status');
            formData.append('step_id', stepId);
            formData.append('status', status);

            fetch('src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                    .then(handleFetchResponse)
                    .then(data => {
                        if (data.success) {
                    alert('Cập nhật trạng thái thành công');
                    loadSteps(document.getElementById('stepEventId').value);
                        } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái: ' + error.message);
            });
        }

        function deleteStep(stepId) {
            if (confirm('Bạn có chắc muốn xóa bước thực hiện này?')) {
                const formData = new FormData();
                formData.append('action', 'delete_step');
                formData.append('step_id', stepId);

            fetch('src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                        alert('Xóa bước thực hiện thành công');
                        loadSteps(document.getElementById('stepEventId').value);
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                    alert('Có lỗi xảy ra khi xóa bước thực hiện: ' + error.message);
                });
            }
        }

        // Helper function to handle fetch responses
        function handleFetchResponse(response) {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            
            return response.json();
        }
    </script>
</body>
</html>
