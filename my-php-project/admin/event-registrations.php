<?php
// Bao gồm header admin
include 'includes/admin-header.php';
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-clipboard-list"></i>
                <?php 
                $userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
                if ($userRole == 3) {
                    echo "Xem đăng ký sự kiện";
                } else {
                    echo "Quản lý đăng ký sự kiện";
                }
                ?>
            </h1>
            <p class="page-subtitle">
                <?php 
                if ($userRole == 3) {
                    echo "Xem danh sách đăng ký sự kiện từ khách hàng";
                } else {
                    echo "Duyệt và quản lý các đăng ký sự kiện từ khách hàng";
                }
                ?>
            </p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-number" id="totalRegistrations">0</div>
                <div class="stat-label">Tổng đăng ký</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-number" id="pendingRegistrations">0</div>
                <div class="stat-label">Chờ duyệt</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="approvedRegistrations">0</div>
                <div class="stat-label">Đã duyệt</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon rejected">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number" id="rejectedRegistrations">0</div>
                <div class="stat-label">Từ chối</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Chờ duyệt">Chờ duyệt</option>
                        <option value="Đã duyệt">Đã duyệt</option>
                        <option value="Từ chối">Từ chối</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Từ ngày</label>
                    <input type="date" class="form-control" id="dateFrom">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" class="form-control" id="dateTo">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                        <button class="btn btn-outline-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">
                    <i class="fas fa-list"></i>
                    Danh sách đăng ký sự kiện
                </h3>
                <div class="action-buttons">
                    <button class="btn btn-success" onclick="exportData('csv')">
                        <i class="fas fa-download"></i> Xuất CSV
                    </button>
                    
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="registrationsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sự kiện</th>
                            <th>Khách hàng</th>
                            <th>Địa điểm</th>
                            <th>Tổng tiền</th>
                            <th>Ngày bắt đầu</th>
                            <th>Ngày kết thúc</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- View Registration Modal -->
        <div class="modal fade" id="viewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eye"></i>
                            Chi tiết đăng ký sự kiện
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="viewModalBody">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve/Reject Modal -->
        <div class="modal fade" id="actionModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="actionModalTitle">
                            <i class="fas fa-check"></i>
                            Duyệt đăng ký
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="actionForm">
                            <input type="hidden" id="registrationId" name="id">
                            <input type="hidden" id="actionType" name="action">
                            
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea class="form-control" id="actionNote" name="note" rows="3" 
                                          placeholder="Nhập ghi chú (tùy chọn)"></textarea>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Lưu ý:</strong> Hành động này không thể hoàn tác.
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" id="confirmActionBtn">
                            <i class="fas fa-check"></i> Xác nhận
                        </button>
                    </div>
                </div>
            </div>
        </div>

    
    <style>
        /* Remove modal backdrop completely */
        .modal-backdrop {
            display: none !important;
        }
        
        /* Ensure body doesn't get locked when modal is open */
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }
        
        /* Optional: Add a subtle overlay effect if you want some visual indication */
        .modal.show {
            background-color: rgba(0, 0, 0, 0.1);
        }
    </style>

    <script>
        let registrationsTable;
        let currentFilters = {};
        let userRole = <?php echo json_encode($_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null); ?>;

        // Khởi tạo trang
        document.addEventListener('DOMContentLoaded', function() {
            // Kiểm tra AJAX call trước
            testAjaxCall();
            initializeDataTable();
            loadStatistics();
            setupEventListeners();
        });
        
        function testAjaxCall() {
            console.log('Testing AJAX call...');
            $.ajax({
                url: '../src/controllers/admin-events.php',
                type: 'GET',
                data: { action: 'get_registrations' },
                dataType: 'json',
                success: function(response) {
                    console.log('Direct AJAX test success:', response);
                    console.log('Response type:', typeof response);
                    console.log('Has success:', 'success' in response);
                    console.log('Has registrations:', 'registrations' in response);
                    if (response.registrations) {
                        console.log('Registrations type:', typeof response.registrations);
                        console.log('Is array:', Array.isArray(response.registrations));
                        console.log('Count:', response.registrations.length);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Direct AJAX test error:', xhr, status, error);
                    console.error('Response text:', xhr.responseText);
                }
            });
        }

        function initializeDataTable() {
            // Kiểm tra DataTables có sẵn không
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables not available');
                alert('DataTables không khả dụng - sử dụng bảng đơn giản');
                loadSimpleTable();
                return;
            }

            try {
                registrationsTable = $('#registrationsTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '../src/controllers/admin-events.php',
                        type: 'GET',
                        data: function(d) {
                            d.action = 'get_registrations';
                            return $.extend(d, currentFilters);
                        },
                        dataSrc: function(json) {
                            console.log('AJAX Response:', json);
                            console.log('Response type:', typeof json);
                            console.log('Response keys:', Object.keys(json || {}));
                            
                            if (json && json.success && Array.isArray(json.registrations)) {
                                console.log('Found registrations:', json.registrations.length);
                                return json.registrations;
                            } else if (json && json.success && json.registrations === null) {
                                console.log('No registrations found (null)');
                                return [];
                            } else if (json && json.success && json.registrations === undefined) {
                                console.log('No registrations found (undefined)');
                                return [];
                            } else {
                                console.error('Invalid data format:', json);
                                console.error('Success:', json ? json.success : 'undefined');
                                console.error('Registrations:', json ? json.registrations : 'undefined');
                                console.error('Message:', json ? json.message : 'undefined');
                                
                                // Thử hiển thị thông báo lỗi cho người dùng
                                if (json && json.message) {
                                    alert('Lỗi: ' + json.message);
                                }
                                
                                return [];
                            }
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTable AJAX Error:', xhr, error, thrown);
                            console.error('Response Text:', xhr.responseText);
                            alert('Lỗi khi tải dữ liệu: ' + error + ' - ' + xhr.responseText);
                        }
                    },
                columns: [
                    { data: 'ID_DatLich', className: 'text-center' },
                    { 
                        data: 'TenSuKien',
                        render: function(data, type, row) {
                            return `<strong>${data}</strong><br><small class="text-muted">${row.TenLoai}</small>`;
                        }
                    },
                    { data: 'HoTen' },
                    { data: 'TenDiaDiem' },
                    { 
                        data: 'TongTien',
                        render: function(data) {
                            if (!data || data == 0) return '<span class="text-muted">Chưa có</span>';
                            return `<strong class="text-success">${new Intl.NumberFormat('vi-VN').format(data)} VNĐ</strong>`;
                        }
                    },
                    { 
                        data: 'NgayBatDau',
                        render: function(data) {
                            if (!data) return '';
                            const date = new Date(data);
                            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
                        }
                    },
                    { 
                        data: 'NgayKetThuc',
                        render: function(data) {
                            if (!data) return '';
                            const date = new Date(data);
                            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
                        }
                    },
                    { 
                        data: 'TrangThaiDuyet',
                        render: function(data) {
                            if (!data) return '<span class="status-badge status-unknown">Không xác định</span>';
                            const statusClass = data.toLowerCase().replace(' ', '-');
                            return `<span class="status-badge status-${statusClass}">${data}</span>`;
                        }
                    },
                    { 
                        data: 'NgayTao',
                        render: function(data) {
                            if (!data) return '';
                            const date = new Date(data);
                            return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
                        }
                    },
                    { 
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            let actions = `
                                <button class="btn btn-info btn-sm" onclick="viewRegistration(${row.ID_DatLich})" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                            `;
                            
                            // Chỉ hiển thị nút duyệt/từ chối cho role 1 và 2
                            if (row.TrangThaiDuyet === 'Chờ duyệt' && (userRole == 1 || userRole == 2)) {
                                actions += `
                                    <button class="btn btn-success btn-sm" onclick="showActionModal(${row.ID_DatLich}, 'approve')" title="Duyệt">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="showActionModal(${row.ID_DatLich}, 'reject')" title="Từ chối">
                                        <i class="fas fa-times"></i>
                                    </button>
                                `;
                            }
                            
                            return `<div class="action-buttons">${actions}</div>`;
                        }
                    }
                ],
                order: [[0, 'desc']],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                // Ẩn thanh tìm kiếm và thông tin hiển thị của DataTable
                dom: 'rtip',
                info: false, // Ẩn thông tin "Hiển thị X dữ liệu"
                paging: false // Ẩn phân trang (hiển thị tất cả dữ liệu)
            });
            } catch (error) {
                console.error('Error initializing DataTable:', error);
                alert('Lỗi khởi tạo DataTable - chuyển sang bảng đơn giản');
                loadSimpleTable();
            }
        }

        function loadStatistics() {
            // Sử dụng jQuery AJAX đơn giản thay vì AdminPanel
            $.ajax({
                url: '../src/controllers/admin-events.php',
                type: 'GET',
                data: { action: 'get_registration_stats' },
                dataType: 'json',
                success: function(response) {
                    console.log('Statistics response:', response);
                    if (response.success) {
                        $('#totalRegistrations').text(response.stats.total || 0);
                        $('#pendingRegistrations').text(response.stats.pending || 0);
                        $('#approvedRegistrations').text(response.stats.approved || 0);
                        $('#rejectedRegistrations').text(response.stats.rejected || 0);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Statistics load error:', xhr, status, error);
                    console.error('Response:', xhr.responseText);
                }
            });
        }

        function setupEventListeners() {
            // Sự kiện thay đổi bộ lọc
            $('#statusFilter, #dateFrom, #dateTo').on('change', function() {
                applyFilters();
            });

            // Nút xác nhận modal hành động
            $('#confirmActionBtn').on('click', function() {
                const action = $('#actionType').val();
                const id = $('#registrationId').val();
                const note = $('#actionNote').val();

                if (action === 'approve') {
                    approveRegistration(id, note);
                } else if (action === 'reject') {
                    rejectRegistration(id, note);
                }
            });
        }

        function applyFilters() {
            const statusFilter = $('#statusFilter').val();
            const dateFrom = $('#dateFrom').val();
            const dateTo = $('#dateTo').val();
            
            console.log('Applying filters:', { statusFilter, dateFrom, dateTo });
            
            // Xóa tất cả bộ lọc hiện có trước
            registrationsTable.columns().search('');
            
            // Áp dụng bộ lọc trạng thái (cột 7 - TrangThaiDuyet, đã dịch chuyển do cột TongTien mới)
            if (statusFilter) {
                registrationsTable.column(7).search(statusFilter);
            }
            
            // Áp dụng bộ lọc ngày (logic tùy chỉnh cho khoảng ngày)
            if (dateFrom || dateTo) {
                // Xóa bộ lọc ngày hiện có trước
                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                    return fn.toString().indexOf('dateFrom') === -1 && fn.toString().indexOf('dateTo') === -1;
                });
                
                // Thêm bộ lọc ngày mới
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'registrationsTable') return true;
                    
                    // Lấy ngày từ cột 5 (NgayBatDau, đã dịch chuyển do cột TongTien mới)
                    const rowDateStr = data[5];
                    if (!rowDateStr) return true;
                    
                    try {
                        // Parse ngày (định dạng: dd/mm/yyyy hh:mm)
                        const dateParts = rowDateStr.split(' ')[0].split('/');
                        if (dateParts.length !== 3) return true;
                        
                        const rowDate = new Date(parseInt(dateParts[2]), parseInt(dateParts[1]) - 1, parseInt(dateParts[0]));
                        const fromDate = dateFrom ? new Date(dateFrom) : null;
                        const toDate = dateTo ? new Date(dateTo) : null;
                        
                        // Đặt thời gian về đầu/cuối ngày để so sánh
                        if (fromDate) fromDate.setHours(0, 0, 0, 0);
                        if (toDate) toDate.setHours(23, 59, 59, 999);
                        rowDate.setHours(0, 0, 0, 0);
                        
                        if (fromDate && rowDate < fromDate) return false;
                        if (toDate && rowDate > toDate) return false;
                        
                        return true;
                    } catch (e) {
                        console.error('Date parsing error:', e);
                        return true;
                    }
                });
            }
            
            // Vẽ lại bảng
            registrationsTable.draw();
        }

        function clearFilters() {
            $('#statusFilter').val('');
            $('#dateFrom').val('');
            $('#dateTo').val('');
            
            // Xóa tất cả bộ lọc DataTable
            registrationsTable.columns().search('');
            registrationsTable.order([0, 'desc']).draw();
            
            // Xóa tất cả bộ lọc ngày tùy chỉnh
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                return fn.toString().indexOf('dateFrom') === -1 && fn.toString().indexOf('dateTo') === -1;
            });
        }

        function viewRegistration(id) {
            $('#viewModalBody').html('<div class="text-center"><div class="spinner-border" role="status"></div><p>Đang tải...</p></div>');
            
            const modal = new bootstrap.Modal(document.getElementById('viewModal'));
            modal.show();

            $.ajax({
                url: '../src/controllers/admin-events.php',
                type: 'GET',
                data: { action: 'get_registration_details', id: id },
                dataType: 'json',
                success: function(response) {
                    console.log('View registration response:', response);
                    if (response.success) {
                        $('#viewModalBody').html(response.html);
                    } else {
                        $('#viewModalBody').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                ${response.message || 'Không thể tải chi tiết đăng ký'}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('View registration error:', xhr, status, error);
                    $('#viewModalBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Có lỗi xảy ra khi tải chi tiết đăng ký
                        </div>
                    `);
                }
            });
        }

        function showActionModal(id, action) {
            $('#registrationId').val(id);
            $('#actionType').val(action);
            $('#actionNote').val('');

            const modal = new bootstrap.Modal(document.getElementById('actionModal'));
            const title = action === 'approve' ? 'Duyệt đăng ký' : 'Từ chối đăng ký';
            const icon = action === 'approve' ? 'check' : 'times';
            const btnClass = action === 'approve' ? 'success' : 'danger';

            $('#actionModalTitle').html(`<i class="fas fa-${icon}"></i> ${title}`);
            $('#confirmActionBtn').removeClass('btn-success btn-danger').addClass(`btn-${btnClass}`);
            $('#confirmActionBtn').html(`<i class="fas fa-${icon}"></i> ${title}`);

            modal.show();
        }

        function approveRegistration(id, note = '') {
            $.ajax({
                url: '../src/controllers/admin-events.php',
                type: 'POST',
                data: {
                    action: 'update_registration_status',
                    registration_id: id,
                    status: 'Đã duyệt',
                    note: note
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Approve response:', response);
                    if (response.success) {
                        alert('Đã duyệt đăng ký thành công');
                        bootstrap.Modal.getInstance(document.getElementById('actionModal')).hide();
                        registrationsTable.ajax.reload();
                        loadStatistics();
                    } else {
                        alert('Lỗi: ' + (response.message || 'Có lỗi xảy ra khi duyệt đăng ký'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Approve error:', xhr, status, error);
                    alert('Có lỗi xảy ra khi duyệt đăng ký');
                }
            });
        }

        function rejectRegistration(id, note = '') {
            $.ajax({
                url: '../src/controllers/admin-events.php',
                type: 'POST',
                data: {
                    action: 'update_registration_status',
                    registration_id: id,
                    status: 'Từ chối',
                    note: note
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Reject response:', response);
                    if (response.success) {
                        alert('Đã từ chối đăng ký thành công');
                        bootstrap.Modal.getInstance(document.getElementById('actionModal')).hide();
                        registrationsTable.ajax.reload();
                        loadStatistics();
                    } else {
                        alert('Lỗi: ' + (response.message || 'Có lỗi xảy ra khi từ chối đăng ký'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Reject error:', xhr, status, error);
                    alert('Có lỗi xảy ra khi từ chối đăng ký');
                }
            });
        }

        

        function exportData(format) {
            if (format === 'csv') {
                registrationsTable.button('.buttons-csv').trigger();
            }
        }

        // Bảng đơn giản dự phòng
        function loadSimpleTable() {
            console.log('Loading simple table fallback');
            $.ajax({
                url: '../src/controllers/admin-events.php',
                type: 'GET',
                data: { action: 'get_registrations' },
                dataType: 'json',
                success: function(response) {
                    console.log('Simple table response:', response);
                    if (response.success && response.registrations) {
                        let html = '';
                        response.registrations.forEach(function(reg) {
                            const startDate = new Date(reg.NgayBatDau).toLocaleDateString('vi-VN');
                            const endDate = new Date(reg.NgayKetThuc).toLocaleDateString('vi-VN');
                            const createDate = new Date(reg.NgayTao).toLocaleDateString('vi-VN');
                            
                            html += `
                                <tr>
                                    <td class="text-center">${reg.ID_DatLich}</td>
                                    <td><strong>${reg.TenSuKien}</strong><br><small class="text-muted">${reg.TenLoai}</small></td>
                                    <td>${reg.HoTen || 'N/A'}</td>
                                    <td>${reg.TenDiaDiem || 'N/A'}</td>
                                    <td>
                                        ${reg.TongTien && reg.TongTien > 0 ? 
                                            `<strong class="text-success">${new Intl.NumberFormat('vi-VN').format(reg.TongTien)} VNĐ</strong>` : 
                                            '<span class="text-muted">Chưa có</span>'
                                        }
                                    </td>
                                    <td>${startDate}</td>
                                    <td>${endDate}</td>
                                    <td><span class="badge bg-${reg.TrangThaiDuyet === 'Đã duyệt' ? 'success' : (reg.TrangThaiDuyet === 'Từ chối' ? 'danger' : 'warning')}">${reg.TrangThaiDuyet}</span></td>
                                    <td>${createDate}</td>
                                    <td>
                                        <button class="btn btn-info btn-sm" onclick="viewRegistration(${reg.ID_DatLich})" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        ${reg.TrangThaiDuyet === 'Chờ duyệt' && (userRole == 1 || userRole == 2) ? `
                                            <button class="btn btn-success btn-sm" onclick="showActionModal(${reg.ID_DatLich}, 'approve')" title="Duyệt">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="showActionModal(${reg.ID_DatLich}, 'reject')" title="Từ chối">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        ` : ''}
                                    </td>
                                </tr>
                            `;
                        });
                        
                        if (html === '') {
                            html = '<tr><td colspan="10" class="text-center">Không có dữ liệu</td></tr>';
                        }
                        
                        $('#registrationsTable tbody').html(html);
                    } else {
                        $('#registrationsTable tbody').html('<tr><td colspan="10" class="text-center">Không có dữ liệu</td></tr>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Simple table error:', xhr, status, error);
                    $('#registrationsTable tbody').html('<tr><td colspan="10" class="text-center text-danger">Lỗi khi tải dữ liệu</td></tr>');
                }
            });
        }

        // Tự động làm mới mỗi 30 giây
        setInterval(() => {
            loadStatistics();
        }, 30000);
    </script>

<?php include 'includes/admin-footer.php'; ?>