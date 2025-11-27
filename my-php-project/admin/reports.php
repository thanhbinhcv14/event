<?php
// Include admin header
include 'includes/admin-header.php';
// Chỉ cho phép Role 1 (QTV) và Role 2 (QLTC) truy cập
if (!in_array($user['ID_Role'], [1, 2])) {
    echo '<script>alert("Bạn không có quyền truy cập trang này!"); window.location.href = "index.php";</script>';
    exit;
}
// Định nghĩa constant để báo cho controller biết đang được include từ admin
define('ADMIN_ACCESS', true);
// Lấy dữ liệu thống kê từ controller
require_once __DIR__ . '/../src/controllers/reports-controller.php';
require_once __DIR__ . '/../config/database.php';
$userRole = $user['ID_Role'];
$statsData = [];
$pageTitle = '';
$pageSubtitle = '';
try {
    $pdo = getDBConnection();
   
    // Kiểm tra kết nối database
    if (!$pdo) {
        throw new Exception("Không thể kết nối database");
    }
   
    // Lấy filter type từ GET
    $filterType = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : 'all';
    
    // Debug filter (chỉ khi có ?debug=1)
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        error_log("=== REPORTS.PHP DEBUG ===");
        error_log("Filter Type: " . $filterType);
        error_log("Filter Date: " . ($_GET['filter_date'] ?? 'not set'));
        error_log("Filter Month: " . ($_GET['filter_month'] ?? 'not set'));
        error_log("Filter Year: " . ($_GET['filter_year'] ?? 'not set'));
        error_log("Filter Date From: " . ($_GET['filter_date_from'] ?? 'not set'));
        error_log("Filter Date To: " . ($_GET['filter_date_to'] ?? 'not set'));
        error_log("User Role: " . $userRole);
        error_log("All GET params: " . print_r($_GET, true));
        
        // Test query để xem dữ liệu
        if ($filterType === 'month' && isset($_GET['filter_month'])) {
            $testMonth = trim($_GET['filter_month']);
            error_log("Testing month filter: $testMonth");
            try {
                $testPdo = getDBConnection();
                $testQuery = "SELECT COUNT(*) as count, DATE_FORMAT(NgayTao, '%Y-%m') as month FROM users WHERE ID_Role = 5 GROUP BY DATE_FORMAT(NgayTao, '%Y-%m')";
                $testStmt = $testPdo->query($testQuery);
                $testResults = $testStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Available months in users table: " . print_r($testResults, true));
            } catch (Exception $e) {
                error_log("Test query error: " . $e->getMessage());
            }
        }
    }
   
    // Lấy dữ liệu theo role
    if ($userRole == 1) {
        // Role 1: QTV - Thống kê về khách hàng, nhân viên, bài viết, bình luận, mã giảm giá
        $statsData = getReportsDataForAdmin($pdo, $filterType);
        $pageTitle = 'Thống kê báo cáo - Quản trị viên';
        $pageSubtitle = 'Tổng quan thống kê về khách hàng, nhân viên, bài viết, bình luận và mã giảm giá';
    } elseif ($userRole == 2) {
        // Role 2: QLTC - Thống kê về đăng ký sự kiện, địa điểm, phòng, thiết bị, nhân viên, thanh toán
        $statsData = getReportsDataForManager($pdo, $filterType);
        $pageTitle = 'Thống kê báo cáo - Quản lý tổ chức';
        $pageSubtitle = 'Tổng quan thống kê về đăng ký sự kiện, địa điểm, phòng, thiết bị, nhân viên và thanh toán';
    }
   
    // Debug: Log để kiểm tra dữ liệu (chỉ khi có ?debug=1)
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        error_log("Reports Data (Role {$userRole}): " . print_r($statsData, true));
        error_log("StatsData keys: " . implode(', ', array_keys($statsData)));
    }
   
    // Đảm bảo tất cả các key cần thiết đều có trong statsData
    $isArrayKey = function($key) {
        return strpos($key, '_by_') !== false || strpos($key, 'by_') !== false;
    };
    if ($userRole == 1) {
        // Đảm bảo các key cho Role 1
        $requiredKeys = ['total_customers', 'active_customers', 'pending_customers', 'blocked_customers',
                        'total_staff', 'active_staff', 'staff_by_role', 'staff_by_month',
                        'total_posts', 'published_posts', 'draft_posts', 'archived_posts', 'total_views',
                        'posts_by_type', 'posts_by_month',
                        'total_comments', 'approved_comments', 'pending_comments', 'rejected_comments',
                        'top_posts_comments', 'comments_by_month',
                        'total_discounts', 'active_discounts', 'expired_discounts', 'upcoming_discounts',
                        'discounts_by_type', 'total_discount_uses', 'customers_by_month'];
        foreach ($requiredKeys as $key) {
            if (!isset($statsData[$key])) {
                $statsData[$key] = $isArrayKey($key) ? [] : 0;
            }
        }
    } elseif ($userRole == 2) {
        // Đảm bảo các key cho Role 2
        $requiredKeys = ['total_registrations', 'pending_registrations', 'approved_registrations', 'rejected_registrations',
                        'registrations_by_month', 'registrations_by_type',
                        'total_locations', 'active_locations', 'inactive_locations',
                        'total_rooms', 'available_rooms', 'occupied_rooms',
                        'total_equipment', 'available_equipment', 'occupied_equipment', 'equipment_by_type',
                        'total_staff', 'active_staff', 'inactive_staff', 'staff_by_role',
                        'total_payments', 'pending_payments', 'completed_payments', 'failed_payments',
                        'total_revenue', 'payments_by_month',
                        'total_customers', 'active_customers', 'customers_by_month'];
        foreach ($requiredKeys as $key) {
            if (!isset($statsData[$key])) {
                $statsData[$key] = $isArrayKey($key) ? [] : 0;
            }
        }
    }
   
} catch (PDOException $e) {
    error_log("Reports data PDO error: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    if (isset($pdo) && $pdo) {
        error_log("PDO Error Info: " . print_r($pdo->errorInfo(), true));
    }
    $statsData = ($userRole == 1) ? getDefaultReportsData() : getDefaultReportsDataForManager();
} catch (Exception $e) {
    error_log("Reports data error: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    $statsData = ($userRole == 1) ? getDefaultReportsData() : getDefaultReportsDataForManager();
}
// Đảm bảo statsData không rỗng
if (empty($statsData)) {
    error_log("CRITICAL: statsData is empty after all attempts, using defaults");
    $statsData = ($userRole == 1) ? getDefaultReportsData() : getDefaultReportsDataForManager();
}
?>
<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-chart-bar"></i>
        <?= $pageTitle ?>
    </h1>
    <p class="page-subtitle"><?= $pageSubtitle ?></p>
</div>
<!-- Bộ lọc thống kê -->
<div class="filter-section mb-4">
    <form method="GET" action="reports.php" class="filter-form" id="filterForm">
        <div class="filter-controls">
            <div class="filter-item">
                <label>Loại lọc:</label>
                <select name="filter_type" id="filterType" onchange="showFilterInput()">
                    <option value="all" <?= (!isset($_GET['filter_type']) || $_GET['filter_type'] == 'all') ? 'selected' : '' ?>>Tất cả</option>
                    <option value="date" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'date') ? 'selected' : '' ?>>Theo ngày</option>
                    <option value="month" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'month') ? 'selected' : '' ?>>Theo tháng</option>
                    <option value="year" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'year') ? 'selected' : '' ?>>Theo năm</option>
                    <option value="range" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'range') ? 'selected' : '' ?>>Khoảng thời gian</option>
                </select>
            </div>
           
            <div class="filter-item" id="dateInput" style="display: <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'date') ? 'block' : 'none' ?>;">
                <label>Chọn ngày:</label>
                <input type="date" name="filter_date" id="filter_date" value="<?= $_GET['filter_date'] ?? date('Y-m-d') ?>" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'date') ? '' : 'disabled' ?>>
            </div>
           
            <div class="filter-item" id="monthInput" style="display: <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'month') ? 'block' : 'none' ?>;">
                <label>Chọn tháng:</label>
                <input type="month" name="filter_month" id="filter_month" value="<?= $_GET['filter_month'] ?? date('Y-m') ?>" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'month') ? '' : 'disabled' ?>>
            </div>
           
            <div class="filter-item" id="yearInput" style="display: <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'year') ? 'block' : 'none' ?>;">
                <label>Chọn năm:</label>
                <select name="filter_year" id="filter_year" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'year') ? '' : 'disabled' ?>>
                    <?php
                    $currentYear = date('Y');
                    $selectedYear = $_GET['filter_year'] ?? $currentYear;
                    for ($year = $currentYear - 5; $year <= $currentYear + 1; $year++) { // Fixed loop: ascending from past to future
                        $selected = ($year == $selectedYear) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
            </div>
           
            <div class="filter-item filter-range" id="rangeInput" style="display: <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'range') ? 'flex' : 'none' ?>;">
                <div>
                    <label>Từ ngày:</label>
                    <input type="date" name="filter_date_from" id="filter_date_from" value="<?= $_GET['filter_date_from'] ?? date('Y-m-01') ?>" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'range') ? '' : 'disabled' ?>>
                </div>
                <div>
                    <label>Đến ngày:</label>
                    <input type="date" name="filter_date_to" id="filter_date_to" value="<?= $_GET['filter_date_to'] ?? date('Y-m-d') ?>" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'range') ? '' : 'disabled' ?>>
                </div>
            </div>
           
            <div class="filter-actions">
                <button type="submit" id="applyFilterBtn" onclick="return submitFilterForm()">Áp dụng</button>
                <a href="reports.php" class="btn-clear">Xóa</a>
            </div>
        </div>
    </form>
