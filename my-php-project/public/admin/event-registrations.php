<?php
// Include admin header
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
                    <button class="btn btn-info" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i> Làm mới
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

    <script>
        let registrationsTable;
        let currentFilters = {};
        let userRole = <?php echo json_encode($_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null); ?>;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeDataTable();
            loadStatistics();
            setupEventListeners();
        });

        function initializeDataTable() {
            // Check if DataTables is available
            if (typeof $.fn.DataTable === 'undefined') {
                console.error('DataTables not available');
                AdminPanel.showError('DataTables không khả dụng');
                return;
            }

            try {
                registrationsTable = $('#registrationsTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '../../src/controllers/admin-events.php',
                        type: 'GET',
                        data: function(d) {
                            d.action = 'get_registrations';
                            return $.extend(d, currentFilters);
                        },
                        dataSrc: function(json) {
                            if (json.success && json.registrations) {
                                return json.registrations;
                            } else {
                                console.error('Invalid data format:', json);
                                return [];
                            }
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTable AJAX Error:', error);
                            AdminPanel.showError('Không thể tải dữ liệu đăng ký sự kiện');
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
                        data: 'NgayBatDau',
                        render: function(data) {
                            return AdminPanel.formatDate(data, 'dd/mm/yyyy hh:mm');
                        }
                    },
                    { 
                        data: 'NgayKetThuc',
                        render: function(data) {
                            return AdminPanel.formatDate(data, 'dd/mm/yyyy hh:mm');
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
                            return AdminPanel.formatDate(data, 'dd/mm/yyyy hh:mm');
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
                            
                            // Only show approve/reject buttons for role 1 and 2
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
                AdminPanel.showError('Lỗi khởi tạo bảng dữ liệu');
            }
        }

        function loadStatistics() {
            AdminPanel.makeAjaxRequest('../../src/controllers/admin-events.php', {
                action: 'get_registration_stats'
            })
            .then(response => {
                if (response.success) {
                    $('#totalRegistrations').text(response.stats.total || 0);
                    $('#pendingRegistrations').text(response.stats.pending || 0);
                    $('#approvedRegistrations').text(response.stats.approved || 0);
                    $('#rejectedRegistrations').text(response.stats.rejected || 0);
                }
            })
            .catch(error => {
                console.error('Statistics load error:', error);
            });
        }

        function setupEventListeners() {
            // Filter change events
            $('#statusFilter, #dateFrom, #dateTo').on('change', function() {
                applyFilters();
            });

            // Action modal confirm button
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
            
            // Clear all existing filters first
            registrationsTable.columns().search('');
            
            // Apply status filter (column 6 - TrangThaiDuyet)
            if (statusFilter) {
                registrationsTable.column(6).search(statusFilter);
            }
            
            // Apply date filters (custom logic for date range)
            if (dateFrom || dateTo) {
                // Remove any existing date filter first
                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                    return fn.toString().indexOf('dateFrom') === -1 && fn.toString().indexOf('dateTo') === -1;
                });
                
                // Add new date filter
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'registrationsTable') return true;
                    
                    // Get the date from column 4 (NgayBatDau)
                    const rowDateStr = data[4];
                    if (!rowDateStr) return true;
                    
                    try {
                        // Parse the date (format: dd/mm/yyyy hh:mm)
                        const dateParts = rowDateStr.split(' ')[0].split('/');
                        if (dateParts.length !== 3) return true;
                        
                        const rowDate = new Date(parseInt(dateParts[2]), parseInt(dateParts[1]) - 1, parseInt(dateParts[0]));
                        const fromDate = dateFrom ? new Date(dateFrom) : null;
                        const toDate = dateTo ? new Date(dateTo) : null;
                        
                        // Set time to start/end of day for comparison
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
            
            // Redraw table
            registrationsTable.draw();
        }

        function clearFilters() {
            $('#statusFilter').val('');
            $('#dateFrom').val('');
            $('#dateTo').val('');
            
            // Clear all DataTable filters
            registrationsTable.columns().search('');
            registrationsTable.order([0, 'desc']).draw();
            
            // Remove all custom date filters
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function(fn) {
                return fn.toString().indexOf('dateFrom') === -1 && fn.toString().indexOf('dateTo') === -1;
            });
        }

        function viewRegistration(id) {
            AdminPanel.showLoading('#viewModalBody');
            
            const modal = new bootstrap.Modal(document.getElementById('viewModal'));
            modal.show();

            AdminPanel.makeAjaxRequest('../../src/controllers/admin-events.php', {
                action: 'get_registration_details',
                id: id
            })
            .then(response => {
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
            })
            .catch(error => {
                $('#viewModalBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Có lỗi xảy ra khi tải chi tiết đăng ký
                    </div>
                `);
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
            const formData = new FormData();
            formData.append('action', 'update_registration_status');
            formData.append('registration_id', id);
            formData.append('status', 'Đã duyệt');
            formData.append('note', note);
            
            AdminPanel.makeAjaxRequest('../../src/controllers/admin-events.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess('Đã duyệt đăng ký thành công');
                    bootstrap.Modal.getInstance(document.getElementById('actionModal')).hide();
                    registrationsTable.ajax.reload();
                    loadStatistics();
                } else {
                    AdminPanel.showError(response.message || 'Có lỗi xảy ra khi duyệt đăng ký');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi duyệt đăng ký');
            });
        }

        function rejectRegistration(id, note = '') {
            const formData = new FormData();
            formData.append('action', 'update_registration_status');
            formData.append('registration_id', id);
            formData.append('status', 'Từ chối');
            formData.append('note', note);
            
            AdminPanel.makeAjaxRequest('../../src/controllers/admin-events.php', formData, 'POST')
            .then(response => {
                if (response.success) {
                    AdminPanel.showSuccess('Đã từ chối đăng ký thành công');
                    bootstrap.Modal.getInstance(document.getElementById('actionModal')).hide();
                    registrationsTable.ajax.reload();
                    loadStatistics();
                } else {
                    AdminPanel.showError(response.message || 'Có lỗi xảy ra khi từ chối đăng ký');
                }
            })
            .catch(error => {
                AdminPanel.showError('Có lỗi xảy ra khi từ chối đăng ký');
            });
        }

        function refreshData() {
            registrationsTable.ajax.reload();
            loadStatistics();
            AdminPanel.showSuccess('Đã làm mới dữ liệu');
        }

        function exportData(format) {
            if (format === 'csv') {
                registrationsTable.button('.buttons-csv').trigger();
            }
        }

        // Auto refresh every 30 seconds
        setInterval(() => {
            loadStatistics();
        }, 30000);
    </script>

<?php include 'includes/admin-footer.php'; ?>