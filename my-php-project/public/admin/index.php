<?php
// Include admin header
include 'includes/admin-header.php';

// Get dashboard data
$dashboardData = [];
try {
    // Include database connection
    require_once '../../config/database.php';
    $pdo = getDBConnection();
    
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
    
    // Active locations
    $stmt = $pdo->query("SELECT COUNT(*) AS active_locations FROM diadiem WHERE TrangThaiHoatDong = 'Hoạt động'");
    $dashboardData['active_locations'] = $stmt->fetchColumn();
    
    // Total equipment
    $stmt = $pdo->query("SELECT COUNT(*) AS total_equipment FROM thietbi");
    $dashboardData['total_equipment'] = $stmt->fetchColumn();
    
    // Total staff
    $stmt = $pdo->query("SELECT COUNT(*) AS total_staff FROM nhanvieninfo");
    $dashboardData['total_staff'] = $stmt->fetchColumn();
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) AS total_customers FROM users WHERE ID_Role = 5");
    $dashboardData['total_customers'] = $stmt->fetchColumn();
    
    // Active customers
    $stmt = $pdo->query("SELECT COUNT(*) AS active_customers FROM users WHERE ID_Role = 5 AND TrangThai = 'Hoạt động'");
    $dashboardData['active_customers'] = $stmt->fetchColumn();
    
    // Pending customers
    $stmt = $pdo->query("SELECT COUNT(*) AS pending_customers FROM users WHERE ID_Role = 5 AND TrangThai = 'Chưa xác minh'");
    $dashboardData['pending_customers'] = $stmt->fetchColumn();
    
    // Blocked customers
    $stmt = $pdo->query("SELECT COUNT(*) AS blocked_customers FROM users WHERE ID_Role = 5 AND TrangThai = 'Bị khóa'");
    $dashboardData['blocked_customers'] = $stmt->fetchColumn();
    
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
    
} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    $dashboardData = [
        'total_registrations' => 0,
        'pending_registrations' => 0,
        'approved_registrations' => 0,
        'rejected_registrations' => 0,
        'total_locations' => 0,
        'active_locations' => 0,
        'total_equipment' => 0,
        'total_staff' => 0,
        'total_customers' => 0,
        'active_customers' => 0,
        'pending_customers' => 0,
        'blocked_customers' => 0,
        'recent_registrations' => []
    ];
}
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </h1>
            <p class="page-subtitle">Tổng quan hệ thống quản lý sự kiện</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-number"><?= $dashboardData['total_registrations'] ?></div>
                <div class="stat-label">Tổng đăng ký</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-number"><?= $dashboardData['pending_registrations'] ?></div>
                <div class="stat-label">Chờ duyệt</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?= $dashboardData['approved_registrations'] ?></div>
                <div class="stat-label">Đã duyệt</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon rejected">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number"><?= $dashboardData['rejected_registrations'] ?></div>
                <div class="stat-label">Từ chối</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="stat-number"><?= $dashboardData['total_locations'] ?></div>
                <div class="stat-label">Địa điểm</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-number"><?= $dashboardData['total_equipment'] ?></div>
                <div class="stat-label">Thiết bị</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-user-tie"></i>
    </div>
                <div class="stat-number"><?= $dashboardData['total_staff'] ?></div>
                <div class="stat-label">Nhân viên</div>
                        </div>
            
            <div class="stat-card">
                <div class="stat-icon customers">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= $dashboardData['total_customers'] ?></div>
                <div class="stat-label">Khách hàng</div>
            </div>
                        </div>

        <!-- Charts Section -->
        <div class="row">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Thống kê đăng ký sự kiện
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="registrationsChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
              <div class="chart-container">
                <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Thống kê khách hàng
                </h3>
                <div class="chart-wrapper">
                        <canvas id="customersChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar"></i>
                        Trạng thái địa điểm
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="locationsChart" width="300" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-bar"></i>
                        Tổng quan hệ thống
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="overviewChart" width="300" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="table-container">
            <h3 class="mb-4">
                <i class="fas fa-history"></i>
                Đăng ký sự kiện gần đây
            </h3>
            
            <?php if (!empty($dashboardData['recent_registrations'])): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
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
                                    <button class="btn btn-info btn-sm" onclick="viewRegistration(<?= $reg['ID_DatLich'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($reg['TrangThaiDuyet'] === 'Chờ duyệt'): ?>
                                    <button class="btn btn-success btn-sm" onclick="approveRegistration(<?= $reg['ID_DatLich'] ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectRegistration(<?= $reg['ID_DatLich'] ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
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
        </div>