</div>
<style>
.filter-section {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.filter-form {
    width: 100%;
}
.filter-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}
.filter-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.filter-item label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
}
.filter-item input,
.filter-item select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}
.filter-item input:focus,
.filter-item select:focus {
    outline: none;
    border-color: #667eea;
}
.filter-range {
    flex-direction: row;
    gap: 1rem;
}
.filter-range > div {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.filter-actions {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
}
.filter-actions button {
    padding: 0.5rem 1.5rem;
    background: #667eea;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}
.filter-actions button:hover {
    background: #5568d3;
}
.filter-actions button:disabled {
    background: #ccc;
    cursor: not-allowed;
    opacity: 0.6;
}
.filter-actions a {
    padding: 0.5rem 1.5rem;
    background: #6c757d;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
}
.filter-actions a:hover {
    background: #5a6268;
}
@media (max-width: 768px) {
    .filter-controls {
        flex-direction: column;
    }
   
    .filter-range {
        flex-direction: column;
    }
   
    .filter-actions {
        width: 100%;
        margin-left: 0;
    }
   
    .filter-actions button,
    .filter-actions a {
        flex: 1;
        text-align: center;
    }
}
</style>
<script>
// Hàm submit form filter
function submitFilterForm() {
    console.log('submitFilterForm called');
    
    const filterForm = document.getElementById('filterForm');
    if (!filterForm) {
        console.error('Filter form not found');
        return false;
    }
    
    // Enable tất cả inputs trước khi submit
    const filterType = document.getElementById('filterType').value;
    console.log('Filter type:', filterType);
    
    if (filterType === 'date') {
        const filterDate = document.getElementById('filter_date');
        if (filterDate) {
            filterDate.disabled = false;
            console.log('Filter date:', filterDate.value);
        }
    } else if (filterType === 'month') {
        const filterMonth = document.getElementById('filter_month');
        if (filterMonth) {
            filterMonth.disabled = false;
            console.log('Filter month:', filterMonth.value);
        }
    } else if (filterType === 'year') {
        const filterYear = document.getElementById('filter_year');
        if (filterYear) {
            filterYear.disabled = false;
            console.log('Filter year:', filterYear.value);
        }
    } else if (filterType === 'range') {
        const filterDateFrom = document.getElementById('filter_date_from');
        const filterDateTo = document.getElementById('filter_date_to');
        if (filterDateFrom) {
            filterDateFrom.disabled = false;
            console.log('Filter date from:', filterDateFrom.value);
        }
        if (filterDateTo) {
            filterDateTo.disabled = false;
            console.log('Filter date to:', filterDateTo.value);
        }
    }
    
    // Submit form
    filterForm.submit();
    return false; // Prevent default button behavior
}

function showFilterInput() {
    const filterType = document.getElementById('filterType').value;
   
    // Lấy tất cả các input elements
    const dateInput = document.getElementById('dateInput');
    const monthInput = document.getElementById('monthInput');
    const yearInput = document.getElementById('yearInput');
    const rangeInput = document.getElementById('rangeInput');
    const filterDate = document.getElementById('filter_date');
    const filterMonth = document.getElementById('filter_month');
    const filterYear = document.getElementById('filter_year');
    const filterDateFrom = document.getElementById('filter_date_from');
    const filterDateTo = document.getElementById('filter_date_to');
   
    // Ẩn tất cả và disable tất cả inputs
    dateInput.style.display = 'none';
    monthInput.style.display = 'none';
    yearInput.style.display = 'none';
    rangeInput.style.display = 'none';
    
    if (filterDate) filterDate.disabled = true;
    if (filterMonth) filterMonth.disabled = true;
    if (filterYear) filterYear.disabled = true;
    if (filterDateFrom) filterDateFrom.disabled = true;
    if (filterDateTo) filterDateTo.disabled = true;
   
    // Hiển thị input tương ứng và enable nó
    if (filterType === 'date') {
        dateInput.style.display = 'block';
        if (filterDate) filterDate.disabled = false;
    } else if (filterType === 'month') {
        monthInput.style.display = 'block';
        if (filterMonth) filterMonth.disabled = false;
    } else if (filterType === 'year') {
        yearInput.style.display = 'block';
        if (filterYear) filterYear.disabled = false;
    } else if (filterType === 'range') {
        rangeInput.style.display = 'flex';
        if (filterDateFrom) filterDateFrom.disabled = false;
        if (filterDateTo) filterDateTo.disabled = false;
    }
}

// Đảm bảo form submit đúng cách
document.addEventListener('DOMContentLoaded', function() {
    showFilterInput();
    
    // Thêm event listener cho form submit để đảm bảo tất cả inputs được enable trước khi submit
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            console.log('Form submit triggered');
            
            // Enable tất cả inputs trước khi submit để đảm bảo chúng được gửi đi
            const filterType = document.getElementById('filterType').value;
            console.log('Filter type:', filterType);
            
            if (filterType === 'date') {
                const filterDate = document.getElementById('filter_date');
                if (filterDate) {
                    filterDate.disabled = false;
                    console.log('Filter date:', filterDate.value);
                }
            } else if (filterType === 'month') {
                const filterMonth = document.getElementById('filter_month');
                if (filterMonth) {
                    filterMonth.disabled = false;
                    console.log('Filter month:', filterMonth.value);
                }
            } else if (filterType === 'year') {
                const filterYear = document.getElementById('filter_year');
                if (filterYear) {
                    filterYear.disabled = false;
                    console.log('Filter year:', filterYear.value);
                }
            } else if (filterType === 'range') {
                const filterDateFrom = document.getElementById('filter_date_from');
                const filterDateTo = document.getElementById('filter_date_to');
                if (filterDateFrom) {
                    filterDateFrom.disabled = false;
                    console.log('Filter date from:', filterDateFrom.value);
                }
                if (filterDateTo) {
                    filterDateTo.disabled = false;
                    console.log('Filter date to:', filterDateTo.value);
                }
            }
            
            // Không preventDefault - để form submit bình thường
            // Form sẽ submit với method="GET" và action="reports.php"
        });
    }
    
    // Thêm click handler cho nút Áp dụng để debug
    const applyBtn = document.getElementById('applyFilterBtn');
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            console.log('Apply button clicked');
            // Không preventDefault - để form submit bình thường
            // Nút type="submit" sẽ tự động trigger form submit
        });
    }
});
</script>
<?php if ($userRole == 1): ?>
    <!-- ========== ROLE 1: QUẢN TRỊ VIÊN ========== -->
   
    <!-- Statistics Cards -->
    <div class="stats-cards">
        <!-- Khách hàng -->
        <div class="stat-card">
            <div class="stat-icon customers">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_customers'] ?? 0 ?></div>
            <div class="stat-label">Tổng khách hàng</div>
        </div>
       
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number"><?= $statsData['active_customers'] ?? 0 ?></div>
            <div class="stat-label">Khách hàng hoạt động</div>
        </div>
       
        <!-- Nhân viên -->
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_staff'] ?? 0 ?></div>
            <div class="stat-label">Tổng nhân viên</div>
        </div>
       
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number"><?= $statsData['active_staff'] ?? 0 ?></div>
            <div class="stat-label">Nhân viên hoạt động</div>
        </div>
       
        <!-- Bài viết -->
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-blog"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_posts'] ?? 0 ?></div>
            <div class="stat-label">Tổng bài viết</div>
        </div>
       
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-number"><?= number_format($statsData['total_views'] ?? 0) ?></div>
            <div class="stat-label">Tổng lượt xem</div>
        </div>
       
        <!-- Comment -->
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_comments'] ?? 0 ?></div>
            <div class="stat-label">Tổng bình luận</div>
        </div>
       
        <!-- Mã giảm giá -->
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_discounts'] ?? 0 ?></div>
            <div class="stat-label">Tổng mã giảm giá</div>
        </div>
    </div>
   
    <style>
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
       
        .stat-card {
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
       
        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.2rem;
            color: #fff;
        }
       
        .stat-icon.customers { background: #007bff; }
        .stat-icon.approved { background: #28a745; }
        .stat-icon.info { background: #17a2b8; }
        .stat-icon.total { background: #6f42c1; }
        .stat-icon.pending { background: #ffc107; }
       
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
            color: #333;
        }
       
        .stat-label {
            font-size: 0.85rem;
            color: #666;
        }
       
        .chart-wrapper {
            position: relative;
            min-height: 300px;
            height: 300px;
        }
       
        .chart-container {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
       
        .chart-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #333;
        }
       
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }
       
        .col-lg-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 15px;
            box-sizing: border-box;
        }
       
        @media (max-width: 768px) {
            .col-lg-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
    <!-- Charts Section -->
    <div class="row">
        <!-- Thống kê khách hàng -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Trạng thái khách hàng
                </h3>
                <div class="chart-wrapper">
                    <canvas id="customersStatusChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Thống kê nhân viên -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Nhân viên theo vai trò
                </h3>
                <div class="chart-wrapper">
                    <canvas id="staffRoleChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Thống kê bài viết -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Trạng thái bài viết
                </h3>
                <div class="chart-wrapper">
                    <canvas id="postsStatusChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Thống kê comment -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Trạng thái bình luận
                </h3>
                <div class="chart-wrapper">
                    <canvas id="commentsStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Thống kê mã giảm giá -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Trạng thái mã giảm giá
                </h3>
                <div class="chart-wrapper">
                    <canvas id="discountsStatusChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Mã giảm giá theo loại -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                <i class="fas fa-chart-bar"></i>
                    Mã giảm giá theo loại
                </h3>
                <div class="chart-wrapper">
                    <canvas id="discountsTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Khách hàng theo tháng -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Khách hàng đăng ký theo tháng (12 tháng gần nhất)
                </h3>
                <div class="chart-wrapper">
                    <canvas id="customersMonthlyChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Nhân viên theo tháng -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Nhân viên tuyển dụng theo tháng (12 tháng gần nhất)
                </h3>
                <div class="chart-wrapper">
                    <canvas id="staffMonthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Bài viết theo tháng -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Bài viết đăng theo tháng (12 tháng gần nhất)
                </h3>
                <div class="chart-wrapper">
                    <canvas id="postsMonthlyChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Comment theo tháng -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Bình luận theo tháng (12 tháng gần nhất)
                </h3>
                <div class="chart-wrapper">
                    <canvas id="commentsMonthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Bài viết theo loại sự kiện -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-bar"></i>
                    Bài viết theo loại sự kiện
                </h3>
                <div class="chart-wrapper">
                    <canvas id="postsByTypeChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Top 10 bài viết có nhiều comment -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-bar"></i>
                    Top 10 bài viết có nhiều bình luận nhất
                </h3>
                <div class="chart-wrapper">
                    <canvas id="topPostsCommentsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($userRole == 2): ?>
    <!-- ========== ROLE 2: QUẢN LÝ TỔ CHỨC ========== -->
    <!-- Statistics Cards -->
    <div class="stats-cards">
        <!-- Đăng ký sự kiện -->
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_registrations'] ?? 0 ?></div>
            <div class="stat-label">Tổng đăng ký</div>
        </div>
       
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number"><?= $statsData['pending_registrations'] ?? 0 ?></div>
            <div class="stat-label">Chờ duyệt</div>
        </div>
       
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-number"><?= $statsData['approved_registrations'] ?? 0 ?></div>
            <div class="stat-label">Đã duyệt</div>
        </div>
       
        <!-- Địa điểm -->
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_locations'] ?? 0 ?></div>
            <div class="stat-label">Tổng địa điểm</div>
        </div>
       
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-number"><?= $statsData['active_locations'] ?? 0 ?></div>
            <div class="stat-label">Địa điểm hoạt động</div>
        </div>
       
        <!-- Phòng -->
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-door-open"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_rooms'] ?? 0 ?></div>
            <div class="stat-label">Tổng phòng</div>
        </div>
       
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-number"><?= $statsData['available_rooms'] ?? 0 ?></div>
            <div class="stat-label">Phòng sẵn sàng</div>
        </div>
       
        <!-- Thiết bị -->
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-tools"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_equipment'] ?? 0 ?></div>
            <div class="stat-label">Tổng thiết bị</div>
        </div>
       
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-number"><?= $statsData['available_equipment'] ?? 0 ?></div>
            <div class="stat-label">Thiết bị sẵn sàng</div>
        </div>
       
        <!-- Nhân viên -->
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_staff'] ?? 0 ?></div>
            <div class="stat-label">Tổng nhân viên</div>
        </div>
       
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number"><?= $statsData['active_staff'] ?? 0 ?></div>
            <div class="stat-label">Nhân viên hoạt động</div>
        </div>
       
        <!-- Thanh toán -->
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fas fa-credit-card"></i>
            </div>
            <div class="stat-number"><?= $statsData['total_payments'] ?? 0 ?></div>
            <div class="stat-label">Tổng thanh toán</div>
        </div>
       
        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-number"><?= number_format($statsData['total_revenue'] ?? 0, 0, ',', '.') ?></div>
            <div class="stat-label">Tổng doanh thu (VNĐ)</div>
        </div>
    </div>
   
    <style>
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
       
        .stat-card {
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
       
        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.2rem;
            color: #fff;
        }
       
        .stat-icon.total { background: #6f42c1; }
        .stat-icon.pending { background: #ffc107; }
        .stat-icon.approved { background: #28a745; }
        .stat-icon.info { background: #17a2b8; }
       
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
            color: #333;
        }
       
        .stat-label {
            font-size: 0.85rem;
            color: #666;
        }
       
        .chart-wrapper {
            position: relative;
            min-height: 300px;
            height: 300px;
        }
       
        .chart-container {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
       
        .chart-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #333;
        }
       
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }
       
        .col-lg-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 15px;
            box-sizing: border-box;
        }
       
        @media (max-width: 768px) {
            .col-lg-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
    <!-- Charts Section -->
    <div class="row">
        <!-- Thống kê đăng ký sự kiện -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Trạng thái đăng ký sự kiện
                </h3>
                <div class="chart-wrapper">
                    <canvas id="registrationsStatusChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Đăng ký theo loại sự kiện -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-bar"></i>
                    Đăng ký theo loại sự kiện
                </h3>
                <div class="chart-wrapper">
                    <canvas id="registrationsByTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Địa điểm -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Trạng thái địa điểm
                </h3>
                <div class="chart-wrapper">
                    <canvas id="locationsStatusChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Phòng -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Trạng thái phòng
                </h3>
                <div class="chart-wrapper">
                    <canvas id="roomsStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Thiết bị -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Trạng thái thiết bị
                </h3>
                <div class="chart-wrapper">
                    <canvas id="equipmentStatusChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Thiết bị theo loại -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-bar"></i>
                    Thiết bị theo loại
                </h3>
                <div class="chart-wrapper">
                    <canvas id="equipmentByTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Nhân viên theo vai trò -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Nhân viên theo vai trò
                </h3>
                <div class="chart-wrapper">
                    <canvas id="staffRoleChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Thống kê thanh toán -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Trạng thái thanh toán
                </h3>
                <div class="chart-wrapper">
                    <canvas id="paymentsStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Đăng ký theo tháng -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Đăng ký sự kiện theo tháng (12 tháng gần nhất)
                </h3>
                <div class="chart-wrapper">
                    <canvas id="registrationsMonthlyChart"></canvas>
                </div>
            </div>
        </div>
       
        <!-- Thanh toán theo tháng -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Doanh thu theo tháng (12 tháng gần nhất)
                </h3>
                <div class="chart-wrapper">
                    <canvas id="revenueMonthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
   
    <div class="row">
        <!-- Khách hàng theo tháng -->
        <div class="col-lg-6">
            <div class="chart-container">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Khách hàng đăng ký theo tháng (12 tháng gần nhất)
                </h3>
                <div class="chart-wrapper">
                    <canvas id="customersMonthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Đợi Chart.js load xong
function waitForChartJS(callback, maxAttempts = 50) {
    let attempts = 0;
    const checkChart = function() {
        attempts++;
        if (typeof Chart !== 'undefined') {
            console.log('Chart.js đã được load thành công');
            callback();
        } else if (attempts < maxAttempts) {
            setTimeout(checkChart, 100);
        } else {
            console.error('Chart.js không thể load sau ' + (maxAttempts * 100) + 'ms');
            // Thử load Chart.js từ CDN
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
            script.onload = function() {
                console.log('Chart.js đã được load từ fallback');
                callback();
            };
            script.onerror = function() {
                console.error('Không thể load Chart.js từ CDN');
            };
            document.head.appendChild(script);
        }
    };
    checkChart();
}
// Đợi DOM và Chart.js đều sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM đã sẵn sàng, đợi Chart.js...');
    // Kiểm tra xem Chart.js đã có sẵn chưa (từ admin-footer.php)
    if (typeof Chart !== 'undefined') {
        console.log('Chart.js đã có sẵn, khởi tạo charts ngay...');
        setTimeout(function() {
            initializeCharts();
        }, 200);
    } else {
        waitForChartJS(function() {
            setTimeout(function() {
                initializeCharts();
            }, 200);
        });
    }
});
function initializeCharts() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js chưa được load');
        return;
    }
   
    console.log('Bắt đầu khởi tạo charts...');
    console.log('StatsData available:', typeof statsData !== 'undefined' ? 'Yes' : 'No');
   
    <?php if ($userRole == 1): ?>
        // ========== ROLE 1 CHARTS ==========
        initializeAdminCharts();
    <?php elseif ($userRole == 2): ?>
        // ========== ROLE 2 CHARTS ==========
        initializeManagerCharts();
    <?php endif; ?>
   
    console.log('Đã hoàn tất khởi tạo tất cả charts');
}
<?php if ($userRole == 1): ?>
function initializeAdminCharts() {
    console.log('Khởi tạo biểu đồ cho Admin (Role 1)...');
   
    // Trạng thái khách hàng
    const customersStatusCtx = document.getElementById('customersStatusChart');
    if (customersStatusCtx) {
        try {
            new Chart(customersStatusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Hoạt động', 'Chưa xác minh', 'Bị khóa'],
                    datasets: [{
                        data: [
                            <?= (int)($statsData['active_customers'] ?? 0) ?>,
                            <?= (int)($statsData['pending_customers'] ?? 0) ?>,
                            <?= (int)($statsData['blocked_customers'] ?? 0) ?>
                        ],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo customersStatusChart:', error);
        }
    }
   
    // Nhân viên theo vai trò
    const staffRoleCtx = document.getElementById('staffRoleChart');
    if (staffRoleCtx) {
        try {
            const staffRoleData = <?= json_encode($statsData['staff_by_role'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(staffRoleData) && staffRoleData.length > 0
                ? staffRoleData.map(item => item.TenRole || 'Chưa xác định')
                : ['Chưa có dữ liệu'];
            const data = Array.isArray(staffRoleData) && staffRoleData.length > 0
                ? staffRoleData.map(item => parseInt(item.count) || 0)
                : [0];
            const colors = ['#007bff', '#17a2b8', '#6f42c1', '#28a745', '#ffc107', '#dc3545'];
           
            new Chart(staffRoleCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, Math.max(labels.length, 1)),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo staffRoleChart:', error);
        }
    }
   
    // Trạng thái bài viết
    const postsStatusCtx = document.getElementById('postsStatusChart');
    if (postsStatusCtx) {
        try {
            new Chart(postsStatusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Đã xuất bản', 'Bản nháp', 'Đã lưu trữ'],
                    datasets: [{
                        data: [
                            <?= (int)($statsData['published_posts'] ?? 0) ?>,
                            <?= (int)($statsData['draft_posts'] ?? 0) ?>,
                            <?= (int)($statsData['archived_posts'] ?? 0) ?>
                        ],
                        backgroundColor: ['#28a745', '#ffc107', '#6c757d'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo postsStatusChart:', error);
        }
    }
   
    // Trạng thái comment
    const commentsStatusCtx = document.getElementById('commentsStatusChart');
    if (commentsStatusCtx) {
        try {
            new Chart(commentsStatusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Đã duyệt', 'Chờ duyệt', 'Bị từ chối'],
                    datasets: [{
                        data: [
                            <?= (int)($statsData['approved_comments'] ?? 0) ?>,
                            <?= (int)($statsData['pending_comments'] ?? 0) ?>,
                            <?= (int)($statsData['rejected_comments'] ?? 0) ?>
                        ],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo commentsStatusChart:', error);
        }
    }
   
    // Trạng thái mã giảm giá
    const discountsStatusCtx = document.getElementById('discountsStatusChart');
    if (discountsStatusCtx) {
        try {
            new Chart(discountsStatusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Đang hoạt động', 'Đã hết hạn', 'Chưa bắt đầu'],
                    datasets: [{
                        data: [
                            <?= (int)($statsData['active_discounts'] ?? 0) ?>,
                            <?= (int)($statsData['expired_discounts'] ?? 0) ?>,
                            <?= (int)($statsData['upcoming_discounts'] ?? 0) ?>
                        ],
                        backgroundColor: ['#28a745', '#dc3545', '#ffc107'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo discountsStatusChart:', error);
        }
    }
   
    // Mã giảm giá theo loại
    const discountsTypeCtx = document.getElementById('discountsTypeChart');
    if (discountsTypeCtx) {
        try {
            const discountsTypeData = <?= json_encode($statsData['discounts_by_type'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(discountsTypeData) && discountsTypeData.length > 0
                ? discountsTypeData.map(item => item.LoaiGiamGia || 'Chưa phân loại')
                : ['Chưa có dữ liệu'];
            const data = Array.isArray(discountsTypeData) && discountsTypeData.length > 0
                ? discountsTypeData.map(item => parseInt(item.count) || 0)
                : [0];
           
            new Chart(discountsTypeCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số mã',
                        data: data,
                        backgroundColor: '#17a2b8',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo discountsTypeChart:', error);
        }
    }
   
    // Khách hàng theo tháng
    const customersMonthlyCtx = document.getElementById('customersMonthlyChart');
    if (customersMonthlyCtx) {
        try {
            const customersMonthlyData = <?= json_encode($statsData['customers_by_month'] ?? []) ?>;
            const labels = customersMonthlyData && customersMonthlyData.length > 0 ? customersMonthlyData.map(item => item.month) : [];
            const data = customersMonthlyData && customersMonthlyData.length > 0 ? customersMonthlyData.map(item => parseInt(item.count)) : [];
           
            new Chart(customersMonthlyCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số khách hàng',
                        data: data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: { legend: { display: true, position: 'top', labels: { font: { size: 12 } } } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo customersMonthlyChart:', error);
        }
    }
   
    // Nhân viên theo tháng
    const staffMonthlyCtx = document.getElementById('staffMonthlyChart');
    if (staffMonthlyCtx) {
        try {
            const staffMonthlyData = <?= json_encode($statsData['staff_by_month'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(staffMonthlyData) && staffMonthlyData.length > 0
                ? staffMonthlyData.map(item => item.month || '')
                : [];
            const data = Array.isArray(staffMonthlyData) && staffMonthlyData.length > 0
                ? staffMonthlyData.map(item => parseInt(item.count) || 0)
                : [];
           
            if (labels.length === 0) {
                console.warn('Không có dữ liệu nhân viên theo tháng');
            }
           
            new Chart(staffMonthlyCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số nhân viên',
                        data: data,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: { legend: { display: true, position: 'top', labels: { font: { size: 12 } } } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo staffMonthlyChart:', error);
        }
    }
   
    // Bài viết theo tháng
    const postsMonthlyCtx = document.getElementById('postsMonthlyChart');
    if (postsMonthlyCtx) {
        try {
            const postsMonthlyData = <?= json_encode($statsData['posts_by_month'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(postsMonthlyData) && postsMonthlyData.length > 0
                ? postsMonthlyData.map(item => item.month || '')
                : [];
            const data = Array.isArray(postsMonthlyData) && postsMonthlyData.length > 0
                ? postsMonthlyData.map(item => parseInt(item.count) || 0)
                : [];
           
            if (labels.length === 0) {
                console.warn('Không có dữ liệu bài viết theo tháng');
            }
           
            new Chart(postsMonthlyCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số bài viết',
                        data: data,
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: { legend: { display: true, position: 'top', labels: { font: { size: 12 } } } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo postsMonthlyChart:', error);
        }
    }
   
    // Comment theo tháng
    const commentsMonthlyCtx = document.getElementById('commentsMonthlyChart');
    if (commentsMonthlyCtx) {
        try {
            const commentsMonthlyData = <?= json_encode($statsData['comments_by_month'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(commentsMonthlyData) && commentsMonthlyData.length > 0
                ? commentsMonthlyData.map(item => item.month || '')
                : [];
            const data = Array.isArray(commentsMonthlyData) && commentsMonthlyData.length > 0
                ? commentsMonthlyData.map(item => parseInt(item.count) || 0)
                : [];
           
            if (labels.length === 0) {
                console.warn('Không có dữ liệu comment theo tháng');
            }
           
            new Chart(commentsMonthlyCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số bình luận',
                        data: data,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: { legend: { display: true, position: 'top', labels: { font: { size: 12 } } } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo commentsMonthlyChart:', error);
        }
    }
   
    // Bài viết theo loại sự kiện
    const postsByTypeCtx = document.getElementById('postsByTypeChart');
    if (postsByTypeCtx) {
        try {
            const postsByTypeData = <?= json_encode($statsData['posts_by_type'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(postsByTypeData) && postsByTypeData.length > 0
                ? postsByTypeData.map(item => item.TenLoai || 'Chưa phân loại')
                : ['Chưa có dữ liệu'];
            const data = Array.isArray(postsByTypeData) && postsByTypeData.length > 0
                ? postsByTypeData.map(item => parseInt(item.count) || 0)
                : [0];
           
            new Chart(postsByTypeCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số bài viết',
                        data: data,
                        backgroundColor: '#667eea',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo postsByTypeChart:', error);
        }
    }
   
    // Top 10 bài viết có nhiều comment
    const topPostsCommentsCtx = document.getElementById('topPostsCommentsChart');
    if (topPostsCommentsCtx) {
        try {
            const topPostsCommentsData = <?= json_encode($statsData['top_posts_comments'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(topPostsCommentsData) && topPostsCommentsData.length > 0
                ? topPostsCommentsData.map(item => item.title ? (item.title.length > 30 ? item.title.substring(0, 30) + '...' : item.title) : 'Chưa có tiêu đề')
                : ['Chưa có dữ liệu'];
            const data = Array.isArray(topPostsCommentsData) && topPostsCommentsData.length > 0
                ? topPostsCommentsData.map(item => parseInt(item.comment_count) || 0)
                : [0];
           
            new Chart(topPostsCommentsCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số bình luận',
                        data: data,
                        backgroundColor: '#6f42c1',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, y: { ticks: { font: { size: 10 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo topPostsCommentsChart:', error);
        }
    }
}
<?php elseif ($userRole == 2): ?>
function initializeManagerCharts() {
    console.log('Khởi tạo biểu đồ cho Manager (Role 2)...');
   
    // Trạng thái đăng ký sự kiện
    const registrationsStatusCtx = document.getElementById('registrationsStatusChart');
    if (registrationsStatusCtx) {
        try {
            new Chart(registrationsStatusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Chờ duyệt', 'Đã duyệt', 'Từ chối'],
                    datasets: [{
                        data: [
                            <?= (int)($statsData['pending_registrations'] ?? 0) ?>,
                            <?= (int)($statsData['approved_registrations'] ?? 0) ?>,
                            <?= (int)($statsData['rejected_registrations'] ?? 0) ?>
                        ],
                        backgroundColor: ['#ffc107', '#28a745', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo registrationsStatusChart:', error);
        }
    }
   
    // Đăng ký theo loại sự kiện
    const registrationsByTypeCtx = document.getElementById('registrationsByTypeChart');
    if (registrationsByTypeCtx) {
        try {
            const registrationsByTypeData = <?= json_encode($statsData['registrations_by_type'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(registrationsByTypeData) && registrationsByTypeData.length > 0
                ? registrationsByTypeData.map(item => item.TenLoai || 'Chưa phân loại')
                : ['Chưa có dữ liệu'];
            const data = Array.isArray(registrationsByTypeData) && registrationsByTypeData.length > 0
                ? registrationsByTypeData.map(item => parseInt(item.count) || 0)
                : [0];
           
            new Chart(registrationsByTypeCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số đăng ký',
                        data: data,
                        backgroundColor: '#667eea',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo registrationsByTypeChart:', error);
        }
    }
   
    // Trạng thái địa điểm
    const locationsStatusCtx = document.getElementById('locationsStatusChart');
    if (locationsStatusCtx) {
        try {
            new Chart(locationsStatusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Hoạt động', 'Không hoạt động'],
                    datasets: [{
                        data: [
                            <?= (int)($statsData['active_locations'] ?? 0) ?>,
                            <?= (int)($statsData['inactive_locations'] ?? 0) ?>
                        ],
                        backgroundColor: ['#28a745', '#6c757d'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo locationsStatusChart:', error);
        }
    }
   
    // Trạng thái phòng
    const roomsStatusCtx = document.getElementById('roomsStatusChart');
    if (roomsStatusCtx) {
        try {
            new Chart(roomsStatusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Sẵn sàng', 'Đang sử dụng'],
                    datasets: [{
                        data: [
                            <?= (int)($statsData['available_rooms'] ?? 0) ?>,
                            <?= (int)($statsData['occupied_rooms'] ?? 0) ?>
                        ],
                        backgroundColor: ['#28a745', '#ffc107'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo roomsStatusChart:', error);
        }
    }
   
    // Trạng thái thiết bị
    const equipmentStatusCtx = document.getElementById('equipmentStatusChart');
    if (equipmentStatusCtx) {
        try {
            new Chart(equipmentStatusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Sẵn sàng', 'Đang sử dụng'],
                    datasets: [{
                        data: [
                            <?= (int)($statsData['available_equipment'] ?? 0) ?>,
                            <?= (int)($statsData['occupied_equipment'] ?? 0) ?>
                        ],
                        backgroundColor: ['#28a745', '#ffc107'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo equipmentStatusChart:', error);
        }
    }
   
    // Thiết bị theo loại
    const equipmentByTypeCtx = document.getElementById('equipmentByTypeChart');
    if (equipmentByTypeCtx) {
        try {
            const equipmentByTypeData = <?= json_encode($statsData['equipment_by_type'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(equipmentByTypeData) && equipmentByTypeData.length > 0
                ? equipmentByTypeData.map(item => item.LoaiTB || 'Chưa phân loại')
                : ['Chưa có dữ liệu'];
            const data = Array.isArray(equipmentByTypeData) && equipmentByTypeData.length > 0
                ? equipmentByTypeData.map(item => parseInt(item.count) || 0)
                : [0];
           
            new Chart(equipmentByTypeCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số thiết bị',
                        data: data,
                        backgroundColor: '#17a2b8',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo equipmentByTypeChart:', error);
        }
    }
   
    // Nhân viên theo vai trò
    const staffRoleCtx = document.getElementById('staffRoleChart');
    if (staffRoleCtx) {
        try {
            const staffRoleData = <?= json_encode($statsData['staff_by_role'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(staffRoleData) && staffRoleData.length > 0
                ? staffRoleData.map(item => item.TenRole || 'Chưa xác định')
                : ['Chưa có dữ liệu'];
            const data = Array.isArray(staffRoleData) && staffRoleData.length > 0
                ? staffRoleData.map(item => parseInt(item.count) || 0)
                : [0];
            const colors = ['#007bff', '#17a2b8', '#6f42c1', '#28a745'];
           
            new Chart(staffRoleCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, Math.max(labels.length, 1)),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo staffRoleChart:', error);
        }
    }
   
    // Trạng thái thanh toán
    const paymentsStatusCtx = document.getElementById('paymentsStatusChart');
    if (paymentsStatusCtx) {
        try {
            new Chart(paymentsStatusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Đang xử lý', 'Thành công', 'Thất bại'],
                    datasets: [{
                        data: [
                            <?= (int)($statsData['pending_payments'] ?? 0) ?>,
                            <?= (int)($statsData['completed_payments'] ?? 0) ?>,
                            <?= (int)($statsData['failed_payments'] ?? 0) ?>
                        ],
                        backgroundColor: ['#ffc107', '#28a745', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, font: { size: 12 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo paymentsStatusChart:', error);
        }
    }
   
    // Đăng ký theo tháng
    const registrationsMonthlyCtx = document.getElementById('registrationsMonthlyChart');
    if (registrationsMonthlyCtx) {
        try {
            const registrationsMonthlyData = <?= json_encode($statsData['registrations_by_month'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(registrationsMonthlyData) && registrationsMonthlyData.length > 0
                ? registrationsMonthlyData.map(item => item.month || '')
                : [];
            const data = Array.isArray(registrationsMonthlyData) && registrationsMonthlyData.length > 0
                ? registrationsMonthlyData.map(item => parseInt(item.count) || 0)
                : [];
           
            if (labels.length === 0) {
                console.warn('Không có dữ liệu đăng ký theo tháng');
            }
           
            new Chart(registrationsMonthlyCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số đăng ký',
                        data: data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: { legend: { display: true, position: 'top', labels: { font: { size: 12 } } } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo registrationsMonthlyChart:', error);
        }
    }
   
    // Doanh thu theo tháng
    const revenueMonthlyCtx = document.getElementById('revenueMonthlyChart');
    if (revenueMonthlyCtx) {
        try {
            const paymentsMonthlyData = <?= json_encode($statsData['payments_by_month'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(paymentsMonthlyData) && paymentsMonthlyData.length > 0
                ? paymentsMonthlyData.map(item => item.month || '')
                : [];
            const data = Array.isArray(paymentsMonthlyData) && paymentsMonthlyData.length > 0
                ? paymentsMonthlyData.map(item => parseFloat(item.revenue || 0))
                : [];
           
            if (labels.length === 0) {
                console.warn('Không có dữ liệu doanh thu theo tháng');
            }
           
            new Chart(revenueMonthlyCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: data,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: {
                        legend: { display: true, position: 'top', labels: { font: { size: 12 } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1000000,
                                font: { size: 11 },
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN').format(value) + ' VNĐ';
                                }
                            }
                        },
                        x: { ticks: { font: { size: 11 } } }
                    }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo revenueMonthlyChart:', error);
        }
    }
   
    // Khách hàng theo tháng
    const customersMonthlyCtx = document.getElementById('customersMonthlyChart');
    if (customersMonthlyCtx) {
        try {
            const customersMonthlyData = <?= json_encode($statsData['customers_by_month'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
            const labels = Array.isArray(customersMonthlyData) && customersMonthlyData.length > 0
                ? customersMonthlyData.map(item => item.month || '')
                : [];
            const data = Array.isArray(customersMonthlyData) && customersMonthlyData.length > 0
                ? customersMonthlyData.map(item => parseInt(item.count) || 0)
                : [];
           
            if (labels.length === 0) {
                console.warn('Không có dữ liệu khách hàng theo tháng');
            }
           
            new Chart(customersMonthlyCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Số khách hàng',
                        data: data,
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: { legend: { display: true, position: 'top', labels: { font: { size: 12 } } } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } } }, x: { ticks: { font: { size: 11 } } } }
                }
            });
        } catch (error) {
            console.error('Lỗi khi tạo customersMonthlyChart:', error);
        }
    }
}
<?php endif; ?>
</script>
<?php include 'includes/admin-footer.php'; ?>