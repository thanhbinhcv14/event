<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has role 2
if (!isLoggedIn() || $_SESSION['user']['ID_Role'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $pdo = getDBConnection();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_manager_info':
            getManagerInfo($pdo);
            break;
            
        case 'get_progress_reports':
            getProgressReports($pdo);
            break;
            
        case 'get_issue_reports':
            getIssueReports($pdo);
            break;
            
        case 'get_statistics':
            getStatistics($pdo);
            break;
            
        case 'update_report_status':
            updateReportStatus($pdo);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Manager Reports Controller Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getManagerInfo($pdo) {
    try {
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
        $managerInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$managerInfo) {
            throw new Exception("Manager not found");
        }
        
        echo json_encode(['success' => true, 'data' => $managerInfo]);
        
    } catch (Exception $e) {
        error_log("Error getting manager info: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getProgressReports($pdo) {
    try {
        $userId = $_SESSION['user']['ID_User'];
        
        // Get manager ID
        $stmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $managerId = $stmt->fetchColumn();
        
        if (!$managerId) {
            throw new Exception("Manager not found");
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                bct.ID_BaoCao,
                bct.TienDo,
                bct.GhiChu,
                bct.TrangThai,
                bct.NgayBaoCao,
                nv.HoTen as TenNhanVien,
                nv.ChucVu as ChucVuNhanVien,
                CASE 
                    WHEN bct.LoaiTask = 'lichlamviec' THEN llv.NhiemVu
                    WHEN bct.LoaiTask = 'chitietkehoach' THEN ctk.TenBuoc
                END as TenCongViec,
                dl.TenSuKien,
                dl.NgayBatDau,
                dl.NgayKetThuc
            FROM baocaotiendo bct
            LEFT JOIN nhanvieninfo nv ON bct.ID_NhanVien = nv.ID_NhanVien
            LEFT JOIN lichlamviec llv ON bct.ID_Task = llv.ID_LLV AND bct.LoaiTask = 'lichlamviec'
            LEFT JOIN chitietkehoach ctk ON bct.ID_Task = ctk.ID_ChiTiet AND bct.LoaiTask = 'chitietkehoach'
            LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
            LEFT JOIN datlichsukien dl ON COALESCE(llv.ID_DatLich, kht.ID_DatLich) = dl.ID_DatLich
            WHERE bct.ID_QuanLy = ?
            ORDER BY bct.NgayBaoCao DESC
            LIMIT 50
        ");
        $stmt->execute([$managerId]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $reports]);
        
    } catch (Exception $e) {
        error_log("Error getting progress reports: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getIssueReports($pdo) {
    try {
        $userId = $_SESSION['user']['ID_User'];
        
        // Get manager ID
        $stmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $managerId = $stmt->fetchColumn();
        
        if (!$managerId) {
            throw new Exception("Manager not found");
        }
        
        // Create table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS baocaosuco (
                ID_BaoCao INT AUTO_INCREMENT PRIMARY KEY,
                ID_NhanVien INT NOT NULL,
                ID_QuanLy INT NOT NULL,
                ID_Task INT NOT NULL,
                LoaiTask ENUM('lichlamviec', 'chitietkehoach') NOT NULL,
                TieuDe VARCHAR(255) NOT NULL,
                MoTa TEXT,
                MucDo ENUM('Thấp', 'Trung bình', 'Cao', 'Khẩn cấp') DEFAULT 'Trung bình',
                TrangThai ENUM('Mới', 'Đang xử lý', 'Đã xử lý', 'Đã đóng') DEFAULT 'Mới',
                NgayBaoCao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                NgayCapNhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (ID_NhanVien) REFERENCES nhanvieninfo(ID_NhanVien),
                FOREIGN KEY (ID_QuanLy) REFERENCES nhanvieninfo(ID_NhanVien)
            )
        ");
        
        $stmt = $pdo->prepare("
            SELECT 
                bs.ID_BaoCao,
                bs.TieuDe,
                bs.MoTa,
                bs.MucDo,
                bs.TrangThai,
                bs.NgayBaoCao,
                bs.NgayCapNhat,
                nv.HoTen as TenNhanVien,
                nv.ChucVu as ChucVuNhanVien,
                CASE 
                    WHEN bs.LoaiTask = 'lichlamviec' THEN llv.NhiemVu
                    WHEN bs.LoaiTask = 'chitietkehoach' THEN ctk.TenBuoc
                END as TenCongViec,
                dl.TenSuKien,
                dl.NgayBatDau,
                dl.NgayKetThuc
            FROM baocaosuco bs
            LEFT JOIN nhanvieninfo nv ON bs.ID_NhanVien = nv.ID_NhanVien
            LEFT JOIN lichlamviec llv ON bs.ID_Task = llv.ID_LLV AND bs.LoaiTask = 'lichlamviec'
            LEFT JOIN chitietkehoach ctk ON bs.ID_Task = ctk.ID_ChiTiet AND bs.LoaiTask = 'chitietkehoach'
            LEFT JOIN kehoachthuchien kht ON ctk.ID_KeHoach = kht.ID_KeHoach
            LEFT JOIN datlichsukien dl ON COALESCE(llv.ID_DatLich, kht.ID_DatLich) = dl.ID_DatLich
            WHERE bs.ID_QuanLy = ?
            ORDER BY bs.NgayBaoCao DESC
            LIMIT 50
        ");
        $stmt->execute([$managerId]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $reports]);
        
    } catch (Exception $e) {
        error_log("Error getting issue reports: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getStatistics($pdo) {
    try {
        $userId = $_SESSION['user']['ID_User'];
        
        // Get manager ID
        $stmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $managerId = $stmt->fetchColumn();
        
        if (!$managerId) {
            throw new Exception("Manager not found");
        }
        
        // Progress reports statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_progress_reports,
                COUNT(DISTINCT bct.ID_NhanVien) as total_staff_progress,
                AVG(bct.TienDo) as avg_progress,
                SUM(CASE WHEN bct.TrangThai = 'Hoàn thành' THEN 1 ELSE 0 END) as completed_tasks
            FROM baocaotiendo bct
            WHERE bct.ID_QuanLy = ?
            AND bct.NgayBaoCao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$managerId]);
        $progressStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Issue reports statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_issue_reports,
                COUNT(DISTINCT bs.ID_NhanVien) as total_staff_issues,
                SUM(CASE WHEN bs.TrangThai = 'Mới' THEN 1 ELSE 0 END) as new_issues,
                SUM(CASE WHEN bs.TrangThai = 'Đang xử lý' THEN 1 ELSE 0 END) as in_progress_issues,
                SUM(CASE WHEN bs.TrangThai = 'Đã xử lý' THEN 1 ELSE 0 END) as resolved_issues,
                SUM(CASE WHEN bs.MucDo = 'Khẩn cấp' THEN 1 ELSE 0 END) as urgent_issues
            FROM baocaosuco bs
            WHERE bs.ID_QuanLy = ?
            AND bs.NgayBaoCao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$managerId]);
        $issueStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats = array_merge($progressStats, $issueStats);
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (Exception $e) {
        error_log("Error getting statistics: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateReportStatus($pdo) {
    try {
        $reportId = $_POST['reportId'] ?? '';
        $status = $_POST['status'] ?? '';
        $reportType = $_POST['reportType'] ?? 'progress'; // 'progress' or 'issue'
        
        if (empty($reportId) || empty($status)) {
            throw new Exception("Missing required parameters");
        }
        
        $userId = $_SESSION['user']['ID_User'];
        
        // Get manager ID
        $stmt = $pdo->prepare("SELECT ID_NhanVien FROM nhanvieninfo WHERE ID_User = ?");
        $stmt->execute([$userId]);
        $managerId = $stmt->fetchColumn();
        
        if (!$managerId) {
            throw new Exception("Manager not found");
        }
        
        if ($reportType === 'issue') {
            $stmt = $pdo->prepare("
                UPDATE baocaosuco 
                SET TrangThai = ?, NgayCapNhat = NOW()
                WHERE ID_BaoCao = ? AND ID_QuanLy = ?
            ");
            $stmt->execute([$status, $reportId, $managerId]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE baocaotiendo 
                SET TrangThai = ?, NgayCapNhat = NOW()
                WHERE ID_BaoCao = ? AND ID_QuanLy = ?
            ");
            $stmt->execute([$status, $reportId, $managerId]);
        }
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            throw new Exception("No report found or unauthorized access");
        }
        
    } catch (Exception $e) {
        error_log("Error updating report status: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
