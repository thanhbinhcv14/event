<?php
session_start();

// Check if user is logged in and has event manager privileges
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$userRole = $_SESSION['user']['ID_Role'] ?? $_SESSION['user']['role'] ?? null;
if (!in_array($userRole, [1, 2, 3])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sự kiện - Đăng ký thay mặt khách hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        .main-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            padding: 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status-approved { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-rejected { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .action-buttons .btn {
            margin: 0 2px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        
        .equipment-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .equipment-item.selected {
            background: #e3f2fd;
            border-color: #2196f3;
        }
        
        .equipment-checkbox {
            margin-right: 0.5rem;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
    </style>
</head>
<body>
    <!-- Main Header -->
    <div class="main-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-calendar-plus me-3"></i>
                        Đăng ký sự kiện thay mặt khách hàng
                    </h1>
                    <p class="mb-0 mt-2">Quản lý và đăng ký sự kiện cho khách hàng</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i> Quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Registration Form -->
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-plus-circle me-2"></i>
                    Đăng ký sự kiện mới
                </h4>
            </div>
            <div class="card-body">
                <form id="eventRegistrationForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customerSelect" class="form-label">Chọn khách hàng *</label>
                            <select class="form-select" id="customerSelect" required>
                                <option value="">-- Chọn khách hàng --</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="eventName" class="form-label">Tên sự kiện *</label>
                            <input type="text" class="form-control" id="eventName" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="eventDate" class="form-label">Ngày tổ chức *</label>
                            <input type="date" class="form-control" id="eventDate" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="eventTime" class="form-label">Giờ bắt đầu *</label>
                            <input type="time" class="form-control" id="eventTime" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="locationSelect" class="form-label">Địa điểm *</label>
                            <select class="form-select" id="locationSelect" required>
                                <option value="">-- Chọn địa điểm --</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="eventType" class="form-label">Loại sự kiện</label>
                            <select class="form-select" id="eventType">
                                <option value="">-- Chọn loại sự kiện --</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expectedGuests" class="form-label">Số khách dự kiến</label>
                            <input type="number" class="form-control" id="expectedGuests" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="budget" class="form-label">Ngân sách (VNĐ)</label>
                            <input type="number" class="form-control" id="budget" min="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả sự kiện</label>
                        <textarea class="form-control" id="description" rows="3"></textarea>
                    </div>
                    
                    <!-- Equipment Selection -->
                    <div class="mb-4">
                        <label class="form-label">Thiết bị cần thiết</label>
                        <div class="row" id="equipmentList">
                            <!-- Equipment items will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">
                            <i class="fas fa-undo me-2"></i> Làm mới
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Đăng ký sự kiện
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Customer Registrations List -->
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-list-alt me-2"></i>
                    Danh sách đăng ký của khách hàng
                </h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="registrationsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Khách hàng</th>
                                <th>Tên sự kiện</th>
                                <th>Ngày tổ chức</th>
                                <th>Địa điểm</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Registration Details Modal -->
    <div class="modal fade" id="registrationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Chi tiết đăng ký sự kiện
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="registrationDetails">
                    <!-- Details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        let registrationsTable;
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadCustomers();
            loadLocations();
            loadEventTypes();
            loadEquipment();
            initializeDataTable();
            setupFormValidation();
        });
        
        // Load customers
        function loadCustomers() {
            fetch('../../src/controllers/event-manager.php?action=get_customers')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('customerSelect');
                        select.innerHTML = '<option value="">-- Chọn khách hàng --</option>';
                        
                        data.customers.forEach(customer => {
                            const option = document.createElement('option');
                            option.value = customer.ID_KhachHang;
                            option.textContent = `${customer.HoTen} - ${customer.SoDienThoai}`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading customers:', error);
                });
        }
        
        // Load locations
        function loadLocations() {
            fetch('../../src/controllers/event-manager.php?action=get_locations')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('locationSelect');
                        select.innerHTML = '<option value="">-- Chọn địa điểm --</option>';
                        
                        data.locations.forEach(location => {
                            const option = document.createElement('option');
                            option.value = location.ID_DD;
                            option.textContent = location.TenDiaDiem;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading locations:', error);
                });
        }
        
        // Load event types
        function loadEventTypes() {
            fetch('../../src/controllers/event-manager.php?action=get_event_types')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('eventType');
                        select.innerHTML = '<option value="">-- Chọn loại sự kiện --</option>';
                        
                        data.event_types.forEach(type => {
                            const option = document.createElement('option');
                            option.value = type.TenLoai;
                            option.textContent = type.TenLoai;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading event types:', error);
                });
        }
        
        // Load equipment
        function loadEquipment() {
            fetch('../../src/controllers/event-manager.php?action=get_equipment')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const container = document.getElementById('equipmentList');
                        container.innerHTML = '';
                        
                        data.equipment.forEach(equipment => {
                            const col = document.createElement('div');
                            col.className = 'col-md-6 col-lg-4';
                            
                            col.innerHTML = `
                                <div class="equipment-item">
                                    <div class="form-check">
                                        <input class="form-check-input equipment-checkbox" type="checkbox" 
                                               value="${equipment.ID_TB}" id="equipment_${equipment.ID_TB}">
                                        <label class="form-check-label" for="equipment_${equipment.ID_TB}">
                                            <strong>${equipment.TenThietBi}</strong><br>
                                            <small class="text-muted">${equipment.LoaiThietBi} - ${equipment.TrangThai}</small><br>
                                            <small class="text-success">${equipment.GiaThue.toLocaleString()} VNĐ</small>
                                        </label>
                                    </div>
                                </div>
                            `;
                            
                            container.appendChild(col);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading equipment:', error);
                });
        }
        
        // Initialize DataTable
        function initializeDataTable() {
            registrationsTable = $('#registrationsTable').DataTable({
                ajax: {
                    url: '../../src/controllers/event-manager.php',
                    data: { action: 'get_customer_registrations' },
                    dataSrc: 'registrations'
                },
                columns: [
                    { data: 'HoTen' },
                    { data: 'TenSuKien' },
                    { 
                        data: 'NgayBatDau',
                        render: function(data) {
                            return new Date(data).toLocaleDateString('vi-VN');
                        }
                    },
                    { data: 'TenDiaDiem' },
                    { 
                        data: 'TrangThaiDuyet',
                        render: function(data) {
                            const statusMap = {
                                'Chờ duyệt': { class: 'pending', text: 'Chờ duyệt', icon: 'fa-clock' },
                                'Đã duyệt': { class: 'approved', text: 'Đã duyệt', icon: 'fa-check-circle' },
                                'Từ chối': { class: 'rejected', text: 'Từ chối', icon: 'fa-times-circle' }
                            };
                            const status = statusMap[data] || { class: 'pending', text: data, icon: 'fa-question' };
                            return `<span class="status-badge status-${status.class}">
                                        <i class="fas ${status.icon}"></i> ${status.text}
                                    </span>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div class="action-buttons">
                                    <button class="btn btn-info btn-sm" onclick="viewRegistration(${row.ID_DatLich})" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="editRegistration(${row.ID_DatLich})" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteRegistration(${row.ID_DatLich})" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
                }
            });
        }
        
        // Setup form validation
        function setupFormValidation() {
            document.getElementById('eventRegistrationForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitRegistration();
            });
        }
        
        // Submit registration
        function submitRegistration() {
            const formData = new FormData();
            formData.append('action', 'register_for_customer');
            formData.append('customer_id', document.getElementById('customerSelect').value);
            formData.append('event_name', document.getElementById('eventName').value);
            formData.append('event_date', document.getElementById('eventDate').value);
            formData.append('event_time', document.getElementById('eventTime').value);
            formData.append('location_id', document.getElementById('locationSelect').value);
            formData.append('event_type', document.getElementById('eventType').value);
            formData.append('expected_guests', document.getElementById('expectedGuests').value);
            formData.append('budget', document.getElementById('budget').value);
            formData.append('description', document.getElementById('description').value);
            
            // Get selected equipment
            const selectedEquipment = [];
            document.querySelectorAll('.equipment-checkbox:checked').forEach(checkbox => {
                selectedEquipment.push(checkbox.value);
            });
            formData.append('equipment_ids', JSON.stringify(selectedEquipment));
            
            fetch('../../src/controllers/event-manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Đăng ký sự kiện thành công!');
                    resetForm();
                    registrationsTable.ajax.reload();
                } else {
                    alert('Lỗi: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi đăng ký sự kiện');
            });
        }
        
        // Reset form
        function resetForm() {
            document.getElementById('eventRegistrationForm').reset();
            document.querySelectorAll('.equipment-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
        }
        
        // View registration details
        function viewRegistration(id) {
            fetch(`../../src/controllers/event-manager.php?action=get_registration_details&registration_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showRegistrationDetails(data.registration);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Show registration details
        function showRegistrationDetails(registration) {
            const modal = new bootstrap.Modal(document.getElementById('registrationModal'));
            const content = document.getElementById('registrationDetails');
            
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Thông tin sự kiện</h6>
                        <p><strong>Tên sự kiện:</strong> ${registration.TenSuKien}</p>
                        <p><strong>Ngày tổ chức:</strong> ${new Date(registration.NgayBatDau).toLocaleString('vi-VN')}</p>
                        <p><strong>Địa điểm:</strong> ${registration.TenDiaDiem}</p>
                        <p><strong>Loại sự kiện:</strong> ${registration.TenLoai || 'Không xác định'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Thông tin khách hàng</h6>
                        <p><strong>Họ tên:</strong> ${registration.HoTen}</p>
                        <p><strong>Số điện thoại:</strong> ${registration.SoDienThoai}</p>
                        <p><strong>Trạng thái:</strong> ${registration.TrangThaiDuyet}</p>
                    </div>
                </div>
                ${registration.equipment && registration.equipment.length > 0 ? `
                    <div class="mt-3">
                        <h6>Thiết bị đã đặt</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tên thiết bị</th>
                                        <th>Loại</th>
                                        <th>Số lượng</th>
                                        <th>Giá thuê</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${registration.equipment.map(eq => `
                                        <tr>
                                            <td>${eq.TenThietBi}</td>
                                            <td>${eq.LoaiThietBi}</td>
                                            <td>${eq.SoLuong} ${eq.DonViTinh || 'cái'}</td>
                                            <td>${eq.DonGia.toLocaleString()} VNĐ</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ` : ''}
            `;
            
            modal.show();
        }
        
        // Edit registration
        function editRegistration(id) {
            // Implementation for editing registration
            alert('Chức năng chỉnh sửa đang được phát triển');
        }
        
        // Delete registration
        function deleteRegistration(id) {
            if (confirm('Bạn có chắc chắn muốn xóa đăng ký này?')) {
                fetch('../../src/controllers/event-manager.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_registration&registration_id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Xóa đăng ký thành công!');
                        registrationsTable.ajax.reload();
                    } else {
                        alert('Lỗi: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi xóa đăng ký');
                });
            }
        }
    </script>
</body>
</html>