<script>
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });

        function initializeCharts() {
            // Registrations Chart
            const registrationsCtx = document.getElementById('registrationsChart').getContext('2d');
            new Chart(registrationsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Chờ duyệt', 'Đã duyệt', 'Từ chối'],
                    datasets: [{
                        data: [
                            <?= $dashboardData['pending_registrations'] ?>,
                            <?= $dashboardData['approved_registrations'] ?>,
                            <?= $dashboardData['rejected_registrations'] ?>
                        ],
                        backgroundColor: [
                            '#ffc107',
                            '#28a745',
                            '#dc3545'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // Customers Chart
            const customersCtx = document.getElementById('customersChart').getContext('2d');
            new Chart(customersCtx, {
        type: 'doughnut',
        data: {
                    labels: ['Hoạt động', 'Chưa xác minh', 'Bị khóa'],
            datasets: [{
                        data: [
                            <?= $dashboardData['active_customers'] ?>,
                            <?= $dashboardData['pending_customers'] ?>,
                            <?= $dashboardData['blocked_customers'] ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#dc3545'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // Locations Chart
            const locationsCtx = document.getElementById('locationsChart').getContext('2d');
            new Chart(locationsCtx, {
                type: 'bar',
                data: {
                    labels: ['Tổng địa điểm', 'Hoạt động'],
                    datasets: [{
                        label: 'Số lượng',
                        data: [
                            <?= $dashboardData['total_locations'] ?>,
                            <?= $dashboardData['active_locations'] ?>
                        ],
                        backgroundColor: [
                            '#667eea',
                            '#28a745'
                        ],
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // Overview Chart
            const overviewCtx = document.getElementById('overviewChart').getContext('2d');
            new Chart(overviewCtx, {
                type: 'bar',
                data: {
                    labels: ['Đăng ký', 'Địa điểm', 'Thiết bị', 'Nhân viên', 'Khách hàng'],
                    datasets: [{
                        label: 'Tổng số',
                        data: [
                            <?= $dashboardData['total_registrations'] ?>,
                            <?= $dashboardData['total_locations'] ?>,
                            <?= $dashboardData['total_equipment'] ?>,
                            <?= $dashboardData['total_staff'] ?>,
                            <?= $dashboardData['total_customers'] ?>
                        ],
                        backgroundColor: [
                            '#007bff',
                            '#28a745',
                            '#ffc107',
                            '#17a2b8',
                            '#6f42c1'
                        ],
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
            }
        }
    });
}

        // Registration actions
        function viewRegistration(id) {
            window.location.href = `event-registrations.php?view=${id}`;
        }

        function approveRegistration(id) {
            if (confirm('Bạn có chắc muốn duyệt đăng ký này?')) {
                AdminPanel.makeAjaxRequest('../../src/controllers/admin-events.php', {
                    action: 'approve',
                    id: id
                }, 'POST')
                .then(response => {
                    if (response.success) {
                        AdminPanel.showSuccess('Đã duyệt đăng ký thành công');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        AdminPanel.showError(response.message || 'Có lỗi xảy ra');
                    }
                })
                .catch(error => {
                    AdminPanel.showError('Có lỗi xảy ra khi duyệt đăng ký');
                });
            }
        }

        function rejectRegistration(id) {
            if (confirm('Bạn có chắc muốn từ chối đăng ký này?')) {
                AdminPanel.makeAjaxRequest('../../src/controllers/admin-events.php', {
                    action: 'reject',
                    id: id
                }, 'POST')
                .then(response => {
                    if (response.success) {
                        AdminPanel.showSuccess('Đã từ chối đăng ký thành công');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        AdminPanel.showError(response.message || 'Có lỗi xảy ra');
                    }
                })
                .catch(error => {
                    AdminPanel.showError('Có lỗi xảy ra khi từ chối đăng ký');
                });
            }
        }

        // Auto refresh data every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
</script>

<?php include 'includes/admin-footer.php'; ?>