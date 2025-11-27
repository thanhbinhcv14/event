<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Kiểm tra người dùng đã đăng nhập và có role 2 hoặc 3 (Quản lý tổ chức hoặc Quản lý sự kiện)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2, 3])) {
    header('Location: ../login.php');
    exit;
}

// Lấy dữ liệu từ controller
require_once __DIR__ . '/../src/controllers/event-planning.php';

$approvedEvents = [];
$existingPlans = [];

try {
    $pdo = getDBConnection();
    
    // Lấy các sự kiện đã duyệt từ controller
    $approvedEvents = getApprovedEventsForView($pdo);
    
    // Lấy các kế hoạch hiện có từ controller
    $existingPlans = getExistingPlansForView($pdo);
    
    // Cập nhật trạng thái tất cả kế hoạch trước khi hiển thị
    updateAllPlanStatusesForView($pdo);
    
} catch (Exception $e) {
    error_log("Error loading data: " . $e->getMessage());
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
        /* Sửa vị trí nội dung để tránh chồng lên header */
        body {
            padding-top: 0; /* Xóa mọi padding của body */
        }
        
        .container-fluid {
            margin-top: 0;
            padding-top: 20px;
        }
        
        /* Bố cục Trang */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
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
        
        /* Thẻ Thống kê */
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
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
        
        /* Container cho các card - đảm bảo cùng chiều cao */
        .event-card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        /* Thẻ Lên kế hoạch sự kiện */
        .planning-card {
            background: white;
            border: 1px solid #e9ecef;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            position: relative;
        }
        
        /* Status Badge ở góc trên bên phải của event-header */
        .plan-status-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            z-index: 20;
        }
        
        .plan-status-badge .badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            padding: 1.5rem;
            background: white;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .card-body .row {
            margin-bottom: 1.25rem;
            padding: 0.75rem;
            background: rgba(255,255,255,0.7);
            border-radius: 12px;
            border: 1px solid rgba(233, 236, 239, 0.5);
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
        
        /* Nút bấm */
        .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            color: white;
        }
        
        .btn-outline-info {
            border: 2px solid #17a2b8;
            color: #17a2b8;
            background: transparent;
        }
        
        .btn-outline-info:hover {
            background: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        
        /* Phần Kế hoạch hiện có */
        .existing-plans-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        /* Tùy chỉnh Form Bước nâng cao */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .form-control-lg, .form-select-lg {
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control-lg:focus, .form-select-lg:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-label.fw-bold {
            color: #2c3e50;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .form-label i {
            font-size: 0.8rem;
        }
        
        .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            font-weight: 600;
        }
        
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        
        .card.shadow-sm {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
            border: none;
            border-radius: 15px;
        }
        
        .card-header.bg-gradient-primary {
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        
        .card-body.p-4 {
            background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
        }
        
        .g-3 > * {
            padding: 0.75rem;
        }
        
        /* Tùy chỉnh Placeholder */
        .form-control::placeholder, .form-select::placeholder {
            color: #6c757d;
            opacity: 0.7;
        }
        
        /* Timeline nâng cao cho danh sách bước */
        .timeline-item .card {
            border-radius: 12px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .timeline-item .card-body {
            padding: 1.75rem;
        }
        
        .timeline-item h6 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
        }
        
        .timeline-item .text-muted {
            color: #6c757d !important;
            font-size: 0.9rem;
        }
        
        .timeline-item .badge {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        
        .btn-group-vertical .btn {
            border-radius: 8px;
            margin-bottom: 0.25rem;
        }
        
        /* Trạng thái trống cho bước */
        #stepsList .text-muted {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
            font-style: italic;
        }
        
        /* Cải thiện Modal */
        .modal-xl {
            max-width: 95%;
        }
        
        @media (min-width: 1200px) {
            .modal-xl {
                max-width: 1400px;
            }
        }
        
        .modal-lg {
            max-width: 90%;
        }
        
        @media (min-width: 992px) {
            .modal-lg {
                max-width: 900px;
            }
        }
        
        /* Sửa vị trí modal và overflow */
        .modal-dialog {
            margin: 1.75rem auto;
            max-width: 95%;
        }
        
        .modal-xl .modal-dialog {
            max-width: 95%;
        }
        
        @media (min-width: 1200px) {
            .modal-xl .modal-dialog {
                max-width: 1400px;
            }
        }
        
        @media (min-width: 992px) {
            .modal-lg .modal-dialog {
                max-width: 900px;
            }
        }
        
        /* Đảm bảo modal được căn giữa đúng cách */
        .modal.show {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* Đảm bảo nội dung modal không bị tràn */
        .modal-body {
            overflow-x: hidden;
        }
        
        /* Sửa vị trí nút trong footer modal */
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .modal-footer .btn {
            margin: 0;
        }
        
        /* Đảm bảo các phần tử form không bị tràn */
        .form-control, .form-select {
            max-width: 100%;
        }
        
        /* Sửa các vấn đề responsive */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
            
            .modal-xl .modal-dialog {
                max-width: calc(100% - 1rem);
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .modal-footer {
                padding: 1rem;
                flex-direction: column;
            }
            
            .modal-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
        
        /* Cải thiện Responsive */
        @media (max-width: 768px) {
            .form-control-lg, .form-select-lg {
                font-size: 0.9rem;
                padding: 0.6rem 0.8rem;
            }
            
            .btn-lg {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .card-body.p-4 {
                padding: 1.5rem !important;
            }
            
            .d-flex.gap-2 {
                flex-direction: column;
                gap: 0.5rem !important;
            }
            
            .d-flex.gap-2 .btn {
                width: 100%;
            }
        }
        
        /* Buộc ẩn phần kế hoạch hiện có ở dưới như yêu cầu */
        #existingPlansSection { 
            display: none !important; 
            visibility: hidden !important;
            height: 0 !important;
            overflow: hidden !important;
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
            opacity: 0.9;
        }
        
        /* Timeline cho Bước */
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
        
        /* Tùy chỉnh Kế hoạch Sự kiện */
        .event-plans-list {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 0.5rem;
        }
        
        .event-plan-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef !important;
            transition: all 0.3s ease;
        }
        
        .event-plan-item:hover {
            background: #f8f9fa;
            border-color: #667eea !important;
        }
        
        .event-plan-item .text-primary {
            color: #667eea !important;
            font-size: 0.9rem;
        }
        
        .event-plan-item .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* Tùy chỉnh Modal - Z-index cố định */
        .modal {
            z-index: 10000 !important;
        }
        
        .modal-backdrop {
            display: none !important;
        }
        
        .modal.show {
            z-index: 10000 !important;
        }
        
        .modal.show .modal-dialog {
            z-index: 10001 !important;
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal.show .modal-content {
            z-index: 10002 !important;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            position: relative;
        }
        
        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            z-index: 1;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border-bottom: none;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .modal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .modal-header .btn-close:hover {
            opacity: 1;
        }
        
        .modal-title {
            font-weight: 700;
            font-size: 1.4rem;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .modal-body {
            padding: 2.5rem;
            background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1.5rem 2.5rem;
            border-radius: 0 0 20px 20px;
            background: #f8f9fa;
        }
        
        /* Đảm bảo modal bao phủ mọi thứ */
        .modal.show {
            background-color: rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Sửa cuộn body khi modal mở */
        body.modal-open {
            overflow: hidden !important;
            padding-right: 0 !important;
        }
        
        /* Đảm bảo sidebar không can thiệp */
        .sidebar, .admin-header, nav {
            z-index: 1030 !important;
        }
        
        /* Cải thiện Form cho Modal */
        .modal .form-control, .modal .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 1rem 1.25rem;
            transition: all 0.3s ease;
            background: white;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .modal .form-control:focus, .modal .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.3rem rgba(102, 126, 234, 0.15);
            background: white;
            transform: translateY(-2px);
        }
        
        .modal .form-label {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Tùy chỉnh Card trong modal */
        .modal .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .modal .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .modal .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0;
        }
        
        .modal .card-header h6 {
            color: #2c3e50;
            font-weight: 700;
            margin: 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal .card-body {
            padding: 2rem;
            background: white;
        }
        
        /* Tùy chỉnh Nút trong modal */
        .modal .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .modal .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        
        .modal .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .modal .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Trạng thái Tải */
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
        }
        
        /* Thiết kế Responsive */
        @media (max-width: 768px) {
            .container-fluid {
                padding-top: 10px;
            }
           
            .page-title {
                font-size: 2rem;
            }
           
            .stats-card h3 {
                font-size: 2rem;
            }
           
            .planning-card {
                margin-bottom: 1.5rem;
                padding: 1rem;
            }
           
            .event-header {
                padding: 1rem;
                padding-top: 2rem; /* Khoảng trống cho badge trên mobile */
            }
            
            .plan-status-badge {
                top: 0.75rem;
                right: 0.75rem;
            }
            
            .plan-status-badge .badge {
                font-size: 0.75rem;
                padding: 0.4rem 0.8rem;
            }
           
            .card-body {
                padding: 1rem;
            }
           
            .modal-dialog {
                margin: 1rem;
                max-width: 95% !important;
            }
           
            .modal-body {
                padding: 1rem;
            }
        }
        
        /* Đảm bảo menu/sidebar không chồng lên modal */
        .sidebar, .admin-header, nav {
            z-index: 1030 !important;
            pointer-events: auto !important;
        }
        
        /* Giữ cuộn body khi modal mở */
        body.modal-open {
            overflow: hidden !important;
            padding-right: 0 !important;
        }
        
        /* Đảm bảo modal luôn ở trên cùng */
        .modal.show {
            z-index: 10000 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Sửa vị trí modal để căn giữa đúng cách */
        .modal.show .modal-dialog {
            position: fixed !important;
            z-index: 10001 !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
            max-width: 95vw;
            max-height: 90vh;
            width: 1200px;
        }
        
        /* Đảm bảo modal không chồng lên sidebar */
        @media (min-width: 769px) {
            .modal.show .modal-dialog {
                left: calc(50% + 125px) !important; /* Điều chỉnh cho chiều rộng sidebar */
                max-width: calc(95vw - 250px);
                width: 1000px;
            }
            
            /* Đảm bảo nội dung modal vừa vặn đúng cách */
            .modal-content {
                max-width: 100%;
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .modal.show .modal-dialog {
                left: 50% !important;
                max-width: 95vw;
                width: 95vw;
            }
        }
        
        /* Sửa lỗi responsive bổ sung */
        @media (min-width: 1200px) {
            .modal.show .modal-dialog {
                left: calc(50% + 125px) !important;
                max-width: calc(100vw - 300px);
                width: 1200px;
            }
        }
        
        @media (min-width: 1400px) {
            .modal.show .modal-dialog {
                width: 1400px;
            }
        }
        
        /* Cải thiện Alert */
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
        
        /* Trạng thái Trống */
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
        
        /* Danh sách Checkbox Chọn Nhân viên */
        .staff-selection-container {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .staff-selection-container:hover {
            border-color: #667eea;
        }
        
        .staff-checkbox-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .staff-checkbox-item:hover {
            background: #f0f4ff;
            border-color: #667eea;
        }
        
        .staff-checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .staff-checkbox-item label {
            margin: 0;
            cursor: pointer;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .staff-checkbox-item .staff-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        .staff-checkbox-item .staff-role {
            color: #6c757d;
            font-size: 0.85rem;
            font-style: italic;
        }
        
        .staff-checkbox-item input[type="checkbox"]:checked + label .staff-name {
            color: #667eea;
        }
        
        .staff-checkbox-item:has(input[type="checkbox"]:checked) {
            background: #e8f0fe;
            border-color: #667eea;
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

                <!-- Filter Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Tìm kiếm</label>
                                        <input type="text" id="eventSearchInput" class="form-control form-control-sm" placeholder="Tên sự kiện, địa điểm, khách hàng...">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Loại sự kiện</label>
                                        <select id="eventTypeFilter" class="form-select form-select-sm">
                                            <option value="">Tất cả</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Từ ngày</label>
                                        <input type="date" id="eventDateFrom" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Đến ngày</label>
                                        <input type="date" id="eventDateTo" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="resetEventFilters()" title="Xóa bộ lọc">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
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
                                            <div class="col-md-3">
                                <label for="startDate" class="form-label">Ngày bắt đầu</label>
                                <input type="date" class="form-control" id="startDate" name="startDate" required>
                                            </div>
                                            <div class="col-md-3">
                                <label for="startTime" class="form-label">Giờ bắt đầu</label>
                                <input type="time" class="form-control" id="startTime" name="startTime" required>
                                            </div>
                                            <div class="col-md-3">
                                <label for="endDate" class="form-label">Ngày kết thúc</label>
                                <input type="date" class="form-control" id="endDate" name="endDate" required>
                                            </div>
                                            <div class="col-md-3">
                                <label for="endTime" class="form-label">Giờ kết thúc</label>
                                <input type="time" class="form-control" id="endTime" name="endTime" required>
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
        <div class="modal-dialog modal-xl" style="max-width: 1200px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cogs"></i> Quản lý bước thực hiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="stepEventId" name="eventId">
                    
                    <div class="row g-4">
                        <!-- Left Side: Steps List -->
                        <div class="col-md-6">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header bg-gradient-info text-white">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-list-check me-2"></i> 
                                        Danh sách các bước
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <div id="stepsList" class="steps-timeline">
                                        <!-- Steps will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Side: Add Step Form -->
                        <div class="col-md-6" id="addStepFormContainer">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header bg-gradient-primary text-white">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-plus-circle me-2"></i> 
                                        Thêm bước thực hiện mới
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <form id="addStepForm">
                                        <div class="mb-3">
                                            <label for="stepName" class="form-label fw-bold">
                                                <i class="fas fa-tag text-primary me-1"></i>Tên bước
                                            </label>
                                            <input type="text" class="form-control form-control-lg" id="stepName" name="stepName" 
                                                   placeholder="Nhập tên bước thực hiện" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-users text-primary me-1"></i>Nhân viên phụ trách
                                            </label>
                                            <div class="staff-selection-container border rounded p-3" style="max-height: 300px; overflow-y: auto; background: #f8f9fa;">
                                                <div id="stepStaffCheckboxes" class="staff-checkbox-list">
                                                    <div class="text-center text-muted py-3">
                                                        <i class="fas fa-spinner fa-spin"></i> Đang tải danh sách nhân viên...
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="stepDescription" class="form-label fw-bold">
                                                <i class="fas fa-file-text text-primary me-1"></i>Mô tả chi tiết
                                            </label>
                                            <textarea class="form-control" id="stepDescription" name="stepDescription" 
                                                      rows="4" placeholder="Mô tả chi tiết về bước thực hiện này..."></textarea>
                                        </div>
                                        
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label for="stepStartDate" class="form-label fw-bold">
                                                    <i class="fas fa-calendar text-primary me-1"></i>Ngày bắt đầu
                                                </label>
                                                <input type="date" class="form-control" id="stepStartDate" name="stepStartDate" required>
                                            </div>
                                            <div class="col-6">
                                                <label for="stepStartTime" class="form-label fw-bold">
                                                    <i class="fas fa-clock text-primary me-1"></i>Giờ bắt đầu
                                                </label>
                                                <input type="time" class="form-control" id="stepStartTime" name="stepStartTime" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label for="stepEndDate" class="form-label fw-bold">
                                                    <i class="fas fa-calendar text-primary me-1"></i>Ngày kết thúc
                                                </label>
                                                <input type="date" class="form-control" id="stepEndDate" name="stepEndDate" required>
                                            </div>
                                            <div class="col-6">
                                                <label for="stepEndTime" class="form-label fw-bold">
                                                    <i class="fas fa-clock text-primary me-1"></i>Giờ kết thúc
                                                </label>
                                                <input type="time" class="form-control" id="stepEndTime" name="stepEndTime" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="stepNote" class="form-label fw-bold">
                                                <i class="fas fa-sticky-note text-primary me-1"></i>Ghi chú bổ sung
                                            </label>
                                            <textarea class="form-control" id="stepNote" name="note" rows="3" 
                                                      placeholder="Thêm ghi chú hoặc lưu ý đặc biệt..."></textarea>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-primary btn-lg" onclick="addStep()">
                                                <i class="fas fa-plus me-2"></i>Thêm bước thực hiện
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="resetStepForm()">
                                                <i class="fas fa-undo me-1"></i>Làm mới form
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Plan Modal -->
    <div class="modal fade" id="editPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-pen-to-square"></i> Sửa kế hoạch
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPlanForm">
                        <input type="hidden" id="editPlanId" name="planId">
                        <div class="mb-3">
                            <label for="editAssignedStaff" class="form-label">Nhân viên phụ trách</label>
                            <select class="form-select" id="editAssignedStaff" name="assignedStaff">
                                <option value="">Chọn nhân viên</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editPlanName" class="form-label">Tên kế hoạch</label>
                            <input type="text" class="form-control" id="editPlanName" name="planName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPlanContent" class="form-label">Nội dung kế hoạch</label>
                            <textarea class="form-control" id="editPlanContent" name="planContent" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="editStartDate" class="form-label">Ngày bắt đầu</label>
                                <input type="date" class="form-control" id="editStartDate" required>
                            </div>
                            <div class="col-md-3">
                                <label for="editStartTime" class="form-label">Giờ bắt đầu</label>
                                <input type="time" class="form-control" id="editStartTime" required>
                            </div>
                            <div class="col-md-3">
                                <label for="editEndDate" class="form-label">Ngày kết thúc</label>
                                <input type="date" class="form-control" id="editEndDate" required>
                            </div>
                            <div class="col-md-3">
                                <label for="editEndTime" class="form-label">Giờ kết thúc</label>
                                <input type="time" class="form-control" id="editEndTime" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="editStatus" class="form-label">Trạng thái</label>
                            <select class="form-select" id="editStatus" name="status">
                                <option value="Chưa bắt đầu">Chưa bắt đầu</option>
                                <option value="Đang thực hiện">Đang thực hiện</option>
                                <option value="Hoàn thành">Hoàn thành</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditPlan()">Lưu thay đổi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Event Plans Modal -->
    <div class="modal fade" id="viewEventPlansModal" tabindex="-1">
        <div class="modal-dialog modal-lg" style="max-width: 900px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-list-check"></i> Kế hoạch đã tạo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="viewEventPlansContent">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                            <p class="text-muted mt-2">Đang tải kế hoạch...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Step Modal -->
    <div class="modal fade" id="editStepModal" tabindex="-1">
        <div class="modal-dialog modal-lg" style="max-width: 900px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Chỉnh sửa bước thực hiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editStepForm">
                        <input type="hidden" id="editStepId" name="stepId">
                        
                        <div class="mb-3">
                            <label for="editStepName" class="form-label">Tên bước *</label>
                            <input type="text" class="form-control" id="editStepName" name="stepName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStepDescription" class="form-label">Mô tả chi tiết</label>
                            <textarea class="form-control" id="editStepDescription" name="stepDescription" rows="3"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editStepStartDate" class="form-label">Ngày bắt đầu *</label>
                                <input type="date" class="form-control" id="editStepStartDate" name="stepStartDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editStepStartTime" class="form-label">Giờ bắt đầu *</label>
                                <input type="time" class="form-control" id="editStepStartTime" name="stepStartTime" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editStepEndDate" class="form-label">Ngày kết thúc *</label>
                                <input type="date" class="form-control" id="editStepEndDate" name="stepEndDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editStepEndTime" class="form-label">Giờ kết thúc *</label>
                                <input type="time" class="form-control" id="editStepEndTime" name="stepEndTime" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nhân viên phụ trách</label>
                            <div class="staff-selection-container border rounded p-3" style="max-height: 300px; overflow-y: auto; background: #f8f9fa;">
                                <div id="editStepStaffCheckboxes" class="staff-checkbox-list">
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-spinner fa-spin"></i> Đang tải danh sách nhân viên...
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStepNote" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="editStepNote" name="stepNote" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditStep()">Lưu thay đổi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Biến toàn cục để lưu dữ liệu
        let approvedEvents = <?= json_encode($approvedEvents) ?>;
        let existingPlans = <?= json_encode($existingPlans) ?>;
        
        // Ẩn overlay loading khi trang đã tải xong
        document.addEventListener('DOMContentLoaded', function() {
            const pageLoading = document.getElementById('pageLoading');
            if (pageLoading) {
                pageLoading.style.display = 'none';
            }
            
            // Tải dữ liệu ban đầu
            loadPageData();
            
            // Thêm event listeners cho các bộ lọc
            const eventSearch = document.getElementById('eventSearchInput');
            const eventType = document.getElementById('eventTypeFilter');
            const eventDateFrom = document.getElementById('eventDateFrom');
            const eventDateTo = document.getElementById('eventDateTo');
            
            if (eventSearch) eventSearch.addEventListener('input', filterEvents);
            if (eventType) eventType.addEventListener('change', filterEvents);
            if (eventDateFrom) eventDateFrom.addEventListener('change', filterEvents);
            if (eventDateTo) eventDateTo.addEventListener('change', filterEvents);
        });
        
        // Cũng ẩn overlay loading trên window load như dự phòng
        window.addEventListener('load', function() {
                const pageLoading = document.getElementById('pageLoading');
                if (pageLoading) {
                    pageLoading.style.display = 'none';
                }
        });
        
        // Tự động làm mới các bước mỗi 10 giây để cập nhật thanh tiến trình khi nhân viên cập nhật tiến độ
        setInterval(function() {
            const currentEventId = document.getElementById('stepEventId')?.value;
            if (currentEventId) {
                // Kiểm tra xem sự kiện đã hoàn thành chưa
                const event = approvedEvents.find(e => e.ID_DatLich == currentEventId);
                const isReadOnly = event && event.TrangThaiSuKien === 'Hoàn thành';
                console.log('Auto-refreshing steps for event:', currentEventId, 'isReadOnly:', isReadOnly);
                loadSteps(currentEventId, isReadOnly);
            }
        }, 10000);
        
        // Tải dữ liệu trang - sử dụng dữ liệu PHP trực tiếp
        function loadPageData() {
            console.log('Loading page data from PHP variables');
            console.log('Approved events:', approvedEvents);
            console.log('Existing plans:', existingPlans);
            
            // Dữ liệu đã được tải từ PHP, chỉ cần hiển thị
            displayEvents();
            displayPlans();
            updateStatistics();
            
            // Điền dữ liệu vào các dropdown bộ lọc
            populateEventFilters();
            
            // Cũng tải kế hoạch từ API để đảm bảo có dữ liệu mới nhất
            loadPlansFromAPI();
        }
        
        // Điền dữ liệu vào các dropdown bộ lọc với các giá trị duy nhất
        function populateEventFilters() {
            // Đầu tiên, lấy các loại sự kiện duy nhất từ các sự kiện đã duyệt (để hiển thị nhanh)
            const eventTypesFromEvents = new Set();
            approvedEvents.forEach(event => {
                if (event.TenLoaiSK && event.TenLoaiSK !== 'Chưa phân loại') {
                    eventTypesFromEvents.add(event.TenLoaiSK);
                }
            });
            
            // Điền bộ lọc loại sự kiện với các loại sự kiện từ các sự kiện đã duyệt trước
            const eventTypeFilter = document.getElementById('eventTypeFilter');
            if (eventTypeFilter) {
                // Xóa các tùy chọn hiện có trừ "Tất cả"
                eventTypeFilter.innerHTML = '<option value="">Tất cả</option>';
                
                // Thêm các loại sự kiện từ các sự kiện đã duyệt
                Array.from(eventTypesFromEvents).sort().forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.toLowerCase();
                    option.textContent = type;
                    eventTypeFilter.appendChild(option);
                });
            }
            
            // Sau đó, lấy tất cả các loại sự kiện từ database để đảm bảo có đầy đủ
            fetch('../src/controllers/event-types.php?action=get_all_public', {
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.event_types) {
                    const eventTypeFilter = document.getElementById('eventTypeFilter');
                    if (eventTypeFilter) {
                        // Lấy các giá trị hiện có để tránh trùng lặp
                        const existingValues = new Set();
                        Array.from(eventTypeFilter.options).forEach(opt => {
                            if (opt.value) existingValues.add(opt.value.toLowerCase());
                        });
                        
                        // Thêm tất cả các loại sự kiện từ database
                        data.event_types.forEach(eventType => {
                            const typeName = eventType.TenLoai || eventType.ten_loai;
                            if (typeName && typeName !== 'Chưa phân loại' && !existingValues.has(typeName.toLowerCase())) {
                                const option = document.createElement('option');
                                option.value = typeName.toLowerCase();
                                option.textContent = typeName;
                                eventTypeFilter.appendChild(option);
                            }
                        });
                        
                        // Sắp xếp các tùy chọn theo thứ tự bảng chữ cái (giữ "Tất cả" ở đầu)
                        const options = Array.from(eventTypeFilter.options);
                        const allOption = options[0]; // "Tất cả"
                        const otherOptions = options.slice(1).sort((a, b) => {
                            return a.textContent.localeCompare(b.textContent, 'vi');
                        });
                        eventTypeFilter.innerHTML = '';
                        eventTypeFilter.appendChild(allOption);
                        otherOptions.forEach(opt => eventTypeFilter.appendChild(opt));
                    }
                }
            })
            .catch(error => {
                console.error('Error loading all event types:', error);
                // Tiếp tục với các loại sự kiện từ các sự kiện đã duyệt
            });
        }
        
        // Lọc sự kiện dựa trên tiêu chí tìm kiếm
        function filterEvents() {
            const searchTerm = document.getElementById('eventSearchInput')?.value.toLowerCase() || '';
            const eventType = document.getElementById('eventTypeFilter')?.value || '';
            const dateFrom = document.getElementById('eventDateFrom')?.value || '';
            const dateTo = document.getElementById('eventDateTo')?.value || '';
            
            const eventCards = document.querySelectorAll('.event-card');
            let visibleCount = 0;
            
            eventCards.forEach(card => {
                const eventName = card.getAttribute('data-event-name') || '';
                const eventTypeValue = card.getAttribute('data-event-type') || '';
                const locationValue = card.getAttribute('data-location') || '';
                const customer = card.getAttribute('data-customer') || '';
                const startDate = card.getAttribute('data-start-date') || '';
                const endDate = card.getAttribute('data-end-date') || '';
                
                // Bộ lọc tìm kiếm
                const matchesSearch = !searchTerm || 
                    eventName.includes(searchTerm) || 
                    locationValue.includes(searchTerm) || 
                    customer.includes(searchTerm);
                
                // Bộ lọc loại sự kiện
                const matchesType = !eventType || eventTypeValue === eventType;
                
                // Bộ lọc ngày - kiểm tra xem sự kiện có trùng với khoảng ngày không
                let matchesDate = true;
                if (dateFrom || dateTo) {
                    if (dateFrom && dateTo) {
                        // Sự kiện phải trùng với khoảng ngày
                        matchesDate = (startDate <= dateTo && endDate >= dateFrom);
                    } else if (dateFrom) {
                        matchesDate = endDate >= dateFrom;
                    } else if (dateTo) {
                        matchesDate = startDate <= dateTo;
                    }
                }
                
                if (matchesSearch && matchesType && matchesDate) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Hiển thị thông báo nếu không có kết quả
            const eventsList = document.getElementById('eventsList');
            let noResultsMessage = eventsList.querySelector('.no-results-message');
            if (visibleCount === 0 && eventCards.length > 0) {
                if (!noResultsMessage) {
                    noResultsMessage = document.createElement('div');
                    noResultsMessage.className = 'col-12 no-results-message';
                    noResultsMessage.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h4>Không tìm thấy sự kiện nào</h4>
                            <p>Không có sự kiện nào phù hợp với bộ lọc của bạn.</p>
                        </div>
                    `;
                    eventsList.appendChild(noResultsMessage);
                }
                noResultsMessage.style.display = '';
            } else if (noResultsMessage) {
                noResultsMessage.style.display = 'none';
            }
        }
        
        // Đặt lại tất cả các bộ lọc
        function resetEventFilters() {
            document.getElementById('eventSearchInput').value = '';
            document.getElementById('eventTypeFilter').value = '';
            document.getElementById('eventDateFrom').value = '';
            document.getElementById('eventDateTo').value = '';
            filterEvents();
        }
        
        // Tải kế hoạch từ API
        function loadPlansFromAPI() {
            fetch('../src/controllers/event-planning.php?action=get_plans', {
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success && data.plans) {
                    console.log('Loaded plans from API:', data.plans);
                    existingPlans = data.plans;
                    displayPlans();
                    updateStatistics();
                    
                    // Tải lại kế hoạch cho từng sự kiện
                    approvedEvents.forEach(event => {
                        loadEventPlans(event.ID_DatLich);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading plans from API:', error);
            });
        }
        
        // Hiển thị sự kiện trong giao diện
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
            
            // Sắp xếp sự kiện: đã hoàn thành xuống dưới, còn lại lên trên
            // Loại bỏ các sự kiện trùng lặp (do JOIN với kế hoạch)
            const uniqueEvents = [];
            const seenEventIds = new Set();
            
            approvedEvents.forEach(event => {
                if (!seenEventIds.has(event.ID_DatLich)) {
                    seenEventIds.add(event.ID_DatLich);
                    uniqueEvents.push(event);
                } else {
                    // Nếu sự kiện đã tồn tại, cập nhật nếu có kế hoạch hoàn thành
                    const existingIndex = uniqueEvents.findIndex(e => e.ID_DatLich === event.ID_DatLich);
                    if (existingIndex !== -1) {
                        const existing = uniqueEvents[existingIndex];
                        // Ưu tiên kế hoạch đã hoàn thành
                        if (event.TrangThaiKeHoach === 'Hoàn thành' && existing.TrangThaiKeHoach !== 'Hoàn thành') {
                            uniqueEvents[existingIndex] = event;
                        }
                    }
                }
            });
            
            const sortedEvents = uniqueEvents.sort((a, b) => {
                const aCompleted = (a.TrangThaiThucTe === 'Hoàn thành') || (a.TrangThaiKeHoach === 'Hoàn thành');
                const bCompleted = (b.TrangThaiThucTe === 'Hoàn thành') || (b.TrangThaiKeHoach === 'Hoàn thành');
                
                // Nếu cả hai đều hoàn thành hoặc cả hai đều chưa hoàn thành, sắp xếp theo ngày bắt đầu (mới nhất lên trên)
                if (aCompleted === bCompleted) {
                    const dateA = new Date(a.NgayBatDau);
                    const dateB = new Date(b.NgayBatDau);
                    return dateB - dateA; // Mới nhất lên trên
                }
                
                // Sự kiện chưa hoàn thành lên trên (trả về -1), đã hoàn thành xuống dưới (trả về 1)
                return aCompleted ? 1 : -1;
            });
            
            let html = '';
            sortedEvents.forEach(event => {
                // Phân tích datetime để lấy cả ngày và giờ
                const startDateTime = new Date(event.NgayBatDau);
                const endDateTime = new Date(event.NgayKetThuc);
                
                const startDate = startDateTime.toLocaleDateString('vi-VN');
                const startTime = startDateTime.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                const endDate = endDateTime.toLocaleDateString('vi-VN');
                const endTime = endDateTime.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                
                // Định dạng ngày để lọc (YYYY-MM-DD)
                const startDateFilter = startDateTime.toISOString().split('T')[0];
                const endDateFilter = endDateTime.toISOString().split('T')[0];
                
                html += `
                    <div class="col-md-6 col-lg-5 mb-4 event-card" 
                         data-event-id="${event.ID_DatLich}"
                         data-event-name="${escapeHtml(event.TenSuKien).toLowerCase()}"
                         data-event-type="${escapeHtml(event.TenLoaiSK).toLowerCase()}"
                         data-location="${escapeHtml(event.TenDiaDiem).toLowerCase()}"
                         data-customer="${escapeHtml(event.TenKhachHang).toLowerCase()}"
                         data-start-date="${startDateFilter}"
                         data-end-date="${endDateFilter}">
                        <div class="planning-card">
                            <div class="event-header">
                                <!-- Status Badge - Hiển thị ở góc trên bên phải của event-header -->
                                <div class="plan-status-badge" id="plan-status-${event.ID_DatLich}">
                                    <!-- Status will be loaded here -->
                                </div>
                                <h5 class="mb-1" style="font-size: 1.3rem;">${escapeHtml(event.TenSuKien)}</h5>
                                <p class="mb-1" style="font-size: 0.9rem;">
                                    <i class="fas fa-calendar"></i>
                                    ${startDate} - ${endDate}
                                </p>
                                <p class="mb-0" style="font-size: 0.9rem;">
                                    <i class="fas fa-clock"></i>
                                    ${startTime} - ${endTime}
                                </p>
                            </div>
                            <div class="card-body" style="font-size: 0.9rem;">
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small class="text-muted" style="font-size: 0.75rem;">Địa điểm:</small><br>
                                        <strong style="font-size: 0.9rem;">${escapeHtml(event.TenDiaDiem)}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted" style="font-size: 0.75rem;">Loại sự kiện:</small><br>
                                        <strong style="font-size: 0.9rem;">${escapeHtml(event.TenLoaiSK)}</strong>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small class="text-muted" style="font-size: 0.75rem;">Số người:</small><br>
                                        <strong style="font-size: 0.9rem;">${Number(event.SoNguoiDuKien).toLocaleString()}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted" style="font-size: 0.75rem;">Ngân sách:</small><br>
                                        <strong style="font-size: 0.9rem;">${Number(event.NganSach).toLocaleString()} VNĐ</strong>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted" style="font-size: 0.75rem;">Khách hàng:</small><br>
                                    <strong style="font-size: 0.9rem;">${escapeHtml(event.TenKhachHang)}</strong><br>
                                    <small class="text-muted" style="font-size: 0.75rem;">${escapeHtml(event.SoDienThoai)}</small>
                                </div>
                                
                                <!-- Existing Plans for this Event - Hidden, will show in modal -->
                                <div class="mb-3" id="plans-${event.ID_DatLich}" style="display: none;">
                                    <div class="event-plans-list" id="event-plans-${event.ID_DatLich}">
                                        <!-- Plans will be loaded here -->
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 mt-auto">
                                    <!-- Buttons will be updated by loadEventPlans() -->
                                    <div class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            eventsList.innerHTML = html;
            
            // Tải kế hoạch cho từng sự kiện (sử dụng sortedEvents để giữ thứ tự đã sắp xếp)
            sortedEvents.forEach(event => {
                loadEventPlans(event.ID_DatLich);
            });
        }
        
        // Tải kế hoạch cho một sự kiện cụ thể (chỉ để cập nhật nút, không hiển thị trong card)
        function loadEventPlans(eventId) {
            const eventPlansContainer = document.getElementById(`event-plans-${eventId}`);
            if (!eventPlansContainer) return;
            
            console.log('Loading plans for event:', eventId);
            
            // Tải kế hoạch cho sự kiện cụ thể này từ backend
            fetch(`../src/controllers/event-planning.php?action=get_plans&event_id=${eventId}`, {
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                console.log('Plans data for event', eventId, ':', data);
                
                if (!data.success || !data.plans || data.plans.length === 0) {
                    // Không hiển thị gì trong card, chỉ cập nhật nút
                    eventPlansContainer.innerHTML = '';
                    
                    // Ẩn status badge nếu không có kế hoạch
                    const statusBadgeContainer = document.getElementById(`plan-status-${eventId}`);
                    if (statusBadgeContainer) {
                        statusBadgeContainer.innerHTML = '';
                    }
                    
                    // Cập nhật các nút thẻ sự kiện dựa trên việc kế hoạch có tồn tại không
                    updateEventCardButtons(eventId, false, false);
                    return;
                }
                
                // Lưu kế hoạch vào container (ẩn) để dùng khi xem
                const plan = data.plans[0];
                const allStepsCompleted = plan.TrangThai === 'Hoàn thành';
                
                // Xử lý cả định dạng ngày và datetime
                let startDate, endDate;
                try {
                    if (plan.NgayBatDau.includes(' ')) {
                        startDate = new Date(plan.NgayBatDau).toLocaleDateString('vi-VN');
                    } else {
                        startDate = new Date(plan.NgayBatDau).toLocaleDateString('vi-VN');
                    }
                    
                    if (plan.NgayKetThuc.includes(' ')) {
                        endDate = new Date(plan.NgayKetThuc).toLocaleDateString('vi-VN');
                    } else {
                        endDate = new Date(plan.NgayKetThuc).toLocaleDateString('vi-VN');
                    }
                } catch (e) {
                    startDate = plan.NgayBatDau || 'N/A';
                    endDate = plan.NgayKetThuc || 'N/A';
                }
                
                const statusClass = plan.TrangThai === 'Hoàn thành' ? 'success' : 
                                  plan.TrangThai === 'Đang thực hiện' ? 'warning' : 'secondary';
                
                let html = `
                    <div class="event-plan-item mb-3 p-3 border rounded shadow-sm bg-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <strong class="text-primary fs-6">${escapeHtml(plan.TenKeHoach)}</strong>
                                    <span class="badge bg-${statusClass} px-2 py-1">${escapeHtml(plan.TrangThai)}</span>
                                </div>
                                <div class="text-muted mb-2 small">
                                    <i class="fas fa-calendar text-success me-1"></i>
                                    ${startDate} - ${endDate}
                                </div>
                                ${plan.NoiDung ? `<div class="text-muted mb-2 small"><i class="fas fa-file-lines text-info me-1"></i> ${escapeHtml(plan.NoiDung)}</div>` : ''}
                                <div class="text-muted small">
                                    <i class="fas fa-user text-warning me-1"></i> 
                                    ${plan.TenNhanVien ? `
                                        <span class="fw-bold text-dark">${escapeHtml(plan.TenNhanVien)}</span>
                                        ${plan.ChucVu ? `<small class="text-muted ms-1">- ${escapeHtml(plan.ChucVu)}</small>` : ''}
                                    ` : '<span class="text-muted">Chưa phân công</span>'}
                                </div>
                            </div>
                            ${allStepsCompleted ? `
                            <div class="ms-3 d-flex flex-column align-items-end">
                                <span class="badge bg-success mb-2">Đã hoàn thành</span>
                            </div>
                            ` : `
                            <div class="ms-3 d-flex flex-column align-items-end">
                                <button class="btn btn-sm btn-outline-primary mb-2 px-3"
                                        onclick="editPlan(${plan.ID_KeHoach || ''})">
                                    <i class="fas fa-edit me-1"></i>Chỉnh sửa
                                </button>
                            </div>
                            `}
                        </div>
                    </div>`;
            
                eventPlansContainer.innerHTML = html;
                
                // Hiển thị trạng thái bên ngoài card
                const statusBadgeContainer = document.getElementById(`plan-status-${eventId}`);
                if (statusBadgeContainer) {
                    const statusBadgeClass = plan.TrangThai === 'Hoàn thành' ? 'success' : 
                                           plan.TrangThai === 'Đang thực hiện' ? 'warning' : 'secondary';
                    const statusIcon = plan.TrangThai === 'Hoàn thành' ? 'check-circle' : 
                                     plan.TrangThai === 'Đang thực hiện' ? 'spinner' : 'clock';
                    const statusIconClass = plan.TrangThai === 'Đang thực hiện' ? 'fa-spin' : '';
                    statusBadgeContainer.innerHTML = `
                        <span class="badge bg-${statusBadgeClass}">
                            <i class="fas fa-${statusIcon} ${statusIconClass} me-1"></i>
                            ${escapeHtml(plan.TrangThai)}
                        </span>
                    `;
                }
                
                // Cập nhật các nút thẻ sự kiện dựa trên trạng thái kế hoạch
                updateEventCardButtons(eventId, true, allStepsCompleted);
            })
            .catch(error => {
                console.error('Error loading event plans:', error);
                eventPlansContainer.innerHTML = '';
                
                // Ẩn status badge khi có lỗi
                const statusBadgeContainer = document.getElementById(`plan-status-${eventId}`);
                if (statusBadgeContainer) {
                    statusBadgeContainer.innerHTML = '';
                }
                
                updateEventCardButtons(eventId, false, false);
            });
        }
        
        // Hàm xem kế hoạch trong modal
        function viewEventPlans(eventId) {
            const modalContent = document.getElementById('viewEventPlansContent');
            const modalTitle = document.querySelector('#viewEventPlansModal .modal-title');
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            
            if (!modalContent) {
                alert('Không tìm thấy modal');
                return;
            }
            
            // Cập nhật title modal với tên sự kiện
            if (modalTitle && event) {
                modalTitle.innerHTML = `<i class="fas fa-list-check"></i> Kế hoạch: ${escapeHtml(event.TenSuKien)}`;
            }
            
            // Hiển thị loading
            modalContent.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-2">Đang tải kế hoạch...</p></div>';
            
            // Mở modal trước
            const modal = new bootstrap.Modal(document.getElementById('viewEventPlansModal'));
            modal.show();
            
            // Tải kế hoạch từ backend
            fetch(`../src/controllers/event-planning.php?action=get_plans&event_id=${eventId}`, {
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.plans || data.plans.length === 0) {
                    modalContent.innerHTML = '<div class="text-center py-4"><p class="text-muted">Chưa có kế hoạch cho sự kiện này</p></div>';
                    return;
                }
                
                const plan = data.plans[0];
                const allStepsCompleted = plan.TrangThai === 'Hoàn thành';
                
                // Xử lý định dạng ngày
                let startDate, endDate;
                try {
                    if (plan.NgayBatDau.includes(' ')) {
                        startDate = new Date(plan.NgayBatDau).toLocaleDateString('vi-VN');
                    } else {
                        startDate = new Date(plan.NgayBatDau).toLocaleDateString('vi-VN');
                    }
                    
                    if (plan.NgayKetThuc.includes(' ')) {
                        endDate = new Date(plan.NgayKetThuc).toLocaleDateString('vi-VN');
                    } else {
                        endDate = new Date(plan.NgayKetThuc).toLocaleDateString('vi-VN');
                    }
                } catch (e) {
                    startDate = plan.NgayBatDau || 'N/A';
                    endDate = plan.NgayKetThuc || 'N/A';
                }
                
                const statusClass = plan.TrangThai === 'Hoàn thành' ? 'success' : 
                                  plan.TrangThai === 'Đang thực hiện' ? 'warning' : 'secondary';
                
                let html = `
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-2">${escapeHtml(plan.TenKeHoach)}</h5>
                                    <span class="badge bg-${statusClass} fs-6">${escapeHtml(plan.TrangThai)}</span>
                                </div>
                                ${!allStepsCompleted ? `
                                <button class="btn btn-sm btn-outline-primary" onclick="manageSteps(${eventId}, '${escapeHtml(plan.TenKeHoach)}')">
                                    <i class="fas fa-cogs me-1"></i> Quản lý bước
                                </button>
                                ` : ''}
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Thời gian:</small>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-calendar text-success"></i>
                                    <span>${startDate} - ${endDate}</span>
                                </div>
                            </div>
                            
                            ${plan.NoiDung ? `
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Nội dung:</small>
                                <p class="mb-0">${escapeHtml(plan.NoiDung)}</p>
                            </div>
                            ` : ''}
                            
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Nhân viên phụ trách:</small>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-user text-warning"></i>
                                    ${plan.TenNhanVien ? `
                                        <span class="fw-bold">${escapeHtml(plan.TenNhanVien)}</span>
                                        ${plan.ChucVu ? `<small class="text-muted">- ${escapeHtml(plan.ChucVu)}</small>` : ''}
                                    ` : '<span class="text-muted">Chưa phân công</span>'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                modalContent.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading event plans:', error);
                modalContent.innerHTML = '<div class="text-center py-4"><p class="text-danger">Lỗi khi tải kế hoạch</p></div>';
            });
        }
        
        // Cập nhật các nút thẻ sự kiện dựa trên việc kế hoạch tồn tại và trạng thái hoàn thành
        function updateEventCardButtons(eventId, hasPlan, allStepsCompleted) {
            const eventCard = document.querySelector(`.event-card[data-event-id="${eventId}"]`);
            if (!eventCard) return;
            
            const buttonContainer = eventCard.querySelector('.d-grid.gap-2');
            if (!buttonContainer) return;
            
            // Đảm bảo button container có mt-auto để đẩy xuống dưới cùng
            if (!buttonContainer.classList.contains('mt-auto')) {
                buttonContainer.classList.add('mt-auto');
            }
            
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (!event) return;
            
            // Nếu sự kiện đã hoàn thành, hiển thị chế độ chỉ đọc
            if (event.TrangThaiSuKien === 'Hoàn thành') {
                buttonContainer.innerHTML = `
                    <button class="btn btn-outline-info btn-sm" onclick="viewEventPlans(${eventId})">
                        <i class="fas fa-eye me-1"></i> Xem kế hoạch
                    </button>
                `;
                return;
            }
            
            // Nếu kế hoạch tồn tại, hiển thị nút xem kế hoạch
            if (hasPlan) {
                buttonContainer.innerHTML = `
                    <button class="btn btn-outline-info btn-sm" onclick="viewEventPlans(${eventId})">
                        <i class="fas fa-eye me-1"></i> Xem kế hoạch
                    </button>
                    ${!allStepsCompleted ? `
                    <button class="btn btn-outline-primary btn-sm mt-2" onclick="manageSteps(${eventId}, '${escapeHtml(event.TenSuKien)}')">
                        <i class="fas fa-cogs me-1"></i> Quản lý bước
                    </button>
                    ` : ''}
                `;
                return;
            }
            
            // Nếu không có kế hoạch, hiển thị nút tạo kế hoạch
            if (!hasPlan) {
                buttonContainer.innerHTML = `
                    <button class="btn btn-primary" onclick="createPlan(${eventId}, '${escapeHtml(event.TenSuKien)}')">
                        <i class="fas fa-plus"></i> Tạo kế hoạch
                    </button>
                `;
            }
        }
        
        // Hiển thị kế hoạch trong giao diện
        function displayPlans() {
            const existingPlansSection = document.getElementById('existingPlansSection');
            const existingPlansList = document.getElementById('existingPlansList');
            
            // Luôn hiển thị phần nếu có kế hoạch
            if (existingPlans.length > 0) {
                existingPlansSection.style.display = 'block';
            } else {
                existingPlansSection.style.display = 'none';
                return;
            }
            
            let html = '';
            existingPlans.forEach(plan => {
                // Xử lý cả định dạng ngày và datetime
                let startDate, endDate;
                try {
                    if (plan.NgayBatDau.includes(' ')) {
                        // Đây là chuỗi datetime
                        startDate = new Date(plan.NgayBatDau).toLocaleDateString('vi-VN');
                    } else {
                        // Đây là chuỗi ngày
                        startDate = new Date(plan.NgayBatDau).toLocaleDateString('vi-VN');
                    }
                    
                    if (plan.NgayKetThuc.includes(' ')) {
                        // Đây là chuỗi datetime
                        endDate = new Date(plan.NgayKetThuc).toLocaleDateString('vi-VN');
                    } else {
                        // Đây là chuỗi ngày
                        endDate = new Date(plan.NgayKetThuc).toLocaleDateString('vi-VN');
                    }
                } catch (e) {
                    startDate = plan.NgayBatDau || 'N/A';
                    endDate = plan.NgayKetThuc || 'N/A';
                }
                
                const statusClass = plan.TrangThai === 'Hoàn thành' ? 'success' : 
                                  plan.TrangThai === 'Đang thực hiện' ? 'warning' : 'secondary';
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="existing-plans-card">
                            <h6>${escapeHtml(plan.ten_kehoach)}</h6>
                            <p class="card-text">${escapeHtml(plan.NoiDung)}</p>
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i>
                                ${startDate} - ${endDate}
                            </small>
                            <div class="mt-2">
                                <span class="badge bg-${statusClass}">
                                    ${escapeHtml(plan.TrangThai)}
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            existingPlansList.innerHTML = html;
        }

        // Mở modal chỉnh sửa kế hoạch với dữ liệu
        function openEditPlanModal(planData) {
            try {
                // Phân tích khi được gọi từ inline html (JSON đã escape)
                if (typeof planData === 'string') {
                    planData = JSON.parse(planData);
                }
            } catch (e) {}

            const modalEl = document.getElementById('editPlanModal');
            document.getElementById('editPlanId').value = planData.id || planData.ID_KeHoach || '';
            document.getElementById('editPlanName').value = planData.name || planData.ten_kehoach || '';
            document.getElementById('editPlanContent').value = planData.content || planData.NoiDung || '';

            // Tách datetime thành ngày và giờ - xử lý nhiều định dạng
            let startDate = '';
            let startTime = '08:00';
            let endDate = '';
            let endTime = '17:00';
            
            const start = (planData.start || planData.NgayBatDau || '').toString().trim();
            const end = (planData.end || planData.NgayKetThuc || '').toString().trim();
            
            if (start) {
                if (start.includes(' ')) {
                    // Định dạng: "YYYY-MM-DD HH:MM:SS"
                    const parts = start.split(' ');
                    startDate = parts[0];
                    startTime = parts[1] ? parts[1].substring(0, 5) : '08:00'; // Chỉ lấy HH:MM
                } else if (start.includes('T')) {
                    // Định dạng: "YYYY-MM-DDTHH:MM:SS"
                    const parts = start.split('T');
                    startDate = parts[0];
                    startTime = parts[1] ? parts[1].substring(0, 5) : '08:00'; // Chỉ lấy HH:MM
                } else {
                    // Định dạng: "YYYY-MM-DD"
                    startDate = start;
                    startTime = '08:00';
                }
            }
            
            if (end) {
                if (end.includes(' ')) {
                    // Định dạng: "YYYY-MM-DD HH:MM:SS"
                    const parts = end.split(' ');
                    endDate = parts[0];
                    endTime = parts[1] ? parts[1].substring(0, 5) : '17:00'; // Chỉ lấy HH:MM
                } else if (end.includes('T')) {
                    // Định dạng: "YYYY-MM-DDTHH:MM:SS"
                    const parts = end.split('T');
                    endDate = parts[0];
                    endTime = parts[1] ? parts[1].substring(0, 5) : '17:00'; // Chỉ lấy HH:MM
                } else {
                    // Định dạng: "YYYY-MM-DD"
                    endDate = end;
                    endTime = '17:00';
                }
            }
            
            document.getElementById('editStartDate').value = startDate;
            document.getElementById('editStartTime').value = startTime;
            document.getElementById('editEndDate').value = endDate;
            document.getElementById('editEndTime').value = endTime;
            document.getElementById('editStatus').value = planData.status || planData.TrangThai || 'Chưa bắt đầu';

            // Tải các tùy chọn nhân viên vào select chỉnh sửa, sau đó chọn trước nếu có
            loadStaffOptions().then(() => {
                const staffSelect = document.getElementById('editAssignedStaff');
                if (planData.ID_NhanVien || planData.ID_NhanVien) {
                    staffSelect.value = planData.ID_NhanVien || planData.ID_NhanVien;
                }
            }).catch(() => {});

            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }

        function editPlan(planId) {
            // Kiểm tra xem sự kiện của kế hoạch đã hoàn thành chưa
            const plan = existingPlans.find(p => (p.ID_KeHoach == planId || p.id == planId));
            if (plan) {
                const event = approvedEvents.find(e => e.ID_DatLich == plan.ID_DatLich);
                if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                    alert('Sự kiện đã hoàn thành, không thể chỉnh sửa kế hoạch');
                    return;
                }
            }
            
            console.log('Editing plan:', planId);
            
            // Lấy dữ liệu kế hoạch từ database
            fetch(`../src/controllers/event-planning.php?action=get_plan&plan_id=${planId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.plan) {
                        const plan = data.plan;
                        console.log('Plan data loaded:', plan);
                        
                        // Điền dữ liệu vào modal chỉnh sửa kế hoạch
                        document.getElementById('editPlanId').value = plan.ID_KeHoach || '';
                        document.getElementById('editPlanName').value = plan.TenKeHoach || '';
                        document.getElementById('editPlanContent').value = plan.NoiDung || '';
                        
                        // Tách datetime - xử lý nhiều định dạng
                        let startDate = '';
                        let startTime = '08:00';
                        let endDate = '';
                        let endTime = '17:00';
                        
                        if (plan.NgayBatDau) {
                            // Handle formats: "YYYY-MM-DD HH:MM:SS", "YYYY-MM-DDTHH:MM:SS", "YYYY-MM-DD"
                            const startDateTime = plan.NgayBatDau.toString().trim();
                            if (startDateTime.includes(' ')) {
                                // Định dạng: "YYYY-MM-DD HH:MM:SS"
                                const parts = startDateTime.split(' ');
                                startDate = parts[0];
                                startTime = parts[1] ? parts[1].substring(0, 5) : '08:00'; // Chỉ lấy HH:MM
                            } else if (startDateTime.includes('T')) {
                                // Định dạng: "YYYY-MM-DDTHH:MM:SS"
                                const parts = startDateTime.split('T');
                                startDate = parts[0];
                                startTime = parts[1] ? parts[1].substring(0, 5) : '08:00'; // Chỉ lấy HH:MM
                            } else {
                                // Định dạng: "YYYY-MM-DD"
                                startDate = startDateTime;
                                startTime = '08:00';
                            }
                        }
                        
                        if (plan.NgayKetThuc) {
                            // Handle formats: "YYYY-MM-DD HH:MM:SS", "YYYY-MM-DDTHH:MM:SS", "YYYY-MM-DD"
                            const endDateTime = plan.NgayKetThuc.toString().trim();
                            if (endDateTime.includes(' ')) {
                                // Định dạng: "YYYY-MM-DD HH:MM:SS"
                                const parts = endDateTime.split(' ');
                                endDate = parts[0];
                                endTime = parts[1] ? parts[1].substring(0, 5) : '17:00'; // Chỉ lấy HH:MM
                            } else if (endDateTime.includes('T')) {
                                // Định dạng: "YYYY-MM-DDTHH:MM:SS"
                                const parts = endDateTime.split('T');
                                endDate = parts[0];
                                endTime = parts[1] ? parts[1].substring(0, 5) : '17:00'; // Chỉ lấy HH:MM
                            } else {
                                // Định dạng: "YYYY-MM-DD"
                                endDate = endDateTime;
                                endTime = '17:00';
                            }
                        }
                        
                        document.getElementById('editStartDate').value = startDate;
                        document.getElementById('editStartTime').value = startTime;
                        document.getElementById('editEndDate').value = endDate;
                        document.getElementById('editEndTime').value = endTime;
                        document.getElementById('editStatus').value = plan.TrangThai || 'Chưa bắt đầu';
                        
                        // Load staff options and set selected staff
                        loadStaffOptions().then(() => {
                            if (plan.ID_NhanVien) {
                                document.getElementById('editAssignedStaff').value = plan.ID_NhanVien;
                            }
                        });
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('editPlanModal'));
                        modal.show();
                    } else {
                        alert('Không thể tải thông tin kế hoạch: ' + (data.error || 'Lỗi không xác định'));
                    }
                })
                .catch(error => {
                    console.error('Error loading plan:', error);
                    alert('Có lỗi xảy ra khi tải thông tin kế hoạch');
                });
        }

        // Đọc data-* từ nút để mở modal an toàn
        function openEditPlanFromButton(btn) {
            const planData = {
                id: btn.getAttribute('data-plan-id') || '',
                name: btn.getAttribute('data-plan-name') || '',
                content: btn.getAttribute('data-plan-content') || '',
                start: btn.getAttribute('data-start') || '',
                end: btn.getAttribute('data-end') || '',
                status: btn.getAttribute('data-status') || 'Chưa bắt đầu'
            };
            openEditPlanModal(planData);
        }

        // Gửi chỉnh sửa kế hoạch
        function submitEditPlan() {
            const planId = document.getElementById('editPlanId').value;
            const name = document.getElementById('editPlanName').value.trim();
            const content = document.getElementById('editPlanContent').value.trim();
            const startDate = document.getElementById('editStartDate').value;
            const startTime = document.getElementById('editStartTime').value;
            const endDate = document.getElementById('editEndDate').value;
            const endTime = document.getElementById('editEndTime').value;
            const status = document.getElementById('editStatus').value;

            if (!planId || !name || !content || !startDate || !startTime || !endDate || !endTime) {
                alert('Vui lòng điền đầy đủ thông tin');
                return;
            }

            const startDateTime = `${startDate} ${startTime}`;
            const endDateTime = `${endDate} ${endTime}`;
            if (new Date(endDateTime) <= new Date(startDateTime)) {
                alert('Thời gian kết thúc phải sau thời gian bắt đầu');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_plan');
            formData.append('planId', planId);
            formData.append('planName', name);
            formData.append('planContent', content);
            formData.append('startDateTime', startDateTime);
            formData.append('endDateTime', endDateTime);
            formData.append('status', status);
            formData.append('managerId', document.getElementById('editAssignedStaff').value || '');

            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    // Cập nhật trong danh sách local
                    const idx = existingPlans.findIndex(p => (p.ID_KeHoach == planId || p.id == planId));
                    if (idx !== -1) {
                        existingPlans[idx].ten_kehoach = name;
                        existingPlans[idx].NoiDung = content;
                        existingPlans[idx].NgayBatDau = startDateTime;
                        existingPlans[idx].NgayKetThuc = endDateTime;
                        existingPlans[idx].TrangThai = status;
                        // cũng cập nhật tên/id nhân viên được phân công
                        existingPlans[idx].ID_NhanVien = document.getElementById('editAssignedStaff').value || null;
                        const staffName = getStaffName(existingPlans[idx].ID_NhanVien);
                        if (staffName && staffName !== 'Chưa phân công') {
                            existingPlans[idx].TenNhanVien = staffName;
                        }
                    }
                    displayPlans();
                    updateStatistics();
                    approvedEvents.forEach(ev => loadEventPlans(ev.ID_DatLich));

                    const modal = bootstrap.Modal.getInstance(document.getElementById('editPlanModal'));
                    modal.hide();
                    alert('Cập nhật kế hoạch thành công');
                } else {
                    alert('Lỗi: ' + (data.error || 'Không xác định'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Có lỗi xảy ra khi cập nhật kế hoạch');
            });
        }
        
        // Cập nhật thống kê
        function updateStatistics() {
            document.getElementById('approvedEventsCount').textContent = approvedEvents.length;
            document.getElementById('totalPlansCount').textContent = existingPlans.length;
            
            const inProgressCount = existingPlans.filter(p => p.TrangThai === 'Đang thực hiện').length;
            const completedCount = existingPlans.filter(p => p.TrangThai === 'Hoàn thành').length;
            
            document.getElementById('inProgressPlansCount').textContent = inProgressCount;
            document.getElementById('completedPlansCount').textContent = completedCount;
        }
        
        // Hiển thị thông báo lỗi cho sự kiện
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
        
        // Hàm tiện ích để escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function createPlan(eventId, eventName) {
            // Kiểm tra xem sự kiện đã hoàn thành chưa
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể tạo kế hoạch mới');
                return;
            }
            
            // Kiểm tra xem sự kiện đã có kế hoạch chưa
            fetch(`../src/controllers/event-planning.php?action=get_plans&event_id=${eventId}`, {
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.plans && data.plans.length > 0) {
                    alert('Sự kiện này đã có kế hoạch. Mỗi sự kiện chỉ được tạo 1 kế hoạch.');
                    return;
                }
                
                console.log('Creating plan for eventId:', eventId, 'eventName:', eventName);
                document.getElementById('eventId').value = eventId;
                document.querySelector('#createPlanModal .modal-title').innerHTML = 
                    '<i class="fas fa-plus-circle"></i> Tạo kế hoạch cho: ' + eventName;
                
                // Load staff options
                loadStaffOptions();
                
                // Lấy ngày từ sự kiện nếu có
                let startDateValue = '';
                let startTimeValue = '';
                let endDateValue = '';
                let endTimeValue = '';
                let minDate = '';
                let maxDate = '';
                
                if (event && event.NgayBatDau && event.NgayKetThuc) {
                    // Parse ngày từ sự kiện
                    const eventStartDate = new Date(event.NgayBatDau);
                    const eventEndDate = new Date(event.NgayKetThuc);
                    
                    // Set giá trị mặc định từ sự kiện
                    startDateValue = eventStartDate.toISOString().split('T')[0];
                    endDateValue = eventEndDate.toISOString().split('T')[0];
                    
                    // Lấy giờ từ sự kiện nếu có
                    if (event.NgayBatDau.includes(' ') || event.NgayBatDau.includes('T')) {
                        const timePart = event.NgayBatDau.includes(' ') 
                            ? event.NgayBatDau.split(' ')[1] 
                            : event.NgayBatDau.split('T')[1];
                        if (timePart) {
                            startTimeValue = timePart.substring(0, 5); // HH:MM
                        }
                    }
                    
                    if (event.NgayKetThuc.includes(' ') || event.NgayKetThuc.includes('T')) {
                        const timePart = event.NgayKetThuc.includes(' ') 
                            ? event.NgayKetThuc.split(' ')[1] 
                            : event.NgayKetThuc.split('T')[1];
                        if (timePart) {
                            endTimeValue = timePart.substring(0, 5); // HH:MM
                        }
                    }
                    
                    // Set min và max date từ sự kiện (cho phép 1 ngày buffer)
                    const minDateObj = new Date(eventStartDate);
                    minDateObj.setDate(minDateObj.getDate() - 1);
                    minDate = minDateObj.toISOString().split('T')[0];
                    
                    const maxDateObj = new Date(eventEndDate);
                    maxDateObj.setDate(maxDateObj.getDate() + 1);
                    maxDate = maxDateObj.toISOString().split('T')[0];
                } else {
                    // Nếu không có sự kiện, dùng ngày hôm nay
                    const today = new Date().toISOString().split('T')[0];
                    startDateValue = today;
                    endDateValue = today;
                }
                
                // Set giá trị cho form
                const startDateInput = document.getElementById('startDate');
                const endDateInput = document.getElementById('endDate');
                const startTimeInput = document.getElementById('startTime');
                const endTimeInput = document.getElementById('endTime');
                
                startDateInput.value = startDateValue;
                startTimeInput.value = startTimeValue;
                endDateInput.value = endDateValue;
                endTimeInput.value = endTimeValue;
                
                // Set ràng buộc min và max
                if (minDate) {
                    startDateInput.setAttribute('min', minDate);
                    endDateInput.setAttribute('min', minDate);
                }
                if (maxDate) {
                    startDateInput.setAttribute('max', maxDate);
                    endDateInput.setAttribute('max', maxDate);
                }
                
                // Xóa các trường form khác
                document.getElementById('planName').value = '';
                document.getElementById('planContent').value = '';
                document.getElementById('assignedStaff').value = '';
                
                const modal = new bootstrap.Modal(document.getElementById('createPlanModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error checking existing plans:', error);
                alert('Có lỗi xảy ra khi kiểm tra kế hoạch hiện có');
            });
        }

        function loadStaffOptions() {
            return fetch('../src/controllers/event-planning.php?action=get_staff', {
                credentials: 'same-origin'
            })
                .then(handleFetchResponse)
                .then(data => {
                    if (data.success) {
                        const selects = [];
                        const s1 = document.getElementById('assignedStaff');
                        const s2 = document.getElementById('editAssignedStaff');
                        if (s1) selects.push(s1);
                        if (s2) selects.push(s2);
                        selects.forEach(select => {
                            select.innerHTML = '<option value="">Chọn nhân viên</option>';
                            data.staff.forEach(staff => {
                                select.innerHTML += `<option value="${staff.ID_NhanVien}">${staff.HoTen} - ${staff.ChucVu}</option>`;
                            });
                        });
                    }
                    return data.staff || [];
                })
                .catch(error => {
                    console.error('Error loading staff:', error);
                    return [];
                });
        }

        function savePlan() {
            const form = document.getElementById('createPlanForm');
            const formData = new FormData(form);
            formData.append('action', 'create_plan');
            
            // Kiểm tra các trường bắt buộc
            const eventId = formData.get('eventId');
            const planName = formData.get('planName');
            const planContent = formData.get('planContent');
            const startDate = formData.get('startDate');
            const startTime = formData.get('startTime');
            const endDate = formData.get('endDate');
            const endTime = formData.get('endTime');
            
            if (!eventId || !planName || !planContent || !startDate || !startTime || !endDate || !endTime) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                return;
            }
            
            // Combine date and time
            const startDateTime = startDate + ' ' + startTime;
            const endDateTime = endDate + ' ' + endTime;
            
            // Update form data with combined datetime
            formData.set('startDateTime', startDateTime);
            formData.set('endDateTime', endDateTime);
            
            // Xác thực ngày và giờ
            const startDateObj = new Date(startDateTime);
            const endDateObj = new Date(endDateTime);
            
            if (endDateObj <= startDateObj) {
                alert('Thời gian kết thúc phải sau thời gian bắt đầu');
                return;
            }

            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Tạo kế hoạch thành công! Bây giờ bạn có thể thêm các bước thực hiện.');
                    
                    // Thêm kế hoạch mới vào mảng kế hoạch hiện có
                    const newPlan = {
                        ID_KeHoach: data.planId || Date.now(), // Sử dụng ID trả về hoặc giá trị dự phòng
                        ten_kehoach: formData.get('planName'),
                        NoiDung: formData.get('planContent'),
                        NgayBatDau: formData.get('startDateTime'),
                        NgayKetThuc: formData.get('endDateTime'),
                        TrangThai: 'Chưa thực hiện',
                        ID_DatLich: formData.get('eventId'), // Sử dụng ID_DatLich để khớp với truy vấn
                        ten_nhanvien: getStaffName(formData.get('assignedStaff'))
                    };
                    
                    existingPlans.unshift(newPlan); // Thêm vào đầu mảng
                    
                    // Cập nhật hiển thị ngay lập tức
                    displayPlans();
                    updateStatistics();
                    
                    // Cập nhật kế hoạch cho sự kiện cụ thể
                    loadEventPlans(formData.get('eventId'));
                    
                    // Đóng modal tạo kế hoạch
                    const createModal = bootstrap.Modal.getInstance(document.getElementById('createPlanModal'));
                    createModal.hide();
                    
                    // Lấy thông tin sự kiện để quản lý bước
                    const eventId = document.getElementById('eventId').value;
                    const eventName = document.querySelector('#createPlanModal .modal-title').textContent.replace('Tạo kế hoạch cho: ', '');
                    
                    // Mở modal quản lý bước
                    setTimeout(() => {
                        manageSteps(eventId, eventName);
                    }, 500);
                    
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tạo kế hoạch: ' + error.message);
            });
        }

        // Đặt lại form bước
        function resetStepForm() {
            document.getElementById('addStepForm').reset();
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('stepStartDate').value = today;
            document.getElementById('stepStartTime').value = '08:00';
            document.getElementById('stepEndDate').value = today;
            document.getElementById('stepEndTime').value = '17:00';
            
            // Xóa lựa chọn nhân viên checkbox
            const stepStaffCheckboxes = document.querySelectorAll('#stepStaffCheckboxes input[type="checkbox"]');
            stepStaffCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        // Hàm helper để lấy tên nhân viên theo ID
        function getStaffName(staffId) {
            if (!staffId) return 'Chưa phân công';
            
            // Thử tìm tên nhân viên từ các tùy chọn select
            const staffSelect = document.getElementById('assignedStaff');
            if (staffSelect) {
                const selectedOption = staffSelect.querySelector(`option[value="${staffId}"]`);
                if (selectedOption) {
                    return selectedOption.textContent.split(' - ')[0]; // Chỉ lấy phần tên
                }
            }
            
            return 'Nhân viên #' + staffId;
        }

        function manageSteps(eventId, eventName) {
            document.getElementById('stepEventId').value = eventId;
            
            // Kiểm tra xem sự kiện đã hoàn thành chưa
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            const isEventCompleted = event && event.TrangThaiSuKien === 'Hoàn thành';
            
            // Kiểm tra xem tất cả các bước đã hoàn thành chưa bằng cách tải các bước trước
            fetch(`../src/controllers/event-planning.php?action=get_event_steps&event_id=${eventId}`, {
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                let allStepsCompleted = false;
                if (data.success && data.steps && data.steps.length > 0) {
                    const totalSteps = data.steps.length;
                    const completedSteps = data.steps.filter(step => step.TrangThai === 'Hoàn thành').length;
                    allStepsCompleted = totalSteps > 0 && completedSteps === totalSteps;
                }
                
                const isReadOnly = isEventCompleted || allStepsCompleted;
                
                if (isReadOnly) {
                    const reason = isEventCompleted ? 'Sự kiện đã hoàn thành' : 'Tất cả các bước đã hoàn thành';
                    document.querySelector('#manageStepsModal .modal-title').innerHTML = 
                        '<i class="fas fa-eye"></i> Xem bước thực hiện: ' + eventName + ' <span class="badge bg-success">' + reason + '</span>';
                } else {
                    document.querySelector('#manageStepsModal .modal-title').innerHTML = 
                        '<i class="fas fa-cogs"></i> Quản lý bước thực hiện: ' + eventName;
                }
                
                // Tải các bước hiện có
                loadSteps(eventId, isReadOnly);
                
                // Tải các tùy chọn nhân viên (chỉ khi không phải chế độ chỉ đọc)
                if (!isReadOnly) {
                    loadStaffOptionsForSteps();
                    
                    // Đặt ngày và giờ mặc định
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('stepStartDate').value = today;
                    document.getElementById('stepStartTime').value = '08:00';
                    document.getElementById('stepEndDate').value = today;
                    document.getElementById('stepEndTime').value = '17:00';
                    
                    // Hiển thị form thêm bước
                    const addStepFormContainer = document.getElementById('addStepFormContainer');
                    if (addStepFormContainer) {
                        addStepFormContainer.style.display = '';
                    }
                } else {
                    // Ẩn form thêm bước nếu ở chế độ chỉ đọc
                    const addStepFormContainer = document.getElementById('addStepFormContainer');
                    if (addStepFormContainer) {
                        addStepFormContainer.style.display = 'none';
                    }
                }
                
                const modal = new bootstrap.Modal(document.getElementById('manageStepsModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error checking steps:', error);
                // Dự phòng để tải các bước bình thường
                loadSteps(eventId, isEventCompleted);
                
                if (!isEventCompleted) {
                    loadStaffOptionsForSteps();
                    const addStepFormContainer = document.getElementById('addStepFormContainer');
                    if (addStepFormContainer) {
                        addStepFormContainer.style.display = '';
                    }
                } else {
                    const addStepFormContainer = document.getElementById('addStepFormContainer');
                    if (addStepFormContainer) {
                        addStepFormContainer.style.display = 'none';
                    }
                }
                
                const modal = new bootstrap.Modal(document.getElementById('manageStepsModal'));
                modal.show();
            });
        }

        function loadSteps(eventId, isReadOnly = false) {
            console.log('Loading steps for event:', eventId, 'isReadOnly:', isReadOnly);
            fetch(`../src/controllers/event-planning.php?action=get_event_steps&event_id=${eventId}`, {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Steps data:', data);
                    if (data.success && data.steps && data.steps.length > 0) {
                        // Tính phần trăm hoàn thành (dựa trên nhân viên đã END)
                        const totalSteps = data.steps.length;
                        let totalStaffTasks = 0;
                        let completedStaffTasks = 0;
                        
                        data.steps.forEach(step => {
                            if (step.assignedStaff && step.assignedStaff.length > 0) {
                                step.assignedStaff.forEach(staff => {
                                    totalStaffTasks++;
                                    if (staff.ThoiGianKetThucThucTe !== null) {
                                        completedStaffTasks++;
                                    }
                                });
                            }
                        });
                        
                        const completionPercentage = totalStaffTasks > 0 ? Math.round((completedStaffTasks / totalStaffTasks) * 100) : 0;
                        const completedSteps = data.steps.filter(step => {
                            if (step.assignedStaff && step.assignedStaff.length > 0) {
                                return step.assignedStaff.every(staff => staff.ThoiGianKetThucThucTe !== null);
                            }
                            return step.TrangThai === 'Hoàn thành';
                        }).length;
                        // Kiểm tra xem tất cả các bước đã hoàn thành chưa - nếu có, đặt isReadOnly thành true
                        const allStepsCompleted = totalSteps > 0 && completedSteps === totalSteps;
                        if (allStepsCompleted) {
                            isReadOnly = true;
                        }
                        
                        // Xây dựng HTML thống kê (không dùng progress bar, bỏ "Đang làm")
                        let html = `
                            <div class="mb-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">
                                                <i class="fas fa-tasks me-2 text-primary"></i>
                                                <strong>Thống kê nhiệm vụ</strong>
                                            </h6>
                                            <span class="badge bg-primary fs-6">${completionPercentage}%</span>
                                        </div>
                                        <div class="mt-2 d-flex justify-content-between text-muted small">
                                            <span><i class="fas fa-check-circle text-success me-1"></i>Hoàn thành: ${completedStaffTasks}/${totalStaffTasks}</span>
                                            <span><i class="fas fa-clock text-secondary me-1"></i>Chưa làm: ${totalStaffTasks - completedStaffTasks}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        html += '<div class="timeline">';
                        data.steps.forEach((step, index) => {
                            const statusClass = step.TrangThai === 'Hoàn thành' ? 'success' : 'secondary';
                            
                            // Kiểm tra xem có nhân viên nào đã START chưa (để ẩn nút chỉnh sửa/xóa)
                            const hasStartedStaff = step.assignedStaff && step.assignedStaff.length > 0 && 
                                                   step.assignedStaff.some(s => s.ThoiGianBatDauThucTe !== null);
                            
                            // Kiểm tra xem bước đã được công bố chưa
                            const isPublished = step.DaCongBo == 1 || step.DaCongBo === true;
                            
                            // Tính KPI trung bình cho bước (nếu có nhân viên đã END)
                            let avgKPI = null;
                            let kpiCount = 0;
                            if (step.assignedStaff && step.assignedStaff.length > 0) {
                                step.assignedStaff.forEach(staff => {
                                    if (staff.KPI !== null) {
                                        avgKPI = (avgKPI === null ? 0 : avgKPI) + parseFloat(staff.KPI);
                                        kpiCount++;
                                    }
                                });
                                if (kpiCount > 0) {
                                    avgKPI = avgKPI / kpiCount;
                                }
                            }
                            
                            html += `
                                <div class="timeline-item">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <span class="badge bg-primary me-2">Bước ${index + 1}</span>
                                                    ${step.TenBuoc}
                                                </h6>
                                                <span class="badge bg-${statusClass}">${step.TrangThai}</span>
                                            </div>
                                        </div>
                                        <div class="card-body" style="padding: 1.5rem;">
                                            ${step.MoTa ? `
                                            <div class="mb-3">
                                                <h6 class="text-primary mb-2" style="font-size: 0.95rem; font-weight: 600;"><i class="fas fa-info-circle me-2"></i> Mô tả</h6>
                                                <p class="text-muted mb-0" style="font-size: 0.9rem; line-height: 1.6;">${escapeHtml(step.MoTa)}</p>
                                            </div>
                                            ` : ''}
                                            
                                            <div class="d-flex gap-3 mb-3">
                                                <div class="flex-grow-1">
                                                    <div class="mb-3">
                                                        <h6 class="text-success mb-2" style="font-size: 0.95rem; font-weight: 600;"><i class="fas fa-calendar-alt me-2"></i> Thời gian</h6>
                                                        <div class="bg-light p-3 rounded border">
                                                            <p class="mb-2" style="font-size: 0.9rem;"><strong style="color: #2c3e50;">Bắt đầu:</strong><br><span style="color: #495057;">${new Date(step.NgayBatDau).toLocaleString('vi-VN')}</span></p>
                                                            <p class="mb-0" style="font-size: 0.9rem;"><strong style="color: #2c3e50;">Kết thúc:</strong><br><span style="color: #495057;">${new Date(step.NgayKetThuc).toLocaleString('vi-VN')}</span></p>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="text-info mb-2" style="font-size: 0.95rem; font-weight: 600;"><i class="fas fa-users me-2"></i> Nhân viên</h6>
                                                            ${step.assignedStaff && step.assignedStaff.length > 0 ? `
                                                            <div>
                                                                ${step.assignedStaff.map(staff => {
                                                                    const canStart = !staff.ThoiGianBatDauThucTe && !staff.ThoiGianKetThucThucTe;
                                                                    const canEnd = staff.ThoiGianBatDauThucTe && !staff.ThoiGianKetThucThucTe;
                                                                    const isCompleted = staff.ThoiGianKetThucThucTe !== null;
                                                                    const kpiValue = staff.KPI !== null ? parseFloat(staff.KPI) : null;
                                                                    const kpiDisplay = kpiValue !== null ? (kpiValue >= 0 ? `+${kpiValue.toFixed(2)}%` : `${kpiValue.toFixed(2)}%`) : '';
                                                                    return `
                                                                    <div class="mb-2 p-2 bg-light rounded border">
                                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                                            <div>
                                                                                <p class="mb-0" style="font-size: 0.9rem; font-weight: 600; color: #2c3e50;"><strong>${escapeHtml(staff.HoTen || 'N/A')}</strong></p>
                                                                                ${staff.ChucVu ? `<p class="mb-0 text-secondary" style="font-size: 0.8rem;"><i class="fas fa-briefcase me-1"></i>${escapeHtml(staff.ChucVu)}</p>` : ''}
                                                                            </div>
                                                                            ${kpiValue !== null ? `
                                                                            <div class="text-end">
                                                                                <span class="badge ${kpiValue >= 0 ? 'bg-success' : 'bg-danger'}" style="font-size: 0.8rem; padding: 0.4rem 0.6rem; font-weight: 600;">
                                                                                    <i class="fas fa-chart-line me-1"></i>KPI: ${kpiDisplay}
                                                                                </span>
                                                                            </div>
                                                                            ` : ''}
                                                                        </div>
                                                                        ${staff.ThoiGianBatDauThucTe ? `
                                                                        <div class="mb-1 p-1 bg-white rounded" style="font-size: 0.8rem;">
                                                                            <i class="fas fa-play-circle text-success me-1"></i><strong>Bắt đầu:</strong> ${new Date(staff.ThoiGianBatDauThucTe).toLocaleString('vi-VN')}
                                                                        </div>
                                                                        ` : ''}
                                                                        ${staff.ThoiGianKetThucThucTe ? `
                                                                        <div class="mb-1 p-1 bg-white rounded" style="font-size: 0.8rem;">
                                                                            <i class="fas fa-stop-circle text-danger me-1"></i><strong>Kết thúc:</strong> ${new Date(staff.ThoiGianKetThucThucTe).toLocaleString('vi-VN')}
                                                                        </div>
                                                                        ` : ''}
                                                                        ${!isReadOnly ? `
                                                                        <div class="d-grid gap-1 mt-2">
                                                                            ${canEnd ? `
                                                                            <button class="btn btn-danger btn-sm" onclick="endTask(${staff.ID_LLV})" title="Kết thúc nhiệm vụ" style="font-size: 0.8rem; padding: 0.4rem 0.75rem;">
                                                                                <i class="fas fa-stop me-1"></i>END
                                                                            </button>
                                                                            ` : ''}
                                                                            ${isCompleted ? `
                                                                            <button class="btn btn-secondary btn-sm" disabled style="font-size: 0.8rem; padding: 0.4rem 0.75rem;">
                                                                                <i class="fas fa-check me-1"></i>Đã hoàn thành
                                                                            </button>
                                                                            ` : ''}
                                                                        </div>
                                                                        ` : ''}
                                                                    </div>
                                                                    `;
                                                                }).join('')}
                                                            </div>
                                                            ` : step.TenNhanVien ? `
                                                            <div class="bg-light p-2 rounded border">
                                                                <p class="mb-0" style="font-size: 0.9rem; font-weight: 600; color: #2c3e50;"><strong>${escapeHtml(step.TenNhanVien)}</strong></p>
                                                                ${step.ChucVu ? `<p class="mb-0 text-secondary" style="font-size: 0.8rem;"><i class="fas fa-briefcase me-1"></i>${escapeHtml(step.ChucVu)}</p>` : ''}
                                                            </div>
                                                            ` : `
                                                            <div class="text-center text-muted p-2 bg-light rounded border">
                                                                <i class="fas fa-user-slash mb-1" style="font-size: 1.2rem;"></i>
                                                                <p class="mb-0" style="font-size: 0.85rem;">Chưa phân công</p>
                                                            </div>
                                                            `}
                                                    </div>
                                                </div>
                                                
                                                ${!isReadOnly && !hasStartedStaff ? `
                                                <div class="flex-shrink-0" style="align-self: flex-start;">
                                                    <div class="d-flex flex-column gap-2">
                                                        ${!isPublished ? `
                                                        <button class="btn btn-success btn-sm" onclick="publishStep(${step.ID_ChiTiet})" title="Công bố" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-bullhorn"></i>
                                                        </button>
                                                        ` : `
                                                        <span class="badge bg-success" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;" title="Đã công bố">
                                                            <i class="fas fa-check"></i>
                                                        </span>
                                                        `}
                                                        <button class="btn btn-outline-info btn-sm" onclick="editStep(${step.ID_ChiTiet})" title="Chỉnh sửa" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteStep(${step.ID_ChiTiet})" title="Xóa" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                ` : ''}
                                            </div>
                                            
                                            ${(() => {
                                                // Extract note from MoTa if it contains [Ghi chú: ...]
                                                let note = '';
                                                if (step.MoTa) {
                                                    const noteMatch = step.MoTa.match(/\[Ghi chú:\s*([^\]]+)\]/);
                                                    if (noteMatch) {
                                                        note = noteMatch[1].trim();
                                                    }
                                                }
                                                return note ? `
                                            <div class="mb-3">
                                                <h6 class="text-warning"><i class="fas fa-sticky-note"></i> Ghi chú</h6>
                                                <div class="alert alert-light py-2">
                                                    <p class="mb-0">${escapeHtml(note)}</p>
                                                </div>
                                            </div>
                                            ` : '';
                                            })()}
                                            
                                            ${isReadOnly ? `
                                            <div class="mt-3">
                                                <div class="alert alert-success d-flex align-items-center mb-0" role="alert">
                                                    <i class="fas fa-check-circle fa-2x me-3"></i>
                                                    <div>
                                                        <strong>Sự kiện đã hoàn thành</strong>
                                                        <p class="mb-0 small">Bạn đang xem ở chế độ chỉ đọc. Không thể chỉnh sửa hoặc xóa các bước.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            ` : ''}
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

        function editStep(stepId) {
            // Kiểm tra xem sự kiện đã hoàn thành chưa
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể chỉnh sửa bước');
                return;
            }
            
            console.log('Editing step:', stepId);
            
            // Fetch step details
            fetch(`../src/controllers/event-planning.php?action=get_step&step_id=${stepId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response ok:', response.ok);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Step data response:', data);
                    if (data.success && data.step) {
                        const step = data.step;
                        console.log('Step object:', step);
                        
                        // Fill edit step modal
                        document.getElementById('editStepId').value = step.ID_ChiTiet;
                        document.getElementById('editStepName').value = step.TenBuoc || '';
                        
                        // Extract description and note from MoTa
                        // Note: GhiChu column doesn't exist, so we parse from MoTa if it contains [Ghi chú: ...]
                        let description = step.MoTa || '';
                        let note = '';
                        
                        // Check if MoTa contains note in format [Ghi chú: ...]
                        const noteMatch = description.match(/\[Ghi chú:\s*([^\]]+)\]/);
                        if (noteMatch) {
                            note = noteMatch[1].trim();
                            // Remove note from description
                            description = description.replace(/\s*\[Ghi chú:[^\]]+\]\s*$/, '').trim();
                        }
                        
                        document.getElementById('editStepDescription').value = description;
                        
                        // Handle GhiChu field (extracted from MoTa)
                        const stepNoteElement = document.getElementById('editStepNote');
                        if (stepNoteElement) {
                            stepNoteElement.value = note;
                        }
                        
                        // Split datetime
                        const startDate = step.NgayBatDau ? step.NgayBatDau.split(' ')[0] : '';
                        const startTime = step.NgayBatDau ? step.NgayBatDau.split(' ')[1] : '08:00';
                        const endDate = step.NgayKetThuc ? step.NgayKetThuc.split(' ')[0] : '';
                        const endTime = step.NgayKetThuc ? step.NgayKetThuc.split(' ')[1] : '17:00';
                        
                        document.getElementById('editStepStartDate').value = startDate;
                        document.getElementById('editStepStartTime').value = startTime;
                        document.getElementById('editStepEndDate').value = endDate;
                        document.getElementById('editStepEndTime').value = endTime;
                        
                        // Load staff options and set selected staff (multiple checkboxes)
                        loadStaffOptionsForSteps().then(() => {
                            // Clear previous selections
                            const editCheckboxes = document.querySelectorAll('#editStepStaffCheckboxes input[type="checkbox"]');
                            editCheckboxes.forEach(checkbox => {
                                checkbox.checked = false;
                            });
                            
                            // If step has assignedStaff array, check all assigned staff
                            if (step.assignedStaff && Array.isArray(step.assignedStaff) && step.assignedStaff.length > 0) {
                                step.assignedStaff.forEach(staff => {
                                    const checkbox = document.getElementById(`edit_staff_${staff.ID_NhanVien}`);
                                    if (checkbox) {
                                        checkbox.checked = true;
                                    }
                                });
                            } else if (step.ID_NhanVien) {
                                // Fallback to single staff (backward compatibility)
                                const checkbox = document.getElementById(`edit_staff_${step.ID_NhanVien}`);
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                            }
                        });
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('editStepModal'));
                        modal.show();
                    } else {
                        console.error('Failed to load step data:', data);
                        alert('Không thể tải thông tin bước thực hiện: ' + (data.error || 'Lỗi không xác định'));
                    }
                })
                .catch(error => {
                    console.error('Error loading step:', error);
                    alert('Có lỗi xảy ra khi tải thông tin bước thực hiện: ' + error.message);
                });
        }

        function submitEditStep() {
            // Kiểm tra xem sự kiện đã hoàn thành chưa
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể chỉnh sửa bước');
                return;
            }
            
            const stepIdElement = document.getElementById('editStepId');
            const stepNameElement = document.getElementById('editStepName');
            const stepDescriptionElement = document.getElementById('editStepDescription');
            const stepStartDateElement = document.getElementById('editStepStartDate');
            const stepStartTimeElement = document.getElementById('editStepStartTime');
            const stepEndDateElement = document.getElementById('editStepEndDate');
            const stepEndTimeElement = document.getElementById('editStepEndTime');
            const stepNoteElement = document.getElementById('editStepNote');
            
            // Check if all required elements exist (staff is optional, loaded from checkboxes)
            if (!stepIdElement || !stepNameElement || !stepDescriptionElement || 
                !stepStartDateElement || !stepStartTimeElement || 
                !stepEndDateElement || !stepEndTimeElement) {
                console.error('Missing form elements:', {
                    stepIdElement: !!stepIdElement,
                    stepNameElement: !!stepNameElement,
                    stepDescriptionElement: !!stepDescriptionElement,
                    stepStartDateElement: !!stepStartDateElement,
                    stepStartTimeElement: !!stepStartTimeElement,
                    stepEndDateElement: !!stepEndDateElement,
                    stepEndTimeElement: !!stepEndTimeElement
                });
                alert('Có lỗi với form. Vui lòng thử lại.');
                return;
            }
            
            const stepId = stepIdElement.value;
            const stepName = stepNameElement.value.trim();
            const stepDescription = stepDescriptionElement.value.trim();
            const stepStartDate = stepStartDateElement.value;
            const stepStartTime = stepStartTimeElement.value;
            const stepEndDate = stepEndDateElement.value;
            const stepEndTime = stepEndTimeElement.value;
            const stepNote = stepNoteElement ? stepNoteElement.value.trim() : '';
            
            // Get all selected staff IDs from checkboxes
            const stepStaffCheckboxes = document.querySelectorAll('#editStepStaffCheckboxes input[type="checkbox"]:checked');
            const selectedStaffIds = Array.from(stepStaffCheckboxes)
                .map(checkbox => checkbox.value)
                .filter(value => value !== '' && value !== null); // Remove empty values
            
            // Validate required fields
            if (!stepName) {
                alert('Vui lòng nhập tên bước');
                return;
            }
            
            if (!stepStartDate || !stepStartTime || !stepEndDate || !stepEndTime) {
                alert('Vui lòng chọn đầy đủ ngày và giờ bắt đầu, kết thúc');
                return;
            }
            
            const startDateTime = `${stepStartDate} ${stepStartTime}`;
            const endDateTime = `${stepEndDate} ${stepEndTime}`;
            
            // Validate datetime
            const startDateObj = new Date(startDateTime);
            const endDateObj = new Date(endDateTime);
            
            if (isNaN(startDateObj.getTime()) || isNaN(endDateObj.getTime())) {
                alert('Ngày giờ không hợp lệ. Vui lòng kiểm tra lại.');
                return;
            }
            
            if (endDateObj <= startDateObj) {
                alert('Thời gian kết thúc phải sau thời gian bắt đầu');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_step');
            formData.append('stepId', stepId);
            formData.append('stepName', stepName);
            formData.append('stepDescription', stepDescription);
            formData.append('stepStartDateTime', startDateTime);
            formData.append('stepEndDateTime', endDateTime);
            
            // Add note if provided
            if (stepNote) {
                formData.append('stepNote', stepNote);
            }
            
            // Add all selected staff IDs
            selectedStaffIds.forEach(staffId => {
                formData.append('staffId[]', staffId);
            });
            
            // Also set as staffId for backward compatibility
            if (selectedStaffIds.length > 0) {
                formData.append('staffId', selectedStaffIds.join(','));
            }
            
            console.log('Sending update step data:', {
                action: 'update_step',
                stepId: stepId,
                stepName: stepName,
                stepDescription: stepDescription,
                stepStartDateTime: startDateTime,
                stepEndDateTime: endDateTime,
                staffIds: selectedStaffIds
            });
            
            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                console.log('Response headers:', response.headers);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('Update step response:', data);
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editStepModal'));
                    modal.hide();
                    alert('Cập nhật bước thực hiện thành công');
                    
                    // Reload steps for current event
                    const stepEventIdElement = document.getElementById('stepEventId');
                    if (stepEventIdElement) {
                        const currentEventId = stepEventIdElement.value;
                        if (currentEventId) {
                            // Kiểm tra xem sự kiện đã hoàn thành chưa to determine read-only mode
                            const event = approvedEvents.find(e => e.ID_DatLich == currentEventId);
                            const isReadOnly = event && event.TrangThaiSuKien === 'Hoàn thành';
                            loadSteps(currentEventId, isReadOnly);
                        }
                    } else {
                        console.warn('stepEventId element not found, skipping step reload');
                    }
                } else {
                    console.error('Update step failed:', data);
                    alert('Lỗi: ' + (data.error || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error updating step:', error);
                alert('Có lỗi xảy ra khi cập nhật bước thực hiện: ' + error.message);
            });
        }

        function loadStaffOptionsForSteps() {
            return fetch('../src/controllers/event-planning.php?action=get_staff_list', {
                credentials: 'same-origin'
            })
                .then(handleFetchResponse)
                .then(data => {
                    const addContainer = document.getElementById('stepStaffCheckboxes');
                    const editContainer = document.getElementById('editStepStaffCheckboxes');
                    
                    // Clear containers
                    if (addContainer) addContainer.innerHTML = '';
                    if (editContainer) editContainer.innerHTML = '';
                    
                    if (data.success && data.staff && data.staff.length > 0) {
                        // Populate add step form
                        if (addContainer) {
                            data.staff.forEach(staff => {
                                const checkboxItem = document.createElement('div');
                                checkboxItem.className = 'staff-checkbox-item';
                                checkboxItem.innerHTML = `
                                    <input type="checkbox" id="staff_${staff.ID_NhanVien}" name="staffId[]" value="${staff.ID_NhanVien}">
                                    <label for="staff_${staff.ID_NhanVien}">
                                        <span class="staff-name">${escapeHtml(staff.HoTen)}</span>
                                        <span class="staff-role">${escapeHtml(staff.ChucVu)}</span>
                                    </label>
                                `;
                                addContainer.appendChild(checkboxItem);
                            });
                        }
                        
                        // Populate edit step form
                        if (editContainer) {
                            data.staff.forEach(staff => {
                                const checkboxItem = document.createElement('div');
                                checkboxItem.className = 'staff-checkbox-item';
                                checkboxItem.innerHTML = `
                                    <input type="checkbox" id="edit_staff_${staff.ID_NhanVien}" name="staffId[]" value="${staff.ID_NhanVien}">
                                    <label for="edit_staff_${staff.ID_NhanVien}">
                                        <span class="staff-name">${escapeHtml(staff.HoTen)}</span>
                                        <span class="staff-role">${escapeHtml(staff.ChucVu)}</span>
                                    </label>
                                `;
                                editContainer.appendChild(checkboxItem);
                            });
                        }
                    } else {
                        // Show no staff message
                        const noStaffMsg = '<div class="text-center text-muted py-3"><i class="fas fa-user-slash"></i> Không có nhân viên nào</div>';
                        if (addContainer) addContainer.innerHTML = noStaffMsg;
                        if (editContainer) editContainer.innerHTML = noStaffMsg;
                    }
                    
                    return Promise.resolve();
                })
                .catch(error => {
                    console.error('Error loading staff:', error);
                    const errorMsg = '<div class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle"></i> Lỗi khi tải danh sách nhân viên</div>';
                    const addContainer = document.getElementById('stepStaffCheckboxes');
                    const editContainer = document.getElementById('editStepStaffCheckboxes');
                    if (addContainer) addContainer.innerHTML = errorMsg;
                    if (editContainer) editContainer.innerHTML = errorMsg;
                    return Promise.reject(error);
                });
        }

        function addStep() {
            // Kiểm tra xem sự kiện đã hoàn thành chưa
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể thêm bước mới');
                return;
            }
            
            const form = document.getElementById('addStepForm');
            const stepEventIdElement = document.getElementById('stepEventId');
            
            if (!form) {
                console.error('addStepForm not found');
                alert('Có lỗi với form. Vui lòng thử lại.');
                return;
            }
            
            if (!stepEventIdElement) {
                console.error('stepEventId element not found');
                alert('Có lỗi với form. Vui lòng thử lại.');
                return;
            }
            
            const formData = new FormData(form);
            formData.append('action', 'add_event_step');
            formData.append('eventId', stepEventIdElement.value);
            
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

            // Get all selected staff IDs from checkboxes
            const stepStaffCheckboxes = document.querySelectorAll('#stepStaffCheckboxes input[type="checkbox"]:checked');
            const selectedStaffIds = Array.from(stepStaffCheckboxes)
                .map(checkbox => checkbox.value)
                .filter(value => value !== ''); // Remove empty values
            
            // Remove old staffId entries and add new ones
            formData.delete('staffId[]');
            selectedStaffIds.forEach(staffId => {
                formData.append('staffId[]', staffId);
            });
            
            // Also set as stepStaff for backward compatibility
            if (selectedStaffIds.length > 0) {
                formData.set('stepStaff', selectedStaffIds.join(','));
            }
            
            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Thêm bước thực hiện thành công');
                    form.reset();
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('stepStartDate').value = today;
                    document.getElementById('stepStartTime').value = '08:00';
                    document.getElementById('stepEndTime').value = '17:00';
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

        function startTask(taskId) {
            const formData = new FormData();
            formData.append('action', 'start_task');
            formData.append('task_id', taskId);
            formData.append('ID_LLV', taskId);

            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Đã bắt đầu nhiệm vụ');
                    const eventId = document.getElementById('stepEventId')?.value;
                    if (eventId) {
                        loadSteps(eventId);
                    }
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi bắt đầu nhiệm vụ: ' + error.message);
            });
        }

        function endTask(taskId) {
            if (!confirm('Bạn có chắc muốn kết thúc nhiệm vụ này?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'end_task');
            formData.append('task_id', taskId);
            formData.append('ID_LLV', taskId);

            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    let message = 'Đã kết thúc nhiệm vụ';
                    if (data.kpiDisplay) {
                        const kpiClass = parseFloat(data.kpi) >= 0 ? 'text-success' : 'text-danger';
                        message += `\nKPI: <span class="${kpiClass}">${data.kpiDisplay}</span>`;
                    }
                    alert(message);
                    const eventId = document.getElementById('stepEventId')?.value;
                    if (eventId) {
                        loadSteps(eventId);
                    }
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi kết thúc nhiệm vụ: ' + error.message);
            });
        }

        function updateStepStatus(stepId, status) {
            // Kiểm tra xem sự kiện đã hoàn thành chưa
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể cập nhật trạng thái');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_step_status');
            formData.append('step_id', stepId);
            formData.append('status', status);

            fetch('../src/controllers/event-planning.php', {
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
            // Kiểm tra xem sự kiện đã hoàn thành chưa
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể xóa bước');
                return;
            }
            
            if (confirm('Bạn có chắc muốn xóa bước thực hiện này?')) {
                const formData = new FormData();
                formData.append('action', 'delete_step');
                formData.append('step_id', stepId);

            fetch('../src/controllers/event-planning.php', {
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

        function publishStep(stepId) {
            // Kiểm tra xem sự kiện đã hoàn thành chưa
            const eventId = document.getElementById('stepEventId')?.value;
            const event = approvedEvents.find(e => e.ID_DatLich == eventId);
            if (event && event.TrangThaiSuKien === 'Hoàn thành') {
                alert('Sự kiện đã hoàn thành, không thể công bố bước');
                return;
            }
            
            if (confirm('Bạn có chắc muốn công bố bước này? Sau khi công bố, nhân viên sẽ thấy nhiệm vụ của mình.')) {
                const formData = new FormData();
                formData.append('action', 'publish_step');
                formData.append('stepId', stepId);

                fetch('../src/controllers/event-planning.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(handleFetchResponse)
                .then(data => {
                    if (data.success) {
                        AdminPanel.showSuccess(data.message || 'Đã công bố bước thành công');
                        loadSteps(document.getElementById('stepEventId').value);
                    } else {
                        AdminPanel.showError(data.error || data.message || 'Lỗi không xác định');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    AdminPanel.showError('Có lỗi xảy ra khi công bố bước: ' + error.message);
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
