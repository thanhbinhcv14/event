<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and has role 2 (Quản lý tổ chức)
if (!isset($_SESSION['user']) || $_SESSION['user']['ID_Role'] != 2) {
    header('Location: login.php');
    exit;
}

// Get all plans with staff assignments
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            kht.id_kehoach,
            kht.ten_kehoach,
            kht.noidung,
            kht.ngay_batdau,
            kht.ngay_ketthuc,
            kht.trangthai,
            kht.id_nhanvien,
            nv.HoTen AS TenNhanVien,
            nv.ChucVu,
            nv.SoDienThoai,
            COALESCE(u.OnlineStatus, 'Offline') AS OnlineStatus,
            dl.TenSuKien,
            dd.TenDiaDiem,
            dd.DiaChi,
            s.ID_SuKien
        FROM kehoachthuchien kht
        LEFT JOIN nhanvieninfo nv ON kht.id_nhanvien = nv.ID_NhanVien
        LEFT JOIN users u ON nv.ID_User = u.ID_User
        LEFT JOIN sukien s ON kht.id_sukien = s.ID_SuKien
        LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        ORDER BY kht.ngay_batdau ASC
    ");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all staff
    $stmt = $pdo->query("
        SELECT 
            nv.ID_NhanVien,
            nv.HoTen,
            nv.ChucVu,
            nv.SoDienThoai,
            u.OnlineStatus
        FROM nhanvieninfo nv
        JOIN users u ON nv.ID_User = u.ID_User
        WHERE u.TrangThai = 'Hoạt động'
        ORDER BY nv.HoTen
    ");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $plans = [];
    $staff = [];
    error_log("Error fetching plans and staff: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân công nhân viên - Quản lý sự kiện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .assignment-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .assignment-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .plan-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        .staff-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .staff-assigned {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .staff-unassigned {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending {
            background: #e9ecef;
            color: #495057;
        }
        .status-in-progress {
            background: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .online-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .online {
            background: #28a745;
        }
        .offline {
            background: #6c757d;
        }
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
            width: 2px;
            background: #667eea;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
        }
        .timeline-item.assigned::before {
            background: #28a745;
        }
        .timeline-item.unassigned::before {
            background: #ffc107;
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
                        <i class="fas fa-users-cog"></i>
                        Phân công nhân viên
                    </h1>
                    <p class="page-subtitle">Quản lý và phân công nhân viên cho các kế hoạch thực hiện</p>
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
                                <h3 class="text-success"><?= count(array_filter($plans, function($p) { return !empty($p['id_nhanvien']); })) ?></h3>
                                <p class="text-muted mb-0">Đã phân công</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?= count(array_filter($plans, function($p) { return empty($p['id_nhanvien']); })) ?></h3>
                                <p class="text-muted mb-0">Chưa phân công</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?= count($staff) ?></h3>
                                <p class="text-muted mb-0">Tổng nhân viên</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Plans List -->
                <div class="row">
                    <?php foreach ($plans as $plan): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="assignment-card">
                            <div class="plan-header">
                                <h5 class="mb-2">
                                    <i class="fas fa-tasks"></i>
                                    <?= htmlspecialchars($plan['ten_kehoach']) ?>
                                </h5>
                                <p class="mb-1">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('d/m/Y', strtotime($plan['ngay_batdau'])) ?> - 
                                    <?= date('d/m/Y', strtotime($plan['ngay_ketthuc'])) ?>
                                </p>
                                <?php if ($plan['TenSuKien']): ?>
                                <p class="mb-0">
                                    <i class="fas fa-calendar-check"></i>
                                    <?= htmlspecialchars($plan['TenSuKien']) ?>
                                </p>
                                <?php endif; ?>
                        </div>
                            
                            <div class="card-body">
                                <div class="mb-3">
                                    <p class="text-muted"><?= htmlspecialchars($plan['noidung']) ?></p>
                </div>
                
                                <?php if ($plan['TenDiaDiem']): ?>
                                <div class="mb-3">
                                    <p class="mb-1">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <strong>Địa điểm:</strong> <?= htmlspecialchars($plan['TenDiaDiem']) ?>
                                    </p>
                                    <p class="mb-0 text-muted"><?= htmlspecialchars($plan['DiaChi']) ?></p>
                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $plan['trangthai'])) ?>">
                                        <?= htmlspecialchars($plan['trangthai']) ?>
                                    </span>
                        </div>
                        
                                <!-- Staff Assignment -->
                                <div class="staff-info <?= $plan['id_nhanvien'] ? 'staff-assigned' : 'staff-unassigned' ?>">
                                    <?php if ($plan['id_nhanvien']): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <span class="online-indicator <?= $plan['OnlineStatus'] == 'Online' ? 'online' : 'offline' ?>"></span>
                                                <?= htmlspecialchars($plan['TenNhanVien']) ?>
                                            </h6>
                                            <p class="mb-1 text-muted">
                                                <i class="fas fa-briefcase"></i>
                                                <?= htmlspecialchars($plan['ChucVu']) ?>
                                            </p>
                                            <p class="mb-0 text-muted">
                                                <i class="fas fa-phone"></i>
                                                <?= htmlspecialchars($plan['SoDienThoai']) ?>
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <button class="btn btn-outline-danger btn-sm" onclick="removeAssignment(<?= $plan['id_kehoach'] ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center">
                                        <p class="mb-2 text-muted">Chưa phân công nhân viên</p>
                                        <button class="btn btn-primary btn-sm" onclick="assignStaff(<?= $plan['id_kehoach'] ?>)">
                                            <i class="fas fa-user-plus"></i>
                                            Phân công
                                        </button>
                </div>
                                    <?php endif; ?>
                        </div>
                    </div>
                </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($plans)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <h3>Chưa có kế hoạch nào</h3>
                    <p class="text-muted">Các kế hoạch thực hiện sẽ hiển thị ở đây để phân công nhân viên.</p>
                    <a href="event-planning.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Tạo kế hoạch mới
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Assign Staff Modal -->
    <div class="modal fade" id="assignStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus"></i>
                        Phân công nhân viên
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="assignStaffForm">
                        <input type="hidden" id="planId" name="planId">
                        
                                <div class="mb-3">
                            <label for="staffSelect" class="form-label">Chọn nhân viên *</label>
                            <select class="form-select" id="staffSelect" name="staffId" required>
                                        <option value="">Chọn nhân viên</option>
                                <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['ID_NhanVien'] ?>" data-phone="<?= htmlspecialchars($s['SoDienThoai']) ?>" data-status="<?= $s['OnlineStatus'] ?>">
                                    <?= htmlspecialchars($s['HoTen']) ?> - <?= htmlspecialchars($s['ChucVu']) ?>
                                    <?= $s['OnlineStatus'] == 'Online' ? ' (Online)' : ' (Offline)' ?>
                                </option>
                                <?php endforeach; ?>
                                    </select>
                                </div>
                        
                        <div id="staffInfo" class="alert alert-info" style="display: none;">
                            <h6>Thông tin nhân viên:</h6>
                            <p id="staffDetails"></p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveAssignment()">
                        <i class="fas fa-save"></i>
                        Phân công
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
        // Hide page loading overlay when page is fully loaded
        window.addEventListener('load', function() {
            const pageLoading = document.getElementById('pageLoading');
            if (pageLoading) {
                pageLoading.style.display = 'none';
            }
        });
        
        // Also hide loading overlay on DOMContentLoaded as fallback
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const pageLoading = document.getElementById('pageLoading');
                if (pageLoading) {
                    pageLoading.style.display = 'none';
                }
            }, 1000); // Hide after 1 second as fallback
        });
        
        function assignStaff(planId) {
            document.getElementById('planId').value = planId;
            
            const modal = new bootstrap.Modal(document.getElementById('assignStaffModal'));
            modal.show();
        }

        function removeAssignment(planId) {
            if (confirm('Bạn có chắc muốn hủy phân công nhân viên này?')) {
                fetch('../src/controllers/staff-assignment.php', {
        method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove_assignment&plan_id=${planId}`
                })
                .then(response => response.json())
                .then(data => {
        if (data.success) {
                        alert('Hủy phân công thành công');
                        location.reload();
            } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi hủy phân công');
        });
    }
}

        function saveAssignment() {
            const form = document.getElementById('assignStaffForm');
            const formData = new FormData(form);
            formData.append('action', 'assign_staff');
            
            fetch('../../src/controllers/staff-assignment.php', {
        method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
            if (data.success) {
                    alert('Phân công nhân viên thành công');
                    location.reload();
            } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi phân công nhân viên');
            });
        }

        // Show staff info when selected
        document.getElementById('staffSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const staffInfo = document.getElementById('staffInfo');
            const staffDetails = document.getElementById('staffDetails');
            
            if (this.value) {
                const phone = selectedOption.getAttribute('data-phone');
                const status = selectedOption.getAttribute('data-status');
                const name = selectedOption.text.split(' - ')[0];
                
                staffDetails.innerHTML = `
                    <strong>Tên:</strong> ${name}<br>
                    <strong>Số điện thoại:</strong> ${phone}<br>
                    <strong>Trạng thái:</strong> ${status == 'Online' ? 'Đang online' : 'Offline'}
                `;
                staffInfo.style.display = 'block';
                } else {
                staffInfo.style.display = 'none';
            }
        });
</script>
</body>
</html>