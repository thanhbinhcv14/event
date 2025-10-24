<?php
require_once 'includes/admin-header.php';

// Check if user has role 4 (Nhân viên)
if ($user['ID_Role'] != 4) {
    header('Location: index.php');
    exit;
}

// Get staff info
try {
    $pdo = getDBConnection();
    $userId = $_SESSION['user']['ID_User'];
    
    $stmt = $pdo->prepare("
        SELECT 
            nv.ID_NhanVien,
            nv.HoTen,
            nv.ChucVu,
            nv.SoDienThoai,
            u.Email
        FROM nhanvieninfo nv
        JOIN users u ON nv.ID_User = u.ID_User
        WHERE nv.ID_User = ?
    ");
    $stmt->execute([$userId]);
    $staffInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staffInfo) {
        header('Location: login.php');
        exit;
    }
    
    // Get assignments from both lichlamviec and chitietkehoach
    $assignments = [];
    
    // First, try to get from lichlamviec
    $stmt = $pdo->prepare("
        SELECT 
            llv.ID_LLV,
            llv.NhiemVu,
            llv.NgayBatDau,
            llv.NgayKetThuc,
            llv.TrangThai,
            llv.GhiChu,
            llv.CongViec,
            llv.HanHoanThanh,
            llv.Tiendo,
            llv.ThoiGianBatDauThucTe,
            llv.ThoiGianKetThucThucTe,
            llv.TienDoPhanTram,
            llv.ThoiGianLamViec,
            llv.ChamTienDo,
            llv.GhiChuTienDo,
            COALESCE(dl.TenSuKien, 'Không xác định') as TenSuKien,
            COALESCE(dl.NgayBatDau, llv.NgayBatDau) as EventStartDate,
            COALESCE(dl.NgayKetThuc, llv.NgayKetThuc) as EventEndDate,
            COALESCE(dd.TenDiaDiem, 'Không xác định') as TenDiaDiem,
            COALESCE(dd.DiaChi, 'Không xác định') as DiaChi,
            COALESCE(kht.ten_kehoach, llv.NhiemVu) as ten_kehoach,
            COALESCE(kht.noidung, llv.GhiChu) as kehoach_noidung,
            COALESCE(kht.trangthai, llv.TrangThai) as kehoach_trangthai,
            'lichlamviec' as source_table
        FROM lichlamviec llv
        LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        LEFT JOIN kehoachthuchien kht ON llv.id_kehoach = kht.id_kehoach
        WHERE llv.ID_NhanVien = ?
        ORDER BY llv.NgayBatDau ASC
    ");
    $stmt->execute([$staffInfo['ID_NhanVien']]);
    $lichlamviec_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Then, try to get from chitietkehoach
    $stmt = $pdo->prepare("
        SELECT 
            ck.ID_ChiTiet as ID_LLV,
            ck.TenBuoc as NhiemVu,
            ck.NgayBatDau,
            ck.NgayKetThuc,
            ck.TrangThai,
            ck.GhiChu,
            ck.TenBuoc as CongViec,
            ck.NgayKetThuc as HanHoanThanh,
            '0' as Tiendo,
            ck.ThoiGianBatDauThucTe,
            ck.ThoiGianKetThucThucTe,
            ck.TienDoPhanTram,
            ck.ThoiGianLamViec,
            ck.ChamTienDo,
            ck.GhiChuTienDo,
            COALESCE(dl.TenSuKien, 'Không xác định') as TenSuKien,
            COALESCE(dl.NgayBatDau, ck.NgayBatDau) as EventStartDate,
            COALESCE(dl.NgayKetThuc, ck.NgayKetThuc) as EventEndDate,
            COALESCE(dd.TenDiaDiem, 'Không xác định') as TenDiaDiem,
            COALESCE(dd.DiaChi, 'Không xác định') as DiaChi,
            COALESCE(kht.ten_kehoach, ck.TenBuoc) as ten_kehoach,
            COALESCE(kht.noidung, ck.MoTa) as kehoach_noidung,
            COALESCE(kht.trangthai, ck.TrangThai) as kehoach_trangthai,
            'chitietkehoach' as source_table
        FROM chitietkehoach ck
        LEFT JOIN kehoachthuchien kht ON ck.id_kehoach = kht.id_kehoach
        LEFT JOIN sukien s ON kht.id_sukien = s.ID_SuKien
        LEFT JOIN datlichsukien dl ON s.ID_DatLich = dl.ID_DatLich
        LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
        WHERE ck.ID_NhanVien = ?
        ORDER BY ck.NgayBatDau ASC
    ");
    $stmt->execute([$staffInfo['ID_NhanVien']]);
    $chitietkehoach_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine both results
    $assignments = array_merge($lichlamviec_assignments, $chitietkehoach_assignments);
    
    // Debug: Log assignments query
    error_log("DEBUG: Staff Schedule - Staff ID: " . $staffInfo['ID_NhanVien']);
    error_log("DEBUG: Staff Schedule - lichlamviec assignments: " . count($lichlamviec_assignments));
    error_log("DEBUG: Staff Schedule - chitietkehoach assignments: " . count($chitietkehoach_assignments));
    error_log("DEBUG: Staff Schedule - Total assignments: " . count($assignments));
    
    // Debug: Log first assignment data
    if (!empty($assignments)) {
        error_log("DEBUG: First assignment data: " . json_encode($assignments[0]));
    }
    
    if (!empty($lichlamviec_assignments)) {
        error_log("DEBUG: Staff Schedule - First lichlamviec assignment: " . json_encode($lichlamviec_assignments[0]));
    }
    if (!empty($chitietkehoach_assignments)) {
        error_log("DEBUG: Staff Schedule - First chitietkehoach assignment: " . json_encode($chitietkehoach_assignments[0]));
    }
    if (!empty($assignments)) {
        error_log("DEBUG: Staff Schedule - First combined assignment: " . json_encode($assignments[0]));
    }
    
} catch (Exception $e) {
    $assignments = [];
    $staffInfo = ['HoTen' => 'Nhân viên', 'ChucVu' => 'Staff'];
    error_log("Error fetching staff assignments: " . $e->getMessage());
    echo "<!-- Error: " . $e->getMessage() . " -->";
}
?>

<style>
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .assignment-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .assignment-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .assignment-header {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px 10px 0 0;
            border-bottom: 1px solid #e0e0e0;
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
        
        .status-issue {
            background: #f8d7da;
            color: #721c24;
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
        
        .timeline-item.completed::before {
            background: #28a745;
        }
        
        .timeline-item.in-progress::before {
            background: #ffc107;
        }
        
        .timeline-item.issue::before {
            background: #dc3545;
        }
        
        .event-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
    </style>

    <!-- Statistics -->
    <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-tasks fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stats-number"><?= count($assignments) ?></div>
                            <div class="stats-label">Tổng công việc</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock fa-2x text-secondary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stats-number">
                                <?= count(array_filter($assignments, function($a) { return $a['TrangThai'] == 'Chưa làm'; })) ?>
                            </div>
                            <div class="stats-label">Chưa làm</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-play fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stats-number">
                                <?= count(array_filter($assignments, function($a) { return $a['TrangThai'] == 'Đang làm'; })) ?>
                            </div>
                            <div class="stats-label">Đang làm</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stats-number">
                                <?= count(array_filter($assignments, function($a) { return $a['TrangThai'] == 'Hoàn thành'; })) ?>
                            </div>
                            <div class="stats-label">Hoàn thành</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments List -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">
                        <i class="fas fa-tasks"></i>
                        Danh sách công việc được phân công
                    </h3>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i>
                            Làm mới
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="viewAllAssignments()">
                            <i class="fas fa-eye"></i>
                            Xem tất cả
                        </button>
                    </div>
                </div>

                <?php if (empty($assignments)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h3>Chưa có công việc nào được phân công</h3>
                    <p class="text-muted">Các công việc được phân công sẽ hiển thị ở đây.</p>
                    <?php if (isset($staffInfo['ID_NhanVien'])): ?>
                    <p class="text-muted">Staff ID: <?= $staffInfo['ID_NhanVien'] ?></p>
                    <?php endif; ?>
                    <div class="alert alert-info mt-3">
                        <small>Debug: Tìm kiếm công việc cho nhân viên ID: <?= $staffInfo['ID_NhanVien'] ?? 'N/A' ?></small>
                    </div>
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($assignments as $assignment): ?>
                    <div class="timeline-item <?= strtolower(str_replace(' ', '-', $assignment['TrangThai'])) ?>">
                        <div class="assignment-card">
                            <div class="assignment-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-2">
                                            <i class="fas fa-tasks"></i>
                                            <?= htmlspecialchars($assignment['NhiemVu']) ?>
                                        </h5>
                                        <p class="mb-1 text-muted">
                                            <i class="fas fa-calendar"></i>
                                            <?= date('d/m/Y H:i', strtotime($assignment['NgayBatDau'])) ?> - 
                                            <?= date('d/m/Y H:i', strtotime($assignment['NgayKetThuc'])) ?>
                                        </p>
                                        <?php if ($assignment['HanHoanThanh']): ?>
                                        <p class="mb-1 text-muted">
                                            <i class="fas fa-clock"></i>
                                            Hạn hoàn thành: <?= date('d/m/Y', strtotime($assignment['HanHoanThanh'])) ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $assignment['TrangThai'])) ?>">
                                            <?= htmlspecialchars($assignment['TrangThai']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Progress Bar -->
                                <?php if ($assignment['Tiendo']): ?>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">Tiến độ</small>
                                        <small class="text-muted"><?= $assignment['Tiendo'] ?></small>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" style="width: <?= $assignment['Tiendo'] ?>"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body">
                                <!-- Event Information -->
                                <div class="event-info">
                                    <h6 class="mb-2">
                                        <i class="fas fa-calendar-check"></i>
                                        Thông tin sự kiện
                                    </h6>
                                    <p class="mb-1">
                                        <strong>Sự kiện:</strong> <?= htmlspecialchars($assignment['TenSuKien']) ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Địa điểm:</strong> <?= htmlspecialchars($assignment['TenDiaDiem']) ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Địa chỉ:</strong> <?= htmlspecialchars($assignment['DiaChi']) ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Thời gian sự kiện:</strong> 
                                        <?= date('d/m/Y H:i', strtotime($assignment['EventStartDate'])) ?> - 
                                        <?= date('d/m/Y H:i', strtotime($assignment['EventEndDate'])) ?>
                                    </p>
                                </div>
                                
                                <!-- Assignment Details -->
                                <?php if ($assignment['GhiChu']): ?>
                                <div class="mb-3">
                                    <h6><i class="fas fa-sticky-note"></i> Ghi chú</h6>
                                    <p class="text-muted"><?= htmlspecialchars($assignment['GhiChu']) ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($assignment['kehoach_noidung']): ?>
                                <div class="mb-3">
                                    <h6><i class="fas fa-clipboard-list"></i> Nội dung kế hoạch</h6>
                                    <p class="text-muted"><?= htmlspecialchars($assignment['kehoach_noidung']) ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Timer and Progress Section -->
                                <?php if ($assignment['TrangThai'] == 'Chưa bắt đầu' || $assignment['TrangThai'] == 'Chưa làm'): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6><i class="fas fa-clock"></i> Bắt đầu làm việc</h6>
                                        <button class="btn btn-success btn-sm" onclick="startWork(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>')">
                                        <i class="fas fa-play"></i>
                                            Bắt đầu làm việc
                                    </button>
                                    </div>
                                </div>
                                <?php elseif ($assignment['TrangThai'] == 'Đang thực hiện' || $assignment['TrangThai'] == 'Đang làm'): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6><i class="fas fa-stopwatch"></i> Đang làm việc</h6>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-info btn-sm" onclick="updateProgress(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>')">
                                        <i class="fas fa-percentage"></i>
                                        Cập nhật tiến độ
                                    </button>
                                            <button class="btn btn-success btn-sm" onclick="completeWork(<?= $assignment['ID_LLV'] ?>, '<?= $assignment['source_table'] ?? 'lichlamviec' ?>')">
                                                <i class="fas fa-check"></i>
                                                Hoàn thành
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Thời gian làm việc thực tế -->
                                    <?php if (isset($assignment['ThoiGianBatDauThucTe']) && $assignment['ThoiGianBatDauThucTe']): ?>
                                    <div class="mt-2">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-play text-success"></i>
                                                    <strong>Bắt đầu:</strong> <?= date('d/m/Y H:i', strtotime($assignment['ThoiGianBatDauThucTe'])) ?>
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock text-info"></i>
                                                    <strong>Đã làm:</strong> 
                                                    <?php
                                                    $startTime = strtotime($assignment['ThoiGianBatDauThucTe']);
                                                    $currentTime = time();
                                                    $workTime = $currentTime - $startTime;
                                                    $hours = floor($workTime / 3600);
                                                    $minutes = floor(($workTime % 3600) / 60);
                                                    echo $hours . 'h ' . $minutes . 'm';
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Tiến độ hiện tại -->
                                    <?php if (isset($assignment['TienDoPhanTram']) && $assignment['TienDoPhanTram'] > 0): ?>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">Tiến độ hiện tại</small>
                                            <small class="text-muted"><?= $assignment['TienDoPhanTram'] ?>%</small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-primary" style="width: <?= $assignment['TienDoPhanTram'] ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Ghi chú tiến độ -->
                                    <?php if (isset($assignment['GhiChuTienDo']) && $assignment['GhiChuTienDo']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-sticky-note"></i>
                                            <strong>Ghi chú:</strong> <?= htmlspecialchars($assignment['GhiChuTienDo']) ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php elseif ($assignment['TrangThai'] == 'Hoàn thành'): ?>
                                <div class="mb-3">
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i>
                                        <strong>Đã hoàn thành</strong>
                                        <?php if (isset($assignment['ChamTienDo']) && $assignment['ChamTienDo']): ?>
                                        <span class="badge bg-warning ms-2">Chậm tiến độ</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Thời gian làm việc hoàn thành -->
                                    <?php if (isset($assignment['ThoiGianBatDauThucTe']) && $assignment['ThoiGianBatDauThucTe']): ?>
                                    <div class="mt-2">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-play text-success"></i>
                                                    <strong>Bắt đầu:</strong> <?= date('d/m/Y H:i', strtotime($assignment['ThoiGianBatDauThucTe'])) ?>
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-stop text-danger"></i>
                                                    <strong>Kết thúc:</strong> 
                                                    <?php if (isset($assignment['ThoiGianKetThucThucTe']) && $assignment['ThoiGianKetThucThucTe']): ?>
                                                        <?= date('d/m/Y H:i', strtotime($assignment['ThoiGianKetThucThucTe'])) ?>
                                                    <?php else: ?>
                                                        Chưa có
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <!-- Tổng thời gian làm việc -->
                                        <?php if (isset($assignment['ThoiGianKetThucThucTe']) && $assignment['ThoiGianKetThucThucTe']): ?>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                <i class="fas fa-clock text-info"></i>
                                                <strong>Tổng thời gian:</strong> 
                                                <?php
                                                $startTime = strtotime($assignment['ThoiGianBatDauThucTe']);
                                                $endTime = strtotime($assignment['ThoiGianKetThucThucTe']);
                                                $totalTime = $endTime - $startTime;
                                                $hours = floor($totalTime / 3600);
                                                $minutes = floor(($totalTime % 3600) / 60);
                                                echo $hours . 'h ' . $minutes . 'm';
                                                ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Tiến độ cuối cùng -->
                                    <?php if (isset($assignment['TienDoPhanTram']) && $assignment['TienDoPhanTram'] > 0): ?>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">Tiến độ cuối cùng</small>
                                            <small class="text-muted"><?= $assignment['TienDoPhanTram'] ?>%</small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: <?= $assignment['TienDoPhanTram'] ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Ghi chú hoàn thành -->
                                    <?php if (isset($assignment['GhiChuTienDo']) && $assignment['GhiChuTienDo']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-sticky-note"></i>
                                            <strong>Ghi chú hoàn thành:</strong> <?= htmlspecialchars($assignment['GhiChuTienDo']) ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="action-buttons">
                                    <button class="btn btn-danger btn-sm" onclick="reportIssue(<?= $assignment['ID_LLV'] ?>)">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Báo sự cố
                                    </button>
                                    
                                    <button class="btn btn-outline-info btn-sm" onclick="viewDetails(<?= $assignment['ID_LLV'] ?>)">
                                        <i class="fas fa-eye"></i>
                                        Chi tiết
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật trạng thái</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateStatusForm">
                        <input type="hidden" id="assignmentId" name="assignmentId">
                        <input type="hidden" id="newStatus" name="newStatus">
                        
                        <div class="mb-3">
                            <label for="progress" class="form-label">Tiến độ (%)</label>
                            <input type="number" class="form-control" id="progress" name="progress" min="0" max="100" value="0">
                        </div>
                        
                        <div class="mb-3">
                            <label for="note" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveStatusUpdate()">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Progress Modal -->
    <div class="modal fade" id="updateProgressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật tiến độ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProgressForm">
                        <input type="hidden" id="progressAssignmentId" name="assignmentId">
                        
                        <div class="mb-3">
                            <label for="progressValue" class="form-label">Tiến độ (%)</label>
                            <input type="number" class="form-control" id="progressValue" name="progress" min="0" max="100" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="progressNote" class="form-label">Ghi chú tiến độ</label>
                            <textarea class="form-control" id="progressNote" name="note" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveProgressUpdate()">Cập nhật</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Issue Modal -->
    <div class="modal fade" id="reportIssueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Báo sự cố</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reportIssueForm">
                        <input type="hidden" id="issueAssignmentId" name="assignmentId">
                        
                        <div class="mb-3">
                            <label for="issueDescription" class="form-label">Mô tả sự cố *</label>
                            <textarea class="form-control" id="issueDescription" name="note" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" onclick="saveIssueReport()">Báo sự cố</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Start Work Modal -->
    <div class="modal fade" id="startWorkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-play"></i>
                        Bắt đầu làm việc
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="startWorkForm">
                        <input type="hidden" id="startWorkId" name="assignmentId">
                        <input type="hidden" id="startWorkTable" name="sourceTable">
                        
                        <div class="mb-3">
                            <label for="startWorkNote" class="form-label">Ghi chú bắt đầu</label>
                            <textarea class="form-control" id="startWorkNote" name="note" rows="3" placeholder="Mô tả công việc sẽ thực hiện..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Lưu ý:</strong> Thời gian bắt đầu sẽ được ghi nhận tự động khi bạn nhấn "Bắt đầu".
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success" onclick="saveStartWork()">
                        <i class="fas fa-play"></i>
                        Bắt đầu làm việc
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Progress Modal -->
    <div class="modal fade" id="updateProgressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-percentage"></i>
                        Cập nhật tiến độ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProgressForm">
                        <input type="hidden" id="progressAssignmentId" name="assignmentId">
                        <input type="hidden" id="progressTable" name="sourceTable">
                        
                        <div class="mb-3">
                            <label for="progressValue" class="form-label">Tiến độ hoàn thành (%)</label>
                            <input type="number" class="form-control" id="progressValue" name="progress" min="0" max="100" required>
                            <div class="form-text">Nhập phần trăm hoàn thành từ 0 đến 100</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="progressNote" class="form-label">Ghi chú tiến độ</label>
                            <textarea class="form-control" id="progressNote" name="note" rows="3" placeholder="Mô tả tiến độ hiện tại..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="saveProgressUpdate()">
                        <i class="fas fa-save"></i>
                        Cập nhật tiến độ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Complete Work Modal -->
    <div class="modal fade" id="completeWorkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-check"></i>
                        Hoàn thành công việc
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="completeWorkForm">
                        <input type="hidden" id="completeWorkId" name="assignmentId">
                        <input type="hidden" id="completeWorkTable" name="sourceTable">
                        
                        <div class="mb-3">
                            <label for="finalProgress" class="form-label">Tiến độ cuối cùng (%)</label>
                            <input type="number" class="form-control" id="finalProgress" name="progress" min="0" max="100" value="100" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="completeWorkNote" class="form-label">Ghi chú hoàn thành</label>
                            <textarea class="form-control" id="completeWorkNote" name="note" rows="3" placeholder="Mô tả kết quả công việc..."></textarea>
                        </div>
                        
                        <div class="alert alert-warning" id="lateProgressAlert" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Cảnh báo:</strong> Bạn đang hoàn thành sau thời hạn yêu cầu. Điều này sẽ được ghi nhận là chậm tiến độ.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success" onclick="saveCompleteWork()">
                        <i class="fas fa-check"></i>
                        Hoàn thành
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-check"></i>
                        Chi tiết sự kiện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="eventDetailsContent">
                        <!-- Event details will be loaded here -->
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
        function updateStatus(assignmentId, status) {
            try {
                document.getElementById('assignmentId').value = assignmentId;
                document.getElementById('newStatus').value = status;
                
                const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening update status modal:', error);
                alert('Có lỗi xảy ra khi mở modal cập nhật trạng thái');
            }
        }

        function startWork(assignmentId, sourceTable) {
            try {
                document.getElementById('startWorkId').value = assignmentId;
                document.getElementById('startWorkTable').value = sourceTable;
                
                const modal = new bootstrap.Modal(document.getElementById('startWorkModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening start work modal:', error);
                alert('Có lỗi xảy ra khi mở modal bắt đầu làm việc');
            }
        }

        function updateProgress(assignmentId, sourceTable) {
            try {
                document.getElementById('progressAssignmentId').value = assignmentId;
                document.getElementById('progressTable').value = sourceTable;
                
                const modal = new bootstrap.Modal(document.getElementById('updateProgressModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening update progress modal:', error);
                alert('Có lỗi xảy ra khi mở modal cập nhật tiến độ');
            }
        }

        function completeWork(assignmentId, sourceTable) {
            try {
                console.log('=== COMPLETE WORK DEBUG ===');
                console.log('assignmentId:', assignmentId);
                console.log('sourceTable:', sourceTable);
                
                const completeWorkId = document.getElementById('completeWorkId');
                const completeWorkTable = document.getElementById('completeWorkTable');
                
                console.log('completeWorkId element:', completeWorkId);
                console.log('completeWorkTable element:', completeWorkTable);
                
                if (!completeWorkId || !completeWorkTable) {
                    console.error('Modal elements not found!');
                    alert('Lỗi: Không tìm thấy các trường form');
                    return;
                }
                
                completeWorkId.value = assignmentId;
                completeWorkTable.value = sourceTable;
                
                console.log('Set values - completeWorkId.value:', completeWorkId.value);
                console.log('Set values - completeWorkTable.value:', completeWorkTable.value);
                
                // Check if late progress
                checkLateProgress(assignmentId, sourceTable);
                
                const modal = new bootstrap.Modal(document.getElementById('completeWorkModal'));
                modal.show();
                
                console.log('Modal opened successfully');
            } catch (error) {
                console.error('Error opening complete work modal:', error);
                alert('Có lỗi xảy ra khi mở modal hoàn thành công việc: ' + error.message);
            }
        }

        function reportIssue(assignmentId) {
            try {
                document.getElementById('issueAssignmentId').value = assignmentId;
                
                const modal = new bootstrap.Modal(document.getElementById('reportIssueModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening report issue modal:', error);
                alert('Có lỗi xảy ra khi mở modal báo sự cố');
            }
        }

        function saveStatusUpdate() {
            try {
                const form = document.getElementById('updateStatusForm');
                const formData = new FormData(form);
                formData.append('action', 'update_assignment_status');
                
                fetch('src/controllers/staff-schedule.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Cập nhật trạng thái thành công');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi cập nhật trạng thái: ' + error.message);
                });
            } catch (error) {
                console.error('Error in saveStatusUpdate:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái');
            }
        }

        function saveStartWork() {
            const form = document.getElementById('startWorkForm');
            const formData = new FormData(form);
            formData.append('action', 'start_work');
            
            fetch('src/controllers/staff-schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Bắt đầu làm việc thành công');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi bắt đầu làm việc');
            });
        }

        function saveProgressUpdate() {
            const form = document.getElementById('updateProgressForm');
            const formData = new FormData(form);
            formData.append('action', 'update_progress');
            
            fetch('src/controllers/staff-schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cập nhật tiến độ thành công');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật tiến độ');
            });
        }

        function saveCompleteWork() {
            const form = document.getElementById('completeWorkForm');
            const formData = new FormData(form);
            formData.append('action', 'complete_work');
            
            // Debug logs
            console.log('=== SAVE COMPLETE WORK DEBUG ===');
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            fetch('src/controllers/staff-schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text(); // Get raw response first
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    if (data.success) {
                        alert('Hoàn thành công việc thành công');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw response was:', text);
                    alert('Lỗi: Không thể phân tích phản hồi từ server');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Có lỗi xảy ra khi hoàn thành công việc: ' + error.message);
            });
        }

        function checkLateProgress(assignmentId, sourceTable) {
            // This function will check if the work is being completed late
            // For now, we'll show the alert if the current time is past the deadline
            const now = new Date();
            const deadlineElements = document.querySelectorAll(`[data-assignment-id="${assignmentId}"] .deadline`);
            
            if (deadlineElements.length > 0) {
                const deadlineText = deadlineElements[0].textContent;
                const deadlineDate = new Date(deadlineText.split(': ')[1]);
                
                if (now > deadlineDate) {
                    document.getElementById('lateProgressAlert').style.display = 'block';
                }
            }
        }

        function saveIssueReport() {
            const form = document.getElementById('reportIssueForm');
            const formData = new FormData(form);
            formData.append('action', 'report_issue');
            
            fetch('src/controllers/staff-schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Báo sự cố thành công');
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi báo sự cố');
            });
        }

        function viewDetails(assignmentId) {
            console.log('=== VIEW DETAILS DEBUG ===');
            console.log('assignmentId:', assignmentId);
            
            // Show loading in modal
            document.getElementById('eventDetailsContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Đang tải chi tiết sự kiện...</p>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
            modal.show();
            
            // Fetch event details
            console.log('Fetching event details for assignmentId:', assignmentId);
            console.log('Fetch URL:', 'src/controllers/staff-schedule.php');
            fetch('src/controllers/staff-schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_event_details&assignmentId=' + assignmentId
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response URL:', response.url);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                console.log('Response length:', text.length);
                console.log('First 200 chars:', text.substring(0, 200));
                
                // Check if response looks like HTML (error page)
                if (text.includes('<html') || text.includes('<!DOCTYPE') || text.includes('<body') || text.includes('<br>') || text.includes('<b>')) {
                    console.error('Response appears to be HTML, not JSON');
                    console.error('Full HTML response:', text);
                    console.error('This usually means the request went to wrong URL or server error');
                    document.getElementById('eventDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lỗi:</strong> Server trả về trang HTML thay vì dữ liệu JSON. 
                            <br><small>Có thể do:</small>
                            <ul class="small">
                                <li>Đường dẫn controller không đúng</li>
                                <li>Server trả về trang lỗi</li>
                                <li>PHP error hoặc warning</li>
                            </ul>
                            <br><small>Response: ${text.substring(0, 500)}...</small>
                            <br><button class="btn btn-sm btn-outline-danger mt-2" onclick="console.log('Full response:', \`${text}\`)">Xem toàn bộ response</button>
                        </div>
                    `;
                    return;
                }
                
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                if (data.success) {
                    displayEventDetails(data.event);
                } else {
                    document.getElementById('eventDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                                <strong>Lỗi:</strong> ${data.message}
                            </div>
                        `;
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Raw response was:', text);
                    document.getElementById('eventDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lỗi:</strong> Không thể phân tích phản hồi từ server
                            <br><small>Lỗi JSON: ${e.message}</small>
                            <br><small>Response: ${text.substring(0, 200)}...</small>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                document.getElementById('eventDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Lỗi:</strong> Có lỗi xảy ra khi tải chi tiết sự kiện: ${error.message}
                    </div>
                `;
            });
        }
        
        function displayEventDetails(event) {
            console.log('=== DISPLAY EVENT DETAILS DEBUG ===');
            console.log('Event data:', event);
            console.log('Equipment count:', event.equipment ? event.equipment.length : 0);
            console.log('Combos count:', event.combos ? event.combos.length : 0);
            console.log('Combo equipment:', event.comboEquipment);
            
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle"></i>
                                    Thông tin sự kiện
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                <p><strong>Tên sự kiện:</strong> ${event.TenSuKien || 'Không xác định'}</p>
                                <p><strong>Loại sự kiện:</strong> ${event.TenLoaiSK || 'Không xác định'}</p>
                                        <p><strong>Ngày bắt đầu:</strong> ${formatDateTime(event.NgayBatDau)}</p>
                                        <p><strong>Ngày kết thúc:</strong> ${formatDateTime(event.NgayKetThuc)}</p>
                                        <p><strong>Thời gian diễn ra:</strong> 
                                            <span class="badge bg-info">
                                                ${calculateEventDuration(event.NgayBatDau, event.NgayKetThuc)}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                <p><strong>Số người dự kiến:</strong> ${event.SoNguoiDuKien || 'Không xác định'}</p>
                                <p><strong>Ngân sách:</strong> ${formatCurrency(event.NganSach)}</p>
                                <p><strong>Trạng thái duyệt:</strong> 
                                    <span class="badge bg-${getApprovalStatusColor(event.TrangThaiDuyet)}">${event.TrangThaiDuyet || 'Không xác định'}</span>
                                </p>
                                        <p><strong>Trạng thái thanh toán:</strong> 
                                            <span class="badge bg-${getPaymentStatusColor(event.TrangThaiThanhToan)}">${event.TrangThaiThanhToan || 'Không xác định'}</span>
                                        </p>
                                    </div>
                                </div>
                                ${event.MoTa ? `
                                <div class="mt-3">
                                    <p><strong>Mô tả sự kiện:</strong></p>
                                    <div class="alert alert-light">
                                        <i class="fas fa-info-circle"></i>
                                        ${event.MoTa}
                                    </div>
                                </div>
                                ` : ''}
                                ${event.GhiChu ? `
                                <div class="mt-2">
                                    <p><strong>Ghi chú:</strong></p>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-sticky-note"></i>
                                        ${event.GhiChu}
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Thông tin địa điểm
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                <p><strong>Tên địa điểm:</strong> ${event.TenDiaDiem || 'Không xác định'}</p>
                                <p><strong>Địa chỉ:</strong> ${event.DiaChi || 'Không xác định'}</p>
                                        ${event.SucChua ? `<p><strong>Sức chứa:</strong> ${event.SucChua} người</p>` : ''}
                                    </div>
                                    <div class="col-md-6">
                                        ${event.GiaThue ? `<p><strong>Giá thuê:</strong> ${formatCurrency(event.GiaThue)}</p>` : ''}
                                        <div class="mt-2">
                                            <a href="https://maps.google.com/?q=${encodeURIComponent(event.DiaChi || '')}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-map-marker-alt"></i> Xem trên bản đồ
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                ${event.DiaDiemMoTa ? `
                                <div class="mt-3">
                                    <p><strong>Mô tả địa điểm:</strong></p>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        ${event.DiaDiemMoTa}
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-user"></i>
                                    Thông tin khách hàng
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                <p><strong>Tên khách hàng:</strong> ${event.TenKhachHang || 'Không xác định'}</p>
                                        <p><strong>Số điện thoại:</strong> 
                                            ${event.SoDienThoai ? `<a href="tel:${event.SoDienThoai}" class="text-decoration-none">${event.SoDienThoai}</a>` : 'Không xác định'}
                                        </p>
                                ${event.KhachHangDiaChi ? `<p><strong>Địa chỉ:</strong> ${event.KhachHangDiaChi}</p>` : ''}
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mt-2">
                                            ${event.SoDienThoai ? `
                                            <a href="tel:${event.SoDienThoai}" class="btn btn-sm btn-outline-success me-2 mb-2">
                                                <i class="fas fa-phone"></i> Gọi điện
                                            </a>
                                            ` : ''}
                                            ${event.SoDienThoai ? `
                                            <a href="https://zalo.me/${event.SoDienThoai}" target="_blank" class="btn btn-sm btn-outline-primary mb-2">
                                                <i class="fab fa-facebook-messenger"></i> Zalo
                                            </a>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-tasks"></i>
                                    Thông tin công việc
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Nhiệm vụ:</strong> ${event.NhiemVu || 'Không xác định'}</p>
                                <p><strong>Trạng thái:</strong> 
                                    <span class="badge bg-${getStatusColor(event.TrangThai)}">${event.TrangThai || 'Không xác định'}</span>
                                </p>
                                <p><strong>Tiến độ:</strong> ${event.Tiendo || '0%'}</p>
                                <p><strong>Hạn hoàn thành:</strong> ${formatDate(event.HanHoanThanh)}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${event.registration ? `
                <div class="card mb-3">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-clipboard-check"></i>
                            Thông tin đăng ký sự kiện
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Ngày đăng ký:</strong> ${formatDateTime(event.registration.NgayDangKy)}</p>
                                <p><strong>Trạng thái duyệt:</strong> 
                                    <span class="badge bg-${getApprovalStatusColor(event.registration.TrangThaiDuyet)}">${event.registration.TrangThaiDuyet || 'Không xác định'}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                ${event.registration.NgayDuyet ? `<p><strong>Ngày duyệt:</strong> ${formatDateTime(event.registration.NgayDuyet)}</p>` : ''}
                                ${event.registration.NguoiDuyet ? `<p><strong>Người duyệt:</strong> ${event.registration.NguoiDuyet}</p>` : ''}
                            </div>
                        </div>
                        ${event.registration.DangKyGhiChu ? `<p><strong>Ghi chú đăng ký:</strong> ${event.registration.DangKyGhiChu}</p>` : ''}
                        ${event.registration.LyDoTuChoi ? `<p><strong>Lý do từ chối:</strong> <span class="text-danger">${event.registration.LyDoTuChoi}</span></p>` : ''}
                    </div>
                </div>
                ` : ''}
                
                ${event.equipment && event.equipment.length > 0 ? `
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-tools"></i>
                            Thiết bị đã đăng ký (${event.equipment.length} thiết bị)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tên thiết bị</th>
                                        <th>Số lượng</th>
                                        <th>Giá thuê</th>
                                        <th>Trạng thái</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${event.equipment.map(item => `
                                        <tr>
                                            <td>
                                                <strong>${item.TenThietBi}</strong>
                                                ${item.MoTa ? `<br><small class="text-muted">${item.MoTa}</small>` : ''}
                                            </td>
                                            <td><span class="badge bg-primary">${item.SoLuong}</span></td>
                                            <td>${formatCurrency(item.GiaThue)}</td>
                                            <td><span class="badge bg-${getEquipmentStatusColor(item.ThietBiTrangThai)}">${item.ThietBiTrangThai || 'Không xác định'}</span></td>
                                            <td>
                                                ${formatDate(item.ThietBiNgayBatDau)} - ${formatDate(item.ThietBiNgayKetThuc)}
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                ` : `
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-tools"></i>
                            Thiết bị đã đăng ký
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted text-center">
                            <i class="fas fa-info-circle"></i>
                            Chưa có thiết bị nào được đăng ký cho sự kiện này
                        </p>
                    </div>
                </div>
                `}
                
                ${(!event.equipment || event.equipment.length === 0) && (!event.combos || event.combos.length === 0) ? `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Thông báo:</strong> Chưa có thiết bị hoặc combo nào được đăng ký cho sự kiện này.
                </div>
                ` : ''}
                
                ${event.GhiChu ? `
                <div class="card mb-3">
                    <div class="card-header bg-light text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-sticky-note"></i>
                            Ghi chú công việc
                        </h6>
                    </div>
                    <div class="card-body">
                        <p>${event.GhiChu}</p>
                    </div>
                </div>
                ` : ''}
                
                ${event.kehoach_noidung ? `
                <div class="card mb-3">
                    <div class="card-header bg-light text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-clipboard-list"></i>
                            Nội dung kế hoạch
                        </h6>
                    </div>
                    <div class="card-body">
                        <p>${event.kehoach_noidung}</p>
                    </div>
                </div>
                ` : ''}
                
                ${event.combos && event.combos.length > 0 ? `
                <div class="card mb-3">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-box"></i>
                            Combo thiết bị đã đăng ký (${event.combos.length} combo)
                        </h6>
                    </div>
                    <div class="card-body">
                        ${event.combos.map(combo => `
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-box"></i>
                                        ${combo.TenCombo || 'Combo không xác định'}
                                        <span class="badge bg-primary ms-2">${combo.SoLuong || '0'} combo</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Mô tả combo:</strong> ${combo.ComboMoTa || 'Không có mô tả'}</p>
                                            <p><strong>Giá combo:</strong> ${formatCurrency(combo.GiaCombo)}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Số lượng:</strong> ${combo.SoLuong || '0'}</p>
                                            <p><strong>Ghi chú:</strong> ${combo.GhiChu || 'Không có ghi chú'}</p>
                                        </div>
                                    </div>
                                    
                                    ${event.comboEquipment && event.comboEquipment[combo.ID_Combo] && event.comboEquipment[combo.ID_Combo].length > 0 ? `
                                    <div class="mt-3">
                                        <h6><i class="fas fa-list"></i> Thiết bị trong combo:</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Tên thiết bị</th>
                                                        <th>Mô tả</th>
                                                        <th>Số lượng</th>
                                                        <th>Giá thuê</th>
                                                        <th>Trạng thái</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${event.comboEquipment[combo.ID_Combo].map(item => `
                                                        <tr>
                                                            <td>${item.TenThietBi || 'Không xác định'}</td>
                                                            <td>${item.MoTa || 'Không có mô tả'}</td>
                                                            <td>${item.SoLuong || '0'}</td>
                                                            <td>${formatCurrency(item.GiaThue)}</td>
                                                            <td>
                                                                <span class="badge bg-${getEquipmentStatusColor(item.TrangThai)}">${item.TrangThai || 'Không xác định'}</span>
                                                            </td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
                
                ${(!event.equipment || event.equipment.length === 0) && (!event.combos || event.combos.length === 0) ? `
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle"></i>
                            Thông tin thiết bị
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Thông báo:</strong> Chưa có thiết bị hoặc combo nào được đăng ký cho sự kiện này.
                        </div>
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('eventDetailsContent').innerHTML = content;
        }
        
        function formatDateTime(dateString) {
            if (!dateString) return 'Không xác định';
            const date = new Date(dateString);
            return date.toLocaleString('vi-VN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function formatDate(dateString) {
            if (!dateString) return 'Không xác định';
            const date = new Date(dateString);
            return date.toLocaleDateString('vi-VN');
        }
        
        function formatCurrency(amount) {
            if (!amount) return 'Không xác định';
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        }
        
        function getStatusColor(status) {
            switch(status) {
                case 'Chưa làm': return 'secondary';
                case 'Đang làm': return 'warning';
                case 'Hoàn thành': return 'success';
                case 'Báo sự cố': return 'danger';
                default: return 'secondary';
            }
        }
        
        function getApprovalStatusColor(status) {
            switch(status) {
                case 'Đã duyệt': return 'success';
                case 'Chờ duyệt': return 'warning';
                case 'Từ chối': return 'danger';
                default: return 'secondary';
            }
        }
        
        function getPaymentStatusColor(status) {
            switch(status) {
                case 'Đã thanh toán': return 'success';
                case 'Chưa thanh toán': return 'warning';
                case 'Thanh toán một phần': return 'info';
                case 'Quá hạn': return 'danger';
                default: return 'secondary';
            }
        }
        
        function calculateEventDuration(startDate, endDate) {
            if (!startDate || !endDate) return 'Không xác định';
            
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffMs = end - start;
            
            if (diffMs <= 0) return 'Thời gian không hợp lệ';
            
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
            
            if (diffHours > 0) {
                return `${diffHours} giờ ${diffMinutes} phút`;
            } else {
                return `${diffMinutes} phút`;
            }
        }
        
        function getEquipmentStatusColor(status) {
            switch(status) {
                case 'Sẵn sàng': return 'success';
                case 'Đang sử dụng': return 'warning';
                case 'Bảo trì': return 'danger';
                case 'Không khả dụng': return 'secondary';
                default: return 'info';
            }
        }

        function viewAllAssignments() {
            // Scroll to assignments list
            document.querySelector('.timeline').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }

        // Hide loading overlay when page is loaded
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const loadingOverlay = document.getElementById('pageLoading');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
            }, 1000);
        });

        // Also hide on window load as backup
        window.addEventListener('load', function() {
            setTimeout(function() {
                const loadingOverlay = document.getElementById('pageLoading');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
            }, 500);
        });

        // Force hide loading after 3 seconds
        setTimeout(function() {
            const loadingOverlay = document.getElementById('pageLoading');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        }, 3000);
    </script>
</body>
</html>
