<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and has role 2 or 3 (Quản lý tổ chức hoặc Quản lý sự kiện)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['ID_Role'], [2, 3])) {
    header('Location: ../login.php');
    exit;
}

// Get page data from controller
$approvedEvents = [];
$existingPlans = [];
$debugInfo = [];

try {
    $pdo = getDBConnection();
    
    
    // Get approved events
    $stmt = $pdo->query("
        SELECT 
            dl.ID_DatLich,
            dl.TenSuKien,
            dl.NgayBatDau,
            dl.NgayKetThuc,
            dl.SoNguoiDuKien,
            dl.NganSach,
            dl.TrangThaiDuyet,
            COALESCE(dd.TenDiaDiem, 'Chưa xác định') as TenDiaDiem,
            COALESCE(dd.DiaChi, 'Chưa xác định') as DiaChi,
            COALESCE(ls.TenLoai, 'Chưa phân loại') as TenLoaiSK,
            COALESCE(kh.HoTen, 'Chưa có thông tin') as TenKhachHang,
            COALESCE(kh.SoDienThoai, 'Chưa có') as SoDienThoai
        FROM datlichsukien dl
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
        LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE dl.TrangThaiDuyet = 'Đã duyệt'
        ORDER BY dl.NgayBatDau ASC
    ");
    $approvedEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Auto-approve events if none exist
    if (empty($approvedEvents)) {
        try {
            $stmt = $pdo->query("SELECT ID_DatLich, TenSuKien FROM datlichsukien WHERE TrangThaiDuyet != 'Đã duyệt' LIMIT 3");
            $eventsToApprove = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($eventsToApprove)) {
                $pdo->beginTransaction();
                foreach ($eventsToApprove as $event) {
                    $updateStmt = $pdo->prepare("UPDATE datlichsukien SET TrangThaiDuyet = 'Đã duyệt' WHERE ID_DatLich = ?");
                    $updateStmt->execute([$event['ID_DatLich']]);
                }
                $pdo->commit();
                
                // Re-fetch approved events
                $stmt = $pdo->query("
                    SELECT 
                        dl.ID_DatLich,
                        dl.TenSuKien,
                        dl.NgayBatDau,
                        dl.NgayKetThuc,
                        dl.SoNguoiDuKien,
                        dl.NganSach,
                        dl.TrangThaiDuyet,
                        COALESCE(dd.TenDiaDiem, 'Chưa xác định') as TenDiaDiem,
                        COALESCE(dd.DiaChi, 'Chưa xác định') as DiaChi,
                        COALESCE(ls.TenLoai, 'Chưa phân loại') as TenLoaiSK,
                        COALESCE(kh.HoTen, 'Chưa có thông tin') as TenKhachHang,
                        COALESCE(kh.SoDienThoai, 'Chưa có') as SoDienThoai
                    FROM datlichsukien dl
                    LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
                    LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
                    LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
                    WHERE dl.TrangThaiDuyet = 'Đã duyệt'
                    ORDER BY dl.NgayBatDau ASC
                ");
                $approvedEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Error auto-approving events: " . $e->getMessage());
            $pdo->rollBack();
        }
    }
    
    // Get existing plans
    $stmt = $pdo->query("
        SELECT 
            kht.id_kehoach,
            kht.id_sukien,
            kht.ten_kehoach,
            kht.noidung,
            kht.ngay_batdau,
            kht.ngay_ketthuc,
            kht.trangthai,
            s.ID_DatLich,
            dl.TenSuKien
        FROM kehoachthuchien kht
        LEFT JOIN sukien s ON kht.id_sukien = s.ID_SuKien
        LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
        ORDER BY kht.ngay_batdau ASC
    ");
    $existingPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error in event-planning.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lên kế hoạch thực hiện và phân công - Quản lý sự kiện</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .planning-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .planning-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .event-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        .plan-step {
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
        }
        .step-completed {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .step-in-progress {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        .step-pending {
            border-left-color: #6c757d;
            background: #f8f9fa;
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
        .online-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .online-indicator.online {
            background-color: #28a745;
        }
        .online-indicator.offline {
            background-color: #6c757d;
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
                        <i class="fas fa-calendar-alt"></i>
                        Lên kế hoạch thực hiện và phân công
                    </h1>
                    <p class="page-subtitle">Tạo và quản lý kế hoạch thực hiện cho các sự kiện đã được duyệt</p>
                </div>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?= count($approvedEvents) ?></h3>
                                <p class="text-muted mb-0">Sự kiện đã duyệt</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?= count($existingPlans) ?></h3>
                                <p class="text-muted mb-0">Kế hoạch đã tạo</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?= count(array_filter($existingPlans, function($p) { return $p['trangthai'] == 'Đang thực hiện'; })) ?></h3>
                                <p class="text-muted mb-0">Đang thực hiện</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?= count(array_filter($existingPlans, function($p) { return $p['trangthai'] == 'Hoàn thành'; })) ?></h3>
                                <p class="text-muted mb-0">Hoàn thành</p>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Debug Info (Remove in production) -->
                <?php if (count($approvedEvents) == 0): ?>
                <div class="alert alert-warning mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small>
                                <strong>Debug:</strong> 
                                Tổng sự kiện đã duyệt: <?= count($approvedEvents) ?> | 
                                Tổng kế hoạch: <?= count($existingPlans) ?>
                                <?php if (!empty($debugInfo['status_counts'])): ?>
                                <br>Trạng thái sự kiện: 
                                <?php foreach ($debugInfo['status_counts'] as $status): ?>
                                    <?= $status['TrangThaiDuyet'] ?>: <?= $status['count'] ?> |
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="testDatabaseConnection()">
                                <i class="fas fa-database"></i> Test DB
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="approveTestEvents()">
                                <i class="fas fa-check"></i> Approve Events
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="testEditPlan()">
                                <i class="fas fa-edit"></i> Test Edit Plan
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Approved Events List -->
                <?php if (!empty($approvedEvents)): ?>
                <div class="row">
                    <?php foreach ($approvedEvents as $event): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="planning-card">
                            <div class="event-header">
                                <h5 class="mb-2">
                                    <i class="fas fa-calendar-check"></i>
                                    <?= htmlspecialchars($event['TenSuKien']) ?>
                                </h5>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($event['TenDiaDiem']) ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-clock"></i>
                                    <?= date('d/m/Y H:i', strtotime($event['NgayBatDau'])) ?> - 
                                    <?= date('d/m/Y H:i', strtotime($event['NgayKetThuc'])) ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-users"></i>
                                    <?= number_format($event['SoNguoiDuKien']) ?> người
                                </p>
                            </div>
                            
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-primary"><?= htmlspecialchars($event['TenLoaiSK']) ?></span>
                                    <span class="text-muted"><?= number_format($event['NganSach'], 0, ',', '.') ?> VNĐ</span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Khách hàng:</strong> <?= htmlspecialchars($event['TenKhachHang']) ?><br>
                                    <strong>Liên hệ:</strong> <?= htmlspecialchars($event['SoDienThoai']) ?>
                                </div>

                                <!-- Existing Plans for this event -->
                                <?php 
                                $eventPlans = array_filter($existingPlans, function($plan) use ($event) {
                                    return $plan['ID_DatLich'] && $plan['ID_DatLich'] == $event['ID_DatLich'];
                                });
                                
                                // Debug: Check existing plans
                                error_log("Debug for event " . $event['ID_DatLich'] . ":");
                                error_log("Total existing plans: " . count($existingPlans));
                                error_log("Filtered event plans: " . count($eventPlans));
                                if (!empty($existingPlans)) {
                                    error_log("First existing plan: " . json_encode($existingPlans[0]));
                                }
                                if (!empty($eventPlans) && isset($eventPlans[0])) {
                                    error_log("First event plan: " . json_encode($eventPlans[0]));
                                }
                                ?>
                                
                                <?php if (!empty($eventPlans)): ?>
                                <div class="timeline">
                                    <h6 class="mb-3">
                                        <i class="fas fa-tasks"></i>
                                        Kế hoạch thực hiện
                                    </h6>
                                    <?php foreach ($eventPlans as $plan): ?>
                                    <div class="timeline-item">
                                        <div class="plan-step <?= $plan['trangthai'] == 'Hoàn thành' ? 'step-completed' : ($plan['trangthai'] == 'Đang thực hiện' ? 'step-in-progress' : 'step-pending') ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= htmlspecialchars($plan['ten_kehoach']) ?></h6>
                                                    <p class="mb-1 text-muted"><?= htmlspecialchars($plan['noidung']) ?></p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar"></i>
                                                        <?= date('d/m/Y', strtotime($plan['ngay_batdau'])) ?> - 
                                                        <?= date('d/m/Y', strtotime($plan['ngay_ketthuc'])) ?>
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-<?= $plan['trangthai'] == 'Hoàn thành' ? 'success' : ($plan['trangthai'] == 'Đang thực hiện' ? 'warning' : 'secondary') ?>">
                                                        <?= htmlspecialchars($plan['trangthai']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2 mt-3">
                                    <button class="btn btn-primary" onclick="createMainPlan(<?= $event['ID_DatLich'] ?>, '<?= htmlspecialchars($event['TenSuKien'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-plus"></i>
                                        Tạo kế hoạch chính
                                    </button>
                                    <button class="btn btn-outline-success" onclick="manageSteps(<?= $event['ID_DatLich'] ?>, '<?= htmlspecialchars($event['TenSuKien'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-cogs"></i>
                                        Quản lý bước chi tiết
                                    </button>
                                    <?php if (!empty($eventPlans) && isset($eventPlans[0])): ?>
                                    <button class="btn btn-outline-warning" onclick="editPlan(<?= $eventPlans[0]['id_kehoach'] ?>, <?= $event['ID_DatLich'] ?>, '<?= htmlspecialchars($event['TenSuKien'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-edit"></i>
                                        Sửa kế hoạch
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-outline-secondary" disabled>
                                        <i class="fas fa-edit"></i>
                                        Chưa có kế hoạch
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h3>Chưa có sự kiện nào được duyệt</h3>
                    <p class="text-muted">Các sự kiện đã được duyệt sẽ hiển thị ở đây để tạo kế hoạch thực hiện.</p>
                    
                    <div class="mt-4">
                        <a href="event-registrations.php" class="btn btn-primary">
                            <i class="fas fa-clipboard-list"></i>
                            Duyệt đăng ký sự kiện
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Main Plan Modal -->
    <div class="modal fade" id="createMainPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i>
                        Tạo kế hoạch chính
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createMainPlanForm">
                        <input type="hidden" id="mainEventId" name="eventId">
                        <div class="mb-3">
                            <label for="mainPlanName" class="form-label">Tên kế hoạch chính *</label>
                            <input type="text" class="form-control" id="mainPlanName" name="planName" required>
                        </div>
                        <div class="mb-3">
                            <label for="mainPlanDescription" class="form-label">Mô tả kế hoạch *</label>
                            <textarea class="form-control" id="mainPlanDescription" name="planDescription" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mainStartDate" class="form-label">Ngày bắt đầu *</label>
                                    <input type="date" class="form-control" id="mainStartDate" name="startDate" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mainStartTime" class="form-label">Giờ bắt đầu *</label>
                                    <input type="time" class="form-control" id="mainStartTime" name="startTime" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mainEndDate" class="form-label">Ngày kết thúc *</label>
                                    <input type="date" class="form-control" id="mainEndDate" name="endDate" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mainEndTime" class="form-label">Giờ kết thúc *</label>
                                    <input type="time" class="form-control" id="mainEndTime" name="endTime" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="mainManager" class="form-label">Người quản lý</label>
                            <select class="form-select" id="mainManager" name="managerId">
                                <option value="">Chọn người quản lý</option>
                                <!-- Manager options will be loaded via AJAX -->
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveMainPlan()">
                        <i class="fas fa-save"></i>
                        Tạo kế hoạch chính
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Plan Modal (Legacy) -->
    <div class="modal fade" id="createPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i>
                        Tạo kế hoạch thực hiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createPlanForm">
                        <input type="hidden" id="eventId" name="eventId">
                        <div class="mb-3">
                            <label for="planName" class="form-label">Tên kế hoạch *</label>
                            <input type="text" class="form-control" id="planName" name="planName" required>
                        </div>
                        <div class="mb-3">
                            <label for="planContent" class="form-label">Nội dung kế hoạch *</label>
                            <textarea class="form-control" id="planContent" name="planContent" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="startDate" class="form-label">Ngày bắt đầu *</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="endDate" class="form-label">Ngày kết thúc *</label>
                                    <input type="date" class="form-control" id="endDate" name="endDate" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="assignedStaff" class="form-label">Phân công nhân viên</label>
                            <select class="form-select" id="assignedStaff" name="assignedStaff">
                                <option value="">Chọn nhân viên</option>
                                <!-- Staff options will be loaded via AJAX -->
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="savePlan()">
                        <i class="fas fa-save"></i>
                        Lưu kế hoạch
                    </button>
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
                        Sửa kế hoạch thực hiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPlanForm">
                        <input type="hidden" id="editPlanId" name="planId">
                        <input type="hidden" id="editEventId" name="eventId">
                        
                        <div class="mb-3">
                            <label for="editPlanName" class="form-label">Tên kế hoạch *</label>
                            <input type="text" class="form-control" id="editPlanName" name="planName" required>
                </div>
                        
                        <div class="mb-3">
                            <label for="editPlanDescription" class="form-label">Mô tả kế hoạch *</label>
                            <textarea class="form-control" id="editPlanDescription" name="planDescription" rows="4" required></textarea>
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
                                    <label for="editStartTime" class="form-label">Giờ bắt đầu *</label>
                                    <input type="time" class="form-control" id="editStartTime" name="startTime" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editEndDate" class="form-label">Ngày kết thúc *</label>
                                    <input type="date" class="form-control" id="editEndDate" name="endDate" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editEndTime" class="form-label">Giờ kết thúc *</label>
                                    <input type="time" class="form-control" id="editEndTime" name="endTime" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editPlanStatus" class="form-label">Trạng thái</label>
                            <select class="form-select" id="editPlanStatus" name="status">
                                <option value="Chưa bắt đầu">Chưa bắt đầu</option>
                                <option value="Đang thực hiện">Đang thực hiện</option>
                                <option value="Hoàn thành">Hoàn thành</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editManager" class="form-label">Người quản lý</label>
                            <select class="form-select" id="editManager" name="managerId">
                                <option value="">Chọn người quản lý</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="updatePlan()">
                        <i class="fas fa-save"></i>
                        Cập nhật kế hoạch
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
                        <i class="fas fa-cogs"></i>
                        Quản lý nhiều bước thực hiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Danh sách bước thực hiện</h6>
                                <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" onclick="refreshSteps()">
                                    <i class="fas fa-sync-alt"></i> Làm mới
                                </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editStep()">
                                        <i class="fas fa-edit"></i> Sửa bước
                                    </button>
                                </div>
                            </div>
                            <div id="stepsList" class="mb-3">
                                <!-- Steps will be loaded here -->
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-plus"></i>
                                        Thêm bước mới
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form id="addStepForm">
                                        <input type="hidden" id="stepEventId" name="eventId">
                                        
                                        <div class="mb-3">
                                            <label for="stepName" class="form-label">Tên bước *</label>
                                            <input type="text" class="form-control" id="stepName" name="stepName" 
                                                   placeholder="VD: Chuẩn bị thiết bị" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="stepContent" class="form-label">Nội dung chi tiết *</label>
                                            <textarea class="form-control" id="stepContent" name="stepContent" 
                                                      rows="3" placeholder="Mô tả chi tiết công việc cần làm..." required></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="stepStartDate" class="form-label">Ngày bắt đầu *</label>
                                                    <input type="date" class="form-control" id="stepStartDate" name="stepStartDate" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="stepStartTime" class="form-label">Giờ bắt đầu *</label>
                                                    <input type="time" class="form-control" id="stepStartTime" name="stepStartTime" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="stepEndDate" class="form-label">Ngày kết thúc *</label>
                                                    <input type="date" class="form-control" id="stepEndDate" name="stepEndDate" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="stepEndTime" class="form-label">Giờ kết thúc *</label>
                                                    <input type="time" class="form-control" id="stepEndTime" name="stepEndTime" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="stepStaff" class="form-label">Nhân viên phụ trách</label>
                                            <select class="form-select" id="stepStaff" name="staffId">
                                                <option value="">Chọn nhân viên (tùy chọn)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="stepPriority" class="form-label">Mức độ ưu tiên</label>
                                            <select class="form-select" id="stepPriority" name="priority">
                                                <option value="Trung bình">Trung bình</option>
                                                <option value="Thấp">Thấp</option>
                                                <option value="Cao">Cao</option>
                                                <option value="Khẩn cấp">Khẩn cấp</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="stepNote" class="form-label">Ghi chú</label>
                                            <textarea class="form-control" id="stepNote" name="note" rows="2" placeholder="Ghi chú thêm (tùy chọn)"></textarea>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-primary" onclick="addStep()">
                                                <i class="fas fa-plus"></i>
                                                Thêm bước
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearStepForm()">
                                                <i class="fas fa-eraser"></i>
                                                Xóa form
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        
        function createMainPlan(eventId, eventName) {
            document.getElementById('mainEventId').value = eventId;
            document.querySelector('#createMainPlanModal .modal-title').innerHTML = 
                '<i class="fas fa-plus-circle"></i> Tạo kế hoạch chính cho: ' + eventName;
            
            // Load manager options
            loadManagerOptions();
            
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('mainStartDate').value = today;
            
            const modal = new bootstrap.Modal(document.getElementById('createMainPlanModal'));
            modal.show();
        }

        function createPlan(eventId, eventName) {
            console.log('Creating plan for eventId:', eventId, 'eventName:', eventName);
            document.getElementById('eventId').value = eventId;
            document.querySelector('#createPlanModal .modal-title').innerHTML = 
                '<i class="fas fa-plus-circle"></i> Tạo kế hoạch cho: ' + eventName;
            
            // Load staff options
            loadStaffOptions();
            
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('startDate').value = today;
            
            // Debug: Check if eventId is set correctly
            console.log('EventId set to:', document.getElementById('eventId').value);
            
            // Clear form fields
            document.getElementById('planName').value = '';
            document.getElementById('planContent').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('assignedStaff').value = '';
            
            // Debug: Check if form fields are accessible
            console.log('Form fields check:');
            console.log('planName element:', document.getElementById('planName'));
            console.log('planContent element:', document.getElementById('planContent'));
            console.log('startDate element:', document.getElementById('startDate'));
            console.log('endDate element:', document.getElementById('endDate'));
            
            const modal = new bootstrap.Modal(document.getElementById('createPlanModal'));
            modal.show();
        }

        function loadManagerOptions() {
            fetch('../../src/controllers/event-planning.php?action=get_staff')
                .then(handleFetchResponse)
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('mainManager');
                        select.innerHTML = '<option value="">Chọn người quản lý</option>';
                        data.staff.forEach(staff => {
                            select.innerHTML += `<option value="${staff.ID_NhanVien}">${staff.HoTen} - ${staff.ChucVu}</option>`;
                        });
                    }
                })
                .catch(error => console.error('Error loading managers:', error));
        }

        function loadStaffOptions() {
            fetch('../../src/controllers/event-planning.php?action=get_staff')
                .then(handleFetchResponse)
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('assignedStaff');
                        select.innerHTML = '<option value="">Chọn nhân viên</option>';
                        data.staff.forEach(staff => {
                            select.innerHTML += `<option value="${staff.ID_NhanVien}">${staff.HoTen} - ${staff.ChucVu}</option>`;
                        });
                    }
                })
                .catch(error => console.error('Error loading staff:', error));
        }

        function saveMainPlan() {
            const form = document.getElementById('createMainPlanForm');
            const formData = new FormData(form);
            formData.append('action', 'create_plan');
            
            // Debug form data
            console.log('Main plan form data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            // Check required fields
            const eventId = formData.get('eventId');
            const planName = formData.get('planName');
            const planDescription = formData.get('planDescription');
            const startDate = formData.get('startDate');
            const startTime = formData.get('startTime');
            const endDate = formData.get('endDate');
            const endTime = formData.get('endTime');
            
            console.log('Main plan validation:');
            console.log('eventId:', eventId);
            console.log('planName:', planName);
            console.log('planDescription:', planDescription);
            console.log('startDate:', startDate);
            console.log('startTime:', startTime);
            console.log('endDate:', endDate);
            console.log('endTime:', endTime);
            
            if (!eventId || !planName || !planDescription || !startDate || !startTime || !endDate || !endTime) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                return;
            }
            
            // Combine date and time
            const startDateTime = startDate + ' ' + startTime;
            const endDateTime = endDate + ' ' + endTime;
            
            // Update form data with combined datetime
            formData.set('startDateTime', startDateTime);
            formData.set('endDateTime', endDateTime);
            
            // Validate dates
            const startDateObj = new Date(formData.get('startDate'));
            const endDateObj = new Date(formData.get('endDate'));
            
            if (endDateObj < startDateObj) {
                alert('Ngày kết thúc phải sau ngày bắt đầu');
                return;
            }

            fetch('../../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Tạo kế hoạch chính thành công');
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createMainPlanModal'));
                    modal.hide();
                    // Reload page to show new plan
                    setTimeout(() => {
                    location.reload();
                    }, 500);
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tạo kế hoạch chính: ' + error.message);
            });
        }

        // Helper function to handle fetch responses
        function handleFetchResponse(response) {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            
            return response.json();
        }

        function savePlan() {
            const form = document.getElementById('createPlanForm');
            const formData = new FormData(form);
            formData.append('action', 'create_plan');
            
            // Debug form data
            console.log('Form data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            // Check if planContent is empty
            const planContent = formData.get('planContent');
            console.log('PlanContent value:', planContent);
            if (!planContent || planContent.trim() === '') {
                alert('Vui lòng nhập nội dung kế hoạch');
                return;
            }
            
            // Check all required fields
            const eventId = formData.get('eventId');
            const planName = formData.get('planName');
            const startDate = formData.get('startDate');
            const endDate = formData.get('endDate');
            
            console.log('Required fields check:');
            console.log('eventId:', eventId);
            console.log('planName:', planName);
            console.log('startDate:', startDate);
            console.log('endDate:', endDate);
            console.log('planContent:', planContent);
            
            if (!eventId || !planName || !startDate || !endDate || !planContent) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc');
                return;
            }
            
            // Validate dates
            const startDateObj = new Date(formData.get('startDate'));
            const endDateObj = new Date(formData.get('endDate'));
            
            if (endDateObj < startDateObj) {
                alert('Ngày kết thúc phải sau ngày bắt đầu');
                return;
            }

            fetch('../../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(handleFetchResponse)
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    alert('Tạo kế hoạch thành công');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tạo kế hoạch: ' + error.message);
            });
        }


        function manageSteps(eventId, eventName) {
            document.getElementById('stepEventId').value = eventId;
            document.querySelector('#manageStepsModal .modal-title').innerHTML = 
                '<i class="fas fa-cogs"></i> Quản lý bước thực hiện: ' + eventName;
            
            // Load existing steps
            loadSteps(eventId);
            
            // Load staff options
            loadStaffOptionsForSteps();
            
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('stepStartDate').value = today;
            
            const modal = new bootstrap.Modal(document.getElementById('manageStepsModal'));
            modal.show();
        }

        function loadSteps(eventId) {
            console.log('Loading steps for event:', eventId);
            fetch(`../../src/controllers/event-planning.php?action=get_event_steps&event_id=${eventId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
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
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6>Bước ${index + 1}: ${step.TenBuoc}</h6>
                                                    <p class="text-muted">${step.MoTa}</p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar"></i>
                                                        ${new Date(step.NgayBatDau).toLocaleDateString('vi-VN')} - 
                                                        ${new Date(step.NgayKetThuc).toLocaleDateString('vi-VN')}
                                                    </small>
                                                    ${step.TenNhanVien ? `<br><small class="text-muted"><i class="fas fa-user"></i> ${step.TenNhanVien}</small>` : ''}
                                                    ${step.GhiChu ? `<br><small class="text-muted"><i class="fas fa-sticky-note"></i> ${step.GhiChu}</small>` : ''}
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-${statusClass}">${step.TrangThai}</span>
                                                    <div class="btn-group-vertical mt-2">
                                                        <button class="btn btn-sm btn-outline-success" onclick="updateStepStatus(${step.ID_ChiTiet}, 'Hoàn thành')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteStep(${step.ID_ChiTiet})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
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
                        console.log('No steps found or error:', data);
                        document.getElementById('stepsList').innerHTML = '<p class="text-muted">Chưa có bước thực hiện nào.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading steps:', error);
                    document.getElementById('stepsList').innerHTML = '<p class="text-danger">Lỗi khi tải danh sách bước thực hiện.</p>';
                });
        }

        function loadStaffOptionsForSteps() {
            fetch('../../src/controllers/event-planning.php?action=get_staff_list')
                .then(handleFetchResponse)
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


        function updateStepStatus(stepId, status) {
            const formData = new FormData();
            formData.append('action', 'update_step_status');
            formData.append('step_id', stepId);
            formData.append('status', status);

            fetch('../../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Cập nhật trạng thái thành công');
                    loadSteps(document.getElementById('stepEventId').value);
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

                fetch('../../src/controllers/event-planning.php', {
                    method: 'POST',
                    body: formData
                })
                .then(handleFetchResponse)
                .then(data => {
                    if (data.success) {
                        alert('Xóa bước thực hiện thành công');
                        loadSteps(document.getElementById('stepEventId').value);
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

        function refreshSteps() {
            const eventId = document.getElementById('stepEventId').value;
            if (eventId) {
                loadSteps(eventId);
            }
        }

        function editStep() {
            // Get all steps from the list
            const stepsList = document.getElementById('stepsList');
            const steps = stepsList.querySelectorAll('.timeline-item');
            
            if (steps.length === 0) {
                alert('Không có bước nào để sửa');
                return;
            }
            
            if (steps.length === 1) {
                // If only one step, edit it directly
                const stepId = steps[0].querySelector('button[onclick*="updateStepStatus"]').getAttribute('onclick').match(/\d+/)[0];
                openEditStepModal(stepId);
                return;
            }
            
            // If multiple steps, show selection modal
            showStepSelectionModal();
        }

        function showStepSelectionModal() {
            // Create step selection modal if it doesn't exist
            let selectionModal = document.getElementById('stepSelectionModal');
            if (!selectionModal) {
                createStepSelectionModal();
                selectionModal = document.getElementById('stepSelectionModal');
            }
            
            // Load steps into selection modal
            loadStepsForSelection();
            
            // Show modal
            const modal = new bootstrap.Modal(selectionModal);
            modal.show();
        }

        function createStepSelectionModal() {
            const modalHtml = `
                <div class="modal fade" id="stepSelectionModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-list"></i> Chọn bước cần sửa
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="stepSelectionList">
                                    <!-- Steps will be loaded here -->
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        function loadStepsForSelection() {
            const eventId = document.getElementById('stepEventId').value;
            if (!eventId) return;
            
            fetch(`../../src/controllers/event-planning.php?action=get_event_steps&event_id=${eventId}`)
                .then(handleFetchResponse)
                .then(data => {
                    if (data.success && data.steps && data.steps.length > 0) {
                        let html = '<div class="list-group">';
                        data.steps.forEach((step, index) => {
                            const statusClass = step.TrangThai === 'Hoàn thành' ? 'success' : 
                                             step.TrangThai === 'Đang thực hiện' ? 'warning' : 'secondary';
                            html += `
                                <div class="list-group-item list-group-item-action" onclick="selectStepForEdit(${step.ID_ChiTiet})">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Bước ${index + 1}: ${step.TenBuoc}</h6>
                                        <span class="badge bg-${statusClass}">${step.TrangThai}</span>
                                    </div>
                                    <p class="mb-1">${step.MoTa}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        ${new Date(step.NgayBatDau).toLocaleDateString('vi-VN')} - 
                                        ${new Date(step.NgayKetThuc).toLocaleDateString('vi-VN')}
                                    </small>
                                    ${step.TenNhanVien ? `<br><small class="text-muted"><i class="fas fa-user"></i> ${step.TenNhanVien}</small>` : ''}
                                </div>
                            `;
                        });
                        html += '</div>';
                        document.getElementById('stepSelectionList').innerHTML = html;
                    } else {
                        document.getElementById('stepSelectionList').innerHTML = '<p class="text-muted">Không có bước nào</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading steps for selection:', error);
                    document.getElementById('stepSelectionList').innerHTML = '<p class="text-danger">Lỗi khi tải danh sách bước</p>';
                });
        }

        function selectStepForEdit(stepId) {
            // Close selection modal
            const selectionModal = bootstrap.Modal.getInstance(document.getElementById('stepSelectionModal'));
            selectionModal.hide();
            
            // Open edit modal for selected step
            openEditStepModal(stepId);
        }

        function openEditStepModal(stepId) {
            // Create edit modal if it doesn't exist
            let editModal = document.getElementById('editStepModal');
            if (!editModal) {
                createEditStepModal();
                editModal = document.getElementById('editStepModal');
            }
            
            // Load step data
            loadStepData(stepId);
            
            // Show modal
            const modal = new bootstrap.Modal(editModal);
            modal.show();
        }

        function createEditStepModal() {
            const modalHtml = `
                <div class="modal fade" id="editStepModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-edit"></i> Sửa bước thực hiện
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editStepForm">
                                    <input type="hidden" id="editStepId" name="stepId">
                                    
                                    <div class="mb-3">
                                        <label for="editStepName" class="form-label">Tên bước</label>
                                        <input type="text" class="form-control" id="editStepName" name="stepName" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="editStepDescription" class="form-label">Mô tả</label>
                                        <textarea class="form-control" id="editStepDescription" name="stepDescription" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="editStepStartDate" class="form-label">Ngày bắt đầu</label>
                                            <input type="date" class="form-control" id="editStepStartDate" name="stepStartDate" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="editStepStartTime" class="form-label">Giờ bắt đầu</label>
                                            <input type="time" class="form-control" id="editStepStartTime" name="stepStartTime" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <label for="editStepEndDate" class="form-label">Ngày kết thúc</label>
                                            <input type="date" class="form-control" id="editStepEndDate" name="stepEndDate" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="editStepEndTime" class="form-label">Giờ kết thúc</label>
                                            <input type="time" class="form-control" id="editStepEndTime" name="stepEndTime" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="editStepStaff" class="form-label">Nhân viên phụ trách</label>
                                        <select class="form-select" id="editStepStaff" name="staffId">
                                            <option value="">Chọn nhân viên</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="editStepNote" class="form-label">Ghi chú</label>
                                        <textarea class="form-control" id="editStepNote" name="note" rows="2"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                <button type="button" class="btn btn-primary" onclick="updateStep()">Cập nhật</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        function loadStepData(stepId) {
            fetch(`../../src/controllers/event-planning.php?action=get_step&step_id=${stepId}`)
                .then(handleFetchResponse)
                .then(data => {
                    if (data.success && data.step) {
                        const step = data.step;
                        
                        // Populate form fields
                        document.getElementById('editStepId').value = step.ID_ChiTiet;
                        document.getElementById('editStepName').value = step.TenBuoc || '';
                        document.getElementById('editStepDescription').value = step.MoTa || '';
                        
                        // Parse datetime fields
                        if (step.NgayBatDau) {
                            const startDate = new Date(step.NgayBatDau);
                            document.getElementById('editStepStartDate').value = startDate.toISOString().split('T')[0];
                            document.getElementById('editStepStartTime').value = startDate.toTimeString().split(' ')[0].substring(0, 5);
                        }
                        
                        if (step.NgayKetThuc) {
                            const endDate = new Date(step.NgayKetThuc);
                            document.getElementById('editStepEndDate').value = endDate.toISOString().split('T')[0];
                            document.getElementById('editStepEndTime').value = endDate.toTimeString().split(' ')[0].substring(0, 5);
                        }
                        
                        document.getElementById('editStepStaff').value = step.ID_NhanVien || '';
                        document.getElementById('editStepNote').value = step.GhiChu || '';
                        
                        // Load staff options
                        loadStaffOptionsForEditStep();
                    } else {
                        alert('Lỗi: ' + (data.error || 'Không thể tải dữ liệu bước'));
                    }
                })
                .catch(error => {
                    console.error('Error loading step data:', error);
                    alert('Có lỗi xảy ra khi tải dữ liệu bước');
                });
        }

        function loadStaffOptionsForEditStep() {
            fetch('../../src/controllers/event-planning.php?action=get_staff_list')
                .then(handleFetchResponse)
                .then(data => {
                    const select = document.getElementById('editStepStaff');
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

        function updateStep() {
            const form = document.getElementById('editStepForm');
            const formData = new FormData(form);
            formData.append('action', 'update_step');
            
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
            
            fetch('../../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Cập nhật bước thành công');
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editStepModal'));
                    modal.hide();
                    // Refresh steps list
                    refreshSteps();
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật bước: ' + error.message);
            });
        }

        function clearStepForm() {
            document.getElementById('addStepForm').reset();
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('stepStartDate').value = today;
        }

        // Override addStep function to include auto-refresh
        function addStep() {
            const form = document.getElementById('addStepForm');
            const formData = new FormData(form);
            formData.append('action', 'add_plan_step');
            
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

            fetch('../../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(handleFetchResponse)
            .then(data => {
                if (data.success) {
                    alert('Thêm bước kế hoạch thành công');
                    form.reset();
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('stepStartDate').value = today;
                    loadSteps(document.getElementById('stepEventId').value);
                } else {
                    alert('Lỗi: ' + (data.error || data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi thêm bước kế hoạch: ' + error.message);
            });
        }

        // Debug functions
        function testDatabaseConnection() {
            fetch('../../src/controllers/event-planning.php?action=get_approved_events')
                .then(handleFetchResponse)
                .then(data => {
                    if (data.success) {
                        alert(`Database OK! Found ${data.events.length} approved events`);
                        console.log('Approved events:', data.events);
                    } else {
                        alert('Database Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Database test error:', error);
                    alert('Database connection failed: ' + error.message);
                });
        }
        
        function testEditPlan() {
            console.log('Testing edit plan function...');
            // Test with dummy data
            editPlan(1, 1, 'Test Event');
        }

        function approveTestEvents() {
            if (confirm('Bạn có muốn approve một số sự kiện để test không?')) {
                fetch('../../src/controllers/event-planning.php?action=auto_approve_events')
                    .then(handleFetchResponse)
                    .then(data => {
                        if (data.success) {
                            alert(`Đã approve ${data.count} sự kiện! Reload trang để xem kết quả.`);
                            location.reload();
                        } else {
                            alert('Lỗi: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi approve sự kiện');
                    });
            }
        }

        function editPlan(planId, eventId, eventName) {
            console.log('=== EDIT PLAN DEBUG ===');
            console.log('planId:', planId, 'type:', typeof planId);
            console.log('eventId:', eventId, 'type:', typeof eventId);
            console.log('eventName:', eventName, 'type:', typeof eventName);
            
            // Validate parameters
            if (!planId || planId === 'undefined' || planId === 'null' || planId === '') {
                console.error('Invalid planId:', planId);
                alert('Lỗi: ID kế hoạch không hợp lệ: ' + planId);
                return;
            }
            
            if (!eventId || eventId === 'undefined' || eventId === 'null' || eventId === '') {
                console.error('Invalid eventId:', eventId);
                alert('Lỗi: ID sự kiện không hợp lệ: ' + eventId);
                return;
            }
            
            // Check if modal exists
            let editModal = document.getElementById('editPlanModal');
            if (!editModal) {
                console.error('Edit plan modal not found!');
                alert('Lỗi: Modal sửa kế hoạch không tồn tại');
                return;
            }
            
            // Check if form fields exist
            const editPlanId = document.getElementById('editPlanId');
            const editEventId = document.getElementById('editEventId');
            const editPlanName = document.getElementById('editPlanName');
            const editPlanDescription = document.getElementById('editPlanDescription');
            const editStartDate = document.getElementById('editStartDate');
            const editStartTime = document.getElementById('editStartTime');
            const editEndDate = document.getElementById('editEndDate');
            const editEndTime = document.getElementById('editEndTime');
            const editPlanStatus = document.getElementById('editPlanStatus');
            const editManager = document.getElementById('editManager');
            
            console.log('Form fields check:');
            console.log('editPlanId:', !!editPlanId);
            console.log('editEventId:', !!editEventId);
            console.log('editPlanName:', !!editPlanName);
            console.log('editPlanDescription:', !!editPlanDescription);
            console.log('editStartDate:', !!editStartDate);
            console.log('editStartTime:', !!editStartTime);
            console.log('editEndDate:', !!editEndDate);
            console.log('editEndTime:', !!editEndTime);
            console.log('editPlanStatus:', !!editPlanStatus);
            console.log('editManager:', !!editManager);
            
            if (!editPlanId || !editEventId || !editPlanName || !editPlanDescription || 
                !editStartDate || !editStartTime || !editEndDate || !editEndTime || 
                !editPlanStatus || !editManager) {
                console.error('Form fields not found!');
                alert('Lỗi: Không tìm thấy các trường form');
                return;
            }
            
            // Set form values
            editPlanId.value = planId;
            editEventId.value = eventId;
            document.querySelector('#editPlanModal .modal-title').innerHTML = 
                '<i class="fas fa-edit"></i> Sửa kế hoạch: ' + eventName;
            
            // Load plan data
            loadPlanData(planId);
            
            // Load manager options
            loadManagerOptionsForEdit();
            
            const modal = new bootstrap.Modal(editModal);
            modal.show();
            
            console.log('Edit plan modal opened for plan:', planId);
        }

        function loadPlanData(planId) {
            console.log('=== LOAD PLAN DATA DEBUG ===');
            console.log('Loading plan data for ID:', planId);
            
            if (!planId || planId === 'undefined' || planId === 'null') {
                console.error('Invalid plan ID:', planId);
                alert('Lỗi: ID kế hoạch không hợp lệ');
                return;
            }
            
            fetch(`../../src/controllers/event-planning.php?action=get_plan&plan_id=${planId}`)
                .then(handleFetchResponse)
                .then(data => {
                    console.log('Plan data response:', data);
                    if (data.success && data.plan) {
                        const plan = data.plan;
                        console.log('Plan object:', plan);
                        
                        // Check if form fields exist
                        const editPlanName = document.getElementById('editPlanName');
                        const editPlanDescription = document.getElementById('editPlanDescription');
                        const editStartDate = document.getElementById('editStartDate');
                        const editStartTime = document.getElementById('editStartTime');
                        const editEndDate = document.getElementById('editEndDate');
                        const editEndTime = document.getElementById('editEndTime');
                        const editPlanStatus = document.getElementById('editPlanStatus');
                        const editManager = document.getElementById('editManager');
                        
                        console.log('Form fields check:');
                        console.log('editPlanName:', !!editPlanName);
                        console.log('editPlanDescription:', !!editPlanDescription);
                        console.log('editStartDate:', !!editStartDate);
                        console.log('editStartTime:', !!editStartTime);
                        console.log('editEndDate:', !!editEndDate);
                        console.log('editEndTime:', !!editEndTime);
                        console.log('editPlanStatus:', !!editPlanStatus);
                        console.log('editManager:', !!editManager);
                        
                        if (!editPlanName || !editPlanDescription || !editStartDate || !editStartTime || !editEndDate || !editEndTime || !editPlanStatus || !editManager) {
                            console.error('Form fields not found!');
                            alert('Lỗi: Không tìm thấy các trường form');
                            return;
                        }
                        
                        // Fill form fields
                        console.log('Filling form fields:');
                        console.log('ten_kehoach:', plan.ten_kehoach);
                        console.log('noidung:', plan.noidung);
                        console.log('trangthai:', plan.trangthai);
                        console.log('id_nhanvien:', plan.id_nhanvien);
                        
                        editPlanName.value = plan.ten_kehoach || '';
                        editPlanDescription.value = plan.noidung || '';
                        
                        console.log('After setting values:');
                        console.log('editPlanName.value:', editPlanName.value);
                        console.log('editPlanDescription.value:', editPlanDescription.value);
                        
                        // Parse datetime
                        console.log('Parsing datetime:');
                        console.log('ngay_batdau:', plan.ngay_batdau);
                        console.log('ngay_ketthuc:', plan.ngay_ketthuc);
                        
                        if (plan.ngay_batdau) {
                            try {
                                // Handle different datetime formats
                                let startDate;
                                if (plan.ngay_batdau.includes(' ')) {
                                    // Format: "2025-10-25 07:00:00"
                                    const [datePart, timePart] = plan.ngay_batdau.split(' ');
                                    editStartDate.value = datePart;
                                    editStartTime.value = timePart.substring(0, 5);
                                } else {
                                    // Format: "2025-10-25" or ISO format
                                    startDate = new Date(plan.ngay_batdau);
                                    editStartDate.value = startDate.toISOString().split('T')[0];
                                    editStartTime.value = '00:00';
                                }
                                console.log('Start date parsed:', editStartDate.value, editStartTime.value);
                            } catch (e) {
                                console.error('Error parsing start date:', e);
                                editStartDate.value = '';
                                editStartTime.value = '00:00';
                            }
                        }
                        
                        if (plan.ngay_ketthuc) {
                            try {
                                // Handle different datetime formats
                                let endDate;
                                if (plan.ngay_ketthuc.includes(' ')) {
                                    // Format: "2025-10-27 07:00:00"
                                    const [datePart, timePart] = plan.ngay_ketthuc.split(' ');
                                    editEndDate.value = datePart;
                                    editEndTime.value = timePart.substring(0, 5);
                                } else {
                                    // Format: "2025-10-27" or ISO format
                                    endDate = new Date(plan.ngay_ketthuc);
                                    editEndDate.value = endDate.toISOString().split('T')[0];
                                    editEndTime.value = '23:59';
                                }
                                console.log('End date parsed:', editEndDate.value, editEndTime.value);
                            } catch (e) {
                                console.error('Error parsing end date:', e);
                                editEndDate.value = '';
                                editEndTime.value = '23:59';
                            }
                        }
                        
                        editPlanStatus.value = plan.trangthai || 'Chưa bắt đầu';
                        
                        console.log('Set form values:');
                        console.log('editPlanStatus.value:', editPlanStatus.value);
                        console.log('editManager.value before load:', editManager.value);
                        
                        // Load staff options for manager dropdown first
                        loadManagerOptionsForEdit().then(() => {
                            // Set manager value after options are loaded
                            editManager.value = plan.id_nhanvien || '';
                            console.log('editManager.value after load:', editManager.value);
                        });
                        
                        console.log('Plan data loaded successfully:', plan);
                    } else {
                        console.error('Failed to load plan data:', data);
                        alert('Lỗi: ' + (data.error || 'Không thể tải dữ liệu kế hoạch'));
                    }
                })
                .catch(error => {
                    console.error('Error loading plan data:', error);
                    alert('Có lỗi xảy ra khi tải dữ liệu kế hoạch: ' + error.message);
                });
        }

        function loadManagerOptionsForEdit() {
            console.log('Loading manager options for edit...');
            return fetch('../../src/controllers/event-planning.php?action=get_staff_list')
                .then(handleFetchResponse)
                .then(data => {
                    console.log('Manager options response:', data);
                    if (data.success && data.staff) {
                        const select = document.getElementById('editManager');
                        if (select) {
                            select.innerHTML = '<option value="">Chọn người quản lý</option>';
                            data.staff.forEach(staff => {
                                select.innerHTML += `<option value="${staff.ID_NhanVien}">${staff.HoTen} - ${staff.ChucVu}</option>`;
                            });
                            console.log('Manager options loaded successfully');
                        } else {
                            console.error('Edit manager select element not found');
                        }
                    } else {
                        console.error('Failed to load manager options:', data);
                    }
                })
                .catch(error => {
                    console.error('Error loading managers:', error);
                });
        }

        function updatePlan() {
            console.log('Starting plan update...');
            const form = document.getElementById('editPlanForm');
            const formData = new FormData(form);
            formData.append('action', 'update_plan');
            
            // Get date and time values
            const startDate = formData.get('startDate');
            const startTime = formData.get('startTime');
            const endDate = formData.get('endDate');
            const endTime = formData.get('endTime');
            
            console.log('Date/Time values:', {startDate, startTime, endDate, endTime});
            
            // Combine date and time
            const startDateTime = startDate + ' ' + startTime;
            const endDateTime = endDate + ' ' + endTime;
            
            console.log('Combined datetime:', {startDateTime, endDateTime});
            
            // Update form data with combined datetime
            formData.set('startDateTime', startDateTime);
            formData.set('endDateTime', endDateTime);
            
            // Validate dates
            const startDateObj = new Date(startDateTime);
            const endDateObj = new Date(endDateTime);
            
            if (endDateObj < startDateObj) {
                alert('Ngày kết thúc phải sau ngày bắt đầu');
                return;
            }
            
            // Debug form data
            console.log('Update plan form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            fetch('../../src/controllers/event-planning.php', {
                method: 'POST',
                body: formData
            })
            .then(handleFetchResponse)
            .then(data => {
                console.log('Update plan response:', data);
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
    </script>
</body>
</html>
