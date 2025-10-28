<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sự kiện của tôi - Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        
        .content-section {
            padding: 3rem;
        }
        
        .event-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .event-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .total-price {
            text-align: right;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 10px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        
        .total-price h4 {
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .total-price small {
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .event-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #495057;
            margin: 0;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-paid {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b3d7ff;
        }
        
        .event-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            color: #6c757d;
        }
        
        .detail-item i {
            width: 20px;
            margin-right: 0.5rem;
            color: #667eea;
        }
        
        .event-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
        }
        
        .btn-outline-primary:hover {
            background: #667eea;
            border-color: #667eea;
        }
        
        .btn-review {
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .btn-review:hover {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b35 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
            color: white;
        }
        
        .btn-review:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(255, 193, 7, 0.3);
        }
        
        .btn-review i {
            margin-right: 5px;
            animation: starPulse 2s ease-in-out infinite;
        }
        
        @keyframes starPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Tooltip styling */
        .btn-review[title] {
            position: relative;
        }
        
        .btn-review[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            margin-bottom: 5px;
            animation: tooltipFadeIn 0.3s ease;
        }
        
        .btn-review[title]:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            margin-bottom: -5px;
        }
        
        @keyframes tooltipFadeIn {
            from { opacity: 0; transform: translateX(-50%) translateY(5px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        
        /* Review Modal Styles */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .review-form {
            padding: 1rem 0;
        }
        
        .rating-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .rating-title {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .star-rating {
            display: flex;
            gap: 8px;
            margin-bottom: 0.5rem;
        }
        
        .star {
            font-size: 1.8rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .star:hover,
        .star.active {
            color: #ffc107;
            transform: scale(1.1);
        }
        
        .comment-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .comment-title {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .btn-submit-review {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 8px;
            color: white;
            padding: 10px 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-submit-review:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-submit-review:disabled {
            opacity: 0.6;
            transform: none;
        }
        
        .success-message-modal {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 1px solid #c3e6cb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            color: #155724;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: slideInDown 0.5s ease-out;
        }
        
        .success-message-modal i {
            color: #28a745;
            margin-right: 0.5rem;
        }
        
        .success-message-modal strong {
            font-size: 1.1rem;
            display: block;
            margin-bottom: 0.5rem;
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
        
        /* Event Details Modal Styles */
        .event-details-content {
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .event-image-section {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .event-main-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .event-status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .event-info-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .event-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .event-info-item i {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            margin-right: 1rem;
            font-size: 1.1rem;
        }
        
        .event-info-content {
            flex-grow: 1;
        }
        
        .event-info-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .event-info-value {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }
        
        .reviews-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }
        
        .review-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }
        
        .review-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .review-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .review-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .review-author {
            font-weight: 600;
            color: #333;
        }
        
        .review-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .review-content {
            color: #555;
            line-height: 1.6;
            font-style: italic;
        }
        
        .equipment-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }
        
        .equipment-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 0.75rem;
        }
        
        .equipment-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 1rem;
        }
        
        .equipment-info {
            flex-grow: 1;
        }
        
        .equipment-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .equipment-details {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .equipment-price {
            font-weight: 600;
            color: #28a745;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 3rem;
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
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .filter-group {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-select, .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.5rem 1rem;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Payment Modal Styles */
        .payment-method-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
        }
        
        .payment-method-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .payment-method-card.border-primary {
            border-color: #667eea !important;
            background-color: #f8f9ff !important;
        }
        
        .payment-details {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .form-check-label {
            cursor: pointer;
        }
        
        .momo-logo {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border-radius: 50%;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }
        
        .momo-logo i {
            color: white !important;
        }
        
        .momo-logo-small {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }
        
        .momo-logo-small i {
            color: white !important;
        }
        
        .payment-info-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            border: 1px solid #dee2e6;
        }
        
        .payment-info-card h6 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .payment-info-card ul {
            margin-bottom: 0;
        }
        
        .payment-info-card li {
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #ee5a24, #ff6b6b);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }
        
        .qr-container {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                border-radius: 15px;
            }
            
            .content-section {
                padding: 2rem 1.5rem;
            }
            
            .header-section h1 {
            font-size: 2rem;
            }
            
            .event-details {
                grid-template-columns: 1fr;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .payment-method-card {
                margin-bottom: 1rem;
            }
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
                        <h1><i class="fas fa-calendar-check"></i> Sự kiện của tôi</h1>
                        <p>Quản lý và theo dõi các sự kiện đã đăng ký</p>
                    </div>
                    <div>
                        <a href="../index.php" class="btn btn-light btn-lg">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Content Section -->
            <div class="content-section">
                <!-- Error Message -->
                <div class="error-message" id="errorMessage"></div>
                
                <!-- Statistics -->
                <div class="stats-cards" id="statsCards">
                    <div class="stat-card">
                        <div class="stat-number" id="totalEvents">-</div>
                        <div class="stat-label">Tổng sự kiện</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="pendingEvents">-</div>
                        <div class="stat-label">Chờ duyệt</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="approvedEvents">-</div>
                        <div class="stat-label">Đã duyệt</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="paidEvents">-</div>
                        <div class="stat-label">Đã thanh toán</div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filter-section">
                    <div class="filter-group">
                        <div class="flex-grow-1">
                            <label for="statusFilter" class="form-label">Trạng thái:</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">Tất cả trạng thái</option>
                                <option value="Chờ duyệt">Chờ duyệt</option>
                                <option value="Đã duyệt">Đã duyệt</option>
                                <option value="Từ chối">Từ chối</option>
                            </select>
                        </div>
                        <div class="flex-grow-1">
                            <label for="paymentFilter" class="form-label">Thanh toán:</label>
                            <select class="form-select" id="paymentFilter">
                                <option value="">Tất cả</option>
                                <option value="Chưa thanh toán">Chưa thanh toán</option>
                                <option value="Đã đặt cọc">Đã đặt cọc</option>
                                <option value="Đã thanh toán đủ">Đã thanh toán đủ</option>
                            </select>
                        </div>
                        <div class="flex-grow-1">
                            <label for="searchInput" class="form-label">Tìm kiếm:</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Tên sự kiện, địa điểm...">
                        </div>
                        <div>
                            <button class="btn btn-primary" onclick="loadMyEvents()">
                                <i class="fas fa-search"></i> Lọc
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Events List -->
                <div id="eventsList">
                    <div class="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Đang tải danh sách sự kiện...</p>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div class="empty-state" id="emptyState" style="display: none;">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Chưa có sự kiện nào</h3>
                    <p>Bạn chưa đăng ký sự kiện nào. Hãy bắt đầu đăng ký sự kiện đầu tiên của bạn!</p>
                <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Đăng ký sự kiện
                </a>
                </div>
            </div>
        </div>
            </div>
            
    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEventTitle">Chi tiết sự kiện</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalEventBody">
                    <!-- Event details will be populated here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="paymentModalTitle">
                        <i class="fas fa-credit-card"></i> Thanh toán sự kiện
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="paymentModalBody">
                    <!-- Payment details will be populated here -->
                </div>
                <div class="modal-footer" id="paymentModalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="processPayment()">
                        <i class="fas fa-credit-card"></i> Xác nhận thanh toán
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MoMo Payment Modal -->
    <div class="modal fade" id="momoPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-mobile-alt"></i> Thanh toán qua MoMo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="momo-logo mb-3">
                            <i class="fas fa-mobile-alt fa-4x text-danger"></i>
                        </div>
                        <h4 class="text-danger">MoMo Wallet</h4>
                        <p class="text-muted">Thanh toán nhanh chóng và bảo mật</p>
                    </div>
                    
                    <div class="payment-info-card">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-shield-alt text-success"></i> Bảo mật cao</h6>
                                <ul class="text-start">
                                    <li>Mã hóa SSL 256-bit</li>
                                    <li>Xác thực 2 lớp</li>
                                    <li>Bảo vệ thông tin cá nhân</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-bolt text-warning"></i> Thanh toán nhanh</h6>
                                <ul class="text-start">
                                    <li>Xử lý trong vài giây</li>
                                    <li>Xác nhận ngay lập tức</li>
                                    <li>Không cần nhập thông tin</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Hướng dẫn:</strong>
                        <ol class="text-start mt-2 mb-0">
                            <li>Nhấn "Xác nhận thanh toán"</li>
                            <li>Hệ thống sẽ chuyển hướng đến MoMo Gateway</li>
                            <li>Đăng nhập và xác nhận thanh toán trên MoMo</li>
                            <li>Quay lại trang web sau khi hoàn thành</li>
                        </ol>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger btn-lg" onclick="confirmMoMoPayment()">
                        <i class="fas fa-mobile-alt"></i> Xác nhận thanh toán MoMo
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="modal-title" id="eventDetailsModalLabel">
                        <i class="fas fa-info-circle"></i> Chi tiết sự kiện
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="eventDetailsModalBody">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="modal-title" id="reviewModalLabel">
                        <i class="fas fa-star"></i> Đánh giá sự kiện
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="reviewModalBody">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Pass user data from PHP to JavaScript
        const currentUser = <?= json_encode($user) ?>;
        console.log('Current user:', currentUser);
        
        let allEvents = [];
        let filteredEvents = [];
        
        // Helper function to get location price text
        function getLocationPriceText(event) {
            if (!event) return 'Chưa có giá';
            
            // If we have the applied rental type, show the specific price
            if (event.LoaiThueApDung) {
                if (event.LoaiThueApDung === 'Theo giờ' && event.GiaThueGio) {
                    return `${new Intl.NumberFormat('vi-VN').format(event.GiaThueGio)}/giờ`;
                }
                if (event.LoaiThueApDung === 'Theo ngày' && event.GiaThueNgay) {
                    return `${new Intl.NumberFormat('vi-VN').format(event.GiaThueNgay)}/ngày`;
                }
            }
            
            // If only one price type available, show it
            if (event.LoaiThue === 'Theo giờ' && event.GiaThueGio) {
                return `${new Intl.NumberFormat('vi-VN').format(event.GiaThueGio)}/giờ`;
            }
            if (event.LoaiThue === 'Theo ngày' && event.GiaThueNgay) {
                return `${new Intl.NumberFormat('vi-VN').format(event.GiaThueNgay)}/ngày`;
            }
            
            // If both prices available but no applied type, determine which one was used based on duration
            if (event.LoaiThue === 'Cả hai' && event.GiaThueGio && event.GiaThueNgay) {
                const startDate = new Date(event.NgayBatDau);
                const endDate = new Date(event.NgayKetThuc);
                const durationMs = endDate - startDate;
                const durationHours = Math.ceil(durationMs / (1000 * 60 * 60));
                const durationDays = Math.ceil(durationMs / (1000 * 60 * 60 * 24));
                
                const hourlyPrice = durationHours * parseFloat(event.GiaThueGio);
                const dailyPrice = durationDays * parseFloat(event.GiaThueNgay);
                
                // Show the pricing that was actually used (cheaper one)
                if (hourlyPrice < dailyPrice) {
                    return `${new Intl.NumberFormat('vi-VN').format(event.GiaThueGio)}/giờ`;
                } else {
                    return `${new Intl.NumberFormat('vi-VN').format(event.GiaThueNgay)}/ngày`;
                }
            }
            
            // Fallback: show first available price
            if (event.GiaThueGio && event.GiaThueGio > 0) {
                return `${new Intl.NumberFormat('vi-VN').format(event.GiaThueGio)}/giờ`;
            }
            if (event.GiaThueNgay && event.GiaThueNgay > 0) {
                return `${new Intl.NumberFormat('vi-VN').format(event.GiaThueNgay)}/ngày`;
            }
            
            return 'Chưa có giá';
        }
        
        // Helper function to calculate total event price
        function calculateTotalEventPrice(event) {
            if (!event) return 0;
            
            let totalPrice = 0;
            
            // Add event type price
            if (event.GiaCoBan && event.GiaCoBan > 0) {
                totalPrice += parseFloat(event.GiaCoBan);
            }
            
            // Add location rental price based on duration
            if (event.GiaThueGio || event.GiaThueNgay) {
                const startDate = new Date(event.NgayBatDau);
                const endDate = new Date(event.NgayKetThuc);
                const durationMs = endDate - startDate;
                const durationHours = Math.ceil(durationMs / (1000 * 60 * 60));
                const durationDays = Math.ceil(durationMs / (1000 * 60 * 60 * 24));
                
                // Calculate location price based on rental type
                if (event.LoaiThue === 'Theo giờ' && event.GiaThueGio) {
                    totalPrice += durationHours * parseFloat(event.GiaThueGio);
                } else if (event.LoaiThue === 'Theo ngày' && event.GiaThueNgay) {
                    totalPrice += durationDays * parseFloat(event.GiaThueNgay);
                } else if (event.LoaiThue === 'Cả hai') {
                    // Use the cheaper option
                    const hourlyPrice = durationHours * parseFloat(event.GiaThueGio || 0);
                    const dailyPrice = durationDays * parseFloat(event.GiaThueNgay || 0);
                    
                    if (hourlyPrice > 0 && dailyPrice > 0) {
                        totalPrice += Math.min(hourlyPrice, dailyPrice);
                    } else if (hourlyPrice > 0) {
                        totalPrice += hourlyPrice;
                    } else if (dailyPrice > 0) {
                        totalPrice += dailyPrice;
                    }
                }
            }
            
            // Add equipment price
            if (event.TongGiaThietBi && event.TongGiaThietBi > 0) {
                totalPrice += parseFloat(event.TongGiaThietBi);
            }
            
            return totalPrice;
        }
        
        // Helper function to get event price breakdown
        function getEventPriceBreakdown(event) {
            if (!event) return { locationPrice: 0, eventTypePrice: 0, equipmentPrice: 0, totalPrice: 0 };
            
            let locationPrice = 0;
            let eventTypePrice = parseFloat(event.GiaCoBan) || 0;
            let equipmentPrice = parseFloat(event.TongGiaThietBi) || 0;
            
            // If TongTien is available and greater than 0, use it as the source of truth
            if (event.TongTien && event.TongTien > 0) {
                // Calculate location price by subtracting event type and equipment prices
                locationPrice = Math.max(0, event.TongTien - eventTypePrice - equipmentPrice);
                
                console.log('Using TongTien from database:', {
                    TongTien: event.TongTien,
                    eventTypePrice: eventTypePrice,
                    equipmentPrice: equipmentPrice,
                    calculatedLocationPrice: locationPrice
                });
                
                return {
                    locationPrice: locationPrice,
                    eventTypePrice: eventTypePrice,
                    equipmentPrice: equipmentPrice,
                    totalPrice: event.TongTien
                };
            }
            
            // Debug logging
            console.log('Price Breakdown Debug:', {
                GiaCoBan: event.GiaCoBan,
                eventTypePrice: eventTypePrice,
                TongGiaThietBi: event.TongGiaThietBi,
                equipmentPrice: equipmentPrice,
                GiaThueGio: event.GiaThueGio,
                GiaThueNgay: event.GiaThueNgay,
                LoaiThue: event.LoaiThue,
                LoaiThueApDung: event.LoaiThueApDung,
                NgayBatDau: event.NgayBatDau,
                NgayKetThuc: event.NgayKetThuc
            });
            
            // Calculate location rental price based on applied rental type
            if (event.LoaiThueApDung) {
                // Use the applied rental type
                const startDate = new Date(event.NgayBatDau);
                const endDate = new Date(event.NgayKetThuc);
                const durationMs = endDate - startDate;
                const durationHours = Math.ceil(durationMs / (1000 * 60 * 60));
                const durationDays = Math.ceil(durationMs / (1000 * 60 * 60 * 24));
                
                console.log('Duration calculation:', {
                    startDate: startDate,
                    endDate: endDate,
                    durationMs: durationMs,
                    durationHours: durationHours,
                    durationDays: durationDays
                });
                
                if (event.LoaiThueApDung === 'Theo giờ' && event.GiaThueGio) {
                    locationPrice = durationHours * parseFloat(event.GiaThueGio);
                } else if (event.LoaiThueApDung === 'Theo ngày' && event.GiaThueNgay) {
                    locationPrice = durationDays * parseFloat(event.GiaThueNgay);
                }
                
                console.log('Applied rental type calculation:', {
                    LoaiThueApDung: event.LoaiThueApDung,
                    locationPrice: locationPrice
                });
            } else if (event.GiaThueGio || event.GiaThueNgay) {
                // Fallback: calculate based on duration and available prices
                const startDate = new Date(event.NgayBatDau);
                const endDate = new Date(event.NgayKetThuc);
                const durationMs = endDate - startDate;
                const durationHours = Math.ceil(durationMs / (1000 * 60 * 60));
                const durationDays = Math.ceil(durationMs / (1000 * 60 * 60 * 24));
                
                console.log('Fallback duration calculation:', {
                    startDate: startDate,
                    endDate: endDate,
                    durationMs: durationMs,
                    durationHours: durationHours,
                    durationDays: durationDays
                });
                
                // Calculate location price based on rental type
                if (event.LoaiThue === 'Theo giờ' && event.GiaThueGio) {
                    locationPrice = durationHours * parseFloat(event.GiaThueGio);
                } else if (event.LoaiThue === 'Theo ngày' && event.GiaThueNgay) {
                    locationPrice = durationDays * parseFloat(event.GiaThueNgay);
                } else if (event.LoaiThue === 'Cả hai') {
                    // Use the cheaper option
                    const hourlyPrice = durationHours * parseFloat(event.GiaThueGio || 0);
                    const dailyPrice = durationDays * parseFloat(event.GiaThueNgay || 0);
                    
                    console.log('Both rental types:', {
                        hourlyPrice: hourlyPrice,
                        dailyPrice: dailyPrice
                    });
                    
                    if (hourlyPrice > 0 && dailyPrice > 0) {
                        locationPrice = Math.min(hourlyPrice, dailyPrice);
                    } else if (hourlyPrice > 0) {
                        locationPrice = hourlyPrice;
                    } else if (dailyPrice > 0) {
                        locationPrice = dailyPrice;
                    }
                }
            }
            
            const result = {
                locationPrice: locationPrice,
                eventTypePrice: eventTypePrice,
                equipmentPrice: equipmentPrice,
                totalPrice: locationPrice + eventTypePrice + equipmentPrice
            };
            
            console.log('Final price breakdown:', result);
            
            return result;
        }
        
        // Initialize the page
        $(document).ready(function() {
            loadMyEvents();
            
            // Setup filters
            $('#statusFilter, #paymentFilter').on('change', filterEvents);
            $('#searchInput').on('keyup', filterEvents);
        });
        
        // Load user's events
        function loadMyEvents() {
                    $('#eventsList').html(`
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải danh sách sự kiện...</p>
                        </div>
                    `);
            
            $.get('../src/controllers/event-register.php?action=get_my_events', function(data) {
                if (data.success) {
                    allEvents = data.events;
                    filteredEvents = allEvents;
                    displayEvents();
                    updateStatistics();
                } else {
                    showError('Không thể tải danh sách sự kiện: ' + data.error);
                    $('#eventsList').html('');
                }
            }, 'json').fail(function() {
                showError('Lỗi kết nối khi tải danh sách sự kiện');
                $('#eventsList').html('');
            });
        }
        
        // Display events
        function displayEvents() {
            if (filteredEvents.length === 0) {
                $('#eventsList').html('');
                $('#emptyState').show();
                return;
            }
            
            $('#emptyState').hide();
            
            let html = '';
            filteredEvents.forEach(event => {
                const statusClass = getStatusClass(event.TrangThaiDuyet);
                const paymentClass = getPaymentClass(event.TrangThaiThanhToan);
                const eventDate = formatDateTime(event.NgayBatDau);
                const priceText = getLocationPriceText(event);
                const priceBreakdown = getEventPriceBreakdown(event);
                const totalPrice = new Intl.NumberFormat('vi-VN').format(priceBreakdown.totalPrice);
                
                html += `
                    <div class="event-card">
                        <div class="event-header">
                            <div class="flex-grow-1">
                                <h3 class="event-title">${event.TenSuKien}</h3>
                                <div class="d-flex gap-2 mt-2">
                                    <span class="status-badge ${statusClass}">${event.TrangThaiDuyet}</span>
                                    <span class="status-badge ${paymentClass}">${event.TrangThaiThanhToan}</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="total-price">
                                    <h4 class="text-primary mb-0">${totalPrice} VNĐ</h4>
                                    <small class="text-muted">Tổng giá</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="event-details">
                            <div class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <span>${eventDate}</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>${event.TenDiaDiem || 'Chưa xác định'}</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-users"></i>
                                <span>${event.SoNguoiDuKien || 'Chưa xác định'} người</span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-home"></i>
                                <span>${priceText}</span>
                            </div>
                            ${event.TenLoai ? `
                            <div class="detail-item">
                                <i class="fas fa-tag"></i>
                                <span>${event.TenLoai}</span>
                            </div>
                            ` : ''}
                            ${priceBreakdown.eventTypePrice > 0 ? `
                            <div class="detail-item">
                                <i class="fas fa-star"></i>
                                <span>Loại sự kiện: ${new Intl.NumberFormat('vi-VN').format(priceBreakdown.eventTypePrice)} VNĐ</span>
                            </div>
                            ` : ''}
                        </div>
                        
                        ${event.MoTa ? `
                        <div class="mb-3">
                            <strong>Mô tả:</strong>
                            <p class="text-muted mt-1">${event.MoTa}</p>
                        </div>
                        ` : ''}
                        
                        <div class="event-actions">
                            <button class="btn btn-primary btn-sm" onclick="viewEventDetails(${event.ID_DatLich})">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </button>
                            ${event.TrangThaiDuyet === 'Chờ duyệt' ? `
                            <button class="btn btn-warning btn-sm" onclick="editEvent(${event.ID_DatLich})">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="cancelEvent(${event.ID_DatLich})">
                                <i class="fas fa-times"></i> Hủy
                            </button>
                            ` : ''}
                            ${event.TrangThaiDuyet === 'Đã duyệt' && event.TrangThaiThanhToan === 'Chưa thanh toán' ? `
                            <button class="btn btn-outline-primary btn-sm" onclick="makePayment(${event.ID_DatLich})">
                                <i class="fas fa-credit-card"></i> Thanh toán
                            </button>
                            ` : ''}
                            ${event.TrangThaiSuKien === 'Hoàn thành' && event.TrangThaiThanhToan === 'Đã thanh toán đủ' ? `
                            <button class="btn btn-review btn-sm" onclick="openReviewModal(${event.ID_DatLich}, '${event.TenSuKien}')" 
                                    title="Đánh giá sự kiện đã hoàn thành">
                                <i class="fas fa-star"></i> Đánh giá
                            </button>
                            ` : ''}
                            </div>
                    </div>
                `;
            });
            
            $('#eventsList').html(html);
        }
        
        // Filter events
        function filterEvents() {
            const statusFilter = $('#statusFilter').val();
            const paymentFilter = $('#paymentFilter').val();
            const searchTerm = $('#searchInput').val().toLowerCase();
            
            filteredEvents = allEvents.filter(event => {
                const matchesStatus = !statusFilter || event.TrangThaiDuyet === statusFilter;
                const matchesPayment = !paymentFilter || event.TrangThaiThanhToan === paymentFilter;
                const matchesSearch = !searchTerm || 
                    event.TenSuKien.toLowerCase().includes(searchTerm) ||
                    (event.TenDiaDiem && event.TenDiaDiem.toLowerCase().includes(searchTerm));
                
                return matchesStatus && matchesPayment && matchesSearch;
            });
            
            displayEvents();
        }
        
        // Update statistics
        function updateStatistics() {
            const total = allEvents.length;
            const pending = allEvents.filter(e => e.TrangThaiDuyet === 'Chờ duyệt').length;
            const approved = allEvents.filter(e => e.TrangThaiDuyet === 'Đã duyệt').length;
            const paid = allEvents.filter(e => e.TrangThaiThanhToan === 'Đã thanh toán đủ').length;
            
            $('#totalEvents').text(total);
            $('#pendingEvents').text(pending);
            $('#approvedEvents').text(approved);
            $('#paidEvents').text(paid);
        }
        
        // Get status class
        function getStatusClass(status) {
            switch(status) {
                case 'Chờ duyệt': return 'status-pending';
                case 'Đã duyệt': return 'status-approved';
                case 'Từ chối': return 'status-rejected';
                default: return 'status-pending';
            }
        }
        
        // Get payment class
        function getPaymentClass(payment) {
            switch(payment) {
                case 'Chưa thanh toán': return 'status-pending';
                case 'Đã đặt cọc': return 'status-approved';
                case 'Đã thanh toán đủ': return 'status-paid';
                default: return 'status-pending';
            }
        }
        
        // Format date time
        function formatDateTime(dateTimeString) {
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
        }
        
        // View event details
        function viewEventDetails(eventId) {
            const event = allEvents.find(e => e.ID_DatLich === eventId);
            if (!event) return;
            
            const eventDate = formatDateTime(event.NgayBatDau);
            const endDate = formatDateTime(event.NgayKetThuc);
            const priceText = getLocationPriceText(event);
            const budget = event.NganSach ? new Intl.NumberFormat('vi-VN').format(event.NganSach) : 'Chưa xác định';
            const priceBreakdown = getEventPriceBreakdown(event);
            
            $('#modalEventTitle').text(event.TenSuKien);
            $('#modalEventBody').html(`
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle text-primary"></i> Thông tin cơ bản</h6>
                        <table class="table table-borderless">
                            <tr><td><strong>Tên sự kiện:</strong></td><td>${event.TenSuKien}</td></tr>
                            <tr><td><strong>Loại sự kiện:</strong></td><td>${event.TenLoai || 'Chưa xác định'}</td></tr>
                            <tr><td><strong>Ngày bắt đầu:</strong></td><td>${eventDate}</td></tr>
                            <tr><td><strong>Ngày kết thúc:</strong></td><td>${endDate}</td></tr>
                            <tr><td><strong>Số khách dự kiến:</strong></td><td>${event.SoNguoiDuKien || 'Chưa xác định'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-map-marker-alt text-primary"></i> Địa điểm</h6>
                        <table class="table table-borderless">
                            <tr><td><strong>Tên địa điểm:</strong></td><td>${event.TenDiaDiem || 'Chưa xác định'}</td></tr>
                            <tr><td><strong>Địa chỉ:</strong></td><td>${event.DiaChi || 'Chưa xác định'}</td></tr>
                            <tr><td><strong>Sức chứa:</strong></td><td>${event.SucChua || 'Chưa xác định'} người</td></tr>
                            <tr><td><strong>Giá thuê:</strong></td><td>${priceText}</td></tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6><i class="fas fa-credit-card text-primary"></i> Chi tiết giá tiền</h6>
                        <table class="table table-borderless">
                            ${priceBreakdown.eventTypePrice > 0 ? `
                            <tr><td><strong>Giá loại sự kiện:</strong></td><td><span class="text-info">${new Intl.NumberFormat('vi-VN').format(priceBreakdown.eventTypePrice)} VNĐ</span></td></tr>
                            ` : ''}
                            ${priceBreakdown.locationPrice > 0 ? `
                            <tr><td><strong>Giá thuê địa điểm:</strong></td><td><span class="text-warning">${new Intl.NumberFormat('vi-VN').format(priceBreakdown.locationPrice)} VNĐ</span></td></tr>
                            ` : ''}
                            ${priceBreakdown.equipmentPrice > 0 ? `
                            <tr><td><strong>Giá thiết bị:</strong></td><td><span class="text-danger">${new Intl.NumberFormat('vi-VN').format(priceBreakdown.equipmentPrice)} VNĐ</span></td></tr>
                            ` : ''}
                            <tr><td><strong>Tổng giá:</strong></td><td><span class="text-success fw-bold">${new Intl.NumberFormat('vi-VN').format(priceBreakdown.totalPrice)} VNĐ</span></td></tr>
                            <tr><td><strong>Ngân sách:</strong></td><td>${budget} VNĐ</td></tr>
                            <tr><td><strong>Trạng thái duyệt:</strong></td><td><span class="status-badge ${getStatusClass(event.TrangThaiDuyet)}">${event.TrangThaiDuyet}</span></td></tr>
                            <tr><td><strong>Trạng thái thanh toán:</strong></td><td><span class="status-badge ${getPaymentClass(event.TrangThaiThanhToan)}">${event.TrangThaiThanhToan}</span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-user text-primary"></i> Thông tin liên hệ</h6>
                        <table class="table table-borderless">
                            <tr><td><strong>Họ tên:</strong></td><td>${event.HoTen || 'Chưa xác định'}</td></tr>
                            <tr><td><strong>Số điện thoại:</strong></td><td>${event.SoDienThoai || 'Chưa xác định'}</td></tr>
                        </table>
                    </div>
                </div>
                
                ${event.MoTa ? `
                <div class="mt-3">
                    <h6><i class="fas fa-align-left text-primary"></i> Mô tả</h6>
                    <p class="text-muted">${event.MoTa}</p>
                </div>
                ` : ''}
                
                ${event.GhiChu ? `
                <div class="mt-3">
                    <h6><i class="fas fa-sticky-note text-primary"></i> Ghi chú</h6>
                    <p class="text-muted">${event.GhiChu}</p>
                </div>
                ` : ''}
                
                <div class="mt-3">
                    <h6><i class="fas fa-cogs text-primary"></i> Thiết bị đã đăng ký</h6>
                    <div id="equipmentDetails_${event.ID_DatLich}">
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <small class="text-muted">Đang tải thiết bị...</small>
                        </div>
                    </div>
                </div>
            `);
            
            $('#eventDetailsModal').modal('show');
            
            // Load equipment details
            loadEventEquipment(eventId);
        }
        
        // Edit event
        function editEvent(eventId) {
            if (confirm('Bạn có chắc chắn muốn sửa sự kiện này?')) {
                // Redirect to edit page or show edit modal
                window.location.href = `register.php?edit=${eventId}`;
            }
        }
        
        // Cancel event
        function cancelEvent(eventId) {
            if (confirm('Bạn có chắc chắn muốn hủy sự kiện này? Hành động này không thể hoàn tác.')) {
                $.ajax({
                    url: '../src/controllers/my-events.php?action=cancel_event',
                    method: 'POST',
                    data: { event_id: eventId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Hủy sự kiện thành công!');
                            loadEvents(); // Reload the events list
                        } else {
                            alert('Lỗi: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Lỗi khi hủy sự kiện. Vui lòng thử lại.');
                    }
                });
            }
        }
        
        // Make payment
        function makePayment(eventId) {
            const event = allEvents.find(e => e.ID_DatLich === eventId);
            if (!event) return;
            
            // Show payment modal
            showPaymentModal(event);
        }
        
        // Show payment modal
        function showPaymentModal(event) {
            const priceBreakdown = getEventPriceBreakdown(event);
            const totalAmount = priceBreakdown.totalPrice || event.TongTien || 0;
            const depositAmount = event.TienCocYeuCau && event.TienCocYeuCau > 0 ? event.TienCocYeuCau : Math.round(totalAmount * 0.3); // 30% deposit
            
            // Debug logging
            console.log('Payment Modal Debug:', {
                event: event,
                priceBreakdown: priceBreakdown,
                totalAmount: totalAmount,
                TongTien: event.TongTien,
                TienCocYeuCau: event.TienCocYeuCau,
                depositAmount: depositAmount,
                GiaCoBan: event.GiaCoBan,
                GiaThueGio: event.GiaThueGio,
                GiaThueNgay: event.GiaThueNgay,
                LoaiThue: event.LoaiThue
            });
            const remainingAmount = totalAmount - depositAmount;
            
            $('#paymentModalTitle').text(`Thanh toán cho: ${event.TenSuKien}`);
            $('#paymentModalBody').html(`
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle text-primary"></i> Thông tin sự kiện</h6>
                        <table class="table table-borderless table-sm">
                            <tr><td><strong>Tên sự kiện:</strong></td><td>${event.TenSuKien}</td></tr>
                            <tr><td><strong>Ngày tổ chức:</strong></td><td>${formatDateTime(event.NgayBatDau)}</td></tr>
                            <tr><td><strong>Địa điểm:</strong></td><td>${event.TenDiaDiem || 'Chưa xác định'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-money-bill-wave text-success"></i> Chi tiết thanh toán</h6>
                        <table class="table table-borderless table-sm">
                            <tr><td><strong>Tổng tiền:</strong></td><td><span class="text-primary fw-bold">${new Intl.NumberFormat('vi-VN').format(totalAmount)} VNĐ</span></td></tr>
                            <tr><td><strong>Tiền cọc yêu cầu:</strong></td><td><span class="text-warning fw-bold">${new Intl.NumberFormat('vi-VN').format(depositAmount)} VNĐ</span></td></tr>
                            <tr><td><strong>Số tiền còn lại:</strong></td><td><span class="text-info fw-bold">${new Intl.NumberFormat('vi-VN').format(remainingAmount)} VNĐ</span></td></tr>
                        </table>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6><i class="fas fa-credit-card text-primary"></i> Chọn phương thức thanh toán</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card payment-method-card" data-method="momo">
                                <div class="card-body text-center">
                                    <i class="fas fa-mobile-alt fa-3x text-danger mb-3"></i>
                                    <h6>Ví MoMo</h6>
                                    <small class="text-muted">Thanh toán qua ví điện tử</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card payment-method-card" data-method="banking">
                                <div class="card-body text-center">
                                    <i class="fas fa-university fa-3x text-primary mb-3"></i>
                                    <h6>Chuyển khoản</h6>
                                    <small class="text-muted">Chuyển khoản ngân hàng</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card payment-method-card" data-method="cash">
                                <div class="card-body text-center">
                                    <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                                    <h6>Tiền mặt</h6>
                                    <small class="text-muted">Thanh toán trực tiếp</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6><i class="fas fa-calculator text-primary"></i> Chọn loại thanh toán</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentType" id="depositPayment" value="deposit" checked>
                                <label class="form-check-label" for="depositPayment">
                                    <strong>Đặt cọc</strong> - ${new Intl.NumberFormat('vi-VN').format(depositAmount)} VNĐ
                                    <br><small class="text-muted">Thanh toán 30% để giữ chỗ</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentType" id="fullPayment" value="full">
                                <label class="form-check-label" for="fullPayment">
                                    <strong>Thanh toán đủ</strong> - ${new Intl.NumberFormat('vi-VN').format(totalAmount)} VNĐ
                                    <br><small class="text-muted">Thanh toán toàn bộ số tiền</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4" id="paymentDetails" style="display: none;">
                    <h6><i class="fas fa-info-circle text-primary"></i> Thông tin thanh toán</h6>
                    <div id="momoDetails" class="payment-details" style="display: none;">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-mobile-alt"></i> Thanh toán qua MoMo Gateway</h6>
                            <div class="text-center">
                                <div class="momo-logo-small mb-3">
                                    <i class="fas fa-mobile-alt fa-2x text-danger"></i>
                                </div>
                                <h6 class="text-danger">MoMo Wallet</h6>
                                <p class="text-muted mb-3">Thanh toán nhanh chóng và bảo mật qua MoMo Gateway</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-shield-alt text-success"></i> Bảo mật</h6>
                                        <ul class="text-start small">
                                            <li>Mã hóa SSL 256-bit</li>
                                            <li>Xác thực 2 lớp</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-bolt text-warning"></i> Tốc độ</h6>
                                        <ul class="text-start small">
                                            <li>Xử lý trong vài giây</li>
                                            <li>Xác nhận ngay lập tức</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Lưu ý:</strong> Sau khi nhấn "Xác nhận thanh toán", bạn sẽ được chuyển hướng đến trang thanh toán MoMo để hoàn tất giao dịch.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="bankingDetails" class="payment-details" style="display: none;">
                        <div class="alert alert-primary">
                            <h6><i class="fas fa-university"></i> Thông tin chuyển khoản</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Ngân hàng:</strong></td><td>Vietcombank</td></tr>
                                <tr><td><strong>Số tài khoản:</strong></td><td>1234567890</td></tr>
                                <tr><td><strong>Chủ tài khoản:</strong></td><td>CÔNG TY TNHH EVENT MANAGEMENT</td></tr>
                                <tr><td><strong>Nội dung:</strong></td><td>THANH TOAN SU KIEN ${event.ID_DatLich}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div id="cashDetails" class="payment-details" style="display: none;">
                        <div class="alert alert-success">
                            <h6><i class="fas fa-money-bill-wave"></i> Thanh toán tiền mặt</h6>
                            <p>Vui lòng liên hệ trực tiếp với chúng tôi để thanh toán:</p>
                            <ul>
                                <li><strong>Địa chỉ:</strong> 123 Đường ABC, Quận 1, TP.HCM</li>
                                <li><strong>Điện thoại:</strong> 0123456789</li>
                                <li><strong>Thời gian:</strong> 8:00 - 17:00 (Thứ 2 - Thứ 6)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `);
            
            // Store event data for payment processing
            $('#paymentModal').data('event', event);
            $('#paymentModal').modal('show');
            
            // Setup payment method selection
            setupPaymentMethodSelection();
        }
        
        // Setup payment method selection
        function setupPaymentMethodSelection() {
            $('.payment-method-card').on('click', function() {
                $('.payment-method-card').removeClass('border-primary bg-light');
                $(this).addClass('border-primary bg-light');
                
                const method = $(this).data('method');
                
                if (method === 'momo') {
                    // Show MoMo payment modal
                    $('#momoPaymentModal').modal('show');
                } else {
                    $('.payment-details').hide();
                    $(`#${method}Details`).show();
                    $('#paymentDetails').show();
                }
            });
        }
        
        // Confirm MoMo payment
        function confirmMoMoPayment() {
            // Close MoMo payment modal
            $('#momoPaymentModal').modal('hide');
            
            // Set MoMo type to gateway (online only)
            $('#paymentModal').data('momoType', 'gateway');
            
            // Show payment details
            $('.payment-details').hide();
            $('#momoDetails').show();
            $('#paymentDetails').show();
        }
        
        // Process payment
        function processPayment() {
            const event = $('#paymentModal').data('event');
            const paymentMethod = $('.payment-method-card.border-primary').data('method');
            const paymentType = $('input[name="paymentType"]:checked').val();
            
            if (!paymentMethod) {
                alert('Vui lòng chọn phương thức thanh toán');
                return;
            }
            
            const priceBreakdown = getEventPriceBreakdown(event);
            const totalEventPrice = priceBreakdown.totalPrice || event.TongTien || 0;
            
            const amount = paymentType === 'deposit' ? 
                (event.TienCocYeuCau && event.TienCocYeuCau > 0 ? event.TienCocYeuCau : Math.round(totalEventPrice * 0.3)) : 
                totalEventPrice;
            
            // Debug logging
            console.log('Process Payment Debug:', {
                paymentType: paymentType,
                totalEventPrice: totalEventPrice,
                TienCocYeuCau: event.TienCocYeuCau,
                amount: amount,
                priceBreakdown: priceBreakdown
            });
            
            if (confirm(`Xác nhận thanh toán ${new Intl.NumberFormat('vi-VN').format(amount)} VNĐ qua ${getPaymentMethodName(paymentMethod)}?`)) {
                // Show loading
                $('#paymentModalFooter').html(`
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang xử lý...</span>
                        </div>
                        <p class="mt-2">Đang xử lý thanh toán...</p>
                    </div>
                `);
                
                // Choose payment API based on method
                let apiAction, apiData;
                
                if (paymentMethod === 'momo') {
                    // Use MoMo official API (Gateway only)
                    apiAction = 'create_momo_payment';
                    apiData = {
                        action: apiAction,
                        event_id: event.ID_DatLich,
                        amount: amount,
                        payment_type: paymentType
                    };
                } else {
                    // Use offline payment (QR code)
                    apiAction = 'create_payment';
                    apiData = {
                        action: apiAction,
                        event_id: event.ID_DatLich,
                        amount: amount,
                        payment_method: paymentMethod,
                        payment_type: paymentType
                    };
                }
                
                // Process payment
                $.ajax({
                    url: '../src/controllers/payment.php',
                    method: 'POST',
                    data: apiData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (paymentMethod === 'momo' && response.pay_url) {
                                // Redirect to MoMo Gateway
                                $('#paymentModalFooter').html(`
                                    <div class="text-center">
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i>
                                            <strong>Đang chuyển hướng đến MoMo...</strong>
                                        </div>
                                        <p>Vui lòng đợi trong giây lát...</p>
                                    </div>
                                `);
                                
                                // Redirect to MoMo Gateway
                                setTimeout(() => {
                                    window.location.href = response.pay_url;
                                }, 2000);
                            } else if (response.qr_code) {
                                // Show QR code for offline payment
                                showQRCodeModal(response);
                            } else {
                                alert('Thanh toán thành công! Chúng tôi sẽ xác nhận trong thời gian sớm nhất.');
                                $('#paymentModal').modal('hide');
                                loadMyEvents(); // Reload events
                            }
                        } else {
                            alert('Lỗi thanh toán: ' + response.error);
                            resetPaymentModal();
                        }
                    },
                    error: function() {
                        alert('Lỗi kết nối. Vui lòng thử lại.');
                        resetPaymentModal();
                    }
                });
            }
        }
        
        // Get payment method name
        function getPaymentMethodName(method) {
            switch(method) {
                case 'momo': return 'Ví MoMo';
                case 'banking': return 'Chuyển khoản ngân hàng';
                case 'cash': return 'Tiền mặt';
                default: return 'Phương thức thanh toán';
            }
        }
        
        // Reset payment modal
        function resetPaymentModal() {
            $('#paymentModalFooter').html(`
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="processPayment()">
                    <i class="fas fa-credit-card"></i> Xác nhận thanh toán
                </button>
            `);
        }
        
        // Show QR Code Modal
        function showQRCodeModal(paymentData) {
            const event = $('#paymentModal').data('event');
            const paymentMethod = $('.payment-method-card.border-primary').data('method');
            const paymentType = $('input[name="paymentType"]:checked').val();
            
            $('#paymentModalTitle').text(`Mã QR thanh toán - ${event.TenSuKien}`);
            $('#paymentModalBody').html(`
                <div class="text-center">
                    <div class="alert alert-info">
                        <i class="fas fa-qrcode"></i>
                        <strong>Quét mã QR để thanh toán</strong>
                    </div>
                    
                    <div class="qr-container mb-4">
                        <div id="qrcode" class="d-flex justify-content-center"></div>
                    </div>
                    
                    <div class="payment-info">
                        <h6><i class="fas fa-info-circle text-primary"></i> Thông tin thanh toán</h6>
                        <table class="table table-borderless table-sm">
                            <tr><td><strong>Số tiền:</strong></td><td><span class="text-primary fw-bold">${new Intl.NumberFormat('vi-VN').format(paymentData.amount)} VNĐ</span></td></tr>
                            <tr><td><strong>Loại thanh toán:</strong></td><td>${paymentType === 'deposit' ? 'Đặt cọc' : 'Thanh toán đủ'}</td></tr>
                            <tr><td><strong>Phương thức:</strong></td><td>${getPaymentMethodName(paymentMethod)}</td></tr>
                            <tr><td><strong>Mã giao dịch:</strong></td><td><code>${paymentData.transaction_code}</code></td></tr>
                        </table>
                    </div>
                    
                    ${paymentMethod === 'momo' ? `
                    <div class="alert alert-warning">
                        <i class="fas fa-mobile-alt"></i>
                        <strong>Hướng dẫn:</strong>
                        <ol class="text-start mt-2">
                            <li>Mở ứng dụng MoMo trên điện thoại</li>
                            <li>Chọn "Quét mã QR" hoặc "Chuyển tiền"</li>
                            <li>Quét mã QR ở trên</li>
                            <li>Xác nhận thanh toán</li>
                        </ol>
                    </div>
                    ` : ''}
                    
                    ${paymentMethod === 'banking' ? `
                    <div class="alert alert-primary">
                        <i class="fas fa-university"></i>
                        <strong>Hướng dẫn:</strong>
                        <ol class="text-start mt-2">
                            <li>Mở ứng dụng ngân hàng trên điện thoại</li>
                            <li>Chọn "Chuyển khoản" hoặc "Quét mã QR"</li>
                            <li>Quét mã QR ở trên</li>
                            <li>Xác nhận thông tin và thanh toán</li>
                        </ol>
                    </div>
                    ` : ''}
                    
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <strong>Sau khi thanh toán:</strong>
                        <p class="mb-0 mt-1">Hệ thống sẽ tự động cập nhật trạng thái thanh toán. Bạn có thể đóng cửa sổ này.</p>
                    </div>
                </div>
            `);
            
            $('#paymentModalFooter').html(`
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="checkPaymentStatus('${paymentData.transaction_code}')">
                    <i class="fas fa-sync"></i> Kiểm tra trạng thái
                </button>
            `);
            
            // Generate QR Code
            generateQRCode(paymentData.qr_code);
        }
        
        // Generate QR Code
        function generateQRCode(qrString) {
            // Simple QR code generation using a library or service
            // For now, we'll use a QR code service
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrString)}`;
            
            $('#qrcode').html(`
                <img src="${qrUrl}" alt="QR Code" class="img-fluid border rounded" style="max-width: 300px;">
            `);
        }
        
        // Check payment status
        function checkPaymentStatus(transactionCode) {
            $('#paymentModalFooter').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang kiểm tra...</span>
                    </div>
                    <p class="mt-2">Đang kiểm tra trạng thái thanh toán...</p>
                </div>
            `);
            
            // Find the payment record and check status
            $.ajax({
                url: '../src/controllers/payment.php',
                method: 'POST',
                data: {
                    action: 'get_payment_history'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const payment = response.payments.find(p => p.MaGiaoDich && p.MaGiaoDich.includes(transactionCode));
                        if (payment) {
                            if (payment.TrangThai === 'Thành công') {
                                alert('Thanh toán thành công! Trạng thái đã được cập nhật.');
                                $('#paymentModal').modal('hide');
                                loadMyEvents(); // Reload events
                            } else if (payment.TrangThai === 'Thất bại') {
                                alert('Thanh toán thất bại. Vui lòng thử lại.');
                                resetPaymentModal();
                            } else {
                                alert('Thanh toán đang được xử lý. Vui lòng đợi thêm.');
                                resetPaymentModal();
                            }
                        } else {
                            alert('Chưa tìm thấy thông tin thanh toán. Vui lòng thử lại sau.');
                            resetPaymentModal();
                        }
                    } else {
                        alert('Lỗi khi kiểm tra trạng thái thanh toán.');
                        resetPaymentModal();
                    }
                },
                error: function() {
                    alert('Lỗi kết nối khi kiểm tra trạng thái.');
                    resetPaymentModal();
                }
            });
        }
        
        // Load event equipment
        function loadEventEquipment(eventId) {
            $.get(`../src/controllers/event-register.php?action=get_event_equipment&event_id=${eventId}`, function(data) {
                if (data.success) {
                    displayEventEquipment(eventId, data.equipment);
                } else {
                    $(`#equipmentDetails_${eventId}`).html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Không có thiết bị nào được đăng ký cho sự kiện này.
                        </div>
                    `);
                }
            }, 'json').fail(function() {
                $(`#equipmentDetails_${eventId}`).html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Lỗi khi tải thông tin thiết bị.
                    </div>
                `);
            });
        }
        
        // Display event equipment
        function displayEventEquipment(eventId, equipment) {
            if (!equipment || equipment.length === 0) {
                $(`#equipmentDetails_${eventId}`).html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Chưa có thiết bị nào được đăng ký cho sự kiện này.
                    </div>
                `);
                return;
            }
            
            let html = '<div class="table-responsive"><table class="table table-sm table-striped">';
            html += '<thead class="table-dark"><tr><th>Tên thiết bị</th><th>Loại</th><th>Hãng</th><th>Số lượng</th><th>Đơn vị</th><th>Giá</th><th>Ghi chú</th></tr></thead><tbody>';
            
            equipment.forEach(item => {
                if (item.TenCombo) {
                    // Combo equipment
                    html += `
                        <tr>
                            <td><strong><i class="fas fa-box text-primary"></i> ${item.TenCombo}</strong></td>
                            <td><span class="badge bg-info">Combo</span></td>
                            <td>N/A</td>
                            <td><span class="badge bg-primary">${item.SoLuong || 1}</span></td>
                            <td>combo</td>
                            <td><strong class="text-success">${new Intl.NumberFormat('vi-VN').format(item.DonGia || item.GiaCombo || 0)} VNĐ</strong></td>
                            <td>${item.GhiChu || 'Combo thiết bị'}</td>
                        </tr>
                    `;
                } else {
                    // Individual equipment
                    html += `
                        <tr>
                            <td><strong><i class="fas fa-cog text-primary"></i> ${item.TenThietBi || 'N/A'}</strong></td>
                            <td>${item.LoaiThietBi || 'N/A'}</td>
                            <td>${item.HangSX || 'N/A'}</td>
                            <td><span class="badge bg-primary">${item.SoLuong || 1}</span></td>
                            <td>${item.DonViTinh || 'cái'}</td>
                            <td><strong class="text-success">${new Intl.NumberFormat('vi-VN').format(item.DonGia || item.GiaThue || 0)} VNĐ</strong></td>
                            <td>${item.GhiChu || 'Thiết bị riêng lẻ'}</td>
                        </tr>
                    `;
                }
            });
            
            html += '</tbody></table></div>';
            $(`#equipmentDetails_${eventId}`).html(html);
        }
        
        // View event details
        function viewEventDetails(eventId) {
            // Update modal title
            document.getElementById('eventDetailsModalLabel').innerHTML = 
                `<i class="fas fa-info-circle"></i> Chi tiết sự kiện`;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
            modal.show();
            
            // Show loading
            document.getElementById('eventDetailsModalBody').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải chi tiết sự kiện...</p>
                </div>
            `;
            
            // Load event details
            fetch(`../src/controllers/event-details.php?action=get_event_details&event_id=${eventId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayEventDetails(data.event, data.reviews, data.equipment);
                    } else {
                        throw new Error(data.message || 'Không thể tải chi tiết sự kiện');
                    }
                })
                .catch(error => {
                    console.error('Error loading event details:', error);
                    document.getElementById('eventDetailsModalBody').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Có lỗi xảy ra khi tải chi tiết sự kiện: ${error.message}
                        </div>
                    `;
                });
        }
        
        // Display event details in modal
        function displayEventDetails(event, reviews, equipment) {
            const eventDate = formatDateTime(event.NgayBatDau);
            const eventEndDate = formatDateTime(event.NgayKetThuc);
            const priceBreakdown = getEventPriceBreakdown(event);
            const totalPrice = new Intl.NumberFormat('vi-VN').format(priceBreakdown.totalPrice);
            
            // Get status badge class
            const statusClass = getStatusClass(event.TrangThaiDuyet);
            const paymentClass = getPaymentClass(event.TrangThaiThanhToan);
            
            // Get location image
            const locationImage = event.DiaDiemHinhAnh ? 
                `img/diadiem/${event.DiaDiemHinhAnh}` : 
                'img/diadiem/default.jpg';
            
            let html = `
                <div class="event-details-content">
                    <!-- Event Image Section -->
                    <div class="event-image-section">
                        <img src="${locationImage}" alt="${event.TenDiaDiem}" class="event-main-image" 
                             onerror="this.src='img/diadiem/default.jpg'">
                        <span class="event-status-badge ${statusClass}">${event.TrangThaiDuyet}</span>
                    </div>
                    
                    <!-- Event Info Section -->
                    <div class="event-info-section">
                        <h3 class="mb-3"><i class="fas fa-calendar-alt"></i> Thông tin sự kiện</h3>
                        
                        <div class="event-info-item">
                            <i class="fas fa-tag"></i>
                            <div class="event-info-content">
                                <div class="event-info-label">Tên sự kiện</div>
                                <div class="event-info-value">${event.TenSuKien}</div>
                            </div>
                        </div>
                        
                        <div class="event-info-item">
                            <i class="fas fa-calendar"></i>
                            <div class="event-info-content">
                                <div class="event-info-label">Thời gian</div>
                                <div class="event-info-value">${eventDate} - ${eventEndDate}</div>
                            </div>
                        </div>
                        
                        <div class="event-info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="event-info-content">
                                <div class="event-info-label">Địa điểm</div>
                                <div class="event-info-value">${event.TenDiaDiem || 'Chưa xác định'}</div>
                            </div>
                        </div>
                        
                        ${event.DiaChi ? `
                        <div class="event-info-item">
                            <i class="fas fa-location-arrow"></i>
                            <div class="event-info-content">
                                <div class="event-info-label">Địa chỉ</div>
                                <div class="event-info-value">${event.DiaChi}</div>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="event-info-item">
                            <i class="fas fa-users"></i>
                            <div class="event-info-content">
                                <div class="event-info-label">Số người dự kiến</div>
                                <div class="event-info-value">${event.SoNguoiDuKien || 'Chưa xác định'} người</div>
                            </div>
                        </div>
                        
                        ${event.TenLoai ? `
                        <div class="event-info-item">
                            <i class="fas fa-star"></i>
                            <div class="event-info-content">
                                <div class="event-info-label">Loại sự kiện</div>
                                <div class="event-info-value">${event.TenLoai}</div>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="event-info-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <div class="event-info-content">
                                <div class="event-info-label">Tổng giá</div>
                                <div class="event-info-value text-success">${totalPrice} VNĐ</div>
                            </div>
                        </div>
                        
                        <div class="event-info-item">
                            <i class="fas fa-info-circle"></i>
                            <div class="event-info-content">
                                <div class="event-info-label">Trạng thái thanh toán</div>
                                <div class="event-info-value">
                                    <span class="badge ${paymentClass}">${event.TrangThaiThanhToan}</span>
                                </div>
                            </div>
                        </div>
                        
                        ${event.MoTa ? `
                        <div class="event-info-item">
                            <i class="fas fa-file-alt"></i>
                            <div class="event-info-content">
                                <div class="event-info-label">Mô tả</div>
                                <div class="event-info-value">${event.MoTa}</div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
            `;
            
            // Add equipment section if exists
            if (equipment && equipment.length > 0) {
                html += `
                    <div class="equipment-section">
                        <h4 class="mb-3"><i class="fas fa-cogs"></i> Thiết bị đã đặt</h4>
                `;
                
                equipment.forEach(item => {
                    const itemName = item.TenThietBi || item.TenCombo || 'Thiết bị';
                    const itemImage = item.ThietBiHinhAnh;
                    const imagePath = itemImage ? 
                        `img/thietbi/${itemImage}` : 
                        'img/thietbi/default.jpg';
                    const itemType = item.TenThietBi ? 'Thiết bị' : 'Combo';
                    const itemPrice = new Intl.NumberFormat('vi-VN').format(item.DonGia);
                    
                    html += `
                        <div class="equipment-item">
                            <img src="${imagePath}" alt="${itemName}" class="equipment-image" 
                                 onerror="this.src='img/thietbi/default.jpg'">
                            <div class="equipment-info">
                                <div class="equipment-name">${itemName}</div>
                                <div class="equipment-details">
                                    ${itemType} • Số lượng: ${item.SoLuong}
                                    ${item.LoaiThietBi ? ` • Loại: ${item.LoaiThietBi}` : ''}
                                </div>
                            </div>
                            <div class="equipment-price">${itemPrice} VNĐ</div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            // Add reviews section
            if (reviews && reviews.length > 0) {
                html += `
                    <div class="reviews-section">
                        <h4 class="mb-3"><i class="fas fa-star"></i> Đánh giá của khách hàng</h4>
                `;
                
                reviews.forEach(review => {
                    const reviewTime = review.ThoiGianDanhGia ? 
                        new Date(review.ThoiGianDanhGia).toLocaleString('vi-VN') : 
                        'Vừa đánh giá';
                    const stars = '⭐'.repeat(review.DiemDanhGia);
                    
                    html += `
                        <div class="review-item">
                            <div class="review-header">
                                <div class="review-rating">
                                    <span class="review-stars">${stars}</span>
                                    <span class="review-author">${review.TenKhachHang || 'Khách hàng'}</span>
                                </div>
                                <div class="review-time">${reviewTime}</div>
                            </div>
                            ${review.NoiDung ? `
                            <div class="review-content">"${review.NoiDung}"</div>
                            ` : ''}
                        </div>
                    `;
                });
                
                html += `</div>`;
            } else {
                html += `
                    <div class="reviews-section">
                        <div class="text-center text-muted">
                            <i class="fas fa-star fa-3x mb-3"></i>
                            <h5>Chưa có đánh giá nào</h5>
                            <p>Sự kiện này chưa có đánh giá từ khách hàng.</p>
                        </div>
                    </div>
                `;
            }
            
            html += `</div>`;
            
            document.getElementById('eventDetailsModalBody').innerHTML = html;
        }
        
        // Open review modal
        function openReviewModal(eventId, eventName) {
            // Update modal title
            document.getElementById('reviewModalLabel').innerHTML = 
                `<i class="fas fa-star"></i> Đánh giá sự kiện: ${eventName}`;
            
            // Show modal immediately with basic form
            const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
            modal.show();
            
            // Show basic form first
            const basicFormHTML = `
                <div class="review-form">
                    <form id="reviewFormModal">
                        <input type="hidden" name="event_id" value="${eventId}">
                        
                        <!-- Overall Rating -->
                        <div class="rating-section">
                            <div class="rating-title">
                                <i class="fas fa-star"></i>
                                Đánh giá tổng thể (Bắt buộc)
                            </div>
                            <div class="star-rating" data-rating="overall">
                                <i class="fas fa-star star" data-value="1"></i>
                                <i class="fas fa-star star" data-value="2"></i>
                                <i class="fas fa-star star" data-value="3"></i>
                                <i class="fas fa-star star" data-value="4"></i>
                                <i class="fas fa-star star" data-value="5"></i>
                            </div>
                            <input type="hidden" name="overall_rating" value="0">
                            <small class="text-muted">Nhấp vào sao để chọn điểm (1-5 sao)</small>
                        </div>
                        
                        <!-- Comment Section -->
                        <div class="comment-section">
                            <div class="comment-title">
                                <i class="fas fa-comment"></i>
                                Nội dung đánh giá
                            </div>
                            <textarea 
                                name="comment" 
                                class="form-control" 
                                rows="4" 
                                placeholder="Hãy chia sẻ trải nghiệm của bạn về sự kiện này..."
                                maxlength="1000"
                            ></textarea>
                            <small class="text-muted">Tối đa 1000 ký tự</small>
                        </div>
                        
                        <button type="submit" class="btn-submit-review">
                            <i class="fas fa-paper-plane"></i>
                            Gửi đánh giá
                        </button>
                    </form>
                </div>
            `;
            
            document.getElementById('reviewModalBody').innerHTML = basicFormHTML;
            
            // Initialize star rating
            initializeStarRating();
            
            // Try to load existing review data in background
            loadExistingReviewData(eventId);
        }
        
        // Load existing review data in background
        function loadExistingReviewData(eventId) {
            fetch(`../src/controllers/review-controller.php?action=get_user_review&event_id=${eventId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Existing review data:', data);
                    if (data.success && data.review) {
                        // Update form with existing data
                        updateFormWithExistingData(data.review);
                    }
                })
                .catch(error => {
                    console.log('No existing review found or error:', error);
                    // This is normal if user hasn't reviewed yet
                });
        }
        
        // Update form with existing review data
        function updateFormWithExistingData(review) {
            // Add existing review info
            const existingReviewDiv = document.createElement('div');
            existingReviewDiv.className = 'success-message-modal';
            
            // Format time display - use current time if ThoiGianDanhGia is empty
            let timeDisplay = 'Vừa đánh giá';
            if (review.ThoiGianDanhGia && review.ThoiGianDanhGia !== '') {
                try {
                    timeDisplay = new Date(review.ThoiGianDanhGia).toLocaleString('vi-VN');
                } catch (e) {
                    timeDisplay = 'Vừa đánh giá';
                }
            }
            
            existingReviewDiv.innerHTML = `
                <div class="d-flex align-items-start">
                    <i class="fas fa-check-circle fa-2x me-3" style="color: #28a745;"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-2"><strong>Bạn đã đánh giá sự kiện này!</strong></h6>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">Điểm đánh giá:</small><br>
                                <span class="badge bg-success fs-6">${review.DiemDanhGia}/5 ⭐</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Thời gian:</small><br>
                                <span class="text-success">${timeDisplay}</span>
                            </div>
                        </div>
                        ${review.NoiDung ? `
                        <div class="mt-2">
                            <small class="text-muted">Nội dung đánh giá:</small><br>
                            <em class="text-dark">"${review.NoiDung}"</em>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            const form = document.getElementById('reviewFormModal');
            form.insertBefore(existingReviewDiv, form.firstChild);
            
            // Update rating
            const overallStars = document.querySelector('[data-rating="overall"]').querySelectorAll('.star');
            const overallValue = review.DiemDanhGia;
            document.querySelector('input[name="overall_rating"]').value = overallValue;
            overallStars.forEach((star, i) => {
                star.classList.toggle('active', i < overallValue);
            });
            
            // Update comment
            const textarea = document.querySelector('textarea[name="comment"]');
            if (textarea) {
                textarea.value = review.NoiDung || '';
            }
            
            // Update button text
            const submitBtn = document.querySelector('.btn-submit-review');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Cập nhật đánh giá';
            }
        }
        
        // Initialize star rating functionality
        function initializeStarRating() {
            document.querySelectorAll('.star-rating').forEach(rating => {
                const stars = rating.querySelectorAll('.star');
                const hiddenInput = rating.parentElement.querySelector('input[type="hidden"]');
                
                stars.forEach((star, index) => {
                    star.addEventListener('click', () => {
                        const value = index + 1;
                        hiddenInput.value = value;
                        
                        // Update star display
                        stars.forEach((s, i) => {
                            s.classList.toggle('active', i < value);
                        });
                    });
                    
                    star.addEventListener('mouseenter', () => {
                        stars.forEach((s, i) => {
                            s.classList.toggle('active', i <= index);
                        });
                    });
                });
                
                rating.addEventListener('mouseleave', () => {
                    const currentValue = parseInt(hiddenInput.value) || 0;
                    stars.forEach((s, i) => {
                        s.classList.toggle('active', i < currentValue);
                    });
                });
            });
            
            // Form submission
            const form = document.getElementById('reviewFormModal');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const overallRating = formData.get('overall_rating');
                    const comment = formData.get('comment');
                    
                    if (overallRating == 0) {
                        alert('Vui lòng chọn điểm đánh giá tổng thể!');
                        return;
                    }
                    
                    if (!comment.trim()) {
                        alert('Vui lòng nhập nội dung đánh giá!');
                        return;
                    }
                    
                    // Show loading
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
                    submitBtn.disabled = true;
                    
                    fetch('../src/controllers/review-controller.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.text(); // Get as text first to debug
                    })
                    .then(text => {
                        console.log('Raw response:', text);
                        try {
                            const data = JSON.parse(text);
                            console.log('Parsed data:', data);
                            
                            if (data.success) {
                                // Show success message
                                const successDiv = document.createElement('div');
                                successDiv.className = 'success-message-modal';
                                successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                                
                                this.insertBefore(successDiv, this.firstChild);
                                
                                // Close modal after 2 seconds
                                setTimeout(() => {
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                                    modal.hide();
                                    
                                    // Reload events to update UI
                                    loadEvents();
                                }, 2000);
                            } else {
                                alert('Lỗi: ' + data.message);
                            }
                        } catch (parseError) {
                            console.error('JSON parse error:', parseError);
                            console.error('Response text:', text);
                            
                            // Show error message in modal
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'alert alert-danger';
                            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Lỗi định dạng phản hồi từ server!';
                            this.insertBefore(errorDiv, this.firstChild);
                        }
                    })
                    .catch(error => {
                        console.error('Network error:', error);
                        
                        // Show error message in modal
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-danger';
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra khi gửi đánh giá: ' + error.message;
                        this.insertBefore(errorDiv, this.firstChild);
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            }
        }
        
        // Set existing review data
        function setExistingReviewData(review) {
            // Set overall rating
            const overallStars = document.querySelector('[data-rating="overall"]').querySelectorAll('.star');
            const overallValue = review.DiemDanhGia;
            document.querySelector('input[name="overall_rating"]').value = overallValue;
            overallStars.forEach((star, i) => {
                star.classList.toggle('active', i < overallValue);
            });
        }
        
        // Show error message
        function showError(message) {
            $('#errorMessage').text(message).show();
            setTimeout(() => {
                $('#errorMessage').hide();
            }, 5000);
        }
    </script>
</body>
</html>
