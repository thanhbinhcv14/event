<?php
require_once 'includes/admin-header.php';

// Check if user has role 2 (Quản lý tổ chức)
if ($user['ID_Role'] != 2) {
    header('Location: index.php');
    exit;
}

// Get approved events
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            dl.ID_DatLich,
            dl.TenSuKien,
            dl.NgayBatDau,
            dl.NgayKetThuc,
            dd.TenDiaDiem,
            dd.DiaChi,
            ls.TenLoai,
            kh.HoTen as TenKhachHang
        FROM datlichsukien dl
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        LEFT JOIN loaisukien ls ON dl.ID_LoaiSK = ls.ID_LoaiSK
        LEFT JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
        WHERE dl.TrangThaiDuyet = 'Đã duyệt'
        ORDER BY dl.NgayBatDau DESC
        LIMIT 10
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    $events = [];
    $staff = [];
    error_log("Error fetching data: " . $e->getMessage());
}
?>

<style>
    .event-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: 1px solid #e9ecef;
        margin-bottom: 15px;
        transition: transform 0.2s ease;
    }
    
    .event-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    
    .step-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        border-left: 4px solid #007bff;
    }
    
    .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    
    .staff-badge {
        background: #e3f2fd;
        color: #1976d2;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-tasks text-primary"></i>
                    Tạo bước và gán nhân viên
                </h2>
                <div class="text-muted">
                    <small>Tạo các bước trong kế hoạch và gán nhân viên thực hiện</small>
                </div>
            </div>
            
            <!-- Events List -->
            <div class="row">
                <?php if (empty($events)): ?>
                    <div class="col-12">
                        <div class="event-card text-center">
                            <i class="fas fa-calendar-times text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">Chưa có sự kiện nào</h5>
                            <p class="text-muted">Vui lòng tạo sự kiện trước khi tạo bước</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="event-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h6 class="mb-0">
                                        <i class="fas fa-calendar-check text-primary"></i>
                                        <?= htmlspecialchars($event['TenSuKien']) ?>
                                    </h6>
                                    <button class="btn btn-sm btn-outline-primary" onclick="showAddStepModal(<?= $event['ID_DatLich'] ?>, '<?= htmlspecialchars($event['TenSuKien']) ?>')">
                                        <i class="fas fa-plus"></i>
                                        Thêm bước
                                    </button>
                                </div>
                                
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y H:i', strtotime($event['NgayBatDau'])) ?> - 
                                        <?= date('d/m/Y H:i', strtotime($event['NgayKetThuc'])) ?>
                                    </small>
                                </div>
                                
                                <?php if ($event['TenDiaDiem']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($event['TenDiaDiem']) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($event['TenLoai']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-tag"></i>
                                        <?= htmlspecialchars($event['TenLoai']) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($event['TenKhachHang']): ?>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($event['TenKhachHang']) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Steps for this event -->
                                <div id="steps-<?= $event['ID_DatLich'] ?>" class="mt-3">
                                    <h6 class="text-muted mb-2">
                                        <i class="fas fa-list"></i>
                                        Các bước đã tạo
                                    </h6>
                                    <div id="steps-list-<?= $event['ID_DatLich'] ?>">
                                        <!-- Steps will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Step Modal -->
<div class="modal fade" id="addStepModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i>
                    Thêm bước mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStepForm">
                    <input type="hidden" id="stepEventId" name="eventId">
                    
                    <div class="mb-3">
                        <label class="form-label">Sự kiện</label>
                        <input type="text" class="form-control" id="stepEventName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="stepName" class="form-label">Tên bước *</label>
                        <input type="text" class="form-control" id="stepName" name="stepName" 
                               placeholder="Ví dụ: Chuẩn bị trang trí, Setup âm thanh..." required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="stepDescription" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="stepDescription" name="stepDescription" rows="3" 
                                  placeholder="Mô tả chi tiết công việc cần thực hiện..."></textarea>
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
                        <select class="form-select" id="stepStaff" name="stepStaff">
                            <option value="">Chọn nhân viên (tùy chọn)</option>
                            <?php foreach ($staff as $s): ?>
                            <option value="<?= $s['ID_NhanVien'] ?>">
                                <?= htmlspecialchars($s['HoTen']) ?> - <?= htmlspecialchars($s['ChucVu']) ?>
                                <?= $s['OnlineStatus'] == 'Online' ? ' (Online)' : ' (Offline)' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Lưu ý:</strong> Khi gán nhân viên, hệ thống sẽ tự động tạo lịch làm việc cho nhân viên đó.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="saveStep()">
                    <i class="fas fa-save"></i>
                    Tạo bước
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Show add step modal
    function showAddStepModal(eventId, eventName) {
        document.getElementById('stepEventId').value = eventId;
        document.getElementById('stepEventName').value = eventName;
        
        // Set default dates
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        document.getElementById('stepStartDate').value = today.toISOString().split('T')[0];
        document.getElementById('stepEndDate').value = tomorrow.toISOString().split('T')[0];
        
        const modal = new bootstrap.Modal(document.getElementById('addStepModal'));
        modal.show();
    }
    
    // Save step function
    function saveStep() {
        const form = document.getElementById('addStepForm');
        const formData = new FormData(form);
        formData.append('action', 'add_event_step');
        
        // Validate required fields
        const stepName = formData.get('stepName');
        const stepStartDate = formData.get('stepStartDate');
        const stepStartTime = formData.get('stepStartTime');
        const stepEndDate = formData.get('stepEndDate');
        const stepEndTime = formData.get('stepEndTime');
        
        if (!stepName || !stepStartDate || !stepStartTime || !stepEndDate || !stepEndTime) {
            alert('Vui lòng điền đầy đủ thông tin bắt buộc');
            return;
        }
        
        // Validate dates
        const startDateTime = new Date(stepStartDate + ' ' + stepStartTime);
        const endDateTime = new Date(stepEndDate + ' ' + stepEndTime);
        
        if (endDateTime <= startDateTime) {
            alert('Thời gian kết thúc phải sau thời gian bắt đầu');
            return;
        }
        
        fetch('../../src/controllers/event-planning.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Tạo bước thành công!' + (data.message.includes('lịch làm việc') ? ' Đã tạo lịch làm việc cho nhân viên.' : ''));
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addStepModal'));
                modal.hide();
                
                // Reload page to show new step
                location.reload();
            } else {
                alert('Lỗi: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tạo bước');
        });
    }
    
    // Load steps for each event
    function loadStepsForEvent(eventId) {
        fetch('../../src/controllers/event-planning.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_event_steps&eventId=' + eventId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const stepsList = document.getElementById('steps-list-' + eventId);
                stepsList.innerHTML = '';
                
                if (data.data.length === 0) {
                    stepsList.innerHTML = '<p class="text-muted small">Chưa có bước nào</p>';
                } else {
                    data.data.forEach(step => {
                        const stepDiv = document.createElement('div');
                        stepDiv.className = 'step-card';
                        stepDiv.innerHTML = `
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${step.TenBuoc}</h6>
                                    <p class="mb-1 text-muted small">${step.MoTa || 'Không có mô tả'}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i>
                                        ${step.NgayBatDau} - ${step.NgayKetThuc}
                                    </small>
                                </div>
                                <div class="text-end">
                                    ${step.HoTen ? `<span class="staff-badge">${step.HoTen}</span>` : '<span class="text-muted small">Chưa gán</span>'}
                                    <br>
                                    <small class="text-muted">${step.TrangThai}</small>
                                </div>
                            </div>
                        `;
                        stepsList.appendChild(stepDiv);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading steps:', error);
        });
    }
    
    // Load steps for all events on page load
    document.addEventListener('DOMContentLoaded', function() {
        <?php foreach ($events as $event): ?>
        loadStepsForEvent(<?= $event['ID_DatLich'] ?>);
        <?php endforeach; ?>
    });
</script>
</body>
</html>
