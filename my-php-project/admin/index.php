<?php
// Include admin header
include 'includes/admin-header.php';

// Get dashboard data based on user role
$dashboardData = [];
$userRole = $user['ID_Role'];

try {
    // Include database connection
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();
    
    // ============================================
    // ROLE 1: ADMIN - Tất cả thống kê
    // ============================================
    if ($userRole == 1) {
        // Total event registrations
        $stmt = $pdo->query("SELECT COUNT(*) AS total_registrations FROM datlichsukien");
        $dashboardData['total_registrations'] = $stmt->fetchColumn();
        
        // Pending registrations
        $stmt = $pdo->query("SELECT COUNT(*) AS pending_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
        $dashboardData['pending_registrations'] = $stmt->fetchColumn();
        
        // Approved registrations
        $stmt = $pdo->query("SELECT COUNT(*) AS approved_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
        $dashboardData['approved_registrations'] = $stmt->fetchColumn();
        
        // Rejected registrations
        $stmt = $pdo->query("SELECT COUNT(*) AS rejected_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Từ chối'");
        $dashboardData['rejected_registrations'] = $stmt->fetchColumn();
        
        // Total locations
        $stmt = $pdo->query("SELECT COUNT(*) AS total_locations FROM diadiem");
        $dashboardData['total_locations'] = $stmt->fetchColumn();
        
        // Total equipment
        $stmt = $pdo->query("SELECT COUNT(*) AS total_equipment FROM thietbi");
        $dashboardData['total_equipment'] = $stmt->fetchColumn();
        
        // Total staff
        $stmt = $pdo->query("SELECT COUNT(*) AS total_staff FROM nhanvieninfo");
        $dashboardData['total_staff'] = $stmt->fetchColumn();
        
        // Total customers
        $stmt = $pdo->query("SELECT COUNT(*) AS total_customers FROM users WHERE ID_Role = 5");
        $dashboardData['total_customers'] = $stmt->fetchColumn();
        
        // Recent registrations
        $stmt = $pdo->query("
            SELECT dl.*, kh.HoTen AS TenKhachHang, dd.TenDiaDiem, lsk.TenLoai
            FROM datlichsukien dl
            JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            JOIN loaisukien lsk ON dl.ID_LoaiSK = lsk.ID_LoaiSK
            ORDER BY dl.NgayTao DESC
            LIMIT 5
        ");
        $dashboardData['recent_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $dashboardTitle = "Tổng quan hệ thống quản lý sự kiện";
    }
    
    // ============================================
    // ROLE 2: QUẢN LÝ TỔ CHỨC - Quản lý và duyệt
    // ============================================
    elseif ($userRole == 2) {
        // Pending registrations (cần duyệt)
        $stmt = $pdo->query("SELECT COUNT(*) AS pending_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
        $dashboardData['pending_registrations'] = $stmt->fetchColumn();
        
        // Approved registrations
        $stmt = $pdo->query("SELECT COUNT(*) AS approved_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
        $dashboardData['approved_registrations'] = $stmt->fetchColumn();
        
        // Total locations (quản lý)
        $stmt = $pdo->query("SELECT COUNT(*) AS total_locations FROM diadiem");
        $dashboardData['total_locations'] = $stmt->fetchColumn();
        
        // Active locations
        $stmt = $pdo->query("SELECT COUNT(*) AS active_locations FROM diadiem WHERE TrangThaiHoatDong = 'Hoạt động'");
        $dashboardData['active_locations'] = $stmt->fetchColumn();
        
        // Total rooms
        $stmt = $pdo->query("SELECT COUNT(*) AS total_rooms FROM phong WHERE TrangThai = 'Sẵn sàng'");
        $dashboardData['total_rooms'] = $stmt->fetchColumn();
        
        // Total staff
        $stmt = $pdo->query("SELECT COUNT(*) AS total_staff FROM nhanvieninfo");
        $dashboardData['total_staff'] = $stmt->fetchColumn();
        
        // Total equipment
        $stmt = $pdo->query("SELECT COUNT(*) AS total_equipment FROM thietbi WHERE TrangThai = 'Sẵn sàng'");
        $dashboardData['total_equipment'] = $stmt->fetchColumn();
        
        // Total customers
        $stmt = $pdo->query("SELECT COUNT(*) AS total_customers FROM users WHERE ID_Role = 5");
        $dashboardData['total_customers'] = $stmt->fetchColumn();
        
        // Pending payments
        $stmt = $pdo->query("SELECT COUNT(*) AS pending_payments FROM thanhtoan WHERE TrangThai = 'Chờ thanh toán'");
        $dashboardData['pending_payments'] = $stmt->fetchColumn();
        
        // Recent pending registrations
        $stmt = $pdo->query("
            SELECT dl.*, kh.HoTen AS TenKhachHang, dd.TenDiaDiem, lsk.TenLoai
            FROM datlichsukien dl
            JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            JOIN loaisukien lsk ON dl.ID_LoaiSK = lsk.ID_LoaiSK
            WHERE dl.TrangThaiDuyet = 'Chờ duyệt'
            ORDER BY dl.NgayTao DESC
            LIMIT 5
        ");
        $dashboardData['recent_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $dashboardTitle = "Tổng quan quản lý tổ chức sự kiện";
    }
    
    // ============================================
    // ROLE 3: QUẢN LÝ SỰ KIỆN - Đăng ký và xem
    // ============================================
    elseif ($userRole == 3) {
        // Total registrations (đã đăng ký)
        $stmt = $pdo->query("SELECT COUNT(*) AS total_registrations FROM datlichsukien");
        $dashboardData['total_registrations'] = $stmt->fetchColumn();
        
        // Pending registrations (chờ duyệt)
        $stmt = $pdo->query("SELECT COUNT(*) AS pending_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'");
        $dashboardData['pending_registrations'] = $stmt->fetchColumn();
        
        // Approved registrations
        $stmt = $pdo->query("SELECT COUNT(*) AS approved_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'");
        $dashboardData['approved_registrations'] = $stmt->fetchColumn();
        
        // Upcoming events (sắp diễn ra)
        $stmt = $pdo->query("
            SELECT COUNT(*) AS upcoming_events 
            FROM datlichsukien 
            WHERE TrangThaiDuyet = 'Đã duyệt' 
            AND NgayBatDau >= CURDATE()
        ");
        $dashboardData['upcoming_events'] = $stmt->fetchColumn();
        
        // Today's events
        $stmt = $pdo->query("
            SELECT COUNT(*) AS today_events 
            FROM datlichsukien 
            WHERE TrangThaiDuyet = 'Đã duyệt' 
            AND DATE(NgayBatDau) = CURDATE()
        ");
        $dashboardData['today_events'] = $stmt->fetchColumn();
        
        // Total customers
        $stmt = $pdo->query("SELECT COUNT(*) AS total_customers FROM users WHERE ID_Role = 5");
        $dashboardData['total_customers'] = $stmt->fetchColumn();
        
        // Recent registrations
        $stmt = $pdo->query("
            SELECT dl.*, kh.HoTen AS TenKhachHang, dd.TenDiaDiem, lsk.TenLoai
            FROM datlichsukien dl
            JOIN khachhanginfo kh ON dl.ID_KhachHang = kh.ID_KhachHang
            JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
            JOIN loaisukien lsk ON dl.ID_LoaiSK = lsk.ID_LoaiSK
            ORDER BY dl.NgayTao DESC
            LIMIT 5
        ");
        $dashboardData['recent_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $dashboardTitle = "Tổng quan quản lý sự kiện";
    }
    
    // ============================================
    // ROLE 4: NHÂN VIÊN - Lịch làm việc và nhiệm vụ
    // ============================================
    elseif ($userRole == 4) {
        // Get staff ID
        $userId = $_SESSION['user']['ID_User'];
        $stmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ? LIMIT 1");
        $stmt->execute([$userId]);
        $staffInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        $staffId = $staffInfo ? $staffInfo['ID_NhanVien'] : null;
        
        if ($staffId) {
            // Total assignments (tổng nhiệm vụ)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS total_assignments 
                FROM lichlamviec 
                WHERE ID_NhanVien = ?
            ");
            $stmt->execute([$staffId]);
            $dashboardData['total_assignments'] = $stmt->fetchColumn();
            
            // Pending tasks (nhiệm vụ chưa hoàn thành)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS pending_tasks 
                FROM lichlamviec 
                WHERE ID_NhanVien = ? 
                AND TrangThai IN ('Chưa bắt đầu', 'Đang thực hiện')
            ");
            $stmt->execute([$staffId]);
            $dashboardData['pending_tasks'] = $stmt->fetchColumn();
            
            // Completed tasks (nhiệm vụ đã hoàn thành)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS completed_tasks 
                FROM lichlamviec 
                WHERE ID_NhanVien = ? 
                AND TrangThai = 'Hoàn thành'
            ");
            $stmt->execute([$staffId]);
            $dashboardData['completed_tasks'] = $stmt->fetchColumn();
            
            // Today's tasks (nhiệm vụ hôm nay)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS today_tasks 
                FROM lichlamviec 
                WHERE ID_NhanVien = ? 
                AND DATE(NgayBatDau) = CURDATE()
                AND TrangThai != 'Hoàn thành'
            ");
            $stmt->execute([$staffId]);
            $dashboardData['today_tasks'] = $stmt->fetchColumn();
            
            // Upcoming tasks (nhiệm vụ sắp tới - 7 ngày)
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS upcoming_tasks 
                FROM lichlamviec 
                WHERE ID_NhanVien = ? 
                AND NgayBatDau BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                AND TrangThai != 'Hoàn thành'
            ");
            $stmt->execute([$staffId]);
            $dashboardData['upcoming_tasks'] = $stmt->fetchColumn();
            
            // Recent assignments
            $stmt = $pdo->prepare("
                SELECT llv.*, dl.TenSuKien, dd.TenDiaDiem
                FROM lichlamviec llv
                LEFT JOIN datlichsukien dl ON llv.ID_DatLich = dl.ID_DatLich
                LEFT JOIN diadiem dd ON dl.ID_DD = dd.ID_DD
                WHERE llv.ID_NhanVien = ?
                ORDER BY llv.NgayBatDau DESC
                LIMIT 5
            ");
            $stmt->execute([$staffId]);
            $dashboardData['recent_assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // No staff info found
            $dashboardData = [
                'total_assignments' => 0,
                'pending_tasks' => 0,
                'completed_tasks' => 0,
                'today_tasks' => 0,
                'upcoming_tasks' => 0,
                'recent_assignments' => []
            ];
        }
        
        $dashboardTitle = "Tổng quan lịch làm việc và nhiệm vụ";
    }
    
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    // Set default empty data based on role
    if ($userRole == 1) {
        $dashboardData = [
            'total_registrations' => 0,
            'pending_registrations' => 0,
            'approved_registrations' => 0,
            'rejected_registrations' => 0,
            'total_locations' => 0,
            'total_equipment' => 0,
            'total_staff' => 0,
            'total_customers' => 0,
            'recent_registrations' => []
        ];
        $dashboardTitle = "Tổng quan hệ thống quản lý sự kiện";
    } elseif ($userRole == 2) {
        $dashboardData = [
            'pending_registrations' => 0,
            'approved_registrations' => 0,
            'total_locations' => 0,
            'active_locations' => 0,
            'total_rooms' => 0,
            'total_staff' => 0,
            'total_equipment' => 0,
            'total_customers' => 0,
            'pending_payments' => 0,
            'recent_registrations' => []
        ];
        $dashboardTitle = "Tổng quan quản lý tổ chức sự kiện";
    } elseif ($userRole == 3) {
        $dashboardData = [
            'total_registrations' => 0,
            'pending_registrations' => 0,
            'approved_registrations' => 0,
            'upcoming_events' => 0,
            'today_events' => 0,
            'total_customers' => 0,
            'recent_registrations' => []
        ];
        $dashboardTitle = "Tổng quan quản lý sự kiện";
    } elseif ($userRole == 4) {
        $dashboardData = [
            'total_assignments' => 0,
            'pending_tasks' => 0,
            'completed_tasks' => 0,
            'today_tasks' => 0,
            'upcoming_tasks' => 0,
            'recent_assignments' => []
        ];
        $dashboardTitle = "Tổng quan lịch làm việc và nhiệm vụ";
    }
}
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </h1>
            <p class="page-subtitle"><?= $dashboardTitle ?? 'Tổng quan hệ thống' ?></p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <?php if ($userRole == 1): ?>
                <!-- ROLE 1: ADMIN -->
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_registrations'] ?? 0 ?></div>
                    <div class="stat-label">Tổng đăng ký</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['pending_registrations'] ?? 0 ?></div>
                    <div class="stat-label">Chờ duyệt</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['approved_registrations'] ?? 0 ?></div>
                    <div class="stat-label">Đã duyệt</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon rejected">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['rejected_registrations'] ?? 0 ?></div>
                    <div class="stat-label">Từ chối</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_locations'] ?? 0 ?></div>
                    <div class="stat-label">Địa điểm</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_equipment'] ?? 0 ?></div>
                    <div class="stat-label">Thiết bị</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_staff'] ?? 0 ?></div>
                    <div class="stat-label">Nhân viên</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_customers'] ?? 0 ?></div>
                    <div class="stat-label">Khách hàng</div>
                </div>
                
            <?php elseif ($userRole == 2): ?>
                <!-- ROLE 2: QUẢN LÝ TỔ CHỨC -->
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['pending_registrations'] ?? 0 ?></div>
                    <div class="stat-label">Chờ duyệt</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['approved_registrations'] ?? 0 ?></div>
                    <div class="stat-label">Đã duyệt</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_locations'] ?? 0 ?></div>
                    <div class="stat-label">Địa điểm</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_rooms'] ?? 0 ?></div>
                    <div class="stat-label">Phòng</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_staff'] ?? 0 ?></div>
                    <div class="stat-label">Nhân viên</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_equipment'] ?? 0 ?></div>
                    <div class="stat-label">Thiết bị</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_customers'] ?? 0 ?></div>
                    <div class="stat-label">Khách hàng</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['pending_payments'] ?? 0 ?></div>
                    <div class="stat-label">Thanh toán chờ</div>
                </div>
                
            <?php elseif ($userRole == 3): ?>
                <!-- ROLE 3: QUẢN LÝ SỰ KIỆN -->
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_registrations'] ?? 0 ?></div>
                    <div class="stat-label">Tổng đăng ký</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['pending_registrations'] ?? 0 ?></div>
                    <div class="stat-label">Chờ duyệt</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['approved_registrations'] ?? 0 ?></div>
                    <div class="stat-label">Đã duyệt</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['upcoming_events'] ?? 0 ?></div>
                    <div class="stat-label">Sự kiện sắp tới</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon approved">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['today_events'] ?? 0 ?></div>
                    <div class="stat-label">Sự kiện hôm nay</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_customers'] ?? 0 ?></div>
                    <div class="stat-label">Khách hàng</div>
                </div>
                
            <?php elseif ($userRole == 4): ?>
                <!-- ROLE 4: NHÂN VIÊN -->
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['total_assignments'] ?? 0 ?></div>
                    <div class="stat-label">Tổng nhiệm vụ</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['pending_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Chưa hoàn thành</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['completed_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Đã hoàn thành</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['today_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Nhiệm vụ hôm nay</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-number"><?= $dashboardData['upcoming_tasks'] ?? 0 ?></div>
                    <div class="stat-label">Sắp tới (7 ngày)</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="table-container">
            <?php if ($userRole == 1 || $userRole == 2 || $userRole == 3): ?>
                <!-- Recent Registrations for Admin, Manager, Event Manager -->
                <h3 class="mb-4">
                    <i class="fas fa-history"></i>
                    <?= $userRole == 2 ? 'Đăng ký chờ duyệt' : 'Đăng ký sự kiện gần đây' ?>
                </h3>
                
                <?php if (!empty($dashboardData['recent_registrations'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sự kiện</th>
                                <th>Khách hàng</th>
                                <th>Địa điểm</th>
                                <th>Ngày bắt đầu</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboardData['recent_registrations'] as $reg): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars($reg['ID_DatLich']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($reg['TenSuKien']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($reg['TenLoai']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($reg['TenKhachHang']) ?></td>
                                <td><?= htmlspecialchars($reg['TenDiaDiem']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($reg['NgayBatDau'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $reg['TrangThaiDuyet'])) ?>">
                                        <?= htmlspecialchars($reg['TrangThaiDuyet']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm" onclick="viewRegistrationDetails(<?= $reg['ID_DatLich'] ?>)">
                                            <i class="fas fa-eye"></i> Xem chi tiết
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Chưa có đăng ký sự kiện</h3>
                    <p>Chưa có đăng ký sự kiện nào trong hệ thống.</p>
                </div>
                <?php endif; ?>
                
            <?php elseif ($userRole == 4): ?>
                <!-- Recent Assignments for Staff -->
                <h3 class="mb-4">
                    <i class="fas fa-tasks"></i>
                    Nhiệm vụ gần đây
                </h3>
                
                <?php if (!empty($dashboardData['recent_assignments'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Sự kiện</th>
                                <th>Nhiệm vụ</th>
                                <th>Địa điểm</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dashboardData['recent_assignments'] as $assignment): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($assignment['TenSuKien'] ?? 'N/A') ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($assignment['NhiemVu'] ?? $assignment['CongViec'] ?? 'N/A') ?>
                                </td>
                                <td><?= htmlspecialchars($assignment['TenDiaDiem'] ?? 'N/A') ?></td>
                                <td><?= $assignment['NgayBatDau'] ? date('d/m/Y H:i', strtotime($assignment['NgayBatDau'])) : 'N/A' ?></td>
                                <td><?= $assignment['NgayKetThuc'] ? date('d/m/Y H:i', strtotime($assignment['NgayKetThuc'])) : 'N/A' ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $assignment['TrangThai'] ?? 'Chưa xác định')) ?>">
                                        <?= htmlspecialchars($assignment['TrangThai'] ?? 'Chưa xác định') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="staff-schedule.php" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> Xem chi tiết
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Chưa có nhiệm vụ</h3>
                    <p>Bạn chưa được phân công nhiệm vụ nào.</p>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

<!-- Modal Xem Chi Tiết Đăng Ký -->
<div class="modal fade" id="registrationDetailModal" tabindex="-1" aria-labelledby="registrationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registrationDetailModalLabel">
                    <i class="fas fa-info-circle"></i> Chi tiết đăng ký sự kiện
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="registrationDetailContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải thông tin...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <a href="#" id="viewFullDetailsLink" class="btn btn-primary" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Xem chi tiết đầy đủ
                </a>
            </div>
        </div>
    </div>
        </div>

<script>
        // Registration actions
        function viewRegistrationDetails(id) {
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('registrationDetailModal'));
            modal.show();
            
            // Reset content
            document.getElementById('registrationDetailContent').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải thông tin...</p>
                </div>
            `;
            
            // Set link to full details page
            document.getElementById('viewFullDetailsLink').href = `event-registrations.php?view=${id}`;
            
            // Fetch registration details
            fetch(`../src/controllers/admin-events.php?action=get_registration_details&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.html) {
                        // Endpoint trả về HTML sẵn, hiển thị trực tiếp
                        document.getElementById('registrationDetailContent').innerHTML = data.html;
                    } else {
                        document.getElementById('registrationDetailContent').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                ${data.message || 'Không thể tải thông tin đăng ký'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('registrationDetailContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Lỗi khi tải thông tin: ${error.message}
                        </div>
                    `;
                });
        }

        // Auto refresh data every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
</script>

<?php include 'includes/admin-footer.php'; ?>
