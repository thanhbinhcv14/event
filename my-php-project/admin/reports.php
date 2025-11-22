<?php
// Include admin header
include 'includes/admin-header.php';

// Get dashboard data
$dashboardData = [];
try {
    // Include database connection
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();
    
    // Get current user info
    $currentUserId = $user['ID_User'] ?? null;
    $currentUserRole = $user['ID_Role'] ?? null;
    
    // Build WHERE clause based on role
    // Role 3 (Quản lý sự kiện) chỉ xem sự kiện đã đăng ký giúp khách hàng
    $isRole3 = ($currentUserRole == 3);
    $role3WhereClause = "";
    $role3Params = [];
    
    if ($isRole3) {
        // Role 3: Chỉ hiển thị sự kiện có GhiChu chứa "Đăng ký bởi quản lý sự kiện" hoặc "Đăng ký bởi"
        $role3WhereClause = "WHERE (GhiChu LIKE ? OR GhiChu LIKE ? OR GhiChu LIKE ?)";
        $role3Params = ['%Đăng ký bởi quản lý sự kiện%', '%Đăng ký bởi%', '%quản lý sự kiện%'];
    }
    // Role 1, 2, 4: Xem tất cả sự kiện (không có WHERE clause)
    
    // Total event registrations
    $sql = "SELECT COUNT(*) AS total_registrations FROM datlichsukien" . ($isRole3 ? " " . $role3WhereClause : "");
    $stmt = $pdo->prepare($sql);
    $stmt->execute($isRole3 ? $role3Params : []);
    $dashboardData['total_registrations'] = $stmt->fetchColumn();
    
    // Pending registrations
    if ($isRole3) {
        $sql = "SELECT COUNT(*) AS pending_registrations FROM datlichsukien " . $role3WhereClause . " AND TrangThaiDuyet = 'Chờ duyệt'";
        $params = $role3Params;
    } else {
        $sql = "SELECT COUNT(*) AS pending_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Chờ duyệt'";
        $params = [];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dashboardData['pending_registrations'] = $stmt->fetchColumn();
    
    // Approved registrations
    if ($isRole3) {
        $sql = "SELECT COUNT(*) AS approved_registrations FROM datlichsukien " . $role3WhereClause . " AND TrangThaiDuyet = 'Đã duyệt'";
        $params = $role3Params;
    } else {
        $sql = "SELECT COUNT(*) AS approved_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Đã duyệt'";
        $params = [];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dashboardData['approved_registrations'] = $stmt->fetchColumn();
    
    // Rejected registrations
    if ($isRole3) {
        $sql = "SELECT COUNT(*) AS rejected_registrations FROM datlichsukien " . $role3WhereClause . " AND TrangThaiDuyet = 'Từ chối'";
        $params = $role3Params;
    } else {
        $sql = "SELECT COUNT(*) AS rejected_registrations FROM datlichsukien WHERE TrangThaiDuyet = 'Từ chối'";
        $params = [];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
    
    // Monthly event registrations
    if ($isRole3) {
        $sql = "
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                COUNT(*) as count
            FROM datlichsukien 
            WHERE NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
            AND (GhiChu LIKE ? OR GhiChu LIKE ? OR GhiChu LIKE ?)
            GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
            ORDER BY month ASC
        ";
        $params = $role3Params;
    } else {
        $sql = "
            SELECT 
                DATE_FORMAT(NgayTao, '%Y-%m') as month,
                COUNT(*) as count
            FROM datlichsukien 
            WHERE NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
            ORDER BY month ASC
        ";
        $params = [];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dashboardData['monthly_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Statistics for customer registration assistance
    // Total registrations
    $totalRegistrations = $dashboardData['total_registrations'];
    
    // Registrations made by staff (role 3) for customers
    if ($isRole3) {
        // For role 3, all registrations are staff-assisted
        $staffAssistedRegistrations = $totalRegistrations;
    } else {
        // Count registrations with "Đăng ký bởi" in GhiChu
        $sql = "SELECT COUNT(*) AS staff_assisted FROM datlichsukien WHERE GhiChu LIKE ? OR GhiChu LIKE ? OR GhiChu LIKE ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['%Đăng ký bởi quản lý sự kiện%', '%Đăng ký bởi%', '%[NHANVIEN_ID:%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $staffAssistedRegistrations = (int)($result['staff_assisted'] ?? 0);
    }
    
    // Customer self-registrations
    $customerSelfRegistrations = $totalRegistrations - $staffAssistedRegistrations;
    
    // Calculate percentages
    $dashboardData['staff_assisted_count'] = $staffAssistedRegistrations;
    $dashboardData['customer_self_count'] = $customerSelfRegistrations;
    $dashboardData['staff_assisted_percentage'] = $totalRegistrations > 0 ? round(($staffAssistedRegistrations / $totalRegistrations) * 100, 2) : 0;
    $dashboardData['customer_self_percentage'] = $totalRegistrations > 0 ? round(($customerSelfRegistrations / $totalRegistrations) * 100, 2) : 0;
    
} catch (Exception $e) {
    error_log("Reports data error: " . $e->getMessage());
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
        'monthly_registrations' => [],
        'staff_assisted_count' => 0,
        'customer_self_count' => 0,
        'staff_assisted_percentage' => 0,
        'customer_self_percentage' => 0
    ];
}
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-bar"></i>
                Thống kê báo cáo
            </h1>
            <p class="page-subtitle">
                <?php if ($currentUserRole == 3): ?>
                    Thống kê các sự kiện đã đăng ký giúp khách hàng
                <?php else: ?>
                    Tổng quan thống kê và biểu đồ hệ thống
                <?php endif; ?>
            </p>
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
        
        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        Đăng ký sự kiện theo tháng (12 tháng gần nhất)
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="monthlyChart" width="400" height="150"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="chart-container">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        Phân loại đăng ký sự kiện
                    </h3>
                    <div class="chart-wrapper">
                        <canvas id="registrationTypeChart" width="400" height="200"></canvas>
                    </div>
                    <div class="text-center mt-3">
                        <p class="mb-1">
                            <span class="badge bg-info me-2">Nhờ đăng ký: <?= $dashboardData['staff_assisted_count'] ?> (<?= $dashboardData['staff_assisted_percentage'] ?>%)</span>
                            <span class="badge bg-success">Tự đăng ký: <?= $dashboardData['customer_self_count'] ?> (<?= $dashboardData['customer_self_percentage'] ?>%)</span>
                        </p>
                    </div>
                </div>
            </div>
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

            // Monthly Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyData = <?= json_encode($dashboardData['monthly_registrations']) ?>;
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: monthlyData.map(item => item.month),
                    datasets: [{
                        label: 'Số lượng đăng ký',
                        data: monthlyData.map(item => item.count),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
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
            
            // Registration Type Chart (Customer self-registration vs Staff-assisted)
            const registrationTypeCtx = document.getElementById('registrationTypeChart').getContext('2d');
            new Chart(registrationTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Khách hàng nhờ đăng ký', 'Khách hàng tự đăng ký'],
                    datasets: [{
                        data: [
                            <?= $dashboardData['staff_assisted_count'] ?>,
                            <?= $dashboardData['customer_self_count'] ?>
                        ],
                        backgroundColor: [
                            '#17a2b8',
                            '#28a745'
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
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed + ' sự kiện';
                                    if (context.dataIndex === 0) {
                                        label += ' (<?= $dashboardData['staff_assisted_percentage'] ?>%)';
                                    } else {
                                        label += ' (<?= $dashboardData['customer_self_percentage'] ?>%)';
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
</script>

<?php include 'includes/admin-footer.php'; ?>

