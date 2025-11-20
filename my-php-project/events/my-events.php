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
    <link rel="icon" href="../img/logo/logo.jpg">
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
            padding: 1rem;
        }
        
        .event-details-content h4 {
            color: #495057;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .detail-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.25rem;
            height: 100%;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .section-header {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #dee2e6;
            font-size: 1rem;
        }
        
        .section-header i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        .detail-section .table {
            margin-bottom: 0;
        }
        
        .detail-section .table td {
            padding: 0.5rem 0.5rem 0.5rem 0;
            vertical-align: middle;
            border: none;
        }
        
        .detail-section .table td:first-child {
            width: 45%;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .detail-section .table td:last-child {
            color: #333;
            font-weight: 500;
        }
        
        .detail-section .badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            font-weight: 600;
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
        
        .sepay-logo {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .sepay-logo i {
            color: white !important;
        }
        
        .sepay-logo-small {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .sepay-logo-small i {
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
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            text-align: center;
        }
        
        .payment-info-compact {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .bank-info-compact {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            border: 1px solid #e9ecef;
            margin-top: 0.75rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
        
        .info-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        
        .info-item:hover {
            background: rgba(0, 0, 0, 0.02);
            border-radius: 6px;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item small {
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .info-item strong {
            color: #495057;
            font-weight: 700;
        }
        
        .info-item code {
            background: #f8f9fa;
            color: #e83e8c;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .info-item .text-primary {
            color: #007bff !important;
            font-weight: 700;
        }
        
        .bank-info-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            border: 1px solid #dee2e6;
        }
        
        .bank-info-card h6 {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        
        .bank-info-card table td {
            padding: 0.5rem 0;
            vertical-align: top;
        }
        
        .bank-info-card code {
            background: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        /* Alert Styling */
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%) !important;
            border: none !important;
            border-radius: 12px !important;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1) !important;
            color: #0c5460 !important;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
            border: none !important;
            border-radius: 12px !important;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1) !important;
            color: #155724 !important;
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
        
        /* Modal Styling */
        .modal-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            border: none;
            padding: 1.25rem 1.5rem;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.8;
        }
        
        .modal-header .btn-close:hover {
            opacity: 1;
        }
        
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .modal-footer {
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
            border: none;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        
        .modal-footer .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
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
            

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
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
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="processPayment()">
                        <i class="fas fa-credit-card"></i> Xác nhận thanh toán
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
    
    <!-- Invoice Modal -->
    <div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="modal-title" id="invoiceModalLabel">
                        <i class="fas fa-file-invoice"></i> Hóa đơn thanh toán
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="invoiceModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                        <p class="mt-2">Đang tải thông tin hóa đơn...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="printInvoice()">
                        <i class="fas fa-print"></i> In hóa đơn
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- CSRF Protection Helper - Load after jQuery to override AJAX -->
    <script src="../assets/js/csrf-helper.js"></script>
    <script>
        // Pass user data from PHP to JavaScript
        const currentUser = <?= json_encode($user) ?>;
        console.log('Current user:', currentUser);
        
        let allEvents = [];
        let filteredEvents = [];
        
        // Thông tin công ty (hardcode)
        const companyInfo = {
            TenCongTy: 'CÔNG TY TNHH EVENT MANAGEMENT',
            DiaChi: '123 Đường ABC, Quận 1, TP.HCM',
            SoDienThoai: '0123456789',
            Email: 'info@eventmanagement.com',
            GioLamViec: '8:00 - 17:00 (Thứ 2 - Thứ 6)'
        };
        
        // Helper function to get location price text
        function getLocationPriceText(event) {
            if (!event) return 'Chưa có giá';
            
            // Nếu là địa điểm trong nhà và có phòng, hiển thị giá phòng
            const isIndoor = event.LoaiDiaDiem === 'Trong nhà' || event.LoaiDiaDiem === 'Trong nha';
            const hasRoom = event.TenPhong && event.ID_Phong;
            
            if (isIndoor && hasRoom) {
                // Hiển thị giá phòng
                if (event.LoaiThueApDung === 'Theo giờ' && event.PhongGiaThueGio) {
                    return `Phòng: ${new Intl.NumberFormat('vi-VN').format(event.PhongGiaThueGio)}/giờ`;
                }
                if (event.LoaiThueApDung === 'Theo ngày' && event.PhongGiaThueNgay) {
                    return `Phòng: ${new Intl.NumberFormat('vi-VN').format(event.PhongGiaThueNgay)}/ngày`;
                }
                if (event.PhongLoaiThue === 'Theo giờ' && event.PhongGiaThueGio) {
                    return `Phòng: ${new Intl.NumberFormat('vi-VN').format(event.PhongGiaThueGio)}/giờ`;
                }
                if (event.PhongLoaiThue === 'Theo ngày' && event.PhongGiaThueNgay) {
                    return `Phòng: ${new Intl.NumberFormat('vi-VN').format(event.PhongGiaThueNgay)}/ngày`;
                }
                if (event.PhongLoaiThue === 'Cả hai' && event.PhongGiaThueGio && event.PhongGiaThueNgay) {
                    return `Phòng: ${new Intl.NumberFormat('vi-VN').format(event.PhongGiaThueGio)}/giờ hoặc ${new Intl.NumberFormat('vi-VN').format(event.PhongGiaThueNgay)}/ngày`;
                }
                if (event.PhongGiaThueGio && event.PhongGiaThueGio > 0) {
                    return `Phòng: ${new Intl.NumberFormat('vi-VN').format(event.PhongGiaThueGio)}/giờ`;
                }
                if (event.PhongGiaThueNgay && event.PhongGiaThueNgay > 0) {
                    return `Phòng: ${new Intl.NumberFormat('vi-VN').format(event.PhongGiaThueNgay)}/ngày`;
                }
            }
            
            // Địa điểm ngoài trời hoặc không có phòng - hiển thị giá địa điểm
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
            
            // Nếu là địa điểm trong nhà và có phòng, tính giá theo phòng
            const isIndoor = event.LoaiDiaDiem === 'Trong nhà' || event.LoaiDiaDiem === 'Trong nha';
            const hasRoom = event.TenPhong && event.ID_Phong;
            
            if (isIndoor && hasRoom) {
                // Tính giá theo phòng
                const startDate = new Date(event.NgayBatDau);
                const endDate = new Date(event.NgayKetThuc);
                const durationMs = endDate - startDate;
                const durationHours = Math.ceil(durationMs / (1000 * 60 * 60));
                const durationDays = Math.ceil(durationMs / (1000 * 60 * 60 * 24));
                
                if (event.LoaiThueApDung === 'Theo giờ' && event.PhongGiaThueGio) {
                    locationPrice = durationHours * parseFloat(event.PhongGiaThueGio);
                } else if (event.LoaiThueApDung === 'Theo ngày' && event.PhongGiaThueNgay) {
                    locationPrice = durationDays * parseFloat(event.PhongGiaThueNgay);
                } else if (event.PhongLoaiThue === 'Theo giờ' && event.PhongGiaThueGio) {
                    locationPrice = durationHours * parseFloat(event.PhongGiaThueGio);
                } else if (event.PhongLoaiThue === 'Theo ngày' && event.PhongGiaThueNgay) {
                    locationPrice = durationDays * parseFloat(event.PhongGiaThueNgay);
                } else if (event.PhongLoaiThue === 'Cả hai') {
                    const hourlyPrice = durationHours * parseFloat(event.PhongGiaThueGio || 0);
                    const dailyPrice = durationDays * parseFloat(event.PhongGiaThueNgay || 0);
                    if (hourlyPrice > 0 && dailyPrice > 0) {
                        locationPrice = Math.min(hourlyPrice, dailyPrice);
                    } else if (hourlyPrice > 0) {
                        locationPrice = hourlyPrice;
                    } else if (dailyPrice > 0) {
                        locationPrice = dailyPrice;
                    }
                }
                
                console.log('Room price calculation:', {
                    isIndoor: isIndoor,
                    hasRoom: hasRoom,
                    TenPhong: event.TenPhong,
                    PhongGiaThueGio: event.PhongGiaThueGio,
                    PhongGiaThueNgay: event.PhongGiaThueNgay,
                    locationPrice: locationPrice
                });
            } else if (event.LoaiThueApDung) {
                // Địa điểm ngoài trời hoặc không có phòng - tính theo địa điểm
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

            // Cancel pending payment when modal is closed and restore UI
            const paymentModalEl = document.getElementById('paymentModal');
            if (paymentModalEl) {
                paymentModalEl.addEventListener('hidden.bs.modal', function () {
                    try {
                        const paymentData = $('#paymentModal').data('paymentData');
                        // Clear modal UI immediately
                        resetPaymentModal();
                        // If a pending transaction exists, request cancel
                        if (paymentData && paymentData.transaction_id) {
                            $.ajax({
                                url: '../src/controllers/payment.php',
                                method: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'cancel_payment',
                                    transaction_id: paymentData.transaction_id
                                }
                            }).always(function() {
                                // Clean stored data and refresh events to show payment button again
                                $('#paymentModal').removeData('paymentData');
                                loadMyEvents();
                            });
                        } else {
                            // No created payment → just refresh list
                            loadMyEvents();
                        }
                    } catch (e) {
                        console.error('Error handling modal close:', e);
                        loadMyEvents();
                    }
                });
            }
        });
        
        // Check if event has passed payment deadline (event end time)
        function isEventExpired(event) {
            if (!event.NgayKetThuc) return false;
            
            const eventEndTime = new Date(event.NgayKetThuc);
            const now = new Date();
            
            return eventEndTime < now;
        }
        
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
                    
                    // Show notification if expired events were auto-cancelled
                    if (data.cancelled_expired && data.cancelled_expired > 0) {
                        showError(`Có ${data.cancelled_expired} sự kiện đã qua thời gian tổ chức và chưa thanh toán đủ đã được tự động hủy.`);
                    }
                    
                    // Show notification if events were cancelled due to payment deadline
                    if (data.cancelled_deadline && data.cancelled_deadline > 0) {
                        showError(`Có ${data.cancelled_deadline} sự kiện đã quá hạn thanh toán đủ và đã bị tự động hủy. Tiền cọc không được hoàn lại.`);
                    }
                    
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
                // Tạo text hiển thị trạng thái thanh toán chi tiết
                const paymentStatusText = getPaymentStatusText(event);
                const eventDate = formatDateTime(event.NgayBatDau);
                const eventEndDate = formatDateTime(event.NgayKetThuc);
                const priceText = getLocationPriceText(event);
                const priceBreakdown = getEventPriceBreakdown(event);
                const totalPrice = new Intl.NumberFormat('vi-VN').format(priceBreakdown.totalPrice);
                
                // Check if event has expired (passed end time)
                const isExpired = isEventExpired(event);
                const isFullyPaid = (event.TrangThaiThanhToan || 'Chưa thanh toán') === 'Đã thanh toán đủ';
                const hasDeposit = (event.TrangThaiThanhToan || 'Chưa thanh toán') === 'Đã đặt cọc';
                const showExpiredWarning = isExpired && !isFullyPaid;
                const paymentDeadline = event.PaymentDeadline || null;
                const isPendingApproval = (event.TrangThaiDuyet || 'Chờ duyệt') === 'Chờ duyệt';
                const daysFromRegistration = event.DaysFromRegistrationToEvent || 0;
                
                html += `
                    <div class="event-card">
                        ${showExpiredWarning ? `
                        <div class="alert alert-danger mb-2 py-2" role="alert">
                            <i class="fas fa-ban"></i> <strong>Đã hết hạn:</strong> Sự kiện đã qua thời gian tổ chức và chưa thanh toán đủ. Đã tự động hủy.
                        </div>
                        ` : ''}
                        ${!showExpiredWarning && isPendingApproval && !isFullyPaid ? `
                        ${daysFromRegistration < 3 ? `
                        <div class="alert alert-warning mb-2 py-2" role="alert">
                            <i class="fas fa-clock"></i> <strong>Chờ duyệt sự kiện và thanh toán:</strong> Đăng ký trước ngày diễn ra.
                        </div>
                        ` : daysFromRegistration >= 7 ? `
                        <div class="alert alert-info mb-2 py-2" role="alert">
                            <i class="fas fa-info-circle"></i> <strong>Chờ duyệt sự kiện và thanh toán:</strong> Có 7 ngày để thanh toán.
                        </div>
                        ` : `
                        <div class="alert alert-info mb-2 py-2" role="alert">
                            <i class="fas fa-info-circle"></i> <strong>Chờ duyệt sự kiện và thanh toán:</strong> Có ${daysFromRegistration} ngày để thanh toán.
                        </div>
                        `}
                        ` : ''}
                        ${!showExpiredWarning && !isPendingApproval && hasDeposit && paymentDeadline ? `
                        ${paymentDeadline.is_past_deadline ? `
                        <div class="alert alert-danger mb-2 py-2" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Quá hạn thanh toán:</strong> Hạn ${paymentDeadline.deadline_formatted}. <strong>Vui lòng đến công ty đóng tiền mặt</strong> trước khi sự kiện diễn ra, nếu không sự kiện sẽ bị hủy và không hoàn lại cọc.
                        </div>
                        ` : paymentDeadline.is_approaching ? `
                        <div class="alert alert-warning mb-2 py-2" role="alert">
                            <i class="fas fa-clock"></i> <strong>Cảnh báo:</strong> Còn <strong>${paymentDeadline.days_until_deadline} ngày</strong> đến hạn thanh toán đủ (${paymentDeadline.deadline_formatted}). Vui lòng thanh toán sớm.
                        </div>
                        ` : `
                        <div class="alert alert-info mb-2 py-2" role="alert">
                            <i class="fas fa-info-circle"></i> <strong>Hạn thanh toán đủ:</strong> ${paymentDeadline.deadline_formatted} (còn ${paymentDeadline.days_until_deadline} ngày).
                        </div>
                        `}
                        ` : ''}
                        ${!showExpiredWarning && !isPendingApproval && event.RequiresFullPayment && (event.TrangThaiThanhToan || 'Chưa thanh toán') === 'Chưa thanh toán' ? `
                        <div class="alert alert-info mb-2 py-2" role="alert">
                            <i class="fas fa-info-circle"></i> <strong>Sự kiện sẽ diễn ra trong ${event.DaysFromRegistrationToEvent} ngày tới.</strong> Vui lòng thanh toán.
                        </div>
                        ` : ''}
                        <div class="event-header">
                            <div class="flex-grow-1">
                                <h3 class="event-title">${event.TenSuKien}</h3>
                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                    <span class="status-badge ${statusClass}">${event.TrangThaiDuyet || 'Chờ duyệt'}</span>
                                    <span class="status-badge ${paymentClass}">${paymentStatusText}</span>
                                    ${showExpiredWarning ? `<span class="status-badge status-rejected"><i class="fas fa-clock"></i> Đã hết hạn</span>` : ''}
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
                            ${event.TenPhong && (event.LoaiDiaDiem === 'Trong nhà' || event.LoaiDiaDiem === 'Trong nha') ? `
                            <div class="detail-item">
                                <i class="fas fa-door-open"></i>
                                <span>Phòng: ${event.TenPhong}</span>
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
                        
                        ${event.TrangThaiThanhToan === 'Thanh toán bằng tiền mặt' && companyInfo ? `
                        <div class="mb-3 p-3 bg-info bg-opacity-10 border border-info rounded">
                            <strong class="text-info"><i class="fas fa-money-bill-wave"></i> Thông tin thanh toán tiền mặt:</strong>
                            <div class="mt-2">
                                <div class="mb-2">
                                    <strong>Số tiền cần thanh toán:</strong>
                                    <span class="text-primary fw-bold ms-2">${new Intl.NumberFormat('vi-VN').format(priceBreakdown.totalPrice)} VNĐ</span>
                                </div>
                                <div class="mb-2">
                                    <strong><i class="fas fa-building"></i> Địa chỉ công ty:</strong>
                                    <div class="ms-3 mt-1">
                                        <div>${companyInfo.TenCongTy || 'CÔNG TY TNHH EVENT MANAGEMENT'}</div>
                                        <div>${companyInfo.DiaChi || '123 Đường ABC, Quận 1, TP.HCM'}</div>
                                        <div><i class="fas fa-phone"></i> ${companyInfo.SoDienThoai || '0123456789'}</div>
                                        ${companyInfo.GioLamViec ? `<div><i class="fas fa-clock"></i> ${companyInfo.GioLamViec}</div>` : ''}
                                    </div>
                                </div>
                                <div class="alert alert-warning mb-0 mt-2 py-2">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <small>Vui lòng đến công ty để thanh toán tiền mặt trước khi sự kiện diễn ra.</small>
                                </div>
                            </div>
                        </div>
                        ` : event.PaymentMethod && event.PaymentMethod !== 'cash' && event.PaymentType ? `
                        <div class="mb-3 p-2 bg-light rounded">
                            <strong class="text-primary"><i class="fas fa-info-circle"></i> Thông tin thanh toán:</strong>
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                ${event.PaymentMethod ? `
                                    <span class="badge ${event.PaymentMethod === 'cash' ? 'bg-info' : 'bg-primary'}">
                                        <i class="fas ${event.PaymentMethod === 'cash' ? 'fa-money-bill-wave' : 'fa-credit-card'}"></i> 
                                        ${event.PaymentMethod === 'cash' ? 'Tiền mặt' : 'Chuyển khoản'}
                                    </span>
                                ` : ''}
                                ${event.PaymentType ? `
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-receipt"></i> ${event.PaymentType}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="event-actions">
                            <button class="btn btn-primary btn-sm" onclick="viewEventDetails(${event.ID_DatLich})">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </button>
                            ${(event.TrangThaiDuyet || 'Chờ duyệt') === 'Chờ duyệt' ? `
                            <button class="btn btn-warning btn-sm" onclick="editEvent(${event.ID_DatLich})">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="cancelEvent(${event.ID_DatLich})">
                                <i class="fas fa-times"></i> Hủy
                            </button>
                            ` : ''}
                            ${(event.TrangThaiDuyet || 'Chờ duyệt') === 'Đã duyệt' 
                              && (event.TrangThaiThanhToan || 'Chưa thanh toán') === 'Chưa thanh toán'
                              && (!event.PendingPayments || event.PendingPayments == 0)
                              && !isExpired 
                              && !event.RequiresFullPayment ? `
                            <button class="btn btn-primary btn-sm" onclick="makePayment(${event.ID_DatLich})">
                                <i class="fas fa-credit-card"></i> Thanh toán
                            </button>
                            ` : ''}
                            ${(event.TrangThaiDuyet || 'Chờ duyệt') === 'Đã duyệt' 
                              && (event.TrangThaiThanhToan || 'Chưa thanh toán') === 'Chưa thanh toán'
                              && (!event.PendingPayments || event.PendingPayments == 0)
                              && !isExpired 
                              && event.RequiresFullPayment ? `
                            <button class="btn btn-primary btn-sm" onclick="makePayment(${event.ID_DatLich}, 'full')">
                                <i class="fas fa-credit-card"></i> Thanh toán
                            </button>
                            ` : ''}
                            ${(event.TrangThaiDuyet || 'Chờ duyệt') === 'Đã duyệt' 
                              && (event.TrangThaiThanhToan || 'Chưa thanh toán') === 'Đã đặt cọc'
                              && (!event.PendingPayments || event.PendingPayments == 0)
                              && !isExpired 
                              && (!paymentDeadline || !paymentDeadline.is_past_deadline) ? `
                            <button class="btn btn-success btn-sm" onclick="makePayment(${event.ID_DatLich}, 'full')">
                                <i class="fas fa-credit-card"></i> Thanh toán đủ
                            </button>
                            ` : ''}
                            ${paymentDeadline && paymentDeadline.is_past_deadline && !isFullyPaid ? `
                            <button class="btn btn-secondary btn-sm" disabled title="Đã quá hạn thanh toán đủ">
                                <i class="fas fa-clock"></i> Đã quá hạn thanh toán
                            </button>
                            ` : ''}
                            ${isExpired && !isFullyPaid ? `
                            <button class="btn btn-secondary btn-sm" disabled title="Đã qua thời gian thanh toán">
                                <i class="fas fa-clock"></i> Hết hạn thanh toán
                            </button>
                            ` : ''}
                            ${(event.TrangThaiDuyet || 'Chờ duyệt') === 'Đã duyệt' && (event.TrangThaiThanhToan || 'Chưa thanh toán') === 'Đã thanh toán đủ' ? `
                            <button class="btn btn-review btn-sm" onclick="openReviewModal(${event.ID_DatLich}, '${event.TenSuKien}')" 
                                    title="Đánh giá sự kiện đã hoàn thành">
                                <i class="fas fa-star"></i> Đánh giá
                            </button>
                            ` : ''}
                            ${event.SuccessfulPayments && event.SuccessfulPayments.length > 0 ? `
                            <button class="btn btn-info btn-sm" onclick="viewInvoice(${event.SuccessfulPayments[0].ID_ThanhToan})" 
                                    title="Xem hóa đơn thanh toán">
                                <i class="fas fa-file-invoice"></i> Xem hóa đơn
                            </button>
                            ` : event.PendingCashPayment && event.PendingCashPayment.ID_ThanhToan ? `
                            <button class="btn btn-info btn-sm" onclick="viewInvoice(${event.PendingCashPayment.ID_ThanhToan})" 
                                    title="Xem hóa đơn cần thanh toán (đang chờ duyệt)">
                                <i class="fas fa-file-invoice"></i> Xem hóa đơn
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
                const matchesStatus = !statusFilter || (event.TrangThaiDuyet || 'Chờ duyệt') === statusFilter;
                const matchesPayment = !paymentFilter || (event.TrangThaiThanhToan || 'Chưa thanh toán') === paymentFilter;
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
            const pending = allEvents.filter(e => (e.TrangThaiDuyet || 'Chờ duyệt') === 'Chờ duyệt').length;
            const approved = allEvents.filter(e => (e.TrangThaiDuyet || 'Chờ duyệt') === 'Đã duyệt').length;
            const paid = allEvents.filter(e => (e.TrangThaiThanhToan || 'Chưa thanh toán') === 'Đã thanh toán đủ').length;
            
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
            // Handle null, undefined, or empty values
            if (!payment || payment === null || payment === 'NULL' || payment.trim() === '') {
                return 'status-pending';
            }
            
            switch(payment.trim()) {
                case 'Chưa thanh toán': return 'status-pending';
                case 'Thanh toán bằng tiền mặt': return 'status-approved';
                case 'Đã đặt cọc': return 'status-approved';
                case 'Đã thanh toán đủ': return 'status-paid';
                default: return 'status-pending';
            }
        }
        
        // Get payment status text with details
        function getPaymentStatusText(event) {
            const trangThai = event.TrangThaiThanhToan || 'Chưa thanh toán';
            const paymentMethod = event.PaymentMethod || null;
            const paymentType = event.PaymentType || null;
            
            // Nếu là "Thanh toán bằng tiền mặt"
            if (trangThai === 'Thanh toán bằng tiền mặt') {
                return 'Thanh toán bằng tiền mặt';
            }
            
            // Nếu chưa thanh toán
            if (trangThai === 'Chưa thanh toán' || !trangThai || trangThai.trim() === '') {
                return 'Chưa thanh toán';
            }
            
            // Xác định phương thức thanh toán
            let methodText = '';
            if (paymentMethod === 'cash') {
                methodText = 'Tiền mặt';
            } else if (paymentMethod === 'sepay') {
                methodText = 'Chuyển khoản';
            } else if (paymentMethod) {
                // Các phương thức khác
                methodText = paymentMethod;
            }
            
            // Kết hợp loại thanh toán và phương thức
            if (trangThai === 'Đã đặt cọc') {
                if (methodText) {
                    return `Đặt cọc - ${methodText}`;
                }
                return 'Đã đặt cọc';
            } else if (trangThai === 'Đã thanh toán đủ') {
                if (methodText) {
                    return `Thanh toán đủ - ${methodText}`;
                }
                return 'Đã thanh toán đủ';
            } else if (trangThai === 'Hoàn tiền') {
                return 'Hoàn tiền';
            }
            
            // Fallback
            return trangThai;
        }
        
        // Format date time
        function formatDateTime(dateTimeString) {
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
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
        
        // Make payment - Redirect to payment page
        function makePayment(eventId, paymentType = 'deposit') {
            const event = allEvents.find(e => e.ID_DatLich === eventId);
            if (!event) return;
            
            // Check deadline if full payment
            if (paymentType === 'full') {
                const paymentDeadline = event.PaymentDeadline || null;
                if (paymentDeadline && paymentDeadline.is_past_deadline) {
                    alert(`Đã quá hạn thanh toán đủ (hạn: ${paymentDeadline.deadline_formatted}).\n\n⚠️ QUAN TRỌNG: Vui lòng đến công ty đóng tiền mặt trước khi sự kiện diễn ra.\nNếu không thanh toán, sự kiện sẽ bị hủy và KHÔNG HOÀN LẠI CỌC.`);
                    return;
                }
            }
            
            // Redirect to payment page
            window.location.href = `../payment/payment.php?event_id=${eventId}&payment_type=${paymentType}`;
        }
        
        // Show payment modal
        function showPaymentModal(event, defaultPaymentType = 'deposit') {
            const priceBreakdown = getEventPriceBreakdown(event);
            const totalAmount = priceBreakdown.totalPrice || event.TongTien || 0;
            const depositAmount = event.TienCocYeuCau && event.TienCocYeuCau > 0 ? event.TienCocYeuCau : Math.round(totalAmount * 0.3); // 30% deposit
            const remainingAmount = totalAmount - depositAmount;
            
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
                LoaiThue: event.LoaiThue,
                defaultPaymentType: defaultPaymentType
            });
            
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
                        <div class="col-md-6">
                            <div class="card payment-method-card" data-method="sepay">
                                <div class="card-body text-center">
                                    <i class="fas fa-university fa-3x text-primary mb-3"></i>
                                    <h6>SePay Banking</h6>
                                    <small class="text-muted">Thanh toán qua ngân hàng</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
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
                                <input class="form-check-input" type="radio" name="paymentType" id="depositPayment" value="deposit" ${defaultPaymentType === 'deposit' ? 'checked' : ''} ${event.TrangThaiThanhToan === 'Đã đặt cọc' || event.RequiresFullPayment ? 'disabled' : ''}>
                                <label class="form-check-label" for="depositPayment">
                                    <strong>Đặt cọc</strong> - ${new Intl.NumberFormat('vi-VN').format(depositAmount)} VNĐ
                                    <br><small class="text-muted">Thanh toán 30% để giữ chỗ</small>
                                    ${event.TrangThaiThanhToan === 'Đã đặt cọc' ? '<br><small class="text-success"><i class="fas fa-check"></i> Đã đặt cọc</small>' : ''}
                                    ${event.RequiresFullPayment ? '<br><small class="text-danger"><i class="fas fa-ban"></i> Sự kiện diễn ra trong vòng 7 ngày, không thể đặt cọc</small>' : ''}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentType" id="fullPayment" value="full" ${defaultPaymentType === 'full' ? 'checked' : ''} ${event.RequiresFullPayment ? '' : (event.TrangThaiThanhToan !== 'Đã đặt cọc' ? 'disabled' : '')}>
                                <label class="form-check-label" for="fullPayment">
                                    <strong>Thanh toán đủ</strong> - ${new Intl.NumberFormat('vi-VN').format(totalAmount)} VNĐ
                                    <br><small class="text-muted">Thanh toán toàn bộ số tiền</small>
                                    ${event.RequiresFullPayment ? '<br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Bắt buộc thanh toán đủ (sự kiện diễn ra trong vòng 7 ngày)</small>' : ''}
                                    ${event.PaymentDeadline ? `
                                    <br><small class="${event.PaymentDeadline.is_past_deadline ? 'text-danger' : event.PaymentDeadline.is_approaching ? 'text-warning' : 'text-info'}">
                                        <i class="fas fa-clock"></i> Hạn: ${event.PaymentDeadline.deadline_formatted} (7 ngày sau khi đặt cọc)
                                        ${event.PaymentDeadline.is_past_deadline ? 
                                            '<br><strong class="text-danger">⚠️ Đã quá hạn! Phải đến công ty đóng tiền mặt. Nếu không, sự kiện sẽ bị hủy và không hoàn lại cọc.</strong>' : 
                                            event.PaymentDeadline.is_approaching ? 
                                                ` (Còn ${event.PaymentDeadline.days_until_deadline} ngày)` : 
                                                ` (Còn ${event.PaymentDeadline.days_until_deadline} ngày)`}
                                    </small>
                                    ` : ''}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6><i class="fas fa-user text-primary"></i> Thông tin liên lạc</h6>
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle"></i> 
                                <small>Vui lòng kiểm tra và cập nhật thông tin liên lạc để chúng tôi có thể liên hệ với bạn về thanh toán.</small>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="invoiceName" value="${event.HoTen || ''}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="invoicePhone" value="${event.SoDienThoai || ''}" required>
                                    <small class="text-muted">Số điện thoại để liên lạc (có thể khác với số đăng ký)</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-muted">(không thể thay đổi)</span></label>
                                    <input type="email" class="form-control" id="invoiceEmail" value="${event.UserEmail || event.Email || ''}" readonly disabled style="background-color: #e9ecef; cursor: not-allowed;">
                                    <small class="text-muted">Email đăng ký tài khoản (không thể thay đổi)</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Địa chỉ</label>
                                    <input type="text" class="form-control" id="invoiceAddress" value="${event.DiaChiKhachHang || ''}" placeholder="Nhập địa chỉ liên lạc">
                                    <small class="text-muted">Địa chỉ để liên lạc (có thể khác với địa chỉ đăng ký)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4" id="paymentDetails" style="display: none;">
                    <h6><i class="fas fa-info-circle text-primary"></i> Thông tin thanh toán</h6>
                    <div id="sepayDetails" class="payment-details" style="display: none;">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i>
                            Sau khi nhấn "Xác nhận thanh toán", hệ thống sẽ hiển thị QR và thông tin ngân hàng.
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
                
                $('.payment-details').hide();
                $(`#${method}Details`).show();
                $('#paymentDetails').show();
                
                // Nếu chọn tiền mặt, chỉ cho phép thanh toán đủ
                if (method === 'cash') {
                    $('#depositPayment').prop('disabled', true);
                    $('#fullPayment').prop('disabled', false).prop('checked', true);
                } else {
                    // Nếu chọn SePay, khôi phục logic ban đầu
                    const event = $('#paymentModal').data('event');
                    if (event) {
                        if (event.TrangThaiThanhToan === 'Đã đặt cọc' || event.RequiresFullPayment) {
                            $('#depositPayment').prop('disabled', true);
                        } else {
                            $('#depositPayment').prop('disabled', false);
                        }
                        
                        if (event.RequiresFullPayment) {
                            $('#fullPayment').prop('disabled', false);
                        } else if (event.TrangThaiThanhToan !== 'Đã đặt cọc') {
                            $('#fullPayment').prop('disabled', true);
                        } else {
                            $('#fullPayment').prop('disabled', false);
                        }
                    }
                }
            });
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
            
            // Validate invoice information
            const invoiceName = $('#invoiceName').val().trim();
            const invoicePhone = $('#invoicePhone').val().trim();
            
            if (!invoiceName || !invoicePhone) {
                alert('Vui lòng điền đầy đủ thông tin hóa đơn (Họ tên và Số điện thoại)');
                return;
            }
            
            // Get invoice information
            // Email luôn lấy từ server (email đăng ký), không gửi từ form
            const invoiceData = {
                name: invoiceName,
                phone: invoicePhone,
                address: $('#invoiceAddress').val().trim() || null
            };
            
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
                priceBreakdown: priceBreakdown,
                invoiceData: invoiceData
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
                
                if (paymentMethod === 'sepay') {
                    // Use SePay for banking payments
                    apiAction = 'create_sepay_payment';
                    apiData = {
                        action: apiAction,
                        event_id: event.ID_DatLich,
                        amount: amount,
                        payment_type: paymentType,
                        invoice_data: invoiceData
                    };
                } else {
                    // Use offline payment (QR code)
                    apiAction = 'create_payment';
                    apiData = {
                        action: apiAction,
                        event_id: event.ID_DatLich,
                        amount: amount,
                        payment_method: paymentMethod,
                        payment_type: paymentType,
                        invoice_data: invoiceData
                    };
                }
                
                // Process payment
                console.log('Payment API Data:', apiData);
                console.log('Payment URL:', '../src/controllers/payment.php');
                
                $.ajax({
                    url: '../src/controllers/payment.php',
                    method: 'POST',
                    data: apiData,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Payment Success Response:', response);
                        if (response.success) {
                            // For cash payments, do not show QR; mark as processing and return to list
                            if (paymentMethod === 'cash') {
                                alert('Yêu cầu thanh toán tiền mặt đã được tạo. Trạng thái: Đang xử lý.');
                                $('#paymentModal').modal('hide');
                                loadMyEvents();
                                return;
                            }
                            if (response.bank_info) {
                                // Show SePay banking info
                                showSePayBankingModal(response);
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
                    error: function(xhr, status, error) {
                        console.error('Payment Error:', xhr, status, error);
                        console.error('Response Text:', xhr.responseText);
                        alert('Lỗi kết nối. Vui lòng thử lại.\nStatus: ' + status + '\nError: ' + error);
                        resetPaymentModal();
                    }
                });
            }
        }
        
        // Get payment method name
        function getPaymentMethodName(method) {
            switch(method) {
                case 'sepay': return 'SePay Banking';
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
        
        // Show SePay Banking Modal
        function showSePayBankingModal(paymentData) {
            const event = $('#paymentModal').data('event');
            const paymentMethod = $('.payment-method-card.border-primary').data('method');
            const paymentType = $('input[name="paymentType"]:checked').val();
            
            $('#paymentModalTitle').text(`Chuyển khoản - ${event.TenSuKien}`);
            $('#paymentModalBody').html(`
                <div class="row">
                    <div class="col-md-6">
                        <div class="qr-container mb-3">
                            <div id="qrcode" class="d-flex justify-content-center"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="payment-info-compact">
                            <h6 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Thông tin thanh toán</h6>
                            <div class="info-item mb-2">
                                <small class="text-muted">Số tiền:</small><br>
                                <strong class="text-primary">${new Intl.NumberFormat('vi-VN').format(paymentData.amount)} VNĐ</strong>
                            </div>
                            <div class="info-item mb-2">
                                <small class="text-muted">Loại:</small><br>
                                <span>${paymentType === 'deposit' ? 'Đặt cọc' : 'Thanh toán đủ'}</span>
                            </div>
                            <div class="info-item mb-3">
                                <small class="text-muted">Mã giao dịch:</small><br>
                                <code class="small">${paymentData.transaction_id}</code>
                            </div>
                            
                            <div class="bank-info-compact">
                                <h6 class="text-primary mb-2"><i class="fas fa-university"></i> Thông tin ngân hàng</h6>
                                <div class="info-item mb-1">
                                    <small class="text-muted">Ngân hàng:</small><br>
                                    <strong>${paymentData.bank_info.bank_name}</strong>
                                </div>
                                <div class="info-item mb-1">
                                    <small class="text-muted">Số tài khoản:</small><br>
                                    <code class="small">${paymentData.bank_info.account_number}</code>
                                </div>
                                <div class="info-item mb-1">
                                    <small class="text-muted">Chủ tài khoản:</small><br>
                                    <span class="small">${paymentData.bank_info.account_name}</span>
                                </div>
                                <div class="info-item mb-2">
                                    <small class="text-muted">Nội dung:</small><br>
                                    <code class="small">${paymentData.bank_info.content}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-mobile-alt"></i>
                    <strong>Hướng dẫn:</strong> Mở app ngân hàng → Quét QR hoặc chuyển khoản theo thông tin trên
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <small>Sau khi chuyển khoản, vui lòng nhấn nút <strong>"Xác nhận thanh toán"</strong> để hệ thống kiểm tra và cập nhật trạng thái.</small>
                </div>
            `);
            
            $('#paymentModalFooter').html(`
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-info" onclick="checkPaymentStatus('${paymentData.transaction_id}')">
                    <i class="fas fa-sync"></i> Kiểm tra trạng thái
                </button>
                <button type="button" class="btn btn-success" onclick="verifyPaymentStatus(${paymentData.payment_id}, '${paymentData.transaction_id}')">
                    <i class="fas fa-check-circle"></i> Xác nhận thanh toán
                </button>
            `);
            
            // Store payment data for fallback
            $('#paymentModal').data('paymentData', paymentData);
            
            // Generate QR Code for banking
            generateQRCodeWithFallback(paymentData);

            // Không tự động kiểm tra - chỉ kiểm tra khi người dùng nhấn nút "Xác nhận thanh toán"
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
                            <tr><td><strong>Mã giao dịch:</strong></td><td><code>${paymentData.transaction_code || paymentData.transaction_id}</code></td></tr>
                        </table>
                    </div>
                    
                    <!-- MoMo removed - using SePay only -->
                    
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
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Sau khi chuyển khoản:</strong>
                        <p class="mb-0 mt-1">Vui lòng nhấn nút <strong>"Xác nhận thanh toán"</strong> để hệ thống kiểm tra và cập nhật trạng thái.</p>
                    </div>
                </div>
            `);
            
            $('#paymentModalFooter').html(`
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-info" onclick="checkPaymentStatus('${paymentData.transaction_code || paymentData.transaction_id}')">
                    <i class="fas fa-sync"></i> Kiểm tra trạng thái
                </button>
                ${paymentData.payment_id ? `
                <button type="button" class="btn btn-success" onclick="verifyPaymentStatus(${paymentData.payment_id}, '${paymentData.transaction_code || paymentData.transaction_id}')">
                    <i class="fas fa-check-circle"></i> Xác nhận thanh toán
                </button>
                ` : ''}
            `);
            
            // Store payment data for fallback usage and generate QR with robust fallback
            $('#paymentModal').data('paymentData', paymentData);
            generateQRCodeWithFallback(paymentData);
        }
        
        // Generate QR Code with Fallback
        function generateQRCodeWithFallback(paymentData) {
            console.log('Generating QR Code with fallback for:', paymentData);
            
            // Always use fallback for better reliability - VietQR is unstable
            useFallbackQR(paymentData);
        }
        
        // Use Fallback QR
        function useFallbackQR(paymentData) {
            console.log('Using fallback QR for:', paymentData);
            
            let fallbackUrl;
            let qrData = '';
            
            try {
                // 0) If original VietQR URL có sẵn, thử render qua proxy CDN để tránh lỗi kết nối trực tiếp
                const proxiedVietQR = [];
                if (paymentData.qr_code && typeof paymentData.qr_code === 'string') {
                    const withoutProtocol = paymentData.qr_code.replace(/^https?:\/\//, '');
                    proxiedVietQR.push(`https://images.weserv.nl/?url=${encodeURIComponent(withoutProtocol)}`);
                    proxiedVietQR.push(`https://wsrv.nl/?url=${encodeURIComponent(withoutProtocol)}`);
                }
                if (paymentData.fallback_qr) {
                    fallbackUrl = paymentData.fallback_qr;
                } else if (paymentData.bank_info) {
                    // Create QR with bank info
                    qrData = `Bank: ${paymentData.bank_info.bank_name}\nAccount: ${paymentData.bank_info.account_number}\nAmount: ${paymentData.amount}\nContent: ${paymentData.bank_info.content}`;
                    fallbackUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrData)}`;
                } else {
                    qrData = paymentData.qr_code || paymentData.transaction_id || 'QR_CODE';
                    fallbackUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrData)}`;
                }
                
                console.log('Fallback URL:', fallbackUrl);
                
                // Try multiple QR services for better reliability
                const qrServices = [
                    ...proxiedVietQR,
                    fallbackUrl,
                    `https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=${encodeURIComponent(qrData)}`,
                    `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrData)}&format=png`
                ];
                
                let currentService = 0;
                
                function tryNextService() {
                    if (currentService >= qrServices.length) {
                        // All services failed, show manual info
                        $('#qrcode').html(`
                            <div class="alert alert-info text-center">
                                <i class="fas fa-qrcode fa-3x mb-3 text-muted"></i>
                                <h6>QR Code không khả dụng</h6>
                                <p class="mb-2">Vui lòng chuyển khoản theo thông tin bên cạnh:</p>
                                <div class="text-start">
                                    <small><strong>Ngân hàng:</strong> ${paymentData.bank_info?.bank_name || 'N/A'}</small><br>
                                    <small><strong>Số TK:</strong> ${paymentData.bank_info?.account_number || 'N/A'}</small><br>
                                    <small><strong>Nội dung:</strong> ${paymentData.bank_info?.content || 'N/A'}</small>
                                </div>
                            </div>
                        `);
                        return;
                    }
                    
                    const img = new Image();
                    img.onload = function() {
                        $('#qrcode').html(`
                            <img src="${qrServices[currentService]}" alt="QR Code" class="img-fluid border rounded" style="max-width: 300px; max-height: 300px;">
                        `);
                    };
                    img.onerror = function() {
                        console.log(`QR service ${currentService} failed, trying next...`);
                        currentService++;
                        tryNextService();
                    };
                    img.src = qrServices[currentService];
                }
                
                tryNextService();
                
            } catch (error) {
                console.error('Error creating fallback QR:', error);
                $('#qrcode').html(`
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h6>Lỗi tạo QR Code</h6>
                        <p class="mb-0">Vui lòng chuyển khoản theo thông tin bên cạnh</p>
                    </div>
                `);
            }
        }
        
        // Generate QR Code
        function generateQRCode(qrString) {
            console.log('Generating QR Code for:', qrString);
            
            // Check if qrString is a URL or plain text
            if (qrString && qrString.startsWith('http')) {
                // If it's already a URL (VietQR), use it with fallback
                const img = new Image();
                img.onload = function() {
                    $('#qrcode').html(`
                        <img src="${qrString}" alt="QR Code" class="img-fluid border rounded" style="max-width: 300px; max-height: 300px;">
                    `);
                };
                img.onerror = function() {
                    console.log('VietQR failed, using fallback service');
                    // Try to get fallback QR from payment data
                    const paymentData = $('#paymentModal').data('paymentData');
                    let fallbackUrl;
                    
                    if (paymentData && paymentData.fallback_qr) {
                        fallbackUrl = paymentData.fallback_qr;
                    } else {
                        // Create fallback QR with bank info
                        const bankInfo = paymentData ? paymentData.bank_info : null;
                        if (bankInfo) {
                            const fallbackData = `Bank: ${bankInfo.bank_name}\nAccount: ${bankInfo.account_number}\nAmount: ${paymentData.amount}\nContent: ${bankInfo.content}`;
                            fallbackUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(fallbackData)}`;
                        } else {
                            fallbackUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrString)}`;
                        }
                    }
                    
                    $('#qrcode').html(`
                        <img src="${fallbackUrl}" alt="QR Code" class="img-fluid border rounded" style="max-width: 300px; max-height: 300px;" 
                             onerror="this.parentElement.innerHTML='<div class=\\"alert alert-warning\\"><i class=\\"fas fa-exclamation-triangle\\"></i> Không thể tải QR code. Vui lòng thử lại.</div>'">
                    `);
                };
                img.src = qrString;
            } else if (qrString) {
                // If it's plain text, generate QR code using service with smaller size
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrString)}`;
                $('#qrcode').html(`
                    <img src="${qrUrl}" alt="QR Code" class="img-fluid border rounded" style="max-width: 300px; max-height: 300px;"
                         onerror="this.parentElement.innerHTML='<div class=\\"alert alert-warning\\"><i class=\\"fas fa-exclamation-triangle\\"></i> Không thể tải QR code. Vui lòng thử lại.</div>'">
                `);
            } else {
                $('#qrcode').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Không thể tạo mã QR. Vui lòng thử lại.
                    </div>
                `);
            }
        }
        
        // Check payment status (kiểm tra nhanh)
        function checkPaymentStatus(transactionCode) {
            const originalFooter = $('#paymentModalFooter').html();
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
                                if (window.__sepayPollTimer) clearInterval(window.__sepayPollTimer);
                                alert('Thanh toán đã được xác nhận thành công!');
                                loadMyEvents();
                                $('#paymentModal').modal('hide');
                                return;
                            } else if (payment.TrangThai === 'Thất bại') {
                                if (window.__sepayPollTimer) clearInterval(window.__sepayPollTimer);
                                alert('Thanh toán thất bại. Vui lòng liên hệ hỗ trợ.');
                                return;
                            }
                            // Still pending
                            alert('Thanh toán đang được xử lý. Vui lòng đợi trong giây lát...');
                            $('#paymentModalFooter').html(originalFooter);
                        } else {
                            alert('Chưa tìm thấy thông tin thanh toán. Vui lòng thử lại sau.');
                            $('#paymentModalFooter').html(originalFooter);
                        }
                    } else {
                        alert('Lỗi khi kiểm tra trạng thái thanh toán.');
                        $('#paymentModalFooter').html(originalFooter);
                    }
                },
                error: function() {
                    alert('Lỗi kết nối khi kiểm tra trạng thái.');
                    $('#paymentModalFooter').html(originalFooter);
                }
            });
        }
        
        // Verify payment status (xác nhận thanh toán - gọi API verify_payment)
        function verifyPaymentStatus(paymentId, transactionCode) {
            if (!confirm('Bạn đã hoàn tất chuyển khoản? Hệ thống sẽ kiểm tra và xác nhận thanh toán.')) {
                return;
            }
            
            const originalFooter = $('#paymentModalFooter').html();
            $('#paymentModalFooter').html(`
                <div class="text-center">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Đang xác nhận...</span>
                    </div>
                    <p class="mt-2">Đang xác nhận thanh toán...</p>
                </div>
            `);
            
            $.ajax({
                url: '../src/controllers/payment.php',
                method: 'POST',
                data: {
                    action: 'verify_payment',
                    payment_id: paymentId,
                    transaction_code: transactionCode
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.is_success) {
                            // Thanh toán thành công
                            if (window.__sepayPollTimer) clearInterval(window.__sepayPollTimer);
                            alert('✅ ' + response.message);
                            $('#paymentModal').modal('hide');
                            loadMyEvents(); // Reload danh sách sự kiện
                        } else if (response.is_pending) {
                            // Đang xử lý
                            alert('⏳ ' + response.message + '\n\nHệ thống sẽ tự động cập nhật khi nhận được webhook từ SePay.');
                            $('#paymentModalFooter').html(originalFooter);
                        } else {
                            // Chưa xác nhận
                            alert('⚠️ ' + response.message + '\n\nVui lòng đảm bảo bạn đã chuyển khoản với đúng nội dung: ' + (response.transaction_code || ''));
                            $('#paymentModalFooter').html(originalFooter);
                        }
                    } else {
                        alert('❌ Lỗi: ' + (response.error || 'Không thể xác nhận thanh toán'));
                        $('#paymentModalFooter').html(originalFooter);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Verify payment error:', error);
                    alert('❌ Lỗi kết nối khi xác nhận thanh toán. Vui lòng thử lại sau.');
                    $('#paymentModalFooter').html(originalFooter);
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
            const eventTypePrice = new Intl.NumberFormat('vi-VN').format(priceBreakdown.eventTypePrice || 0);
            const equipmentPrice = new Intl.NumberFormat('vi-VN').format(priceBreakdown.equipmentPrice || 0);
            const budget = event.NganSach ? new Intl.NumberFormat('vi-VN').format(event.NganSach) : null;
            
            // Get status badge class
            const statusClass = getStatusClass(event.TrangThaiDuyet);
            const paymentClass = getPaymentClass(event.TrangThaiThanhToan);
            
            // Get location image
            const locationImage = event.DiaDiemHinhAnh ? 
                `../img/diadiem/${event.DiaDiemHinhAnh}` : 
                '../img/diadiem/default.jpg';
            
            // Calculate location price
            const locationPriceText = getLocationPriceText(event);
            
            let html = `
                <div class="event-details-content">
                    <!-- Modal Title with Event Name -->
                    <h4 class="text-center mb-4">${event.TenSuKien}</h4>
                    
                    <!-- Grid Layout -->
                    <div class="row g-3">
                        <!-- Thông tin cơ bản (Top Left) -->
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h6 class="section-header">
                                    <i class="fas fa-info-circle text-primary"></i> Thông tin cơ bản
                                </h6>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Tên sự kiện:</strong></td>
                                        <td>${event.TenSuKien}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Loại sự kiện:</strong></td>
                                        <td>${event.TenLoai || 'Chưa xác định'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ngày bắt đầu:</strong></td>
                                        <td>${eventDate}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ngày kết thúc:</strong></td>
                                        <td>${eventEndDate}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Số khách dự kiến:</strong></td>
                                        <td>${event.SoNguoiDuKien || 'Chưa xác định'}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Địa điểm (Top Right) -->
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h6 class="section-header">
                                    <i class="fas fa-map-marker-alt text-primary"></i> Địa điểm
                                </h6>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Tên địa điểm:</strong></td>
                                        <td>${event.TenDiaDiem || 'Chưa xác định'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Địa chỉ:</strong></td>
                                        <td>${event.DiaChi || 'Chưa xác định'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Sức chứa:</strong></td>
                                        <td>${event.SucChua ? new Intl.NumberFormat('vi-VN').format(event.SucChua) + ' người' : 'Chưa xác định'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Giá thuê:</strong></td>
                                        <td>${locationPriceText || 'Chưa có giá'}</td>
                                    </tr>
                                    ${event.TenPhong && (event.LoaiDiaDiem === 'Trong nhà' || event.LoaiDiaDiem === 'Trong nha') ? `
                                    <tr>
                                        <td><strong>Phòng đã chọn:</strong></td>
                                        <td><span class="badge bg-primary">${event.TenPhong}</span></td>
                                    </tr>
                                    ` : ''}
                                </table>
                            </div>
                        </div>
                        
                        <!-- Chi tiết giá tiền (Middle Left) -->
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h6 class="section-header">
                                    <i class="fas fa-money-bill-wave text-primary"></i> Chi tiết giá tiền
                                </h6>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Giá loại sự kiện:</strong></td>
                                        <td>${eventTypePrice} VNĐ</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Giá thiết bị:</strong></td>
                                        <td>${equipmentPrice} VNĐ</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tổng giá:</strong></td>
                                        <td><strong class="text-success">${totalPrice} VNĐ</strong></td>
                                    </tr>
                                    ${budget ? `
                                    <tr>
                                        <td><strong>Ngân sách:</strong></td>
                                        <td>${budget} VNĐ</td>
                                    </tr>
                                    ` : ''}
                                    <tr>
                                        <td><strong>Trạng thái duyệt:</strong></td>
                                        <td><span class="badge ${statusClass}">${event.TrangThaiDuyet || 'Chờ duyệt'}</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Trạng thái thanh toán:</strong></td>
                                        <td><span class="badge ${paymentClass}">${event.TrangThaiThanhToan || 'Chưa thanh toán'}</span></td>
                                    </tr>
                                    ${event.PaymentMethod ? `
                                    <tr>
                                        <td><strong>Phương thức thanh toán:</strong></td>
                                        <td>
                                            <span class="badge ${event.PaymentMethod === 'cash' ? 'bg-info' : 'bg-primary'}">
                                                <i class="fas ${event.PaymentMethod === 'cash' ? 'fa-money-bill-wave' : 'fa-credit-card'}"></i> 
                                                ${event.PaymentMethod === 'cash' ? 'Tiền mặt' : 'Chuyển khoản'}
                                            </span>
                                        </td>
                                    </tr>
                                    ` : ''}
                                    ${event.PaymentType ? `
                                    <tr>
                                        <td><strong>Loại thanh toán:</strong></td>
                                        <td><span class="badge bg-warning text-dark"><i class="fas fa-receipt"></i> ${event.PaymentType}</span></td>
                                    </tr>
                                    ` : ''}
                                </table>
                            </div>
                        </div>
                        
                        <!-- Thông tin liên hệ (Middle Right) -->
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h6 class="section-header">
                                    <i class="fas fa-user text-primary"></i> Thông tin liên hệ
                                </h6>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Họ tên:</strong></td>
                                        <td>${event.HoTen || 'Chưa xác định'}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Số điện thoại:</strong></td>
                                        <td>${event.SoDienThoai || 'Chưa xác định'}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thiết bị đã đăng ký (Bottom) -->
                    ${equipment && equipment.length > 0 ? `
                    <div class="mt-4">
                        <div class="detail-section">
                            <h6 class="section-header">
                                <i class="fas fa-cogs text-primary"></i> Thiết bị đã đăng ký
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tên thiết bị</th>
                                            <th>Loại</th>
                                            <th>Hãng</th>
                                            <th>Số lượng</th>
                                            <th>Đơn vị</th>
                                            <th>Giá</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
            ` : ''}
            
            ${equipment && equipment.length > 0 ? equipment.map(item => {
                const itemName = item.TenThietBi || item.TenCombo || 'Thiết bị';
                const itemType = item.TenCombo ? 'Combo' : (item.LoaiThietBi || 'N/A');
                const itemBrand = item.HangSX || 'N/A';
                const itemQuantity = item.SoLuong || 1;
                const itemUnit = item.TenCombo ? 'combo' : (item.DonViTinh || 'cái');
                const itemPrice = new Intl.NumberFormat('vi-VN').format(item.DonGia || 0);
                const itemNote = item.GhiChu || (item.TenCombo ? 'Combo thiết bị' : 'Thiết bị riêng lẻ');
                
                return `
                                        <tr>
                                            <td><strong>${itemName}</strong></td>
                                            <td>${itemType}</td>
                                            <td>${itemBrand}</td>
                                            <td><span class="badge bg-primary">${itemQuantity}</span></td>
                                            <td>${itemUnit}</td>
                                            <td><strong class="text-success">${itemPrice} VNĐ</strong></td>
                                            <td>${itemNote}</td>
                                        </tr>
                `;
            }).join('') : ''}
            
            ${equipment && equipment.length > 0 ? `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
            ` : ''}
                </div>
            `;
            
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
        
        // View invoice
        function viewInvoice(paymentId) {
            const modal = new bootstrap.Modal(document.getElementById('invoiceModal'));
            const modalBody = document.getElementById('invoiceModalBody');
            
            // Hiển thị loading
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải thông tin hóa đơn...</p>
                </div>
            `;
            
            modal.show();
            
            // Lấy thông tin hóa đơn
            fetch(`../src/controllers/payment.php?action=get_invoice&payment_id=${paymentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const invoice = data.invoice;
                        const payment = data.payment;
                        
                        const isPendingCash = payment.TrangThai === 'Đang xử lý' && payment.PhuongThuc === 'Tiền mặt';
                        
                        modalBody.innerHTML = `
                            <div class="invoice-details">
                                ${isPendingCash ? `
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-clock"></i> 
                                    <strong>Đang chờ duyệt:</strong> Hóa đơn này đang chờ admin xác nhận thanh toán tiền mặt. 
                                    Vui lòng đến công ty để thanh toán theo thông tin bên dưới.
                                </div>
                                ` : ''}
                                
                                <div class="text-center mb-4">
                                    <h4 class="text-primary">HÓA ĐƠN THANH TOÁN</h4>
                                    <p class="text-muted mb-0">Mã hóa đơn: #${invoice.ID_HoaDon || payment.ID_ThanhToan}</p>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3"><i class="fas fa-user"></i> Thông tin khách hàng</h6>
                                        <table class="table table-sm table-borderless">
                                            <tr><td class="text-muted" style="width: 40%;">Họ tên:</td><td><strong>${invoice.HoTen || payment.HoTen}</strong></td></tr>
                                            <tr><td class="text-muted">Số điện thoại:</td><td>${invoice.SoDienThoai || payment.SoDienThoai}</td></tr>
                                            <tr><td class="text-muted">Email:</td><td>${invoice.Email || payment.UserEmail || 'N/A'}</td></tr>
                                            <tr><td class="text-muted">Địa chỉ:</td><td>${invoice.DiaChi || payment.DiaChi || 'N/A'}</td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Thông tin thanh toán</h6>
                                        <table class="table table-sm table-borderless">
                                            <tr><td class="text-muted" style="width: 40%;">Sự kiện:</td><td><strong>${payment.TenSuKien}</strong></td></tr>
                                            <tr><td class="text-muted">Loại thanh toán:</td><td><span class="badge bg-${payment.LoaiThanhToan === 'Đặt cọc' ? 'warning' : 'success'}">${payment.LoaiThanhToan}</span></td></tr>
                                            <tr><td class="text-muted">Số tiền:</td><td><strong class="text-primary">${new Intl.NumberFormat('vi-VN').format(payment.SoTien)} VNĐ</strong></td></tr>
                                            <tr><td class="text-muted">Phương thức:</td><td>${payment.PhuongThuc}</td></tr>
                                            <tr><td class="text-muted">Ngày tạo:</td><td>${new Date(payment.NgayThanhToan).toLocaleString('vi-VN')}</td></tr>
                                            ${payment.MaGiaoDich ? `<tr><td class="text-muted">Mã giao dịch:</td><td><code>${payment.MaGiaoDich}</code></td></tr>` : ''}
                                            <tr><td class="text-muted">Trạng thái:</td><td><span class="badge bg-${payment.TrangThai === 'Thành công' ? 'success' : payment.TrangThai === 'Đang xử lý' ? 'warning' : 'danger'}">${payment.TrangThai}</span></td></tr>
                                        </table>
                                    </div>
                                </div>
                                
                                ${isPendingCash && companyInfo ? `
                                <div class="alert alert-info mb-3">
                                    <h6 class="mb-2"><i class="fas fa-building"></i> Thông tin công ty để thanh toán:</h6>
                                    <div class="ms-3">
                                        <div><strong>${companyInfo.TenCongTy}</strong></div>
                                        <div><i class="fas fa-map-marker-alt"></i> ${companyInfo.DiaChi}</div>
                                        <div><i class="fas fa-phone"></i> ${companyInfo.SoDienThoai}</div>
                                        ${companyInfo.GioLamViec ? `<div><i class="fas fa-clock"></i> ${companyInfo.GioLamViec}</div>` : ''}
                                    </div>
                                </div>
                                ` : ''}
                                
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Lưu ý:</strong> Đây là hóa đơn ${payment.LoaiThanhToan === 'Đặt cọc' ? 'đặt cọc' : 'thanh toán đủ'} cho sự kiện "${payment.TenSuKien}".
                                    ${payment.LoaiThanhToan === 'Đặt cọc' ? 'Bạn cần thanh toán đủ số tiền còn lại trước khi sự kiện diễn ra.' : ''}
                                    ${isPendingCash ? 'Vui lòng đến công ty để thanh toán tiền mặt theo thông tin trên.' : ''}
                                </div>
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.error || 'Không thể tải thông tin hóa đơn'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading invoice:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Lỗi khi tải thông tin hóa đơn. Vui lòng thử lại sau.
                        </div>
                    `;
                });
        }
        
        // Print invoice
        function printInvoice() {
            const invoiceContent = document.getElementById('invoiceModalBody').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Hóa đơn thanh toán</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .invoice-details { max-width: 800px; margin: 0 auto; }
                        table { width: 100%; margin-bottom: 20px; }
                        .badge { padding: 5px 10px; border-radius: 4px; }
                        .bg-success { background-color: #28a745; color: white; }
                        .bg-warning { background-color: #ffc107; color: black; }
                        .bg-danger { background-color: #dc3545; color: white; }
                        @media print {
                            button { display: none; }
                        }
                    </style>
                </head>
                <body>
                    ${invoiceContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>
