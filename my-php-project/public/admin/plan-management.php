<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and has role 1 or 2 (Admin or Quản lý tổ chức)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2])) {
    header('Location: ../login.php');
    exit;
}

// Get all plans
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            kht.id_kehoach,
            kht.id_sukien,
            kht.ten_kehoach,
            kht.NoiDung,
            kht.ngay_batdau,
            kht.ngay_ketthuc,
            kht.trangthai,
            kht.LoaiKeHoach,
            kht.TongSoBuoc,
            kht.SoBuocHoanThanh,
            kht.ID_NhanVien,
            kht.ngay_tao,
            nv.HoTen AS TenNhanVien,
            dl.TenSuKien
        FROM kehoachthuchien kht
        LEFT JOIN nhanvieninfo nv ON kht.ID_NhanVien = nv.ID_NhanVien
        LEFT JOIN sukien s ON kht.id_sukien = s.ID_SuKien
        LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
        ORDER BY kht.ngay_tao DESC
    ");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all staff for dropdown
    $stmt = $pdo->query("
        SELECT 
            nv.ID_NhanVien,
            nv.HoTen,
            nv.ChucVu
        FROM nhanvieninfo nv
        JOIN users u ON nv.ID_User = u.ID_User
        WHERE u.TrangThai = 'Hoạt động'
        ORDER BY nv.HoTen
    ");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $plans = [];
    $staff = [];
    error_log("Error fetching plans: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý kế hoạch thực hiện - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .plan-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .plan-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 500;
        }
        .status-chưa-bắt-đầu { background-color: #f8f9fa; color: #6c757d; }
        .status-đang-thực-hiện { background-color: #fff3cd; color: #856404; }
        .status-hoàn-thành { background-color: #d4edda; color: #155724; }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .action-buttons .btn {
            padding: 4px 8px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-header mb-4">
                    <h1 class="page-title">
                        <i class="fas fa-tasks"></i>
                        Quản lý kế hoạch thực hiện
                    </h1>
                    <p class="page-subtitle">Quản lý và chỉnh sửa các kế hoạch thực hiện sự kiện</p>
                </div>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?= count($plans) ?></h3>
                                <p class="text-muted mb-0">Tổng kế hoạch</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?= count(array_filter($plans, function($p) { return $p['trangthai'] == 'Hoàn thành'; })) ?></h3>
                                <p class="text-muted mb-0">Hoàn thành</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?= count(array_filter($plans, function($p) { return $p['trangthai'] == 'Đang thực hiện'; })) ?></h3>
                                <p class="text-muted mb-0">Đang thực hiện</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?= count(array_filter($plans, function($p) { return $p['trangthai'] == 'Chưa bắt đầu'; })) ?></h3>
                                <p class="text-muted mb-0">Chưa bắt đầu</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plans Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i>
                            Danh sách kế hoạch thực hiện
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="plansTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên kế hoạch</th>
                                        <th>Nội dung</th>
                                        <th>Ngày bắt đầu</th>
                                        <th>Ngày kết thúc</th>
                                        <th>Trạng thái</th>
                                        <th>Loại kế hoạch</th>
                                        <th>Nhân viên</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plans as $plan): ?>
                                    <tr>
                                        <td><?= $plan['id_kehoach'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($plan['ten_kehoach']) ?></strong>
                                            <?php if ($plan['TenSuKien']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($plan['TenSuKien']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(substr($plan['NoiDung'], 0, 50)) ?><?= strlen($plan['NoiDung']) > 50 ? '...' : '' ?></td>
                                        <td><?= date('d/m/Y', strtotime($plan['ngay_batdau'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($plan['ngay_ketthuc'])) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $plan['trangthai'])) ?>">
                                                <?= htmlspecialchars($plan['trangthai']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($plan['LoaiKeHoach']) ?></td>
                                        <td>
                                            <?php if ($plan['TenNhanVien']): ?>
                                                <?= htmlspecialchars($plan['TenNhanVien']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Chưa phân công</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($plan['ngay_tao'])) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-warning btn-sm" onclick="editPlan(<?= $plan['id_kehoach'] ?>)" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-info btn-sm" onclick="viewPlan(<?= $plan['id_kehoach'] ?>)" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deletePlan(<?= $plan['id_kehoach'] ?>)" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
                        <i class="fas fa-edit"></i>
                        Chỉnh sửa kế hoạch
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPlanForm">
                        <input type="hidden" id="editPlanId" name="planId">
                        
                        <div class="mb-3">
                            <label for="editPlanName" class="form-label">Tên kế hoạch *</label>
                            <input type="text" class="form-control" id="editPlanName" name="planName" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editPlanContent" class="form-label">Nội dung *</label>
                            <textarea class="form-control" id="editPlanContent" name="planContent" rows="4" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editStartDate" class="form-label">Ngày bắt đầu *</label>
                                    <input type="date" class="form-control" id="editStartDate" name="startDate" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editEndDate" class="form-label">Ngày kết thúc *</label>
                                    <input type="date" class="form-control" id="editEndDate" name="endDate" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editStatus" class="form-label">Trạng thái</label>
                                    <select class="form-select" id="editStatus" name="status">
                                        <option value="Chưa bắt đầu">Chưa bắt đầu</option>
                                        <option value="Đang thực hiện">Đang thực hiện</option>
                                        <option value="Hoàn thành">Hoàn thành</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editPlanType" class="form-label">Loại kế hoạch</label>
                                    <select class="form-select" id="editPlanType" name="planType">
                                        <option value="Đơn giản">Đơn giản</option>
                                        <option value="Nhiều bước">Nhiều bước</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStaff" class="form-label">Nhân viên phụ trách</label>
                            <select class="form-select" id="editStaff" name="staffId">
                                <option value="">Chọn nhân viên</option>
                                <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['ID_NhanVien'] ?>"><?= htmlspecialchars($s['HoTen']) ?> - <?= htmlspecialchars($s['ChucVu']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="updatePlan()">
                        <i class="fas fa-save"></i>
                        Cập nhật
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#plansTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                },
                order: [[0, 'desc']],
                pageLength: 25
            });
        });

        function editPlan(planId) {
            console.log('=== EDIT PLAN DEBUG ===');
            console.log('Editing plan ID:', planId);
            
            // Fetch plan data
            fetch(`../../src/controllers/event-planning.php?action=get_plan&plan_id=${planId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.json();
                })
                .then(data => {
                    console.log('Plan data response:', data);
                    if (data.success && data.plan) {
                        const plan = data.plan;
                        console.log('Plan object:', plan);
                        
                        // Check if form fields exist
                        const editPlanId = document.getElementById('editPlanId');
                        const editPlanName = document.getElementById('editPlanName');
                        const editPlanContent = document.getElementById('editPlanContent');
                        const editStartDate = document.getElementById('editStartDate');
                        const editEndDate = document.getElementById('editEndDate');
                        const editStatus = document.getElementById('editStatus');
                        const editPlanType = document.getElementById('editPlanType');
                        const editStaff = document.getElementById('editStaff');
                        
                        console.log('Form fields check:');
                        console.log('editPlanId:', !!editPlanId);
                        console.log('editPlanName:', !!editPlanName);
                        console.log('editPlanContent:', !!editPlanContent);
                        console.log('editStartDate:', !!editStartDate);
                        console.log('editEndDate:', !!editEndDate);
                        console.log('editStatus:', !!editStatus);
                        console.log('editPlanType:', !!editPlanType);
                        console.log('editStaff:', !!editStaff);
                        
                        if (!editPlanId || !editPlanName || !editPlanContent || !editStartDate || !editEndDate || !editStatus || !editPlanType || !editStaff) {
                            console.error('Form fields not found!');
                            alert('Lỗi: Không tìm thấy các trường form');
                            return;
                        }
                        
                        // Fill form
                        editPlanId.value = plan.id_kehoach;
                        editPlanName.value = plan.ten_kehoach || '';
                        editPlanContent.value = plan.noidung || '';
                        editStartDate.value = plan.ngay_batdau ? plan.ngay_batdau.split(' ')[0] : '';
                        editEndDate.value = plan.ngay_ketthuc ? plan.ngay_ketthuc.split(' ')[0] : '';
                        editStatus.value = plan.trangthai || 'Chưa bắt đầu';
                        editPlanType.value = plan.LoaiKeHoach || 'Đơn giản';
                        editStaff.value = plan.id_nhanvien || '';
                        
                        console.log('Form filled successfully');
                        
                        // Load staff options
                        loadStaffOptionsForEdit();
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('editPlanModal'));
                        modal.show();
                        
                        console.log('Edit plan modal opened');
                    } else {
                        console.error('Failed to load plan data:', data);
                        alert('Lỗi: ' + (data.error || 'Không thể tải dữ liệu kế hoạch'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi tải dữ liệu kế hoạch: ' + error.message);
                });
        }

        function updatePlan() {
            console.log('=== UPDATE PLAN DEBUG ===');
            const form = document.getElementById('editPlanForm');
            const formData = new FormData(form);
            formData.append('action', 'update_plan');
            
            // Debug form data
            console.log('Update plan form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            // Validate
            const planName = formData.get('planName');
            const planContent = formData.get('planContent');
            const startDate = formData.get('startDate');
            const endDate = formData.get('endDate');
            
            console.log('Validation check:');
            console.log('planName:', planName);
            console.log('planContent:', planContent);
            console.log('startDate:', startDate);
            console.log('endDate:', endDate);
            
            if (!planName || !planContent || !startDate || !endDate) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                return;
            }
            
            // Combine date and time
            const startDateTime = startDate + ' 00:00:00';
            const endDateTime = endDate + ' 23:59:59';
            
            formData.set('startDateTime', startDateTime);
            formData.set('endDateTime', endDateTime);
            
            console.log('Final form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            fetch('../../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Update response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Update response data:', data);
                if (data.success) {
                    alert('Cập nhật kế hoạch thành công');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật kế hoạch: ' + error.message);
            });
        }

        function viewPlan(planId) {
            alert('Chức năng xem chi tiết đang được phát triển');
        }

        function loadStaffOptionsForEdit() {
            console.log('Loading staff options for edit...');
            fetch('../../src/controllers/event-planning.php?action=get_staff_list')
                .then(response => response.json())
                .then(data => {
                    console.log('Staff options response:', data);
                    if (data.success && data.staff) {
                        const staffSelect = document.getElementById('editStaff');
                        if (staffSelect) {
                            // Clear existing options
                            staffSelect.innerHTML = '<option value="">Chọn nhân viên</option>';
                            
                            // Add staff options
                            data.staff.forEach(staff => {
                                const option = document.createElement('option');
                                option.value = staff.ID_NhanVien;
                                option.textContent = `${staff.HoTen} (${staff.ChucVu})`;
                                staffSelect.appendChild(option);
                            });
                            
                            console.log('Staff options loaded successfully');
                        } else {
                            console.error('Staff select element not found');
                        }
                    } else {
                        console.error('Failed to load staff options:', data);
                    }
                })
                .catch(error => {
                    console.error('Error loading staff options:', error);
                });
        }

        function deletePlan(planId) {
            if (confirm('Bạn có chắc muốn xóa kế hoạch này?')) {
                const formData = new FormData();
                formData.append('action', 'delete_plan');
                formData.append('planId', planId);
                
                fetch('../../src/controllers/event-planning.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Xóa kế hoạch thành công');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi xóa kế hoạch: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>
