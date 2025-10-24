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
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let allEvents = [];
        let filteredEvents = [];
        
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
            
            $.get('../../src/controllers/event-register.php?action=get_my_events', function(data) {
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
                const price = event.GiaThue ? new Intl.NumberFormat('vi-VN').format(event.GiaThue) : 'Chưa xác định';
                
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
                                <i class="fas fa-money-bill-wave"></i>
                                <span>${price} VNĐ</span>
                            </div>
                            ${event.TenLoai ? `
                            <div class="detail-item">
                                <i class="fas fa-tag"></i>
                                <span>${event.TenLoai}</span>
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
            const price = event.GiaThue ? new Intl.NumberFormat('vi-VN').format(event.GiaThue) : 'Chưa xác định';
            const budget = event.NganSach ? new Intl.NumberFormat('vi-VN').format(event.NganSach) : 'Chưa xác định';
            
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
                            <tr><td><strong>Giá thuê:</strong></td><td>${price} VNĐ</td></tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6><i class="fas fa-credit-card text-primary"></i> Tài chính</h6>
                        <table class="table table-borderless">
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
                    url: '../../src/controllers/my-events.php?action=cancel_event',
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
        
        // Make payment (placeholder)
        function makePayment(eventId) {
            alert('Chức năng thanh toán sẽ được triển khai trong phiên bản tiếp theo.');
        }
        
        // Load event equipment
        function loadEventEquipment(eventId) {
            $.get(`../../src/controllers/event-register.php?action=get_event_equipment&event_id=${eventId}`, function(data) {
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
