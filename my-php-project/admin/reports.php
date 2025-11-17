<?php
// Include admin header
include 'includes/admin-header.php';

// Get dashboard data
$dashboardData = [];
try {
    // Include database connection
    require_once __DIR__ . '/../config/database.php';
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
    
    // Monthly event registrations
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(NgayTao, '%Y-%m') as month,
            COUNT(*) as count
        FROM datlichsukien 
        WHERE NgayTao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')
        ORDER BY month ASC
    ");
    $dashboardData['monthly_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
        'monthly_registrations' => []
    ];
}
?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-bar"></i>
                Thống kê báo cáo
            </h1>
            <p class="page-subtitle">Tổng quan thống kê và biểu đồ hệ thống</p>
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
            <div class="col-lg-12">
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
        }
</script>

<?php include 'includes/admin-footer.php'; ?>

