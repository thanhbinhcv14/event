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
            padding: 0.5rem;
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
                                <option value="Chờ thanh toán">Chờ thanh toán</option>
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

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
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
                // Debug logging for payment status
                console.log('Event payment status debug:', {
                    eventId: event.ID_DatLich,
                    eventName: event.TenSuKien,
                    TrangThaiThanhToan: event.TrangThaiThanhToan,
                    TrangThaiDuyet: event.TrangThaiDuyet
                });
                
                const statusClass = getStatusClass(event.TrangThaiDuyet);
                const paymentClass = getPaymentClass(event);
                const paymentStatusText = getPaymentStatusText(event);
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
                                    <span class="status-badge ${paymentClass}">${paymentStatusText}</span>
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
                            ${getPaymentButton(event)}
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
                const paymentStatus = getPaymentStatusText(event);
                const matchesPayment = !paymentFilter || paymentStatus === paymentFilter;
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
            const paid = allEvents.filter(e => {
                const paymentStatus = getPaymentStatusText(e);
                return paymentStatus === 'Đã thanh toán đủ' || paymentStatus === 'Đã đặt cọc';
            }).length;
            
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
        
        // Get payment class based on both event status and payment data
        function getPaymentClass(event) {
            // If there's payment data, use it to determine status
            if (event.ID_ThanhToan && event.ID_ThanhToan > 0 && event.PaymentStatus) {
                switch(event.PaymentStatus) {
                    case 'Thành công':
                        return event.LoaiThanhToan === 'Đặt cọc' ? 'status-approved' : 'status-paid';
                    case 'Đang xử lý':
                        return 'status-pending';
                    case 'Thất bại':
                    case 'Đã hủy':
                        return 'status-rejected';
                    default:
                        return 'status-pending';
                }
            }
            
            // Fallback to event payment status
            const payment = event.TrangThaiThanhToan;
            if (!payment || payment === null || payment === undefined || payment === '') {
                return 'status-pending';
            }
            
            switch(payment) {
                case 'Chưa thanh toán': return 'status-pending';
                case 'Chờ thanh toán': return 'status-pending';
                case 'Đã đặt cọc': return 'status-approved';
                case 'Đã thanh toán đủ': return 'status-paid';
                default: 
                    console.warn('Unknown payment status:', payment);
                    return 'status-pending';
            }
        }
        
        // Get payment status text based on both event status and payment data
        function getPaymentStatusText(event) {
            // If there's payment data, use it to determine status
            if (event.ID_ThanhToan && event.ID_ThanhToan > 0 && event.PaymentStatus) {
                switch(event.PaymentStatus) {
                    case 'Thành công':
                        return event.LoaiThanhToan === 'Đặt cọc' ? 'Đã đặt cọc' : 'Đã thanh toán đủ';
                    case 'Đang xử lý':
                        return 'Chờ thanh toán';
                    case 'Thất bại':
                        return 'Thanh toán thất bại';
                    case 'Đã hủy':
                        return 'Đã hủy thanh toán';
                    default:
                        return 'Chưa thanh toán';
                }
            }
            
            // Fallback to event payment status
            return event.TrangThaiThanhToan || 'Chưa thanh toán';
        }
        
        // Get payment button based on both event status and payment data
        function getPaymentButton(event) {
            // Only show payment button for approved events
            if (event.TrangThaiDuyet !== 'Đã duyệt') {
                return '';
            }
            
            // If there's payment data, use it to determine button
            if (event.ID_ThanhToan && event.ID_ThanhToan > 0 && event.PaymentStatus) {
                switch(event.PaymentStatus) {
                    case 'Thành công':
                        if (event.LoaiThanhToan === 'Đặt cọc') {
                            return `<button class="btn btn-info btn-sm" disabled>
                                <i class="fas fa-hand-holding-usd"></i> Đã đặt cọc
                            </button>`;
                        } else {
                            return `<button class="btn btn-success btn-sm" disabled>
                                <i class="fas fa-check-circle"></i> Đã thanh toán đủ
                            </button>`;
                        }
                    case 'Đang xử lý':
                        return `<div class="btn-group" role="group">
                            <button class="btn btn-warning btn-sm" disabled>
                                <i class="fas fa-clock"></i> Đang xử lý
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="cancelPayment(${event.ID_DatLich})" title="Hủy thanh toán">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>`;
                    case 'Thất bại':
                        return `<button class="btn btn-outline-primary btn-sm" onclick="makePayment(${event.ID_DatLich})">
                            <i class="fas fa-credit-card"></i> Thanh toán lại
                        </button>`;
                    case 'Đã hủy':
                        return `<button class="btn btn-outline-primary btn-sm" onclick="makePayment(${event.ID_DatLich})">
                            <i class="fas fa-credit-card"></i> Thanh toán
                        </button>`;
                    default:
                        return `<button class="btn btn-outline-primary btn-sm" onclick="makePayment(${event.ID_DatLich})">
                            <i class="fas fa-credit-card"></i> Thanh toán
                        </button>`;
                }
            }
            
            // Fallback to event payment status
            const paymentStatus = event.TrangThaiThanhToan || 'Chưa thanh toán';
            switch(paymentStatus) {
                case 'Chưa thanh toán':
                    return `<button class="btn btn-outline-primary btn-sm" onclick="makePayment(${event.ID_DatLich})">
                        <i class="fas fa-credit-card"></i> Thanh toán
                    </button>`;
                case 'Chờ thanh toán':
                    return `<button class="btn btn-warning btn-sm" disabled>
                        <i class="fas fa-clock"></i> Chờ xác nhận
                    </button>`;
                case 'Đã đặt cọc':
                    return `<button class="btn btn-info btn-sm" disabled>
                        <i class="fas fa-hand-holding-usd"></i> Đã đặt cọc
                    </button>`;
                case 'Đã thanh toán đủ':
                    return `<button class="btn btn-success btn-sm" disabled>
                        <i class="fas fa-check-circle"></i> Đã thanh toán đủ
                    </button>`;
                default:
                    return `<button class="btn btn-outline-primary btn-sm" onclick="makePayment(${event.ID_DatLich})">
                        <i class="fas fa-credit-card"></i> Thanh toán
                    </button>`;
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
                            <tr><td><strong>Trạng thái thanh toán:</strong></td><td><span class="status-badge ${getPaymentClass(event)}">${getPaymentStatusText(event)}</span></td></tr>
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
                            loadMyEvents(); // Reload the events list
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
                        <div class="col-md-4">
                            <div class="card payment-method-card" data-method="sepay">
                                <div class="card-body text-center">
                                    <i class="fas fa-university fa-3x text-info mb-3"></i>
                                    <h6>SePay</h6>
                                    <small class="text-muted">Thanh toán qua SePay</small>
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
                                <input class="form-check-input" type="radio" name="paymentType" id="depositPayment" value="deposit">
                                <label class="form-check-label" for="depositPayment">
                                    <strong>Đặt cọc</strong> - ${new Intl.NumberFormat('vi-VN').format(depositAmount)} VNĐ
                                    <br><small class="text-muted">Thanh toán 30% để giữ chỗ</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="paymentType" id="fullPayment" value="full" checked>
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
                    <div id="sepayDetails" class="payment-details" style="display: none;">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-university"></i> Thanh toán qua SePay</h6>
                            <p class="mb-0">Thanh toán nhanh chóng và bảo mật qua SePay</p>
                        </div>
                    </div>
                </div>
            `);
            
            // Store event data for payment processing
            $('#paymentModal').data('event', event);
            $('#paymentModal').modal('show');
            
            // Setup payment method selection
            setupPaymentMethodSelection();

            // Default preference: ưu tiên Thanh toán đủ khi mới mở modal
            $('#fullPayment').prop('checked', true);
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
                
                if (method === 'cash') {
                    // Tiền mặt cho phép cả đặt cọc và thanh toán đủ
                    $('#depositPayment').prop('disabled', false);
                    $('#fullPayment').prop('disabled', false);
                } else if (method === 'banking') {
                    // Chuyển khoản cho phép cả đặt cọc và thanh toán đủ
                    $('#depositPayment').prop('disabled', false);
                    $('#fullPayment').prop('disabled', false);
                } else if (method === 'sepay') {
                    // SePay cho phép cả đặt cọc và thanh toán đủ
                    $('#depositPayment').prop('disabled', false);
                    $('#fullPayment').prop('disabled', false);
                }
            });
        }
        
        
        // Process payment
        function processPayment() {
            const event = $('#paymentModal').data('event');
            const paymentMethod = $('.payment-method-card.border-primary').data('method');
            let paymentType = $('input[name="paymentType"]:checked').val();
            
            if (!paymentMethod) {
                alert('Vui lòng chọn phương thức thanh toán');
                return;
            }

            // Enforce business rules on the client-side
            // Cash, banking, and sepay allow both deposit and full payment
            // Only momo and zalo are restricted to deposit only
            
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
                
                if (paymentMethod === 'sepay') {
                    // Use SePay API
                    apiAction = 'create_sepay_payment';
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
                           if (response.fallback) {
                               // SePay không khả dụng, hiển thị thông báo và QR code
                               alert(response.message);
                               if (response.qr_code) {
                                   showQRCodeModal({
                                       qr_code: response.qr_code,
                                       amount: response.amount,
                                       transaction_id: response.transaction_id || response.payment_code,
                                       payment_method: 'banking'
                                   });
                               }
                               $('#paymentModal').modal('hide');
                               loadMyEvents();
                           } else if (paymentMethod === 'sepay' && response.pay_url) {
                               // Redirect to SePay Gateway
                               $('#paymentModalFooter').html(`
                                   <div class="text-center">
                                       <div class="alert alert-info">
                                           <i class="fas fa-university"></i>
                                           <strong>Đang chuyển hướng đến SePay...</strong>
                                       </div>
                                       <p>Vui lòng đợi trong giây lát...</p>
                                   </div>
                               `);
                               
                               // Redirect to SePay Gateway
                               setTimeout(() => {
                                   window.location.href = response.pay_url;
                               }, 2000);
                           } else if (response.qr_code) {
                                // Show QR code for offline payment
                                showQRCodeModal({
                                    qr_code: response.qr_code,
                                    amount: response.amount,
                                    transaction_id: response.transaction_id || response.payment_code,
                                    waiting_payment: response.waiting_payment,
                                    payment_method: paymentMethod,
                                    message: response.message
                                });
                            } else {
                                // Với tiền mặt/chuyển khoản (không SePay), coi như đã tạo giao dịch và chờ xác nhận
                                alert('Đã tạo giao dịch. Trạng thái: Chờ thanh toán. Quản lý sẽ xác nhận sớm.');
                                $('#paymentModal').modal('hide');
                                loadMyEvents(); // Reload events để hiển thị "Chờ thanh toán"
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
                case 'banking': return 'Chuyển khoản ngân hàng';
                case 'cash': return 'Tiền mặt';
                case 'sepay': return 'SePay';
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
                        <strong>${paymentData.message || 'Quét mã QR để thanh toán'}</strong>
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
                            <tr><td><strong>Mã giao dịch:</strong></td><td><code>${paymentData.transaction_id || paymentData.transaction_code || paymentData.payment_code}</code></td></tr>
                        </table>
                    </div>
                    
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
                    
                    ${paymentMethod === 'sepay' ? `
                    ` : ''}
                    
                </div>
            `);
            
            $('#paymentModalFooter').html(`
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="checkPaymentStatus('${paymentData.transaction_id || paymentData.transaction_code || paymentData.payment_code}')">
                    <i class="fas fa-sync"></i> Load lại thanh toán
                </button>
            `);
            
            // Generate QR Code
            generateQRCode(paymentData.qr_code);
        }
        
        // Generate QR Code
        function generateQRCode(qrData) {
            // Check if qrData is a URL (from VietQR) or a string (for QR generation)
            if (qrData.startsWith('http')) {
                // It's a VietQR URL, use it directly
                $('#qrcode').html(`
                    <img src="${qrData}" alt="QR Code" class="img-fluid border rounded" style="max-width: 300px;">
                `);
            } else {
                // It's a string, generate QR code using service
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(qrData)}`;
                $('#qrcode').html(`
                    <img src="${qrUrl}" alt="QR Code" class="img-fluid border rounded" style="max-width: 300px;">
                `);
            }
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
                    action: 'check_payment_status',
                    transaction_code: transactionCode
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.found && response.payment) {
                            const payment = response.payment;
                            if (payment.TrangThai === 'Thành công') {
                                alert('Thanh toán thành công! Trạng thái đã được cập nhật.');
                                $('#paymentModal').modal('hide');
                                loadMyEvents(); // Reload events
                            } else if (payment.TrangThai === 'Thất bại') {
                                alert('Thanh toán thất bại. Vui lòng thử lại.');
                                resetPaymentModal();
                            } else if (payment.TrangThai === 'Đã hủy') {
                                alert('Thanh toán đã bị hủy. Bạn có thể thanh toán lại.');
                                $('#paymentModal').modal('hide');
                                loadMyEvents(); // Reload events
                            } else {
                                alert('Thanh toán đang được xử lý. Vui lòng đợi thêm.');
                                // Khôi phục nút "Load lại thanh toán"
                                $('#paymentModalFooter').html(`
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                    <button type="button" class="btn btn-primary" onclick="checkPaymentStatus('${transactionCode}')">
                                        <i class="fas fa-sync"></i> Load lại thanh toán
                                    </button>
                                `);
                            }
                        } else {
                            alert('Chưa tìm thấy thông tin thanh toán. Vui lòng thử lại sau.');
                            // Khôi phục nút "Load lại thanh toán"
                            $('#paymentModalFooter').html(`
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                <button type="button" class="btn btn-primary" onclick="checkPaymentStatus('${transactionCode}')">
                                    <i class="fas fa-sync"></i> Load lại thanh toán
                                </button>
                            `);
                        }
                    } else {
                        // Xử lý lỗi cụ thể
                        if (response.error && response.error.includes('đăng nhập')) {
                            alert('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
                            window.location.href = '../login.php';
                        } else {
                            alert('Lỗi khi kiểm tra trạng thái thanh toán: ' + (response.error || 'Lỗi không xác định'));
                        }
                        // Khôi phục nút "Load lại thanh toán"
                        $('#paymentModalFooter').html(`
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="button" class="btn btn-primary" onclick="checkPaymentStatus('${transactionCode}')">
                                <i class="fas fa-sync"></i> Load lại thanh toán
                            </button>
                        `);
                    }
                },
                error: function() {
                    alert('Lỗi kết nối khi kiểm tra trạng thái.');
                    // Khôi phục nút "Load lại thanh toán"
                    $('#paymentModalFooter').html(`
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" onclick="checkPaymentStatus('${transactionCode}')">
                            <i class="fas fa-sync"></i> Load lại thanh toán
                        </button>
                    `);
                }
            });
        }
        
        // Cancel payment and reset to initial state
        function cancelPayment(eventId) {
            if (confirm('Bạn có chắc chắn muốn hủy thanh toán này? Thanh toán sẽ được hủy và bạn có thể thanh toán lại sau.')) {
                console.log('Cancelling payment for event ID:', eventId);
                
                $.ajax({
                    url: '../src/controllers/payment.php',
                    method: 'POST',
                    data: {
                        action: 'cancel_payment',
                        event_id: eventId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Cancel payment response:', response);
                        
                        if (response.success) {
                            alert('Đã hủy thanh toán thành công!');
                            $('#paymentModal').modal('hide'); // Đóng modal thanh toán
                            loadMyEvents(); // Reload events để hiển thị nút "Thanh toán" lại
                        } else {
                            console.error('Cancel payment failed:', response.error);
                            
                            // Handle specific errors
                            if (response.error && response.error.includes('đăng nhập')) {
                                alert('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
                                window.location.href = '../login.php';
                            } else {
                                alert('Lỗi khi hủy thanh toán: ' + (response.error || 'Lỗi không xác định'));
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', xhr, status, error);
                        console.error('Response text:', xhr.responseText);
                        
                        // Try to parse response as JSON
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error && response.error.includes('đăng nhập')) {
                                alert('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
                                window.location.href = '../login.php';
                            } else {
                                alert('Lỗi khi hủy thanh toán: ' + (response.error || 'Lỗi kết nối'));
                            }
                        } catch (e) {
                            alert('Lỗi kết nối khi hủy thanh toán. Vui lòng thử lại.');
                        }
                    }
                });
            }
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
