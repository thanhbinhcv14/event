<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and has role 1 or 2 (Admin or Quản lý tổ chức)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [1, 2])) {
    header('Location: login.php');
    exit;
}

// Get all plans
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            kht.ID_KeHoach,
            kht.ID_SuKien,
            kht.TenKeHoach,
            kht.NoiDung,
            kht.NgayBatDau,
            kht.NgayKetThuc,
            kht.TrangThai,
            kht.LoaiKeHoach,
            kht.TongSoBuoc,
            kht.SoBuocHoanThanh,
            kht.ID_NhanVien,
            kht.NgayTao,
            nv.HoTen AS TenNhanVien,
            dl.TenSuKien
        FROM kehoachthuchien kht
        LEFT JOIN nhanvieninfo nv ON kht.ID_NhanVien = nv.ID_NhanVien
        LEFT JOIN sukien s ON kht.ID_SuKien = s.ID_SuKien
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
        /* Fix content positioning to avoid header overlap */
        body {
            padding-top: 80px; /* Add padding to account for fixed header */
        }
        
        .container-fluid {
            margin-top: 0;
            padding-top: 20px;
        }
        
        /* Ensure table and content are visible */
        .table-responsive {
            margin-top: 20px;
        }
        
        .page-header {
            margin-top: 0;
            padding-top: 0;
        }
        
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
        
        /* Manage Steps Modal Styles */
        .modal {
            z-index: 1060 !important; /* Ensure modal is above header */
        }
        
        .modal-backdrop {
            display: none !important;
        }
        
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
            padding: 1.5rem;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-body {
            padding: 2rem;
            background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
        }
        
        .modal-footer {
            border-top: 1px solid #f0f0f0;
            padding: 1.5rem;
            border-radius: 0 0 15px 15px;
            background: #f8f9fa;
        }
        
        /* Form Improvements */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        /* Card Styles */
        .card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0;
            padding: 1rem 1.5rem;
        }
        
        .card-header h6 {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
        }
        
        .card-body {
            padding: 1.5rem;
            background: white;
        }
        
        /* Button Styles */
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
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        /* Timeline for Steps */
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 8px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #667eea;
            border: 4px solid white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .timeline-item .card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .timeline-item .card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        /* Badge Styles */
        .badge {
            font-size: 0.8rem;
            padding: 0.6rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding-top: 70px; /* Reduce padding on mobile */
            }
            
            .container-fluid {
                padding-top: 10px;
            }
            
            .modal-dialog {
                margin: 1rem;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .timeline {
                padding-left: 20px;
            }
            
            .timeline::before {
                left: 10px;
            }
            
            .timeline-item::before {
                left: -16px;
            }
        }
        
        /* Additional fixes for header overlap */
        @media (min-width: 769px) {
            .container-fluid {
                margin-left: 250px; /* Account for sidebar width */
                padding-left: 20px;
            }
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
                                <h3 class="text-success"><?= count(array_filter($plans, function($p) { return $p['TrangThai'] == 'Hoàn thành'; })) ?></h3>
                                <p class="text-muted mb-0">Hoàn thành</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?= count(array_filter($plans, function($p) { return $p['TrangThai'] == 'Đang thực hiện'; })) ?></h3>
                                <p class="text-muted mb-0">Đang thực hiện</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?= count(array_filter($plans, function($p) { return $p['TrangThai'] == 'Chưa bắt đầu'; })) ?></h3>
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
                                        <td><?= $plan['ID_KeHoach'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($plan['ten_kehoach']) ?></strong>
                                            <?php if ($plan['TenSuKien']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($plan['TenSuKien']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(substr($plan['NoiDung'], 0, 50)) ?><?= strlen($plan['NoiDung']) > 50 ? '...' : '' ?></td>
                                        <td><?= date('d/m/Y', strtotime($plan['NgayBatDau'])) ?></td>
                                        <td><?= date('d/m/Y', strtotime($plan['NgayKetThuc'])) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $plan['TrangThai'])) ?>">
                                                <?= htmlspecialchars($plan['TrangThai']) ?>
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
                                                <button class="btn btn-warning btn-sm" onclick="editPlan(<?= $plan['ID_KeHoach'] ?>)" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-info btn-sm" onclick="viewPlan(<?= $plan['ID_KeHoach'] ?>)" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-success btn-sm" onclick="manageSteps(<?= $plan['ID_KeHoach'] ?>, '<?= htmlspecialchars($plan['ten_kehoach']) ?>')" title="Quản lý bước">
                                                    <i class="fas fa-cogs"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deletePlan(<?= $plan['ID_KeHoach'] ?>)" title="Xóa">
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

    <!-- Manage Steps Modal -->
    <div class="modal fade" id="manageStepsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-cogs"></i> Quản lý bước thực hiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="stepPlanId" name="planId">
                    
                    <!-- Add Step Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-plus"></i> Thêm bước mới
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="addStepForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="stepName" class="form-label">Tên bước</label>
                                        <input type="text" class="form-control" id="stepName" name="stepName" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="stepStaff" class="form-label">Nhân viên phụ trách</label>
                                        <select class="form-select" id="stepStaff" name="staffId">
                                            <option value="">Chọn nhân viên</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="stepDescription" class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="stepDescription" name="stepDescription" rows="2"></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="stepStartDate" class="form-label">Ngày bắt đầu</label>
                                        <input type="date" class="form-control" id="stepStartDate" name="stepStartDate" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="stepStartTime" class="form-label">Giờ bắt đầu</label>
                                        <input type="time" class="form-control" id="stepStartTime" name="stepStartTime" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="stepEndDate" class="form-label">Ngày kết thúc</label>
                                        <input type="date" class="form-control" id="stepEndDate" name="stepEndDate" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="stepEndTime" class="form-label">Giờ kết thúc</label>
                                        <input type="time" class="form-control" id="stepEndTime" name="stepEndTime" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="stepNote" class="form-label">Ghi chú</label>
                                    <textarea class="form-control" id="stepNote" name="note" rows="2"></textarea>
                                </div>
                                
                                <div class="text-end">
                                    <button type="button" class="btn btn-primary" onclick="addStep()">
                                        <i class="fas fa-plus"></i> Thêm bước
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Steps List -->
                    <div id="stepsList">
                        <!-- Steps will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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
            fetch(`../src/controllers/event-planning.php?action=get_plan&plan_id=${planId}`)
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
                        editPlanId.value = plan.ID_KeHoach;
                        editPlanName.value = plan.ten_kehoach || '';
                        editPlanContent.value = plan.NoiDung || '';
                        editStartDate.value = plan.NgayBatDau ? plan.NgayBatDau.split(' ')[0] : '';
                        editEndDate.value = plan.NgayKetThuc ? plan.NgayKetThuc.split(' ')[0] : '';
                        editStatus.value = plan.TrangThai || 'Chưa bắt đầu';
                        editPlanType.value = plan.LoaiKeHoach || 'Đơn giản';
                        editStaff.value = plan.ID_NhanVien || '';
                        
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
            
            fetch('../src/controllers/event-planning.php', {
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
            fetch('../src/controllers/event-planning.php?action=get_staff_list')
                .then(response => {
                    console.log('Staff response status:', response.status);
                    console.log('Staff response headers:', response.headers);
                    return response.json();
                })
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
                
                fetch('../src/controllers/event-planning.php', {
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

        function manageSteps(planId, planName) {
            console.log('Managing steps for plan:', planId, planName);
            document.getElementById('stepPlanId').value = planId;
            document.querySelector('#manageStepsModal .modal-title').innerHTML = 
                '<i class="fas fa-cogs"></i> Quản lý bước thực hiện: ' + planName;
            
            // Load existing steps
            loadSteps(planId);
            
            // Load staff options
            loadStaffOptionsForSteps();
            
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('stepStartDate').value = today;
            
            const modal = new bootstrap.Modal(document.getElementById('manageStepsModal'));
            modal.show();
        }

        function loadSteps(planId) {
            console.log('Loading steps for plan:', planId);
            fetch(`../src/controllers/event-planning.php?action=get_plan_steps&plan_id=${planId}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Steps data:', data);
                    if (data.success && data.steps && data.steps.length > 0) {
                        let html = '<div class="timeline">';
                        data.steps.forEach((step, index) => {
                            const statusClass = step.TrangThai === 'Hoàn thành' ? 'success' : 
                                             step.TrangThai === 'Đang thực hiện' ? 'warning' : 'secondary';
                            html += `
                                <div class="timeline-item">
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <span class="badge bg-primary me-2">Bước ${index + 1}</span>
                                                    ${step.TenBuoc}
                                                </h6>
                                                <span class="badge bg-${statusClass}">${step.TrangThai}</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    ${step.MoTa ? `
                                                    <div class="mb-3">
                                                        <h6 class="text-primary"><i class="fas fa-info-circle"></i> Mô tả chi tiết</h6>
                                                        <p class="text-muted">${step.MoTa}</p>
                                                    </div>
                                                    ` : ''}
                                                    
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <h6 class="text-success mb-2"><i class="fas fa-calendar-alt me-2"></i>Thời gian thực hiện</h6>
                                                            <div class="p-2 bg-light rounded">
                                                                <p class="mb-1">
                                                                    <strong>Bắt đầu:</strong> ${new Date(step.NgayBatDau).toLocaleString('vi-VN')}
                                                                </p>
                                                                <p class="mb-0">
                                                                    <strong>Kết thúc:</strong> ${new Date(step.NgayKetThuc).toLocaleString('vi-VN')}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="text-info mb-2"><i class="fas fa-user-tie me-2"></i>Nhân viên</h6>
                                                            ${step.TenNhanVien ? `
                                                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                                                <div class="flex-grow-1">
                                                                    <p class="mb-1 fw-bold text-dark">${step.TenNhanVien}</p>
                                                                    ${step.ChucVu ? `<p class="mb-0 text-secondary small"><i class="fas fa-briefcase me-1"></i>${step.ChucVu}</p>` : ''}
                                                                </div>
                                                            </div>
                                                            ` : `
                                                            <div class="text-center text-muted p-3 bg-light rounded">
                                                                <i class="fas fa-user-slash fa-2x mb-2 text-muted"></i>
                                                                <p class="mb-1">Chưa phân công nhân viên</p>
                                                                <small>Click "Chỉnh sửa" để phân công</small>
                                                            </div>
                                                            `}
                                                        </div>
                                                    </div>
                                                    
                                                    ${step.GhiChu ? `
                                                    <div class="mb-3">
                                                        <h6 class="text-warning"><i class="fas fa-sticky-note"></i> Ghi chú</h6>
                                                        <div class="alert alert-light">
                                                            <p class="mb-0">${step.GhiChu}</p>
                                                        </div>
                                                    </div>
                                                    ` : ''}
                                                    
                                                    <div class="mb-3">
                                                        <h6 class="text-secondary"><i class="fas fa-info"></i> Thông tin bổ sung</h6>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <small class="text-muted">
                                                                    <strong>ID Bước:</strong> ${step.ID_ChiTiet}
                                                                </small>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <small class="text-muted">
                                                                    <strong>ID Kế hoạch:</strong> ${step.ID_KeHoach}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <div class="text-center">
                                                        <h6 class="text-primary mb-3"><i class="fas fa-cogs me-2"></i>Thao tác</h6>
                                                        <div class="d-grid gap-2">
                                                            <button class="btn btn-outline-success btn-sm" onclick="updateStepStatus(${step.ID_ChiTiet}, 'Hoàn thành')" title="Đánh dấu hoàn thành">
                                                                <i class="fas fa-check me-1"></i>Hoàn thành
                                                            </button>
                                                            <button class="btn btn-outline-warning btn-sm" onclick="updateStepStatus(${step.ID_ChiTiet}, 'Đang thực hiện')" title="Đang thực hiện">
                                                                <i class="fas fa-play me-1"></i>Đang làm
                                                            </button>
                                                            <button class="btn btn-outline-info btn-sm" onclick="editStep(${step.ID_ChiTiet})" title="Chỉnh sửa">
                                                                <i class="fas fa-edit me-1"></i>Chỉnh sửa
                                                            </button>
                                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteStep(${step.ID_ChiTiet})" title="Xóa bước">
                                                                <i class="fas fa-trash me-1"></i>Xóa
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        document.getElementById('stepsList').innerHTML = html;
                    } else {
                        document.getElementById('stepsList').innerHTML = '<p class="text-muted">Chưa có bước thực hiện nào.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading steps:', error);
                    document.getElementById('stepsList').innerHTML = '<p class="text-danger">Lỗi khi tải danh sách bước thực hiện.</p>';
                });
        }

        function loadStaffOptionsForSteps() {
            fetch('../src/controllers/event-planning.php?action=get_staff_list')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('stepStaff');
                    select.innerHTML = '<option value="">Chọn nhân viên</option>';
                    
                    if (data.success && data.staff) {
                        data.staff.forEach(staff => {
                            const option = document.createElement('option');
                            option.value = staff.ID_NhanVien;
                            option.textContent = staff.HoTen + ' - ' + staff.ChucVu;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading staff:', error));
        }

        function addStep() {
            const form = document.getElementById('addStepForm');
            const formData = new FormData(form);
            formData.append('action', 'add_plan_step');
            formData.append('planId', document.getElementById('stepPlanId').value);
            
            // Get date and time values
            const startDate = formData.get('stepStartDate');
            const startTime = formData.get('stepStartTime');
            const endDate = formData.get('stepEndDate');
            const endTime = formData.get('stepEndTime');
            
            // Combine date and time
            const startDateTime = startDate + ' ' + startTime;
            const endDateTime = endDate + ' ' + endTime;
            
            // Update form data with combined datetime
            formData.set('stepStartDateTime', startDateTime);
            formData.set('stepEndDateTime', endDateTime);
            
            // Validate dates
            const startDateObj = new Date(startDateTime);
            const endDateObj = new Date(endDateTime);
            
            if (endDateObj < startDateObj) {
                alert('Ngày kết thúc phải sau ngày bắt đầu');
                return;
            }

            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thêm bước kế hoạch thành công');
                    form.reset();
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('stepStartDate').value = today;
                    loadSteps(document.getElementById('stepPlanId').value);
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thêm bước kế hoạch: ' + error.message);
            });
        }

        function updateStepStatus(stepId, status) {
            const formData = new FormData();
            formData.append('action', 'update_step_status');
            formData.append('step_id', stepId);
            formData.append('status', status);

            fetch('../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cập nhật trạng thái thành công');
                    loadSteps(document.getElementById('stepPlanId').value);
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái: ' + error.message);
            });
        }

        function deleteStep(stepId) {
            if (confirm('Bạn có chắc muốn xóa bước thực hiện này?')) {
                const formData = new FormData();
                formData.append('action', 'delete_step');
                formData.append('step_id', stepId);

                fetch('../src/controllers/event-planning.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Xóa bước thực hiện thành công');
                        loadSteps(document.getElementById('stepPlanId').value);
                    } else {
                        alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi xóa bước thực hiện: ' + error.message);
                });
            }
        }
        
        // Auto refresh steps every 30 seconds to sync with staff updates
        setInterval(function() {
            const currentPlanId = document.getElementById('stepPlanId')?.value;
            if (currentPlanId) {
                console.log('Auto-refreshing steps for plan:', currentPlanId);
                loadSteps(currentPlanId);
            }
        }, 30000);
    </script>
</body>
</html>
