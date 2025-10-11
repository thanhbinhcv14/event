<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công nhân viên - Event Management System</title>
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
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #5a6fd8, #6a4190);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: linear-gradient(45deg, #56ab2f, #a8e6cf);
            border: none;
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #f093fb, #f5576c);
            border: none;
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #ff416c, #ff4b2b);
            border: none;
        }
        
        .btn-info {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            border: none;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .table tbody tr {
            transition: background-color 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .status-assigned {
            background: linear-gradient(45deg, #56ab2f, #a8e6cf);
            color: white;
        }
        
        .status-pending {
            background: linear-gradient(45deg, #f093fb, #f5576c);
            color: white;
        }
        
        .status-completed {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            color: white;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .spinner-border {
            color: #667eea;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .assignment-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .assignment-card h5 {
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .assignment-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            backdrop-filter: blur(10px);
        }
        
        .staff-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .staff-info {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <div class="header-section">
                <h1><i class="fas fa-users-cog"></i> Phân công nhân viên</h1>
                <p>Quản lý và phân công nhân viên thực hiện các sự kiện</p>
            </div>
            
            <div class="content-section">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <label class="form-label"><i class="fas fa-filter"></i> Lọc theo trạng thái</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="Đã phân công">Đã phân công</option>
                                    <option value="Đang thực hiện">Đang thực hiện</option>
                                    <option value="Hoàn thành">Hoàn thành</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <label class="form-label"><i class="fas fa-search"></i> Tìm kiếm</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Tên sự kiện...">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <label class="form-label"><i class="fas fa-calendar"></i> Từ ngày</label>
                                <input type="date" class="form-control" id="fromDate">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <label class="form-label"><i class="fas fa-calendar"></i> Đến ngày</label>
                                <input type="date" class="form-control" id="toDate">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" onclick="loadAssignments()">
                                <i class="fas fa-sync"></i> Tải lại
                            </button>
                            <button class="btn btn-success" onclick="showCreateAssignmentModal()">
                                <i class="fas fa-plus"></i> Tạo phân công mới
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Assignments Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Danh sách phân công</h5>
                    </div>
                    <div class="card-body">
                        <div class="loading" id="loadingSpinner">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Đang tải dữ liệu...</p>
                        </div>
                        
                        <div id="assignmentsList" class="fade-in">
                            <!-- Assignments will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Assignment Modal -->
    <div class="modal fade" id="createAssignmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Tạo phân công nhân viên</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createAssignmentForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sự kiện *</label>
                                    <select class="form-select" id="eventSelect" required>
                                        <option value="">Chọn sự kiện</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nhân viên *</label>
                                    <select class="form-select" id="staffSelect" required>
                                        <option value="">Chọn nhân viên</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ngày bắt đầu *</label>
                                    <input type="date" class="form-control" id="startDate" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ngày kết thúc *</label>
                                    <input type="date" class="form-control" id="endDate" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nhiệm vụ *</label>
                            <textarea class="form-control" id="taskDescription" rows="4" required placeholder="Mô tả nhiệm vụ cụ thể..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="assignmentNotes" rows="3" placeholder="Ghi chú bổ sung..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="createAssignment()">
                        <i class="fas fa-save"></i> Tạo phân công
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Assignment Modal -->
    <div class="modal fade" id="viewAssignmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye"></i> Chi tiết phân công</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="assignmentDetails">
                    <!-- Assignment details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

<script>
let allAssignments = [];
let filteredAssignments = [];
let allEvents = [];
let allStaff = [];

$(document).ready(function() {
    loadAssignments();
    loadEvents();
    loadStaff();
});

// Load assignments
function loadAssignments() {
    $('#loadingSpinner').show();
    $('#assignmentsList').html('');
    
    $.get('../../src/controllers/staff-assignment.php?action=get_assignments', function(data) {
        $('#loadingSpinner').hide();
        if (data.success) {
            allAssignments = data.assignments;
            filteredAssignments = [...allAssignments];
            displayAssignments();
        } else {
            $('#assignmentsList').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Lỗi khi tải dữ liệu: ${data.error}
                </div>
            `);
        }
    }, 'json').fail(function() {
        $('#loadingSpinner').hide();
        $('#assignmentsList').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                Lỗi kết nối. Vui lòng thử lại.
            </div>
        `);
    });
}

// Load events for dropdown
function loadEvents() {
    $.get('../../src/controllers/staff-assignment.php?action=get_events', function(data) {
        if (data.success) {
            allEvents = data.events;
            updateEventSelect();
        }
    }, 'json');
}

// Load staff for dropdown
function loadStaff() {
    $.get('../../src/controllers/staff-assignment.php?action=get_staff', function(data) {
        if (data.success) {
            allStaff = data.staff;
            updateStaffSelect();
        }
    }, 'json');
}

// Update event select dropdown
function updateEventSelect() {
    const select = document.getElementById('eventSelect');
    if (select) {
        select.innerHTML = '<option value="">Chọn sự kiện</option>';
        allEvents.forEach(event => {
            const option = document.createElement('option');
            option.value = event.ID_DatLich;
            option.textContent = `${event.TenSuKien} - ${new Date(event.NgayBatDau).toLocaleDateString('vi-VN')}`;
            select.appendChild(option);
        });
    }
}

// Update staff select dropdown
function updateStaffSelect() {
    const select = document.getElementById('staffSelect');
    if (select) {
        select.innerHTML = '<option value="">Chọn nhân viên</option>';
        allStaff.forEach(staff => {
            const option = document.createElement('option');
            option.value = staff.ID_NhanVien;
            option.textContent = `${staff.HoTen} - ${staff.ChucVu}`;
            select.appendChild(option);
        });
    }
}

// Display assignments
function displayAssignments() {
    if (filteredAssignments.length === 0) {
        $('#assignmentsList').html(`
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Không có phân công nào.
            </div>
        `);
        return;
    }
    
    let html = '<div class="row">';
    filteredAssignments.forEach(assignment => {
        const startDate = new Date(assignment.NgayBatDau).toLocaleDateString('vi-VN');
        const endDate = new Date(assignment.NgayKetThuc).toLocaleDateString('vi-VN');
        
        html += `
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">${assignment.TenSuKien}</h5>
                        <div class="staff-info">
                            <div class="staff-avatar">
                                ${assignment.HoTen.charAt(0)}
                            </div>
                            <div>
                                <strong>${assignment.HoTen}</strong><br>
                                <small class="text-muted">${assignment.ChucVu}</small>
                            </div>
                        </div>
                        <p class="card-text">
                            <strong>Nhiệm vụ:</strong> ${assignment.NhiemVu}<br>
                            <strong>Thời gian:</strong> ${startDate} - ${endDate}<br>
                            <strong>Trạng thái:</strong> <span class="status-badge status-${getStatusClass(assignment.TrangThai)}">${assignment.TrangThai}</span>
                        </p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-info btn-sm" onclick="viewAssignmentDetails(${assignment.ID_LLV})">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="editAssignment(${assignment.ID_LLV})">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteAssignment(${assignment.ID_LLV})">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    $('#assignmentsList').html(html);
}

// Update statistics
function updateStatistics() {
    const total = allAssignments.length;
    const pending = allAssignments.filter(a => a.TrangThai === 'Đã phân công').length;
    const completed = allAssignments.filter(a => a.TrangThai === 'Hoàn thành').length;
    const inProgress = allAssignments.filter(a => a.TrangThai === 'Đang thực hiện').length;
    
    document.getElementById('totalAssignments').textContent = total;
    document.getElementById('pendingAssignments').textContent = pending;
    document.getElementById('completedAssignments').textContent = completed;
    document.getElementById('inProgressAssignments').textContent = inProgress;
}

// Get status class
function getStatusClass(status) {
    switch(status) {
        case 'Đã phân công': return 'status-assigned';
        case 'Đang thực hiện': return 'status-pending';
        case 'Hoàn thành': return 'status-completed';
        default: return 'status-pending';
    }
}

// Show create assignment modal
function showCreateAssignmentModal() {
    $('#createAssignmentModal').modal('show');
}

// Create assignment
function createAssignment() {
    const eventId = document.getElementById('eventSelect').value;
    const staffId = document.getElementById('staffSelect').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const taskDescription = document.getElementById('taskDescription').value;
    const notes = document.getElementById('assignmentNotes').value;
    
    if (!eventId || !staffId || !startDate || !endDate || !taskDescription) {
        showAlert('warning', 'Vui lòng điền đầy đủ thông tin bắt buộc');
        return;
    }
    
    $.ajax({
        url: '../../src/controllers/staff-assignment.php?action=create_assignment',
        method: 'POST',
        data: {
            event_id: eventId,
            staff_id: staffId,
            start_date: startDate,
            end_date: endDate,
            task_description: taskDescription,
            notes: notes
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showAlert('success', 'Tạo phân công thành công!');
                $('#createAssignmentModal').modal('hide');
                loadAssignments();
            } else {
                showAlert('error', 'Lỗi: ' + data.error);
            }
        },
        error: function() {
            showAlert('error', 'Lỗi kết nối. Vui lòng thử lại.');
        }
    });
}

// View assignment details
function viewAssignmentDetails(assignmentId) {
    console.log('View assignment details:', assignmentId);
}

// Edit assignment
function editAssignment(assignmentId) {
    console.log('Edit assignment:', assignmentId);
}

// Delete assignment
function deleteAssignment(assignmentId) {
    if (confirm('Bạn có chắc chắn muốn xóa phân công này?')) {
        $.ajax({
            url: '../../src/controllers/staff-assignment.php?action=delete_assignment',
            method: 'POST',
            data: { assignment_id: assignmentId },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showAlert('success', 'Xóa phân công thành công!');
                    loadAssignments();
                } else {
                    showAlert('error', 'Lỗi: ' + data.error);
                }
            },
            error: function() {
                showAlert('error', 'Lỗi kết nối. Vui lòng thử lại.');
            }
        });
    }
}

// Apply filters
function applyFilters() {
    const statusFilter = document.getElementById('statusFilter').value;
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    
    filteredAssignments = allAssignments.filter(assignment => {
        const matchesStatus = !statusFilter || assignment.TrangThai === statusFilter;
        const matchesFromDate = !fromDate || new Date(assignment.NgayBatDau) >= new Date(fromDate);
        const matchesToDate = !toDate || new Date(assignment.NgayBatDau) <= new Date(toDate);
        
        return matchesStatus && matchesFromDate && matchesToDate;
    });
    
    displayAssignments();
}

// Clear filters
function clearFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('fromDate').value = '';
    document.getElementById('toDate').value = '';
    filteredAssignments = [...allAssignments];
    displayAssignments();
}

// Sort table
function sortTable(columnIndex) {
    console.log('Sort column:', columnIndex);
}

// Export data
function exportData() {
    console.log('Export CSV');
}

// Show alert
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-danger';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    document.querySelectorAll('.alert').forEach(alert => alert.remove());
    
    // Add new alert
    document.querySelector('.main-content').insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) alert.remove();
    }, 5000);
}
</script>

</body>
</html>