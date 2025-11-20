<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký sự kiện - Event Management System</title>
    <link rel="icon" href="../img/logo/logo.jpg">
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
        
        /* Room Selection Card - Compact Design */
        .room-selection-card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        /* Room List Cards */
        .room-list-container {
            margin-top: 1rem;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .room-card {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .room-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .room-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
        }
        
        .room-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .room-card-title {
            font-weight: 600;
            color: #2d3748;
            font-size: 1rem;
            margin: 0;
        }
        
        .room-card-badge {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .room-card-info {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .room-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #495057;
        }
        
        .room-info-item i {
            color: #667eea;
            width: 16px;
        }
        
        .room-price-info {
            display: flex;
            gap: 1rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #dee2e6;
        }
        
        .room-price-item {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            background: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        
        .room-price-item.active {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .room-price-label {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .room-price-value {
            font-weight: 600;
            color: #667eea;
            font-size: 0.875rem;
        }
        
        .room-card-description {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
            font-style: italic;
        }
        
        .no-rooms-message {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px dashed #dee2e6;
        }
        
        .no-rooms-message i {
            font-size: 2rem;
            color: #adb5bd;
            margin-bottom: 0.5rem;
        }
        
        /* Room Selection Modal */
        .room-selection-modal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .room-selection-modal .modal-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px 12px 0 0;
            border-bottom: none;
        }
        
        .room-selection-modal .modal-header .btn-close {
            filter: invert(1);
        }
        
        .room-selection-modal .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .btn-select-room {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-select-room:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .btn-select-room:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .selected-room-info {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .selected-room-info h6 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .room-selection-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }
        
        .room-selection-header i {
            color: #667eea;
            font-size: 1rem;
        }
        
        .room-selection-header label {
            font-weight: 600;
            color: #2d3748;
            margin: 0;
            font-size: 0.875rem;
        }
        
        .room-select-wrapper {
            margin-bottom: 0.75rem;
        }
        
        .room-select-wrapper .form-select {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            background: #ffffff;
        }
        
        .room-select-wrapper .form-select:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }
        
        .room-select-wrapper .form-select:disabled {
            background-color: #f9fafb;
            color: #9ca3af;
        }
        
        .room-rental-type-container {
            margin-bottom: 0.75rem;
            display: block !important; /* Đảm bảo luôn hiển thị */
        }
        
        .room-rental-type-wrapper {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.75rem;
        }
        
        .room-rental-type-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .room-rental-type-header i {
            color: #3b82f6;
            font-size: 0.875rem;
        }
        
        .room-rental-type-header label {
            font-weight: 600;
            color: #495057;
            margin: 0;
            font-size: 0.875rem;
        }
        
        .room-rental-type-wrapper .form-select {
            border: 1px solid #3b82f6;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            background: white;
            width: 100%;
            cursor: pointer;
            appearance: auto; /* Đảm bảo dropdown arrow hiển thị */
            -webkit-appearance: menulist;
            -moz-appearance: menulist;
        }
        
        .room-rental-type-wrapper .form-select:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }
        
        .room-price-display {
            background: #667eea;
            color: white;
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            margin-top: 0.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .room-price-display i {
            margin-right: 0.375rem;
            font-size: 0.875rem;
        }
        
        .info-text {
            display: none;
        }
        
        .room-price-display span {
            display: inline-block;
        }
        
        .room-price-display.price-updated {
            animation: pricePulse 0.5s ease;
        }
        
        @keyframes pricePulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
            }
        }
        
        .required-badge {
            background: #dc3545;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .info-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .info-text i {
            color: #667eea;
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
            align-items: center;
            margin-top: 2rem;
        }
        
        .navigation-buttons #nextBtn,
        .navigation-buttons #submitBtn {
            margin-left: auto;
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
        
        /* Combo disabled state */
        .combo-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            position: relative;
        }
        
        .combo-card.disabled:hover {
            transform: none;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.1);
        }
        
        .combo-unavailable-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            z-index: 10;
            color: #dc3545;
        }
        
        .combo-availability-info {
            margin-top: 10px;
        }
        
        .alert-sm {
            padding: 0.5rem;
            font-size: 0.85rem;
        }
        
        /* Improved card styling for step 3 */
        .card.shadow-sm {
            border: none;
            transition: all 0.3s ease;
        }
        
        .card.shadow-sm:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }
        
        .card-header {
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
        }
        
        /* Sticky summary */
        .sticky-top {
            position: sticky;
            z-index: 1020;
        }
        
        /* Equipment card improvements */
        .equipment-card {
            transition: all 0.3s ease;
        }
        
        .equipment-card:hover {
            transform: translateY(-3px);
        }
        
        .equipment-card.selected {
            border-width: 3px;
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
                                    <label for="cityFilter" class="form-label">
                                        <i class="fas fa-city"></i> Tỉnh/Thành phố
                                    </label>
                                    <select class="form-select" id="cityFilter">
                                        <option value="">Tất cả</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="districtFilter" class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Quận/Huyện
                                    </label>
                                    <select class="form-select" id="districtFilter">
                                        <option value="">Tất cả</option>
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
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="clearLocationFilters()" title="Xóa tất cả bộ lọc">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
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
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Combo Suggestions -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-box"></i> Combo thiết bị đề xuất
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                    <div id="comboSuggestions">
                                            <div class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                                <p class="mt-2 text-muted">Đang tải combo thiết bị...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Individual Equipment -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-tools"></i> Thiết bị riêng lẻ
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                    <div id="equipmentSuggestions">
                                            <div class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                                <p class="mt-2 text-muted">Đang tải danh sách thiết bị...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                </div>
                            
                            <!-- Order Summary Sidebar -->
                            <div class="col-lg-4">
                                <div class="card shadow-sm sticky-top" style="top: 20px;">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-receipt"></i> Tóm tắt đơn hàng
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="orderSummary">
                                            <div class="text-center py-4">
                                                <i class="fas fa-info-circle text-muted fa-2x mb-2"></i>
                                                <p class="text-muted">Vui lòng chọn thiết bị để xem tóm tắt</p>
                                            </div>
                                        </div>
                                    </div>
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
    
    <!-- Room Selection Modal -->
    <div class="modal fade room-selection-modal" id="roomSelectionModal" tabindex="-1" aria-labelledby="roomSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomSelectionModalLabel">
                        <i class="fas fa-door-open"></i> Chọn phòng
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-clock"></i> Loại thuê <span class="required-badge">*</span>
                        </label>
                        <select class="form-select form-select-lg" 
                                id="modal-room-rental-type"
                                onchange="onModalRentalTypeChange()">
                            <option value="">-- Chọn loại thuê --</option>
                            <option value="hour">⏰ Theo giờ</option>
                            <option value="day">📅 Theo ngày</option>
                        </select>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i> Vui lòng chọn loại thuê để xem danh sách phòng có sẵn
                        </small>
                    </div>
                    
                    <div id="modal-room-list-container">
                        <div class="text-center py-5">
                            <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Vui lòng chọn loại thuê để xem danh sách phòng</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" id="btn-confirm-room" onclick="confirmRoomSelection()" disabled>
                        <i class="fas fa-check"></i> Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- CSRF Protection Helper - Phải load sau jQuery -->
    <script src="../assets/js/csrf-helper.js"></script>
    
    <script>
        let currentStep = 1;
        let selectedLocation = null;
        let selectedEquipment = [];
        let selectedCombos = []; // Array để lưu các combo đã được xác nhận
        let pendingComboSelections = []; // Array tạm thời để lưu các combo đang được chọn (chưa xác nhận)
        let eventTypes = [];
        let locations = [];
        let allLocations = [];
        let suggestedLocations = [];
        let equipmentSuggestions = [];
        let comboSuggestions = [];
        let locationTypes = [];
        let cities = [];
        let districts = [];
        
        // Khởi tạo form
        $(document).ready(function() {
            loadEventTypes();
            setMinDate();
            
            // Kiểm tra nếu đang chỉnh sửa sự kiện hiện có
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            if (editId) {
                loadEventForEdit(editId);
            }
        });
        
        // Đặt ngày tối thiểu là hôm nay
        function setMinDate() {
            const today = new Date().toISOString().split('T')[0];
            $('#eventDate').attr('min', today);
            $('#eventEndDate').attr('min', today);
        }
        
        // Hàm helper kiểm tra thời gian bắt đầu sự kiện phải cách ít nhất 12 giờ từ bây giờ
        function checkMinimum12Hours(eventDate, eventTime) {
            if (!eventDate || !eventTime) return { valid: true };
            
            const eventStartDateTime = new Date(eventDate + 'T' + eventTime);
            const now = new Date();
            const minDateTime = new Date(now.getTime() + (12 * 60 * 60 * 1000)); // Thêm 12 giờ
            
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
        
        // Tự động đặt ngày kết thúc khi ngày bắt đầu thay đổi
        $('#eventDate').on('change', function() {
            const startDate = $(this).val();
            const startTime = $('#eventTime').val();
            
            if (startDate) {
                $('#eventEndDate').attr('min', startDate);
                // Nếu ngày kết thúc trước ngày bắt đầu, đặt nó bằng ngày bắt đầu
                const endDate = $('#eventEndDate').val();
                if (endDate && endDate < startDate) {
                    $('#eventEndDate').val(startDate);
                }
                
                // Kiểm tra tối thiểu 12 giờ
                if (startTime) {
                    const checkResult = checkMinimum12Hours(startDate, startTime);
                    if (!checkResult.valid) {
                        showError(`Sự kiện phải được đăng ký trước ít nhất 12 giờ. Thời gian bắt đầu hiện tại chỉ còn ${checkResult.hoursLeft} giờ. Vui lòng chọn thời gian muộn hơn.`);
                    }
                }
                
                // Kiểm tra lại tính khả dụng của thiết bị khi ngày thay đổi
                selectedEquipment.forEach(eq => {
                    checkEquipmentAvailability(eq.ID_TB);
                });
                
                // Tải lại phòng cho địa điểm trong nhà khi ngày thay đổi
                // QUAN TRỌNG: Giữ nguyên selectedLocation khi thay đổi giờ/ngày
                if (selectedLocation) {
                    const isIndoor = selectedLocation.LoaiDiaDiem === 'Trong nhà' || selectedLocation.LoaiDiaDiem === 'Trong nha';
                    if (isIndoor) {
                        // Lưu lại thông tin phòng đã chọn trước khi load lại
                        const savedRoomId = selectedLocation.selectedRoomId;
                        const savedRoomRentalType = selectedLocation.selectedRoomRentalType;
                        const savedRoom = selectedLocation.selectedRoom;
                        
                        loadRoomsForLocation(selectedLocation.ID_DD);
                        
                        // Sau khi load xong, khôi phục lại thông tin phòng đã chọn (nếu phòng vẫn còn trống)
                        setTimeout(() => {
                            if (savedRoomId && savedRoom) {
                                // Kiểm tra xem phòng có còn trống không
                                // Nếu còn trống, khôi phục lại selection
                                selectedLocation.selectedRoomId = savedRoomId;
                                selectedLocation.selectedRoomRentalType = savedRoomRentalType;
                                selectedLocation.selectedRoom = savedRoom;
                                
                                // Cập nhật lại hiển thị
                                updateSelectedRoomDisplay(selectedLocation.ID_DD);
                                console.log('Restored room selection after date change:', savedRoomId);
                            }
                        }, 500);
                    }
                    
                    // Đảm bảo địa điểm đã chọn vẫn được hiển thị và highlight
                    setTimeout(() => {
                        $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"]`).addClass('selected');
                        displaySuggestedLocations();
                        displayAllLocations();
                    }, 300);
                }
                
                // Tải lại phòng cho tất cả địa điểm trong nhà đang hiển thị
                setTimeout(() => {
                    $('.suggestion-card').each(function() {
                        const locationId = $(this).data('location-id');
                        const location = suggestedLocations.find(loc => loc.ID_DD === locationId) || 
                                       allLocations.find(loc => loc.ID_DD === locationId);
                        if (location) {
                            const isIndoor = location.LoaiDiaDiem === 'Trong nhà' || location.LoaiDiaDiem === 'Trong nha';
                            if (isIndoor) {
                                loadRoomsForLocation(locationId);
                            }
                        }
                    });
                }, 300);
            }
        });
        
            // Kiểm tra tối thiểu 12 giờ khi thời gian bắt đầu thay đổi
        $('#eventTime').on('change', function() {
            const startDate = $('#eventDate').val();
            const startTime = $(this).val();
            
            if (startDate && startTime) {
                const checkResult = checkMinimum12Hours(startDate, startTime);
                if (!checkResult.valid) {
                    showError(`Sự kiện phải được đăng ký trước ít nhất 12 giờ. Thời gian bắt đầu hiện tại chỉ còn ${checkResult.hoursLeft} giờ. Vui lòng chọn thời gian muộn hơn.`);
                    $(this).focus();
                }
                
                // QUAN TRỌNG: Giữ nguyên selectedLocation khi thay đổi giờ
                if (selectedLocation) {
                    // Đảm bảo địa điểm đã chọn vẫn được hiển thị và highlight
                    setTimeout(() => {
                        $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"]`).addClass('selected');
                        if (selectedLocation.selectedRoom) {
                            updateSelectedRoomDisplay(selectedLocation.ID_DD);
                        }
                    }, 100);
                }
                
                // Kiểm tra lại tính khả dụng của combo khi thời gian thay đổi (với debounce)
                if (currentStep === 3 && comboSuggestions.length > 0) {
                    // Clear timeout cũ nếu có
                    if (comboAvailabilityCheckTimeout) {
                        clearTimeout(comboAvailabilityCheckTimeout);
                    }
                    // Debounce: chỉ check sau 500ms khi người dùng ngừng thay đổi
                    comboAvailabilityCheckTimeout = setTimeout(function() {
                        checkAllComboAvailability();
                    }, 500);
                }
            }
        });
        
        // Xác thực ngày kết thúc khi nó thay đổi
        $('#eventEndDate').on('change', function() {
            const startDate = $('#eventDate').val();
            const endDate = $(this).val();
            
            if (startDate && endDate && endDate < startDate) {
                showError('Ngày kết thúc không được trước ngày bắt đầu');
                $(this).focus();
            }
            
            // QUAN TRỌNG: Giữ nguyên selectedLocation khi thay đổi ngày kết thúc
            if (selectedLocation) {
                const isIndoor = selectedLocation.LoaiDiaDiem === 'Trong nhà' || selectedLocation.LoaiDiaDiem === 'Trong nha';
                if (isIndoor) {
                    // Lưu lại thông tin phòng đã chọn trước khi load lại
                    const savedRoomId = selectedLocation.selectedRoomId;
                    const savedRoomRentalType = selectedLocation.selectedRoomRentalType;
                    const savedRoom = selectedLocation.selectedRoom;
                    
                    loadRoomsForLocation(selectedLocation.ID_DD);
                    
                    // Sau khi load xong, khôi phục lại thông tin phòng đã chọn (nếu phòng vẫn còn trống)
                    setTimeout(() => {
                        if (savedRoomId && savedRoom) {
                            selectedLocation.selectedRoomId = savedRoomId;
                            selectedLocation.selectedRoomRentalType = savedRoomRentalType;
                            selectedLocation.selectedRoom = savedRoom;
                            updateSelectedRoomDisplay(selectedLocation.ID_DD);
                        }
                    }, 500);
                }
                
                // Đảm bảo địa điểm đã chọn vẫn được hiển thị và highlight
                setTimeout(() => {
                    $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"]`).addClass('selected');
                    displaySuggestedLocations();
                    displayAllLocations();
                }, 300);
            }
        });
        
        // Xác thực thời gian kết thúc khi nó thay đổi (nếu cùng ngày)
        $('#eventEndTime').on('change', function() {
            const startDate = $('#eventDate').val();
            const endDate = $('#eventEndDate').val();
            const startTime = $('#eventTime').val();
            const endTime = $(this).val();
            
            if (startDate === endDate && startTime && endTime && endTime <= startTime) {
                showError('Giờ kết thúc phải sau giờ bắt đầu khi cùng ngày');
                $(this).focus();
            }
            
            // QUAN TRỌNG: Giữ nguyên selectedLocation khi thay đổi giờ kết thúc
            if (selectedLocation) {
                // Đảm bảo địa điểm đã chọn vẫn được hiển thị và highlight
                setTimeout(() => {
                    $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"]`).addClass('selected');
                    if (selectedLocation.selectedRoom) {
                        updateSelectedRoomDisplay(selectedLocation.ID_DD);
                    }
                }, 100);
            }
            
            // Kiểm tra xem thời gian kết thúc sự kiện có trong quá khứ không
            if (endDate && endTime) {
                const eventEndDateTime = new Date(endDate + 'T' + endTime);
                const now = new Date();
                
                if (eventEndDateTime < now) {
                    showError('Cảnh báo: Thời gian kết thúc sự kiện đã qua. Bạn không thể đăng ký sự kiện với thời gian trong quá khứ.');
                    $(this).focus();
                }
            }
            
            // Kiểm tra lại tính khả dụng của thiết bị khi thời gian kết thúc thay đổi
            selectedEquipment.forEach(eq => {
                checkEquipmentAvailability(eq.ID_TB);
            });
            
            // Kiểm tra lại tính khả dụng của combo khi thời gian kết thúc thay đổi (với debounce)
            if (currentStep === 3 && comboSuggestions.length > 0) {
                // Clear timeout cũ nếu có
                if (comboAvailabilityCheckTimeout) {
                    clearTimeout(comboAvailabilityCheckTimeout);
                }
                // Debounce: chỉ check sau 500ms khi người dùng ngừng thay đổi
                comboAvailabilityCheckTimeout = setTimeout(function() {
                    checkAllComboAvailability();
                }, 500);
            }
            
            // Tải lại phòng cho địa điểm trong nhà khi ngày kết thúc thay đổi
            if (selectedLocation) {
                const isIndoor = selectedLocation.LoaiDiaDiem === 'Trong nhà' || selectedLocation.LoaiDiaDiem === 'Trong nha';
                if (isIndoor) {
                    loadRoomsForLocation(selectedLocation.ID_DD);
                }
            }
        });
        
        // Tải dữ liệu sự kiện để chỉnh sửa
        function loadEventForEdit(eventId) {
            // Đầu tiên tải loại sự kiện, sau đó tải dữ liệu sự kiện
            $.get('../src/controllers/event-types.php?action=get_public', function(typesData) {
                if (typesData.success) {
                    eventTypes = typesData.event_types;
                    const select = $('#eventType');
                    select.empty().append('<option value="">Chọn loại sự kiện</option>');
                    eventTypes.forEach(type => {
                        select.append(`<option value="${type.ID_LoaiSK}" data-price="${type.GiaCoBan}">${type.TenLoai}</option>`);
                    });
                    
                    // Thêm event listener cho thay đổi loại sự kiện
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
                        
                        // Cập nhật tóm tắt đơn hàng nếu đang ở bước 3
                        if (currentStep === 3) {
                            updateOrderSummary();
                        }
                    });
                    
                    // Bây giờ tải dữ liệu sự kiện
                    $.get(`../src/controllers/event-register.php?action=get_event_for_edit&event_id=${eventId}`, function(data) {
                        if (data.success) {
                            const event = data.event;
                            
                            // Điền các trường form
                            $('#eventName').val(event.TenSuKien);
                            $('#description').val(event.MoTa);
                            $('#eventDate').val(event.NgayBatDau.split(' ')[0]);
                            $('#eventTime').val(event.NgayBatDau.split(' ')[1]);
                            $('#eventEndDate').val(event.NgayKetThuc.split(' ')[0]);
                            $('#eventEndTime').val(event.NgayKetThuc.split(' ')[1]);
                            $('#expectedGuests').val(event.SoNguoiDuKien);
                            $('#budget').val(event.NganSach);
                            $('#eventType').val(event.ID_LoaiSK);
                            
                            // Hiển thị giá loại sự kiện
                            const selectedEventTypeOption = $('#eventType option:selected');
                            const price = selectedEventTypeOption.data('price');
                            if (price && price > 0) {
                                const formattedPrice = new Intl.NumberFormat('vi-VN').format(price);
                                $('#eventTypePriceValue').text(formattedPrice);
                                $('#eventTypePrice').show();
                            }
                            
                            // Cập nhật header để hiển thị chế độ chỉnh sửa
                            $('.header-section h1').text('Chỉnh sửa sự kiện');
                            $('.header-section p').text('Cập nhật thông tin sự kiện của bạn');
                            $('#submitBtn').html('<i class="fas fa-save"></i> Cập nhật sự kiện');
                            
                            // Tải dữ liệu địa điểm và thiết bị
                            loadLocationSuggestions();
                            // KHÔNG gọi loadEquipmentSuggestions() ở đây vì selectedLocation chưa được set
                            // Sẽ được gọi sau khi loadSelectedData() hoàn thành
                            
                            // Tải địa điểm và thiết bị đã chọn sau một khoảng thời gian ngắn
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
        
        // Tải dữ liệu đã chọn để chỉnh sửa
        function loadSelectedData(eventId) {
            $.get(`../src/controllers/event-register.php?action=get_event_selected_data&event_id=${eventId}`, function(data) {
                if (data.success) {
                    console.log('Loaded selected data:', data);
                    
                    // Đặt địa điểm đã chọn
                    if (data.location) {
                        // Tải đầy đủ thông tin địa điểm từ API để đảm bảo có đủ các field cần thiết
                        $.ajax({
                            url: '../src/controllers/locations.php',
                            type: 'GET',
                            data: { action: 'get_location', id: data.location.ID_DD },
                            dataType: 'json',
                            success: function(locationResponse) {
                                if (locationResponse.success && locationResponse.location) {
                                    // Merge thông tin từ cả hai nguồn
                                    selectedLocation = {
                                        ...locationResponse.location,
                                        ...data.location, // Ưu tiên thông tin từ get_event_selected_data (có LoaiThueApDung, ID_Phong, etc.)
                                        ID_DD: data.location.ID_DD // Đảm bảo ID_DD không bị ghi đè
                                    };
                                    console.log('Loaded full location data from API:', selectedLocation);
                                    
                                    // Đảm bảo địa điểm đã chọn có trong danh sách để hiển thị
                                    // Kiểm tra xem địa điểm có trong suggestedLocations không
                                    const inSuggested = suggestedLocations.some(loc => loc.ID_DD === selectedLocation.ID_DD);
                                    if (!inSuggested) {
                                        // Nếu không có trong suggested, thêm vào ĐẦU danh sách để hiển thị nổi bật
                                        suggestedLocations.unshift(selectedLocation);
                                        console.log('Added selected location to suggestedLocations (at beginning):', selectedLocation.ID_DD, selectedLocation.TenDiaDiem);
                                    } else {
                                        // Nếu đã có, cập nhật thông tin và di chuyển lên đầu
                                        const index = suggestedLocations.findIndex(loc => loc.ID_DD === selectedLocation.ID_DD);
                                        if (index !== -1) {
                                            suggestedLocations[index] = {...suggestedLocations[index], ...selectedLocation};
                                            // Di chuyển lên đầu
                                            const location = suggestedLocations.splice(index, 1)[0];
                                            suggestedLocations.unshift(location);
                                            console.log('Moved selected location to beginning of suggestedLocations:', selectedLocation.ID_DD);
                                        }
                                    }
                                    
                                    // Kiểm tra xem địa điểm có trong allLocations không
                                    const inAll = allLocations.some(loc => loc.ID_DD === selectedLocation.ID_DD);
                                    if (!inAll) {
                                        // Nếu không có trong allLocations, thêm vào ĐẦU danh sách
                                        allLocations.unshift(selectedLocation);
                                        console.log('Added selected location to allLocations (at beginning):', selectedLocation.ID_DD, selectedLocation.TenDiaDiem);
                                    } else {
                                        // Nếu đã có, cập nhật thông tin và di chuyển lên đầu
                                        const index = allLocations.findIndex(loc => loc.ID_DD === selectedLocation.ID_DD);
                                        if (index !== -1) {
                                            allLocations[index] = {...allLocations[index], ...selectedLocation};
                                            // Di chuyển lên đầu
                                            const location = allLocations.splice(index, 1)[0];
                                            allLocations.unshift(location);
                                            console.log('Moved selected location to beginning of allLocations:', selectedLocation.ID_DD);
                                        }
                                    }
                                    
                                    // Render ngay lập tức để hiển thị địa điểm đã chọn (trước khi load phòng)
                                    displaySuggestedLocations();
                                    displayAllLocations();
                                    console.log('Rendered locations with selected location:', selectedLocation.ID_DD, selectedLocation.TenDiaDiem);
                                    
                                    // Tiếp tục xử lý phòng nếu có
                                    processLocationRoomData(data.location);
                                } else {
                                    console.error('Failed to load full location data:', locationResponse);
                                    // Fallback: sử dụng data.location nếu không load được
                                    selectedLocation = data.location;
                                    addLocationToLists(selectedLocation);
                                    processLocationRoomData(data.location);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error loading full location data:', error);
                                // Fallback: sử dụng data.location nếu không load được
                                selectedLocation = data.location;
                                addLocationToLists(selectedLocation);
                                processLocationRoomData(data.location);
                            }
                        });
                        
                        // Hàm helper để thêm địa điểm vào danh sách
                        function addLocationToLists(location) {
                            const inSuggested = suggestedLocations.some(loc => loc.ID_DD === location.ID_DD);
                            if (!inSuggested) {
                                suggestedLocations.unshift(location);
                            }
                            const inAll = allLocations.some(loc => loc.ID_DD === location.ID_DD);
                            if (!inAll) {
                                allLocations.unshift(location);
                            }
                            displaySuggestedLocations();
                            displayAllLocations();
                        }
                        
                        // Hàm helper để xử lý dữ liệu phòng
                        function processLocationRoomData(locationData) {
                            // Nếu là địa điểm trong nhà và có phòng đã chọn, load thông tin phòng
                            const isIndoor = selectedLocation.LoaiDiaDiem === 'Trong nhà' || selectedLocation.LoaiDiaDiem === 'Trong nha';
                            if (isIndoor && locationData.ID_Phong) {
                                console.log('Loading room data for edit mode:', locationData.ID_Phong);
                                
                                // Đặt room ID và rental type từ database
                                selectedLocation.selectedRoomId = locationData.ID_Phong;
                                
                                // Xác định room rental type từ LoaiThueApDung
                                if (locationData.LoaiThueApDung) {
                                    selectedLocation.selectedRoomRentalType = locationData.LoaiThueApDung === 'Theo giờ' ? 'hour' : 'day';
                                    console.log('Set room rental type from LoaiThueApDung:', selectedLocation.selectedRoomRentalType);
                                }
                                
                                // Tải đầy đủ thông tin phòng từ API
                                $.ajax({
                                    url: '../src/controllers/rooms.php',
                                    type: 'GET',
                                    data: { action: 'get_room', id: locationData.ID_Phong },
                                    dataType: 'json',
                                    success: function(response) {
                                        if (response.success && response.data) {
                                            selectedLocation.selectedRoom = response.data;
                                            console.log('Loaded room data:', selectedLocation.selectedRoom);
                                            
                                            // Cập nhật hiển thị phòng đã chọn
                                            updateSelectedRoomDisplay(selectedLocation.ID_DD);
                                            
                                            // Render lại locations để hiển thị phòng đã chọn
                                            displaySuggestedLocations();
                                            displayAllLocations();
                                            
                                            // Scroll đến địa điểm đã chọn (sau khi đã scroll to top)
                                            setTimeout(() => {
                                                const selectedCard = $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"]`);
                                                if (selectedCard.length > 0 && selectedCard.offset()) {
                                                    $('html, body').animate({
                                                        scrollTop: selectedCard.offset().top - 100
                                                    }, 500);
                                                }
                                            }, 400);
                                        } else {
                                            console.error('Failed to load room details:', response);
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.error('Error loading room details:', error);
                                        // Fallback: sử dụng thông tin phòng từ location data
                                        if (locationData.TenPhong) {
                                            selectedLocation.selectedRoom = {
                                                ID_Phong: locationData.ID_Phong,
                                                TenPhong: locationData.TenPhong,
                                                GiaThueGio: locationData.PhongGiaThueGio,
                                                GiaThueNgay: locationData.PhongGiaThueNgay,
                                                LoaiThue: locationData.PhongLoaiThue,
                                                SucChua: locationData.PhongSucChua
                                            };
                                            updateSelectedRoomDisplay(selectedLocation.ID_DD);
                                            displaySuggestedLocations();
                                            displayAllLocations();
                                        }
                                    }
                                });
                            } else {
                                // Địa điểm ngoài trời hoặc không có phòng
                                // Nếu địa điểm có loại thuê "Cả hai", sử dụng loại thuê đã áp dụng từ database
                                if (selectedLocation.LoaiThue === 'Cả hai') {
                                    // Sử dụng LoaiThueApDung từ database nếu có, nếu không thì mặc định là giờ
                                    if (locationData.LoaiThueApDung) {
                                        selectedLocation.selectedRentalType = locationData.LoaiThueApDung === 'Theo giờ' ? 'hour' : 'day';
                                        console.log('Using LoaiThueApDung from database:', locationData.LoaiThueApDung, '->', selectedLocation.selectedRentalType);
                                    } else {
                                        selectedLocation.selectedRentalType = 'hour'; // Mặc định fallback
                                        console.log('No LoaiThueApDung found, using default hour');
                                    }
                                }
                                console.log('Set selected location:', selectedLocation);
                                
                                // Render lại với thông tin đã cập nhật (đảm bảo hiển thị)
                                displaySuggestedLocations();
                                displayAllLocations();
                                
                                // Scroll đến địa điểm đã chọn sau khi render (sau khi đã scroll to top)
                                setTimeout(() => {
                                    const selectedCard = $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"]`);
                                    if (selectedCard.length > 0 && selectedCard.offset()) {
                                        $('html, body').animate({
                                            scrollTop: selectedCard.offset().top - 100
                                        }, 500);
                                        console.log('Scrolled to selected location:', selectedLocation.ID_DD);
                                    } else {
                                        console.warn('Selected location card not found in DOM:', selectedLocation.ID_DD, 'Total cards:', $('.suggestion-card').length);
                                    }
                                }, 400);
                            }
                        }
                    } else {
                        console.warn('No location data in response:', data);
                    }
                    
                    // Đặt thiết bị đã chọn
                    if (data.equipment && data.equipment.length > 0) {
                        selectedEquipment = data.equipment;
                        console.log('Set selected equipment:', selectedEquipment);
                    }
                    
                    // Đặt combo đã chọn (chuyển đổi từ object sang array nếu cần)
                    if (data.combo) {
                        // Nếu data.combo là object, chuyển thành array
                        if (Array.isArray(data.combo)) {
                            selectedCombos = data.combo;
                        } else {
                            selectedCombos = [data.combo];
                        }
                        console.log('Set selected combos:', selectedCombos);
                    }
                    
                    // Sau khi đã load xong selectedLocation, mới load equipment và combo suggestions
                    // Điều này đảm bảo selectedLocation đã được set trước khi gọi loadEquipmentSuggestions()
                    if (selectedLocation) {
                        loadEquipmentSuggestions();
                        const eventType = $('#eventType').val();
                        loadComboSuggestions(eventType);
                    }
                    
                    // Hiển thị equipment và combo đã chọn
                    displayEquipmentSuggestions();
                    displayComboSuggestions();
                    
                    // Cập nhật tóm tắt đơn hàng sau một khoảng thời gian ngắn
                    setTimeout(() => {
                        console.log('Force updating order summary after loading selected data');
                        updateOrderSummary();
                    }, 500);
                    
                    // Cũng thử cập nhật ngay lập tức
                    console.log('Immediate update order summary');
                    updateOrderSummary();
                }
            }, 'json').fail(function() {
                console.log('Failed to load selected data');
            });
        }
        
        // Cập nhật tóm tắt đơn hàng (cho chế độ chỉnh sửa)
        function forceUpdateOrderSummary() {
            console.log('Force updating order summary');
            if (selectedLocation) {
                updateOrderSummary();
            } else {
                console.log('No selected location, cannot force update');
            }
        }
        
        // Tải loại sự kiện
        function loadEventTypes() {
            $.get('../src/controllers/event-types.php?action=get_public', function(data) {
                if (data.success) {
                    eventTypes = data.event_types;
                    const select = $('#eventType');
                    select.empty().append('<option value="">Chọn loại sự kiện</option>');
                    eventTypes.forEach(type => {
                        select.append(`<option value="${type.ID_LoaiSK}" data-price="${type.GiaCoBan}">${type.TenLoai}</option>`);
                    });
                    
                    // Thêm event listener cho thay đổi loại sự kiện
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
                        
                        // Cập nhật tóm tắt đơn hàng nếu đang ở bước 3
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
        
        // Thay đổi bước
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
                } else if (currentStep === 3) {
                    // Kiểm tra lại tính khả dụng của thiết bị khi vào bước 3
                    selectedEquipment.forEach(eq => {
                        checkEquipmentAvailability(eq.ID_TB);
                    });
                    // Kiểm tra tính khả dụng của combo
                    if (comboSuggestions.length > 0) {
                        checkAllComboAvailability();
                    }
                }
            }
            
            // Ẩn bước hiện tại
            $(`#step${currentStep}`).removeClass('active');
            $(`#step${currentStep}-indicator`).removeClass('active').addClass('completed');
            
            // Hiển thị bước tiếp theo
            currentStep += direction;
            $(`#step${currentStep}`).addClass('active');
            $(`#step${currentStep}-indicator`).addClass('active');
            
            // Scroll to top khi chuyển step
            $('html, body').animate({
                scrollTop: 0
            }, 300);
            
            // Khi vào step 2, đảm bảo địa điểm đã chọn được hiển thị rõ ràng (đặc biệt trong edit mode)
            if (currentStep === 2 && selectedLocation) {
                // Đảm bảo địa điểm đã chọn có trong danh sách để hiển thị
                const inSuggested = suggestedLocations.some(loc => loc.ID_DD === selectedLocation.ID_DD);
                const inAll = allLocations.some(loc => loc.ID_DD === selectedLocation.ID_DD);
                
                if (!inSuggested) {
                    suggestedLocations.unshift(selectedLocation);
                }
                if (!inAll) {
                    allLocations.unshift(selectedLocation);
                }
                
                // Render lại để hiển thị địa điểm đã chọn
                displaySuggestedLocations();
                displayAllLocations();
                
                // Highlight địa điểm đã chọn
                setTimeout(() => {
                    $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"]`).addClass('selected');
                    
                    // Nếu có phòng đã chọn, cập nhật hiển thị
                    if (selectedLocation.selectedRoom) {
                        updateSelectedRoomDisplay(selectedLocation.ID_DD);
                    }
                    
                    // Scroll đến địa điểm đã chọn (sau khi đã scroll to top)
                    setTimeout(() => {
                        const selectedCard = $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"]`);
                        if (selectedCard.length > 0 && selectedCard.offset()) {
                            $('html, body').animate({
                                scrollTop: selectedCard.offset().top - 100
                            }, 500);
                        }
                    }, 400); // Đợi scroll to top hoàn tất (300ms) + thêm 100ms
                }, 100);
            }
            
            // Cập nhật nút điều hướng
            updateNavigationButtons();
        }
        
        // Xác thực bước hiện tại
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
                
                // Xác thực ngày
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
                
                // Kiểm tra xem thời gian bắt đầu sự kiện có cách ít nhất 12 giờ từ bây giờ không
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
                
                // Xác thực thời gian nếu cùng ngày
                if (eventStartDateObj.getTime() === eventEndDateObj.getTime()) {
                    if (eventTime >= eventEndTime) {
                        showError('Giờ kết thúc phải sau giờ bắt đầu khi cùng ngày');
                        return false;
                    }
                }
                
                // Kiểm tra xem thời gian kết thúc sự kiện có trong quá khứ không
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
                
                // Xác thực lựa chọn phòng cho địa điểm trong nhà
                const isIndoor = selectedLocation.LoaiDiaDiem === 'Trong nhà' || selectedLocation.LoaiDiaDiem === 'Trong nha';
                
                // QUAN TRỌNG: Phải chọn loại thuê TRƯỚC khi chọn phòng
                if (isIndoor && !selectedLocation.selectedRoomRentalType) {
                    showError('Vui lòng chọn loại thuê (theo giờ hoặc theo ngày) trước khi chọn phòng');
                    return false;
                }
                
                if (isIndoor && !selectedLocation.selectedRoomId) {
                    showError('Vui lòng chọn phòng cho địa điểm trong nhà');
                    return false;
                }
                
                // Xác thực lựa chọn loại thuê phòng (đã được validate ở trên, nhưng double check)
                if (isIndoor && selectedLocation.selectedRoom && !selectedLocation.selectedRoomRentalType) {
                    showError('Vui lòng chọn loại thuê (theo giờ hoặc theo ngày) cho phòng');
                    return false;
                }
                
                // Xác thực lựa chọn loại thuê cho địa điểm ngoài trời có tùy chọn "Cả hai"
                if (!isIndoor && selectedLocation.LoaiThue === 'Cả hai' && !selectedLocation.selectedRentalType) {
                    showError('Vui lòng chọn loại thuê (theo giờ hoặc theo ngày) cho địa điểm này');
                    return false;
                }
            } else if (currentStep === 3) {
                // Xác thực bước 3 - lựa chọn thiết bị là tùy chọn
                // Không cần xác thực cụ thể cho bước 3
                console.log('Step 3 validation passed');
            }
            
            return true;
        }
        
        // Cập nhật nút điều hướng
        function updateNavigationButtons() {
            $('#prevBtn').toggle(currentStep > 1);
            $('#nextBtn').toggle(currentStep < 3);
            $('#submitBtn').toggle(currentStep === 3);
        }
        
        // Tải địa điểm đề xuất dựa trên loại sự kiện
        function loadLocationSuggestions() {
            const eventTypeId = $('#eventType').val();
            if (!eventTypeId) {
                showError('Vui lòng chọn loại sự kiện trước');
                return;
            }
            
            // Tìm tên loại sự kiện từ danh sách đã tải
            const eventType = eventTypes.find(type => type.ID_LoaiSK == eventTypeId);
            if (!eventType) {
                showError('Không tìm thấy thông tin loại sự kiện');
                return;
            }
            
            // Tải địa điểm đề xuất dựa trên loại sự kiện
            $('#suggestedLocations').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải địa điểm đề xuất...</p>
                </div>
            `);
            
            // Tải tất cả địa điểm
            $('#allLocations').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải tất cả địa điểm...</p>
                </div>
            `);
            
            // Tải địa điểm đề xuất
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
            
            // Tải tất cả địa điểm
            $.get('../src/controllers/event-register.php?action=get_all_locations', function(data) {
                if (data.success) {
                    allLocations = data.locations;
                    locationTypes = [...new Set(data.locations.map(loc => loc.LoaiDiaDiem))].filter(type => type);
                    
                    // Parse địa chỉ để lấy tỉnh/thành phố và quận/huyện
                    parseLocationAddresses(data.locations);
                    
                    loadLocationTypeFilter();
                    loadCityFilter();
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
        
        // Hàm helper để lấy text giá địa điểm
        function getLocationPriceText(location, selectedType = null) {
            if (!location) return 'Chưa có giá';
            
            // Nếu một loại cụ thể được chọn, chỉ hiển thị giá đó
            if (selectedType === 'hour' && location.GiaThueGio && location.GiaThueGio > 0) {
                return `${new Intl.NumberFormat('vi-VN').format(location.GiaThueGio)}/giờ`;
            }
            if (selectedType === 'day' && location.GiaThueNgay && location.GiaThueNgay > 0) {
                return `${new Intl.NumberFormat('vi-VN').format(location.GiaThueNgay)}/ngày`;
            }
            
            // Mặc định: hiển thị tất cả giá có sẵn
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
        
        // Cập nhật hiển thị giá địa điểm khi người dùng chọn loại thuê
        function updateLocationPrice(locationId, rentalType, section) {
            console.log('updateLocationPrice called:', {locationId, rentalType, section});
            
            const location = allLocations.find(loc => loc.ID_DD === locationId);
            if (!location) {
                console.log('Location not found:', locationId);
                return;
            }
            
            console.log('Found location:', location);
            
            // Cập nhật hiển thị giá ngay lập tức
            const priceText = getLocationPriceText(location, rentalType);
            const priceElementId = section === 'suggested' ? `price-suggested-${locationId}` : `price-all-${locationId}`;
            $(`#${priceElementId}`).text(priceText);
            
            console.log('Updated price element:', priceElementId, 'with text:', priceText);
            
            // Cập nhật dữ liệu địa điểm đã chọn nếu địa điểm này đang được chọn
            if (selectedLocation && selectedLocation.ID_DD === locationId) {
                console.log('Updating selectedLocation.selectedRentalType from', selectedLocation.selectedRentalType, 'to', rentalType);
                selectedLocation.selectedRentalType = rentalType;
                console.log('Updated selectedLocation:', selectedLocation);
                
                // Cũng cập nhật địa điểm trong mảng allLocations để lưu lựa chọn
                const locationInAllLocations = allLocations.find(loc => loc.ID_DD === locationId);
                if (locationInAllLocations) {
                    locationInAllLocations.selectedRentalType = rentalType;
                    console.log('Updated location in allLocations:', locationInAllLocations);
                }
                
                // Cập nhật tất cả dropdown cho địa điểm này để hiển thị cùng lựa chọn
                $(`.suggestion-card[data-location-id="${locationId}"] select`).val(rentalType);
                
                // Cập nhật tóm tắt đơn hàng với giá mới
                updateOrderSummary();
            } else {
                console.log('Location not currently selected or no selectedLocation');
                
                // Ngay cả khi không được chọn, lưu tùy chọn để tham khảo sau
                const locationInAllLocations = allLocations.find(loc => loc.ID_DD === locationId);
                if (locationInAllLocations) {
                    locationInAllLocations.selectedRentalType = rentalType;
                    console.log('Stored preference for non-selected location:', locationInAllLocations);
                }
            }
        }
        
        // Tính tổng giá cho việc submit form
        function calculateTotalPrice() {
            let totalPrice = 0;
            
            // Lấy giá loại sự kiện
            const selectedEventTypeOption = $('#eventType option:selected');
            const eventTypePrice = parseFloat(selectedEventTypeOption.data('price')) || 0;
            totalPrice += eventTypePrice;
            
            console.log('calculateTotalPrice - Event type price:', eventTypePrice);
            console.log('calculateTotalPrice - Selected location:', selectedLocation);
            
            // Tính giá địa điểm dựa trên loại thuê và thời lượng
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
                    
                    // Nếu là địa điểm trong nhà và có chọn phòng, tính giá theo phòng
                    const isIndoor = selectedLocation.LoaiDiaDiem === 'Trong nhà' || selectedLocation.LoaiDiaDiem === 'Trong nha';
                    if (isIndoor && selectedLocation.selectedRoom) {
                        const room = selectedLocation.selectedRoom;
                        console.log('calculateTotalPrice - Using room price:', room);
                        
                        // Xác định loại thuê: ưu tiên selectedRoomRentalType, nếu không có thì dựa vào giá có sẵn
                        let rentalType = selectedLocation.selectedRoomRentalType;
                        if (!rentalType) {
                            // Mặc định: nếu có giá giờ thì chọn giờ, nếu không có giá giờ nhưng có giá ngày thì chọn ngày
                            if (room.GiaThueGio && room.GiaThueGio > 0) {
                                rentalType = 'hour';
                                selectedLocation.selectedRoomRentalType = 'hour';
                            } else if (room.GiaThueNgay && room.GiaThueNgay > 0) {
                                rentalType = 'day';
                                selectedLocation.selectedRoomRentalType = 'day';
                            }
                        }
                        
                        if (room.LoaiThue === 'Theo giờ' && room.GiaThueGio && room.GiaThueGio > 0) {
                            // Phòng chỉ có giá theo giờ
                            const roomPrice = durationHours * parseFloat(room.GiaThueGio);
                            totalPrice += roomPrice;
                            console.log('calculateTotalPrice - Room hourly price added:', roomPrice);
                        } else if (room.LoaiThue === 'Theo ngày' && room.GiaThueNgay && room.GiaThueNgay > 0) {
                            // Phòng chỉ có giá theo ngày
                            const roomPrice = durationDays * parseFloat(room.GiaThueNgay);
                            totalPrice += roomPrice;
                            console.log('calculateTotalPrice - Room daily price added:', roomPrice);
                        } else if (room.LoaiThue === 'Cả hai') {
                            // Phòng có cả hai loại giá
                            if (rentalType === 'hour' && room.GiaThueGio && room.GiaThueGio > 0) {
                                const roomPrice = durationHours * parseFloat(room.GiaThueGio);
                                totalPrice += roomPrice;
                                console.log('calculateTotalPrice - Room selected hourly price added:', roomPrice);
                            } else if (rentalType === 'day' && room.GiaThueNgay && room.GiaThueNgay > 0) {
                                const roomPrice = durationDays * parseFloat(room.GiaThueNgay);
                                totalPrice += roomPrice;
                                console.log('calculateTotalPrice - Room selected daily price added:', roomPrice);
                            } else {
                                // Chưa chọn loại thuê hoặc không có giá cho loại đã chọn - mặc định chọn giờ nếu có
                                const hourlyPrice = durationHours * parseFloat(room.GiaThueGio || 0);
                                const dailyPrice = durationDays * parseFloat(room.GiaThueNgay || 0);
                                if (hourlyPrice > 0 && dailyPrice > 0) {
                                    // Có cả hai giá - mặc định chọn giờ
                                    totalPrice += hourlyPrice;
                                    if (!selectedLocation.selectedRoomRentalType) {
                                        selectedLocation.selectedRoomRentalType = 'hour';
                                    }
                                    console.log('calculateTotalPrice - Room default hourly price added (both available):', hourlyPrice);
                                } else if (hourlyPrice > 0) {
                                    totalPrice += hourlyPrice;
                                    console.log('calculateTotalPrice - Room hourly price added (only hourly available):', hourlyPrice);
                                } else if (dailyPrice > 0) {
                                    totalPrice += dailyPrice;
                                    console.log('calculateTotalPrice - Room daily price added (only daily available):', dailyPrice);
                                }
                            }
                        }
                    } else {
                        // Địa điểm ngoài trời hoặc không chọn phòng, tính giá theo địa điểm
                    if (selectedLocation.LoaiThue === 'Theo giờ' && selectedLocation.GiaThueGio) {
                        const locationPrice = durationHours * parseFloat(selectedLocation.GiaThueGio);
                        totalPrice += locationPrice;
                        console.log('calculateTotalPrice - Hourly price added:', locationPrice);
                    } else if (selectedLocation.LoaiThue === 'Theo ngày' && selectedLocation.GiaThueNgay) {
                        const locationPrice = durationDays * parseFloat(selectedLocation.GiaThueNgay);
                        totalPrice += locationPrice;
                        console.log('calculateTotalPrice - Daily price added:', locationPrice);
                    } else if (selectedLocation.LoaiThue === 'Cả hai') {
                        // Kiểm tra xem người dùng đã chọn loại thuê cụ thể chưa
                        if (selectedLocation.selectedRentalType === 'hour' && selectedLocation.GiaThueGio) {
                            const locationPrice = durationHours * parseFloat(selectedLocation.GiaThueGio);
                            totalPrice += locationPrice;
                            console.log('calculateTotalPrice - Selected hourly price added:', locationPrice);
                        } else if (selectedLocation.selectedRentalType === 'day' && selectedLocation.GiaThueNgay) {
                            const locationPrice = durationDays * parseFloat(selectedLocation.GiaThueNgay);
                            totalPrice += locationPrice;
                            console.log('calculateTotalPrice - Selected daily price added:', locationPrice);
                        } else {
                            // Mặc định: Sử dụng tùy chọn rẻ hơn
                            const hourlyPrice = durationHours * parseFloat(selectedLocation.GiaThueGio || 0);
                            const dailyPrice = durationDays * parseFloat(selectedLocation.GiaThueNgay || 0);
                            
                            console.log('calculateTotalPrice - Both prices calculated:', {hourlyPrice, dailyPrice});
                            
                            if (hourlyPrice > 0 && dailyPrice > 0) {
                                // Không thêm vào tổng cho đến khi người dùng chọn loại thuê
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
            }
            
            // Add combo prices if selected (tính tổng giá của tất cả combo đã chọn - cả pending và confirmed)
            const allSelectedCombos = [...pendingComboSelections, ...selectedCombos];
            if (allSelectedCombos && allSelectedCombos.length > 0) {
                let totalComboPrice = 0;
                allSelectedCombos.forEach(combo => {
                    const comboPrice = parseFloat(combo.GiaCombo) || 0;
                    totalComboPrice += comboPrice;
                });
                totalPrice += totalComboPrice;
                console.log('calculateTotalPrice - Total combo price added:', totalComboPrice, 'from', allSelectedCombos.length, 'combos (pending:', pendingComboSelections.length, ', confirmed:', selectedCombos.length, ')');
            }
            
            // Add individual equipment prices (tính theo số lượng)
            if (selectedEquipment.length > 0) {
                let equipmentTotal = 0;
                selectedEquipment.forEach(equipment => {
                    const equipmentPrice = parseFloat(equipment.GiaThue) || 0;
                    const quantity = parseInt(equipment.SoLuong) || 1;
                    equipmentTotal += equipmentPrice * quantity;
                });
                totalPrice += equipmentTotal;
                console.log('calculateTotalPrice - Equipment total added:', equipmentTotal);
            }
            
            console.log('calculateTotalPrice - Final total price:', totalPrice);
            return totalPrice;
        }
        function displaySuggestedLocations(filteredLocations = null) {
            const locationsToShow = filteredLocations !== null ? filteredLocations : suggestedLocations;
            
            if (locationsToShow.length === 0) {
                const searchTerm = $('#locationSearch').val().trim();
                const hasFilter = searchTerm || $('#locationTypeFilter').val() || $('#priceTypeFilter').val() || $('#priceRangeFilter').val();
                
                $('#suggestedLocations').html(`
                    <div class="alert alert-${hasFilter ? 'info' : 'warning'}">
                        <i class="fas fa-${hasFilter ? 'info-circle' : 'exclamation-triangle'}"></i>
                        ${hasFilter ? 'Không tìm thấy địa điểm đề xuất phù hợp với bộ lọc.' : 'Không có địa điểm đề xuất cho loại sự kiện này.'}
                    </div>
                `);
                return;
            }
            
            let html = '';
            locationsToShow.forEach(location => {
                // Xác định loại thuê nào để hiển thị dựa trên địa điểm đã chọn
                let selectedRentalType = null;
                if (selectedLocation && selectedLocation.ID_DD === location.ID_DD) {
                    // Sử dụng lựa chọn đã lưu cho địa điểm hiện đang được chọn
                    selectedRentalType = selectedLocation.selectedRentalType || 'hour';
                } else if (location.LoaiThue === 'Cả hai') {
                    // Đối với địa điểm chưa được chọn có "Cả hai", kiểm tra xem có tùy chọn đã lưu không
                    // Đầu tiên kiểm tra xem địa điểm này đã được chọn trước đó và có tùy chọn đã lưu không
                    const storedLocation = allLocations.find(loc => loc.ID_DD === location.ID_DD);
                    if (storedLocation && storedLocation.selectedRentalType) {
                        selectedRentalType = storedLocation.selectedRentalType;
                    } else {
                        selectedRentalType = 'hour'; // Mặc định là theo giờ cho địa điểm 'Cả hai'
                    }
                }
                const priceText = getLocationPriceText(location, selectedRentalType);
                const isSelected = selectedLocation && selectedLocation.ID_DD === location.ID_DD;
                const imagePath = location.HinhAnh ? `../img/diadiem/${location.HinhAnh}` : '../img/diadiem/default.php';
                const isIndoor = location.LoaiDiaDiem === 'Trong nhà' || location.LoaiDiaDiem === 'Trong nha';
                
                console.log('Rendering location:', location.ID_DD, 'LoaiDiaDiem:', location.LoaiDiaDiem, 'isIndoor:', isIndoor);
                
                // Thêm style highlight cho địa điểm đã chọn (giống như trong hình)
                const selectedStyleSuggested = isSelected ? 'style="border: 2px solid #0d6efd; background-color: #f0f8ff;"' : '';
                
                html += `
                    <div class="suggestion-card ${isSelected ? 'selected' : ''}" onclick="selectLocation(${location.ID_DD})" data-location-id="${location.ID_DD}" ${selectedStyleSuggested}>
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
                                ${!isIndoor ? `
                                <h5 class="text-primary" id="price-suggested-${location.ID_DD}">${priceText}</h5>
                                <small class="text-muted">Giá thuê</small>
                                ` : `
                                    <h5 class="text-primary" id="price-suggested-${location.ID_DD}" style="display: none;"></h5>
                                    <small class="text-muted" style="display: none;">Giá thuê</small>
                                `}
                                ${isIndoor ? `
                                    <div class="room-selection-card">
                                        <div class="room-selection-header mb-3">
                                            <i class="fas fa-door-open"></i>
                                            <label>Chọn phòng <span class="required-badge">*</span></label>
                                        </div>
                                        
                                        <div id="selected-room-info-${location.ID_DD}" class="selected-room-info" style="display: none;">
                                            <h6><i class="fas fa-check-circle text-success"></i> Phòng đã chọn</h6>
                                            <p class="mb-1"><strong id="selected-room-name-${location.ID_DD}"></strong></p>
                                            <p class="mb-0 text-muted small" id="selected-room-details-${location.ID_DD}"></p>
                                        </div>
                                        
                                        <button type="button" 
                                                class="btn btn-select-room w-100" 
                                                onclick="openRoomSelectionModal(${location.ID_DD})"
                                                id="btn-select-room-${location.ID_DD}">
                                            <i class="fas fa-door-open"></i> Chọn phòng
                                        </button>
                                    </div>
                                ` : ''}
                                ${location.LoaiThue === 'Cả hai' && location.LoaiDiaDiem !== 'Trong nhà' ? `
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
            
            // Debug: Kiểm tra xem dropdowns đã được render chưa
            console.log('Suggested locations rendered. Dropdowns found:', $('.suggestion-card .form-select-sm').length);
            console.log('Suggested locations data:', suggestedLocations);
            
            // Cập nhật giá trị dropdown cho địa điểm đã chọn
            if (selectedLocation && selectedLocation.LoaiThue === 'Cả hai') {
                $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"] select`).val(selectedLocation.selectedRentalType || 'hour');
            }
            
            // Tải phòng cho tất cả địa điểm trong nhà trong danh sách đề xuất
            // Sử dụng setTimeout để đảm bảo DOM đã sẵn sàng
            setTimeout(() => {
                const eventDate = $('#eventDate').val();
                const eventEndDate = $('#eventEndDate').val();
                
                suggestedLocations.forEach(location => {
                    const isIndoor = location.LoaiDiaDiem === 'Trong nhà' || location.LoaiDiaDiem === 'Trong nha';
                    console.log('Checking location:', location.ID_DD, 'LoaiDiaDiem:', location.LoaiDiaDiem, 'isIndoor:', isIndoor);
                    if (isIndoor) {
                        console.log('Loading rooms for indoor location:', location.ID_DD, 'Event dates:', eventDate, eventEndDate);
                        loadRoomsForLocation(location.ID_DD);
                    }
                });
            }, 200);
        }
        
        // Hiển thị tất cả địa điểm
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
                // Xác định loại thuê nào để hiển thị dựa trên địa điểm đã chọn
                let selectedRentalType = null;
                if (selectedLocation && selectedLocation.ID_DD === location.ID_DD) {
                    // Sử dụng lựa chọn đã lưu cho địa điểm hiện đang được chọn
                    selectedRentalType = selectedLocation.selectedRentalType || 'hour';
                } else if (location.LoaiThue === 'Cả hai') {
                    // Đối với địa điểm chưa được chọn có "Cả hai", kiểm tra xem có tùy chọn đã lưu không
                    // Đầu tiên kiểm tra xem địa điểm này đã được chọn trước đó và có tùy chọn đã lưu không
                    const storedLocation = allLocations.find(loc => loc.ID_DD === location.ID_DD);
                    if (storedLocation && storedLocation.selectedRentalType) {
                        selectedRentalType = storedLocation.selectedRentalType;
                    } else {
                        selectedRentalType = 'hour'; // Mặc định là theo giờ cho địa điểm 'Cả hai'
                    }
                }
                const priceText = getLocationPriceText(location, selectedRentalType);
                const isSelected = selectedLocation && selectedLocation.ID_DD === location.ID_DD;
                const imagePath = location.HinhAnh ? `../img/diadiem/${location.HinhAnh}` : '../img/diadiem/default.php';
                const isIndoor = location.LoaiDiaDiem === 'Trong nhà' || location.LoaiDiaDiem === 'Trong nha';
                
                console.log('Rendering location (all):', location.ID_DD, 'LoaiDiaDiem:', location.LoaiDiaDiem, 'isIndoor:', isIndoor);
                
                // Thêm style highlight cho địa điểm đã chọn (giống như trong hình)
                const selectedStyleAll = isSelected ? 'style="border: 2px solid #0d6efd; background-color: #f0f8ff;"' : '';
                
                html += `
                    <div class="suggestion-card ${isSelected ? 'selected' : ''}" onclick="selectLocation(${location.ID_DD})" data-location-id="${location.ID_DD}" ${selectedStyleAll}>
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
                                ${!isIndoor ? `
                                <h5 class="text-primary" id="price-all-${location.ID_DD}">${priceText}</h5>
                                <small class="text-muted">Giá thuê</small>
                                ` : `
                                    <h5 class="text-primary" id="price-all-${location.ID_DD}" style="display: none;"></h5>
                                    <small class="text-muted" style="display: none;">Giá thuê</small>
                                `}
                                ${isIndoor ? `
                                    <div class="room-selection-card">
                                        <div class="room-selection-header mb-3">
                                            <i class="fas fa-door-open"></i>
                                            <label>Chọn phòng <span class="required-badge">*</span></label>
                                        </div>
                                        
                                        <div id="selected-room-info-all-${location.ID_DD}" class="selected-room-info" style="display: none;">
                                            <h6><i class="fas fa-check-circle text-success"></i> Phòng đã chọn</h6>
                                            <p class="mb-1"><strong id="selected-room-name-all-${location.ID_DD}"></strong></p>
                                            <p class="mb-0 text-muted small" id="selected-room-details-all-${location.ID_DD}"></p>
                                        </div>
                                        
                                        <button type="button" 
                                                class="btn btn-select-room w-100" 
                                                onclick="openRoomSelectionModal(${location.ID_DD})"
                                                id="btn-select-room-all-${location.ID_DD}">
                                            <i class="fas fa-door-open"></i> Chọn phòng
                                        </button>
                                    </div>
                                ` : ''}
                                ${location.LoaiThue === 'Cả hai' && location.LoaiDiaDiem !== 'Trong nhà' ? `
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
            
            // Debug: Kiểm tra xem dropdowns đã được render chưa
            console.log('All locations rendered. Dropdowns found:', $('.suggestion-card .form-select-sm').length);
            console.log('Locations to show:', locationsToShow);
            
            // Cập nhật giá trị dropdown cho địa điểm đã chọn
            if (selectedLocation && selectedLocation.LoaiThue === 'Cả hai') {
                $(`.suggestion-card[data-location-id="${selectedLocation.ID_DD}"] select`).val(selectedLocation.selectedRentalType || 'hour');
            }
            
            // Tải phòng cho tất cả địa điểm trong nhà
            // Sử dụng setTimeout để đảm bảo DOM đã sẵn sàng
            setTimeout(() => {
                const eventDate = $('#eventDate').val();
                const eventEndDate = $('#eventEndDate').val();
                
                locationsToShow.forEach(location => {
                    const isIndoor = location.LoaiDiaDiem === 'Trong nhà' || location.LoaiDiaDiem === 'Trong nha';
                    console.log('Checking location:', location.ID_DD, 'LoaiDiaDiem:', location.LoaiDiaDiem, 'isIndoor:', isIndoor);
                    if (isIndoor) {
                        console.log('Loading rooms for indoor location:', location.ID_DD, 'Event dates:', eventDate, eventEndDate);
                        loadRoomsForLocation(location.ID_DD);
                    }
                });
            }, 200);
        }
        
        // Hàm parse địa chỉ để lấy tỉnh/thành phố và quận/huyện
        function parseLocationAddresses(locations) {
            cities = [];
            districts = [];
            
            locations.forEach(location => {
                if (!location.DiaChi) return;
                
                const address = location.DiaChi;
                
                // Parse tỉnh/thành phố (thường ở cuối địa chỉ)
                // Các pattern: TP.HCM, TP. HCM, Hà Nội, Đà Nẵng, v.v.
                const cityPatterns = [
                    /TP\.?\s*HCM/i,
                    /TP\.?\s*Hồ\s*Chí\s*Minh/i,
                    /Hà\s*Nội/i,
                    /Đà\s*Nẵng/i,
                    /Cần\s*Thơ/i,
                    /Hải\s*Phòng/i,
                    /An\s*Giang/i,
                    /Bà\s*Rịa\s*-\s*Vũng\s*Tàu/i,
                    /Bắc\s*Giang/i,
                    /Bắc\s*Kạn/i,
                    /Bạc\s*Liêu/i,
                    /Bắc\s*Ninh/i,
                    /Bến\s*Tre/i,
                    /Bình\s*Định/i,
                    /Bình\s*Dương/i,
                    /Bình\s*Phước/i,
                    /Bình\s*Thuận/i,
                    /Cà\s*Mau/i,
                    /Cao\s*Bằng/i,
                    /Đắk\s*Lắk/i,
                    /Đắk\s*Nông/i,
                    /Điện\s*Biên/i,
                    /Đồng\s*Nai/i,
                    /Đồng\s*Tháp/i,
                    /Gia\s*Lai/i,
                    /Hà\s*Giang/i,
                    /Hà\s*Nam/i,
                    /Hà\s*Tĩnh/i,
                    /Hải\s*Dương/i,
                    /Hậu\s*Giang/i,
                    /Hòa\s*Bình/i,
                    /Hưng\s*Yên/i,
                    /Khánh\s*Hòa/i,
                    /Kiên\s*Giang/i,
                    /Kon\s*Tum/i,
                    /Lai\s*Châu/i,
                    /Lâm\s*Đồng/i,
                    /Lạng\s*Sơn/i,
                    /Lào\s*Cai/i,
                    /Long\s*An/i,
                    /Nam\s*Định/i,
                    /Nghệ\s*An/i,
                    /Ninh\s*Bình/i,
                    /Ninh\s*Thuận/i,
                    /Phú\s*Thọ/i,
                    /Phú\s*Yên/i,
                    /Quảng\s*Bình/i,
                    /Quảng\s*Nam/i,
                    /Quảng\s*Ngãi/i,
                    /Quảng\s*Ninh/i,
                    /Quảng\s*Trị/i,
                    /Sóc\s*Trăng/i,
                    /Sơn\s*La/i,
                    /Tây\s*Ninh/i,
                    /Thái\s*Bình/i,
                    /Thái\s*Nguyên/i,
                    /Thanh\s*Hóa/i,
                    /Thừa\s*Thiên\s*Huế/i,
                    /Tiền\s*Giang/i,
                    /Trà\s*Vinh/i,
                    /Tuyên\s*Quang/i,
                    /Vĩnh\s*Long/i,
                    /Vĩnh\s*Phúc/i,
                    /Yên\s*Bái/i
                ];
                
                let foundCity = null;
                for (const pattern of cityPatterns) {
                    const match = address.match(pattern);
                    if (match) {
                        foundCity = match[0].trim();
                        // Chuẩn hóa tên thành phố
                        if (/TP\.?\s*HCM/i.test(foundCity)) {
                            foundCity = 'TP.HCM';
                        } else if (/TP\.?\s*Hồ\s*Chí\s*Minh/i.test(foundCity)) {
                            foundCity = 'TP.HCM';
                        }
                        break;
                    }
                }
                
                if (foundCity && !cities.includes(foundCity)) {
                    cities.push(foundCity);
                }
                
                // Parse quận/huyện (thường ở giữa địa chỉ)
                // Các pattern: Quận 1, Quận Phú Nhuận, Huyện Củ Chi, v.v.
                const districtPatterns = [
                    /(Quận\s+\d+)/i,
                    /(Quận\s+[A-Za-zÀ-ỹ\s]+)/i,
                    /(Huyện\s+[A-Za-zÀ-ỹ\s]+)/i,
                    /(Thị\s+xã\s+[A-Za-zÀ-ỹ\s]+)/i,
                    /(Thành\s+phố\s+[A-Za-zÀ-ỹ\s]+)/i
                ];
                
                let foundDistrict = null;
                for (const pattern of districtPatterns) {
                    const match = address.match(pattern);
                    if (match) {
                        foundDistrict = match[1].trim();
                        // Loại bỏ phần tỉnh/thành phố nếu có trong district
                        if (!foundDistrict.match(/TP\.?\s*HCM|Hà\s*Nội|Đà\s*Nẵng/i)) {
                            break;
                        }
                    }
                }
                
                if (foundDistrict && !districts.includes(foundDistrict)) {
                    districts.push(foundDistrict);
                }
            });
            
            // Sắp xếp danh sách
            cities.sort();
            districts.sort();
        }
        
        // Tải các tùy chọn bộ lọc loại địa điểm
        function loadLocationTypeFilter() {
            const select = $('#locationTypeFilter');
            select.empty().append('<option value="">Tất cả loại</option>');
            locationTypes.forEach(type => {
                select.append(`<option value="${type}">${type}</option>`);
            });
        }
        
        // Tải các tùy chọn bộ lọc tỉnh/thành phố
        function loadCityFilter() {
            const select = $('#cityFilter');
            select.empty().append('<option value="">Tất cả</option>');
            cities.forEach(city => {
                select.append(`<option value="${city}">${city}</option>`);
            });
        }
        
        // Tải các tùy chọn bộ lọc quận/huyện (dựa trên tỉnh/thành phố đã chọn)
        function loadDistrictFilter(selectedCity = null) {
            const select = $('#districtFilter');
            select.empty().append('<option value="">Tất cả</option>');
            
            if (!selectedCity) {
                // Nếu không chọn tỉnh/thành phố, hiển thị tất cả quận/huyện
                districts.forEach(district => {
                    select.append(`<option value="${district}">${district}</option>`);
                });
            } else {
                // Nếu có chọn tỉnh/thành phố, chỉ hiển thị quận/huyện của tỉnh/thành phố đó
                const filteredDistricts = allLocations
                    .filter(loc => {
                        if (!loc.DiaChi) return false;
                        // Kiểm tra xem địa chỉ có chứa tỉnh/thành phố đã chọn không
                        const address = loc.DiaChi;
                        if (selectedCity === 'TP.HCM') {
                            return /TP\.?\s*HCM|TP\.?\s*Hồ\s*Chí\s*Minh/i.test(address);
                        }
                        return new RegExp(selectedCity.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i').test(address);
                    })
                    .map(loc => {
                        // Parse quận/huyện từ địa chỉ
                        const address = loc.DiaChi;
                        const districtPatterns = [
                            /(Quận\s+\d+)/i,
                            /(Quận\s+[A-Za-zÀ-ỹ\s]+)/i,
                            /(Huyện\s+[A-Za-zÀ-ỹ\s]+)/i,
                            /(Thị\s+xã\s+[A-Za-zÀ-ỹ\s]+)/i,
                            /(Thành\s+phố\s+[A-Za-zÀ-ỹ\s]+)/i
                        ];
                        
                        for (const pattern of districtPatterns) {
                            const match = address.match(pattern);
                            if (match) {
                                const district = match[1].trim();
                                if (!district.match(/TP\.?\s*HCM|Hà\s*Nội|Đà\s*Nẵng/i)) {
                                    return district;
                                }
                            }
                        }
                        return null;
                    })
                    .filter(district => district !== null);
                
                const uniqueDistricts = [...new Set(filteredDistricts)].sort();
                uniqueDistricts.forEach(district => {
                    select.append(`<option value="${district}">${district}</option>`);
                });
            }
        }
        
        // Biến để debounce tìm kiếm
        let locationSearchTimeout = null;
        
        // Thiết lập bộ lọc địa điểm
        function setupLocationFilters() {
            // Bộ lọc tìm kiếm với debounce (tránh gọi quá nhiều lần khi đang gõ)
            $('#locationSearch').on('input', function() {
                // Clear timeout cũ nếu có
                if (locationSearchTimeout) {
                    clearTimeout(locationSearchTimeout);
                }
                
                // Debounce: chỉ filter sau 300ms khi người dùng ngừng gõ
                locationSearchTimeout = setTimeout(function() {
                    filterLocations();
                }, 300);
            });
            
            // Bộ lọc loại
            $('#locationTypeFilter').on('change', function() {
                filterLocations();
            });
            
            // Bộ lọc tỉnh/thành phố
            $('#cityFilter').on('change', function() {
                const selectedCity = $(this).val();
                loadDistrictFilter(selectedCity);
                filterLocations();
            });
            
            // Bộ lọc quận/huyện
            $('#districtFilter').on('change', function() {
                filterLocations();
            });
            
            // Bộ lọc loại giá
            $('#priceTypeFilter').on('change', function() {
                filterLocations();
            });
            
            // Bộ lọc khoảng giá
            $('#priceRangeFilter').on('change', function() {
                filterLocations();
            });
        }
        
        // Hàm parse địa chỉ để lấy tỉnh/thành phố và quận/huyện từ một địa chỉ
        function parseAddress(address) {
            if (!address) return { city: null, district: null };
            
            const result = { city: null, district: null };
            
            // Parse tỉnh/thành phố
            const cityPatterns = [
                /TP\.?\s*HCM/i,
                /TP\.?\s*Hồ\s*Chí\s*Minh/i,
                /Hà\s*Nội/i,
                /Đà\s*Nẵng/i,
                /Cần\s*Thơ/i,
                /Hải\s*Phòng/i,
                /An\s*Giang/i,
                /Bà\s*Rịa\s*-\s*Vũng\s*Tàu/i,
                /Bắc\s*Giang/i,
                /Bắc\s*Kạn/i,
                /Bạc\s*Liêu/i,
                /Bắc\s*Ninh/i,
                /Bến\s*Tre/i,
                /Bình\s*Định/i,
                /Bình\s*Dương/i,
                /Bình\s*Phước/i,
                /Bình\s*Thuận/i,
                /Cà\s*Mau/i,
                /Cao\s*Bằng/i,
                /Đắk\s*Lắk/i,
                /Đắk\s*Nông/i,
                /Điện\s*Biên/i,
                /Đồng\s*Nai/i,
                /Đồng\s*Tháp/i,
                /Gia\s*Lai/i,
                /Hà\s*Giang/i,
                /Hà\s*Nam/i,
                /Hà\s*Tĩnh/i,
                /Hải\s*Dương/i,
                /Hậu\s*Giang/i,
                /Hòa\s*Bình/i,
                /Hưng\s*Yên/i,
                /Khánh\s*Hòa/i,
                /Kiên\s*Giang/i,
                /Kon\s*Tum/i,
                /Lai\s*Châu/i,
                /Lâm\s*Đồng/i,
                /Lạng\s*Sơn/i,
                /Lào\s*Cai/i,
                /Long\s*An/i,
                /Nam\s*Định/i,
                /Nghệ\s*An/i,
                /Ninh\s*Bình/i,
                /Ninh\s*Thuận/i,
                /Phú\s*Thọ/i,
                /Phú\s*Yên/i,
                /Quảng\s*Bình/i,
                /Quảng\s*Nam/i,
                /Quảng\s*Ngãi/i,
                /Quảng\s*Ninh/i,
                /Quảng\s*Trị/i,
                /Sóc\s*Trăng/i,
                /Sơn\s*La/i,
                /Tây\s*Ninh/i,
                /Thái\s*Bình/i,
                /Thái\s*Nguyên/i,
                /Thanh\s*Hóa/i,
                /Thừa\s*Thiên\s*Huế/i,
                /Tiền\s*Giang/i,
                /Trà\s*Vinh/i,
                /Tuyên\s*Quang/i,
                /Vĩnh\s*Long/i,
                /Vĩnh\s*Phúc/i,
                /Yên\s*Bái/i
            ];
            
            for (const pattern of cityPatterns) {
                const match = address.match(pattern);
                if (match) {
                    let city = match[0].trim();
                    if (/TP\.?\s*HCM/i.test(city) || /TP\.?\s*Hồ\s*Chí\s*Minh/i.test(city)) {
                        city = 'TP.HCM';
                    }
                    result.city = city;
                    break;
                }
            }
            
            // Parse quận/huyện
            const districtPatterns = [
                /(Quận\s+\d+)/i,
                /(Quận\s+[A-Za-zÀ-ỹ\s]+)/i,
                /(Huyện\s+[A-Za-zÀ-ỹ\s]+)/i,
                /(Thị\s+xã\s+[A-Za-zÀ-ỹ\s]+)/i,
                /(Thành\s+phố\s+[A-Za-zÀ-ỹ\s]+)/i
            ];
            
            for (const pattern of districtPatterns) {
                const match = address.match(pattern);
                if (match) {
                    const district = match[1].trim();
                    if (!district.match(/TP\.?\s*HCM|Hà\s*Nội|Đà\s*Nẵng/i)) {
                        result.district = district;
                        break;
                    }
                }
            }
            
            return result;
        }
        
        // Lọc địa điểm dựa trên tìm kiếm và bộ lọc
        function filterLocations() {
            const searchTerm = $('#locationSearch').val().toLowerCase().trim();
            const selectedType = $('#locationTypeFilter').val();
            const selectedCity = $('#cityFilter').val();
            const selectedDistrict = $('#districtFilter').val();
            const priceType = $('#priceTypeFilter').val();
            const priceRange = $('#priceRangeFilter').val();
            
            // Hàm kiểm tra location có match với filter không
            function matchesFilter(location) {
                // Bộ lọc tìm kiếm
                const matchesSearch = !searchTerm || 
                    location.TenDiaDiem.toLowerCase().includes(searchTerm) ||
                    location.DiaChi.toLowerCase().includes(searchTerm) ||
                    (location.MoTa && location.MoTa.toLowerCase().includes(searchTerm));
                
                // Bộ lọc loại
                const matchesType = !selectedType || location.LoaiDiaDiem === selectedType;
                
                // Bộ lọc tỉnh/thành phố
                let matchesCity = true;
                if (selectedCity) {
                    const addressInfo = parseAddress(location.DiaChi);
                    if (selectedCity === 'TP.HCM') {
                        matchesCity = addressInfo.city === 'TP.HCM' || 
                                     /TP\.?\s*HCM|TP\.?\s*Hồ\s*Chí\s*Minh/i.test(location.DiaChi);
                    } else {
                        matchesCity = addressInfo.city === selectedCity;
                    }
                }
                
                // Bộ lọc quận/huyện
                let matchesDistrict = true;
                if (selectedDistrict) {
                    const addressInfo = parseAddress(location.DiaChi);
                    matchesDistrict = addressInfo.district === selectedDistrict;
                }
                
                // Bộ lọc loại giá
                let matchesPriceType = true;
                if (priceType) {
                    if (priceType === 'hour') {
                        matchesPriceType = location.LoaiThue === 'Theo giờ' || location.LoaiThue === 'Cả hai';
                    } else if (priceType === 'day') {
                        matchesPriceType = location.LoaiThue === 'Theo ngày' || location.LoaiThue === 'Cả hai';
                    }
                }
                
                // Bộ lọc khoảng giá
                let matchesPrice = true;
                if (priceRange) {
                    const [minPrice, maxPrice] = priceRange.split('-').map(Number);
                    
                    // Xác định giá nào cần kiểm tra dựa trên bộ lọc loại giá
                    let locationPrice = 0;
                    if (priceType === 'hour' && location.GiaThueGio) {
                        locationPrice = parseFloat(location.GiaThueGio);
                    } else if (priceType === 'day' && location.GiaThueNgay) {
                        locationPrice = parseFloat(location.GiaThueNgay);
                    } else {
                        // Nếu không chọn loại giá cụ thể, kiểm tra cả hai giá
                        const hourlyPrice = parseFloat(location.GiaThueGio) || 0;
                        const dailyPrice = parseFloat(location.GiaThueNgay) || 0;
                        locationPrice = Math.max(hourlyPrice, dailyPrice);
                    }
                    
                    matchesPrice = locationPrice >= minPrice && locationPrice <= maxPrice;
                }
                
                return matchesSearch && matchesType && matchesCity && matchesDistrict && matchesPriceType && matchesPrice;
            }
            
            // Filter cả suggestedLocations và allLocations
            const filteredSuggested = suggestedLocations.filter(matchesFilter);
            const filteredAll = allLocations.filter(matchesFilter);
            
            // Hiển thị kết quả
            // Nếu có tìm kiếm hoặc filter, hiển thị cả 2 phần với kết quả đã filter
            // Nếu không có filter, hiển thị bình thường
            if (searchTerm || selectedType || selectedCity || selectedDistrict || priceType || priceRange) {
                // Có filter: hiển thị kết quả đã filter
                displaySuggestedLocations(filteredSuggested);
                displayAllLocations(filteredAll);
            } else {
                // Không có filter: hiển thị tất cả
                displaySuggestedLocations();
                displayAllLocations();
            }
        }
        
        // Xóa tất cả bộ lọc địa điểm
        function clearLocationFilters() {
            $('#locationSearch').val('');
            $('#locationTypeFilter').val('');
            $('#cityFilter').val('');
            $('#districtFilter').val('');
            $('#priceTypeFilter').val('');
            $('#priceRangeFilter').val('');
            loadDistrictFilter(); // Reset quận/huyện về tất cả
            displaySuggestedLocations();
            displayAllLocations();
        }
        
        // Chọn địa điểm
        function selectLocation(locationId) {
            // Tìm địa điểm từ cả danh sách đề xuất và tất cả địa điểm
            selectedLocation = suggestedLocations.find(loc => loc.ID_DD === locationId) || 
                             allLocations.find(loc => loc.ID_DD === locationId);
            
            // Cập nhật UI
            $('.suggestion-card').removeClass('selected');
            $(`.suggestion-card[data-location-id="${locationId}"]`).addClass('selected');
            
            // Nếu là địa điểm trong nhà, reset room selection
            const isIndoor = selectedLocation && (selectedLocation.LoaiDiaDiem === 'Trong nhà' || selectedLocation.LoaiDiaDiem === 'Trong nha');
            if (isIndoor) {
                // Reset rental type khi chọn địa điểm mới
                selectedLocation.selectedRoomRentalType = null;
                selectedLocation.selectedRoomId = null;
                selectedLocation.selectedRoom = null;
                
                // Ẩn thông tin phòng đã chọn
                $(`#selected-room-info-${locationId}, #selected-room-info-all-${locationId}`).hide();
                $(`#btn-select-room-${locationId}, #btn-select-room-all-${locationId}`).html('<i class="fas fa-door-open"></i> Chọn phòng');
            }
            
            // Nếu địa điểm có loại thuê "Cả hai", đảm bảo có selectedRentalType
            if (selectedLocation && selectedLocation.LoaiThue === 'Cả hai' && selectedLocation.LoaiDiaDiem !== 'Trong nhà') {
                // Chỉ đặt mặc định nếu chưa được đặt (giữ nguyên lựa chọn trước đó của người dùng)
                if (!selectedLocation.selectedRentalType) {
                    selectedLocation.selectedRentalType = 'hour'; // Chỉ đặt mặc định nếu chưa có lựa chọn trước đó
                    console.log('Set default rental type to hour for location:', selectedLocation.ID_DD);
                } else {
                    console.log('Preserving existing rental type choice:', selectedLocation.selectedRentalType, 'for location:', selectedLocation.ID_DD);
                }
                
                // Cập nhật tất cả dropdown cho địa điểm này để hiển thị giá trị đã chọn
                setTimeout(() => {
                    $(`.suggestion-card[data-location-id="${locationId}"] select`).val(selectedLocation.selectedRentalType);
                    // Cập nhật hiển thị giá
                    updateLocationPrice(locationId, selectedLocation.selectedRentalType, 'suggested');
                    updateLocationPrice(locationId, selectedLocation.selectedRentalType, 'all');
                }, 100);
            }
            
            console.log('Selected location:', selectedLocation);
            
            // Cập nhật tóm tắt đơn hàng nếu đang ở bước 3
            if (currentStep === 3) {
                updateOrderSummary();
            }
        }
        
        // Render các card phòng
        function renderRoomCards(locationId, rooms, rentalType = null) {
            const containerIds = [`room-list-container-${locationId}`, `room-list-container-all-${locationId}`];
            const selectedRoomId = selectedLocation && selectedLocation.selectedRoomId ? selectedLocation.selectedRoomId : null;
            
            if (rooms.length === 0) {
                const noRoomsHtml = `
                    <div class="no-rooms-message">
                        <i class="fas fa-door-open"></i>
                        <p class="mb-0">Không có phòng ${rentalType === 'hour' ? 'theo giờ' : rentalType === 'day' ? 'theo ngày' : ''} nào có sẵn trong khoảng thời gian đã chọn</p>
                    </div>
                `;
                containerIds.forEach(containerId => {
                    $(`#${containerId}`).html(noRoomsHtml).show();
                });
                return;
            }
            
            let html = '';
            rooms.forEach(room => {
                const giaThueGio = room.GiaThueGio ? parseFloat(room.GiaThueGio) : 0;
                const giaThueNgay = room.GiaThueNgay ? parseFloat(room.GiaThueNgay) : 0;
                const isSelected = selectedRoomId && room.ID_Phong == selectedRoomId;
                
                // Xác định loại thuê có sẵn
                const hasHourly = giaThueGio > 0 && (room.LoaiThue === 'Theo giờ' || room.LoaiThue === 'Cả hai');
                const hasDaily = giaThueNgay > 0 && (room.LoaiThue === 'Theo ngày' || room.LoaiThue === 'Cả hai');
                
                html += `
                    <div class="room-card ${isSelected ? 'selected' : ''}" 
                         onclick="selectRoomFromCard(${locationId}, ${room.ID_Phong})" 
                         data-room-id="${room.ID_Phong}">
                        <div class="room-card-header">
                            <h6 class="room-card-title">${room.TenPhong || 'Phòng không tên'}</h6>
                            ${isSelected ? '<span class="room-card-badge"><i class="fas fa-check"></i> Đã chọn</span>' : ''}
                        </div>
                        
                        <div class="room-card-info">
                            <div class="room-info-item">
                                <i class="fas fa-users"></i>
                                <span>Sức chứa: ${room.SucChua || 0} người</span>
                            </div>
                            <div class="room-info-item">
                                <i class="fas fa-info-circle"></i>
                                <span>Trạng thái: ${room.TrangThai || 'Sẵn sàng'}</span>
                            </div>
                        </div>
                        
                        ${room.MoTa ? `
                            <div class="room-card-description">
                                <i class="fas fa-quote-left"></i> ${room.MoTa}
                            </div>
                        ` : ''}
                        
                        <div class="room-price-info">
                            ${hasHourly ? `
                                <div class="room-price-item ${rentalType === 'hour' ? 'active' : ''}">
                                    <div class="room-price-label">⏰ Theo giờ</div>
                                    <div class="room-price-value">${formatCurrency(giaThueGio)}/giờ</div>
                                </div>
                            ` : ''}
                            ${hasDaily ? `
                                <div class="room-price-item ${rentalType === 'day' ? 'active' : ''}">
                                    <div class="room-price-label">📅 Theo ngày</div>
                                    <div class="room-price-value">${formatCurrency(giaThueNgay)}/ngày</div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            containerIds.forEach(containerId => {
                $(`#${containerId}`).html(html).show();
            });
        }
        
        // Chọn phòng từ card
        function selectRoomFromCard(locationId, roomId) {
            selectRoom(locationId, roomId);
            
            // Cập nhật UI - highlight card đã chọn
            $(`.room-card[data-room-id="${roomId}"]`).addClass('selected').siblings().removeClass('selected');
            $(`.room-card[data-room-id="${roomId}"] .room-card-badge`).html('<i class="fas fa-check"></i> Đã chọn');
            $(`.room-card[data-room-id="${roomId}"]`).siblings().find('.room-card-badge').remove();
        }
        
        // Tải phòng cho địa điểm trong nhà
        function loadRoomsForLocation(locationId, rentalType = null) {
            console.log('loadRoomsForLocation called for location:', locationId, 'rentalType:', rentalType);
            
            // Show loading state in room list container
            const containerIds = [`room-list-container-${locationId}`, `room-list-container-all-${locationId}`];
            containerIds.forEach(containerId => {
                $(`#${containerId}`).html(`
                    <div class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted small">Đang tải danh sách phòng...</p>
                    </div>
                `).show();
            });
            
            // Get event dates to filter available rooms
            const eventDate = $('#eventDate').val();
            const eventEndDate = $('#eventEndDate').val();
            
            $.ajax({
                url: '../src/controllers/locations.php',
                type: 'GET',
                data: { 
                    action: 'get_location', 
                    id: locationId,
                    event_date: eventDate || null,
                    event_end_date: eventEndDate || null
                },
                dataType: 'json',
                success: function(response) {
                    console.log('API Response for location:', locationId, response);
                    
                    if (response.success && response.location) {
                        const rooms = response.location.rooms || [];
                        console.log('Rooms found:', rooms.length, rooms);
                        
                        const selectedRoomId = selectedLocation && selectedLocation.selectedRoomId ? selectedLocation.selectedRoomId : '';
                        
                        if (rooms.length === 0) {
                            console.warn('No rooms found for location:', locationId);
                            renderRoomCards(locationId, [], rentalType);
                        } else {
                            // Lọc phòng theo loại thuê nếu có
                            let filteredRooms = rooms;
                            if (rentalType) {
                                filteredRooms = rooms.filter(room => {
                                    // Chuyển đổi giá thành number và kiểm tra
                                    const giaThueGio = room.GiaThueGio ? parseFloat(room.GiaThueGio) : 0;
                                    const giaThueNgay = room.GiaThueNgay ? parseFloat(room.GiaThueNgay) : 0;
                                    const loaiThue = room.LoaiThue || '';
                                    
                                    console.log(`Room ${room.ID_Phong} (${room.TenPhong}):`, {
                                        GiaThueGio: room.GiaThueGio,
                                        GiaThueNgay: room.GiaThueNgay,
                                        LoaiThue: loaiThue,
                                        giaThueGio_parsed: giaThueGio,
                                        giaThueNgay_parsed: giaThueNgay
                                    });
                                    
                                    // Kiểm tra phòng có giá phù hợp với loại thuê đã chọn
                                    if (rentalType === 'hour') {
                                        // Chỉ hiển thị phòng có giá theo giờ
                                        // Kiểm tra: GiaThueGio > 0 VÀ (LoaiThue = 'Theo giờ' HOẶC 'Cả hai')
                                        const hasHourlyPrice = giaThueGio > 0;
                                        const isHourlyOrBoth = loaiThue === 'Theo giờ' || loaiThue === 'Cả hai';
                                        const isValid = hasHourlyPrice && isHourlyOrBoth;
                                        
                                        console.log(`  → Hourly check: hasHourlyPrice=${hasHourlyPrice}, isHourlyOrBoth=${isHourlyOrBoth}, isValid=${isValid}`);
                                        return isValid;
                                    } else if (rentalType === 'day') {
                                        // Chỉ hiển thị phòng có giá theo ngày
                                        // Kiểm tra: GiaThueNgay > 0 VÀ (LoaiThue = 'Theo ngày' HOẶC 'Cả hai')
                                        const hasDailyPrice = giaThueNgay > 0;
                                        const isDailyOrBoth = loaiThue === 'Theo ngày' || loaiThue === 'Cả hai';
                                        const isValid = hasDailyPrice && isDailyOrBoth;
                                        
                                        console.log(`  → Daily check: hasDailyPrice=${hasDailyPrice}, isDailyOrBoth=${isDailyOrBoth}, isValid=${isValid}`);
                                        return isValid;
                                    }
                                    return true;
                                });
                                console.log('Filtered rooms by rental type:', rentalType, 'from', rooms.length, 'to', filteredRooms.length);
                                console.log('Filtered rooms:', filteredRooms);
                            }
                            
                            // Render các card phòng thay vì dropdown
                            renderRoomCards(locationId, filteredRooms, rentalType);
                            
                            console.log('Rooms loaded successfully:', filteredRooms.length);
                        }
                    } else {
                        // Xử lý phản hồi lỗi
                        const errorMessage = response.error || 'Không thể tải danh sách phòng';
                        const errorHtml = `
                            <div class="no-rooms-message">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                <p class="mb-0 text-danger">${errorMessage}</p>
                            </div>
                        `;
                        containerIds.forEach(containerId => {
                            $(`#${containerId}`).html(errorHtml).show();
                        });
                        console.error('Failed to load rooms - Invalid response:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('=== ERROR loading rooms ===');
                    console.error('Location ID:', locationId);
                    console.error('Rental Type:', rentalType);
                    console.error('Error:', error);
                    console.error('Status:', status);
                    console.error('Status Code:', xhr.status);
                    console.error('Response Text:', xhr.responseText);
                    
                    // Thử parse phản hồi lỗi
                    let errorMessage = 'Lỗi tải phòng';
                    try {
                        if (xhr.responseText) {
                            const errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.error) {
                                errorMessage = errorResponse.error;
                            } else if (errorResponse.message) {
                                errorMessage = errorResponse.message;
                            }
                        }
                    } catch (e) {
                        console.error('Failed to parse error response:', e);
                        // Nếu không phải JSON, kiểm tra mã trạng thái
                        if (xhr.status === 404) {
                            errorMessage = 'Không tìm thấy API';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Lỗi server';
                        } else if (xhr.status === 403) {
                            errorMessage = 'Không có quyền truy cập';
                        }
                    }
                    
                    const errorHtml = `
                        <div class="no-rooms-message">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <p class="mb-0 text-danger">${errorMessage}</p>
                        </div>
                    `;
                    containerIds.forEach(containerId => {
                        $(`#${containerId}`).html(errorHtml).show();
                    });
                    
                    // Hiển thị lỗi cho người dùng
                    const hintText = $(`#room-rental-hint-${locationId}, #room-rental-hint-all-${locationId}`);
                    hintText.html(`<i class="fas fa-exclamation-triangle text-danger"></i> <strong>Lỗi:</strong> ${errorMessage}`).show();
                }
            });
        }
        
        // Chọn phòng
        function selectRoom(locationId, roomId) {
            if (!selectedLocation || selectedLocation.ID_DD !== locationId) {
                return;
            }
            
            selectedLocation.selectedRoomId = roomId;
            selectedLocation.selectedRoom = null;
            // KHÔNG reset rental type khi chọn phòng - giữ nguyên loại thuê đã chọn trước đó
            // selectedLocation.selectedRoomRentalType = null; // REMOVED - keep rental type
            
            console.log('selectRoom called:', {locationId, roomId, selectedRoomRentalType: selectedLocation.selectedRoomRentalType});
            
            // KHÔNG ẩn dropdown loại thuê vì nó đã được hiển thị trước
            // $(`#room-rental-type-${locationId}, #room-rental-type-all-${locationId}`).hide(); // REMOVED
            
            // Ẩn giá phòng tạm thời, sẽ hiển thị lại sau khi load thông tin phòng
            $(`#room-price-display-${locationId}, #room-price-display-all-${locationId}`).hide();
            
            if (roomId) {
                // Tìm chi tiết phòng
                $.ajax({
                    url: '../src/controllers/rooms.php',
                    type: 'GET',
                    data: { action: 'get_room', id: roomId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            selectedLocation.selectedRoom = response.data;
                            const room = response.data;
                            
                            // Luôn hiển thị dropdown loại thuê nếu phòng có giá (bất kể LoaiThue)
                            console.log('Checking room rental type:', {
                                LoaiThue: room.LoaiThue,
                                GiaThueGio: room.GiaThueGio,
                                GiaThueNgay: room.GiaThueNgay,
                                hasHourly: room.GiaThueGio && room.GiaThueGio > 0,
                                hasDaily: room.GiaThueNgay && room.GiaThueNgay > 0
                            });
                            
                            const hasHourly = room.GiaThueGio && parseFloat(room.GiaThueGio) > 0;
                            const hasDaily = room.GiaThueNgay && parseFloat(room.GiaThueNgay) > 0;
                            
                            console.log('Room price check:', {
                                hasHourly: hasHourly,
                                hasDaily: hasDaily,
                                GiaThueGio: room.GiaThueGio,
                                GiaThueNgay: room.GiaThueNgay,
                                LoaiThue: room.LoaiThue
                            });
                            
                            // Cập nhật dropdown loại thuê dựa trên thông tin phòng đã chọn
                            const rentalSelects = $(`#room-rental-select-${locationId}, #room-rental-select-all-${locationId}`);
                            const hourOption = rentalSelects.find('option[value="hour"]');
                            const dayOption = rentalSelects.find('option[value="day"]');
                            
                            console.log('=== DEBUG: Updating rental type dropdown ===');
                            console.log('Room:', {
                                ID_Phong: room.ID_Phong,
                                TenPhong: room.TenPhong,
                                LoaiThue: room.LoaiThue,
                                GiaThueGio: room.GiaThueGio,
                                GiaThueNgay: room.GiaThueNgay,
                                hasHourly: hasHourly,
                                hasDaily: hasDaily
                            });
                            console.log('Current selectedRoomRentalType:', selectedLocation.selectedRoomRentalType);
                            console.log('Hour option found:', hourOption.length);
                            console.log('Day option found:', dayOption.length);
                            
                            // Cập nhật options dựa trên LoaiThue của phòng
                            // LƯU Ý: Không thể dùng .hide()/.show() trên <option>, chỉ dùng disabled
                            if (room.LoaiThue === 'Theo giờ') {
                                // Phòng chỉ hỗ trợ theo giờ
                                hourOption.prop('disabled', false);
                                dayOption.prop('disabled', true);
                                console.log('Room supports HOURLY only - disabled day option');
                                
                                // Nếu đã chọn "Theo ngày", chuyển sang "Theo giờ"
                                if (selectedLocation.selectedRoomRentalType === 'day') {
                                    selectedLocation.selectedRoomRentalType = 'hour';
                                    rentalSelects.val('hour');
                                    console.log('Switched from day to hour');
                                }
                            } else if (room.LoaiThue === 'Theo ngày') {
                                // Phòng chỉ hỗ trợ theo ngày
                                hourOption.prop('disabled', true);
                                dayOption.prop('disabled', false);
                                console.log('Room supports DAILY only - disabled hour option');
                                
                                // Nếu đã chọn "Theo giờ", chuyển sang "Theo ngày"
                                if (selectedLocation.selectedRoomRentalType === 'hour') {
                                    selectedLocation.selectedRoomRentalType = 'day';
                                    rentalSelects.val('day');
                                    console.log('Switched from hour to day');
                                }
                            } else if (room.LoaiThue === 'Cả hai') {
                                // Phòng hỗ trợ cả hai - hiển thị tất cả options
                                hourOption.prop('disabled', false);
                                dayOption.prop('disabled', false);
                                console.log('Room supports BOTH - enabled all options');
                            } else {
                                // Phòng không có LoaiThue rõ ràng - dựa vào giá có sẵn
                                if (hasHourly && hasDaily) {
                                    hourOption.prop('disabled', false);
                                    dayOption.prop('disabled', false);
                                    console.log('Room has both prices - enabled all options');
                                } else if (hasHourly) {
                                    hourOption.prop('disabled', false);
                                    dayOption.prop('disabled', true);
                                    console.log('Room has hourly price only - disabled day option');
                                    if (selectedLocation.selectedRoomRentalType === 'day') {
                                        selectedLocation.selectedRoomRentalType = 'hour';
                                        rentalSelects.val('hour');
                                        console.log('Switched from day to hour');
                                    }
                                } else if (hasDaily) {
                                    hourOption.prop('disabled', true);
                                    dayOption.prop('disabled', false);
                                    console.log('Room has daily price only - disabled hour option');
                                    if (selectedLocation.selectedRoomRentalType === 'hour') {
                                        selectedLocation.selectedRoomRentalType = 'day';
                                        rentalSelects.val('day');
                                        console.log('Switched from hour to day');
                                    }
                                }
                            }
                            
                            // Debug: Kiểm tra trạng thái sau khi cập nhật
                            console.log('After update:', {
                                hourOptionDisabled: hourOption.prop('disabled'),
                                dayOptionDisabled: dayOption.prop('disabled'),
                                selectedValue: rentalSelects.val(),
                                selectedRoomRentalType: selectedLocation.selectedRoomRentalType
                            });
                            
                            // Cập nhật hiển thị giá cho phòng
                            updateRoomPriceDisplay(locationId, room);
                            
                            // Cập nhật tóm tắt đơn hàng nếu đang ở bước 3
                            if (currentStep === 3) {
                                updateOrderSummary();
                            }
                        }
                    }
                });
            } else {
                // Xóa lựa chọn phòng - Reset dropdown loại thuê về trạng thái ban đầu
                const rentalSelects = $(`#room-rental-select-${locationId}, #room-rental-select-all-${locationId}`);
                const hourOption = rentalSelects.find('option[value="hour"]');
                const dayOption = rentalSelects.find('option[value="day"]');
                
                // Reset về trạng thái ban đầu - hiển thị tất cả options
                hourOption.prop('disabled', false);
                dayOption.prop('disabled', false);
                console.log('Reset rental type dropdown - enabled all options');
                
                // Reset giá trị nếu chưa chọn phòng
                if (!selectedLocation.selectedRoomId) {
                    selectedLocation.selectedRoomRentalType = null;
                    rentalSelects.val('');
                }
                
                $(`#room-price-display-${locationId}, #room-price-display-all-${locationId}`).hide();
                updateLocationPrice(locationId, null, 'suggested');
                updateLocationPrice(locationId, null, 'all');
                if (currentStep === 3) {
                    updateOrderSummary();
                }
            }
        }
        
        // ============================================
        // MODAL CHỌN PHÒNG - State và Functions
        // ============================================
        
        // Biến lưu trữ trạng thái modal
        let currentModalLocationId = null; // ID địa điểm đang được chọn trong modal
        let selectedRoomInModal = null; // Thông tin phòng đã chọn trong modal (chưa xác nhận)
        
        /**
         * Mở modal chọn phòng cho địa điểm trong nhà
         * @param {number} locationId - ID của địa điểm cần chọn phòng
         */
        function openRoomSelectionModal(locationId) {
            // Nếu chưa chọn địa điểm hoặc địa điểm khác, tự động chọn địa điểm này
            if (!selectedLocation || selectedLocation.ID_DD !== locationId) {
                // Tự động chọn địa điểm trước khi mở modal
                selectLocation(locationId);
                
                // Đợi một chút để đảm bảo địa điểm đã được chọn xong
                setTimeout(() => {
                    // Kiểm tra lại sau khi chọn
                    if (!selectedLocation || selectedLocation.ID_DD !== locationId) {
                        showError('Không thể chọn địa điểm. Vui lòng thử lại.');
                        return;
                    }
                    
                    // Tiếp tục mở modal
                    proceedOpenRoomModal(locationId);
                }, 100);
                return;
            }
            
            // Nếu đã chọn đúng địa điểm, mở modal ngay
            proceedOpenRoomModal(locationId);
        }
        
        /**
         * Hàm helper để mở modal chọn phòng (sau khi đã chọn địa điểm)
         */
        function proceedOpenRoomModal(locationId) {
            currentModalLocationId = locationId;
            selectedRoomInModal = null;
            
            // Reset modal
            $('#modal-room-rental-type').val('');
            $('#modal-room-list-container').html(`
                <div class="text-center py-5">
                    <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Vui lòng chọn loại thuê để xem danh sách phòng</p>
                </div>
            `);
            $('#btn-confirm-room').prop('disabled', true);
            
            // Đặt lựa chọn hiện tại nếu có
            if (selectedLocation && selectedLocation.selectedRoomRentalType) {
                $('#modal-room-rental-type').val(selectedLocation.selectedRoomRentalType);
                onModalRentalTypeChange();
            }
            
            // Hiển thị modal
            const modal = new bootstrap.Modal(document.getElementById('roomSelectionModal'));
            modal.show();
        }
        
        /**
         * Xử lý khi người dùng thay đổi loại thuê trong modal
         * Tự động load và hiển thị danh sách phòng theo loại thuê đã chọn
         */
        function onModalRentalTypeChange() {
            const rentalType = $('#modal-room-rental-type').val();
            
            if (!rentalType || !currentModalLocationId) {
                $('#modal-room-list-container').html(`
                    <div class="text-center py-5">
                        <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Vui lòng chọn loại thuê để xem danh sách phòng</p>
                    </div>
                `);
                $('#btn-confirm-room').prop('disabled', true);
                selectedRoomInModal = null;
                return;
            }
            
            // Hiển thị loading
            $('#modal-room-list-container').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Đang tải danh sách phòng...</p>
                </div>
            `);
            
            // Tải phòng
            loadRoomsForModal(currentModalLocationId, rentalType);
        }
        
        /**
         * Tải danh sách phòng cho modal dựa trên địa điểm và loại thuê
         * Chỉ hiển thị các phòng chưa được đặt trong khoảng thời gian đã chọn
         * @param {number} locationId - ID địa điểm
         * @param {string} rentalType - Loại thuê ('hour' hoặc 'day')
         */
        function loadRoomsForModal(locationId, rentalType) {
            console.log('loadRoomsForModal called:', {locationId, rentalType});
            
            const eventDate = $('#eventDate').val();
            const eventEndDate = $('#eventEndDate').val();
            
            if (!eventDate || !eventEndDate) {
                $('#modal-room-list-container').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Vui lòng chọn ngày bắt đầu và ngày kết thúc sự kiện trước
                    </div>
                `);
                return;
            }
            
            $.ajax({
                url: '../src/controllers/locations.php',
                type: 'GET',
                data: { 
                    action: 'get_location', 
                    id: locationId,
                    event_date: eventDate || null,
                    event_end_date: eventEndDate || null
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Modal API Response:', response);
                    
                    if (response.success && response.location) {
                        const rooms = response.location.rooms || [];
                        console.log('Rooms found:', rooms.length);
                        
                        // Lọc phòng theo loại thuê
                        let filteredRooms = rooms;
                        if (rentalType) {
                            filteredRooms = rooms.filter(room => {
                                const giaThueGio = room.GiaThueGio ? parseFloat(room.GiaThueGio) : 0;
                                const giaThueNgay = room.GiaThueNgay ? parseFloat(room.GiaThueNgay) : 0;
                                const loaiThue = room.LoaiThue || '';
                                
                                if (rentalType === 'hour') {
                                    const hasHourlyPrice = giaThueGio > 0;
                                    const isHourlyOrBoth = loaiThue === 'Theo giờ' || loaiThue === 'Cả hai';
                                    return hasHourlyPrice && isHourlyOrBoth;
                                } else if (rentalType === 'day') {
                                    const hasDailyPrice = giaThueNgay > 0;
                                    const isDailyOrBoth = loaiThue === 'Theo ngày' || loaiThue === 'Cả hai';
                                    return hasDailyPrice && isDailyOrBoth;
                                }
                                return true;
                            });
                        }
                        
                        // Render phòng trong modal
                        renderRoomsInModal(filteredRooms, rentalType);
                    } else {
                        $('#modal-room-list-container').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                ${response.error || 'Không thể tải danh sách phòng'}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading rooms for modal:', error);
                    $('#modal-room-list-container').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Lỗi khi tải danh sách phòng. Vui lòng thử lại.
                        </div>
                    `);
                }
            });
        }
        
        /**
         * Render danh sách phòng dưới dạng cards trong modal
         * @param {Array} rooms - Mảng các phòng cần hiển thị
         * @param {string} rentalType - Loại thuê đã chọn ('hour' hoặc 'day')
         */
        function renderRoomsInModal(rooms, rentalType) {
            const container = $('#modal-room-list-container');
            
            if (rooms.length === 0) {
                container.html(`
                    <div class="no-rooms-message">
                        <i class="fas fa-door-open"></i>
                        <p class="mb-0">Không có phòng ${rentalType === 'hour' ? 'theo giờ' : 'theo ngày'} nào có sẵn trong khoảng thời gian đã chọn</p>
                    </div>
                `);
                $('#btn-confirm-room').prop('disabled', true);
                return;
            }
            
            let html = '<div class="row g-3">';
            rooms.forEach(room => {
                const giaThueGio = room.GiaThueGio ? parseFloat(room.GiaThueGio) : 0;
                const giaThueNgay = room.GiaThueNgay ? parseFloat(room.GiaThueNgay) : 0;
                const price = rentalType === 'hour' ? giaThueGio : giaThueNgay;
                const priceText = rentalType === 'hour' ? 'giờ' : 'ngày';
                
                // Escape HTML để tránh lỗi với ký tự đặc biệt
                const roomName = (room.TenPhong || 'Phòng không tên').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                const roomMoTa = room.MoTa ? room.MoTa.replace(/'/g, "\\'").replace(/"/g, '&quot;') : '';
                const roomTrangThai = (room.TrangThai || 'Sẵn sàng').replace(/'/g, "\\'");
                
                html += `
                    <div class="col-md-6">
                        <div class="room-card" 
                             onclick="selectRoomInModal(${room.ID_Phong}, '${roomName.replace(/'/g, "\\'")}', ${price}, '${rentalType}')"
                             data-room-id="${room.ID_Phong}">
                            <div class="room-card-header">
                                <h6 class="room-card-title">${room.TenPhong || 'Phòng không tên'}</h6>
                            </div>
                            <div class="room-card-info">
                                <div class="room-info-item">
                                    <i class="fas fa-users"></i>
                                    <span>Sức chứa: ${room.SucChua || 0} người</span>
                                </div>
                                <div class="room-info-item">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Trạng thái: ${room.TrangThai || 'Sẵn sàng'}</span>
                                </div>
                            </div>
                            ${room.MoTa ? `
                                <div class="room-card-description">
                                    <i class="fas fa-quote-left"></i> ${room.MoTa}
                                </div>
                            ` : ''}
                            <div class="room-price-info mt-3">
                                <div class="room-price-item active">
                                    <div class="room-price-label">${rentalType === 'hour' ? '⏰' : '📅'} ${rentalType === 'hour' ? 'Theo giờ' : 'Theo ngày'}</div>
                                    <div class="room-price-value">${formatCurrency(price)}/${priceText}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.html(html);
        }
        
        /**
         * Xử lý khi người dùng click chọn một phòng trong modal
         * Highlight card đã chọn và enable nút xác nhận
         * @param {number} roomId - ID phòng
         * @param {string} roomName - Tên phòng
         * @param {number} price - Giá phòng
         * @param {string} rentalType - Loại thuê
         */
        function selectRoomInModal(roomId, roomName, price, rentalType) {
            // Xác thực đầu vào
            if (!roomId || !roomName || !rentalType) {
                console.error('Invalid room selection:', {roomId, roomName, price, rentalType});
                showError('Lỗi khi chọn phòng. Vui lòng thử lại.');
                return;
            }
            
            selectedRoomInModal = {
                ID_Phong: parseInt(roomId),
                TenPhong: String(roomName),
                price: parseFloat(price) || 0,
                rentalType: String(rentalType)
            };
            
            // Cập nhật UI - highlight card đã chọn
            $('.room-card').removeClass('selected');
            $(`.room-card[data-room-id="${roomId}"]`).addClass('selected');
            
            // Thêm badge vào card đã chọn
            $(`.room-card[data-room-id="${roomId}"] .room-card-header`).append('<span class="room-card-badge"><i class="fas fa-check"></i> Đã chọn</span>');
            $(`.room-card[data-room-id="${roomId}"]`).siblings().find('.room-card-badge').remove();
            
            // Kích hoạt nút xác nhận
            $('#btn-confirm-room').prop('disabled', false);
            
            console.log('Room selected in modal:', selectedRoomInModal);
        }
        
        /**
         * Xác nhận chọn phòng - Lưu thông tin phòng đã chọn và đóng modal
         * Tải đầy đủ thông tin phòng từ server và cập nhật UI
         */
        function confirmRoomSelection() {
            if (!selectedRoomInModal || !currentModalLocationId) {
                showError('Vui lòng chọn phòng');
                return;
            }
            
            if (!selectedLocation || selectedLocation.ID_DD !== currentModalLocationId) {
                showError('Lỗi: Địa điểm không hợp lệ');
                return;
            }
            
            // Đặt phòng đã chọn
            selectedLocation.selectedRoomId = selectedRoomInModal.ID_Phong;
            selectedLocation.selectedRoomRentalType = selectedRoomInModal.rentalType;
            
            // Vô hiệu hóa nút xác nhận và hiển thị loading
            $('#btn-confirm-room').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
            
            // Tải đầy đủ thông tin phòng từ server
            $.ajax({
                url: '../src/controllers/rooms.php',
                type: 'GET',
                data: { action: 'get_room', id: selectedRoomInModal.ID_Phong },
                dataType: 'json',
                success: function(response) {
                    // Khôi phục nút xác nhận
                    $('#btn-confirm-room').prop('disabled', false).html('<i class="fas fa-check"></i> Xác nhận');
                    
                    if (response.success && response.data) {
                        // Lưu thông tin phòng đầy đủ vào selectedLocation
                        selectedLocation.selectedRoom = response.data;
                        
                        // Cập nhật UI hiển thị thông tin phòng đã chọn
                        updateSelectedRoomDisplay(currentModalLocationId);
                        
                        // Đóng modal
                        const modalElement = document.getElementById('roomSelectionModal');
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Cập nhật tóm tắt đơn hàng nếu đang ở bước 3
                        if (currentStep === 3) {
                            updateOrderSummary();
                        }
                        
                        // Hiển thị thông báo thành công
                        showSuccess('Đã chọn phòng thành công!');
                    } else {
                        // Xử lý lỗi khi không tải được thông tin phòng
                        showError('Không thể tải thông tin phòng. Vui lòng thử lại.');
                        console.error('Failed to load room details:', response);
                    }
                },
                error: function(xhr, status, error) {
                    // Khôi phục nút xác nhận khi có lỗi
                    $('#btn-confirm-room').prop('disabled', false).html('<i class="fas fa-check"></i> Xác nhận');
                    
                    // Hiển thị thông báo lỗi
                    showError('Lỗi khi tải thông tin phòng. Vui lòng thử lại.');
                    console.error('Error loading room details:', error);
                    console.error('Response:', xhr.responseText);
                }
            });
        }
        
        /**
         * Cập nhật hiển thị thông tin phòng đã chọn bên ngoài modal
         * Hiển thị tên phòng, sức chứa và giá
         * @param {number} locationId - ID địa điểm
         */
        function updateSelectedRoomDisplay(locationId) {
            if (!selectedLocation.selectedRoom) return;
            
            const room = selectedLocation.selectedRoom;
            const rentalType = selectedLocation.selectedRoomRentalType;
            const price = rentalType === 'hour' ? room.GiaThueGio : room.GiaThueNgay;
            const priceText = rentalType === 'hour' ? 'giờ' : 'ngày';
            
            // Cập nhật hiển thị thông tin
            $(`#selected-room-name-${locationId}, #selected-room-name-all-${locationId}`).text(room.TenPhong);
            $(`#selected-room-details-${locationId}, #selected-room-details-all-${locationId}`).html(`
                Sức chứa: ${room.SucChua || 0} người | 
                Giá: ${formatCurrency(price)}/${priceText}
            `);
            
            // Hiển thị thông tin, cập nhật text nút
            $(`#selected-room-info-${locationId}, #selected-room-info-all-${locationId}`).show();
            $(`#btn-select-room-${locationId}, #btn-select-room-all-${locationId}`).html('<i class="fas fa-edit"></i> Thay đổi phòng');
        }
        
        // DEPRECATED: selectRoomRentalTypeFirst - Không còn sử dụng, đã được thay thế bằng modal
        // Hàm này được giữ lại để tương thích ngược nhưng không nên được gọi
        function selectRoomRentalTypeFirst(locationId, rentalType) {
            console.warn('selectRoomRentalTypeFirst is deprecated. Use openRoomSelectionModal instead.');
            // Chuyển hướng đến modal
            openRoomSelectionModal(locationId);
        }
        
        // Chọn loại thuê phòng (sau khi đã chọn phòng - để thay đổi)
        function selectRoomRentalType(locationId, rentalType) {
            if (!selectedLocation || selectedLocation.ID_DD !== locationId) {
                console.warn('selectRoomRentalType: Invalid location or not selected', {selectedLocation, locationId});
                return;
            }
            
            const oldRentalType = selectedLocation.selectedRoomRentalType;
            selectedLocation.selectedRoomRentalType = rentalType;
            
            console.log('=== selectRoomRentalType ===');
            console.log('Location ID:', locationId);
            console.log('Rental type changed from', oldRentalType, 'to', rentalType);
            console.log('Selected room:', selectedLocation.selectedRoom);
            console.log('Room GiaThueGio:', selectedLocation.selectedRoom?.GiaThueGio);
            console.log('Room GiaThueNgay:', selectedLocation.selectedRoom?.GiaThueNgay);
            
            // Update all dropdowns for this location
            $(`#room-rental-select-${locationId}, #room-rental-select-all-${locationId}`).val(rentalType);
            console.log('Updated dropdown values to:', rentalType);
            
            // Update price display immediately for room with animation
            if (selectedLocation && selectedLocation.selectedRoom) {
                console.log('Updating price display for room...');
                // Thêm hiệu ứng fade khi thay đổi giá
                const priceDisplay = $(`#room-price-display-${locationId}, #room-price-display-all-${locationId}`);
                const priceTextEl = $(`#room-price-text-${locationId}, #room-price-text-all-${locationId}`);
                
                if (priceDisplay.is(':visible')) {
                    console.log('Price display is visible, using fade animation');
                    // Fade out text first
                    priceTextEl.fadeOut(100, function() {
                        // Update price display
                        updateRoomPriceDisplay(locationId, selectedLocation.selectedRoom);
                        // Fade in new text
                        priceTextEl.fadeIn(200);
                    });
                } else {
                    console.log('Price display not visible, showing directly');
                    updateRoomPriceDisplay(locationId, selectedLocation.selectedRoom);
                }
            } else {
                console.warn('No selected room found, cannot update price display');
            }
            
            // Update order summary if on step 3
            if (currentStep === 3) {
                console.log('Updating order summary...');
                updateOrderSummary();
            }
        }
        
        // Update room price display
        function updateRoomPriceDisplay(locationId, room) {
            if (!room || !selectedLocation || selectedLocation.ID_DD !== locationId) {
                console.log('updateRoomPriceDisplay: Invalid parameters', {room, selectedLocation, locationId});
                return;
            }
            
            let priceText = 'Chưa có giá';
            // Xác định loại thuê: ưu tiên selectedRoomRentalType, nếu không có thì dựa vào giá có sẵn
            let rentalType = selectedLocation.selectedRoomRentalType;
            
            console.log('updateRoomPriceDisplay - Current state:', {
                rentalType: rentalType,
                roomLoaiThue: room.LoaiThue,
                GiaThueGio: room.GiaThueGio,
                GiaThueNgay: room.GiaThueNgay,
                hasHourly: room.GiaThueGio && room.GiaThueGio > 0,
                hasDaily: room.GiaThueNgay && room.GiaThueNgay > 0
            });
            
            if (!rentalType) {
                // Mặc định: nếu có giá giờ thì chọn giờ, nếu không có giá giờ nhưng có giá ngày thì chọn ngày
                if (room.GiaThueGio && room.GiaThueGio > 0) {
                    rentalType = 'hour';
                    selectedLocation.selectedRoomRentalType = 'hour';
                } else if (room.GiaThueNgay && room.GiaThueNgay > 0) {
                    rentalType = 'day';
                    selectedLocation.selectedRoomRentalType = 'day';
                }
            }
            
            // Xử lý theo LoaiThue của phòng
            if (room.LoaiThue === 'Theo giờ') {
                // Phòng chỉ có giá theo giờ
                if (room.GiaThueGio && room.GiaThueGio > 0) {
                    priceText = `${new Intl.NumberFormat('vi-VN').format(room.GiaThueGio)} VNĐ/giờ`;
                }
            } else if (room.LoaiThue === 'Theo ngày') {
                // Phòng chỉ có giá theo ngày
                if (room.GiaThueNgay && room.GiaThueNgay > 0) {
                    priceText = `${new Intl.NumberFormat('vi-VN').format(room.GiaThueNgay)} VNĐ/ngày`;
                }
            } else if (room.LoaiThue === 'Cả hai') {
                // Phòng có cả hai loại giá - hiển thị theo loại đã chọn
                console.log('Room has "Cả hai" - rentalType:', rentalType);
                
                if (rentalType === 'hour') {
                    // Người dùng chọn "Theo giờ"
                    if (room.GiaThueGio && room.GiaThueGio > 0) {
                        priceText = `${new Intl.NumberFormat('vi-VN').format(room.GiaThueGio)} VNĐ/giờ`;
                        console.log('Displaying hourly price:', priceText);
                    } else {
                        console.warn('Hourly price not available but rentalType is hour');
                        // Fallback: nếu không có giá giờ nhưng có giá ngày, hiển thị giá ngày
                        if (room.GiaThueNgay && room.GiaThueNgay > 0) {
                            priceText = `${new Intl.NumberFormat('vi-VN').format(room.GiaThueNgay)} VNĐ/ngày`;
                            selectedLocation.selectedRoomRentalType = 'day';
                            rentalType = 'day';
                        }
                    }
                } else if (rentalType === 'day') {
                    // Người dùng chọn "Theo ngày"
                    if (room.GiaThueNgay && room.GiaThueNgay > 0) {
                        priceText = `${new Intl.NumberFormat('vi-VN').format(room.GiaThueNgay)} VNĐ/ngày`;
                        console.log('Displaying daily price:', priceText);
                    } else {
                        console.warn('Daily price not available but rentalType is day');
                        // Fallback: nếu không có giá ngày nhưng có giá giờ, hiển thị giá giờ
                        if (room.GiaThueGio && room.GiaThueGio > 0) {
                            priceText = `${new Intl.NumberFormat('vi-VN').format(room.GiaThueGio)} VNĐ/giờ`;
                            selectedLocation.selectedRoomRentalType = 'hour';
                            rentalType = 'hour';
                        }
                    }
                } else {
                    // Chưa chọn loại thuê - hiển thị cả hai hoặc giá có sẵn
                    if (room.GiaThueGio && room.GiaThueGio > 0 && room.GiaThueNgay && room.GiaThueNgay > 0) {
                        // Có cả hai giá nhưng chưa chọn - hiển thị cả hai
                        priceText = `${new Intl.NumberFormat('vi-VN').format(room.GiaThueGio)} VNĐ/giờ hoặc ${new Intl.NumberFormat('vi-VN').format(room.GiaThueNgay)} VNĐ/ngày`;
                    } else if (room.GiaThueGio && room.GiaThueGio > 0) {
                        priceText = `${new Intl.NumberFormat('vi-VN').format(room.GiaThueGio)} VNĐ/giờ`;
                    } else if (room.GiaThueNgay && room.GiaThueNgay > 0) {
                        priceText = `${new Intl.NumberFormat('vi-VN').format(room.GiaThueNgay)} VNĐ/ngày`;
                    }
                }
            }
            
            // Update price display elements (though they're hidden for indoor locations)
            $(`#price-suggested-${locationId}, #price-all-${locationId}`).text(priceText);
            
            // Update room price display card với animation
            const priceDisplay = $(`#room-price-display-${locationId}, #room-price-display-all-${locationId}`);
            const priceTextEl = $(`#room-price-text-${locationId}, #room-price-text-all-${locationId}`);
            
            if (priceText !== 'Chưa có giá') {
                // Cập nhật text với animation
                priceTextEl.fadeOut(100, function() {
                    $(this).text(priceText).fadeIn(200);
                });
                
                // Hiển thị card giá nếu chưa hiển thị
                if (!priceDisplay.is(':visible')) {
                    priceDisplay.fadeIn(300);
                }
                
                // Thêm class để highlight giá mới
                priceDisplay.addClass('price-updated');
                setTimeout(() => {
                    priceDisplay.removeClass('price-updated');
                }, 500);
            } else {
                priceDisplay.fadeOut(300);
            }
            
            console.log('Updated room price display:', priceText, 'for rental type:', rentalType, 'room:', room);
        }
        
        // Format currency helper
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount) + ' VNĐ';
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
                    
                    // Check availability for already selected equipment
                    setTimeout(() => {
                        selectedEquipment.forEach(eq => {
                            checkEquipmentAvailability(eq.ID_TB);
                        });
                    }, 500);
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
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải combo thiết bị...</p>
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
            
            // Lọc chỉ hiển thị các combo đủ thiết bị (available !== false)
            // Nếu available chưa được check (undefined/null), vẫn hiển thị để check
            const availableCombos = comboSuggestions.filter(combo => {
                // Nếu đã check và available === false thì ẩn đi
                // Nếu chưa check (undefined/null) hoặc available === true thì hiển thị
                return combo.available !== false;
            });
            
            if (availableCombos.length === 0) {
                $('#comboSuggestions').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Không có combo thiết bị nào đủ thiết bị trong khoảng thời gian đã chọn.
                    </div>
                `);
                return;
            }
            
            let html = '<div class="row g-3">';
            availableCombos.forEach(combo => {
                const price = new Intl.NumberFormat('vi-VN').format(combo.GiaCombo);
                // Kiểm tra cả pending và confirmed
                const isSelected = pendingComboSelections.some(c => c.ID_Combo === combo.ID_Combo) || 
                                 selectedCombos.some(c => c.ID_Combo === combo.ID_Combo);
                
                // Combo đã được filter, chỉ hiển thị combo đủ thiết bị
                html += `
                    <div class="col-md-6">
                        <div class="combo-card ${isSelected ? 'selected' : ''}" 
                             onclick="selectCombo(${combo.ID_Combo})"
                             data-combo-id="${combo.ID_Combo}">
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
                                    ${combo.equipment ? combo.equipment.map(item => `
                                        <div class="equipment-item-combo">
                                            <span class="equipment-name">${item.TenThietBi}</span>
                                            <span class="equipment-quantity">x${item.SoLuong}</span>
                                        </div>
                                    `).join('') : ''}
                                </div>
                            </div>
                            ${combo.availabilityInfo ? `
                                <div class="combo-availability-info mt-2">
                                    ${combo.availabilityInfo}
                                </div>
                            ` : ''}
                            <div class="combo-footer">
                                <button class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); selectCombo(${combo.ID_Combo})">
                                    <i class="fas fa-check"></i> Chọn combo này
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            $('#comboSuggestions').html(html);
            
            // Check availability for all combos (chỉ khi không đang check)
            if (!isCheckingComboAvailability) {
                checkAllComboAvailability();
            }
        }
        
        // Hiển thị combo suggestions mà không check availability (để tránh vòng lặp)
        function displayComboSuggestionsWithoutCheck() {
            if (comboSuggestions.length === 0) {
                $('#comboSuggestions').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Không có combo thiết bị nào có sẵn.
                    </div>
                `);
                return;
            }
            
            // Lọc chỉ hiển thị các combo đủ thiết bị (available !== false)
            // Nếu available chưa được check (undefined/null), vẫn hiển thị để check
            const availableCombos = comboSuggestions.filter(combo => {
                // Nếu đã check và available === false thì ẩn đi
                // Nếu chưa check (undefined/null) hoặc available === true thì hiển thị
                return combo.available !== false;
            });
            
            if (availableCombos.length === 0) {
                $('#comboSuggestions').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Không có combo thiết bị nào đủ thiết bị trong khoảng thời gian đã chọn.
                    </div>
                `);
                return;
            }
            
            let html = '<div class="row g-3">';
            availableCombos.forEach(combo => {
                const price = new Intl.NumberFormat('vi-VN').format(combo.GiaCombo);
                // Kiểm tra cả pending và confirmed
                const isSelected = pendingComboSelections.some(c => c.ID_Combo === combo.ID_Combo) || 
                                 selectedCombos.some(c => c.ID_Combo === combo.ID_Combo);
                
                // Combo đã được filter, chỉ hiển thị combo đủ thiết bị
                html += `
                    <div class="col-md-6">
                        <div class="combo-card ${isSelected ? 'selected' : ''}" 
                             onclick="selectCombo(${combo.ID_Combo})"
                             data-combo-id="${combo.ID_Combo}">
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
                                    ${combo.equipment ? combo.equipment.map(item => `
                                        <div class="equipment-item-combo">
                                            <span class="equipment-name">${item.TenThietBi}</span>
                                            <span class="equipment-quantity">x${item.SoLuong}</span>
                                        </div>
                                    `).join('') : ''}
                                </div>
                            </div>
                            ${combo.availabilityInfo ? `
                                <div class="combo-availability-info mt-2">
                                    ${combo.availabilityInfo}
                                </div>
                            ` : ''}
                            <div class="combo-footer">
                                <button class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); selectCombo(${combo.ID_Combo})">
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
        
        // Biến để tránh gọi checkAllComboAvailability() liên tục
        let isCheckingComboAvailability = false;
        let comboAvailabilityCheckTimeout = null;
        
        // Check availability for all combos
        function checkAllComboAvailability() {
            // Nếu đang check, bỏ qua
            if (isCheckingComboAvailability) {
                console.log('checkAllComboAvailability: Already checking, skipping...');
                return;
            }
            
            const eventDate = $('#eventDate').val();
            const eventTime = $('#eventTime').val();
            const eventEndDate = $('#eventEndDate').val();
            const eventEndTime = $('#eventEndTime').val();
            
            if (!eventDate || !eventEndDate) {
                console.log('checkAllComboAvailability: Missing event dates');
                return;
            }
            
            if (!comboSuggestions || comboSuggestions.length === 0) {
                console.log('checkAllComboAvailability: No combos to check');
                return;
            }
            
            // Đánh dấu đang check
            isCheckingComboAvailability = true;
            
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            
            console.log('checkAllComboAvailability: Checking', comboSuggestions.length, 'combos');
            
            // Đếm số lượng requests đã hoàn thành để chỉ re-render một lần
            let completedRequests = 0;
            const totalRequests = comboSuggestions.length;
            let renderTimeout = null;
            
            // Hàm để re-render sau khi delay (debounce) - KHÔNG gọi checkAllComboAvailability() lại
            const scheduleRender = function() {
                if (renderTimeout) {
                    clearTimeout(renderTimeout);
                }
                renderTimeout = setTimeout(function() {
                    // Chỉ re-render, KHÔNG gọi checkAllComboAvailability() để tránh vòng lặp
                    displayComboSuggestionsWithoutCheck();
                    // Reset flag sau khi render xong
                    isCheckingComboAvailability = false;
                }, 100); // Delay 100ms để tránh re-render quá nhiều
            };
            
            comboSuggestions.forEach(combo => {
                if (!combo || !combo.ID_Combo) {
                    console.warn('checkAllComboAvailability: Invalid combo:', combo);
                    completedRequests++;
                    if (completedRequests === totalRequests) {
                        scheduleRender();
                    }
                    return;
                }
                
                const requestData = {
                    action: 'check_combo_availability',
                    combo_id: combo.ID_Combo,
                    start_date: eventDate,
                    start_time: eventTime || '00:00',
                    end_date: eventEndDate,
                    end_time: eventEndTime || '23:59',
                    event_id: editId || null
                };
                
                console.log('Checking combo availability:', requestData);
                
                $.ajax({
                    url: '../src/controllers/event-register.php',
                    type: 'GET',
                    data: requestData,
                    dataType: 'json',
                    timeout: 10000, // 10 seconds timeout
                    success: function(data) {
                        completedRequests++;
                        console.log('Combo availability response for combo', combo.ID_Combo, ':', data);
                        
                        if (data && data.success !== undefined) {
                            combo.available = data.available;
                            combo.availabilityData = data.equipment;
                            
                            // Tạo thông báo availability
                            if (!data.available) {
                                const unavailableItems = (data.equipment || []).filter(eq => !eq.sufficient);
                                let infoHtml = '<div class="alert alert-warning alert-sm mb-0">';
                                infoHtml += '<small><i class="fas fa-exclamation-triangle"></i> <strong>Thiếu thiết bị:</strong><br>';
                                if (unavailableItems.length > 0) {
                                    unavailableItems.forEach(item => {
                                        const equipment = (combo.equipment || []).find(eq => eq.ID_TB === item.equipment_id);
                                        const equipmentName = equipment ? equipment.TenThietBi : `ID: ${item.equipment_id}`;
                                        infoHtml += `• ${equipmentName}: Cần ${item.required}, còn ${item.available}/${item.total}<br>`;
                                    });
                                } else {
                                    infoHtml += 'Không đủ thiết bị trong khoảng thời gian đã chọn<br>';
                                }
                                infoHtml += '</small></div>';
                                combo.availabilityInfo = infoHtml;
                            } else {
                                combo.availabilityInfo = '<div class="alert alert-success alert-sm mb-0"><small><i class="fas fa-check-circle"></i> Đủ thiết bị</small></div>';
                            }
                        } else {
                            console.error('Invalid response format for combo', combo.ID_Combo, ':', data);
                            combo.available = false;
                            combo.availabilityInfo = '<div class="alert alert-danger alert-sm mb-0"><small><i class="fas fa-exclamation-circle"></i> Lỗi kiểm tra</small></div>';
                        }
                        
                        // Schedule re-render sau khi tất cả requests hoàn thành
                        if (completedRequests === totalRequests) {
                            scheduleRender();
                        }
                        // KHÔNG gọi scheduleRender() cho từng request để tránh re-render quá nhiều
                    },
                    error: function(xhr, status, error) {
                        completedRequests++;
                        console.error('Failed to check combo availability for combo:', combo.ID_Combo);
                        console.error('Status:', status);
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText);
                        console.error('Status Code:', xhr.status);
                        
                        // Set combo as unavailable on error
                        combo.available = false;
                        combo.availabilityInfo = '<div class="alert alert-danger alert-sm mb-0"><small><i class="fas fa-exclamation-circle"></i> Lỗi kết nối khi kiểm tra</small></div>';
                        
                        // Schedule re-render chỉ khi tất cả requests hoàn thành
                        if (completedRequests === totalRequests) {
                            scheduleRender();
                        }
                    }
                });
            });
        }
        
        /**
         * Chọn combo - Kiểm tra số lượng thiết bị trước khi cho phép chọn
         * Người dùng có thể chọn nhiều combo, nhưng phải kiểm tra availability trước
         * @param {number} comboId - ID của combo cần chọn
         */
        function selectCombo(comboId) {
            const combo = comboSuggestions.find(c => c.ID_Combo === comboId);
            
            if (!combo) {
                showError('Không tìm thấy combo này');
                return;
            }
            
            // Kiểm tra xem combo đã được chọn chưa (trong pending hoặc confirmed)
            const isPending = pendingComboSelections.some(c => c.ID_Combo === comboId);
            const isConfirmed = selectedCombos.some(c => c.ID_Combo === comboId);
            
            if (isPending || isConfirmed) {
                // Nếu đã chọn, bỏ chọn
                console.log('🔄 Bỏ chọn combo:', comboId, 'isPending:', isPending, 'isConfirmed:', isConfirmed);
                
                // Xóa khỏi pending và confirmed
                const beforePendingCount = pendingComboSelections.length;
                const beforeConfirmedCount = selectedCombos.length;
                
                pendingComboSelections = pendingComboSelections.filter(c => c.ID_Combo !== comboId);
                selectedCombos = selectedCombos.filter(c => c.ID_Combo !== comboId);
                
                const afterPendingCount = pendingComboSelections.length;
                const afterConfirmedCount = selectedCombos.length;
                
                console.log('📊 Trước khi bỏ chọn - Pending:', beforePendingCount, 'Confirmed:', beforeConfirmedCount);
                console.log('📊 Sau khi bỏ chọn - Pending:', afterPendingCount, 'Confirmed:', afterConfirmedCount);
                
                // Đảm bảo combo đã được xóa hoàn toàn
                if (pendingComboSelections.some(c => c.ID_Combo === comboId) || selectedCombos.some(c => c.ID_Combo === comboId)) {
                    console.error('❌ LỖI: Combo vẫn còn trong danh sách sau khi bỏ chọn!');
                    // Force remove
                    pendingComboSelections = pendingComboSelections.filter(c => c.ID_Combo !== comboId);
                    selectedCombos = selectedCombos.filter(c => c.ID_Combo !== comboId);
                }
                
                $(`.combo-card[data-combo-id="${comboId}"]`).removeClass('selected');
                
                // Reset availability info
                const combo = comboSuggestions.find(c => c.ID_Combo === comboId);
                if (combo) {
                    if (combo.available === true) {
                        combo.availabilityInfo = '<div class="alert alert-success alert-sm mb-0"><small><i class="fas fa-check-circle"></i> Đủ thiết bị</small></div>';
                    }
                }
                
                // Re-render combo card để cập nhật UI (không check availability để tránh vòng lặp)
                displayComboSuggestionsWithoutCheck();
                
                // Cập nhật tóm tắt đơn hàng
                updateOrderSummary();
                
                showSuccess(`Đã bỏ chọn combo "${combo ? combo.TenCombo : comboId}". Thay đổi chưa được lưu, nhấn "Đăng ký sự kiện" để xác nhận.`);
                
                console.log('✅ Đã bỏ chọn combo thành công');
                return;
            }
            
            // Kiểm tra availability trước khi cho phép chọn
            const eventDate = $('#eventDate').val();
            const eventTime = $('#eventTime').val();
            const eventEndDate = $('#eventEndDate').val();
            const eventEndTime = $('#eventEndTime').val();
            
            if (!eventDate || !eventEndDate) {
                showError('Vui lòng chọn ngày bắt đầu và ngày kết thúc sự kiện trước');
                return;
            }
            
            // Hiển thị loading trên card
            const card = $(`.combo-card[data-combo-id="${comboId}"]`);
            const originalFooter = card.find('.combo-footer').html(); // Lưu nội dung footer gốc
            card.find('.combo-footer').html('<div class="text-center"><div class="spinner-border spinner-border-sm text-primary"></div> <small>Đang kiểm tra...</small></div>');
            
            // Kiểm tra availability ngay lập tức
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            
            $.ajax({
                url: '../src/controllers/event-register.php',
                type: 'GET',
                data: {
                    action: 'check_combo_availability',
                    combo_id: comboId,
                    start_date: eventDate,
                    start_time: eventTime || '00:00',
                    end_date: eventEndDate,
                    end_time: eventEndTime || '23:59',
                    event_id: editId || null
                },
                dataType: 'json',
                timeout: 10000,
                success: function(data) {
                    // Khôi phục nội dung footer
                    card.find('.combo-footer').html(originalFooter);
                    
                    if (data && data.success !== undefined) {
                        if (data.available) {
                            // Combo có đủ thiết bị - chỉ highlight, KHÔNG tự động lưu vào selectedCombos
                            // Thêm vào pendingComboSelections để hiển thị trong tóm tắt tạm thời
                            if (!pendingComboSelections.some(c => c.ID_Combo === comboId)) {
                                pendingComboSelections.push(combo);
                                console.log('✅ Đã thêm combo vào pendingComboSelections:', comboId, combo.TenCombo);
                                console.log('📊 Trạng thái hiện tại - Pending:', pendingComboSelections.length, 'Confirmed:', selectedCombos.length);
                            } else {
                                console.log('⚠️ Combo đã có trong pendingComboSelections:', comboId);
                            }
                            
                            // Đảm bảo KHÔNG có trong selectedCombos (nếu có thì xóa)
                            if (selectedCombos.some(c => c.ID_Combo === comboId)) {
                                selectedCombos = selectedCombos.filter(c => c.ID_Combo !== comboId);
                                console.log('⚠️ Đã xóa combo khỏi selectedCombos (chỉ nên ở pending):', comboId);
                            }
                            
                            card.addClass('selected');
                            
                            // Cập nhật trạng thái combo
                            combo.available = true;
                            combo.availabilityInfo = '<div class="alert alert-info alert-sm mb-0"><small><i class="fas fa-info-circle"></i> Đã chọn (chưa xác nhận)</small></div>';
                            
                            // Hiển thị thông báo
                            showSuccess(`Đã chọn combo "${combo.TenCombo}". Bạn có thể tiếp tục chọn thêm combo hoặc thiết bị khác. Nhấn "Đăng ký sự kiện" để xác nhận.`);
                            
                            // Cập nhật tóm tắt đơn hàng (hiển thị cả pending và confirmed)
                            updateOrderSummary();
                        } else {
                            // Combo không đủ thiết bị - không cho chọn
                            const unavailableItems = (data.equipment || []).filter(eq => !eq.sufficient);
                            let errorMessage = 'Combo này không đủ thiết bị trong khoảng thời gian đã chọn.\n\n';
                            
                            if (unavailableItems.length > 0) {
                                errorMessage += 'Thiết bị thiếu:\n';
                                unavailableItems.forEach(item => {
                                    const equipment = (combo.equipment || []).find(eq => eq.ID_TB === item.equipment_id);
                                    const equipmentName = equipment ? equipment.TenThietBi : `ID: ${item.equipment_id}`;
                                    errorMessage += `• ${equipmentName}: Cần ${item.required}, còn ${item.available}/${item.total}\n`;
                                });
                            }
                            
                            showError(errorMessage);
                            
                            // Cập nhật trạng thái combo
                            combo.available = false;
                            let infoHtml = '<div class="alert alert-warning alert-sm mb-0">';
                            infoHtml += '<small><i class="fas fa-exclamation-triangle"></i> <strong>Thiếu thiết bị:</strong><br>';
                            if (unavailableItems.length > 0) {
                                unavailableItems.forEach(item => {
                                    const equipment = (combo.equipment || []).find(eq => eq.ID_TB === item.equipment_id);
                                    const equipmentName = equipment ? equipment.TenThietBi : `ID: ${item.equipment_id}`;
                                    infoHtml += `• ${equipmentName}: Cần ${item.required}, còn ${item.available}/${item.total}<br>`;
                                });
                            }
                            infoHtml += '</small></div>';
                            combo.availabilityInfo = infoHtml;
                            
                            // Re-render để hiển thị thông báo
                            displayComboSuggestions();
                        }
                    } else {
                        showError('Lỗi khi kiểm tra combo. Vui lòng thử lại.');
                        card.find('.combo-footer').html(originalFooter);
                    }
                },
                error: function(xhr, status, error) {
                    // Khôi phục nội dung footer
                    card.find('.combo-footer').html(originalFooter);
                    
                    console.error('Error checking combo availability:', error);
                    showError('Lỗi kết nối khi kiểm tra combo. Vui lòng thử lại.');
                }
            });
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
                    const selectedEq = selectedEquipment.find(eq => eq.ID_TB === equipment.ID_TB);
                    const selectedQuantity = selectedEq ? selectedEq.SoLuong : 1;
                    const totalQuantity = equipment.SoLuong || 0;
                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="card equipment-card h-100 ${isSelected ? 'selected' : ''}" data-equipment-id="${equipment.ID_TB}">
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
                                                <div class="mt-2">
                                                    <small class="text-muted">Tổng số lượng:</small><br>
                                                    <span class="badge bg-info">${totalQuantity} ${equipment.DonViTinh}</span>
                                                </div>
                                                <div class="mt-2 equipment-quantity-section" style="display: ${isSelected ? 'block' : 'none'};">
                                                    <label class="form-label small">Số lượng:</label>
                                                    <div class="input-group input-group-sm">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="changeEquipmentQuantity(${equipment.ID_TB}, -1)">-</button>
                                                        <input type="number" class="form-control text-center equipment-quantity-input" 
                                                               id="quantity_${equipment.ID_TB}" 
                                                               value="${selectedQuantity}" 
                                                               min="1" 
                                                               max="${totalQuantity}"
                                                               onchange="updateEquipmentQuantity(${equipment.ID_TB}, this.value)"
                                                               onblur="updateEquipmentQuantity(${equipment.ID_TB}, this.value)">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="changeEquipmentQuantity(${equipment.ID_TB}, 1)">+</button>
                                                    </div>
                                                    <small class="text-muted d-block mt-1" id="available_${equipment.ID_TB}">
                                                        <i class="fas fa-info-circle"></i> Đang kiểm tra...
                                                    </small>
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
                // Show quantity section
                $(card).find('.equipment-quantity-section').show();
                // Check availability
                checkEquipmentAvailability(equipmentId);
            } else {
                // Remove equipment if selected
                if (existingIndex !== -1) {
                    selectedEquipment.splice(existingIndex, 1);
                }
                // Remove selected class
                card.classList.remove('selected');
                // Hide quantity section
                $(card).find('.equipment-quantity-section').hide();
            }
            
            updateOrderSummary();
        }
        
        // Check equipment availability
        function checkEquipmentAvailability(equipmentId) {
            const eventDate = $('#eventDate').val();
            const eventTime = $('#eventTime').val();
            const eventEndDate = $('#eventEndDate').val();
            const eventEndTime = $('#eventEndTime').val();
            
            if (!eventDate || !eventEndDate) {
                $('#available_' + equipmentId).html('<i class="fas fa-exclamation-triangle text-warning"></i> Vui lòng chọn ngày sự kiện');
                return;
            }
            
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            
            $.get('../src/controllers/event-register.php', {
                action: 'check_equipment_availability',
                equipment_id: equipmentId,
                start_date: eventDate,
                start_time: eventTime || '00:00',
                end_date: eventEndDate,
                end_time: eventEndTime || '23:59',
                event_id: editId || null
            }, function(data) {
                if (data.success) {
                    const available = data.available_quantity;
                    const booked = data.booked_quantity;
                    const total = data.total_quantity;
                    
                    const availableEl = $('#available_' + equipmentId);
                    const quantityInput = $('#quantity_' + equipmentId);
                    const equipment = equipmentSuggestions.find(eq => eq.ID_TB === equipmentId);
                    const maxQuantity = equipment ? equipment.SoLuong : total;
                    
                    // Update max attribute
                    quantityInput.attr('max', available);
                    
                    if (available <= 0) {
                        availableEl.html(`<i class="fas fa-times-circle text-danger"></i> Hết hàng (${booked}/${total} đã đặt)`);
                        quantityInput.prop('disabled', true);
                        // Uncheck if already checked
                        $('#equipment_' + equipmentId).prop('checked', false);
                        toggleEquipment(equipmentId, '', 0);
                    } else {
                        availableEl.html(`<i class="fas fa-check-circle text-success"></i> Còn ${available} ${equipment ? equipment.DonViTinh : 'cái'} (${booked}/${total} đã đặt)`);
                        quantityInput.prop('disabled', false);
                        
                        // Adjust quantity if current selection exceeds available
                        const currentQuantity = parseInt(quantityInput.val()) || 1;
                        if (currentQuantity > available) {
                            const equipment = equipmentSuggestions.find(eq => eq.ID_TB === equipmentId);
                            const equipmentName = equipment ? equipment.TenThietBi : 'thiết bị';
                            showError(`Số lượng ${currentQuantity} vượt quá số lượng còn lại (${available} cái). Đã tự động điều chỉnh về ${available} cái cho "${equipmentName}".`);
                            console.warn(`⚠️ Số lượng hiện tại ${currentQuantity} vượt quá số lượng còn lại ${available}, đã điều chỉnh về ${available}`);
                            quantityInput.val(available);
                            
                            // Cập nhật trực tiếp vào selectedEquipment để tránh gọi lại updateEquipmentQuantity (tránh vòng lặp)
                            const existingIndex = selectedEquipment.findIndex(eq => eq.ID_TB === equipmentId);
                            if (existingIndex !== -1) {
                                selectedEquipment[existingIndex].SoLuong = available;
                                updateOrderSummary();
                            }
                        }
                    }
                } else {
                    $('#available_' + equipmentId).html('<i class="fas fa-exclamation-triangle text-warning"></i> ' + (data.error || 'Không thể kiểm tra'));
                }
            }, 'json').fail(function() {
                $('#available_' + equipmentId).html('<i class="fas fa-exclamation-triangle text-warning"></i> Lỗi kết nối');
            });
        }
        
        // Change equipment quantity
        function changeEquipmentQuantity(equipmentId, delta) {
            const quantityInput = $('#quantity_' + equipmentId);
            const currentValue = parseInt(quantityInput.val()) || 1;
            const min = parseInt(quantityInput.attr('min')) || 1;
            const max = parseInt(quantityInput.attr('max')) || 999;
            
            // Tính giá trị mới
            const newValue = Math.max(min, Math.min(max, currentValue + delta));
            
            // Nếu đã đạt max và cố tăng, hoặc đã đạt min và cố giảm, không làm gì
            if ((delta > 0 && currentValue >= max) || (delta < 0 && currentValue <= min)) {
                console.log(`⚠️ Không thể thay đổi số lượng: ${currentValue} (min: ${min}, max: ${max})`);
                return;
            }
            
            quantityInput.val(newValue);
            updateEquipmentQuantity(equipmentId, newValue);
        }
        
        // Update equipment quantity
        function updateEquipmentQuantity(equipmentId, quantity) {
            const qty = parseInt(quantity) || 1;
            const quantityInput = $('#quantity_' + equipmentId);
            const max = parseInt(quantityInput.attr('max')) || 999;
            
            // Validate: không cho phép vượt quá max (available quantity)
            const validQty = Math.max(1, Math.min(max, qty));
            
            // Nếu số lượng không hợp lệ, cập nhật lại input và hiển thị thông báo lỗi
            if (validQty !== qty) {
                quantityInput.val(validQty);
                const equipment = equipmentSuggestions.find(eq => eq.ID_TB === equipmentId);
                const equipmentName = equipment ? equipment.TenThietBi : 'thiết bị';
                showError(`Số lượng ${qty} vượt quá số lượng còn lại (${max} cái). Đã tự động điều chỉnh về ${validQty} cái cho "${equipmentName}".`);
                console.warn(`⚠️ Số lượng ${qty} vượt quá giới hạn ${max}, đã điều chỉnh về ${validQty}`);
            }
            
            const existingIndex = selectedEquipment.findIndex(eq => eq.ID_TB === equipmentId);
            
            if (existingIndex !== -1) {
                selectedEquipment[existingIndex].SoLuong = validQty;
                updateOrderSummary();
                
                // Re-check availability after quantity change
                checkEquipmentAvailability(equipmentId);
            }
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
            const isIndoor = selectedLocation && (selectedLocation.LoaiDiaDiem === 'Trong nhà' || selectedLocation.LoaiDiaDiem === 'Trong nha');
            
            if (selectedLocation && eventDate && eventTime && eventEndDate && eventEndTime) {
                const startDate = new Date(eventDate + ' ' + eventTime);
                const endDate = new Date(eventEndDate + ' ' + eventEndTime);
                const durationMs = endDate - startDate;
                const durationHours = Math.ceil(durationMs / (1000 * 60 * 60));
                const durationDays = Math.ceil(durationMs / (1000 * 60 * 60 * 24));
                
                // QUAN TRỌNG: Nếu là địa điểm trong nhà và có chọn phòng, CHỈ tính giá phòng, KHÔNG tính giá địa điểm
                const isIndoor = selectedLocation.LoaiDiaDiem === 'Trong nhà' || selectedLocation.LoaiDiaDiem === 'Trong nha';
                if (isIndoor && selectedLocation.selectedRoom) {
                    const room = selectedLocation.selectedRoom;
                    console.log('Calculating room price (indoor location with room):', room);
                    
                    // Xác định loại thuê: ưu tiên selectedRoomRentalType, nếu không có thì dựa vào giá có sẵn
                    let rentalType = selectedLocation.selectedRoomRentalType;
                    if (!rentalType) {
                        // Mặc định: nếu có giá giờ thì chọn giờ, nếu không có giá giờ nhưng có giá ngày thì chọn ngày
                        if (room.GiaThueGio && room.GiaThueGio > 0) {
                            rentalType = 'hour';
                            selectedLocation.selectedRoomRentalType = 'hour';
                        } else if (room.GiaThueNgay && room.GiaThueNgay > 0) {
                            rentalType = 'day';
                            selectedLocation.selectedRoomRentalType = 'day';
                        }
                    }
                    
                    if (room.LoaiThue === 'Theo giờ' && room.GiaThueGio && room.GiaThueGio > 0) {
                        // Phòng chỉ có giá theo giờ
                        locationPriceNum = durationHours * parseFloat(room.GiaThueGio);
                        locationPriceText = `Phòng: ${new Intl.NumberFormat('vi-VN').format(room.GiaThueGio)}/giờ × ${durationHours} giờ`;
                        console.log('Using room hourly pricing:', locationPriceNum);
                    } else if (room.LoaiThue === 'Theo ngày' && room.GiaThueNgay && room.GiaThueNgay > 0) {
                        // Phòng chỉ có giá theo ngày
                        locationPriceNum = durationDays * parseFloat(room.GiaThueNgay);
                        locationPriceText = `Phòng: ${new Intl.NumberFormat('vi-VN').format(room.GiaThueNgay)}/ngày × ${durationDays} ngày`;
                        console.log('Using room daily pricing:', locationPriceNum);
                    } else if (room.LoaiThue === 'Cả hai') {
                        // Phòng có cả hai loại giá
                        if (rentalType === 'hour' && room.GiaThueGio && room.GiaThueGio > 0) {
                            locationPriceNum = durationHours * parseFloat(room.GiaThueGio);
                            locationPriceText = `Phòng: ${new Intl.NumberFormat('vi-VN').format(room.GiaThueGio)}/giờ × ${durationHours} giờ`;
                            console.log('Using room selected hourly pricing:', locationPriceNum);
                        } else if (rentalType === 'day' && room.GiaThueNgay && room.GiaThueNgay > 0) {
                            locationPriceNum = durationDays * parseFloat(room.GiaThueNgay);
                            locationPriceText = `Phòng: ${new Intl.NumberFormat('vi-VN').format(room.GiaThueNgay)}/ngày × ${durationDays} ngày`;
                            console.log('Using room selected daily pricing:', locationPriceNum);
                        } else {
                            // Chưa chọn loại thuê hoặc không có giá cho loại đã chọn
                            const hourlyPrice = durationHours * parseFloat(room.GiaThueGio || 0);
                            const dailyPrice = durationDays * parseFloat(room.GiaThueNgay || 0);
                            if (hourlyPrice > 0 && dailyPrice > 0) {
                                // Có cả hai giá nhưng chưa chọn - mặc định chọn giờ
                                locationPriceNum = hourlyPrice;
                                locationPriceText = `Phòng: ${new Intl.NumberFormat('vi-VN').format(room.GiaThueGio)}/giờ × ${durationHours} giờ`;
                                // Tự động set loại thuê
                                if (!selectedLocation.selectedRoomRentalType) {
                                    selectedLocation.selectedRoomRentalType = 'hour';
                                }
                                console.log('Using default hourly pricing (both available):', locationPriceNum);
                            } else if (hourlyPrice > 0) {
                                locationPriceNum = hourlyPrice;
                                locationPriceText = `Phòng: ${new Intl.NumberFormat('vi-VN').format(room.GiaThueGio)}/giờ × ${durationHours} giờ`;
                                console.log('Using hourly pricing (only hourly available):', locationPriceNum);
                            } else if (dailyPrice > 0) {
                                locationPriceNum = dailyPrice;
                                locationPriceText = `Phòng: ${new Intl.NumberFormat('vi-VN').format(room.GiaThueNgay)}/ngày × ${durationDays} ngày`;
                                console.log('Using daily pricing (only daily available):', locationPriceNum);
                            }
                        }
                    }
                } else {
                    // Địa điểm ngoài trời hoặc không chọn phòng, tính giá theo địa điểm
                    console.log('Calculating location price (outdoor or no room):', {
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
                ${isIndoor && selectedLocation.selectedRoom ? `
                <div class="summary-item">
                    <span>Phòng đã chọn:</span>
                    <span><strong>${selectedLocation.selectedRoom.TenPhong || 'N/A'}</strong></span>
                </div>
                ` : ''}
                <div class="summary-item">
                    <span>${isIndoor && selectedLocation.selectedRoom ? 'Giá thuê phòng:' : 'Giá thuê địa điểm:'}</span>
                    <span>${locationPriceText}</span>
                </div>
                <div class="summary-item">
                    <span>Tổng ${isIndoor && selectedLocation.selectedRoom ? 'giá phòng' : 'giá địa điểm'}:</span>
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
            
            // Add combos if selected (hiển thị tất cả combo đã chọn - cả pending và confirmed)
            const allSelectedCombos = [...pendingComboSelections, ...selectedCombos];
            if (allSelectedCombos && allSelectedCombos.length > 0) {
                const pendingCount = pendingComboSelections.length;
                const confirmedCount = selectedCombos.length;
                html += `<div class="summary-item"><span><strong>Combo thiết bị (${allSelectedCombos.length}${pendingCount > 0 ? ' - ' + pendingCount + ' chưa xác nhận' : ''}):</strong></span></div>`;
                let totalComboPrice = 0;
                allSelectedCombos.forEach(combo => {
                    const comboPrice = parseFloat(combo.GiaCombo) || 0;
                    const comboPriceFormatted = new Intl.NumberFormat('vi-VN').format(comboPrice);
                    const isPending = pendingComboSelections.some(c => c.ID_Combo === combo.ID_Combo);
                    totalComboPrice += comboPrice;
                    html += `
                        <div class="summary-item" style="margin-left: 15px;">
                            <span>• ${combo.TenCombo}${isPending ? ' <small class="text-muted">(chưa xác nhận)</small>' : ''}</span>
                            <span>${comboPriceFormatted} VNĐ</span>
                        </div>
                    `;
                });
                html += `
                    <div class="summary-item">
                        <span><strong>Tổng giá combo:</strong></span>
                        <span><strong>${new Intl.NumberFormat('vi-VN').format(totalComboPrice)} VNĐ</strong></span>
                    </div>
                `;
                totalPrice += totalComboPrice;
            }
            
            // Add individual equipment if selected (tính theo số lượng)
            if (selectedEquipment.length > 0) {
                html += `<div class="summary-item"><span><strong>Thiết bị riêng lẻ:</strong></span></div>`;
                let equipmentTotal = 0;
                selectedEquipment.forEach(equipment => {
                    const equipmentPrice = parseFloat(equipment.GiaThue) || 0;
                    const quantity = parseInt(equipment.SoLuong) || 1;
                    const itemTotal = equipmentPrice * quantity;
                    const itemTotalFormatted = new Intl.NumberFormat('vi-VN').format(itemTotal);
                    const unitPriceFormatted = new Intl.NumberFormat('vi-VN').format(equipmentPrice);
                    html += `
                        <div class="summary-item" style="margin-left: 15px;">
                            <span>• ${equipment.TenThietBi} (${quantity} cái × ${unitPriceFormatted} VNĐ):</span>
                            <span>${itemTotalFormatted} VNĐ</span>
                        </div>
                    `;
                    equipmentTotal += itemTotal;
                });
                totalPrice += equipmentTotal;
            }
            
            // Debug: Log values to console
            console.log('Event Type Price:', eventTypePrice);
            console.log('Location Price:', locationPriceNum);
            console.log('Combos:', selectedCombos.length, 'Total Combo Price:', selectedCombos.length > 0 ? selectedCombos.reduce((sum, c) => sum + (parseFloat(c.GiaCombo) || 0), 0) : 0);
            console.log('Equipment Total:', selectedEquipment.length > 0 ? selectedEquipment.reduce((sum, eq) => sum + (parseFloat(eq.GiaThue) || 0) * (parseInt(eq.SoLuong) || 1), 0) : 0);
            console.log('Total Price:', totalPrice);
            console.log('Selected Location:', selectedLocation);
            console.log('Selected Equipment:', selectedEquipment);
            console.log('Selected Combos:', selectedCombos);
            
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
            
            // Determine which rental type to send based on location type
            let rentalTypeToSend = null;
            if (selectedLocation) {
                const isIndoor = selectedLocation.LoaiDiaDiem === 'Trong nhà' || selectedLocation.LoaiDiaDiem === 'Trong nha';
                if (isIndoor && selectedLocation.selectedRoom) {
                    // For indoor locations with room, use room rental type
                    rentalTypeToSend = selectedLocation.selectedRoomRentalType || null;
                } else {
                    // For outdoor locations, use location rental type
                    rentalTypeToSend = selectedLocation.selectedRentalType || null;
                }
            }
            
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
                location_rental_type: rentalTypeToSend, // Use room rental type for indoor, location rental type for outdoor
                room_id: selectedLocation && selectedLocation.selectedRoomId ? selectedLocation.selectedRoomId : null,
                room_rental_type: selectedLocation && selectedLocation.selectedRoom ? selectedLocation.selectedRoomRentalType : null,
                equipment_ids: selectedEquipment.map(eq => eq.ID_TB),
                equipment_quantities: selectedEquipment.map(eq => {
                    const qty = parseInt(eq.SoLuong) || 1;
                    console.log(`📦 Equipment ${eq.ID_TB} (${eq.TenThietBi}): Quantity = ${qty}`);
                    return { id: eq.ID_TB, quantity: qty };
                }),
                // Khi submit, chuyển tất cả pending combos sang confirmed và gửi đi
                combo_ids: [...pendingComboSelections, ...selectedCombos].map(c => c.ID_Combo), // Gửi array các combo ID
                total_price: calculateTotalPrice()
            };
            
            // Chuyển pending combos sang confirmed khi submit
            selectedCombos = [...pendingComboSelections, ...selectedCombos];
            pendingComboSelections = [];
            
            console.log('Form data:', formData);
            console.log('Selected location:', selectedLocation);
            console.log('Selected equipment:', selectedEquipment);
            console.log('Selected combos:', selectedCombos);
            
            // Add edit ID if we're editing
            if (editId) {
                formData.edit_id = editId;
            }
            
            // Đảm bảo có CSRF token trước khi submit
            // Nếu token chưa có, fetch trước
            (async function() {
                try {
                    // Lấy CSRF token (đợi nếu cần)
                    const token = await CSRFHelper.getToken();
                    if (token) {
                        formData.csrf_token = token;
                    } else {
                        console.error('Failed to get CSRF token');
                        showError('Không thể lấy CSRF token. Vui lòng tải lại trang.');
                        $('#loadingSpinner').hide();
                        $('#submitBtn').prop('disabled', false);
                        return;
                    }
                    
                    // Gửi request sau khi có token
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
                            
                            // Kiểm tra nếu là lỗi CSRF
                            if (xhr.status === 403 && xhr.responseJSON && xhr.responseJSON.code === 'CSRF_TOKEN_INVALID') {
                                showError('CSRF token không hợp lệ. Vui lòng tải lại trang và thử lại.');
                                // Refresh token
                                CSRFHelper.refreshToken();
                            } else {
                                showError('Lỗi kết nối. Vui lòng thử lại.');
                            }
                        }
                    });
                } catch (err) {
                    console.error('Error getting CSRF token:', err);
                    showError('Lỗi khi lấy CSRF token. Vui lòng tải lại trang.');
                    $('#loadingSpinner').hide();
                    $('#submitBtn').prop('disabled', false);
                }
            })();
            
            // Return false để ngăn form submit mặc định
            return false;
        });
    </script>
</body>
</html>


